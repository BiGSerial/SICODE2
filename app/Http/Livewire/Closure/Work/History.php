<?php

namespace App\Http\Livewire\Closure\Work;

use App\Http\Livewire\Closure\Work\Concerns\{ClosureWorkListing, WithOpsWorkFilters};
use App\Services\Closure\ClosureOrderWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithPagination};

/**
 * "Meu histórico" (services.historic): Ordens do usuário já finalizadas, encerradas ou canceladas.
 * O tipo P/M/F exibido é o congelado no encerramento (closed_meta_type), via ClosureMetaClassifier.
 */
class History extends Component
{
    use ClosureWorkListing, WithOpsWorkFilters {
        WithOpsWorkFilters::clearFilters insteadof ClosureWorkListing;
        ClosureWorkListing::clearFilters as protected clearListingFilters;
        WithOpsWorkFilters::statusUserOptions insteadof ClosureWorkListing;
        ClosureWorkListing::statusUserOptions as protected statusUserOptionsFromListing;
    }
    use WithPagination;

    private ?array $memoStatusUserOptions = null;

    public const PER_PAGE = 25;

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
    }

    public function exportQuery(): Builder
    {
        return $this->historyQuery();
    }

    protected function statusFilterLabel(): ?string
    {
        return 'Situação';
    }

    protected function statusFilterValues(): array
    {
        return self::HISTORY_STATUSES;
    }

    public function exportList(): void
    {
        \App\Jobs\Closure\ExportClosureListJob::dispatch(static::class, ['type' => $this->type, 'status' => $this->status, 'search' => $this->search, 'statusUsers' => $this->statusUsers, 'bulkTerms' => $this->bulkTerms, 'filters' => $this->filters], (string) auth()->id());

        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'success', 'title' => 'Exportação iniciada', 'html' => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>', 'timer' => 5000]);
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.closure.work.history', [
            'states'        => $this->historyQuery()->paginate(self::PER_PAGE),
            'hasCriteria'   => $this->hasCriteria(),
            'criteriaChips' => $this->criteriaChips(),
        ]);
    }

    public const HISTORY_STATUSES = [
        ClosureOrderWorkflowService::STATUS_FINISHED,
        ClosureOrderWorkflowService::STATUS_WAITING,
        ClosureOrderWorkflowService::STATUS_CLOSED,
        ClosureOrderWorkflowService::STATUS_CANCELLED,
    ];

    private function historyQuery(): Builder
    {
        $this->syncWorkFilters();

        $user = auth()->user();

        $query = $this->baseListingQuery()
            ->where('closure_order_states.assigned_user_id', $user->id);

        $this->applyRubricFilter($query);

        $statuses = array_values(array_intersect($this->filterValues('status'), self::HISTORY_STATUSES));

        $query->whereIn('closure_order_states.operational_status', $statuses ?: self::HISTORY_STATUSES);

        return $query->orderByDesc('closure_order_states.entered_current_status_at');
    }
}
