<?php

namespace App\Services\Payment;

use App\Enum\{CancellationRequestScope, CancellationRequestStatus};
use App\Models\{CancellationRequest, Note, Order, UncancellationRequest, User, WorkReport};
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
        ?string $description = null,
        array $workReportIds = []
    ): UncancellationRequest {
        return DB::transaction(function () use ($note, $scope, $orders, $requestedBy, $description, $workReportIds) {
            $ordersCollection = $this->resolveOrders($note, $scope, $orders);
            $workReports      = $this->resolveWorkReports($note, $scope, $workReportIds);

            $this->ensureNoOpenRequest($note, $scope, $ordersCollection, $workReports);

            $request = UncancellationRequest::create([
                'note_id'      => $note->id,
                'scope'        => $scope,
                'requested_by' => $requestedBy->id,
                'description'  => $description,
                'status'       => CancellationRequestStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $request->Orders()->sync($ordersCollection->pluck('id')->all());
            $request->WorkReports()->sync($workReports->pluck('id')->all());

            $this->logEvent($request, $requestedBy, 'submitted', [
                'scope'        => $scope,
                'orders'       => $ordersCollection->pluck('id')->all(),
                'work_reports' => $workReports->pluck('id')->all(),
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
                'status'      => CancellationRequestStatus::ASSIGNED->value,
                'updated_at'  => now(),
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

            $affectedTargets = $this->captureUncancellationTargets($request);

            if ($request->scope === CancellationRequestScope::NOTE_FULL) {
                if ($request->Note->canceled) {
                    $request->Note->update([
                        'canceled'    => false,
                        'canceled_at' => null,
                        'canceled_by' => null,
                    ]);
                }

                $request->Orders()->update([
                    'canceled'    => false,
                    'canceled_at' => null,
                    'canceled_by' => null,
                ]);

                $this->uncancelWorkReports($request->Note);
            } elseif ($request->scope === CancellationRequestScope::WORK_FORM_ONLY) {
                $this->uncancelWorkReports($request->Note, $request);
            } else {
                $request->Orders()->update([
                    'canceled'    => false,
                    'canceled_at' => null,
                    'canceled_by' => null,
                ]);
            }

            $abortedCancellationIds = $this->abortOpenCancellationRequests($request, $user);

            $request->update([
                'status'       => CancellationRequestStatus::DONE,
                'closed_by'    => $user->id,
                'closed_at'    => now(),
                'closure_type' => UncancellationRequest::CLOSURE_DONE,
            ]);

            $this->logEvent($request, $user, 'done', [
                'aborted_cancellation_request_ids' => $abortedCancellationIds,
                'affected_targets'                 => $affectedTargets,
            ]);

            return $request;
        });
    }

    public function revertDone(UncancellationRequest $request, User $user, string $reason): UncancellationRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request->refresh()->loadMissing(['Note.WorkFormsAny', 'Orders', 'Events']);

            if ($request->status !== CancellationRequestStatus::DONE || $request->closure_type !== UncancellationRequest::CLOSURE_DONE) {
                throw new RuntimeException('Somente descancelamentos concluídos podem ser desfeitos.');
            }

            if (!$this->isSupervisor($user) && $request->closed_by !== $user->id && $request->assigned_to !== $user->id) {
                throw new RuntimeException('Somente o responsável ou um supervisor pode desfazer este descancelamento.');
            }

            $reversalReason = trim((string) $reason);

            if ($reversalReason === '') {
                throw new RuntimeException('Informe o motivo para desfazer o descancelamento.');
            }

            $targets = $this->targetsFromDoneEvent($request) ?? $this->fallbackReversalTargets($request);
            $now     = now();

            if (($targets['note'] ?? false) && $request->Note) {
                $request->Note->update([
                    'canceled'    => true,
                    'canceled_at' => $now,
                    'canceled_by' => $user->id,
                ]);
            }

            $orderIds = array_values(array_filter($targets['order_ids'] ?? []));

            if (!empty($orderIds)) {
                Order::where('note_id', $request->note_id)
                    ->whereIn('id', $orderIds)
                    ->update([
                        'canceled'    => true,
                        'canceled_at' => $now,
                        'canceled_by' => $user->id,
                    ]);
            }

            $workReportIds = array_values(array_filter($targets['work_report_ids'] ?? []));

            if (!empty($workReportIds)) {
                // Por model (não em massa) para atualizar o status materializado do informe.
                foreach ($request->Note->WorkFormsAny()->whereIn('id', $workReportIds)->get() as $workReport) {
                    $workReport->update([
                        'canceled'    => true,
                        'canceled_at' => $now,
                        'canceled_by' => $user->id,
                    ]);
                }
            }

            $request->update([
                'status'       => CancellationRequestStatus::REVERTED,
                'closed_by'    => $user->id,
                'closed_at'    => $now,
                'closure_type' => UncancellationRequest::CLOSURE_REVERTED,
                'closure_note' => $reversalReason,
            ]);

            $this->logEvent($request, $user, 'reverted', [
                'reason'           => $reversalReason,
                'affected_targets' => $targets,
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
                'status'       => CancellationRequestStatus::REJECTED,
                'closed_by'    => $user->id,
                'closed_at'    => now(),
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
            $hasCanceledWorkForm = $note->WorkFormsAny()->where('canceled', true)->exists();

            if (!$hasCanceledWorkForm && !$this->hasOpenCancellationRequest($note, $scope)) {
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

    /**
     * Informes escolhidos para o descancelamento: precisam pertencer à nota e estar cancelados
     * (ou em processo de cancelamento em aberto, que será abortado).
     */
    private function resolveWorkReports(Note $note, string $scope, array $workReportIds): Collection
    {
        if ($scope !== CancellationRequestScope::WORK_FORM_ONLY->value) {
            return new Collection();
        }

        $ids = collect(Arr::flatten($workReportIds))->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            throw new RuntimeException('Selecione ao menos um informe para descancelar.');
        }

        $reports = WorkReport::query()->where('note_id', $note->id)->whereIn('id', $ids)->get();

        if ($reports->count() !== $ids->count()) {
            throw new RuntimeException('Informe selecionado não pertence à nota.');
        }

        $invalid = $reports->first(fn (WorkReport $report) => !$report->canceled
            && !$this->hasOpenCancellationRequest($note, CancellationRequestScope::WORK_FORM_ONLY->value, [], [$report->id]));

        if ($invalid) {
            throw new RuntimeException("O informe #{$invalid->id} não está cancelado nem em processo de cancelamento.");
        }

        return $reports;
    }

    private function abortOpenCancellationRequests(UncancellationRequest $request, User $user): array
    {
        $openRequests = $this->openCancellationRequestQuery(
            $request->Note,
            $request->scope->value,
            $request->Orders->pluck('id')->all(),
            $request->WorkReports()->pluck('work_reports.id')->all()
        )->get();

        foreach ($openRequests as $cancellationRequest) {
            $cancellationRequest->update([
                'status'       => CancellationRequestStatus::ABORTED,
                'closed_by'    => $user->id,
                'closed_at'    => now(),
                'closure_type' => CancellationRequest::CLOSURE_ABORTED,
                'closure_note' => "Processo encerrado pelo descancelamento #{$request->id}.",
            ]);

            $cancellationRequest->Events()->create([
                'actor_id' => $user->id,
                'type'     => 'aborted_by_uncancellation',
                'meta'     => [
                    'uncancellation_request_id' => $request->id,
                ],
            ]);
        }

        return $openRequests->pluck('id')->all();
    }

    private function captureUncancellationTargets(UncancellationRequest $request): array
    {
        $request->loadMissing(['Note.WorkFormsAny', 'Orders']);

        return [
            'note'      => $request->scope === CancellationRequestScope::NOTE_FULL && (bool) $request->Note?->canceled,
            'order_ids' => $request->scope === CancellationRequestScope::WORK_FORM_ONLY
                ? []
                : $request->Orders->where('canceled', true)->pluck('id')->values()->all(),
            'work_report_ids' => $this->canceledWorkReportsInScope($request)->pluck('id')->values()->all(),
        ];
    }

    private function targetsFromDoneEvent(UncancellationRequest $request): ?array
    {
        $event = $request->Events
            ->where('event', 'done')
            ->sortByDesc('created_at')
            ->first();

        $targets = $event?->payload['affected_targets'] ?? null;

        return is_array($targets) ? $targets : null;
    }

    private function fallbackReversalTargets(UncancellationRequest $request): array
    {
        $request->loadMissing(['Note.WorkFormsAny', 'Orders']);

        return [
            'note'      => $request->scope === CancellationRequestScope::NOTE_FULL,
            'order_ids' => $request->scope === CancellationRequestScope::WORK_FORM_ONLY
                ? []
                : $request->Orders->pluck('id')->values()->all(),
            'work_report_ids' => $this->workReportsInScope($request)->pluck('id')->values()->all(),
        ];
    }

    private function hasOpenCancellationRequest(Note $note, string $scope, array $orderIds = [], array $workReportIds = []): bool
    {
        return $this->openCancellationRequestQuery($note, $scope, $orderIds, $workReportIds)->exists();
    }

    private function openCancellationRequestQuery(Note $note, string $scope, array $orderIds = [], array $workReportIds = [])
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
            })
            ->when($scope === CancellationRequestScope::WORK_FORM_ONLY->value && !empty($workReportIds), function ($query) use ($workReportIds) {
                // Solicitação legada (sem informe escolhido) vale para todos os informes da nota.
                $query->where(function ($q) use ($workReportIds) {
                    $q->whereDoesntHave('WorkReports')
                        ->orWhereHas('WorkReports', fn ($w) => $w->whereIn('work_reports.id', $workReportIds));
                });
            });
    }

    private function ensureNoOpenRequest(Note $note, string $scope, Collection $orders, ?Collection $workReports = null): void
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

        if ($scope === CancellationRequestScope::WORK_FORM_ONLY->value) {
            $reportIds = ($workReports ?? new Collection())->pluck('id')->all();
            $query->where(function ($q) use ($reportIds) {
                $q->whereDoesntHave('WorkReports')
                    ->orWhereHas('WorkReports', fn ($w) => $w->whereIn('work_reports.id', $reportIds));
            });
        }

        if ($query->exists()) {
            throw new RuntimeException('Já existe solicitação de descancelamento em aberto para este escopo.');
        }
    }

    /** Informes que a solicitação abrange: os escolhidos; sem escolha (legado) ou nota inteira, todos. */
    private function workReportsInScope(UncancellationRequest $request): Collection
    {
        if ($request->scope === CancellationRequestScope::WORK_FORM_ONLY) {
            $selected = $request->WorkReports()->get();

            if ($selected->isNotEmpty()) {
                return $selected;
            }
        }

        if (in_array($request->scope, [CancellationRequestScope::NOTE_FULL, CancellationRequestScope::WORK_FORM_ONLY], true)) {
            return $request->Note->WorkFormsAny()->get();
        }

        return new Collection();
    }

    private function canceledWorkReportsInScope(UncancellationRequest $request): Collection
    {
        return $this->workReportsInScope($request)->where('canceled', true)->values();
    }

    /** Usa o model (não update em massa) para atualizar o status materializado do informe. */
    private function uncancelWorkReports(Note $note, ?UncancellationRequest $request = null): void
    {
        $reports = $request
            ? $this->workReportsInScope($request)
            : $note->WorkFormsAny()->get();

        foreach ($reports->where('canceled', true) as $report) {
            $report->update([
                'canceled'    => false,
                'canceled_at' => null,
                'canceled_by' => null,
            ]);
        }
    }

    private function logEvent(UncancellationRequest $request, User $user, string $event, array $payload = []): void
    {
        $request->Events()->create([
            'user_id' => $user->id,
            'event'   => $event,
            'payload' => empty($payload) ? null : $payload,
        ]);
    }

    private function isSupervisor(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management);
    }
}
