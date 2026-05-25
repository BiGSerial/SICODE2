<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Custom\RuleBuilder;
use App\Exports\Dispatchs\SupervisionExportList;
use App\Exports\ExportDDExcel;
use App\Exports\ExportDDSupervision;
use App\Helpers\TextFormatter;
use App\Jobs\ExportSupervisionList;
use App\Models\Bancoupdate;
use App\Models\Company;
use App\Models\Edp_depc\City;
use App\Models\Note;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\Wpa;
use App\Repositories\SupervisionRepository;
use App\Services\Supervision\BlockEvaluator;
use App\Services\D5\D5WorkflowService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpParser\Node\Expr\Empty_;

class Main extends Component
{
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

    // Filtros Municípios
    public $region_l;
    public $region_s = [];
    public $district_l;
    public $district_s = [];
    public $city_l;
    public $city_s = [];


    public $branco = false;




    //Variáveis para DDs
    public $enter_dd;
    public $existDD;

    public $key;
    public $municipio_edit;

    //Botão de exibição de nao atribuído
    public $not_assigned = false;
    public $filter_d5 = false;

    // Filters
    private $filter_group = 'supervision';

    private $filter;



    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy' => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch' => 'confirmed_att',
        'confirm_mass_dd' => 'confirmed_mass_dd',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
    ];



    private $supervisionRepository;

    public function boot(SupervisionRepository $supervisionRepository)
    {
        $this->supervisionRepository = $supervisionRepository;
    }

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

    public function municipio_update(Note $note)
    {
        if (trim($this->municipio_edit)) {
            if ($note->update(['lexp' => mb_strtoupper(trim($this->municipio_edit))])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'success',
                    'title' => 'Informação Alterada',
                    'timer' => 2500,
                ]);

                $this->municipio_edit = "";
                $this->hide_edit();
                $this->emit('refresh_dispatch');
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma informação inserida.',
                'timer' => 2500,
            ]);
            $this->municipio_edit = "";
            $this->hide_edit();
            $this->emit('refresh_dispatch');
        }
    }

    public function mount($service)
    {

        $this->service = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        // if (!session()->isStarted()) { session()->start(); }
        // if (isset($_SESSION['filtro']) && $_SESSION['filtro']) {
        //     if (isset($_SESSION['filtro']['rubrica'])) {
        //         $this->rubrica_s = $_SESSION['filtro']['rubrica'];
        //     }
        //     if (isset($_SESSION['filtro']['city'])) {
        //         $this->city_s = $_SESSION['filtro']['city'];
        //     }
        //     if (isset($_SESSION['filtro']['district'])) {
        //         $this->district_s = $_SESSION['filtro']['district'];
        //     }
        //     if (isset($_SESSION['filtro']['region'])) {
        //         $this->region_s = $_SESSION['filtro']['region'];
        //     }
        // }
    }

    public function export_excel()
    {
        // if (!count($this->selected)) {
        //     ExportSupervisionList::dispatch(
        //         $this->getListsProperty(), // Atenção: pode precisar ser um clone/filtro se for QueryBuilder
        //         $this->service->uuid,
        //         auth()->user()->id
        //     );


        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'success',
        //         'title' => 'Seu relatório está sendo gerado. Você será avisado quando estiver pronto!',
        //         'timer' => 3000,
        //     ]);

        //     return;
        // }

        // ExportSupervisionList::dispatch(
        //     $this->getListsProperty()->find($this->selected), // Atenção: pode precisar ser um clone/filtro se for QueryBuilder
        //     $this->service->uuid,
        //     auth()->user()->id
        // );

        // $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'success',
        //         'title' => 'Seu relatório está sendo gerado. Você será avisado quando estiver pronto!',
        //         'timer' => 3000,
        //     ]);

        // return;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $this->filter = [];
        $sessionFilters = session('filter.' . $this->filter_group);
        if (is_array($sessionFilters)) {
            $this->filter = $sessionFilters;
        } elseif (isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        ExportSupervisionList::dispatch([
            'search'      => $this->search,
            'multiSearch' => $this->multiSearch,
            'rubrica_s'   => $this->rubrica_s,
            'typeNote'    => $this->typeNote,
            'not_assigned' => $this->not_assigned,
            'filter'      => $this->filter,
            'serviceUuid' => $this->service->uuid,
            'user_id'     => auth()->user()->id,
            'filterD5'     => $this->filter_d5,
        ]);

        $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Seu relatório está sendo gerado. Você será avisado quando estiver pronto!',
                'timer' => 3000,
            ]);

        return;
    }


    public function hasPublication(Note $note)
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



    public function hasPublicationCount(Note $note)
    {
        return $note->Productions->where('service_id', $this->service->uuid)->count();
    }

    public function updatedCompanyS()
    {

        $this->user_s = '';
    }

    public function setSelectAll()
    {

        if ($this->selectAll) {

            // Adicionar os IDs que cumprem as regras à lista de selecionados
            foreach ($this->toLists as $item) {
                $id = $item->id;
                if (!in_array($id, $this->selected)) {

                    $production = !$item->Productions->isEmpty() ? $item->Productions()
                                                                    ->where(function ($q) {
                                                                        $q->Where('service_id', $this->service->uuid)
                                                                        ->where('completed', false);
                                                                    })->orWhere(function ($q) use ($item) {
                                                                        if ($item->note_type == 2) {
                                                                            $q->Where('service_id', $this->service->uuid)
                                                                            ->where('dt_note', $item->dt_status);
                                                                        }
                                                                    })->count()
                                                                    : null;

                    if (!$production) {
                        $this->selected[] = $id;
                    }
                }
            }
        } else {
            // Remover os IDs de $selected que estão presentes em $this->lists
            $visibleIds = $this->toLists->pluck('id')->toArray();
            $this->selected = array_filter($this->selected, function ($id) use ($visibleIds) {
                return !in_array($id, $visibleIds);
            });
        }
    }

    public function checkAllSelect($items)
    {

        $items = $items->pluck('id')->toArray();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status' => 'success',
            'menssage' => $msg,
        ]);
    }

    public function filter_save()
    {
        $this->gotoPage(1);
        // session()->put('filtro', $this->rubrica_s);
        if (!session()->isStarted()) { session()->start(); }
        $_SESSION['filtro']['rubrica'] = $this->rubrica_s;
        $_SESSION['filtro']['city'] = $this->city_s;
        $_SESSION['filtro']['district'] = $this->district_s;
        $_SESSION['filtro']['region'] = $this->region_s;
        $this->emit('refresh_service');
    }

    public function filter_clean()
    {
        $this->gotoPage(1);

        $this->rubrica_s = [];
        $this->city_s = [];
        $this->district_s = [];
        $this->region_s = [];

        if (!session()->isStarted()) { session()->start(); }
        if (isset($_SESSION['filtro'])) {
            unset($_SESSION['filtro']);
        }

        $this->emit('refresh_service');
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
                'icon' => 'warning',
                'title' => 'Nenhuma nota foi selecionada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        $this->notes = Note::with('Wpas')->find($this->selected);

        $this->type = '2';

        $this->additionalData = [];

        if ($this->notes->count()) {

            foreach ($this->notes as $index => $wpa) {
                $this->additionalData[$index] = $wpa->Wpas->count() ? (!$wpa->Wpas->last()->production_id ? $wpa->Wpas->last()->dd : '') : '';
            }


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'add_mass_notes'
            ]);
        }
    }

    public function confirm_att()
    {
        if ($this->type === "2") {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer' => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . " da " . (Company::find($this->company_s))->name;
        } else {

            if (!$this->company_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Nenhuma empresa foi selecionada para despacho!',
                    'timer' => 2500,
                ]);

                return;
            }

            $para = (Company::find($this->company_s))->name;
        }

        $partial = Note::whereIn('id', $this->selected)->whereHas('Partials', function ($q) {
            $q->where('allow', true)
                ->where('supervision', false)
                ->where('deny', false);
        })->count();


        if ($partial > 0) {

            $this->dispatchBrowserEvent('alertar', [
                'title' =>  'Confirmar Despachar',
                'msg' => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}. <p class='py-2 my-3 text-bg-danger'> <strong>Atenção:</strong> Existem Notas/OVs Parciais para Fiscalização neste remessa.</strong></p>",
                'icon' => 'warning',
                'btnOktxt' => 'Sim, Despache!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => "confirm_dispatch",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma nenhum usuário foi removido.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title' =>  'Confirmar Despachar',
                'msg' => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
                'icon' => 'warning',
                'btnOktxt' => 'Sim, Despache!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => "confirm_dispatch",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma nenhum usuário foi removido.',

            ]);
        }



    }

    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma entrada para atribuição!',
                'timer' => 5000,
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
        if ($this->type == "2") {



            // Verifica se todas as entradas estão com DD atriobuídas.
            if (count($this->additionalData)) {


                // Checa se existe DD não preenchida
                foreach ($this->additionalData as $key => $value) {
                    if (!trim($value)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon' => 'warning',
                            'title' => 'Todas as Notas/OVs precisam estar associadas a uma Nota DD',
                            'timer' => 5000,
                        ]);

                        return;
                    }
                }


                if (count(array_unique($this->additionalData)) !== count($this->additionalData)) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon' => 'warning',
                        'title' => 'Existem Notas DD repetidas atribuídas a Nota/OVs diferentes',
                        'timer' => 5000,
                    ]);

                    return;
                }

                //Checa se existe DD Repetida


                $dds = Wpa::whereIn('dd', $this->additionalData)->with('Note')->get();

                if ($dds->count()) {

                    foreach ($this->additionalData as $key => $value) {
                        $chk = $dds->where('dd', $value)->first();

                        if ($chk && $chk->Note->note != $this->notes[$key]->note) {
                            $this->dispatchBrowserEvent('swal', [
                                'position' => 'center',
                                'icon' => 'error',
                                'title' => "DD {$value} já foi associada a Nota/OV {$chk->Note->note}",
                                'timer' => 5000,
                            ]);

                            return;
                        }
                    }
                }
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Nenhuma Nota DD associada as Notas/OVs!',
                    'timer' => 5000,
                ]);

                return;
            }
        }

        DB::beginTransaction();

        $erros = [];

        if ($this->type == "2") {

            foreach ($this->notes as $key => $note) {

                $block = $this->needBlock($note);

                $partial = (bool) $block['isPartial'];


                if ($note->FiveNote && $note->FiveNote->is_completed && !$note->FiveNote->is_supervisioned) {
                    $dfive = true;
                } else {
                    $dfive = false;
                }





                if ($erro = $block['command']) {
                    $production = Production::create([
                        'note_id' => $note->id,
                        'service_id' => $this->service->uuid,
                        'user_id' => $this->user_s,
                        'company_id' => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'att_by' => Auth()->User()->id,
                        'dt_note' => $note->dt_status,
                        'status_note' => $note->nstats,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'att_at' => date('Y-m-d H:i:s'),
                        'status' => 2,
                        'centroTrab' => $note->centerjob,
                        'partial' => $partial,
                        'dfive' => $dfive,
                    ]);

                    $user = Auth()->User()->name;

                    if (trim($this->user_s)) {
                        $user_info = "Atribuiu a NOTA/OV para: " . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                    } else {
                        $user_info = "Despachou a NOTA/OV para:" . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                    }

                    if ($production) {
                        Notetimeline::Create([
                            'note_id' => $production->id,
                            'service_id' => $production->service_id,
                            'user_id' => Auth()->User()->id,
                            'info' => "Usuário {$user} {$user_info}",
                            'status' => 2,
                            'productionId' => $production->id,
                        ]);

                        if ($production->user_id && $note->FiveNote) {
                            $note->FiveNote->productions()->syncWithoutDetaching([$production->id]);

                            app(D5WorkflowService::class)->onProductionAssigned(
                                $note->FiveNote,
                                $production,
                                auth()->id(),
                                null
                            );
                        }
                    }


                    if ($production) {

                        $wpa = Wpa::where('note_id', $note->id)->where('dd', $this->additionalData[$key])->whereNull('production_id')->first();

                        if ($wpa) {
                            $wpa->update([
                                'production_id' => $production->id,
                            ]);
                        } else {
                            Wpa::create([
                                'production_id' => $production->id,
                                'note_id' => $note->id,
                                'dd' => $this->additionalData[$key]
                            ]);
                        }
                    }
                } else {
                    $erros[] = $erro;
                    $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Existe uma atividade em andamento para uma ou mais Notas/Ovs',
                    'timer' => 5000,
                ]);

                    return;
                }
            }
        } else {
            foreach ($this->notes as $key => $note) {

                $block = $this->needBlock($note);

                $partial = (bool) $block['isPartial'];

                if ($note->FiveNote && $note->FiveNote->is_completed && !$note->FiveNote->is_supervisioned) {
                    $dfive = true;
                } else {
                    $dfive = false;
                }


                if ($erro = $block['command']) {
                    $production = Production::create([
                        'note_id' => $note->id,
                        'service_id' => $this->service->uuid,
                        'company_id' => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'dt_note' => $note->dt_status,
                        'status_note' => $note->nstats,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'status' => 1,
                        'centroTrab' => $note->centerjob,
                        'partial' => $partial,
                        'dfive' => $dfive,
                    ]);

                    $user = Auth()->User()->name;

                    if (trim($this->user_s)) {
                        $user_info = "Atribuiu a NOTA/OV para: " . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                    } else {
                        $user_info = "Despachou a NOTA/OV para:" . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                    }

                    if ($production) {
                        Notetimeline::Create([
                            'note_id' => $production->id,
                            'service_id' => $production->service_id,
                            'user_id' => Auth()->User()->id,
                            'info' => "Usuário {$user} {$user_info}",
                            'status' => 1,
                            'productionId' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;

                    $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Existe uma atividade em andamento para uma ou mais Notas/Ovs',
                    'timer' => 5000,
                ]);

                    return;
                }
            }
        }

        if ($erros) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'OOPS!, Ocorreram erros ao atribuir as Notas/OVs',
                'timer' => 2500,
            ]);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Notas Despachadas com sucesso!',
            'timer' => 2500,
        ]);

        DB::commit();

        $this->closeall();
        $this->emit('refresh_dispatch');
    }



    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');


        $this->company_s = "";
        $this->selected = [];
        $this->user_s = "";
        // $this->type = "";
        $this->additionalData = [];

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {

        $this->company_s = "";
        $this->enter_dd = "";
        $this->user_s = "";
        // $this->type = "";
        $this->additionalData = [];
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
                'icon' => 'warning',
                'title' => 'Nenhum entrada para atribuição',
                'timer' => 5000,
            ]);

            return;
        }

        $additionalData = [];
        $additionalDataUpd = [];

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {
            $count = 0;
            $ok = 0;

            foreach ($linhas as $linha) {

                if ($linha) {
                    $coluna = explode("\t", $linha);

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(";", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(" ", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(",", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon' => 'warning',
                            'title' => "Gentileza separar os valores com alguma forma válida: ' ', ';', ','.",

                        ]);

                        return;
                    }



                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $dd = Wpa::Where('dd', trim($coluna[1]))->first();

                        if ($dd && !$dd->production_id) {

                            $note = Note::find($dd->note_id);

                            if ($note->note != trim($coluna[0])) {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon' => 'warning',
                                    'title' => "A DD {$dd->dd} já foi atribuída a Nota: {$note->note}. (Nenhuma Nota foi associada. Verifique novamente.)",

                                ]);

                                return;
                            }
                        } elseif ($dd && $dd->production_id) {

                            $production = Production::with('Note', 'User')->find($dd->production_id);

                            if ($production) {

                                $name_user = isset($production->User) ? $production->User->name : 'Desconhecido';

                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon' => 'warning',
                                    'title' => "A DD {$dd->dd} já foi atribuída para a nota {$production->Note->note} despachada para {$name_user}",

                                ]);
                            } else {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon' => 'error',
                                    'title' => "A DD {$dd->dd} parece que ja foi despachada anteriormente, porém não encontrei relação de produção. Informe ao ADM.",

                                ]);
                            }

                            return;
                        }

                        $dd = Wpa::WhereRelation('Note', 'note', trim($coluna[0]))->whereNull('production_id')->first();
                        $note = Note::where('note', trim($coluna[0]))->first();

                        if (!$dd && $note) {



                            if (count($additionalData)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalData, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada. <br> Revise novamente as DDs a serem cadastradas.<br> Nenhuma DD foi associada.",

                                    ]);

                                    return;
                                }
                            }

                            if (count($additionalDataUpd)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalDataUpd, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;
                                }
                            }




                            $additionalData[] = [
                                'note_id' => $note->id,
                                'dd' => trim($coluna[1]),
                            ];

                            $ok++;
                        }

                        if (($dd && $note) && ($dd->dd != trim($coluna[1]))) {

                            if (count($additionalData)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalData, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;
                                }
                            }

                            if (count($additionalDataUpd)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalDataUpd, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;
                                }
                            }

                            $additionalDataUpd[] = [
                                'id' => $dd->id,
                                'dd' => $coluna[1],
                            ];

                            $ok++;
                        }
                    } else {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon' => 'warning',
                            'title' => "Existem Notas/OV ou DD com caracteres inválidos (SOMENTE NÚMEROS SÃO PERMITIDOS). Confira novamente.",

                        ]);

                        return;
                    }
                }
            }

            if (count($additionalData) || count($additionalDataUpd)) {

                $count = count($additionalData) + count($additionalDataUpd);

                $this->additionalData = $additionalData;
                $this->additionalDataUpd = $additionalDataUpd;

                $this->dispatchBrowserEvent('alertar', [
                    'title' =>  'Confirmar Atribuir DD?',
                    'msg' => "Você está prestes a atribuir {$count} de {$ok} notas DD, Deseja Continuar?",
                    'icon' => 'info',
                    'btnOktxt' => 'Sim, Continue!',
                    'btnCanceltxt' => 'Não, Cancele',
                    'action' => "confirm_mass_dd",
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg' => 'Nenhuma Nota Atribuída.',

                ]);
            }
        }
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
        $totalCount = $count + $countUpd;

        if (!$totalErrors) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Notas DDs associadas com sucesso',
                'timer' => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => "OOPS!, Ocorreram {$totalErrors} de {$totalCount} ao associar as DD às Notas.",
                'timer' => 8000,
            ]);
        }
    }

    public function filterStatus()
    {
        if ($this->not_assigned) {
            $this->not_assigned = false;
        } else {
            $this->not_assigned = true;
        }
    }

    public function filterD5()
    {
        $this->filter_d5 = !$this->filter_d5;
    }


    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $this->filter = [];
        $sessionFilters = session('filter.' . $this->filter_group);
        if (is_array($sessionFilters)) {
            $this->filter = $sessionFilters;
        } elseif (isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        // if ($this->filter) {
        //     dd($this->filter);
        // }


        $query = $this->supervisionRepository->getBaseQuery();


        if (strlen($this->search)) {


            $query->where(function ($q) {
                return $q->where('note', 'like', '%' . trim($this->search) . '%')
                    ->orWhere('material', 'like', '%' . trim($this->search) . '%')
                    ->orWhere('numPedido', 'like', '%' . trim($this->search) . '%')
                    ->orWhere('group1', 'like', '%' . trim($this->search) . '%')
                    ->orWhere('group2', 'like', '%' . trim($this->search) . '%')
                    ->orWhere('group4', 'like', '%' . trim($this->search) . '%')
                    ->orWhere('group5', 'like', '%' . trim($this->search) . '%')
                    ->orWhereRelation('Orders', 'ordem', 'like', '%' . trim($this->search) . '%');
            });
        }

        if (count($this->multiSearch)) {
            $this->gotoPage(1);
            $this->search = "";

            $query->where(function ($q) {
                return $q->WhereIn('note', $this->multiSearch)
                    ->orWhereRelation('Orders', function ($q) {
                        $q->WhereIn('ordem', $this->multiSearch);
                    });
            });
        }

        if (count($this->rubrica_s)) {
            $query->where(function ($q) {
                return $q->whereIn('rubrica', $this->rubrica_s)
                    ->orWhereNull('rubrica');
            });
        }

        if ($this->typeNote) {
            $query->where(function ($q) {
                return $q->where('type_note', $this->typeNote)
                    ->orWhereNull('type_note');
            });
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

        if ($this->filter_d5) {
            $query->where(function ($q) {
                $q->whereHas('FiveNote');
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['city'])) {

            $query->whereRelation('City', function ($q) {
                $q->whereIn('rdMunicipio', $this->filter['city']);
            });
        }



        $query->with(['orders' => function ($q) {
            $q->where('statusSist', 'not like', 'ENT%')->where('statusSist', 'not like', 'ENC%');
        },'Productions.User', 'Wpas', 'Partials', 'TempAdsInfos', 'OldAds', 'FiveNote'])
            ->select('notes.*', 'work_reports.created_at as work_dt_created')
            ->orderBy('work_dt_created', 'ASC')
            ->orderBy('id', 'ASC');

        return $query;
    }

    // public function getBaseProperty()
    // {
    //     try {
    //         $query = City::query();
    //         $filtersApplied = false;

    //         if (!empty($this->region_s)) {
    //             $query->whereIn('regiao', $this->region_s);
    //             $filtersApplied = true;
    //         }

    //         if (!empty($this->district_s)) {
    //             $query->whereIn('baseConstrucao', $this->district_s);
    //             $filtersApplied = true;
    //         }

    //         if (!empty($this->city_s)) {
    //             $query->whereIn('cidade', $this->city_s);
    //             $filtersApplied = true;
    //         }

    //         if (!$filtersApplied) {
    //             return [];
    //         }

    //         $result = $query->orderBy('cidade')
    //             ->get()
    //             ->pluck('rdMunicipio')
    //             ->toArray();

    //         return $result;
    //     } catch (\Throwable $th) {
    //         return [];
    //     }
    // }

    public function getToListsProperty()
    {
        return $this->lists->paginate($this->perPage);
    }


    public function render()
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
                 ->orWhereRelation('Employee.Contract.company', 'id', $this->company_s);
         })
        ->when($this->search_user, function ($q) {
            return $q->where('name', 'like', '%' . $this->search_user . '%');
        })
        ->orderBy('name', 'ASC')->get();


        return view('livewire.dispatchs.supervision.main', [
            'lists' => $this->toLists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first()
        ]);
    }
}
