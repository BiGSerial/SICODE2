<?php

namespace Tests\Feature;

use App\Enum\LegalDemandInternalStatus;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandAssignment;
use App\Models\User;
use App\Services\Legal\LegalDemandWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\TestCase;

class LegalDemandWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function allowAllLegalPermissions(): void
    {
        $abilities = [
            'legal.demands.view',
            'legal.demands.triage',
            'legal.demands.assign',
            'legal.demands.answer',
            'legal.demands.review',
            'legal.demands.close_internal',
            'legal.demands.close_external',
            'legal.demands.reopen',
            'legal.demands.ignore',
            'legal.demands.manage_files',
            'legal.demands.view_controller_files',
        ];

        foreach ($abilities as $ability) {
            Gate::define($ability, fn () => true);
        }
    }

    private function makeDemand(string $status = LegalDemandInternalStatus::TRIAGE->value): LegalDemand
    {
        $case = LegalCase::create([
            'uuid' => (string) str()->uuid(),
            'process_number' => '1234567-89.2026.8.26.0100',
            'process_number_normalized' => '12345678920268260100',
            'company_name' => 'ACME',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $case->id,
            'source_type' => 'liminar',
            'source_external_id' => 'EXT-1',
            'source_record_key' => hash('sha256', uniqid('demand', true)),
            'source_hash' => hash('sha256', uniqid('hash', true)),
            'title' => 'Teste',
            'subject' => 'Assunto Teste',
            'internal_status' => $status,
            'source_presence_status' => 'present',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function test_block_internal_close_without_permission(): void
    {
        $this->allowAllLegalPermissions();
        Gate::define('legal.demands.close_internal', fn () => false);

        $actor = User::factory()->create();
        $demand = $this->makeDemand(LegalDemandInternalStatus::READY_TO_CLOSE_EXTERNAL->value);
        $service = new LegalDemandWorkflowService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sem permissão: legal.demands.close_internal');

        $service->closeInternal($demand, $actor, 'Sem permissão de teste');
    }

    public function test_block_field_answer_when_demand_is_closed(): void
    {
        $this->allowAllLegalPermissions();

        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $demand = $this->makeDemand(LegalDemandInternalStatus::CLOSED_EXTERNAL->value);

        $assignment = LegalDemandAssignment::create([
            'uuid' => (string) str()->uuid(),
            'legal_demand_id' => $demand->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_to_user_id' => $assignee->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $service = new LegalDemandWorkflowService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Demanda encerrada/cancelada não permite esta ação.');

        $service->answerFromField($assignment, $assignee, 'Retorno', false, null);
    }

    public function test_resend_creates_new_assignment_without_overwriting_previous(): void
    {
        $this->allowAllLegalPermissions();

        $controller = User::factory()->create();
        $assigneeA = User::factory()->create();
        $assigneeB = User::factory()->create();

        $demand = $this->makeDemand(LegalDemandInternalStatus::TRIAGE->value);
        $service = new LegalDemandWorkflowService();

        $first = $service->sendToField(
            $demand->fresh(),
            $controller,
            $assigneeA->id,
            null,
            'Primeiro envio',
            now()->addDay()
        );

        $second = $service->sendToField(
            $demand->fresh(),
            $controller,
            $assigneeB->id,
            null,
            'Reenvio',
            now()->addDays(2)
        );

        $this->assertNotEquals($first->id, $second->id);
        $this->assertDatabaseCount('legal_demand_assignments', 2);
        $this->assertDatabaseHas('legal_demand_assignments', [
            'id' => $first->id,
            'assigned_to_user_id' => $assigneeA->id,
            'message' => 'Primeiro envio',
        ]);
        $this->assertDatabaseHas('legal_demand_assignments', [
            'id' => $second->id,
            'assigned_to_user_id' => $assigneeB->id,
            'message' => 'Reenvio',
        ]);
        $this->assertDatabaseHas('legal_demands', [
            'id' => $demand->id,
            'current_assigned_user_id' => $assigneeB->id,
            'internal_status' => LegalDemandInternalStatus::SENT_TO_FIELD->value,
        ]);
    }
}
