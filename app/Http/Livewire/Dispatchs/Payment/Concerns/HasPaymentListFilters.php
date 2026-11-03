<?php

namespace App\Http\Livewire\Dispatchs\Payment\Concerns;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\Note;
use App\Services\Payment\BlockEvaluator;
use App\Support\SicodeRules;

/**
 * Peças compartilhadas pelas listas de pagamento do despacho (`Main` e `PartnerMain`) no padrão de listas operacionais
 * (docs/PADRAO_LISTAS_OPERACIONAIS.md): a avaliação de bloqueio por linha em memo.
 * Usa `rowSelectionKey()` e as propriedades públicas do componente que a inclui.
 */
trait HasPaymentListFilters
{
    use WithNoteListFilters;

    /** @var array<string, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição.
     * A tela, o "selecionar todos" e a verificação do mestre liam isso 3x por linha (várias queries cada).
     *
     * @return array{eval: array, stack: ?\App\Models\Production, canDispatch: bool}
     */
    public function rowDispatchState(Note $note): array
    {
        $key = $this->rowSelectionKey($note);

        return $this->dispatchStateMemo[$key] ??= (function () use ($note) {
            $eval  = app(BlockEvaluator::class)->evaluate($note, $this->service);
            $stack = SicodeRules::openCompanyStackProductionFor($note, Auth()->User(), $this->service->uuid);

            return [
                'eval'        => $eval,
                'stack'       => $stack,
                'canDispatch' => !$eval['block'] || (bool) ($eval['command'] ?? false) || (bool) $stack,
            ];
        })();
    }

    private function canDispatchNote(Note $note): bool
    {
        return $this->rowDispatchState($note)['canDispatch'];
    }

}
