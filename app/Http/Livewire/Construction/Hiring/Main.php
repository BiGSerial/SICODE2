<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Exports\HiringAccompanyExport;
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


    protected $listeners = [
         'refresh' => '$refresh',
         'refresh_list' => '$refresh',
         'closeAll' => 'closeAll',
     ];

    public function mount($service)
    {
        $this->service   = Service::where('uuid', $service)->first();
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
    }


    // Lógica para selecionar todos os registros
    public function setSelectAll()
    {
        if ($this->selectAll) {



            foreach ($this->lists->paginate($this->perPage) as $item) {

                $id = $item->id;

                if (!in_array($id, $this->selected)) {
                    $waitingsCount = $item->Waitings->where('complete', false)->count();
                    $inViability = $item->Viabilities->where('complete', false)->count();

                    if (!$waitingsCount && !$inViability) {
                        $this->selected[] = $id;
                    }

                } else {
                    $visibleIds = $this->lists->paginate($this->perPage)->pluck('id')->toArray();
                    $this->selected = array_filter($this->selected, function ($id) use ($visibleIds) {
                        return !in_array($id, $visibleIds);
                    });
                }
            }
        } else {
            // Remover os IDs de $selected que estão presentes em $this->lists
            $visibleIds = $this->lists->paginate($this->perPage)->pluck('id')->toArray();
            $this->selected = array_filter($this->selected, function ($id) use ($visibleIds) {
                return !in_array($id, $visibleIds);
            });
        }
    }


    // Lógiva para verificar se todos os registros estão selecionados
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

            $this->emitTo('construction.hiring.actions.go-waiting', 'getNotes', $this->selected);
        }
    }

    // Lógica para exportar os registros selecionados para o Excel
    public function export_excel()
    {
        if (count($this->selected)) {

            $export = Order::where('statusSist', 'not like', 'ENTE%')
                ->where('statusSist', 'not like', 'ENCE%')
                ->whereIn('note_id', $this->selected)
                ->with('Note');

        } else {

            $export = Order::where('statusSist', 'not like', 'ENTE%')
                ->where('statusSist', 'not like', 'ENCE%')
                ->whereIn('note_id', $this->lists->get()->pluck('id'))
                ->with('Note');
        }



        return (new HiringListExport($export))->download(date('YmdHis-') . 'exportViabilityAccompany.xlsx');
    }


    public function buscarMulti()
    {

        if ($this->advanceSearch) {

            $this->search = '';

            $this->gotoPage(1);


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

            $limpar = [];

            foreach ($this->multiSearch as $value) {
                if ($value) {
                    $limpar[] = $value;
                }
            }

            $this->multiSearch = $limpar;
            $this->search = '';
            $this->dispatchBrowserEvent('hideModal');
            $this->closeAll();
        }
    }

    public function getListsProperty()
    {
        // Ensure session is active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session()->isStarted()) { session()->start(); }
        }

        // Initialize filter from session if available
        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Note::query();

        // Base query conditions
        if (!$this->allCenters) {
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
            });
        }

        if (!$this->allCenters) {
            $query->whereDoesntHave('Viabilities', function ($q) {
                $q->where('hired', true);
            });

            $query->whereDoesntHave('Waitings', function ($q) {
                $q->where('complete', false);
            });
        }

        // Condition for 'Orders' relationship (always required)
        $query->whereHas('Orders', function ($q) {
            // These specific restrictions on statusSist and Operations are only applied if allCenters is false

            $q->where('statusSist', 'not like', 'ENTE%')
                ->where('statusSist', 'not like', 'ENCE%');

            if (!$this->allCenters) {
                $q->where(function ($q) { // This block is what we're now conditionally ignoring
                    $q->whereRelation('Operations', function ($sq) {
                        $sq->where('operacao', '0010')
                            ->where('status', 'like', 'ABER%');
                    });
                });
            }

        });

        // Approval, Priority, Viabilities, Waitings, or PZE conditions
        if (!$this->allCenters) {
            $query->where(function ($query) {
                $query->whereHas('Approval', function ($q) {
                    $q->where('approved', true);
                })
                ->orWhere(function ($query) {
                    $query->whereIn('txpriority', ['Emergente']);
                })
                ->orWhereHas('Viabilities')
                ->orWhereHas('Waitings')
                ->orWhere('pze', 25);
            });
        }

        // Search functionality
        if ($this->search) {
            $this->multiSearch = [];
            $this->advanceSearch = '';
            $this->allCenters = false; // Setting allCenters to false when search is active

            $query->where(function ($query) {
                $query->where('note', 'like', '%' . $this->search . '%')
                    ->orWhere('lexp', 'like', '%' . $this->search . '%')
                    ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                    ->orWhere('centerjob', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('Orders', function ($query) {
                        $query->where('ordem', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Multi-search functionality
        if ($this->multiSearch) {
            $query->where(function ($query) {
                $query->whereIn('note', $this->multiSearch)
                    ->orWhereIn('lexp', $this->multiSearch)
                    ->orWhereIn('rubrica', $this->multiSearch)
                    ->orWhereIn('centerjob', $this->multiSearch)
                    ->orWhereRelation('Orders', function ($query) {
                        $query->whereIn('ordem', $this->multiSearch);
                    });
            });
        }

        // Session filters
        if (isset($_SESSION['filter'][$this->filter_group]['empreiteira'])) {
            $query->whereHas('Orders.Operations', function ($query) {
                $query->where('operacao', '0010')
                    ->where('status', 'like', 'ABER%')
                    ->whereIn('cenTrab', $_SESSION['filter'][$this->filter_group]['empreiteira'])
                    ->orWhere('cenTrab', ''); // Allow empty cenTrab
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['city'])) {
            $query->where(function ($query) {
                $query->whereIn('lexp', $_SESSION['filter'][$this->filter_group]['city'])
                    ->orWhere('lexp', ''); // Allow empty lexp
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['rubrica'])) {
            $query->where(function ($query) {
                $query->whereIn('rubrica', $_SESSION['filter'][$this->filter_group]['rubrica'])
                    ->orWhere('rubrica', ''); // Allow empty rubrica
            });
        }

        // Type note filter
        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        // Ordering
        $query->orderBy('mesalization', 'ASC')
            ->orderBy('is45', 'DESC')
            ->orderBy('type_note', 'ASC')
            ->orderBy('days_left')
            ->orderBy('note');

        return $query;
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function closeAll()
    {

        $this->selected = [];
        $this->selectAll = false;
        $this->emit('refresh');
    }

    public function render()
    {
        return view('livewire.construction.hiring.main', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
