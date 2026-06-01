<?php

namespace Tests\Feature\Legal;

use App\Enum\LegalDemandSubdemandStatus;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\User;
use App\Services\Legal\LegalDemandSubdemandWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdemandWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeControllerUser(): User
    {
        return User::factory()->create([
            'legal_controller' => true,
            'admin' => false,
            'superadm' => false,
        ]);
    }

    private function makeDemand(): LegalDemand
    {
        $legalCase = LegalCase::create([
            'uuid' => (string) str()->uuid(),
            'case_number' => 'C-1',
        ]);

        return LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $legalCase->id,
            'source_type' => 'subsidy',
            'source_status_group' => 'open_in_progress',
            'internal_status' => 'new_imported',
        ]);
    }

    public function test_transition_contract_and_audit_event(): void
    {
        $user = $this->makeControllerUser();
        $demand = $this->makeDemand();
        $service = app(LegalDemandSubdemandWorkflowService::class);

        $sub = $service->create($demand, $user, null, null, null, 'Criada para teste');
        $this->assertSame(LegalDemandSubdemandStatus::ABERTA, $sub->status);

        $sub = $service->transitionStatus($sub, $user, LegalDemandSubdemandStatus::EM_ANDAMENTO, null, 'Iniciada');
        $this->assertSame(LegalDemandSubdemandStatus::EM_ANDAMENTO, $sub->status);

        $event = $sub->events()->where('event_type', 'status_changed')->latest('id')->first();
        $this->assertSame('status_changed', $event->event_type);
        $this->assertSame('controller', $event->actor_role);
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->makeControllerUser();
        $demand = $this->makeDemand();
        $service = app(LegalDemandSubdemandWorkflowService::class);
        $sub = $service->create($demand, $user, null, null, null);

        $service->transitionStatus($sub, $user, LegalDemandSubdemandStatus::CONCLUIDA);
    }

    public function test_controller_close_requires_reason(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->makeControllerUser();
        $demand = $this->makeDemand();
        $service = app(LegalDemandSubdemandWorkflowService::class);
        $sub = $service->create($demand, $user, null, null, null);

        $service->transitionStatus($sub, $user, LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR);
    }
}
