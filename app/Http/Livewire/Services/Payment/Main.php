<?php

namespace App\Http\Livewire\Services\Payment;

use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\Services\BulkSelfAssignable;
use App\Jobs\Dispatchs\ExportDispatchPaymentJob;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User, WorkReport};
use App\Services\D5\D5WorkflowService;
use App\Services\Payment\{BlockEvaluator, NoteFilter};
use App\Services\WorkReports\{WorkReportFinalScopeOptions, WorkReportFlowProductionLinker};
use Carbon\Carbon;
use Illuminate\Support\Facades\{Auth, DB};
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use TextFormatter;
    use BulkSelfAssignable;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $advanceSearch;

    public $multiSearch = [];

    public $rubrica_s = [];

    public $rubrica_l;

    public $note;
    public ?int $paymentTargetWorkReportId = null;

    public $last_update;

    public $typeNote;

    public $partial = false;

    public $partials;

    public $partialDate;

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $filter_d5 = false;

    public $multi_search_any_situation = false;

    public bool $bulkSearchAnyStatus = false;

    public $assigned_mmgd = false;

    public $count = [
        'total'    => 0,
        'partials' => 0,
    ];

    // Filters
    private $filter_group = 'payments';

    protected $listeners = [
        'refresh_service'        => '$refresh',
        'refresh_list'           => '$refresh',
        'getCopy'                => 'copy',
        'confirm_accompany'      => 'add_to_accompany',
        'confirm_accompany_mass' => 'add_to_accompany_mass',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'partials' => ['except' => false, 'as' => 'parciais'],
    ];

    protected $noteFilter;

    private ?User $assignmentUser = null;

    private array $workReportScopeCache = [];

    public function boot(NoteFilter $noteFilter)
    {
        $this->noteFilter = $noteFilter;
    }

    private function rowSelectionKey(Note $note): string
    {
        $contextKey = $note->getAttribute('payment_context_key');
        if ($contextKey) {
            return (string) $contextKey;
        }

        $workReportId = (int) ($note->getAttribute('payment_work_report_id')
            ?: $note->WorkForm?->id);

        return $workReportId ? $note->id . ':' . $workReportId : (string) $note->id;
    }

    private function parseSelectionKey($key): array
    {
        if (is_string($key) && str_contains($key, ':')) {
            [$noteId, $workReportId] = array_pad(explode(':', $key, 2), 2, null);

            return [(int) $noteId, (int) $workReportId];
        }

        return [(int) $key, null];
    }

    private function selectionKeysForCurrentFilter(): array
    {
        $page = $this->baseQuery()->get();

        $page->load([
            'WorkForm:id,note_id,company_id,informed_at,created_at,rejected,selected_final_scopes,equipment,changes,damage,connection',
            'WorkForm.Note:id,type_note',
            'WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'WorkForm.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'WorkForms' => fn ($q) => $q->select(['id', 'note_id', 'company_id', 'informed_at', 'created_at', 'rejected', 'selected_final_scopes', 'equipment', 'changes', 'damage', 'connection'])
                ->where('canceled', false),
            'WorkForms.Note:id,type_note',
            'WorkForms.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'WorkForms.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'FiveNotes:id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,is_payed,completed_at',
        ]);

        return $this->expandPaymentRows($page)
            ->map(fn (Note $note) => $this->rowSelectionKey($note))
            ->values()
            ->all();
    }

    public function setSelectAllFiltered(): void
    {
        $keys = $this->selectionKeysForCurrentFilter();

        if ($this->selectAll) {
            foreach ($keys as $key) {
                if (!in_array($key, $this->selected, true)) {
                    $this->selected[] = $key;
                }
            }
        } else {
            $this->selected = array_values(array_diff($this->selected, $keys));
        }
    }

    public function checkAllSelect(): bool
    {
        $keys = $this->selectionKeysForCurrentFilter();

        if (!count($keys)) {
            $this->selectAll = false;

            return false;
        }

        $this->selectAll = empty(array_diff($keys, $this->selected));

        return $this->selectAll;
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = optional(Note::orderByDesc('dt_status')->first())->dt_status;

    }

    public function filterD5()
    {
        $this->filter_d5 = !$this->filter_d5;
        $this->gotoPage(1);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->gotoPage(1);
            $this->multiSearch = array_values($this->formatTextToArray($this->advanceSearch));
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch                = [];
            $this->multi_search_any_situation = false;
            $this->bulkSearchAnyStatus        = false;
        }
    }

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->selected    = [];
        $this->selectAll   = false;
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function updatedTypeNote()
    {
        $this->selected  = [];
        $this->selectAll = false;
        $this->gotoPage(1);
    }

    public function updatedMultiSearchAnySituation($value)
    {
        if ((bool) $value && !empty($this->advanceSearch)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Modo de risco ativado',
                'html'     => 'Você habilitou a busca em qualquer situação. Isso pode exibir notas fora do fluxo padrão e aumentar risco operacional. Use apenas com conferência manual.',
                'timer'    => 5000,
            ]);
        }
    }

    public function updatedBulkSearchAnyStatus($value)
    {
        $this->multi_search_any_situation = (bool) $value;
        $this->updatedMultiSearchAnySituation($value);
    }

    public function clean()
    {
        $this->advanceSearch              = '';
        $this->multiSearch                = [];
        $this->multi_search_any_situation = false;
        $this->bulkSearchAnyStatus        = false;
        $this->search                     = '';
        $this->gotoPage(1);
    }

    public function export_excel()
    {
        if (\PHP_SESSION_ACTIVE !== session_status() && !session()->isStarted()) {
            session()->start();
        }

        $filters = $_SESSION['filter'][$this->filter_group] ?? session('filter.' . $this->filter_group, []);

        ExportDispatchPaymentJob::dispatch([
            'source'                     => 'service',
            'service_uuid'               => $this->service->uuid,
            'search'                     => $this->search,
            'multiSearch'                => $this->multiSearch,
            'multi_search_any_situation' => (bool) $this->multi_search_any_situation,
            'bulkSearchAnyStatus'        => (bool) $this->bulkSearchAnyStatus,
            'selected_ids'               => [],
            'typeNote'                   => $this->typeNote,
            'not_assigned'               => $this->not_assigned,
            'company_ids'                => $filters['company'] ?? null,
            'rubricas'                   => $filters['rubrica'] ?? null,
            'regions'                    => $filters['region'] ?? null,
            'regionals'                  => $filters['regional'] ?? null,
            'cities'                     => $filters['city'] ?? null,
            'filter_d5'                  => (bool) $this->filter_d5,
        ], (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'info',
            'title'    => 'Estamos gerando seu relatório!',
            'html'     => 'Você será notificado quando o arquivo estiver pronto para download.',
            'timer'    => 3000,
        ]);
    }

    public function filterMMGD()
    {
        if ($this->assigned_mmgd) {
            $this->assigned_mmgd = false;
        } else {
            $this->assigned_mmgd = true;
        }
    }

    public function to_accompany($noteId, ?int $workReportId = null)
    {
        $this->paymentTargetWorkReportId = $workReportId ?: null;
        $note = Note::findOrFail($noteId);
        $this->note = $note->loadMissing([
            'WorkForm',
            'FiveNote',
            'FiveNotes',
            'Partials',
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                                        ->orderByDesc('created_at'),
        ]);
        $this->note->setAttribute('payment_target_work_report_id', $this->paymentTargetWorkReportId);

        // 1. Pegar a parcial mais recente
        $latestPartial = $note->partials?->sortByDesc('created_at')->first();

        // 2. Verificar se esta parcial atende aos critérios
        $this->partial     = false;
        $this->partialDate = null;

        if (!$this->note->WorkForm) {
            if ($latestPartial
            && $latestPartial->allow
            && $latestPartial->supervision
            && !$latestPartial->payment
            ) {
                $this->partial     = true;
                $this->partialDate = $latestPartial->created_at;
            }
        }

        $currentUser = auth()->user();
        $targetName  = $currentUser?->name ?? 'voce';

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Assumir tarefa',
            'msg'   => "
            Você deseja atribuir a NOTA/OV "
                . ($this->partial ? "(PARCIAL) " : "")
                . "para <strong>{$targetName}</strong>?</br></br>
            <div class='card card-light'>
              <div class='card-body'>
                <p>A NOTA/OV ficará disponível em acompanhamento como sua tarefa. Nesta lista de serviço, a atribuição é sempre para o usuário logado.</p>
              </div>
            </div>
        ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, assumir',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',
        ]);
    }

    public function add_to_accompany()
    {
        $workReportId = (int) ($this->paymentTargetWorkReportId ?: $this->note->getAttribute('payment_target_work_report_id'));
        $result = $this->assignNoteToSelf(
            $this->note,
            $this->finalScopesForWorkReportId($workReportId),
            $workReportId ?: null
        );

        if (!$result['ok']) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV JÁ ATRIBUÍDA',
                'html'     => "<strong>{$result['note']}</strong> {$result['reason']}",
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "{$result['note']} foi atribuído a você com sucesso.",
            'timer'    => 2500,
        ]);
    }

    public function go_att_mass(): void
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota selecionada',
                'timer'    => 2500,
            ]);

            return;
        }

        $count = count($this->selected);
        $targetName = auth()->user()?->name ?? 'voce';

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Assumir tarefas',
            'msg'   => "Você deseja assumir <strong>{$count}</strong> Nota(s)/OV(s) para <strong>{$targetName}</strong>?</br></br>
                <div class='card card-light'>
                  <div class='card-body'>
                    <p>As Notas/OVs elegíveis ficarão disponíveis em acompanhamento como suas tarefas. Nesta lista de serviço, a atribuição é sempre para o usuário logado.</p>
                  </div>
                </div>",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, assumir',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany_mass',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',
        ]);
    }

    public function add_to_accompany_mass(): void
    {
        $success = 0;
        $errors = [];
        $selectedRows = [];

        foreach ($this->selected as $selectedKey) {
            [$noteId, $workReportId] = $this->parseSelectionKey($selectedKey);
            $selectedRows[] = [
                'note_id' => $noteId,
                'work_report_id' => $workReportId ?: null,
            ];
        }

        $notes = Note::query()
            ->whereIn('id', collect($selectedRows)->pluck('note_id')->unique()->all())
            ->with($this->assignmentNoteRelations())
            ->get()
            ->keyBy('id');
        $this->primeWorkReportScopeCache(collect($selectedRows)->pluck('work_report_id')->filter()->unique()->all());

        foreach ($selectedRows as $selectedRow) {
            $workReportId = $selectedRow['work_report_id'];
            $note = $notes->get($selectedRow['note_id']);

            if (!$note) {
                continue;
            }

            $result = $this->assignNoteToSelf(
                $note,
                $this->finalScopesForWorkReportId($workReportId),
                $workReportId
            );

            if ($result['ok']) {
                $success++;
            } else {
                $errors[] = "{$result['note']}: {$result['reason']}";
            }
        }

        $this->selected = [];
        $this->selectAll = false;

        if (count($errors)) {
            $msg = implode('<br>', array_slice($errors, 0, 10));
            $msg .= count($errors) > 10 ? '<br>...' : '';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => $success ? 'warning' : 'error',
                'title' => "Atribuição concluída ({$success} sucesso, " . count($errors) . ' falha)',
                'html' => $msg,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => "Atribuição em massa concluída ({$success})",
            'timer' => 2500,
        ]);
    }

    /**
     * Executa a atribuição de fato (individual ou dentro do laço de atribuição em massa).
     * Autocontido: recalcula parcial/dt a partir da própria Nota, sem depender de estado
     * de instância — assim serve tanto para o fluxo de 1 item quanto para o de N itens.
     */
    public function assignNoteToSelf(Note $note, ?array $targetFinalScopes = null, ?int $targetWorkReportId = null): array
    {
        $user = $this->assignmentUser();

        if (!$user) {
            return ['ok' => false, 'note' => $note->note, 'reason' => 'usuario autenticado nao encontrado'];
        }

        if ($targetWorkReportId) {
            $note->setAttribute('dispatch_work_report_id', $targetWorkReportId);
        }

        $note->loadMissing($this->assignmentNoteRelations());

        $targetWorkReport = $targetWorkReportId
            ? $this->workReportForPaymentAssignment($note, $targetWorkReportId)
            : null;
        $workFormForValidation = $targetWorkReport ?: $note->WorkForm;

        $targetFiveNote = $targetWorkReportId
            ? $note->FiveNotes->firstWhere("work_report_id", $targetWorkReportId)
            : $note->FiveNote;
        $d5ReturnReady = (bool) ($targetFiveNote?->is_completed && $targetFiveNote?->is_supervisioned && !$targetFiveNote?->is_archived);

        if ($workFormForValidation && !$d5ReturnReady && !$this->workReportEligibleForPayment($workFormForValidation)) {
            return [
                'ok' => false,
                'note' => $note->note,
                'reason' => 'status das operacoes ainda nao permite assumir para medicao',
            ];
        }

        $latestPartial = $note->Partials?->sortByDesc('created_at')->first();
        $isPartial     = false;
        $partialDate   = null;

        if (!$note->WorkForm) {
            if ($latestPartial
                && $latestPartial->allow
                && $latestPartial->supervision
                && !$latestPartial->payment
            ) {
                $isPartial   = true;
                $partialDate = $latestPartial->created_at;
            }
        }

        $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);
        // Em uma atribuicao por informe, somente o vinculo ativo daquele informe bloqueia.
        if ($targetWorkReportId) {
            $scopedProduction = Production::query()
                ->where("note_id", $note->id)
                ->where("service_id", $this->service->uuid)
                ->whereHas("WorkReportFlowProductions", function ($query) use ($targetWorkReportId) {
                    $query->where("work_report_id", $targetWorkReportId)
                        ->where("stage", \App\Models\WorkReportFlowProduction::STAGE_PAYMENT)
                        ->where("is_current", true);
                })
                ->latest("id")
                ->first();

            $eval["command"] = !$scopedProduction;
            if ($scopedProduction) {
                $eval["production"] = $scopedProduction;
                $eval["reason"] = "prod_open_for_work_report";
            }
        }

        if (!$eval['command']) {
            $when = $eval['production']?->dt_note ? Carbon::parse($eval['production']->dt_note)->format('d/m/Y H:i') : '---';

            return [
                'ok'     => false,
                'note'   => $note->note,
                'reason' => "já foi atribuída em {$when} — {$eval['reason']}",
            ];
        }

        $finalScopes = $targetFinalScopes
            ?? collect(app(WorkReportFinalScopeOptions::class)->forNote($note))->pluck('scope')->all();

        if (count($finalScopes) > 1 && $targetFinalScopes === null) {
            return [
                'ok'     => false,
                'note'   => $note->note,
                'reason' => 'possui mais de um informe final aberto — use a tela de despacho para selecionar Rede, Ligação ou ambos',
            ];
        }

        $dt       = $isPartial ? $partialDate : $note->dt_status;
        $fiveNote = (bool) $targetFiveNote;
        $data = [
            'note_id'     => $note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $dt,
            'status_note' => $note->nstats,
            'dispatch_at' => now(),
            'att_at'      => now(),
            'status'      => 2,
            'dhstats'     => $dt,
            'partial'     => $isPartial,
            'dfive'       => $fiveNote,
        ];

        $production = null;

        if ($targetWorkReportId) {
            $production = Production::query()
                ->where("note_id", $note->id)
                ->where("service_id", $this->service->uuid)
                ->whereHas("WorkReportFlowProductions", function ($query) use ($targetWorkReportId) {
                    $query->where("work_report_id", $targetWorkReportId)
                        ->where("stage", \App\Models\WorkReportFlowProduction::STAGE_PAYMENT)
                        ->where("is_current", true);
                })
                ->latest("id")
                ->first();
        }

        $production ??= $targetWorkReportId
            ? Production::create($data)
            : Production::firstOrCreate([
                "note_id" => $note->id,
                "service_id" => $this->service->uuid,
                "user_id" => $user->id,
                "completed" => false,
            ], $data);

        if (!$production) {
            return ['ok' => false, 'note' => $note->note, 'reason' => 'erro ao tentar atribuir'];
        }

        // O fallback legado (D5 diretamente na nota) também deve permanecer identificado como D5,
        // inclusive quando a produção já existia antes desta regra.
        if ($targetFiveNote && !(bool) $production->dfive) {
            $production->forceFill(['dfive' => true])->save();
        }

        $linker = app(WorkReportFlowProductionLinker::class);

        if ($targetWorkReportId) {
            $linker->linkPaymentForWorkReport($production, $targetWorkReportId, $finalScopes, 'services_payment_self_assign');
        } else {
            $linker->linkPaymentForScopes(
                $production,
                $finalScopes,
                'services_payment_self_assign'
            );
        }
        if ($targetFiveNote) {
            $targetFiveNote->productions()->syncWithoutDetaching([$production->id]);

            app(D5WorkflowService::class)->onProductionAssigned(
                $targetFiveNote,
                $production,
                auth()->id(),
                null
            );
        }

        return ['ok' => true, 'note' => $note->note, 'reason' => null];
    }

    private function assignmentUser(): ?User
    {
        if ($this->assignmentUser && (string) $this->assignmentUser->id === (string) Auth::id()) {
            return $this->assignmentUser;
        }

        return $this->assignmentUser = User::with('Employee.Contract')->find(Auth::id());
    }

    private function assignmentNoteRelations(): array
    {
        return [
            'WorkForm',
            'WorkForm.Orders.Operations',
            'WorkForms.Orders.Operations',
            'FiveNote',
            'FiveNotes',
            'Partials',
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                ->with('WorkReportFlowProductions:id,production_id,work_report_id,stage,is_current')
                ->orderByDesc('created_at'),
        ];
    }

    private function workReportForPaymentAssignment(Note $note, int $workReportId): ?WorkReport
    {
        if ($note->relationLoaded('WorkForms')) {
            return $note->WorkForms->firstWhere('id', $workReportId);
        }

        return WorkReport::query()
            ->with('Orders.Operations')
            ->where('note_id', $note->id)
            ->where('id', $workReportId)
            ->where('canceled', false)
            ->first();
    }

    /**
     * Verifica se a nota tem produção associada, considerando regras específicas
     *
     * @param Note $note
     * @return Production|bool|null
     */
    public function hasProduction(Note $note)
    {
        $production = $note->Productions->where('service_id', $this->service->uuid)->last();

        if ($production) {
            return $production;
        } else {
            return false;
        }
    }

    public function needBlock(Note $note): array
    {
        $workForms = $note->relationLoaded('WorkForms')
            ? $note->WorkForms
            : collect([$note->WorkForm])->filter();
        $workForm = $workForms->first();

        if ($workForm && !$this->isD5ReturnReadyForPayment($note) && !$this->workReportEligibleForPayment($workForm)) {
            return [
                'block' => BlockEvaluator::HOLD_RED,
                'command' => false,
                'color' => 'table-danger',
                'reason' => 'operations_not_ready_for_payment',
                'production' => $note->Productions?->first(),
            ];
        }

        $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);

        // retorna estrutura pra view usar diretamente
        return $eval;
    }

    public function filterStatus()
    {
        if ($this->not_assigned) {
            $this->not_assigned = false;
        } else {
            $this->not_assigned = true;
        }

        $this->gotoPage(1);
    }

    private function baseQuery()
    {
        $useAnySituationFromMassSearch = ($this->bulkSearchAnyStatus || $this->multi_search_any_situation)
            && !empty($this->multiSearch);

        $base = ($useAnySituationFromMassSearch
            ? Note::query()
            : $this->noteFilter->filter(null, $this->filter_group))
            ->select([
                'notes.id',
                'notes.note',
                'notes.lexp',
                'notes.mesalization',
                'notes.days_left',
                'notes.type_note',
                'notes.nstats',
                'notes.dt_status',
                DB::raw('(SELECT COALESCE(SUM(o.moaberto),0) FROM orders o WHERE o.note_id = notes.id) AS total_moaberto'),
            ]);

        // latest_ops (MAX fimLancado)
        $latestOps = DB::table('operation_resps')
            ->select('note_id', DB::raw('MAX(fimLancado) AS latest_fimLancado'))
            ->groupBy('note_id');

        // latest_partials (ROW_NUMBER)
        $latestPartialBase = DB::table('partials as p')
            ->selectRaw("
            p.note_id,
            p.supervision_at,
            ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.id DESC) AS rn
        ")
            ->where('p.allow', 1)
            ->where('p.deny', 0)
            ->where('p.supervision', 1);

        $latestPartials = DB::query()
            ->fromSub($latestPartialBase, 't')
            ->select('t.note_id', 't.supervision_at')
            ->where('t.rn', 1);

        // latest production por serviço (ROW_NUMBER)
        $latestProdBase = DB::table('productions as p')
            ->selectRaw("
            p.note_id,
            p.id            AS latest_prod_id,
            p.user_id       AS latest_user_id,
            p.completed     AS latest_completed,
            p.status        AS latest_status,
            p.partial       AS latest_partial,
            p.confirmed     AS latest_confirmed,
            p.dfive         AS latest_dfive,
            p.created_at    AS latest_created_at,
            p.completed_at  AS latest_completed_at,
            p.dhstats       AS latest_dhstats,
            p.dt_note       AS latest_dt_note,
            p.status_note   AS latest_status_note,
            ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.created_at DESC, p.id DESC) AS rn
        ")
            ->where('p.service_id', $this->service->uuid);

        $latestProd = DB::query()
            ->fromSub($latestProdBase, 'u')
            ->select([
                'u.note_id',
                'u.latest_prod_id',
                'u.latest_user_id',
                'u.latest_completed',
                'u.latest_status',
                'u.latest_partial',
                'u.latest_confirmed',
                'u.latest_dfive',
                'u.latest_created_at',
                'u.latest_completed_at',
                'u.latest_dhstats',
                'u.latest_dt_note',
                'u.latest_status_note',
            ])
            ->where('u.rn', 1);

        // JOINs
        $base->leftJoinSub($latestOps, 'latest_ops', fn ($j) => $j->on('notes.id', '=', 'latest_ops.note_id'));
        $base->leftJoinSub($latestPartials, 'latest_partials', fn ($j) => $j->on('notes.id', '=', 'latest_partials.note_id'));
        $base->leftJoinSub($latestProd, 'lp', fn ($j) => $j->on('notes.id', '=', 'lp.note_id'));

        // fimLancado (WorkForm => latest_ops; senão => partial supervision_at)
        $base->addSelect(DB::raw("
        CASE
          WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
            THEN latest_ops.latest_fimLancado
          ELSE latest_partials.supervision_at
        END AS fimLancado
    "));

        // ===== BUCKET de ordenação =====
        // 0 = PARCIAL válida (sem WorkForm) -> vem primeiro
        // 1 = FiveNote pendente para Medicao ou liberacao final
        // 2 = FINAL (com WorkForm)
        // 3 = Demais
        $base->addSelect(DB::raw("
            CASE
            -- 0: PARCIAL válida (sem WorkForm)
            WHEN latest_partials.supervision_at IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                THEN 0

            -- 1: FiveNote pendente para criacao/despacho pela Medicao, ou ja fiscalizada para liberacao final
            WHEN EXISTS (
                SELECT 1 FROM five_notes as fn
                    WHERE fn.note_id = notes.id
                    AND COALESCE(fn.is_archived, 0) = 0
                    AND (
                        (
                            COALESCE(fn.is_supervisioned, 0) = 0
                            AND COALESCE(fn.visible_partner, 0) = 0
                            AND COALESCE(fn.is_payed, 0) = 0
                        )
                        OR (
                            COALESCE(fn.is_supervisioned, 0) = 1
                            AND COALESCE(fn.is_completed, 0) = 1
                        )
                    )
            )
                THEN 1

            -- 2: FINAL (tem WorkForm)
            WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                THEN 2

            -- 3: demais
            ELSE 3
            END AS sort_bucket
        "));

        // ----- Filtros da tela (mantém como já estava) -----
        if ($this->not_assigned && isset($this->service)) {
            $base->where(function ($q) {
                $q->whereNull('lp.latest_prod_id')
                  ->orWhereNull('lp.latest_user_id')
                  ->orWhere('lp.latest_user_id', 0);
            });
        }

        if (!empty($this->multiSearch)) {
            $ms = $this->multiSearch;
            $base->where(function ($q) use ($ms) {
                $q->whereIn('notes.note', $ms)
                  ->orWhereExists(function ($sq) use ($ms) {
                      $sq->select(DB::raw(1))
                         ->from('orders')
                         ->whereColumn('orders.note_id', 'notes.id')
                         ->whereIn('orders.ordem', $ms);
                  });
            });
        } elseif (!empty($this->search)) {
            $s = '%' . $this->search . '%';
            $base->where(function ($q) use ($s) {
                $q->where('notes.note', 'like', $s)
                  ->orWhereExists(function ($sq) use ($s) {
                      $sq->select(DB::raw(1))
                         ->from('orders')
                         ->whereColumn('orders.note_id', 'notes.id')
                         ->where('orders.ordem', 'like', $s);
                  });
            });
        }

        $base->when($this->typeNote, fn ($q) => $q->where('notes.type_note', $this->typeNote));
        $base->when($this->filter_d5, fn ($q) => $q->whereExists(function ($sq) {
            $sq->select(DB::raw(1))
               ->from('five_notes as fn')
               ->whereColumn('fn.note_id', 'notes.id');
        }));

        // ===== ORDEM FINAL =====
        // 1) parciais (0) → 2) finais (1) → 3) five (2) → 4) demais (3)
        // dentro de cada bucket, manter tua lógica: nulos por último e data crescente
        $base->orderBy('sort_bucket', 'ASC')
             ->orderByRaw('(fimLancado IS NULL) DESC')
             ->orderBy('fimLancado', 'ASC')
             ->orderBy('notes.id', 'ASC');

        return $base;
    }

    public function getListsProperty()
    {
        $page = $this->baseQuery()->paginate($this->perPage);

        $page->load([
            'WorkForm' => fn ($q) => $q->select([
                'id',
                'note_id',
                'company_id',
                'informed_at',
                'created_at',
                'rejected',
                'selected_final_scopes',
                'equipment',
                'changes',
                'damage',
                'connection',
            ]),
            'WorkForm.Note:id,type_note',
            'WorkForm.Company:id,name,deleted_at',
            'WorkForm.Orders'            => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'WorkForm.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'WorkForm.Adsform:id,work_report_id,created_at',
            'WorkForms' => fn ($q) => $q->select([
                'id',
                'note_id',
                'company_id',
                'informed_at',
                'created_at',
                'rejected',
                'selected_final_scopes',
                'equipment',
                'changes',
                'damage',
                'connection',
            ])->where('canceled', false),
            'WorkForms.Note:id,type_note',
            'WorkForms.Company:id,name,deleted_at',
            'WorkForms.Orders'            => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'WorkForms.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'FiveNotes:id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,is_payed,completed_at',
            'WorkForms.Adsform:id,work_report_id,created_at',
            'WorkForms.FiveNote:id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,is_payed,completed_at',
            'Partials' => fn ($q) => $q->select([
                'id',
                'note_id',
                'company_id',
                'allow',
                'deny',
                'payment',
                'supervision',
                'supervision_at',
                'created_at',
                'value',
            ])
                ->where('allow', true)
                ->where('deny', false)
                ->where('supervision', true)
                ->where('payment', false)
                ->orderByDesc('created_at'),
            'Partials.Company:id,name,deleted_at',
            'Partials.Orders'            => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'Partials.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'FiveNote:id,note_id,is_supervisioned,is_completed,is_archived,completed_at',
            'FiveNotes:id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,is_payed,completed_at',
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                ->select([
                    'id',
                    'note_id',
                    'service_id',
                    'user_id',
                    'company_id',
                    'completed',
                    'confirmed',
                    'status',
                    'partial',
                    'dfive',
                    'created_at',
                    'completed_at',
                    'dt_note',
                    'status_note',
                ])
                ->with('User')
                ->orderByDesc('created_at'),
        ]);

        $page->setCollection($this->expandPaymentRows($page->getCollection()));

        return $page;
    }

    private function expandPaymentRows($notes)
    {
        return $notes->flatMap(function (Note $note) {
            $workForms = $note->relationLoaded('WorkForms')
                ? $note->WorkForms
                : collect([$note->WorkForm])->filter();

            $eligibleWorkForms = $workForms
                ->filter(fn ($workForm) => $this->workReportEligibleForPayment($workForm))
                ->values();

            // Cada D5 associado a um informe deve aparecer de forma independente.
            // A relacao Note->FiveNote e legada e so representa D5 sem informe.
            $d5ByWorkReport = ($note->FiveNotes ?? collect())
                ->filter(fn ($five) => !$five->is_archived && !$five->is_payed && $five->work_report_id)
                ->keyBy(fn ($five) => (int) $five->work_report_id);

            $d5WorkForms = $workForms
                ->filter(function ($workForm) use ($d5ByWorkReport) {
                    $five = $d5ByWorkReport->get((int) $workForm->id);

                    return $five && (
                        ($five->is_supervisioned && $five->is_completed)
                        || $this->workReportEligibleForPayment($workForm)
                    );
                })
                ->values();

            if ($d5WorkForms->isNotEmpty()) {
                return $d5WorkForms->map(function ($workForm) use ($note, $d5ByWorkReport) {
                    $row = clone $note;
                    $five = $d5ByWorkReport->get((int) $workForm->id);
                    $row->setRelation('FiveNote', $five);
                    $row->setRelation('WorkForm', $workForm);
                    $row->setRelation('WorkForms', collect([$workForm]));
                    $row->setAttribute('payment_context_key', $note->id . ':' . $workForm->id);
                    $row->setAttribute('payment_work_report_id', (int) $workForm->id);
                    $row->setAttribute('dispatch_work_report_id', (int) $workForm->id);

                    return $row;
                });
            }

            if ($this->d5MustUsePrimaryNetworkReport($note)) {
                $eligibleWorkForms = $eligibleWorkForms
                    ->filter(fn ($workForm) => $this->isNetworkWorkReport($workForm))
                    ->values();
            }

            if ($eligibleWorkForms->isEmpty()) {
                if ($workForms->isNotEmpty() && !$this->isD5ReturnReadyForPayment($note)) {
                    return [];
                }

                $note->setAttribute('payment_context_key', (string) $note->id);

                return [$note];
            }

            return $eligibleWorkForms->map(function ($workForm) use ($note) {
                $row = clone $note;
                $row->setRelation('WorkForm', $workForm);
                $row->setRelation('WorkForms', collect([$workForm]));
                $row->setAttribute('payment_context_key', $note->id . ':' . $workForm->id);
                $row->setAttribute('payment_work_report_id', (int) $workForm->id);
                $row->setAttribute('dispatch_work_report_id', (int) $workForm->id);

                return $row;
            });
        })->values();
    }

    private function workReportEligibleForPayment($workForm): bool
    {
        if (!$workForm) {
            return false;
        }

        $orders = $workForm->relationLoaded('Orders')
            ? $workForm->Orders
            : $workForm->Orders()->with('Operations')->get();

        $normalizedStatus = fn ($status) => strtoupper(strtok((string) $status, ' ') ?: (string) $status);

        return $orders->contains(function ($order) use ($normalizedStatus) {
            if (!str_starts_with($normalizedStatus($order->statusSist ?? ''), 'LIB')) {
                return false;
            }

            $statuses = function (string $operation) use ($order, $normalizedStatus) {
                return collect($order->Operations ?? [])
                    ->where('operacao', $operation)
                    ->pluck('status')
                    ->map($normalizedStatus);
            };

            return $statuses('0010')->contains(fn ($status) => str_starts_with($status, 'CONF'))
                && $statuses('0030')->contains(fn ($status) => str_starts_with($status, 'CONF'))
                && $statuses('0040')->contains(fn ($status) => str_starts_with($status, 'LIB') || str_starts_with($status, 'CONF') || str_starts_with($status, 'CNPA'))
                && $statuses('0050')->contains(fn ($status) => str_starts_with($status, 'LIB') || str_starts_with($status, 'CNPA') || str_starts_with($status, 'JBFI'));
        });
    }

    private function d5MustUsePrimaryNetworkReport(Note $note): bool
    {
        $five = $note->FiveNote;

        if (!$five || (bool) $five->is_archived) {
            return false;
        }

        return (!$five->is_completed && !$five->visible_partner && !$five->is_payed)
            || ($five->is_completed && !$five->is_supervisioned);
    }

    private function isD5ReturnReadyForPayment(Note $note): bool
    {
        $five = $note->FiveNote;

        return (bool) (
            $five
            && !$five->is_archived
            && $five->is_completed
            && $five->is_supervisioned
        );
    }

    private function isNetworkWorkReport($workForm): bool
    {
        $scopes = (array) ($workForm->selected_final_scopes ?? []);

        if (!empty($scopes)) {
            return in_array('network', $scopes, true);
        }

        return (bool) ($workForm->equipment ?? false)
            || (bool) ($workForm->changes ?? false)
            || (bool) ($workForm->damage ?? false)
            || !(bool) ($workForm->connection ?? false);
    }

    private function finalScopesForWorkReportId(?int $workReportId): ?array
    {
        if (!$workReportId) {
            return null;
        }

        if (array_key_exists($workReportId, $this->workReportScopeCache)) {
            return $this->workReportScopeCache[$workReportId];
        }

        $workReport = WorkReport::with(['Note:id,type_note', 'Orders:id,ordem'])
            ->where('id', $workReportId)
            ->where('canceled', false)
            ->first();

        if (!$workReport) {
            return null;
        }

        return $this->workReportScopeCache[$workReportId] = collect($workReport->finalScopePayloads())
            ->pluck('scope')
            ->unique()
            ->values()
            ->all();
    }

    private function primeWorkReportScopeCache(array $workReportIds): void
    {
        if (empty($workReportIds)) {
            return;
        }

        WorkReport::query()
            ->with(['Note:id,type_note', 'Orders:id,ordem'])
            ->whereIn('id', $workReportIds)
            ->where('canceled', false)
            ->get()
            ->each(function (WorkReport $workReport) {
                $this->workReportScopeCache[(int) $workReport->id] = collect($workReport->finalScopePayloads())
                    ->pluck('scope')
                    ->unique()
                    ->values()
                    ->all();
            });
    }

    // Rules Days Left
    public function deadline(Note $note)
    {
        $days       = 10;
        $date_forms = $note->WorkForm ? $note->WorkForm->informed_at : null;

        if ($date_forms) {

            $deadline_date = Carbon::parse($date_forms)->addDays($days);

            return Carbon::now()->diffInDays($deadline_date, false);
        } else {
            return 0;
        }
    }

    public function render()
    {
        $lists = $this->lists;

        return view('livewire.services.payment.main', [
            'lists'  => $lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
