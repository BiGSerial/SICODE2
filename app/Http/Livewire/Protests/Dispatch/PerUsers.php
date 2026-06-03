<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\MedProtest;
use App\Models\UserAssignment;
use App\Traits\WildcardFormmater;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Component;
use Livewire\WithPagination;

class PerUsers extends Component
{
    use WildcardFormmater;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search = '';
    public $dt_start;
    public $dt_end;
    public $month;

    // TODO: Terminar de Acertar a lista de Usuarios.
    public function getListQueryBase()
    {
        return UserAssignment::where('completed', false)
                        ->with([
                'user:id,name',
                'assignable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        MedProtest::class => [
                            'Protest:id,nota,txtGrpCodificacao',  // sem protest_id
                            'Notes:id,note,material',
                            'Protest.Notes:id,note,material',
                        ],
                    ]);
                },
            ]);

    }

    public function getListProperty()
    {
        return $this->getListQueryBase()->orderBy('started_at', 'desc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $list = $this->list;

        return view('livewire.protests.dispatch.per-users', [
            'list' => $list,
            'legalTagsByAssignmentId' => $this->buildLegalTagsByAssignmentId($list->items()),
        ]);
    }

    protected function buildLegalTagsByAssignmentId(array $assignments): array
    {
        $tagsByAssignment = [];
        $noteIdsByAssignment = [];
        $allNoteIds = [];

        foreach ($assignments as $assignment) {
            $notes = $assignment->assignable?->all_notes ?? collect();
            $ids = $notes->pluck('id')->filter()->unique()->values()->all();
            $noteIdsByAssignment[$assignment->id] = $ids;
            $allNoteIds = array_merge($allNoteIds, $ids);
        }

        $allNoteIds = array_values(array_unique($allNoteIds));
        if ($allNoteIds === []) {
            return $tagsByAssignment;
        }

        $instructionLinks = DB::table('legal_demand_note_instructions')
            ->whereIn('note_id', $allNoteIds)
            ->where('active', true)
            ->select(['note_id', 'legal_demand_id']);

        $activityLinks = DB::table('legal_demand_note_activity_links')
            ->whereIn('note_id', $allNoteIds)
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
                    'sentence' => 'Sentenca',
                    'subsidy' => 'Subsidio',
                    default => (string) $row->source_type,
                },
                'status' => (string) ($row->source_status ?: 'Sem status'),
                'due_at' => $row->source_due_at ? Carbon::parse($row->source_due_at)->format('d/m/Y H:i') : 'Sem prazo',
                'is_overdue' => $row->source_due_at ? Carbon::parse($row->source_due_at)->isPast() : false,
                'badge_class' => match ((string) $row->source_type) {
                    'injunction' => 'bg-danger text-white',
                    'sentence' => 'bg-warning text-dark',
                    'subsidy' => 'bg-info text-dark',
                    default => 'bg-secondary text-white',
                },
            ];
        }

        foreach ($noteIdsByAssignment as $assignmentId => $noteIds) {
            $merged = [];
            foreach ($noteIds as $noteId) {
                foreach (($tagsByNote[(int) $noteId] ?? []) as $tag) {
                    $merged[$tag['id']] = $tag;
                }
            }
            if ($merged !== []) {
                $tagsByAssignment[(int) $assignmentId] = array_values($merged);
            }
        }

        return $tagsByAssignment;
    }
}
