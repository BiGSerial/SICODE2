<?php

namespace App\Http\Livewire\Legal\Management;

use App\Models\Legal\{LegalDemand, LegalDemandAssignment, LegalImportBatch};
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class LegalDashboard extends Component
{
    public string  $period = 'current_month';

    public ?string $periodFrom = null;

    public ?string $periodTo = null;

    public string $sourceTypeFilter = '';

    public string $areaFilter = '';

    public string $controllerFilter = '';

    public string $regionalFilter = '';

    public bool $autoRefresh = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('legal.manager'), 403);
    }

    public function refreshData(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return "legal_dashboard_{$this->period}_{$this->sourceTypeFilter}_{$this->areaFilter}_{$this->controllerFilter}_{$this->regionalFilter}";
    }

    private function periodDates(): array
    {
        return match ($this->period) {
            'last_30d' => [now()->subDays(30), now()],
            'last_90d' => [now()->subDays(90), now()],
            'custom'   => [$this->periodFrom ? new \DateTime($this->periodFrom) : now()->startOfMonth(), $this->periodTo ? new \DateTime($this->periodTo) : now()],
            default    => [now()->startOfMonth(), now()],
        };
    }

    private function applyGlobalFilters($query)
    {
        if ($this->sourceTypeFilter) {
            $query->where('source_type', $this->sourceTypeFilter);
        }

        if ($this->areaFilter) {
            $query->where('requesting_area_name', 'like', "%{$this->areaFilter}%");
        }

        if ($this->controllerFilter) {
            $query->where('controller_user_id', $this->controllerFilter);
        }

        if ($this->regionalFilter) {
            $query->where('responsible_area_name', 'like', "%{$this->regionalFilter}%");
        }

        return $query;
    }

    private function dashboardData(): array
    {
        return Cache::remember($this->cacheKey(), 180, function () {
            [$from, $to] = $this->periodDates();

            // active = externallyActive + filtros globais; closed = externally OR internally closed
            $active = fn () => $this->applyGlobalFilters(
                LegalDemand::externallyActive()
                    ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
            );
            $closed = fn () => $this->applyGlobalFilters(LegalDemand::query())->where(function ($q) {
                $q->whereIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored']);
                $q->orWhereIn('source_status_group', ['closed_done', 'closed_cancelled']);
            });

            $kpis = [
                'total_active' => $active()->whereNotIn('internal_status', ['cancelled', 'ignored'])->count(),
                'overdue'      => $active()->overdue()->count(),
                'overdue_7d'   => $active()->overdue()->whereRaw('DATEDIFF(NOW(), source_due_at) > 7')->count(),
                'in_field'     => $active()->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count(),
                'resolved'     => $closed()->whereIn('internal_status', ['closed_internal', 'closed_external'])->whereBetween('closed_at', [$from, $to])->count(),
                'resolved_int' => $closed()->where('internal_status', 'closed_internal')->whereBetween('closed_at', [$from, $to])->count(),
                'resolved_ext' => $closed()->where('internal_status', 'closed_external')->whereBetween('closed_at', [$from, $to])->count(),
            ];

            // SLA: demandas internamente fechadas no prazo / total fechadas no período
            $resolvedInPeriod = $closed()->whereIn('internal_status', ['closed_internal', 'closed_external'])->whereBetween('closed_at', [$from, $to])->count();
            $resolvedOnTime   = $closed()->whereIn('internal_status', ['closed_internal', 'closed_external'])->whereBetween('closed_at', [$from, $to])
                ->whereRaw('closed_at <= source_due_at OR source_due_at IS NULL')->count();
            $kpis['sla'] = $resolvedInPeriod > 0 ? round(($resolvedOnTime / $resolvedInPeriod) * 100) : null;

            // SLAs operacionais do fluxo (base em assignments no período)
            $assignmentsBase = LegalDemandAssignment::query()
                ->join('legal_demands', 'legal_demands.id', '=', 'legal_demand_assignments.legal_demand_id')
                ->whereBetween('legal_demand_assignments.sent_at', [$from, $to]);

            $this->applyGlobalFilters($assignmentsBase);

            /** @var Collection<int, object> $assignments */
            $assignments = $assignmentsBase
                ->select([
                    'legal_demand_assignments.sent_at',
                    'legal_demand_assignments.received_at',
                    'legal_demand_assignments.answered_at',
                    'legal_demand_assignments.metadata',
                    'legal_demands.first_seen_at',
                    'legal_demands.closed_at',
                ])
                ->get();

            $totalAssignments = max(1, $assignments->count());
            $receivedCount = $assignments->filter(fn ($a) => !empty($a->received_at))->count();
            $answeredCount = $assignments->filter(fn ($a) => !empty($a->answered_at))->count();

            $controllerDispatchAvgHours = $assignments
                ->filter(fn ($a) => !empty($a->first_seen_at) && !empty($a->sent_at))
                ->map(fn ($a) => \Carbon\Carbon::parse($a->first_seen_at)->diffInMinutes(\Carbon\Carbon::parse($a->sent_at), false))
                ->filter(fn ($m) => is_numeric($m) && $m >= 0)
                ->avg();

            $executorReceiveAvgHours = $assignments
                ->filter(fn ($a) => !empty($a->received_at) && !empty($a->sent_at))
                ->map(fn ($a) => \Carbon\Carbon::parse($a->sent_at)->diffInMinutes(\Carbon\Carbon::parse($a->received_at), false))
                ->filter(fn ($m) => is_numeric($m) && $m >= 0)
                ->avg();

            $executorAnswerAvgHours = $assignments
                ->filter(fn ($a) => !empty($a->answered_at) && !empty($a->received_at))
                ->map(fn ($a) => \Carbon\Carbon::parse($a->received_at)->diffInMinutes(\Carbon\Carbon::parse($a->answered_at), false))
                ->filter(fn ($m) => is_numeric($m) && $m >= 0)
                ->avg();

            $onTimeByAssignmentDue = $assignments
                ->filter(function ($a) {
                    $meta = is_array($a->metadata) ? $a->metadata : (json_decode((string) ($a->metadata ?? ''), true) ?: []);
                    $dueAt = data_get($meta, 'due_at');
                    return !empty($a->answered_at) && !empty($dueAt);
                })
                ->filter(function ($a) {
                    $meta = is_array($a->metadata) ? $a->metadata : (json_decode((string) ($a->metadata ?? ''), true) ?: []);
                    $dueAt = data_get($meta, 'due_at');
                    return \Carbon\Carbon::parse($a->answered_at)->lte(\Carbon\Carbon::parse($dueAt));
                })
                ->count();

            $withDue = $assignments->filter(function ($a) {
                $meta = is_array($a->metadata) ? $a->metadata : (json_decode((string) ($a->metadata ?? ''), true) ?: []);
                return !empty(data_get($meta, 'due_at'));
            })->count();

            $controllerCloseRate = $active()->whereNotNull('closed_at')->count();
            $controllerTotalManaged = max(1, $active()->count() + $closed()->count());

            $slaOps = [
                'controller_dispatch_avg_h' => $controllerDispatchAvgHours !== null ? round($controllerDispatchAvgHours / 60, 1) : null,
                'executor_receive_avg_h' => $executorReceiveAvgHours !== null ? round($executorReceiveAvgHours / 60, 1) : null,
                'executor_answer_avg_h' => $executorAnswerAvgHours !== null ? round($executorAnswerAvgHours / 60, 1) : null,
                'executor_receive_rate' => round(($receivedCount / $totalAssignments) * 100),
                'executor_answer_rate' => round(($answeredCount / $totalAssignments) * 100),
                'answer_on_due_rate' => $withDue > 0 ? round(($onTimeByAssignmentDue / $withDue) * 100) : null,
                'controller_close_rate' => round(($controllerCloseRate / $controllerTotalManaged) * 100),
            ];

            // Funil por status (ativos excluem externamente encerrados)
            $statusFunnel = [
                'new_imported' => $active()->where('internal_status', 'new_imported')->count(),
                'triage'       => $active()->whereIn('internal_status', ['triage', 'waiting_controller_action'])->count(),
                'in_field'     => $active()->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count(),
                'returned'     => $active()->whereIn('internal_status', ['returned_by_field', 'under_controller_review', 'returned_for_correction'])->count(),
                'ready_close'  => $active()->where('internal_status', 'ready_to_close_external')->count(),
                'closed'       => $closed()->count(),
            ];

            // Distribuição por tipo (apenas ativos)
            $byType = $active()->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->selectRaw('source_type, COUNT(*) as total')
                ->groupBy('source_type')
                ->pluck('total', 'source_type');

            // Top 5 áreas (apenas ativos)
            $topAreas = $active()->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->selectRaw('requesting_area_name as origin_area_name, COUNT(*) as total')
                ->groupBy('requesting_area_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            // Heatmap: criticidade × tipo (apenas ativos)
            $heatmap = [];

            foreach (['injunction', 'sentence', 'subsidy'] as $type) {
                $heatmap[$type] = [
                    'overdue' => $active()->where('source_type', $type)->overdue()->count(),
                    '3d'      => $active()->where('source_type', $type)->whereBetween('source_due_at', [now(), now()->addDays(3)])->count(),
                    '7d'      => $active()->where('source_type', $type)->whereBetween('source_due_at', [now()->addDays(3), now()->addDays(7)])->count(),
                    'no_date' => $active()->where('source_type', $type)->whereNull('source_due_at')->count(),
                ];
            }

            // Ranking executantes (atribuições abertas em demandas ativas)
            $executors = LegalDemandAssignment::query()
                ->join('users', 'users.id', '=', 'legal_demand_assignments.to_user_id')
                ->join('legal_demands', 'legal_demands.id', '=', 'legal_demand_assignments.legal_demand_id')
                ->selectRaw('users.id, users.name, COUNT(*) as active_count, SUM(CASE WHEN legal_demands.source_due_at < NOW() AND legal_demands.source_decision_at IS NULL THEN 1 ELSE 0 END) as overdue_count')
                ->whereNotIn('legal_demand_assignments.status', ['cancelled', 'closed', 'answered'])
                ->whereNotIn('legal_demands.source_status_group', ['closed_done', 'closed_cancelled'])
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('active_count')
                ->limit(10)
                ->get();

            // Top 10 críticos (apenas ativos)
            $criticalDemands = $active()
                ->with(['currentAssignee'])
                ->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->orderByRaw('ISNULL(source_due_at) ASC')
                ->orderBy('source_due_at', 'asc')
                ->limit(10)
                ->get();

            // Alertas
            $alerts = [];

            if ($kpis['overdue'] > 0) {
                $withoutResponsible = $active()->overdue()->whereNull('current_assigned_user_id')->count();

                if ($withoutResponsible > 0) {
                    $alerts[] = ['level' => 'danger', 'message' => "{$withoutResponsible} demandas vencidas sem responsável designado"];
                }
            }
            $lastBatch = LegalImportBatch::latest()->first();

            if ($lastBatch && \Carbon\Carbon::parse($lastBatch->created_at)->diffInHours(now()) > 24) {
                $alerts[] = ['level' => 'warning', 'message' => 'Batch de importação não executado há mais de 24h'];
            }
            $needsReview = $active()->where('needs_identity_review', true)->count();

            if ($needsReview > 0) {
                $alerts[] = ['level' => 'accent', 'message' => "{$needsReview} demandas precisam revisão de identidade"];
            }

            $funnelLabels = ['Novas', 'Triagem', 'Em Campo', 'Retornadas', 'Prontas Fechar', 'Encerradas'];
            $funnelData = [
                (int) ($statusFunnel['new_imported'] ?? 0),
                (int) ($statusFunnel['triage'] ?? 0),
                (int) ($statusFunnel['in_field'] ?? 0),
                (int) ($statusFunnel['returned'] ?? 0),
                (int) ($statusFunnel['ready_close'] ?? 0),
                (int) ($statusFunnel['closed'] ?? 0),
            ];

            $typeLabels = ['Liminar', 'Sentença', 'Subsídio'];
            $typeData = [
                (int) ($byType['injunction'] ?? 0),
                (int) ($byType['sentence'] ?? 0),
                (int) ($byType['subsidy'] ?? 0),
            ];

            $areaLabels = $topAreas->pluck('origin_area_name')->map(fn ($v) => (string) ($v ?: '—'))->values()->all();
            $areaData = $topAreas->pluck('total')->map(fn ($v) => (int) $v)->values()->all();

            return compact(
                'kpis',
                'slaOps',
                'statusFunnel',
                'byType',
                'topAreas',
                'heatmap',
                'executors',
                'criticalDemands',
                'alerts',
                'funnelLabels',
                'funnelData',
                'typeLabels',
                'typeData',
                'areaLabels',
                'areaData'
            );
        });
    }

    public function render()
    {
        $data        = $this->dashboardData();
        $controllers = User::whereIn('id', LegalDemand::whereNotNull('controller_user_id')->pluck('controller_user_id')->unique())->orderBy('name')->get();

        return view('livewire.legal.management.legal-dashboard', array_merge($data, ['controllers' => $controllers]));
    }
}
