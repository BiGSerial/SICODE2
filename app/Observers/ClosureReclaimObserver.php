<?php

namespace App\Observers;

use App\Models\Reclaim;
use App\Services\Closure\ClosureInternalReturnService;
use Throwable;

/**
 * Mantém o RI do Encerramento em sincronia com o Reclaim (conclusão fecha o RI; reabertura apenas avisa).
 *
 * O Reclaim é de outra atividade (Desenho/Viabilidade) e é salvo por vários fluxos: nada daqui pode quebrar esse salvamento.
 * Qualquer erro é só reportado; a varredura `closure:sync-internal-returns` corrige o RI depois.
 */
class ClosureReclaimObserver
{
    public function saved(Reclaim $reclaim): void
    {
        try {
            app(ClosureInternalReturnService::class)->handleReclaimSaved($reclaim);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
