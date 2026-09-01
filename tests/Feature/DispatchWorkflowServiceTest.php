<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\Wpa;
use App\Services\Dispatch\DispatchException;
use App\Services\Dispatch\DispatchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_dispatch_reuses_dd_already_linked_to_same_note_production(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $actor = User::factory()->create(['contract' => false]);
        $targetUser = User::factory()->create(['contract' => false, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Levantamento', 'folder' => 'levantamento']);
        $note = Note::create([
            'note' => '4000000001',
            'dt_status' => '2026-08-30 08:00:00',
            'nstats' => 'NEW',
        ]);
        Note::create(['note' => '4000000002']);

        $oldProduction = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $targetUser->id,
            'dt_note' => '2026-08-01 08:00:00',
            'status_note' => 'OLD',
            'completed' => true,
            'confirmed' => true,
            'status' => 5,
        ]);

        $wpa = Wpa::create([
            'note_id' => $note->id,
            'production_id' => $oldProduction->id,
            'service_id' => $service->uuid,
            'dd' => '170000001',
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $targetUser,
            $actor,
            '170000001'
        );

        $this->assertNotSame($oldProduction->id, $production->id);
        $this->assertDatabaseHas('wpas', [
            'id' => $wpa->id,
            'note_id' => $note->id,
            'production_id' => $production->id,
            'service_id' => $service->uuid,
            'dd' => '170000001',
        ]);
    }

    public function test_dispatch_keeps_blocking_dd_linked_to_another_note(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $actor = User::factory()->create(['contract' => false]);
        $targetUser = User::factory()->create(['contract' => false, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Levantamento', 'folder' => 'levantamento']);
        $note = Note::create(['note' => '4000000003']);
        $otherNote = Note::create(['note' => '4000000004']);

        Wpa::create([
            'note_id' => $otherNote->id,
            'service_id' => $service->uuid,
            'dd' => '170000002',
        ]);

        $this->expectException(DispatchException::class);
        $this->expectExceptionMessage('DD 170000002 ja foi associada a outra Nota/OV.');

        app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $targetUser,
            $actor,
            '170000002'
        );
    }

    public function test_design_dispatch_does_not_require_dd(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $actor = User::factory()->create(['contract' => false]);
        $targetUser = User::factory()->create(['contract' => false, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Desenho', 'folder' => 'desenho']);
        $note = Note::create([
            'note' => '4000000005',
            'dt_status' => '2026-08-30 08:00:00',
            'nstats' => 'NEW',
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $targetUser,
            $actor
        );

        $this->assertDatabaseHas('productions', [
            'id' => $production->id,
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'user_id' => $targetUser->id,
            'company_id' => $company->id,
            'status' => 2,
        ]);
        $this->assertDatabaseCount('wpas', 0);
    }

    public function test_payment_contract_user_can_self_assign_without_company_stack(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $contract = new \App\Models\Contract();
        $contract->company_id = $company->id;
        $contract->number = 'PAY-001';
        $contract->service = true;
        $contract->construction = false;
        $contract->date_end = now()->addYear()->toDateString();
        $contract->save();
        $actor = User::factory()->create([
            'contract' => true,
            'company_id' => $company->id,
        ]);
        $actor->Employee()->create([
            'contract_id' => $contract->id,
            'service_id' => null,
        ]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $note = Note::create([
            'note' => '4000000007',
            'dt_status' => '2026-08-30 08:00:00',
            'nstats' => 'NEW',
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor
        );

        $this->assertDatabaseHas('productions', [
            'id' => $production->id,
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'user_id' => $actor->id,
            'company_id' => $company->id,
            'status' => 2,
        ]);
    }

    public function test_design_dispatch_uses_design_block_evaluator(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $actor = User::factory()->create(['contract' => false]);
        $targetUser = User::factory()->create(['contract' => false, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Desenho', 'folder' => 'desenho']);
        $note = Note::create([
            'note' => '4000000006',
            'dt_status' => '2026-08-30 08:00:00',
            'nstats' => 'NEW',
        ]);

        Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $targetUser->id,
            'dt_note' => '2026-08-30 08:00:00',
            'status_note' => 'NEW',
            'completed' => true,
            'confirmed' => false,
            'status' => 5,
        ]);

        $this->expectException(DispatchException::class);

        app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $targetUser,
            $actor
        );
    }
}
