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

    public $notAtt = false;

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
    // Filters
    private $filter_group = 'd5controls';
    private $filters;

    // Orderenação
    public $sortField = 'created_at';
    public $sortDirection = 'asc';


    protected $queryString = [
        'search' => ['except' => '', 'as' => 'busca'],
        'perPage' => ['as' => 'pagina'],
        'filterUser' => ['except' => ''],
        'sortDirection' => ['except' => 'asc'],
        'notAtt' => ['except' => false],
    ];

    protected $listeners = [
        'refresh_list',
        'refreshComponent' => '$refresh',
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
            if (!session()->isStarted()) { session()->start(); }
        }



        $this->service   = Service::where('uuid', $service)->first();
        $this->companies = Company::query()
            ->linkedToService($this->service->uuid)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        // $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
    }

    public function setNotAtt()
    {
        $this->notAtt = !$this->notAtt;
    }


    public function massAssign()
    {
        if (!$this->selected) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM OBRAS SELECIONADAS',
                'html'      => 'Verifique a seleção das obras e tente novamente.',
                'timer'    => 5000,
            ]);        # code...
        } else {
            $this->emitTo('dispatchs.common.return-in-mass', 'goOpenMassAtt', $this->selected);
        }
    }

    public function refresh_list()
    {
        $this->emitSelf('$refresh');
        $this->resetPage();
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

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];

        }

        $query = Reclaim::query()
                ->Where('service_id', $this->service->uuid)
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
                });

        if (isset($this->filters['rubrica'])) {
            $query->whereRelation('Note', function ($query) {
                $query->whereIn('rubrica', $this->filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        }

        if (isset($this->filters['city'])) {
            $query->whereRelation('Note', function ($query) {
                $query->whereIn('lexp', $this->filters['city'])
                    ->orWhereNull('lexp');
            });
        }

        if ($this->notAtt) {
            $query->whereDoesntHave('production');
        }

        $query->Where('completed', false)
            ->leftJoin('notes as n', 'reclaims.note_id', '=', 'n.id')

            ->select('reclaims.*', 'n.note as note', 'n.rubrica as rubrica', 'n.group5 as group5', 'n.material as material', 'n.lexp')
            ->with([
                'Production.User',
                'Note',
                'Approvals',
                'Viabilities',
                'Waiting',
                'Externals',
            ])
            ->orderBy($this->sortField, $this->sortDirection);


        return $query;

    }


    public function render()
    {
        return view('livewire.dispatchs.return-d5', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
