<?php

namespace Tests\Feature\Closure;

use App\Models\{ClosureCycle, ClosureTarget, Note, Operation, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillTargetsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeNote(array $overrides = []): Note
    {
        return Note::create(array_merge([
            'note'      => (string) rand(1000000, 9999999),
            'type_note' => 1,
        ], $overrides));
    }

    private function makeOrder(Note $note, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'note_id'    => $note->id,
            'ordem'      => 'O' . rand(100000, 999999),
            'statusSist' => 'LIB CNPA',
            'canceled'   => false,
        ], $overrides));
    }

    private function makeOp20(Order $order, array $overrides = []): Operation
    {
        return Operation::create(array_merge([
            'order_id' => $order->id,
            'operacao' => '0020',
            'status'   => 'CONF',
            'fimReal'  => '2025-01-15',
        ], $overrides));
    }

    public function test_discovers_pending_months_and_locks_each_cycle(): void
    {
        $note = $this->makeNote();

        $orderJan = $this->makeOrder($note);
        $this->makeOp20($orderJan, ['fimReal' => '2025-01-15']);

        $orderMar = $this->makeOrder($note);
        $this->makeOp20($orderMar, ['fimReal' => '2025-03-10']);

        // fora do --until (mês de referência posterior ao limite) — não deve entrar nesta passada.
        $orderApr = $this->makeOrder($note);
        $this->makeOp20($orderApr, ['fimReal' => '2025-04-05']);

        $this->artisan('closure:backfill-targets', ['--freeze' => true, '--until' => '2025-04'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('closure_targets', ['order_id' => $orderJan->id]);
        $this->assertDatabaseHas('closure_targets', ['order_id' => $orderMar->id]);
        $this->assertDatabaseMissing('closure_targets', ['order_id' => $orderApr->id]);

        $cycleFeb = ClosureCycle::where('year', 2025)->where('month', 2)->first();
        $cycleApr = ClosureCycle::where('year', 2025)->where('month', 4)->first();

        $this->assertNotNull($cycleFeb);
        $this->assertNotNull($cycleApr);
        $this->assertSame(ClosureCycle::STATUS_FROZEN, $cycleFeb->status);
        $this->assertSame(ClosureCycle::STATUS_FROZEN, $cycleApr->status);
    }

    public function test_does_not_reprocess_orders_that_already_have_a_target(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $this->makeOp20($order, ['fimReal' => '2025-01-15']);

        $cycle = ClosureCycle::create(['year' => 2025, 'month' => 2, 'label' => '2025-02', 'status' => ClosureCycle::STATUS_FROZEN]);
        ClosureTarget::create([
            'closure_cycle_id' => $cycle->id,
            'order_id'         => $order->id,
            'note_id'          => $note->id,
            'entry_rule'       => 'manual_seed_for_test',
            'frozen_at'        => now(),
        ]);

        $this->artisan('closure:backfill-targets', ['--freeze' => true, '--until' => '2025-04'])
            ->assertExitCode(0);

        $this->assertSame(1, ClosureTarget::where('order_id', $order->id)->count());
    }

    public function test_dry_run_does_not_write_anything(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $this->makeOp20($order, ['fimReal' => '2025-01-15']);

        $this->artisan('closure:backfill-targets', ['--until' => '2025-04'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('closure_targets', 0);
        $this->assertDatabaseCount('closure_cycles', 0);
    }
}
