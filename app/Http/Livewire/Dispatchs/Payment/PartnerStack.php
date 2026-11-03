<?php

namespace App\Http\Livewire\Dispatchs\Payment;

use Illuminate\Support\Facades\DB;

/**
 * Controle de Pagamento da parceira: a mesma lista de controle de `Stack` (consulta única, filtros na URL, ações sem
 * subcomponente por linha), com Empresa e Usuário em colunas separadas, sem o saldo do PEP e com o Excel em fila.
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 */
class PartnerStack extends Stack
{
    use \App\Traits\QueuesPartnerExports;

    /** Excel em fila (notifica ao concluir): respeita os filtros da tela e, com seleção, só as linhas marcadas. */
    public function export_excel()
    {
        $this->queuePartnerExport('stack');
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
    private function partnerLegalNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    public function render()
    {
        $lists = $this->lists;

        $this->recomputeSelectAllFor($lists?->items() ?? []);

        return view('livewire.dispatchs.pagamento.partner_stack', [
            'lists'        => $lists,
            'legalNoteIds' => $this->partnerLegalNoteIds($lists->pluck('note_id')->all()),
            'statusCounts' => $this->statusCounts,
        ]);
    }
}
