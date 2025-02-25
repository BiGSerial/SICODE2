<?php

namespace App\Http\Livewire\Responsible;

use App\Helpers\TextFormatter;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalList extends Component
{
    use WithPagination;
    use TextFormatter;


    protected $paginationTheme = 'bootstrap';

    public $allCenters = false;
    public $typeNote = '';
    public $search;
    public $advanceSearch = '';
    public $multinotas = [];
    public $selected = [];
    public $select_all = false;

    private $filter_group = 'analises';
    private $filter;

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'search' => ['except' => '', 'as' => 'busca'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirm_att',
    ];

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->gotoPage(1);
            $this->multinotas = $this->formatTextToArray($this->advanceSearch);
            $this->dispatchBrowserEvent('hideModal');
        }

    }


    public function setSelectAll()
    {
        $ids = $this->lists->pluck('id')->toArray();

        if (!$this->select_all) {
            $this->selected = array_unique(array_merge($this->selected, $ids));
            $this->select_all = true;
        } else {
            $this->selected = array_diff($this->selected, $ids);
            $this->select_all = false;
        }
    }

    public function chkAllSelected($ids)
    {

        $ids = $ids->pluck('id')->toArray();

        // dd(empty(array_diff($ids, $this->selected)));
        return empty(array_diff($ids, $this->selected));
    }

    public function onlySelected($id)
    {
        $this->selected[] = $id;

        $this->preAtt();
    }



    public function preAtt()
    {

        $this->selected = array_unique($this->selected);

        if (!count($this->selected) > 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para assumir!',
                'timer'    => 2500,
            ]);

            return;
        }

        $count = count($this->selected);

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmação de Atribuição',
            'msg'           => "Você está prestes a assumir <strong>{$count}</strong> nota(s) para Analisar Projeto.
                <p class='border border-1 rounded text-bg-secondary p-1 mt-2'>É válido lembrar que existe um prazo para analisar os projetos e dar uma definição. Caso vença o
                tempo sem definição, o sistema automáticamente irá aprovar e seguir para contratação.</p>
                <p class='fw-bold'>Deseja prosseguir?</p>
                ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Assumir!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_att',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/Ov foi assumida.',

        ]);


    }


    public function confirm_att()
    {



        $notes = Note::find($this->selected);

        if ($notes->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi encontrada para assumir! <p>Por favor, tente novamente.</p>',
                'timer'    => 2500,
            ]);

            return;            # code...
        }

        DB::beginTransaction();

        foreach ($notes as $note) {

            if (!$note->Approval()->exists()) {
                try {
                    $note->Approval()->create([

                        'user_id'     => auth()->id(),
                        'status'      => $note->nstats,
                        'dt_status'   => $note->dt_status,
                    ]);

                } catch (\Throwable $th) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'Erro ao assumir Notas/Ov',
                        'html'      => 'Erro: ' . $th->getMessage(),
                        // 'timer'    => 2500,
                    ]);

                    DB::rollBack();

                    return;
                }
            }

        }

        DB::commit();

        $this->clearAll();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas assumidas com sucesso',
            'timer'    => 2500,
        ]);

    }



    public function clearAll()
    {
        $this->search = '';
        $this->advanceSearch = '';
        $this->multinotas = [];
        $this->selected = [];
        $this->gotoPage(1);
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
            if (!$this->allCenters) {
                $q->where('statusSist', 'not like', 'ENTE%')
                    ->where('statusSist', 'not like', 'ENCE%')
                    ->where(function ($q) {
                        $q->whereRelation('Operations', function ($sq) {
                            $sq->where('operacao', '0010')
                                ->where('status', 'like', 'ABER%');
                        });
                    });
            }
        })
        ->where(function ($q) {
            $q->whereDoesntHave('Approval')
            ->where(function ($query) {
                $query->whereNotIn('txpriority', ['Emergente'])
                ->orWhereNotIn('group5', ['DSR-Cus.Cliente/Ener', 'DSR-Cus.Tot.Cliente']);
                ;
            });

        })
        ->with([
            'orders' => function ($q) {
                $q->where('statusSist', 'not like', 'ENT%')
                    ->where('statusSist', 'not like', 'ENC%')
                    ->orderBy('ordem');
            },
            'orders.operations' => function ($q) {
                $q->where('operacao', '0010');
            },
        ]);

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        if ($this->search) {
            $this->multinotas = [];
            $query->where(function ($q) {
                $q->where('note', 'like', "%{$this->search}%")
                    ->orWhereRelation('orders', function ($q) {
                        $q->where('ordem', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->multinotas) {
            $query->where(function ($q) {
                $q->whereIn('note', $this->multinotas)
                    ->orWhereRelation('orders', function ($q) {
                        $q->whereIn('ordem', $this->multinotas);
                    });
            });
        }



        if (isset($this->filter['city'])) {
            $query->whereIn('lexp', $this->filter['city']);
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['operacao'])) {
            $query->whereRelation('orders.operations', function ($q) {
                $q->where('operacao', '0010')
                    ->whereIn('cenTrab', $this->filter['operacao']);
            });
        }

        return $query
                ->orderBy('type_note', 'DESC')
                ->orderBy('dt_status', 'ASC')
                ->paginate(50);
    }


    public function render()
    {
        return view('livewire.responsible.approval-list', [
            'lists' => $this->lists,
        ]);
    }
}
