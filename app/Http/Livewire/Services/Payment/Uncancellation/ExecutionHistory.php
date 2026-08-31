<?php

namespace App\Http\Livewire\Services\Payment\Uncancellation;

use App\Enum\CancellationRequestStatus;
use App\Models\UncancellationRequest;
use Livewire\Component;
use Livewire\WithPagination;

class ExecutionHistory extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $requests = UncancellationRequest::query()
            ->with(['Note', 'Orders', 'Requester', 'Closer'])
            ->whereIn('status', [
                CancellationRequestStatus::DONE->value,
                CancellationRequestStatus::REJECTED->value,
                CancellationRequestStatus::ABORTED->value,
            ])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('closed_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('closed_at', '<=', $this->dateTo))
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->whereHas('Note', fn ($note) => $note->where('note', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('Orders', fn ($order) => $order->where('ordem', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderByDesc('closed_at')
            ->paginate(20);

        return view('livewire.services.payment.uncancellation.execution-history', [
            'requests' => $requests,
        ]);
    }
}
