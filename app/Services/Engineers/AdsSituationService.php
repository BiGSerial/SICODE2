<?php

namespace App\Services\Engineers;

use App\Models\Edp_depc\BaseCosts;
use App\Services\Ads\TacitFineCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdsSituationService
{
    private const LEGACY_NO_ADS_CUTOFF = '2026-02-01';

    public function __construct(private TacitFineCalculator $fineCalculator)
    {
    }

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $rows = $this->buildQuery($filters, true)->paginate($perPage);

        $rows->setCollection(
            $rows->getCollection()->map(fn ($row) => $this->enrichRow($row))
        );

        return $rows;
    }

    public function exportRows(array $filters): Collection
    {
        return $this->buildQuery($filters, true)
            ->get()
            ->map(fn ($row) => $this->enrichRow($row));
    }

    public function summarize(array $filters): array
    {
        $summaryFilters = $filters;
        $summaryFilters['detailStatusFilter'] = 'all';

        $summary = [
            'total' => 0,
            'passivo' => 0,
            'a_informar' => 0,
            'no_prazo' => 0,
            'vencendo_3_dias' => 0,
            'vencida_sem_entrega' => 0,
            'com_entrega' => 0,
            'entregue_atraso' => 0,
        ];

        $overdueByCompany = [];

        foreach ($this->buildQuery($summaryFilters, true)->cursor() as $row) {
            $enriched = $this->enrichRow($row);

            $summary['total']++;
            if (!$enriched['has_delivery']) {
                $summary['a_informar']++;
            }

            $summary[$enriched['status_code']] = ($summary[$enriched['status_code']] ?? 0) + 1;

            if ($enriched['status_code'] === 'vencida_sem_entrega') {
                $company = $enriched['company_name'] ?: '—';
                $overdueByCompany[$company] = ($overdueByCompany[$company] ?? 0) + 1;
            }
        }

        arsort($overdueByCompany);
        $summary['top_companies_overdue'] = collect($overdueByCompany)
            ->take(5)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values()
            ->all();

        return $summary;
    }

    private function buildQuery(array $filters, bool $applyStatusFilter)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;
        $companyIds = $filters['companyIds'] ?? [];
        $scopeFilter = (string) ($filters['statusFilter'] ?? 'disabled');
        $detailStatusFilter = (string) ($filters['detailStatusFilter'] ?? 'all');

        $query = DB::table('work_reports as wr')
            ->join('notes as n', 'n.id', '=', 'wr.note_id')
            ->leftJoin('companies as c', 'c.id', '=', 'wr.company_id')
            ->leftJoin('adsforms as af', 'af.work_report_id', '=', 'wr.id')
            ->where('wr.rejected', false)
            ->whereNotNull('wr.informed_at')
            ->select([
                'wr.id as work_report_id',
                'wr.note_id',
                'wr.informed_at',
                'wr.company_id',
                'n.note as note_number',
                DB::raw('COALESCE(c.name, "—") as company_name'),
                'af.id as adsform_id',
                'af.tacit',
                'af.tacit_due_at',
                'af.tacit_delivered_at',
                'af.created_at as ads_created_at',
            ])
            ->orderByDesc('wr.informed_at')
            ->orderBy('wr.id');

        if ($dateIn || $dateOut) {
            $query->where(function ($dateQuery) use ($dateIn, $dateOut) {
                $dateQuery->where(function ($range) use ($dateIn, $dateOut) {
                    if ($dateIn) {
                        $range->whereDate('wr.informed_at', '>=', $dateIn);
                    }
                    if ($dateOut) {
                        $range->whereDate('wr.informed_at', '<=', $dateOut);
                    }
                })
                // Inclui também o legado sem ADS, anterior ao cutoff, para análise histórica.
                ->orWhere(function ($legacy) {
                    $legacy->whereNull('af.id')
                        ->whereDate('wr.informed_at', '<', self::LEGACY_NO_ADS_CUTOFF);
                });
            });
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('n.note', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%");
            });
        }

        if (!empty($companyIds)) {
            $query->whereIn('wr.company_id', $companyIds);
        }

        if ($applyStatusFilter) {
            $this->applyScopeFilter($query, $scopeFilter);
            $this->applyDetailStatusFilter($query, $scopeFilter, $detailStatusFilter);
        }

        return $query;
    }

    private function applyScopeFilter($query, string $scopeFilter): void
    {
        if ($scopeFilter === 'disabled') {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($scopeFilter === 'passivo') {
            $query->whereNull('af.id')
                ->whereDate('wr.informed_at', '<', self::LEGACY_NO_ADS_CUTOFF);
            $query->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('order_work_report as owr_v')
                    ->join('orders as o_v', 'o_v.id', '=', 'owr_v.order_id')
                    ->whereColumn('owr_v.work_report_id', 'wr.id')
                    ->where('o_v.canceled', false)
                    ->where('o_v.statusSist', 'not like', 'CANC%')
                    ->where('o_v.statusSist', 'not like', 'ENT%')
                    ->where('o_v.statusSist', 'not like', 'ENC%');
            });
            return;
        }

        if ($scopeFilter === 'atual') {
            $query->where(function ($q) {
                $q->whereNotNull('af.id')
                    ->orWhereDate('wr.informed_at', '>=', self::LEGACY_NO_ADS_CUTOFF);
            });
            return;
        }
    }

    private function applyDetailStatusFilter($query, string $scopeFilter, string $detailStatusFilter): void
    {
        if ($scopeFilter !== 'atual' || $detailStatusFilter === 'all') {
            return;
        }

        $dueExpr = $this->dueAtExpression();
        $deliveryExpr = $this->deliveryExpression();

        if ($detailStatusFilter === 'a_informar') {
            $query->whereRaw("{$deliveryExpr} IS NULL");
            return;
        }

        if ($detailStatusFilter === 'no_prazo') {
            $query->whereRaw("{$deliveryExpr} IS NULL")
                ->whereRaw("NOW() <= {$dueExpr}")
                ->whereRaw("TIMESTAMPDIFF(DAY, NOW(), {$dueExpr}) > 3");
            return;
        }

        if ($detailStatusFilter === 'vencendo_3_dias') {
            $query->whereRaw("{$deliveryExpr} IS NULL")
                ->whereRaw("NOW() <= {$dueExpr}")
                ->whereRaw("TIMESTAMPDIFF(DAY, NOW(), {$dueExpr}) BETWEEN 0 AND 3");
            return;
        }

        if ($detailStatusFilter === 'vencida_sem_entrega') {
            $query->whereRaw("{$deliveryExpr} IS NULL")
                ->whereRaw("NOW() > {$dueExpr}");
            return;
        }

        if ($detailStatusFilter === 'com_entrega') {
            $query->whereRaw("{$deliveryExpr} IS NOT NULL");
            return;
        }

        if ($detailStatusFilter === 'entregue_atraso') {
            $query->whereRaw("{$deliveryExpr} IS NOT NULL")
                ->whereRaw("{$deliveryExpr} > {$dueExpr}");
        }
    }

    private function enrichRow(object $row): array
    {
        $informedAt = $this->asCarbon($row->informed_at ?? null);
        $dueAt = $this->resolveDueAt($row);
        $deliveredAt = $this->resolveDeliveredAt($row);
        $daysToDue = $this->resolveDaysToDue($dueAt, $deliveredAt);

        $hasDelivery = $deliveredAt !== null;
        $statusCode = $this->resolveStatusCode($informedAt, $dueAt, $deliveredAt);
        $statusLabel = $this->statusLabel($statusCode);
        $statusBadge = $this->statusBadge($statusCode);

        $delayDays = $this->fineCalculator->calcularDiasMulta($dueAt, $deliveredAt, now());
        $delayDays = in_array($statusCode, ['vencida_sem_entrega', 'entregue_atraso', 'passivo'], true)
            ? $delayDays
            : 0;

        return [
            'work_report_id' => (int) $row->work_report_id,
            'note_number' => (string) ($row->note_number ?? '—'),
            'company_name' => (string) ($row->company_name ?? '—'),
            'informed_at' => $informedAt,
            'due_at' => $dueAt,
            'delivered_at' => $deliveredAt,
            'has_delivery' => $hasDelivery,
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'status_badge' => $statusBadge,
            'delay_days' => $delayDays,
            'days_to_due' => $daysToDue,
        ];
    }

    public function refreshSingleWorkReportFine(int $workReportId): ?array
    {
        $row = DB::table('work_reports as wr')
            ->leftJoin('adsforms as af', 'af.work_report_id', '=', 'wr.id')
            ->select([
                'wr.id',
                'wr.informed_at',
                'af.id as adsform_id',
                'af.tacit',
                'af.tacit_due_at',
                'af.tacit_delivered_at',
                'af.created_at as ads_created_at',
            ])
            ->where('wr.id', $workReportId)
            ->first();

        if (!$row) {
            return null;
        }

        $orders = DB::table('order_work_report as owr')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where('owr.work_report_id', $workReportId)
            ->where('o.canceled', false)
            ->where('o.statusSist', 'not like', 'CANC%')
            ->where('o.statusSist', 'not like', 'ENT%')
            ->where('o.statusSist', 'not like', 'ENC%')
            ->select('o.id', 'o.ordem')
            ->get();

        if ($orders->isEmpty()) {
            return [
                'base_amount' => 0.0,
                'daily_fine_amount' => 0.0,
                'total_fine_amount' => 0.0,
                'fine_percentage' => 0.0,
            ];
        }

        $orderNumbers = $orders->pluck('ordem')->filter()->unique()->values()->all();
        $loadedCosts = BaseCosts::query()
            ->whereIn('ordem', $orderNumbers)
            ->select('ordem', DB::raw('SUM(qtdNecessaria * preco) as base_cost'))
            ->groupBy('ordem')
            ->pluck('base_cost', 'ordem');

        $baseAmount = 0.0;
        foreach ($orders as $order) {
            $cost = round((float) ($loadedCosts[$order->ordem] ?? 0), 2);
            $baseAmount += $cost;

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'service_cost' => $cost,
                    'updated_at' => now(),
                ]);
        }

        $dueAt = $this->resolveDueAt($row);
        $deliveredAt = $this->resolveDeliveredAt($row);
        $status = $this->resolveStatusCode($this->asCarbon($row->informed_at), $dueAt, $deliveredAt);
        $delayDays = in_array($status, ['vencida_sem_entrega', 'entregue_atraso', 'passivo'], true)
            ? $this->fineCalculator->calcularDiasMulta($dueAt, $deliveredAt, now())
            : 0;

        $fine = $this->fineCalculator->calcularMultaPrevistaLinear($baseAmount, $delayDays);

        return [
            'base_amount' => round($baseAmount, 2),
            'daily_fine_amount' => $fine['valor_diario'],
            'total_fine_amount' => $fine['valor_total'],
            'fine_percentage' => $fine['percentual_aplicado'],
        ];
    }

    private function resolveStatusCode(?Carbon $informedAt, ?Carbon $dueAt, ?Carbon $deliveredAt): string
    {
        if (!$deliveredAt && $informedAt && $informedAt->lt(Carbon::parse(self::LEGACY_NO_ADS_CUTOFF)->startOfDay())) {
            return 'passivo';
        }

        if (!$dueAt) {
            return $deliveredAt ? 'com_entrega' : 'a_informar';
        }

        if ($deliveredAt) {
            return $deliveredAt->greaterThan($dueAt) ? 'entregue_atraso' : 'com_entrega';
        }

        $daysToDue = now()->startOfDay()->diffInDays($dueAt->copy()->startOfDay(), false);

        if ($daysToDue < 0) {
            return 'vencida_sem_entrega';
        }

        if ($daysToDue <= 3) {
            return 'vencendo_3_dias';
        }

        return 'no_prazo';
    }

    private function resolveDaysToDue(?Carbon $dueAt, ?Carbon $deliveredAt): ?int
    {
        if (!$dueAt || $deliveredAt) {
            return null;
        }

        $days = now()->startOfDay()->diffInDays($dueAt->copy()->startOfDay(), false);
        return $days >= 0 ? $days : null;
    }

    private function resolveDueAt(object $row): ?Carbon
    {
        $dueAt = $this->asCarbon($row->tacit_due_at ?? null);
        if ($dueAt) {
            return $dueAt;
        }

        $informedAt = $this->asCarbon($row->informed_at ?? null);
        if (!$informedAt) {
            return null;
        }

        return $informedAt->copy()->addDays(6)->endOfDay();
    }

    private function resolveDeliveredAt(object $row): ?Carbon
    {
        if (empty($row->adsform_id)) {
            return null;
        }

        if ((bool) ($row->tacit ?? false)) {
            return $this->asCarbon($row->tacit_delivered_at ?? null);
        }

        return $this->asCarbon($row->ads_created_at ?? null);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'passivo' => 'PASSIVO',
            'a_informar' => 'A INFORMAR',
            'no_prazo' => 'NO PRAZO',
            'vencendo_3_dias' => 'VENCE EM ATÉ 3 DIAS',
            'vencida_sem_entrega' => 'VENCIDA TÁCITA SEM ENTREGA',
            'com_entrega' => 'COM ENTREGA',
            'entregue_atraso' => 'ENTREGUE EM ATRASO',
            default => strtoupper($status),
        };
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'passivo' => 'bg-dark',
            'no_prazo' => 'bg-success',
            'vencendo_3_dias' => 'bg-warning text-dark',
            'vencida_sem_entrega' => 'bg-danger',
            'com_entrega' => 'bg-primary',
            'entregue_atraso' => 'bg-secondary',
            default => 'bg-dark',
        };
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function dueAtExpression(): string
    {
        return "COALESCE(af.tacit_due_at, TIMESTAMP(DATE(DATE_ADD(wr.informed_at, INTERVAL 6 DAY)), '23:59:59'))";
    }

    private function deliveryExpression(): string
    {
        return "CASE WHEN af.id IS NULL THEN NULL WHEN af.tacit = 1 THEN af.tacit_delivered_at ELSE af.created_at END";
    }

}
