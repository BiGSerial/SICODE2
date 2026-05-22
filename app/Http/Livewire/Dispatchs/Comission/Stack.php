<?php

namespace App\Http\Livewire\Dispatchs\Comission;

use App\Helpers\TextFormatter;
use App\Jobs\Dispatchs\ExportDispatchPaymentJob;
use App\Models\Production;
use App\Models\Service;
use App\Traits\WildcardFormatter;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Stack extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormatter;

    protected $paginationTheme = 'bootstrap';

    /**
     * UUID do serviço atual (utilizado nos filtros/consultas).
     */
    public string $service;

    /**
     * Modelo completo do serviço para exibir dados na view.
     */
    public Service $serviceInfo;

    public int $perPage = 50;
    public array $selected = [];
    public ?int $statusFilter = null;
    public string $search = '';
    public ?string $advancedSearch = null;
    public array $multiSearch = [];
    public string $note_type = '';

    /**
     * Filtros persistidos via componente de filtros inteligentes.
     */
    private string $filterGroup = 'comission';
    private array $filter = [];

    protected $queryString = [
        'statusFilter' => ['except' => null],
        'search'       => ['except' => ''],
        'page'         => ['except' => 1],
        'note_type'    => ['except' => ''],
        'multiSearch'  => ['except' => []],
    ];

    protected $listeners = [
        'resetFilters',
        'refresh_list' => '$refresh',
    ];

    public function mount(string $service): void
    {
        $this->service     = $service;
        $this->serviceInfo = Service::where('uuid', $service)->with('Status')->firstOrFail();
    }

    public function exportToExcel(): void
    {
        $this->ensureFilterState();

        ExportDispatchPaymentJob::dispatch([
            'service_uuid' => $this->service,
            'search'       => $this->search,
            'multiSearch'  => $this->multiSearch,
            'note_type'    => $this->note_type,
            'company_ids'  => $this->filter['company'] ?? null,
            'rubricas'     => $this->filter['rubrica'] ?? null,
            'cities'       => $this->filter['city'] ?? null,
        ], auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Exportação iniciada!',
            'text'  => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    public function updatedSearch(): void
    {
        if (!trim($this->search)) {
            return;
        }

        $this->reset('statusFilter', 'page', 'multiSearch');
        $this->advancedSearch = null;
    }

    public function buscarMulti(): void
    {
        if (!trim((string) $this->advancedSearch)) {
            return;
        }

        $this->reset('statusFilter', 'page');
        $this->multiSearch = $this->formatTextToArray($this->advancedSearch ?? '');

        if (count($this->multiSearch) > 0) {
            $this->search         = '';
            $this->advancedSearch = null;

            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function resetFilters(): void
    {
        $this->reset('statusFilter', 'search', 'page');
        $this->multiSearch     = [];
        $this->advancedSearch  = null;
        $this->search          = '';
    }

    private function ensureFilterState(): void
    {
        if (\PHP_SESSION_ACTIVE !== session_status()) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $this->filter = $_SESSION['filter'][$this->filterGroup] ?? [];
    }

    private function baseQuery()
    {
        $pzoExpr = "
            CASE
            WHEN n.type_note = 1
            AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$' THEN
                CASE
                WHEN CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) BETWEEN 1 AND 12 THEN
                    DATE_ADD(
                        DATE_ADD(
                            MAKEDATE(CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED), 1),
                            INTERVAL (CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) - 1) MONTH
                        ),
                        INTERVAL 27 DAY
                    )
                ELSE NULL
                END
            WHEN n.type_note = 2 THEN
                DATE_ADD(CURDATE(), INTERVAL COALESCE(n.days_left, 0) DAY)
            ELSE NULL
            END
        ";

        return Production::query()
            ->where('service_id', $this->service)
            ->where('completed', false)
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->addSelect('productions.*')
            ->addSelect(DB::raw("$pzoExpr AS pzo"))
            ->addSelect(DB::raw("n.dt_created as dt_created"))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'service:id,uuid,service',
                'user:id,name',
                'dispatcher:id,name',
                'note:id,note,dt_created,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
                'company:id,name',
            ]);
    }

    private function filtersQuery()
    {
        $this->ensureFilterState();

        return $this->baseQuery()
            ->when(trim($this->search), function ($query) {
                $query->where(function ($q) {
                    $search = $this->formatWithWildcard($this->search);
                    $q->where('n.note', $search->type, $search->search)
                        ->orWhere('n.rubrica', $search->type, $search->search)
                        ->orWhere('n.lexp', $search->type, $search->search)
                        ->orWhere('productions.odi', $search->type, $search->search)
                        ->orWhere('productions.odd', $search->type, $search->search)
                        ->orWhere('productions.ods', $search->type, $search->search)
                        ->orWhereHas('user', function ($sub) use ($search) {
                            $sub->where('name', $search->type, $search->search);
                        })
                        ->orWhereHas('note.orders', function ($sub) use ($search) {
                            $sub->where('ordem', $search->type, $search->search);
                        });
                });
            })
            ->when(count($this->multiSearch) > 0, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('note', function ($sub) {
                        $sub->whereIn('note', $this->multiSearch)
                            ->orWhereIn('rubrica', $this->multiSearch)
                            ->orWhereIn('lexp', $this->multiSearch);
                    })
                    ->orWhereHas('user', function ($sub) {
                        $sub->whereIn('name', $this->multiSearch);
                    })
                    ->orWhereHas('note.orders', function ($sub) {
                        $sub->whereIn('ordem', $this->multiSearch);
                    });
                });
            })
            ->when(!empty($this->filter['city'] ?? []), function ($query) {
                $query->whereIn('n.nexp', (array) $this->filter['city']);
            })
            ->when(!empty($this->filter['rubrica'] ?? []), function ($query) {
                $query->whereIn('n.rubrica', (array) $this->filter['rubrica']);
            })
            ->when(!empty($this->filter['company'] ?? []), function ($query) {
                $query->whereIn('productions.company_id', (array) $this->filter['company']);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('productions.status', $this->statusFilter);
            })
            ->when($this->note_type !== '', function ($query) {
                $query->where('n.type_note', $this->note_type);
            });
    }

    public function getListsProperty()
    {
        return $this->filtersQuery()
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('dt_created', 'asc')
            ->orderBy('att_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $statusList = $this->baseQuery()
            ->select('productions.status as status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status => $item->count])
            ->toArray();

        return view('livewire.dispatchs.comission.stack', [
            'lists'       => $this->lists,
            'statusList'  => $statusList,
            'serviceInfo' => $this->serviceInfo,
            'service'     => $this->service,
        ]);
    }
}
