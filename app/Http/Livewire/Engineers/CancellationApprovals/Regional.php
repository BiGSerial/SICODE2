<?php

namespace App\Http\Livewire\Engineers\CancellationApprovals;

use App\Enum\{CancellationRequestScope, CancellationRequestStatus};
use App\Models\CancellationRequest;
use Livewire\Component;
use Livewire\WithPagination;

class Regional extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $baseConstructionFilter = '';

    public string $scopeFilter = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBaseConstructionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'baseConstructionFilter', 'scopeFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $baseConstructions = auth()->user()->regionNames()
            ->map(fn ($base) => trim((string) $base))
            ->filter()
            ->unique()
            ->values();

        $items = CancellationRequest::query()
            ->with(['Note.City', 'Requester', 'Assignee', 'Category'])
            ->whereIn('status', [
                CancellationRequestStatus::DRAFT->value,
                CancellationRequestStatus::SUBMITTED->value,
                CancellationRequestStatus::ASSIGNED->value,
                CancellationRequestStatus::PAUSED->value,
            ])
            ->when($baseConstructions->isNotEmpty(), function ($query) use ($baseConstructions) {
                $query->whereHas('Note', function ($noteQuery) use ($baseConstructions) {
                    $noteQuery->whereIn(
                        'nexp',
                        \App\Models\City::query()
                            ->whereIn('baseConstrucao', $baseConstructions->all())
                            ->whereNotNull('rdMunicipio')
                            ->pluck('rdMunicipio')
                    );
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($this->baseConstructionFilter !== '' && $baseConstructions->contains($this->baseConstructionFilter), function ($query) {
                $query->whereHas('Note', function ($noteQuery) {
                    $noteQuery->whereIn(
                        'nexp',
                        \App\Models\City::query()
                            ->where('baseConstrucao', $this->baseConstructionFilter)
                            ->whereNotNull('rdMunicipio')
                            ->pluck('rdMunicipio')
                    );
                });
            })
            ->when($this->scopeFilter !== '', fn ($query) => $query->where('scope', $this->scopeFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(trim($this->search) !== '', function ($query) {
                $term = trim($this->search);
                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery
                        ->whereHas('Note', fn ($noteQuery) => $noteQuery->where('note', 'like', "%{$term}%"))
                        ->orWhereHas('Requester', fn ($userQuery) => $userQuery->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('Assignee', fn ($userQuery) => $userQuery->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.engineers.cancellation-approvals.regional', [
            'items' => $items,
            'baseConstructions' => $baseConstructions,
            'scopes' => CancellationRequestScope::cases(),
            'statuses' => [
                CancellationRequestStatus::DRAFT,
                CancellationRequestStatus::SUBMITTED,
                CancellationRequestStatus::ASSIGNED,
                CancellationRequestStatus::PAUSED,
            ],
        ]);
    }
}
