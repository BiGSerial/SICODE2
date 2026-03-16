<?php

namespace App\Services\Payment;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Enum\CancellationRequestStatus;
use App\Enum\CancellationRequestScope;
use App\Models\CancellationCategory;
use App\Models\Comment;
use App\Models\CancellationRequest;
use App\Models\CancellationRequestEvent;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Models\EvidenceFile;
use App\Notifications\SystemNotification;
use App\Support\EvidenceFileUploader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CancellationRequestService
{
    public function createRequest(
        Note $note,
        string $scope,
        CancellationCategory $category,
        array $orders,
        array $attachments,
        User $requestedBy,
        ?string $description = null
    ): CancellationRequest {
        return DB::transaction(function () use ($note, $scope, $category, $orders, $attachments, $requestedBy, $description) {
            if ($note->canceled) {
                throw new RuntimeException('Nota já está cancelada.');
            }

            if ($scope === CancellationRequestScope::NOTE_FULL->value) {
                $hasOpenNoteFullRequest = CancellationRequest::query()
                    ->where('note_id', $note->id)
                    ->where('scope', CancellationRequestScope::NOTE_FULL->value)
                    ->whereIn('status', [
                        CancellationRequestStatus::DRAFT->value,
                        CancellationRequestStatus::SUBMITTED->value,
                        CancellationRequestStatus::ASSIGNED->value,
                        CancellationRequestStatus::PAUSED->value,
                    ])
                    ->exists();

                if ($hasOpenNoteFullRequest) {
                    throw new RuntimeException('Já existe solicitação em aberto para cancelamento da nota inteira.');
                }
            }

            $ordersCollection = $this->resolveOrders($note, $scope, $orders);

            if ($ordersCollection->isEmpty() && $scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
                throw new RuntimeException('Selecione ao menos uma ordem válida.');
            }

            if ($category->require_evidence && count($attachments) < max(1, (int) $category->min_evidence_files)) {
                throw new RuntimeException('Quantidade mínima de evidências não atendida.');
            }

            $request = CancellationRequest::create([
                'note_id' => $note->id,
                'scope' => $scope,
                'category_id' => $category->id,
                'requested_by' => $requestedBy->id,
                'description' => $description,
                'status' => CancellationRequestStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $request->Orders()->sync($ordersCollection->pluck('id')->all());

            $this->storeEvidenceFiles($request, $attachments, $requestedBy);

            $this->logEvent($request, $requestedBy, 'submitted', [
                'scope' => $scope,
                'category_id' => $category->id,
                'orders' => $ordersCollection->pluck('id')->all(),
            ]);

            return $request;
        });
    }

    public function createRequestForBulkOrder(
        Note $note,
        Order $order,
        CancellationCategory $category,
        User $requestedBy,
        ?string $description = null,
        int $evidenceCount = 0
    ): CancellationRequest {
        return DB::transaction(function () use ($note, $order, $category, $requestedBy, $description, $evidenceCount) {
            if ($note->canceled) {
                throw new RuntimeException('Nota já está cancelada.');
            }

            if ((int) $order->note_id !== (int) $note->id) {
                throw new RuntimeException('Ordem não pertence à nota informada.');
            }

            if ($order->canceled) {
                throw new RuntimeException('Ordem já está cancelada.');
            }

            if ($category->require_evidence && $evidenceCount < max(1, (int) $category->min_evidence_files)) {
                throw new RuntimeException('Quantidade mínima de evidências não atendida.');
            }

            $request = CancellationRequest::create([
                'note_id' => $note->id,
                'scope' => CancellationRequestScope::ORDERS_PARTIAL,
                'category_id' => $category->id,
                'requested_by' => $requestedBy->id,
                'description' => $description,
                'status' => CancellationRequestStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $request->Orders()->sync([$order->id]);

            $this->logEvent($request, $requestedBy, 'submitted', [
                'scope' => CancellationRequestScope::ORDERS_PARTIAL->value,
                'category_id' => $category->id,
                'orders' => [$order->id],
                'bulk' => true,
            ]);

            return $request;
        });
    }

    public function submitRequest(CancellationRequest $request, User $actor): CancellationRequest
    {
        return DB::transaction(function () use ($request, $actor) {
            if ($request->status !== CancellationRequestStatus::DRAFT) {
                throw new RuntimeException('Solicitação não está em rascunho.');
            }

            $request->update([
                'status' => CancellationRequestStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->logEvent($request, $actor, 'submitted');

            return $request;
        });
    }

    public function claimRequest(CancellationRequest $request, User $user): CancellationRequest
    {
        $updated = DB::table('cancellation_requests')
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
        $this->notifyRequesterAndAssignee(
            $request,
            'Solicitação de cancelamento assumida',
            "A solicitação #{$request->id} foi assumida por {$user->name}.",
            'info'
        );

        return $request;
    }

    public function pauseRequest(CancellationRequest $request, User $user, string $reason): CancellationRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED], true)) {
                throw new RuntimeException('Solicitação não está disponível para pausar.');
            }

            if ($request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode pausar.');
            }

            if (!trim($reason)) {
                throw new RuntimeException('Informe o motivo da pausa.');
            }

            $request->update([
                'status' => CancellationRequestStatus::PAUSED,
            ]);

            $this->logEvent($request, $user, 'paused', ['reason' => $reason]);
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação de cancelamento pausada',
                "A solicitação #{$request->id} foi pausada por {$user->name}. Motivo: {$reason}",
                'warning'
            );

            return $request;
        });
    }

    public function reopenRequest(CancellationRequest $request, User $user, string $reason): CancellationRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request->refresh();

            if ($request->status !== CancellationRequestStatus::PAUSED) {
                throw new RuntimeException('Solicitação não está pausada para reabertura.');
            }

            if ($request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode reabrir.');
            }

            $reopenReason = trim((string) $reason);
            if ($reopenReason === '') {
                throw new RuntimeException('Informe o motivo da reabertura.');
            }

            $request->update([
                'status' => CancellationRequestStatus::ASSIGNED,
            ]);

            $this->logEvent($request, $user, 'reopened', ['reason' => $reopenReason]);
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação de cancelamento reaberta',
                "A solicitação #{$request->id} foi reaberta por {$user->name}. Motivo: {$reopenReason}",
                'info'
            );

            return $request;
        });
    }

    public function finalizeDone(CancellationRequest $request, User $user): CancellationRequest
    {
        return DB::transaction(function () use ($request, $user) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED, CancellationRequestStatus::PAUSED], true)) {
                throw new RuntimeException('Solicitação não está disponível para finalização.');
            }

            if (in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::PAUSED], true)
                && $request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode finalizar.');
            }

            if ($request->requires_engineer_approval) {
                if ($request->engineer_approval_status === CancellationEngineerApprovalStatus::PENDING) {
                    throw new RuntimeException('Aguardando aprovação do engenheiro. Cancele a solicitação ao engenheiro para continuar.');
                }

                if ($request->engineer_approval_status !== CancellationEngineerApprovalStatus::APPROVED) {
                    throw new RuntimeException('Cancelamento exige aprovação do engenheiro antes da finalização.');
                }
            }

            if ($request->scope === CancellationRequestScope::NOTE_FULL) {
                if ($request->Note->canceled) {
                    throw new RuntimeException('Nota já cancelada.');
                }
                if ($request->Orders()->where('canceled', true)->exists()) {
                    throw new RuntimeException('Existem ordens já canceladas nesta nota.');
                }
                $request->Note->update([
                    'canceled' => true,
                    'canceled_at' => now(),
                    'canceled_by' => $user->id,
                ]);

                $request->Orders()->where('canceled', false)->update([
                    'canceled' => true,
                    'canceled_at' => now(),
                    'canceled_by' => $user->id,
                ]);

                $this->cancelWorkForm($request->Note, $user);
            } elseif ($request->scope === CancellationRequestScope::WORK_FORM_ONLY) {
                $this->cancelWorkForm($request->Note, $user);
            } else {
                $orders = $request->Orders()->get();
                foreach ($orders as $order) {
                    if ($order->canceled) {
                        throw new RuntimeException('Existe ordem já cancelada nesta solicitação.');
                    }
                }

                $request->Orders()->update([
                    'canceled' => true,
                    'canceled_at' => now(),
                    'canceled_by' => $user->id,
                ]);
            }

            $request->update([
                'status' => CancellationRequestStatus::DONE,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_DONE,
            ]);

            $this->logEvent($request, $user, 'done');
            $this->notifyRequesterRequestDone($request, $user);

            return $request;
        });
    }

    public function requestEngineerApproval(
        CancellationRequest $request,
        User $actor,
        User $engineer,
        string $reason
    ): CancellationRequest {
        return DB::transaction(function () use ($request, $actor, $engineer, $reason) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::PAUSED], true)) {
                throw new RuntimeException('Solicitação não está disponível para aprovação de engenheiro.');
            }

            if ($request->assigned_to !== $actor->id && !$this->isSupervisor($actor)) {
                throw new RuntimeException('Somente o executante pode solicitar aprovação ao engenheiro.');
            }

            if (!$engineer->engineer) {
                throw new RuntimeException('Usuário selecionado não é engenheiro.');
            }

            if (!trim($reason)) {
                throw new RuntimeException('Informe o motivo para solicitar aprovação do engenheiro.');
            }

            $eventType = $request->engineer_approval_status === CancellationEngineerApprovalStatus::REJECTED
                ? 'engineer_approval_reopened'
                : 'engineer_approval_requested';

            $request->update([
                'requires_engineer_approval' => true,
                'engineer_approval_status' => CancellationEngineerApprovalStatus::PENDING,
                'engineer_approval_requested_by' => $actor->id,
                'engineer_approval_requested_at' => now(),
                'engineer_approver_id' => $engineer->id,
                'engineer_approval_decided_by' => null,
                'engineer_approval_decided_at' => null,
                'engineer_approval_reason' => $reason,
            ]);

            $this->logEvent($request, $actor, $eventType, [
                'engineer_id' => $engineer->id,
                'reason' => $reason,
            ]);

            $this->notifyEngineerApprovalRequested($request, $actor, $engineer, $reason);
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação enviada para aprovação do engenheiro',
                "A solicitação #{$request->id} foi encaminhada para {$engineer->name}. Motivo: {$reason}",
                'info'
            );

            return $request;
        });
    }

    public function changeEngineerApprover(
        CancellationRequest $request,
        User $actor,
        User $engineer,
        string $reason
    ): CancellationRequest {
        return DB::transaction(function () use ($request, $actor, $engineer, $reason) {
            $request->refresh();

            if (!$request->requires_engineer_approval || $request->engineer_approval_status !== CancellationEngineerApprovalStatus::PENDING) {
                throw new RuntimeException('Troca de engenheiro disponível apenas para solicitações pendentes.');
            }

            if ($request->assigned_to !== $actor->id && !$this->isSupervisor($actor)) {
                throw new RuntimeException('Somente o executante pode trocar o engenheiro.');
            }

            if (!$engineer->engineer) {
                throw new RuntimeException('Usuário selecionado não é engenheiro.');
            }

            if (!trim($reason)) {
                throw new RuntimeException('Informe o motivo da troca de engenheiro.');
            }

            $previousEngineerId = $request->engineer_approver_id;

            $request->update([
                'engineer_approver_id' => $engineer->id,
                'engineer_approval_reason' => $reason,
            ]);

            $this->logEvent($request, $actor, 'engineer_approval_engineer_changed', [
                'from_engineer_id' => $previousEngineerId,
                'to_engineer_id' => $engineer->id,
                'reason' => $reason,
            ]);

            $this->notifyEngineerApprovalRequested($request, $actor, $engineer, $reason);
            $this->notifyUsersByIds(
                [$previousEngineerId],
                'Solicitação de aprovação transferida',
                "Você foi removido da aprovação da solicitação #{$request->id}.",
                route('engineers.cancellations.history'),
                'info'
            );
            $this->notifyRequesterAndAssignee(
                $request,
                'Engenheiro alterado na aprovação',
                "A solicitação #{$request->id} teve o engenheiro alterado para {$engineer->name}. Motivo: {$reason}",
                'info'
            );

            return $request;
        });
    }

    public function cancelEngineerApproval(CancellationRequest $request, User $actor, string $reason): CancellationRequest
    {
        return DB::transaction(function () use ($request, $actor, $reason) {
            $request->refresh();

            if (!$request->requires_engineer_approval || $request->engineer_approval_status !== CancellationEngineerApprovalStatus::PENDING) {
                throw new RuntimeException('Não há solicitação de aprovação pendente para cancelar.');
            }

            if ($request->assigned_to !== $actor->id && !$this->isSupervisor($actor)) {
                throw new RuntimeException('Somente o executante pode cancelar a solicitação ao engenheiro.');
            }

            if (!trim($reason)) {
                throw new RuntimeException('Informe o motivo do cancelamento da solicitação ao engenheiro.');
            }

            $request->update([
                'requires_engineer_approval' => false,
                'engineer_approval_status' => CancellationEngineerApprovalStatus::CANCELED,
                'engineer_approval_decided_by' => $actor->id,
                'engineer_approval_decided_at' => now(),
                'engineer_approval_reason' => $reason,
            ]);

            $this->logEvent($request, $actor, 'engineer_approval_canceled', [
                'reason' => $reason,
            ]);
            $this->notifyUsersByIds(
                [$request->engineer_approver_id],
                'Solicitação de aprovação cancelada',
                "A aprovação da solicitação #{$request->id} foi cancelada por {$actor->name}. Motivo: {$reason}",
                route('engineers.cancellations.history'),
                'warning'
            );
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação ao engenheiro cancelada',
                "A solicitação #{$request->id} teve a etapa de aprovação cancelada por {$actor->name}. Motivo: {$reason}",
                'warning'
            );

            return $request;
        });
    }

    public function decideEngineerApproval(
        CancellationRequest $request,
        User $engineer,
        string $decision,
        string $reason
    ): CancellationRequest {
        return DB::transaction(function () use ($request, $engineer, $decision, $reason) {
            $request->refresh();

            if ($request->engineer_approval_status !== CancellationEngineerApprovalStatus::PENDING) {
                throw new RuntimeException('Solicitação não está pendente para decisão.');
            }

            if ((string) $request->engineer_approver_id !== (string) $engineer->id && !$this->isSupervisor($engineer)) {
                throw new RuntimeException('Somente o engenheiro designado pode decidir.');
            }

            if (!$engineer->engineer && !$this->isSupervisor($engineer)) {
                throw new RuntimeException('Apenas engenheiros podem decidir.');
            }

            if (!in_array($decision, [CancellationEngineerApprovalStatus::APPROVED->value, CancellationEngineerApprovalStatus::REJECTED->value], true)) {
                throw new RuntimeException('Decisão de aprovação inválida.');
            }

            if (!trim($reason)) {
                throw new RuntimeException('Informe a justificativa da decisão.');
            }

            $approved = $decision === CancellationEngineerApprovalStatus::APPROVED->value;
            $approvalStatus = $approved
                ? CancellationEngineerApprovalStatus::APPROVED
                : CancellationEngineerApprovalStatus::REJECTED;

            $request->update([
                'requires_engineer_approval' => true,
                'engineer_approval_status' => $approvalStatus,
                'engineer_approval_decided_by' => $engineer->id,
                'engineer_approval_decided_at' => now(),
                'engineer_approval_reason' => $reason,
            ]);

            $this->logEvent($request, $engineer, $approved ? 'engineer_approval_approved' : 'engineer_approval_rejected', [
                'reason' => $reason,
            ]);

            $this->notifyEngineerDecision($request, $engineer, $approved, $reason);

            return $request;
        });
    }

    public function finalizeRejected(CancellationRequest $request, User $user, string $reason): CancellationRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED, CancellationRequestStatus::PAUSED], true)) {
                throw new RuntimeException('Solicitação não está disponível para rejeição.');
            }

            if (in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::PAUSED], true)
                && $request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode rejeitar.');
            }

            $rejectedReason = trim((string) $reason);
            if ($rejectedReason === '') {
                throw new RuntimeException('Informe o motivo da rejeição.');
            }

            $request->update([
                'status' => CancellationRequestStatus::REJECTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_REJECTED,
                'closure_note' => $rejectedReason,
            ]);

            $this->logEvent($request, $user, 'rejected', ['reason' => $rejectedReason]);
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação de cancelamento rejeitada',
                "A solicitação #{$request->id} foi rejeitada por {$user->name}. Motivo: {$rejectedReason}",
                'danger'
            );

            return $request;
        });
    }

    public function abortRequest(CancellationRequest $request, User $user, ?string $reason = null): CancellationRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED, CancellationRequestStatus::PAUSED], true)) {
                throw new RuntimeException('Solicitação não está disponível para cancelamento.');
            }

            if (in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::PAUSED], true)
                && $request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode cancelar.');
            }

            $abortReason = trim((string) $reason);

            if ($abortReason === '') {
                throw new RuntimeException('Informe o motivo do cancelamento.');
            }

            $request->update([
                'status' => CancellationRequestStatus::ABORTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_ABORTED,
                'closure_note' => $abortReason,
            ]);

            $this->logEvent($request, $user, 'aborted', ['reason' => $abortReason]);
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação de cancelamento abortada',
                "A solicitação #{$request->id} foi abortada por {$user->name}. Motivo: {$abortReason}",
                'warning'
            );

            return $request;
        });
    }

    public function transferRequest(CancellationRequest $request, User $actor, User $target): CancellationRequest
    {
        return DB::transaction(function () use ($request, $actor, $target) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED, CancellationRequestStatus::DONE], true)) {
                throw new RuntimeException('Solicitação não está disponível para transferência.');
            }

            $request->update([
                'assigned_to' => $target->id,
                'assigned_at' => now(),
                'status' => CancellationRequestStatus::ASSIGNED,
                'closed_by' => $request->status === CancellationRequestStatus::DONE ? null : $request->closed_by,
                'closed_at' => $request->status === CancellationRequestStatus::DONE ? null : $request->closed_at,
                'closure_type' => $request->status === CancellationRequestStatus::DONE ? null : $request->closure_type,
                'closure_note' => $request->status === CancellationRequestStatus::DONE ? null : $request->closure_note,
            ]);

            $this->logEvent($request, $actor, $request->status === CancellationRequestStatus::DONE ? 'reopened' : 'transferred', [
                'from' => $actor->id,
                'to' => $target->id,
            ]);
            $this->notifyUsersByIds(
                [$target->id],
                'Nova solicitação de cancelamento atribuída',
                "A solicitação #{$request->id} foi transferida para você por {$actor->name}.",
                route('cancellations.show', ['request' => $request->id]),
                'info'
            );
            $this->notifyRequesterAndAssignee(
                $request,
                'Solicitação de cancelamento transferida',
                "A solicitação #{$request->id} foi transferida por {$actor->name} para {$target->name}.",
                'info'
            );

            return $request;
        });
    }

    public function updateRequest(
        CancellationRequest $request,
        User $user,
        string $scope,
        CancellationCategory $category,
        array $orders,
        array $attachments,
        array $removeEvidenceIds = [],
        ?string $description = null
    ): CancellationRequest {
        return DB::transaction(function () use ($request, $user, $scope, $category, $orders, $attachments, $removeEvidenceIds, $description) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequestStatus::ASSIGNED, CancellationRequestStatus::SUBMITTED], true)) {
                throw new RuntimeException('Solicitação não está disponível para edição.');
            }

            if ($request->status === CancellationRequestStatus::ASSIGNED && $request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode editar.');
            }

            if ($request->Note->canceled) {
                throw new RuntimeException('Nota já cancelada.');
            }

            $ordersCollection = $this->resolveOrders($request->Note, $scope, $orders);

            if ($ordersCollection->isEmpty() && $scope === CancellationRequestScope::ORDERS_PARTIAL->value) {
                throw new RuntimeException('Selecione ao menos uma ordem válida.');
            }

            $existingCount = $request->EvidenceFiles()->whereNotIn('id', $removeEvidenceIds)->count();
            $incomingCount = count($attachments);
            $totalCount = $existingCount + $incomingCount;

            if ($category->require_evidence && $totalCount < max(1, (int) $category->min_evidence_files)) {
                throw new RuntimeException('Quantidade mínima de evidências não atendida.');
            }

            $request->update([
                'scope' => $scope,
                'category_id' => $category->id,
                'description' => $description,
            ]);

            $request->Orders()->sync($ordersCollection->pluck('id')->all());

            if (!empty($removeEvidenceIds)) {
                $this->removeEvidenceFiles($request, $removeEvidenceIds, $user);
            }

            $this->storeEvidenceFiles($request, $attachments, $user, 'CANCELLATION_CONTROL');

            $this->logEvent($request, $user, 'updated', [
                'scope' => $scope,
                'category_id' => $category->id,
                'orders' => $ordersCollection->pluck('id')->all(),
            ]);

            return $request;
        });
    }

    public function deleteRequest(CancellationRequest $request, User $user): void
    {
        DB::transaction(function () use ($request, $user) {
            $request->refresh();

            $this->removeEvidenceFiles($request, $request->EvidenceFiles()->pluck('id')->all(), $user);
            $request->Events()->delete();
            $request->Orders()->detach();
            $request->delete();
        });
    }

    private function resolveOrders(Note $note, string $scope, array $orders): Collection
    {
        $orders = array_filter(Arr::flatten($orders));

        if ($scope === CancellationRequestScope::NOTE_FULL->value) {
            if ($note->Orders()->where('canceled', true)->exists()) {
                throw new RuntimeException('Nota possui ordens já canceladas. Selecione ordens específicas.');
            }

            return $note->Orders()->where('canceled', false)->get();
        }

        if ($scope === CancellationRequestScope::WORK_FORM_ONLY->value) {
            if (!$note->WorkForm) {
                throw new RuntimeException('A nota não possui WorkForm para cancelar.');
            }

            return new Collection();
        }

        return Order::where('note_id', $note->id)
            ->whereIn('id', $orders)
            ->where('canceled', false)
            ->get();
    }

    private function cancelWorkForm(Note $note, User $actor): void
    {
        $workForm = $note->WorkForm;

        if (!$workForm) {
            return;
        }

        $workForm->update([
            'canceled' => true,
            'canceled_at' => now(),
            'canceled_by' => $actor->id,
        ]);
    }

    public function addEvidenceFiles(CancellationRequest $request, User $user, array $attachments, string $origin): void
    {
        $this->storeEvidenceFiles($request, $attachments, $user, $origin);
    }

    public function addComment(CancellationRequest $request, User $user, string $message): Comment
    {
        $comment = $request->Comments()->create([
            'user_id' => $user->id,
            'message' => $message,
            'restrict' => false,
        ]);

        $this->logEvent($request, $user, 'comment', ['message' => $message]);

        return $comment;
    }

    private function storeEvidenceFiles(CancellationRequest $request, array $attachments, User $user, string $origin = 'CANCELLATION_REQUEST'): void
    {
        if (empty($attachments)) {
            return;
        }

        (new EvidenceFileUploader())->storeCancellationEvidence($request, $attachments, $user, $origin);

        foreach ($attachments as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $this->logEvent($request, $user, 'attachment_added', [
                'name' => $file->getClientOriginalName(),
            ]);
        }
    }

    public function attachSharedEvidence(CancellationRequest $request, User $user, array $data, string $origin): EvidenceFile
    {
        $file = (new EvidenceFileUploader())->attachEvidence($request, $user, $data, $origin);

        $this->logEvent($request, $user, 'attachment_added', [
            'name' => $data['original_name'] ?? $file->original_name,
        ]);

        return $file;
    }

    private function removeEvidenceFiles(CancellationRequest $request, array $ids, User $user): void
    {
        if (empty($ids)) {
            return;
        }

        $files = $request->EvidenceFiles()->whereIn('id', $ids)->get();

        foreach ($files as $file) {
            $sharedCount = EvidenceFile::query()
                ->where('disk', $file->disk)
                ->where('path', $file->path)
                ->whereNull('deleted_at')
                ->count();

            if ($sharedCount <= 1 && Storage::disk($file->disk)->exists($file->path)) {
                Storage::disk($file->disk)->delete($file->path);
            }
            $file->delete();
            $this->logEvent($request, $user, 'attachment_removed', [
                'name' => $file->original_name,
                'path' => $file->path,
            ]);
        }
    }

    private function logEvent(CancellationRequest $request, User $actor, string $type, array $meta = []): void
    {
        CancellationRequestEvent::create([
            'cancellation_request_id' => $request->id,
            'actor_id' => $actor->id,
            'type' => $type,
            'meta' => $meta ?: null,
        ]);
    }

    private function notifyEngineerApprovalRequested(
        CancellationRequest $request,
        User $actor,
        User $engineer,
        string $reason
    ): void {
        $engineer->notify(new SystemNotification(
            titulo: 'Nova aprovação de cancelamento',
            mensagem: "A solicitação #{$request->id} foi encaminhada por {$actor->name}. Motivo: {$reason}",
            link: route('engineers.cancellations.show', ['request' => $request->id]),
            status: 'warning'
        ));
    }

    private function notifyEngineerDecision(
        CancellationRequest $request,
        User $engineer,
        bool $approved,
        string $reason
    ): void {
        $title = $approved ? 'Cancelamento autorizado pelo engenheiro' : 'Cancelamento rejeitado pelo engenheiro';
        $message = $approved
            ? "O engenheiro {$engineer->name} autorizou a solicitação #{$request->id}. Justificativa: {$reason}"
            : "O engenheiro {$engineer->name} rejeitou a solicitação #{$request->id}. Motivo: {$reason}";

        $users = collect([
            $request->assigned_to,
            $request->engineer_approval_requested_by,
            $request->requested_by,
        ])->filter()->unique()->all();

        User::query()->whereIn('id', $users)->get()->each(function (User $user) use ($title, $message, $request) {
            $user->notify(new SystemNotification(
                titulo: $title,
                mensagem: $message,
                link: route('cancellations.show', ['request' => $request->id]),
                status: 'info'
            ));
        });
    }

    private function notifyRequesterRequestDone(CancellationRequest $request, User $actor): void
    {
        if (!$request->requested_by || (string) $request->requested_by === (string) $actor->id) {
            return;
        }

        $requester = User::find($request->requested_by);
        if (!$requester) {
            return;
        }

        $requester->notify(new SystemNotification(
            titulo: 'Cancelamento concluído',
            mensagem: "A solicitação #{$request->id} foi concluída por {$actor->name}.",
            link: route('cancellations.show', ['request' => $request->id]),
            status: 'success'
        ));
    }

    private function notifyRequesterAndAssignee(
        CancellationRequest $request,
        string $title,
        string $message,
        string $status = 'info'
    ): void {
        $this->notifyUsersByIds(
            [$request->requested_by, $request->assigned_to],
            $title,
            $message,
            route('cancellations.show', ['request' => $request->id]),
            $status
        );
    }

    private function notifyUsersByIds(
        array $userIds,
        string $title,
        string $message,
        ?string $link = null,
        string $status = 'info'
    ): void {
        $ids = collect($userIds)->filter()->unique()->values()->all();
        if (empty($ids)) {
            return;
        }

        User::query()->whereIn('id', $ids)->get()->each(function (User $user) use ($title, $message, $link, $status) {
            $user->notify(new SystemNotification(
                titulo: $title,
                mensagem: $message,
                link: $link,
                status: $status
            ));
        });
    }

    private function isSupervisor(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management);
    }
}
