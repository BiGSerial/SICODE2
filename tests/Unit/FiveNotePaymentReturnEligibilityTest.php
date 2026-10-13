<?php

namespace Tests\Unit;

use App\Models\{FiveNote, Note};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiveNotePaymentReturnEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_exact_completed_supervisioned_active_and_not_returned_state_enters_cycle_two(): void
    {
        $eligible = $this->five([
            'is_completed'     => true,
            'is_supervisioned' => true,
        ]);

        $notCompleted = $this->five([
            'is_completed'     => false,
            'is_supervisioned' => true,
        ]);
        $notSupervisioned = $this->five([
            'is_completed'     => true,
            'is_supervisioned' => false,
        ]);
        $archived = $this->five([
            'is_completed'     => true,
            'is_supervisioned' => true,
            'is_archived'      => true,
        ]);
        $returned = $this->five([
            'is_completed'     => true,
            'is_supervisioned' => true,
            'returned'         => true,
        ]);

        $this->assertTrue($eligible->isEligibleForPaymentReturn());
        $this->assertFalse($notCompleted->isEligibleForPaymentReturn());
        $this->assertFalse($notSupervisioned->isEligibleForPaymentReturn());
        $this->assertFalse($archived->isEligibleForPaymentReturn());
        $this->assertFalse($returned->isEligibleForPaymentReturn());
        $this->assertSame([$eligible->id], FiveNote::eligibleForPaymentReturn()->pluck('id')->all());
    }

    public function test_returned_d5_is_not_reintroduced_as_new_payment_creation(): void
    {
        $returned = $this->five(['returned' => true]);
        $fresh    = $this->five();

        $this->assertFalse($returned->isAwaitingPaymentCreation());
        $this->assertFalse($returned->needsPaymentAction());
        $this->assertTrue($fresh->isAwaitingPaymentCreation());
        $this->assertSame([$fresh->id], FiveNote::awaitingPaymentCreation()->pluck('id')->all());
    }

    public function test_resubmission_after_return_clears_returned_and_can_progress_again(): void
    {
        $five = $this->five(['returned' => true]);

        $five->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $this->assertTrue($five->fresh()->is_completed);
        $this->assertFalse($five->fresh()->returned);
    }

    private function five(array $attributes = []): FiveNote
    {
        $note = Note::create([
            'note'      => uniqid('400', true),
            'dt_status' => '2026-10-06 08:00:00',
            'nstats'    => 'NEW',
        ]);

        return FiveNote::create(array_merge([
            'note_id' => $note->id,
            'note_d5' => uniqid('D5', true),
        ], $attributes));
    }
}
