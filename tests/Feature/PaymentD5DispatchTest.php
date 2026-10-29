<?php

namespace Tests\Feature;

use App\Models\{Company, Contract, FiveNote, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use App\Services\Dispatch\DispatchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentD5DispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_dispatch_for_informe_links_its_own_pending_d5(): void
    {
        [$note, $report, $service, $company, $actor] = $this->fixture();
        $five                                        = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '']);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [WorkReportFlowProduction::SCOPE_NETWORK],
            $report->id
        );

        $this->assertTrue((bool) $production->fresh()->dfive);
        $this->assertSame([$five->id], $production->fresh()->fiveNotes()->pluck('five_notes.id')->all());
        $this->assertFalse($five->fresh()->isAwaitingPaymentCreation());
    }

    public function test_payment_dispatch_for_informe_without_d5_stays_a_normal_production(): void
    {
        [$note, $report, $service, $company, $actor] = $this->fixture();
        // D5 legada já despachada não pode virar D5 do informe novo.
        FiveNote::create(['note_id' => $note->id, 'note_d5' => 'L1', 'visible_partner' => true, 'is_completed' => true]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [WorkReportFlowProduction::SCOPE_NETWORK],
            $report->id
        );

        $this->assertFalse((bool) $production->fresh()->dfive);
        $this->assertSame(0, $production->fresh()->fiveNotes()->count());
    }

    public function test_payment_dispatch_accepts_supervisioned_passive_d5_without_informe(): void
    {
        [$note, , $service, $company, $actor] = $this->fixture();
        $five                                 = FiveNote::create([
            'note_id'          => $note->id,
            'note_d5'          => 'P1',
            'isPassive'        => true,
            'is_completed'     => true,
            'is_supervisioned' => true,
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [],
            null,
            null,
            $five->id
        );

        $this->assertTrue((bool) $production->fresh()->dfive);
        $this->assertSame([$five->id], $production->fresh()->fiveNotes()->pluck('five_notes.id')->all());
    }

    public function test_passive_d5_dispatch_ignores_modal_scope_and_existing_informe(): void
    {
        [$note, , $service, $company, $actor] = $this->fixture();
        $five                                 = FiveNote::create([
            'note_id'          => $note->id,
            'note_d5'          => 'P2',
            'isPassive'        => true,
            'is_completed'     => true,
            'is_supervisioned' => true,
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [WorkReportFlowProduction::SCOPE_NETWORK],
            null,
            null,
            $five->id
        );

        $this->assertTrue((bool) $production->fresh()->dfive);
        $this->assertSame([$five->id], $production->fresh()->fiveNotes()->pluck('five_notes.id')->all());
    }

    public function test_payment_dispatch_for_cycle_one_accepts_explicit_pending_d5_of_the_informe(): void
    {
        [$note, $report, $service, $company, $actor] = $this->fixture();
        $five                                        = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '']);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [WorkReportFlowProduction::SCOPE_NETWORK],
            $report->id,
            null,
            $five->id
        );

        $this->assertTrue((bool) $production->fresh()->dfive);
        $this->assertSame([$five->id], $production->fresh()->fiveNotes()->pluck('five_notes.id')->all());
    }

    public function test_cycle_one_pending_d5_accepts_new_activity_when_previous_production_is_finished(): void
    {
        [$note, $report, $service, $company, $actor] = $this->fixture();
        $five                                        = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '']);
        $previous                                    = $this->paymentProductionFor($note, $service, $company, $actor, $five, true);
        WorkReportFlowProduction::create([
            'work_report_id' => $report->id, 'production_id' => $previous->id,
            'stage'          => WorkReportFlowProduction::STAGE_PAYMENT, 'is_current' => true, 'linked_at' => now(),
        ]);

        $production = app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [WorkReportFlowProduction::SCOPE_NETWORK],
            $report->id,
            null,
            $five->id
        );

        $this->assertNotSame($previous->id, $production->id);
        $this->assertTrue((bool) $production->fresh()->dfive);
        $this->assertSame([$five->id], $production->fresh()->fiveNotes()->pluck('five_notes.id')->all());
    }

    public function test_cycle_one_pending_d5_is_blocked_by_open_production_of_the_same_d5(): void
    {
        [$note, $report, $service, $company, $actor] = $this->fixture();
        $five                                        = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '']);
        $this->paymentProductionFor($note, $service, $company, $actor, $five, false);

        $this->expectException(\App\Services\Dispatch\DispatchException::class);

        app(DispatchWorkflowService::class)->dispatchToUser(
            $note,
            $service,
            $company,
            $actor,
            $actor,
            null,
            [WorkReportFlowProduction::SCOPE_NETWORK],
            $report->id,
            null,
            $five->id
        );
    }

    private function paymentProductionFor(Note $note, Service $service, Company $company, User $user, FiveNote $five, bool $completed): Production
    {
        $production = Production::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
            'dt_note' => '2026-09-10 08:00:00', 'status_note' => 'NEW', 'status' => $completed ? 5 : 2, 'completed' => $completed, 'dfive' => true,
        ]);
        $five->productions()->syncWithoutDetaching([$production->id]);

        return $production;
    }

    private function fixture(): array
    {
        $company                = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $contract               = new Contract();
        $contract->company_id   = $company->id;
        $contract->number       = 'PAY-001';
        $contract->service      = true;
        $contract->construction = false;
        $contract->date_end     = now()->addYear()->toDateString();
        $contract->save();
        $actor = User::factory()->create(['contract' => true, 'company_id' => $company->id]);
        $actor->Employee()->create(['contract_id' => $contract->id, 'service_id' => null]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $note    = Note::create(['note' => '4000000007', 'type_note' => 1, 'dt_status' => '2026-08-30 08:00:00', 'nstats' => 'NEW']);
        $order   = Order::create(['note_id' => $note->id, 'ordem' => '1700003900', 'statusSist' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0030', 'status' => 'CONF']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0040', 'status' => 'LIB']);
        $report = WorkReport::create([
            'note_id'               => $note->id, 'company_id' => $company->id, 'user_id' => $actor->id,
            'date'                  => '2026-09-10', 'informed_at' => '2026-09-10 08:00:00',
            'rejected'              => false, 'canceled' => false,
            'selected_final_scopes' => [WorkReportFlowProduction::SCOPE_NETWORK],
        ]);
        $report->Orders()->sync([$order->id]);

        return [$note, $report, $service, $company, $actor];
    }
}
