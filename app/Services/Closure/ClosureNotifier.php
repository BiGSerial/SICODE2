<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureInternalReturn, ClosureIssue, ClosureOrderState, ClosurePartnerSignal, ClosurePriorityRequest, ClosureTransferRequest, ClosureWorkMessage};
use App\Models\{Company, Order, User};
use App\Notifications\SystemNotification;
use App\Services\PartnerAccess\PartnerAccessGate;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Route};

/**
 * Notificações (sino + toast) do módulo Encerramento.
 *
 * Contrato:
 * - chamado SOMENTE pelos services, na transição de estado (nunca em render/poll);
 * - o envio é adiado com DB::afterCommit (retry/rollback não notifica);
 * - nunca notifica o autor da ação;
 * - dedupe por evento+entidade+destinatário (Cache::add, DEDUPE_TTL segundos).
 */
class ClosureNotifier
{
    public const DEDUPE_TTL = 60;

    public const PARTNER_PERMISSION = 'closure.issues.list';

    public const RI_SENT      = 'sent';
    public const RI_RETURNED  = 'returned';
    public const RI_CANCELLED = 'cancelled';
    public const RI_REOPENED  = 'reopened';

    public function partnerResponded(ClosureIssue $issue, User $actor): void
    {
        $issue->loadMissing(['Order.Note', 'State']);

        $this->deliver(
            event: 'closure.partner_responded',
            entity: "issue:{$issue->id}:c{$issue->cycle}",
            authorId: $actor->id,
            recipients: fn () => $this->internalUsers([
                $issue->opened_by,
                $issue->State?->assigned_user_id,
                $issue->State?->current_holder_user_id,
            ]),
            title: 'Parceira respondeu à pendência',
            message: $this->context($issue->Order) . ': a parceira respondeu à pendência #' . $issue->id . '.',
            status: 'message',
            link: fn (User $recipient) => $this->workLink($issue->Order, $recipient, ClosureNavContext::DISPATCH),
            extras: $this->extras($issue),
        );
    }

    public function issueRequestedToPartner(ClosureIssue $issue, User $actor): void
    {
        $this->notifyPartnerOfIssue($issue, $actor, 'closure.issue_requested', 'Nova pendência de encerramento', 'assignment', 'enviou uma pendência para a sua empresa');
    }

    public function issueReturnedToPartner(ClosureIssue $issue, User $actor): void
    {
        $this->notifyPartnerOfIssue($issue, $actor, 'closure.issue_returned', 'Pendência devolvida para correção', 'warning', 'devolveu a pendência para correção');
    }

    public function orderAssigned(ClosureOrderState $state, User $actor): void
    {
        $state->loadMissing('Order.Note');

        if (!$state->assigned_user_id) {
            return;
        }

        $this->deliver(
            event: 'closure.order_assigned',
            entity: "state:{$state->id}:u{$state->assigned_user_id}:" . optional($state->entered_current_status_at)->timestamp,
            authorId: $actor->id,
            recipients: fn () => $this->internalUsers([$state->assigned_user_id]),
            title: 'Ordem atribuída a você',
            message: $this->context($state->Order) . ' foi atribuída a você no Encerramento.',
            status: 'assignment',
            link: fn (User $recipient) => $this->workLink($state->Order, $recipient, ClosureNavContext::SERVICE),
            extras: ['order_id' => $state->order_id, 'note_id' => $state->Order?->note_id],
        );
    }

    /** Bloqueio/liberação de depósito sinalizado pela parceira: avisa gestores e os responsáveis pelas Ordens afetadas. */
    public function partnerSignalChanged(ClosurePartnerSignal $signal, User $actor, bool $blocked): void
    {
        $signal->loadMissing('Company');
        $company = 'Depósito ' . $signal->deposit_code . ($signal->deposit_label ? " ({$signal->deposit_label})" : '') . ($signal->origin === ClosurePartnerSignal::ORIGIN_CALENDAR ? ' — inventário' : '');

        $this->deliver(
            event: $blocked ? 'closure.partner_deposit_blocked' : 'closure.partner_deposit_released',
            entity: "signal:{$signal->id}:" . ($blocked ? 'blocked' : 'released'),
            authorId: $actor->id,
            recipients: function () use ($signal) {
                $stateIds  = $signal->Items()->pluck('closure_order_state_id');
                $assignees = ClosureOrderState::query()->whereIn('id', $stateIds)->get(['assigned_user_id', 'current_holder_user_id']);

                return $this->dispatchers()->merge($this->internalUsers(array_merge($assignees->pluck('assigned_user_id')->all(), $assignees->pluck('current_holder_user_id')->all())));
            },
            title: $blocked ? 'Depósito bloqueado' : 'Depósito liberado',
            message: $blocked
                ? "{$company} bloqueado: {$signal->affected_count} Ordem(ns) passaram para Depósito Bloqueado. " . $this->excerpt($signal->reason)
                : "{$company} liberado: {$signal->released_count} Ordem(ns) voltaram ao status anterior.",
            status: $blocked ? 'warning' : 'info',
            link: fn (User $recipient) => ClosureServiceLocator::url($recipient->can('closure.dispatch') ? 'dispatch.stack' : 'services.accompany'),
            extras: ['signal_id' => $signal->id, 'company_id' => $signal->company_id],
        );
    }

