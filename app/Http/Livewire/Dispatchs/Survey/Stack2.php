<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Exports\Dispatchs\SurveyProductionExport;
use App\Exports\ProductionControlExport;
use App\Helpers\TextFormatter;
use App\Models\{Analise, Company, Note, Notetimeline, Production, Service, User, Wpa};
use Livewire\{Component, WithPagination};
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Stack2 extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    // VAr System
    public $service;
    public $search;
    public $perPage = 100;
    public $advanceSearch;
    public $multiSearch = [];
    public $selected = [];
    public $company_s;
    public $user_s;
    public $company_l;
    public $user_l;
    public $additionalData = [];
    public $enter_dd;
    public $forcar = false;
    public $production;
    public $productions;
    public $notes;
    public $existDD;
    public $selectall;



    protected $listeners = [
        'refresh_list' => '$refresh',
        'getCopy' => 'copy',
        'confirm_mass_dd' => 'confirmed_mass_dd',
        'confirm_des_att_mass' => 'confirm_des_att_mass',
        'confirm_dispatch'     => 'confirmed_att',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function updatedSearch()
    {
        $this->gotoPage(1);
    }

    public function export_excel()
    {

        return (new SurveyProductionExport($this->lists, $this->service->uuid, $this->selected))->download(date('YmdHis-') . 'controle_de_producao.xlsx');
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->gotoPage(1);
            $this->search = '';
            $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
            $this->closeall();
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status' => 'success',
            'menssage' => $msg,
        ]);
    }

    public function go_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma nota foi selecionada para atribuição!',
                'timer' => 2500,
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
        if (!$this->user_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhum usuário foi selecionado para despacho individual!',
                'timer' => 2500,
            ]);
            return;
        }

        $para = User::find($this->user_s)->name . ' da ' . (Company::find($this->company_s))->name;


        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Confirmar Atribuir',
            'msg' => "Você está prestes a Atribuir {$this->notes->count()} nota(s) para {$para}",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Despache!',
            'btnCanceltxt' => 'Não, Cancele',
            'action' => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

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

    public function go_des_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma nota foi selecionada para desatribuição!',
                'timer' => 2500,
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
                'title' => 'Confirmar Desatribuição Parcial',
                'msg' => "{$notes_not_valids} das Das {$this->productions->count()} selecionadas, não atende(m) o critério para Desatribuição. Deseja continuar?",
                'icon' => 'warning',
                'btnOktxt' => 'Sim, Desatribua!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma nota foi Desatribuída.',
            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Confirmar Desatribuição em Massa',
                'msg' => "{$this->productions->count()} NOTAS/OVs estão prontas para serem desatribuídas. Deseja continuar?",
                'icon' => 'warning',
                'btnOktxt' => 'Sim, Desatribua!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma nota foi Desatribuída.',

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
                    'icon' => 'warning',
                    'title' => "{$erros} de {$total} não foram desatribuídos.",
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'success',
                    'title' => "{$total} Notas/Ovs Desatribídas com sucesso",
                    'timer' => 2500,
                ]);

                $this->emit('refresh_list');
            }

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhum registro de desatribuição. Repita o procedimento.',
                'timer' => 2500,
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
                    'icon' => 'success',
                    'title' => 'Notas DDs associadas com sucesso',
                    'timer' => 2500,
                ]);
                $this->closeall();
                $this->emit('refresh_list');
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'error',
                    'title' => "OOPS!, Ocorreram {$error} de {$count} ao associar as DD às Notas.",
                    'timer' => 8000,
                ]);
            }
        }
    }


    public function confirmed_att()
    {
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

        foreach ($this->notes as $key => $note) {
            $production = $this->productions->where('note_id', $note->id)->first();

            if ($production) {

                if ($production->update([
                    'user_id' => $this->user_s,
                    'company_id' => $this->company_s,
                    'att_by' => Auth()->User()->id,
                    'att_at' => date('Y-m-d H:i:s'),
                    'status' => 2,
                    'block' => false,
                ])) {

                    $user = Auth()->User()->name;

                    if (trim($this->user_s)) {
                        $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                    } else {
                        $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
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
                    }

                    Wpa::create([
                        'production_id' => $production->id,
                        'note_id' => $note->id,
                        'dd' => $this->additionalData[$key],
                    ]);
                } else {


                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon' => 'error',
                        'title' => 'Erro ao atribuir as notas!',
                        'timer' => 2500,
                    ]);

                    return;
                }

            } else {
                dd($production, $note->note);

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'error',
                    'title' => 'Erro ao atribuir as notas!',
                    'timer' => 2500,
                ]);

                return;
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Notas Despachadas com sucesso!',
            'timer' => 2500,
        ]);

        $this->closeall();

        $this->emit('refresh_list');
    }


    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma empresa foi selecionada para despacho!',
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

    public function getListsProperty()
    {
        return Production::with(['Note', 'Company', 'User', 'Dispatcher'])
            ->where('confirmed', false)
            ->where('service_id', $this->service->uuid)
            ->when($this->search, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    $query->where('note', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('group2', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('group3', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('group4', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('group5', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('numPedido', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('material', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('lexp', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('rubrica', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('centerjob', 'like', '%' . trim($this->search) . '%');
                });
            })
            ->when(Auth()->User()->contract, function ($q) {
                return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
            })
            ->when($this->company_s, function ($q) {
                return $q->where('company_id', $this->company_s);
            })
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            })
            ->when($this->multiSearch, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('note', $this->multiSearch);
                });
            })
            ->orderBy('priority', 'DESC')
            ->orderBy('created_at', 'DESC');
    }

    public function closeall()
    {
        $this->emit('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
        $this->company_s = '';
        $this->selected = [];
        $this->user_s = '';
        $this->additionalData = [];
        $this->enter_dd = '';
    }


    public function render()
    {

        // $this->company_l = Company::whereHas('toUsers', function ($query) {
        //     $query->whereRelation('ToServices', function ($q) {
        //         $q->where('service_id', $this->service->uuid)
        //             ->where('service', true);
        //     });
        // })
        //     ->orderBy('name', 'ASC')
        //     ->get();

        // $this->user_l = User::whereRelation('ToServices', function ($q) {
        //     $q->where('service_id', $this->service->uuid)
        //         ->where('service', true);
        // })
        //  ->where(function ($q) {
        //      $q->whereRelation('Company', 'company_id', $this->company_s)
        //          ->orWhereRelation('Employee.Contract.company', 'id', $this->company_s);
        //  })
        // // ->when($this->search_user, function ($q) {
        // //     return $q->where('name', 'like', '%' . $this->search_user . '%');
        // // })
        // ->orderBy('name', 'ASC')->get();

        $lists = $this->lists->paginate($this->perPage);

        return view('livewire.dispatchs.survey.stack2', [
            'lists' => $lists,
        ]);
    }
}
