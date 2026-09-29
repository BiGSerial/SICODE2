<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\Control\PartialEdit;
use App\Models\{Company, Note, Partial, Production, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartialEditProductionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_finished_payment_production_fills_partial_payment_data(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $note = Note::create(['note' => '4000009901']);
        $partial = Partial::create(['note_id' => $note->id, 'company_id' => $company->id, 'allow' => false, 'deny' => false, 'payment' => false, 'supervision' => false, 'complete' => false]);
        $finishedAt = '2026-09-23 10:30:00';
        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'completed' => true,
            'completed_at' => $finishedAt,
        ]);

        Livewire::test(PartialEdit::class)
            ->call('getInfoResponse', $partial)
            ->call('addProduction', $production->id);

        $this->assertDatabaseHas('partials', [
            'id' => $partial->id,
            'payment' => 1,
            'complete' => 1,
            'payment_id' => $user->id,
            'payment_at' => $finishedAt,
        ]);
    }

    public function test_open_payment_production_does_not_fill_payment_data(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $note = Note::create(['note' => '4000009902']);
        $partial = Partial::create(['note_id' => $note->id, 'company_id' => $company->id, 'allow' => false, 'deny' => false, 'payment' => false, 'supervision' => false, 'complete' => false]);
        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'completed' => false,
        ]);

        Livewire::test(PartialEdit::class)
            ->call('getInfoResponse', $partial)
            ->call('addProduction', $production->id);

        $this->assertDatabaseHas('partials', [
            'id' => $partial->id,
            'payment' => 0,
            'complete' => 0,
            'payment_id' => null,
            'payment_at' => null,
        ]);
    }
}
