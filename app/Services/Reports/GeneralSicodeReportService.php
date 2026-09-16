<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneralSicodeReportService
{
    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function report(array $filters = []): array
    {
        $application = (string) config('app.name', 'SICODE');
        $rows = collect($this->activityDefinitions())
            ->map(fn (array $definition) => $this->activityRow($definition, $filters, $application));

        $measurement = $this->measurementRow($filters, $application);
        $fiscalization = $this->fiscalizationRow($filters, $application);
        $viability = $this->viabilityRow($filters, $application);

        $rows = $rows
            ->map(fn (array $row) => $row['description'] === 'Fiscalização' ? $fiscalization : $row)
            ->map(fn (array $row) => $row['description'] === 'Medição/Pagamento' ? $measurement['row'] : $row)
            ->map(fn (array $row) => $row['description'] === 'Viabilidade/Contratação' ? $viability : $row)
            ->map(fn (array $row) => $row['description'] === 'Analise de Projetos' ? $this->projectReviewRow($filters, $application) : $row)
            ->values();

        return [
            'application' => $application,
            'rows' => $rows,
            'totals' => [
                'quantity' => (int) $rows->sum('quantity'),
                'value' => (float) $rows->sum('value'),
            ],
            'quality' => $measurement['quality'],
            'generated_at' => now(),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function activityDefinitions(): array
    {
        return [
            ['description' => 'Analise', 'services' => ['Analise', 'Análise', 'Analises', 'Análises']],
            ['description' => 'Pré-Analise', 'services' => ['Pre-Analise', 'Pré-Analise', 'Pré-Análise', 'AnalisesPre', 'Análises Pre']],
            ['description' => 'Fluxo-reverso/Inverso', 'services' => ['Fluxo-reverso', 'Fluxo Reverso', 'Fluxo Inverso', 'Inverso']],
            ['description' => 'Desenho', 'services' => ['Desenho']],
            ['description' => 'Levantamento', 'services' => ['Levantamento']],
            ['description' => 'Fiscalização', 'services' => ['Fiscalizacao', 'Fiscalização']],
            ['description' => 'Medição/Pagamento', 'services' => ['Pagamento']],
            ['description' => 'Publicação', 'services' => ['Publicacao', 'Publicação']],
            ['description' => 'Analise de Projetos', 'services' => []],
            ['description' => 'Viabilidade/Contratação', 'services' => []],
        ];
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function activityRow(array $definition, array $filters, string $application): array
    {
        if (empty($definition['services'])) {
            return $this->emptyRow($definition['description'], $application, 'viabilities');
        }

        $base = DB::query()
            ->fromSub(function ($query) use ($definition, $filters) {
                $query->from('productions as p')
                    ->join('services as s', 's.uuid', '=', 'p.service_id')
                    ->leftJoin('notes as n', 'n.id', '=', 'p.note_id')
                    ->where('p.rejected', false)
                    ->whereIn('s.service', $definition['services'])
                    ->when(!empty($filters['company_id']), fn ($q) => $q->where('p.company_id', $filters['company_id']))
                    ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw($this->productionEventDateExpression()), '>=', $filters['date_from']))
                    ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw($this->productionEventDateExpression()), '<=', $filters['date_to']))
                    ->select([
                        'p.note_id',
                        DB::raw('0 as value'),
                    ])
                    ->groupBy('p.note_id');
            }, 'activity_notes');

        $aggregate = $base->selectRaw('COUNT(*) as quantity, COALESCE(SUM(value), 0) as value')->first();

        return [
            'description' => $definition['description'],
            'quantity' => (int) ($aggregate->quantity ?? 0),
            'value' => (float) ($aggregate->value ?? 0),
            'application' => $application,
            'source' => 'productions/services',
            'resolution' => 'quantitativo operacional por nota deduplicada',
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function fiscalizationRow(array $filters, string $application): array
    {
        $partial = $this->partialFiscalizationAggregate($filters);
        $final = $this->finalFiscalizationAggregate($filters);

        return [
            'description' => 'Fiscalização',
            'quantity' => (int) $partial['quantity'] + (int) $final['quantity'],
            'value' => (float) $partial['value'] + (float) $final['value'],
            'application' => $application,
            'source' => 'partials + work_reports',
            'resolution' => 'fiscalizadas, incluindo pendentes de medição',
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function measurementRow(array $filters, string $application): array
    {
        $partial = $this->partialMeasurementAggregate($filters);
        $final = $this->finalMeasurementAggregate($filters);

        return [
            'row' => [
                'description' => 'Medição/Pagamento',
                'quantity' => (int) $partial['quantity'] + (int) $final['quantity'],
                'value' => (float) $partial['value'] + (float) $final['value'],
                'application' => $application,
                'source' => 'partials + work_reports',
                'resolution' => 'parciais diretas + finais com fallback',
            ],
            'quality' => [
                'partial_quantity' => (int) $partial['quantity'],
                'partial_value' => (float) $partial['value'],
                'final_quantity' => (int) $final['quantity'],
                'final_value' => (float) $final['value'],
                'final_with_new_association' => (int) $final['with_new_association'],
                'final_with_fallback' => (int) $final['with_fallback'],
                'final_without_value' => (int) $final['without_value'],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function partialMeasurementAggregate(array $filters): array
    {
        $aggregate = DB::table('partials as p')
            ->where('p.allow', true)
            ->where('p.payment', true)
            ->when(!empty($filters['company_id']), fn ($q) => $q->where('p.company_id', $filters['company_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw('COALESCE(p.payment_at, p.updated_at, p.created_at)'), '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw('COALESCE(p.payment_at, p.updated_at, p.created_at)'), '<=', $filters['date_to']))
            ->selectRaw('COUNT(DISTINCT p.id) as quantity, COALESCE(SUM(p.value), 0) as value')
            ->first();

        return [
            'quantity' => (int) ($aggregate->quantity ?? 0),
            'value' => (float) ($aggregate->value ?? 0),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function partialFiscalizationAggregate(array $filters): array
    {
        $aggregate = DB::table('partials as p')
            ->where('p.allow', true)
            ->where('p.supervision', true)
            ->when(!empty($filters['company_id']), fn ($q) => $q->where('p.company_id', $filters['company_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw('COALESCE(p.supervision_at, p.updated_at, p.created_at)'), '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw('COALESCE(p.supervision_at, p.updated_at, p.created_at)'), '<=', $filters['date_to']))
            ->selectRaw('COUNT(DISTINCT p.id) as quantity, COALESCE(SUM(p.value), 0) as value')
            ->first();

        return [
            'quantity' => (int) ($aggregate->quantity ?? 0),
            'value' => (float) ($aggregate->value ?? 0),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function finalMeasurementAggregate(array $filters): array
    {
        $orderValues = DB::table('order_work_report as owr')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where(function ($query) {
                $query->where('o.canceled', false)->orWhereNull('o.canceled');
            })
            ->selectRaw('owr.work_report_id, SUM(COALESCE(o.service_cost, o.custRealizado, o.custPlanejado, 0)) as orders_value')
            ->groupBy('owr.work_report_id');

        $rows = DB::table('work_reports as wr')
            ->leftJoin('notes as n', 'n.id', '=', 'wr.note_id')
            ->leftJoinSub($orderValues, 'ov', 'ov.work_report_id', '=', 'wr.id')
            ->where('wr.canceled', false)
            ->whereRaw($this->paymentDoneExistsExpression())
            ->when(!empty($filters['company_id']), fn ($q) => $q->where('wr.company_id', $filters['company_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw('COALESCE(wr.informed_at, wr.created_at)'), '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw('COALESCE(wr.informed_at, wr.created_at)'), '<=', $filters['date_to']))
            ->select([
                'wr.id',
                DB::raw('COALESCE(NULLIF(ov.orders_value, 0), n.value, 0) as resolved_value'),
                DB::raw($this->newAssociationExistsExpression() . ' as has_new_association'),
            ])
            ->get();

        $quantity = $rows->count();
        $withNewAssociation = $rows->where('has_new_association', 1)->count();

        return [
            'quantity' => $quantity,
            'value' => (float) $rows->sum('resolved_value'),
            'with_new_association' => $withNewAssociation,
            'with_fallback' => max(0, $quantity - $withNewAssociation),
            'without_value' => $rows->filter(fn ($row) => (float) $row->resolved_value <= 0)->count(),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function finalFiscalizationAggregate(array $filters): array
    {
        $orderValues = DB::table('order_work_report as owr')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where(function ($query) {
                $query->where('o.canceled', false)->orWhereNull('o.canceled');
            })
            ->selectRaw('owr.work_report_id, SUM(COALESCE(o.service_cost, o.custRealizado, o.custPlanejado, 0)) as orders_value')
            ->groupBy('owr.work_report_id');

        $rows = DB::table('work_reports as wr')
            ->leftJoin('notes as n', 'n.id', '=', 'wr.note_id')
            ->leftJoinSub($orderValues, 'ov', 'ov.work_report_id', '=', 'wr.id')
            ->where('wr.canceled', false)
            ->whereRaw($this->fiscalizationDoneExistsExpression())
            ->when(!empty($filters['company_id']), fn ($q) => $q->where('wr.company_id', $filters['company_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw('COALESCE(wr.informed_at, wr.created_at)'), '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw('COALESCE(wr.informed_at, wr.created_at)'), '<=', $filters['date_to']))
            ->select([
                'wr.id',
                DB::raw('COALESCE(NULLIF(ov.orders_value, 0), n.value, 0) as resolved_value'),
            ])
            ->get();

        return [
            'quantity' => $rows->count(),
            'value' => (float) $rows->sum('resolved_value'),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function projectReviewRow(array $filters, string $application): array
    {
        $latestCycles = DB::table('project_review_cycles')
            ->selectRaw('production_id, MAX(round_number) as round_number')
            ->groupBy('production_id');

        $base = DB::table('project_review_cycles as cy')
            ->joinSub($latestCycles, 'latest_cy', function ($join) {
                $join->on('latest_cy.production_id', '=', 'cy.production_id')
                    ->on('latest_cy.round_number', '=', 'cy.round_number');
            })
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->leftJoin('project_review_orders as pro', 'pro.cycle_id', '=', 'cy.id')
            ->when(!empty($filters['company_id']), fn ($q) => $q->where('p.company_id', $filters['company_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw('COALESCE(cy.decided_at, cy.submitted_at, cy.created_at)'), '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw('COALESCE(cy.decided_at, cy.submitted_at, cy.created_at)'), '<=', $filters['date_to']));

        $aggregate = DB::query()
            ->fromSub(function ($query) use ($base) {
                $query->fromSub($base->selectRaw('cy.production_id, SUM(COALESCE(pro.company_cost, 0) + COALESCE(pro.client_cost, 0)) as value')->groupBy('cy.production_id'), 'project_review_values');
            }, 'project_review_notes')
            ->selectRaw('COUNT(*) as quantity, COALESCE(SUM(value), 0) as value')
            ->first();

        return [
            'description' => 'Analise de Projetos',
            'quantity' => (int) ($aggregate->quantity ?? 0),
            'value' => (float) ($aggregate->value ?? 0),
            'application' => $application,
            'source' => 'project_review_orders',
            'resolution' => 'última rodada, custo empresa + cliente',
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function viabilityRow(array $filters, string $application): array
    {
        $orderValues = DB::table('order_viability as ovb')
            ->join('orders as o', 'o.id', '=', 'ovb.order_id')
            ->where(function ($query) {
                $query->where('o.canceled', false)->orWhereNull('o.canceled');
            })
            ->selectRaw('ovb.viability_id, SUM(COALESCE(o.service_cost, o.custRealizado, o.custPlanejado, 0)) as orders_value')
            ->groupBy('ovb.viability_id');

        $rows = DB::table('viabilities as v')
            ->leftJoin('orders as direct_order', 'direct_order.id', '=', 'v.order_id')
            ->leftJoinSub($orderValues, 'ov', 'ov.viability_id', '=', 'v.id')
            ->where(function ($q) {
                $q->where('v.hired', true)->orWhereNotNull('v.hired_at');
            })
            ->where('v.canceled', false)
            ->when(!empty($filters['company_id']), fn ($q) => $q->where('v.company_id', $filters['company_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate(DB::raw('COALESCE(v.hired_at, v.completed_at, v.updated_at, v.created_at)'), '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate(DB::raw('COALESCE(v.hired_at, v.completed_at, v.updated_at, v.created_at)'), '<=', $filters['date_to']))
            ->select([
                'v.id',
                DB::raw('COALESCE(v.value, NULLIF(ov.orders_value, 0), direct_order.service_cost, direct_order.custRealizado, direct_order.custPlanejado, 0) as resolved_value'),
            ])
            ->get();

        return [
            'description' => 'Viabilidade/Contratação',
            'quantity' => $rows->count(),
            'value' => (float) $rows->sum('resolved_value'),
            'application' => $application,
            'source' => 'viabilities',
            'resolution' => 'contratadas por hired/hired_at',
        ];
    }

    private function productionEventDateExpression(): string
    {
        return 'COALESCE(p.completed_at, p.confirmed_at, p.att_at, p.dispatch_at, p.created_at)';
    }

    private function newAssociationExistsExpression(): string
    {
        return "CASE WHEN EXISTS (
            SELECT 1 FROM note_inform_flows nif
            WHERE nif.work_report_id = wr.id
              AND nif.flow_type = 'final'
              AND nif.active = true
        ) OR EXISTS (
            SELECT 1 FROM work_report_flow_productions wrfp
            WHERE wrfp.work_report_id = wr.id
              AND wrfp.is_current = true
        ) THEN 1 ELSE 0 END";
    }

    private function fiscalizationDoneExistsExpression(): string
    {
        return "(
            EXISTS (
                SELECT 1 FROM note_inform_flows nif
                WHERE nif.work_report_id = wr.id
                  AND nif.flow_type = 'final'
                  AND nif.active = true
                  AND nif.fiscalization_completed_at IS NOT NULL
            )
            OR EXISTS (
                SELECT 1
                FROM work_report_flow_productions wrfp
                INNER JOIN productions p ON p.id = wrfp.production_id
                WHERE wrfp.work_report_id = wr.id
                  AND wrfp.is_current = true
                  AND wrfp.stage = 'fiscalization'
                  AND (p.completed = true OR p.confirmed = true)
            )
            OR EXISTS (
                SELECT 1
                FROM productions p
                INNER JOIN services s ON s.uuid = p.service_id
                WHERE p.note_id = wr.note_id
                  AND p.rejected = false
                  AND (p.partial IS NULL OR p.partial = false)
                  AND s.service IN ('Fiscalizacao', 'Fiscalização')
                  AND (p.completed = true OR p.confirmed = true)
                  AND COALESCE(p.completed_at, p.confirmed_at, p.att_at, p.created_at) >= COALESCE(wr.informed_at, wr.created_at)
            )
        )";
    }

    private function paymentDoneExistsExpression(): string
    {
        return "(
            EXISTS (
                SELECT 1 FROM note_inform_flows nif
                WHERE nif.work_report_id = wr.id
                  AND nif.flow_type = 'final'
                  AND nif.active = true
                  AND (nif.measurement_completed_at IS NOT NULL OR nif.measurement_exited_at IS NOT NULL)
            )
            OR EXISTS (
                SELECT 1
                FROM work_report_flow_productions wrfp
                INNER JOIN productions p ON p.id = wrfp.production_id
                WHERE wrfp.work_report_id = wr.id
                  AND wrfp.is_current = true
                  AND wrfp.stage = 'payment'
                  AND (p.completed = true OR p.confirmed = true)
            )
            OR EXISTS (
                SELECT 1
                FROM productions p
                INNER JOIN services s ON s.uuid = p.service_id
                WHERE p.note_id = wr.note_id
                  AND p.rejected = false
                  AND (p.partial IS NULL OR p.partial = false)
                  AND s.service = 'Pagamento'
                  AND (p.completed = true OR p.confirmed = true)
                  AND COALESCE(p.completed_at, p.confirmed_at, p.att_at, p.created_at) >= COALESCE(wr.informed_at, wr.created_at)
            )
        )";
    }

    /**
     * @return Collection<int,object>
     */
    public function companies(): Collection
    {
        return DB::table('companies')
            ->where(function ($query) {
                $query->whereExists(fn ($q) => $q->selectRaw('1')->from('productions')->whereColumn('productions.company_id', 'companies.id'))
                    ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('partials')->whereColumn('partials.company_id', 'companies.id'))
                    ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('work_reports')->whereColumn('work_reports.company_id', 'companies.id'))
                    ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('viabilities')->whereColumn('viabilities.company_id', 'companies.id'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyRow(string $description, string $application, string $source): array
    {
        return [
            'description' => $description,
            'quantity' => 0,
            'value' => 0.0,
            'application' => $application,
            'source' => $source,
            'resolution' => 'sem registros',
        ];
    }
}
