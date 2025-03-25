<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Exports\{ExportDDExcel, ProductionControlExport};
use App\Exports\Dispatchs\SurveyProductionExport;
use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\{Analise, Company, Note, Notetimeline, Production, Service, User, Wpa};
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Stack extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    // VAr System
    public $service;

    public $last_update;

    public $search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $perPage = 100;

    public $advanceSearch;

    public $multiSearch = [];

    public $note;

    public $notes;

    public $enter_dd;

    private $filteredLists;

    public $priority;

    public $status_l;

    public $status_s = [];

    public $selectall;

    public $selected = [];

    public $company_l;

    public $company_s;

    public $company_fs = [];

    public $user_l;

    public $user_s;

    public $user_fl;

    public $user_fs = [];

    public $type = '2';

    public $additionalData = [];

    // Filtros Municípios
    public $region_l;

    public $region_s = [];

    public $district_l;

    public $district_s = [];

    public $city_l;

    public $city_s = [];

    public $note_type = '';

    public $force = true;

    public $forcar = false;

    public $delete;

    public $production;

    public $productions;

    public $existDD = [];





    private $filter_group = 'control_survey';
    private $filter;

    protected $queryString = [
        'note_type' => ['except' => '', 'as' => 'tipo'],
        'search' => ['except' => '', 'as' => 'busca'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        // 'confirm_add_priority' => 'give_priority',
        // 'confirm_rem_priority' => 'remove_priority',
        // 'confirm_delete' => "confirm_delete",
        'confirm_remove_att'   => 'remove_att',
        'confirm_dispatch'     => 'confirmed_att',
        'getCopy'              => 'copy',
        'confirm_mass_dd'      => 'confirmed_mass_dd',
        'confirm_des_att_mass' => 'confirm_des_att_mass',
        'filterUser'           => 'filterUser',
        'closeall'             => 'closeall',
    ];



    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;
        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->orderBy('name', 'ASC')
            ->get();


    }

    public function updatedCompanyS()
    {
        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
         ->where(function ($q) {
             $q->whereRelation('Company', 'company_id', $this->company_s)
                 ->orWhereRelation('Employee.Contract.company', 'id', $this->company_s);
         })
        ->orderBy('name', 'ASC')->get();
    }

    public function filterUser($user_id)
    {

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }


        // Inicializa o array se não estiver definido
        if (!isset($_SESSION['filter'][$this->filter_group]['user'])) {
            $_SESSION['filter'][$this->filter_group]['user'][] = $user_id;
        } else {
            $_SESSION['filter'][$this->filter_group]['user'] = [];
            $_SESSION['filter'][$this->filter_group]['user'][] = $user_id;
        }

        $this->emit('toUpdate', 'user');


    }

    public function updatedSearch()
    {
        if (!trim($this->search)) {
            $this->advanceSearch = '';
            $this->multiSearch   = [];
            $this->goToPage(1);
        }
    }

    public function aplicar()
    {

    }

    public function setSelectall()
    {

        // Força o recálculo da propriedade computada para atualizar $this->filteredLists


        // Obter todos os IDs da lista filtrada


        if (!$this->selectall) {
            $this->lists;
            $idsToKeep = $this->filteredLists->get()->pluck('id')->toArray();
            // Adiciona os IDs que ainda não estão selecionados
            foreach ($idsToKeep as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            // Remove os IDs que pertencem à lista filtrada dos selecionados
            $this->selected = [];
        }
    }

    public function checkSelectAll($lists)
    {
        $ids = $lists->pluck('id')->toArray();
        $this->selectall = empty(array_diff($ids, $this->selected));
    }

    public function export_excel()
    {
        $this->lists;



        return (new SurveyProductionExport($this->filteredLists, $this->service->uuid, $this->selected))->download(date('YmdHis-') . 'controle_de_producao.xlsx');


    }

    public function buscarMulti()
    {

        if ($this->advanceSearch) {


            $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
        } else {
            $this->multiSearch = [];
        }

        if (count($this->multiSearch)) {
            $this->search = '';
            $this->goToPage(1);
            $this->closeall();
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }



    // public function filter_clean()
    // {
    //     $this->gotoPage(1);

    //     $this->rubrica_s  = [];
    //     $this->city_s     = [];
    //     $this->district_s = [];
    //     $this->region_s   = [];
    //     $this->status_s   = [];
    //     $this->company_fs = [];
    //     $this->user_fs    = [];

    //     $this->multiSearch = [];

    //     if (!(session_status() == PHP_SESSION_ACTIVE)) {
    //         session_start();
    //     }

    //     if (isset($_SESSION['filtro'])) {
    //         unset($_SESSION['filtro']);
    //     }

    //     $this->emit('refresh_service');
    // }

    public function give_priority()
    {
        if ($this->priority->update(['priority' => true])) {
            $this->emit('refresh_list');
            unset($this->priority);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Nota Priorizada com Sucesso',
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao tentar priorizar a nota/ov',
                'timer'    => 2500,
            ]);
        }
    }

    public function ask_priority($production_id)
    {
        $this->priority = Production::with('Note')->find($production_id);

        if ($this->priority) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Priorizar',
                'msg'           => "Você deseja priorizar <strong>{$this->priority->Note->note}</strong>?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, priorize!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_add_priority',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhuma priorização foi definida.',
            ]);
        }
    }

    public function remove_priority()
    {
        if ($this->priority->update(['priority' => false])) {
            $this->emit('refresh_list');
            unset($this->priority);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Nota prioridade removida com Sucesso',
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao tentar remover prioridade a nota/ov',
                'timer'    => 2500,
            ]);
        }
    }

    public function ask_despriority($production_id)
    {
        $this->priority = Production::with('Note')->find($production_id);

        if ($this->priority) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Remover Prioridade',
                'msg'           => "Você deseja remover prioridade <strong>{$this->priority->Note->note}</strong>?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, despriorize!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_rem_priority',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhuma despriorização foi definida.',
            ]);
        }
    }

    public function get_single_note($prod)
    {

        $this->selected = [$prod];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {

        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para atribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->productions = Production::find($this->selected);

        $this->notes = Note::whereHas('Productions', function ($query) {
            return $query->whereIn('id', $this->selected);
        })->get();

        if ($this->notes->count()) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'add_mass_notes',
            ]);
        }
    }

    public function confirm_att()
    {
        if ($this->type == '2') {

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
            'title'         => 'Confirmar Atribuir',
            'msg'           => "Você está prestes a Atribuir {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function mass_modal()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum entrada para atribuição',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->additionalData = [];
        $this->existDD        = [];

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {
            $count = 0;
            $ok    = 0;

            foreach ($linhas as $linha) {
                if ($linha) {
                    $coluna = explode("\t", $linha);

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(';', $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(' ', $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(',', $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'warning',
                            'title'    => "Gentileza separar os valores com alguma forma válida: ' ', ';', ','.",

                        ]);

                        return;
                    }

                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $dd = Production::where('completed', false)->where('service_id', $this->service->uuid)->whereRelation('Note', 'note', trim($coluna[0]))->first();

                        if ($dd) {

                            $chk = Wpa::Where('dd', trim($coluna[1]))->first();

                            if ($chk && $chk->note_id != $dd->note_id) {
                                $count++;
                                $this->existDD[] = [
                                    'dd'   => $coluna[1],
                                    'note' => $chk->load('Note')->Note->note,
                                ];
                            }

                            $ok++;

                            $jaExiste = collect($this->additionalData)->contains('dd', $coluna[1]);

                            if (!$jaExiste) {
                                // Adiciona os dados se o valor não existir
                                $this->additionalData[] = [
                                    'production_id' => $dd->id,
                                    'note_id'       => $dd->note_id,
                                    'dd'            => $coluna[1],
                                ];
                            } else {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon'     => 'warning',
                                    'title'    => 'NOTA DD REPETIDA',
                                    'html'     => "A Nota DD <strong>{$coluna[1]}</strong> está sendo repetida para mais de uma Nota/OV. Gentileza verificar.",

                                ]);

                                return;
                            }

                        }
                    }

                }

            }

            if ($count) {
                // $this->dispatchBrowserEvent('alertar', [
                //     'title' =>  'Confirmar Atribuir DD a notas DIFERENTES?',
                //     'msg' => "Existem {$count} Notas DD que estão sendo atribuídas a notas diferentes às já atribuídas anteriormente.",
                //     'icon' => 'warning',
                //     'btnOktxt' => 'Sim, Atribua!',
                //     'btnCanceltxt' => 'Não, Cancele',
                //     'action' => "confirm_mass_dd",
                //     'cancel_titulo' => 'Cancelado!',
                //     'cancel_msg' => 'Nenhuma Nota Atribuída.',

                // ]);

                $text = '';

                foreach ($this->existDD as $dd_exist) {
                    $text .= '<strong>' . $dd_exist['dd'] . '</strong> => <strong>' . $dd_exist['note'] . '</strong>.<br>';
                }

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'DD EXISTENTE',
                    'html'     => "Você está tentando atribuir {$count} notas DD já atribuídas a Notas diferentes.<br>" . $text,
                ]);

                return;
            } else {

                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Atribuir DD?',
                    'msg'           => "Você está prestes a atribuir {$ok} notas DD, Deseja Continuar?",
                    'icon'          => 'info',
                    'btnOktxt'      => 'Sim, Continue!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirm_mass_dd',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhuma Nota Atribuída.',

                ]);
            }

        }
    }

    public function go_des_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para desatribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->productions = Production::with('Note')->find($this->selected);

        $notes_not_valids = 0;

        if ($this->productions) {
            foreach ($this->productions as $production) {
                if (($production->status > 2 && !$this->forcar) || $production->completed) {
                    $notes_not_valids++;
                }
            }
        }

        if ($notes_not_valids > 0) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição Parcial',
                'msg'           => "{$notes_not_valids} das Das {$this->productions->count()} selecionadas, não atende(m) o critério para Desatribuição. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Desatribua!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi Desatribuída.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição em Massa',
                'msg'           => "{$this->productions->count()} NOTAS/OVs estão prontas para serem desatribuídas. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Desatribua!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi Desatribuída.',

            ]);
        }

    }

    public function confirm_des_att_mass()
    {
        $erros = 0;
        $total = 0;

        if ($this->productions) {

            foreach ($this->productions as $production) {
                if (($production->status <= 2 || $this->forcar) && !$production->completed) {
                    $total++;

                    if ($analise = Analise::Where('production_id', $production->id)->first()) {
                        $analise->delete();
                    }

                    if ($wpa = Wpa::Where('production_id', $production->id)->get()->last()) {
                        $wpa->update(['production_id' => null]);
                    }

                    if (!$production->delete()) {
                        $erros++;
                    }
                }
            }

            if ($erros) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => "{$erros} de {$total} não foram desatribuídos.",
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => "{$total} Notas/Ovs Desatribídas com sucesso",
                    'timer'    => 2500,
                ]);
            }

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum registro de desatribuição. Repita o procedimento.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function confirmed_mass_dd()
    {

        if ($count = count($this->additionalData)) {
            $error = 0;

            foreach ($this->additionalData as $wpa) {
                if (!Wpa::Create($wpa)) {
                    $error++;
                }
            }

            if (!$error) {
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
                    'title'    => "OOPS!, Ocorreram {$error} de {$count} ao associar as DD às Notas.",
                    'timer'    => 8000,
                ]);
            }

        }
    }

    public function confirmed_att()
    {

        if ($this->type == '2') {

            // Verifica se todas as entradas estão com DD atriobuídas.
            if (count($this->additionalData)) {

                // Checa se existe DD não preenchida
                foreach ($this->additionalData as $key => $value) {
                    if (!trim($value)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'warning',
                            'title'    => 'Todas as Notas/OVs precisam estar associadas a uma Nota DD',
                            'timer'    => 5000,
                        ]);

                        return;
                    }
                }

                if (count(array_unique($this->additionalData)) !== count($this->additionalData)) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Existem Notas DD repetidas atribuídas a Nota/OVs diferentes',
                        'timer'    => 5000,
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
                                'icon'     => 'error',
                                'title'    => "DD {$value} já foi associada a uma outra Nota/OV",
                                'timer'    => 5000,
                            ]);

                            return;
                        }
                    }
                }

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhuma Nota DD associada as Notas/OVs!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }

        if ($this->type == '2') {

            foreach ($this->notes as $key => $note) {

                // $production = Production::create([
                //     'note_id' => $note->id,
                //     'service_id' => $this->service->uuid,
                //     'user_id' => $this->user_s,
                //     'company_id' => $this->company_s,
                //     'dispatch_by' => Auth()->User()->id,
                //     'att_by' => Auth()->User()->id,
                //     'dt_note' => $note->dt_status,
                //     'status_note' => $note->nstats,
                //     'dispatch_at' => date('Y-m-d H:i:s'),
                //     'att_at' => date('Y-m-d H:i:s'),
                //     'status' => 2,
                // ]);

                $production = $this->productions->where('note_id', $note->id)->first();

                if ($production) {

                    // $update = Production::find($production->id);

                    // dd($update);

                    if ($production->update([
                        'user_id'    => $this->user_s,
                        'company_id' => $this->company_s,
                        'att_by'     => Auth()->User()->id,
                        'att_at'     => date('Y-m-d H:i:s'),
                        'status'     => 2,
                        'block'      => false,
                    ])) {

                        $user = Auth()->User()->name;

                        if (trim($this->user_s)) {
                            $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                        } else {
                            $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                        }

                        if ($production) {
                            Notetimeline::Create([
                                'note_id'      => $production->id,
                                'service_id'   => $production->service_id,
                                'user_id'      => Auth()->User()->id,
                                'info'         => "Usuário {$user} {$user_info}",
                                'status'       => 2,
                                'productionId' => $production->id,
                            ]);
                        }

                        Wpa::create([
                            'production_id' => $production->id,
                            'note_id'       => $note->id,
                            'dd'            => $this->additionalData[$key],
                        ]);
                    } else {

                        // dd($production);

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Erro ao atribuir as notas!',
                            'timer'    => 2500,
                        ]);

                        return;
                    }

                } else {
                    dd($production, $note->note);

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'Erro ao atribuir as notas!',
                        'timer'    => 2500,
                    ]);

                    return;
                }
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas Despachadas com sucesso!',
            'timer'    => 2500,
        ]);

        $this->closeall();
    }

    /**
     * Inserir as DDs ás notas em massa
     *
     * @return void
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



    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Production::query()->with(['Note'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('confirmed', false)
            ->where('service_id', $this->service->uuid);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('Note', function ($subquery) {
                    $subquery->where('note', 'like', '%' . $this->search . '%')
                        ->orWhere('group2', 'like', '%' . $this->search . '%')
                        ->orWhere('group3', 'like', '%' . $this->search . '%')
                        ->orWhere('group4', 'like', '%' . $this->search . '%')
                        ->orWhere('group5', 'like', '%' . $this->search . '%')
                        ->orWhere('numPedido', 'like', '%' . $this->search . '%')
                        ->orWhere('material', 'like', '%' . $this->search . '%')
                        ->orWhere('lexp', 'like', '%' . $this->search . '%')
                        ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                        ->orWhere('centerjob', 'like', '%' . $this->search . '%');
                });
            });
        }

        if (Auth()->User()->contract) {
            $query->where('company_id', Auth()->User()->Employee->Contract->company_id)
                  ->orWhereNull('company_id');
        }

        if (isset($this->filter['user']) && $this->filter['user']) {
            $query->whereIn('user_id', $this->filter['user']);
        }

        if ($this->multiSearch) {
            $query->whereHas('Note', function ($q) {
                $q->whereIn('note', $this->multiSearch);
            });
        }

        if (isset($this->filter['rubrica']) && $this->filter['rubrica']) {
            $query->whereRelation('Note', function ($sq) {
                $sq->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city']) && $this->filter['city']) {
            $query->whereRelation('Note', function ($sq) {
                $sq->whereIn('lexp', $this->filter['city']);
            });
        }

        if (count($this->status_s)) {
            $query->where(function ($q) {
                $q->whereIn('productions.status', $this->status_s)
                  ->orWhereNull('productions.status');
            });
        }

        if ($this->note_type) {
            $query->whereRelation('Note', function ($q) {
                $q->where('type_note', $this->note_type)
                  ->orWhereNull('type_note');
            });
        }

        $query->orderBy('productions.priority', 'DESC')
              ->orderBy('notes.type_note', 'DESC')
              ->orderBy('notes.days_left', 'asc')
              ->select('productions.*', 'notes.dt_created as note_dt_created');

        $this->filteredLists = (clone $query);


        return $query->paginate($this->perPage);
    }

    public function getStatusProperty()
    {
        return Production::select('status', DB::raw('COUNT(*) as total'))
            ->where('confirmed', false)
            ->where('service_id', $this->service->uuid)
            ->orderBy('status')
            ->groupBy('status')
            ->get();
    }



    public function filterStatus($status)
    {
        if ($status) {
            $this->status_s   = [];
            $this->status_s[] = $status;
        }
    }



    public function closeall()
    {

        $this->emit('refresh_list');

        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->user_s         = '';
        $this->additionalData = [];
        $this->enter_dd       = '';

    }

    public function clean()
    {

        $this->company_s = '';
        $this->enter_dd  = '';
        $this->user_s    = '';

        $this->additionalData = [];
    }





    public function render()
    {


        return view('livewire.dispatchs.survey.stack', [
            'status' => $this->status,
            'lists'   => $this->lists,
        ]);
    }
}
