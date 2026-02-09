<?php

namespace App\Services\Payment;

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

            $ordersCollection = $this->resolveOrders($note, $scope, $orders);

            if ($ordersCollection->isEmpty() && $scope !== CancellationRequestScope::NOTE_FULL->value) {
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

            $request->update([
                'status' => CancellationRequestStatus::REJECTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_REJECTED,
                'closure_note' => $reason,
            ]);

            $this->logEvent($request, $user, 'rejected', ['reason' => $reason]);

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

            if (!trim((string) $reason)) {
                throw new RuntimeException('Informe o motivo do cancelamento.');
            }

            $request->update([
                'status' => CancellationRequestStatus::ABORTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_ABORTED,
                'closure_note' => $reason,
            ]);

            $this->logEvent($request, $user, 'aborted', ['reason' => $reason]);

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

            if ($ordersCollection->isEmpty() && $scope !== CancellationRequestScope::NOTE_FULL->value) {
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

        return Order::where('note_id', $note->id)
            ->whereIn('id', $orders)
            ->where('canceled', false)
            ->get();
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

    private function isSupervisor(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management);
    }
}
