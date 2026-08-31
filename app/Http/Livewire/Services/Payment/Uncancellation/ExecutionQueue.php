<?php

namespace App\Http\Livewire\Services\Payment\Uncancellation;

use App\Enum\CancellationRequestStatus;
use App\Models\UncancellationRequest;
use App\Services\Payment\UncancellationRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class ExecutionQueue extends Component
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

    public function claim(int $requestId, UncancellationRequestService $service): void
    {
        $request = UncancellationRequest::findOrFail($requestId);

        try {
            $service->claimRequest($request, Auth::user());
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação assumida.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $requests = UncancellationRequest::query()
            ->with(['Note', 'Orders', 'Requester'])
            ->where('status', CancellationRequestStatus::SUBMITTED->value)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->whereHas('Note', fn ($note) => $note->where('note', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('Orders', fn ($order) => $order->where('ordem', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('submitted_at')
            ->paginate(20);

        return view('livewire.services.payment.uncancellation.execution-queue', [
            'requests' => $requests,
        ]);
    }
}
