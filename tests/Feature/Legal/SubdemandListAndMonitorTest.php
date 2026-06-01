<?php

namespace Tests\Feature\Legal;

use App\Http\Livewire\Legal\Controller\DemandQueue;
use App\Http\Livewire\Legal\Controller\SubdemandMonitor;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandSubdemand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubdemandListAndMonitorTest extends TestCase
{
    use RefreshDatabase;

    private function controllerUser(): User
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
            'case_number' => 'C-3',
        ]);

        return LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $legalCase->id,
            'source_type' => 'subsidy',
            'source_status_group' => 'open_in_progress',
            'internal_status' => 'triage',
        ]);
    }

    public function test_queue_keeps_one_row_per_main_demand(): void
    {
        $user = $this->controllerUser();
        $demand = $this->makeDemand();

        LegalDemandSubdemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_demand_id' => $demand->id,
            'status' => 'aberta',
            'created_by_user_id' => $user->id,
            'status_contract_version' => 'v1',
        ]);
        LegalDemandSubdemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_demand_id' => $demand->id,
            'status' => 'em_andamento',
            'created_by_user_id' => $user->id,
            'status_contract_version' => 'v1',
        ]);

        $this->actingAs($user);

        Livewire::test(DemandQueue::class)
            ->assertViewHas('demands', function ($demands) {
                return $demands->total() === 1
                    && (int) ($demands->first()?->subdemands_count ?? 0) === 2;
            });
    }

    public function test_monitor_kpis_and_ordering_prioritize_overdue(): void
    {
        $user = $this->controllerUser();
        $demand = $this->makeDemand();

        $overdue = LegalDemandSubdemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_demand_id' => $demand->id,
            'status' => 'em_andamento',
            'deadline_at' => now()->subDay(),
            'created_by_user_id' => $user->id,
            'status_contract_version' => 'v1',
        ]);

        LegalDemandSubdemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_demand_id' => $demand->id,
            'status' => 'aberta',
            'deadline_at' => now()->addDay(),
            'created_by_user_id' => $user->id,
            'status_contract_version' => 'v1',
        ]);

        $this->actingAs($user);

        Livewire::test(SubdemandMonitor::class)
            ->set('scope', 'overdue')
            ->assertViewHas('kpis', fn (array $kpis) => ($kpis['overdue'] ?? 0) >= 1)
            ->assertViewHas('subdemands', function ($subdemands) use ($overdue) {
                return (int) ($subdemands->first()?->id ?? 0) === (int) $overdue->id;
            });
    }
}

