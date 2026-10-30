<?php

namespace App\Console\Commands\Update;

use App\Services\Partner\PepBudgetWatchService;
use Illuminate\Console\Command;

class ReconcilePepAlerts extends Command
{
    protected $signature = 'sicode:pep-alerts-reconcile';

    protected $description = 'Reavalia a lista de observação de saldo de PEP e encerra sozinho o que ficou coberto por mais de 48h sem ação.';

    public function handle(PepBudgetWatchService $watch): int
    {
        $stats = $watch->reconcile();

        $this->info("PEP: {$stats['checked']} avaliado(s), {$stats['covered']} coberto(s), {$stats['resolved']} encerrado(s) automaticamente.");

        return self::SUCCESS;
    }
}
