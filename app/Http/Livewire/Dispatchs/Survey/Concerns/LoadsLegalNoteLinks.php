<?php

namespace App\Http\Livewire\Dispatchs\Survey\Concerns;

use Illuminate\Support\Facades\DB;

/** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
trait LoadsLegalNoteLinks
{
    /** @return array<int, true> */
    protected function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }
}
