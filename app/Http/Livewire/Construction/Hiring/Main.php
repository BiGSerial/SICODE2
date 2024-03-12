<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Exports\HiringListExport;
use App\Models\Company;
use App\Models\File;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use ZipArchive;

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
    public $show_existing_files = [];
    public $show_registers = [];

    public $perPage = 50;

    //Selects
    public $companies = null;
    public $company_s;
    public $engineers = null;
    public $engineer_s;

    // Filters
    private $filter_group = "hiring";
    private $filter;

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirm_viability' => 'confirm_viability',
    ];

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],

        ];

    public function mount($service)
    {
        if ($this->perPage > 500) {
            $this->perPage = 500;
        }

        $this->service = Service::where('uuid', $service)->first();
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
    }

    public function export_excel()
    {
        if (count($this->selected)) {


            $query = Order::with('Operations', 'Note.Files', 'Viabilities')
            ->find($this->selected);




            return (new HiringListExport($query))->download(date('YmdHis-').'exportOrdersList.xlsx');


        } else {
            if (!(session_status() == PHP_SESSION_ACTIVE)) {
                session_start();
            }

            if (isset($_SESSION['filter'][$this->filter_group])) {
                $this->filter = $_SESSION['filter'][$this->filter_group];
            }


            $query = Order::Query();

            $query->with('Operations', 'Note.Files', 'Viabilities')

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

            $query->where('statusSist', 'like', 'ABER%')
            ->where(function ($q) {
                return $q->whereRelation('Note', function ($query) {
                    $query->where(function ($qq) {
                        $qq->WhereIn('nstats', [46,47,48,49,50])
                            ->where('type_note', 2);
                    })->orWhere(function ($qq) {
                        $qq->Where('centerjob', 'like', 'VIAB%')
                        ->Where('type_note', 1)
                        ->orWhere(function ($qq) {
                            $qq->Where('centerjob', '')
                            ->Where('type_note', 1);
                        });
                    });
                });
            });


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


            $send = $query->get();



            return (new HiringListExport($send))->download(date('YmdHis-').'exportOrdersList.xlsx');
        }
    }

    public function go_att_mass()
    {

        if (count($this->selected)) {
            $orders = Order::with('Note.Files')->find($this->selected);

            if ($orders) {
                foreach ($orders as $order) {

                    $this->show_registers[$order->id] = [
                        'id' => $order->id,
                        'note_id' => $order->Note->id,
                        'order' => $order->ordem,
                        'note' => $order->Note->note,
                        'file_index' => '',
                        'file_online' => false,
                    ];


                    if ($order->Note->Files->count()) {

                        foreach ($order->Note->Files as $file) {
                            $this->show_existing_files[$order->id] = [
                                'id' => $order->id,
                                'name' => $file->file_name,
                                'ext' => $file->ext,
                                'chk' => false,
                            ];
                        }

                        $this->show_registers[$order->id] = array_merge($this->show_registers[$order->id], ['file_online' => true]);
                    }
                }

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Nenhuma nota foi selecionada para Envio.',
                    'timer' => 5000,
                ]);

                return;
            }


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'viability_modal'
            ]);
        }

    }

    public function to_viability()
    {

        if ($this->company_s == '' && $this->engineer_s == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'É necessário selecionar a EMPREITEIRA e o ENGENHEIRO RESPONSÁVEL.',
                'timer' => 5000,
            ]);

            return;

        } else {
            $company = $this->companies->where('id', $this->company_s)->first()->name;

            // dd($this->engineer_s, $this->engineers);

            $engineer = $this->engineers->where('id', $this->engineer_s)->first()->name;
        }

        if (count($this->show_registers)) {

            $count = count($this->show_registers);

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

            $this->dispatchBrowserEvent('alertar', [
                'title' =>  'Confirmar Envio para Viabilidade?',
                'msg' => $text,
                'icon' => 'warning',
                'btnOktxt' => 'Sim, Despache!',
                'btnCanceltxt' => 'Não, Cancele',
                'action' => "confirm_viability",
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg' => 'Nenhuma Ordem foi Enviada!',

            ]);

            return;
        }
    }

    public function confirm_viability()
    {
        DB::beginTransaction();

        $erro = false;

        if (count($this->show_files)) {

            foreach ($this->show_files as $temp_file) {

                $caminho = '';

                if (isset($this->files[$temp_file['id']])) {

                    $caminho = $this->files[$temp_file['id']]->store('/arquivos');

                    if ($caminho) {

                        $file = File::create([
                                    'note_id' => $temp_file['note_id'],
                                    'user_id' => Auth()->User()->id,
                                    'service_id' => $this->service->uuid,
                                    'file_name' => $temp_file['name'],
                                    'path' => $caminho,
                                    'ext' => $temp_file['ext'],
                                ]);

                        if (!$file) {
                            $erro = true;
                        }

                    }

                }

            }
        }

        if (count($this->show_registers)) {

            foreach ($this->show_registers as $register) {

                $viability = Viability::Create([
                    'order_id' => $register['id'],
                    'company_id' => $this->company_s,
                    'user_id' => Auth()->User()->id,
                    'engineer_id' => $this->engineer_s,
                    'sended_at' => date('Y-m-d H:i:s'),
                ]);

                if (!$viability) {
                    $erro = true;


                }
            }
        }

        if ($erro) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'TIVEMOS UM ERRO INESPERADO, NENHUM REGISTRO FOI EXECUTADO.',
                'timer' => 5000,
            ]);

        } else {

            DB::commit();

            $this->closeall();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Registro Efetuado com Sucesso.',
                'timer' => 5000,
            ]);
        }
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)->first()) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function downloadZip()
    {
        if (count($this->selected)) {
            $files = File::WhereIn('note_id', Order::find($this->selected)->pluck('note_id'))->get();

            if ($files) {
                $zipFile = "Aruivos-Lote-".hash('crc32', time()).".zip";
                $zip = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString($file->file_name.".".$file->ext, $content);
                }

                $zip->close();

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        } else {

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
                        'id' => $index,
                        'note_id' => "",
                        'name' => explode('.', $file->getClientOriginalName())[0],
                        'ext' => $file->getClientOriginalExtension(),
                        'chk' => false,
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


        $this->company_s = "";
        $this->selected = [];
        $this->engineer_s = "";
        $this->show_files = [];
        $this->show_registers = [];
        $this->gotoPage(1);


        $this->emit('refresh_list');
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

        $query->with('Operations', 'Note.Files', 'Viabilities')

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
        ->where('statusSist', 'like', 'ABER%')
        ->where(function ($q) {
            return $q->whereRelation('Note', function ($query) {
                $query->where(function ($qq) {
                    $qq->WhereIn('nstats', [46,47,48,49,50])
                        ->where('type_note', 2);
                })->orWhere(function ($qq) {
                    $qq->Where('centerjob', 'like', 'VIAB%')
                    ->Where('type_note', 1)
                    ->orWhere(function ($qq) {
                        $qq->Where('centerjob', '')
                        ->Where('type_note', 1);
                    });
                });
            });
        });
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
