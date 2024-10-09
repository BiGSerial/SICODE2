<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Exports\HiringListExport;
use App\Models\{Company, File, HiringWaiting, Note, Order, Production, Reclaim, Service, User, Viability};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Storage};
use Livewire\{Component, WithFileUploads, WithPagination};
use ZipArchive;

class Main extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

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
    public $show_returns;
    public $perPage = 50;
    public $allCenters = false;

    //Selects
    public $companies = null;
    public $company_s;
    public $engineers = null;
    public $engineer_s;
    public $services;
    public $service_s;
    public $category;
    public $action;

    // Indicate Hiring Note when send to Viability
    public $hiring = false;
    public $comment;

    // Clipboard
    public $clipboardData = [];

    // Filters
    private $filter_group = 'hiring';
    private $filter;

    public function mount($service)
    {
        $this->service   = Service::where('uuid', $service)->first();
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
    }

    public function setSelectAll()
    {

        if ($this->selectAll) {
            // Adicionar os IDs que cumprem as regras à lista de selecionados
            foreach ($this->lists as $item) {

                $id = $item->id;

                if (!in_array($id, $this->selected)) {
                    $waitingsCount = $item->Waitings->where('complete', false)->count();

                    if (!$waitingsCount) {
                        $this->selected[] = $id;
                    }
                } else {
                    $visibleIds = $this->lists->pluck('id')->toArray();
                    $this->selected = array_filter($this->selected, function ($id) use ($visibleIds) {
                        return !in_array($id, $visibleIds);
                    });
                }
            }
        } else {
            // Remover os IDs de $selected que estão presentes em $this->lists
            $visibleIds = $this->lists->pluck('id')->toArray();
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


    public function go_att_mass()
    {
        if (!$this->action) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Selecione uma ação para continuar.',
                'timer'    => 10000,
            ]);

            return;
        }

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Selecione pelo menos um registro para continuar.',
                'timer'    => 10000,
            ]);

            return;
        }


        if ($this->action == 'viabilizar') {

            $this->emitTo('construction.hiring.actions.viability', 'getNotes', $this->selected);
        }

        if ($this->action == 'ri') {
            dd('RI');
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

        $query = Note::query();

        $query->where(function ($query) {
            $query->where(function ($qq) {
                $qq->when(!$this->allCenters, function ($q) {
                    $q->whereIn('nstats', [46, 47, 48, 49, 50]);
                })
                ->whereNotIn('rubrica', ['Incoporação'])
                ->where('type_note', 2);
            })
            ->orWhere(function ($qq) {
                $qq->where('type_note', 1)
                ->when(!$this->allCenters, function ($q) {
                    $q->where('centerjob', 'like', 'VIAB%');
                })
                ->orWhere(function ($qq) {
                    $qq->where('centerjob', '')
                        ->where('type_note', 1);
                });
            });
        })
        ->whereHas('Orders', function ($q) {
            $q->where('statusSist', 'not like', 'ENTE%')
                    ->where('statusSist', 'not like', 'ENCE%')
                    ->where(function ($q) {
                        $q->whereRelation('Operations', function ($sq) {
                            $sq->where('operacao', '0010')
                                ->where('status', 'like', 'ABER%');
                        });
                    });
        });

        if ($this->multiSearch) {
            $query->where(function ($q) {
                $q->whereRelation('Orders', function ($query) {
                    $query->whereIn('ordem', $this->multiSearch);
                })->orWhereIn('note', $this->multiSearch);
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['empreiteira'])) {
            $query->whereRelation('Orders.Operations', function ($query) {
                $query->where('operacao', '0010')
                    ->where('status', 'like', 'ABER%')
                    ->whereIn('cenTrab', $_SESSION['filter'][$this->filter_group]['empreiteira'])
                    ->orWhere('cenTrab', '');
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['city'])) {
            $query->where(function ($query) {
                $query->whereIn('lexp', $_SESSION['filter'][$this->filter_group]['city'])
                    ->orWhere('lexp', '');
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['rubrica'])) {
            $query->where(function ($query) {
                $query->whereIn('rubrica', $_SESSION['filter'][$this->filter_group]['rubrica'])
                    ->orWhere('rubrica', '');
            });
        }

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        $query->orderBy('mesalization', 'ASC')
            ->orderBy('type_note', 'ASC')
            ->orderBy('days_left')
            ->orderBy('note');

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.construction.hiring.main', [
            'lists' => $this->lists,
        ]);
    }
}
