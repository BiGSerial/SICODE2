<?php

namespace App\Listeners;

use App\Models\ScheduleExecutionLog;
use Carbon\Carbon;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LogScheduledTask
{
    public function handle(object $event): void
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return;
        }

        try {
            match (true) {
                $event instanceof ScheduledTaskStarting => $this->markStarting($event->task),
                $event instanceof ScheduledTaskFinished => $this->markFinished($event->task, (float) $event->runtime),
                $event instanceof ScheduledBackgroundTaskFinished => $this->markFinished($event->task),
                $event instanceof ScheduledTaskFailed => $this->markFailed($event->task, $event->exception),
                $event instanceof ScheduledTaskSkipped => $this->markSkipped($event->task),
                default => null,
            };
        } catch (Throwable) {
            // O monitoramento do Scheduler nunca deve quebrar o schedule:run.
        }
    }

    private function markStarting(Event $task): void
    {
        ScheduleExecutionLog::query()
            ->where('event_hash', $this->eventHash($task))
            ->where('status', ScheduleExecutionLog::STATUS_RUNNING)
            ->whereNull('finished_at')
            ->where('started_at', '<', now()->subHours(12))
            ->update([
                'status' => ScheduleExecutionLog::STATUS_FAIL,
                'finished_at' => now(),
                'exception_message' => 'Execucao anterior ficou em aberto por mais de 12 horas.',
            ]);

        ScheduleExecutionLog::create(array_merge($this->basePayload($task), [
            'status' => ScheduleExecutionLog::STATUS_RUNNING,
            'started_at' => now(),
        ]));
    }

    private function markFinished(Event $task, ?float $runtime = null): void
    {
        $log = $this->latestRunningLog($task);
        $finishedAt = now();

        if (!$log) {
            ScheduleExecutionLog::create(array_merge($this->basePayload($task), [
                'status' => ScheduleExecutionLog::STATUS_DONE,
                'started_at' => $finishedAt,
                'finished_at' => $finishedAt,
                'duration_seconds' => $runtime,
                'exit_code' => $task->exitCode,
            ]));
            return;
        }

        $duration = $runtime ?? ($log->started_at ? $log->started_at->diffInSeconds($finishedAt) : null);

        if ($log->stopped_at) {
            $log->update([
                'status' => ScheduleExecutionLog::STATUS_FAIL,
                'finished_at' => $finishedAt,
                'duration_seconds' => $duration,
                'exit_code' => $task->exitCode,
                'exception_message' => $log->exception_message ?: 'Execucao interrompida manualmente pelo monitor do schedule.',
            ]);
            return;
        }

        $log->update([
            'status' => ((int) $task->exitCode === 0) ? ScheduleExecutionLog::STATUS_DONE : ScheduleExecutionLog::STATUS_FAIL,
            'finished_at' => $finishedAt,
            'duration_seconds' => $duration,
            'exit_code' => $task->exitCode,
            'exception_message' => ((int) $task->exitCode === 0) ? null : 'Comando terminou com exit code ' . $task->exitCode . '.',
        ]);
    }

    private function markFailed(Event $task, Throwable $exception): void
    {
        $log = $this->latestRunningLog($task);
        $finishedAt = now();

        if (!$log) {
            ScheduleExecutionLog::create(array_merge($this->basePayload($task), [
                'status' => ScheduleExecutionLog::STATUS_FAIL,
                'started_at' => $finishedAt,
                'finished_at' => $finishedAt,
                'exit_code' => $task->exitCode,
                'exception_message' => $this->limitText($exception->getMessage()),
            ]));
            return;
        }

        if ($log->stopped_at) {
            $log->update([
                'status' => ScheduleExecutionLog::STATUS_FAIL,
                'finished_at' => $finishedAt,
                'duration_seconds' => $log->started_at ? $log->started_at->diffInSeconds($finishedAt) : null,
                'exit_code' => $task->exitCode,
                'exception_message' => $log->exception_message ?: 'Execucao interrompida manualmente pelo monitor do schedule.',
            ]);
            return;
        }

        $log->update([
            'status' => ScheduleExecutionLog::STATUS_FAIL,
            'finished_at' => $finishedAt,
            'duration_seconds' => $log->started_at ? $log->started_at->diffInSeconds($finishedAt) : null,
            'exit_code' => $task->exitCode,
            'exception_message' => $this->limitText($exception->getMessage()),
        ]);
    }

    private function markSkipped(Event $task): void
    {
        ScheduleExecutionLog::create(array_merge($this->basePayload($task), [
            'status' => ScheduleExecutionLog::STATUS_SKIPPED,
            'scheduled_at' => now()->startOfMinute(),
            'finished_at' => now(),
            'skip_reason' => $task->withoutOverlapping
                ? 'Filtros do Scheduler nao passaram ou havia execucao sobreposta.'
                : 'Filtros do Scheduler nao passaram.',
        ]));
    }

    private function latestRunningLog(Event $task): ?ScheduleExecutionLog
    {
        $query = ScheduleExecutionLog::query()
            ->where('event_hash', $this->eventHash($task));

        if (Schema::hasColumn('schedule_execution_logs', 'stopped_at')) {
            $query->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('status', ScheduleExecutionLog::STATUS_RUNNING)
                        ->whereNull('finished_at');
                })->orWhere(function ($query) {
                    $query->whereNotNull('stopped_at')
                        ->where('started_at', '>=', now()->subHours(12));
                })->orWhere(function ($query) {
                    $query->where('status', ScheduleExecutionLog::STATUS_FAIL)
                        ->where('exception_message', 'like', 'Processo PID % nao esta mais em execucao no sistema.')
                        ->where('started_at', '>=', now()->subHours(12));
                });
            });
        } else {
            $query->where('status', ScheduleExecutionLog::STATUS_RUNNING)
                ->whereNull('finished_at');
        }

        return $query
            ->orderByDesc('started_at')
            ->first();
    }

    private function basePayload(Event $task): array
    {
        $command = (string) $task->command;

        return [
            'event_hash' => $this->eventHash($task),
            'command_label' => $task->description ?: $this->labelForCommand($command),
            'command' => $command,
            'expression' => $task->expression,
            'scheduled_at' => now()->startOfMinute(),
            'output_path' => $task->output !== '/dev/null' ? (string) $task->output : null,
            'without_overlapping' => (bool) $task->withoutOverlapping,
            'run_in_background' => (bool) $task->runInBackground,
        ];
    }

    private function eventHash(Event $task): string
    {
        return sha1($task->expression . '|' . $task->command);
    }

    private function labelForCommand(string $command): string
    {
        $parts = collect(preg_split('/\s+&&\s+/', $command) ?: [])
            ->map(fn ($part) => $this->cleanArtisanCommand($part))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return $this->limitText($this->cleanArtisanCommand($command), 255) ?: 'schedule';
        }

        if ($parts->count() === 1) {
            return $this->limitText($parts->first(), 255);
        }

        return $this->limitText($parts->first() . ' +' . ($parts->count() - 1), 255);
    }

    private function cleanArtisanCommand(string $command): string
    {
        $command = str_replace(["'", '"'], '', trim($command));
        $command = str_replace(base_path('artisan'), 'artisan', $command);
        $command = preg_replace('/^.*?\bartisan\s+/', '', $command) ?? $command;

        return trim($command);
    }

    private function limitText(?string $value, int $limit = 1000): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }
}
