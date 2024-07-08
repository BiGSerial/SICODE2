<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Exports\HiringAccompanyExport;
use App\Models\{Company, File, Note, Order, Service, User, Viability};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{DB, Storage};
use Livewire\{Component, WithFileUploads, WithPagination};
use ZipArchive;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as PDF;

class Accompany extends Component
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

    public $perPage = 50;

    public $hirings;

    public $selectAllHirings;

    public $hiringSelected = [];

    //Selects
    public $companies = null;

    public $company_s;

    public $engineers = null;

    public $engineer_s;

    // Filters
    private $filter_group = 'hiring';

    private $filter;

    protected $listeners = [
        'refresh_list'      => '$refresh',
        'confirm_viability' => 'confirm_viability',
        'confirm_contract' => 'confirm_contract',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],

    ];

    public function mount($service)
    {
        if ($this->perPage > 500) {
            $this->perPage = 500;
        }

        $this->service   = Service::where('uuid', $service)->first();
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
    }

    public function to_contract()
    {
        if (!count($this->hiringSelected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma Obra Selecionada',
                'html'     => 'Nenhuma obra selecionada para contratação. Gentileza selecionar as ordens que deseja encerrar.',
                'timer'    => 5000,
            ]);

            return;
        } else {
            $count = count($this->hiringSelected);
            $text = "
            <div class='card'>
            <div class='card-body'>
                <p>Você está prestes a contratar <span class='fw-bold'>{$count}</span> obras.</p>
                <p class='text-justify fs-6'>
                    É válido lembrar que a contratação pelo SICODE é apenas para controle e não substitui o SAP. Espera-se que todas as etapas referentes às obras tenham sido realizadas no SAP.
                </p>
            </div>
        </div>
        ";

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Contratação?',
                'msg'           => $text,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Contrate!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_contract',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Obra Contratada!',

            ]);

            return;
        }
    }

    public function confirm_contract()
    {
        if (count($this->hiringSelected)) {
            $hirings = Viability::whereRelation('Order.Note', function ($query) {
                return $query->whereIn('id', $this->hiringSelected);
            })->get();

            if ($hirings->count()) {

                DB::beginTransaction();

                $erro = false;

                foreach ($hirings as $hiring) {

                    try {

                        $register = $hiring->update([
                            'completed' => true,
                            'completed_at' => date('Y-m-d H:i:s'),
                            'hired' => true,
                            'hired_at' => date('Y-m-d H:i:s'),
                            'status' => 9
                        ]);

                        if ($register) {
                            $hiring->Comments()->create([
                                'user_id' => Auth()->User()->id,
                                'message' => "Contratado em " . date('d/m/Y') . " as " .  date('H:i:s') . ". Por confirmação manual, pós viabilidade."
                            ]);
                        }
                    } catch (\Throwable $th) {
                        $erro = true;
                    }
                }

                if ($erro) {

                    DB::rollBack();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'Erro ao Contratar',
                        'html'     => 'Ocorreram erro(s) ao tentar Contratar a Obra, verifique e tente novamente.',
                        'timer'    => 5000,
                    ]);

                    return;
                } else {

                    DB::commit();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Contratada(s) com Sucesso!',
                        'timer'    => 2500,
                    ]);

                    $this->closeall();
                }
            }
        }
    }

    public function export_excel()
    {
        if (count($this->selected)) {

            $export = Viability::whereRelation('Order.Note', function ($query) {
                return $query->whereIn('id', $this->selected);
            })->with('Order.Note');
        } else {

            $export = Viability::whereRelation('Order.Note', function ($query) {
                return $query->whereIn('id', $this->myhirings->pluck('id')->toArray());
            })->with('Order.Note');
        }

        return (new HiringAccompanyExport($export))->download(date('YmdHis-') . 'exportViabilityAccompany.xlsx');
    }

    public function export_excel_hiring()
    {
        if (count($this->hiringSelected)) {

            $export = Viability::whereRelation('Order.Note', function ($query) {
                return $query->whereIn('id', $this->hiringSelected);
            })->with('Order.Note');
        } else {


            $export = Viability::whereRelation('Order.Note', function ($query) {
                return $query->whereIn('id', $this->myhirings->pluck('id')->toArray());
            })->with('Order.Note');
        }

        return (new HiringAccompanyExport($export))->download(date('YmdHis-') . 'exportViabilityAccompany.xlsx');
    }

    public function updatedSelectAllHirings($value)
    {
        // dd($this->myhirings->pluck('id')->toArray());
        if ($value) {
            // Adicionar os IDs ausentes de $selected
            foreach ($this->myhirings->pluck('id')->toArray() as $id) {
                if (!in_array($id, $this->hiringSelected)) {
                    // dd();
                    $this->hiringSelected[] = $id;
                }
            }
        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];

            foreach ($this->hiringSelected as $id) {
                if (!in_array($id, $this->myhirings->pluck('id')->toArray())) {
                    $newSelected[] = $id;
                }
            }
            $this->hiringSelected = $newSelected;
        }
    }

    public function checkAllSelect($items)
    {
        $items = $items->pluck('id')->toArray();

        if (!array_diff($items,  $this->hiringSelected)) {
            return true;
        } else {
            return false;
        }
    }


    public function go_to_hiring()
    {
        $this->hirings = $this->myhirings;

        if ($this->hirings) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'hiring_jobs',
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma Obra Disponível para Contratação.',
                'html'     => 'Nas condições atuais, não foram encontradas Obras aptas a serem contratadas. Verifique a existência de filtros ativados, e se essas condições atendem os critérios desejados',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function go_att_mass()
    {

        if (count($this->selected)) {
            $orders = Order::with('Note.Files')->find($this->selected);

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
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhuma nota foi selecionada para Envio.',
                    'timer'    => 5000,
                ]);

                return;
            }

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'viability_modal',
            ]);
        }
    }

    public function edit($note)
    {

        $this->emit('edit_hiring', $note);
    }

    public function to_viability()
    {

        if ($this->company_s == '' && $this->engineer_s == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'É necessário selecionar a EMPREITEIRA e o ENGENHEIRO RESPONSÁVEL.',
                'timer'    => 5000,
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
                'title'         => 'Confirmar Envio para Viabilidade?',
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

            foreach ($this->show_registers as $register) {

                $viability = Viability::Create([
                    'order_id'    => $register['id'],
                    'company_id'  => $this->company_s,
                    'user_id'     => Auth()->User()->id,
                    'engineer_id' => $this->engineer_s,
                    'sended_at'   => date('Y-m-d H:i:s'),
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
                'icon'     => 'error',
                'title'    => 'TIVEMOS UM ERRO INESPERADO, NENHUM REGISTRO FOI EXECUTADO.',
                'timer'    => 5000,
            ]);
        } else {

            DB::commit();

            $this->closeall();

            $this->dispatchBrowserEvent(
                'swal',
                [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Registro Efetuado com Sucesso.',
                    'timer'    => 5000,
                ]
            );
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
            $files = File::WhereIn('note_id', Note::find($this->selected)->pluck('id'))->get();



            if ($files->count()) {
                $zipFile = 'Arquivos-Lote-' . hash('crc32', time()) . '.zip';
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
            $this->dispatchBrowserEvent(
                'swal',
                [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Nenhum Arquivo Selecionados para Download.',
                    'timer'    => 5000,
                ]
            );
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

            $this->search = '';

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

        // dd($this->multiSearch);

        if (count($this->multiSearch)) {
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
        $this->hiringSelected = [];

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

    public function getNotesProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Note::query();

        $query->with('Viabilities.Company', 'Viabilities.Order', 'Files')
            ->whereHas('Viabilities', function ($q) {
                $q->where('completed', false)
                    ->orderBy('sended_at');
            });

        if ($this->search) {
            $query->where('note', 'like', '%' . $this->search . '%');
        }

        if (isset($this->filter['cidade'])) {
            $query->whereIn('lexp', $this->filter['cidade']);
        }

        if (isset($this->filter['empreiteira'])) {
            $query->whereHas('Viabilities', function ($q) {
                $q->whereIn('company_id', $this->filter['empreiteira']);
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }




        return $query; // Executar a consulta e retornar os resultados
    }



    public function getMyhiringsProperty()
    {

        return Note::whereDoesntHave('viabilities', function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('completed', true)
                    ->orWhere('approved', false);
            });
        })
            ->whereHas('viabilities', function ($query) {
                $query->where('approved', true)
                    ->where('completed', false);
            })->with('Viabilities.Company')
            ->find($this->notes->get()->pluck('id'));
    }



    public function getListsProperty()
    {

        $lists = $this->notes;

        return $lists->paginate($this->perPage);
    }

    public function render()
    {
        // if (empty(array_diff($this->lists->pluck('id')->toArray(), $this->selected))) {
        //     $this->selectAll = true;
        // } else {
        //     $this->selectAll = false;
        // }

        return view('livewire.construction.hiring.accompany', [
            'lists' => $this->lists,
        ]);
    }
}
