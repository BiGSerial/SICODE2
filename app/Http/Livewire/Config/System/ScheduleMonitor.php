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
use Symfony\Component\Process\Process;
use Throwable;

class ScheduleMonitor extends Component
{
    public array $scheduledEvents = [];
    public array $runningCommands = [];
    public array $recentLogs = [];
    public array $supervisor = [];
    public ?string $restartMessage = null;
    public string $restartStatus = 'info';
    public ?string $forceMessage = null;
    public string $forceStatus = 'info';

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->scheduledEvents = $this->buildScheduledEvents();
        $this->runningCommands = $this->buildRunningCommands();
        $this->recentLogs = $this->buildRecentLogs();
        $this->supervisor = $this->detectSupervisorStatus();
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

        $command = implode(' ', [
            'nohup',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('artisan')),
            'schedule:force-run',
            escapeshellarg($eventHash),
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
        $this->forceMessage = 'Execucao forçada iniciada para ' . ($event->description ?: $this->labelForCommands(
            $this->extractArtisanCommands((string) $event->command),
            (string) $event->command
        )) . ($pid ? " (PID {$pid})." : '.');

        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.config.system.schedule-monitor');
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
            ->map(fn (ScheduleExecutionLog $row) => [
                'source' => 'schedule',
                'id' => (string) $row->id,
                'command' => $this->normalizeRunningCommand($row->command_label),
                'started_at' => optional($row->started_at)->toDateTimeString(),
                'elapsed' => $row->started_at ? $row->started_at->diffForHumans(null, true) : 'N/A',
                'status' => $row->status,
            ]);

        return $scheduleLogs->merge($this->runningArtisanProcesses())
            ->unique(fn ($row) => $this->normalizeRunningCommand((string) ($row['command'] ?? '')))
            ->values()
            ->all();
    }

    private function buildRecentLogs(): array
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return $this->legacyRecentLogs();
        }

        return ScheduleExecutionLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->map(function (ScheduleExecutionLog $row) {
                return [
                    'id' => (string) $row->id,
                    'task' => $row->command_label,
                    'status' => $row->status,
                    'scheduled_at' => optional($row->scheduled_at)->toDateTimeString(),
                    'started_at' => optional($row->started_at)->toDateTimeString(),
                    'finished_at' => optional($row->finished_at)->toDateTimeString(),
                    'duration' => $this->durationLabel($row->started_at, $row->finished_at, $row->duration_seconds),
                    'exit_code' => $row->exit_code,
                    'expression' => $row->expression,
                    'errors' => $row->status === ScheduleExecutionLog::STATUS_FAIL ? 1 : 0,
                    'fail_reason' => $row->exception_message ?: $row->skip_reason,
                ];
            })
            ->all();
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

        return collect(explode("\n", trim($process->getOutput())))
            ->filter()
            ->map(function (string $line) use ($scheduledCommands) {
                [$pid, $command] = array_pad(explode(' ', $line, 2), 2, '');

                if (str_contains($command, 'schedule:work')) {
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
                    'command' => $normalized,
                    'started_at' => null,
                    'elapsed' => 'em execucao',
                    'status' => 'RUNNING',
                ];
            })
            ->filter()
            ->values()
            ->all();
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
