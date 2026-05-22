<?php

namespace App\Http\Livewire\Responsible;

use App\Models\Company;
use App\Models\File;
use App\Models\Operation;
use App\Models\Service;
use App\Models\User;
use App\Models\Viability;
use App\Models\Note;
use App\Models\HiringWaiting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ViabWaiting extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';




    public $centerJobs;
    public $search;
    public $multiSearch = [];
    public $advanceSearch = '';
    public $action;
    public $perPage = 50;
    public $service;
    public $companies;
    public $engineers;
    public $services;
    public $clipboardData = [];
    public $cjobes;
    public $typeNote;
    public $company_id;


    public $dtStart;
    public $dtEnd;

    // Orderenação
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // Seleção
    public $selectAll = false;
    public $selected = [];


    protected $listeners = [
        'refresh_list' => '$refresh',
        'refresh' => '$refresh',
        'confirm_viability' => 'confirm_viability',
        'cleanAll' => 'closeall',
        'giveBack' => 'giveBack',
        'deleteWaiting',
        '7c22165caa5691e6f26883cc3654c5e0' => 'confirm_sendind',
    ];

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
    ];



    public function getListsProperty()
    {
        $query = Viability::query()
        ->with('Note', 'Orders', 'Company', 'Engineer')
        ->where('completed', false)
        ->when($this->dtStart, function ($query) {
            $query->where('created_at', '>=', $this->dtStart . ' 00:00:00');
        })
        ->when($this->dtEnd, function ($query) {
            $query->where('created_at', '<=', $this->dtEnd . ' 23:59:59');
        })
        ->when($this->dtStart && $this->dtEnd, function ($query) {
            $query->whereBetween('created_at', [$this->dtStart . ' 00:00:00', $this->dtEnd . ' 23:59:59']);
        })
        ->where('visible_partner', true)
        ->when($this->cjobes, function ($query) {
            $query->whereHas('Note.Orders.Operations', function ($subquery) {
                $subquery->where('cenTrab', $this->cjobes)->where('operacao', '0010');
            });
        });

        if ($this->search) {

            $this->advanceSearch = '';
            $this->multiSearch = [];

            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('Note.Orders', 'ordem', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->multiSearch) {
            $query->where(function ($q) {
                $q->whereRelation('Note', function ($sq) {
                    $sq->whereIn('type_note', $this->multiSearch);
                })
                ->orWhereRelation('Note.Orders', function ($sq) {
                    $sq->whereIn('ordem', $this->multiSearch);
                });
            });
        }

        if (!auth()->user()->superadm) {

            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->where(function ($q) {
            //         $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
            //         ->orWhere('company_id', Auth()->user()->Company->id);
            //     });
            // } else {
            //     $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        if ($this->typeNote) {
            $query->whereRelation('Note', 'type_note', $this->typeNote);
        }



        // Add the sum of the 'moaberto' column from the 'orders' relation
        $query->withSum('Orders', 'moaberto');

        return $query->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
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


    public function mount()
    {
        if ($this->perPage > 500) {
            $this->perPage = 500;
        }

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }




        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();
        $this->engineers = User::where('engineer', true)->Select('id', 'name')->orderBy('name')->get();
        $this->services  = Service::orderBy('service')->get();
    }


    public function openMultiNotas()
    {
        $this->dispatchBrowserEvent('showModal', [
            'id' => "modal_multi_notas",
        ]);
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


    // Lógica para selecionar todos os registros
    public function setSelectAll()
    {


        if ($this->selectAll) {
            // Adicionar os IDs que cumprem as regras à lista de selecionados
            foreach ($this->lists->paginate($this->perPage) as $item) {

                $id = $item->id;

                if (!in_array($id, $this->selected)) {
                    if ($item->visible_partner == true) {
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


    // Lógiva para verificar se todos os registros estão selecionados
    public function checkAllSelect($items)
    {

        $items = $items->filter(function ($item) {
            return !$item->hired && !$item->completed && $item->approved;
        })->pluck('id')->toArray();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Arquivo não encontrado.',
                'timer'    => 5000,
            ]);
        }
    }

    public function getCentroTrabProperty()
    {
        return Operation::where('operacao', '0010')
            // ->where('descOperacao', 'like', '%EMPREITAR E VIABIL%')
            ->select('cenTrab')
            ->orderBy('cenTrab')
            ->groupBy('cenTrab')
            ->get();
    }


    public function copyClipboard()
    {
        if (count($this->selected)) {
            $notes = Note::with('Orders.Operations', 'Files')
            ->whereIn('id', Viability::whereIn('id', $this->selected)->pluck('note_id'))
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

        // dd($this->clipboardData);
    }

    public function go_att_mass()
    {

        if (!$this->selected) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Nenhum registro selecionado.',
                'timer'    => 5000,
            ]);
            return;
        }

        $count = count($this->selected);


        $this->dispatchBrowserEvent('alertar', [
            'title'         => "CONFIRMAR LIBERAÇÃO",
            'msg'           => "
                <p>Deseja confirmar a liberação da(s) <span class='fw-bold'>{$count}</span> obra(s) para Viabilidade?</p>

            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Liberar!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => '7c22165caa5691e6f26883cc3654c5e0',
            'confirm'       => 'Sim envie',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Obra foi Liberada para Viabildiade!',

        ]);

        return;
    }

    public function confirm_sendind()
    {
        if (!$this->selected) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Nenhum registro selecionado.',
                'timer'    => 5000,
            ]);
            return;
        }

        $viabilities = Viability::whereIn('id', $this->selected)->get();

        DB::beginTransaction();

        try {

            if ($viabilities) {

                foreach ($viabilities as $viability) {
                    $viability->sended_at = now();
                    $viability->status = 1;
                    $viability->visible_partner = false;
                    $viability->save();
                }


                DB::commit();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'SUCESSO',
                    'html'     => 'Contratação confirmada com sucesso.',
                    'timer'    => 5000,
                ]);

                $this->closeall();

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ERRO',
                    'html'     => 'Nenhum registro encontrado.',
                    'timer'    => 5000,
                ]);
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Ocorreu um erro ao tentar confirmar a contratação, tente novamente.<br><br>' . $th->getMessage(),
                // 'timer'    => 10000,
            ]);
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

    public function render()
    {
        return view('livewire.responsible.viab-waiting', [
            'lists' => $this->lists,
            'centerJobers' => $this->centroTrab,
        ]);
    }
}
