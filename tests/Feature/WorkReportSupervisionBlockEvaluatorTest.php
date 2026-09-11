<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Production, Service, User, WorkReport, WorkReportFlowProduction};
use App\Services\Supervision\WorkReportBlockEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkReportSupervisionBlockEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_work_report_has_no_row_color_and_can_dispatch(): void
    {
        [$workReport, $service] = $this->fixture();

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport, $service);

        $this->assertSame('', $result['color']);
        $this->assertTrue($result['command']);
        $this->assertSame('workreport_sem_producao', $result['reason']);
    }

    public function test_company_stack_work_report_is_yellow_and_blocked(): void
    {
        [$workReport, $service, $company] = $this->fixture();

        $this->linkProduction($workReport, $service, $company, [
            'user_id'   => null,
            'status'    => 1,
            'completed' => false,
        ]);

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport->fresh(['FlowProductions.Production']), $service);

        $this->assertSame('table-warning', $result['color']);
        $this->assertFalse($result['command']);
        $this->assertSame('workreport_producao_na_pilha', $result['reason']);
    }

    public function test_assigned_open_work_report_is_blue_and_blocked(): void
    {
        [$workReport, $service, $company, $user] = $this->fixture();

        $this->linkProduction($workReport, $service, $company, [
            'user_id'   => $user->id,
            'status'    => 2,
            'completed' => false,
        ]);

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport->fresh(['FlowProductions.Production']), $service);

        $this->assertSame('table-primary', $result['color']);
        $this->assertFalse($result['command']);
        $this->assertSame('workreport_producao_em_andamento', $result['reason']);
    }

    public function test_completed_work_report_is_green_and_blocked(): void
    {
        [$workReport, $service, $company, $user] = $this->fixture();

        $this->linkProduction($workReport, $service, $company, [
            'user_id'      => $user->id,
            'status'       => 5,
            'completed'    => true,
            'confirmed'    => false,
            'completed_at' => '2026-09-10 09:00:00',
        ]);

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport->fresh(['Note', 'FlowProductions.Production']), $service);

        $this->assertSame('table-success', $result['color']);
        $this->assertFalse($result['command']);
        $this->assertSame('workreport_producao_concluida', $result['reason']);
    }

    public function test_reinformed_work_report_after_completed_production_is_free(): void
    {
        [$workReport, $service, $company, $user] = $this->fixture([
            'informed_at' => '2026-09-11 10:00:00',
        ]);

        $this->linkProduction($workReport, $service, $company, [
            'user_id'      => $user->id,
            'status'       => 5,
            'completed'    => true,
            'confirmed'    => false,
            'completed_at' => '2026-09-10 09:00:00',
        ]);

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport->fresh(['Note', 'FlowProductions.Production']), $service);

        $this->assertSame('', $result['color']);
        $this->assertTrue($result['command']);
        $this->assertSame('workreport_reinformado_pos_producao_concluida', $result['reason']);
    }

    public function test_confirmed_work_report_is_red_and_reprocessable(): void
    {
        [$workReport, $service, $company, $user] = $this->fixture();

        $this->linkProduction($workReport, $service, $company, [
            'user_id'      => $user->id,
            'status'       => 5,
            'completed'    => true,
            'confirmed'    => true,
            'completed_at' => '2026-09-10 09:00:00',
        ]);

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport->fresh(['Note', 'FlowProductions.Production']), $service);

        $this->assertSame('table-danger', $result['color']);
        $this->assertTrue($result['command']);
        $this->assertSame('workreport_producao_confirmada', $result['reason']);
    }

    public function test_rejected_assigned_work_report_is_yellow_and_blocked(): void
    {
        [$workReport, $service, $company, $user] = $this->fixture([
            'rejected' => true,
        ]);

        $production = $this->linkProduction($workReport, $service, $company, [
            'user_id'   => $user->id,
            'status'    => 2,
            'completed' => false,
        ]);

        $result = app(WorkReportBlockEvaluator::class)->evaluate($workReport->fresh(['FlowProductions.Production']), $service);

        $this->assertSame('table-warning', $result['color']);
        $this->assertFalse($result['command']);
        $this->assertSame('workreport_rejeitado_com_producao_atribuida', $result['reason']);
        $this->assertTrue($result['production']->is($production));
    }

    private function fixture(array $workReportOverrides = []): array
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('compel') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $note    = Note::create([
            'note'      => uniqid('400'),
            'dt_status' => '2026-09-10 08:00:00',
            'nstats'    => 'NEW',
        ]);
        $workReport = WorkReport::create(array_merge([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'date'        => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ], $workReportOverrides));

        return [$workReport, $service, $company, $user];
    }

    private function linkProduction(WorkReport $workReport, Service $service, Company $company, array $overrides): Production
    {
        $production = Production::create(array_merge([
            'note_id'     => $workReport->note_id,
            'service_id'  => $service->uuid,
            'company_id'  => $company->id,
            'completed'   => false,
            'confirmed'   => false,
            'dt_note'     => '2026-09-09 08:00:00',
            'status_note' => 'OLD',
        ], $overrides));

        WorkReportFlowProduction::create([
            'work_report_id' => $workReport->id,
            'production_id'  => $production->id,
            'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
            'is_current'     => true,
            'source'         => 'test',
        ]);

        return $production;
    }
}
