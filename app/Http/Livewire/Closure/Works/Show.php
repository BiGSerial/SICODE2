<?php

namespace App\Http\Livewire\Closure\Works;

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureOrderState, ClosurePriorityRequest, ClosureWorkMessage};
use App\Models\{Company, Note};
use App\Services\Closure\{
    ClosureIssueWorkflowException,
    ClosureIssueWorkflowService,
    ClosureOrderWorkflowException,
    ClosureOrderWorkflowService,
    ClosurePriorityException,
    ClosurePriorityService,
    ClosureWorkDossier,
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

    // Enviar à parceira
    public string $issueCategoryId = '';

    public string $issueSubcategoryId = '';

    public string $issueDescription = '';

    public array $issueOrderIds = [];

    public string $issueCompanyId = '';

    public bool $issueBlocking = true;

    public bool $partnerModal = false;

    /** @var array<int, string> exigências que o operador faz à parceira (uma por linha do modal) */
    public array $issueRequirements = [''];

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

    public bool $showCanceled = false;

    public ?string $feedback = null;

    public string $feedbackType = 'info';

    /** Origem da navegação (dispatch|service): mantém menu/links coerentes. */
    public string $ctx = 'dispatch';

    /**
     * Público da rota (partner|internal; `auto` = nível real do usuário, só para uso interno/testes): cada rota mostra UMA visão,
     * qualquer que seja o usuário. Adulterar isto no cliente não eleva acesso (o nível é recalculado a cada ação) e a visão da
     * parceira exige permissão de parceira e empresa na obra.
     */
    public string $audience = ClosureWorkService::AUDIENCE_AUTO;

    /** Superadm na visão da parceira: empresa observada (uma das empresas da obra; vazio = a padrão). */
    public string $observeCompanyId = '';

    // Abas do dossiê (só a equipe interna): overview | ri | informs | files
    public string $tab = 'overview';

    public string $fileType = '';

    public string $fileSearch = '';

    /** @var array<int, string> */
    public array $selectedFiles = [];

    protected $listeners = ['closureRiChanged' => '$refresh'];

    public function mount(int $noteId, string $ctx = 'dispatch', string $audience = ClosureWorkService::AUDIENCE_AUTO): void
    {
        $this->noteId   = $noteId;
        $this->ctx      = \App\Services\Closure\ClosureNavContext::resolve($ctx);
        $this->audience = in_array($audience, [ClosureWorkService::AUDIENCE_PARTNER, ClosureWorkService::AUDIENCE_INTERNAL], true) ? $audience : ClosureWorkService::AUDIENCE_AUTO;
        $note           = Note::query()->findOrFail($noteId);
        $level          = auth()->user() ? $this->work()->accessLevel($note, auth()->user()) : null;

        abort_if($level === null, 403);

        $this->level = $level;

        if ($level === ClosureWorkService::LEVEL_PARTNER) {
            $this->chatVisibility = ClosureWorkMessage::VISIBILITY_WITH_PARTNER;
        }
    }

    public function setTab(string $tab): void
    {
        $this->guardInternal();
        $this->tab = in_array($tab, ['overview', 'ri', 'informs', 'files'], true) ? $tab : 'overview';
    }

    /** Pode enviar RI: equipe interna com permissão de trabalho/gestão e sem RI ativo na obra. */
    public function canSendRi(): bool
    {
        $user = auth()->user();

        return !$this->isPartner() && $user && ($user->can('closure.manage') || $user->can('closure.work'))
            && !app(\App\Services\Closure\ClosureInternalReturnService::class)->activeForNote($this->noteId);
    }

    public function downloadFile(int $fileId)
    {
        $this->guardInternal();
        $file = app(ClosureWorkDossier::class)->ownedFiles($this->note(), [$fileId])->first();

        abort_if($file === null, 404);

        $storage = app(\App\Services\Files\FileStorageService::class);

        if (!$storage->exists($file)) {
            $this->notify('O arquivo não foi localizado no armazenamento.', 'warning');

            return null;
        }

        // O Encerramento precisa de todos os arquivos da obra, inclusive os de ADS tácita (no gerenciador geral eles são só do superadm).
        return $storage->download($file, $file->file_name);
    }

    public function downloadZip(): void
    {
        $this->guardInternal();
        $files = app(ClosureWorkDossier::class)->ownedFiles($this->note(), $this->selectedFiles);

        if ($files->isEmpty()) {
            $this->notify('Selecione ao menos um arquivo.', 'warning');

            return;
        }

        $batch = \App\Models\FileDownloadBatch::create(['user_id' => auth()->id(), 'status' => 'queued', 'file_ids' => $files->pluck('id')->all()]);
        \App\Jobs\Files\GenerateFileDownloadBatchJob::dispatch($batch->file_ids, $batch->id);
        $this->selectedFiles = [];
        $this->notify('A compactação continua em segundo plano; você será avisado na Central de Notificações quando o link estiver pronto.', 'success');
    }

    public function selectAllFiles(): void
    {
        $this->guardInternal();
        $this->selectedFiles = app(ClosureWorkDossier::class)->files($this->note(), $this->fileType, $this->fileSearch)
            ->where('exists', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
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

    /** Service preso ao público da rota: o nível e o escopo de empresa seguem a rota, não o usuário. */
    private function work(): ClosureWorkService
    {
        return app(ClosureWorkService::class)->forAudience($this->audience)->observingAs($this->observeCompanyId);
    }

    /** Superadm (ou qualquer um sem empresa parceira própria) vendo a obra como parceira: aviso de modo observador. */
    public function isObserving(): bool
    {
        return $this->isPartner() && (bool) auth()->user()?->superadm;
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
            'chatBody' => 'required|string|max:' . ClosureWorkService::MAX_BODY,
        ], [
            'chatBody.required' => 'Escreva a mensagem.',
        ]);

        $visibility = $this->isPartner() ? ClosureWorkMessage::VISIBILITY_WITH_PARTNER : $this->chatVisibility;

        try {
            $this->work()->postMessage($this->note(), auth()->user(), $this->chatBody, $visibility, null, null);
        } catch (ClosureWorkException $e) {
            $this->notify($e->getMessage(), 'warning');

            return;
        }

        $this->reset('chatBody');
        $this->notify('Mensagem enviada.', 'success');
    }

    // ----------------------------------------------- Enviar à parceira

    /** Abre o modal operacional; com `$stateId`, a Ordem já vem marcada e a parceira sugerida é a da produção. */
    public function openPartnerModal(?int $stateId = null): void
    {
        $this->guardInternal();
        $this->resetErrorBag();
        $this->reset('issueCategoryId', 'issueSubcategoryId', 'issueDescription', 'issueOrderIds', 'issueCompanyId');
        $this->issueRequirements = [''];
        $this->issueBlocking     = true;

        if ($stateId && $this->noteStates()->whereKey($stateId)->exists()) {
            $this->issueOrderIds  = [(string) $stateId];
            $productionId         = ClosureOrderState::query()->whereKey($stateId)->value('production_id');
            $this->issueCompanyId = (string) (\App\Models\Production::query()->whereKey($productionId)->value('company_id') ?? '');
        }

        $this->partnerModal = true;
    }

    public function addRequirement(): void
    {
        if (count($this->issueRequirements) < 30) {
            $this->issueRequirements[] = '';
        }
    }

    public function removeRequirement(int $index): void
    {
        unset($this->issueRequirements[$index]);
        $this->issueRequirements = array_values($this->issueRequirements) ?: [''];
    }

    public function closePartnerModal(): void
    {
        $this->partnerModal = false;
        $this->resetErrorBag();
    }

    public function sendToPartner(): void
    {
        $this->guardInternal();

        $this->validate([
            'issueCategoryId'     => 'required',
            'issueSubcategoryId'  => 'required',
            'issueOrderIds'       => 'required|array|min:1',
            'issueCompanyId'      => 'required',
            'issueRequirements.*' => 'nullable|string|max:500',
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

        $requirements = collect($this->issueRequirements)->map(fn ($text) => trim((string) $text))->filter()->values()->all();
        $message      = $requirements === [] ? null : trim(trim($this->issueDescription) . "\n\nExigências:\n" . collect($requirements)->map(fn ($text, $i) => ($i + 1) . '. ' . $text)->implode("\n"));

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
                    $message,
                    $this->issueBlocking,
                    $requirements,
                );
                $ok++;
            } catch (ClosureIssueWorkflowException|ClosureOrderWorkflowException $e) {
                $errors[] = 'Ordem ' . ($state->Order?->ordem ?? $state->id) . ': ' . $e->getMessage();
            }
        }

        if ($ok > 0) {
            $this->reset('issueCategoryId', 'issueSubcategoryId', 'issueDescription', 'issueOrderIds', 'issueCompanyId');
            $this->issueRequirements = [''];
            $this->issueBlocking     = true;
            $this->partnerModal      = false;
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
            abort_unless($mode === 'respond' && !$this->isObserving() && PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'), 403);
        } else {
            abort_unless(in_array($mode, ['validate', 'return', 'resolve', 'release'], true), 403);
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

        if (in_array($mode, ['return', 'respond', 'release'], true) && trim($this->actingMessage) === '') {
            $this->addError('actingMessage', ['return' => 'Informe o motivo da devolução.', 'release' => 'Informe o motivo do desbloqueio.'][$mode] ?? 'Informe o retorno da pendência.');

            return;
        }

        $this->validate(['actingAttachments.*' => 'mimes:pdf,jpg,jpeg,png,gif,webp,xlsx,xls,csv,doc,docx,txt,zip|max:10240']);

        try {
            match (true) {
                $mode === 'respond' && $this->isPartner()   => $this->respondAs($issue, $service),
                $mode === 'validate' && !$this->isPartner() => $service->validate($issue, $user, $this->actingMessage ?: null),
                $mode === 'return' && !$this->isPartner()   => $service->returnForCorrection($issue, $user, $this->actingMessage),
                $mode === 'resolve' && !$this->isPartner()  => $service->resolve($issue, $user, $this->actingMessage ?: null),
                $mode === 'release' && !$this->isPartner()  => $service->release($issue, $user, $this->actingMessage),
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
        $overview = $this->work()->overview($note, $user, $this->showCanceled);
        $states   = $this->noteStates()->get()->keyBy('id');
        $rows     = collect($overview['orders'])->map(function (array $o) use ($states) {
            $state       = $o['state_id'] ? $states->get($o['state_id']) : null;
            $o['manage'] = $state && $this->canManage($state);
            $o['finish'] = $o['manage'] && in_array($o['operational_status'], ClosureOrderWorkflowService::FINISHABLE_STATUSES, true);

            return $o;
        });

        $issues = $this->issuesQuery()->with(['Order', 'Category', 'Subcategory', 'DestinationCompany', 'Events.Actor'])->orderBy('opened_at')->get();

        $internal = !$this->isPartner();
        $dossier  = app(ClosureWorkDossier::class);
        $tab      = $internal ? $this->tab : 'overview';

        return view('livewire.closure.works.show', [
            'tab'           => $tab,
            'activeRi'      => $internal ? app(\App\Services\Closure\ClosureInternalReturnService::class)->activeForNote($this->noteId) : null,
            'riStage'       => $internal ? $this->activeRiStage($dossier) : null,
            'returns'       => $internal && $tab === 'ri' ? $dossier->internalReturns($note) : collect(),
            'informs'       => $internal && $tab === 'informs' ? $dossier->informs($note) : collect(),
            'files'         => $internal && $tab === 'files' ? $dossier->files($note, $this->fileType, $this->fileSearch) : collect(),
            'fileTypes'     => $dossier::FILE_TYPES,
            'canSendRi'     => $this->canSendRi(),
            'dossierCounts' => $internal ? [
                'ri'      => \App\Models\Closure\ClosureInternalReturn::query()->where('note_id', $this->noteId)->count(),
                'informs' => \App\Models\WorkReport::query()->where('note_id', $this->noteId)->count(),
                'files'   => \App\Models\File::query()->where('note_id', $this->noteId)->count(),
            ] : [],
            'overview'        => $overview,
            'rows'            => $rows,
            'eligibleSet'     => $internal ? app(\App\Services\Closure\ClosureEligibleOrders::class)->all() : [],
            'blockingByState' => $issues->where('blocking', true)->groupBy('closure_order_state_id')->map(fn ($group) => $group->first()->id),
            'depositSignals'  => app(\App\Services\Closure\ClosurePartnerSignalService::class)->activeByState($rows->pluck('state_id')),
            'typeSummary'     => $rows->pluck('type')->filter()->countBy()->all(),
            'statusSummary'   => $rows->groupBy(fn ($r) => $r['has_state'] ? $r['operational_status'] : 'NOT_DISPATCHED')->map->count()->all(),
            'canDispatch'     => !$this->isPartner() && (bool) auth()->user()?->can('closure.dispatch'),
            'messages'        => $this->work()->messages($note, $user),
            'issues'          => $issues,
            'internal'        => $internal,
            'observing'       => $this->isObserving() ? $this->work()->observableCompanies($note) : [],
            'observedId'      => $this->isObserving() ? ($this->work()->partnerCompanyScope($note, $user)[0] ?? '') : '',
            'categories'      => $internal ? ClosureIssueCategory::query()->where('active', true)->with(['Subcategories' => fn ($q) => $q->where('active', true)->orderBy('display_order')->orderBy('name')])->orderBy('display_order')->orderBy('name')->get() : collect(),
            'companies'       => $internal && $this->partnerModal ? app(\App\Services\Closure\ClosurePartnerCompanies::class)->for(auth()->user(), $this->issueCompanyId ?: null) : collect(),
            'statusState'     => $this->statusStateId ? $states->get($this->statusStateId) : null,
            'canFinishAny'    => $rows->contains(fn ($r) => $r['finish']),
            'canRespond'      => $this->isPartner() && !$this->isObserving() && PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'),
            'closedIssues'    => $this->issuesQuery(true)->where('status', ClosureIssue::STATUS_RESOLVED)->count(),
        ]);
    }

    private function activeRiStage(ClosureWorkDossier $dossier): ?array
    {
        $ri = app(\App\Services\Closure\ClosureInternalReturnService::class)->activeForNote($this->noteId);

        return $ri ? $dossier->riStage($ri, $ri->reclaim_id ? \App\Models\Reclaim::query()->find($ri->reclaim_id) : null) : null;
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
            $query->whereIn('destination_company_id', $this->work()->partnerCompanyScope($this->note(), auth()->user()));
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
        $level = $this->work()->accessLevel($this->note(), auth()->user());

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
