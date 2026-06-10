<?php

namespace App\Jobs\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
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
        $process = new Process([
            PHP_BINARY,
            base_path('artisan'),
            'schedule:force-run',
            $this->eventHash,
            $this->displayName,
            '--timeout=' . $this->timeout,
        ], base_path());

        $process->setTimeout($this->timeout + 30);
        $process->run();

        $exitCode = $process->getExitCode();

        if ((int) $exitCode !== 0) {
            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

            throw new \RuntimeException("Execucao manual do schedule falhou com exit code {$exitCode}. " . ($output ?: 'Sem saida do processo.'));
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
