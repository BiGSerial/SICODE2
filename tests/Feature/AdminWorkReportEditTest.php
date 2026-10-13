<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\Control\WorkReportEdit;
use App\Models\{Company, Note, Production, Service, User, WorkReport, WorkReportFlowProduction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminWorkReportEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_a_production_and_its_work_report_links(): void
    {
        $company = Company::create(['name' => 'Empresa de teste', 'email' => 'empresa@example.test']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '500000' . random_int(1000, 9999), 'dt_status' => now(), 'nstats' => 'NEW']);
        $service = Service::create([
            'uuid'    => (string) Str::uuid(),
            'service' => 'Medicao',
            'folder'  => 'medicao',
        ]);
        $workReport = WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'date'        => now()->toDateString(),
            'informed_at' => now(),
            'canceled'    => false,
        ]);
        $production = Production::create([
            'note_id'    => $note->id,
            'company_id' => $company->id,
            'service_id' => $service->uuid,
            'completed'  => false,
            'confirmed'  => false,
            'status'     => 2,
        ]);
        $link = WorkReportFlowProduction::create([
            'work_report_id' => $workReport->id,
            'production_id'  => $production->id,
            'stage'          => WorkReportFlowProduction::STAGE_PAYMENT,
            'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
            'is_current'     => true,
            'source'         => 'test',
        ]);

        Livewire::test(WorkReportEdit::class)
            ->call('getInfoResponse', $workReport)
            ->call('requestDeleteProduction', $production->id)
            ->call('deleteProduction');

        $this->assertDatabaseMissing('productions', ['id' => $production->id]);
        $this->assertDatabaseMissing('work_report_flow_productions', ['id' => $link->id]);
    }
}
