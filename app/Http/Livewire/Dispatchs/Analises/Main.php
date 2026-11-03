<?php

namespace App\Http\Livewire\Dispatchs\Analises;

use App\Custom\RuleBuilder;
use App\Exports\Services\analisesExport;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User};
use App\Services\Analises\AnalysisListRowPresenter;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use Concerns\HasAnalysisListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    public $company_s;

    public $user_s;

    public $type;

    public $additionalData = [];

    public $notes;

    public $enter_dd;

    public $note_type = '';

    public $not_assigned = false;

    public $mmgd = false;

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'mmgd'         => ['except' => false, 'as' => 'semmmgd'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('analises-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    /** Perfil da linha para o apresentador e da view: `dispatch` (Despacho) ou `partner` (parceira). */
    protected function listProfile(): string
    {
        return 'dispatch';
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

    public function updatedCompanyS()
    {

        $this->user_s = '';
    }

    public function filterMMGD()
    {
        $this->mmgd = !$this->mmgd;
        $this->gotoPage(1);
    }

    public function export_excel()
    {
        $query = $this->lists->with('Productions.Company');

        if (count($this->selected)) {
            $query->whereIn('id', $this->selected);
        }

        return (new analisesExport($query, $this->service->uuid))->download(date('YmdHis-') . 'exportAnalises.xlsx');
    }

    /** Página atual da lista (uma vez por requisição; o render a refaz). */
    private ?\Illuminate\Pagination\LengthAwarePaginator $pageMemo = null;

    private function currentPage(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->pageMemo ??= $this->lists->paginate($this->perPage);
    }

    /** Selecionar todos: só as linhas da página que podem ser despachadas. */
    public function setSelectAll()
    {
        $visibleItems = $this->currentPage()->items();
        $selectedSet  = array_fill_keys(array_map('strval', $this->selected), true);

        if ($this->selectall) {
            foreach ($visibleItems as $note) {
                if ($this->canDispatchNote($note)) {
                    $selectedSet[(string) $note->id] = true;
                }
            }
        } else {
            foreach ($visibleItems as $note) {
                unset($selectedSet[(string) $note->id]);
            }
        }

        $this->selected = array_map(strval(...), array_keys($selectedSet));
    }

    /** O mestre fica marcado quando todas as linhas elegíveis da página estão selecionadas. */
    protected function recomputeSelectAllFor(array $items): void
    {
        $eligible = [];

        foreach ($items as $note) {
            if ($this->canDispatchNote($note)) {
                $eligible[] = (string) $note->id;
            }
        }

        $this->selectall = $eligible && !array_diff($eligible, array_map('strval', $this->selected));
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

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', array_values($this->selected));
    }

    public function confirm_att()
    {
        if ($this->type === '2') {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . ' da ' . (Company::find($this->company_s))->name;

        } else {

            if (!$this->company_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = (Company::find($this->company_s))->name;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Despachar',
            'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

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

        $erros = [];

        if ($this->type == '2') {

            foreach ($this->notes as $key => $note) {

                if (!$erro = Production::where('note_id', $note->id)->Where('service_id', $this->service->uuid)->Where('confirmed', false)->first()) {
                    $production = Production::create([
                        'note_id'     => $note->id,
                        'service_id'  => $this->service->uuid,
                        'user_id'     => $this->user_s,
                        'company_id'  => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'att_by'      => Auth()->User()->id,
                        'dt_note'     => $note->dt_status,
                        'status_note' => $note->nstats,
                        'centroTrab'  => $note->centerjob,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'att_at'      => date('Y-m-d H:i:s'),
                        'status'      => 2,
                    ]);

                    $user = Auth()->User()->name;

                    $user_info = $this->dispatchRecipientInfo();

                    if ($production) {
                        Notetimeline::Create([
                            'note_id'       => $production->note_id,
                            'service_id'    => $production->service_id,
                            'user_id'       => Auth()->User()->id,
                            'info'          => "Usuário {$user} {$user_info}",
                            'status'        => 2,
                            'production_id' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;
                }
            }

        } else {

            foreach ($this->notes as $key => $note) {

                if (!$erro = Production::where('note_id', $note->id)->Where('service_id', $this->service->uuid)->Where('confirmed', false)->first()) {
                    $production = Production::create([
                        'note_id'     => $note->id,
                        'service_id'  => $this->service->uuid,
                        'company_id'  => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'dt_note'     => $note->dt_status,
                        'status_note' => $note->nstats,
                        'centroTrab'  => $note->centerjob,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'status'      => 1,
                    ]);

                    $user = Auth()->User()->name;

                    $user_info = $this->dispatchRecipientInfo();

                    if ($production) {
                        Notetimeline::Create([
                            'note_id'       => $production->note_id,
                            'service_id'    => $production->service_id,
                            'user_id'       => Auth()->User()->id,
                            'info'          => "Usuário {$user} {$user_info}",
                            'status'        => 1,
                            'production_id' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;
                }

            }
        }

        if (count($erros)) {

            $info = '<br>';

            foreach ($erros as $err) {
                $info .= $this->productionAssignmentLabel($err) . '<br>';
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso parcial!',
                'msg'      => "Foram Despachadas com sucesso, porém, algumas ja se enconram em controle: {$info}",
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso!',
                'timer'    => 2500,
            ]);
        }

        $this->closeall();
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->advanceSearch  = '';
        $this->search         = '';
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
        $this->advanceSearch  = '';
        $this->search         = '';
    }

    public function buscarMulti()
    {

        if ($this->advanceSearch) {

            $this->gotoPage(1);

            $this->search = '';

            $this->multiSearch = explode("\n", $this->advanceSearch);

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(' ', $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(',', $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(';', $this->advanceSearch);
            }

            $this->multiSearch = array_map('trim', $this->multiSearch);
        }

        if (count($this->multiSearch)) {
            $this->closeall();
        }
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->gotoPage(1);
    }

    public function getListsProperty()
    {
        $query = Note::query()->excludeCanceledFullDone();

        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            Auth()->User(),
            $this->service->uuid,
            fn ($statusQuery) => RuleBuilder::applyRules($statusQuery, $this->service->Status)
        );

        if (count($this->multiSearch)) {
            $query->whereIn('note', $this->multiSearch);
        }

        if ($this->not_assigned) {
            $query->where(function ($q) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) {
                        $subquery->where('service_id', $this->service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        $query->when($this->search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('material', 'like', '%' . $s . '%')
                    ->orWhere('numPedido', 'like', '%' . $s . '%')
                    ->orWhere('group2', 'like', '%' . $s . '%')
                    ->orWhere('group4', 'like', '%' . $s . '%')
                    ->orWhere('group5', 'like', '%' . $s . '%');
            });
        });

        if ($this->mmgd) {
            $query->where('material', 'not like', '%MMGD%');
        }

        $rubrica  = $this->filterValues('rubrica');
        $material = $this->filterValues('material');
        $base     = $this->base;

        $query->when($rubrica, fn ($q) => $q->whereIn('rubrica', $rubrica))
            ->when($material, fn ($q) => $q->whereIn('material', $material))
            ->when($base, fn ($q) => $q->whereIn('nexp', $base));

        // Só as Productions desta atividade, com o que a linha mostra (a avaliação de bloqueio lê da relação carregada).
        $serviceId = $this->service->uuid;

        $query->with(['Productions' => fn ($q) => $q->where('service_id', $serviceId)->with(['User:id,name', 'Company:id,name'])])
            ->orderBy('type_note', 'DESC')
            ->orderBy('dt_created');

        return $query;
    }

    private function dispatchRecipientInfo(): string
    {
        if (trim((string) $this->user_s)) {
            $userName = User::find($this->user_s)?->name ?? 'Desconhecido';

            return "Atribuiu a NOTA/OV para: {$userName}";
        }

        $companyName = Company::find($this->company_s)?->name ?? 'Desconhecido';

        return "Despachou a NOTA/OV para: {$companyName}";
    }

    private function productionAssignmentLabel(Production $production): string
    {
        $production->loadMissing(['Note', 'User', 'Company']);

        $note = $production->Note?->note ?? $production->note_id;

        if ($production->User) {
            return "{$note} => {$production->User->name}";
        }

        if ($production->Company) {
            return "{$note} => {$production->Company->name} (sem usuário atribuído)";
        }

        return "{$note} => Desconhecido";
    }

    public function render()
    {
        $this->dispatchStateMemo = [];
        $this->pageMemo          = null;

        $lists = $this->currentPage();
        $this->recomputeSelectAllFor($lists->items());

        $presenter = app(AnalysisListRowPresenter::class);
        $rows      = $lists->getCollection()->map(fn (Note $note) => $presenter->present($note, $this->rowDispatchState($note), $this->listProfile()));

        return view('livewire.dispatchs.analises.main', [
            'lists'        => $lists,
            'rows'         => $rows,
            'profile'      => $this->listProfile(),
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('id')->all()),
            'update'       => Cache::remember('analises-dispatch:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
        ]);
    }
}
