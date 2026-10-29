<?php

namespace Tests\Feature;

use App\Models\{Adsform, Company, FiveNote, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncNoteInformFlowsD5AssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_final_report_uses_only_its_explicitly_linked_d5(): void
    {
        $company      = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user         = User::factory()->create(['company_id' => $company->id]);
        $note         = Note::create(['note' => '4000009920']);
        $firstReport  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $secondReport = $this->workReport($note, $company, $user, '2026-10-03 08:00:00');
        $firstD5      = FiveNote::create([
            'note_id'        => $note->id,
            'work_report_id' => $firstReport->id,
            'company_id'     => $company->id,
            'note_d5'        => '17500001',
        ]);
        $secondD5 = FiveNote::create([
            'note_id'        => $note->id,
            'work_report_id' => $secondReport->id,
            'company_id'     => $company->id,
            'note_d5'        => '17500002',
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'         => "work_report:{$firstReport->id}",
            'five_note_id'     => $firstD5->id,
            'five_note_number' => '17500001',
        ]);
        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'         => "work_report:{$secondReport->id}",
            'five_note_id'     => $secondD5->id,
            'five_note_number' => '17500002',
        ]);
    }

    public function test_it_exports_the_preferred_operation_20_and_ads_amount(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000009921']);
        $report  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');

        foreach ([
            ['1500000001', '2026-09-01 08:00:00'],
            ['1930000001', '2026-09-02 08:00:00'],
            ['1800000001', '2026-09-03 08:00:00'],
        ] as [$number, $finishedAt]) {
            $order = Order::create(['note_id' => $note->id, 'ordem' => $number]);
            $report->Orders()->attach($order->id);
            Operation::create(['order_id' => $order->id, 'operacao' => '0020', 'fimReal' => $finishedAt]);
        }

        Adsform::create([
            'work_report_id' => $report->id,
            'note_id'        => $note->id,
            'user_id'        => $user->id,
            'amount'         => 1234.56,
            'partial'        => false,
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'          => "work_report:{$report->id}",
            'op20_order_number' => '1800000001',
            'op20_finished_at'  => '2026-09-03 08:00:00',
            'ads_amount'        => 1234.56,
        ]);

        $fallbackReport = $this->workReport($note, $company, $user, '2026-10-02 08:00:00');

        foreach ([
            ['1500000002', '2026-09-04 08:00:00'],
            ['1930000002', '2026-09-05 08:00:00'],
        ] as [$number, $finishedAt]) {
            $order = Order::create(['note_id' => $note->id, 'ordem' => $number]);
            $fallbackReport->Orders()->attach($order->id);
            Operation::create(['order_id' => $order->id, 'operacao' => '0020', 'fimReal' => $finishedAt]);
        }

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'          => "work_report:{$fallbackReport->id}",
            'op20_order_number' => '1930000002',
            'op20_finished_at'  => '2026-09-05 08:00:00',
        ]);
    }

    public function test_it_keeps_sap_confirmation_and_finished_dates_separate_from_production_dates(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000009922']);
        $report  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $order   = Order::create(['note_id' => $note->id, 'ordem' => '1800000003']);
        $report->Orders()->attach($order->id);

        Operation::create([
            'order_id' => $order->id,
            'operacao' => '0030',
            'status'   => 'CNPA',
            'fimReal'  => '2026-09-06 10:00:00',
        ]);
        Operation::create([
            'order_id' => $order->id,
            'operacao' => '0050',
            'status'   => 'CONF',
            'fimReal'  => '2026-09-07 11:00:00',
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                       => "work_report:{$report->id}",
            'fiscalization_closed_in_sap'    => 0,
            'fiscalization_closed_in_sap_at' => '2026-09-06 10:00:00',
            'measurement_closed_in_sap'      => 1,
            'measurement_closed_in_sap_at'   => '2026-09-07 11:00:00',
            'measurement_completed_at'       => null,
            'measurement_exited_at'          => null,
        ]);
    }

    public function test_it_uses_the_latest_fiscalization_and_measurement_when_there_is_no_d5(): void
    {
        $company        = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user           = User::factory()->create(['company_id' => $company->id]);
        $note           = Note::create(['note' => '4000009923']);
        $report         = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $fiscalService  = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $paymentService = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        $fiscalOlder = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $fiscalService->uuid, 'att_at' => '2026-10-02 08:00:00',
            'completed'  => true, 'completed_at' => '2026-10-03 08:00:00',
        ]);
        $fiscalNewer = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $fiscalService->uuid, 'att_at' => '2026-10-04 08:00:00',
        ]);
        $paymentOlder = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $paymentService->uuid, 'att_at' => '2026-10-05 08:00:00',
            'completed'  => true, 'completed_at' => '2026-10-06 08:00:00',
        ]);
        $paymentNewer = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $paymentService->uuid, 'att_at' => '2026-10-07 08:00:00',
            'confirmed'  => true, 'confirmed_at' => '2026-10-08 08:00:00',
        ]);

        foreach ([[$fiscalOlder, 'fiscalization'], [$fiscalNewer, 'fiscalization'], [$paymentOlder, 'payment'], [$paymentNewer, 'payment']] as [$production, $stage]) {
            WorkReportFlowProduction::create([
                'work_report_id' => $report->id,
                'production_id'  => $production->id,
                'stage'          => $stage,
                'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
                'is_current'     => true,
                'linked_at'      => now(),
            ]);
        }

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        // Sem D5: vale sempre a ultima Fiscalizacao e a ultima Medicao do informe.
        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                    => "work_report:{$report->id}",
            'fiscalization_production_id' => $fiscalNewer->id,
            'fiscalization_entered_at'    => '2026-10-04 08:00:00',
            'fiscalization_completed_at'  => null,
            'measurement_production_id'   => $paymentNewer->id,
            'measurement_entered_at'      => '2026-10-07 08:00:00',
            'measurement_completed_at'    => null,
            'measurement_exited_at'       => '2026-10-08 08:00:00',
        ]);
    }

    public function test_incremental_sync_recalculates_final_flow_when_production_link_changes(): void
    {
        Carbon::setTestNow('2026-10-01 08:00:00');

        try {
            $company                       = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
            $user                          = User::factory()->create(['company_id' => $company->id]);
            $note                          = Note::create(['note' => '4000009924', 'type_note' => 1]);
            $networkOrder                  = Order::create(['note_id' => $note->id, 'ordem' => '1700000004']);
            $report                        = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
            $report->selected_final_scopes = ['network'];
            $report->save();
            $report->Orders()->attach($networkOrder->id);
            $fiscalService  = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
            $paymentService = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

            $fiscal = Production::create([
                'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
                'service_id' => $fiscalService->uuid, 'att_at' => '2026-10-01 09:00:00',
            ]);
            $payment = Production::create([
                'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
                'service_id' => $paymentService->uuid, 'att_at' => '2026-10-01 10:00:00',
            ]);

            $fiscalLink = WorkReportFlowProduction::create([
                'work_report_id' => $report->id, 'production_id' => $fiscal->id,
                'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
                'final_scope'    => WorkReportFlowProduction::SCOPE_NETWORK,
                'is_current'     => false,
            ]);
            $paymentLink = WorkReportFlowProduction::create([
                'work_report_id' => $report->id, 'production_id' => $payment->id,
                'stage'          => WorkReportFlowProduction::STAGE_PAYMENT,
                'final_scope'    => WorkReportFlowProduction::SCOPE_NETWORK,
                'is_current'     => false,
            ]);

            $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
                ->assertSuccessful();

            $this->assertDatabaseHas('note_inform_flows', [
                'flow_key'                    => "work_report:{$report->id}:network",
                'fiscalization_production_id' => null,
                'measurement_production_id'   => null,
            ]);

            Carbon::setTestNow('2026-10-01 11:00:00');
            $fiscalLink->update(['is_current' => true]);
            $paymentLink->update(['is_current' => true]);

            $this->artisan('sicode:sync-note-inform-flows', ['--since' => '2026-10-01 10:30:00'])
                ->assertSuccessful();

            $this->assertDatabaseHas('note_inform_flows', [
                'flow_key'                    => "work_report:{$report->id}:network",
                'fiscalization_production_id' => $fiscal->id,
                'measurement_production_id'   => $payment->id,
                'fiscalization_entered_at'    => '2026-10-01 09:00:00',
                'measurement_entered_at'      => '2026-10-01 10:00:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_with_d5_uses_last_fiscalization_without_d5_and_first_measurement_with_d5(): void
    {
        $company        = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user           = User::factory()->create(['company_id' => $company->id]);
        $note           = Note::create(['note' => '4000009925']);
        $report         = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $fiveNote       = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'company_id' => $company->id, 'note_d5' => '17500003']);
        $fiscalService  = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
        $paymentService = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        $cycleOneFiscal = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $fiscalService->uuid, 'att_at' => '2026-10-02 08:00:00',
            'completed'  => true, 'completed_at' => '2026-10-03 08:00:00',
        ]);
        $cycleOnePayment = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $paymentService->uuid, 'att_at' => '2026-10-04 08:00:00',
            'completed'  => true, 'completed_at' => '2026-10-05 08:00:00',
            // A medicao do ciclo 1 e marcada como D5 (solicitacao) e continua sendo do ciclo 1.
            'dfive' => true, 'partial' => true,
        ]);
        // D5 atribuida pela atividade: mesmo com a flag partial, precisa aparecer.
        $d5Fiscal = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $fiscalService->uuid, 'att_at' => '2026-10-20 08:00:00',
            'completed'  => true, 'completed_at' => '2026-10-21 08:00:00', 'dfive' => true, 'partial' => true,
        ]);
        $d5Payment = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $paymentService->uuid, 'att_at' => '2026-10-22 08:00:00',
            'completed'  => true, 'completed_at' => '2026-10-23 08:00:00', 'dfive' => true, 'partial' => true,
        ]);

        foreach ([[$cycleOneFiscal, 'fiscalization'], [$d5Fiscal, 'fiscalization'], [$cycleOnePayment, 'payment'], [$d5Payment, 'payment']] as [$production, $stage]) {
            WorkReportFlowProduction::create([
                'work_report_id' => $report->id,
                'production_id'  => $production->id,
                'stage'          => $stage,
                'final_scope'    => WorkReportFlowProduction::SCOPE_GENERAL,
                'is_current'     => true,
                'linked_at'      => now(),
            ]);
        }

        $fiveNote->productions()->attach([$d5Fiscal->id, $d5Payment->id]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                       => "work_report:{$report->id}",
            'has_d5'                         => 1,
            'regulatory_deadline_applies'    => 1,
            'fiscalization_production_id'    => $cycleOneFiscal->id,
            'fiscalization_completed_at'     => '2026-10-03 08:00:00',
            'measurement_production_id'      => $cycleOnePayment->id,
            'measurement_completed_at'       => '2026-10-05 08:00:00',
            'd5_fiscalization_production_id' => $d5Fiscal->id,
            'd5_fiscalization_completed_at'  => '2026-10-21 08:00:00',
            'd5_measurement_production_id'   => $d5Payment->id,
            'd5_measurement_completed_at'    => '2026-10-23 08:00:00',
        ]);
    }

    public function test_single_scope_report_uses_fiscalization_linked_in_another_scope(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000009926', 'type_note' => 1]);
        $order   = Order::create(['note_id' => $note->id, 'ordem' => '1700000005']);
        $report  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $report->selected_final_scopes = ['network'];
        $report->save();
        $report->Orders()->attach($order->id);
        $fiscalService = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);

        $fiscal = Production::create([
            'note_id'    => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'service_id' => $fiscalService->uuid, 'att_at' => '2026-10-02 09:00:00',
            'completed'  => true, 'completed_at' => '2026-10-03 09:00:00',
        ]);
        WorkReportFlowProduction::create([
            'work_report_id' => $report->id, 'production_id' => $fiscal->id,
            'stage'          => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'final_scope'    => WorkReportFlowProduction::SCOPE_CONNECTION,
            'is_current'     => true, 'linked_at' => now(),
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                    => "work_report:{$report->id}:network",
            'fiscalization_production_id' => $fiscal->id,
            'fiscalization_completed_at'  => '2026-10-03 09:00:00',
        ]);
    }

    public function test_rejected_report_stays_in_the_flow_as_rejected_stage_and_does_not_cut_the_previous_cycle(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000009927']);
        $report  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $reject  = $this->workReport($note, $company, $user, '2026-10-05 08:00:00');
        $reject->update(['rejected' => true]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                    => "work_report:{$reject->id}",
            'active'                      => 1,
            'regulatory_deadline_applies' => 0,
            'current_stage'               => 'Informe Rejeitado',
        ]);
        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'                    => "work_report:{$report->id}",
            'regulatory_deadline_applies' => 1,
            'final_cycle_ended_at'        => null,
        ]);
    }

    private function workReport(Note $note, Company $company, User $user, string $informedAt): WorkReport
    {
        return WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'date'        => substr($informedAt, 0, 10),
            'informed_at' => $informedAt,
            'rejected'    => false,
            'canceled'    => false,
        ]);
    }
}
