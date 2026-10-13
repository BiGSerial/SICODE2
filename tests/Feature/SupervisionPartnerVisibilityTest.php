<?php

namespace Tests\Feature;

use App\Models\{Company, FiveNote, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use App\Services\Dispatch\DispatchWorkflowService;
use App\Services\Supervision\SupervisionDispatchListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisionPartnerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_only_sees_its_own_eligible_work_report_on_a_shared_note(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $partner      = Company::create(['name' => 'Parceira', 'email' => 'parceira@example.com']);
        $otherCompany = Company::create(['name' => 'Outra empresa', 'email' => 'outra@example.com']);
        $partnerUser  = User::factory()->create(['contract' => true, 'company_id' => $partner->id]);
        $otherUser    = User::factory()->create(['company_id' => $otherCompany->id]);
        $service      = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note         = Note::create(['note' => '4000007301', 'type_note' => 1]);

        $partnerReport = $this->reportWithEligibleOrder($note, $partner, '1700007301');
        $otherReport   = $this->reportWithEligibleOrder($note, $otherCompany, '1700007302');

        $this->linkProduction($partnerReport, $service, $partner, $partnerUser, true);
        $this->linkProduction($otherReport, $service, $otherCompany, $otherUser, false);

        $visibleReportIds = app(SupervisionDispatchListQuery::class)->build([
            'serviceUuid' => $service->uuid,
            'user'        => $partnerUser,
        ])->pluck('operational_work_report_id')->map(fn ($id) => (int) $id)->all();

        // A produção já concluída continua até o SAP deixar de elegê-la, mas o
        // informe da outra empresa, na mesma Nota, nunca é exposto.
        $this->assertSame([$partnerReport->id], $visibleReportIds);
    }

    public function test_completed_partner_activity_disappears_when_the_report_is_no_longer_sap_eligible(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $partner     = Company::create(['name' => 'Parceira', 'email' => 'parceira@example.com']);
        $partnerUser = User::factory()->create(['contract' => true, 'company_id' => $partner->id]);
        $service     = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note        = Note::create(['note' => '4000007302', 'type_note' => 1]);
        $report      = $this->reportWithEligibleOrder($note, $partner, '1700007303');

        $this->linkProduction($report, $service, $partner, $partnerUser, true);
        $report->Orders()->first()->update(['statusSist' => 'ENCERRADA']);

        $visibleReportIds = app(SupervisionDispatchListQuery::class)->build([
            'serviceUuid' => $service->uuid,
            'user'        => $partnerUser,
        ])->pluck('operational_work_report_id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([], $visibleReportIds);
    }

    public function test_passive_d5_dispatch_is_linked_to_the_selected_d5(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $company = Company::create(['name' => 'Parceira', 'email' => 'parceira@example.com']);
        $actor   = User::factory()->create(['contract' => false]);
        $user    = User::factory()->create(['contract' => true, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note    = Note::create(['note' => '4000007303', 'type_note' => 1]);
        $d5      = FiveNote::create([
            'note_id'          => $note->id,
            'note_d5'          => 'D5-7303',
            'isPassive'        => true,
            'is_completed'     => true,
            'is_supervisioned' => false,
            'is_archived'      => false,
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $user,
            $actor,
            fiveNoteId: $d5->id,
        );

        $this->assertTrue($production->dfive);
        $this->assertTrue($d5->productions()->whereKey($production->id)->exists());
    }

    private function reportWithEligibleOrder(Note $note, Company $company, string $orderNumber): WorkReport
    {
        $order = Order::create([
            'note_id'    => $note->id,
            'ordem'      => $orderNumber,
            'statusSist' => 'LIB',
        ]);
        Operation::create(['order_id' => $order->id, 'operacao' => '0030', 'status' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0040', 'status' => 'LIB']);

        $report = WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'date'        => '2026-10-13',
            'informed_at' => '2026-10-13 08:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ]);
        $report->Orders()->attach($order->id);

        return $report;
    }

    private function linkProduction(WorkReport $report, Service $service, Company $company, User $user, bool $completed): void
    {
        $production = Production::create([
            'note_id'      => $report->note_id,
            'service_id'   => $service->uuid,
            'company_id'   => $company->id,
            'user_id'      => $user->id,
            'status'       => 2,
            'completed'    => $completed,
            'confirmed'    => $completed,
            'completed_at' => $completed ? now() : null,
        ]);
        WorkReportFlowProduction::create([
            'work_report_id' => $report->id,
            'production_id'  => $production->id,
            'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
            'is_current'     => true,
            'source'         => 'test',
        ]);
    }
}
