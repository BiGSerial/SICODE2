<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Partial, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncNoteInformFlowsPartialTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_fiscalization_fields_do_not_use_payment_completion(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000009910']);
        $partial = Partial::create([
            'note_id'        => $note->id,
            'company_id'     => $company->id,
            'supervision_id' => $user->id,
            'allow'          => true,
            'supervision'    => true,
            'supervision_at' => '2026-10-01 09:30:00',
            'payment'        => true,
            'payment_at'     => '2026-10-03 14:15:00',
            'complete'       => true,
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                          => "partial:{$partial->id}",
            'fiscalization_completed_at'        => '2026-10-01 09:30:00',
            'fiscalization_closed_in_sicode'    => 1,
            'fiscalization_closed_in_sicode_at' => '2026-10-01 09:30:00',
            'measurement_completed_at'          => '2026-10-03 14:15:00',
            'measurement_exited_at'             => '2026-10-03 14:15:00',
        ]);
    }

    public function test_partial_payment_completion_does_not_close_an_unfinished_fiscalization(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $note    = Note::create(['note' => '4000009911']);
        $partial = Partial::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'allow'       => true,
            'supervision' => false,
            'payment'     => true,
            'payment_at'  => '2026-10-03 14:15:00',
            'complete'    => true,
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                          => "partial:{$partial->id}",
            'fiscalization_closed_in_sicode'    => 0,
            'fiscalization_closed_in_sicode_at' => null,
            'measurement_exited_at'             => '2026-10-03 14:15:00',
        ]);
    }
}
