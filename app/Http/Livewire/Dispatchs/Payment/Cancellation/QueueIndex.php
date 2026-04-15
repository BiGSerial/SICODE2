<?php

namespace App\Http\Livewire\Dispatchs\Payment\Cancellation;

use App\Jobs\Dispatchs\ExportCancellationQueueJob;
use App\Models\CancellationCategory;
use App\Models\CancellationRequest;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class QueueIndex extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public ?string $status = null;
    public ?int $categoryId = null;
    public string $noteSearch = '';
    public string $orderSearch = '';
    public string $requesterSearch = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if (in_array($field, ['status', 'categoryId', 'noteSearch', 'orderSearch', 'requesterSearch', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function claim(int $requestId, CancellationRequestService $service): void
    {
        $request = CancellationRequest::findOrFail($requestId);
        $this->authorize('claim', $request);

        try {
            $service->claimRequest($request, Auth::user());
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação assumida.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function exportToExcel(): void
    {
        $this->authorize('viewQueue', CancellationRequest::class);

        ExportCancellationQueueJob::dispatch($this->exportPayload(), (string) Auth::id());

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Exportação iniciada. Você será notificado quando concluir.',
        ]);
    }

    private function exportPayload(): array
    {
        return [
            'service_uuid' => $this->service,
            'status' => $this->status,
            'categoryId' => $this->categoryId,
            'noteSearch' => $this->noteSearch,
            'orderSearch' => $this->orderSearch,
            'requesterSearch' => $this->requesterSearch,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ];
    }

    public function render()
    {
        $this->authorize('viewQueue', CancellationRequest::class);

        $requests = CancellationRequest::query()
            ->with(['Note', 'Category', 'Requester', 'Assignee', 'Orders'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->noteSearch, function ($q) {
                $q->whereHas('Note', fn ($note) => $note->where('note', 'like', '%' . $this->noteSearch . '%'));
            })
            ->when($this->orderSearch, function ($q) {
                $q->whereHas('Orders', fn ($order) => $order->where('ordem', 'like', '%' . $this->orderSearch . '%'));
            })
            ->when($this->requesterSearch, function ($q) {
                $q->whereHas('Requester', fn ($u) => $u->where('name', 'like', '%' . $this->requesterSearch . '%'));
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at')
            ->paginate(20);

        $categories = CancellationCategory::orderBy('display_order')->orderBy('name')->get();

        return view('livewire.dispatchs.payment.cancellation.queue-index', [
            'requests' => $requests,
            'categories' => $categories,
        ]);
    }
}
