<?php

namespace App\Http\Livewire\ProjectReview;

use App\Custom\Notestatus;
use App\Jobs\Reports\ExportProjectReviewGovernanceJob;
use App\Models\Company;
use App\Models\Production;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GovernanceDashboard extends Component
{
    private const USER_REPRESENTATIVITY_LIMIT = 8;

    public ?string $period_from = null;
    public ?string $period_to = null;
    public string $company_id = '';
    public string $user_id = '';
    public string $final_status = '';
    public string $rejection_filter = 'all'; // all|with|without

    public ?string $finalized_from = null;
    public ?string $finalized_to = null;
    public ?string $approved_from = null;
    public ?string $approved_to = null;
    public ?string $rejected_from = null;
    public ?string $rejected_to = null;

    public function mount(): void
    {
        $this->period_from = now()->startOfMonth()->toDateString();
        $this->period_to = now()->toDateString();
    }

    public function clearFilters(): void
    {
        $this->period_from = now()->startOfMonth()->toDateString();
        $this->period_to = now()->toDateString();
        $this->company_id = '';
        $this->user_id = '';
        $this->final_status = '';
        $this->rejection_filter = 'all';
        $this->finalized_from = null;
        $this->finalized_to = null;
        $this->approved_from = null;
        $this->approved_to = null;
        $this->rejected_from = null;
        $this->rejected_to = null;
    }

    public function exportReport(): void
    {
        ExportProjectReviewGovernanceJob::dispatch($this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>Seu relatório está sendo gerado.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver pronto.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    public function getCompaniesProperty()
    {
        $query = Company::query()->orderBy('name');

        if (auth()->user()?->contract) {
            $companyIds = $this->allowedCompanyIds();
            $query->whereIn('id', $companyIds->all());
        }

        return $query->get(['id', 'name']);
    }

    public function getUsersProperty()
    {
        $query = User::query()->withTrashed()->orderBy('name');

        if (auth()->user()?->contract) {
            $companyIds = $this->allowedCompanyIds();
            $query->whereIn('company_id', $companyIds->all());
        }

        return $query->get(['id', 'name']);
    }

    public function getStatusOptionsProperty(): array
    {
        return [
            Production::STATUS_IN_PROJECT_REVIEW => Notestatus::status(Production::STATUS_IN_PROJECT_REVIEW)->status,
            Production::STATUS_REJECTED_PROJECT_REVIEW => Notestatus::status(Production::STATUS_REJECTED_PROJECT_REVIEW)->status,
            5 => Notestatus::status(5)->status,
        ];
    }

    private function baseProductionsQuery()
    {
        $query = Production::query()
            ->whereHas('ProjectReviewCycles');

        $this->applyContractScopeToProductions($query);

        if ($this->company_id !== '') {
            $query->where('company_id', $this->company_id);
        }

        if ($this->user_id !== '') {
            $query->where('user_id', $this->user_id);
        }

        if ($this->final_status !== '') {
            $query->where('status', (int) $this->final_status);
        }

        if ($this->period_from || $this->period_to) {
            $query->whereHas('ProjectReviewCycles', function ($q) {
                if ($this->period_from) {
                    $q->whereDate('submitted_at', '>=', $this->period_from);
                }
                if ($this->period_to) {
                    $q->whereDate('submitted_at', '<=', $this->period_to);
                }
            });
        }

        if ($this->finalized_from || $this->finalized_to) {
            if ($this->finalized_from) {
                $query->whereDate('completed_at', '>=', $this->finalized_from);
            }
            if ($this->finalized_to) {
                $query->whereDate('completed_at', '<=', $this->finalized_to);
            }
        }

        if ($this->approved_from || $this->approved_to) {
            $query->whereHas('ProjectReviewCycles', function ($q) {
                $q->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS']);
                if ($this->approved_from) {
                    $q->whereDate('decided_at', '>=', $this->approved_from);
                }
                if ($this->approved_to) {
                    $q->whereDate('decided_at', '<=', $this->approved_to);
                }
            });
        }

        if ($this->rejected_from || $this->rejected_to) {
            $query->whereHas('ProjectReviewCycles', function ($q) {
                $q->where('decision', 'REJECTED');
                if ($this->rejected_from) {
                    $q->whereDate('decided_at', '>=', $this->rejected_from);
                }
                if ($this->rejected_to) {
                    $q->whereDate('decided_at', '<=', $this->rejected_to);
                }
            });
        }

        if ($this->rejection_filter === 'with') {
            $query->whereHas('ProjectReviewCycles', fn($q) => $q->where('decision', 'REJECTED'));
        }

        if ($this->rejection_filter === 'without') {
            $query->whereDoesntHave('ProjectReviewCycles', fn($q) => $q->where('decision', 'REJECTED'));
        }

        return $query;
    }

    private function applyContractScopeToProductions($query): void
    {
        $user = auth()->user();
        if (!$user?->contract) {
            return;
        }

        $companyIds = $this->allowedCompanyIds();
        if ($companyIds->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('company_id', $companyIds->all());
    }

    private function allowedCompanyIds()
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        return collect([$user->company_id])
            ->merge($user->Companies()->pluck('companies.id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function findingsBaseQuery()
    {
        $productionIds = $this->baseProductionsQuery()->pluck('id');

        return DB::table('project_review_findings as f')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'f.cycle_id')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->whereIn('p.id', $productionIds);
    }

    private function metrics(): array
    {
        $productionQuery = $this->baseProductionsQuery();
        $productionIds = (clone $productionQuery)->pluck('id');

        if ($productionIds->isEmpty()) {
            return [
                'summary' => [
                    'total_productions' => 0,
                    'with_rejection' => 0,
                    'without_rejection' => 0,
                    'cycles_submitted_count' => 0,
                    'cycles_rejected_count' => 0,
                    'cycles_approved_count' => 0,
                    'cycles_approved_with_remarks_count' => 0,
                    'cycles_approved_without_remarks_count' => 0,
                    'cycles_waiting_analysis_count' => 0,
                    'first_pass_approval_pct' => 0,
                    'avg_send_to_decision_hours' => 0,
                    'avg_reject_to_resubmit_hours' => 0,
                    'avg_total_until_final_approval_hours' => 0,
                    'planned_total_cost' => 0,
                    'revised_total_cost' => 0,
                    'planned_company_total_cost' => 0,
                    'planned_client_total_cost' => 0,
                    'revised_company_total_cost' => 0,
                    'revised_client_total_cost' => 0,
                    'company_net_variation_cost' => 0,
                    'client_net_variation_cost' => 0,
                    'economy_total_cost' => 0,
                    'increase_total_cost' => 0,
                    'net_variation_cost' => 0,
                    'maintained_orders_count' => 0,
                ],
                'charts' => [
                    'categories' => ['labels' => [], 'data' => []],
                    'subcategories' => ['labels' => [], 'data' => []],
                    'items' => ['labels' => [], 'data' => []],
                    'users_error_count' => ['labels' => [], 'data' => []],
                    'users_error_pct' => ['labels' => [], 'data' => []],
                    'companies' => ['labels' => [], 'data' => []],
                    'origins' => ['labels' => [], 'data' => []],
                    'rejections_per_production' => ['labels' => [], 'data' => []],
                    'timeline_submitted' => ['labels' => [], 'data' => []],
                    'timeline_rejected' => ['labels' => [], 'data' => []],
                    'timeline_approved_with_remarks' => ['labels' => [], 'data' => []],
                    'timeline_approved_without_remarks' => ['labels' => [], 'data' => []],
                ],
                'tables' => [
                    'top_items' => collect(),
                    'top_subcategories' => collect(),
                    'top_categories' => collect(),
                    'top_users' => collect(),
                    'user_error_percent' => collect(),
                    'top_companies' => collect(),
                    'company_error_summary' => collect(),
                    'origins' => collect(),
                    'rejections_per_production' => collect(),
                    'timeline' => collect(),
                ],
            ];
        }

        $cyclesBase = DB::table('project_review_cycles as cy')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->whereIn('p.id', $productionIds);

        if ($this->period_from) {
            $cyclesBase->whereDate('cy.submitted_at', '>=', $this->period_from);
        }
        if ($this->period_to) {
            $cyclesBase->whereDate('cy.submitted_at', '<=', $this->period_to);
        }

        $findingsBase = $this->findingsBaseQuery();

        $topCategories = (clone $findingsBase)
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->join('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->selectRaw('c.name as label, COUNT(*) as total')
            ->groupBy('c.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topSubcategories = (clone $findingsBase)
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->selectRaw('s.name as label, COUNT(*) as total')
            ->groupBy('s.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topItems = (clone $findingsBase)
            ->join('project_review_items as i', 'i.id', '=', 'f.item_id')
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->join('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->selectRaw('c.name as category, s.name as subcategory, i.name as item, COUNT(*) as total')
            ->groupBy('c.name', 's.name', 'i.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $topUsers = (clone $findingsBase)
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->selectRaw("u.name as label, SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) as total")
            ->groupBy('u.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $usersRepresentativity = $this->buildUsersRepresentativity($findingsBase, self::USER_REPRESENTATIVITY_LIMIT);

        $cyclesForDecisionStats = $this->applyDecisionDateFilters((clone $cyclesBase));

        $userErrorPercent = $this->buildUserTemporalStats($cyclesBase)->take(15)->values();

        $mainErrorByUser = (clone $findingsBase)
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
            ->map(function ($rows) {
                $top = $rows->first();
                if (!$top) {
                    return '---';
                }

                return "{$top->category_name} / {$top->subcategory_name} / {$top->item_name}";
            });

        $userErrorPercent = $userErrorPercent->map(function ($row) use ($mainErrorByUser) {
            $row->main_error = $mainErrorByUser->get($row->user_name, '---');
            return $row;
        });

        $companyErrorTotals = (clone $findingsBase)
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->selectRaw("COALESCE(co.name, 'Sem empresa') as label, SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) as total")
            ->groupBy('co.name')
            ->get();

        $topCompanies = collect($companyErrorTotals)
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $origins = (clone $findingsBase)
            ->selectRaw("f.origin as label, SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) as total")
            ->groupBy('f.origin')
            ->orderByDesc('total')
            ->havingRaw('SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) > 0')
            ->get();

        $companyAnalysisCount = (clone $cyclesForDecisionStats)
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->whereIn('cy.decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->selectRaw("COALESCE(co.name, 'Sem empresa') as company_name, COUNT(*) as total_analysis")
            ->groupBy('co.name')
            ->pluck('total_analysis', 'company_name');

        $mainErrorByCompany = (clone $findingsBase)
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->leftJoin('project_review_categories as c', 'c.id', '=', 's.category_id')
            ->leftJoin('project_review_items as i', 'i.id', '=', 'f.item_id')
            ->selectRaw("
                COALESCE(co.name, 'Sem empresa') as company_name,
                COALESCE(c.name, 'Sem categoria') as category_name,
                COALESCE(s.name, 'Sem subcategoria') as subcategory_name,
                COALESCE(i.name, 'Estrutura sem item') as item_name,
                SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) as total
            ")
            ->groupBy('co.name', 'c.name', 's.name', 'i.name')
            ->orderBy('company_name')
            ->orderByDesc('total')
            ->get()
            ->groupBy('company_name')
            ->map(function ($rows) {
                $top = $rows->first();
                if (!$top) {
                    return '---';
                }
                return "{$top->category_name} / {$top->subcategory_name} / {$top->item_name}";
            });

        $companyErrorSummary = collect($companyErrorTotals)
            ->sortByDesc('total')
            ->values()
            ->map(function (object $row) use ($companyAnalysisCount, $mainErrorByCompany) {
                $label = (string) ($row->label ?? '');
                $analysisCount = (int) $companyAnalysisCount->get($label, 0);
                $errors = (int) ($row->total ?? 0);
                return (object) [
                    'company_name' => $label,
                    'error_total' => $errors,
                    'analysis_total' => $analysisCount,
                    'errors_per_analysis' => $analysisCount > 0 ? round($errors / $analysisCount, 2) : 0,
                    'main_error' => $mainErrorByCompany->get($label, '---'),
                ];
            });

        $rejectionsPerProduction = DB::table('project_review_findings as f')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'f.cycle_id')
            ->join('productions as p', 'p.id', '=', 'cy.production_id')
            ->leftJoin('notes as n', 'n.id', '=', 'p.note_id')
            ->whereIn('p.id', $productionIds)
            ->where('cy.decision', 'REJECTED')
            ->selectRaw("
                COALESCE(n.note, CONCAT('PROD #', p.id)) as label,
                SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) as total
            ")
            ->groupBy('p.id', 'n.note')
            ->havingRaw('SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) > 0')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $totalProductions = $productionIds->count();
        $withRejection = DB::table('project_review_cycles')
            ->whereIn('production_id', $productionIds)
            ->where('decision', 'REJECTED')
            ->distinct('production_id')
            ->count('production_id');

        $cyclesSubmittedCount = (clone $cyclesBase)
            ->whereNotNull('cy.submitted_at')
            ->count();

        $cyclesRejectedCount = (clone $this->applyRejectedDateFilter((clone $cyclesBase)))
            ->where('cy.decision', 'REJECTED')
            ->count();

        $cyclesApprovedWithRemarksCount = (clone $this->applyApprovedDateFilter((clone $cyclesBase)))
            ->where('cy.decision', 'APPROVED_WITH_REMARKS')
            ->count();

        $cyclesApprovedWithoutRemarksCount = (clone $this->applyApprovedDateFilter((clone $cyclesBase)))
            ->where('cy.decision', 'APPROVED')
            ->count();

        $cyclesApprovedCount = $cyclesApprovedWithRemarksCount + $cyclesApprovedWithoutRemarksCount;
        $cyclesWaitingAnalysisCount = (clone $cyclesBase)
            ->whereNotNull('cy.submitted_at')
            ->where('cy.decision', 'PENDING')
            ->count();

        $firstPassDen = DB::table('project_review_cycles')
            ->whereIn('production_id', $productionIds)
            ->where('round_number', 1)
            ->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])
            ->count();

        $firstPassNum = DB::table('project_review_cycles')
            ->whereIn('production_id', $productionIds)
            ->where('round_number', 1)
            ->whereIn('decision', ['APPROVED', 'APPROVED_WITH_REMARKS'])
            ->count();

        $avgSendToDecisionHours = (float) (clone $cyclesForDecisionStats)
            ->whereNotNull('submitted_at')
            ->whereNotNull('decided_at')
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
                function ($join) {
                    $join->on('first_cycle.production_id', '=', 'c.production_id');
                }
            )
            ->whereIn('c.production_id', $productionIds)
            ->whereIn('c.decision', ['APPROVED', 'APPROVED_WITH_REMARKS'])
            ->whereNotNull('c.decided_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, first_cycle.first_submitted_at, c.decided_at)) as avg_hours')
            ->value('avg_hours');

        $costSummary = $this->buildCostVariationSummary($productionIds);
        $timeline = $this->buildProjectReviewTimeline($cyclesBase);

        return [
            'summary' => [
                'total_productions' => $totalProductions,
                'with_rejection' => $withRejection,
                'without_rejection' => max(0, $totalProductions - $withRejection),
                'cycles_submitted_count' => $cyclesSubmittedCount,
                'cycles_rejected_count' => $cyclesRejectedCount,
                'cycles_approved_count' => $cyclesApprovedCount,
                'cycles_approved_with_remarks_count' => $cyclesApprovedWithRemarksCount,
                'cycles_approved_without_remarks_count' => $cyclesApprovedWithoutRemarksCount,
                'cycles_waiting_analysis_count' => $cyclesWaitingAnalysisCount,
                'first_pass_approval_pct' => $firstPassDen > 0 ? round(($firstPassNum / $firstPassDen) * 100, 2) : 0,
                'avg_send_to_decision_hours' => round($avgSendToDecisionHours ?: 0, 2),
                'avg_reject_to_resubmit_hours' => round($avgRejectToResubmitHours ?: 0, 2),
                'avg_total_until_final_approval_hours' => round($avgTotalUntilFinalApprovalHours ?: 0, 2),
                'planned_total_cost' => round($costSummary['planned_total_cost'], 2),
                'revised_total_cost' => round($costSummary['revised_total_cost'], 2),
                'planned_company_total_cost' => round($costSummary['planned_company_total_cost'], 2),
                'planned_client_total_cost' => round($costSummary['planned_client_total_cost'], 2),
                'revised_company_total_cost' => round($costSummary['revised_company_total_cost'], 2),
                'revised_client_total_cost' => round($costSummary['revised_client_total_cost'], 2),
                'company_net_variation_cost' => round($costSummary['company_net_variation_cost'], 2),
                'client_net_variation_cost' => round($costSummary['client_net_variation_cost'], 2),
                'economy_total_cost' => round($costSummary['economy_total_cost'], 2),
                'increase_total_cost' => round($costSummary['increase_total_cost'], 2),
                'net_variation_cost' => round($costSummary['net_variation_cost'], 2),
                'maintained_orders_count' => (int) $costSummary['maintained_orders_count'],
            ],
            'charts' => [
                'categories' => $this->toChartData($topCategories),
                'subcategories' => $this->toChartData($topSubcategories),
                'items' => $this->toChartData($topItems->map(fn($r) => (object) ['label' => "{$r->subcategory} - {$r->item}", 'total' => $r->total])),
                'users_error_count' => $this->toChartData($topUsers),
                'users_error_pct' => $usersRepresentativity,
                'companies' => $this->toChartData($topCompanies),
                'origins' => $this->toChartData($origins),
                'rejections_per_production' => $this->toChartData($rejectionsPerProduction),
                'timeline_submitted' => $this->toChartData($timeline->map(fn ($row) => (object) ['label' => $row->day, 'total' => $row->submitted])),
                'timeline_rejected' => $this->toChartData($timeline->map(fn ($row) => (object) ['label' => $row->day, 'total' => $row->rejected])),
                'timeline_approved_with_remarks' => $this->toChartData($timeline->map(fn ($row) => (object) ['label' => $row->day, 'total' => $row->approved_with_remarks])),
                'timeline_approved_without_remarks' => $this->toChartData($timeline->map(fn ($row) => (object) ['label' => $row->day, 'total' => $row->approved_without_remarks])),
            ],
            'tables' => [
                'top_items' => $topItems,
                'top_subcategories' => $topSubcategories,
                'top_categories' => $topCategories,
                'top_users' => $topUsers,
                'user_error_percent' => $userErrorPercent,
                'top_companies' => $topCompanies,
                'company_error_summary' => $companyErrorSummary,
                'origins' => $origins,
                'rejections_per_production' => $rejectionsPerProduction,
                'timeline' => $timeline,
            ],
        ];
    }

    private function toChartData($rows): array
    {
        return [
            'labels' => collect($rows)->pluck('label')->values()->all(),
            'data' => collect($rows)->pluck('total')->map(fn($v) => (int) $v)->values()->all(),
        ];
    }

    private function buildCostVariationSummary($productionIds): array
    {
        if (collect($productionIds)->isEmpty()) {
            return [
                'planned_total_cost' => 0,
                'revised_total_cost' => 0,
                'planned_company_total_cost' => 0,
                'planned_client_total_cost' => 0,
                'revised_company_total_cost' => 0,
                'revised_client_total_cost' => 0,
                'company_net_variation_cost' => 0,
                'client_net_variation_cost' => 0,
                'economy_total_cost' => 0,
                'increase_total_cost' => 0,
                'net_variation_cost' => 0,
                'maintained_orders_count' => 0,
            ];
        }

        $rows = DB::table('project_review_orders as o')
            ->join('project_review_cycles as cy', 'cy.id', '=', 'o.cycle_id')
            ->whereIn('cy.production_id', $productionIds)
            ->selectRaw('cy.production_id, cy.round_number, o.id as order_id, o.order_number, o.total_cost, o.company_cost, o.client_cost')
            ->orderBy('cy.production_id')
            ->orderBy('cy.round_number')
            ->orderBy('o.id')
            ->get();

        $planned = 0.0;
        $revised = 0.0;
        $plannedCompany = 0.0;
        $plannedClient = 0.0;
        $revisedCompany = 0.0;
        $revisedClient = 0.0;
        $economy = 0.0;
        $increase = 0.0;
        $maintainedCount = 0;

        $rows->groupBy('production_id')->each(function ($productionRows) use (&$planned, &$revised, &$plannedCompany, &$plannedClient, &$revisedCompany, &$revisedClient, &$economy, &$increase, &$maintainedCount) {
            $byRound = collect($productionRows)->groupBy('round_number');
            if ($byRound->isEmpty()) {
                return;
            }

            $prefixSeries = $this->buildPrefixSeriesByRound($byRound);
            $hasPrefixData = false;

            foreach (['170', '190', '150', '200'] as $prefix) {
                $series = collect($prefixSeries[$prefix] ?? [])->values();
                if ($series->isEmpty()) {
                    continue;
                }

                $hasPrefixData = true;
                $first = (array) $series->first();
                $last = (array) $series->last();

                $planned += (float) ($first['total'] ?? 0);
                $revised += (float) ($last['total'] ?? 0);
                $plannedCompany += (float) ($first['company'] ?? 0);
                $plannedClient += (float) ($first['client'] ?? 0);
                $revisedCompany += (float) ($last['company'] ?? 0);
                $revisedClient += (float) ($last['client'] ?? 0);

                if ($series->count() >= 2) {
                    $delta = round(((float) ($last['total'] ?? 0)) - ((float) ($first['total'] ?? 0)), 2);
                    if ($delta > 0) {
                        $increase += $delta;
                    } elseif ($delta < 0) {
                        $economy += abs($delta);
                    } else {
                        $maintainedCount++;
                    }
                }
            }

            if (!$hasPrefixData) {
                $roundTotals = $byRound
                    ->sortKeys()
                    ->map(function ($roundRows) {
                        $rows = collect($roundRows);
                        return [
                            'total' => (float) $rows->sum('total_cost'),
                            'company' => (float) $rows->sum('company_cost'),
                            'client' => (float) $rows->sum('client_cost'),
                        ];
                    })
                    ->values();

                $firstTotals = (array) $roundTotals->first();
                $lastTotals = (array) $roundTotals->last();
                $planned += (float) ($firstTotals['total'] ?? 0);
                $revised += (float) ($lastTotals['total'] ?? 0);
                $plannedCompany += (float) ($firstTotals['company'] ?? 0);
                $plannedClient += (float) ($firstTotals['client'] ?? 0);
                $revisedCompany += (float) ($lastTotals['company'] ?? 0);
                $revisedClient += (float) ($lastTotals['client'] ?? 0);

                if ($roundTotals->count() >= 2) {
                    $delta = round(((float) ($lastTotals['total'] ?? 0)) - ((float) ($firstTotals['total'] ?? 0)), 2);
                    if ($delta > 0) {
                        $increase += $delta;
                    } elseif ($delta < 0) {
                        $economy += abs($delta);
                    } else {
                        $maintainedCount++;
                    }
                }
            }
        });

        return [
            'planned_total_cost' => $planned,
            'revised_total_cost' => $revised,
            'planned_company_total_cost' => $plannedCompany,
            'planned_client_total_cost' => $plannedClient,
            'revised_company_total_cost' => $revisedCompany,
            'revised_client_total_cost' => $revisedClient,
            'company_net_variation_cost' => $revisedCompany - $plannedCompany,
            'client_net_variation_cost' => $revisedClient - $plannedClient,
            'economy_total_cost' => $economy,
            'increase_total_cost' => $increase,
            'net_variation_cost' => $revised - $planned,
            'maintained_orders_count' => $maintainedCount,
        ];
    }

    private function buildPrefixSeriesByRound($byRound): array
    {
        $series = collect(['170', '190', '150', '200'])
            ->mapWithKeys(fn ($prefix) => [$prefix => []])
            ->all();

        collect($byRound)->sortKeys()->each(function ($roundRows) use (&$series) {
            $latestByPrefix = [];

            foreach (collect($roundRows) as $row) {
                $prefix = $this->extractOrderPrefix((string) ($row->order_number ?? ''));
                if ($prefix === null) {
                    continue;
                }

                $latestByPrefix[$prefix] = [
                    'total' => (float) ($row->total_cost ?? 0),
                    'company' => (float) ($row->company_cost ?? 0),
                    'client' => (float) ($row->client_cost ?? 0),
                ];
            }

            foreach (['170', '190', '150', '200'] as $prefix) {
                if (array_key_exists($prefix, $latestByPrefix)) {
                    $series[$prefix][] = $latestByPrefix[$prefix];
                }
            }
        });

        return $series;
    }

    private function extractOrderPrefix(string $orderNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $orderNumber);
        if (!$digits || strlen($digits) < 3) {
            return null;
        }

        $prefix = substr($digits, 0, 3);
        return in_array($prefix, ['170', '190', '150', '200'], true) ? $prefix : null;
    }

    private function buildProjectReviewTimeline($cyclesBase)
    {
        $timeline = (clone $cyclesBase)
            ->selectRaw("
                DATE(cy.submitted_at) as submitted_day,
                DATE(cy.decided_at) as decided_day,
                cy.decision
            ")
            ->get()
            ->reduce(function ($carry, $row) {
                $submittedDay = $row->submitted_day;
                $decidedDay = $row->decided_day;

                if ($submittedDay) {
                    if (!isset($carry[$submittedDay])) {
                        $carry[$submittedDay] = [
                            'day' => $submittedDay,
                            'submitted' => 0,
                            'rejected' => 0,
                            'approved_with_remarks' => 0,
                            'approved_without_remarks' => 0,
                        ];
                    }
                    $carry[$submittedDay]['submitted']++;
                }

                if ($decidedDay) {
                    if (!isset($carry[$decidedDay])) {
                        $carry[$decidedDay] = [
                            'day' => $decidedDay,
                            'submitted' => 0,
                            'rejected' => 0,
                            'approved_with_remarks' => 0,
                            'approved_without_remarks' => 0,
                        ];
                    }

                    if ($row->decision === 'REJECTED') {
                        $carry[$decidedDay]['rejected']++;
                    } elseif ($row->decision === 'APPROVED_WITH_REMARKS') {
                        $carry[$decidedDay]['approved_with_remarks']++;
                    } elseif ($row->decision === 'APPROVED') {
                        $carry[$decidedDay]['approved_without_remarks']++;
                    }
                }

                return $carry;
            }, []);

        return collect($timeline)
            ->sortKeys()
            ->map(fn ($row) => (object) $row)
            ->values();
    }

    private function buildUsersRepresentativity($findingsBase, int $limit = 8): array
    {
        $rows = (clone $findingsBase)
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->selectRaw("u.name as label, SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) as total")
            ->groupBy('u.name')
            ->havingRaw('SUM(CASE WHEN f.item_id IS NULL THEN 0 ELSE COALESCE(f.quantity, 1) END) > 0')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => [], 'data' => []];
        }

        $totalErrors = (float) $rows->sum('total');
        if ($totalErrors <= 0) {
            return ['labels' => [], 'data' => []];
        }

        $topRows = $rows->take($limit)->values();
        $remainingTotal = (float) $rows->slice($limit)->sum('total');

        $labels = [];
        $data = [];

        foreach ($topRows as $row) {
            $labels[] = $row->label;
            $data[] = round((((float) $row->total) / $totalErrors) * 100, 2);
        }

        if ($remainingTotal > 0) {
            $labels[] = 'Outros';
            $data[] = round(($remainingTotal / $totalErrors) * 100, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function buildUserTemporalStats($cyclesBase)
    {
        $rows = (clone $cyclesBase)
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->selectRaw("
                u.name as user_name,
                cy.submitted_at,
                cy.decided_at,
                cy.decision
            ")
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $stats = [];

        foreach ($rows as $row) {
            $userName = (string) ($row->user_name ?? 'Sem usuário');

            if (!isset($stats[$userName])) {
                $stats[$userName] = [
                    'user_name' => $userName,
                    'submitted_cycles' => 0,
                    'analyzed_cycles' => 0,
                    'rejected_cycles' => 0,
                    'approved_with_remarks_cycles' => 0,
                    'approved_without_remarks_cycles' => 0,
                    'approved_cycles' => 0,
                    'total_cycles' => 0,
                    'error_pct' => 0.0,
                ];
            }

            if (!is_null($row->submitted_at)) {
                $stats[$userName]['submitted_cycles']++;
            }

            if ($row->decision === 'REJECTED' && $this->passesRejectedDecisionDateFilter($row->decided_at)) {
                $stats[$userName]['rejected_cycles']++;
                $stats[$userName]['analyzed_cycles']++;
                $stats[$userName]['total_cycles']++;
                continue;
            }

            if ($row->decision === 'APPROVED_WITH_REMARKS' && $this->passesApprovedDecisionDateFilter($row->decided_at)) {
                $stats[$userName]['approved_with_remarks_cycles']++;
                $stats[$userName]['approved_cycles']++;
                $stats[$userName]['analyzed_cycles']++;
                $stats[$userName]['total_cycles']++;
                continue;
            }

            if ($row->decision === 'APPROVED' && $this->passesApprovedDecisionDateFilter($row->decided_at)) {
                $stats[$userName]['approved_without_remarks_cycles']++;
                $stats[$userName]['approved_cycles']++;
                $stats[$userName]['analyzed_cycles']++;
                $stats[$userName]['total_cycles']++;
            }
        }

        return collect($stats)
            ->map(function ($row) {
                $row['error_pct'] = $row['total_cycles'] > 0
                    ? round(($row['rejected_cycles'] / $row['total_cycles']) * 100, 2)
                    : 0.0;

                return (object) $row;
            })
            ->sortByDesc('rejected_cycles')
            ->sortByDesc('error_pct')
            ->values();
    }

    private function passesApprovedDecisionDateFilter($decidedAt): bool
    {
        if (is_null($decidedAt)) {
            return false;
        }

        $decisionDate = date('Y-m-d', strtotime((string) $decidedAt));
        if ($this->approved_from && $decisionDate < $this->approved_from) {
            return false;
        }
        if ($this->approved_to && $decisionDate > $this->approved_to) {
            return false;
        }

        return true;
    }

    private function passesRejectedDecisionDateFilter($decidedAt): bool
    {
        if (is_null($decidedAt)) {
            return false;
        }

        $decisionDate = date('Y-m-d', strtotime((string) $decidedAt));
        if ($this->rejected_from && $decisionDate < $this->rejected_from) {
            return false;
        }
        if ($this->rejected_to && $decisionDate > $this->rejected_to) {
            return false;
        }

        return true;
    }

    private function applyApprovedDateFilter($query)
    {
        if ($this->approved_from) {
            $query->whereDate('cy.decided_at', '>=', $this->approved_from);
        }
        if ($this->approved_to) {
            $query->whereDate('cy.decided_at', '<=', $this->approved_to);
        }

        return $query;
    }

    private function applyRejectedDateFilter($query)
    {
        if ($this->rejected_from) {
            $query->whereDate('cy.decided_at', '>=', $this->rejected_from);
        }
        if ($this->rejected_to) {
            $query->whereDate('cy.decided_at', '<=', $this->rejected_to);
        }

        return $query;
    }

    private function applyDecisionDateFilters($query)
    {
        $hasApprovedFilter = (bool) ($this->approved_from || $this->approved_to);
        $hasRejectedFilter = (bool) ($this->rejected_from || $this->rejected_to);

        if (!$hasApprovedFilter && !$hasRejectedFilter) {
            return $query;
        }

        $query->where(function ($q) use ($hasApprovedFilter, $hasRejectedFilter) {
            if ($hasApprovedFilter) {
                $q->orWhere(function ($approved) {
                    $approved->whereIn('cy.decision', ['APPROVED', 'APPROVED_WITH_REMARKS']);
                    $this->applyApprovedDateFilter($approved);
                });
            } else {
                $q->orWhereIn('cy.decision', ['APPROVED', 'APPROVED_WITH_REMARKS']);
            }

            if ($hasRejectedFilter) {
                $q->orWhere(function ($rejected) {
                    $rejected->where('cy.decision', 'REJECTED');
                    $this->applyRejectedDateFilter($rejected);
                });
            } else {
                $q->orWhere('cy.decision', 'REJECTED');
            }
        });

        return $query;
    }

    public function render()
    {
        $metrics = $this->metrics();

        return view('livewire.project-review.governance-dashboard', [
            'companies' => $this->companies,
            'users' => $this->users,
            'statusOptions' => $this->statusOptions,
            'summary' => $metrics['summary'],
            'charts' => $metrics['charts'],
            'tables' => $metrics['tables'],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function filters(): array
    {
        $companyIds = auth()->user()?->contract ? $this->allowedCompanyIds()->all() : [];

        return [
            'period_from' => $this->period_from,
            'period_to' => $this->period_to,
            'company_id' => $this->company_id,
            'company_ids' => $companyIds,
            'user_id' => $this->user_id,
            'final_status' => $this->final_status,
            'rejection_filter' => $this->rejection_filter,
            'finalized_from' => $this->finalized_from,
            'finalized_to' => $this->finalized_to,
            'approved_from' => $this->approved_from,
            'approved_to' => $this->approved_to,
            'rejected_from' => $this->rejected_from,
            'rejected_to' => $this->rejected_to,
        ];
    }
}
