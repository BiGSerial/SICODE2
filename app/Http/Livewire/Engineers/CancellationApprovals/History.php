<?php

namespace App\Http\Livewire\Engineers\CancellationApprovals;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Models\CancellationRequest;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
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
            ->with(['Note', 'Requester', 'Assignee', 'EngineerApprovalRequester', 'EngineerApprover', 'EngineerApprovalDecider'])
            ->whereIn('engineer_approver_id', auth()->user()->visibleUserIdsForWork())
            ->whereIn('engineer_approval_status', [
                CancellationEngineerApprovalStatus::APPROVED->value,
                CancellationEngineerApprovalStatus::REJECTED->value,
                CancellationEngineerApprovalStatus::CANCELED->value,
            ])
            ->when($this->search, function ($q) {
                $q->whereHas('Note', function ($note) {
                    $note->where('note', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('engineer_approval_decided_at')
            ->paginate(15);

        return view('livewire.engineers.cancellation-approvals.history', [
            'items' => $items,
        ]);
    }
}
