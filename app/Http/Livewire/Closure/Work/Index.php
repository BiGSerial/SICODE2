<?php

namespace App\Http\Livewire\Closure\Work;

use App\Enum\ClosureOperationalStatus;
use App\Http\Livewire\Closure\Work\Concerns\ClosureWorkListing;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\Closure\{ClosureIssueCategory, ClosureOrderState};
use App\Models\{Company, Operation, User, UserDelegation};
use App\Services\Closure\{ClosureIssueWorkflowException, ClosureIssueWorkflowService, ClosureMetaClassifier, ClosureOrderBatchWorkflowService, ClosureOrderWorkflowException, ClosureOrderWorkflowService, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use ClosureWorkListing {
        clearFilters as protected clearListingFilters;
        statusUserOptions as protected statusUserOptionsFromTrait;
    }
    use WithListFilters;
    use WithPagination;

    public const PER_PAGE = 25;

    public const SELECT_ALL_LIMIT = 1000;

    /** Modal de ação em massa aberto. */
    public bool $showBatch = false;

    /** Calcula os cartões sem o recorte de status (cada cartão é um filtro de status e conta no mesmo recorte). */
    protected bool $ignoreStatusScope = false;

    private ?array $statusUserMemo = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'type'         => ['except' => 'all'],
        'onlyPriority' => ['except' => false, 'as' => 'prioridade'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected $paginationTheme = 'bootstrap';

    public int $perPage = self::PER_PAGE;

    public array $selectedStateIds = [];

    public string $batchAction = '';

    public string $batchReason = '';

    public string $batchStatus = '';

    public ?string $managerCompanyId = null;

    public ?string $managerAssigneeId = null;

    public string $managerPriority = '0';

    public array $batchPreview = [];

    public ?int $pendingStateId = null;

    /** 'status' (mudança de status com motivo) ou 'return' (devolver à pilha). */
    public ?string $pendingAction = null;

    public ?string $pendingStatus = null;

    public string $actionReason = '';

    public ?int $confirmFinishId = null;

    public array $batchReceipt = [];

    public ?string $filterCompany = null;

    public ?string $filterUser = null;

    public bool $onlyPriority = false;

    /** Estado cuja lista de operações está aberta no modal leve (carregada só sob demanda). */
    public ?int $opsStateId = null;

    /** Envio à parceira (Aguardando Parceira): Ordem em andamento e dados da pendência. */
    public ?int $partnerStateId = null;

    public string $issueCategoryId = '';

    public string $issueSubcategoryId = '';

    public string $issueCompanyId = '';

    public string $issueDescription = '';

    protected $listeners = ['closureWorkFinishConfirmed' => 'finishConfirmed', 'closureRiChanged' => '$refresh'];

    /**
     * Recorte da lista: `mine` = o que depende do usuário (executar); `follow` = Acompanhamento (aguarda outra pessoa);
     * `all` = sem recorte (o Controle sobrescreve).
     */
    public string $scope = self::SCOPE_MINE;

    public const SCOPE_MINE = 'mine';

    public const SCOPE_FOLLOW = 'follow';

    public const SCOPE_ALL = 'all';

    /** Status em que a Ordem espera outra pessoa/evento: parceira, depósito, meta futura e SAP encerrar a finalizada. */
    public const WAITING_STATUSES = [
        ClosureOrderWorkflowService::STATUS_WAITING_PARTNER,
        ClosureOrderWorkflowService::STATUS_DEPOSIT_BLOCKED,
        ClosureOrderWorkflowService::STATUS_WAITING,
        ClosureOrderWorkflowService::STATUS_FINISHED,
    ];

    public function mount(string $scope = self::SCOPE_MINE): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);

        $this->scope = in_array($scope, [self::SCOPE_MINE, self::SCOPE_FOLLOW], true) ? $scope : self::SCOPE_MINE;
    }

    public function isFollow(): bool
    {
        return $this->scope === self::SCOPE_FOLLOW;
    }

    /** Notas com RI ativo (Desenho está com a obra): as Ordens dela esperam o Desenho, não o usuário. */
    private function activeRiNotes(): \Closure
    {
        return fn ($sub) => $sub->select('note_id')->from('closure_internal_returns')->where('status', 'SENT')->whereNull('closed_at');
    }

    /** Recorte Minha atividade × Acompanhamento; vale sempre, inclusive nos cartões (que ignoram só o filtro de status). */
    protected function applyScope(Builder $query): void
    {
        if ($this->scope === self::SCOPE_MINE) {
            $query->whereNotIn('closure_order_states.operational_status', self::WAITING_STATUSES)
                ->whereNotIn('orders.note_id', $this->activeRiNotes());
        } elseif ($this->scope === self::SCOPE_FOLLOW) {
            $query->where(function (Builder $w) {
                $w->whereIn('closure_order_states.operational_status', self::WAITING_STATUSES)
                    ->orWhereIn('orders.note_id', $this->activeRiNotes())
                    // Obra encerrada que voltou a ficar aberta no SAP: aguarda novo despacho, mas é histórico a acompanhar.
                    ->orWhere(fn (Builder $r) => static::reopenedInSap($r));
            });
        }
    }

    /** Estado CLOSED cuja Ordem deixou de estar encerrada no SAP (alguém reabriu): vale como "reaberta". */
    public static function reopenedInSap(Builder $query): Builder
    {
        return $query->where('closure_order_states.operational_status', ClosureOrderWorkflowService::STATUS_CLOSED)
            ->where('orders.canceled', false)
            ->where(fn (Builder $q) => $q->whereNull('orders.statusSist')->orWhere(fn (Builder $w) => $w->where('orders.statusSist', 'not like', 'ENT%')->where('orders.statusSist', 'not like', 'ENC%')));
    }

    public function render()
    {
        $settings = app(ClosureSettings::class);

        $states  = $this->visibleStatesQuery()->paginate($this->perPage);
        $signals = app(\App\Services\Closure\ClosurePartnerSignalService::class);

        return view($this->listView(), [
            'activeDeposits'    => $signals->activeSignals(),
            'centerSignals'     => $states->getCollection()->pluck('cen_trab')->filter()->unique()->mapWithKeys(fn ($center) => [$center => $signals->signalForCenter($center)])->filter(),
            'hasCriteria'       => $this->hasCriteria(),
            'criteriaChips'     => $this->criteriaChips(),
            'batchActions'      => $this->availableBatchActions(),
            'states'            => $states,
            'depositSignals'    => app(\App\Services\Closure\ClosurePartnerSignalService::class)->activeByState($states->getCollection()->pluck('id')),
            'selfAssignEnabled' => $settings->selfAssignEnabled(),
            'allowFuture'       => $settings->allowFutureCompletion(),
            'statusOptions'     => ClosureOperationalStatus::cases(),
            'stats'             => $this->stats(),
            'statusUserOptions' => $this->statusUserOptions(),
            'applied'           => $this->appliedFilters(),
            'partnerState'      => $this->partnerStateId ? ClosureOrderState::query()->with('Order.Note')->find($this->partnerStateId) : null,
            'issueCategories'   => $this->partnerStateId
                ? ClosureIssueCategory::query()->where('active', true)->with(['Subcategories' => fn ($q) => $q->where('active', true)->orderBy('display_order')->orderBy('name')])->orderBy('display_order')->orderBy('name')->get()
                : collect(),
            'pendingState' => $this->pendingStateId ? ClosureOrderState::query()->with('Order.Note')->find($this->pendingStateId) : null,
            'opsState'     => $this->opsStateId ? ClosureOrderState::query()->with('Order.Note')->find($this->opsStateId) : null,
            'opsList'      => $this->opsStateId ? $this->operationsFor($this->opsStateId) : collect(),
        ]);
    }

    /** View da lista; o Controle sobrescreve. */
    protected function listView(): string
    {
        return 'livewire.closure.work.mine';
    }

    // ------------------------------------------------------------------ filtros (WithListFilters)

    /** @return array<string, array{label: string, depends?: array<int, string>}> */
    protected function listFilterDefinitions(): array
    {
        return [
            'statusUser' => ['label' => 'Status usuário'],
            'rubric'     => ['label' => 'Rubrica'],
            'status'     => ['label' => 'Status da atividade'],
        ];
    }

    /** @return array<string, array<int, array{v: string, l: string, p?: array<string, string>}>> */
    protected function listFilterOptions(): array
    {
        return [
            'statusUser' => $this->statusUserFilterOptions(),
            'rubric'     => $this->rubricFilterOptions(),
            'status'     => $this->statusFilterOptions(),
        ];
    }

    protected function statusUserFilterOptions(): array
    {
        return array_map(fn ($o) => ['v' => $o['v'], 'l' => $o['v'] . ' (' . $o['n'] . ')'], $this->statusUserOptions());
    }

    protected function rubricFilterOptions(): array
    {
        return $this->rememberFilterOptions('rubric', fn () => array_map(fn ($r) => ['v' => $r, 'l' => $r], ClosureDispatchListQuery::rubricOptions()));
    }

    protected function statusFilterOptions(): array
    {
        return collect(ClosureOperationalStatus::cases())
            ->reject(fn (ClosureOperationalStatus $case) => $case->isTerminal())
            ->reject(fn (ClosureOperationalStatus $case) => match ($this->scope) {
                self::SCOPE_MINE   => in_array($case->value, self::WAITING_STATUSES, true),
                self::SCOPE_FOLLOW => !in_array($case->value, self::WAITING_STATUSES, true),
                default            => false,
            })
            ->map(fn (ClosureOperationalStatus $case) => ['v' => $case->value, 'l' => $case->label()])->values()
            ->when($this->isFollow(), fn ($options) => $options->push(['v' => ClosureOrderWorkflowService::STATUS_CLOSED, 'l' => 'Reaberta no SAP']))
            ->all();
    }

    /** Opções do filtro Status usuário, uma vez por requisição (a consulta agrega a lista inteira). */
    public function statusUserOptions(): array
    {
        return $this->statusUserMemo ??= $this->filteredStatusUserOptions();
    }

    private function filteredStatusUserOptions(): array
    {
        // As opções contam a lista sem o próprio filtro de Status usuário.
        $saved = $this->filters;
        unset($this->filters['statusUser']);

        try {
            return $this->statusUserOptionsFromTrait();
        } finally {
            $this->filters = $saved;
        }
    }

    protected function afterListFiltersChanged(): void
    {
        $this->resetPage();
        $this->selectedStateIds = [];
        $this->batchPreview     = [];
        $this->dispatchBrowserEvent('ops-filters', ['sel' => (object) $this->filters]);
    }

    /** Traz os filtros do trait para as propriedades que a consulta herdada lê. */
    protected function syncListing(): void
    {
        $this->statusUsers = $this->filterValues('statusUser');
        $this->status      = 'all';
    }

    /** Conjuntos de status dos cartões que juntam vários status. */
    protected const CARD_STATUS_SETS = [
        'blocked' => [ClosureOrderWorkflowService::STATUS_PAUSED, ClosureOrderWorkflowService::STATUS_WAITING_PARTNER, ClosureOrderWorkflowService::STATUS_DEPOSIT_BLOCKED],
    ];

    /** Cartões: status liga/desliga (um recorte por vez), prioridade liga/desliga e "Total" limpa os recortes dos cartões. */
    public function toggleCard(string $card): void
    {
        $filters = $this->filters;

        if ($card === 'priority') {
            $this->onlyPriority = !$this->onlyPriority;
        } elseif ($card === 'all') {
            unset($filters['status']);
            $this->onlyPriority = false;
        } else {
            $set = self::CARD_STATUS_SETS[$card] ?? (in_array($card, ClosureOperationalStatus::values(), true) ? [$card] : null);

            if ($set === null) {
                return;
            }

            $current = (array) ($filters['status'] ?? []);
            sort($current);
            $wanted = $set;
            sort($wanted);

            if ($current === $wanted) {
                unset($filters['status']);
            } else {
                $filters['status'] = $set;
            }
        }

        $this->filters = $filters;
        $this->afterListFiltersChanged();
    }

    /** O cartão está aceso? (mesma regra do toggleCard) */
    public function cardActive(string $card): bool
    {
        $current = (array) ($this->filters['status'] ?? []);
        sort($current);
        $wanted = self::CARD_STATUS_SETS[$card] ?? [$card];
        sort($wanted);

        return $current === $wanted;
    }

    public function clearFilters(): void
    {
        $this->clearListingFilters();
        $this->onlyPriority     = false;
        $this->selectedStateIds = [];
        $this->clearListFilters();
    }

    /** Remove um chip: critério da tela (busca, tipo, prioridade, busca em massa) ou filtro do trait. */
    public function removeAnyFilter(string $key): void
    {
        match ($key) {
            'search'   => $this->search = '',
            'bulk'     => $this->clearBulkSearch(),
            'type'     => $this->type         = 'all',
            'priority' => $this->onlyPriority = false,
            default    => $this->removeListFilter($key),
        };

        $this->resetPage();
        $this->selectedStateIds = [];
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || trim($this->search) !== '' || $this->type !== 'all' || $this->onlyPriority || $this->bulkTerms !== [];
    }

    /** @return array<int, array{key: string, label: string, value: string}> */
    protected function criteriaChips(): array
    {
        $chips = [];

        if (trim($this->search) !== '') {
            $chips[] = ['key' => 'search', 'label' => 'Busca', 'value' => trim($this->search)];
        }

        if ($this->bulkTerms !== []) {
            $chips[] = ['key' => 'bulk', 'label' => 'Busca em massa', 'value' => count($this->bulkTerms) . ' termo(s)'];
        }

        if ($this->type !== 'all') {
            $chips[] = ['key' => 'type', 'label' => 'Tipo', 'value' => ClosureMetaClassifier::label($this->type)];
        }

        if ($this->onlyPriority) {
            $chips[] = ['key' => 'priority', 'label' => 'Situação', 'value' => 'Só prioritárias'];
        }

        return $chips;
    }

    // ------------------------------------------------------------------ ações em massa (modal)

    /** @return array<string, array{label: string, icon: string, hint: string}> */
    public function availableBatchActions(): array
    {
        return [
            'start'         => ['label' => 'Iniciar', 'icon' => 'ri-play-circle-line', 'hint' => 'Põe em andamento as Ordens atribuídas a você.'],
            'change_status' => ['label' => 'Alterar status', 'icon' => 'ri-refresh-line', 'hint' => 'Pausa, depósito bloqueado/liberado ou volta a Em andamento, com motivo quando exigido.'],
            'finish'        => ['label' => 'Finalizar', 'icon' => 'ri-checkbox-circle-line', 'hint' => 'Marca como finalizadas; aguardam a baixa no SAP.'],
        ];
    }

    public function openBatch(string $action): void
    {
        if (!array_key_exists($action, $this->availableBatchActions())) {
            $this->toast('Ação indisponível', 'Você não tem acesso a esta ação em massa.', 'warning');

            return;
        }

        if ($this->selectedStateIds === []) {
            $this->toast('Nada selecionado', 'Marque as Ordens antes de escolher a ação.', 'warning');

            return;
        }

        $this->batchAction       = $action;
        $this->batchPreview      = [];
        $this->batchReceipt      = [];
        $this->batchReason       = '';
        $this->batchStatus       = '';
        $this->managerCompanyId  = null;
        $this->managerAssigneeId = null;
        $this->managerPriority   = '1';
        $this->showBatch         = true;
    }

    public function closeBatch(): void
    {
        $this->showBatch    = false;
        $this->batchAction  = '';
        $this->batchPreview = [];
        $this->batchReceipt = [];
        $this->batchReason  = '';
        $this->batchStatus  = '';
    }

    /** Volta da prévia para os campos, sem perder a seleção. */
    public function editBatch(): void
    {
        $this->batchPreview = [];
    }

    /** Seleciona todas as Ordens do recorte atual (até o limite), entre páginas. */
    public function selectAllFilteredStates(): void
    {
        $this->selectedStateIds = $this->visibleStatesQuery()->reorder()->limit(self::SELECT_ALL_LIMIT)->pluck('closure_order_states.id')->map(fn ($id) => (int) $id)->all();
        $this->batchPreview     = [];
    }
    public function exportQuery(): Builder
    {
        return $this->visibleStatesQuery();
    }

    public function updatedStatusUsers(): void
    {
        $this->resetPage();
        $this->selectedStateIds = [];
        $this->batchPreview     = [];
    }

    /** Exporta a lista (com os filtros atuais) em segundo plano; avisa por notificação quando estiver pronta. */
    public function exportList(): void
    {
        \App\Jobs\Closure\ExportClosureListJob::dispatch(static::class, $this->exportFilters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>',
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> propriedades públicas da lista que definem o recorte exportado */
    protected function exportFilters(): array
    {
        return [
            'type'          => $this->type,
            'status'        => $this->status,
            'search'        => $this->search,
            'statusUsers'   => $this->statusUsers,
            'bulkTerms'     => $this->bulkTerms,
            'filterCompany' => $this->filterCompany,
            'filterUser'    => $this->filterUser,
            'onlyPriority'  => $this->onlyPriority,
            'filters'       => $this->filters,
        ];
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedType(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : self::PER_PAGE;
        $this->resetPage();
    }

    public function updatedBatchAction(): void
    {
        $this->batchPreview = [];
    }

    public function updatedBatchStatus(): void
    {
        $this->batchPreview = [];
    }

    public function updatedManagerCompanyId(): void
    {
        $this->managerAssigneeId = null;
        $this->batchPreview      = [];
    }

    public function getDestinationCompaniesProperty()
    {
        return Company::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Destinos válidos de uma pendência: empresas parceiras/empreiteiras. O cadastro não tem flag de parceira;
     * a regra do sistema é a empresa ter contrato (`contracts`). Fica de fora a empresa de quem envia, e a
     * empresa atual da Ordem é sempre mantida como opção.
     */
    public function getPartnerCompaniesProperty()
    {
        $current = $this->partnerStateId
            ? \App\Models\Production::query()->whereKey(ClosureOrderState::query()->whereKey($this->partnerStateId)->value('production_id'))->value('company_id')
            : null;

        return app(\App\Services\Closure\ClosurePartnerCompanies::class)->for(auth()->user(), $current ? (string) $current : null);
    }

    public function getDestinationUsersProperty()
    {
        if (!$this->managerCompanyId) {
            return collect();
        }

        return User::query()
            ->where('company_id', $this->managerCompanyId)
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);
    }

    public function toggleState(int $stateId): void
    {
        $selected               = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);
        $this->selectedStateIds = $selected->contains($stateId)
            ? $selected->reject(fn (int $id) => $id === $stateId)->values()->all()
            : $selected->push($stateId)->unique()->values()->all();
        $this->batchPreview = [];
    }

    public function toggleVisibleStates(): void
    {
        $visible  = $this->visibleStatesQuery()->forPage(max(1, (int) $this->page), $this->perPage)->pluck('closure_order_states.id')->map(fn ($id) => (int) $id);
        $selected = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);

        $this->selectedStateIds = $visible->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visible->contains($id))->values()->all()
            : $selected->merge($visible)->unique()->values()->all();
        $this->batchPreview = [];
    }

    /** Ações simples por linha: start, return (pede motivo), finish (pede confirmação). */
    public function runAction(int $stateId, string $action): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->toast('Ordem indisponível', 'Esta Ordem não está disponível na sua lista.', 'warning');

            return;
        }

        if ($action === 'return') {
            // O executor não devolve à pilha nesta tela (a gestão faz isso no Despacho).
            $this->toast('Ação indisponível', 'Aqui você não devolve a Ordem à pilha. Use a transferência, o envio à parceira ou o RI.', 'warning');

            return;
        }

        if ($action === 'finish') {
            $this->askFinish($state);

            return;
        }

        $this->runWorkflow($action, $state);
    }

    /** A Ordem encerrada voltou a ficar aberta no SAP e o usuário (responsável ou gestão) pode reabrir a atividade. */
    public function canReopen(ClosureOrderState $state): bool
    {
        return $state->operational_status === ClosureOrderWorkflowService::STATUS_CLOSED
            && ($this->isOwner($state) || $this->isManager());
    }

    public function reopenActivity(int $stateId): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state || !$this->canReopen($state)) {
            $this->toast('Ação indisponível', 'Esta atividade não pode ser reaberta.', 'warning');

            return;
        }

        try {
            app(ClosureOrderWorkflowService::class)->reopenAfterSap($state, auth()->user());
            $this->toast('Atividade reaberta', 'Ordem ' . ($state->Order?->ordem ?? "#{$state->id}") . ' voltou para Minha atividade.');
        } catch (\App\Services\Closure\ClosureOrderWorkflowException $e) {
            $this->toast('Não foi possível reabrir', $e->getMessage(), 'warning');
        }
    }

    /** Alteração de status (Pausa, Aguardando Parceira, Depósito, voltar a Em andamento). */
    public function changeStatus(int $stateId, string $newStatus): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->toast('Ordem indisponível', 'Esta Ordem não está disponível na sua lista.', 'warning');

            return;
        }

        if (!in_array($newStatus, $this->statusTargets($state), true) || !$this->canChangeStatus($state)) {
            $this->toast('Transição não permitida', 'Este status não pode ser aplicado no estado atual da Ordem.', 'warning');

            return;
        }

        if ($newStatus === ClosureOrderWorkflowService::STATUS_WAITING_PARTNER) {
            $this->openPartnerSend($state);

            return;
        }

        if ($this->statusNeedsReason($state, $newStatus)) {
            $this->openReason($state->id, 'status', $newStatus);

            return;
        }

        $this->runWorkflow('status', $state, null, $newStatus);
    }

    private function openPartnerSend(ClosureOrderState $state): void
    {
        $this->partnerStateId     = $state->id;
        $this->issueCategoryId    = '';
        $this->issueSubcategoryId = '';
        $this->issueDescription   = '';
        $this->issueCompanyId     = (string) (\App\Models\Production::query()->whereKey($state->production_id)->value('company_id') ?? '');
        $this->resetErrorBag();
    }

    public function updatedIssueCategoryId(): void
    {
        $this->issueSubcategoryId = '';
    }

    public function cancelPartnerSend(): void
    {
        $this->partnerStateId = null;
        $this->reset('issueCategoryId', 'issueSubcategoryId', 'issueCompanyId', 'issueDescription');
        $this->resetErrorBag();
    }

    /** Abre a pendência bloqueante e a envia à parceira; é isso que coloca a Ordem em Aguardando Parceira. */
    public function sendToPartner(): void
    {
        $this->validate([
            'issueCategoryId'    => 'required',
            'issueSubcategoryId' => 'required',
            'issueCompanyId'     => 'required',
        ], [
            'issueCategoryId.required'    => 'Escolha a categoria.',
            'issueSubcategoryId.required' => 'Escolha a subcategoria.',
            'issueCompanyId.required'     => 'Escolha a parceira.',
        ]);

        $state = $this->partnerStateId ? $this->visibleStatesQuery()->find($this->partnerStateId) : null;

        if (!$state || !$this->canChangeStatus($state)) {
            $this->cancelPartnerSend();
            $this->toast('Ação indisponível', 'Esta Ordem não pode ser enviada à parceira agora.', 'warning');

            return;
        }

        $category    = ClosureIssueCategory::query()->find($this->issueCategoryId);
        $subcategory = $category?->Subcategories()->whereKey($this->issueSubcategoryId)->first();
        $company     = Company::query()->find($this->issueCompanyId);

        if (!$category || !$subcategory || !$company) {
            $this->toast('Dados inválidos', 'Categoria, subcategoria ou parceira inválida.', 'warning');

            return;
        }

        if ($subcategory->requires_description && trim($this->issueDescription) === '') {
            $this->addError('issueDescription', 'Esta subcategoria exige uma descrição.');

            return;
        }

        try {
            app(ClosureIssueWorkflowService::class)->requestToPartner($state, $category, $subcategory, $company, auth()->user(), $this->issueDescription, null, true);
            $this->toast('Enviada à parceira', 'Ordem ' . ($state->Order?->ordem ?? $state->id) . ' em Aguardando Parceira até a devolução da solução.');
            $this->cancelPartnerSend();
        } catch (ClosureIssueWorkflowException|ClosureOrderWorkflowException $exception) {
            $this->toast('Não foi possível enviar', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    public function confirmActionWithReason(): void
    {
        if (!$this->pendingStateId || !$this->pendingAction) {
            $this->toast('Ação não identificada', 'Escolha novamente a ação que deseja realizar.', 'warning');

            return;
        }

        if (trim($this->actionReason) === '') {
            $this->toast('Motivo obrigatório', 'Informe o motivo antes de continuar.', 'warning');

            return;
        }

        $state = $this->visibleStatesQuery()->find($this->pendingStateId);

        if (!$state) {
            $this->cancelActionWithReason();
            $this->toast('Ordem indisponível', 'Esta Ordem não está mais disponível na sua lista.', 'warning');

            return;
        }

        $this->runWorkflow($this->pendingAction, $state, $this->actionReason, $this->pendingStatus);
        $this->cancelActionWithReason();
    }

    public function cancelActionWithReason(): void
    {
        $this->pendingStateId = null;
        $this->pendingAction  = null;
        $this->pendingStatus  = null;
        $this->actionReason   = '';
    }

    /** Retorno do SweetAlert de confirmação de "Finalizar". */
    public function finishConfirmed(): void
    {
        $stateId               = $this->confirmFinishId;
        $this->confirmFinishId = null;

        if (!$stateId) {
            return;
        }

        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->toast('Ordem indisponível', 'Esta Ordem não está mais disponível na sua lista.', 'warning');

            return;
        }

        $this->runWorkflow('finish', $state);
    }

    public function prepareBatchPreview(): void
    {
        if (!$this->batchAction || empty($this->selectedStateIds)) {
            $this->toast('Seleção incompleta', 'Selecione Ordens e a ação que deseja aplicar.', 'warning');

            return;
        }

        if (!$this->validateBatchPayload()) {
            return;
        }

        $states = $this->visibleStatesQuery()->whereIn('closure_order_states.id', $this->selectedStateIds)->get();

        if ($states->count() !== count($this->selectedStateIds)) {
            $this->toast('Seleção desatualizada', 'Uma ou mais Ordens deixaram de estar na sua lista. Atualize a seleção.', 'warning');

            return;
        }

        try {
            $payload = $this->batchPayload();
            $result  = app(ClosureOrderBatchWorkflowService::class)->preview(
                $states->pluck('id')->all(),
                $this->batchAction,
                $payload,
                auth()->user(),
            );
            $labels             = $states->mapWithKeys(fn (ClosureOrderState $state) => [$state->id => $state->Order?->ordem ?? "Item {$state->id}"]);
            $this->batchPreview = [
                'action'   => $this->batchAction,
                'payload'  => $payload,
                'stateIds' => $states->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                'eligible' => $result->eligible()->map(fn ($item) => $labels->get($item->stateId, "Item {$item->stateId}"))->all(),
                'refusals' => $result->refused()->map(fn ($item) => [
                    'order'  => $labels->get($item->stateId, "Item {$item->stateId}"),
                    'reason' => $item->reason,
                ])->all(),
            ];
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível gerar a prévia', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    public function confirmBatchAction(): void
    {
        if (!$this->validateBatchPayload()) {
            return;
        }

        if (!$this->batchPreview
            || $this->batchPreview['action'] !== $this->batchAction
            || $this->batchPreview['payload'] !== $this->batchPayload()
            || $this->batchPreview['stateIds'] !== collect($this->selectedStateIds)->map(fn ($id) => (int) $id)->sort()->values()->all()) {
            $this->toast('Prévia desatualizada', 'A seleção ou os dados mudaram. Gere uma nova prévia antes de confirmar.', 'warning');

            return;
        }

        try {
            $result = app(ClosureOrderBatchWorkflowService::class)->execute(
                $this->batchPreview['stateIds'],
                $this->batchAction,
                $this->batchPayload(),
                auth()->user(),
            );
            $states             = ClosureOrderState::query()->with('Order')->whereIn('id', $this->batchPreview['stateIds'])->get();
            $labels             = $states->mapWithKeys(fn (ClosureOrderState $state) => [$state->id => $state->Order?->ordem ?? "Item {$state->id}"]);
            $this->batchReceipt = [
                'batchId'   => $result->batchId,
                'successes' => $result->successful()->map(fn ($item) => $labels->get($item->stateId, "Item {$item->stateId}"))->all(),
                'refusals'  => $result->refused()->map(fn ($item) => [
                    'order'  => $labels->get($item->stateId, "Item {$item->stateId}"),
                    'reason' => $item->reason,
                ])->all(),
            ];
            $ok   = count($this->batchReceipt['successes']);
            $fail = count($this->batchReceipt['refusals']);
            $this->clearSelection();
            $this->batchReason       = '';
            $this->batchStatus       = '';
            $this->managerCompanyId  = null;
            $this->managerAssigneeId = null;
            $this->batchPreview      = [];
            $this->toast(
                'Lote concluído',
                "{$ok} Ordem(ns) processada(s), {$fail} recusada(s). Consulte o recibo abaixo.",
                $fail === 0 ? 'success' : ($ok === 0 ? 'error' : 'warning'),
            );
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível concluir o lote', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    /** Indicadores dos cartões, sobre o conjunto filtrado e SEM o recorte de status (cada cartão é um filtro de status). */
    public function stats(): array
    {
        $this->ignoreStatusScope = true;

        try {
            $base                     = $this->visibleStatesQuery()->reorder()->toBase();
            $base->columns            = null;
            $base->orders             = null;
            $base->bindings['select'] = [];

            $row = $base->selectRaw("count(*) as total, coalesce(sum(closure_order_states.operational_status = 'IN_PROGRESS'), 0) as progress, coalesce(sum(closure_order_states.operational_status = 'ASSIGNED'), 0) as assigned, coalesce(sum(closure_order_states.priority = 1), 0) as priority, coalesce(sum(closure_order_states.operational_status = 'AVAILABLE'), 0) as available, coalesce(sum(closure_order_states.operational_status = 'DEPOSIT_BLOCKED'), 0) as deposit, coalesce(sum(closure_order_states.operational_status in ('PAUSED','WAITING_PARTNER','DEPOSIT_BLOCKED')), 0) as blocked, coalesce(sum(closure_order_states.operational_status = 'PAUSED'), 0) as paused, coalesce(sum(closure_order_states.operational_status = 'WAITING_PARTNER'), 0) as partner, coalesce(sum(closure_order_states.operational_status = 'WAITING'), 0) as future, coalesce(sum(closure_order_states.operational_status = 'FINISHED'), 0) as finished, coalesce(sum(closure_order_states.operational_status = 'CLOSED'), 0) as reopened, coalesce(sum(orders.note_id in (select note_id from closure_internal_returns where status = 'SENT' and closed_at is null)), 0) as ri, coalesce(avg(timestampdiff(MINUTE, closure_order_states.entered_current_status_at, now())), 0) as avg_min")->first();
        } finally {
            $this->ignoreStatusScope = false;
        }

        return [
            'total'     => (int) ($row->total ?? 0),
            'progress'  => (int) ($row->progress ?? 0),
            'assigned'  => (int) ($row->assigned ?? 0),
            'priority'  => (int) ($row->priority ?? 0),
            'available' => (int) ($row->available ?? 0),
            'deposit'   => (int) ($row->deposit ?? 0),
            'blocked'   => (int) ($row->blocked ?? 0),
            'paused'    => (int) ($row->paused ?? 0),
            'partner'   => (int) ($row->partner ?? 0),
            'future'    => (int) ($row->future ?? 0),
            'finished'  => (int) ($row->finished ?? 0),
            'ri'        => (int) ($row->ri ?? 0),
            'reopened'  => (int) ($row->reopened ?? 0),
            'avg_min'   => (int) round((float) ($row->avg_min ?? 0)),
        ];
    }

    /** @return array<int, array{kind: string, label: string}> */
    public function appliedFilters(): array
    {
        $chips = [];

        if (trim($this->search) !== '') {
            $chips[] = ['kind' => 'search', 'label' => 'Busca: ' . trim($this->search)];
        }

        if ($this->type !== 'all') {
            $chips[] = ['kind' => 'type', 'label' => 'Tipo: ' . \App\Services\Closure\ClosureMetaClassifier::label($this->type)];
        }

        if ($this->status !== 'all') {
            $chips[] = ['kind' => 'status', 'label' => 'Status: ' . ClosureOperationalStatus::labelFor($this->status)];
        }

        foreach ($this->statusUsers as $prefix) {
            $chips[] = ['kind' => 'su', 'value' => (string) $prefix, 'label' => 'Status usuário: ' . $prefix];
        }

        if ($this->filterCompany) {
            $chips[] = ['kind' => 'company', 'label' => 'Empresa: ' . (Company::query()->whereKey($this->filterCompany)->value('name') ?? '—')];
        }

        if ($this->filterUser) {
            $chips[] = ['kind' => 'user', 'label' => 'Responsável: ' . (User::query()->whereKey($this->filterUser)->value('name') ?? '—')];
        }

        if ($this->onlyPriority) {
            $chips[] = ['kind' => 'priority', 'label' => 'Só prioritárias'];
        }

        return $chips;
    }

    public function removeFilter(string $kind, ?string $value = null): void
    {
        match ($kind) {
            'su'       => $this->statusUsers   = array_values(array_filter($this->statusUsers, fn ($v) => $v !== $value)),
            'search'   => $this->search        = '',
            'type'     => $this->type          = 'all',
            'status'   => $this->status        = 'all',
            'company'  => $this->filterCompany = null,
            'user'     => $this->filterUser    = null,
            'priority' => $this->onlyPriority  = false,
            default    => null,
        };
        $this->resetPage();
        $this->selectedStateIds = [];
    }

    public function clearSelectionNow(): void
    {
        $this->selectedStateIds = [];
        $this->batchPreview     = [];
    }

    public function showOperations(int $stateId): void
    {
        $this->opsStateId = $this->visibleStatesQuery()->whereKey($stateId)->exists() ? $stateId : null;
    }

    public function closeOperations(): void
    {
        $this->opsStateId = null;
    }

    protected function operationsFor(int $stateId)
    {
        $orderId = ClosureOrderState::query()->whereKey($stateId)->value('order_id');

        return $orderId ? Operation::query()->where('order_id', $orderId)->orderBy('operacao')->get() : collect();
    }

    /** Operações já confirmadas (status CONF*) — mesma regra do contador da lista de Despacho. */
    public function isConfirmedOperation(?string $status): bool
    {
        return str_starts_with(strtoupper((string) $status), 'CONF');
    }

    /** Primeiro grupo do status SAP do usuário (ex.: "LIB CNPA CAPC ..." → "LIB CNPA"). */
    public function sapHeadline(?string $status): string
    {
        $tokens = preg_split('/\s+/', trim((string) $status), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $tokens ? implode(' ', array_slice($tokens, 0, 2)) : '—';
    }

    public function updatedOnlyPriority(): void
    {
        $this->resetPage();
        $this->selectedStateIds = [];
    }

    public function holderLabel(ClosureOrderState $state): string
    {
        if ($state->current_holder_user_id) {
            return $state->CurrentHolderUser?->name ?? 'Usuário indisponível';
        }

        return $state->CurrentHolderCompany?->name ?? 'Pilha da empresa';
    }

    public function elapsedLabel(ClosureOrderState $state): string
    {
        return $state->entered_current_status_at
            ? $state->entered_current_status_at->diffForHumans(null, true)
            : '—';
    }

    /**
     * Titulares que o usuário cobre agora (delegação vigente: férias, licença...). Vale para Minha atividade e Acompanhamento;
     * só visibilidade: as ações continuam exigindo ser o responsável (ou gestor).
     *
     * @return array<int, string>
     */
    public function coveredUserIds(): array
    {
        return $this->coveredIds ??= UserDelegation::query()->currentForDelegate((string) auth()->id())->pluck('principal_id')->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    private ?array $coveredIds = null;

    public function isOwner(ClosureOrderState $state): bool
    {
        return $state->assigned_user_id && (string) $state->assigned_user_id === (string) auth()->id();
    }

    protected function isManager(): bool
    {
        return (bool) auth()->user()?->can('closure.manage');
    }

    public function canRun(ClosureOrderState $state, string $action): bool
    {
        return match ($action) {
            'start'  => $this->isOwner($state) && $state->operational_status === ClosureOrderWorkflowService::STATUS_ASSIGNED,
            'return' => $this->isOwner($state) && in_array($state->operational_status, array_diff(
                ClosureOrderWorkflowService::WORKING_STATUSES,
                [ClosureOrderWorkflowService::STATUS_AVAILABLE],
            ), true),
            'finish' => ($this->isOwner($state) || $this->isManager())
                && in_array($state->operational_status, ClosureOrderWorkflowService::FINISHABLE_STATUSES, true),
            default => false,
        };
    }

    /** Transferência entre usuários: o responsável pode enviar a Ordem a outro usuário do Encerramento (que aceita ou rejeita). */
    public function canTransfer(ClosureOrderState $state): bool
    {
        return $this->isOwner($state) && in_array($state->operational_status, \App\Services\Closure\ClosureTransferService::TRANSFERABLE_STATUSES, true);
    }

    public function transferState(int $stateId): void
    {
        $this->emit('closureTransferOpen', [$stateId]);
    }

    public function transferSelected(): void
    {
        $mine = ClosureOrderState::query()->whereIn('id', $this->selectedStateIds)->where('assigned_user_id', auth()->id())->pluck('id')->all();

        if ($mine === []) {
            $this->toast('Nada para transferir', 'Selecione Ordens que estejam com você.', 'warning');

            return;
        }

        $this->emit('closureTransferOpen', $mine);
    }

    /** RI ao Desenho: o responsável da Ordem (ou a gestão) devolve a OBRA inteira ao Desenho. */
    public function canSendRi(ClosureOrderState $state): bool
    {
        return ($this->isOwner($state) || $this->isManager())
            && in_array($state->operational_status, ClosureOrderWorkflowService::WORKING_STATUSES, true);
    }

    public function sendRi(int $stateId): void
    {
        $noteId = ClosureOrderState::query()->whereKey($stateId)->first()?->Order?->note_id;

        if ($noteId) {
            $this->emit('closureRiOpen', (int) $noteId);
        }
    }

    public function cancelRi(int $internalReturnId): void
    {
        $this->emit('closureRiCancelOpen', $internalReturnId);
    }

    public function canChangeStatus(ClosureOrderState $state): bool
    {
        return ($this->isOwner($state) || $this->isManager())
            && array_key_exists($state->operational_status, ClosureOrderWorkflowService::MANUAL_TRANSITIONS);
    }

    /** @return array<int, string> */
    public function statusTargets(ClosureOrderState $state): array
    {
        return ClosureOrderWorkflowService::MANUAL_TRANSITIONS[$state->operational_status] ?? [];
    }

    public function statusActionLabel(string $target): string
    {
        return $target === ClosureOrderWorkflowService::STATUS_IN_PROGRESS
            ? 'Voltar a Em andamento'
            : ClosureOperationalStatus::labelFor($target);
    }

    /** Motivo pelo qual Finalizar está bloqueado para a Ordem (null quando liberado). */
    public function finishBlockedReason(ClosureOrderState $state): ?string
    {
        if ($state->meta_type === ClosureMetaClassifier::TYPE_FUTURE && !app(ClosureSettings::class)->allowFutureCompletion()) {
            return 'Ordem de meta futura: o despachante ainda não liberou a finalização antes do mês da meta.';
        }

        return null;
    }

    private function statusNeedsReason(ClosureOrderState $state, string $target): bool
    {
        return in_array($target, ClosureOrderWorkflowService::REASON_REQUIRED_TARGETS, true)
            || in_array($state->operational_status, [
                ClosureOrderWorkflowService::STATUS_FINISHED,
                ClosureOrderWorkflowService::STATUS_WAITING,
            ], true);
    }

    private function openReason(int $stateId, string $action, ?string $status = null): void
    {
        $this->pendingStateId = $stateId;
        $this->pendingAction  = $action;
        $this->pendingStatus  = $status;
        $this->actionReason   = '';
    }

    private function askFinish(ClosureOrderState $state): void
    {
        if (!$this->canRun($state, 'finish')) {
            $this->toast('Ação indisponível', 'Esta Ordem não pode ser finalizada no estado atual.', 'warning');

            return;
        }

        if ($blocked = $this->finishBlockedReason($state)) {
            $this->toast('Finalização bloqueada', $blocked, 'warning');

            return;
        }

        $this->confirmFinishId = $state->id;
        $future                = $state->meta_type === ClosureMetaClassifier::TYPE_FUTURE;

        $this->dispatchBrowserEvent('closure:confirm', [
            'title' => 'Finalizar a Ordem ' . ($state->Order?->ordem ?? ''),
            'html'  => $future
                ? 'Esta é uma Ordem de <strong>meta futura</strong>. Ela ficará em <strong>Aguardando</strong> até o mês da meta e será finalizada automaticamente.'
                : 'A Ordem será marcada como <strong>Finalizada</strong> e aguardará o encerramento no SAP.',
            'confirmText' => 'Sim, finalizar',
            'cancelText'  => 'Cancelar',
            'action'      => 'closureWorkFinishConfirmed',
        ]);
    }

    /** Minha atividade e Acompanhamento: só o que é do usuário (e de quem ele cobre). O Controle desliga isto e vê todos. */
    protected function limitsToOwner(): bool
    {
        return true;
    }

    protected function visibleStatesQuery(): Builder
    {
        $this->syncListing();

        $user    = auth()->user();
        $query   = $this->baseListingQuery();
        $rubrics = $this->filterValues('rubric');

        if ($rubrics !== []) {
            $query->whereIn('orders.note_id', fn ($sub) => $sub->select('id')->from('notes')->whereIn('rubrica', $rubrics));
        }

        // Centro de trabalho da operação 0010: base para marcar as Ordens no alcance de um depósito bloqueado.
        $query->selectRaw("(select max(o10.cenTrab) from operations o10 where o10.order_id = orders.id and o10.operacao = '0010') as cen_trab");

        // Minha atividade / Acompanhamento: só as Ordens do próprio usuário, inclusive para a gestão (a visão de todos é o Controle, que desliga isto).
        if ($this->limitsToOwner()) {
            // Substituto: quem cobre alguém de férias/licença (UserDelegation vigente) também enxerga as Ordens dessa pessoa.
            $owners = [(string) $user->id, ...$this->coveredUserIds()];

            $query->where(function (Builder $own) use ($owners) {
                $own->whereIn('closure_order_states.assigned_user_id', $owners)
                    ->orWhereIn('closure_order_states.current_holder_user_id', $owners);
            });
        }

        if ($this->onlyPriority) {
            $query->where('closure_order_states.priority', true);
        }

        $this->applyScope($query);
        $this->applyStatusScope($query);

        return $query
            ->orderByDesc('closure_order_states.priority')
            ->orderBy('closure_order_states.entered_current_status_at');
    }

    /** Recorte por status da atividade; a visão de controle sobrescreve (várias opções e Disponível incluído). */
    protected function applyStatusScope(Builder $query): void
    {
        $statuses = $this->ignoreStatusScope ? [] : $this->filterValues('status');

        if ($statuses !== []) {
            $query->whereIn('closure_order_states.operational_status', $statuses);

            return;
        }

        $query->whereNotIn('closure_order_states.operational_status', $this->hiddenStatuses());
    }

    /** Status que ficam fora quando não há filtro de status. A lista do executor também esconde a pilha (Disponível). */
    protected function hiddenStatuses(): array
    {
        return $this->isFollow()
            ? [ClosureOrderWorkflowService::STATUS_CANCELLED, ClosureOrderWorkflowService::STATUS_AVAILABLE]
            : [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED, ClosureOrderWorkflowService::STATUS_AVAILABLE];
    }

    private function runWorkflow(string $action, ClosureOrderState $state, ?string $reason = null, ?string $newStatus = null): void
    {
        $user    = auth()->user();
        $ordem   = $state->Order?->ordem ?? "#{$state->id}";
        $allowed = match ($action) {
            'start', 'return', 'finish' => $this->canRun($state, $action),
            'status' => $this->canChangeStatus($state) && in_array($newStatus, $this->statusTargets($state), true),
            default  => false,
        };

        if (!$allowed) {
            $this->toast('Ação indisponível', 'Esta ação não está disponível para a Ordem no estado atual.', 'warning');

            return;
        }

        try {
            $workflow = app(ClosureOrderWorkflowService::class);

            switch ($action) {
                case 'start':
                    $workflow->start($state, $user);
                    $this->toast('Atividade iniciada', "Ordem {$ordem} em andamento.");

                    break;
                case 'return':
                    $workflow->returnToPool($state, $user, (string) $reason);
                    $this->toast('Ordem devolvida', "Ordem {$ordem} voltou para a pilha.");

                    break;
                case 'status':
                    $workflow->changeStatus($state, (string) $newStatus, $user, $reason);
                    $this->toast('Status alterado', "Ordem {$ordem}: " . ClosureOperationalStatus::labelFor($newStatus) . '.');

                    break;
                case 'finish':
                    $done = $workflow->finish($state, $user);
                    $this->toast(
                        $done->operational_status === ClosureOrderWorkflowService::STATUS_WAITING ? 'Ordem em Aguardando' : 'Ordem finalizada',
                        $done->operational_status === ClosureOrderWorkflowService::STATUS_WAITING
                            ? "Ordem {$ordem} é meta futura: fica em Aguardando até o mês da meta."
                            : "Ordem {$ordem} finalizada; aguardando o encerramento no SAP.",
                    );

                    break;
            }
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível atualizar a Ordem', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    protected function clearSelection(): void
    {
        $this->selectedStateIds = [];
        $this->batchPreview     = [];
    }

    private function isManagerAction(): bool
    {
        return in_array($this->batchAction, ['assign', 'transfer', 'set_priority'], true);
    }

    private function validateBatchPayload(): bool
    {
        if ($this->isManagerAction() && !$this->isManager()) {
            abort(403);
        }

        $needsReason = in_array($this->batchAction, ['return_to_pool', 'transfer'], true)
            || ($this->batchAction === 'change_status' && in_array($this->batchStatus, ClosureOrderWorkflowService::REASON_REQUIRED_TARGETS, true));

        if ($needsReason && trim($this->batchReason) === '') {
            $this->toast('Motivo obrigatório', 'Informe o motivo antes de aplicar esta ação em lote.', 'warning');

            return false;
        }

        if ($this->batchAction === 'change_status' && $this->batchStatus === '') {
            $this->toast('Status obrigatório', 'Escolha o novo status para o lote.', 'warning');

            return false;
        }

        if ($this->batchAction === 'assign' && (!$this->managerCompanyId || !$this->managerAssigneeId)) {
            $this->toast('Destino obrigatório', 'Informe empresa e usuário de destino para atribuir.', 'warning');

            return false;
        }

        if ($this->batchAction === 'transfer' && !$this->managerCompanyId) {
            $this->toast('Destino obrigatório', 'Informe a empresa de destino para transferir.', 'warning');

            return false;
        }

        if ($this->managerAssigneeId && $this->managerCompanyId && !User::query()->whereKey($this->managerAssigneeId)->where('company_id', $this->managerCompanyId)->exists()) {
            $this->toast('Destino inválido', 'O usuário de destino não pertence à empresa informada.', 'warning');

            return false;
        }

        return true;
    }

    private function batchPayload(): array
    {
        $payload = ['reason' => trim($this->batchReason)];

        if ($this->isManagerAction()) {
            $payload['company_id']  = $this->managerCompanyId;
            $payload['assignee_id'] = $this->managerAssigneeId;
        }

        if ($this->batchAction === 'set_priority') {
            $payload['priority'] = $this->managerPriority === '1';
        }

        if ($this->batchAction === 'change_status') {
            $payload['status'] = $this->batchStatus;
        }

        return $payload;
    }
}
