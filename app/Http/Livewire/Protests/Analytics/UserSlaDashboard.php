<?php

namespace App\Http\Livewire\Protests\Analytics;

use App\Models\MedProtest;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;

class UserSlaDashboard extends Component
{
    public $dt_in;
    public $dt_out;
    public $advanceFilter = 'all'; // all | advance | normal
    public $userId = null;

    public $usersOptions = [];

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
    }

    /**
     * Query base para filtrar os ProtestJobs do período / filtros.
     */
    protected function jobsBaseQuery(Carbon $start, Carbon $end, string $dateColumn = 'sent_at')
    {
        if (!in_array($dateColumn, ['sent_at', 'finished_at'], true)) {
            $dateColumn = 'sent_at';
        }

        return ProtestJob::query()
            ->whereBetween($dateColumn, [$start, $end])
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
            });
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
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, med_protests.created_at, protest_jobs.sent_at)) as avg_reaction_seconds')
            ->first();

        $avgReactionSeconds = (int)($dispatcherGlobal->avg_reaction_seconds ?? 0);

        // Média global de execução (Responsáveis)
        $ownerGlobal = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->whereNotNull('protest_jobs.started_at')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, protest_jobs.started_at, protest_jobs.finished_at)) as avg_exec_seconds')
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
            'period_label'       => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
            'total_jobs'         => $totalJobs,
            'finished_jobs'      => $finishedJobs,
            'sla_rate'           => $slaRate,
            'avg_reaction_sec'   => $avgReactionSeconds,
            'avg_reaction_human' => $this->secondsToHuman($avgReactionSeconds),
            'avg_exec_sec'       => $avgExecSeconds,
            'avg_exec_human'     => $this->secondsToHuman($avgExecSeconds),
            'self_closure_rate'  => $selfClosureRate,
            'self_closed'        => $selfClosed,
            'total_closed'       => $totalClosed,
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
                AVG(TIMESTAMPDIFF(SECOND, protest_jobs.started_at, protest_jobs.finished_at)) as avg_exec_seconds
            ')
            ->groupBy('protest_jobs.owner_id')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $totalJobs     = (int)$row->total_jobs;
            $totalAdvance  = (int)($row->total_advance ?? 0);
            $finishedJobs  = (int)($row->finished_jobs ?? 0);
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

    /**
     * Gráfico de aberturas diárias usando apenas Protest / MedProtest.
     */
    protected function buildDailyOpeningsChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $protestData = Protest::query()
            ->whereNotNull('dtAberturaNota')
            ->whereBetween('dtAberturaNota', [$rangeStart, $rangeEnd])
            ->whereHas('ProtestJobs')
            ->selectRaw('DATE(dtAberturaNota) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $medData = MedProtest::query()
            ->whereNotNull('dtCriacaoMedida')
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd])
            ->whereHas('ProtestJobs')
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
                        'label'           => 'Abertura Reclamações (Protest)',
                        'data'            => $seriesProtests,
                        'backgroundColor' => 'rgba(102,126,234,0.4)',
                        'borderColor'     => '#667eea',
                        'borderWidth'     => 1,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média Protest',
                        'data'        => $avgProtestSeries,
                        'borderColor' => '#1f3a8a',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Criação Medidas (MedProtest)',
                        'data'            => $seriesMed,
                        'borderColor'     => '#f5576c',
                        'backgroundColor' => 'rgba(245,87,108,0.2)',
                        'tension'         => 0.1,
                        'fill'            => false,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média MedProtest',
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
                        'text'    => 'Aberturas diárias (Protest x MedProtest)',
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
            ->count();

        $openProtests = Protest::whereHas('medProtests', function ($q) {
            $q->where('statusSist', 'MEDA')
                ->whereHas('ProtestJobs');
        })->count();

        $totalProtests  = Protest::whereHas('ProtestJobs')->count();
        $closedProtests = Protest::whereHas('ProtestJobs', function ($q) {
            $q->whereNotNull('finished_at');
        })->count();

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
            ->leftJoinSub($jobsSub, 'jobs', 'jobs.med_protest_id', '=', 'med_protests.id')
            ->where('med_protests.statusSist', 'MEDA')
            ->whereBetween('med_protests.dtCriacaoMedida', [$rangeStart, $rangeEnd])
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
                        'label'           => 'MEDA com Job',
                        'data'            => $seriesWithJob,
                        'backgroundColor' => 'rgba(16,185,129,0.4)',
                        'borderColor'     => '#10b981',
                        'borderWidth'     => 1,
                        'stack'           => 'meda',
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média MEDA com Job',
                        'data'        => $avgWithJobSeries,
                        'borderColor' => '#0f766e',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'MEDA sem Job',
                        'data'            => $seriesNoJob,
                        'backgroundColor' => 'rgba(239,68,68,0.35)',
                        'borderColor'     => '#ef4444',
                        'borderWidth'     => 1,
                        'stack'           => 'meda',
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média MEDA sem Job',
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
                        'text'    => 'Medidas MEDA (com x sem Job)',
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
                'protest_jobs.sla_due_at',
                'protest_jobs.finished_at',
                'protest_jobs.sla_breached_at',
            ])
            ->orderByDesc('protest_jobs.sla_due_at')
            ->limit(50)
            ->get();

        return $rows->map(function ($row) {
            $slaDue    = $row->sla_due_at ? Carbon::parse($row->sla_due_at) : null;
            $finished  = $row->finished_at ? Carbon::parse($row->finished_at) : null;
            $reference = $finished ?? now();
            $diffSeconds = $slaDue ? $reference->diffInSeconds($slaDue, false) : null;

            $isBreached = !is_null($row->sla_breached_at) || ($diffSeconds !== null && $diffSeconds > 0);

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
                'sla_due_at'      => $slaDue ? $slaDue->format('d/m/Y H:i') : 'N/A',
                'finished_at'     => $finished ? $finished->format('d/m/Y H:i') : 'Em aberto',
                'status_label'    => $statusLabel,
                'status_badge'    => $statusBadge,
                'delta_label'     => $deltaLabel,
            ];
        })->toArray();
    }



    public function render()
    {
        [$start, $end] = $this->getDateRange();

        $summary         = $this->buildSummary($start, $end);
        $dispatcherStats = $this->buildDispatcherStats($start, $end);
        $ownerStats      = $this->buildOwnerStats($start, $end);
        $dailyOpenings          = $this->buildDailyOpeningsChart($start, $end);
        $medaJobsChart          = $this->buildMedaJobsChart($start, $end);
        $dailyDispatchCompletion = $this->buildDailyDispatchCompletionChart($start, $end);
        $jobSlaList             = $this->buildJobSlaList($start, $end);
        $medaSnapshot           = $this->buildMedaSnapshot($start, $end);

        $this->dispatchDailyOpeningsChart($dailyOpenings);
        $this->dispatchMedaJobsChart($medaJobsChart);
        $this->dispatchDailyDispatchCompletionChart($dailyDispatchCompletion);

        return view('livewire.protests.analytics.user-sla-dashboard', [
            'summary'                 => $summary,
            'dispatcherStats'         => $dispatcherStats,
            'ownerStats'              => $ownerStats,
            'dailyOpenings'           => $dailyOpenings,
            'medaJobsChart'           => $medaJobsChart,
            'dailyDispatchCompletion' => $dailyDispatchCompletion,
            'jobSlaList'              => $jobSlaList,
            'medaSnapshot'            => $medaSnapshot,
        ]);
    }
}
