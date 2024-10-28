<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Exports\{ExportDDExcel, ProductionControlExport};
use App\Models\Edp_depc\City;
use App\Models\{Analise, Company, Note, Notetimeline, Production, Service, User, Wpa};
use Livewire\{Component, WithPagination};

class Stack extends Component
{
    use WithPagination;

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

    public $filteredLists;

    public $priority;

    public $status_l;

    public $status_s = [];

    public $selectAll;

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
    }

    public function filterUser($user_id)
    {
        $this->user_fs = [$user_id];
    }

    public function setSelectAll()
    {

        $idsToKeep = $this->lists->pluck('id')->toArray();

        if ($this->selectAll) {
            // Adicionar os IDs ausentes de $selected
            foreach ($idsToKeep as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $idsToKeep)) {
                    $newSelected[] = $id;
                }
            }
            $this->selected = $newSelected;
        }

    }

    public function checkAllSelect($items)
    {

        $items = $items->pluck('id')->toArray();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
    }


    public function export_excel()
    {
        // if (!count($this->selected)) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'warning',
        //         'title' => 'Nenhuma nota foi selecionada para Exportar!',
        //         'timer' => 2500,
        //     ]);

        //     return;
        // }

        // $notes = Note::whereHas('Productions', function ($query) {
        //     return $query->whereIn('id', $this->selected);
        // });

        // return (new ExportDDExcel())->exportDD($notes->pluck('id')->toArray(), $this->service->service)->download(date('YmdHis-').'exportProdNote.xlsx');

        if (!count($this->selected)) {
            return (new ProductionControlExport($this->status->get()))->download(date('YmdHis-') . 'controle_de_producao.xlsx');
        } else {
            return (new ProductionControlExport($this->status->find($this->selected)))->download(date('YmdHis-') . 'controle_de_producao.xlsx');
        }
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

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function filter_save()
    {
        $this->gotoPage(1);

        // session()->put('filtro', $this->rubrica_s);
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }
        $_SESSION['filtro']['rubrica']  = $this->rubrica_s;
        $_SESSION['filtro']['city']     = $this->city_s;
        $_SESSION['filtro']['district'] = $this->district_s;
        $_SESSION['filtro']['region']   = $this->region_s;
        $_SESSION['filtro']['user']     = $this->user_fs;
        $_SESSION['filtro']['company']  = $this->company_fs;
        $this->emit('refresh_service');
    }

    public function filter_clean()
    {
        $this->gotoPage(1);

        $this->rubrica_s  = [];
        $this->city_s     = [];
        $this->district_s = [];
        $this->region_s   = [];
        $this->status_s   = [];
        $this->company_fs = [];
        $this->user_fs    = [];

        $this->multiSearch = [];

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filtro'])) {
            unset($_SESSION['filtro']);
        }

        $this->emit('refresh_service');
    }

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

    public function to_remove_add($id)
    {
        $this->production = Production::with('User')->find($id);

        if ($this->production) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição',
                'msg'           => "Você está prestes a Desatribuir a produção para {$this->production->User->name}. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_remove_att',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        }
    }

    public function remove_att()
    {
        if ($this->production->update(['user_id' => '', 'status' => 1])) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Produção foi desatribuída com sucesso',
                'timer'    => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                'timer'    => 6000,
            ]);

            $this->closeall();
        }
    }

    public function getListsProperty()
    {
        return Production::with(['Note'])
        ->join('notes', 'productions.note_id', '=', 'notes.id')
        ->where('confirmed', false)
        ->where('service_id', $this->service->uuid)
        ->when($this->search, function ($q) {
            return $q->where(function ($query) {
                $query->whereHas('Note', function ($subquery) {
                    return $subquery->where('note', 'like', '%' . $this->search . '%')
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
        })
        ->when(Auth()->User()->contract, function ($q) {
            return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
        })
        ->when($this->company_fs, function ($q) {
            return $q->whereIn('company_id', $this->company_fs);
        })
        ->when($this->user_fs, function ($q) {
            return $q->whereIn('user_id', $this->user_fs);
        })
        ->when($this->rubrica_s, function ($q) {
            return $q->whereHas('Note', function ($query) {
                $query->whereIn('rubrica', $this->rubrica_s);
            });
        })
        ->when($this->base, function ($q) {
            return $q->whereHas('Note', function ($query) {
                return $query->whereIn('nexp', $this->base)
                    ->orwhere('nexp', null)
                    ->orwhere('nexp', '');
            });
        })
        ->when($this->multiSearch, function ($q) {
            return $q->whereHas('Note', function ($query) {
                return $query->whereIn('note', $this->multiSearch);
            });
        })
        ->when($this->status_s, function ($q) {
            return $q->whereIn('productions.status', $this->status_s);
        })
        ->when($this->note_type, function ($q) {
            return $q->whereRelation('Note', 'type_note', $this->note_type);
        })
        ->orderBy('priority', 'DESC')
        ->orderBy('d5', 'DESC')
        ->orderBy('notes.type_note', 'DESC')
        ->orderBy('notes.days_left', 'asc')
        ->select('productions.*', 'notes.dt_created as note_dt_created')
        ->paginate($this->perPage);

    }

    public function getStatusProperty()
    {

        return Production::with(['Note'])
        ->join('notes', 'productions.note_id', '=', 'notes.id')
        ->where('confirmed', false)
        ->where('service_id', $this->service->uuid)
        ->when($this->search, function ($q) {
            return $q->where(function ($query) {
                $query->whereHas('Note', function ($subquery) {
                    return $subquery->where('note', 'like', '%' . $this->search . '%')
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
        })
        ->when(Auth()->User()->contract, function ($q) {
            return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
        })
        ->when($this->company_fs, function ($q) {
            return $q->whereIn('company_id', $this->company_fs);
        })
        ->when($this->user_fs, function ($q) {
            return $q->whereIn('user_id', $this->user_fs);
        })
        ->when($this->rubrica_s, function ($q) {
            return $q->whereHas('Note', function ($query) {
                $query->whereIn('rubrica', $this->rubrica_s);
            });
        })
        ->when($this->base, function ($q) {
            return $q->whereHas('Note', function ($query) {
                return $query->whereIn('nexp', $this->base)
                    ->orwhere('nexp', null)
                    ->orwhere('nexp', '');
            });
        })
        ->when($this->multiSearch, function ($q) {
            return $q->whereHas('Note', function ($query) {
                return $query->whereIn('note', $this->multiSearch);
            });
        })
        ->when($this->status_s, function ($q) {
            return $q->whereIn('productions.status', $this->status_s);
        })
        ->when($this->note_type, function ($q) {
            return $q->whereRelation('Note', 'type_note', $this->note_type);
        })
        ->orderBy('priority', 'DESC')
        ->orderBy('d5', 'DESC')
        ->orderBy('notes.type_note', 'DESC')
        ->orderBy('notes.days_left', 'asc')
        ->select('productions.*', 'notes.dt_created as note_dt_created'); // Seleciona a coluna 'dt_created' da tabela 'Note' com um alias 'note_dt_created'

    }

    public function filterStatus($status)
    {
        if ($status) {
            $this->status_s   = [];
            $this->status_s[] = $status;
        }
    }

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

            $result = $query->orderBy('cidade')
                ->get()
                ->pluck('rdMunicipio')
                ->toArray();

            return $result;
        } catch (\Throwable $th) {
            return [];
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

    public function to_delete($id)
    {

        $this->delete = '';

        $this->delete = Production::with('Note')->find($id);

        if ($this->delete) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Deletar Produção',
                'msg'   => "Você está prestes a removert {$this->delete->Note->note} da produção. Esteja ciente ao fazer isso de forma inadequada poderá prejudicar a medição do usuário ou empresa.
                Lembrando que a exclusão também removerá do LOG do BI. \n Deseja Continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_delete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nao encontramos a produção para remover!',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function confirm_delete()
    {
        if ($this->delete->delete()) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Produção removida com sucesso',
                'timer'    => 2500,
            ]);

            $this->closeall();
        } else {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                'timer'    => 6000,
            ]);

            $this->closeall();
        }
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
        ->when($this->company_s, function ($q) {
            return $q->where(function ($q) {
                $q->whereRelation('Company', 'company_id', $this->company_s)
                    ->orWhereRelation('Employee.Contract.company', 'id', $this->company_s);
            });

        })
        // ->when($this->search_user, function ($q) {
        //     return $q->where('name', 'like', '%' . $this->search_user . '%');
        // })
        ->orderBy('name', 'ASC')->get();




        $this->user_fl = Production::where('service_id', $this->service->uuid)
            ->when(Auth()->user()->contract, function ($q) {
                return $q->where('company_id', Auth()->user()->employee->contract->company_id);
            })
            ->when($this->company_fs, function ($q) {
                return $q->whereIn('company_id', $this->company_fs);
            })
            ->select('user_id')
            ->with('User')
            ->groupBy('user_id')
            ->get();

        $this->status_l = $this->lists->pluck('status')->unique();

        // $this->user_l = User::whereRelation('Employee.Contract', 'company_id', $this->company_s)->orderBy('name')->get();

        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        //Filtros depedentes de bancos externos, testar antes.
        try {

            $this->region_l = City::select('regiao')->orderBy('regiao')->groupBy('regiao')->get();

            $this->district_l = City::when($this->region_s, function ($q) {
                return $q->whereIn('regiao', $this->region_s);

            })->select('baseConstrucao')->orderBy('baseConstrucao')->groupBy('baseConstrucao')->get();
            $this->city_l = City::when($this->region_s, function ($q) {
                return $q->whereIn('regiao', $this->region_s);
            })
                ->when($this->district_s, function ($q) {
                    return $q->whereIn('baseConstrucao', $this->district_s);
                })
                ->select('rdMunicipio', 'cidade', 'municipio')
                ->orderBy('cidade')
                ->groupBy('rdMunicipio', 'cidade', 'municipio')
                ->get();

        } catch (\Illuminate\Database\QueryException $e) {

            $this->region_l   = [];
            $this->district_l = [];
            $this->city_l     = [];
        }

        return view('livewire.dispatchs.supervision.stack', [
            'allList' => $this->status->get(),
            'lists'   => $this->lists,
        ]);
    }
}
