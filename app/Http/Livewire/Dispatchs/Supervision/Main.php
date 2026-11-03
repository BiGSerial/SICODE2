<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Helpers\TextFormatter;
use App\Http\Livewire\Dispatchs\Supervision\Concerns\HasSupervisionList;
use App\Jobs\ExportSupervisionList;
use App\Models\{Company, Note, Production, Service, User, WorkReport, Wpa};
use App\Services\Dispatch\{DispatchException, DispatchWorkflowService};
use App\Services\Supervision\{BlockEvaluator, SupervisionDispatchListQuery, SupervisionListRowPresenter};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use HasSupervisionList;
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $search_user;

    public $rubrica_s = [];

    public $rubrica_l;

    public $note;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectAll;

    public $selected = [];

    public $company_l;

    public $company_s;

    public $user_l;

    public $user_s;

    public $type;

    public $additionalData = [];

    public $additionalDataUpd = [];

    public $typeNote = '';

    public $notes;

    public $filteredLists;

    public $note_type = '';

    public $branco = false;

    //Variáveis para DDs
    public $enter_dd;

    public $existDD;

    public $key;

    public $municipio_edit;

    //Botão de exibição de nao atribuído
    public $not_assigned = false;

    public $filter_d5 = false;

    public bool $bulkSearchAnyStatus = false;

    /** @var array<string, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

    /** @var array<string, \Illuminate\Support\Collection> Memo de `rowScopedProductions()` por linha (zerado a cada render). */
    private array $scopedProductionsMemo = [];

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
        'confirm_mass_dd'   => 'confirmed_mass_dd',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'typeNote'     => ['except' => '', 'as' => 'tipo'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filter_d5'    => ['except' => false, 'as' => 'd5'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    public function view_edit($key)
    {
        $this->key = $key;
    }

    public function hide_edit()
    {
        $this->key = "";
    }

    public function updatedSearch()
    {
        $this->gotoPage(1);
        $this->multiSearch = [];
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function updatedTypeNote()
    {
        $this->gotoPage(1);
    }

    public function municipio_update(Note $note)
    {
        if (trim($this->municipio_edit)) {
            if ($note->update(['lexp' => mb_strtoupper(trim($this->municipio_edit))])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Informação Alterada',
                    'timer'    => 2500,
                ]);

                $this->municipio_edit = "";
                $this->hide_edit();
                $this->emit('refresh_dispatch');
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma informação inserida.',
                'timer'    => 2500,
            ]);
            $this->municipio_edit = "";
            $this->hide_edit();
            $this->emit('refresh_dispatch');
        }
    }

    public function mount($service)
    {

        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = $this->lastSapUpdate();
    }

    public function export_excel()
    {
        ExportSupervisionList::dispatch([
            'search'                   => $this->search,
            'multiSearch'              => $this->multiSearch,
            'rubrica_s'                => $this->rubrica_s,
            'typeNote'                 => $this->typeNote,
            'not_assigned'             => $this->not_assigned,
            'filter'                   => $this->listQueryFilter(),
            'serviceUuid'              => $this->service->uuid,
            'user_id'                  => auth()->user()->id,
            'filterD5'                 => $this->filter_d5,
            'bulkSearchAnyStatus'      => (bool) $this->bulkSearchAnyStatus,
            'selected_work_report_ids' => collect($this->selected)
                ->map(fn ($key) => $this->parseWorkReportSelectionKey($key))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'selected_partial_ids' => collect($this->selected)
                ->map(fn ($key) => $this->parsePartialSelectionKey($key)['partial_id'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'selected_five_note_ids' => collect($this->selected)
                ->map(fn ($key) => $this->parsePassiveD5SelectionKey($key)['five_note_id'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Seu relatório está sendo gerado. Você será avisado quando estiver pronto!',
            'timer'    => 3000,
        ]);

    }

    public function hasPublication(Note $note)
    {
        $production = $this->rowScopedProductions($note)->last();

        if ($production) {
            return $production;
        } else {
            return false;
        }
    }

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição
     * (a tela, o "selecionar todos" e a verificação do mestre liam isso mais de uma vez por linha).
     *
     * @return array{eval: array, stack: ?Production, canDispatch: bool, key: string, count: int}
     */
    public function rowDispatchState(Note $note): array
    {
        $key = $this->selectionKeyFor($note);

        return $this->dispatchStateMemo[$key] ??= (function () use ($note, $key) {
            $eval  = app(BlockEvaluator::class)->evaluate($note, $this->service);
            $stack = $this->rowOpenCompanyStackProduction($note);

            return [
                'eval'              => $eval,
                'stack'             => $stack,
                'canDispatch'       => !$eval['block'] || (bool) $eval['command'] || (bool) $stack,
                'key'               => $key,
                'count'             => $this->hasPublicationCount($note),
                'partnerProduction' => null,
            ];
        })();
    }

    public function rowOpenCompanyStackProduction(Note $note): ?Production
    {
        if (!Auth()->User()?->usesContractDispatch()) {
            return null;
        }

        $companyIds = $this->visibleCompanyIds();

        return $this->rowScopedProductions($note)->first(
            fn (Production $production) => in_array((int) $production->company_id, $companyIds, true)
            && is_null($production->user_id)
            && !$production->completed
            && !$production->confirmed
        );
    }

    public function hasPublicationCount(Note $note)
    {
        return $this->rowScopedProductions($note)->count();
    }

    private function rowScopedProductions(Note $note)
    {
        $memoKey = $this->selectionKeyFor($note);

        return $this->scopedProductionsMemo[$memoKey] ??= $this->computeRowScopedProductions($note);
    }

    private function computeRowScopedProductions(Note $note)
    {
        $productions  = $note->Productions->where('service_id', $this->service->uuid);
        $workReportId = (int) ($note->operational_work_report_id ?? 0);
        $fiveNoteId   = (int) ($note->dispatch_five_note_id ?? 0);
        $partialId    = (int) ($note->dispatch_partial_id ?? 0);

        if ($workReportId <= 0) {
            if ($partialId > 0) {
                $linked = $productions->filter(fn (Production $production) => $production->partialInforms->contains("id", $partialId));

                if ($linked->isNotEmpty()) {
                    return $linked;
                }

                $eligiblePartials = $note->Partials
                    ->where('allow', true)
                    ->where('deny', false)
                    ->where('supervision', false)
                    ->where('payment', false);

                if ($eligiblePartials->count() === 1 && (int) $eligiblePartials->first()->id === $partialId) {
                    $legacyOpen = $productions->filter(
                        fn (Production $production) => (bool) $production->partial
                        && !$production->completed
                        && !$production->confirmed
                        && $production->partialInforms->isEmpty()
                    );

                    if ($legacyOpen->count() === 1) {
                        return $legacyOpen;
                    }
                }

                return collect();
            }

            if ($fiveNoteId > 0) {
                $linked        = $productions->filter(fn (Production $production) => $production->fiveNotes->contains("id", $fiveNoteId));
                $legacyD5Count = $note->relationLoaded("FiveNotes")
                    ? $note->FiveNotes->whereNull("work_report_id")->where("is_completed", true)->where("is_supervisioned", false)->where("is_archived", false)->count()
                    : 0;
                $unlinkedD5Productions = $productions->filter(
                    fn (Production $production) => $production->dfive
                    && !$production->completed
                    && !$production->confirmed
                    && $production->fiveNotes->isEmpty()
                    && $production->WorkReportFlowProductions
                        ->where("stage", \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION)
                        ->where("is_current", true)->isNotEmpty()
                );

                if ($legacyD5Count === 1 && $unlinkedD5Productions->count() === 1) {
                    return $linked->concat($unlinkedD5Productions);
                }

                return $linked;
            }

            return $productions;
        }

        $scoped = $productions->filter(function (Production $production) use ($workReportId) {
            return $production->WorkReportFlowProductions
                ->where('work_report_id', $workReportId)
                ->where('stage', \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION)
                ->where('is_current', true)
                ->isNotEmpty();
        });

        return $scoped->filter(function (Production $production) use ($workReportId) {
            $latestLink = $production->WorkReportFlowProductions
                ->where('stage', \App\Models\WorkReportFlowProduction::STAGE_FISCALIZATION)
                ->where('is_current', true)
                ->sortByDesc(fn ($link) => ($link->linked_at?->format('YmdHis') ?? '00000000000000') . str_pad((string) $link->id, 10, '0', STR_PAD_LEFT))
                ->first();

            return (int) ($latestLink?->work_report_id ?? 0) === $workReportId;
        });
    }

    public function updatedCompanyS()
    {
        $this->user_s = '';
        $this->loadDispatchUsers();
    }

    public function dispatchCompanyChanged($companyId): void
    {
        $this->company_s = $companyId;
        $this->updatedCompanyS();
    }

    public function loadDispatchCompanies(): void
    {
        if (Auth()->User()?->usesContractDispatch() && $this->notes && $this->notes->count()) {
            $companyIds = $this->notes
                ->map(fn ($note) => $this->rowOpenCompanyStackProduction($note)?->company_id)
                ->filter()
                ->unique()
                ->values();

            $this->company_l = Company::whereIn('id', $companyIds)
                ->orderBy('name', 'ASC')
                ->get();

            return;
        }

        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->when(Auth()->User()?->usesContractDispatch(), function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(Auth()->User());

                return count($companyIds)
                    ? $q->whereIn('id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function loadDispatchUsers(): void
    {
        $this->user_s = '';

        if (!$this->company_s) {
            $this->user_l = collect();

            return;
        }

        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
            ->where(function ($q) {
                $q->where('company_id', $this->company_s)
                    ->orWhere('company_id', $this->company_s)
                    ->orWhereRelation('Companies', 'companies.id', $this->company_s);
            })
            ->when($this->search_user, function ($q) {
                return $q->where('name', 'like', '%' . $this->search_user . '%');
            })
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->get();
    }

    private function preselectContractDispatchCompany(): void
    {
        if (!Auth()->User()?->usesContractDispatch() || !$this->notes || !$this->notes->count()) {
            return;
        }

        $companyIds = $this->notes
            ->map(fn ($note) => $this->rowOpenCompanyStackProduction($note)?->company_id)
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($companyIds->count() === 1) {
            $this->company_s = $companyIds->first();
        }
    }

    public function setSelectAll()
    {

        if ($this->selectAll) {

            // Adicionar os IDs que cumprem as regras à lista de selecionados
            foreach ($this->toLists as $item) {
                $id = $this->selectionKeyFor($item);

                if (!in_array($id, $this->selected)) {
                    if (Auth()->User()?->usesContractDispatch()) {
                        if ($this->rowOpenCompanyStackProduction($item)) {
                            $this->selected[] = $id;
                        }

                        continue;
                    }

                    $production = $this->rowScopedProductions($item)
                        ->where('completed', false)
                        ->where('confirmed', false)
                        ->isNotEmpty();

                    if (!$production) {
                        $this->selected[] = $id;
                    }
                }
            }
        } else {
            // Remover os IDs de $selected que estão presentes em $this->lists
            $visibleIds     = $this->toLists->map(fn ($item) => $this->selectionKeyFor($item))->all();
            $this->selected = array_filter($this->selected, function ($id) use ($visibleIds) {
                return !in_array($id, $visibleIds);
            });
        }
    }

    public function checkAllSelect($items)
    {

        $items = $items->map(fn ($item) => $this->selectionKeyFor($item))->all();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function get_single_note($note)
    {
        $this->selected = [$note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {

        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $selectedKeys  = collect($this->selected);
        $workReportIds = $selectedKeys
            ->map(fn ($key) => $this->parseWorkReportSelectionKey($key))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $partialContexts = $selectedKeys
            ->map(fn ($key) => $this->parsePartialSelectionKey($key))
            ->filter()
            ->values();
        $passiveD5Contexts = $selectedKeys
            ->map(fn ($key) => $this->parsePassiveD5SelectionKey($key))
            ->filter()
            ->values();

        if (!count($workReportIds) && $partialContexts->isEmpty() && $passiveD5Contexts->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($partialContexts->isEmpty() && $passiveD5Contexts->isEmpty()) {
            $this->emitTo('dispatchs.shared.dispatch-modal', 'openForWorkReports', $workReportIds);

            return;
        }

        $reportContexts = WorkReport::query()
            ->whereIn('id', $workReportIds)
            ->get(['id', 'note_id'])
            ->map(fn (WorkReport $report) => ['note_id' => (int) $report->note_id, 'work_report_id' => (int) $report->id]);

        $this->emitTo(
            'dispatchs.shared.dispatch-modal',
            'openForNotes',
            $reportContexts->concat($partialContexts)->concat($passiveD5Contexts)->all()
        );
    }

    public function confirm_att()
    {
        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->type === "2") {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . " da " . (Company::find($this->company_s))->name;
        } else {
            $para = (Company::find($this->company_s))->name;
        }

        $partial = Note::whereIn('id', $this->selectedNoteIds())->whereHas('Partials', function ($q) {
            $q->where('allow', true)
                ->where('supervision', false)
                ->where('deny', false);
        })->count();

        if ($partial > 0) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Despachar',
                'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}. <p class='py-2 my-3 text-bg-danger'> <strong>Atenção:</strong> Existem Notas/OVs Parciais para Fiscalização neste remessa.</strong></p>",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Despache!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => "confirm_dispatch",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Despachar',
                'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Despache!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => "confirm_dispatch",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        }

    }

    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma entrada para atribuição!',
                'timer'    => 5000,
            ]);

            return;
        }

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {

            foreach ($linhas as $linha) {

                if ($linha) {

                    $coluna = explode("\t", $linha);

                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $index = $this->notes->search(function ($note) use ($coluna) {
                            return $note->note == $coluna[0];
                        });

                        if ($index !== false) {
                            $this->additionalData[$index] = $coluna[1];
                        }
                    }
                }
            }
        }
    }

    public function confirmed_att()
    {
        if (!in_array((string) $this->type, ['1', '2'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione o tipo de despacho.',
                'timer'    => 2500,
            ]);

            return;
        }

        try {
            $workflow   = app(DispatchWorkflowService::class);
            $company    = Company::findOrFail($this->company_s);
            $targetUser = (string) $this->type === '2' ? User::findOrFail($this->user_s) : null;
            $actor      = Auth()->User();

            DB::transaction(function () use ($workflow, $company, $targetUser, $actor) {
                foreach ($this->notes as $key => $note) {
                    $dd = $this->additionalData[$key] ?? null;

                    if ($targetUser) {
                        $workflow->dispatchToUser($note, $this->service, $company, $targetUser, $actor, $dd);
                    } else {
                        $workflow->dispatchToCompanyStack($note, $this->service, $company, $actor, $dd);
                    }
                }
            });
        } catch (DispatchException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => $e->getMessage(),
                'timer'    => 6000,
            ]);

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao despachar as Notas/OVs.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas Despachadas com sucesso!',
            'timer'    => 2500,
        ]);

        $this->closeall();
        $this->emit('refresh_dispatch');
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s = "";
        $this->selected  = [];
        $this->user_s    = "";
        $this->user_l    = collect();
        // $this->type = "";
        $this->additionalData      = [];
        $this->bulkSearchAnyStatus = false;

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {

        $this->company_s   = "";
        $this->enter_dd    = "";
        $this->user_s      = "";
        $this->search_user = "";
        $this->user_l      = collect();
        // $this->type = "";
        $this->additionalData      = [];
        $this->multiSearch         = [];
        $this->bulkSearchAnyStatus = false;
        $this->advanceSearch       = "";
        $this->search              = "";
    }

    public function buscarMulti()
    {
        $this->gotoPage(1);

        $this->multiSearch = $this->formatTextToArray($this->advanceSearch);

        if ($this->multiSearch) {
            $this->search = "";
            $this->gotoPage(1);
            $this->advanceSearch = "";
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    /**
     * Atribuição de DD em Mmassa.
     *
     * Aqui foi montando uma arquitetura para que seja possível associar uma nota a uma DD, na tabela
     * WPAs. Assim sendo, quando carregar as DD para atribuição em massa, o mesmo ja seja inserido nas
     * correspondentes.
     */

    public function mass_modal()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma entrada para atribuição.',
                'timer'    => 5000,
            ]);

            return;
        }

        $additionalData    = [];
        $additionalDataUpd = [];
        $seenDds           = [];
        $unchanged         = 0;
        $moved             = 0;

        foreach (preg_split('/\r\n|\r|\n/', trim($this->enter_dd)) as $lineNumber => $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                continue;
            }

            $coluna = preg_split('/[\s;,]+/', $linha, -1, PREG_SPLIT_NO_EMPTY);

            if (count($coluna) !== 2) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Linha ' . ($lineNumber + 1) . ' inválida.',
                    'html'     => 'Informe exatamente dois valores por linha: <strong>Nota/OV</strong> e <strong>DD</strong>.',
                ]);

                return;
            }

            [$noteNumber, $ddNumber] = array_map('trim', $coluna);

            if (!preg_match('/^[0-9]+$/', $noteNumber) || !preg_match('/^[0-9]+$/', $ddNumber)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Linha ' . ($lineNumber + 1) . ' contém caracteres inválidos.',
                    'html'     => 'Nota/OV e DD devem conter somente números.',
                ]);

                return;
            }

            if (isset($seenDds[$ddNumber])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'DD duplicada no lote.',
                    'html'     => "A DD <strong>{$ddNumber}</strong> apareceu mais de uma vez. Revise o lote antes de continuar.",
                ]);

                return;
            }
            $seenDds[$ddNumber] = $noteNumber;

            $note = Note::where('note', $noteNumber)->first();

            if (!$note) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nota/OV não encontrada.',
                    'html'     => "A Nota/OV <strong>{$noteNumber}</strong> da linha " . ($lineNumber + 1) . ' não foi encontrada.',
                ]);

                return;
            }

            $existingByDd = Wpa::where('dd', $ddNumber)->first();

            if ($existingByDd && (string) $existingByDd->note_id !== (string) $note->id) {
                $additionalDataUpd[] = [
                    'id'            => $existingByDd->id,
                    'note_id'       => $note->id,
                    'service_id'    => $this->service->uuid,
                    'production_id' => null,
                    'dd'            => $ddNumber,
                ];
                $moved++;

                continue;
            }

            if ($existingByDd) {
                $unchanged++;

                continue;
            }

            $existingForNote = Wpa::where('note_id', $note->id)->whereNull('production_id')->first();

            if ($existingForNote) {
                $additionalDataUpd[] = [
                    'id'            => $existingForNote->id,
                    'note_id'       => $note->id,
                    'service_id'    => $this->service->uuid,
                    'production_id' => null,
                    'dd'            => $ddNumber,
                ];
            } else {
                $additionalData[] = [
                    'note_id'    => $note->id,
                    'service_id' => $this->service->uuid,
                    'dd'         => $ddNumber,
                ];
            }
        }

        if (!count($additionalData) && !count($additionalDataUpd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'Nenhuma alteração necessária.',
                'html'     => $unchanged
                    ? "{$unchanged} vínculo(s) informado(s) já existiam para a própria Nota/OV."
                    : 'Nenhuma associação nova foi identificada.',
            ]);

            return;
        }

        $count = count($additionalData) + count($additionalDataUpd);

        $this->additionalData    = $additionalData;
        $this->additionalDataUpd = $additionalDataUpd;

        $summary = "Você está prestes a associar {$count} Nota(s)/OV(s) e DD(s).";

        if ($moved) {
            $summary .= " {$moved} DD(s) serão movidas de outra Nota/OV para a Nota/OV informada.";
        }

        if ($unchanged) {
            $summary .= " {$unchanged} vínculo(s) já existiam e serão mantidos.";
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Associação de DD?',
            'msg'           => $summary,
            'icon'          => 'info',
            'btnOktxt'      => 'Sim, associar',
            'btnCanceltxt'  => 'Não, cancelar',
            'action'        => "confirm_mass_dd",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/OV foi associada.',

        ]);
    }

    public function confirmed_mass_dd()
    {

        $count = count($this->additionalData);
        $error = 0;

        foreach ($this->additionalData as $wpa) {
            if (!Wpa::create($wpa)) {
                $error++;
            }
        }

        $countUpd = count($this->additionalDataUpd);
        $errorUpd = 0;

        foreach ($this->additionalDataUpd as $wpa) {
            $idToUpdate = $wpa['id'];
            unset($wpa['id']);

            if (!Wpa::where('id', $idToUpdate)->update($wpa)) {
                $errorUpd++;
            }
        }

        $totalErrors = $error + $errorUpd;
        $totalCount  = $count + $countUpd;

        if (!$totalErrors) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas DDs associadas com sucesso',
                'timer'    => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "OOPS!, Ocorreram {$totalErrors} de {$totalCount} ao associar as DD às Notas.",
                'timer'    => 8000,
            ]);
        }
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->gotoPage(1);
    }

    public function filterD5()
    {
        $this->filter_d5 = !$this->filter_d5;
        $this->gotoPage(1);
    }

    public function getListsProperty()
    {
        $query = app(SupervisionDispatchListQuery::class)->build([
            'serviceUuid'         => $this->service->uuid,
            'user'                => Auth()->User(),
            'search'              => $this->search,
            'multiSearch'         => $this->multiSearch,
            'rubrica_s'           => $this->rubrica_s,
            'typeNote'            => $this->typeNote,
            'not_assigned'        => (bool) $this->not_assigned,
            'filterD5'            => (bool) $this->filter_d5,
            'filter'              => $this->listQueryFilter(),
            'bulkSearchAnyStatus' => (bool) $this->bulkSearchAnyStatus,
        ]);

        if (count($this->multiSearch)) {
            $this->gotoPage(1);
            $this->search = "";
        }

        return $query;
    }

    /**
     * Paginação com total. Contar as linhas repete a consulta pesada de candidatos (tanto quanto a própria página), então o
     * total fica em cache curto (60 s) por consulta: trocar de página, ordenar ou voltar à lista não paga a contagem de novo.
     */
    private function paginateWithCachedTotal(Builder $query): LengthAwarePaginator
    {
        $page  = Paginator::resolveCurrentPage('page');
        $total = Cache::remember(
            'supervision-dispatch:count:' . md5($query->toSql() . json_encode($query->getBindings())),
            60,
            fn () => (clone $query)->toBase()->getCountForPagination()
        );

        return new LengthAwarePaginator(
            $total ? (clone $query)->forPage($page, (int) $this->perPage)->get() : $query->getModel()->newCollection(),
            $total,
            (int) $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );
    }

    public function getToListsProperty()
    {
        $lists = $this->paginateWithCachedTotal($this->lists);
        $this->preloadRowRelations($lists->getCollection());

        $lists->getCollection()->transform(function (Note $note) {
            $note->setAttribute("dispatch_five_note_id", (int) ($note->passive_d5_id ?? 0));
            $note->setAttribute("dispatch_work_report_id", (int) ($note->operational_work_report_id ?? 0));
            $note->setAttribute("dispatch_partial_id", (int) ($note->row_partial_id ?? 0));
            $workReport = $this->operationalWorkReportFor($note);

            if ($workReport) {
                $note->setRelation('WorkForm', $workReport);
                $note->setRelation('FiveNote', $this->rowFiveNote($note));
            } elseif ((int) ($note->dispatch_partial_id ?? 0) > 0) {
                $note->setRelation('WorkForm', null);
                $note->setRelation('FiveNote', null);
            }

            return $note;
        });

        $this->preloadLazyWorkForms($lists->getCollection());

        return $lists;
    }

    public function render()
    {
        $this->dispatchStateMemo     = [];
        $this->scopedProductionsMemo = [];

        $lists     = $this->toLists;
        $legal     = $this->buildLegalTagsByNoteIds($lists->pluck('id')->all());
        $presenter = app(SupervisionListRowPresenter::class);
        $rows      = $lists->getCollection()->map(
            fn (Note $note) => $presenter->present($note, $this->rowDispatchState($note), $this->service->uuid, 'dispatch', $legal)
        );

        return view('livewire.dispatchs.supervision.main', [
            'lists'      => $lists,
            'rows'       => $rows,
            'lastUpdate' => $this->last_update,
        ]);
    }

    private function rowFiveNote(Note $note)
    {
        if ((int) ($note->dispatch_partial_id ?? 0) > 0) {
            return null;
        }

        $workReportId = (int) ($note->operational_work_report_id ?? 0);
        $workForm     = $workReportId > 0 && $note->relationLoaded('WorkForms')
            ? $note->WorkForms->firstWhere('id', $workReportId)
            : null;

        if ($workForm?->FiveNote) {
            return $workForm->FiveNote;
        }

        if ($workReportId <= 0) {
            return $note->relationLoaded('FiveNote') ? $note->FiveNote : null;
        }

        $scopedFive = $this->rowScopedProductions($note)
            ->sortByDesc('created_at')
            ->flatMap(fn (Production $production) => $production->relationLoaded('fiveNotes') ? $production->fiveNotes : collect())
            ->first(function ($five) use ($workReportId) {
                return (int) ($five->work_report_id ?? 0) === $workReportId;
            });

        if ($scopedFive) {
            return $scopedFive;
        }

        // D5 sem informe tem sua propria linha passiva; nao deve migrar para
        // uma linha de outro informe da mesma nota.
        return null;
    }

    public function selectionKeyFor(Note $note): string
    {
        $workReportId = (int) ($note->operational_work_report_id ?? 0);
        $partialId    = (int) ($note->dispatch_partial_id ?? 0);

        if ($workReportId > 0) {
            return "wr:" . $workReportId;
        }

        return $partialId > 0
            ? "pt:" . (int) $note->id . ":" . $partialId
            : "d5:" . (int) $note->id . ":" . (int) ($note->dispatch_five_note_id ?? 0);
    }

    public function operationalWorkReportFor(Note $note): ?WorkReport
    {
        $workReportId = (int) ($note->operational_work_report_id ?? 0);

        if ($workReportId <= 0) {
            return null;
        }

        if ($note->relationLoaded('WorkReports')) {
            return $note->WorkReports->firstWhere('id', $workReportId);
        }

        if ($note->relationLoaded('WorkForms')) {
            return $note->WorkForms->firstWhere('id', $workReportId);
        }

        return $note->WorkReports()->whereKey($workReportId)->first();
    }

    private function parsePartialSelectionKey($key): ?array
    {
        if (!is_string($key) || !str_starts_with($key, "pt:")) {
            return null;
        }

        [, $noteId, $partialId] = array_pad(explode(":", $key, 3), 3, null);

        return (int) $noteId > 0 && (int) $partialId > 0
            ? ["note_id" => (int) $noteId, "work_report_id" => null, "partial_id" => (int) $partialId, "five_note_id" => null]
            : null;
    }

    private function parsePassiveD5SelectionKey($key): ?array
    {
        if (!is_string($key) || !str_starts_with($key, 'd5:')) {
            return null;
        }

        [, $noteId, $fiveNoteId] = array_pad(explode(':', $key, 3), 3, null);

        if ((int) $noteId <= 0 || (int) $fiveNoteId <= 0) {
            return null;
        }

        return ['note_id' => (int) $noteId, 'work_report_id' => null, 'five_note_id' => (int) $fiveNoteId];
    }

    private function parseWorkReportSelectionKey($key): ?int
    {
        if (is_string($key) && str_starts_with($key, 'wr:')) {
            $id = (int) substr($key, 3);

            return $id > 0 ? $id : null;
        }

        return null;
    }

    /**
     * @deprecated Formato de chave antiga (noteId:workReportId:partialId), mantida
     * apenas para os fluxos legados abaixo (confirm_att/selectedNoteIds) que nao
     * sao mais acionados pela view atual. A selecao ativa usa selectionKeyFor().
     */
    private function parseSelectionKey($key): array
    {
        if (is_string($key) && str_starts_with($key, 'd5:')) {
            [, $noteId] = array_pad(explode(':', $key, 3), 3, null);

            return [(int) $noteId, null, null];
        }

        if (is_string($key) && str_contains($key, ':')) {
            [$noteId, $workReportId, $partialId] = array_pad(explode(':', $key, 3), 3, null);

            return [(int) $noteId, (int) $workReportId, (int) $partialId];
        }

        return [(int) $key, null, null];
    }

    private function selectedNoteIds(): array
    {
        return collect($this->selected)
            ->map(fn ($key) => $this->parseSelectionKey($key)[0])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
