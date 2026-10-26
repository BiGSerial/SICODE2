<?php

namespace App\Console\Commands\Closure;

use App\Services\Closure\ClosureStatusUserTracker;
use Illuminate\Console\Command;

class SyncStatusUser extends Command
{
    protected $signature = 'closure:sync-status-user';

    protected $description = 'Registra desde quando cada Ordem do Encerramento está no status usuário atual (caixa "Em acompanhamento" de Medição/Fiscalização).';

    public function handle(ClosureStatusUserTracker $tracker): int
    {
        $summary = $tracker->sync();

        $this->info("Encerramento status usuário: {$summary['scanned']} verificadas, {$summary['initialized']} registradas pela 1ª vez, {$summary['changed']} mudaram de status.");

        return self::SUCCESS;
    }
}
