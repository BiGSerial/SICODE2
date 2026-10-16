<?php

namespace App\Console\Commands\Closure;

use App\Services\Closure\ClosureOrderWorkflowService;
use Illuminate\Console\Command;

class PromoteWaiting extends Command
{
    protected $signature = 'closure:promote-waiting';

    protected $description = 'Promove de Aguardando (WAITING) para Finalizado as atividades cuja Ordem deixou de ser meta futura.';

    public function handle(ClosureOrderWorkflowService $workflow): int
    {
        $this->info("Atividades promovidas para Finalizado: {$workflow->promoteWaiting()}.");

        return self::SUCCESS;
    }
}