    /** RI (Retorno Interno) com o Desenho: avisa gestores, responsáveis pelas Ordens da obra e quem enviou. */
    public function internalReturnChanged(ClosureInternalReturn $internalReturn, ?User $actor, string $kind): void
    {
        $internalReturn->loadMissing('Note');
        $note = $internalReturn->Note?->note ?? '#' . $internalReturn->note_id;

        [$title, $message, $status] = match ($kind) {
            self::RI_SENT      => ['RI enviado ao Desenho', "Obra {$note}: as Ordens ficam travadas até o Desenho retornar. " . $this->excerpt($internalReturn->message), 'warning'],
            self::RI_RETURNED  => ['RI retornado pelo Desenho', "Obra {$note}: o Desenho concluiu o RI e as Ordens foram destravadas.", 'success'],
            self::RI_CANCELLED => ['RI cancelado', "Obra {$note}: a gestão cancelou a espera pelo RI e as Ordens foram destravadas. " . $this->excerpt($internalReturn->cancel_reason), 'info'],
            default            => ['RI reaberto pelo Desenho', "Obra {$note}: o Desenho reabriu o retorno interno. As Ordens NÃO foram travadas de novo; se precisar aguardar, envie um novo RI.", 'warning'],
        };

        $this->deliver(
            event: 'closure.internal_return_' . $kind,
            entity: "ri:{$internalReturn->id}:{$kind}",
            authorId: $actor?->id,
            recipients: fn () => $this->dispatchers()->merge($this->noteParticipants((int) $internalReturn->note_id))->merge($this->internalUsers([$internalReturn->sent_by])),
            title: $title,
            message: $message,
            status: $status,
            link: fn (User $recipient) => $this->workLinkForNote((int) $internalReturn->note_id, null, $recipient, ClosureNavContext::SERVICE),
            extras: ['note_id' => $internalReturn->note_id, 'internal_return_id' => $internalReturn->id],
        );
    }

    public function priorityRequested(ClosurePriorityRequest $request): void
    {
        $request->loadMissing('Note');

        $this->deliver(
            event: 'closure.priority_requested',
            entity: "priority:{$request->id}",
            authorId: $request->requested_by,
            recipients: fn () => $this->dispatchers(),
            title: 'Pedido de prioridade',
            message: 'Foi solicitada prioridade para a obra ' . ($request->Note?->note ?? '#' . $request->note_id) . ': ' . $this->excerpt($request->reason),
            status: 'warning',
            link: fn (User $recipient) => $this->workLinkForNote($request->note_id, null, $recipient, ClosureNavContext::DISPATCH),
            extras: ['note_id' => $request->note_id, 'priority_request_id' => $request->id],
        );
    }

    public function priorityDecided(ClosurePriorityRequest $request): void
    {
        $request->loadMissing('Note');
        $accepted = $request->status === ClosurePriorityRequest::STATUS_ACCEPTED;

        $this->deliver(
            event: 'closure.priority_decided',
            entity: "priority:{$request->id}:{$request->status}",
            authorId: $request->decided_by,
            recipients: fn () => $this->internalUsers([$request->requested_by]),
            title: $accepted ? 'Prioridade atendida' : 'Prioridade recusada',
            message: 'O pedido de prioridade da obra ' . ($request->Note?->note ?? '#' . $request->note_id)
                . ($accepted ? ' foi atendido.' : ' foi recusado.')
                . ($request->decision_note ? ' ' . $this->excerpt($request->decision_note) : ''),
            status: 'info',
            link: fn (User $recipient) => $this->workLinkForNote($request->note_id, null, $recipient, ClosureNavContext::SERVICE),
            extras: ['note_id' => $request->note_id, 'priority_request_id' => $request->id],
        );
    }

