<?php

namespace App\Services\Payment;

use App\Models\CancellationCategory;
use App\Models\CancellationRequest;
use App\Models\CancellationRequestEvent;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

            if ($ordersCollection->isEmpty()) {
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
                'status' => CancellationRequest::STATUS_SUBMITTED,
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
            if ($request->status !== CancellationRequest::STATUS_DRAFT) {
                throw new RuntimeException('Solicitação não está em rascunho.');
            }

            $request->update([
                'status' => CancellationRequest::STATUS_SUBMITTED,
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
            ->where('status', CancellationRequest::STATUS_SUBMITTED)
            ->update([
                'assigned_to' => $user->id,
                'assigned_at' => now(),
                'status' => CancellationRequest::STATUS_ASSIGNED,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            throw new RuntimeException('Solicitação já assumida por outro usuário.');
        }

        $request->refresh();
        $this->logEvent($request, $user, 'assigned');

        return $request;
    }

    public function finalizeDone(CancellationRequest $request, User $user): CancellationRequest
    {
        return DB::transaction(function () use ($request, $user) {
            $request->refresh();

            if (!in_array($request->status, [CancellationRequest::STATUS_ASSIGNED, CancellationRequest::STATUS_SUBMITTED], true)) {
                throw new RuntimeException('Solicitação não está disponível para finalização.');
            }

            if ($request->status === CancellationRequest::STATUS_ASSIGNED && $request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode finalizar.');
            }

            if ($request->scope === CancellationRequest::SCOPE_NOTE_FULL) {
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
                'status' => CancellationRequest::STATUS_DONE,
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

            if (!in_array($request->status, [CancellationRequest::STATUS_ASSIGNED, CancellationRequest::STATUS_SUBMITTED], true)) {
                throw new RuntimeException('Solicitação não está disponível para rejeição.');
            }

            if ($request->status === CancellationRequest::STATUS_ASSIGNED && $request->assigned_to !== $user->id && !$this->isSupervisor($user)) {
                throw new RuntimeException('Somente o responsável pode rejeitar.');
            }

            $request->update([
                'status' => CancellationRequest::STATUS_REJECTED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_type' => CancellationRequest::CLOSURE_REJECTED,
                'closure_note' => $reason,
            ]);

            $this->logEvent($request, $user, 'rejected', ['reason' => $reason]);

            return $request;
        });
    }

    private function resolveOrders(Note $note, string $scope, array $orders): Collection
    {
        $orders = array_filter(Arr::flatten($orders));

        if ($scope === CancellationRequest::SCOPE_NOTE_FULL) {
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

    private function storeEvidenceFiles(CancellationRequest $request, array $attachments, User $user): void
    {
        if (empty($attachments)) {
            return;
        }

        $dir = 'evidences/CANCELLATION_REQUEST/' . $request->id;

        foreach ($attachments as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $storedName = Str::uuid()->toString();
            $path = $file->storeAs($dir, $storedName . '.' . $extension, 'public');

            $request->EvidenceFiles()->create([
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'disk' => 'public',
                'path' => $path,
                'mime' => $file->getMimeType(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'sha256' => hash('sha256', Storage::disk('public')->get($path)),
                'uploaded_at' => now(),
                'origin' => 'CANCELLATION_REQUEST',
            ]);

            $this->logEvent($request, $user, 'attachment_added', [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
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
