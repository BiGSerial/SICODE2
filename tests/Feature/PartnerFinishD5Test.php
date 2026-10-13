<?php

namespace Tests\Feature;

use App\Http\Livewire\Partner\FiveNote\Actions\FinishD5;
use App\Models\{FiveNote, Note, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerFinishD5Test extends TestCase
{
    use RefreshDatabase;

    public function test_partner_finishing_a_returned_d5_persists_responsible_and_clears_returned(): void
    {
        $user = User::factory()->create(['superadm' => true]);
        $note = Note::create(['note' => '4000000099', 'dt_status' => '2026-10-06 08:00:00', 'nstats' => 'NEW']);
        $five = FiveNote::create([
            'note_id'      => $note->id, 'note_d5' => 'D5-RET', 'visible_partner' => true,
            'is_completed' => false, 'returned' => true,
        ]);

        Livewire::actingAs($user)->test(FinishD5::class)
            ->call('getInfoResponse', $five->id)
            ->set('five.name', 'Maria Responsável')
            ->set('observations', 'Corrigido')
            ->call('finish');

        $five = $five->fresh();
        $this->assertTrue($five->is_completed);
        $this->assertFalse($five->returned);
        $this->assertSame('Maria Responsável', $five->name);
        $this->assertSame($user->id, $five->answered_by_user_id);
        $this->assertSame('Corrigido', $five->Comments()->latest('id')->first()?->message);
    }
}
