<?php

namespace App\Console\Commands\Closure;

use App\Services\Closure\ClosureSapSyncService;
use Illuminate\Console\Command;

class SyncClosed extends Command
{
    protected $signature = 'closure:sync-closed';

    protected $description = 'Move para CLOSED/CANCELLED as atividades de Encerramento cujas Ordens foram encerradas (ENTE/ENCE) ou canceladas, congelando o tipo de meta.';

    public function handle(ClosureSapSyncService $service): int
    {
        $summary = $service->syncClosed();

        $this->info("Encerramento SAP: {$summary['scanned']} verificadas, {$summary['closed']} encerradas, {$summary['cancelled']} canceladas.");

        return self::SUCCESS;
    }
}
