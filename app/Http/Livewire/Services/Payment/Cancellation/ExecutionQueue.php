<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationRequest;
use App\Enum\CancellationRequestStatus;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class ExecutionQueue extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $multiSearch = '';
    public bool $selectAll = false;
    public array $selected = [];

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if ($field === 'multiSearch') {
            $this->resetPage();
            $this->selectAll = false;
            $this->selected = [];
        }
    }

    public function setSelectAll(): void
    {
        if (!$this->lists) {
            return;
        }

        $visibleItems = $this->lists->items();
        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);

        if ($this->selectAll) {
            foreach ($visibleItems as $row) {
                $id = (int) $row->id;
                $selectedSet[$id] = true;
            }
        } else {
            foreach ($visibleItems as $row) {
                unset($selectedSet[(int) $row->id]);
            }
        }

        $this->selected = array_map('intval', array_keys($selectedSet));
    }

    public function claim(int $requestId, CancellationRequestService $service): void
    {
        $request = CancellationRequest::findOrFail($requestId);

        try {
            $service->claimRequest($request, Auth::user());
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação assumida.']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function claimSelected(CancellationRequestService $service): void
    {
        if (empty($this->selected)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Selecione ao menos uma solicitação.']);
            return;
        }

        $success = 0;
        $failed = 0;

        foreach ($this->selected as $id) {
            $request = CancellationRequest::find($id);
            if (!$request) {
                $failed++;
                continue;
            }

            try {
                $service->claimRequest($request, Auth::user());
                $success++;
            } catch (RuntimeException $e) {
                $failed++;
            }
        }

        $this->selected = [];
        $this->selectAll = false;

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => "Assumidas: {$success}. Falhas: {$failed}.",
        ]);
    }

    private function parseMultiSearch(): array
    {
        return collect(preg_split('/[\s,;\n\r]+/', $this->multiSearch))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function getListsProperty()
    {
        $multi = $this->parseMultiSearch();

        return CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category', 'Requester'])
            ->where('status', CancellationRequestStatus::SUBMITTED->value)
            ->whereNull('assigned_to')
            ->when(count($multi), function ($q) use ($multi) {
                $q->where(function ($sub) use ($multi) {
                    $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                        ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                });
            })
            ->orderBy('created_at');
    }

    public function render()
    {
        $lists = $this->lists->paginate(20);

        return view('livewire.services.payment.cancellation.execution-queue', [
            'requests' => $lists,
        ]);
    }
}
