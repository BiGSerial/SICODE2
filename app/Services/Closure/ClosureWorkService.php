<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureIssue, ClosureOrderState, ClosurePriorityRequest, ClosureWorkMessage};
use App\Models\{Note, Order, User};
use App\Services\PartnerAccess\PartnerAccessGate;
use App\Support\EvidenceFileUploader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Gate};

/**
 * Visão e colaboração por OBRA (Nota): dados, Ordens, pendências e chat.
 */
class ClosureWorkService
{
    public const LEVEL_DISPATCHER = 'DISPATCHER';
    public const LEVEL_INTERNAL   = 'INTERNAL';
    public const LEVEL_PARTNER    = 'PARTNER';

    public const MAX_BODY = 5000;

    public function __construct(private readonly ClosureNotifier $notifier)
    {
    }

    /**
     * Nível de acesso do usuário à obra: DISPATCHER (gestor/despachante),
     * INTERNAL (tem Ordem atribuída na Nota), PARTNER (empresa que detém item
     * ou é destinatária de pendência da Nota) ou null (sem acesso).
     */
    public function accessLevel(Note $note, User $user): ?string
    {
        if (Gate::forUser($user)->allows('closure.dispatch')) {
            return self::LEVEL_DISPATCHER;
        }

        $hasAssignedOrder = ClosureOrderState::query()
            ->whereHas('Order', fn ($q) => $q->where('note_id', $note->id))
            ->where(fn ($q) => $q->where('assigned_user_id', $user->id)->orWhere('current_holder_user_id', $user->id))
            ->exists();

        if ($hasAssignedOrder) {
            return self::LEVEL_INTERNAL;
        }

        $visible = $this->visibleCompanyIds($user);

        if ($visible !== [] && array_intersect($visible, $this->partnerCompanyIdsForNote($note->id)) !== []) {
            return self::LEVEL_PARTNER;
        }

        return null;
    }

    public function canAccess(Note $note, User $user): bool
    {
        return $this->accessLevel($note, $user) !== null;
    }

