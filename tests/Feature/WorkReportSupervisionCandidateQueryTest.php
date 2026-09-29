<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use App\Services\Supervision\WorkReportSupervisionCandidateQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkReportSupervisionCandidateQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_final_work_reports_independently_for_the_same_note(): void
    {
        config(['sicode.ruleset' => 'sp']);

        [$networkReport, $connectionReport] = $this->noteWithTwoEligibleWorkReports();

        $ids = app(WorkReportSupervisionCandidateQuery::class)->query()->pluck('id')->all();

        $this->assertSame([$networkReport->id, $connectionReport->id], $ids);
    }

    public function test_blocks_only_the_work_report_that_already_has_open_supervision(): void
    {
        config(['sicode.ruleset' => 'sp']);

        [$networkReport, $connectionReport, $company, $user, $service] = $this->noteWithTwoEligibleWorkReports();
        $production                                                    = Production::create([
            'note_id'    => $networkReport->note_id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'completed'  => false,
            'confirmed'  => false,
        ]);

        WorkReportFlowProduction::create([
            'work_report_id' => $networkReport->id,
            'production_id'  => $production->id,
            'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope'    => WorkReportFlowProduction::SCOPE_NETWORK,
            'is_current'     => true,
            'source'         => 'test',
        ]);

        $ids = app(WorkReportSupervisionCandidateQuery::class)->query()->pluck('id')->all();

        $this->assertSame([$connectionReport->id], $ids);
    }

    public function test_requires_at_least_one_order_in_the_work_report_to_be_eligible(): void
    {
        config(['sicode.ruleset' => 'sp']);

        [$networkReport] = $this->noteWithTwoEligibleWorkReports();
        $blockedOrder    = Order::create([
            'note_id'    => $networkReport->note_id,
            'ordem'      => '1700000999',
            'statusSist' => 'ABER',
        ]);
        $networkReport->Orders()->attach($blockedOrder->id);

        $this->assertTrue(
            app(WorkReportSupervisionCandidateQuery::class)->query()->whereKey($networkReport->id)->exists()
        );
    }

    public function test_does_not_return_work_report_without_any_eligible_order(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $report = $this->workReportWithOrder('4000003301', '1700003301', [
            ['operacao' => '0030', 'status' => 'CONF'],
        ]);

        $this->assertFalse(
            app(WorkReportSupervisionCandidateQuery::class)->query()->whereKey($report->id)->exists()
        );
    }

    public function test_sp_operation_30_confirmed_is_not_a_supervision_candidate(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $report = $this->workReportWithOrder('4000003300', '1700003300', [
            ['operacao' => '0030', 'status' => 'CONF'],
        ]);

        $this->assertFalse(
            app(WorkReportSupervisionCandidateQuery::class)->query()->whereKey($report->id)->exists()
        );
    }

    private function noteWithTwoEligibleWorkReports(): array
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note    = Note::create(['note' => '4000003000', 'type_note' => 1]);

        $networkReport    = $this->workReportFor($note, $company, $user, '1700003000', ['network']);
        $connectionReport = $this->workReportFor($note, $company, $user, '1800003000', ['connection']);

        return [$networkReport, $connectionReport, $company, $user, $service];
    }

    private function workReportWithOrder(string $noteNumber, string $orderNumber, array $operations): WorkReport
    {
        $company = Company::create(['name' => 'Compel ' . $noteNumber, 'email' => $noteNumber . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => $noteNumber, 'type_note' => 1]);
        $order   = Order::create([
            'note_id'    => $note->id,
            'ordem'      => $orderNumber,
            'statusSist' => 'LIB',
        ]);

        foreach ($operations as $operation) {
            Operation::create([
                'order_id' => $order->id,
                'operacao' => $operation['operacao'],
                'status'   => $operation['status'],
            ]);
        }

        $report = WorkReport::create([
            'note_id'               => $note->id,
            'company_id'            => $company->id,
            'user_id'               => $user->id,
            'date'                  => '2026-09-10',
            'informed_at'           => '2026-09-10 08:00:00',
            'rejected'              => false,
            'canceled'              => false,
            'selected_final_scopes' => ['network'],
        ]);
        $report->Orders()->sync([$order->id]);

        return $report;
    }

    private function workReportFor(Note $note, Company $company, User $user, string $orderNumber, array $scopes): WorkReport
    {
        $order = Order::create([
            'note_id'    => $note->id,
            'ordem'      => $orderNumber,
            'statusSist' => 'LIB',
        ]);
        Operation::create(['order_id' => $order->id, 'operacao' => '0030', 'status' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0040', 'status' => 'LIB']);

        $report = WorkReport::create([
            'note_id'               => $note->id,
            'company_id'            => $company->id,
            'user_id'               => $user->id,
            'date'                  => '2026-09-10',
            'informed_at'           => '2026-09-10 08:00:00',
            'rejected'              => false,
            'canceled'              => false,
            'selected_final_scopes' => $scopes,
        ]);
        $report->Orders()->sync([$order->id]);

        return $report;
    }
}
