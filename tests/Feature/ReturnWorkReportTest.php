<?php

namespace Tests\Feature;

use App\Http\Livewire\Production\Return\ReturnWork;
use App\Models\Company;
use App\Models\Note;
use App\Models\Order;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReturnWorkReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_selected_work_report_when_production_has_two_final_scopes(): void
    {
        [$user, $production, $networkReport, $connectionReport] = $this->productionWithTwoLinkedWorkReports();

        Livewire::actingAs($user)
            ->test(ReturnWork::class)
            ->call('toReturn', $production)
            ->set('selectedWorkReportIds', [(string) $networkReport->id])
            ->set('returnWork.category', 'DADOS INCORRETOS')
            ->set('returnWork.text_obs', 'Corrigir os dados do informe de rede.')
            ->call('save');

        $this->assertTrue((bool) $networkReport->fresh()->rejected);
        $this->assertFalse((bool) $connectionReport->fresh()->rejected);
        $this->assertDatabaseHas('return_works', [
            'work_report_id' => $networkReport->id,
            'category' => 'DADOS INCORRETOS',
        ]);
        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $networkReport->id,
            'production_id' => $production->id,
            'is_current' => false,
            'reverse_reason' => 'return_work_report',
        ]);
        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $connectionReport->id,
            'production_id' => $production->id,
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('productions', ['id' => $production->id]);
    }

    public function test_returns_all_selected_work_reports_and_removes_production_when_no_scope_remains(): void
    {
        [$user, $production, $networkReport, $connectionReport] = $this->productionWithTwoLinkedWorkReports();

        Livewire::actingAs($user)
            ->test(ReturnWork::class)
            ->call('toReturn', $production)
            ->set('selectedWorkReportIds', [(string) $networkReport->id, (string) $connectionReport->id])
            ->set('returnWork.category', 'DADOS INCORRETOS')
            ->set('returnWork.text_obs', 'Corrigir os dados dos informes.')
            ->call('save');

        $this->assertTrue((bool) $networkReport->fresh()->rejected);
        $this->assertTrue((bool) $connectionReport->fresh()->rejected);
        $this->assertDatabaseHas('return_works', ['work_report_id' => $networkReport->id]);
        $this->assertDatabaseHas('return_works', ['work_report_id' => $connectionReport->id]);
        $this->assertDatabaseMissing('productions', ['id' => $production->id]);
    }

    private function productionWithTwoLinkedWorkReports(): array
    {
        config(['sicode.ruleset' => 'sp']);

        $user = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4001951584', 'type_note' => 1]);
        $service = Service::create(['service' => 'Fiscalização']);

        $networkOrder = Order::create([
            'note_id' => $note->id,
            'ordem' => '170000000001',
            'statusSist' => 'ABER',
        ]);
        $connectionOrder = Order::create([
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
        $networkReport->Orders()->sync([$networkOrder->id]);

        $connectionReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-02',
            'informed_at' => '2026-08-02 08:00:00',
            'selected_final_scopes' => ['connection'],
        ]);
        $connectionReport->Orders()->sync([$connectionOrder->id]);

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
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

        return [$user, $production, $networkReport, $connectionReport];
    }
}
