<?php

namespace App\Http\Livewire\Dispatchs\Reverse;

/**
 * Controle · Fluxo Reverso, visão da parceira: o mesmo controle (`Stack`); só o cabeçalho muda e a
 * exportação vai para a fila (`exports`) em vez de baixar na hora.
 */
class PartnerStack extends Stack
{
    use \App\Traits\QueuesPartnerExports;

    protected string $profile = 'partner';

    public function export_excel()
    {
        $this->queuePartnerExport('stack');
    }
}
