<?php

namespace App\Http\Livewire\Closure\Works;

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureOrderState, ClosurePriorityRequest, ClosureWorkMessage};
use App\Models\{Company, Note, Operation, Order};
use App\Services\Closure\{
    ClosureIssueWorkflowException,
    ClosureIssueWorkflowService,
    ClosureMetaClassifier,
    ClosureOrderWorkflowException,
    ClosureOrderWorkflowService,
    ClosurePriorityException,
    ClosurePriorityService,
    ClosureWorkException,
    ClosureWorkService
};
use App\Services\PartnerAccess\PartnerAccessGate;
use App\Support\EvidenceFileUploader;
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithFileUploads};

/**
 * Gestão da Obra (por Nota): Ordens, chat, pendências com a parceira, prioridade e status.
 * Serve ao usuário interno/despachante e, em versão restrita, à parceira (nível PARTNER).
 */
class Show extends Component
{
    use WithFileUploads;

    public int $noteId;

    public string $level = '';

    // Chat
    public string $chatBody = '';

    public string $chatVisibility = ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY;

    public $chatAttachments = [];

    // Enviar à parceira
    public string $issueCategoryId = '';

    public string $issueSubcategoryId = '';

    public string $issueDescription = '';

    public array $issueOrderIds = [];

    public string $issueCompanyId = '';

    public bool $issueBlocking = true;

    // Ação sobre pendência (validar / devolver / resolver / responder)
    public ?int $actingIssueId = null;

    public string $actingMode = '';

    public string $actingMessage = '';

    public $actingAttachments = [];

    // Alterar status
    public ?int $statusStateId = null;

    public string $statusTarget = '';

    public string $statusReason = '';

    public bool $statusConfirming = false;

    // Finalizar (null | 'all' | id do estado)
    public ?string $pendingFinish = null;

    public array $finishReceipt = [];

    // Prioridade
    public string $priorityReason = '';

    public string $priorityDecisionNote = '';

    public ?string $feedback = null;

    public string $feedbackType = 'info';

    public function mount(int $noteId): void
    {
        $this->noteId = $noteId;
        $note         = Note::query()->findOrFail($noteId);
        $level        = auth()->user() ? app(ClosureWorkService::class)->accessLevel($note, auth()->user()) : null;

        abort_if($level === null, 403);

        $this->level = $level;

        if ($level === ClosureWorkService::LEVEL_PARTNER) {
            $this->chatVisibility = ClosureWorkMessage::VISIBILITY_WITH_PARTNER;
        }
    }

    public function updatedIssueCategoryId(): void
    {
        $this->issueSubcategoryId = '';
    }

    public function updatedStatusStateId(): void
    {
        $this->statusTarget     = '';
        $this->statusReason     = '';
        $this->statusConfirming = false;
    }

    public function isPartner(): bool
    {
        return $this->level === ClosureWorkService::LEVEL_PARTNER;
    }

    public function isDispatcher(): bool
    {
        return $this->level === ClosureWorkService::LEVEL_DISPATCHER;
    }

    // ---------------------------------------------------------------- Chat

    public function postMessage(): void
    {
        $this->guardLevel();
        $this->validate([
            'chatBody'          => 'required|string|max:' . ClosureWorkService::MAX_BODY,
            'chatAttachments.*' => 'mimes:pdf,jpg,jpeg,png,gif,webp,xlsx,xls,csv,doc,docx,txt,zip|max:10240',
        ], [
            'chatBody.required' => 'Escreva a mensagem.',
        ]);

        $visibility = $this->isPartner() ? ClosureWorkMessage::VISIBILITY_WITH_PARTNER : $this->chatVisibility;

        try {
            app(ClosureWorkService::class)->postMessage($this->note(), auth()->user(), $this->chatBody, $visibility, null, null, $this->chatAttachments);
        } catch (ClosureWorkException $e) {
            $this->notify($e->getMessage(), 'warning');

            return;
        }

        $this->reset('chatBody', 'chatAttachments');
        $this->notify('Mensagem enviada.', 'success');
    }

