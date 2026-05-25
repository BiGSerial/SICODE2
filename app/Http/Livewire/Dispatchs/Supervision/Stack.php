<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Helpers\TextFormatter;
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

    public $service;
    public $perPage = 50;
    public $selected = [];
    public $statusFilter = null;
    public $search = '';
    public $advancedSearch;
    public $multiSearch = [];
    public $note_type = '';

    // Filters
    private $filter_group = 'supervision';
    private $filter;

    protected $queryString = [
        'statusFilter' => ['except' => null],
        'search' => ['except' => ''],
        'page' => ['except' => 1],
        'note_type' => ['except' => null || ''],
        'multiSearch' => ['except' => []],
    ];

    protected $listeners = [
        'resetFilters',
        'refresh_list' => '$refresh',
    ];

    public function mount($service)
    {

        $this->service = $service;
    }

    public function exportToExcel()
    {
        \App\Jobs\Dispatchs\ExportDispatchSupervisionJob::dispatch([
            'service_id'   => $this->service,
            'search'       => $this->search,
            'multiSearch'  => $this->multiSearch,
            'note_type'    => $this->note_type,
        ], auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'info',
            'title' => 'Exportação iniciada!',
            'text' => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    public function updatedSearch()
    {
        if (!trim($this->search)) {
            return;
        }

        $this->reset('statusFilter', 'page', 'multiSearch');
        $this->advancedSearch = null;

    }

    public function buscarMulti()
    {
        if (!trim($this->advancedSearch)) {
            return;
        }

        $this->reset('statusFilter', 'page');
        $this->multiSearch = $this->formatTextToArray($this->advancedSearch);

        if (count($this->multiSearch) > 0) {
            $this->search = null;
            $this->advancedSearch = null;

            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function resetFilters()
    {
        $this->reset('statusFilter', 'search', 'page');
        $this->multiSearch = [];
        $this->advancedSearch = null;
        $this->search = null;
    }

    private function baseQuery()
    {

        $pzoExpr = "
            CASE
            WHEN n.type_note = 1
            AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$' THEN
                CASE
                -- extrai mês e ano
                WHEN CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) BETWEEN 1 AND 12 THEN
                    DATE_ADD(
                    DATE_ADD(
                        MAKEDATE( CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED), 1 ),
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


        $dtInformExpr = "
            CASE
                WHEN wr.informed_at IS NOT NULL THEN wr.informed_at
                WHEN EXISTS (
                    SELECT 1 FROM partials p
                    WHERE p.note_id = n.id
                ) THEN (
                    SELECT p.created_at
                    FROM partials p
                    WHERE p.note_id = n.id
                    ORDER BY p.created_at DESC
                    LIMIT 1
                )
                ELSE NULL
            END
        ";



        return Production::Query()
            ->where('service_id', $this->service)
            ->where('completed', false)
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->leftJoin('work_reports as wr', 'n.id', '=', 'wr.note_id')
            ->leftJoin('adsforms as af', 'wr.id', '=', 'af.work_report_id')
            ->addSelect('productions.*')
            ->addSelect(DB::raw("$pzoExpr AS pzo"))
            ->addSelect(DB::raw("$dtInformExpr AS dt_inform"))
            ->addSelect(DB::raw('af.created_at AS dt_ads'))
            ->addSelect(DB::raw('wr.informed_at AS dt_informed'))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'service:id,uuid,service',
                'user:id,name',
                'dispatcher:id,name',
                'note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
                'note.workform:id,company_id,note_id,informed_at,rejected',
                'note.workform.adsform:id,work_report_id,amount,created_at',
                'note.orders:id,note_id,moaberto',
            ])
        ;
    }

    private function filtersQuery()
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




        return $this->baseQuery()
            ->when(trim($this->search), function ($q) {
                $q->where(function ($q) {
                    $search = $this->formatWithWildcard($this->search);
                    $q->where('n.note', $search->type, $search->search)
                        ->orWhere('n.rubrica', $search->type, $search->search)
                        ->orWhere('n.lexp', $search->type, $search->search)
                        ->orWhere('productions.odi', $search->type, $search->search)
                        ->orWhere('productions.odd', $search->type, $search->search)
                        ->orWhere('productions.ods', $search->type, $search->search)
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', $search->type, $search->search);
                        })
                        ->orWhereHas('note.orders', function ($q) use ($search) {
                            $q->where('ordem', $search->type, $search->search);
                        });
                });
            })
            ->when(count($this->multiSearch) > 0, function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('note', function ($query) {
                        $query->whereIn('note', $this->multiSearch)
                              ->orWhere('rubrica', $this->multiSearch)
                              ->orWhere('lexp', $this->multiSearch);
                    })
                    ->orWhereHas('user', function ($q) {
                        $q->whereIn('name', $this->multiSearch);
                    })
                    ->orWhereHas('note.orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
                });
            })
            ->when(isset($this->filter['city']), function ($q) {
                $cityFilters = collect((array) $this->filter['city'])
                    ->filter(fn ($v) => filled($v))
                    ->map(fn ($v) => trim((string) $v))
                    ->values();
                $q->whereIn('nexp', $cityFilters->all());
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('productions.status', $this->statusFilter);
            })->when($this->note_type, function ($q) {
                $q->where('n.type_note', $this->note_type);
            });
    }

    public function getListsProperty()
    {
        $query = $this->filtersQuery()


            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('partial', 'desc')
            ->orderByRaw('CASE WHEN dt_ads IS NULL THEN 0 ELSE 1 END DESC')
            ->orderBy('dt_inform', 'asc')
            ->orderBy('dt_ads', 'asc')
            ->orderBy('att_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);

        return $query;
    }

    public function render()
    {
        $statusList = $this->baseQuery()
            ->select('productions.status as status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            })
            ->toArray();

        return view('livewire.dispatchs.supervision.stack', [
            'lists' => $this->lists,
            'statusList' => $statusList,
        ]);
    }


}
