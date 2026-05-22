<?php

namespace App\Http\Livewire\Config\System;

use App\Models\ScheduleExecutionLog;
use App\Models\UpdateExecutionLog;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\Process\Process;
use Throwable;

class ScheduleMonitor extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public array $scheduledEvents = [];
    public array $runningCommands = [];
    public array $supervisor = [];
    public string $recentSearch = '';
    public $recentPerPage = 15;
    public ?string $restartMessage = null;
    public string $restartStatus = 'info';
    public ?string $forceMessage = null;
    public string $forceStatus = 'info';
    public ?string $stopMessage = null;
    public string $stopStatus = 'info';

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->syncRunningLogsWithSystem();

        $this->scheduledEvents = $this->buildScheduledEvents();
        $this->runningCommands = $this->buildRunningCommands();
        $this->supervisor = $this->detectSupervisorStatus();
    }

    public function refreshRunningCommands(): void
    {
        $this->syncRunningLogsWithSystem();
        $this->runningCommands = $this->buildRunningCommands();
    }

    public function updatedRecentSearch(): void
    {
        $this->resetPage('recentLogsPage');
    }

    public function updatedRecentPerPage(): void
    {
        $this->recentPerPage = max(10, min(100, (int) $this->recentPerPage));
        $this->resetPage('recentLogsPage');
    }

    public function restartScheduleSupervisor(): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        $program = $this->scheduleSupervisorProgram();

        if (!$program) {
            $this->restartStatus = 'danger';
            $this->restartMessage = 'Nao foi possivel identificar o programa do SupervisorD do schedule. Configure SCHEDULE_SUPERVISOR_PROGRAM no .env.';
            $this->refreshData();
            return;
        }

        Artisan::call('schedule:interrupt');

        $process = new Process(['supervisorctl', 'restart', $program]);
        $process->setTimeout(15);
        try {
            $process->run();
        } catch (Throwable $e) {
            $this->restartStatus = 'danger';
            $this->restartMessage = 'Falha ao executar supervisorctl: ' . $e->getMessage();
            $this->refreshData();
            return;
        }

        $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        if ($process->isSuccessful()) {
            $this->restartStatus = 'success';
            $this->restartMessage = "Restart enviado para {$program}. " . ($output ?: '');
        } else {
            $this->restartStatus = 'danger';
            $this->restartMessage = "Falha ao reiniciar {$program}. " . ($output ?: 'Sem retorno do supervisorctl.');
        }

        $this->refreshData();
    }

    public function forceScheduledEvent(string $eventHash): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        $event = collect($this->scheduleEvents())
            ->first(fn ($event) => $this->eventHash($event->expression, (string) $event->command) === $eventHash);

        if (!$event) {
            $this->forceStatus = 'danger';
            $this->forceMessage = 'Evento agendado nao encontrado.';
            $this->refreshData();
            return;
        }

        $displayName = $event->description ?: $this->labelForCommands(
            $this->extractArtisanCommands((string) $event->command),
            (string) $event->command
        );

        $command = implode(' ', [
            'nohup',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('artisan')),
            'schedule:force-run',
            escapeshellarg($eventHash),
            escapeshellarg($displayName),
            '> /dev/null 2>&1 & echo $!',
        ]);

        $process = Process::fromShellCommandline($command, base_path());
        $process->setTimeout(10);

        try {
            $process->run();
        } catch (Throwable $e) {
            $this->forceStatus = 'danger';
            $this->forceMessage = 'Falha ao iniciar execucao forçada: ' . $e->getMessage();
            $this->refreshData();
            return;
        }

        if (!$process->isSuccessful()) {
            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
            $this->forceStatus = 'danger';
            $this->forceMessage = 'Falha ao iniciar execucao forçada. ' . ($output ?: 'Sem retorno do processo.');
            $this->refreshData();
            return;
        }

        $pid = trim($process->getOutput());
        $this->forceStatus = 'success';
        $this->forceMessage = 'Execucao forçada iniciada para ' . $displayName . ($pid ? " (PID {$pid})." : '.');

        $this->refreshData();
    }

    public function stopRunningCommand(string $logId, string $pid): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        if (!Schema::hasTable('schedule_execution_logs')) {
            $this->stopStatus = 'danger';
            $this->stopMessage = 'Tabela de logs do schedule nao encontrada.';
            $this->refreshData();
            return;
        }

        $processId = (int) $pid;
        $log = ScheduleExecutionLog::query()
            ->whereKey((int) $logId)
            ->where('status', ScheduleExecutionLog::STATUS_RUNNING)
            ->whereNull('finished_at')
            ->first();

        if (!$log || $processId <= 0) {
            $this->stopStatus = 'danger';
            $this->stopMessage = 'Execucao em andamento nao encontrada.';
            $this->refreshData();
            return;
        }

        if (!$this->processStillMatchesLog($processId, $log)) {
            $this->stopStatus = 'danger';
            $this->stopMessage = "PID {$processId} nao corresponde mais a esta execucao.";
            $this->refreshData();
            return;
        }

        if (!$this->signalProcess($processId)) {
            $this->stopStatus = 'danger';
            $this->stopMessage = "Nao foi possivel enviar SIGTERM para o PID {$processId}.";
            $this->refreshData();
            return;
        }

        $finishedAt = now();

        $log->update([
            'status' => ScheduleExecutionLog::STATUS_FAIL,
            'finished_at' => $finishedAt,
            'stopped_at' => $finishedAt,
            'stop_signal' => 'TERM',
            'duration_seconds' => $log->started_at ? $log->started_at->diffInSeconds($finishedAt) : null,
            'exception_message' => 'Execucao interrompida manualmente pelo monitor do schedule.',
        ]);

        $this->stopStatus = 'warning';
        $this->stopMessage = "SIGTERM enviado para {$log->command_label} (PID {$processId}).";
        $this->refreshData();
    }

    public function stopDetectedProcess(string $pid): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        $processId = (int) $pid;

        if ($processId <= 0 || !$this->isStoppableScheduledProcess($processId)) {
            $this->stopStatus = 'danger';
            $this->stopMessage = "PID {$processId} nao corresponde a um comando agendado em execucao.";
            $this->refreshData();
            return;
        }

        if (!$this->signalProcess($processId)) {
            $this->stopStatus = 'danger';
            $this->stopMessage = "Nao foi possivel enviar SIGTERM para o PID {$processId}.";
            $this->refreshData();
            return;
        }

        $this->stopStatus = 'warning';
        $this->stopMessage = "SIGTERM enviado para o processo detectado (PID {$processId}).";
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.config.system.schedule-monitor', [
            'recentLogs' => $this->buildRecentLogs(),
        ]);
    }

    private function buildScheduledEvents(): array
    {
        $now = now();
        $latestLogs = $this->latestScheduleLogsByEventHash();
        $legacyLogs = $this->latestLogsByTask();

        return collect($this->scheduleEvents())
            ->map(function ($event, int $index) use ($now, $latestLogs, $legacyLogs) {
                $commands = $this->extractArtisanCommands((string) $event->command);
                $nextRun = Carbon::instance($event->nextRunDate($now, 0, true));
                $logName = $this->logNameFromOutput((string) $event->output);
                $eventHash = $this->eventHash($event->expression, (string) $event->command);
                $matchedLog = $latestLogs[$eventHash] ?? $this->matchLatestLog($legacyLogs, $commands, $logName);

                return [
                    'id' => sha1($event->expression . '|' . $event->command . '|' . $index),
                    'event_hash' => $eventHash,
                    'label' => $event->description ?: $this->labelForCommands($commands, (string) $event->command),
                    'command_label' => $this->labelForCommands($commands, (string) $event->command),
                    'commands' => $commands,
                    'expression' => $event->expression,
                    'next_run_at' => $nextRun->toDateTimeString(),
                    'next_run_iso' => $nextRun->toIso8601String(),
                    'next_time' => $nextRun->format('H:i'),
                    'next_date' => $nextRun->format('d/m/Y'),
                    'due_in' => $nextRun->diffForHumans($now, true),
                    'without_overlapping' => (bool) $event->withoutOverlapping,
                    'log_name' => $logName,
                    'output' => (string) $event->output,
                    'last_log' => $matchedLog,
                ];
            })
            ->filter(fn (array $event) => Carbon::parse($event['next_run_at'])->isSameDay($now))
            ->sortBy('next_run_at')
            ->values()
            ->all();
    }

    private function buildRunningCommands(): array
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return collect($this->legacyRunningCommands())
                ->merge($this->runningArtisanProcesses())
                ->values()
                ->all();
        }

        $scheduleLogs = ScheduleExecutionLog::query()
            ->where('status', ScheduleExecutionLog::STATUS_RUNNING)
            ->whereNull('finished_at')
            ->where('started_at', '>=', now()->subHours(12))
            ->orderBy('started_at')
            ->limit(50)
            ->get()
            ->toBase()
            ->map(function (ScheduleExecutionLog $row) {
                $process = $this->detectPidForScheduleLog($row);

                return [
                    'source' => 'schedule',
                    'id' => (string) $row->id,
                    'log_id' => (string) $row->id,
                    'pid' => $process['pid'],
                    'process_command' => $process['command'],
                    'command' => $row->command_label,
                    'command_detail' => $this->runningCommandDetail($row),
                    'started_at' => optional($row->started_at)->toDateTimeString(),
                    'elapsed' => $row->started_at ? $row->started_at->diffForHumans(null, true) : 'N/A',
                    'status' => $row->status,
                    'can_stop' => $process['pid'] !== null,
                ];
            });

        return $this->deduplicateRunningRows($scheduleLogs->merge($this->runningArtisanProcesses())->values()->all());
    }

    private function buildRecentLogs()
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return collect($this->legacyRecentLogs());
        }

        $search = trim($this->recentSearch);
        $query = ScheduleExecutionLog::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('command_label', 'like', "%{$search}%")
                        ->orWhere('command', 'like', "%{$search}%")
                        ->orWhere('process_command', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('expression', 'like', "%{$search}%")
                        ->orWhere('exception_message', 'like', "%{$search}%")
                        ->orWhere('skip_reason', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $query->orWhere('process_id', (int) $search)
                            ->orWhere('id', (int) $search)
                            ->orWhere('exit_code', (int) $search);
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query
            ->paginate($this->recentPerPage, ['*'], 'recentLogsPage')
            ->through(function (ScheduleExecutionLog $row) {
                return [
                    'id' => (string) $row->id,
                    'task' => $row->command_label,
                    'status' => $row->status,
                    'scheduled_at' => optional($row->scheduled_at)->toDateTimeString(),
                    'started_at' => optional($row->started_at)->toDateTimeString(),
                    'finished_at' => optional($row->finished_at)->toDateTimeString(),
                    'duration' => $this->durationLabel($row->started_at, $row->finished_at, $row->duration_seconds),
                    'exit_code' => $row->exit_code,
                    'process_id' => $row->process_id,
                    'expression' => $row->expression,
                    'errors' => $row->status === ScheduleExecutionLog::STATUS_FAIL ? 1 : 0,
                    'fail_reason' => $row->exception_message ?: $row->skip_reason,
                ];
            });
    }

    private function latestScheduleLogsByEventHash(): array
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return [];
        }

        return ScheduleExecutionLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->unique('event_hash')
            ->mapWithKeys(fn (ScheduleExecutionLog $row) => [
                (string) $row->event_hash => [
                    'task' => $row->command_label,
                    'status' => $row->status,
                    'started_at' => optional($row->started_at)->toDateTimeString(),
                    'finished_at' => optional($row->finished_at)->toDateTimeString(),
                    'duration' => $this->durationLabel($row->started_at, $row->finished_at, $row->duration_seconds),
                    'exit_code' => $row->exit_code,
                    'errors' => $row->status === ScheduleExecutionLog::STATUS_FAIL ? 1 : 0,
                ],
            ])
            ->all();
    }

    private function latestLogsByTask(): array
    {
        return UpdateExecutionLog::query()
            ->orderByDesc('date_inicio')
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->unique('task')
            ->mapWithKeys(fn (UpdateExecutionLog $row) => [
                strtolower((string) $row->task) => [
                    'task' => $row->task,
                    'status' => $row->status,
                    'started_at' => optional($row->date_inicio)->toDateTimeString(),
                    'finished_at' => optional($row->date_fim)->toDateTimeString(),
                    'duration' => $this->durationLabel($row->date_inicio, $row->date_fim),
                    'errors' => (int) $row->erros,
                ],
            ])
            ->all();
    }

    private function legacyRunningCommands(): array
    {
        return UpdateExecutionLog::query()
            ->where('status', UpdateExecutionLog::STATUS_RUNNING)
            ->whereNull('date_fim')
            ->where('date_inicio', '>=', now()->subHours(12))
            ->orderBy('date_inicio')
            ->limit(50)
            ->get()
            ->map(fn (UpdateExecutionLog $row) => [
                'source' => 'log legado',
                'id' => (string) $row->id,
                'command' => $this->normalizeRunningCommand($row->task),
                'started_at' => optional($row->date_inicio)->toDateTimeString(),
                'elapsed' => $row->date_inicio ? $row->date_inicio->diffForHumans(null, true) : 'N/A',
                'status' => $row->status,
                'log_id' => null,
                'pid' => null,
                'process_command' => null,
                'command_detail' => null,
                'can_stop' => false,
            ])
            ->all();
    }

    private function legacyRecentLogs(): array
    {
        return UpdateExecutionLog::query()
            ->orderByDesc('date_inicio')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->map(function (UpdateExecutionLog $row) {
                return [
                    'id' => (string) $row->id,
                    'task' => $row->task,
                    'status' => $row->status,
                    'scheduled_at' => null,
                    'started_at' => optional($row->date_inicio)->toDateTimeString(),
                    'finished_at' => optional($row->date_fim)->toDateTimeString(),
                    'duration' => $this->durationLabel($row->date_inicio, $row->date_fim),
                    'exit_code' => null,
                    'process_id' => null,
                    'expression' => null,
                    'errors' => (int) $row->erros,
                    'fail_reason' => $row->fail_reason,
                ];
            })
            ->all();
    }

    private function matchLatestLog(array $latestLogs, array $commands, ?string $logName): ?array
    {
        foreach ($this->logCandidates($commands, $logName) as $candidate) {
            $key = strtolower($candidate);
            if (isset($latestLogs[$key])) {
                return $latestLogs[$key];
            }
        }

        return null;
    }

    private function logCandidates(array $commands, ?string $logName): array
    {
        $candidates = [];

        if ($logName) {
            $candidates[] = str_replace('-', '_', $logName);
            $candidates[] = $logName;
        }

        foreach ($commands as $command) {
            $base = trim(preg_replace('/\s+.*/', '', $command));
            $withoutPrefix = preg_replace('/^[^:]+:/', '', $base);

            foreach ([$base, $withoutPrefix] as $value) {
                $candidates[] = $value;
                $candidates[] = str_replace([':', '-'], '_', $value);
                $candidates[] = strtolower(str_replace([':', '-'], '_', $value));
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function extractArtisanCommands(string $rawCommand): array
    {
        return collect(preg_split('/\s+&&\s+/', $rawCommand) ?: [])
            ->map(fn ($part) => $this->cleanArtisanCommand($part))
            ->filter()
            ->values()
            ->all();
    }

    private function cleanArtisanCommand(string $command): string
    {
        $command = str_replace(["'", '"'], '', trim($command));
        $command = str_replace(base_path('artisan'), 'artisan', $command);
        $command = preg_replace('/^.*?\bartisan\s+/', '', $command) ?? $command;
        $command = preg_replace('/\s+(>>|>|2>|2>&1).*/', '', $command) ?? $command;

        return trim($command);
    }

    private function labelForCommands(array $commands, string $rawCommand): string
    {
        if (count($commands) === 0) {
            return $this->cleanArtisanCommand($rawCommand) ?: 'schedule';
        }

        if (count($commands) === 1) {
            return $commands[0];
        }

        return $commands[0] . ' +' . (count($commands) - 1);
    }

    private function logNameFromOutput(string $output): ?string
    {
        if ($output === '' || $output === '/dev/null') {
            return null;
        }

        return pathinfo($output, PATHINFO_FILENAME) ?: null;
    }

    private function eventHash(string $expression, string $command): string
    {
        return sha1($expression . '|' . $command);
    }

    private function runningArtisanProcesses(): array
    {
        $scheduledCommands = $this->scheduledCommandNames();

        return collect($this->artisanProcessLines())
            ->filter()
            ->map(function (string $line) use ($scheduledCommands) {
                [$pid, $command] = array_pad(explode(' ', $line, 2), 2, '');

                if (str_contains($command, 'schedule:work')) {
                    return null;
                }

                if ($this->isShellWrapperWithArtisanChild((int) $pid, $command)) {
                    return null;
                }

                $clean = $this->cleanArtisanCommand($command);
                $normalized = $this->normalizeRunningCommand($clean);

                if (
                    $normalized === ''
                    || str_starts_with($normalized, 'schedule:')
                    || str_starts_with($normalized, 'queue:')
                    || !in_array($normalized, $scheduledCommands, true)
                ) {
                    return null;
                }

                return [
                    'source' => 'processo',
                    'id' => $pid,
                    'log_id' => null,
                    'pid' => (int) $pid,
                    'process_command' => $command,
                    'command' => $normalized,
                    'command_detail' => $this->cleanArtisanCommand($command),
                    'started_at' => null,
                    'elapsed' => 'em execucao',
                    'status' => 'RUNNING',
                    'can_stop' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function isShellWrapperWithArtisanChild(int $pid, string $command): bool
    {
        if ($pid <= 0 || !preg_match('/\b(sh|bash)\s+-c\b/', $command)) {
            return false;
        }

        foreach ($this->childProcessCommandLines($pid) as $childCommand) {
            if (str_contains($childCommand, 'artisan')) {
                return true;
            }
        }

        return false;
    }

    private function runningUniqueKey(array $row): string
    {
        $pid = (int) ($row['pid'] ?? 0);

        if ($pid > 0) {
            return 'pid:' . $pid;
        }

        return 'cmd:' . $this->normalizeRunningCommand((string) ($row['command_detail'] ?? $row['command'] ?? ''));
    }

    private function deduplicateRunningRows(array $rows): array
    {
        $selected = [];
        $aliases = [];

        foreach ($rows as $row) {
            $keys = $this->runningEquivalentKeys($row);
            $existingKey = collect($keys)->first(fn (string $key) => isset($aliases[$key]));

            if ($existingKey === null) {
                $canonicalKey = $keys[0];
                $selected[$canonicalKey] = $row;
                foreach ($keys as $key) {
                    $aliases[$key] = $canonicalKey;
                }
                continue;
            }

            $canonicalKey = $aliases[$existingKey];
            $selected[$canonicalKey] = $this->preferRunningRow($selected[$canonicalKey], $row);
            foreach ($keys as $key) {
                $aliases[$key] = $canonicalKey;
            }
        }

        return array_values($selected);
    }

    private function runningEquivalentKeys(array $row): array
    {
        $keys = [$this->runningUniqueKey($row)];
        $pid = (int) ($row['pid'] ?? 0);

        if ($pid > 0) {
            foreach ($this->relatedProcessIds($pid) as $relatedPid) {
                $keys[] = 'pid:' . $relatedPid;
            }
        }

        $commandKey = $this->normalizeRunningCommand((string) ($row['command_detail'] ?? $row['process_command'] ?? $row['command'] ?? ''));
        if ($commandKey !== '') {
            $keys[] = 'cmd:' . $commandKey;
        }

        return array_values(array_unique($keys));
    }

    private function preferRunningRow(array $current, array $candidate): array
    {
        if (($candidate['source'] ?? null) === 'schedule' && ($current['source'] ?? null) !== 'schedule') {
            return $candidate;
        }

        return $current;
    }

    private function relatedProcessIds(int $pid): array
    {
        $related = [];
        $parentPid = $this->parentProcessId($pid);

        if ($parentPid !== null) {
            $related[] = $parentPid;
        }

        return array_merge($related, array_keys($this->childProcessCommandLines($pid)));
    }

    private function parentProcessId(int $pid): ?int
    {
        $process = new Process(['ps', '-p', (string) $pid, '-o', 'ppid=']);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        $parentPid = (int) trim($process->getOutput());

        return $process->isSuccessful() && $parentPid > 0 ? $parentPid : null;
    }

    private function childProcessCommandLines(int $pid): array
    {
        $process = new Process(['ps', '--ppid', (string) $pid, '-o', 'pid=,args=']);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return [];
        }

        return collect(explode("\n", trim($process->getOutput())))
            ->mapWithKeys(function (string $line) {
                [$pid, $command] = array_pad(explode(' ', trim($line), 2), 2, '');

                return ((int) $pid > 0) ? [(int) $pid => $command] : [];
            })
            ->all();
    }

    private function runningCommandDetail(ScheduleExecutionLog $log): string
    {
        $commands = $this->extractArtisanCommands((string) $log->command);

        if (count($commands) > 0) {
            return implode(' && ', $commands);
        }

        return $this->cleanArtisanCommand((string) $log->process_command);
    }

    private function detectPidForScheduleLog(ScheduleExecutionLog $log): array
    {
        $storedPid = (int) ($log->process_id ?? 0);
        if ($storedPid > 0 && $this->processStillMatchesLog($storedPid, $log)) {
            return [
                'pid' => $storedPid,
                'command' => $this->processCommandLine($storedPid) ?: $log->process_command,
            ];
        }

        foreach ($this->artisanProcessLines() as $line) {
            [$pid, $command] = array_pad(explode(' ', $line, 2), 2, '');
            $processId = (int) $pid;

            if ($processId <= 0 || !$this->commandLineMatchesLog($command, $log)) {
                continue;
            }

            if (Schema::hasColumn('schedule_execution_logs', 'process_id')) {
                $log->update([
                    'process_id' => $processId,
                    'process_command' => $command,
                ]);
            }

            return [
                'pid' => $processId,
                'command' => $command,
            ];
        }

        return [
            'pid' => null,
            'command' => $log->process_command,
        ];
    }

    private function syncRunningLogsWithSystem(): void
    {
        if (
            !Schema::hasTable('schedule_execution_logs')
            || !Schema::hasColumn('schedule_execution_logs', 'process_id')
        ) {
            return;
        }

        ScheduleExecutionLog::query()
            ->where('status', ScheduleExecutionLog::STATUS_RUNNING)
            ->whereNull('finished_at')
            ->where('started_at', '>=', now()->subHours(12))
            ->orderBy('started_at')
            ->limit(80)
            ->get()
            ->each(function (ScheduleExecutionLog $log) {
                $pid = (int) ($log->process_id ?? 0);

                if ($pid <= 0) {
                    $process = $this->detectPidForScheduleLog($log);
                    $pid = (int) ($process['pid'] ?? 0);

                    if ($pid <= 0) {
                        return;
                    }
                }

                if ($this->processStillMatchesLog($pid, $log)) {
                    return;
                }

                if ($this->forceRunWrapperIsStillRunning($log)) {
                    return;
                }

                $finishedAt = now();

                $log->update([
                    'status' => ScheduleExecutionLog::STATUS_FAIL,
                    'finished_at' => $finishedAt,
                    'duration_seconds' => $log->started_at ? $log->started_at->diffInSeconds($finishedAt) : null,
                    'exception_message' => "Processo PID {$pid} nao esta mais em execucao no sistema.",
                ]);
            });
    }

    private function forceRunWrapperIsStillRunning(ScheduleExecutionLog $log): bool
    {
        foreach ($this->artisanProcessLines() as $line) {
            if (
                str_contains($line, 'schedule:force-run')
                && str_contains($line, (string) $log->event_hash)
            ) {
                return true;
            }
        }

        return false;
    }

    private function processStillMatchesLog(int $pid, ScheduleExecutionLog $log): bool
    {
        $command = $this->processCommandLine($pid);

        return $command !== null && $this->commandLineMatchesLog($command, $log);
    }

    private function isStoppableScheduledProcess(int $pid): bool
    {
        $command = $this->processCommandLine($pid);

        if ($command === null || str_contains($command, 'schedule:work')) {
            return false;
        }

        $normalized = $this->normalizeRunningCommand($this->cleanArtisanCommand($command));

        return $normalized !== ''
            && !str_starts_with($normalized, 'schedule:')
            && !str_starts_with($normalized, 'queue:')
            && in_array($normalized, $this->scheduledCommandNames(), true);
    }

    private function commandLineMatchesLog(string $command, ScheduleExecutionLog $log): bool
    {
        if ($command === '' || str_contains($command, 'schedule:work')) {
            return false;
        }

        if (str_contains($command, 'schedule:force-run') && str_contains($command, (string) $log->event_hash)) {
            return true;
        }

        $runningCommand = $this->normalizeRunningCommand($this->cleanArtisanCommand($command));
        foreach ($this->extractArtisanCommands((string) $log->command) as $logCommand) {
            if ($runningCommand === $this->normalizeRunningCommand($logCommand)) {
                return true;
            }
        }

        return false;
    }

    private function processCommandLine(int $pid): ?string
    {
        $process = new Process(['ps', '-p', (string) $pid, '-o', 'args=']);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        $command = trim($process->getOutput());

        return $process->isSuccessful() && $command !== '' ? $command : null;
    }

    private function signalProcess(int $pid): bool
    {
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, SIGTERM);
        }

        $process = new Process(['kill', '-TERM', (string) $pid]);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return false;
        }

        return $process->isSuccessful();
    }

    private function artisanProcessLines(): array
    {
        $process = new Process(['pgrep', '-af', 'artisan']);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return [];
        }

        if (!$process->isSuccessful() && trim($process->getOutput()) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", trim($process->getOutput())))));
    }

    private function scheduledCommandNames(): array
    {
        return collect($this->scheduleEvents())
            ->flatMap(fn ($event) => $this->extractArtisanCommands((string) $event->command))
            ->map(fn ($command) => $this->normalizeRunningCommand($command))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function scheduleEvents(): array
    {
        app(ConsoleKernel::class)->bootstrap();
        app()->forgetInstance(Schedule::class);

        return app(Schedule::class)->events();
    }

    private function normalizeRunningCommand(string $command): string
    {
        $command = $this->cleanArtisanCommand($command);
        $command = preg_replace('/\s+.*/', '', $command) ?? $command;

        return trim($command);
    }

    private function detectSupervisorStatus(): array
    {
        $program = $this->scheduleSupervisorProgram(false);
        $processes = $this->supervisorStatusLines();
        $scheduleWork = $this->isScheduleWorkRunning();

        $matched = collect($processes)
            ->filter(fn ($line) => $this->lineMatchesScheduleProgram($line, $program))
            ->values()
            ->all();

        return [
            'program' => $program ?: 'nao configurado',
            'active' => collect($matched)->contains(fn ($line) => preg_match('/\bRUNNING\b/i', $line)) || $scheduleWork,
            'source' => count($matched) ? 'supervisorctl' : ($scheduleWork ? 'pgrep' : 'indisponivel'),
            'lines' => $matched,
            'schedule_work' => $scheduleWork,
        ];
    }

    private function scheduleSupervisorProgram(bool $detect = true): ?string
    {
        $configured = trim((string) env('SCHEDULE_SUPERVISOR_PROGRAM', ''));
        if ($configured !== '') {
            return $configured;
        }

        if (!$detect) {
            return null;
        }

        foreach ($this->supervisorStatusLines() as $line) {
            if (preg_match('/schedule/i', $line)) {
                return trim(strtok($line, " \t"));
            }
        }

        return null;
    }

    private function supervisorStatusLines(): array
    {
        $process = new Process(['supervisorctl', 'status']);
        $process->setTimeout(5);
        try {
            $process->run();
        } catch (Throwable) {
            return [];
        }

        if (!$process->isSuccessful()) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $process->getOutput()))));
    }

    private function lineMatchesScheduleProgram(string $line, ?string $program): bool
    {
        if (!$program) {
            return preg_match('/schedule/i', $line) === 1;
        }

        $name = trim(strtok($line, " \t"));
        $pattern = '/^' . str_replace('\*', '.*', preg_quote($program, '/')) . '$/i';

        return preg_match($pattern, $name) === 1 || preg_match('/schedule/i', $line) === 1;
    }

    private function isScheduleWorkRunning(): bool
    {
        $process = new Process(['pgrep', '-af', 'artisan.*schedule:work']);
        $process->setTimeout(5);
        try {
            $process->run();
        } catch (Throwable) {
            return false;
        }

        return trim($process->getOutput()) !== '';
    }

    private function durationLabel($start, $end, mixed $storedSeconds = null): string
    {
        if ($storedSeconds !== null) {
            $seconds = (float) $storedSeconds;

            if ($seconds < 60) {
                return rtrim(rtrim(number_format($seconds, 2, '.', ''), '0'), '.') . 's';
            }

            if ($seconds < 3600) {
                return intdiv((int) $seconds, 60) . 'min';
            }

            return intdiv((int) $seconds, 3600) . 'h ' . intdiv(((int) $seconds) % 3600, 60) . 'min';
        }

        if (!$start) {
            return 'N/A';
        }

        $end = $end ?: now();
        $seconds = Carbon::parse($start)->diffInSeconds(Carbon::parse($end));

        if ($seconds < 60) {
            return $seconds . 's';
        }

        if ($seconds < 3600) {
            return intdiv($seconds, 60) . 'min';
        }

        return intdiv($seconds, 3600) . 'h ' . intdiv($seconds % 3600, 60) . 'min';
    }
}
