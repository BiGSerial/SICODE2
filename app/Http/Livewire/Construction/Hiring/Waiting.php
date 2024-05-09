<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Company;
use App\Models\File;
use App\Models\HiringWaiting;
use App\Models\Note;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Waiting extends Component
{
    use WithPagination;
    use WithFileUploads;

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

    public $hiring = false;

    public $cjobes;

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
        'refresh_list' => '$refresh',
        'confirm_viability' => 'confirm_viability',
        'cleanAll' => 'closeall',
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

        $orders = Order::WhereRelation('Note', function ($q) {
            $q->whereIn('id', $this->selected);
        })->get()->pluck('id')->toArray();

        $this->emit('getOrders', $orders);
    }



    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->gotoPage(1);


        $this->selectAll = false;
        $this->selected = [];
        $this->cjobes = "";


        $this->emit('refresh_list');
    }



    public function getListsProperty()
    {
        return HiringWaiting::where('user_id', auth()->user()->id)
                        ->where('complete', false)
                        ->when($this->cjobes, function ($query) {
                            $query->whereHas('Note.Orders.Operations', function ($subquery) {
                                $subquery->where('cenTrab', $this->cjobes)->where('operacao', '0010');
                            });
                        })
                        ->orderBy('created_at')
                        ->with(['Note.Orders.Operations' => function ($query) {
                            $query->where('operacao', '0010');
                        }, 'Note.Files', 'Reclaim.Production'])
                        ->paginate($this->perPage);
    }

    public function getCentroTrabProperty()
    {
        return Operation::where('operacao', '0010')
                    ->where('descOperacao', 'like', '%EMPREITAR E VIABIL%')
                    ->select('cenTrab')
                    ->orderBy('cenTrab')
                    ->groupBy('cenTrab')
                    ->get();


    }

    public function render()
    {
        return view('livewire.construction.hiring.waiting', [
            'lists' => $this->lists,
            'centerJobs' => $this->centroTrab,
        ]);
    }
}