    /** Transferência entre usuários: avisa o destinatário (enviada/cancelada) ou quem enviou (aceita/rejeitada). */
    public function transferChanged(ClosureTransferRequest $request, string $kind): void
    {
        $request->loadMissing(['From', 'To']);
        $count   = $request->Items()->count();
        $toSide  = in_array($kind, ['sent', 'cancelled'], true);
        $author  = $toSide ? $request->from_user_id : $request->to_user_id;
        $subject = $count . ' Ordem(ns)';

        [$title, $message, $status] = match ($kind) {
            'sent'     => ['Transferência para você', ($request->From?->name ?? 'Um usuário') . " quer transferir {$subject} para você. Aceite ou rejeite em Transferência. " . $this->excerpt($request->reason), 'assignment'],
            'accepted' => ['Transferência aceita', ($request->To?->name ?? 'O destinatário') . " aceitou a transferência de {$subject}.", 'success'],
            'rejected' => ['Transferência rejeitada', ($request->To?->name ?? 'O destinatário') . " rejeitou a transferência de {$subject}." . ($request->decision_note ? ' ' . $this->excerpt($request->decision_note) : ''), 'warning'],
            default    => ['Transferência cancelada', ($request->From?->name ?? 'Quem enviou') . " cancelou a transferência de {$subject} para você.", 'info'],
        };

        $this->deliver(
            event: 'closure.transfer_' . $kind,
            entity: "transfer:{$request->id}:{$kind}",
            authorId: $author,
            recipients: fn () => $this->internalUsers([$toSide ? $request->to_user_id : $request->from_user_id]),
            title: $title,
            message: $message,
            status: $status,
            link: fn (User $recipient) => ClosureServiceLocator::url('services.transfers') ?? ClosureServiceLocator::url('services.main'),
            extras: ['transfer_request_id' => $request->id],
        );
    }

    public function newWorkMessage(ClosureWorkMessage $message): void
    {
        $message->loadMissing(['Note', 'Issue', 'Order']);
        $fromPartner = $message->author_role === ClosureWorkMessage::ROLE_PARTNER;

        $this->deliver(
            event: 'closure.work_message',
            entity: "message:{$message->id}",
            authorId: $message->author_id,
            recipients: function () use ($message, $fromPartner) {
                $internal = $this->noteParticipants($message->note_id, $message->closure_issue_id);

                if ($fromPartner) {
                    return $internal;
                }

                if ($message->visibility !== ClosureWorkMessage::VISIBILITY_WITH_PARTNER) {
                    return $internal;
                }

                return $internal->merge($this->partnerUsers($this->partnerCompanyIds($message->note_id, $message->closure_issue_id)));
            },
            title: 'Nova mensagem na obra ' . ($message->Note?->note ?? '#' . $message->note_id),
            message: $this->excerpt($message->body),
            status: 'message',
            link: function (User $recipient) use ($message) {
                return $this->isPartnerRecipient($recipient, $message)
                    ? $this->partnerLink()
                    : $this->workLinkForNote($message->note_id, $message->order_id, $recipient, ClosureNavContext::DISPATCH);
            },
            extras: ['note_id' => $message->note_id, 'issue_id' => $message->closure_issue_id, 'message_id' => $message->id],
        );
    }

    private function notifyPartnerOfIssue(ClosureIssue $issue, User $actor, string $event, string $title, string $status, string $verb): void
    {
        $issue->loadMissing(['Order.Note']);

        $this->deliver(
            event: $event,
            entity: "issue:{$issue->id}:c{$issue->cycle}",
            authorId: $actor->id,
            recipients: fn () => $this->partnerUsers($issue->destination_company_id ? [(string) $issue->destination_company_id] : []),
            title: $title,
            message: $this->context($issue->Order) . ': o Encerramento ' . $verb . ' (#' . $issue->id . ').',
            status: $status,
            link: fn () => $this->partnerLink(),
            extras: $this->extras($issue),
        );
    }

    /**
     * @param callable(): iterable<User>          $recipients
     * @param string|callable(User): ?string|null $link
     * @param array<string, mixed>                $extras
     */
    private function deliver(string $event, string $entity, ?string $authorId, callable $recipients, string $title, string $message, string $status, mixed $link, array $extras = []): void
    {
        DB::afterCommit(function () use ($event, $entity, $authorId, $recipients, $title, $message, $status, $link, $extras) {
            $users = collect($recipients())->filter()->unique('id')->reject(fn (User $user) => $authorId !== null && (string) $user->id === (string) $authorId);

            foreach ($users as $user) {
                $dedupe = "closure:notify:{$event}:{$entity}:{$user->id}";

                if (!Cache::add($dedupe, 1, self::DEDUPE_TTL)) {
                    continue;
                }

                $user->notify(new SystemNotification(new UserNotificationData(
                    title: $title,
                    message: $message,
                    link: is_callable($link) ? $link($user) : $link,
                    status: $status,
                    extras: array_merge($extras, ['toast' => true, 'event' => $event, 'dedupe' => $dedupe]),
                )));
            }
        });
    }

