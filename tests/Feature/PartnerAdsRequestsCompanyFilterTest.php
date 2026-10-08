<?php

namespace Tests\Feature;

use App\Http\Livewire\Partner\AdsRequests;
use App\Models\{Company, Note, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerAdsRequestsCompanyFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_company_does_not_make_an_existing_note_appear_missing(): void
    {
        $company = Company::create(['name' => 'Empresa selecionada', 'email' => 'empresa@example.test']);
        $user    = User::factory()->create(['company_id' => $company->id, 'superadm' => true]);
        $note    = Note::create(['note' => '4001800922', 'dt_status' => '2026-10-07 08:00:00', 'nstats' => 'NEW']);
        Order::create([
            'note_id'    => $note->id,
            'ordem'      => '1000000001',
            'statusSist' => 'LIBERADA',
        ]);

        $this->actingAs($user);

        Livewire::test(AdsRequests::class)
            ->set('partnerCompanyFilter', $company->id)
            ->set('notesInput', $note->note)
            ->call('analyzeNotes')
            ->assertSet('previewItems.' . $note->note . '.can_process', true)
            ->assertSet('previewItems.' . $note->note . '.status_label', 'Apto');
    }
}
