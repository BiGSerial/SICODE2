<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloseCompletedProductionReclaimsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_open_reclaims_using_the_exact_production_completed_at(): void
    {
        [$note, $service, $company] = $this->context();

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'completed' => true,
            'completed_at' => '2026-08-28 10:15:30',
        ]);

        $reclaim = Reclaim::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'production_id' => $production->id,
            'completed' => false,
        ]);

        $this->artisan('sicode:reclaims:close-completed-productions')
            ->assertSuccessful();

        $this->assertDatabaseHas('reclaims', [
            'id' => $reclaim->id,
            'completed' => true,
            'completed_at' => '2026-08-28 10:15:30',
        ]);
    }

    public function test_dry_run_does_not_close_reclaims(): void
    {
        [$note, $service, $company] = $this->context();

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'completed' => true,
            'completed_at' => '2026-08-28 10:15:30',
        ]);

        $reclaim = Reclaim::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'production_id' => $production->id,
            'completed' => false,
        ]);

        $this->artisan('sicode:reclaims:close-completed-productions', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('reclaims', [
            'id' => $reclaim->id,
            'completed' => false,
            'completed_at' => null,
        ]);
    }

    public function test_it_closes_reclaims_matched_by_note_and_service_when_production_id_is_missing(): void
    {
        [$note, $service, $company] = $this->context();

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'completed' => true,
            'completed_at' => '2026-08-28 11:22:33',
        ]);

        $reclaim = Reclaim::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'production_id' => null,
            'completed' => false,
        ]);

        $this->artisan('sicode:reclaims:close-completed-productions')
            ->assertSuccessful();

        $this->assertDatabaseHas('reclaims', [
            'id' => $reclaim->id,
            'production_id' => $production->id,
            'completed' => true,
            'completed_at' => '2026-08-28 11:22:33',
        ]);
    }

    public function test_it_ignores_completed_productions_without_completed_at(): void
    {
        [$note, $service, $company] = $this->context();

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'completed' => true,
            'completed_at' => null,
        ]);

        $reclaim = Reclaim::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'production_id' => $production->id,
            'completed' => false,
        ]);

        $this->artisan('sicode:reclaims:close-completed-productions')
            ->assertSuccessful();

        $this->assertDatabaseHas('reclaims', [
            'id' => $reclaim->id,
            'completed' => false,
            'completed_at' => null,
        ]);
    }

    private function context(): array
    {
        return [
            Note::create(['note' => '4000000001']),
            Service::create(['service' => 'Fiscalizacao']),
            Company::create(['name' => 'Compel', 'email' => 'compel@example.com']),
        ];
    }
}
