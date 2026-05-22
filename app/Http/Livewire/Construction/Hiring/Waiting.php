<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Company;
use App\Models\File;
use App\Models\HiringWaiting;
use App\Models\Note;
use App\Models\Operation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Waiting extends Component
{
    use WithPagination;
    use WithFileUploads;

    protected $paginationTheme = 'bootstrap';


    public $deleteNote;

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

    public $waiting;

    // Clipboard
    public $clipboardData = [];


    protected $listeners = [
        'refresh_list' => '$refresh',
        'refresh' => '$refresh',
        'confirm_viability' => 'confirm_viability',
        'cleanAll' => 'closeall',
        'giveBack' => 'giveBack',
        'deleteWaiting',
    ];

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
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
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
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
            $this->dispatchBrowserEvent('hideModal');
            $this->closeAll();
        }
    }


    public function exportExcel()
    {
        if (count($this->selected)) {

            return (new \App\Exports\Hiring\WaitingListExport($this->getQueryBase()->whereIn('id', $this->selected)))->download('lista_espera_contratacao_selecionador_'.now()->format('Y_m_d_H_i_s').'.xlsx');
        }

        return (new \App\Exports\Hiring\WaitingListExport($this->getQueryBase()))->download('lista_espera_contratacao_'.now()->format('Y_m_d_H_i_s').'.xlsx');
    }






    // Lógica para selecionar todos os registros
    public function setSelectAll()
    {

        if ($this->selectAll) {
            // Adicionar os IDs que cumprem as regras à lista de selecionados
            foreach ($this->lists as $item) {
                $id = $item->id;

                if (!in_array($id, $this->selected)) {
                    if ($item->Reclaim->completed) {
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

    public function openMultiNotas()
    {
        $this->dispatchBrowserEvent('showModal', [
            'id' => "modal_multi_notas",
        ]);
    }


    // Lógiva para verificar se todos os registros estão selecionados
    public function checkAllSelect($items)
    {

        $items = $items->filter(function ($item) {
            return $item->Reclaim->completed;
        })->pluck('id')->toArray();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
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



        $this->emitTo('construction.hiring.actions.waitinghiring', 'getNotes', $this->selected);

    }

    public function copyClipboard()
    {
        if (count($this->selected)) {
            $notes = Note::with('Orders.Operations', 'Files')
            ->whereIn('id', HiringWaiting::whereIn('id', $this->selected)->pluck('note_id'))
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left')
            ->orderBy('note')
            ->get();

            if ($notes) {
                foreach ($notes as $note) {
                    foreach ($note->Orders->filter(function ($order) {
                        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
                    }) as $order) {
                        $this->clipboardData[] = [
                            $order->ordem,
                            $order->Note->note,
                            $order->Note->pep,
                        ];
                    }
                }

                $this->dispatchBrowserEvent('copyToBoard', $this->clipboardData);

                $this->dispatchBrowserEvent('torrada', [
                    'status'   => 'success',
                    'menssage' => "Copiado para a área de transferência",
                ]);
            }
        }
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

    public function cancelWaiting($waiting)
    {
        $this->deleteNote = $waiting;

        if ($this->deleteNote) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => "Remopver Espera",
                'msg'           => "Deseja Remover a Espera de Resolução Interna?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'deleteWaiting',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma espera foi removida!',

            ]);

            return;
        }
    }

    public function deleteWaiting()
    {
        $this->deleteNote = HiringWaiting::find($this->deleteNote);


        // dd($this->deleteNote);

        DB::beginTransaction();

        try {

            if (isset($this->deleteNote->Reclaim->Production)) {

                $this->deleteNote->Reclaim->Production->delete();
            }

            if (isset($this->deleteNote->Reclaim)) {
                $this->deleteNote->Reclaim->delete();
            }

            $this->deleteNote->delete();

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Espera removida com Sucesso.',
                'timer'    => 5000,
            ]);

            $this->deleteNote = null;
            $this->emit('refresh_list');
        } catch (\Throwable $th) {

            dd($th->getMessage());

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum erro ao tentar remover espera.',
                'timer'    => 5000,
            ]);
            $this->deleteNote = null;
            DB::rollback();
        }
    }



    public function getQueryBase()
    {
        return HiringWaiting::where('complete', false)
            ->when($this->cjobes, function ($query) {
                $query->whereHas('Note.Orders.Operations', function ($subquery) {
                    $subquery->where('cenTrab', $this->cjobes)->where('operacao', '0010');
                });
            })
            ->when($this->typeNote, function ($query) {
                $query->whereHas('Note', function ($subquery) {
                    $subquery->where('type_note', $this->typeNote);
                });
            })
            ->when($this->search, function ($query) {

                $this->advanceSearch = '';
                $this->multiSearch = [];

                $query->whereHas('Note', function ($subquery) {
                    $subquery->where('note', 'like', '%' . $this->search . '%')
                        ->orWhereRelation('Orders', 'ordem', 'like', '%' . $this->search . '%');
                });

            })
            ->when($this->multiSearch, function ($query) {
                $query->whereHas('Note', function ($subquery) {
                    $subquery->whereIn('note', $this->multiSearch)
                            ->orWhereRelation('Orders', function ($q) {
                                $q->whereIn('ordem', $this->multiSearch);
                            });

                });
            })
            ->orderBy('created_at');
    }



    public function getListsProperty()
    {
        $query = $this->getQueryBase();

        $paginated = $query->paginate($this->perPage);

        // Carrega as relações apenas para os itens da página atual
        $paginated->getCollection()->load([
            'Note.Orders.Operations' => function ($query) {
                $query->where('operacao', '0010');
            },
            'Note.Files',
            'Reclaim.Production'
        ]);

        return $paginated;
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

    public function go_giveBack(HiringWaiting $waiting)
    {
        $this->waiting = $waiting;

        if ($this->waiting) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => "DEVOLVER (RI)",
                'msg'           => "Deseja devolver a RESOLUÇÃO INTERNA novamente para o Responsável pela Resolução?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Delvolva!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'giveBack',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Ordem foi Enviada!',

            ]);

            return;
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

    public function giveBack()
    {

        DB::beginTransaction();

        try {

            $this->waiting->update([
                'complete' => false,
            ]);

            $this->waiting->Reclaim->update([
                'completed' => false,
                'completed_at' => null,
            ]);

            $this->waiting->Reclaim->Production->update([
                'completed' => false,
                'completed_at' => null,
                'confirmed' => false,
                'confirmed_at' => null,
                'status' => 2,
                'priority' => true
            ]);

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'RESOLUÇÂO DEVOLVIDA',
                'timer'    => 5000,
            ]);

            $this->emit('refresh_list');

            // DB::rollback();

        } catch (\Throwable $th) {

            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ERRO AO RETORNAR',
                'html'     => $th->getMessage(),
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.construction.hiring.waiting', [
            'lists' => $this->lists,
            'centerJobs' => $this->centroTrab,
        ]);
    }
}
