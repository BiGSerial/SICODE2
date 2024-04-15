<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Company;
use App\Models\File;
use App\Models\HiringWaiting;
use App\Models\Note;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Waiting extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;


    public $service;

    public $advanceSearch;

    public $search;

    public $selectAll;

    public $selected = [];

    public $typeNote = '';

    public $multiSearch = [];

    public $page = 1;

    public $files = [];

    public $show_files = [];

    public $show_existing_files = [];

    public $show_registers = [];

    //Selects
    public $companies = null;

    public $company_s;

    public $engineers = null;

    public $engineer_s;

    public $services;

    public $service_s;

    public $category;

    public $action;

    public $comment;

    // Clipboard
    public $clipboardData = [];


    protected $queryString = [

    ];

    protected $listeners = [
        'confirm_viability' => 'confirm_viability'
    ];

    public function mount($service)
    {
        if ($this->perPage > 500) {
            $this->perPage = 500;
        }

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }



        $this->service   = Service::where('uuid', $service)->first();
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Adicionar os IDs ausentes de $selected
            foreach ($this->lists as $list) {

                if (!in_array($list->Note->id, $this->selected) && $list->Reclaim->completed) {
                    $this->selected[] = $list->Note->id;
                }
            }
        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $this->lists->pluck('Note.id')->toArray())) {
                    $newSelected[] = $id;
                }
            }
            $this->selected = $newSelected;
        }
    }

    public function go_att_mass()
    {

        // Bloqueia Caso Nenhuma Nota/Ov Tiver sido selecionada
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para Envio.',
                'timer'    => 5000,
            ]);

            return;
        }


        if ($this->action != 3) {

            $orders = Order::with('Note.Files')->whereRelation('Note', function ($q) {
                return $q->whereIn('id', $this->selected);
            })->get();

            // dd($orders, $this->selected);


            if ($orders) {

                foreach ($orders as $order) {

                    $this->show_registers[$order->id] = [
                        'id'          => $order->id,
                        'note_id'     => $order->Note->id,
                        'order'       => $order->ordem,
                        'note'        => $order->Note->note,
                        'file_index'  => '',
                        'file_online' => false,
                    ];

                    if ($order->Note->Files->count()) {

                        foreach ($order->Note->Files as $file) {
                            $this->show_existing_files[$order->id] = [
                                'id'   => $order->id,
                                'name' => $file->file_name,
                                'ext'  => $file->ext,
                                'chk'  => false,
                            ];
                        }

                        $this->show_registers[$order->id] = array_merge($this->show_registers[$order->id], ['file_online' => true]);
                    }
                }
                $this->dispatchBrowserEvent('showModal', [
                    'id' => 'viability_modal',
                ]);
            }
        }

        // Se Ação for Retornar para Serviços
        if ($this->action == 3) {
            $this->show_registers = Note::whereRelation('Orders', function ($q) {
                $q->whereIn('id', $this->selected);
            })->with('Productions.User')->get();



            $this->dispatchBrowserEvent('showModal', [
                'id' => 'return_modal',
            ]);
        }
    }

    public function to_viability()
    {

        if ($this->company_s == '' && $this->engineer_s == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'É necessário selecionar a EMPREITEIRA e o RESPONSÁVEL PELO PROJETO.',
                'timer'    => 5000,
            ]);

            return;

        }



        $company = $this->companies->where('id', $this->company_s)->first()->name;

        $engineer = $this->engineers->where('id', $this->engineer_s)->first()->name;




        if (count($this->show_registers)) {

            $has_nofile = false;

            foreach ($this->show_registers as $register) {
                $check = Viability::where('order_id', $register['id'])->where('completed', false)->count();

                if ($check) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'NOTAS EXISTENTES EM VIABILIDADE.',
                        'html'     => 'Alguém ja pode ter colocado essas notas em atividade de viabilidade, atualize a págna e tente novamente.',
                        'timer'    => 5000,
                    ]);

                    return;
                }
            }

            foreach ($this->show_registers as $register) {
                if ($register['file_index'] == '' && !$register['file_online']) {
                    $has_nofile = true;
                }
            }

            if ($has_nofile) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Existem Ordens sem arquivo anexado, verique e tente novamente.',
                    'timer'    => 5000,
                ]);

                return;
            }

            $count = count($this->show_registers);

            $title = '';

            if ($this->action == 1) {

                $title = 'Viabilidade';

                $text = "
            <div class='card'>
                <div class='card-body'>
                    <p>Você está prestes a enviar <span class='fw-bold'>{$count}</span> ordem(ns) para Viabilidade. Confira as informações:</p>
                    <p class='text-uppercase text-start fs-5'><span class='fw-bold'>Empreiteira:</span> {$company}<br>
                    <span class='fw-bold'>Eng. Responsável:</span> {$engineer}
                    </p>
                </div>            
            </div>            
            ";
            }

            if ($this->action == 2) {

                $title = 'Contratação';

                $text = "
            <div class='card'>
                <div class='card-body'>
                    <p>Você está prestes a enviar <span class='fw-bold'>{$count}</span> ordem(ns) para Contratação. Confira as informações, pois não será possível alterar posteriormente:</p>
                    <p class='text-uppercase text-start fs-5'><span class='fw-bold'>Empreiteira:</span> {$company}<br>
                    <span class='fw-bold'>Eng. Responsável:</span> {$engineer}
                    </p>
                </div>            
            </div>            
            ";
            }

            $this->dispatchBrowserEvent('alertar', [
                'title'         => "Confirmar Envio para {$title}?",
                'msg'           => $text,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Despache!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_viability',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Ordem foi Enviada!',

            ]);

            return;
        }
    }

    public function confirm_viability()
    {
        DB::beginTransaction();

        $note = null;
        $erro = false;

        if (count($this->show_files)) {

            foreach ($this->show_files as $temp_file) {

                $caminho = '';

                if (isset($this->files[$temp_file['id']])) {

                    $caminho = $this->files[$temp_file['id']]->store('/arquivos');

                    if ($caminho) {

                        $file = File::create([
                            'note_id'    => $temp_file['note_id'],
                            'user_id'    => Auth()->User()->id,
                            'service_id' => $this->service->uuid,
                            'file_name'  => $temp_file['name'],
                            'path'       => $caminho,
                            'ext'        => $temp_file['ext'],
                        ]);

                        if (!$file) {
                            $erro = true;
                        }

                    }

                }

            }
        }

        if (count($this->show_registers)) {

            if ($this->action == 1) {
                foreach ($this->show_registers as $register) {

                    $viability = Viability::Create([
                        'order_id'    => $register['id'],
                        'company_id'  => $this->company_s,
                        'user_id'     => Auth()->User()->id,
                        'engineer_id' => $this->engineer_s,
                        'sended_at'   => date('Y-m-d H:i:s'),
                        'status'      => 1,
                    ]);

                    $note = $viability->Order->Note ? $viability->Order->Note->id : null;

                    if (!$viability) {
                        $erro = true;
                    }

                }


            } elseif ($this->action == 2) {
                // TO Direct hiring, this will direct to completed job.
                foreach ($this->show_registers as $register) {

                    $viability = Viability::Create([
                        'order_id'    => $register['id'],
                        'company_id'  => $this->company_s,
                        'user_id'     => Auth()->User()->id,
                        'engineer_id' => $this->engineer_s,
                        'sended_at'   => date('Y-m-d H:i:s'),
                        'status'      => 9,
                        'hired'       => true,
                        'hired_at'    => date('Y-m-d H:i:s'),
                        'completed'   => true,
                        'completed_at' => date('Y-m-d H:i:s'),

                    ]);


                    $note = $viability->Order->Note ? $viability->Order->Note->id : null;

                    if (!$viability) {
                        $erro = true;

                    }
                }
            }

            if ($note) {
                $wait = HiringWaiting::where('note_id', $note)->where('complete', false)->update([
                    'complete' => true,
                ]);

            } else {
                DB::rollback();
            }


        }

        if ($erro) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'TIVEMOS UM ERRO INESPERADO, NENHUM REGISTRO FOI EXECUTADO.',
                'timer'    => 5000,
            ]);

        } else {

            DB::commit();

            $this->closeall();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Registro Efetuado com Sucesso.',
                'timer'    => 5000,
            ]);
        }
    }

    public function updatedFiles()
    {

        if (count($this->files)) {

            $this->show_files = [];

            foreach ($this->files as $index => $file) {

                $skip_file = false;

                if (count($this->show_existing_files)) {

                    foreach ($this->show_existing_files as $files_existing) {

                        if ($files_existing['name'] === explode('.', $file->getClientOriginalName())[0]) {
                            $skip_file = true;
                        }
                    }
                }

                if (!$skip_file) {
                    $this->show_files[$index] = [
                        'id'      => $index,
                        'note_id' => '',
                        'name'    => explode('.', $file->getClientOriginalName())[0],
                        'ext'     => $file->getClientOriginalExtension(),
                        'chk'     => false,
                    ];
                }
            }

            $this->associate_files();
        }

    }

    public function delete_file($id)
    {
        if (isset($this->show_files[$id])) {
            unset($this->files[$id]);
            unset($this->show_files[$id]);
        }
    }

    public function delete_note($id)
    {
        if (isset($this->show_registers[$id])) {
            if (isset($this->show_files[$this->show_registers[$id]['file_index']])) {
                $this->show_files[$this->show_registers[$id]['file_index']] = array_merge($this->show_files[$this->show_registers[$id]['file_index']], [
                    'chk' => false,
                ]);
            }

            unset($this->show_registers[$id]);
        }
    }

    public function associate_files()
    {
        if (count($this->show_files) && count($this->show_registers)) {

            foreach ($this->show_registers as $register) {

                foreach ($this->show_files as $file) {

                    if (strpos($file['name'], $register['note']) !== false) {
                        $this->show_registers[$register['id']] = array_merge($this->show_registers[$register['id']], [
                            'file_index' => $file['id'],
                        ]);

                        $this->show_files[$file['id']] = array_merge($this->show_files[$file['id']], [
                            'note_id' => $this->show_registers[$register['id']]['note_id'],
                            'chk'     => true,
                        ]);
                    }
                }
            }
        }
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->engineer_s     = '';
        $this->show_files     = [];
        $this->show_registers = [];
        $this->gotoPage(1);

        $this->emit('refresh_list');
    }



    public function getListsProperty()
    {
        return HiringWaiting::where('user_id', Auth()->User()->id)
                ->where('complete', false)
                ->orderBy('created_at')
                ->with('Note', 'Reclaim.Production')
                ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.construction.hiring.waiting', [
            'lists' => $this->lists
        ]);
    }
}
