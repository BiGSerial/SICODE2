<?php

namespace App\Console\Commands\Closure;

use App\Services\Closure\ClosureInternalReturnService;
use Illuminate\Console\Command;

class SyncInternalReturns extends Command
{
    protected $signature = 'closure:sync-internal-returns';

    protected $description = 'Varredura de segurança: encerra os RI (Retorno Interno) cujo Reclaim já foi concluído pelo Desenho e destrava as Ordens.';

    public function handle(ClosureInternalReturnService $service): int
    {
        $summary = $service->syncAll();

        $this->info("Encerramento RI: {$summary['checked']} ativos verificados, {$summary['returned']} retornados.");

        if ($summary['orphans'] > 0) {
            $this->warn("{$summary['orphans']} RI ativo(s) sem Reclaim: a gestão precisa cancelá-los para destravar as Ordens.");
        }

        return self::SUCCESS;
    }
}
