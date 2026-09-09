<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Survey\Main as SurveyDispatchMain;
use App\Http\Livewire\Dispatchs\Survey\Transfer as SurveyTransfer;
use App\Http\Livewire\Dispatchs\Supervision\Main as SupervisionDispatchMain;
use App\Models\Company;
use App\Models\Note;
use App\Models\Prodtransfer;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\Wpa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SurveyMassDdAssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mass_dd_association_moves_existing_dd_to_informed_note(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $service = Service::create(['service' => 'Levantamento', 'folder' => 'levantamento']);
        $oldNote = Note::create(['note' => '4000000001', 'dt_status' => now(), 'nstats' => 'OLD']);
        $newNote = Note::create(['note' => '4000000002', 'dt_status' => now(), 'nstats' => 'NEW']);
        $oldProduction = Production::create([
            'note_id' => $oldNote->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'dt_note' => now()->subDay(),
            'status_note' => 'OLD',
            'completed' => true,
            'confirmed' => true,
            'status' => 5,
        ]);

        $wpa = Wpa::create([
            'note_id' => $oldNote->id,
            'production_id' => $oldProduction->id,
            'service_id' => $service->uuid,
            'dd' => '170000001',
        ]);

        Livewire::test(SurveyDispatchMain::class, ['service' => $service->uuid])
            ->set('enter_dd', '4000000002 170000001')
            ->call('mass_modal')
            ->call('confirmed_mass_dd');

        $this->assertDatabaseHas('wpas', [
            'id' => $wpa->id,
            'note_id' => $newNote->id,
            'production_id' => null,
            'service_id' => $service->uuid,
            'dd' => '170000001',
        ]);
    }

    public function test_supervision_mass_dd_association_moves_existing_dd_to_informed_note(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $service = Service::create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao']);
        $oldNote = Note::create(['note' => '4000000003', 'dt_status' => now(), 'nstats' => 'OLD']);
        $newNote = Note::create(['note' => '4000000004', 'dt_status' => now(), 'nstats' => 'NEW']);
        $oldProduction = Production::create([
            'note_id' => $oldNote->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'dt_note' => now()->subDay(),
            'status_note' => 'OLD',
            'completed' => true,
            'confirmed' => true,
            'status' => 5,
        ]);

        $wpa = Wpa::create([
            'note_id' => $oldNote->id,
            'production_id' => $oldProduction->id,
            'service_id' => $service->uuid,
            'dd' => '170000002',
        ]);

        Livewire::test(SupervisionDispatchMain::class, ['service' => $service->uuid])
            ->set('enter_dd', '4000000004 170000002')
            ->call('mass_modal')
            ->call('confirmed_mass_dd');

        $this->assertDatabaseHas('wpas', [
            'id' => $wpa->id,
            'note_id' => $newNote->id,
            'production_id' => null,
            'service_id' => $service->uuid,
            'dd' => '170000002',
        ]);
    }

    public function test_survey_transfer_accepts_production_without_dd(): void
    {
        $from = User::factory()->create(['contract' => false]);
        $to = User::factory()->create(['contract' => false]);
        $this->actingAs($from);

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $service = Service::create(['service' => 'Levantamento', 'folder' => 'levantamento']);
        $note = Note::create(['note' => '4000000005', 'dt_status' => now(), 'nstats' => 'NEW']);
        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $from->id,
            'dt_note' => now(),
            'status_note' => 'NEW',
            'block_wpa' => true,
            'block' => true,
            'status' => 3,
        ]);

        Prodtransfer::create([
            'production_id' => $production->id,
            'service_id' => $service->uuid,
            'from' => $from->id,
            'to' => $to->id,
            'info' => 'Transferencia sem DD',
            'status' => 1,
        ]);

        Livewire::test(SurveyTransfer::class, ['service' => $service->uuid])
            ->assertSet("dd.{$production->id}", '')
            ->call('verify_transfer', $production)
            ->assertDispatchedBrowserEvent('alertar', function (string $event, array $data) {
                return str_contains($data['msg'] ?? '', 'sem número DD');
            })
            ->call('go_complete_transfer');

        $this->assertDatabaseHas('productions', [
            'id' => $production->id,
            'block_wpa' => false,
            'block' => false,
            'status' => 2,
        ]);
        $this->assertDatabaseCount('wpas', 0);
    }
}
