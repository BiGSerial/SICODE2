<?php

namespace App\Http\Livewire\Legal\Management;

use App\Models\Legal\{LegalDemand, LegalDemandAssignment, LegalImportBatch};
use App\Models\User;
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
            $query->where('origin_area_name', 'like', "%{$this->areaFilter}%");
        }

        if ($this->controllerFilter) {
            $query->where('controller_user_id', $this->controllerFilter);
        }

        if ($this->regionalFilter) {
            $query->where('regional', 'like', "%{$this->regionalFilter}%");
        }

        return $query;
    }

    private function dashboardData(): array
    {
        return Cache::remember($this->cacheKey(), 180, function () {
            [$from, $to] = $this->periodDates();

            $base = fn () => $this->applyGlobalFilters(LegalDemand::query());

            $kpis = [
                'total_active' => $base()->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])->count(),
                'overdue'      => $base()->overdue()->count(),
                'overdue_7d'   => $base()->overdue()->whereRaw('DATEDIFF(NOW(), source_due_at) > 7')->count(),
                'in_field'     => $base()->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count(),
                'resolved'     => $base()->whereIn('internal_status', ['closed_internal', 'closed_external'])->whereBetween('closed_at', [$from, $to])->count(),
                'resolved_int' => $base()->where('internal_status', 'closed_internal')->whereBetween('closed_at', [$from, $to])->count(),
                'resolved_ext' => $base()->where('internal_status', 'closed_external')->whereBetween('closed_at', [$from, $to])->count(),
            ];

            // SLA: demandas fechadas no prazo / total fechadas no período
            $resolvedInPeriod = $base()->whereIn('internal_status', ['closed_internal', 'closed_external'])->whereBetween('closed_at', [$from, $to])->count();
            $resolvedOnTime   = $base()->whereIn('internal_status', ['closed_internal', 'closed_external'])->whereBetween('closed_at', [$from, $to])
                ->whereRaw('closed_at <= source_due_at OR source_due_at IS NULL')->count();
            $kpis['sla'] = $resolvedInPeriod > 0 ? round(($resolvedOnTime / $resolvedInPeriod) * 100) : null;

            // Funil por status
            $statusFunnel = [
                'new_imported' => $base()->where('internal_status', 'new_imported')->count(),
                'triage'       => $base()->whereIn('internal_status', ['triage', 'waiting_controller_action'])->count(),
                'in_field'     => $base()->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])->count(),
                'returned'     => $base()->whereIn('internal_status', ['returned_by_field', 'under_controller_review', 'returned_for_correction'])->count(),
                'ready_close'  => $base()->where('internal_status', 'ready_to_close_external')->count(),
                'closed'       => $base()->whereIn('internal_status', ['closed_internal', 'closed_external'])->count(),
            ];

            // Distribuição por tipo
            $byType = $base()->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->selectRaw('source_type, COUNT(*) as total')
                ->groupBy('source_type')
                ->pluck('total', 'source_type');

            // Top 5 áreas
            $topAreas = $base()->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->selectRaw('origin_area_name, COUNT(*) as total')
                ->groupBy('origin_area_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            // Heatmap: criticidade × tipo
            $heatmap = [];

            foreach (['injunction', 'sentence', 'subsidy'] as $type) {
                $heatmap[$type] = [
                    'overdue' => $base()->where('source_type', $type)->overdue()->count(),
                    '3d'      => $base()->where('source_type', $type)->whereBetween('source_due_at', [now(), now()->addDays(3)])->count(),
                    '7d'      => $base()->where('source_type', $type)->whereBetween('source_due_at', [now()->addDays(3), now()->addDays(7)])->count(),
                    'no_date' => $base()->where('source_type', $type)->whereNull('source_due_at')->count(),
                ];
            }

            // Ranking executantes
            $executors = LegalDemandAssignment::query()
                ->join('users', 'users.id', '=', 'legal_demand_assignments.to_user_id')
                ->selectRaw('users.id, users.name, COUNT(*) as active_count, SUM(CASE WHEN legal_demands.source_due_at < NOW() THEN 1 ELSE 0 END) as overdue_count')
                ->join('legal_demands', 'legal_demands.id', '=', 'legal_demand_assignments.legal_demand_id')
                ->whereNotIn('legal_demand_assignments.status', ['cancelled', 'closed', 'answered'])
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('active_count')
                ->limit(10)
                ->get();

            // Top 10 críticos
            $criticalDemands = $base()
                ->with(['currentAssignee'])
                ->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
                ->orderByRaw('ISNULL(source_due_at) ASC')
                ->orderBy('source_due_at', 'asc')
                ->limit(10)
                ->get();

            // Alertas
            $alerts = [];

            if ($kpis['overdue'] > 0) {
                $withoutResponsible = $base()->overdue()->whereNull('current_assigned_user_id')->count();

                if ($withoutResponsible > 0) {
                    $alerts[] = ['level' => 'danger', 'message' => "{$withoutResponsible} demandas vencidas sem responsável designado"];
                }
            }
            $lastBatch = LegalImportBatch::latest()->first();

            if ($lastBatch && \Carbon\Carbon::parse($lastBatch->created_at)->diffInHours(now()) > 24) {
                $alerts[] = ['level' => 'warning', 'message' => 'Batch de importação não executado há mais de 24h'];
            }
            $needsReview = $base()->where('needs_identity_review', true)->count();

            if ($needsReview > 0) {
                $alerts[] = ['level' => 'accent', 'message' => "{$needsReview} demandas precisam revisão de identidade"];
            }

            return compact('kpis', 'statusFunnel', 'byType', 'topAreas', 'heatmap', 'executors', 'criticalDemands', 'alerts');
        });
    }

    public function render()
    {
        $data        = $this->dashboardData();
        $controllers = User::whereIn('id', LegalDemand::whereNotNull('controller_user_id')->pluck('controller_user_id')->unique())->orderBy('name')->get();

        return view('livewire.legal.management.legal-dashboard', array_merge($data, ['controllers' => $controllers]));
    }
}
