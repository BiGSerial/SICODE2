<?php

namespace App\Http\Livewire\Services\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait BuildsLegalNoteTags
{
    protected function buildLegalTagsByNoteIds(array $noteIds): array
    {
        $noteIds = collect($noteIds)->filter()->unique()->values()->all();

        if ($noteIds === []) {
            return [];
        }

        $instructionLinks = DB::table('legal_demand_note_instructions')
            ->whereIn('note_id', $noteIds)
            ->where('active', true)
            ->select(['note_id', 'legal_demand_id']);

        $activityLinks = DB::table('legal_demand_note_activity_links')
            ->whereIn('note_id', $noteIds)
            ->whereNull('unlinked_at')
            ->select(['note_id', 'legal_demand_id']);

        $rows = DB::query()
            ->fromSub($instructionLinks->union($activityLinks), 'dln')
            ->join('legal_demands as ld', 'ld.id', '=', 'dln.legal_demand_id')
            ->whereIn('ld.source_type', ['injunction', 'sentence', 'subsidy'])
            ->whereNull('ld.closed_at')
            ->whereNotIn('ld.internal_status', ['cancelled', 'ignored'])
            ->orderByRaw('CASE WHEN ld.source_due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('ld.source_due_at')
            ->select(['dln.note_id', 'ld.id', 'ld.source_type', 'ld.source_status', 'ld.source_due_at'])
            ->distinct()
            ->get();

        $tagsByNote = [];

        foreach ($rows as $row) {
            $tagsByNote[(int) $row->note_id][] = [
                'id' => (int) $row->id,
                'type_label' => match ((string) $row->source_type) {
                    'injunction' => 'Liminar',
                    'sentence' => 'Sentença',
                    'subsidy' => 'Subsídio',
                    default => (string) $row->source_type,
                },
                'status' => (string) ($row->source_status ?: 'Sem status'),
                'due_at' => $row->source_due_at ? Carbon::parse($row->source_due_at)->format('d/m/Y H:i') : 'Sem prazo',
                'due_at_raw' => $row->source_due_at,
                'is_overdue' => $row->source_due_at ? Carbon::parse($row->source_due_at)->isPast() : false,
                'badge_class' => match ((string) $row->source_type) {
                    'injunction' => 'bg-danger text-white',
                    'sentence' => 'bg-warning text-dark',
                    'subsidy' => 'bg-info text-dark',
                    default => 'bg-secondary text-white',
                },
            ];
        }

        return $tagsByNote;
    }
}
