<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Helpers\SelectOptions;
use App\Jobs\Reports\ExportExternalReclaimsJob;
use App\Models\External;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\ExternalComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // -------- Filtros --------
    public $dt_in;
    public $dt_out;

    /** @var array<string> */
    public array $status = [];
    /** @var array<int> */
    public array $entityTypeIds = [];
    /** @var array<int> */
    public array $entityIds = [];
    /** @var array<string> */
    public array $rubrics = [];
    /** @var array<int> */
    public array $userIds = [];

    // opções para selects
    public $statusOptions = [];
    public $entityTypeOptions = [];
    public $entityOptions = [];
    public $rubricOptions = [];
    public $userOptions = [];

    protected $queryString = [
        'dt_in'         => ['except' => '', 'as' => 'de'],
        'dt_out'        => ['except' => '', 'as' => 'ate'],
        'status'        => ['except' => [], 'as' => 'sts'],
        'entityTypeIds' => ['except' => [], 'as' => 'et'],
        'entityIds'     => ['except' => [], 'as' => 'e'],
        'rubrics'       => ['except' => [], 'as' => 'rb'],
        'userIds'       => ['except' => [], 'as' => 'u'],
        'page'          => ['except' => 1],
    ];

    public function mount()
    {
        // Datas padrão
        $this->dt_in  = $this->dt_in ?: now()->startOfYear()->format('Y-m-d');
        $this->dt_out = $this->dt_out ?: now()->format('Y-m-d');
        if (Carbon::parse($this->dt_out)->greaterThan(now())) {
            $this->dt_out = now()->format('Y-m-d');
        }

        $this->statusOptions = collect(SelectOptions::getProtocolReasons())
  ->mapWithKeys(fn ($o) => [$o->value => $o->reason])
  ->all();

        $this->entityTypeOptions = EntityType::query()->orderBy('name')->pluck('name', 'id')->toArray();
        $this->entityOptions     = Entity::query()->orderBy('name')->pluck('name', 'id')->toArray();

        // Rubricas existentes em Note
        $this->rubricOptions = DB::table('notes')->select('rubrica')
            ->whereNotNull('rubrica')
            ->distinct()
            ->orderBy('rubrica')
            ->pluck('rubrica')
            ->filter(fn ($r) => trim((string)$r) !== '')
            ->values()
            ->toArray();

        $userCommentsIds = ExternalComment::query()
            ->distinct()
            ->pluck('user_id')
            ->filter(fn ($id) => !is_null($id))
            ->values()
            ->toArray();

        // Usuários que interagiram
        $this->userOptions = User::query()
            ->select('id', 'name')
            ->whereIn('id', $userCommentsIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /* ========================= Helpers de filtro ========================= */
    // protected function applyExternalFilters(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    // {
    //     $start = Carbon::parse($this->dt_in)->startOfDay();
    //     $end   = Carbon::parse($this->dt_out)->endOfDay();

    //     return $q
    //         ->whereBetween('externals.created_at', [$start, $end])
    //         ->when(!empty($this->status), fn ($qq) => $qq->whereIn('externals.status', $this->status))
    //         ->when(!empty($this->entityTypeIds), fn ($qq) => $qq->whereHas('Entity', fn ($e) => $e->whereIn('entity_type_id', $this->entityTypeIds)))
    //         ->when(!empty($this->entityIds), fn ($qq) => $qq->whereIn('externals.entity_id', $this->entityIds))
    //         ->when(!empty($this->rubrics), fn ($qq) => $qq->whereHas('Note', fn ($n) => $n->whereIn('rubrica', $this->rubrics)));
    // }

    protected function applyExternalFilters(
        \Illuminate\Database\Eloquent\Builder $q,
        bool $restrictByUsers = false,
        bool $useExternalDateRange = true
    ): \Illuminate\Database\Eloquent\Builder
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        // filtros padrões sobre externals
        $q->when($useExternalDateRange, fn ($qq) => $qq->whereBetween('externals.created_at', [$start, $end]))
          ->when(!empty($this->status), fn ($qq) => $qq->whereIn('externals.status', $this->status))
          ->when(!empty($this->entityTypeIds), fn ($qq) => $qq->whereHas('Entity', fn ($e) => $e->whereIn('entity_type_id', $this->entityTypeIds)))
          ->when(!empty($this->entityIds), fn ($qq) => $qq->whereIn('externals.entity_id', $this->entityIds))
          ->when(!empty($this->rubrics), fn ($qq) => $qq->whereHas('Note', fn ($n) => $n->whereIn('rubrica', $this->rubrics)));

        // quando quiser filtrar por usuários (para gráficos), restringe aos externals
        // que tiveram comentários desses usuários dentro do range de datas
        if ($restrictByUsers && !empty($this->userIds)) {
            $sub = DB::table('external_comments')
                ->select('external_id')
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('user_id', $this->userIds)
                ->groupBy('external_id');

            $q->whereIn('externals.id', $sub);
        }

        return $q;
    }

    protected function baseCommentQuery()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        return ExternalComment::query()
            ->join('externals as ex', 'ex.id', '=', 'external_comments.external_id')
            ->leftJoin('entities as en', 'en.id', '=', 'ex.entity_id')
            ->leftJoin('notes as nt', 'nt.id', '=', 'ex.note_id')
            ->whereBetween('external_comments.created_at', [$start, $end])
            ->when(!empty($this->status), fn ($q) => $q->whereIn('ex.status', $this->status))
            ->when(!empty($this->entityTypeIds), fn ($q) => $q->whereIn('en.entity_type_id', $this->entityTypeIds))
            ->when(!empty($this->entityIds), fn ($q) => $q->whereIn('ex.entity_id', $this->entityIds))
            ->when(!empty($this->rubrics), fn ($q) => $q->whereIn('nt.rubrica', $this->rubrics))
            ->when(!empty($this->userIds), fn ($q) => $q->whereIn('external_comments.user_id', $this->userIds));
    }

    protected function baseReclaimExternalQuery()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();
        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        return DB::table('external_reclaim as er')
            ->join('reclaims as r', 'r.id', '=', 'er.reclaim_id')
            ->join('externals as ex', 'ex.id', '=', 'er.external_id')
            ->leftJoin('entities as en', 'en.id', '=', 'ex.entity_id')
            ->leftJoin('notes as nt', 'nt.id', '=', 'ex.note_id')
            ->leftJoin('subcategories as sc', 'sc.id', '=', 'r.subcategory_id')
            ->leftJoin('categories as ca', 'ca.id', '=', 'sc.category_id')
            ->whereBetween('r.created_at', [$start, $end])
            ->when(!empty($this->status), fn ($q) => $q->whereIn('ex.status', $this->status))
            ->when(!empty($this->entityTypeIds), fn ($q) => $q->whereIn('en.entity_type_id', $this->entityTypeIds))
            ->when(!empty($this->entityIds), fn ($q) => $q->whereIn('ex.entity_id', $this->entityIds))
            ->when(!empty($this->rubrics), fn ($q) => $q->whereIn('nt.rubrica', $this->rubrics));
    }

    protected function reclaimTopCausesRows(): \Illuminate\Support\Collection
    {
        return (clone $this->baseReclaimExternalQuery())
            ->selectRaw('COALESCE(sc.name, r.category, "SEM CAUSA") as cause, COUNT(DISTINCT r.id) as total')
            ->groupBy('cause')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
    }

    public function getReclaimStatsProperty(): array
    {
        $total = (clone $this->baseReclaimExternalQuery())
            ->selectRaw('COUNT(DISTINCT r.id) as total')
            ->value('total') ?? 0;

        $completed = (clone $this->baseReclaimExternalQuery())
            ->where('er.completed', 1)
            ->selectRaw('COUNT(DISTINCT r.id) as total')
            ->value('total') ?? 0;

        $open = max((int)$total - (int)$completed, 0);
        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return [
            'total' => (int)$total,
            'completed' => (int)$completed,
            'open' => $open,
            'completion_rate' => $rate,
        ];
    }

    public function getReclaimTopCausesListProperty(): array
    {
        return $this->reclaimTopCausesRows()
            ->map(fn ($row) => ['cause' => $row->cause, 'total' => (int)$row->total])
            ->toArray();
    }

    public function getReclaimTopCausesChartProperty(): array
    {
        $rows = $this->reclaimTopCausesRows();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $rows->pluck('cause')->toArray(),
                'datasets' => [[
                    'label' => 'Reclaims',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int)$v)->toArray(),
                    'backgroundColor' => 'rgba(67, 160, 71, .25)',
                    'borderColor' => '#43A047',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Principais causas dos retornos internos'],
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Qtd']],
                    'y' => ['title' => ['display' => true, 'text' => 'Causa']],
                ],
            ],
        ];
    }

    /* ========================= Lista ========================= */
    protected function listQuery()
    {
        $q = External::query()
            ->select('externals.*')
            ->selectSub(
                DB::table('external_comments')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('external_id', 'externals.id'),
                'last_comment_at'
            );

        $this->applyExternalFilters($q);

        return $q
            ->with([
                'Note:id,note,lexp,centerjob,nstats,rubrica',
                'Note.Files:id,service_id,file_name,path,ext',
                'Entity:id,entity_type_id,name,nick',
                'Entity.Type:id,name',
                'User:id,name',
                'User.Company:id,name',
            ])
            ->orderByRaw('last_comment_at IS NULL, last_comment_at ASC')
            ->orderBy('externals.id');
    }

    /* ========================= Charts ========================= */

    // 1) Diário — linha, preenchido com zeros
    public function getDailyInteractionsProperty(): array
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();
        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        $rows = (clone $this->baseCommentQuery())
            ->selectRaw('DATE(external_comments.created_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')
            ->get()->keyBy('d');

        $labels = [];
        $data   = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $data[] = (int)($rows[$key]->c ?? 0);
            $cursor->addDay();
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Interações/Dia',
                    'data'  => $data,
                    'borderColor' => '#008FFB',
                    'backgroundColor' => 'rgba(0,143,251,.15)',
                    'fill'  => true,
                    'tension' => 0.2,
                    'pointRadius' => 2,
                ]],
            ],
            'options' => [
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => ['display' => true, 'text' => 'Interações diárias'],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Qtd']],
                    'x' => ['title' => ['display' => true, 'text' => 'Dia']],
                ],
            ],
        ];
    }

    // 2) Mensal 12m — barras + média
    public function getMonthlyInteractionsProperty(): array
    {
        $dtOut = Carbon::parse($this->dt_out)->endOfDay();
        if ($dtOut->greaterThan(now())) {
            $dtOut = now()->endOfDay();
        }

        $endMonth   = $dtOut->copy()->startOfMonth();
        $currentMon = now()->startOfMonth();
        if ($endMonth->greaterThan($currentMon)) {
            $endMonth = $currentMon;
        }
        $startMonth = $endMonth->copy()->subMonths(11)->startOfMonth();

        $months = collect();
        $c = $startMonth->copy();
        while ($c->lessThanOrEqualTo($endMonth)) {
            $months->push($c->copy());
            $c->addMonth();
        }
        if ($months->isEmpty()) {
            return ['type' => 'bar', 'data' => ['labels' => [], 'datasets' => []], 'options' => ['responsive' => true,'maintainAspectRatio' => false]];
        }

        $userStart = Carbon::parse($this->dt_in)->startOfDay();
        $userEnd   = Carbon::parse($this->dt_out)->endOfDay();
        if ($userEnd->greaterThan(now())) {
            $userEnd = now()->endOfDay();
        }

        $rxStart = $months->first()->copy()->startOfMonth();
        $rxEnd   = $months->last()->copy()->endOfMonth();

        $finalStart = $rxStart->greaterThan($userStart) ? $rxStart : $userStart;
        $finalEnd   = $rxEnd->lessThan($userEnd) ? $rxEnd : $userEnd;

        $rows = collect();
        if ($finalStart->lessThanOrEqualTo($finalEnd)) {
            $rows = (clone $this->baseCommentQuery())
                ->whereBetween('external_comments.created_at', [$finalStart, $finalEnd])
                ->selectRaw('YEAR(external_comments.created_at) as y, MONTH(external_comments.created_at) as m, COUNT(*) as total')
                ->groupBy('y', 'm')->orderBy('y')->orderBy('m')->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->y, $r->m));
        }

        $labels = [];
        $data   = [];
        foreach ($months as $m) {
            $labels[] = $m->translatedFormat('M/Y');
            if ($m->lt($finalStart->copy()->startOfMonth()) || $m->gt($finalEnd->copy()->startOfMonth())) {
                $data[] = 0;
                continue;
            }
            $key = $m->format('Y-m');
            $data[] = (int)($rows[$key]->total ?? 0);
        }

        $avg = count($data) ? array_sum($data) / count($data) : 0;
        $avgLine = array_fill(0, count($labels), round($avg, 2));

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['type' => 'bar','label' => 'Interações/Mês','data' => $data,'backgroundColor' => 'rgba(0,227,150,.25)','borderColor' => '#00E396','borderWidth' => 1],
                    ['type' => 'line','label' => 'Média mensal','data' => $avgLine,'borderColor' => '#FBC02D','borderDash' => [5,5],'pointRadius' => 0,'tension' => 0,'fill' => false],
                ]
            ],
            'options' => [
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title' => ['display' => true,'text' => 'Interações mensais (últimos 12 meses)'],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true,'title' => ['display' => true,'text' => 'Qtd']],
                    'x' => ['title' => ['display' => true,'text' => 'Mês']],
                ]
            ]
        ];
    }

    // 3) Top 3 usuários — multilinhas diário
    public function getTopUsersDailyProperty(): array
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();
        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        $base = (clone $this->baseCommentQuery());

        $topUsers = (clone $base)
            ->selectRaw('external_comments.user_id, COUNT(*) as total')
            ->groupBy('external_comments.user_id')
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('total', 'user_id');

        if ($topUsers->isEmpty()) {
            return ['type' => 'line','data' => ['labels' => [], 'datasets' => []], 'options' => ['responsive' => true,'maintainAspectRatio' => false]];
        }

        $labels = [];
        $days = [];
        $c = $start->copy();
        while ($c->lessThanOrEqualTo($end)) {
            $labels[] = $c->format('d/m');
            $days[] = $c->toDateString();
            $c->addDay();
        }

        $colors = ['#008FFB', '#00E396', '#FF4560'];
        $datasets = [];
        $i = 0;

        foreach ($topUsers as $userId => $total) {
            $userName = $this->userOptions[$userId] ?? ('User #'.$userId);

            $rows = (clone $base)
                ->where('external_comments.user_id', $userId)
                ->selectRaw('DATE(external_comments.created_at) as d, COUNT(*) as c')
                ->groupBy('d')->orderBy('d')->get()->keyBy('d');

            $series = [];
            foreach ($days as $d) {
                $series[] = (int)($rows[$d]->c ?? 0);
            }

            $color = $colors[$i % count($colors)];
            $datasets[] = [
                'label' => $userName,
                'data'  => $series,
                'borderColor' => $color,
                'backgroundColor' => $color.'22',
                'fill' => false,
                'tension' => 0.2,
                'pointRadius' => 2,
            ];
            $i++;
        }

        return [
            'type' => 'line',
            'data' => ['labels' => $labels, 'datasets' => $datasets],
            'options' => [
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => ['display' => true, 'text' => 'Interações diárias por usuário (TOP 3)'],
                    'tooltip' => ['mode' => 'index', 'intersect' => false],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true,'title' => ['display' => true, 'text' => 'Qtd']],
                    'x' => ['title' => ['display' => true, 'text' => 'Dia']],
                ],
            ],
        ];
    }

    // 4) Top Entidades
    public function getTopEntitiesByInteractionsProperty(): array
    {
        $rows = (clone $this->baseCommentQuery())
            ->selectRaw('COALESCE(en.name,"Sem entidade") as entity_name, COUNT(*) as total')
            ->groupBy('entity_name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $rows->pluck('entity_name')->toArray(),
                'datasets' => [[
                    'label' => 'Interações',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int)$v)->toArray(),
                    'backgroundColor' => 'rgba(102,126,234,.25)',
                    'borderColor' => '#667EEA','borderWidth' => 1
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true,'text' => 'Top Entidades por Interações'],
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Qtd']],
                    'y' => ['title' => ['display' => true, 'text' => 'Entidade']],
                ],
            ],
        ];
    }

    // 5) Distribuição por Tipo de Entidade
    public function getByEntityTypeProperty(): array
    {
        $q = External::query()
            ->leftJoin('entities as en', 'en.id', '=', 'externals.entity_id')
            ->leftJoin('entity_types as et', 'et.id', '=', 'en.entity_type_id');

        $this->applyExternalFilters($q, true);

        $rows = $q->selectRaw('COALESCE(et.name,"Sem tipo") as type_name, COUNT(*) as total')
            ->groupBy('type_name')
            ->orderByDesc('total')
            ->get();

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $rows->pluck('type_name')->toArray(),
                'datasets' => [[
                    'label' => 'Externals por tipo',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int)$v)->toArray(),
                    'backgroundColor' => ['rgba(0,143,251,.3)','rgba(0,227,150,.3)','rgba(251,192,45,.3)','rgba(255,69,96,.3)','rgba(119,93,208,.3)'],
                    'borderColor' => ['#008FFB','#00E396','#FBC02D','#FF4560','#775DD0'],
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'right'],
                    'title' => ['display' => true,'text' => 'Distribuição por Tipo de Entidade'],
                ],
                'cutout' => '60%',
            ],
        ];
    }

    // 6) Rubrica × Entidade (stacked)
    public function getRubricByEntityProperty(): array
    {
        $q = External::query()
            ->leftJoin('entities as en', 'en.id', '=', 'externals.entity_id')
            ->leftJoin('notes as nt', 'nt.id', '=', 'externals.note_id');

        $this->applyExternalFilters($q, true);

        $rows = $q->selectRaw('COALESCE(en.name,"Sem entidade") as entity_name, COALESCE(nt.rubrica,"Sem rubrica") as rub, COUNT(*) as total')
            ->groupBy('entity_name', 'rub')
            ->orderBy('entity_name')
            ->get();

        $entities = $rows->pluck('entity_name')->unique()->values()->toArray();
        $entities = array_slice($entities, 0, 15);

        $rubLabels = collect($rows->pluck('rub')->unique()->values())->toArray();
        if (!empty($this->rubrics)) {
            $rubLabels = array_values(array_unique(array_merge($this->rubrics, $rubLabels)));
        }

        $matrix = [];
        foreach ($rubLabels as $r) {
            $series = [];
            foreach ($entities as $e) {
                $item = $rows->first(function ($x) use ($e, $r) {
                    return $x->entity_name === $e && $x->rub === $r;
                });
                $series[] = (int) ($item->total ?? 0);
            }
            $matrix[] = $series;
        }

        $colors = [
            ['bg' => 'rgba(0,143,251,.25)','bd' => '#008FFB'],
            ['bg' => 'rgba(0,227,150,.25)','bd' => '#00E396'],
            ['bg' => 'rgba(251,192,45,.25)','bd' => '#FBC02D'],
            ['bg' => 'rgba(255,69,96,.25)','bd' => '#FF4560'],
            ['bg' => 'rgba(119,93,208,.25)','bd' => '#775DD0'],
            ['bg' => 'rgba(255,159,64,.25)','bd' => '#FF9F40'],
        ];

        $datasets = [];
        foreach ($rubLabels as $i => $rub) {
            $c = $colors[$i % count($colors)];
            $datasets[] = [
                'label' => (string)$rub,
                'data'  => $matrix[$i] ?? [],
                'backgroundColor' => $c['bg'],
                'borderColor'     => $c['bd'],
                'borderWidth'     => 1,
                'stack'           => 'stack1',
            ];
        }

        return [
            'type' => 'bar',
            'data' => ['labels' => $entities, 'datasets' => $datasets],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => ['display' => true,'text' => 'Rubrica × Entidade'],
                ],
                'scales' => [
                    'x' => ['stacked' => true, 'beginAtZero' => true, 'title' => ['display' => true,'text' => 'Qtd']],
                    'y' => ['stacked' => true, 'title' => ['display' => true,'text' => 'Entidade']],
                ],
            ],
        ];
    }

    // 7) Backlog por idade (dias) — abertos
    public function getBacklogByAgeProperty(): array
    {
        $now = now();
        $q = External::query()->where('externals.completed', false);
        $this->applyExternalFilters($q);

        $ages = $q->selectRaw('TIMESTAMPDIFF(DAY, externals.created_at, ?) as age', [$now])
            ->pluck('age')
            ->map(fn ($a) => (int) $a);

        $buckets = [
            '0–7'   => [0,7],
            '8–20'  => [8,20],
            '21–30' => [21,30],
            '31–60' => [31,60],
            '61+'   => [61, PHP_INT_MAX],
        ];

        $labels = array_keys($buckets);
        $data   = [];
        foreach ($buckets as [$min,$max]) {
            $data[] = $ages->filter(fn ($d) => $d >= $min && $d <= $max)->count();
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Abertos por faixa de dias',
                    'data' => $data,
                    'backgroundColor' => 'rgba(255,69,96,.25)',
                    'borderColor' => '#FF4560',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title'  => ['display' => true,'text' => 'Backlog por Faixa de Dias em Aberto'],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true,'title' => ['display' => true,'text' => 'Qtd']],
                    'x' => ['title' => ['display' => true,'text' => 'Faixa (dias)']],
                ],
            ],
        ];
    }

    /* ========================= Atualizações/Paginação ========================= */
    public function updatingDtIn()
    {
        $this->resetPage();
    }
    public function updatingDtOut()
    {
        $this->resetPage();
    }
    public function updatingStatus()
    {
        $this->resetPage();
    }
    public function updatingEntityTypeIds()
    {
        $this->resetPage();
    }
    public function updatingEntityIds()
    {
        $this->resetPage();
    }
    public function updatingRubrics()
    {
        $this->resetPage();
    }
    public function updatingUserIds()
    {
        $this->resetPage();
    }

    public function updatedDtOut()
    {
        if (Carbon::parse($this->dt_out)->greaterThan(now())) {
            $this->dt_out = now()->format('Y-m-d');
        } $this->dispatchCharts();
    }
    public function updatedDtIn()
    {
        $this->dispatchCharts();
    }
    public function updatedStatus()
    {
        $this->dispatchCharts();
    }
    public function updatedEntityTypeIds()
    {
        $this->dispatchCharts();
    }
    public function updatedEntityIds()
    {
        $this->dispatchCharts();
    }
    public function updatedRubrics()
    {
        $this->dispatchCharts();
    }
    public function updatedUserIds()
    {
        $this->dispatchCharts();
    }

    protected function dispatchCharts()
    {
        $this->dispatchBrowserEvent('chart-daily', $this->dailyInteractions);
        $this->dispatchBrowserEvent('chart-monthly', $this->monthlyInteractions);
        $this->dispatchBrowserEvent('chart-top-users', $this->topUsersDaily);
        $this->dispatchBrowserEvent('chart-top-entities', $this->topEntitiesByInteractions);
        $this->dispatchBrowserEvent('chart-etype', $this->byEntityType);
        $this->dispatchBrowserEvent('chart-rubric-entity', $this->rubricByEntity);
        $this->dispatchBrowserEvent('chart-age', $this->backlogByAge);
        $this->dispatchBrowserEvent('chart-reclaim-causes', $this->reclaimTopCausesChart);
    }

    public function exportAdminCsv()
    {
        $params = [
            'dt_in'         => $this->dt_in,
            'dt_out'        => $this->dt_out,
            'status'        => $this->status,
            'entityTypeIds' => $this->entityTypeIds,
            'entityIds'     => $this->entityIds,
            'rubrics'       => $this->rubrics,
            'userIds'       => $this->userIds,
        ];
        // TODO: job de export usando $params
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'info',
            'title'    => 'Exportação administrativa',
            'html'     => "<p>Sua exportação será implementada com base nos filtros atuais.</p>",
            'timer'    => 4000,
        ]);
    }

    public function exportReclaimRaw()
    {
        $params = [
            'dt_in'         => $this->dt_in,
            'dt_out'        => $this->dt_out,
            'status'        => $this->status,
            'entityTypeIds' => $this->entityTypeIds,
            'entityIds'     => $this->entityIds,
            'rubrics'       => $this->rubrics,
        ];

        ExportExternalReclaimsJob::dispatch($params, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTACAO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>Seu arquivo esta sendo gerado. Voce sera notificado quando estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificacao.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function clearFilters()
    {
        $this->dt_in = now()->startOfYear()->format('Y-m-d');
        $this->dt_out = now()->format('Y-m-d');
        $this->status = [];
        $this->entityTypeIds = [];
        $this->entityIds = [];
        $this->rubrics = [];
        $this->userIds = [];

        $this->resetPage();
        $this->dispatchCharts();
    }

    public function render()
    {
        return view('livewire.services.oexterno.dashboard', [
            'list'         => $this->listQuery()->paginate(20),

            // gráficos iniciais
            'daily'        => $this->dailyInteractions,
            'monthly'      => $this->monthlyInteractions,
            'topUsers'     => $this->topUsersDaily,
            'topEntities'  => $this->topEntitiesByInteractions,
            'byType'       => $this->byEntityType,
            'rubricEntity' => $this->rubricByEntity,
            'age'          => $this->backlogByAge,
            'reclaimStats' => $this->reclaimStats,
            'reclaimTopCausesChart' => $this->reclaimTopCausesChart,
            'reclaimTopCausesList' => $this->reclaimTopCausesList,

            // opções
            'statusOptions'     => $this->statusOptions,
            'entityTypeOptions' => $this->entityTypeOptions,
            'entityOptions'     => $this->entityOptions,
            'rubricOptions'     => $this->rubricOptions,
            'userOptions'       => $this->userOptions,
        ]);
    }
}
