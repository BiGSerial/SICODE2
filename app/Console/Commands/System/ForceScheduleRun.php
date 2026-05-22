<?php

namespace App\Console\Commands\System;

use App\Models\ScheduleExecutionLog;
use Illuminate\Console\Command;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Throwable;

class ForceScheduleRun extends Command
{
    protected $signature = 'schedule:force-run {eventHash} {displayName?}';

    protected $description = 'Executa manualmente um evento do Laravel Scheduler pelo hash usado no monitor.';

    public function handle(Schedule $schedule, ExceptionHandler $handler): int
    {
        $eventHash = (string) $this->argument('eventHash');

        $event = collect($schedule->events())
            ->first(fn ($event) => sha1($event->expression . '|' . $event->command) === $eventHash);

        if (!$event) {
            $this->error('Evento agendado nao encontrado.');
            return self::FAILURE;
        }

        $displayName = trim((string) $this->argument('displayName'));
        if ($displayName !== '') {
            $event->name($displayName);
        }

        Event::dispatch(new ScheduledTaskStarting($event));

        $start = microtime(true);

        try {
            $exitCode = $this->runEventCommand($event, $eventHash, $displayName);
            $event->exitCode = $exitCode;

            Event::dispatch(new ScheduledTaskFinished(
                $event,
                round(microtime(true) - $start, 2)
            ));

            return ((int) $event->exitCode === 0) ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            Event::dispatch(new ScheduledTaskFailed($event, $e));
            $handler->report($e);

            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function runEventCommand($event, string $eventHash, string $displayName): int
    {
        $event->callBeforeCallbacks($this->laravel);

        $process = Process::fromShellCommandline((string) $event->command, base_path());
        $process->setTimeout(null);
        $process->start(function (string $type, string $line) use ($event) {
            $this->writeScheduledOutput($event, $line);
        });

        $this->registerProcess($eventHash, $process->getPid(), (string) $event->command, $displayName);
        $this->registerProcess($eventHash, $this->runningChildProcessId($process->getPid(), (string) $event->command), (string) $event->command, $displayName);

        $exitCode = $process->wait(function (string $type, string $line) use ($event) {
            $this->writeScheduledOutput($event, $line);
        });

        $event->callAfterCallbacks($this->laravel);

        return (int) $exitCode;
    }

    private function runningChildProcessId(?int $parentPid, string $expectedCommand): ?int
    {
        if (!$parentPid) {
            return null;
        }

        $deadline = microtime(true) + 2;

        do {
            $process = new Process(['ps', '--ppid', (string) $parentPid, '-o', 'pid=,args=']);
            $process->setTimeout(1);
            $process->run();

            foreach (array_filter(explode("\n", trim($process->getOutput()))) as $line) {
                [$pid, $command] = array_pad(explode(' ', trim($line), 2), 2, '');

                if ((int) $pid > 0 && $this->commandsLookRelated($command, $expectedCommand)) {
                    return (int) $pid;
                }
            }

            usleep(100000);
        } while (microtime(true) < $deadline);

        return $parentPid;
    }

    private function commandsLookRelated(string $runningCommand, string $expectedCommand): bool
    {
        $runningCommand = str_replace(["'", '"'], '', $runningCommand);
        $expectedCommand = str_replace(["'", '"'], '', $expectedCommand);

        if ($runningCommand === '' || $expectedCommand === '') {
            return false;
        }

        foreach (preg_split('/\s+&&\s+/', $expectedCommand) ?: [] as $part) {
            $part = trim(preg_replace('/^.*?\bartisan\s+/', '', $part) ?? $part);
            $base = trim(preg_replace('/\s+.*/', '', $part) ?? $part);

            if ($base !== '' && str_contains($runningCommand, $base)) {
                return true;
            }
        }

        return str_contains($runningCommand, 'artisan') && $this->normalizedArtisanName($runningCommand) === $this->normalizedArtisanName($expectedCommand);
    }

    private function normalizedArtisanName(string $command): string
    {
        $command = str_replace(["'", '"'], '', trim($command));
        $command = preg_replace('/^.*?\bartisan\s+/', '', $command) ?? $command;
        $command = preg_replace('/\s+.*/', '', $command) ?? $command;

        return trim($command);
    }

    private function writeScheduledOutput($event, string $line): void
    {
        $output = (string) $event->output;

        if ($output === '' || $output === '/dev/null') {
            return;
        }

        file_put_contents($output, $line, FILE_APPEND | LOCK_EX);
    }

    private function registerProcess(string $eventHash, ?int $processId, string $processCommand, string $displayName): void
    {
        if (!Schema::hasTable('schedule_execution_logs') || !Schema::hasColumn('schedule_execution_logs', 'process_id')) {
            return;
        }

        if (!$processId) {
            $processId = getmypid();
            $processCommand = implode(' ', $_SERVER['argv'] ?? []);
        }

        $log = ScheduleExecutionLog::query()
            ->where('event_hash', $eventHash)
            ->where('status', ScheduleExecutionLog::STATUS_RUNNING)
            ->whereNull('finished_at')
            ->orderByDesc('started_at')
            ->first();

        $payload = [
            'process_id' => $processId,
            'process_command' => $processCommand,
        ];

        if ($displayName !== '') {
            $payload['command_label'] = $displayName;
        }

        $log?->update($payload);
    }
}
