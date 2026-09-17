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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $regionals = auth()->user()->regionNames()
            ->map(fn ($regional) => trim((string) $regional))
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
            ->when($regionals->isNotEmpty(), function ($query) use ($regionals) {
                $query->whereHas('Note', function ($noteQuery) use ($regionals) {
                    $noteQuery->whereIn(
                        'nexp',
                        \App\Models\City::query()
                            ->whereIn('regional', $regionals->all())
                            ->whereNotNull('rdMunicipio')
                            ->pluck('rdMunicipio')
                    );
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when(trim($this->search) !== '', function ($query) {
                $term = trim($this->search);
                $query->whereHas('Note', fn ($noteQuery) => $noteQuery->where('note', 'like', "%{$term}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.engineers.cancellation-approvals.regional', [
            'items' => $items,
            'regions' => $regionals,
        ]);
    }
}
