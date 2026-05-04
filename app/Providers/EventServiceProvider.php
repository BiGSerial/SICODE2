<?php

namespace App\Providers;

use App\Listeners\LogScheduledTask;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ScheduledTaskStarting::class => [
            LogScheduledTask::class,
        ],
        ScheduledTaskFinished::class => [
            LogScheduledTask::class,
        ],
        ScheduledBackgroundTaskFinished::class => [
            LogScheduledTask::class,
        ],
        ScheduledTaskFailed::class => [
            LogScheduledTask::class,
        ],
        ScheduledTaskSkipped::class => [
            LogScheduledTask::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
