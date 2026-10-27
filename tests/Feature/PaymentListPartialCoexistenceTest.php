<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Payment\Main;
use App\Models\{Note, Operation, Order, Partial, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PaymentListPartialCoexistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_in_measurement_coexists_with_eligible_final_informe(): void
    {
        $note  = Note::create(['note' => '4000000011', 'type_note' => 1, 'dt_status' => '2026-08-30 08:00:00', 'nstats' => 'NEW']);
        $order = Order::create(['note_id' => $note->id, 'ordem' => '1700003911', 'statusSist' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0030', 'status' => 'CONF']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0040', 'status' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0050', 'status' => 'LIB']);

        $report     = new WorkReport(['note_id' => $note->id]);
        $report->id = 1;
        $report->setRelation('Orders', collect([$order->load('Operations')]));
        $report->setRelation('FiveNote', null);

        $partial     = new Partial(['note_id' => $note->id, 'allow' => true, 'deny' => false, 'supervision' => true, 'payment' => false]);
        $partial->id = 102;

        $note->setRelation('WorkForms', collect([$report]));
        $note->setRelation('FiveNote', null);
        $note->setRelation('FiveNotes', collect());
        $note->setRelation('Partials', collect([$partial]));

        $method = new ReflectionMethod(Main::class, 'expandPaymentRows');
        $method->setAccessible(true);
        $rows = $method->invoke(app(Main::class), collect([$note]));

        $keys = $rows->map(fn ($row) => $row->payment_context_key)->all();

        $this->assertSame([$note->id . ':1', $note->id . ':0:p102'], $keys);
        $partialRow = $rows->last();
        $this->assertNull($partialRow->WorkForm);
        $this->assertNull($partialRow->FiveNote);
        $this->assertSame(102, $partialRow->dispatch_partial_id);
    }
}
