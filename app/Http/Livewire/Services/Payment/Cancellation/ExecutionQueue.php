<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Jobs\Dispatchs\ExportCancellationQueueJob;
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
    protected $listeners = [
        'confirm_cancellation_queue_claim_selected' => 'confirmClaimSelected',
    ];

    public string $service;
    public string $multiSearch = '';
    public bool $selectAll = false;
    public array $selected = [];
    public int $perPage = 20;

    protected $queryString = [
        'perPage' => ['except' => 20, 'as' => 'pp'],
    ];

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

    public function updatedPerPage($value): void
    {
        $value = (int) $value;
        if ($value <= 0) {
            $value = 20;
        }

        $this->perPage = min($value, 250);
        $this->resetPage();
        $this->selectAll = false;
        $this->selected = [];
    }

    public function setSelectAll(array $ids): void
    {
        if ($this->selectAll) {
            $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        } else {
            $this->selected = array_values(array_diff($this->selected, $ids));
        }
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

    public function claimSelected(): void
    {
        if (empty($this->selected)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Selecione ao menos uma solicitação.']);
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar ação',
            'msg' => 'Assumir todas as solicitações selecionadas?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, assumir',
            'btnCanceltxt' => 'Não, revisar',
            'action' => 'confirm_cancellation_queue_claim_selected',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'Nenhuma solicitação foi alterada.',
        ]);
    }

    public function confirmClaimSelected(CancellationRequestService $service): void
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

    public function exportToExcel(): void
    {
        ExportCancellationQueueJob::dispatch([
            'service_uuid' => $this->service,
            'multiSearch' => $this->parseMultiSearch(),
            'status' => CancellationRequestStatus::SUBMITTED->value,
            'onlyUnassigned' => true,
        ], (string) Auth::id());

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Exportação iniciada. Você será notificado quando concluir.',
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
        $lists = $this->lists->paginate($this->perPage);

        return view('livewire.services.payment.cancellation.execution-queue', [
            'requests' => $lists,
        ]);
    }
}
