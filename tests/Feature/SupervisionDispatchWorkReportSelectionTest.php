<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Supervision\Main as SupervisionDispatchMain;
use App\Models\{Company, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupervisionDispatchWorkReportSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_report_selection_opens_work_report_dispatch_modal(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        Note::create(['note' => '4000005000', 'dt_status' => '2026-09-10 08:00:00']);

        Livewire::test(SupervisionDispatchMain::class, ['service' => $service->uuid])
            ->set('selected', ['wr:10', 'wr:20'])
            ->call('go_att_mass')
            ->assertEmittedTo('dispatchs.shared.dispatch-modal', 'openForWorkReports', [10, 20]);
    }

    public function test_selection_key_uses_work_report_id_when_line_has_operational_work_report(): void
    {
        $note       = Note::create(['note' => '4000005001']);
        $workReport = WorkReport::create([
            'note_id'     => $note->id,
            'date'        => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ]);

        $note->setAttribute('operational_work_report_id', $workReport->id);
        $note->setRelation('WorkReports', collect([$workReport]));

        $component = new SupervisionDispatchMain();

        $this->assertSame('wr:' . $workReport->id, $component->selectionKeyFor($note));
        $this->assertTrue($component->operationalWorkReportFor($note)->is($workReport));
    }

    public function test_supervision_list_shows_only_work_reports_with_some_eligible_order(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note    = Note::create([
            'note'      => '4000005002',
            'type_note' => 1,
            'dt_status' => '2026-09-10 08:00:00',
            'nstats'    => 'NEW',
        ]);

        $eligible = $this->workReportFor($note, $company, '1700005002', 'LIB');
        $blocked  = $this->workReportFor($note, $company, '1800005002', 'ABER');

        $component = Livewire::test(SupervisionDispatchMain::class, ['service' => $service->uuid]);
        $ids       = $component->instance()
            ->getListsProperty()
            ->pluck('operational_work_report_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->assertContains($eligible->id, $ids);
        $this->assertNotContains($blocked->id, $ids);
    }

    public function test_rejected_work_report_does_not_appear_even_when_it_has_assigned_open_supervision(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note    = Note::create([
            'note'      => '4000005003',
            'type_note' => 1,
            'dt_status' => '2026-09-10 08:00:00',
            'nstats'    => 'NEW',
        ]);

        $rejectedWithoutProduction = $this->workReportFor($note, $company, '1700005003', 'LIB', rejected: true);
        $rejectedAssigned          = $this->workReportFor($note, $company, '1800005003', 'LIB', rejected: true);
        $production                = Production::create([
            'note_id'    => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'status'     => 2,
            'completed'  => false,
            'confirmed'  => false,
        ]);
        WorkReportFlowProduction::create([
            'work_report_id' => $rejectedAssigned->id,
            'production_id'  => $production->id,
            'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope'    => WorkReportFlowProduction::SCOPE_CONNECTION,
            'is_current'     => true,
            'source'         => 'test',
        ]);

        $component = Livewire::test(SupervisionDispatchMain::class, ['service' => $service->uuid]);
        $ids       = $component->instance()
            ->getListsProperty()
            ->pluck('operational_work_report_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->assertNotContains($rejectedWithoutProduction->id, $ids);
        $this->assertNotContains($rejectedAssigned->id, $ids);
    }

    public function test_work_report_appears_when_any_order_in_its_scope_is_eligible(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note    = Note::create([
            'note'      => '4000005004',
            'type_note' => 1,
            'dt_status' => '2026-09-10 08:00:00',
            'nstats'    => 'NEW',
        ]);

        $report        = $this->workReportFor($note, $company, '1700005004', 'ABER');
        $eligibleOrder = Order::create([
            'note_id'    => $note->id,
            'ordem'      => '1700005005',
            'statusSist' => 'LIB',
        ]);
        Operation::create(['order_id' => $eligibleOrder->id, 'operacao' => '0030', 'status' => 'LIB']);
        Operation::create(['order_id' => $eligibleOrder->id, 'operacao' => '0040', 'status' => 'LIB']);
        $report->Orders()->attach($eligibleOrder->id);

        $ids = Livewire::test(SupervisionDispatchMain::class, ['service' => $service->uuid])
            ->instance()
            ->getListsProperty()
            ->pluck('operational_work_report_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->assertContains($report->id, $ids);
    }

    private function workReportFor(Note $note, Company $company, string $orderNumber, string $orderStatus, bool $rejected = false): WorkReport
    {
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
            'date'        => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected'    => $rejected,
            'canceled'    => false,
        ]);
        $report->Orders()->sync([$order->id]);

        return $report;
    }
}
