<?php

namespace Tests\Feature;

use App\Models\CancellationCategory;
use App\Models\CancellationRequest;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Enum\CancellationRequestScope;
use App\Enum\CancellationEngineerApprovalStatus;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CancellationRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function makeNoteWithOrders(int $count = 2): array
    {
        $note = Note::create(['note' => 'N' . rand(1000, 9999)]);
        $orders = [];
        for ($i = 1; $i <= $count; $i++) {
            $orders[] = Order::create([
                'note_id' => $note->id,
                'ordem' => 'OV-' . $i,
            ]);
        }

        return [$note, $orders];
    }

    private function makeCategory(array $override = []): CancellationCategory
    {
        return CancellationCategory::create(array_merge([
            'name' => 'Pedido do Cliente',
            'slug' => 'pedido-do-cliente',
            'active' => true,
            'require_evidence' => true,
            'min_evidence_files' => 1,
        ], $override));
    }

    public function test_create_request_with_required_attachments(): void
    {
        Storage::fake('public');

        [$note, $orders] = $this->makeNoteWithOrders(2);
        $category = $this->makeCategory();
        $user = User::factory()->create(['user' => true]);

        $service = new CancellationRequestService();

        $file = UploadedFile::fake()->create('evidence.pdf', 120, 'application/pdf');

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            $category,
            [$orders[0]->id],
            [$file],
            $user,
            'Teste de cancelamento'
        );

        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $request->id,
            'note_id' => $note->id,
            'status' => CancellationRequest::STATUS_SUBMITTED,
        ]);

        $this->assertDatabaseCount('evidence_files', 1);
    }

    public function test_block_request_if_note_canceled(): void
    {
        [$note] = $this->makeNoteWithOrders(1);
        $note->update(['canceled' => true]);

        $category = $this->makeCategory();
        $user = User::factory()->create(['user' => true]);

        $service = new CancellationRequestService();

        $this->expectException(RuntimeException::class);
        $service->createRequest(
            $note,
            CancellationRequestScope::NOTE_FULL->value,
            $category,
            [],
            [],
            $user,
            null
        );
    }

    public function test_allow_new_request_for_non_canceled_orders(): void
    {
        [$note, $orders] = $this->makeNoteWithOrders(2);
        $orders[0]->update(['canceled' => true]);

        $category = $this->makeCategory(['require_evidence' => false, 'min_evidence_files' => 0]);
        $user = User::factory()->create(['user' => true]);

        $service = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            $category,
            [$orders[1]->id],
            [],
            $user,
            null
        );

        $this->assertDatabaseHas('cancellation_request_orders', [
            'cancellation_request_id' => $request->id,
            'order_id' => $orders[1]->id,
        ]);
    }

    public function test_claim_is_atomic(): void
    {
        [$note, $orders] = $this->makeNoteWithOrders(1);
        $category = $this->makeCategory(['require_evidence' => false, 'min_evidence_files' => 0]);

        $user1 = User::factory()->create(['can_dispatch' => true]);
        $user2 = User::factory()->create(['can_dispatch' => true]);

        $service = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            $category,
            [$orders[0]->id],
            [],
            $user1,
            null
        );

        $service->claimRequest($request, $user1);

        $this->expectException(RuntimeException::class);
        $service->claimRequest($request, $user2);
    }

    public function test_finalize_done_cancels_entities(): void
    {
        [$note, $orders] = $this->makeNoteWithOrders(2);
        $category = $this->makeCategory(['require_evidence' => false, 'min_evidence_files' => 0]);
        $user = User::factory()->create(['can_dispatch' => true]);

        $service = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::NOTE_FULL->value,
            $category,
            [],
            [],
            $user,
            null
        );

        $service->claimRequest($request, $user);
        $service->finalizeDone($request, $user);

        $this->assertTrue($note->fresh()->canceled);
        $this->assertTrue($orders[0]->fresh()->canceled);
        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $request->id,
            'status' => CancellationRequest::STATUS_DONE,
        ]);
    }

    public function test_finalize_rejected_does_not_cancel(): void
    {
        [$note, $orders] = $this->makeNoteWithOrders(1);
        $category = $this->makeCategory(['require_evidence' => false, 'min_evidence_files' => 0]);
        $user = User::factory()->create(['can_dispatch' => true]);

        $service = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            $category,
            [$orders[0]->id],
            [],
            $user,
            null
        );

        $service->claimRequest($request, $user);
        $service->finalizeRejected($request, $user, 'Motivo de rejeição');

        $this->assertFalse($note->fresh()->canceled);
        $this->assertFalse($orders[0]->fresh()->canceled);
        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $request->id,
            'status' => CancellationRequest::STATUS_REJECTED,
            'closure_note' => 'Motivo de rejeição',
        ]);
    }

    public function test_finalize_done_requires_engineer_approval_when_requested(): void
    {
        [$note, $orders] = $this->makeNoteWithOrders(1);
        $category = $this->makeCategory(['require_evidence' => false, 'min_evidence_files' => 0]);
        $executor = User::factory()->create(['can_dispatch' => true]);
        $engineer = User::factory()->create(['engineer' => true]);

        $service = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            $category,
            [$orders[0]->id],
            [],
            $executor,
            null
        );

        $service->claimRequest($request, $executor);
        $service->requestEngineerApproval($request, $executor, $engineer, 'Precisa validar regra técnica');

        $this->expectException(RuntimeException::class);
        $service->finalizeDone($request, $executor);
    }

    public function test_finalize_done_after_engineer_approval(): void
    {
        [$note, $orders] = $this->makeNoteWithOrders(1);
        $category = $this->makeCategory(['require_evidence' => false, 'min_evidence_files' => 0]);
        $executor = User::factory()->create(['can_dispatch' => true]);
        $engineer = User::factory()->create(['engineer' => true]);

        $service = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::ORDERS_PARTIAL->value,
            $category,
            [$orders[0]->id],
            [],
            $executor,
            null
        );

        $service->claimRequest($request, $executor);
        $service->requestEngineerApproval($request, $executor, $engineer, 'Precisa validar regra técnica');
        $service->decideEngineerApproval($request, $engineer, CancellationEngineerApprovalStatus::APPROVED->value, 'Autorizado.');
        $service->finalizeDone($request, $executor);

        $this->assertTrue($orders[0]->fresh()->canceled);
        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $request->id,
            'status' => CancellationRequest::STATUS_DONE,
            'engineer_approval_status' => CancellationEngineerApprovalStatus::APPROVED->value,
        ]);
    }
}
