<?php

namespace App\Http\Livewire\Dispatchs\Reverse;

/**
 * Despacho · Fluxo Reverso, visão da parceira: a mesma lista operacional (`Main`); só o cabeçalho muda e a
 * exportação vai para a fila (`exports`) em vez de baixar na hora.
 */
class PartnerMain extends Main
{
    use \App\Traits\QueuesPartnerExports;

    protected string $profile = 'partner';

    public function export_excel()
    {
        $this->queuePartnerExport('main');
    }
}
