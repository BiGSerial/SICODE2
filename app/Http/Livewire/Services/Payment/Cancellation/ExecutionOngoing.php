<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Enum\CancellationRequestScope;
use App\Enum\CancellationRequestStatus;
use App\Jobs\Services\ExportCancellationExecutionOrdersJob;
use App\Models\CancellationRequest;
use App\Models\User;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class ExecutionOngoing extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = [
        'confirm_bulk_done_action' => 'confirmBulkDoneAction',
        'confirm_bulk_engineer_action' => 'confirmBulkEngineerAction',
        'confirm_bulk_reopen_action' => 'confirmBulkReopenAction',
    ];

    public string $service;
    public string $multiSearch = '';
    public string $scopeFilter = 'ALL';
    public array $selected = [];
    public bool $selectAll = false;
    public string $bulkActionType = 'DONE';
    public string $bulkComment = '';
    public ?string $bulkEngineerId = null;
    public string $bulkReopenComment = '';

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updating($field): void
    {
        if ($field === 'multiSearch') {
            $this->resetPage();
        }

        if ($field === 'scopeFilter') {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
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

    public function openBulkCloseModal(): void
    {
        if (empty($this->selected)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Selecione ao menos uma solicitação.']);
            return;
        }

        $this->resetErrorBag();
        $this->bulkActionType = 'DONE';
        $this->bulkComment = '';
        $this->bulkEngineerId = null;
        $this->dispatchBrowserEvent('bulk-close-modal-show');
    }

    public function openBulkReopenModal(): void
    {
        if (empty($this->selected)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Selecione ao menos uma solicitação.']);
            return;
        }

        $this->resetErrorBag();
        $this->bulkReopenComment = '';
        $this->dispatchBrowserEvent('bulk-reopen-modal-show');
    }

    public function runBulkCloseAction(): void
    {
        $requests = $this->selectedRequests()->get();
        if ($requests->isEmpty()) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhuma solicitação válida selecionada.']);
            return;
        }

        $scopeError = $this->validateBulkScope($requests);
        if ($scopeError !== null) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => $scopeError]);
            return;
        }

        if (!trim($this->bulkComment)) {
            $this->addError('bulkComment', 'Texto obrigatório.');
            return;
        }

        if ($this->bulkActionType === 'ENGINEER_APPROVAL' && !$this->bulkEngineerId) {
            $this->addError('bulkEngineerId', 'Selecione um engenheiro.');
            return;
        }

        if ($this->bulkActionType === 'ENGINEER_APPROVAL') {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Confirmar envio em lote',
                'msg' => 'Deseja enviar as solicitações selecionadas para aprovação do engenheiro?',
                'icon' => 'warning',
                'btnOktxt' => 'Sim, enviar',
                'btnCanceltxt' => 'Não, revisar',
                'action' => 'confirm_bulk_engineer_action',
                'cancel_titulo' => 'Cancelado',
                'cancel_msg' => 'Nenhuma solicitação foi alterada.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar encerramento em lote',
            'msg' => 'Deseja finalizar em massa as solicitações selecionadas?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, finalizar',
            'btnCanceltxt' => 'Não, revisar',
            'action' => 'confirm_bulk_done_action',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'Nenhuma solicitação foi alterada.',
        ]);
    }

    public function runBulkReopenAction(): void
    {
        $requests = $this->selectedRequests()->get();
        if ($requests->isEmpty()) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhuma solicitação válida selecionada.']);
            return;
        }

        if (!trim($this->bulkReopenComment)) {
            $this->addError('bulkReopenComment', 'Texto obrigatório.');
            return;
        }

        $hasNotPaused = $requests->contains(function (CancellationRequest $request) {
            return $request->status !== CancellationRequestStatus::PAUSED;
        });

        if ($hasNotPaused) {
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'warning',
                'title' => 'A abertura em massa é permitida somente para solicitações pausadas.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar abertura em lote',
            'msg' => 'Deseja reabrir em massa as solicitações selecionadas?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, reabrir',
            'btnCanceltxt' => 'Não, revisar',
            'action' => 'confirm_bulk_reopen_action',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'Nenhuma solicitação foi alterada.',
        ]);
    }

    public function confirmBulkDoneAction(CancellationRequestService $service): void
    {
        $requests = $this->selectedRequests()->get();
        $scopeError = $this->validateBulkScope($requests);
        if ($scopeError !== null) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => $scopeError]);
            return;
        }

        $baseComment = trim($this->bulkComment);
        if ($baseComment === '') {
            $this->addError('bulkComment', 'Texto obrigatório.');
            return;
        }

        $batchCount = $requests->count();
        $finalComment = $baseComment . PHP_EOL . PHP_EOL . "** Finalizado em Lote {$batchCount} obras **";

        $processed = 0;
        $errors = 0;
        foreach ($requests as $request) {
            try {
                $service->addComment($request, Auth::user(), $finalComment);
                $service->finalizeDone($request, Auth::user());
                $processed++;
            } catch (RuntimeException $e) {
                $errors++;
            }
        }

        $this->afterBulkAction($processed, $errors);
        $this->dispatchBrowserEvent('bulk-close-modal-hide');
    }

    public function confirmBulkEngineerAction(CancellationRequestService $service): void
    {
        $requests = $this->selectedRequests()->get();
        $scopeError = $this->validateBulkScope($requests);
        if ($scopeError !== null) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => $scopeError]);
            return;
        }

        $reason = trim($this->bulkComment);
        if ($reason === '') {
            $this->addError('bulkComment', 'Texto obrigatório.');
            return;
        }

        if (!$this->bulkEngineerId) {
            $this->addError('bulkEngineerId', 'Selecione um engenheiro.');
            return;
        }

        $engineer = User::query()->where('engineer', true)->find($this->bulkEngineerId);
        if (!$engineer) {
            $this->addError('bulkEngineerId', 'Engenheiro inválido.');
            return;
        }

        $processed = 0;
        $errors = 0;
        foreach ($requests as $request) {
            try {
                $service->requestEngineerApproval($request, Auth::user(), $engineer, $reason);
                $processed++;
            } catch (RuntimeException $e) {
                $errors++;
            }
        }

        $this->afterBulkAction($processed, $errors);
        $this->dispatchBrowserEvent('bulk-close-modal-hide');
    }

    public function confirmBulkReopenAction(CancellationRequestService $service): void
    {
        $requests = $this->selectedRequests()->get();
        $reason = trim($this->bulkReopenComment);
        if ($reason === '') {
            $this->addError('bulkReopenComment', 'Texto obrigatório.');
            return;
        }

        if ($requests->contains(fn (CancellationRequest $request) => $request->status !== CancellationRequestStatus::PAUSED)) {
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'warning',
                'title' => 'A abertura em massa é permitida somente para solicitações pausadas.',
            ]);
            return;
        }

        $processed = 0;
        $errors = 0;
        foreach ($requests as $request) {
            try {
                $service->addComment($request, Auth::user(), $reason);
                $service->reopenRequest($request, Auth::user(), $reason);
                $processed++;
            } catch (RuntimeException $e) {
                $errors++;
            }
        }

        $this->afterBulkAction($processed, $errors);
        $this->dispatchBrowserEvent('bulk-reopen-modal-hide');
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

    private function selectedRequests()
    {
        return CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category'])
            ->whereIn('id', $this->selected)
            ->where('assigned_to', Auth::id())
            ->whereIn('status', [CancellationRequestStatus::ASSIGNED->value, CancellationRequestStatus::PAUSED->value]);
    }

    private function validateBulkScope($requests): ?string
    {
        $scopes = $requests
            ->map(fn (CancellationRequest $request) => $request->scope?->value ?? $request->scope)
            ->unique()
            ->values();

        if ($scopes->count() !== 1) {
            return 'O encerramento em lote só pode ser feito com solicitações do mesmo tipo (Nota inteira ou WorkForm).';
        }

        $scope = (string) $scopes->first();
        if (!in_array($scope, [CancellationRequestScope::NOTE_FULL->value, CancellationRequestScope::WORK_FORM_ONLY->value], true)) {
            return 'Ordens individuais devem ser analisadas individualmente.';
        }

        return null;
    }

    private function afterBulkAction(int $processed, int $errors): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkComment = '';
        $this->bulkReopenComment = '';
        $this->bulkEngineerId = null;
        $this->bulkActionType = 'DONE';

        $this->dispatchBrowserEvent('swal', [
            'icon' => $errors ? 'warning' : 'success',
            'title' => "Processadas: {$processed}. Erros: {$errors}.",
        ]);
    }

    public function getListsProperty()
    {
        $multi = $this->parseMultiSearch();

        return CancellationRequest::query()
            ->with(['Note', 'Orders', 'Category', 'Requester'])
            ->where('assigned_to', Auth::id())
            ->whereIn('status', [CancellationRequestStatus::ASSIGNED->value, CancellationRequestStatus::PAUSED->value])
            ->when($this->scopeFilter !== 'ALL', fn ($q) => $q->where('scope', $this->scopeFilter))
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
        $engineers = User::query()
            ->where('engineer', true)
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name']);

        return view('livewire.services.payment.cancellation.execution-ongoing', [
            'requests' => $lists,
            'engineers' => $engineers,
            'scopes' => CancellationRequestScope::cases(),
        ]);
    }
}
