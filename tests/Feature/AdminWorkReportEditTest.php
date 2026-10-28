<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\Control\WorkReportEdit;
use App\Models\{Company, Note, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
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

    /** @return array{0: WorkReport, 1: Note} informe final EP no SP (split ligado) com uma ordem de Rede (150) e uma de Ligação */
    private function scopedWorkReport(string $note = '600000', array $orders = ['1500001', '9000001']): array
    {
        config(['sicode.ruleset' => 'sp']);

        $company = Company::create(['name' => 'Empresa ' . $note, 'email' => $note . '@example.test']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $noteRow = Note::create(['note' => $note . random_int(1000, 9999), 'dt_status' => now(), 'nstats' => 'NEW', 'type_note' => 1]);

        $workReport = WorkReport::create([
            'note_id'     => $noteRow->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'date'        => now()->toDateString(),
            'informed_at' => now(),
            'canceled'    => false,
        ]);

        $ids = collect($orders)->map(fn ($ordem) => Order::create(['note_id' => $noteRow->id, 'ordem' => $ordem, 'statusSist' => 'LIB'])->id)->all();
        $workReport->Orders()->sync($ids);

        return [$workReport->fresh(), $noteRow];
    }

    public function test_it_lets_the_admin_choose_the_final_scope_and_resyncs_the_note(): void
    {
        [$workReport] = $this->scopedWorkReport();

        $component = Livewire::test(WorkReportEdit::class)->call('getInfoResponse', $workReport);
        $this->assertSame('auto', $component->get('finalScopeChoice'));

        $component->set('finalScopeChoice', 'network')->call('saveFinalScope');
        $this->assertSame(['network'], $workReport->fresh()->selected_final_scopes);

        $component->set('finalScopeChoice', 'both')->call('saveFinalScope');
        $this->assertSame(['network', 'connection'], $workReport->fresh()->selected_final_scopes);

        $component->set('finalScopeChoice', 'connection')->call('saveFinalScope');
        $this->assertSame(['connection'], $workReport->fresh()->selected_final_scopes);
        $this->assertSame('connection', $component->get('finalScopeChoice'));

        $component->set('finalScopeChoice', 'auto')->call('saveFinalScope');
        $this->assertNull($workReport->fresh()->selected_final_scopes);

        // o fluxo consolidado da nota foi recalculado para o escopo escolhido
        $component->set('finalScopeChoice', 'network')->call('saveFinalScope');
        $this->assertDatabaseHas('note_inform_flows', ['flow_key' => 'work_report:' . $workReport->id . ':network', 'final_scope' => 'network', 'active' => true]);
        $this->assertDatabaseMissing('note_inform_flows', ['flow_key' => 'work_report:' . $workReport->id . ':connection', 'active' => true]);
    }

    public function test_it_applies_the_chosen_scope_on_the_main_save_too(): void
    {
        [$workReport] = $this->scopedWorkReport();

        Livewire::test(WorkReportEdit::class)
            ->call('getInfoResponse', $workReport)
            ->set('finalScopeChoice', 'connection')
            ->call('save');

        $this->assertSame(['connection'], $workReport->fresh()->selected_final_scopes);
    }

    public function test_it_refuses_a_scope_the_orders_do_not_support(): void
    {
        [$workReport] = $this->scopedWorkReport('610000', ['9000001', '9000002']); // só Ligação

        Livewire::test(WorkReportEdit::class)
            ->call('getInfoResponse', $workReport)
            ->set('finalScopeChoice', 'network')
            ->call('saveFinalScope')
            ->assertDispatchedBrowserEvent('swal');

        $this->assertNull($workReport->fresh()->selected_final_scopes);

        Livewire::test(WorkReportEdit::class)
            ->call('getInfoResponse', $workReport)
            ->set('finalScopeChoice', 'both')
            ->call('saveFinalScope');

        $this->assertNull($workReport->fresh()->selected_final_scopes);
    }

    public function test_it_does_not_offer_scopes_when_the_unit_does_not_split(): void
    {
        [$workReport] = $this->scopedWorkReport('620000');
        config(['sicode.ruleset' => 'es']); // ES: comportamento histórico, sem separação

        $component = Livewire::test(WorkReportEdit::class)->call('getInfoResponse', $workReport);

        $this->assertFalse($component->get('finalScopeOptions')['split']);

        $component->set('finalScopeChoice', 'network')->call('saveFinalScope');
        $this->assertNull($workReport->fresh()->selected_final_scopes);
    }
}
