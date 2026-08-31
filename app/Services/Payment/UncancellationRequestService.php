<?php

namespace App\Services\Payment;

use App\Enum\CancellationRequestScope;
use App\Enum\CancellationRequestStatus;
use App\Models\CancellationRequest;
use App\Models\Note;
use App\Models\Order;
use App\Models\UncancellationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UncancellationRequestService
{
    public function createRequest(
        Note $note,
        string $scope,
        array $orders,
        User $requestedBy,
        ?string $description = null
    ): UncancellationRequest {
        return DB::transaction(function () use ($note, $scope, $orders, $requestedBy, $description) {
            $ordersCollection = $this->resolveOrders($note, $scope, $orders);

            $this->ensureNoOpenRequest($note, $scope, $ordersCollection);

            $request = UncancellationRequest::create([
                'note_id' => $note->id,
                'scope' => $scope,
                'requested_by' => $requestedBy->id,
                'description' => $description,
                'status' => CancellationRequestStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $request->Orders()->sync($ordersCollection->pluck('id')->all());

            $this->logEvent($request, $requestedBy, 'submitted', [
                'scope' => $scope,
                'orders' => $ordersCollection->pluck('id')->all(),
            ]);

            return $request;
        });
    }

    public function claimRequest(UncancellationRequest $request, User $user): UncancellationRequest
    {
        $updated = DB::table('uncancellation_requests')
            ->where('id', $request->id)
            ->whereNull('assigned_to')
            ->where('status', CancellationRequestStatus::SUBMITTED->value)
            ->update([
                'assigned_to' => $user->id,
                'assigned_at' => now(),
                'status' => CancellationRequestStatus::ASSIGNED->value,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            throw new RuntimeException('Solicitação já assumida por outro usuário.');
        }

        $request->refresh();
        $this->logEvent($request, $user, 'assigned');

        return $request;
    }

    public function finalizeDone(UncancellationRequest $request, User $user): UncancellationRequest
    {
        return DB::transaction(function () use ($request, $user) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED], true)) {
                throw new RuntimeException('Solicitação não está disponível para finalização.');
            }

            if ($request->status === CancellationRequestStatus::ASSIGNED
                && $request->assigned_to !== $user->id
                && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode finalizar.');
            }

            if ($request->scope === CancellationRequestScope::NOTE_FULL) {
                if ($request->Note->canceled) {
                    $request->Note->update([
                        'canceled' => false,
                        'canceled_at' => null,
                        'canceled_by' => null,
                    ]);
                }

                $request->Orders()->update([
                    'canceled' => false,
                    'canceled_at' => null,
                    'canceled_by' => null,
                ]);

                $this->uncancelWorkForm($request->Note);
            } elseif ($request->scope === CancellationRequestScope::WORK_FORM_ONLY) {
                $this->uncancelWorkForm($request->Note);
            } else {
                $request->Orders()->update([
                    'canceled' => false,
                    'canceled_at' => null,
                    'canceled_by' => null,
                ]);
            }

            $abortedCancellationIds = $this->abortOpenCancellationRequests($request, $user);

            $request->update([
                'status' => CancellationRequestStatus::DONE,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => UncancellationRequest::CLOSURE_DONE,
            ]);

            $this->logEvent($request, $user, 'done', [
                'aborted_cancellation_request_ids' => $abortedCancellationIds,
            ]);

            return $request;
        });
    }

    public function finalizeRejected(UncancellationRequest $request, User $user, string $reason): UncancellationRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED], true)) {
                throw new RuntimeException('Solicitação não está disponível para rejeição.');
            }

            $rejectedReason = trim((string) $reason);
            if ($rejectedReason === '') {
                throw new RuntimeException('Informe o motivo da rejeição.');
            }

            $request->update([
                'status' => CancellationRequestStatus::REJECTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => UncancellationRequest::CLOSURE_REJECTED,
                'closure_note' => $rejectedReason,
            ]);

            $this->logEvent($request, $user, 'rejected', ['reason' => $rejectedReason]);

            return $request;
        });
    }

    private function resolveOrders(Note $note, string $scope, array $orders): Collection
    {
        $orders = array_filter(Arr::flatten($orders));

        if ($scope === CancellationRequestScope::NOTE_FULL->value) {
            if (!$note->canceled && !$this->hasOpenCancellationRequest($note, $scope)) {
                throw new RuntimeException('Nota não está cancelada e não possui processo de cancelamento em aberto.');
            }

            return $note->Orders()->where('canceled', true)->get();
        }

        if ($scope === CancellationRequestScope::WORK_FORM_ONLY->value) {
            if ((!$note->WorkFormAny || !$note->WorkFormAny->canceled) && !$this->hasOpenCancellationRequest($note, $scope)) {
                throw new RuntimeException('A nota não possui informe cancelado nem processo de cancelamento em aberto.');
            }

            return new Collection();
        }

        $selected = Order::where('note_id', $note->id)
            ->whereIn('id', $orders)
            ->get();

        if ($selected->isEmpty()) {
            throw new RuntimeException('Selecione ao menos uma ordem.');
        }

        $invalidOrders = $selected->filter(function (Order $order) use ($note, $scope) {
            return !$order->canceled && !$this->hasOpenCancellationRequest($note, $scope, [$order->id]);
        });

        if ($invalidOrders->isNotEmpty()) {
            throw new RuntimeException('Selecione ao menos uma ordem cancelada ou em processo de cancelamento.');
        }

        return $selected;
    }

    private function abortOpenCancellationRequests(UncancellationRequest $request, User $user): array
    {
        $openRequests = $this->openCancellationRequestQuery(
            $request->Note,
            $request->scope->value,
            $request->Orders->pluck('id')->all()
        )->get();

        foreach ($openRequests as $cancellationRequest) {
            $cancellationRequest->update([
                'status' => CancellationRequestStatus::ABORTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_ABORTED,
                'closure_note' => "Processo encerrado pelo descancelamento #{$request->id}.",
            ]);

            $cancellationRequest->Events()->create([
                'actor_id' => $user->id,
                'type' => 'aborted_by_uncancellation',
                'meta' => [
                    'uncancellation_request_id' => $request->id,
                ],
            ]);
        }

        return $openRequests->pluck('id')->all();
    }

    private function hasOpenCancellationRequest(Note $note, string $scope, array $orderIds = []): bool
    {
        return $this->openCancellationRequestQuery($note, $scope, $orderIds)->exists();
    }

    private function openCancellationRequestQuery(Note $note, string $scope, array $orderIds = [])
    {
        $openStatuses = [
            CancellationRequestStatus::DRAFT->value,
            CancellationRequestStatus::SUBMITTED->value,
            CancellationRequestStatus::ASSIGNED->value,
            CancellationRequestStatus::PAUSED->value,
        ];

        return CancellationRequest::query()
            ->where('note_id', $note->id)
            ->where('scope', $scope)
            ->whereIn('status', $openStatuses)
            ->when($scope === CancellationRequestScope::ORDERS_PARTIAL->value && !empty($orderIds), function ($query) use ($orderIds) {
                $query->whereHas('Orders', fn ($order) => $order->whereIn('orders.id', $orderIds));
            });
    }

    private function ensureNoOpenRequest(Note $note, string $scope, Collection $orders): void
    {
        $openStatuses = [
            CancellationRequestStatus::DRAFT->value,
            CancellationRequestStatus::SUBMITTED->value,
            CancellationRequestStatus::ASSIGNED->value,
            CancellationRequestStatus::PAUSED->value,
        ];

        $query = UncancellationRequest::query()
            ->where('note_id', $note->id)
            ->where('scope', $scope)
            ->whereIn('status', $openStatuses);

        if ($scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
            $orderIds = $orders->pluck('id')->all();
            $query->whereHas('Orders', fn ($q) => $q->whereIn('orders.id', $orderIds));
        }

        if ($query->exists()) {
            throw new RuntimeException('Já existe solicitação de descancelamento em aberto para este escopo.');
        }
    }

    private function uncancelWorkForm(Note $note): void
    {
        $workForm = $note->WorkFormAny;

        if (!$workForm || !$workForm->canceled) {
            return;
        }

        $workForm->update([
            'canceled' => false,
            'canceled_at' => null,
            'canceled_by' => null,
        ]);
    }

    private function logEvent(UncancellationRequest $request, User $user, string $event, array $payload = []): void
    {
        $request->Events()->create([
            'user_id' => $user->id,
            'event' => $event,
            'payload' => empty($payload) ? null : $payload,
        ]);
    }

    private function isSupervisor(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management);
    }
}
