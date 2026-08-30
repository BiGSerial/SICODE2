<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Survey\Main as SurveyDispatchMain;
use App\Http\Livewire\Dispatchs\Supervision\Main as SupervisionDispatchMain;
use App\Models\Company;
use App\Models\Note;
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
}
