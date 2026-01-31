<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyRequestsIndex extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public ?string $status = null;
    public string $search = '';

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('create', CancellationRequest::class);

        $requests = CancellationRequest::query()
            ->with(['Note', 'Category', 'Assignee'])
            ->where('requested_by', Auth::id())
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->whereHas('Note', function ($note) {
                    $note->where('note', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.services.payment.cancellation.my-requests-index', [
            'requests' => $requests,
        ]);
    }
}
