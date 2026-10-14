<?php

namespace Tests\Feature;

use App\Http\Livewire\ProjectReview\Queue;
use App\Models\{Company, Note, Production, ProjectReviewCycle, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectReviewFinalizeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function pendingReview(string $submittedAt = '2026-10-05 10:00:00'): array
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $analyst = User::factory()->create(['company_id' => $company->id]);
        $drafter = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Desenho']);
        $note    = Note::create(['note' => uniqid('8'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);

        $production = Production::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $drafter->id,
            'status'  => Production::STATUS_IN_PROJECT_REVIEW, 'completed' => false, 'partial' => false, 'att_at' => $submittedAt,
        ]);
        $cycle = ProjectReviewCycle::create([
            'production_id' => $production->id, 'round_number' => 1, 'submitted_by' => $drafter->id,
            'submitted_at'  => $submittedAt, 'decision' => 'PENDING',
        ]);

        return [$analyst, $production, $cycle, $note];
    }

    public function test_finalize_closes_the_cycle_and_completes_the_production_without_sap_release(): void
    {
        [$analyst, $production, $cycle] = $this->pendingReview();
        $this->actingAs($analyst);

        Livewire::test(Queue::class)
            ->call('openReview', $production->id)
            ->set('analystNote', 'Concluída pelo analista')
            ->call('finalizeReview')
            ->assertDispatchedBrowserEvent('hideModal');

        $cycle->refresh();
        $production->refresh();

        $this->assertSame('FINALIZED', $cycle->decision);
        $this->assertSame($analyst->id, $cycle->decided_by);
        $this->assertNotNull($cycle->decided_at);
        $this->assertSame('Concluída pelo analista', $cycle->analyst_note);
        $this->assertSame(5, (int) $production->status);
        $this->assertTrue((bool) $production->completed);
        $this->assertNotNull($production->completed_at);
        $this->assertDatabaseHas('notetimelines', ['production_id' => $production->id, 'status' => 5]);
        $this->assertDatabaseCount('project_review_findings', 0);
        Notification::assertSentTo($production->User, \App\Notifications\SystemNotification::class);
    }

    public function test_finalize_does_nothing_when_the_review_is_no_longer_pending(): void
    {
        [$analyst, $production, $cycle] = $this->pendingReview();
        $this->actingAs($analyst);

        $component = Livewire::test(Queue::class)->call('openReview', $production->id);

        // Outra sessão aprova antes da confirmação.
        $cycle->update(['decision' => 'APPROVED']);
        $production->update(['status' => 5]);

        $component->call('finalizeReview')->assertNotDispatchedBrowserEvent('hideModal');

        $this->assertSame('APPROVED', $cycle->fresh()->decision);
    }

    public function test_finalized_review_appears_in_history_and_not_in_pending_queue(): void
    {
        [$analyst, $production, $cycle, $note] = $this->pendingReview();
        $this->actingAs($analyst);

        Livewire::test(Queue::class)->call('openReview', $production->id)->call('finalizeReview');

        Livewire::test(Queue::class, ['mode' => 'pending'])->assertDontSee($note->note);
        Livewire::test(Queue::class, ['mode' => 'history'])->assertSee($note->note);
    }

    public function test_sent_date_filter_uses_the_latest_cycle_submission(): void
    {
        [$analyst, , , $inRange] = $this->pendingReview('2026-10-05 10:00:00');
        [, , , $outOfRange]      = $this->pendingReview('2026-09-01 10:00:00');
        $this->actingAs($analyst);

        Livewire::test(Queue::class)
            ->set('sent_from', '2026-10-01')
            ->set('sent_to', '2026-10-10')
            ->assertSee($inRange->note)
            ->assertDontSee($outOfRange->note)
            ->set('sent_from', null)
            ->set('sent_to', '2026-09-30')
            ->assertSee($outOfRange->note)
            ->assertDontSee($inRange->note);
    }
}
