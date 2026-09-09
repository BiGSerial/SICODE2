<?php

namespace Tests\Unit;

use App\Models\FiveNote;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Production;
use App\Models\Service;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use App\Services\WorkReports\WorkReportStatusResolver;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class WorkReportStatusResolverTest extends TestCase
{
    private WorkReportStatusResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new WorkReportStatusResolver();
    }

    public function test_no_fiscalization_and_no_payment_is_inform_without_ads(): void
    {
        $this->assertStatus('Informe', []);
    }

    public function test_fiscalization_in_progress_without_payment(): void
    {
        $this->assertStatus('Em Fiscalização', [
            'has_ads' => true,
            'normal_fiscal_associated' => true,
            'normal_fiscal_finished' => false,
        ]);
    }

    public function test_fiscalization_finished_without_payment(): void
    {
        $this->assertStatus('Aguardando Medição', [
            'has_ads' => true,
            'normal_fiscal_associated' => true,
            'normal_fiscal_finished' => true,
        ]);
    }

    public function test_waiting_payment_without_fiscalization_is_inconsistent(): void
    {
        $this->assertStatus('Medição Inconsistente', [
            'normal_payment_associated' => true,
            'normal_payment_finished' => false,
        ]);
    }

    public function test_payment_in_progress_without_fiscalization_is_inconsistent(): void
    {
        $this->assertStatus('Medição Inconsistente', [
            'normal_payment_associated' => true,
            'normal_payment_finished' => false,
        ]);
    }

    public function test_payment_finished_without_fiscalization_is_inconsistent(): void
    {
        $this->assertStatus('Medição Inconsistente', [
            'normal_payment_associated' => true,
            'normal_payment_finished' => true,
            'd5_completed' => true,
        ]);
    }

    public function test_d5_associated_to_note_but_not_production(): void
    {
        $this->assertStatus('Aguardando Despacho D5', [
            'normal_fiscal_associated' => true,
            'normal_fiscal_finished' => true,
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => false,
        ]);
    }

    public function test_d5_associated_without_d5_fiscalization(): void
    {
        $this->assertStatus('Aguardando Fiscalização D5', [
            'normal_fiscal_associated' => true,
            'normal_fiscal_finished' => true,
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => true,
        ]);
    }

    public function test_d5_fiscalization_in_progress(): void
    {
        $this->assertStatus('Fiscalização D5', [
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => true,
            'd5_fiscal_associated' => true,
            'd5_fiscal_finished' => false,
        ]);
    }

    public function test_d5_fiscalization_finished_without_payment(): void
    {
        $this->assertStatus('Aguardando Medição D5', [
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => true,
            'd5_fiscal_associated' => true,
            'd5_fiscal_finished' => true,
        ]);
    }

    public function test_d5_payment_in_progress(): void
    {
        $this->assertStatus('Liberando Carta', [
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => true,
            'd5_fiscal_associated' => true,
            'd5_fiscal_finished' => true,
            'd5_payment_associated' => true,
            'd5_payment_finished' => false,
        ]);
    }

    public function test_payment_finished_with_d5_not_completed(): void
    {
        $this->assertStatus('Aguardando Resolução D5', [
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => true,
            'd5_fiscal_associated' => true,
            'd5_fiscal_finished' => true,
            'd5_payment_associated' => true,
            'd5_payment_finished' => true,
            'd5_completed' => false,
        ]);
    }

    public function test_d5_payment_before_latest_d5_fiscalization_does_not_close_current_cycle(): void
    {
        $fiscalService = new Service(['service' => 'Fiscalização']);
        $paymentService = new Service(['service' => 'Pagamento']);

        $firstD5Fiscal = new Production([
            'completed' => true,
            'partial' => false,
            'att_at' => '2026-04-02 17:25:55',
            'completed_at' => '2026-04-08 01:44:16',
        ]);
        $firstD5Fiscal->setRelation('Service', $fiscalService);

        $oldD5Payment = new Production([
            'id' => 20,
            'completed' => true,
            'confirmed' => true,
            'partial' => false,
            'dfive' => true,
            'att_at' => '2026-04-10 01:51:19',
            'completed_at' => '2026-04-10 02:00:59',
        ]);
        $oldD5Payment->setRelation('Service', $paymentService);

        $latestD5Fiscal = new Production([
            'id' => 30,
            'completed' => true,
            'partial' => false,
            'att_at' => '2026-05-27 14:29:20',
            'completed_at' => '2026-05-29 17:52:25',
        ]);
        $latestD5Fiscal->setRelation('Service', $fiscalService);

        $fiveNote = new FiveNote([
            'is_completed' => true,
            'is_payed' => true,
            'is_archived' => false,
        ]);
        $fiveNote->setRelation('productions', new EloquentCollection([$firstD5Fiscal, $oldD5Payment, $latestD5Fiscal]));

        $note = new \App\Models\Note();
        $note->setRelation('FiveNote', $fiveNote);

        $normalPaymentLink = new WorkReportFlowProduction([
            'stage' => WorkReportFlowProduction::STAGE_PAYMENT,
            'is_current' => true,
        ]);
        $normalPaymentLink->setRelation('Production', $oldD5Payment);

        $workReport = new WorkReport();
        $workReport->setRelation('FlowProductions', new EloquentCollection([$normalPaymentLink]));
        $workReport->setRelation('Note', $note);
        $workReport->setRelation('Adsform', null);

        $this->assertSame('Aguardando Medição D5', $this->resolver->resolve($workReport)['label']);
    }

    public function test_payment_finished_with_completed_d5_and_finished_fiscalizations(): void
    {
        $this->assertStatus('Finalizado', [
            'normal_fiscal_associated' => true,
            'normal_fiscal_finished' => true,
            'd5_associated_to_note' => true,
            'd5_associated_to_production' => true,
            'd5_completed' => true,
            'd5_fiscal_associated' => true,
            'd5_fiscal_finished' => true,
            'd5_payment_associated' => true,
            'd5_payment_finished' => true,
            'letter_released' => true,
        ]);
    }

    public function test_archived_d5_counts_as_released_letter_for_finalization(): void
    {
        $fiscalService = new Service(['service' => 'Fiscalização']);
        $paymentService = new Service(['service' => 'Pagamento']);

        $d5Fiscal = new Production([
            'completed' => true,
            'partial' => false,
        ]);
        $d5Fiscal->setRelation('Service', $fiscalService);

        $d5Payment = new Production([
            'completed' => false,
            'confirmed' => false,
            'partial' => false,
        ]);
        $d5Payment->setRelation('Service', $paymentService);

        $fiveNote = new FiveNote([
            'is_completed' => true,
            'is_payed' => false,
            'is_archived' => true,
        ]);
        $fiveNote->setRelation('productions', new EloquentCollection([$d5Fiscal, $d5Payment]));

        $note = new \App\Models\Note();
        $note->setRelation('FiveNote', $fiveNote);

        $workReport = new WorkReport();
        $workReport->setRelation('FlowProductions', new EloquentCollection());
        $workReport->setRelation('Note', $note);
        $workReport->setRelation('Adsform', null);

        $this->assertSame('Finalizado', $this->resolver->resolve($workReport)['label']);
    }

    public function test_partial_productions_are_ignored_for_final_work_report_status(): void
    {
        $service = new Service(['service' => 'Pagamento']);
        $partialPayment = new Production([
            'completed' => true,
            'confirmed' => true,
            'partial' => true,
        ]);
        $partialPayment->setRelation('Service', $service);

        $flowProduction = new WorkReportFlowProduction([
            'stage' => WorkReportFlowProduction::STAGE_PAYMENT,
            'is_current' => true,
        ]);
        $flowProduction->setRelation('Production', $partialPayment);

        $fiveNote = new FiveNote([
            'is_completed' => true,
            'is_payed' => true,
        ]);
        $fiveNote->setRelation('productions', new EloquentCollection([$partialPayment]));

        $note = new \App\Models\Note();
        $note->setRelation('FiveNote', $fiveNote);

        $workReport = new WorkReport();
        $workReport->setRelation('FlowProductions', new EloquentCollection([$flowProduction]));
        $workReport->setRelation('Note', $note);
        $workReport->setRelation('Adsform', null);

        $this->assertSame('Informe', $this->resolver->resolve($workReport)['label']);
    }

    public function test_sap_operation_30_open_waits_fiscalization_without_flow(): void
    {
        $this->assertSame('Aguardando Fiscalização', $this->resolveWithOperation('0030')['label']);
    }

    public function test_sap_operation_30_started_is_fiscalization_without_flow(): void
    {
        $this->assertSame('Em Fiscalização', $this->resolveWithOperation('0030', inicioReal: '2026-09-09')['label']);
    }

    public function test_sap_operation_30_finished_waits_payment_without_flow(): void
    {
        $this->assertSame('Aguardando Medição', $this->resolveWithOperation('0030', fimReal: '2026-09-09')['label']);
    }

    public function test_sap_operation_50_finished_is_payment_finished_without_flow(): void
    {
        $this->assertSame('Medição Finalizada', $this->resolveWithOperation('0050', fimReal: '2026-09-09')['label']);
    }

    public function test_sap_operation_60_finished_is_finalized_without_flow(): void
    {
        $this->assertSame('Finalizado', $this->resolveWithOperation('0060', fimReal: '2026-09-09')['label']);
    }

    public function test_sap_operation_ignores_non_main_order_prefixes(): void
    {
        $this->assertSame('Informe', $this->resolveWithOrderOperations([
            ['ordem' => '1900000001', 'operacao' => '0060', 'fimReal' => '2026-09-09'],
            ['ordem' => '1500000001', 'operacao' => '0060', 'fimReal' => '2026-09-09'],
        ])['label']);
    }

    public function test_sap_operation_uses_190_only_when_it_is_the_unique_order(): void
    {
        $this->assertSame('Finalizado', $this->resolveWithOrderOperations([
            ['ordem' => '1900000001', 'operacao' => '0060', 'fimReal' => '2026-09-09'],
        ])['label']);
    }

    public function test_sap_operation_uses_170_order(): void
    {
        $this->assertSame('Finalizado', $this->resolveWithOrderOperations([
            ['ordem' => '1900000001', 'operacao' => '0030'],
            ['ordem' => '1700000001', 'operacao' => '0060', 'fimReal' => '2026-09-09'],
        ])['label']);
    }

    public function test_sap_operation_prefers_170_when_it_exists_with_180(): void
    {
        $this->assertSame('Aguardando Fiscalização', $this->resolveWithOrderOperations([
            ['ordem' => '1700000001', 'operacao' => '0030'],
            ['ordem' => '1800000001', 'operacao' => '0060', 'fimReal' => '2026-09-09'],
        ])['label']);
    }

    public function test_sap_operation_prefers_180_when_it_exists_with_15_or_19(): void
    {
        $this->assertSame('Finalizado', $this->resolveWithOrderOperations([
            ['ordem' => '1500000001', 'operacao' => '0030'],
            ['ordem' => '1900000001', 'operacao' => '0030'],
            ['ordem' => '1800000001', 'operacao' => '0060', 'fimReal' => '2026-09-09'],
        ])['label']);
    }

    public function test_sap_operation_prefers_latest_200_order(): void
    {
        $this->assertSame('Finalizado', $this->resolveWithOrderOperations([
            ['ordem' => '2000000001', 'operacao' => '0030', 'id' => 1],
            ['ordem' => '2000000002', 'operacao' => '0060', 'fimReal' => '2026-09-09', 'id' => 2],
            ['ordem' => '1700000001', 'operacao' => '0030', 'id' => 3],
        ])['label']);
    }

    private function assertStatus(string $expected, array $state): void
    {
        $this->assertSame($expected, $this->resolver->resolveState($state)['label']);
    }

    private function resolveWithOperation(string $code, ?string $inicioReal = null, ?string $fimReal = null): array
    {
        return $this->resolveWithOrderOperations([
            [
                'ordem' => '1700000001',
                'operacao' => $code,
                'inicioReal' => $inicioReal,
                'fimReal' => $fimReal,
            ],
        ]);
    }

    private function resolveWithOrderOperations(array $rows): array
    {
        $orders = collect($rows)
            ->map(function (array $row) {
                $operation = new Operation([
                    'operacao' => $row['operacao'],
                    'inicioReal' => $row['inicioReal'] ?? null,
                    'fimReal' => $row['fimReal'] ?? null,
                ]);

                $order = new Order([
                    'ordem' => $row['ordem'],
                ]);
                $order->id = $row['id'] ?? null;
                $order->setRelation('Operations', new EloquentCollection([$operation]));

                return $order;
            });

        return $this->resolveWithOrders($orders);
    }

    private function resolveWithOrders($orders): array
    {
        $workReport = new WorkReport();
        $workReport->setRelation('Orders', new EloquentCollection($orders->all()));
        $workReport->setRelation('FlowProductions', new EloquentCollection());
        $workReport->setRelation('Adsform', null);
        $workReport->setRelation('Note', new \App\Models\Note());

        return $this->resolver->resolve($workReport);
    }
}
