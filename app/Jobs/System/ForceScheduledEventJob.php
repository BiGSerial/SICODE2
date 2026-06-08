<?php

namespace App\Jobs\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForceScheduledEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $eventHash;
    public string $displayName;

    public $tries = 1;
    public int $timeout = 3600;
    public bool $failOnTimeout = true;

    public function __construct(string $eventHash, string $displayName)
    {
        $this->onQueue('schedule');
        $this->eventHash = $eventHash;
        $this->displayName = $displayName;
    }

    public function handle(): void
    {
        $exitCode = Artisan::call('schedule:force-run', [
            'eventHash' => $this->eventHash,
            'displayName' => $this->displayName,
            '--timeout' => $this->timeout,
        ]);

        if ((int) $exitCode !== 0) {
            throw new \RuntimeException("Execucao manual do schedule falhou com exit code {$exitCode}.");
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ForceScheduledEventJob falhou', [
            'event_hash' => $this->eventHash,
            'display_name' => $this->displayName,
            'error' => $exception->getMessage(),
        ]);
    }
}
