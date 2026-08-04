<?php

namespace App\Services\Reports;

use App\Services\Ads\AdsDeadlinePolicy;
use App\Services\Ads\TacitFineCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InformAdsTacitReportService
{
    public function __construct(
        private TacitFineCalculator $fineCalculator,
        private AdsDeadlinePolicy $deadlinePolicy
    )
    {
    }

    public function paginate(string $mode, array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->buildQuery($mode, $filters);
        $rows = $query->paginate($perPage);

        $rows->setCollection(
            $rows->getCollection()->map(fn ($row) => $this->enrichRow($row, $mode))
        );

        return $rows;
    }

    public function exportRows(string $mode, array $filters): Collection
    {
        return $this->buildQuery($mode, $filters)
            ->get()
            ->map(fn ($row) => $this->enrichRow($row, $mode));
    }

    /**
     * @return array{
     *   total_count:int,
     *   total_open_count:int,
     *   total_base_sum:float,
     *   total_daily_fine_sum:float,
     *   total_fine_sum:float
     * }
     */
    public function summarize(string $mode, array $filters): array
    {
        $summary = [
            'total_count' => 0,
            'total_open_count' => 0,
            'total_base_sum' => 0.0,
            'total_daily_fine_sum' => 0.0,
            'total_fine_sum' => 0.0,
        ];

        foreach ($this->buildQuery($mode, $filters)->cursor() as $row) {
            $enriched = $this->enrichRow($row, $mode);

            $summary['total_count']++;
            if ($enriched['fine_status'] === 'EM ABERTO') {
                $summary['total_open_count']++;
            }
            $summary['total_base_sum'] += (float) $enriched['base_amount'];
            $summary['total_daily_fine_sum'] += (float) $enriched['daily_fine_amount'];
            $summary['total_fine_sum'] += (float) $enriched['total_fine_amount'];
        }

        $summary['total_base_sum'] = round($summary['total_base_sum'], 2);
        $summary['total_daily_fine_sum'] = round($summary['total_daily_fine_sum'], 2);
        $summary['total_fine_sum'] = round($summary['total_fine_sum'], 2);

        return $summary;
    }

    public function buildQuery(string $mode, array $filters)
    {
        $mode = $this->normalizeMode($mode);
        $base = $this->baseQuery($filters);

        if ($mode === 'note') {
            return $base
                ->select([
                    'wr.id as work_report_id',
                    'n.note as note_number',
                    DB::raw('COALESCE(c.name, "—") as company_name'),
                    DB::raw($this->ordersAggregationExpression() . ' as order_numbers'),
                    'wr.informed_at as informed_delivery_at',
                    DB::raw('company_states.state as state'),
                    'af.tacit_due_at',
                    'af.tacit_delivered_at',
                    DB::raw('SUM(COALESCE(o.service_cost, 0)) as base_amount'),
                ])
                ->groupBy([
                    'wr.id',
                    'n.note',
                    'c.name',
                    'wr.informed_at',
                    'company_states.state',
                    'af.tacit_due_at',
                    'af.tacit_delivered_at',
                ])
                ->orderByDesc('wr.informed_at');
        }

        return $base
            ->select([
                'wr.id as work_report_id',
                'n.note as note_number',
                DB::raw('COALESCE(c.name, "—") as company_name'),
                'o.ordem as order_numbers',
                'wr.informed_at as informed_delivery_at',
                DB::raw('company_states.state as state'),
                'af.tacit_due_at',
                'af.tacit_delivered_at',
                DB::raw('COALESCE(o.service_cost, 0) as base_amount'),
            ])
            ->orderByDesc('wr.informed_at')
            ->orderBy('o.ordem');
    }

    public function enrichRow(object $row, string $mode): array
    {
        $mode = $this->normalizeMode($mode);
        $baseAmount = (float) ($row->base_amount ?? 0);
        $openReferenceAt = now();

        $informedAt = $this->asCarbon($row->informed_delivery_at ?? null);
        $dueAt = $this->asCarbon($row->tacit_due_at ?? null);
        $deliveredAt = $this->asCarbon($row->tacit_delivered_at ?? null);
        $state = $this->deadlinePolicy->normalizeState($row->state ?? null);
        $referenceAt = $deliveredAt ?: $openReferenceAt;
        $isOpen = $deliveredAt === null;

        $delayDays = $this->deadlinePolicy->lateDays($dueAt, $deliveredAt, $openReferenceAt, $state, (int) $row->work_report_id);
        $fine = $this->fineCalculator->calcularMultaPrevistaLinear($baseAmount, $delayDays);

        return [
            'mode' => $mode,
            'mode_label' => $mode === 'note' ? 'Por NOTA' : 'Por ORDEM',
            'work_report_id' => $row->work_report_id,
            'note_number' => (string) ($row->note_number ?? '—'),
            'company_name' => (string) ($row->company_name ?? '—'),
            'state' => $state,
            'order_numbers' => (string) ($row->order_numbers ?? '—'),
            'informed_delivery_at' => $informedAt,
            'tacit_due_at' => $dueAt,
            'tacit_delivered_at' => $deliveredAt,
            'fine_reference_at' => $referenceAt,
            'fine_status' => $isOpen ? 'EM ABERTO' : 'ENTREGUE',
            'delay_days' => $delayDays,
            'penalty_band' => $this->deadlinePolicy->penaltyBand($delayDays),
            'base_amount' => round($baseAmount, 2),
            'applied_percentage' => $fine['percentual_aplicado'],
            'daily_fine_amount' => $fine['valor_diario'],
            'total_fine_amount' => $fine['valor_total'],
        ];
    }

    public function normalizeMode(string $mode): string
    {
        return in_array($mode, ['note', 'order'], true) ? $mode : 'note';
    }

    private function baseQuery(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $dateIn = $filters['date_in'] ?? null;
        $dateOut = $filters['date_out'] ?? null;
        $dateField = $filters['dateField'] ?? 'ads_created_at';
        $openFilter = $filters['openFilter'] ?? 'all';
        $companyIds = $filters['companyIds'] ?? [];
        $dateColumn = $this->resolveDateColumn((string) $dateField);

        $query = DB::table('work_reports as wr')
            ->join('notes as n', 'n.id', '=', 'wr.note_id')
            ->leftJoin('companies as c', 'c.id', '=', 'wr.company_id')
            ->leftJoinSub($this->companyStateQuery(), 'company_states', function ($join) {
                $join->on('company_states.company_id', '=', 'wr.company_id');
            })
            ->join('adsforms as af', function ($join) {
                $join->on('af.work_report_id', '=', 'wr.id')
                    ->where('af.tacit', '=', true);
            })
            ->join('order_work_report as owr', 'owr.work_report_id', '=', 'wr.id')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where('wr.rejected', false)
            ->where('wr.canceled', false)
            ->whereNotNull('wr.informed_at')
            ->where('o.canceled', false);

        if ($dateIn) {
            $query->whereDate($dateColumn, '>=', $dateIn);
        }

        if ($dateOut) {
            $query->whereDate($dateColumn, '<=', $dateOut);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('n.note', 'like', "%{$search}%")
                    ->orWhere('o.ordem', 'like', "%{$search}%");
            });
        }

        if (!empty($companyIds)) {
            $query->whereIn('wr.company_id', $companyIds);
        }

        if ($openFilter === 'open') {
            $query->whereNull('af.tacit_delivered_at');
        } elseif ($openFilter === 'delivered') {
            $query->whereNotNull('af.tacit_delivered_at');
        }

        return $query;
    }

    private function companyStateQuery()
    {
        return DB::table('andresscompanies as ac')
            ->joinSub(
                DB::table('andresscompanies')
                    ->select('company_id', DB::raw('MIN(id) as first_address_id'))
                    ->groupBy('company_id'),
                'first_company_address',
                function ($join) {
                    $join->on('first_company_address.first_address_id', '=', 'ac.id');
                }
            )
            ->select([
                'ac.company_id',
                DB::raw('ac.uf as state'),
            ]);
    }

    private function resolveDateColumn(string $dateField): string
    {
        return match ($dateField) {
            'tacit_delivered_at' => 'af.tacit_delivered_at',
            'tacit_due_at' => 'af.tacit_due_at',
            'informed_at' => 'wr.informed_at',
            default => 'af.created_at',
        };
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function ordersAggregationExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            return "STRING_AGG(o.ordem, ', ')";
        }

        return "GROUP_CONCAT(DISTINCT o.ordem ORDER BY o.ordem SEPARATOR ', ')";
    }
}
