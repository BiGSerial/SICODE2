<?php

namespace App\Http\Livewire\Dispatchs\Publication;

use App\Models\Note;

/**
 * Despacho da parceira → Lista para Publicação. Mesma lista operacional de `Main` (filtros, busca, seleção, despacho);
 * muda só a exportação (em fila), a view e o fato de mostrar também as notas sem informe com Publicação exigida.
 */
class PartnerMain extends Main
{
    use \App\Traits\QueuesPartnerExports;

    public function export_excel()
    {
        $this->queuePartnerExport('main');
    }

    protected function ineligibleRows(Note $note): array
    {
        $note->setAttribute('publication_context_key', (string) $note->id);
        $note->setRelation('WorkForm', $note->relationLoaded('WorkForms') ? $note->WorkForms->first() : null);

        return [$note];
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.publication.partner_main';
    }

    protected function heroSubtitle(): string
    {
        return 'Notas e OVs da sua empresa com informe liberado para publicação. Marque as linhas, escolha o responsável e acompanhe os prazos pela cor.';
    }
}
