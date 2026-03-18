<?php

namespace App\Services\Reports;

use App\Custom\Notestatus;
use App\Models\Production;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectReviewGovernanceExportService
{
    private function decisionPt(?string $decision): string
    {
        return match ((string) $decision) {
            'PENDING' => 'Pendente',
            'APPROVED' => 'Aprovado',
            'APPROVED_WITH_REMARKS' => 'Aprovado com ressalvas',
            'REJECTED' => 'Reprovado',
            default => $decision ?: '---',
        };
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int, array{title:string, headings:array<int,string>, rows:array<int,array<int,mixed>>}>
     */
    public function buildSheets(array $filters): array
    {
        $productionIds = $this->baseProductionsQuery($filters)->pluck('id');

        return [
            $this->sheetProductions($productionIds),
            $this->sheetRejectionsDetail($productionIds),
            $this->sheetUsers($productionIds),
            $this->sheetCategories($productionIds),
            $this->sheetSubcategories($productionIds),
            $this->sheetItems($productionIds),
            $this->sheetCompanies($productionIds),
            $this->sheetOrigins($productionIds),
            $this->sheetSla($productionIds),
            $this->sheetCosts($productionIds),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     */
    private function baseProductionsQuery(array $filters): Builder
    {
        $query = Production::query()->whereHas('ProjectReviewCycles');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['final_status'] !== '' && $filters['final_status'] !== null) {
            $query->where('status', (int) $filters['final_status']);
        }

        if (!empty($filters['period_from']) || !empty($filters['period_to'])) {
            $query->whereHas('ProjectReviewCycles', function ($q) use ($filters) {
                if (!empty($filters['period_from'])) {
                    $q->whereDate('submitted_at', '>=', $filters['period_from']);
                }
                if (!empty($filters['period_to'])) {
                    $q->whereDate('submitted_at', '<=', $filters['period_to']);
                }
            });
        }

        if (!empty($filters['finalized_from'])) {
            $query->whereDate('completed_at', '>=', $filters['finalized_from']);
        }
        if (!empty($filters['finalized_to'])) {
            $query->whereDate('completed_at', '<=', $filters['finalized_to']);
        }

        if (!empty($filters['approved_from']) || !empty($filters['approved_to'])) {
            $query->whereHas('ProjectReviewCycles', function ($q) use ($filters) {
                $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS']);
                if (!empty($filters['approved_from'])) {
                    $q->whereDate('decided_at', '>=', $filters['approved_from']);
                }
                if (!empty($filters['approved_to'])) {
                    $q->whereDate('decided_at', '<=', $filters['approved_to']);
                }
            });
        }

        if (!empty($filters['rejected_from']) || !empty($filters['rejected_to'])) {
            $query->whereHas('ProjectReviewCycles', function ($q) use ($filters) {
                $q->where('decision', 'REJECTED');
                if (!empty($filters['rejected_from'])) {
                    $q->whereDate('decided_at', '>=', $filters['rejected_from']);
                }
                if (!empty($filters['rejected_to'])) {
                    $q->whereDate('decided_at', '<=', $filters['rejected_to']);
                }
            });
        }

        if (($filters['rejection_filter'] ?? 'all') === 'with') {
            $query->whereHas('ProjectReviewCycles', fn ($q) => $q->where('decision', 'REJECTED'));
        }

        if (($filters['rejection_filter'] ?? 'all') === 'without') {
            $query->whereDoesntHave('ProjectReviewCycles', fn ($q) => $q->where('decision', 'REJECTED'));
        }

        return $query;
    }

    private function findingsBase(Collection $productionIds)
    {
        return DB::table('project_review_findings as f')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'f.cycle_id')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->whereIn('p.id', $productionIds);
    }

    private function sheetProductions(Collection $productionIds): array
    {
        $headings = [
            'Production ID', 'Nota', 'Desenhista', 'Empresa', 'Status final', 'Rodadas',
            'Qtd reprovações', 'Aprovado na 1ª rodada', 'Último envio', 'Última decisão', 'Principais motivos',
            'Analista (última decisão)', 'Laudo técnico (última decisão)', 'Valor planejado', 'Valor revisado', 'Economia', 'Aumento', 'Saldo'
        ];

        if ($productionIds->isEmpty()) {
            return ['title' => 'Productions', 'headings' => $headings, 'rows' => []];
        }

        $productions = DB::table('productions as p')
            ->leftJoin('notes as n', 'n.id', '=', 'p.note_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->whereIn('p.id', $productionIds)
            ->selectRaw("
                p.id as production_id,
                COALESCE(n.note, '-') as note_number,
                COALESCE(u.name, '-') as user_name,
                COALESCE(co.name, '-') as company_name,
                p.status as final_status,
                (SELECT COUNT(*) FROM project_review_cycles c WHERE c.production_id = p.id) as total_rounds,
                (SELECT COUNT(*) FROM project_review_cycles c WHERE c.production_id = p.id AND c.decision = 'REJECTED') as rejected_rounds,
                (SELECT c.decision FROM project_review_cycles c WHERE c.production_id = p.id AND c.round_number = 1 LIMIT 1) as first_round_decision,
                (SELECT MAX(c.submitted_at) FROM project_review_cycles c WHERE c.production_id = p.id) as last_submitted_at,
                (SELECT c.decision FROM project_review_cycles c WHERE c.production_id = p.id ORDER BY c.round_number DESC LIMIT 1) as last_decision,
                (SELECT COALESCE(uu.name, '-') FROM project_review_cycles c LEFT JOIN users uu ON uu.id = c.decided_by WHERE c.production_id = p.id ORDER BY c.round_number DESC LIMIT 1) as last_analyst_name,
                (SELECT COALESCE(c.analyst_note, '') FROM project_review_cycles c WHERE c.production_id = p.id ORDER BY c.round_number DESC LIMIT 1) as last_analyst_note
            ")
            ->orderByDesc('p.id')
            ->get();

        $motives = $this->findingsBase($productionIds)
            ->leftJoin('project_review_items as i', 'i.id', '=', 'f.item_id')
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->join('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->where('cy.decision', 'REJECTED')
            ->selectRaw("
                p.id as production_id,
                CONCAT(COALESCE(f.action_type, 'FALTA'), ' ', COALESCE(i.name, s.name)) as reason,
                COUNT(*) as total
            ")
            ->groupBy('p.id', 'f.action_type', 'i.name', 's.name')
            ->orderBy('p.id')
            ->orderByDesc('total')
            ->get()
            ->groupBy('production_id')
            ->map(fn ($rows) => $rows->take(5)->map(fn ($r) => "{$r->reason} ({$r->total})")->implode(' | '));

        $costByProduction = $this->buildCostVariationByProduction($productionIds);

        $rows = $productions->map(function ($row) use ($motives, $costByProduction) {
            $cost = $costByProduction->get((int) $row->production_id, [
                'planned_total_cost' => 0,
                'revised_total_cost' => 0,
                'economy_total_cost' => 0,
                'increase_total_cost' => 0,
                'net_variation_cost' => 0,
            ]);

            return [
                $row->production_id,
                $row->note_number,
                $row->user_name,
                $row->company_name,
                Notestatus::status((int) $row->final_status)->status,
                (int) $row->total_rounds,
                (int) $row->rejected_rounds,
                in_array($row->first_round_decision, ['APPROVED', 'APPROVED_WITH_REMARKS'], true) ? 'Sim' : 'Não',
                $row->last_submitted_at,
                $this->decisionPt($row->last_decision),
                $motives->get($row->production_id, '---'),
                $row->last_analyst_name ?: '---',
                $row->last_analyst_note ?: '---',
                round((float) $cost['planned_total_cost'], 2),
                round((float) $cost['revised_total_cost'], 2),
                round((float) $cost['economy_total_cost'], 2),
                round((float) $cost['increase_total_cost'], 2),
                round((float) $cost['net_variation_cost'], 2),
            ];
        })->all();

        return ['title' => 'Productions', 'headings' => $headings, 'rows' => $rows];
    }

    private function sheetRejectionsDetail(Collection $productionIds): array
    {
        $headings = [
            'Production ID', 'Nota', 'Rodada', 'Data reprovação', 'Categoria', 'Subcategoria',
            'Item', 'Ação', 'Origem', 'Quantidade', 'Observação', 'Analista', 'Laudo técnico'
        ];

        if ($productionIds->isEmpty()) {
            return ['title' => 'Reprovações Detalhe', 'headings' => $headings, 'rows' => []];
        }

        $rows = $this->findingsBase($productionIds)
            ->leftJoin('notes as n', 'n.id', '=', 'p.note_id')
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->join('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->leftJoin('project_review_items as i', 'i.id', '=', 'f.item_id')
            ->where('cy.decision', 'REJECTED')
            ->selectRaw("
                p.id as production_id,
                COALESCE(n.note, '-') as note_number,
                cy.round_number,
                cy.decided_at as rejected_at,
                c.name as category_name,
                s.name as subcategory_name,
                COALESCE(i.name, 'Estrutura sem item') as item_name,
                COALESCE(f.action_type, 'FALTA') as action_type,
                COALESCE(f.origin, '-') as origin_name,
                COALESCE(f.quantity, 0) as quantity,
                COALESCE(f.note, '') as note,
                COALESCE(u.name, '-') as analyst_name,
                COALESCE(cy.analyst_note, '') as analyst_note
            ")
            ->leftJoin('users as u', 'u.id', '=', 'cy.decided_by')
            ->orderByDesc('cy.decided_at')
            ->get()
            ->map(fn ($r) => [
                $r->production_id,
                $r->note_number,
                $r->round_number,
                $r->rejected_at,
                $r->category_name,
                $r->subcategory_name,
                $r->item_name,
                $r->action_type,
                $r->origin_name,
                $r->quantity,
                $r->note,
                $r->analyst_name,
                $r->analyst_note,
            ])
            ->all();

        return ['title' => 'Reprovações Detalhe', 'headings' => $headings, 'rows' => $rows];
    }

    private function sheetUsers(Collection $productionIds): array
    {
        $headings = ['Usuário', 'Reprovações', 'Análises', '% erro', 'Principal tipo de erro'];
        if ($productionIds->isEmpty()) {
            return ['title' => 'Usuários', 'headings' => $headings, 'rows' => []];
        }

        $rows = DB::table('project_review_cycles as cy')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->whereIn('p.id', $productionIds)
            ->whereIn('cy.decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->selectRaw("
                u.name as user_name,
                SUM(CASE WHEN cy.decision = 'REJECTED' THEN 1 ELSE 0 END) as rejected_cycles,
                COUNT(*) as total_cycles,
                ROUND((SUM(CASE WHEN cy.decision = 'REJECTED' THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)) * 100, 2) as error_pct
            ")
            ->groupBy('u.name')
            ->orderByDesc('rejected_cycles')
            ->orderByDesc('error_pct')
            ->get();

        $mainErrorByUser = $this->findingsBase($productionIds)
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->leftJoin('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->leftJoin('project_review_items as i', 'i.id', '=', 'f.item_id')
            ->selectRaw("
                u.name as user_name,
                COALESCE(c.name, 'Sem categoria') as category_name,
                COALESCE(s.name, 'Sem subcategoria') as subcategory_name,
                COALESCE(i.name, 'Estrutura sem item') as item_name,
                COUNT(*) as total
            ")
            ->groupBy('u.name', 'c.name', 's.name', 'i.name')
            ->orderBy('u.name')
            ->orderByDesc('total')
            ->get()
            ->groupBy('user_name')
            ->map(function ($items) {
                $top = $items->first();
                return $top ? "{$top->category_name} / {$top->subcategory_name} / {$top->item_name}" : '---';
            });

        $data = $rows->map(fn ($r) => [
            $r->user_name,
            (int) $r->rejected_cycles,
            (int) $r->total_cycles,
            (float) $r->error_pct,
            $mainErrorByUser->get($r->user_name, '---'),
        ])->all();

        return ['title' => 'Usuários', 'headings' => $headings, 'rows' => $data];
    }

    private function sheetCategories(Collection $productionIds): array
    {
        return $this->simpleFindingsSheet(
            $productionIds,
            'Categorias',
            ['Categoria', 'Total'],
            "c.name as label, COUNT(*) as total",
            ['c.name']
        );
    }

    private function sheetSubcategories(Collection $productionIds): array
    {
        return $this->simpleFindingsSheet(
            $productionIds,
            'Subcategorias',
            ['Subcategoria', 'Total'],
            "s.name as label, COUNT(*) as total",
            ['s.name']
        );
    }

    private function sheetItems(Collection $productionIds): array
    {
        return $this->simpleFindingsSheet(
            $productionIds,
            'Itens',
            ['Item', 'Total'],
            "COALESCE(i.name, 'Estrutura sem item') as label, COUNT(*) as total",
            ['i.name']
        );
    }

    private function sheetCompanies(Collection $productionIds): array
    {
        $headings = ['Empresa', 'Total'];
        if ($productionIds->isEmpty()) {
            return ['title' => 'Empresas', 'headings' => $headings, 'rows' => []];
        }

        $rows = $this->findingsBase($productionIds)
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->selectRaw("COALESCE(co.name, 'Sem empresa') as label, COUNT(*) as total")
            ->groupBy('co.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [$r->label, (int) $r->total])
            ->all();

        return ['title' => 'Empresas', 'headings' => $headings, 'rows' => $rows];
    }

    private function sheetOrigins(Collection $productionIds): array
    {
        $headings = ['Origem', 'Total'];
        if ($productionIds->isEmpty()) {
            return ['title' => 'Origens', 'headings' => $headings, 'rows' => []];
        }

        $rows = $this->findingsBase($productionIds)
            ->selectRaw("COALESCE(f.origin, 'N/I') as label, COUNT(*) as total")
            ->groupBy('f.origin')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [$r->label, (int) $r->total])
            ->all();

        return ['title' => 'Origens', 'headings' => $headings, 'rows' => $rows];
    }

    private function sheetSla(Collection $productionIds): array
    {
        $headings = ['Métrica', 'Valor'];
        if ($productionIds->isEmpty()) {
            return ['title' => 'SLA', 'headings' => $headings, 'rows' => []];
        }

        $avgSendToDecisionHours = (float) DB::table('project_review_cycles')
            ->whereIn('production_id', $productionIds)
            ->whereNotNull('submitted_at')
            ->whereNotNull('decided_at')
            ->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, decided_at)) as avg_hours')
            ->value('avg_hours');

        $avgRejectToResubmitHours = (float) DB::table('project_review_cycles as c1')
            ->join('project_review_cycles as c2', function ($join) {
                $join->on('c1.production_id', '=', 'c2.production_id')
                    ->on('c2.round_number', '=', DB::raw('c1.round_number + 1'));
            })
            ->whereIn('c1.production_id', $productionIds)
            ->where('c1.decision', 'REJECTED')
            ->whereNotNull('c1.decided_at')
            ->whereNotNull('c2.submitted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, c1.decided_at, c2.submitted_at)) as avg_hours')
            ->value('avg_hours');

        $avgTotalUntilFinalApprovalHours = (float) DB::table('project_review_cycles as c')
            ->joinSub(
                DB::table('project_review_cycles')
                    ->whereIn('production_id', $productionIds)
                    ->selectRaw('production_id, MIN(submitted_at) as first_submitted_at')
                    ->groupBy('production_id'),
                'first_cycle',
                fn ($join) => $join->on('first_cycle.production_id', '=', 'c.production_id')
            )
            ->whereIn('c.production_id', $productionIds)
            ->whereIn('c.decision', ['APPROVED', 'APPROVED_WITH_REMARKS'])
            ->whereNotNull('c.decided_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, first_cycle.first_submitted_at, c.decided_at)) as avg_hours')
            ->value('avg_hours');

        $totalProductions = $productionIds->count();
        $withRejection = DB::table('project_review_cycles')
            ->whereIn('production_id', $productionIds)
            ->where('decision', 'REJECTED')
            ->distinct('production_id')
            ->count('production_id');

        return [
            'title' => 'SLA',
            'headings' => $headings,
            'rows' => [
                ['Total de productions', $totalProductions],
                ['Productions com reprovação', $withRejection],
                ['Productions sem reprovação', max(0, $totalProductions - $withRejection)],
                ['Tempo médio envio > análise (h)', round($avgSendToDecisionHours ?: 0, 2)],
                ['Tempo médio reprovação > reenvio (h)', round($avgRejectToResubmitHours ?: 0, 2)],
                ['Tempo médio total até aprovação final (h)', round($avgTotalUntilFinalApprovalHours ?: 0, 2)],
            ],
        ];
    }

    private function sheetCosts(Collection $productionIds): array
    {
        $headings = ['Métrica', 'Valor'];
        if ($productionIds->isEmpty()) {
            return ['title' => 'Custos', 'headings' => $headings, 'rows' => []];
        }

        $summary = $this->buildCostVariationSummary($productionIds);

        return [
            'title' => 'Custos',
            'headings' => $headings,
            'rows' => [
                ['Valor planejado total', round((float) $summary['planned_total_cost'], 2)],
                ['Valor revisado total', round((float) $summary['revised_total_cost'], 2)],
                ['Economia total', round((float) $summary['economy_total_cost'], 2)],
                ['Aumento total', round((float) $summary['increase_total_cost'], 2)],
                ['Saldo (Aumento - Economia)', round((float) $summary['net_variation_cost'], 2)],
                ['Ordens mantidas', (int) $summary['maintained_orders_count']],
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{planned_total_cost:float,revised_total_cost:float,economy_total_cost:float,increase_total_cost:float,net_variation_cost:float,maintained_orders_count:int}>
     */
    private function buildCostVariationByProduction(Collection $productionIds): Collection
    {
        if ($productionIds->isEmpty()) {
            return collect();
        }

        $rows = DB::table('project_review_orders as o')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'o.cycle_id')
            ->whereIn('cy.production_id', $productionIds)
            ->selectRaw('cy.production_id, cy.round_number, o.order_number, o.total_cost')
            ->orderBy('cy.production_id')
            ->orderBy('o.order_number')
            ->orderBy('cy.round_number')
            ->get();

        $result = [];

        $rows->groupBy(fn ($r) => (int) $r->production_id)->each(function ($productionRows, $productionId) use (&$result) {
            $planned = 0.0;
            $revised = 0.0;
            $economy = 0.0;
            $increase = 0.0;
            $maintainedCount = 0;

            $byRound = collect($productionRows)->groupBy('round_number');
            if ($byRound->isNotEmpty()) {
                $firstRound = (int) $byRound->keys()->min();
                $lastRound = (int) $byRound->keys()->max();

                $firstMap = collect($byRound->get($firstRound, collect()))
                    ->groupBy('order_number')
                    ->map(fn ($rows) => (float) collect($rows)->sum('total_cost'));

                $lastMap = collect($byRound->get($lastRound, collect()))
                    ->groupBy('order_number')
                    ->map(fn ($rows) => (float) collect($rows)->sum('total_cost'));

                $planned += (float) $firstMap->sum();
                $revised += (float) $lastMap->sum();

                $allOrders = $firstMap->keys()->merge($lastMap->keys())->unique();
                foreach ($allOrders as $orderNumber) {
                    $first = (float) ($firstMap->get($orderNumber, 0));
                    $last = (float) ($lastMap->get($orderNumber, 0));
                    $delta = round($last - $first, 2);

                    if ($delta > 0) {
                        $increase += $delta;
                    } elseif ($delta < 0) {
                        $economy += abs($delta);
                    } else {
                        $maintainedCount++;
                    }
                }
            }

            $result[(int) $productionId] = [
                'planned_total_cost' => $planned,
                'revised_total_cost' => $revised,
                'economy_total_cost' => $economy,
                'increase_total_cost' => $increase,
                'net_variation_cost' => $revised - $planned,
                'maintained_orders_count' => $maintainedCount,
            ];
        });

        return collect($result);
    }

    private function buildCostVariationSummary(Collection $productionIds): array
    {
        $byProduction = $this->buildCostVariationByProduction($productionIds);

        return [
            'planned_total_cost' => (float) $byProduction->sum('planned_total_cost'),
            'revised_total_cost' => (float) $byProduction->sum('revised_total_cost'),
            'economy_total_cost' => (float) $byProduction->sum('economy_total_cost'),
            'increase_total_cost' => (float) $byProduction->sum('increase_total_cost'),
            'net_variation_cost' => (float) $byProduction->sum('net_variation_cost'),
            'maintained_orders_count' => (int) $byProduction->sum('maintained_orders_count'),
        ];
    }

    /**
     * @param Collection<int,mixed> $productionIds
     * @param array<int,string> $headings
     * @param array<int,string> $groupBy
     */
    private function simpleFindingsSheet(Collection $productionIds, string $title, array $headings, string $selectRaw, array $groupBy): array
    {
        if ($productionIds->isEmpty()) {
            return ['title' => $title, 'headings' => $headings, 'rows' => []];
        }

        $query = $this->findingsBase($productionIds)
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->leftJoin('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->leftJoin('project_review_items as i', 'i.id', '=', 'f.item_id')
            ->selectRaw($selectRaw);

        foreach ($groupBy as $col) {
            $query->groupBy($col);
        }

        $rows = $query->orderByDesc('total')->get()
            ->map(fn ($r) => [$r->label, (int) $r->total])
            ->all();

        return ['title' => $title, 'headings' => $headings, 'rows' => $rows];
    }
}
