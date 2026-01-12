<?php

namespace App\Http\Livewire\Protests\Analytics;

use App\Enum\ProtestJobStatus;
use App\Enum\ProtestType;
use App\Jobs\Protests\ExportDispatcherMeasuresJob;
use App\Jobs\Protests\ExportProtestJobsJob;
use App\Models\MedProtest;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class UserSlaDashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $dt_in;
    public $dt_out;
    public $advanceFilter = 'all'; // all | advance | normal
    public $userId = null;
    public array $protestTypes = [];

    public $usersOptions = [];
    public array $protestTypeOptions = [];

    protected $queryString = [
        'dt_in'         => ['except' => ''],
        'dt_out'        => ['except' => ''],
        'advanceFilter' => ['except' => 'all', 'as' => 'adv'],
        'userId'        => ['except' => null, 'as' => 'user'],
    ];

    public function mount()
    {
        $this->dt_in  = now()->startOfMonth()->toDateString();
        $this->dt_out = now()->toDateString();

        $this->advanceFilter = 'all';

        // Usuários que aparecem em created_by ou owner_id em qualquer Job
        $this->usersOptions = User::whereIn('id', function ($q) {
            $q->select('created_by')->from('protest_jobs')->whereNotNull('created_by');
        })
            ->orWhereIn('id', function ($q) {
                $q->select('owner_id')->from('protest_jobs')->whereNotNull('owner_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->protestTypeOptions = collect(ProtestType::cases())->map(function (ProtestType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
            ];
        })->values()->all();

        $this->protestTypes = [];
    }

    public function updated($propertyName)
    {
        $paginationSensitiveProps = ['dt_in', 'dt_out', 'advanceFilter', 'userId', 'protestTypes'];

        $isProtestTypesNested = str_starts_with($propertyName, 'protestTypes.');

        if ($isProtestTypesNested || in_array($propertyName, $paginationSensitiveProps, true)) {
            $this->resetDueMeasuresPagination();
        }
    }

    protected function resetDueMeasuresPagination(): void
    {
        $this->resetPage('due_today_page');
        $this->resetPage('overdue_page');
        $this->resetPage('dispatcher_measures_page');
    }

    /**
     * Query base para filtrar os ProtestJobs do período / filtros.
     */
    protected function baseJobsQuery()
    {
        return ProtestJob::query()
            ->when($this->advanceFilter === 'advance', fn ($q) => $q->where('is_advance', true))
            ->when($this->advanceFilter === 'normal', fn ($q) => $q->where(function ($sub) {
                $sub->where('is_advance', false)->orWhereNull('is_advance');
            }))
            ->when($this->userId, function ($q) {
                $id = $this->userId;
                $q->where(function ($sub) use ($id) {
                    $sub->where('created_by', $id)
                        ->orWhere('owner_id', $id)
                        ->orWhere('closed_by', $id);
                });
            })
            ->when($types = $this->getSelectedProtestTypes(), function ($q) use ($types) {
                $q->whereHas('medProtest', function ($sub) use ($types) {
                    $sub->whereIn('protest_type', $types);
                });
            });
    }

    protected function jobsBaseQuery(Carbon $start, Carbon $end, string $dateColumn = 'sent_at')
    {
        if (!in_array($dateColumn, ['sent_at', 'finished_at'], true)) {
            $dateColumn = 'sent_at';
        }

        return $this->baseJobsQuery()
            ->whereBetween($dateColumn, [$start, $end]);
    }

    protected function getSelectedProtestTypes(): array
    {
        return collect($this->protestTypes)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    protected function applyMedProtestTypeFilter($query)
    {
        $types = $this->getSelectedProtestTypes();
        return $query->when(!empty($types), fn ($q) => $q->whereIn('protest_type', $types));
    }

    protected function applyProtestTypeFilter($query)
    {
        $types = $this->getSelectedProtestTypes();
        return $query->when(!empty($types), function ($q) use ($types) {
            $q->whereHas('medProtests', function ($sub) use ($types) {
                $sub->whereIn('protest_type', $types);
            });
        });
    }

    protected function resolveProtestTypeLabel($value): string
    {
        if ($value instanceof ProtestType) {
            return $value->label();
        }

        if (is_numeric($value)) {
            $enum = ProtestType::tryFrom((int) $value);
            if ($enum) {
                return $enum->label();
            }
        }

        return 'Sem classificacao';
    }

    protected function getDateRange(): array
    {
        $start = $this->dt_in ? Carbon::parse($this->dt_in)->startOfDay() : now()->startOfMonth();
        $end   = $this->dt_out ? Carbon::parse($this->dt_out)->endOfDay() : now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function secondsToHuman(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 min';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    /**
     * Métricas gerais do período (cards de resumo).
     */
    protected function buildSummary(Carbon $start, Carbon $end): array
    {
        $base = $this->jobsBaseQuery($start, $end);

        $totalJobs = (clone $base)->count();
        $finishedJobs = (clone $base)->whereNotNull('finished_at')->count();
        $slaBreached = (clone $base)->whereNotNull('sla_breached_at')->count();
        $onTimeJobs  = max(0, $finishedJobs - $slaBreached);

        $slaRate = $finishedJobs > 0
            ? round(($onTimeJobs / $finishedJobs) * 100, 1)
            : 0;

        // Média global de reação (Despachantes)
        $dispatcherGlobal = (clone $base)
            ->join('med_protests', 'med_protests.id', '=', 'protest_jobs.med_protest_id')
            ->whereNotNull('protest_jobs.created_by')
            ->whereNotNull('med_protests.dtCriacaoMedida')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, med_protests.dtCriacaoMedida, protest_jobs.sent_at)) as avg_reaction_seconds')
            ->first();

        $avgReactionSeconds = (int)($dispatcherGlobal->avg_reaction_seconds ?? 0);

        // Tempo até aceite pelo responsável (sent_at -> accepted_at)
        $userReaction = (clone $base)
            ->whereNotNull('protest_jobs.accepted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.accepted_at)) as avg_accept_seconds')
            ->first();

        $avgUserReactionSeconds = (int)($userReaction->avg_accept_seconds ?? 0);

        // Média global de execução (Responsáveis) sent_at -> finished_at
        $ownerGlobal = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->whereNotNull('protest_jobs.sent_at')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.finished_at)) as avg_exec_seconds')
            ->first();

        $avgExecSeconds = (int)($ownerGlobal->avg_exec_seconds ?? 0);

        // Encerramento pelo próprio responsável
        $closureAgg = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('
                SUM(CASE WHEN protest_jobs.closed_by = protest_jobs.owner_id THEN 1 ELSE 0 END) as self_closed,
                COUNT(*) as total_closed
            ')
            ->first();

        $selfClosed  = (int)($closureAgg->self_closed ?? 0);
        $totalClosed = (int)($closureAgg->total_closed ?? 0);

        $selfClosureRate = $totalClosed > 0
            ? round(($selfClosed / $totalClosed) * 100, 1)
            : 0;

        return [
            'period_label'             => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
            'total_jobs'               => $totalJobs,
            'finished_jobs'            => $finishedJobs,
            'sla_rate'                 => $slaRate,
            'avg_reaction_sec'         => $avgReactionSeconds,
            'avg_reaction_human'       => $this->secondsToHuman($avgReactionSeconds),
            'avg_user_reaction_sec'    => $avgUserReactionSeconds,
            'avg_user_reaction_human'  => $this->secondsToHuman($avgUserReactionSeconds),
            'avg_exec_sec'             => $avgExecSeconds,
            'avg_exec_human'           => $this->secondsToHuman($avgExecSeconds),
            'self_closure_rate'        => $selfClosureRate,
            'self_closed'              => $selfClosed,
            'total_closed'             => $totalClosed,
        ];
    }

    /**
     * Estatísticas por despachante (created_by).
     */
    protected function buildDispatcherStats(Carbon $start, Carbon $end)
    {
        $base = $this->jobsBaseQuery($start, $end);

        $rows = (clone $base)
            ->join('med_protests', 'med_protests.id', '=', 'protest_jobs.med_protest_id')
            ->whereNotNull('protest_jobs.created_by')
            ->selectRaw('
                protest_jobs.created_by as user_id,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN protest_jobs.is_advance = 1 THEN 1 ELSE 0 END) as total_advance,
                AVG(TIMESTAMPDIFF(SECOND, med_protests.created_at, protest_jobs.sent_at)) as avg_reaction_seconds
            ')
            ->groupBy('protest_jobs.created_by')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $totalJobs    = (int)$row->total_jobs;
            $totalAdvance = (int)($row->total_advance ?? 0);
            $avgSec       = (int)($row->avg_reaction_seconds ?? 0);

            return [
                'user_id'            => $row->user_id,
                'user_name'          => optional($users->get($row->user_id))->name ?? 'N/A',
                'total_jobs'         => $totalJobs,
                'total_advance'      => $totalAdvance,
                'advance_ratio'      => $totalJobs > 0 ? round(($totalAdvance / $totalJobs) * 100, 1) : 0,
                'avg_reaction_sec'   => $avgSec,
                'avg_reaction_human' => $this->secondsToHuman($avgSec),
            ];
        })->sortByDesc('total_jobs')->values();
    }

    /**
     * Estatísticas por responsável (owner_id).
     */
    protected function buildOwnerStats(Carbon $start, Carbon $end)
    {
        $base = $this->jobsBaseQuery($start, $end);

        $rows = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->selectRaw('
                protest_jobs.owner_id as user_id,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN protest_jobs.is_advance = 1 THEN 1 ELSE 0 END) as total_advance,
                SUM(CASE WHEN protest_jobs.finished_at IS NOT NULL THEN 1 ELSE 0 END) as finished_jobs,
                SUM(
                    CASE
                        WHEN protest_jobs.finished_at IS NULL
                             OR protest_jobs.status != ?
                        THEN 1 ELSE 0
                    END
                ) as open_jobs,
                SUM(
                    CASE
                        WHEN protest_jobs.sla_breached_at IS NULL
                             AND protest_jobs.sla_due_at IS NOT NULL
                             AND protest_jobs.finished_at IS NOT NULL
                             AND protest_jobs.finished_at <= protest_jobs.sla_due_at
                        THEN 1 ELSE 0
                    END
                ) as sla_on_time,
                SUM(
                    CASE
                        WHEN protest_jobs.closed_by = protest_jobs.owner_id
                             AND protest_jobs.finished_at IS NOT NULL
                        THEN 1 ELSE 0
                    END
                ) as self_closed,
                AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.finished_at)) as avg_total_seconds,
                AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.finished_at)) as avg_exec_seconds
            ', [ProtestJobStatus::DONE->value])
            ->groupBy('protest_jobs.owner_id')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $totalJobs     = (int)$row->total_jobs;
            $totalAdvance  = (int)($row->total_advance ?? 0);
            $finishedJobs  = (int)($row->finished_jobs ?? 0);
            $openJobs      = (int)($row->open_jobs ?? 0);
            $slaOnTime     = (int)($row->sla_on_time ?? 0);
            $selfClosed    = (int)($row->self_closed ?? 0);
            $avgTotalSec   = (int)($row->avg_total_seconds ?? 0);
            $avgExecSec    = (int)($row->avg_exec_seconds ?? 0);

            return [
                'user_id'             => $row->user_id,
                'user_name'           => optional($users->get($row->user_id))->name ?? 'N/A',
                'total_jobs'          => $totalJobs,
                'total_advance'       => $totalAdvance,
                'advance_ratio'       => $totalJobs > 0 ? round(($totalAdvance / $totalJobs) * 100, 1) : 0,
                'finished_jobs'       => $finishedJobs,
                'open_jobs'           => $openJobs,
                'sla_on_time'         => $slaOnTime,
                'sla_rate'            => $finishedJobs > 0 ? round(($slaOnTime / $finishedJobs) * 100, 1) : 0,
                'self_closed'         => $selfClosed,
                'self_closure_rate'   => $finishedJobs > 0 ? round(($selfClosed / $finishedJobs) * 100, 1) : 0,
                'avg_total_seconds'   => $avgTotalSec,
                'avg_total_human'     => $this->secondsToHuman($avgTotalSec),
                'avg_exec_seconds'    => $avgExecSec,
                'avg_exec_human'      => $this->secondsToHuman($avgExecSec),
            ];
        })->sortByDesc('total_jobs')->values();
    }

    protected function buildProductivityPanel(Carbon $start, Carbon $end): array
    {
        $daysRange = max($start->diffInDays($end) + 1, 1);

        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $currentDispatchBase = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'sent_at')
            ->whereNotNull('protest_jobs.sent_at');
        $totalDispatched = (clone $currentDispatchBase)->count();

        $finishedBase = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'finished_at')
            ->whereNotNull('protest_jobs.finished_at');

        $finishedMeta = (clone $finishedBase)
            ->whereBetween('protest_jobs.sent_at', [$rangeStart, $rangeEnd])
            ->count();

        $finishedPassive = (clone $finishedBase)
            ->where('protest_jobs.sent_at', '<', $rangeStart)
            ->count();

        $passiveOpen = $this->baseJobsQuery()
            ->whereNotNull('protest_jobs.sent_at')
            ->where('protest_jobs.sent_at', '<', $rangeStart)
            ->where(function ($q) {
                $q->whereNull('protest_jobs.finished_at')
                    ->orWhere('protest_jobs.status', '!=', ProtestJobStatus::DONE->value);
            })
            ->count();

        $finishedTotal = $finishedMeta + $finishedPassive;

        return [
            'total_dispatched'     => $totalDispatched,
            'finished_meta'        => $finishedMeta,
            'finished_passive'     => $finishedPassive,
            'finished_total'       => $finishedTotal,
            'passivo_aberto'       => $passiveOpen,
            'avg_daily_dispatch'   => round($totalDispatched / $daysRange, 1),
            'avg_daily_finish'     => round($finishedTotal / $daysRange, 1),
        ];
    }

    protected function buildBacklogPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $periodBase = MedProtest::query()
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd]);

        $totalPeriod = (clone $periodBase)->count();
        $withJobPeriod = (clone $periodBase)->whereHas('ProtestJobs')->count();
        $withoutJobPeriod = max(0, $totalPeriod - $withJobPeriod);

        $openBase = MedProtest::query()
            ->where('statusSist', 'MEDA');

        $currentOpen = (clone $openBase)->count();
        $currentOpenWithoutJob = (clone $openBase)
            ->whereDoesntHave('ProtestJobs')
            ->count();

        $startMonth = $rangeStart->copy()->startOfMonth();
        $previousMonthStart = $startMonth->copy()->subMonth();
        $previousMonthEnd = $startMonth->copy()->subDay();

        $passiveOpen = (clone $openBase)
            ->whereBetween('dtCriacaoMedida', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $olderThanFive = (clone $openBase)
            ->whereDoesntHave('ProtestJobs')
            ->whereDate('dtCriacaoMedida', '<=', now()->subDays(5)->startOfDay())
            ->count();

        $expiredOpen = (clone $openBase)
            ->whereNotNull('dtFimMedidaDesej')
            ->whereDate('dtFimMedidaDesej', '<', now()->startOfDay())
            ->count();

        return [
            'period_total'       => $totalPeriod,
            'period_with_job'    => $withJobPeriod,
            'period_without_job' => $withoutJobPeriod,
            'current_open'       => $currentOpen,
            'current_open_without_job' => $currentOpenWithoutJob,
            'passive_open'       => $passiveOpen,
            'passive_month_label'=> $previousMonthStart->format('m/Y'),
            'older_than_5'       => $olderThanFive,
            'expired_open'       => $expiredOpen,
        ];
    }

    protected function buildSlaPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();
        $now        = now();

        $periodMedBase = MedProtest::query()
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd]);

        $medCreated     = (clone $periodMedBase)->count();
        $medStatusOpen  = (clone $periodMedBase)->where('statusSist', 'MEDA')->count();
        $medStatusClose = (clone $periodMedBase)->where('statusSist', 'MEDE')->count();

        $concludedBase = (clone $periodMedBase)->whereNotNull('dtFimMedida');
        $concludedTotal = (clone $concludedBase)->count();
        $concludedOnTime = (clone $concludedBase)
            ->whereNotNull('dtFimMedidaDesej')
            ->whereColumn('dtFimMedida', '<=', 'dtFimMedidaDesej')
            ->count();
        $concludedLate = max(0, $concludedTotal - $concludedOnTime);

        $jobsPeriod = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'sent_at')
            ->whereNotNull('protest_jobs.sla_due_at');

        $jobSlaTotal = (clone $jobsPeriod)->count();
        $jobSlaLate = (clone $jobsPeriod)
            ->where(function ($q) use ($now) {
                $q->whereNotNull('protest_jobs.sla_breached_at')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('protest_jobs.finished_at')
                            ->whereColumn('protest_jobs.finished_at', '>', 'protest_jobs.sla_due_at');
                    })
                    ->orWhere(function ($sub) use ($now) {
                        $sub->whereNull('protest_jobs.finished_at')
                            ->where('protest_jobs.sla_due_at', '<', $now);
                    });
            })
            ->count();
        $jobSlaOnTime = max(0, $jobSlaTotal - $jobSlaLate);

        $measureSlaTotal = $concludedTotal;
        $measureSlaLate  = $concludedLate;
        $measureSlaOnTime = max(0, $measureSlaTotal - $measureSlaLate);

        $volumetryRaw = (clone $periodMedBase)
            ->selectRaw('DATE(dtCriacaoMedida) as date,
                SUM(CASE WHEN statusSist = "MEDA" THEN 1 ELSE 0 END) as opened_status,
                SUM(CASE WHEN statusSist = "MEDE" THEN 1 ELSE 0 END) as closed_status
            ')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $volLabels    = [];
        $volOpenSeries  = [];
        $volClosedSeries = [];

        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->toDateString();
            $volLabels[] = $cursor->format('d/m');
            $volOpenSeries[]   = (int)($volumetryRaw[$key]->opened_status ?? 0);
            $volClosedSeries[] = (int)($volumetryRaw[$key]->closed_status ?? 0);
            $cursor->addDay();
        }

        $volumetryChart = [
            'type' => 'bar',
            'data' => [
                'labels'   => $volLabels,
                'datasets' => [
                    [
                        'label'           => 'MEDA (abertas)',
                        'data'            => $volOpenSeries,
                        'backgroundColor' => 'rgba(59,130,246,0.5)',
                        'borderColor'     => '#2563eb',
                        'borderWidth'     => 1,
                        'stack'           => 'status',
                    ],
                    [
                        'label'           => 'MEDE (encerradas)',
                        'data'            => $volClosedSeries,
                        'backgroundColor' => 'rgba(16,185,129,0.5)',
                        'borderColor'     => '#10b981',
                        'borderWidth'     => 1,
                        'stack'           => 'status',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Volumetria MEDA x MEDE (criação diária)',
                    ],
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => [
                        'stacked'     => true,
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];

        $slaChart = [
            'type' => 'bar',
            'data' => [
                'labels'   => ['SLA Solicitado', 'SLA Medida'],
                'datasets' => [
                    [
                        'label'           => 'Cumprido',
                        'backgroundColor' => 'rgba(16,185,129,0.7)',
                        'borderColor'     => '#047857',
                        'borderWidth'     => 1,
                        'data'            => [$jobSlaOnTime, $measureSlaOnTime],
                        'stack'           => 'sla',
                    ],
                    [
                        'label'           => 'Vencido',
                        'backgroundColor' => 'rgba(239,68,68,0.7)',
                        'borderColor'     => '#b91c1c',
                        'borderWidth'     => 1,
                        'data'            => [$jobSlaLate, $measureSlaLate],
                        'stack'           => 'sla',
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Cumprimento de SLA (jobs x medidas)',
                    ],
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => [
                        'stacked'     => true,
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];

        return [
            'med_created'        => $medCreated,
            'med_open_status'    => $medStatusOpen,
            'med_closed_status'  => $medStatusClose,
            'concluded_total'    => $concludedTotal,
            'concluded_on_time'  => $concludedOnTime,
            'concluded_rate'     => $concludedTotal > 0 ? round(($concludedOnTime / $concludedTotal) * 100, 1) : 0,
            'job_sla' => [
                'total'   => $jobSlaTotal,
                'on_time' => $jobSlaOnTime,
                'late'    => $jobSlaLate,
                'rate'    => $jobSlaTotal > 0 ? round(($jobSlaOnTime / $jobSlaTotal) * 100, 1) : 0,
            ],
            'measure_sla' => [
                'total'   => $measureSlaTotal,
                'on_time' => $measureSlaOnTime,
                'late'    => $measureSlaLate,
                'rate'    => $measureSlaTotal > 0 ? round(($measureSlaOnTime / $measureSlaTotal) * 100, 1) : 0,
            ],
            'volumetry_chart' => $volumetryChart,
            'sla_chart'       => $slaChart,
        ];
    }

    protected function measureSlaOnTimeCaseSql(): string
    {
        return '
            CASE
                WHEN med_protests.dtFimMedida IS NOT NULL
                     AND protests.tipoNota = "NA"
                     AND protests.dtConclusaoDesej IS NOT NULL
                     AND med_protests.dtFimMedida <= protests.dtConclusaoDesej
                THEN 1
                WHEN med_protests.dtFimMedida IS NOT NULL
                     AND (protests.tipoNota <> "NA" OR protests.tipoNota IS NULL)
                     AND med_protests.dtFimMedidaDesej IS NOT NULL
                     AND med_protests.dtFimMedida <= med_protests.dtFimMedidaDesej
                THEN 1
                ELSE 0
            END
        ';
    }

    protected function periodMeasuresBaseQuery(Carbon $start, Carbon $end)
    {
        $query = MedProtest::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereHas('protest', function ($sub) use ($start, $end) {
                    $sub->where('tipoNota', 'NA')
                        ->whereBetween('dtConclusaoDesej', [$start, $end]);
                })
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereBetween('dtFimMedidaDesej', [$start, $end])
                        ->whereHas('protest', function ($tipo) {
                            $tipo->where('tipoNota', '!=', 'NA')
                                ->orWhereNull('tipoNota');
                        });
                });
            })
            ->whereDoesntHave('protest.medProtests', function ($q) {
                $q->where('statusSist', 'MEDA');
            });

        return $this->applyMedProtestTypeFilter($query);
    }

    protected function firstDispatchJobSubquery()
    {
        return ProtestJob::selectRaw('med_protest_id, MIN(id) as job_id')
            ->whereNotNull('created_by')
            ->groupBy('med_protest_id');
    }

    protected function isMeasureOnTime(MedProtest $measure): bool
    {
        $finishedAt = $measure->dtFimMedida;
        if (! $finishedAt) {
            return false;
        }

        $tipoNota = $measure->protest?->tipoNota;
        if ($tipoNota === 'NA') {
            $due = $measure->protest?->dtConclusaoDesej;
            return $due ? $finishedAt->lte($due) : false;
        }

        $due = $measure->dtFimMedidaDesej;
        return $due ? $finishedAt->lte($due) : false;
    }

    protected function buildDispatcherMeasuresPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $base = $this->periodMeasuresBaseQuery($rangeStart, $rangeEnd);

        $totalMeasures = (clone $base)->count();

        $concludedBase = (clone $base)->whereNotNull('med_protests.dtFimMedida');

        $onTimeMeasures = (int) (clone $concludedBase)
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->selectRaw('SUM(' . $this->measureSlaOnTimeCaseSql() . ') as on_time')
            ->value('on_time');

        $concludedTotal = (clone $concludedBase)->count();
        $lateMeasures = max(0, $concludedTotal - $onTimeMeasures);
        $onTimeRate = $concludedTotal > 0
            ? round(($onTimeMeasures / $concludedTotal) * 100, 1)
            : 0;

        $firstJobs = $this->firstDispatchJobSubquery();

        $dispatchedBase = (clone $base)
            ->joinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
            ->join('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id');

        $dispatchedTotal = (clone $dispatchedBase)->count();

        $dispatchedOnTime = (int) (clone $dispatchedBase)
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->selectRaw('SUM(' . $this->measureSlaOnTimeCaseSql() . ') as on_time')
            ->value('on_time');

        $dispatchedConcluded = (clone $dispatchedBase)->whereNotNull('med_protests.dtFimMedida')->count();
        $dispatchedLate = max(0, $dispatchedConcluded - $dispatchedOnTime);
        $dispatchedRate = $dispatchedConcluded > 0
            ? round(($dispatchedOnTime / $dispatchedConcluded) * 100, 1)
            : 0;

        $dispatcherRows = (clone $dispatchedBase)
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->selectRaw('
                first_job.created_by as user_id,
                COUNT(*) as total_measures,
                SUM(' . $this->measureSlaOnTimeCaseSql() . ') as on_time
            ')
            ->groupBy('first_job.created_by')
            ->get();

        $users = User::whereIn('id', $dispatcherRows->pluck('user_id')->filter())->get()->keyBy('id');

        $dispatchers = $dispatcherRows->map(function ($row) use ($users) {
            $total = (int) $row->total_measures;
            $onTime = (int) $row->on_time;
            $late = max(0, $total - $onTime);

            return [
                'user_id'    => $row->user_id,
                'user_name'  => optional($users->get($row->user_id))->name ?? 'N/A',
                'total'      => $total,
                'on_time'    => $onTime,
                'late'       => $late,
                'sla_rate'   => $total > 0 ? round(($onTime / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values();

        $selectedUser = null;
        if ($this->userId) {
            $selectedUserName = User::find($this->userId)?->name ?? 'N/A';

            $listQuery = (clone $base)
                ->joinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
                ->join('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id')
                ->where('first_job.created_by', $this->userId)
                ->with(['protest:id,nota,tipoNota,dtConclusaoDesej'])
                ->select([
                    'med_protests.*',
                    'first_job.id as job_id',
                    'first_job.sent_at as job_sent_at',
                ])
                ->orderByDesc('med_protests.dtFimMedidaDesej');

            $measures = $listQuery->paginate(10, ['*'], 'dispatcher_measures_page');

            $measures->setCollection($measures->getCollection()->map(function (MedProtest $measure) {
                $isOnTime = $this->isMeasureOnTime($measure);
                $dueDate = $measure->protest?->tipoNota === 'NA'
                    ? $measure->protest?->dtConclusaoDesej
                    : $measure->dtFimMedidaDesej;

                return [
                    'protest_number' => $measure->protest?->nota ?? 'N/A',
                    'med_id'         => $measure->med_id ?? 'N/A',
                    'due_date'       => $dueDate?->format('d/m/Y') ?? 'N/A',
                    'finished_at'    => $measure->dtFimMedida?->format('d/m/Y') ?? 'N/A',
                    'job_id'         => $measure->job_id ?? null,
                    'job_sent_at'    => $measure->job_sent_at
                        ? Carbon::parse($measure->job_sent_at)->format('d/m/Y H:i')
                        : 'N/A',
                    'status_label'   => $isOnTime ? 'Dentro do prazo' : 'Fora do prazo',
                    'status_badge'   => $isOnTime ? 'bg-success' : 'bg-danger',
                ];
            }));

            $selectedUser = [
                'name'     => $selectedUserName,
                'measures' => $measures,
            ];
        }

        return [
            'period_label'     => $rangeStart->format('d/m/Y') . ' - ' . $rangeEnd->format('d/m/Y'),
            'total_measures'   => $totalMeasures,
            'on_time'          => $onTimeMeasures,
            'late'             => $lateMeasures,
            'on_time_rate'     => $onTimeRate,
            'dispatched_total' => $dispatchedTotal,
            'dispatched_on'    => $dispatchedOnTime,
            'dispatched_late'  => $dispatchedLate,
            'dispatched_rate'  => $dispatchedRate,
            'dispatchers'      => $dispatchers,
            'selected_user'    => $selectedUser,
        ];
    }

    protected function buildBottlenecksPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();
        $now        = now();

        $periodBase = MedProtest::query()
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd]);

        $categoryRows = (clone $periodBase)
            ->selectRaw('
                protest_type,
                COUNT(*) as total_medidas,
                SUM(CASE WHEN statusSist = "MEDA" THEN 1 ELSE 0 END) as abertas,
                SUM(CASE WHEN statusSist = "MEDA" AND dtFimMedidaDesej IS NOT NULL AND dtFimMedidaDesej < ? THEN 1 ELSE 0 END) as vencidas
            ', [$now])
            ->groupBy('protest_type')
            ->get();

        $prevMonthStart = $rangeStart->copy()->startOfMonth()->subMonth();
        $prevMonthEnd   = $rangeStart->copy()->startOfMonth()->subDay();

        $passiveRows = MedProtest::query()
            ->whereBetween('dtCriacaoMedida', [$prevMonthStart, $prevMonthEnd])
            ->where('statusSist', 'MEDA')
            ->selectRaw('protest_type, COUNT(*) as total_passivo')
            ->groupBy('protest_type')
            ->pluck('total_passivo', 'protest_type');

        $totalMedidasPeriodo = max(1, $categoryRows->sum('total_medidas'));

        $categories = $categoryRows->map(function ($row) use ($totalMedidasPeriodo, $passiveRows) {
            $total = (int)$row->total_medidas;
            $open  = (int)$row->abertas;
            $late  = (int)$row->vencidas;
            $typeKey = $row->protest_type instanceof ProtestType ? $row->protest_type->value : $row->protest_type;
            $passive = (int)($passiveRows[$typeKey] ?? 0);

            return [
                'type_value' => $row->protest_type,
                'label'      => $this->resolveProtestTypeLabel($row->protest_type),
                'total'      => $total,
                'abertas'    => $open,
                'passivo'    => $passive,
                'vencidas'   => $late,
                'percent'    => round(($total / $totalMedidasPeriodo) * 100, 1),
            ];
        })->sortByDesc('total')->values();

        $categoriesTotals = [
            'total'   => $categories->sum('total'),
            'abertas' => $categories->sum('abertas'),
            'passivo' => $categories->sum('passivo'),
            'vencidas'=> $categories->sum('vencidas'),
        ];

        $tipoNota = Protest::query()
            ->whereNotNull('dtAberturaNota')
            ->whereBetween('dtAberturaNota', [$rangeStart, $rangeEnd])
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->selectRaw('protests.tipoNota as tipoNota, COUNT(*) as total')
            ->groupBy('protests.tipoNota')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'tipo'  => $row->tipoNota ?? 'Sem classificacao',
                    'total' => (int)($row->total ?? 0),
                ];
            })
            ->toArray();

        $tipoNotaLate = MedProtest::query()
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->where('med_protests.statusSist', 'MEDA')
            ->whereNotNull('med_protests.dtFimMedidaDesej')
            ->whereBetween('med_protests.dtFimMedidaDesej', [$rangeStart, $rangeEnd])
            ->where('med_protests.dtFimMedidaDesej', '<', $now)
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->selectRaw('protests.tipoNota as tipoNota, COUNT(*) as total')
            ->groupBy('protests.tipoNota')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'tipo'  => $row->tipoNota ?? 'Sem classificacao',
                    'total' => (int)($row->total ?? 0),
                ];
            })
            ->toArray();

        return [
            'categories'        => $categories->toArray(),
            'categories_totals' => $categoriesTotals,
            'tipo_nota'         => $tipoNota,
            'tipo_nota_late'    => $tipoNotaLate,
        ];
    }

    protected function buildDailyOpeningsChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $protestData = Protest::query()
            ->whereNotNull('dtAberturaNota')
            ->whereBetween('dtAberturaNota', [$rangeStart, $rangeEnd])
            ->whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->selectRaw('DATE(dtAberturaNota) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $medData = MedProtest::query()
            ->whereNotNull('dtCriacaoMedida')
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd])
            ->whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->selectRaw('DATE(dtCriacaoMedida) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels         = [];
        $seriesProtests = [];
        $seriesMed      = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();    // Y-m-d
            $labels[] = $cursor->format('d/m'); // label visual

            $seriesProtests[] = (int)($protestData[$key] ?? 0);
            $seriesMed[]      = (int)($medData[$key] ?? 0);

            $cursor->addDay();
        }

        $points = max(count($labels), 1);
        $avgProtest = $points > 0 ? round(array_sum($seriesProtests) / $points, 2) : 0;
        $avgMed     = $points > 0 ? round(array_sum($seriesMed) / $points, 2) : 0;
        $avgProtestSeries = array_fill(0, count($labels), $avgProtest);
        $avgMedSeries     = array_fill(0, count($labels), $avgMed);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'Abertura Reclamações',
                        'data'            => $seriesProtests,
                        'backgroundColor' => 'rgba(102,126,234,0.4)',
                        'borderColor'     => '#667eea',
                        'borderWidth'     => 1,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média Reclamação',
                        'data'        => $avgProtestSeries,
                        'borderColor' => '#1f3a8a',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Criação Medidas',
                        'data'            => $seriesMed,
                        'borderColor'     => '#f5576c',
                        'backgroundColor' => 'rgba(245,87,108,0.2)',
                        'tension'         => 0.1,
                        'fill'            => false,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média Medidas',
                        'data'        => $avgMedSeries,
                        'borderColor' => '#b71c1c',
                        'borderWidth' => 2,
                        'borderDash'  => [4, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Aberturas diárias (Reclamações x Medidas)',
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title'       => [
                            'display' => true,
                            'text'    => 'Qtd de registros',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildMedaSnapshot(Carbon $start, Carbon $end): array
    {
        $openMeasures = MedProtest::where('statusSist', 'MEDA')
            ->whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->count();

        $openProtests = Protest::whereHas('medProtests', function ($q) {
            $q->where('statusSist', 'MEDA')
                ->whereHas('ProtestJobs');
        })
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->count();

        $totalProtests  = Protest::whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->count();
        $closedProtests = Protest::whereHas('ProtestJobs', function ($q) {
            $q->whereNotNull('finished_at');
        })
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->count();

        $baselineStart = $start->copy()->startOfDay();
        $baselineEnd = $end->copy()->endOfDay();
        $daysInRange = max($baselineStart->diffInDays($baselineEnd) + 1, 1);

        $baselineJobs = $this->jobsBaseQuery($baselineStart, $baselineEnd, 'finished_at')
            ->whereNotNull('finished_at')
            ->whereNotNull('med_protest_id')
            ->whereHas('medProtest', function ($q) {
                $q->where('statusSist', '!=', 'MEDA');
            });

        $dispatchedJobs = (clone $baselineJobs)->whereNotNull('created_by')->count();
        $dispatcherUsers = (int)(clone $baselineJobs)
            ->whereNotNull('created_by')
            ->selectRaw('COUNT(DISTINCT protest_jobs.created_by) as total')
            ->value('total');

        $finishedJobs = (clone $baselineJobs)->count();
        $executorUsers = (int)(clone $baselineJobs)
            ->whereNotNull('owner_id')
            ->selectRaw('COUNT(DISTINCT protest_jobs.owner_id) as total')
            ->value('total');

        $avgDispatchDaily    = round($dispatchedJobs / $daysInRange, 1);
        $avgFinishDaily      = round($finishedJobs / $daysInRange, 1);
        $avgDispatchPerUser  = $dispatcherUsers > 0 ? round($dispatchedJobs / ($dispatcherUsers * $daysInRange), 1) : 0;
        $avgFinishPerUser    = $executorUsers > 0 ? round($finishedJobs / ($executorUsers * $daysInRange), 1) : 0;

        $daysToClear = $avgFinishDaily > 0 ? (int)ceil($openMeasures / $avgFinishDaily) : null;

        $statusLabel = 'Sem dados';
        $statusBadge = 'bg-secondary';
        $statusMessage = 'Período ainda sem conclusões para estimar produtividade.';

        if (!is_null($daysToClear)) {
            if ($daysToClear <= 7) {
                $statusLabel = 'Baixo';
                $statusBadge = 'bg-success';
                $statusMessage = 'Capacidade atual elimina a pilha em menos de uma semana.';
            } elseif ($daysToClear <= 15) {
                $statusLabel = 'Moderado';
                $statusBadge = 'bg-warning text-dark';
                $statusMessage = 'A pilha será zerada em aproximadamente ' . $daysToClear . ' dias.';
            } else {
                $statusLabel = 'Alto';
                $statusBadge = 'bg-danger';
                $statusMessage = 'Backlog exige mais de ' . $daysToClear . ' dias com a produtividade atual.';
            }
        }

        return [
            'open_measures'         => $openMeasures,
            'open_protests'         => $openProtests,
            'closed_protests'       => $closedProtests,
            'total_protests'        => $totalProtests,
            'dispatcher_users'      => $dispatcherUsers,
            'executor_users'        => $executorUsers,
            'avg_dispatch_daily'    => $avgDispatchDaily,
            'avg_dispatch_per_user' => $avgDispatchPerUser,
            'avg_finish_daily'      => $avgFinishDaily,
            'avg_finish_per_user'   => $avgFinishPerUser,
            'days_to_clear'         => $daysToClear,
            'status_label'          => $statusLabel,
            'status_badge_class'    => $statusBadge,
            'status_message'        => $statusMessage,
            'days_considered'       => $daysInRange,
            'sample_start'          => $baselineStart->format('d/m/Y'),
            'sample_end'            => $baselineEnd->format('d/m/Y'),
        ];
    }

    /**
     * Gráfico para MEDA: med_protests com e sem job relacionado.
     */
    protected function buildMedaJobsChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $jobsSub = ProtestJob::selectRaw('med_protest_id, COUNT(*) as job_count')
            ->groupBy('med_protest_id');

        $raw = MedProtest::query()
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->leftJoinSub($jobsSub, 'jobs', 'jobs.med_protest_id', '=', 'med_protests.id')
            ->whereBetween('med_protests.dtCriacaoMedida', [$rangeStart, $rangeEnd])
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->selectRaw("
                DATE(med_protests.dtCriacaoMedida) as date,
                SUM(CASE WHEN COALESCE(jobs.job_count, 0) > 0 THEN 1 ELSE 0 END) as with_job,
                SUM(CASE WHEN COALESCE(jobs.job_count, 0) = 0 THEN 1 ELSE 0 END) as without_job
            ")
                ->groupBy('date')
                ->get()
                ->keyBy('date');

        $labels            = [];
        $seriesWithJob     = [];
        $seriesNoJob       = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');

            $withJobValue = $raw[$key]->with_job ?? 0;
            $withoutJobValue = $raw[$key]->without_job ?? 0;

            $seriesWithJob[] = (int)$withJobValue;
            $seriesNoJob[]   = (int)$withoutJobValue;

            $cursor->addDay();
        }

        $points = max(count($labels), 1);
        $avgWithJob = $points > 0 ? round(array_sum($seriesWithJob) / $points, 2) : 0;
        $avgWithoutJob = $points > 0 ? round(array_sum($seriesNoJob) / $points, 2) : 0;
        $avgWithJobSeries = array_fill(0, count($labels), $avgWithJob);
        $avgWithoutJobSeries = array_fill(0, count($labels), $avgWithoutJob);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'MEDA criadas com Job',
                        'data'            => $seriesWithJob,
                        'backgroundColor' => 'rgba(16,185,129,0.4)',
                        'borderColor'     => '#10b981',
                        'borderWidth'     => 1,
                        'stack'           => 'meda',
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Media MEDA com Job',
                        'data'        => $avgWithJobSeries,
                        'borderColor' => '#0f766e',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'MEDA criadas sem Job',
                        'data'            => $seriesNoJob,
                        'backgroundColor' => 'rgba(239,68,68,0.35)',
                        'borderColor'     => '#ef4444',
                        'borderWidth'     => 1,
                        'stack'           => 'meda',
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Media MEDA sem Job',
                        'data'        => $avgWithoutJobSeries,
                        'borderColor' => '#be123c',
                        'borderWidth' => 2,
                        'borderDash'  => [4, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'MEDA criadas (com x sem Job)',
                    ],
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => [
                        'stacked'      => true,
                        'beginAtZero'  => true,
                        'title'        => [
                            'display' => true,
                            'text'    => 'Qtd de medidas',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildDailyDispatchCompletionChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $dispatchRows = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'sent_at')
            ->whereNotNull('protest_jobs.sent_at')
            ->selectRaw('DATE(protest_jobs.sent_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $completionRows = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'finished_at')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('DATE(protest_jobs.finished_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels           = [];
        $dispatchSeries   = [];
        $completionSeries = [];

        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $dispatchSeries[]   = (int)($dispatchRows[$key] ?? 0);
            $completionSeries[] = (int)($completionRows[$key] ?? 0);
            $cursor->addDay();
        }

        $points = max(count($labels), 1);
        $avgDispatch   = $points > 0 ? round(array_sum($dispatchSeries) / $points, 2) : 0;
        $avgCompletion = $points > 0 ? round(array_sum($completionSeries) / $points, 2) : 0;

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'type'            => 'line',
                        'label'           => 'Despachos diários',
                        'data'            => $dispatchSeries,
                        'backgroundColor' => 'rgba(59,130,246,0.25)',
                        'borderColor'     => '#2563eb',
                        'borderWidth'     => 3,
                        'tension'         => 0.25,
                        'fill'            => true,
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Conclusões diárias',
                        'data'            => $completionSeries,
                        'backgroundColor' => 'rgba(220,38,38,0.25)',
                        'borderColor'     => '#dc2626',
                        'borderWidth'     => 3,
                        'tension'         => 0.25,
                        'fill'            => true,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média despachos',
                        'data'        => array_fill(0, count($labels), $avgDispatch),
                        'borderColor' => '#1d4ed8',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média conclusões',
                        'data'        => array_fill(0, count($labels), $avgCompletion),
                        'borderColor' => '#b91c1c',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Despachos x Conclusões por dia',
                    ],
                    'tooltip' => [
                        'mode' => 'index',
                        'intersect' => false,
                    ],
                ],
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title'       => [
                            'display' => true,
                            'text'    => 'Quantidade de jobs',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function dispatchDailyOpeningsChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-dailyOpenings', $chart);
        $this->dispatchBrowserEvent('grafico-atualizar-aberturas-diarias', $chart);
    }

    protected function dispatchMedaJobsChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-medaJobs', $chart);
    }

    protected function dispatchDailyDispatchCompletionChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-dailyDispatchCompletion', $chart);
    }

    protected function buildJobSlaList(Carbon $start, Carbon $end): array
    {
        $base = $this->jobsBaseQuery($start, $end);

        $rows = (clone $base)
            ->leftJoin('protests', 'protests.id', '=', 'protest_jobs.protest_id')
            ->leftJoin('med_protests', 'med_protests.id', '=', 'protest_jobs.med_protest_id')
            ->whereNotNull('protest_jobs.sla_due_at')
            ->select([
                'protest_jobs.id',
                'protests.nota as protest_number',
                'med_protests.med_id as med_id',
                'med_protests.dtFimMedidaDesej as med_sla_due',
                'protest_jobs.sla_due_at',
                'protest_jobs.finished_at',
                'protest_jobs.sla_breached_at',
            ])
            ->orderByDesc('protest_jobs.sla_due_at')
            ->limit(50)
            ->get();

        return $rows->map(function ($row) {
            $slaDue    = $row->sla_due_at ? Carbon::parse($row->sla_due_at) : null;
            $medSlaDue = $row->med_sla_due ? Carbon::parse($row->med_sla_due) : null;
            $finished  = $row->finished_at ? Carbon::parse($row->finished_at) : null;
            $reference = $finished ?? now();
            $diffSeconds = $medSlaDue ? $reference->diffInSeconds($medSlaDue, false) : null;

            $isBreached = $diffSeconds !== null && $diffSeconds > 0;

            $statusLabel = $isBreached
                ? 'Fora do prazo'
                : ($finished ? 'Dentro do prazo' : 'Em andamento');

            $statusBadge = $isBreached
                ? 'bg-danger'
                : ($finished ? 'bg-success' : 'bg-secondary');

            $deltaLabel = null;
            if ($diffSeconds !== null) {
                if ($diffSeconds > 0) {
                    $deltaLabel = '+' . $this->secondsToHuman($diffSeconds) . ' de atraso';
                } elseif ($diffSeconds < 0) {
                    $deltaLabel = '-' . $this->secondsToHuman(abs($diffSeconds)) . ' restante';
                } else {
                    $deltaLabel = 'No prazo';
                }
            }

            return [
                'job_id'          => $row->id,
                'protest_number'  => $row->protest_number ?? 'N/A',
                'med_id'          => $row->med_id ?? 'N/A',
                'med_sla_due_at'  => $medSlaDue ? $medSlaDue->format('d/m/Y H:i') : 'N/A',
                'sla_due_at'      => $slaDue ? $slaDue->format('d/m/Y H:i') : 'N/A',
                'finished_at'     => $finished ? $finished->format('d/m/Y H:i') : 'Em aberto',
                'status_label'    => $statusLabel,
                'status_badge'    => $statusBadge,
                'delta_label'     => $deltaLabel,
            ];
        })->toArray();
    }

    protected function buildDueMeasures(): array
    {
        $base = MedProtest::query()
            ->with(['protest:id,nota'])
            ->where('statusSist', 'MEDA')
            ->whereNotNull('dtFimMedidaDesej');

        $base = $this->applyMedProtestTypeFilter($base);

        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();
        $perPage    = 10;

        $dueTodayQuery = (clone $base)
            ->whereBetween('dtFimMedidaDesej', [$todayStart, $todayEnd])
            ->orderBy('dtFimMedidaDesej');

        $overdueQuery = (clone $base)
            ->where('dtFimMedidaDesej', '<', $todayStart)
            ->orderBy('dtFimMedidaDesej');

        $dueToday = $dueTodayQuery->paginate($perPage, ['*'], 'due_today_page');
        $overdue  = $overdueQuery->paginate($perPage, ['*'], 'overdue_page');

        $dueToday->setCollection($this->transformDueMeasures($dueToday->getCollection()));
        $overdue->setCollection($this->transformDueMeasures($overdue->getCollection()));

        return [
            'due_today' => $dueToday,
            'overdue'   => $overdue,
        ];
    }

    protected function transformDueMeasures($collection)
    {
        return $collection->map(function (MedProtest $measure) {
            return [
                'protest_id'         => $measure->protest_id,
                'protest_number'     => $measure->protest->nota ?? 'N/A',
                'med_id'             => $measure->med_id ?? 'N/A',
                'due_date'           => optional($measure->dtFimMedidaDesej)->format('d/m/Y'),
                'protest_type_label' => $this->resolveProtestTypeLabel($measure->protest_type),
            ];
        });
    }

    protected function toast(string $status, string $message): void
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => $status,
            'menssage' => $message,
        ]);
    }

    public function exportJobs(): void
    {
        [$start, $end] = $this->getDateRange();

        ExportProtestJobsJob::dispatch([
            'start'         => $start->toDateTimeString(),
            'end'           => $end->toDateTimeString(),
            'advanceFilter' => $this->advanceFilter,
            'userId'        => $this->userId,
        ], (string) auth()->id());

        $this->toast('info', 'Estamos gerando o Excel com os filtros aplicados. Você será notificado ao final.');
    }



    public function exportDispatcherMeasures(): void
    {
        [$start, $end] = $this->getDateRange();

        ExportDispatcherMeasuresJob::dispatch([
            'start'        => $start->toDateTimeString(),
            'end'          => $end->toDateTimeString(),
            'userId'       => $this->userId,
            'protestTypes' => $this->getSelectedProtestTypes(),
        ], (string) auth()->id());

        $this->toast('info', 'Estamos gerando o Excel de medidas MEDE. Voce sera notificado ao final.');
    }

    public function render()
    {
        [$start, $end] = $this->getDateRange();

        $summary         = $this->buildSummary($start, $end);
        $productivity    = $this->buildProductivityPanel($start, $end);
        $backlogPanel    = $this->buildBacklogPanel($start, $end);
        $slaPanel        = $this->buildSlaPanel($start, $end);
        $bottlenecks     = $this->buildBottlenecksPanel($start, $end);
        $dispatcherStats = $this->buildDispatcherStats($start, $end);
        $ownerStats      = $this->buildOwnerStats($start, $end);
        $dailyOpenings          = $this->buildDailyOpeningsChart($start, $end);
        $medaJobsChart          = $this->buildMedaJobsChart($start, $end);
        $dailyDispatchCompletion = $this->buildDailyDispatchCompletionChart($start, $end);
        $jobSlaList             = $this->buildJobSlaList($start, $end);
        $medaSnapshot           = $this->buildMedaSnapshot($start, $end);
        $dueMeasures            = $this->buildDueMeasures();
        $dispatcherMeasuresPanel = $this->buildDispatcherMeasuresPanel($start, $end);

        $this->dispatchDailyOpeningsChart($dailyOpenings);
        $this->dispatchMedaJobsChart($medaJobsChart);
        $this->dispatchDailyDispatchCompletionChart($dailyDispatchCompletion);

        return view('livewire.protests.analytics.user-sla-dashboard', [
            'summary'                 => $summary,
            'productivity'            => $productivity,
            'backlogPanel'            => $backlogPanel,
            'slaPanel'                => $slaPanel,
            'bottlenecks'             => $bottlenecks,
            'dispatcherStats'         => $dispatcherStats,
            'ownerStats'              => $ownerStats,
            'dailyOpenings'           => $dailyOpenings,
            'medaJobsChart'           => $medaJobsChart,
            'dailyDispatchCompletion' => $dailyDispatchCompletion,
            'jobSlaList'              => $jobSlaList,
            'medaSnapshot'            => $medaSnapshot,
            'dueMeasures'             => $dueMeasures,
            'dispatcherMeasuresPanel' => $dispatcherMeasuresPanel,
        ]);
    }
}
