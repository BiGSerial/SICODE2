<?php

namespace App\Http\Livewire\Dispatchs;

use App\Models\Company;
use App\Models\Note;
use App\Models\Operation;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReturnD5 extends Component
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

    //Selects
    public $companies = null;

    public $company_s;

    public $services;

    public $service_s;

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
        'giveBack' => 'giveBack',
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
        // $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {



            foreach ($this->lists->pluck('id')->toArray() as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }



        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $this->lists->pluck('id')->toArray())) {
                    $newSelected[] = $id;
                }
            }
            $this->selected = $newSelected;
        }
    }

    public function go_att_mass()
    {



    }

    // public function copyClipboard()
    // {
    //     if (count($this->selected)) {



    //         $orders = Order::join('notes', 'orders.note_id', '=', 'notes.id')->with('Operations', 'Note.Files')
    //         ->select('orders.*', 'notes.id as myNote_id', 'notes.days_left as myDayLeft', 'notes.type_note as myTypeNote', 'notes.note as myNote')
    //         ->orderBy('myTypeNote', 'DESC')
    //         ->orderBy('myDayLeft')
    //         ->orderBy('myNote')
    //         ->whereRelation('Note', function ($q) {
    //             $q->whereIn('note_id', $this->selected);
    //         })->get();

    //         if ($orders) {



    //             foreach ($orders as $order) {

    //                 $this->clipboardData[] = [
    //                     $order->ordem,
    //                     $order->Note->note,
    //                     $order->pep ?? ''
    //                 ];
    //             }

    //             // dd($this->clipboardData);

    //             $this->dispatchBrowserEvent('copyToBoard', $this->clipboardData);

    //             $this->dispatchBrowserEvent('torrada', [
    //                 'status'   => 'success',
    //                 'menssage' => "Copiado para a área de transferência",
    //             ]);
    //         }
    //     }
    // }


    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->gotoPage(1);


        $this->selectAll = false;
        $this->selected = [];


        $this->emit('refresh_list');
    }


    public function getListsProperty()
    {
        return Reclaim::Where('service_id', $this->service->uuid)
                    ->Where('completed', false)
                    ->with('Production.User', 'Note')
                    ->orderBy('created_at')
                    ->paginate($this->perPage);
    }


    public function render()
    {
        return view('livewire.dispatchs.return-d5', [
            'lists' => $this->lists,
        ]);
    }
}
