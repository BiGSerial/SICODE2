<?php

namespace Tests\Feature;

use App\Enum\CancellationRequestScope;
use App\Enum\CancellationRequestStatus;
use App\Models\Note;
use App\Models\Order;
use App\Models\UncancellationRequest;
use App\Models\User;
use App\Models\WorkReport;
use App\Services\Payment\UncancellationRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UncancellationRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCanceledNoteWithOrders(int $count = 2): array
    {
        $actor = User::factory()->create(['can_dispatch' => true]);
        $note = Note::create([
            'note' => 'N' . rand(1000, 9999),
            'canceled' => true,
            'canceled_at' => now()->subDay(),
            'canceled_by' => $actor->id,
        ]);
        $orders = [];

        for ($i = 1; $i <= $count; $i++) {
            $orders[] = Order::create([
                'note_id' => $note->id,
                'ordem' => 'OV-' . $i,
                'canceled' => true,
                'canceled_at' => now()->subDay(),
                'canceled_by' => $actor->id,
            ]);
        }

        WorkReport::create([
            'note_id' => $note->id,
            'canceled' => true,
            'canceled_at' => now()->subDay(),
            'canceled_by' => $actor->id,
        ]);

        return [$note, $orders, $actor];
    }

    public function test_create_uncancellation_request_for_canceled_note(): void
    {
        [$note, $orders, $actor] = $this->makeCanceledNoteWithOrders();

        $request = (new UncancellationRequestService())->createRequest(
            $note,
            CancellationRequestScope::NOTE_FULL->value,
            [],
            $actor,
            'Reativar obra'
        );

        $this->assertDatabaseHas('uncancellation_requests', [
            'id' => $request->id,
            'note_id' => $note->id,
            'status' => CancellationRequestStatus::SUBMITTED->value,
        ]);
        $this->assertDatabaseCount('uncancellation_request_orders', count($orders));
        $this->assertDatabaseHas('uncancellation_request_events', [
            'uncancellation_request_id' => $request->id,
            'event' => 'submitted',
        ]);
    }

    public function test_block_duplicate_open_uncancellation_request(): void
    {
        [$note, , $actor] = $this->makeCanceledNoteWithOrders();
        $service = new UncancellationRequestService();

        $service->createRequest($note, CancellationRequestScope::NOTE_FULL->value, [], $actor, 'Primeira');

        $this->expectException(RuntimeException::class);
        $service->createRequest($note, CancellationRequestScope::NOTE_FULL->value, [], $actor, 'Duplicada');
    }

    public function test_finalize_uncancellation_reopens_note_orders_and_work_form(): void
    {
        [$note, $orders, $actor] = $this->makeCanceledNoteWithOrders();
        $service = new UncancellationRequestService();

        $request = $service->createRequest($note, CancellationRequestScope::NOTE_FULL->value, [], $actor, 'Reativar obra');
        $service->claimRequest($request, $actor);
        $service->finalizeDone($request, $actor);

        $this->assertFalse($note->fresh()->canceled);
        $this->assertNull($note->fresh()->canceled_at);
        $this->assertFalse($orders[0]->fresh()->canceled);
        $this->assertFalse($orders[1]->fresh()->canceled);
        $this->assertFalse($note->WorkFormAny()->first()->canceled);
        $this->assertDatabaseHas('uncancellation_requests', [
            'id' => $request->id,
            'status' => CancellationRequestStatus::DONE->value,
            'closure_type' => UncancellationRequest::CLOSURE_DONE,
        ]);
    }

    public function test_partial_uncancellation_reopens_only_selected_orders(): void
    {
        [$note, $orders, $actor] = $this->makeCanceledNoteWithOrders();
        $note->update(['canceled' => false, 'canceled_at' => null, 'canceled_by' => null]);
        $service = new UncancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            [$orders[0]->id],
            $actor,
            'Reativar ordem'
        );
        $service->claimRequest($request, $actor);
        $service->finalizeDone($request, $actor);

        $this->assertFalse($note->fresh()->canceled);
        $this->assertFalse($orders[0]->fresh()->canceled);
        $this->assertTrue($orders[1]->fresh()->canceled);
    }
}
