<?php

namespace App\Services\Reports;

use App\Enum\AdsRequestStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdsRequestedReportService
{
    private const ADS_DEADLINE_HOURS = 24;
    private const AUTO_REUSE_DESCRIPTION = 'Solicitação automática concluída com reaproveitamento de ADS já disponível.';
    private const AUTO_QUEUE_DESCRIPTION = 'Solicitação automática gerada por ADS tácita.';

    private const ACTIVE_STATUSES = [
        AdsRequestStatus::QUEUED,
        AdsRequestStatus::IN_PROGRESS,
        AdsRequestStatus::RETRY,
    ];

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $rows = $this->buildQuery($filters)->paginate($perPage);

        $rows->setCollection(
            $rows->getCollection()->map(fn ($row) => $this->enrichRow($row))
        );

        return $rows;
    }

    public function paginateQueue(array $filters, int $perPage = 20, string $pageName = 'queue_page'): LengthAwarePaginator
    {
        $rows = $this->buildQueueQuery($filters)->paginate($perPage, ['*'], $pageName);

        $rows->setCollection(
            $rows->getCollection()->map(fn ($row) => $this->enrichRow($row))
        );

        return $rows;
    }

    /**
     * @return array{
     *   opened_count:int,
     *   opened_daily_avg:float,
     *   delivered_daily_avg:float,
     *   delivered_avg_hours:float,
     *   delivered_avg_label:string,
     *   in_progress_now_count:int,
     *   period_days:int,
     *   period_start:string,
     *   period_end:string,
     *   amount_total:float,
     *   amount_daily_avg:float
     * }
     */
    public function summarize(array $filters): array
    {
        [$start, $end] = $this->resolveDateRange($filters);
        $periodDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);

        $baseFilters = $filters;
        $baseFilters['statusFilter'] = 'all';
        $baseFilters['status_exact'] = '';

        $openedCount = (int) $this->buildNowBaseQuery($baseFilters, false)
            ->whereBetween('ar.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();

        $deliveredTotalCount = (int) $this->buildNowBaseQuery($baseFilters, false)
            ->where('ar.status', AdsRequestStatus::DONE->value)
            ->whereNotNull('ar.completed_at')
            ->whereBetween('ar.completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();

        $amountTotal = (float) DB::table('adsforms as af')
            ->whereBetween('af.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum('af.amount');

        $deliveredTotalHours = 0.0;
        $deliveredCount = 0;

        foreach (
            $this->buildNowBaseQuery($baseFilters, false)
                ->selectRaw('ar.created_at as requested_at, ar.completed_at, ar.delivered_at, ar.status')
                ->where('ar.status', AdsRequestStatus::DONE->value)
                ->whereNotNull('ar.completed_at')
                ->whereBetween('ar.completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->cursor() as $row
        ) {
            $requestedAt = $this->asCarbon($row->requested_at ?? null);
            $deliveredAt = $this->resolveDeliveredAt($row);

            if ($requestedAt && $deliveredAt && $deliveredAt->greaterThan($requestedAt)) {
                $deliveredTotalHours += $requestedAt->diffInSeconds($deliveredAt) / 3600;
                $deliveredCount++;
            }
        }

        $dailyAvg = $periodDays > 0 ? $openedCount / $periodDays : 0.0;
        $deliveredDailyAvg = $periodDays > 0 ? $deliveredTotalCount / $periodDays : 0.0;
        $amountDailyAvg = $periodDays > 0 ? $amountTotal / $periodDays : 0.0;
        $avgHours = $deliveredCount > 0 ? $deliveredTotalHours / $deliveredCount : 0.0;

        $filtersForExecution = $baseFilters;
        $filtersForExecution['statusFilter'] = 'all';

        $inProgressNowCount = $this->buildNowBaseQuery($filtersForExecution, false)
            ->where('ar.status', AdsRequestStatus::IN_PROGRESS->value)
            ->count();

        return [
            'opened_count' => $openedCount,
            'opened_daily_avg' => round($dailyAvg, 2),
            'delivered_daily_avg' => round($deliveredDailyAvg, 2),
            'delivered_avg_hours' => round($avgHours, 2),
            'delivered_avg_label' => $this->formatDuration((int) round($avgHours * 3600)),
            'in_progress_now_count' => $inProgressNowCount,
            'period_days' => $periodDays,
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
            'amount_total' => round($amountTotal, 2),
            'amount_daily_avg' => round($amountDailyAvg, 2),
        ];
    }

    /**
     * @return array{
     *   labels:array<int,string>,
     *   date_keys:array<int,string>,
     *   requested:array<int,int>,
     *   delivered:array<int,int>,
     *   open_backlog:array<int,int>,
     *   overdue_backlog:array<int,int>,
     *   analytics:array{
     *     requested_total:int,
     *     delivered_total:int,
     *     completion_rate:float,
     *     backlog_avg:float,
     *     backlog_peak:int,
     *     overdue_avg:float,
     *     current_open:int,
     *     current_overdue:int
     *   }
     * }
     */
    public function demandVsDeliverySeries(array $filters): array
    {
        [$start, $end] = $this->resolveDateRange($filters);
        $granularity = $this->resolveChartGranularity($filters, $start, $end);
        $hasRequestDateScope = filled($filters['date_in'] ?? null) || filled($filters['date_out'] ?? null);
        $requested = [];
        $delivered = [];
        $openBacklog = [];
        $overdueBacklog = [];
        $labels = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $requested[$key] = 0;
            $delivered[$key] = 0;
            $openBacklog[$key] = 0;
            $overdueBacklog[$key] = 0;
            $labels[$key] = $cursor->format('d/m');
            $cursor->addDay();
        }

        $requestedBaseQuery = $this->buildNowBaseQuery($filters, false)
            ->whereNotIn('ar.status', [
                AdsRequestStatus::CANCELED->value,
                AdsRequestStatus::FAILED->value,
            ]);
        if ($hasRequestDateScope) {
            $requestedBaseQuery->whereBetween('ar.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
        }

        $requestedRows = (clone $requestedBaseQuery)
            ->selectRaw('DATE(ar.created_at) as ref_date, COUNT(*) as total')
            ->whereBetween('ar.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('ref_date')
            ->get();

        foreach ($requestedRows as $row) {
            $dateKey = (string) ($row->ref_date ?? '');
            if (array_key_exists($dateKey, $requested)) {
                $requested[$dateKey] = (int) ($row->total ?? 0);
            }
        }

        $terminalRowsBaseQuery = $this->buildNowBaseQuery($filters, false)
            ->where('ar.status', AdsRequestStatus::DONE->value)
            ->whereNotNull(DB::raw('COALESCE(ar.delivered_at, ar.completed_at)'));

        if ($hasRequestDateScope) {
            $terminalRowsBaseQuery->whereBetween('ar.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
        }

        $deliveredRows = (clone $terminalRowsBaseQuery)
            ->selectRaw('DATE(COALESCE(ar.delivered_at, ar.completed_at)) as ref_date, COUNT(*) as total')
            ->whereBetween(DB::raw('COALESCE(ar.delivered_at, ar.completed_at)'), [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('ref_date')
            ->get();

        foreach ($deliveredRows as $row) {
            $dateKey = (string) ($row->ref_date ?? '');
            if (array_key_exists($dateKey, $delivered)) {
                $delivered[$dateKey] = (int) ($row->total ?? 0);
            }
        }

        $openingBacklog = 0;
        if (!$hasRequestDateScope) {
            $openingBacklog = (int) $this->buildNowBaseQuery($filters, false)
                ->whereIn(
                    'ar.status',
                    array_map(static fn (AdsRequestStatus $status) => $status->value, self::ACTIVE_STATUSES)
                )
                ->where('ar.created_at', '<', $start->copy()->startOfDay())
                ->count();
        }

        $runningBacklog = $openingBacklog;
        foreach (array_keys($labels) as $key) {
            $runningBacklog += (int) ($requested[$key] ?? 0);
            $runningBacklog -= (int) ($delivered[$key] ?? 0);
            $openBacklog[$key] = max(0, $runningBacklog);
        }

        $overdueDeliveredRows = (clone $terminalRowsBaseQuery)
            ->whereRaw(
                'TIMESTAMPDIFF(HOUR, ar.created_at, COALESCE(ar.delivered_at, ar.completed_at)) > ?',
                [self::ADS_DEADLINE_HOURS]
            )
            ->selectRaw('DATE(COALESCE(ar.delivered_at, ar.completed_at)) as ref_date, COUNT(*) as total')
            ->whereBetween(DB::raw('COALESCE(ar.delivered_at, ar.completed_at)'), [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('ref_date')
            ->get();

        foreach ($overdueDeliveredRows as $row) {
            $dateKey = (string) ($row->ref_date ?? '');
            if (array_key_exists($dateKey, $overdueBacklog)) {
                $overdueBacklog[$dateKey] = (int) ($row->total ?? 0);
            }
        }

        $orderedKeys = array_keys($labels);
        $requestedSeries = array_map(fn ($key) => (int) $requested[$key], $orderedKeys);
        $deliveredSeries = array_map(fn ($key) => (int) $delivered[$key], $orderedKeys);
        $openSeries = array_map(fn ($key) => (int) $openBacklog[$key], $orderedKeys);
        $overdueSeries = array_map(fn ($key) => (int) $overdueBacklog[$key], $orderedKeys);

        if ($granularity === 'month') {
            $requestedByMonth = [];
            $deliveredByMonth = [];
            $openByMonth = [];
            $overdueByMonth = [];
            $labelsByMonth = [];

            foreach ($orderedKeys as $index => $dayKey) {
                $day = Carbon::createFromFormat('Y-m-d', $dayKey);
                $monthKey = $day->format('Y-m');
                if (!isset($requestedByMonth[$monthKey])) {
                    $requestedByMonth[$monthKey] = 0;
                    $deliveredByMonth[$monthKey] = 0;
                    $openByMonth[$monthKey] = 0;
                    $overdueByMonth[$monthKey] = 0;
                    $labelsByMonth[$monthKey] = $day->format('m/Y');
                }

                $requestedByMonth[$monthKey] += (int) ($requestedSeries[$index] ?? 0);
                $deliveredByMonth[$monthKey] += (int) ($deliveredSeries[$index] ?? 0);
                // backlog acumulado no fim do mês (último dia processado do mês)
                $openByMonth[$monthKey] = (int) ($openSeries[$index] ?? 0);
                $overdueByMonth[$monthKey] += (int) ($overdueSeries[$index] ?? 0);
            }

            $orderedKeys = array_keys($labelsByMonth);
            $requestedSeries = array_map(fn ($key) => (int) ($requestedByMonth[$key] ?? 0), $orderedKeys);
            $deliveredSeries = array_map(fn ($key) => (int) ($deliveredByMonth[$key] ?? 0), $orderedKeys);
            $openSeries = array_map(fn ($key) => (int) ($openByMonth[$key] ?? 0), $orderedKeys);
            $overdueSeries = array_map(fn ($key) => (int) ($overdueByMonth[$key] ?? 0), $orderedKeys);
            $labels = $labelsByMonth;
        }

        $requestedTotal = (int) array_sum($requestedSeries);
        $deliveredTotal = (int) array_sum($deliveredSeries);
        $completionRate = $requestedTotal > 0
            ? round(($deliveredTotal / $requestedTotal) * 100, 1)
            : 0.0;
        $backlogAvg = !empty($openSeries) ? round(array_sum($openSeries) / count($openSeries), 1) : 0.0;
        $backlogPeak = !empty($openSeries) ? (int) max($openSeries) : 0;
        $overdueAvg = !empty($overdueSeries) ? round(array_sum($overdueSeries) / count($overdueSeries), 1) : 0.0;
        $currentOpen = !empty($openSeries) ? (int) end($openSeries) : 0;
        $currentOverdue = !empty($overdueSeries) ? (int) end($overdueSeries) : 0;

        return [
            'labels' => array_map(fn ($key) => $labels[$key], $orderedKeys),
            'date_keys' => $orderedKeys,
            'requested' => $requestedSeries,
            'delivered' => $deliveredSeries,
            'open_backlog' => $openSeries,
            'overdue_backlog' => $overdueSeries,
            'bucket' => $granularity,
            'bucket_label' => $granularity === 'month' ? 'mensal' : 'diária',
            'analytics' => [
                'requested_total' => $requestedTotal,
                'delivered_total' => $deliveredTotal,
                'completion_rate' => $completionRate,
                'backlog_avg' => $backlogAvg,
                'backlog_peak' => $backlogPeak,
                'overdue_avg' => $overdueAvg,
                'current_open' => $currentOpen,
                'current_overdue' => $currentOverdue,
            ],
        ];
    }

    /**
     * @return array{labels:array<int,string>,status_keys:array<int,string>,values:array<int,int>,colors:array<int,string>,total:int}
     */
    public function queueDonutSeries(array $filters): array
    {
        $preferLocalQueue = (bool) ($filters['prefer_local_queue'] ?? false);

        if ($preferLocalQueue) {
            $rows = $this->buildNowBaseQuery($filters, false)
                ->where('ar.status', '!=', AdsRequestStatus::DONE->value)
                ->where('ar.status', '!=', AdsRequestStatus::CANCELED->value)
                ->where('ar.status', '!=', AdsRequestStatus::FAILED->value)
                ->selectRaw('ar.status, COUNT(*) as total')
                ->groupBy('ar.status')
                ->get();
        } else {
            try {
                $rows = $this->buildSqlQueueBaseQuery($filters)
                    ->selectRaw('ar.status, COUNT(*) as total')
                    ->groupBy('ar.status')
                    ->get();
            } catch (\Throwable $e) {
                // Fallback para base local quando sqlsrv2 estiver indisponível/timeout.
                $rows = $this->buildNowBaseQuery($filters, false)
                    ->where('ar.status', '!=', AdsRequestStatus::DONE->value)
                    ->where('ar.status', '!=', AdsRequestStatus::CANCELED->value)
                    ->where('ar.status', '!=', AdsRequestStatus::FAILED->value)
                    ->selectRaw('ar.status, COUNT(*) as total')
                    ->groupBy('ar.status')
                    ->get();
            }
        }

        $labels = [];
        $statusKeys = [];
        $values = [];
        $colors = [];
        $total = 0;

        foreach ($rows as $row) {
            $status = (string) ($row->status ?? '');
            $count = (int) ($row->total ?? 0);
            $enum = $this->resolveAdsStatusEnum($status);
            $label = $enum?->label() ?? $status;
            if ($enum === AdsRequestStatus::IN_PROGRESS) {
                $label = 'Em execução';
            }
            $labels[] = $label;
            $statusKeys[] = $status;
            $values[] = $count;
            $colors[] = match ($enum) {
                AdsRequestStatus::QUEUED => 'rgba(107,114,128,0.9)',
                AdsRequestStatus::IN_PROGRESS => 'rgba(14,165,233,0.9)',
                AdsRequestStatus::RETRY => 'rgba(245,158,11,0.9)',
                AdsRequestStatus::FAILED => 'rgba(220,38,38,0.9)',
                AdsRequestStatus::CANCELED => 'rgba(55,65,81,0.9)',
                default => 'rgba(99,102,241,0.9)',
            };
            $total += $count;
        }

        return [
            'labels' => $labels,
            'status_keys' => $statusKeys,
            'values' => $values,
            'colors' => $colors,
            'total' => $total,
        ];
    }

    /**
     * @return array{
     *   labels:array<int,string>,
     *   values:array<int,int>,
     *   colors:array<int,string>,
     *   total:int,
     *   reused:int,
     *   queued:int,
     *   reuse_rate:float
     * }
     */
    public function reuseEconomyDonutSeries(array $filters): array
    {
        // Mantem o recorte de periodo/empresa/busca, mas ignora filtros de status para
        // representar corretamente o volume de automacoes (reaproveitadas x enfileiradas).
        $baseFilters = $filters;
        $baseFilters['statusFilter'] = 'all';
        $baseFilters['status_exact'] = '';

        $query = $this->buildNowBaseQuery($baseFilters, false);

        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;
        $completedIn = $filters['completed_in'] ?? null;
        $completedOut = $filters['completed_out'] ?? null;

        if ($dateIn) {
            $query->whereDate('ar.created_at', '>=', $dateIn);
        }

        if ($dateOut) {
            $query->whereDate('ar.created_at', '<=', $dateOut);
        }

        if ($completedIn) {
            $query->whereDate('ar.completed_at', '>=', $completedIn);
        }

        if ($completedOut) {
            $query->whereDate('ar.completed_at', '<=', $completedOut);
        }

        $reused = (int) (clone $query)
            ->where('ar.description', 'like', self::AUTO_REUSE_DESCRIPTION . '%')
            ->count();

        $queued = (int) (clone $query)
            ->where('ar.description', 'like', self::AUTO_QUEUE_DESCRIPTION . '%')
            ->count();

        $total = $reused + $queued;
        $reuseRate = $total > 0 ? round(($reused / $total) * 100, 1) : 0.0;

        return [
            'labels' => ['Solicitações Reaproveitadas', 'Novas Solicitações'],
            'values' => [$reused, $queued],
            'colors' => ['rgba(5,150,105,0.85)', 'rgba(59,130,246,0.8)'],
            'total' => $total,
            'reused' => $reused,
            'queued' => $queued,
            'reuse_rate' => $reuseRate,
        ];
    }

    public function buildQuery(array $filters)
    {
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;
        $completedIn = $filters['completed_in'] ?? null;
        $completedOut = $filters['completed_out'] ?? null;

        $query = $this->buildNowBaseQuery($filters, true)
            ->select([
                'ar.id',
                'ar.note_id',
                'ar.status',
                'ar.description',
                'ar.url',
                'ar.requested_by',
                'ar.created_at as requested_at',
                'ar.completed_at',
                'ar.delivered_at',
                'n.note as note_number',
                DB::raw('COALESCE(c.name, "—") as company_name'),
                DB::raw('COALESCE(u.name, "—") as recipient_name'),
                DB::raw('CASE WHEN EXISTS (
                    SELECT 1
                    FROM adsforms af
                    WHERE af.note_id = ar.note_id
                      AND af.tacit_due_at IS NOT NULL
                      AND ar.created_at > af.tacit_due_at
                ) THEN 1 ELSE 0 END as tacit_after_due'),
            ])
            ->orderByDesc('ar.created_at');

        if ($dateIn) {
            $query->whereDate('ar.created_at', '>=', $dateIn);
        }

        if ($dateOut) {
            $query->whereDate('ar.created_at', '<=', $dateOut);
        }

        if ($completedIn) {
            $query->whereDate('ar.completed_at', '>=', $completedIn);
        }

        if ($completedOut) {
            $query->whereDate('ar.completed_at', '<=', $completedOut);
        }

        return $query;
    }

    public function buildQueueQuery(array $filters)
    {
        return $this->buildSqlQueueBaseQuery($filters)
            ->select([
                'ar.id',
                DB::raw('NULL as note_id'),
                'ar.status',
                'ar.description',
                'ar.url',
                DB::raw('NULL as requested_by'),
                'ar.created_at as requested_at',
                'ar.completed_at',
                DB::raw('NULL as delivered_at'),
                DB::raw("COALESCE(ar.note, N'—') as note_number"),
                DB::raw("COALESCE(ar.company, N'—') as company_name"),
                DB::raw("COALESCE(ar.[user], N'—') as recipient_name"),
                DB::raw('0 as tacit_after_due'),
            ])->whereNotNull('ar.id')
            ->orderByDesc('ar.created_at');
    }

    private function buildSqlQueueBaseQuery(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $companyIds = collect($filters['companyIds'] ?? [])->filter()->values();
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;
        $statusExact = trim((string) ($filters['status_exact'] ?? ''));

        $query = DB::connection('sqlsrv2')
            ->table('dbo.ads_requests as ar')
            ->where('ar.status', '!=', AdsRequestStatus::DONE->value)
            ->where('ar.status', '!=', AdsRequestStatus::CANCELED->value)
            ->where('ar.status', '!=', AdsRequestStatus::FAILED->value);

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('ar.note', 'like', "%{$search}%")
                    ->orWhere('ar.company', 'like', "%{$search}%")
                    ->orWhere('ar.status', 'like', "%{$search}%")
                    ->orWhere('ar.url', 'like', "%{$search}%")
                    ->orWhere('ar.description', 'like', "%{$search}%")
                    ->orWhereRaw('CAST(ar.id as NVARCHAR(50)) like ?', ["%{$search}%"]);
            });
        }

        if ($companyIds->isNotEmpty()) {
            $companyNames = DB::table('companies')
                ->whereIn('id', $companyIds->all())
                ->pluck('name')
                ->filter()
                ->values()
                ->all();

            if (!empty($companyNames)) {
                $query->whereIn('ar.company', $companyNames);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($dateIn) {
            $query->whereDate('ar.created_at', '>=', $dateIn);
        }

        if ($dateOut) {
            $query->whereDate('ar.created_at', '<=', $dateOut);
        }

        if ($statusExact !== '') {
            $query->where('ar.status', $statusExact);
        }

        return $query;
    }

    public function enrichRow(object $row): array
    {
        $status = $this->resolveAdsStatusEnum((string) ($row->status ?? ''));
        $requestedAt = $this->asCarbon($row->requested_at ?? null);
        $deliveredAt = $this->resolveDeliveredAt($row);
        $deadlineAt = $requestedAt?->copy()->addHours(self::ADS_DEADLINE_HOURS);
        $referenceAt = $deliveredAt ?: now();
        $seconds = ($requestedAt && $referenceAt && $referenceAt->greaterThan($requestedAt))
            ? $requestedAt->diffInSeconds($referenceAt)
            : 0;
        $deadlineDiffSeconds = $deadlineAt ? now()->diffInSeconds($deadlineAt, false) : null;
        $deadlineDiffDays = is_null($deadlineDiffSeconds) ? null : (int) floor($deadlineDiffSeconds / 86400);
        $deadlineLabel = '—';
        if ($deadlineAt) {
            if ($deliveredAt) {
                $lateSeconds = $deadlineAt->diffInSeconds($deliveredAt, false);
                $deadlineLabel = $lateSeconds > 0
                    ? 'Entregue com atraso de ' . $this->formatDuration($lateSeconds)
                    : 'Entregue no prazo';
            } elseif ($deadlineDiffSeconds < 0) {
                $deadlineLabel = 'Vencido há ' . $this->formatDuration(abs($deadlineDiffSeconds));
            } elseif ($deadlineDiffSeconds === 0) {
                $deadlineLabel = 'Vence agora';
            } else {
                $deadlineLabel = 'Faltam ' . $this->formatDuration($deadlineDiffSeconds);
            }
        }

        return [
            'id' => (int) $row->id,
            'note_number' => (string) ($row->note_number ?? $row->note_id ?? '—'),
            'company_name' => (string) ($row->company_name ?? '—'),
            'recipient_name' => (string) ($row->recipient_name ?? '—'),
            'status_value' => (string) ($row->status ?? ''),
            'status_label' => $status === AdsRequestStatus::IN_PROGRESS
                ? 'Em execução'
                : ($status?->label() ?? (string) ($row->status ?? '—')),
            'status_badge' => $status?->badgeClass() ?? 'text-bg-secondary',
            'is_tacit' => $this->resolveTacitFlag($row),
            'description' => (string) ($row->description ?? '—'),
            'url' => $row->url ?? null,
            'requested_at' => $requestedAt,
            'delivered_at' => $deliveredAt,
            'deadline_at' => $deadlineAt,
            'deadline_days_left' => $deadlineDiffDays,
            'deadline_label' => $deadlineLabel,
            'elapsed_seconds' => $seconds,
            'elapsed_label' => $this->formatDuration($seconds),
        ];
    }

    private function buildNowBaseQuery(array $filters, bool $applyStatusFilter = false)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $companyIds = $filters['companyIds'] ?? [];
        $statusFilter = (string) ($filters['statusFilter'] ?? 'all');
        $statusExact = trim((string) ($filters['status_exact'] ?? ''));
        $excludeStatuses = collect($filters['exclude_statuses'] ?? [])
            ->map(fn ($status) => mb_strtoupper(trim((string) $status)))
            ->filter()
            ->values()
            ->all();

        $query = DB::table('ads_requests as ar')
            ->leftJoin('notes as n', 'n.id', '=', 'ar.note_id')
            ->leftJoin('companies as c', 'c.id', '=', 'ar.company_id')
            ->leftJoin('users as u', 'u.id', '=', 'ar.requested_by');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('n.note', 'like', "%{$search}%")
                    ->orWhere('ar.id', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%")
                    ->orWhere('ar.status', 'like', "%{$search}%")
                    ->orWhere('ar.url', 'like', "%{$search}%");
            });
        }

        if (!empty($companyIds)) {
            $query->whereIn('ar.company_id', $companyIds);
        }

        if ($statusExact !== '') {
            $query->where('ar.status', $statusExact);
        } elseif (!empty($excludeStatuses)) {
            $query->whereNotIn('ar.status', $excludeStatuses);
        }

        if ($applyStatusFilter) {
            $this->applyStatusFilter($query, $statusFilter);
        }

        return $query;
    }

    private function applyStatusFilter($query, string $statusFilter): void
    {
        if ($statusFilter === 'active') {
            $query->whereIn(
                'ar.status',
                array_map(static fn (AdsRequestStatus $status) => $status->value, self::ACTIVE_STATUSES)
            );

            return;
        }

        if ($statusFilter === 'delivered') {
            $query->where('ar.status', AdsRequestStatus::DONE->value);
        }
    }

    private function resolveDeliveredAt(object $row): ?Carbon
    {
        $status = $this->resolveAdsStatusEnum((string) ($row->status ?? ''));
        $delivered = $this->asCarbon($row->delivered_at ?? null);
        if ($delivered) {
            return $delivered;
        }

        if ($status === AdsRequestStatus::DONE) {
            return $this->asCarbon($row->completed_at ?? null);
        }

        return null;
    }

    private function resolveTacitFlag(object $row): bool
    {
        return !empty($row->tacit_after_due);
    }

    private function resolvePeriodDays(array $filters): int
    {
        [$start, $end] = $this->resolveDateRange($filters);
        return max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolveDateRange(array $filters): array
    {
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;
        $completedIn = $filters['completed_in'] ?? null;
        $completedOut = $filters['completed_out'] ?? null;
        $chartPeriod = strtolower(trim((string) ($filters['chart_period'] ?? '')));

        $startRef = $dateIn ?: $completedIn;
        $endRef = $dateOut ?: $completedOut;

        if ($startRef && $endRef) {
            $start = Carbon::parse($startRef)->startOfDay();
            $end = Carbon::parse($endRef)->endOfDay();
        } else {
            $anchor = $endRef ? Carbon::parse($endRef)->startOfDay() : now()->startOfDay();
            if ($chartPeriod === '12m') {
                $start = $anchor->copy()->subMonthsNoOverflow(11)->startOfMonth();
                $end = $anchor->copy()->endOfDay();
            } elseif ($chartPeriod === '30d') {
                $start = $anchor->copy()->subDays(29)->startOfDay();
                $end = $anchor->copy()->endOfDay();
            } else {
                // default compat: 7 dias
                $start = $anchor->copy()->subDays(6)->startOfDay();
                $end = $anchor->copy()->endOfDay();
            }
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function resolveChartGranularity(array $filters, Carbon $start, Carbon $end): string
    {
        $period = strtolower(trim((string) ($filters['chart_period'] ?? '')));
        if ($period === '12m') {
            return 'month';
        }
        if (in_array($period, ['7d', '30d', 'custom'], true)) {
            return 'day';
        }

        $requested = (string) ($filters['chart_granularity'] ?? '');
        if (in_array($requested, ['day', 'month'], true)) {
            return $requested;
        }

        return ($start->diffInDays($end) + 1) > 120 ? 'month' : 'day';
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0h';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        }

        $minutes = intdiv($seconds % 3600, 60);
        return "{$hours}h {$minutes}m";
    }

    private function resolveAdsStatusEnum(?string $status): ?AdsRequestStatus
    {
        if (!$status) {
            return null;
        }

        $normalized = mb_strtoupper(trim($status));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return AdsRequestStatus::tryFrom($normalized);
    }
}
