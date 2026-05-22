<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Helpers\TextFormatter;
use App\Jobs\Construction\ExportHistHiringJob;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use App\Models\Viability;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Histhiring extends Component
{
    use WithFileUploads;
    use TextFormatter;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $cities;

    public $files_selected = [];
    public $hasNoHired = false;
    public $deleteId;

    public $search;
    public $advancedSearch;
    public $multipleSearch = [];

    // search by date
    public $date_in;
    public $date_out;
    public $dateBy = 'sended_at';

    // Filtros dinâmicos (armazenados pelo componente de filtro)
    private $filter_group = 'hiring_hist';
    private $filter;

    /** Seleção em massa */
    public array $selected = [];     // IDs selecionados na página atual
    public bool  $selectPage = false;
    public bool  $selectAll  = false; // (se quiser extender para "selecionar tudo no filtro")

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'update_list'     => '$refresh',
        'refresh_list'    => '$refresh',
        'clear_selection' => 'clearSelection',
        'confirmDeleteViability' => 'deleteViability',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

    /** Limpar seleção (chamado pelo filho após salvar) */
    public function clearSelection()
    {
        $this->reset(['selected', 'selectPage', 'selectAll']);
    }

    /** IDs visíveis na página atual */
    public function getVisibleIdsProperty(): array
    {
        return $this->lists->pluck('id')->all();
    }

    /** Marcar/desmarcar todos da página atual */
    public function updatedSelectPage($value)
    {
        $this->selected = $value ? $this->visible_ids : [];
    }

    /** Botão do topo: abrir edição em massa */
    public function editSelected()
    {
        if (empty($this->selected)) {
            return;
        }

        $this->emitTo(
            'construction.hiring.actions.edit',
            'edit_hiring_bulk',
            $this->selected
        );
    }

    public function buscarMulti()
    {
        if ($this->advancedSearch) {
            $this->multipleSearch = $this->formatTextToArray($this->advancedSearch);

            if (count($this->multipleSearch) > 0) {
                $this->search = null;
                $this->goToPage(1);
                $this->advancedSearch = null;

                $this->dispatchBrowserEvent('hideModal');
            }
        }
    }

    public function updatedSearch()
    {
        if (trim($this->search)) {
            $this->advancedSearch = null;
            $this->multipleSearch = [];
            $this->goToPage(1);
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

    public function cleanAll()
    {
        $this->date_in  = "";
        $this->date_out = "";
        $this->dateBy   = 'sended_at';
        $this->search   = '';
    }

    public function exportToExcel(): void
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $filters = $_SESSION['filter'][$this->filter_group] ?? [];

        $params = [
            'search'         => $this->search,
            'multipleSearch' => $this->multipleSearch,
            'date_in'        => $this->date_in,
            'date_out'       => $this->date_out,
            'dateBy'         => $this->dateBy,
            'hasNoHired'     => $this->hasNoHired,
            'filter'         => $filters,
        ];

        ExportHistHiringJob::dispatch($params, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTACAO INICIADA',
            'text'     => 'A exportacao foi iniciada, voce recebera uma notificacao quando estiver pronta.',
            'timer'    => 5000,
        ]);
    }

    public function requestDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover viabilidade',
            'msg'           => 'Confirma remover este registro?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Nao, Cancele',
            'action'        => 'confirmDeleteViability',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum registro foi removido.',
        ]);
    }

    public function deleteViability(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $viability = Viability::find($this->deleteId);
        if (!$viability) {
            $this->deleteId = null;
            return;
        }

        $user = auth()->user();
        if (!$user?->superadm) {
            $limit = Carbon::now()->subHours(24);
            if (!$viability->created_at || $viability->created_at->lte($limit)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Nao permitido',
                    'text'     => 'Este registro so pode ser excluido em ate 24 horas apos a criacao.',
                    'timer'    => 5000,
                ]);
                return;
            }
        }

        $viability->delete();
        $this->deleteId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Registro excluido',
            'timer'    => 3000,
        ]);
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query();

        $query->where('hired', true);

        if ($this->dateBy && ($this->date_in || $this->date_out)) {
            if ($this->date_in && !$this->date_out) {
                $query->whereDate($this->dateBy, '>=', $this->date_in);
            }

            if (!$this->date_in && $this->date_out) {
                $query->whereDate($this->dateBy, '<=', $this->date_out);
            }

            if ($this->date_in && $this->date_out) {
                $query->whereBetween($this->dateBy, [$this->date_in, $this->date_out]);
            }
        }

        if ($this->hasNoHired) {
            $query->whereHas('Note.Orders', function ($o) {
                $o->whereRaw("LTRIM(statusSist) NOT LIKE 'ENT%'")
                    ->whereRaw("LTRIM(statusSist) NOT LIKE 'ENC%'")
                    ->whereRaw("LTRIM(statusSist) NOT LIKE 'CANCE%'")
                    ->whereHas('Operations', function ($op) {
                        $op->where('operacao', '0010')
                            ->where('status', 'NOT LIKE', 'CONF%');
                    });
            });
        }

        if ($this->multipleSearch) {
            $multipleSearch = $this->multipleSearch;
            $query->whereRelation('Note', function ($q) use ($multipleSearch) {
                $q->whereIn('note', $multipleSearch)
                  ->orWhereHas('orders', function ($q) use ($multipleSearch) {
                      $q->whereIn('ordem', $multipleSearch);
                  });
            });
        }

        $orderDateColumn = $this->dateBy ?: 'sended_at';
        $query->orderBy($orderDateColumn, 'DESC')
            ->orderBy(
                Note::select('note')
                    ->whereColumn('notes.id', 'viabilities.note_id'),
                'ASC'
            );

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', 'like', '%' . $this->search . '%')
                  ->orWhereRelation('Note.Orders', 'ordem', 'like', '%' . $this->search . '%');
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city'])) {
            $query->whereIn('lexp', $this->filter['city']);
        }

        $query->with([
            'Company',
            'User',
            'Form',
            'Comments.User',
            'Files',
            'Note.Orders' => function ($q) {
                $q->where(function ($w) {
                    $w->whereRaw("LTRIM(statusSist) NOT LIKE 'ENT%'")
                        ->whereRaw("LTRIM(statusSist) NOT LIKE 'ENC%'")
                        ->whereRaw("LTRIM(statusSist) NOT LIKE 'CANCE%'");
                });
            },
            'Note.Orders.Operations',
        ]);

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.construction.hiring.histhiring', [
            'lists' => $this->lists
        ]);
    }
}
