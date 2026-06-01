<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandInternalStatus;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandNoteActivityLink;
use App\Models\MedProtest;
use App\Models\ProtestJob;
use Illuminate\Support\Collection;

class LegalDemandActivityLinkService
{
    public function syncForProtestJobCreated(ProtestJob $job): void
    {
        $notes = $this->resolveNotesForJob($job);
        if ($notes->isEmpty()) {
            return;
        }

        $demandIdsByNote = $this->resolveActiveDemandIdsByNote($notes->pluck('id')->all());
        if ($demandIdsByNote === []) {
            return;
        }

        foreach ($notes as $note) {
            foreach (($demandIdsByNote[$note->id] ?? []) as $demandId) {
                LegalDemandNoteActivityLink::query()->firstOrCreate(
                    [
                        'legal_demand_id' => $demandId,
                        'note_id' => $note->id,
                        'activity_type' => 'protest_job',
                        'activity_id' => $job->id,
                        'unlinked_at' => null,
                    ],
                    [
                        'linked_by' => (string) ($job->created_by ?: auth()->id()),
                        'linked_at' => now(),
                        'meta' => [
                            'med_protest_id' => $job->med_protest_id,
                            'protest_id' => $job->protest_id,
                        ],
                    ]
                );
            }
        }
    }

    public function unlinkForProtestJobRemoved(ProtestJob $job, string $reason = 'activity_removed'): void
    {
        LegalDemandNoteActivityLink::query()
            ->where('activity_type', 'protest_job')
            ->where('activity_id', $job->id)
            ->whereNull('unlinked_at')
            ->update([
                'unlinked_at' => now(),
                'unlink_reason' => $reason,
            ]);
    }

    protected function resolveNotesForJob(ProtestJob $job): Collection
    {
        $med = MedProtest::query()
            ->with(['Notes:id', 'Protest.Notes:id'])
            ->find($job->med_protest_id);

        if (!$med) {
            return collect();
        }

        return $med->all_notes->filter(fn ($n) => $n && $n->id)->unique('id')->values();
    }

    protected function resolveActiveDemandIdsByNote(array $noteIds): array
    {
        $closed = [
            LegalDemandInternalStatus::CLOSED_INTERNAL->value,
            LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
            LegalDemandInternalStatus::CANCELLED->value,
            LegalDemandInternalStatus::IGNORED->value,
        ];

        $rows = \DB::table('legal_case_note as lcn')
            ->join('legal_demands as ld', 'ld.legal_case_id', '=', 'lcn.legal_case_id')
            ->whereIn('lcn.note_id', $noteIds)
            ->whereNull('ld.closed_at')
            ->whereNotIn('ld.internal_status', $closed)
            ->select(['lcn.note_id', 'ld.id'])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->note_id][] = (int) $row->id;
        }

        return $out;
    }
}

