<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Supervision\{PartnerStack, Stack};
use App\Models\{Company, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupervisionDispatchStackEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stacks_keep_an_open_production_linked_to_an_eligible_work_report(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $this->actingAs(User::factory()->create(['contract' => false]));
        $company            = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $service            = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $eligibleProduction = $this->productionForReport($company, $service, '4000006001', '1700006001', 'LIB');
        $blockedProduction  = $this->productionForReport($company, $service, '4000006002', '1700006002', 'ABER');

        foreach ([Stack::class, PartnerStack::class] as $component) {
            $productionIds = Livewire::test($component, ['service' => $service->uuid])
                ->instance()
                ->getListsProperty()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->assertContains($eligibleProduction->id, $productionIds);
            $this->assertNotContains($blockedProduction->id, $productionIds);
        }
    }

    private function productionForReport(
        Company $company,
        Service $service,
        string $noteNumber,
        string $orderNumber,
        string $orderStatus,
    ): Production {
        $note  = Note::create(['note' => $noteNumber, 'type_note' => 1]);
        $order = Order::create([
            'note_id'    => $note->id,
            'ordem'      => $orderNumber,
            'statusSist' => $orderStatus,
        ]);
        Operation::create(['order_id' => $order->id, 'operacao' => '0030', 'status' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0040', 'status' => 'LIB']);

        $report = WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'date'        => '2026-10-08',
            'informed_at' => '2026-10-08 08:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ]);
        $report->Orders()->attach($order->id);

        $production = Production::create([
            'note_id'    => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'status'     => 2,
            'completed'  => false,
            'confirmed'  => false,
        ]);
        WorkReportFlowProduction::create([
            'work_report_id' => $report->id,
            'production_id'  => $production->id,
            'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
            'is_current'     => true,
            'source'         => 'test',
        ]);

        return $production;
    }
}