    /** @return array<string, mixed> */
    private function extras(ClosureIssue $issue): array
    {
        return ['issue_id' => $issue->id, 'order_id' => $issue->order_id, 'note_id' => $issue->Order?->note_id];
    }

    /** @param array<int, string|null> $ids */
    private function internalUsers(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));

        return $ids === [] ? collect() : User::query()->whereIn('id', $ids)->get();
    }

    private function dispatchers(): Collection
    {
        return User::query()->where('closure_manager', true)->get();
    }

    /** Participantes internos da obra: responsáveis das Ordens e quem abriu pendências (da pendência, se informada). */
    private function noteParticipants(int $noteId, ?int $issueId = null): Collection
    {
        $states = ClosureOrderState::query()->whereHas('Order', fn ($q) => $q->where('note_id', $noteId))->get(['assigned_user_id', 'current_holder_user_id']);
        $issues = ClosureIssue::query()->whereHas('Order', fn ($q) => $q->where('note_id', $noteId))->when($issueId, fn ($q) => $q->whereKey($issueId))->get(['opened_by']);

        return $this->internalUsers(array_merge(
            $states->pluck('assigned_user_id')->all(),
            $states->pluck('current_holder_user_id')->all(),
            $issues->pluck('opened_by')->all(),
        ));
    }

    /** @return array<int, string> */
    private function partnerCompanyIds(int $noteId, ?int $issueId): array
    {
        if ($issueId) {
            $destination = ClosureIssue::query()->whereKey($issueId)->value('destination_company_id');

            return $destination ? [(string) $destination] : [];
        }

        return app(ClosureWorkService::class)->partnerCompanyIdsForNote($noteId);
    }

    /** @param array<int, string> $companyIds */
    private function partnerUsers(array $companyIds): Collection
    {
        if ($companyIds === []) {
            return collect();
        }

        $parents = Company::withTrashed()->whereIn('id', $companyIds)->whereNotNull('parent_id')->pluck('parent_id')->all();
        $ids     = array_values(array_unique(array_merge($companyIds, $parents)));

        return User::query()
            ->where(fn ($q) => $q->whereIn('company_id', $ids)->orWhereHas('Companies', fn ($c) => $c->whereIn('companies.id', $ids)))
            ->get()
            ->filter(fn (User $user) => PartnerAccessGate::allows($user, self::PARTNER_PERMISSION))
            ->values();
    }

    private function isPartnerRecipient(User $recipient, ClosureWorkMessage $message): bool
    {
        return app(ClosureWorkService::class)->accessLevel($message->Note, $recipient) === ClosureWorkService::LEVEL_PARTNER;
    }

    private function workLink(?Order $order, ?User $recipient = null, string $preferred = ClosureNavContext::DISPATCH): ?string
    {
        return $order ? $this->workLinkForNote($order->note_id, $order->id, $recipient, $preferred) : null;
    }

    /** O link carrega a origem (`ctx`) que o destinatário usa: o lado preferido pelo evento, ou o outro se ele só tiver essa permissão. */
    private function workLinkForNote(int $noteId, ?int $orderId = null, ?User $recipient = null, string $preferred = ClosureNavContext::DISPATCH): ?string
    {
        $ctx = $this->contextFor($recipient, $preferred);

        if (Route::has('closure.work.show')) {
            return route('closure.work.show', ['note' => $noteId] + ($ctx ? ['ctx' => $ctx] : []));
        }

        if (!$orderId) {
            $orderId = Order::query()->where('note_id', $noteId)->orderBy('id')->value('id');
        }

        return $orderId && Route::has('closure.order.detail') ? route('closure.order.detail', ['order' => $orderId] + ($ctx ? ['ctx' => $ctx] : [])) : null;
    }

    private function contextFor(?User $recipient, string $preferred): ?string
    {
        if (!$recipient) {
            return null;
        }

        $can   = [ClosureNavContext::DISPATCH => $recipient->can('closure.dispatch'), ClosureNavContext::SERVICE => $recipient->can('closure.work')];
        $other = $preferred === ClosureNavContext::DISPATCH ? ClosureNavContext::SERVICE : ClosureNavContext::DISPATCH;

        return $can[$preferred] ? $preferred : ($can[$other] ? $other : null);
    }

    private function partnerLink(): ?string
    {
        return Route::has('partner.closure.issues') ? route('partner.closure.issues') : null;
    }

    private function context(?Order $order): string
    {
        if (!$order) {
            return 'Encerramento';
        }

        return 'Nota ' . ($order->Note?->note ?? $order->note_id) . ' / Ordem ' . $order->ordem;
    }

    private function excerpt(?string $text): string
    {
        return \Illuminate\Support\Str::limit(trim(strip_tags((string) $text)), 140);
    }
}
