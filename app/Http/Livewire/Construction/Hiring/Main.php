<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Company;
use App\Models\Order;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;
    use WithFileUploads;
    protected $paginationTheme = 'bootstrap';



    public $service;
    public $advanceSearch;
    public $search;
    public $selectAll;
    public $selected = [];
    public $typeNote = "";
    public $multiSearch = [];
    public $page = 1;

    public $files = [];
    public $show_files = [];

    public $show_registers = [];

    public $perPage = 50;

    //Selects
    public $companies = null;

    // Filters
    private $filter_group = "hiring";
    private $filter;

    protected $listeners = [
        'refresh_list' => '$refresh'
    ];

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],

        ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
    }

    public function export_excel()
    {
        // if (!count($this->selected)) {
        //     return (new DispatchDesenhoMain($this->lists->get()))->download(date('YmdHis-').'exportNotesDesenho.xlsx');
        // } else {
        //     $notes = Note::WhereIn('id', $this->selected)->orderBy('days_left')->get();
        //     return (new DispatchDesenhoMain($notes))->download(date('YmdHis-').'exportNotesDesenho.xlsx');
        // }
    }

    public function go_att_mass()
    {

        if (count($this->selected)) {
            $orders = Order::with('Note')->find($this->selected);

            if ($orders) {
                foreach ($orders as $order) {
                    $this->show_registers[$order->id] = [
                        'id' => $order->id,
                        'note_id' => $order->Note->id,
                        'order' => $order->ordem,
                        'note' => $order->Note->note,
                        'file_index' => '',
                    ];
                }
            }
        }


        $this->dispatchBrowserEvent('showModal', [
            'id' => 'viability_modal'
        ]);

    }

    public function updatedFiles()
    {


        if (count($this->files)) {

            $this->show_files = [];

            foreach ($this->files as $index => $file) {

                $this->show_files[$index] = [
                    'id' => $index,
                    'note_id' => "",
                    'name' => explode('.', $file->getClientOriginalName())[0],
                    'ext' => $file->getClientOriginalExtension(),
                    'chk' => false,
                ];
            }



            $this->associate_files();
        }

    }

    public function delete_file($id)
    {
        if (isset($this->show_files[$id])) {
            unset($this->show_files[$id]);
        }
    }

    public function delete_note($id)
    {
        if (isset($this->show_registers[$id])) {
            $this->show_files[$this->show_registers[$id]['file_index']] = array_merge($this->show_files[$this->show_registers[$id]['file_index']], [
                'chk' => false,
            ]);
            unset($this->show_registers[$id]);
        }
    }

    /**
     * associate_files function
     *
     * This function try join files uploaded associatind a Name Files
     * with 'note' register in relation 'Notes' of 'Order Model' in
     * array Var.
     *
     * @return void
     */
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
                            'chk' => true,
                        ]);
                    }
                }
            }
        }
    }

    public function saveFile()
    {
        dd($this->files);
    }

    public function buscarMulti()
    {


        if ($this->advanceSearch) {

            $this->gotoPage(1);

            $this->search = "";

            $this->multiSearch = explode("\n", $this->advanceSearch);

            if(!count($this->multiSearch)) {
                $this->multiSearch = explode(" ", $this->advanceSearch);
            }

            if(!count($this->multiSearch)) {
                $this->multiSearch = explode(",", $this->advanceSearch);
            }

            if(!count($this->multiSearch)) {
                $this->multiSearch = explode(";", $this->advanceSearch);
            }

            $this->multiSearch = array_map('trim', $this->multiSearch);
        }

        // dd($this->multiSearch);

        if (count($this->multiSearch)) {
            $this->closeall();
        }
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');


        // $this->company_s = "";
        $this->selected = [];
        // $this->user_s = "";
        // $this->type = "";
        // $this->additionalData = [];
        // $this->advanceSearch = "";
        // $this->search = "";
        $this->gotoPage(1);


        $this->emit('refresh_dispatch');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Adicionar os IDs ausentes de $selected
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


    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }


        $query = Order::Query();

        $query->with('Operations', 'Note')

        ->when($this->search, function ($q) {
            $this->gotoPage(1);
            $this->advanceSearch = "";


            return $q->where(function ($query) {
                $query->where('ordem', 'like', trim($this->search))
                    ->orWhereRelation('Note', 'note', 'like', trim($this->search));
            });
        });

        if (count($this->multiSearch)) {

            // $query->whereIn('ordem', $this->multiSearch);
            $query->where(function ($q) {
                return $q->WhereRelation('Note', function ($query) {
                    $query->whereIn('note', $this->multiSearch);
                })->orWhereIn('ordem', $this->multiSearch);
            });
        }

        $query->join('notes', 'orders.note_id', '=', 'notes.id')
        ->where('statusSist', 'like', 'ABER%');
        // ->when($this->search, function ($q) {
        //     $this->gotoPage(1);
        //     $this->advanceSearch = "";
        //     $this->multiSearch = [];

        //     return $q->where(function ($query) {
        //         $query->where('ordem', 'like', '%'.$this->search.'%')
        //             ->orWhereRelation('Note', 'note', 'like', '%'.$this->search.'%');
        //     });
        // });

        if (count($this->multiSearch)) {
            $query->whereIn('ordem', $this->multiSearch);
            // $query->where(function ($q) {
            //     return $q->WhereRelation('Note', function ($query) {
            //         $query->whereIn('note', $this->multiSearch);
            //     })->orWhereIn('ordem', $this->multiSearch);
            // });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['cenTrab'])) {
            $query->whereRelation('Operations', function ($query) {
                $query->where('operacao', '0010')
                    ->where('status', 'like', 'ABER%')
                    ->whereIn('cenTrab', $_SESSION['filter'][$this->filter_group]['cenTrab'])
                    ->orWhere('cenTrab', '');
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['cidade'])) {
            $query->whereRelation('Note', function ($query) {
                $query->whereIn('lexp', $_SESSION['filter'][$this->filter_group]['cidade'])
                    ->orWhere('lexp', '');
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['rubrica'])) {
            $query->whereRelation('Note', function ($query) {
                $query->whereIn('rubrica', $_SESSION['filter'][$this->filter_group]['rubrica'])
                    ->orWhere('rubrica', '');
            });
        }

        if ($this->typeNote) {
            $query->whereRelation('Note', function ($query) {
                $query->where('type_note', $this->typeNote);
            });
        }


        $query->select('orders.*', 'notes.id as myNote_id', 'notes.days_left as myDayLeft', 'notes.type_note as myTypeNote', 'notes.note as myNote')
        ->orderBy('myTypeNote', "DESC")
        ->orderBy('myDayLeft')
        ->orderBy('myNote');


        return $query->paginate($this->perPage);

    }

    public function render()
    {
        if (empty(array_diff($this->lists->pluck('id')->toArray(), $this->selected))) {
            $this->selectAll = true;
        } else {
            $this->selectAll = false;
        }

        return view('livewire.construction.hiring.main', [
            'lists' => $this->lists
        ]);
    }
}
