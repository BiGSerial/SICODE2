<?php

namespace App\Http\Livewire\Dispatchs;

use App\Exports\Reports\ReturnInternExport;
use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\Operation;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    //filter User
    public $filterUser;

    // Orderenação
    public $sortField = 'created_at';
    public $sortDirection = 'desc';


    protected $queryString = [

    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirm_viability' => 'confirm_viability',
        'cleanAll' => 'closeall',
        'giveBack' => 'giveBack',
        'filterUser' => 'filterUser',
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

    public function exportToExcel()
    {
        return (new ReturnInternExport($this->lists->get()))->download('Retorno_Interno_Export_List_'.date('YmdHis').'.xlsx');
    }

    public function filterUser($user_id)
    {
        $this->filterUser = $user_id;
    }

    public function cleanUser()
    {
        $this->filterUser = '';
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

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function go_att_mass()
    {



    }


    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->gotoPage(1);


        $this->selectAll = false;
        $this->selected = [];


        $this->emit('refresh_list');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }


    public function getListsProperty()
    {
        return Reclaim::Where('service_id', $this->service->uuid)
                ->when($this->filterUser, function ($q) {
                    $q->WhereRelation('Production', 'user_id', $this->filterUser);
                })
                ->when($this->search, function ($q) {
                    $this->gotoPage(1);
                    $q->Where(function ($q) {
                        $q->whereRelation('Note', 'note', 'like', '%' . trim($this->search) . '%')
                            ->orWhereRelation('Note', 'rubrica', 'like', '%' . trim($this->search) . '%')
                            ->orWhereRelation('Note', 'group5', 'like', '%' . trim($this->search) . '%')
                            ->orWhereRelation('Note', 'material', 'like', '%' . trim($this->search) . '%')
                            ->orWhereRelation('Note', 'lexp', 'like', '%' . trim($this->search) . '%');
                    });
                })
                ->Where('completed', false)
                ->leftJoin('notes as n', 'reclaims.note_id', '=', 'n.id')

                ->select('reclaims.*', 'n.note as note', 'n.rubrica as rubrica', 'n.group5 as group5', 'n.material as material', 'n.lexp')
                ->with([
                    'Production.User',
                    'Note',
                    'Approvals',
                    'Viabilities',
                    'Waiting',
                ])

                ->orderBy($this->sortField, $this->sortDirection);
    }


    public function render()
    {
        return view('livewire.dispatchs.return-d5', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