    // ----------------------------------------------- Enviar à parceira

    public function sendToPartner(): void
    {
        $this->guardInternal();

        $this->validate([
            'issueCategoryId'    => 'required',
            'issueSubcategoryId' => 'required',
            'issueOrderIds'      => 'required|array|min:1',
            'issueCompanyId'     => 'required',
        ], [
            'issueCategoryId.required'    => 'Escolha a categoria.',
            'issueSubcategoryId.required' => 'Escolha a subcategoria.',
            'issueOrderIds.required'      => 'Selecione ao menos uma Ordem.',
            'issueOrderIds.min'           => 'Selecione ao menos uma Ordem.',
            'issueCompanyId.required'     => 'Escolha a empresa destino.',
        ]);

        $category    = ClosureIssueCategory::query()->find($this->issueCategoryId);
        $subcategory = $category?->Subcategories()->whereKey($this->issueSubcategoryId)->first();
        $company     = Company::query()->find($this->issueCompanyId);

        if (!$category || !$subcategory || !$company) {
            $this->notify('Categoria, subcategoria ou empresa inválida.', 'warning');

            return;
        }

        if ($subcategory->requires_description && trim($this->issueDescription) === '') {
            $this->addError('issueDescription', 'Esta subcategoria exige uma descrição.');

            return;
        }

        $states = $this->noteStates()->whereIn('id', array_map('intval', $this->issueOrderIds))->get();
        $ok     = 0;
        $errors = [];

        foreach ($states as $state) {
            try {
                app(ClosureIssueWorkflowService::class)->requestToPartner(
                    $state,
                    $category,
                    $subcategory,
                    $company,
                    auth()->user(),
                    $this->issueDescription,
                    null,
                    $this->issueBlocking,
                );
                $ok++;
            } catch (ClosureIssueWorkflowException|ClosureOrderWorkflowException $e) {
                $errors[] = 'Ordem ' . ($state->Order?->ordem ?? $state->id) . ': ' . $e->getMessage();
            }
        }

        if ($ok > 0) {
            $this->reset('issueCategoryId', 'issueSubcategoryId', 'issueDescription', 'issueOrderIds', 'issueCompanyId');
            $this->issueBlocking = true;
        }

        $this->notify(
            ($ok > 0 ? "{$ok} pendência(s) enviada(s) à parceira." : 'Nenhuma pendência enviada.') . ($errors ? ' ' . implode(' ', $errors) : ''),
            $errors ? 'warning' : 'success'
        );
    }

