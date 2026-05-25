<?php

namespace App\Http\Livewire\Responsible;

use App\Exports\Responsible\Projeto\ControlExport;
use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalControl extends Component
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
    public $onlyFinished = false;

    private $filter_group = 'analises';
    private $filter;

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'search' => ['except' => '', 'as' => 'busca'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'update_list'  => '$refresh',
        'savedFiles',
        'confirm_approved',
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

    public function savedFiles()
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Ação concluída com sucesso!',
            'timer'    => 2500,
        ]);

        $this->emitTo('responsible.actions.reject-project', 'clearAll');

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

    public function export_excel()
    {
        return (new ControlExport($this->selected))->download('controle_aprovacao.xlsx');
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
        $this->selected = [$id];

        $this->preMassApprove();
    }



    public function preMassApprove()
    {
        if ($this->selected) {
            $this->selected = array_map('intval', $this->selected);
        }


        $this->selected = array_unique($this->selected);

        if (!count($this->selected) > 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TEXTO INVÁLIDO!',
                'html'     => 'Nenhuma nota foi selecionada para assumir! <p>Por favor, tente novamente.</p>',
                'timer'    => 2500,
            ]);

            return;
        }

        $count = count($this->selected);

        $notes = Note::select('note')->find($this->selected)->pluck('note')->toArray();
        $notes = implode(', ', $notes);

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmação de Liberação',
            'msg'           => "Você está prestes a aprovar <strong>{$count}</strong> nota(s) liberando-as para contratação.
                <p class='border border-1 rounded text-bg-danger p-1 mt-2'>Uma vez aprovadas essas essas obras serguirão para CONTRATAÇÃO. <strong>Elas não poderão ser revertidas</strong>.</p>
                <p class='border border-1 rounded fw-bold text-primary p-1 mt-2'>{$notes}</p>
                <p class='fw-bold'>Deseja realmente prosseguir com a liberação?</p>
                ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, liberar!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_approved',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/Ov foi assumida.',
        ]);


    }


    public function confirm_approved()
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

        if ($notes->count() > 1) {
            foreach ($notes as $note) {

                if ($note->Approval()->exists()) {
                    try {
                        $note->Approval->update([

                            'approved'     => true,
                            'reason'      => 'LIBERADO EM MASSA POR ' . auth()->user()->name,
                            'approved_at'   => now(),
                        ]);

                    } catch (\Throwable $th) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Erro ao aprovar Notas/Ov',
                            'html'      => 'Erro: ' . $th->getMessage(),
                            // 'timer'    => 2500,
                        ]);

                        DB::rollBack();

                        return;
                    }
                }

            }



        } else {
            if ($notes->first()->Approval()->exists()) {
                try {
                    $notes->first()->Approval->update([

                        'approved'     => true,
                        'reason'      => 'APROVADO INDIVIDUALMENTE POR ' . auth()->user()->name,
                        'approved_at'   => now(),
                    ]);

                } catch (\Throwable $th) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'Erro ao aprovar Notas/Ov',
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
            'title'    => 'Nota(s) aprovada(s) com sucesso',
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

        $query->whereHas('Approval', function ($q) {
            $q->where('approved', false)
                ->where('tacit', false);

            if (!auth()->user()->superadm) {
                $q->whereIn('user_id', auth()->user()->visibleUserIdsForWork());
            }

            if ($this->onlyFinished) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('viability_approval_reclaim as vr1')
                        ->join('reclaims as r1', 'r1.id', '=', 'vr1.reclaim_id')
                        ->whereColumn('vr1.viability_approval_id', 'viability_approvals.id')
                        ->where('r1.completed', true)
                        ->whereRaw('r1.id = (
                        SELECT MAX(r2.id)
                        FROM viability_approval_reclaim as vr2
                        JOIN reclaims as r2 ON r2.id = vr2.reclaim_id
                        WHERE vr2.viability_approval_id = vr1.viability_approval_id
                    )');
                });
            }


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
            'approval.reclaims',
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
            $nexpCodes = $nexpCodes->merge(
                $cityValues->filter(fn ($v) => preg_match('/^\d+$/', $v) === 1)->values()
            );

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

            $mappedCodes = $mappedQuery->pluck('rdMunicipio')
                ->filter(fn ($v) => filled($v))
                ->map(fn ($v) => trim((string) $v))
                ->values();

            $query->whereIn('nexp', $nexpCodes->merge($mappedCodes)->unique()->values()->all());
        }

        if (isset($activeFilters['rubrica'])) {
            $query->whereIn('rubrica', $activeFilters['rubrica']);
        }

        if (isset($activeFilters['operacao'])) {
            $operacaoFilters = (array) $activeFilters['operacao'];
            $query->whereRelation('orders.operations', function ($q) use ($operacaoFilters) {
                $q->where('operacao', '0010')
                    ->whereIn('cenTrab', $operacaoFilters);
            });
        }



        return $query
                ->orderBy('is45', 'DESC')
                ->orderBy('type_note', 'DESC')
                ->orderBy('dt_status', 'ASC')
                ->orderBy('id', 'ASC');

    }


    public function render()
    {
        return view('livewire.responsible.approval-control', [
            'lists' => $this->lists->paginate(50, ['*'], 'approval_control_page'),
        ]);

    }
}
