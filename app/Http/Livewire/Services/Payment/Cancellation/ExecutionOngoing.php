<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Enum\CancellationRequestStatus;
use App\Jobs\Services\ExportCancellationExecutionOrdersJob;
use App\Models\CancellationRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExecutionOngoing extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public string $service;
    public string $multiSearch = '';
    public array $selected = [];
    public bool $selectAll = false;

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if ($field === 'multiSearch') {
            $this->resetPage();
        }
    }

    public function setSelectAll(array $ids): void
    {
        if ($this->selectAll) {
            $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        } else {
            $this->selected = array_values(array_diff($this->selected, $ids));
        }
    }

    public function goBulkReview()
    {
        if (count($this->selected) < 2) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Selecione ao menos 2 solicitações.']);
            return;
        }

        return redirect()->route('services.cancellations.ongoing.bulk', [
            'service' => $this->service,
            'ids' => implode(',', $this->selected),
        ]);
    }

    public function exportUserList(): void
    {
        $ids = $this->lists->pluck('id')->all();
        if (empty($ids)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhum registro para exportar.']);
            return;
        }

        ExportCancellationExecutionOrdersJob::dispatch([
            'ids' => $ids,
            'user_id' => (string) Auth::id(),
        ]);

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
            ->where('assigned_to', Auth::id())
            ->whereIn('status', [CancellationRequestStatus::ASSIGNED->value, CancellationRequestStatus::PAUSED->value])
            ->when(count($multi), function ($q) use ($multi) {
                $q->where(function ($sub) use ($multi) {
                    $sub->whereHas('Note', fn ($note) => $note->whereIn('note', $multi))
                        ->orWhereHas('Orders', fn ($order) => $order->whereIn('ordem', $multi));
                });
            })
            ->orderByDesc('assigned_at');
    }

    public function render()
    {
        $lists = $this->lists->paginate(20);

        return view('livewire.services.payment.cancellation.execution-ongoing', [
            'requests' => $lists,
        ]);
    }
}
