<?php

namespace App\Http\Livewire\Engineers\CancellationApprovals;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Models\CancellationRequest;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
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
        $items = CancellationRequest::query()
            ->with(['Note', 'Requester', 'Assignee', 'EngineerApprovalRequester'])
            ->whereIn('engineer_approver_id', auth()->user()->visibleUserIdsForWork())
            ->where('engineer_approval_status', CancellationEngineerApprovalStatus::PENDING->value)
            ->when($this->search, function ($q) {
                $q->whereHas('Note', function ($note) {
                    $note->where('note', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('engineer_approval_requested_at')
            ->paginate(15);

        return view('livewire.engineers.cancellation-approvals.index', [
            'items' => $items,
        ]);
    }
}
