<?php

namespace App\Services\Reports;

use App\Services\Ads\TacitFineCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InformAdsTacitReportService
{
    public function __construct(private TacitFineCalculator $fineCalculator)
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

    public function buildQuery(string $mode, array $filters)
    {
        $mode = $this->normalizeMode($mode);
        $base = $this->baseQuery($filters);

        if ($mode === 'note') {
            return $base
                ->select([
                    'wr.id as work_report_id',
                    'n.note as note_number',
                    DB::raw($this->ordersAggregationExpression() . ' as order_numbers'),
                    DB::raw('COALESCE(wr.informed_at, wr.created_at) as informed_delivery_at'),
                    'af.tacit_due_at',
                    'af.tacit_delivered_at',
                    DB::raw('SUM(COALESCE(o.service_cost, 0)) as base_amount'),
                ])
                ->groupBy([
                    'wr.id',
                    'n.note',
                    'wr.informed_at',
                    'wr.created_at',
                    'af.tacit_due_at',
                    'af.tacit_delivered_at',
                ])
                ->orderByDesc('wr.created_at');
        }

        return $base
            ->select([
                'wr.id as work_report_id',
                'n.note as note_number',
                'o.ordem as order_numbers',
                DB::raw('COALESCE(wr.informed_at, wr.created_at) as informed_delivery_at'),
                'af.tacit_due_at',
                'af.tacit_delivered_at',
                DB::raw('COALESCE(o.service_cost, 0) as base_amount'),
            ])
            ->orderByDesc('wr.created_at')
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
        $referenceAt = $deliveredAt ?: $openReferenceAt;
        $isOpen = $deliveredAt === null;

        $delayDays = $this->fineCalculator->calcularDiasMulta($dueAt, $deliveredAt, $openReferenceAt);
        $fine = $this->fineCalculator->calcularMultaPrevistaLinear($baseAmount, $delayDays);

        return [
            'mode' => $mode,
            'mode_label' => $mode === 'note' ? 'Por NOTA' : 'Por ORDEM',
            'work_report_id' => $row->work_report_id,
            'note_number' => (string) ($row->note_number ?? '—'),
            'order_numbers' => (string) ($row->order_numbers ?? '—'),
            'informed_delivery_at' => $informedAt,
            'tacit_due_at' => $dueAt,
            'tacit_delivered_at' => $deliveredAt,
            'fine_reference_at' => $referenceAt,
            'fine_status' => $isOpen ? 'EM ABERTO' : 'ENTREGUE',
            'delay_days' => $delayDays,
            'base_amount' => round($baseAmount, 2),
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
        $companyIds = $filters['companyIds'] ?? [];

        $query = DB::table('work_reports as wr')
            ->join('notes as n', 'n.id', '=', 'wr.note_id')
            ->join('adsforms as af', function ($join) {
                $join->on('af.work_report_id', '=', 'wr.id')
                    ->where('af.tacit', '=', true);
            })
            ->join('order_work_report as owr', 'owr.work_report_id', '=', 'wr.id')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where('wr.rejected', false)
            ->where('o.canceled', false)
            ->where('o.statusSist', 'not like', 'CANC%')
            ->where('o.statusSist', 'not like', 'ENT%')
            ->where('o.statusSist', 'not like', 'ENC%');

        if ($dateIn) {
            $query->whereDate(DB::raw('COALESCE(wr.informed_at, wr.created_at)'), '>=', $dateIn);
        }

        if ($dateOut) {
            $query->whereDate(DB::raw('COALESCE(wr.informed_at, wr.created_at)'), '<=', $dateOut);
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

        return $query;
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
