<?php

namespace Tests\Feature\Legal;

use App\Http\Livewire\Legal\Controller\DemandDetail;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubdemandControllerFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeDemand(): LegalDemand
    {
        $legalCase = LegalCase::create([
            'uuid' => (string) str()->uuid(),
            'case_number' => 'C-2',
        ]);

        return LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $legalCase->id,
            'source_type' => 'subsidy',
            'source_status_group' => 'open_in_progress',
            'internal_status' => 'triage',
        ]);
    }

    public function test_controller_can_create_subdemand_from_detail(): void
    {
        config(['features.legal_subdemands' => true]);

        $controller = User::factory()->create(['legal_controller' => true, 'admin' => false, 'superadm' => false]);
        $demand = $this->makeDemand();
        $demand->controller_user_id = $controller->id;
        $demand->save();

        $this->actingAs($controller);

        Livewire::test(DemandDetail::class, ['uuid' => $demand->uuid])
            ->set('subdemandAssignedToUserId', $controller->id)
            ->set('subdemandDescription', 'Subdemanda de teste')
            ->call('createSubdemand');

        $this->assertDatabaseHas('legal_demand_subdemands', [
            'legal_demand_id' => $demand->id,
            'assigned_to_user_id' => $controller->id,
        ]);
    }

    public function test_non_controller_cannot_change_subdemand_status(): void
    {
        config(['features.legal_subdemands' => true]);

        $controller = User::factory()->create(['legal_controller' => true, 'admin' => false, 'superadm' => false]);
        $other = User::factory()->create(['legal_controller' => true, 'admin' => false, 'superadm' => false]);
        $demand = $this->makeDemand();
        $demand->controller_user_id = $controller->id;
        $demand->save();

        $this->actingAs($controller);
        Livewire::test(DemandDetail::class, ['uuid' => $demand->uuid])
            ->set('subdemandAssignedToUserId', $controller->id)
            ->call('createSubdemand');
        $sub = $demand->subdemands()->firstOrFail();

        $this->actingAs($other);
        Livewire::test(DemandDetail::class, ['uuid' => $demand->uuid])
            ->set("subdemandInlineStatus.{$sub->id}", 'em_andamento')
            ->call('applyInlineSubdemandStatus', $sub->id);

        $this->assertDatabaseHas('legal_demand_subdemands', [
            'id' => $sub->id,
            'status' => 'aberta',
        ]);
    }
}
