<?php

namespace App\Http\Livewire\Engineers\Analises;

use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
    public $noAttribution = false;

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


    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
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

    public function toggleAtrtibution()
    {
        $this->noAttribution = !$this->noAttribution;
        $this->gotoPage(1);
    }



    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $sessionFilters = session('filter.' . $this->filter_group);
        if (is_array($sessionFilters)) {
            $this->filter = $sessionFilters;
        } elseif (isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        } else {
            $this->filter = [];
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
                $qq->where(function ($qs) {
                    $qs->where('type_note', 1)
                    ->where('centerjob', 'like', 'VIAB%');
                })
                ->orWhere(function ($qq) {
                    $qq->orWhereNull('centerjob')
                    ->where('type_note', 1);
                });
            });
        })
        ->whereHas('Orders', function ($q) {
            if (!$this->allCenters) {
                $q->where('statusSist', 'not like', 'ENTE%')
                  ->where('statusSist', 'not like', 'ENCE%')
                  ->whereHas('Operations', function ($sq) {
                      $sq->where('operacao', '0010')
                         ->where('status', 'like', 'ABER%');
                  });
            }
        })
        ->where(function ($q) {
            $q->whereDoesntHave('Approval', function ($q) {
                $q->where('approved', true);
            })
            ->whereDoesntHave('Viabilities')
            ->whereDoesntHave('Waitings');
        })
        ->where(function ($q) {
            $q->where('txpriority', '!=', 'Emergente')
              ->orWhereNull('txpriority');
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

        if ($this->noAttribution) {
            $query->whereDoesntHave('Approval');
        }

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        if ($this->search) {
            $search_term = "%{$this->search}%"; //Define a variável fora das closures
            $query->where(function ($q) use ($search_term) {
                $q->where('note', 'like', $search_term)
                  ->orWhereHas('orders', function ($q) use ($search_term) {
                      $q->where('ordem', 'like', $search_term);
                  });
            });
        }

        if ($this->multinotas) {
            $multinotas = $this->multinotas; //Define a variável fora das closures
            $query->where(function ($q) use ($multinotas) {
                $q->whereIn('note', $multinotas)
                  ->orWhereHas('orders', function ($q) use ($multinotas) {
                      $q->whereIn('ordem', $multinotas);
                  });
            });
        }


        $activeFilters = is_array($this->filter) ? $this->filter : [];
        $regionValues = collect((array) ($activeFilters['region'] ?? []))
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => trim((string) $v))
            ->values();

        $cityValues = collect((array) ($activeFilters['city'] ?? []))
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => trim((string) $v))
            ->values();

        if ($regionValues->isNotEmpty() || $cityValues->isNotEmpty()) {
            $nexpCodes = collect();

            $directCodes = $cityValues
                ->filter(fn ($v) => preg_match('/^\d+$/', $v) === 1)
                ->values();
            $nexpCodes = $nexpCodes->merge($directCodes);

            $mappedQuery = City::query();

            if ($regionValues->isNotEmpty()) {
                $mappedQuery->whereIn('baseConstrucao', $regionValues->all());
            }

            if ($cityValues->isNotEmpty()) {
                $mappedQuery->where(function ($sq) use ($cityValues) {
                    $sq->whereIn('cidade', $cityValues->all())
                        ->orWhereIn('municipio', $cityValues->all())
                        ->orWhereIn('rdMunicipio', $cityValues->all());
                });
            }

            $mappedCodes = $mappedQuery
                ->pluck('rdMunicipio')
                ->filter(fn ($v) => filled($v))
                ->map(fn ($v) => trim((string) $v))
                ->values();

            $nexpCodes = $nexpCodes
                ->merge($mappedCodes)
                ->unique()
                ->values();

            $query->whereIn('nexp', $nexpCodes->all());
        }

        if (isset($activeFilters['rubrica'])) {
            $query->whereIn('rubrica', $activeFilters['rubrica']);
        }

        if (isset($activeFilters['operacao'])) {
            $operacaoFilters = (array) $activeFilters['operacao'];
            $query->whereHas('orders.operations', function ($q) use ($operacaoFilters) {
                $q->where('operacao', '0010')
                  ->whereIn('cenTrab', $operacaoFilters);
            });
        }

        return $query
            ->orderBy('is45', 'DESC')
            ->orderBy('dt_status', 'ASC')
            ->paginate(50);
    }


    public function render()
    {
        return view('livewire.engineers.analises.approval-list', [
            'lists' => $this->lists,
        ]);
    }
}
