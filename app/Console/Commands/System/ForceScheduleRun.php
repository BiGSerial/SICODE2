<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Throwable;

class ForceScheduleRun extends Command
{
    protected $signature = 'schedule:force-run {eventHash}';

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

        Event::dispatch(new ScheduledTaskStarting($event));

        $start = microtime(true);

        try {
            $event->run($this->laravel);

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
}
