<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use App\Services\WorkReports\WorkReportFlowProductionLinker;
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
}