    public function startIssueAction(int $issueId, string $mode): void
    {
        $issue = $this->visibleIssue($issueId);

        if ($this->isPartner()) {
            abort_unless($mode === 'respond' && PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'), 403);
        } else {
            abort_unless(in_array($mode, ['validate', 'return', 'resolve'], true), 403);
        }

        $this->actingIssueId     = $issue->id;
        $this->actingMode        = $mode;
        $this->actingMessage     = '';
        $this->actingAttachments = [];
        $this->resetValidation();
    }

    public function cancelIssueAction(): void
    {
        $this->reset('actingIssueId', 'actingMode', 'actingMessage', 'actingAttachments');
        $this->resetValidation();
    }

    public function confirmIssueAction(): void
    {
        abort_if($this->actingIssueId === null, 422);

        $issue   = $this->visibleIssue($this->actingIssueId);
        $mode    = $this->actingMode;
        $user    = auth()->user();
        $service = app(ClosureIssueWorkflowService::class);

        if (in_array($mode, ['return', 'respond'], true) && trim($this->actingMessage) === '') {
            $this->addError('actingMessage', $mode === 'return' ? 'Informe o motivo da devolução.' : 'Informe o retorno da pendência.');

            return;
        }

        $this->validate(['actingAttachments.*' => 'mimes:pdf,jpg,jpeg,png,gif,webp,xlsx,xls,csv,doc,docx,txt,zip|max:10240']);

        try {
            match (true) {
                $mode === 'respond' && $this->isPartner()   => $this->respondAs($issue, $service),
                $mode === 'validate' && !$this->isPartner() => $service->validate($issue, $user, $this->actingMessage ?: null),
                $mode === 'return' && !$this->isPartner()   => $service->returnForCorrection($issue, $user, $this->actingMessage),
                $mode === 'resolve' && !$this->isPartner()  => $service->resolve($issue, $user, $this->actingMessage ?: null),
                default                                     => abort(403),
            };
        } catch (ClosureIssueWorkflowException $e) {
            $this->notify($e->getMessage(), 'warning');

            return;
        }

        $this->cancelIssueAction();
        $this->notify('Pendência atualizada.', 'success');
    }

    private function respondAs(ClosureIssue $issue, ClosureIssueWorkflowService $service): void
    {
        $user    = auth()->user();
        $visible = $user->superadm ? null : PartnerAccessGate::visibleCompanyIdsFor($user);
        $company = Company::query()
            ->when($visible !== null, fn ($q) => $q->whereIn('id', $visible))
            ->whereKey($issue->destination_company_id)
            ->firstOrFail();

        $service->respond($issue, $company, $user, $this->actingMessage);

        if ($this->actingAttachments !== []) {
            $message = ClosureWorkMessage::query()
                ->where('closure_issue_id', $issue->id)->where('author_id', $user->id)
                ->orderByDesc('id')->first();

            if ($message) {
                app(EvidenceFileUploader::class)->storeClosureWorkMessageEvidence($message, $this->actingAttachments, $user, (string) $this->note()->note);
            }
        }
    }

    // ------------------------------------------------------ Prioridade

    public function requestPriority(): void
    {
        $this->guardInternal();

        try {
            app(ClosurePriorityService::class)->request($this->note(), $this->priorityReason, auth()->user());
        } catch (ClosurePriorityException $e) {
            $this->notify($e->getMessage(), 'warning');

            return;
        }

        $this->reset('priorityReason');
        $this->notify('Pedido de prioridade enviado ao despachante.', 'success');
    }

    public function decidePriority(int $requestId, string $decision): void
    {
        $this->guardLevel();
        abort_unless($this->isDispatcher(), 403);

        $request = ClosurePriorityRequest::query()->where('note_id', $this->noteId)->findOrFail($requestId);
        $service = app(ClosurePriorityService::class);

        try {
            $decision === 'accept'
                ? $service->accept($request, auth()->user(), $this->priorityDecisionNote)
                : $service->decline($request, auth()->user(), $this->priorityDecisionNote);
        } catch (ClosurePriorityException $e) {
            $this->notify($e->getMessage(), 'warning');

            return;
        }

        $this->reset('priorityDecisionNote');
        $this->notify($decision === 'accept' ? 'Prioridade aceita e aplicada às Ordens abertas.' : 'Pedido de prioridade recusado.', 'success');
    }

    // ---------------------------------------------------------- Status

    public function selectStateForStatus(int $stateId): void
    {
        $this->guardInternal();
        $this->statusStateId    = $this->noteStates()->findOrFail($stateId)->id;
        $this->statusTarget     = '';
        $this->statusReason     = '';
        $this->statusConfirming = false;
    }

    public function prepareStatusChange(): void
    {
        $this->guardInternal();
        $state = $this->noteStates()->find($this->statusStateId);

        if (!$state || $this->statusTarget === '') {
            $this->notify('Escolha a Ordem e o novo status.', 'warning');

            return;
        }

        if ($this->reasonRequired($state, $this->statusTarget) && trim($this->statusReason) === '') {
            $this->addError('statusReason', 'Informe o motivo para esta mudança de status.');

            return;
        }

        $this->resetValidation();
        $this->statusConfirming = true;
    }

    public function confirmStatusChange(): void
    {
        $this->guardInternal();
        $state = $this->noteStates()->find($this->statusStateId);

        if (!$state || !$this->statusConfirming) {
            return;
        }

        try {
            app(ClosureOrderWorkflowService::class)->changeStatus($state, $this->statusTarget, auth()->user(), $this->statusReason ?: null);
        } catch (ClosureOrderWorkflowException $e) {
            $this->statusConfirming = false;
            $this->notify($e->getMessage(), 'warning');

            return;
        }

        $this->reset('statusStateId', 'statusTarget', 'statusReason', 'statusConfirming');
        $this->notify('Status da atividade alterado.', 'success');
    }

    public function cancelStatusChange(): void
    {
        $this->reset('statusStateId', 'statusTarget', 'statusReason', 'statusConfirming');
        $this->resetValidation();
    }

    // -------------------------------------------------------- Finalizar

    public function askFinish(string $target): void
    {
        $this->guardInternal();
        $this->pendingFinish = $target;
        $this->finishReceipt = [];
    }

    public function cancelFinish(): void
    {
        $this->pendingFinish = null;
    }

    public function confirmFinish(): void
    {
        $this->guardInternal();

        if ($this->pendingFinish === null) {
            return;
        }

        $query = $this->noteStates()->with('Order');

        if ($this->pendingFinish !== 'all') {
            $query->whereKey((int) $this->pendingFinish);
        } else {
            $query->whereIn('operational_status', ClosureOrderWorkflowService::FINISHABLE_STATUSES);
        }

        $workflow = app(ClosureOrderWorkflowService::class);
        $receipt  = [];

        foreach ($query->get() as $state) {
            if (!$this->canManage($state)) {
                continue;
            }

            try {
                $result    = $workflow->finish($state, auth()->user());
                $receipt[] = ['ordem' => $state->Order?->ordem, 'ok' => true, 'message' => ClosureOperationalStatus::labelFor($result->operational_status)];
            } catch (ClosureOrderWorkflowException $e) {
                $receipt[] = ['ordem' => $state->Order?->ordem, 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        $this->pendingFinish = null;
        $this->finishReceipt = $receipt;
        $done                = count(array_filter($receipt, fn ($r) => $r['ok']));
        $this->notify(
            $receipt === [] ? 'Nenhuma Ordem finalizável sob sua responsabilidade.' : "{$done} de " . count($receipt) . ' Ordem(ns) finalizada(s). Confira o recibo.',
            $receipt === [] || $done < count($receipt) ? 'warning' : 'success'
        );
    }

    // ------------------------------------------------------- Helpers UI

    public function canManage(ClosureOrderState $state): bool
    {
        $user = auth()->user();

        if ($this->isPartner() || !$user) {
            return false;
        }

        return ($state->assigned_user_id && (string) $state->assigned_user_id === (string) $user->id) || $user->can('closure.manage');
    }

    /** @return array<int, string> destinos manuais permitidos a partir do status atual */
    public function targetsFor(ClosureOrderState $state): array
    {
        return ClosureOrderWorkflowService::MANUAL_TRANSITIONS[$state->operational_status] ?? [];
    }

    public function reasonRequired(ClosureOrderState $state, string $target): bool
    {
        return in_array($target, ClosureOrderWorkflowService::REASON_REQUIRED_TARGETS, true)
            || in_array($state->operational_status, [ClosureOrderWorkflowService::STATUS_FINISHED, ClosureOrderWorkflowService::STATUS_WAITING], true);
    }

    public function render()
    {
        $this->guardLevel();

        $note     = $this->note();
        $user     = auth()->user();
        $overview = app(ClosureWorkService::class)->overview($note, $user);
        $orders   = Order::query()->whereIn('id', collect($overview['orders'])->pluck('order_id'))->get();
        $types    = app(ClosureMetaClassifier::class)->typesFor($orders);
        $ops      = Operation::query()->whereIn('order_id', $orders->pluck('id'))
            ->selectRaw('order_id, COUNT(*) as total')->groupBy('order_id')->pluck('total', 'order_id');

        $states = $this->noteStates()->get()->keyBy('id');
        $rows   = collect($overview['orders'])->map(function (array $o) use ($types, $ops, $states) {
            $state       = $states->get($o['state_id']);
            $o['type']   = $types->get($o['order_id']);
            $o['ops']    = (int) ($ops[$o['order_id']] ?? 0);
            $o['manage'] = $state && $this->canManage($state);
            $o['finish'] = $o['manage'] && in_array($o['operational_status'], ClosureOrderWorkflowService::FINISHABLE_STATUSES, true);

            return $o;
        });

        $issues = $this->issuesQuery()->with(['Order', 'Category', 'Subcategory', 'DestinationCompany', 'Events.Actor'])->orderBy('opened_at')->get();

        $internal = !$this->isPartner();

        return view('livewire.closure.works.show', [
            'overview'     => $overview,
            'rows'         => $rows,
            'typeSummary'  => $rows->pluck('type')->filter()->countBy()->all(),
            'messages'     => app(ClosureWorkService::class)->messages($note, $user),
            'issues'       => $issues,
            'internal'     => $internal,
            'categories'   => $internal ? ClosureIssueCategory::query()->where('active', true)->with(['Subcategories' => fn ($q) => $q->where('active', true)->orderBy('display_order')->orderBy('name')])->orderBy('display_order')->orderBy('name')->get() : collect(),
            'companies'    => $internal ? Company::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'statusState'  => $this->statusStateId ? $states->get($this->statusStateId) : null,
            'canFinishAny' => $rows->contains(fn ($r) => $r['finish']),
            'canRespond'   => $this->isPartner() && PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'),
            'closedIssues' => $this->issuesQuery(true)->where('status', ClosureIssue::STATUS_RESOLVED)->count(),
        ]);
    }

    private function note(): Note
    {
        return Note::query()->with('City')->findOrFail($this->noteId);
    }

    private function noteStates(): Builder
    {
        return ClosureOrderState::query()->whereHas('Order', fn ($q) => $q->where('note_id', $this->noteId));
    }

    private function issuesQuery(bool $includeResolved = false): Builder
    {
        $query = ClosureIssue::query()->whereHas('Order', fn ($q) => $q->where('note_id', $this->noteId));

        if (!$includeResolved) {
            $query->where('status', '!=', ClosureIssue::STATUS_RESOLVED);
        }

        if ($this->isPartner()) {
            $user      = auth()->user();
            $companies = $user->superadm ? [] : PartnerAccessGate::visibleCompanyIdsFor($user);
            $query->whereIn('destination_company_id', $companies);
        }

        return $query;
    }

    private function visibleIssue(int $issueId): ClosureIssue
    {
        $this->guardLevel();

        $issue = $this->issuesQuery()->find($issueId);

        abort_if($issue === null, 404);

        return $issue;
    }

    /** Reconfere o acesso a cada ação (o estado público do Livewire não é confiável). */
    private function guardLevel(): void
    {
        $level = app(ClosureWorkService::class)->accessLevel($this->note(), auth()->user());

        abort_if($level === null, 403);

        $this->level = $level;
    }

    private function guardInternal(): void
    {
        $this->guardLevel();
        abort_if($this->isPartner(), 403);
    }

    private function notify(string $message, string $type): void
    {
        $this->feedback     = $message;
        $this->feedbackType = $type;
        $this->dispatchBrowserEvent('notification:toast', [
            'icon'  => $type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'info'),
            'title' => $type === 'success' ? 'Pronto' : 'Atenção',
            'text'  => $message,
        ]);
    }
}
