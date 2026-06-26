<?php

namespace App\Http\Livewire\Config\System;

use App\Models\SqlsrvHealthSnapshot;
use App\Models\SqlsrvJobLogSnapshot;
use App\Models\SqlsrvSourceMetricSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Livewire\WithPagination;

class SqlsrvHealth extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $periodDays = 7;
    public string $selectedSource = '';
    public string $statusFilter = '';
    public string $activeTab = 'executive';

    public function updatingPeriodDays(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setActiveTab(string $tab): void
    {
        if (in_array($tab, ['executive', 'technical'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function runCollectNow(): void
    {
        $exitCode = Artisan::call('sicode:collect-sqlsrv1-health');

        $output = trim(Artisan::output());

        $this->dispatchBrowserEvent('toast-message', [
            'type' => $exitCode === 0 ? 'success' : 'warning',
            'message' => $output ?: 'Coleta executada.',
        ]);
    }

    public function render()
    {
        $from = now()->subDays($this->periodDays);
        $lastSnapshot = SqlsrvHealthSnapshot::latest('collected_at')->first();
        $snapshots = SqlsrvHealthSnapshot::query()
            ->where('collected_at', '>=', $from)
            ->orderBy('collected_at')
            ->get();

        $latestMetricSnapshotId = $lastSnapshot?->id;
        $latestMetrics = $latestMetricSnapshotId
            ? SqlsrvSourceMetricSnapshot::where('snapshot_id', $latestMetricSnapshotId)->orderBy('source_name')->get()
            : collect();

        $sourceOptions = SqlsrvSourceMetricSnapshot::query()
            ->select('source_name')
            ->distinct()
            ->orderBy('source_name')
            ->pluck('source_name')
            ->all();

        if (!$this->selectedSource && !empty($sourceOptions)) {
            $this->selectedSource = $sourceOptions[0];
        }

        $recentLogsQuery = SqlsrvJobLogSnapshot::query()
            ->with('snapshot:id,collected_at,status')
            ->when($this->statusFilter !== '', fn ($query) => $query->where('has_error', $this->statusFilter === 'error'))
            ->orderByDesc('dt_run')
            ->orderByDesc('id');

        $recentLogs = $recentLogsQuery->paginate(15);

        return view('livewire.config.system.sqlsrv-health', [
            'activeTab' => $this->activeTab,
            'selectedSource' => $this->selectedSource,
            'lastSnapshot' => $lastSnapshot,
            'kpis' => $this->kpis($from, $lastSnapshot, $latestMetrics),
            'executiveSummary' => $this->executiveSummary($from, $latestMetrics),
            'pipelineSummary' => $this->pipelineSummary($latestMetrics),
            'expectedRuns' => $this->expectedRuns($latestMetrics),
            'usrJobSummary' => $this->usrJobSummary($from),
            'hostSummary' => $this->hostSummary($from),
            'latestMetrics' => $latestMetrics,
            'latestUsrMetrics' => $latestMetrics
                ->filter(fn ($metric) => str_starts_with($metric->source_name, 'tbld_usr_'))
                ->sortBy(fn ($metric) => $metric->last_update_at?->timestamp ?? 0)
                ->values(),
            'sourceOptions' => $sourceOptions,
            'recentLogs' => $recentLogs,
            'errorRanking' => $this->errorRanking($from),
            'chartPayload' => $this->chartPayload($snapshots, $latestMetrics),
            'processChartPayload' => $this->processChartPayload($latestMetrics),
            'sourceChartPayload' => $this->sourceChartPayload($from),
        ]);
    }

    protected function kpis($from, ?SqlsrvHealthSnapshot $lastSnapshot, $latestMetrics): array
    {
        $snapshots = SqlsrvHealthSnapshot::where('collected_at', '>=', $from);
        $totalSnapshots = (clone $snapshots)->count();
        $successSnapshots = (clone $snapshots)->where('status', 'success')->count();
        $errorsToday = SqlsrvJobLogSnapshot::whereDate('dt_run', today())->where('has_error', true)->count();
        $logsToday = SqlsrvJobLogSnapshot::whereDate('dt_run', today())->count();
        $staleSources = $latestMetrics
            ->filter(fn ($metric) => $metric->last_update_at && $metric->last_update_at->lt(now()->subHours(12)))
            ->count();

        return [
            'last_status' => $lastSnapshot?->status ?? 'sem coleta',
            'last_collected_at' => $lastSnapshot?->collected_at,
            'success_rate' => $totalSnapshots ? round(($successSnapshots / $totalSnapshots) * 100, 1) : 0,
            'errors_today' => $errorsToday,
            'logs_today' => $logsToday,
            'stale_sources' => $staleSources,
            'snapshots' => $totalSnapshots,
        ];
    }

    protected function errorRanking($from)
    {
        return SqlsrvJobLogSnapshot::query()
            ->selectRaw("COALESCE(file_name, file_folder, 'sem_nome') AS label, COUNT(*) AS total")
            ->where('created_at', '>=', $from)
            ->where('has_error', true)
            ->groupByRaw("COALESCE(file_name, file_folder, 'sem_nome')")
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    protected function executiveSummary($from, $latestMetrics): array
    {
        $usrLogs = $this->usrLogsQuery($from);
        $usrRuns = (clone $usrLogs)->count();
        $usrFailures = (clone $usrLogs)->where('has_error', true)->count();
        $lastSuccessLog = (clone $usrLogs)
            ->where('has_error', false)
            ->whereNotNull('dt_run')
            ->orderByDesc('dt_run')
            ->first();

        $latestUsrMetrics = $latestMetrics
            ->filter(fn ($metric) => str_starts_with($metric->source_name, 'tbld_usr_'))
            ->values();

        $lastSuccessMetric = $latestUsrMetrics
            ->filter(fn ($metric) => $metric->last_update_at)
            ->sortByDesc('last_update_at')
            ->first();

        $staleUsrSources = $latestUsrMetrics
            ->filter(fn ($metric) => $metric->last_update_at && $metric->last_update_at->lt(now()->subHours(12)))
            ->count();

        return [
            'runs' => $usrRuns,
            'successes' => max($usrRuns - $usrFailures, 0),
            'failures' => $usrFailures,
            'success_rate' => $usrRuns ? round((($usrRuns - $usrFailures) / $usrRuns) * 100, 1) : 0,
            'last_success_log' => $lastSuccessLog,
            'last_success_metric' => $lastSuccessMetric,
            'usr_sources' => $latestUsrMetrics->count(),
            'stale_sources' => $staleUsrSources,
        ];
    }

    protected function usrJobSummary($from)
    {
        return $this->usrLogsQuery($from)
            ->selectRaw("COALESCE(file_name, file_folder, 'sem_nome') AS label")
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN has_error = 1 THEN 1 ELSE 0 END) AS failures')
            ->selectRaw('MAX(dt_run) AS last_run')
            ->groupByRaw("COALESCE(file_name, file_folder, 'sem_nome')")
            ->orderByDesc('last_run')
            ->get();
    }

    protected function hostSummary($from)
    {
        return SqlsrvJobLogSnapshot::query()
            ->selectRaw("COALESCE(host, 'sem_host') AS host_name")
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN has_error = 1 THEN 1 ELSE 0 END) AS failures')
            ->selectRaw('MAX(dt_run) AS last_run')
            ->where('dt_run', '>=', $from)
            ->groupByRaw("COALESCE(host, 'sem_host')")
            ->orderByDesc('last_run')
            ->limit(10)
            ->get();
    }

    protected function usrLogsQuery($from)
    {
        return SqlsrvJobLogSnapshot::query()
            ->where('dt_run', '>=', $from)
            ->where(function ($query) {
                foreach ($this->usrWorkloadPatterns() as $pattern) {
                    $query->orWhere('file_name', 'like', $pattern)
                        ->orWhere('file_folder', 'like', $pattern);
                }
            });
    }

    protected function usrWorkloadPatterns(): array
    {
        return [
            '%usr%',
            '%nbase%',
            '%baseov%',
            '%baseep%',
            '%baseordens%',
            '%baseoperacoes%',
            '%basecustos%',
            '%based5%',
            '%basereclam%',
            '%basedd%',
        ];
    }

    protected function pipelineSummary($latestMetrics): array
    {
        $runs = collect($this->expectedRuns($latestMetrics));
        $dueRuns = $runs->where('is_due', true);

        return [
            'expected_today' => $runs->count(),
            'due' => $dueRuns->count(),
            'success' => $dueRuns->where('status', 'success')->count(),
            'failed' => $dueRuns->where('status', 'failed')->count(),
            'missing' => $dueRuns->where('status', 'missing')->count(),
            'usr_late' => $dueRuns->where('usr_status', 'late')->count(),
            'pending' => $runs->where('is_due', false)->count(),
        ];
    }

    protected function expectedRuns($latestMetrics): array
    {
        $now = now('America/Sao_Paulo');
        $today = $now->copy()->startOfDay();
        $graceMinutes = 45;
        $metricsBySource = $latestMetrics->keyBy('source_name');
        $logs = SqlsrvJobLogSnapshot::query()
            ->where('dt_run', '>=', $today->copy()->subHour())
            ->where('dt_run', '<=', $now)
            ->orderByDesc('dt_run')
            ->get();

        return collect($this->rpaSchedule())
            ->map(function (array $item) use ($today, $now, $graceMinutes, $metricsBySource, $logs) {
                $scheduledAt = Carbon::parse($today->format('Y-m-d') . ' ' . $item['time'], 'America/Sao_Paulo');
                $dueAt = $scheduledAt->copy()->addMinutes($graceMinutes);
                $isDue = $now->greaterThanOrEqualTo($dueAt);
                $log = $this->findRunLog($logs, $item['key'], $scheduledAt, $now);
                $metric = $metricsBySource->get($item['usr_table']);
                $status = 'pending';
                $delayMinutes = $log?->dt_run ? (int) $scheduledAt->diffInMinutes($log->dt_run, false) : null;
                $ranOnTime = $log?->dt_run ? $log->dt_run->lessThanOrEqualTo($dueAt) : false;

                if ($isDue && !$log) {
                    $status = 'missing';
                } elseif ($log?->has_error) {
                    $status = 'failed';
                } elseif ($log) {
                    $status = 'success';
                }

                $usrStatus = $this->usrStatusForRun($metric, $scheduledAt, $status);

                return [
                    'time' => $item['time'],
                    'file' => $item['file'],
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'usr_table' => $item['usr_table'],
                    'scheduled_at' => $scheduledAt,
                    'due_at' => $dueAt,
                    'is_due' => $isDue,
                    'status' => $status,
                    'status_label' => $this->runStatusLabel($status),
                    'ran_on_time' => $ranOnTime,
                    'delay_minutes' => $delayMinutes,
                    'usr_status' => $usrStatus,
                    'usr_status_label' => $this->usrStatusLabel($usrStatus),
                    'log' => $log,
                    'host' => $log?->host,
                    'last_run_at' => $log?->dt_run,
                    'metric' => $metric,
                    'last_usr_update_at' => $metric?->last_update_at,
                ];
            })
            ->values()
            ->all();
    }

    protected function findRunLog($logs, string $key, Carbon $scheduledAt, Carbon $now): ?SqlsrvJobLogSnapshot
    {
        $from = $scheduledAt->copy()->subMinutes(20);
        $to = $scheduledAt->copy()->addMinutes(90);

        if ($to->greaterThan($now)) {
            $to = $now;
        }

        return $logs->first(function (SqlsrvJobLogSnapshot $log) use ($key, $from, $to) {
            if (!$log->dt_run || $log->dt_run->lt($from) || $log->dt_run->gt($to)) {
                return false;
            }

            return $this->runKeyFromFile((string) $log->file_name) === $key;
        });
    }

    protected function runKeyFromFile(string $fileName): string
    {
        $fileName = strtolower($fileName);
        $fileName = preg_replace('/^run_rpa_sap_/', '', $fileName);
        $fileName = preg_replace('/^rpa_sap_/', '', $fileName);
        $fileName = preg_replace('/\.(bat|py)$/', '', $fileName);

        return (string) $fileName;
    }

    protected function usrStatusForRun(?SqlsrvSourceMetricSnapshot $metric, Carbon $scheduledAt, string $runStatus): string
    {
        if ($runStatus === 'pending') {
            return 'pending';
        }

        if (!$metric) {
            return 'unknown';
        }

        if (!$metric->last_update_at) {
            return 'no_date';
        }

        return $metric->last_update_at->toDateString() >= $scheduledAt->toDateString() ? 'updated' : 'late';
    }

    protected function runStatusLabel(string $status): string
    {
        return [
            'success' => 'Robô executou',
            'failed' => 'Robô falhou',
            'missing' => 'Não executou',
            'pending' => 'Aguardando horário',
        ][$status] ?? $status;
    }

    protected function usrStatusLabel(string $status): string
    {
        return [
            'updated' => 'Base _usr_ atualizada',
            'late' => 'Base _usr_ atrasada',
            'pending' => 'Aguardando execução',
            'unknown' => 'Sem métrica da base',
            'no_date' => 'Sem data de atualização',
        ][$status] ?? $status;
    }

    protected function rpaSchedule(): array
    {
        return [
            ['time' => '01:10', 'file' => 'run_rpa_sap_nbaseoperacoesresps.bat', 'key' => 'nbaseoperacoesresps', 'label' => 'Operações responsáveis', 'usr_table' => 'tbld_usr_baseOperacoesResps'],
            ['time' => '03:00', 'file' => 'run_rpa_sap_nbaseov.bat', 'key' => 'nbaseov', 'label' => 'Base OV', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '03:10', 'file' => 'run_rpa_sap_nbaseep.bat', 'key' => 'nbaseep', 'label' => 'Base EP', 'usr_table' => 'tbld_usr_baseEP'],
            ['time' => '03:30', 'file' => 'run_rpa_sap_nbased5.bat', 'key' => 'nbased5', 'label' => 'Base D5', 'usr_table' => 'tbld_usr_baseD5'],
            ['time' => '04:00', 'file' => 'run_rpa_sap_nbasecustosserv.bat', 'key' => 'nbasecustosserv', 'label' => 'Base Custos', 'usr_table' => 'tbld_usr_baseCustos'],
            ['time' => '06:00', 'file' => 'run_rpa_sap_nbasereclamacao.bat', 'key' => 'nbasereclamacao', 'label' => 'Base Reclamações', 'usr_table' => 'tbld_usr_baseReclamacoes'],
            ['time' => '07:10', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '09:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '09:10', 'file' => 'run_rpa_sap_nbaseoperacoes.bat', 'key' => 'nbaseoperacoes', 'label' => 'Base Operações', 'usr_table' => 'tbld_usr_baseOperacoes'],
            ['time' => '10:00', 'file' => 'run_rpa_sap_nbaseordens.bat', 'key' => 'nbaseordens', 'label' => 'Base Ordens', 'usr_table' => 'tbld_usr_baseOrdens'],
            ['time' => '10:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '11:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '12:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '12:30', 'file' => 'run_rpa_sap_nbasereclamacao.bat', 'key' => 'nbasereclamacao', 'label' => 'Base Reclamações', 'usr_table' => 'tbld_usr_baseReclamacoes'],
            ['time' => '13:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '13:10', 'file' => 'run_rpa_sap_nbaseoperacoesresps.bat', 'key' => 'nbaseoperacoesresps', 'label' => 'Operações responsáveis', 'usr_table' => 'tbld_usr_baseOperacoesResps'],
            ['time' => '14:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '15:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '15:10', 'file' => 'run_rpa_sap_nbaseep.bat', 'key' => 'nbaseep', 'label' => 'Base EP', 'usr_table' => 'tbld_usr_baseEP'],
            ['time' => '15:30', 'file' => 'run_rpa_sap_nbased5.bat', 'key' => 'nbased5', 'label' => 'Base D5', 'usr_table' => 'tbld_usr_baseD5'],
            ['time' => '15:30', 'file' => 'run_rpa_sap_nbasereclamacao.bat', 'key' => 'nbasereclamacao', 'label' => 'Base Reclamações', 'usr_table' => 'tbld_usr_baseReclamacoes'],
            ['time' => '16:00', 'file' => 'run_rpa_sap_nbaseoperacoes.bat', 'key' => 'nbaseoperacoes', 'label' => 'Base Operações', 'usr_table' => 'tbld_usr_baseOperacoes'],
            ['time' => '16:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '17:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '18:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '19:00', 'file' => 'run_rpa_sap_nbaseov_d0.bat', 'key' => 'nbaseov_d0', 'label' => 'Base OV D0', 'usr_table' => 'tbld_usr_baseOV'],
            ['time' => '20:00', 'file' => 'run_rpa_sap_nbasehistoperacoes.bat', 'key' => 'nbasehistoperacoes', 'label' => 'Histórico operações', 'usr_table' => 'tbld_usr_baseOperacoes'],
            ['time' => '20:30', 'file' => 'run_rpa_sap_nbasehistoperacoesresps.bat', 'key' => 'nbasehistoperacoesresps', 'label' => 'Histórico operações responsáveis', 'usr_table' => 'tbld_usr_baseOperacoesResps'],
            ['time' => '21:10', 'file' => 'run_rpa_sap_nbaseoperacoes.bat', 'key' => 'nbaseoperacoes', 'label' => 'Base Operações', 'usr_table' => 'tbld_usr_baseOperacoes'],
            ['time' => '22:00', 'file' => 'run_rpa_sap_nbaseordens.bat', 'key' => 'nbaseordens', 'label' => 'Base Ordens', 'usr_table' => 'tbld_usr_baseOrdens'],
        ];
    }

    protected function chartPayload($snapshots, $latestMetrics): array
    {
        return [
            'timeline' => [
                'labels' => $snapshots->map(fn ($snapshot) => $snapshot->collected_at?->format('d/m H:i'))->values(),
                'logs' => $snapshots->pluck('job_logs_count')->values(),
                'metrics' => $snapshots->pluck('metrics_count')->values(),
                'errors' => $snapshots->map(fn ($snapshot) => $snapshot->jobLogs()->where('has_error', true)->count())->values(),
            ],
            'sourceAge' => [
                'labels' => $latestMetrics->pluck('source_name')->values(),
                'hours' => $latestMetrics
                    ->map(fn ($metric) => $metric->last_update_at ? round($metric->last_update_at->diffInMinutes(now()) / 60, 1) : null)
                    ->values(),
            ],
        ];
    }

    protected function processChartPayload($latestMetrics): array
    {
        $runs = collect($this->expectedRuns($latestMetrics));
        $dueRuns = $runs->where('is_due', true);

        return [
            'status' => [
                'labels' => ['Executou', 'Falhou', 'Não executou', 'Aguardando'],
                'data' => [
                    $dueRuns->where('status', 'success')->count(),
                    $dueRuns->where('status', 'failed')->count(),
                    $dueRuns->where('status', 'missing')->count(),
                    $runs->where('is_due', false)->count(),
                ],
            ],
            'usr' => [
                'labels' => ['Atualizada', 'Atrasada', 'Sem data/métrica', 'Aguardando'],
                'data' => [
                    $dueRuns->where('usr_status', 'updated')->count(),
                    $dueRuns->where('usr_status', 'late')->count(),
                    $dueRuns->filter(fn ($run) => in_array($run['usr_status'], ['unknown', 'no_date'], true))->count(),
                    $runs->where('is_due', false)->count(),
                ],
            ],
        ];
    }

    protected function sourceChartPayload($from): array
    {
        if (!$this->selectedSource) {
            return ['labels' => [], 'rows' => []];
        }

        $rows = SqlsrvSourceMetricSnapshot::query()
            ->where('source_name', $this->selectedSource)
            ->where('created_at', '>=', $from)
            ->orderBy('created_at')
            ->limit(80)
            ->get();

        return [
            'labels' => $rows->map(fn ($metric) => $metric->created_at?->format('d/m H:i'))->values(),
            'rows' => $rows->pluck('row_count')->values(),
        ];
    }
}
