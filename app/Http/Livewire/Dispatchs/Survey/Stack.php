<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Helpers\TextFormatter;
use App\Models\Production;
use App\Models\Service;
use App\Support\SicodeRules;
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
    private $filter_group = 'survey';
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
        'filterUser' => 'filterUser',
    ];

    public function mount($service)
    {

        $this->service = $service;
    }

    public function filterUser($user_id)
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $this->gotoPage(1);
        $_SESSION['filter'][$this->filter_group]['user'] = [$user_id];
        session(['filter.' . $this->filter_group . '.user' => [$user_id]]);

        $this->emit('toUpdate', 'user');
    }



    public function exportToExcel()
    {
        $this->loadFilters();

        \App\Jobs\Dispatchs\ExportDispatchSurveyJob::dispatch([
            'service_uuid' => $this->service,
            'search'       => $this->search,
            'multiSearch'  => $this->multiSearch,
            'note_type'    => $this->note_type,
            'user_fs'      => $this->filter['user'] ?? [],
        ], auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Exportação iniciada!',
            'text'  => 'Você será notificado quando o arquivo estiver pronto.',
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

    private function loadFilters(): void
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

        return Production::Query()
            ->where('service_id', $this->service)
            ->where('completed', false)
            ->when(auth()->user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());

                return count($companyIds)
                    ? $q->whereIn('productions.company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->addSelect('productions.*')
            ->addSelect(DB::raw("$pzoExpr AS pzo"))
            ->addSelect(DB::raw("n.dt_created as dt_created"))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'service:id,uuid,service',
                'company:id,name',
                'user:id,name,company_id,deleted_at',
                'user.Company:id,name',
                'user.Employee:id,user_id,contract_id',
                'user.Employee.Contract:id,company_id',
                'user.Employee.Contract.company:id,name',
                'dispatcher:id,name,company_id,deleted_at',
                'dispatcher.Company:id,name',
                'dispatcher.Employee:id,user_id,contract_id',
                'dispatcher.Employee.Contract:id,company_id',
                'dispatcher.Employee.Contract.company:id,name',
                'note:id,note,dt_created,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
            ]);
    }

    private function filtersQuery()
    {
        $this->loadFilters();




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
            })->when(!empty($this->filter['user']), function ($q) {
                $q->whereIn('productions.user_id', $this->filter['user']);
            });
    }

    public function getListsProperty()
    {
        $query = $this->filtersQuery()


            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('dt_created', 'asc')
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

        return view('livewire.dispatchs.survey.stack', [
            'lists' => $this->lists,
            'statusList' => $statusList,
        ]);
    }
}
