<?php

namespace App\Http\Livewire\Dispatchs\Payment;

use App\Custom\RuleBuilder;
use App\Exports\Dispatchs\DispatchPaymentMain;
use App\Helpers\TextFormatter;
use App\Jobs\Dispatchs\ExportDispatchPaymentJob;
use App\Models\City;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User};
use App\Services\D5\D5WorkflowService;
use App\Services\Payment\BlockEvaluator;
use App\Services\Payment\NoteFilter;
use App\Services\WorkReports\WorkReportFinalScopeOptions;
use App\Services\WorkReports\WorkReportFlowProductionLinker;
use App\Support\SicodeRules;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    // Estado / filtros
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

    public $selectall = false;
    public $selected = [];

    public $company_l;
    public $company_s;
    public $user_l;
    public $user_s;

    public $type;
    public $additionalData = [];
    public array $finalScopeOptions = [];
    public array $finalScopeSelections = [];
    public $notes;
    public $enter_dd;

    public $filteredLists;
    public $note_type = '';

    // Filtros de localidade/grupos
    public $region_l;
    public $region_s = [];

    public $district_l;
    public $district_s = [];

    public $city_l;
    public $city_s = [];

    public $group1_l;
    public $group1_s = [];

    public $group2_l;
    public $group2_s = [];

    public $group5_l;
    public $group5_s = [];

    public $not_assigned = false;
    public $typeNote = '';

    public $filter_d5 = false;
    public $multi_search_any_situation = false;
    public bool $bulkSearchAnyStatus = false;

    // Grupo de filtro (usado pelo NoteFilter)
    private $filter_group = 'payments';

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
    ];

    protected $noteFilter;

    public function boot(NoteFilter $noteFilter)
    {
        $this->noteFilter = $noteFilter;
    }

    private function rowSelectionKey(Note $note): string
    {
        return (string) ($note->getAttribute('payment_context_key') ?: $note->id);
    }

    private function parseSelectionKey($key): array
    {
        if (is_string($key) && str_contains($key, ':')) {
            [$noteId, $workReportId, $partialToken] = array_pad(explode(':', $key, 3), 3, null);
            $partialId = str_starts_with((string) $partialToken, 'p') ? (int) substr((string) $partialToken, 1) : null;

            return [(int) $noteId, (int) $workReportId, $partialId ?: null];
        }

        return [(int) $key, null, null];
    }

    private function dispatchPayloadForSelection($key): array
    {
        [$noteId, $workReportId, $partialId] = $this->parseSelectionKey($key);

        return [
            'note_id' => $noteId,
            'work_report_id' => $workReportId ?: null,
            'partial_id' => $partialId,
            'bulk_any_status' => (bool) $this->bulkSearchAnyStatus,
        ];
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = optional(Note::orderByDesc('dt_status')->first())->dt_status;
        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                  ->where('service', true);
            });
        })
        ->orderBy('name', 'ASC')
        ->get();
    }

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function updatedTypeNote()
    {
        $this->gotoPage(1);
    }

    public function updatedCompanyS()
    {
        $this->user_s = '';
        $this->chargerList();
    }

    /**
     * EXPORTAÇÃO
     * - Sem seleção: exporta tudo que está no filtro atual (sem paginação)
     * - Com seleção: exporta apenas as selecionadas
     */
    public function export_excel()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $filters = $_SESSION['filter'][$this->filter_group] ?? session('filter.' . $this->filter_group, []);

        $params = [
            'source'                     => 'dispatch',
            'service_uuid'               => $this->service->uuid,
            'search'                     => $this->search,
            'multiSearch'                => $this->multiSearch,
            'multi_search_any_situation' => (bool) $this->multi_search_any_situation,
            'bulkSearchAnyStatus'        => (bool) $this->bulkSearchAnyStatus,
            'selected_ids'               => collect($this->selected)
                ->map(fn ($key) => $this->parseSelectionKey($key)[0])
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'selected_work_report_ids'  => collect($this->selected)
                ->map(fn ($key) => $this->parseSelectionKey($key)[1])
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'selected_partial_ids'      => collect($this->selected)
                ->map(fn ($key) => $this->parseSelectionKey($key)[2])
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'typeNote'                   => $this->typeNote,
            'not_assigned'               => $this->not_assigned,
            'company_ids'                => $filters['company'] ?? null,
            'rubricas'                   => $filters['rubrica'] ?? null,
            'regions'                    => $filters['region'] ?? null,
            'regionals'                  => $filters['regional'] ?? null,
            'cities'                     => $filters['city'] ?? null,
            'filter_d5'                  => property_exists($this, 'filter_d5') ? (bool) $this->filter_d5 : false,
        ];

        ExportDispatchPaymentJob::dispatch($params, (string)auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'info',
            'title'    => 'Estamos gerando seu relatório!',
            'html'     => 'Você será notificado quando o arquivo estiver pronto para download.',
            'timer'    => 3000,
        ]);
    }

    public function filterD5()
    {
        $this->filter_d5 = !$this->filter_d5;
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

    /**
     * Regras para bloqueio/produção já existente
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
        $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);
        // retorna estrutura pra view usar diretamente
        return $eval;
    }

    private function canDispatchNote(Note $note): bool
    {
        $eval = $this->needBlock($note);

        return !$eval['block']
            || (bool) ($eval['command'] ?? false)
            || (bool) SicodeRules::openCompanyStackProductionFor($note, Auth()->User(), $this->service->uuid);
    }

    /**
     * Selecionar todos os itens visíveis na página atual, desde que NÃO tenham produção aberta
     */
    public function setSelectAll()
    {
        if (!$this->lists) {
            return;
        }

        $visibleItems = $this->lists->items();

        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);

        if ($this->selectall) {

            foreach ($visibleItems as $note) {

                $id = $this->rowSelectionKey($note);

                if (isset($selectedSet[$id])) {
                    continue;
                }

                if ($this->canDispatchNote($note)) {
                    $selectedSet[$id] = true;
                }
            }
        } else {
            foreach ($visibleItems as $note) {
                unset($selectedSet[$this->rowSelectionKey($note)]);
            }
        }

        $this->selected = array_values(array_keys($selectedSet));

    }

    /**
     * Marca/desmarca o checkbox "selecionar todos" de acordo com os itens visíveis
     */
    public function checkAllSelect($items)
    {
        $eligiblePage = [];

        foreach ($items as $note) {
            if ($this->canDispatchNote($note)) {
                $eligiblePage[] = $this->rowSelectionKey($note);
            }
        }

        // selectall fica true quando TODOS os elegíveis da página estão selecionados
        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectall = false;
                return false;
            }
        }

        $this->selectall = true;
        return true;
    }

    protected function recomputeSelectAllFor(array $items): void
    {
        $eligiblePage = [];

        foreach ($items as $note) {
            if ($this->canDispatchNote($note)) {
                $eligiblePage[] = $this->rowSelectionKey($note);
            }
        }

        // se não há elegíveis na página, não marcar o master
        if (empty($eligiblePage)) {
            $this->selectall = false;
            return;
        }

        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectall = false;
                return;
            }
        }

        $this->selectall = true;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function deadline(Note $note)
    {
        $days       = 10;
        $date_forms = optional($note->WorkForm)->informed_at;

        if ($date_forms) {
            $deadline_date = Carbon::parse($date_forms)->addDays($days);
            return Carbon::now()->diffInDays($deadline_date, false);
        }
        return 0;
    }

    public function filter_save()
    {
        $this->gotoPage(1);

        if (!isset($_SESSION)) {
            if (!session()->isStarted()) { session()->start(); }
        }
        $_SESSION['filtro']['desenho']['rubrica']  = $this->rubrica_s;
        $_SESSION['filtro']['desenho']['city']     = $this->city_s;
        $_SESSION['filtro']['desenho']['district'] = $this->district_s;
        $_SESSION['filtro']['desenho']['region']   = $this->region_s;
        $_SESSION['filtro']['desenho']['group1']   = $this->group1_s;
        $_SESSION['filtro']['desenho']['group2']   = $this->group2_s;
        $_SESSION['filtro']['desenho']['group5']   = $this->group5_s;

        $this->clean();
        $this->emit('refresh_service');
    }

    public function filter_clean()
    {
        $this->gotoPage(1);

        $this->rubrica_s  = [];
        $this->city_s     = [];
        $this->district_s = [];
        $this->region_s   = [];
        $this->group1_s   = [];
        $this->group2_s   = [];
        $this->group5_s   = [];

        $this->multiSearch = [];
        $this->multi_search_any_situation = false;

        if (!isset($_SESSION)) {
            if (!session()->isStarted()) { session()->start(); }
        }
        if (isset($_SESSION['filtro']['desenho'])) {
            unset($_SESSION['filtro']['desenho']);
        }

        $this->emit('refresh_service');
    }

    public function get_single_note($note)
    {
        $this->selected = [(string) $note];
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

        $payload = collect($this->selected)
            ->map(fn ($key) => $this->dispatchPayloadForSelection($key))
            ->values()
            ->all();

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', $payload);
    }

    public function confirm_att()
    {

        $errors       = new Collection();
        // 1) Carrega as notas selecionadas
        $this->notes = Note::find($this->selected);

        // 2) Verifica bloqueios
        $blocked = [];





        foreach ($this->notes as $note) {

            $note->loadMissing([
               'WorkForm',
               'FiveNote',
               'Partials',
               'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                                           ->orderByDesc('created_at'),
           ]);



            $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);

            if ($eval['block'] && !($eval['command'] ?? false)) {
                $errors->push([
                    'note' => $note->note,
                    'when' => $eval['production']?->dt_note?->format('d/m/Y H:i'),
                ]);
                continue;
            }

            if (!$this->hasValidFinalScopeSelection($note)) {
                $errors->push([
                    'note' => $note->note,
                    'when' => 'selecione Rede, Ligacao ou ambos para medicao',
                ]);
                continue;
            }

            if (!$this->hasValidFinalScopeSelection($note)) {
                $errors->push([
                    'note' => $note->note,
                    'when' => 'selecione Rede, Ligacao ou ambos para medicao',
                ]);
            }
        }



        if ($errors->isNotEmpty()) {
            $lines = $errors
                ->map(fn ($e) => "{$e['note']} (já em {$e['when']})")
                ->implode("<br>– ");
            return $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Algumas notas não foram despachadas',
                'html'     => "As seguintes notas já tinham produção:<br>– {$lines}",
                'timer'    => 3000,
            ]);
        }

        // 3) Monta string "para"
        $para = $this->getDispatchTargetName();
        if ($para === false) {
            return;
        }

        // 4) Confirmação
        $message = "Você está prestes a despachar {$this->notes->count()} nota(s) para {$para}.";
        if ($this->multi_search_any_situation && !empty($this->multiSearch)) {
            $message .= "<br><br><div class='text-start'><strong>Atenção:</strong> a busca em qualquer situação da busca em massa está ativa. Esta operação pode incluir notas fora do fluxo padrão. Revise a seleção antes de confirmar.</div>";
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Despachar',
            'msg'           => $message,
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nota foi removida.',
        ]);
    }

    /**
     * Recebe colagem de “nota\tvalor” (linhas)
     */
    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                'timer'    => 5000,
            ]);
            return;
        }

        $linhas = explode("\n", trim($this->enter_dd));

        foreach ($linhas as $linha) {
            if (!$linha) {
                continue;
            }

            $coluna = explode("\t", $linha);

            if (
                isset($coluna[0], $coluna[1]) &&
                preg_match('/^[0-9]+$/', $coluna[0]) &&
                preg_match('/^[0-9]+$/', $coluna[1])
            ) {
                $index = $this->notes?->search(fn ($note) => $note->note == $coluna[0]);

                if ($index !== false && $index !== null) {
                    $this->additionalData[$index] = $coluna[1];
                }
            }
        }
    }

    public function confirmed_att()
    {
        $dispatcherId = auth()->id();
        $now          = now()->format('Y-m-d H:i:s');
        $errors       = new Collection();

        $targetName = $this->getDispatchTargetName();
        if ($targetName === false) {
            return;
        }

        if (empty($this->notes)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota selecionada!',
                'timer'    => 5000,
            ]);
            return;
        }

        foreach ($this->notes as $note) {



            $note->loadMissing([
                'WorkForm',
                'FiveNote',
                'Partials',
                'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                                            ->orderByDesc('created_at'),
            ]);

            $fiveNote = (bool) ($note->WorkForm?->FiveNote ?? $note->FiveNote);

            $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);


            if ($eval['block'] && !($eval['command'] ?? false)) {
                $errors->push([
                    'note' => $note->note,
                    'when' => $eval['production']?->dt_note?->format('d/m/Y H:i'),
                ]);
                continue;
            }

            // 2) parcial elegível?
            $partialModel = $note->dispatch_partial_id
                ? $note->partials()->whereKey($note->dispatch_partial_id)->first()
                : $note->partials()
                    ->where('allow', true)
                    ->where('deny', false)
                    ->where('supervision', true)
                    ->where('payment', false)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

            $isPartial = (bool) $partialModel;

            // 3) dt_note
            $dtNote = $isPartial
                ? $partialModel->created_at->format('Y-m-d H:i:s')
                : $note->dt_status;

            // 4) dados comuns
            $data = [
                'note_id'     => $note->id,
                'service_id'  => $this->service->uuid,
                'dispatch_by' => $dispatcherId,
                'dt_note'     => $dtNote,
                'status_note' => $note->nstats,
                'centroTrab'  => $note->centerjob ?? null,
                'dispatch_at' => $now,
                'partial'     => (bool) $isPartial,
                'dfive'       => $fiveNote,
            ];

            // 5) específicos por tipo
            if ($this->type === '2') {
                $data += [
                    'user_id'    => $this->user_s,
                    'company_id' => $this->company_s,
                    'att_by'     => $dispatcherId,
                    'att_at'     => $now,
                    'status'     => 2,
                ];
            } else {
                $data += [
                    'company_id' => $this->company_s,
                    'status'     => 1,
                ];
            }

            // 6) cria produção + timeline
            $production = Production::firstOrCreate([
                'note_id'    => $note->id,
                'service_id' => $this->service->uuid,
                'user_id'    => $this->type === '2' ? $this->user_s : null,
                'completed'  => false,
            ], $data);



            if ($production) {
                $linker = app(WorkReportFlowProductionLinker::class);
                $finalScopes = $this->selectedFinalScopesForNote($note);
                $workReportId = (int) ($note->dispatch_work_report_id ?? 0);

                if ($workReportId) {
                    $linker->linkPaymentForWorkReport($production, $workReportId, $finalScopes, 'dispatch_payment_main');
                } else {
                    $linker->linkPaymentForScopes($production, $finalScopes, 'dispatch_payment_main');
                }

                if ($partialModel) {
                    $production->update(['partial' => true]);
                    $production->partialInforms()->syncWithoutDetaching([$partialModel->id]);
                }

                Notetimeline::create([
                    'note_id'      => $production->id, // (verifique se aqui não deveria ser $note->id)
                    'service_id'   => $production->service_id,
                    'user_id'      => $dispatcherId,
                    'info'         => "Usuário " . auth()->user()->name . " despachou a Nota/OV para: {$targetName}",
                    'status'       => $data['status'],
                    'productionId' => $production->id,
                ]);

                $productionFiveNote = $note->WorkForm?->FiveNote ?? $note->FiveNote;

                if ($this->type === '2' && $production->user_id && $productionFiveNote) {
                    $productionFiveNote->productions()->syncWithoutDetaching([$production->id]);

                    app(D5WorkflowService::class)->onProductionAssigned(
                        $productionFiveNote,
                        $production,
                        $dispatcherId,
                        null
                    );
                }
            }
        }

        if ($errors->isNotEmpty()) {
            $lines = $errors
                ->map(fn ($e) => "{$e['note']} (já em {$e['when']})")
                ->implode("<br>– ");
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Algumas notas não foram despachadas',
                'html'     => "As seguintes notas já tinham produção:<br>– {$lines}",
                'timer'    => 3000,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas despachadas com sucesso!',
                'timer'    => 2500,
            ]);
        }

        $this->closeall();
    }

    private function getDispatchTargetName()
    {
        if ($this->type === '2') {
            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);
                return false;
            }
            $user    = User::find($this->user_s);
            $company = Company::find($this->company_s);
            return ($user->name ?? 'Desconhecido') . ' da ' . ($company->name ?? 'Desconhecido');
        }

        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa selecionada para despacho!',
                'timer'    => 2500,
            ]);
            return false;
        }
        $company = Company::find($this->company_s);
        return $company->name ?? 'Desconhecido';
    }

    private function prepareFinalScopeSelection(Note $note): void
    {
        $options = app(WorkReportFinalScopeOptions::class)->forNote($note);
        $this->finalScopeOptions[$note->id] = $options;
        $this->finalScopeSelections[$note->id] = [];

        if (count($options) === 1) {
            $this->finalScopeSelections[$note->id][$options[0]['scope']] = true;
        }
    }

    private function selectedFinalScopesForNote(Note $note): array
    {
        $selected = collect($this->finalScopeSelections[$note->id] ?? [])
            ->filter(fn ($enabled) => (bool) $enabled)
            ->keys()
            ->all();

        return app(WorkReportFinalScopeOptions::class)
            ->validScopesForNote($note, $selected);
    }

    private function hasValidFinalScopeSelection(Note $note): bool
    {
        $available = app(WorkReportFinalScopeOptions::class)->forNote($note);

        return count($available) <= 1 || !empty($this->selectedFinalScopesForNote($note));
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->finalScopeOptions = [];
        $this->finalScopeSelections = [];
        $this->advanceSearch  = '';
        $this->search         = '';
        $this->multi_search_any_situation = false;
        $this->bulkSearchAnyStatus = false;
        $this->gotoPage(1);

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {
        $this->company_s      = '';
        $this->enter_dd       = '';
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->multiSearch    = [];
        $this->multi_search_any_situation = false;
        $this->bulkSearchAnyStatus = false;
        $this->advanceSearch  = '';
        $this->search         = '';
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->gotoPage(1);
            $this->search = '';

            $this->multiSearch = array_values($this->formatTextToArray($this->advanceSearch));
        } else {
            $this->multiSearch = [];
            $this->multi_search_any_situation = false;
            $this->bulkSearchAnyStatus = false;
        }

        if (count($this->multiSearch)) {
            $this->gotoPage(1);
            $this->selected = [];
            $this->selectall = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->gotoPage(1);
    }

    /**
     * QUERY BASE (reutilizável)
     */
    private function baseQuery()
    {
        $useAnySituationFromMassSearch = ($this->bulkSearchAnyStatus || $this->multi_search_any_situation)
            && !empty($this->multiSearch);

        $base = ($useAnySituationFromMassSearch
            ? Note::query()
            : $this->noteFilter->filter(null, $this->filter_group));

        SicodeRules::applyContractDispatchMainVisibility(
            $base,
            Auth()->User(),
            $this->service->uuid,
            fn ($statusQuery) => null
        );

        $base = $base->select([
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

        // latest_partials
        $latestPartialBase = DB::table('partials as p')
            ->selectRaw("
                p.note_id,
                p.supervision_at,
                ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.id DESC) AS rn
            ")
            ->where('p.allow', 1)
            ->where('p.deny', 0)
            ->where('p.supervision', 1)
            ->where('p.payment', 0);

        $latestPartials = DB::query()
            ->fromSub($latestPartialBase, 't')
            ->select('t.note_id', 't.supervision_at')
            ->where('t.rn', 1);

        // latest production por serviço
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

        // BUCKET de ordenação
        $base->addSelect(DB::raw("
            CASE
              WHEN latest_partials.supervision_at IS NOT NULL
                   AND NOT EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                THEN 0
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
              WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                THEN 2
              ELSE 3
            END AS sort_bucket
        "));

        // Filtros dinâmicos
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

        // Exibir apenas quem tem D5 (se existir $this->filter_d5)
        if (property_exists($this, 'filter_d5') && $this->filter_d5) {
            $base->whereExists(function ($sq) {
                $sq->select(DB::raw(1))
                   ->from('five_notes as fn')
                   ->whereColumn('fn.note_id', 'notes.id');
            });
        }

        // Ordenação final
        $base->orderBy('sort_bucket', 'ASC')
             ->orderByRaw('(fimLancado IS NULL) DESC')
             ->orderBy('fimLancado', 'ASC');

        return $base;
    }

    /**
     * Propriedade computada usada no Blade: lista paginada
     */
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
            'WorkForm.FiveNote:id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,completed_at',
            'WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
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
            'WorkForms.FiveNote:id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,completed_at',
            'WorkForms.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'WorkForms.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'WorkForms.Adsform:id,work_report_id,created_at',
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
            'Partials.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.moaberto', 'orders.statusSist']),
            'Partials.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'FiveNote:id,note_id,is_supervisioned,is_completed,is_archived,completed_at',
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

            if ($eligibleWorkForms->isEmpty()) {
                if ($workForms->isNotEmpty() && !$this->isD5ReturnReadyForPayment($note)) {
                    return [];
                }

                $note->setAttribute('payment_context_key', (string) $note->id);

                $partial = $note->relationLoaded('Partials')
                    ? $note->Partials
                        ->where('allow', true)
                        ->where('deny', false)
                        ->where('supervision', true)
                        ->where('payment', false)
                        ->sortByDesc(fn ($item) => [$item->created_at?->timestamp ?? 0, $item->id])
                        ->first()
                    : null;

                if ($partial) {
                    $row = clone $note;
                    $row->setAttribute('payment_context_key', $note->id . ':0:p' . $partial->id);
                    $row->setAttribute('dispatch_partial_id', (int) $partial->id);
                    return [$row];
                }

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

    /**
     * Suporte a filtros de base (usado no Blade em selects dependentes)
     */
    public function getBaseProperty()
    {
        try {
            $query          = City::query();
            $filtersApplied = false;

            if (!empty($this->region_s)) {
                $query->whereIn('regiao', $this->region_s);
                $filtersApplied = true;
            }

            if (!empty($this->district_s)) {
                $query->whereIn('baseConstrucao', $this->district_s);
                $filtersApplied = true;
            }

            if (!empty($this->city_s)) {
                $query->whereIn('cidade', $this->city_s);
                $filtersApplied = true;
            }

            if (!$filtersApplied) {
                return [];
            }

            return $query->orderBy('cidade')
                ->get()
                ->pluck('rdMunicipio')
                ->toArray();
        } catch (\Throwable $th) {
            return [];
        }
    }




    public function chargerList()
    {
        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                  ->where('service', true);
            });
        })
        ->orderBy('name', 'ASC')
        ->get();

        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
              ->where('service', true);
        })
            ->where(function ($q) {
                $q->whereRelation('Company', 'company_id', $this->company_s)
                  ->orWhere('company_id', $this->company_s);
            })
            ->when($this->search_user, fn ($q) => $q->where('name', 'like', '%' . $this->search_user . '%'))
            ->orderBy('name', 'ASC')
            ->get();

        $this->emitSelf('refresh_list');
    }

    public function render()
    {

        // $this->company_l = Company::whereHas('toUsers', function ($query) {
        //     $query->whereRelation('ToServices', function ($q) {
        //         $q->where('service_id', $this->service->uuid)
        //           ->where('service', true);
        //     });
        // })
        // ->orderBy('name', 'ASC')
        // ->get();


        // $this->user_l = User::whereRelation('ToServices', function ($q) {
        //     $q->where('service_id', $this->service->uuid)
        //       ->where('service', true);
        // })
        //     ->where(function ($q) {
        //         $q->whereRelation('Company', 'company_id', $this->company_s)
        //           ->orWhereRelation('Employee.Contract.company', 'id', $this->company_s);
        //     })
        //     ->when($this->search_user, fn ($q) => $q->where('name', 'like', '%' . $this->search_user . '%'))
        //     ->orderBy('name', 'ASC')
        //     ->get();


        // $this->rubrica_l = Note::select('rubrica')
        //     ->where('nstats', optional($this->service)->status)
        //     ->orderBy('rubrica')
        //     ->groupBy('rubrica')
        //     ->get();


        // try {
        //     $this->region_l = City::select('regiao')->orderBy('regiao')->groupBy('regiao')->get();

        //     $this->district_l = City::when($this->region_s, fn ($q) => $q->whereIn('regiao', $this->region_s))
        //         ->select('baseConstrucao')->orderBy('baseConstrucao')->groupBy('baseConstrucao')->get();

        //     $this->city_l = City::when($this->region_s, fn ($q) => $q->whereIn('regiao', $this->region_s))
        //         ->when($this->district_s, fn ($q) => $q->whereIn('baseConstrucao', $this->district_s))
        //         ->select('rdMunicipio', 'cidade', 'municipio')
        //         ->orderBy('cidade')
        //         ->groupBy('rdMunicipio', 'cidade', 'municipio')
        //         ->get();
        // } catch (\Illuminate\Database\QueryException $e) {
        //     $this->region_l   = [];
        //     $this->district_l = [];
        //     $this->city_l     = [];
        // }

        $lists = $this->lists;
        $this->recomputeSelectAllFor($lists?->items() ?? []);

        return view('livewire.dispatchs.payment.main', [
            'lists'  => $lists,
            'update' => Bancoupdate::orderByDesc('created_at')->first(),
        ]);
    }
}
