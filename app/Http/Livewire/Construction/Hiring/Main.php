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

    protected $listeners = [
        'refresh_list'      => '$refresh',
        'goClean'           => 'closeall',
        'confirm_viability' => 'confirm_viability',
        'confirm_return' => 'confirm_return',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
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

    public function updatedTypeNote()
    {
        $this->gotoPage(0);
    }

    public function export_excel()
    {
        if (count($this->selected)) {

            $query = Order::with('Operations', 'Note.Files', 'Viabilities')
                ->find($this->selected);

            return (new HiringListExport($query))->download(date('YmdHis-') . 'exportOrdersList.xlsx');

        } else {
            if (!(session_status() == PHP_SESSION_ACTIVE)) {
                session_start();
            }

            if (isset($_SESSION['filter'][$this->filter_group])) {
                $this->filter = $_SESSION['filter'][$this->filter_group];
            }

            $query = Order::Query();

            $query->with('Operations', 'Note.Files', 'Note.Orders', 'Viabilities')
                ->when($this->search, function ($q) {
                    $this->advanceSearch = '';
                    $this->advanceSearch = '';
                    $this->gotoPage(1);


                    return $q->where(function ($query) {
                        $query->where('ordem', 'like', trim($this->search))
                            ->orWhereRelation('Note', 'note', 'like', trim($this->search));
                    });
                });



            $query->join('notes', 'orders.note_id', '=', 'notes.id');


            if (!$this->allCenters) {
                $query->where('statusSist', 'not like', 'ENTE%')
                      ->where('statusSist', 'not like', 'ENCE%')
                      ->where(function ($q) {
                          $q->whereRelation('Operations', function ($sq) {
                              $sq->where('operacao', '0010')
                                  ->where('status', 'not like', 'CONF%');
                          });
                      });

            }

            $query->where(function ($q) {
                return $q->whereRelation('Note', function ($query) {
                    $query->where(function ($qq) {
                        $qq->WhereIn('nstats', [46, 47, 48, 49, 50])
                            ->whereNotIn('rubrica', ['Incoporação'])
                            ->where('type_note', 2);
                    })->orWhere(function ($qq) {
                        $qq->Where('type_note', 1)

                            ->when(!$this->allCenters, function ($q) {
                                return $q->where('centerjob', 'like', 'VIAB%');
                            })

                            ->orWhere(function ($qq) {
                                $qq->Where('centerjob', '')
                                    ->Where('type_note', 1);
                            });
                    });
                });
            });


            // if (count($this->multiSearch)) {
            //     $query->whereIn('ordem', $this->multiSearch);

            // }

            if ($this->multiSearch) {


                // $query->whereIn('ordem', $this->multiSearch);
                $query->where(function ($q) {
                    return $q->WhereRelation('Note', function ($query) {
                        $query->whereIn('note', $this->multiSearch);
                    })->orWhereIn('ordem', $this->multiSearch);
                });
            }

            if (isset($_SESSION['filter'][$this->filter_group]['empreiteira'])) {
                $query->whereRelation('Operations', function ($query) {
                    $query->where('operacao', '0010')
                        ->where('status', 'like', 'ABER%')
                        ->whereIn('cenTrab', $_SESSION['filter'][$this->filter_group]['empreiteira'])
                        ->orWhere('cenTrab', '');
                });
            }

            if (isset($_SESSION['filter'][$this->filter_group]['city'])) {
                $query->whereRelation('Note', function ($query) {
                    $query->whereIn('lexp', $_SESSION['filter'][$this->filter_group]['city'])
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

            return (new HiringListExport($send))->download(date('YmdHis-') . 'exportOrdersList.xlsx');
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

            $this->emit('go_viability', $this->selected);

            return;

            $orders = Order::with('Note.Files')->find($this->selected);

            if ($orders) {

                foreach ($orders as $order) {

                    $this->show_registers[$order->id] = [
                        'id'          => $order->id,
                        'note_id'     => $order->Note->id,
                        'order'       => $order->ordem,
                        'note'        => $order->Note->note,
                        'file_index'  => '',
                        'files'       => [],
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
            $this->show_returns = Note::whereRelation('Orders', function ($q) {
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

                // To Viability, but has possibiliti send with hired
                foreach ($this->show_registers as $register) {

                    $viability = Viability::Create([
                        'order_id'    => $register['id'],
                        'company_id'  => $this->company_s,
                        'user_id'     => Auth()->User()->id,
                        'engineer_id' => $this->engineer_s,
                        'sended_at'   => date('Y-m-d H:i:s'),
                        'hired'       => $this->hiring ? true : false,
                        'hired_at'    => $this->hiring ? date('Y-m-d H:i:s') : null,
                        'status'      => 1,
                    ]);

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

                    if (!$viability) {
                        $erro = true;

                    }
                }
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

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

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
                $zipFile = 'Aruivos-Lote-' . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString($file->file_name . '.' . $file->ext, $content);
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
                            'chk'     => true,
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
            $this->closeall();
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
        $this->show_registers = [];
        $this->gotoPage(1);

        $this->emit('refresh_list');
    }

    public function setSelectAll()
    {

        if ($this->selectAll) {
            // Adicionar os IDs que cumprem as regras à lista de selecionados
            foreach ($this->lists as $item) {
                $id = $item->id;
                if (!in_array($id, $this->selected)) {
                    $viabilitiesCount = $item->Viabilities->count();
                    $waitingsCount = $item->Note->Waitings->where('complete', false)->count();
                    $status = $item->statusSist;

                    if ($viabilitiesCount == 0 && $waitingsCount == 0 &&
                        stripos($status, 'ENCE') === false && stripos($status, 'ENTE') === false) {
                        $this->selected[] = $id;
                    }
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

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Order::Query();

        $query->with('Operations', 'Note.Files', 'Note.Orders', 'Viabilities')
            ->when($this->search, function ($q) {
                $this->advanceSearch = '';
                $this->advanceSearch = '';
                $this->gotoPage(1);


                return $q->where(function ($query) {
                    $query->where('ordem', 'like', trim($this->search))
                        ->orWhereRelation('Note', 'note', 'like', trim($this->search));
                });
            });



        $query->join('notes', 'orders.note_id', '=', 'notes.id');


        if (!$this->allCenters) {
            $query->where('statusSist', 'not like', 'ENTE%')
                  ->where('statusSist', 'not like', 'ENCE%')
                  ->where(function ($q) {
                      $q->whereRelation('Operations', function ($sq) {
                          $sq->where('operacao', '0010')
                              ->where('status', 'not like', 'CONF%');
                      });
                  });

        }

        $query->where(function ($q) {
            return $q->whereRelation('Note', function ($query) {
                $query->where(function ($qq) {
                    $qq->WhereIn('nstats', [46, 47, 48, 49, 50])
                        ->whereNotIn('rubrica', ['Incoporação'])
                        ->where('type_note', 2);
                })->orWhere(function ($qq) {
                    $qq->Where('type_note', 1)

                        ->when(!$this->allCenters, function ($q) {
                            return $q->where('centerjob', 'like', 'VIAB%');
                        })

                        ->orWhere(function ($qq) {
                            $qq->Where('centerjob', '')
                                ->Where('type_note', 1);
                        });
                });
            });
        });


        // if (count($this->multiSearch)) {
        //     $query->whereIn('ordem', $this->multiSearch);

        // }

        if ($this->multiSearch) {


            // $query->whereIn('ordem', $this->multiSearch);
            $query->where(function ($q) {
                return $q->WhereRelation('Note', function ($query) {
                    $query->whereIn('note', $this->multiSearch);
                })->orWhereIn('ordem', $this->multiSearch);
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['empreiteira'])) {
            $query->whereRelation('Operations', function ($query) {
                $query->where('operacao', '0010')
                    ->where('status', 'like', 'ABER%')
                    ->whereIn('cenTrab', $_SESSION['filter'][$this->filter_group]['empreiteira'])
                    ->orWhere('cenTrab', '');
            });
        }

        if (isset($_SESSION['filter'][$this->filter_group]['city'])) {
            $query->whereRelation('Note', function ($query) {
                $query->whereIn('lexp', $_SESSION['filter'][$this->filter_group]['city'])
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

        $query->select('orders.*', 'notes.id as myNote_id', 'notes.days_left as myDayLeft', 'notes.type_note as myTypeNote', 'notes.note as myNote', 'notes.mesalization as mesalization')
            ->orderBy('mesalization', 'ASC')
            ->orderBy('myTypeNote', 'ASC')
            ->orderBy('myDayLeft')
            ->orderBy('myNote');

        return $query->paginate($this->perPage);

    }

    public function copyClipboard()
    {
        if (count($this->selected)) {
            $orders = Order::join('notes', 'orders.note_id', '=', 'notes.id')->with('Operations', 'Note.Files')
            ->select('orders.*', 'notes.id as myNote_id', 'notes.days_left as myDayLeft', 'notes.type_note as myTypeNote', 'notes.note as myNote')
            ->orderBy('myTypeNote', 'DESC')
            ->orderBy('myDayLeft')
            ->orderBy('myNote')
            ->find($this->selected);

            if ($orders) {
                foreach ($orders as $order) {

                    $this->clipboardData[] = [
                        $order->ordem,
                        $order->Note->note,
                        $order->pep ?? ''
                    ];
                }

                $this->dispatchBrowserEvent('copyToBoard', $this->clipboardData);

                $this->dispatchBrowserEvent('torrada', [
                    'status'   => 'success',
                    'menssage' => "Copiado para a área de transferência",
                ]);
            }
        }
    }

    public function go_return()
    {
        // Checka a seleção dos retornos
        if (!$this->service_s || !$this->category || strlen(trim($this->comment)) < 10) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CAMPOS OBRIGATÓRIOS',
                'html'     => 'Existem campos obirgatórios não atendidos. Verifique e tente novamente',
                'timer'    => 5000,
            ]);

            return;
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => "DESEJA RETORNAR AS NOTAS SELECIONADAS?",
                'msg'           => "Você está preste a retornar as NOTAS/OVs para o serviço selecionado. Tem certeza que deseja realmente retornar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Retorne!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_return',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Ordem foi Enviada!',

            ]);

            return;
        }

    }

    public function confirm_return()
    {
        if (!($this->show_returns && $this->show_returns->count())) {
            return;
        }

        DB::beginTransaction();

        $error = 0;
        $debug = [];


        foreach ($this->show_returns as $register) {
            try {
                if ($register->Productions->Where('completed', true)->Where('service_id', $this->service_s)->last()) {
                    $last_user = $register->Productions->Where('completed', true)->Where('service_id', $this->service_s)->last()->User;
                    $last_user_company_id = $last_user->Employee->Contract->company->id ?? null;

                    // Criar HiringWaiting
                    $hiringWaiting = HiringWaiting::create([
                        'note_id' => $register->id,
                        'user_id' => Auth()->user()->id,
                        'category' => $this->category,
                    ]);

                    // Criar Reclaim associado ao HiringWaiting
                    $reclaim = Reclaim::create([
                        'note_id' => $register->id,
                        'service_id' => $this->service_s,
                        'category' => $this->category,
                    ]);

                    $comment = $reclaim->Comments()->create([
                        'user_id' => Auth()->user()->id,
                        'message' => $this->comment
                    ]);

                    // Criar Production associado ao Reclaim
                    $production = Production::create([
                        'note_id' => $register->id,
                        'service_id' => $this->service_s,
                        'user_id' => $last_user->id,
                        'company_id' => $last_user_company_id,
                        'dispatch_by' => Auth()->user()->id,
                        'att_by' => Auth()->user()->id,
                        'dt_note' => $register->dt_status,
                        'status_note' => $register->nstats,
                        'dispatch_at' => now(),
                        'att_at' => now(),
                        'returned' => true,
                        'priority' => false,
                        'status' => 2,
                        'dhstats' => $register->dt_status,
                        'centroTrab' => $register->centerjob,
                        'd5' => true,
                    ]);

                    $hiringWaiting->Reclaim()->associate($reclaim);
                    $reclaim->Production()->associate($production);

                    $hiringWaiting->save();
                    $reclaim->save();

                } else {

                    // Criar HiringWaiting
                    $hiringWaiting = HiringWaiting::create([
                        'note_id' => $register->id,
                        'user_id' => Auth()->user()->id,
                        'category' => $this->category,
                    ]);

                    // Criar Reclaim associado ao HiringWaiting
                    $reclaim = Reclaim::create([
                        'note_id' => $register->id,
                        'service_id' => $this->service_s,
                    ]);

                    $comment = $reclaim->Comments()->create([
                        'user_id' => Auth()->user()->id,
                        'message' => $this->comment
                    ]);



                    $hiringWaiting->Reclaim()->associate($reclaim);
                    $hiringWaiting->save();

                }

            } catch (\Throwable $th) {
                $error++;
                $debug[] = $th->getMessage();
            }

        }

        if (!$error) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'RETORNO INTERNO CRIADO COM SUCESSO',
                'timer'    => 2500,
            ]);

            DB::commit();

            $this->closeall();

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO CRIAR O RETORNO INTERNO',
                'html'    => "Por algum motivo ocorreu ({$error}) erro(s) ao retornar as notas/ovs para serviços. Tente Novamente.",
                'timer'    => 2500,
            ]);

            DB::rollback();

        }

    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function render()
    {
        // if (empty(array_diff($this->lists->pluck('id')->toArray(), $this->selected))) {
        //     $this->selectAll = true;
        // } else {
        //     $this->selectAll = false;
        // }



        return view('livewire.construction.hiring.main', [
            'lists' => $this->lists,
        ]);
    }
}
