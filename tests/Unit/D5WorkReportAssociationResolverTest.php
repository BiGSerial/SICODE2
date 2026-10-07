<?php

namespace Tests\Unit;

use App\Models\{FiveNote, Note, WorkReport};
use App\Services\D5\D5WorkReportAssociationResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class D5WorkReportAssociationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_the_most_recent_informe_that_existed_when_d5_was_created(): void
    {
        $note = $this->note();
        $old  = $this->report($note, '2026-05-01 10:00:00');
        $last = $this->report($note, '2026-05-10 10:00:00');
        $this->report($note, '2026-05-20 10:00:00');

        $d5 = new FiveNote([
            'note_id'   => $note->id,
            'note_d5'   => 'D5-CAUSAL',
            'isPassive' => false,
        ]);
        $d5->created_at = Carbon::parse('2026-05-15 10:00:00');

        $resolved = app(D5WorkReportAssociationResolver::class)->closestFor($d5);

        $this->assertSame($last->id, $resolved?->id);
        $this->assertNotSame($old->id, $resolved?->id);
    }

    public function test_it_uses_the_first_future_informe_only_when_no_previous_informe_exists(): void
    {
        $note  = $this->note();
        $first = $this->report($note, '2026-05-20 10:00:00');
        $this->report($note, '2026-05-30 10:00:00');

        $d5             = new FiveNote(['note_id' => $note->id, 'note_d5' => 'D5-IMPORT']);
        $d5->created_at = Carbon::parse('2026-05-15 10:00:00');

        $this->assertSame($first->id, app(D5WorkReportAssociationResolver::class)->closestFor($d5)?->id);
    }

    public function test_it_never_associates_a_passive_d5_automatically(): void
    {
        $note = $this->note();
        $this->report($note, '2026-05-10 10:00:00');

        $d5             = new FiveNote(['note_id' => $note->id, 'note_d5' => 'D5-PASSIVA', 'isPassive' => true]);
        $d5->created_at = Carbon::parse('2026-05-15 10:00:00');

        $this->assertNull(app(D5WorkReportAssociationResolver::class)->closestFor($d5));
    }

    private function note(): Note
    {
        return Note::create([
            'note'      => uniqid('400', true),
            'dt_status' => '2026-05-01 00:00:00',
            'nstats'    => 'NEW',
        ]);
    }

    private function report(Note $note, string $informedAt): WorkReport
    {
        return WorkReport::create([
            'note_id'     => $note->id,
            'date'        => substr($informedAt, 0, 10),
            'informed_at' => $informedAt,
            'canceled'    => false,
        ]);
    }
}