    /** @return array<int, string> Empresas parceiras que detêm item ou são destinatárias de pendência da Nota. */
    public function partnerCompanyIdsForNote(int $noteId): array
    {
        $holders = ClosureOrderState::query()
            ->whereHas('Order', fn ($q) => $q->where('note_id', $noteId))
            ->whereNotNull('current_holder_company_id')
            ->pluck('current_holder_company_id');

        $destinations = ClosureIssue::query()
            ->whereHas('Order', fn ($q) => $q->where('note_id', $noteId))
            ->whereNotNull('destination_company_id')
            ->pluck('destination_company_id');

        return $holders->merge($destinations)->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    /**
     * Dados consolidados da obra. Com $viewer, aplica autorização e, para a
     * parceira, restringe Ordens/pendências/mensagem aos itens dela. Sem
     * $viewer, é leitura confiável (uso interno de serviços).
     *
     * @return array<string, mixed>
     */
    public function overview(Note $note, ?User $viewer = null): array
    {
        $level = $viewer ? $this->accessLevel($note, $viewer) : self::LEVEL_DISPATCHER;

        if ($level === null) {
            throw new ClosureWorkException('Você não tem acesso a esta obra.');
        }

        $partnerOnly = $level === self::LEVEL_PARTNER;
        $companyIds  = $partnerOnly ? $this->visibleCompanyIds($viewer) : [];

        $states = ClosureOrderState::query()
            ->with(['Order', 'Assignee', 'CurrentHolderCompany', 'Production.Company'])
            ->whereHas('Order', fn ($q) => $q->where('note_id', $note->id))
            ->get()
            ->sortBy(fn (ClosureOrderState $s) => (string) $s->Order?->ordem)
            ->values();

        $issues = ClosureIssue::query()
            ->with(['Order', 'Category', 'Subcategory', 'DestinationCompany'])
            ->whereHas('Order', fn ($q) => $q->where('note_id', $note->id))
            ->where('status', '!=', ClosureIssue::STATUS_RESOLVED)
            ->when($partnerOnly, fn ($q) => $q->whereIn('destination_company_id', $companyIds))
            ->orderBy('opened_at')
            ->get();

        if ($partnerOnly) {
            $partnerOrderIds = $issues->pluck('order_id')->merge(
                $states->filter(fn ($s) => in_array((string) $s->current_holder_company_id, $companyIds, true))->pluck('order_id')
            )->unique();
            $states = $states->filter(fn ($s) => $partnerOrderIds->contains($s->order_id))->values();
        }

        $openByOrder = $issues->groupBy('order_id')->map->count();
        $note->loadMissing('City');
        $company = $states->first()?->Production?->Company;

        $lastMessageQuery = ClosureWorkMessage::query()->with('Author:id,name')->where('note_id', $note->id);

        if ($partnerOnly) {
            $lastMessageQuery->visibleToPartner()->where(fn ($q) => $q->whereNull('closure_issue_id')->orWhereIn('closure_issue_id', $issues->pluck('id')));
        }

        $lastMessage = $lastMessageQuery->orderByDesc('id')->first();

        return [
            'level' => $level,
            'note'  => [
                'id'        => $note->id,
                'number'    => $note->note,
                'type'      => $note->type_note === 2 ? 'OV' : ($note->type_note === 1 ? 'EP' : null),
                'type_note' => $note->type_note,
                'city'      => $note->City?->cidade ?? $note->City?->municipio,
                'client'    => $note->client,
                'value'     => $note->value,
                'currency'  => $note->currency,
                'status'    => $note->status,
            ],
            'company' => $company ? ['id' => $company->id, 'name' => $company->name] : null,
            'orders'  => $states->map(fn (ClosureOrderState $s) => [
                'order_id'           => $s->order_id,
                'state_id'           => $s->id,
                'ordem'              => $s->Order?->ordem,
                'description'        => $s->Order?->descricao,
                'status_sist'        => $s->Order?->statusSist,
                'operational_status' => $s->operational_status,
                'priority'           => (bool) $s->priority,
                'assignee'           => $s->Assignee ? ['id' => $s->Assignee->id, 'name' => $s->Assignee->name] : null,
                'holder_type'        => $s->current_holder_type,
                'holder_company'     => $s->CurrentHolderCompany?->name,
                'since'              => $s->entered_current_status_at,
                'open_issues'        => (int) ($openByOrder[$s->order_id] ?? 0),
            ])->all(),
            'open_issues' => $issues->map(fn (ClosureIssue $i) => [
                'id'          => $i->id,
                'order_id'    => $i->order_id,
                'ordem'       => $i->Order?->ordem,
                'status'      => $i->status,
                'cycle'       => $i->cycle,
                'blocking'    => $i->blocking,
                'category'    => $i->Category?->name,
                'subcategory' => $i->Subcategory?->name,
                'destination' => $i->DestinationCompany?->name,
                'opened_at'   => $i->opened_at,
            ])->all(),
            'priority_pending' => $partnerOnly ? null : ClosurePriorityRequest::query()
                ->where('note_id', $note->id)->where('status', ClosurePriorityRequest::STATUS_PENDING)->first(),
            'has_priority' => $states->contains(fn ($s) => $s->priority),
            'last_message' => $lastMessage,
        ];
    }

    /**
     * @param array<int, mixed> $attachments UploadedFile[]
     */
    public function postMessage(
        Note $note,
        User $actor,
        string $body,
        string $visibility = ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY,
        ?Order $order = null,
        ?ClosureIssue $issue = null,
        array $attachments = [],
    ): ClosureWorkMessage {
        $level = $this->accessLevel($note, $actor);

        if ($level === null) {
            throw new ClosureWorkException('Você não tem acesso a esta obra.');
        }

        $body = trim($body);

        if ($body === '') {
            throw new ClosureWorkException('Escreva a mensagem.');
        }

        if (mb_strlen($body) > self::MAX_BODY) {
            throw new ClosureWorkException('A mensagem excede ' . self::MAX_BODY . ' caracteres.');
        }

        if (!in_array($visibility, [ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY, ClosureWorkMessage::VISIBILITY_WITH_PARTNER], true)) {
            throw new ClosureWorkException('Visibilidade inválida.');
        }

        if ($level === self::LEVEL_PARTNER && $visibility !== ClosureWorkMessage::VISIBILITY_WITH_PARTNER) {
            throw new ClosureWorkException('A parceira só pode enviar mensagens visíveis ao Encerramento.');
        }

        if ($order && (int) $order->note_id !== (int) $note->id) {
            throw new ClosureWorkException('A Ordem informada não pertence a esta obra.');
        }

        if ($issue) {
            $issue->loadMissing('Order');

            if ((int) $issue->Order?->note_id !== (int) $note->id) {
                throw new ClosureWorkException('A pendência informada não pertence a esta obra.');
            }

            if ($level === self::LEVEL_PARTNER && !in_array((string) $issue->destination_company_id, $this->visibleCompanyIds($actor), true)) {
                throw new ClosureWorkException('Esta pendência não pertence à sua empresa.');
            }

            $order ??= $issue->Order;
        }

        return DB::transaction(function () use ($note, $actor, $body, $visibility, $order, $issue, $level, $attachments) {
            $message = $this->createMessage($note->id, $actor, $level, $body, $visibility, $order?->id, $issue?->id);

            if ($attachments !== []) {
                app(EvidenceFileUploader::class)->storeClosureWorkMessageEvidence($message, $attachments, $actor, (string) $note->note);
            }

            $this->notifier->newWorkMessage($message);

            return $message->load(['Author', 'EvidenceFiles']);
        });
    }

    /**
     * Mensagens da obra visíveis para $viewer (ordem cronológica). A parceira
     * nunca recebe INTERNAL_ONLY nem mensagens de pendências de outra empresa.
     *
     * @return Collection<int, ClosureWorkMessage>
     */
    public function messages(Note $note, User $viewer, ?ClosureIssue $issue = null): Collection
    {
        $level = $this->accessLevel($note, $viewer);

        if ($level === null) {
            throw new ClosureWorkException('Você não tem acesso a esta obra.');
        }

        $query = ClosureWorkMessage::query()
            ->with(['Author', 'AuthorCompany', 'EvidenceFiles', 'Issue.Subcategory'])
            ->where('note_id', $note->id)
            ->when($issue, fn ($q) => $q->where('closure_issue_id', $issue->id));

        if ($level === self::LEVEL_PARTNER) {
            $companies = $this->visibleCompanyIds($viewer);
            $query->visibleToPartner()->where(function ($q) use ($companies) {
                $q->whereNull('closure_issue_id')
                    ->orWhereHas('Issue', fn ($i) => $i->whereIn('destination_company_id', $companies));
            });
        }

        return $query->orderBy('created_at')->orderBy('id')->get();
    }

    /**
     * Registro da mensagem de uma transição de pendência (chamado pelo
     * ClosureIssueWorkflowService, que já fez a autorização e notifica pelo
     * evento da transição; por isso não dispara newWorkMessage).
     */
    public function recordIssueMessage(ClosureIssue $issue, User $author, string $role, string $body): ?ClosureWorkMessage
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        $issue->loadMissing('Order');

        return ClosureWorkMessage::query()->create([
            'note_id'           => $issue->Order->note_id,
            'closure_issue_id'  => $issue->id,
            'order_id'          => $issue->order_id,
            'author_id'         => $author->id,
            'author_company_id' => $role === ClosureWorkMessage::ROLE_PARTNER ? $issue->destination_company_id : $author->company_id,
            'author_role'       => $role,
            'body'              => $body,
            'visibility'        => ClosureWorkMessage::VISIBILITY_WITH_PARTNER,
        ]);
    }

