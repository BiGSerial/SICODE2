<?php

namespace Tests\Feature\Closure;

use App\Models\{ClosureCycle, ClosureTarget, Note, Order, User};
use App\Services\Closure\ClosureExceptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClosureExceptionServiceTest extends TestCase
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

    private function makeCycle(string $status = ClosureCycle::STATUS_FROZEN): ClosureCycle
    {
        return ClosureCycle::create([
            'year'   => 2026,
            'month'  => 9,
            'label'  => '2026-09',
            'status' => $status,
        ]);
    }

    public function test_registers_exception_even_when_cycle_is_frozen(): void
    {
        $note   = $this->makeNote();
        $order  = $this->makeOrder($note);
        $cycle  = $this->makeCycle(ClosureCycle::STATUS_FROZEN);
        $author = User::factory()->create();

        $target = (new ClosureExceptionService())->registerException($order, $cycle, 'Solicitação da diretoria', $author->id);

        $this->assertTrue($target->is_exception);
        $this->assertSame(ClosureTarget::ENTRY_RULE_EXCEPTION, $target->entry_rule);
        $this->assertSame(ClosureCycle::STATUS_FROZEN, $cycle->fresh()->status);
    }

    public function test_rejects_without_reason(): void
    {
        $note   = $this->makeNote();
        $order  = $this->makeOrder($note);
        $cycle  = $this->makeCycle();
        $author = User::factory()->create();

        $this->expectException(ValidationException::class);

        (new ClosureExceptionService())->registerException($order, $cycle, '   ', $author->id);
    }

    public function test_rejects_without_authorized_by(): void
    {
        $note  = $this->makeNote();
        $order = $this->makeOrder($note);
        $cycle = $this->makeCycle();

        $this->expectException(ValidationException::class);

        (new ClosureExceptionService())->registerException($order, $cycle, 'Justificativa', null);
    }

    public function test_rejects_canceled_order(): void
    {
        $note   = $this->makeNote();
        $order  = $this->makeOrder($note, ['canceled' => true]);
        $cycle  = $this->makeCycle();
        $author = User::factory()->create();

        $this->expectException(ValidationException::class);

        (new ClosureExceptionService())->registerException($order, $cycle, 'Justificativa', $author->id);
    }

    public function test_rejects_order_with_existing_target(): void
    {
        $note   = $this->makeNote();
        $order  = $this->makeOrder($note);
        $cycle  = $this->makeCycle();
        $author = User::factory()->create();

        ClosureTarget::create([
            'closure_cycle_id' => $cycle->id,
            'order_id'         => $order->id,
            'note_id'          => $note->id,
            'entry_rule'       => 'lib_op20_conf_fimreal_v1',
            'frozen_at'        => now(),
        ]);

        $this->expectException(ValidationException::class);

        (new ClosureExceptionService())->registerException($order, $cycle, 'Justificativa', $author->id);
    }

    public function test_rejects_order_without_valid_note(): void
    {
        $note   = $this->makeNote(['note' => '0']);
        $order  = $this->makeOrder($note);
        $cycle  = $this->makeCycle();
        $author = User::factory()->create();

        $this->expectException(ValidationException::class);

        (new ClosureExceptionService())->registerException($order, $cycle, 'Justificativa', $author->id);
    }

    public function test_rejects_order_already_closed_in_sap(): void
    {
        $note   = $this->makeNote();
        $order  = $this->makeOrder($note, ['statusSist' => 'ENCE CONF']);
        $cycle  = $this->makeCycle();
        $author = User::factory()->create();

        $this->expectException(ValidationException::class);

        (new ClosureExceptionService())->registerException($order, $cycle, 'Justificativa', $author->id);
    }
}
