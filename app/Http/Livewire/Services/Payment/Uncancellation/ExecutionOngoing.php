<?php

namespace App\Http\Livewire\Services\Payment\Uncancellation;

use App\Enum\CancellationRequestStatus;
use App\Models\UncancellationRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExecutionOngoing extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $search = '';

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $requests = UncancellationRequest::query()
            ->with(['Note', 'Orders', 'Requester', 'Assignee'])
            ->where('status', CancellationRequestStatus::ASSIGNED->value)
            ->where('assigned_to', Auth::id())
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->whereHas('Note', fn ($note) => $note->where('note', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('Orders', fn ($order) => $order->where('ordem', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('assigned_at')
            ->paginate(20);

        return view('livewire.services.payment.uncancellation.execution-ongoing', [
            'requests' => $requests,
        ]);
    }
}