    /** Papel de um usuário interno ao registrar mensagens de transição. */
    public function internalRoleFor(User $user): string
    {
        return Gate::forUser($user)->allows('closure.dispatch') ? ClosureWorkMessage::ROLE_DISPATCHER : ClosureWorkMessage::ROLE_INTERNAL;
    }

    private function createMessage(int $noteId, User $actor, string $level, string $body, string $visibility, ?int $orderId, ?int $issueId): ClosureWorkMessage
    {
        return ClosureWorkMessage::query()->create([
            'note_id'           => $noteId,
            'closure_issue_id'  => $issueId,
            'order_id'          => $orderId,
            'author_id'         => $actor->id,
            'author_company_id' => $actor->company_id,
            'author_role'       => match ($level) {
                self::LEVEL_DISPATCHER => ClosureWorkMessage::ROLE_DISPATCHER,
                self::LEVEL_PARTNER    => ClosureWorkMessage::ROLE_PARTNER,
                default                => ClosureWorkMessage::ROLE_INTERNAL,
            },
            'body'       => $body,
            'visibility' => $visibility,
        ]);
    }

    /**
     * Autorização de leitura de evidência do Encerramento (mensagem do chat ou pendência).
     * Retorna null quando o dono da evidência não é do Encerramento (a decisão fica com o chamador).
     */
    public function canViewEvidenceOf(?\Illuminate\Database\Eloquent\Model $owner, User $user): ?bool
    {
        if ($owner instanceof ClosureWorkMessage) {
            $note  = $owner->Note;
            $level = $note ? $this->accessLevel($note, $user) : null;

            if ($level === null) {
                return false;
            }

            if ($level !== self::LEVEL_PARTNER) {
                return true;
            }

            if ($owner->visibility !== ClosureWorkMessage::VISIBILITY_WITH_PARTNER) {
                return false;
            }

            return $owner->closure_issue_id === null
                || in_array((string) $owner->Issue?->destination_company_id, $this->visibleCompanyIds($user), true);
        }

        if ($owner instanceof ClosureIssue) {
            $note  = $owner->Order?->Note;
            $level = $note ? $this->accessLevel($note, $user) : null;

            if ($level === null) {
                return false;
            }

            return $level !== self::LEVEL_PARTNER
                || in_array((string) $owner->destination_company_id, $this->visibleCompanyIds($user), true);
        }

        return null;
    }

    /** @return array<int, string> */
    private function visibleCompanyIds(User $user): array
    {
        return $user->superadm ? [] : PartnerAccessGate::visibleCompanyIdsFor($user);
    }
}
