<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Note;
use App\Models\Order;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use App\Services\WorkReports\WorkReportFlowProductionLinker;
use App\Services\WorkReports\WorkReportScopedProductionSplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkReportFlowProductionLinkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_production_to_latest_active_final_work_report_for_note(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4000000001']);
        $service = Service::create(['service' => 'Fiscalização']);

        $older = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-01',
            'informed_at' => '2026-08-01 08:00:00',
            'canceled' => true,
        ]);
        $latest = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-03',
            'informed_at' => '2026-08-03 08:00:00',
        ]);

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'att_at' => '2026-08-03 09:00:00',
            'completed' => false,
            'partial' => false,
        ]);

        $link = app(WorkReportFlowProductionLinker::class)->linkFiscalization($production, 'test');

        $this->assertNotNull($link);
        $this->assertSame($latest->id, $link->work_report_id);
        $this->assertSame($production->id, $link->production_id);
        $this->assertSame(WorkReportFlowProduction::STAGE_FISCALIZATION, $link->stage);
        $this->assertSame(WorkReportFlowProduction::SCOPE_GENERAL, $link->final_scope);
        $this->assertDatabaseMissing('work_report_flow_productions', [
            'work_report_id' => $older->id,
            'production_id' => $production->id,
        ]);
    }

    public function test_does_not_link_partial_production_to_final_flow(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4000000002']);
        $service = Service::create(['service' => 'Pagamento']);

        WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-03',
            'informed_at' => '2026-08-03 08:00:00',
        ]);

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'att_at' => '2026-08-03 09:00:00',
            'completed' => false,
            'partial' => true,
        ]);

        $link = app(WorkReportFlowProductionLinker::class)->linkPayment($production, 'test');

        $this->assertNull($link);
        $this->assertDatabaseCount('work_report_flow_productions', 0);
    }

    public function test_links_parallel_scoped_work_reports_to_the_matching_scope_report(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $user = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4000000003', 'type_note' => 1]);
        $service = Service::create(['service' => 'Fiscalização']);

        $order170 = Order::create([
            'note_id' => $note->id,
            'ordem' => '170000000001',
            'statusSist' => 'ABER',
        ]);

        $order180 = Order::create([
            'note_id' => $note->id,
            'ordem' => '180000000001',
            'statusSist' => 'ABER',
        ]);

        $networkReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-01',
            'informed_at' => '2026-08-01 08:00:00',
            'selected_final_scopes' => ['network'],
        ]);
        $networkReport->Orders()->sync([$order170->id]);

        $connectionReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-03',
            'informed_at' => '2026-08-03 08:00:00',
            'selected_final_scopes' => ['connection'],
        ]);
        $connectionReport->Orders()->sync([$order180->id]);

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'att_at' => '2026-08-03 09:00:00',
            'completed' => false,
            'partial' => false,
        ]);

        $networkLink = app(WorkReportFlowProductionLinker::class)
            ->linkFiscalization($production, 'test', [], WorkReportFlowProduction::SCOPE_NETWORK);
        $connectionLink = app(WorkReportFlowProductionLinker::class)
            ->linkFiscalization($production, 'test', [], WorkReportFlowProduction::SCOPE_CONNECTION);

        $this->assertSame($networkReport->id, $networkLink?->work_report_id);
        $this->assertSame($connectionReport->id, $connectionLink?->work_report_id);
    }

    public function test_splitter_keeps_closing_scope_on_original_and_moves_remaining_scope_to_mirror_production(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4000000004', 'type_note' => 1]);
        $service = Service::create(['service' => 'Fiscalização']);

        $networkReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-01',
            'informed_at' => '2026-08-01 08:00:00',
            'selected_final_scopes' => ['network'],
        ]);

        $connectionReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-03',
            'informed_at' => '2026-08-03 08:00:00',
            'selected_final_scopes' => ['connection'],
        ]);

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'dispatch_by' => $user->id,
            'att_by' => $user->id,
            'status' => 3,
            'att_at' => '2026-08-03 09:00:00',
            'completed' => false,
            'partial' => false,
        ]);

        WorkReportFlowProduction::create([
            'work_report_id' => $networkReport->id,
            'production_id' => $production->id,
            'stage' => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope' => WorkReportFlowProduction::SCOPE_NETWORK,
            'is_current' => true,
            'source' => 'test',
        ]);

        WorkReportFlowProduction::create([
            'work_report_id' => $connectionReport->id,
            'production_id' => $production->id,
            'stage' => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope' => WorkReportFlowProduction::SCOPE_CONNECTION,
            'is_current' => true,
            'source' => 'test',
        ]);

        $mirror = app(WorkReportScopedProductionSplitter::class)->splitRemainingScopes(
            $production,
            WorkReportFlowProduction::STAGE_FISCALIZATION,
            [WorkReportFlowProduction::SCOPE_NETWORK]
        );

        $this->assertNotNull($mirror);
        $this->assertNotSame($production->id, $mirror->id);
        $this->assertFalse((bool) $mirror->completed);

        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $networkReport->id,
            'production_id' => $production->id,
            'final_scope' => WorkReportFlowProduction::SCOPE_NETWORK,
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $connectionReport->id,
            'production_id' => $production->id,
            'final_scope' => WorkReportFlowProduction::SCOPE_CONNECTION,
            'is_current' => false,
            'reverse_reason' => 'split_remaining_scope',
        ]);

        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $connectionReport->id,
            'production_id' => $mirror->id,
            'final_scope' => WorkReportFlowProduction::SCOPE_CONNECTION,
            'is_current' => true,
            'source' => 'split_remaining_scope',
        ]);
    }
}
