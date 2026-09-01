<?php

namespace Tests\Feature\Closure;

use App\Enum\{CancellationRequestScope, CancellationRequestStatus};
use App\Models\{CancellationCategory, CancellationRequest, ClosureCycle, Note, Operation, Order, User};
use App\Services\Closure\ClosureTargetFreezer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosureTargetFreezerTest extends TestCase
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
            'fimReal'  => '2026-08-15',
        ], $overrides));
    }

    public function test_eligible_order_enters_meta(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $this->makeOp20($order);

        $result = (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseHas('closure_targets', [
            'order_id' => $order->id,
            'note_id'  => $note->id,
        ]);
        $this->assertSame(1, $result['created']);
        $this->assertSame(ClosureCycle::STATUS_OPEN, $result['cycle']->status);
    }

    public function test_order_with_aber_status_sist_not_eligible(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note, ['statusSist' => 'ABER CNPA']);
        $this->makeOp20($order);

        (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseMissing('closure_targets', ['order_id' => $order->id]);
    }

    public function test_order_with_bloq_status_sist_not_eligible(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note, ['statusSist' => 'BLOQ ENTE']);
        $this->makeOp20($order);

        (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseMissing('closure_targets', ['order_id' => $order->id]);
    }

    public function test_order_with_fimreal_but_not_conf_status_not_eligible(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $this->makeOp20($order, ['status' => 'CNPA']);

        (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseMissing('closure_targets', ['order_id' => $order->id]);
    }

    public function test_freeze_twice_without_lock_injects_only_new_orders_and_keeps_open(): void
    {
        $note   = $this->makeNote();
        $orderA = $this->makeOrder($note);
        $this->makeOp20($orderA);

        $freezer = new ClosureTargetFreezer();
        $freezer->freeze(2026, 9, true, null, false);

        $this->assertDatabaseCount('closure_targets', 1);

        $orderB = $this->makeOrder($note);
        $this->makeOp20($orderB);

        $result = $freezer->freeze(2026, 9, true, null, false);

        $this->assertDatabaseCount('closure_targets', 2);
        $this->assertSame(1, $result['created']);
        $this->assertSame(ClosureCycle::STATUS_OPEN, $result['cycle']->fresh()->status);
    }

    public function test_freeze_with_lock_locks_cycle_and_second_call_is_noop(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $this->makeOp20($order);

        $freezer = new ClosureTargetFreezer();
        $freezer->freeze(2026, 9, true, null, true);

        $this->assertDatabaseCount('closure_targets', 1);
        $this->assertSame(ClosureCycle::STATUS_FROZEN, ClosureCycle::where('year', 2026)->where('month', 9)->value('status'));

        $orderB = $this->makeOrder($note);
        $this->makeOp20($orderB);

        $result = $freezer->freeze(2026, 9, true, null, false);

        $this->assertTrue($result['already_frozen']);
        $this->assertDatabaseCount('closure_targets', 1);
    }

    public function test_canceled_order_not_eligible(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note, ['canceled' => true]);
        $this->makeOp20($order);

        (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseMissing('closure_targets', ['order_id' => $order->id]);
    }

    public function test_order_of_fully_canceled_note_not_eligible(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $this->makeOp20($order);
        $user     = User::factory()->create();
        $category = CancellationCategory::create([
            'name'   => 'Pedido do Cliente',
            'slug'   => 'pedido-do-cliente-' . rand(1000, 9999),
            'active' => true,
        ]);

        CancellationRequest::create([
            'note_id'      => $note->id,
            'scope'        => CancellationRequestScope::NOTE_FULL->value,
            'category_id'  => $category->id,
            'requested_by' => $user->id,
            'status'       => CancellationRequestStatus::DONE->value,
        ]);

        (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseMissing('closure_targets', ['order_id' => $order->id]);
    }

    public function test_order_without_valid_note_not_eligible(): void
    {
        $noteZero = $this->makeNote(['note' => '0']);
        $orderA   = $this->makeOrder($noteZero);
        $this->makeOp20($orderA);

        $noteEmpty = $this->makeNote(['note' => '']);
        $orderB    = $this->makeOrder($noteEmpty);
        $this->makeOp20($orderB);

        (new ClosureTargetFreezer())->freeze(2026, 9, true);

        $this->assertDatabaseMissing('closure_targets', ['order_id' => $orderA->id]);
        $this->assertDatabaseMissing('closure_targets', ['order_id' => $orderB->id]);
    }
}
