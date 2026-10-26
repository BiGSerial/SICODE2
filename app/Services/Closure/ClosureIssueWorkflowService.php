<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureIssueEvent, ClosureIssueSubcategory, ClosureOrderState, ClosureWorkMessage};
use App\Models\{Company, User};
use App\Support\EvidenceFileUploader;
use Illuminate\Support\Facades\DB;

class ClosureIssueWorkflowService
{
    public const ORIGIN_INTERNAL = 'INTERNAL';
    public const ORIGIN_PARTNER  = 'PARTNER';

    /** Resposta da parceira a cada exigência do pedido (evento RESPONDED, meta.answers). */
    public const ANSWER_DONE   = 'DONE';
    public const ANSWER_NA     = 'NA';
    public const ANSWER_UNABLE = 'UNABLE';

    public const ANSWER_LABELS = [
        self::ANSWER_DONE   => 'Atendido',
        self::ANSWER_NA     => 'Não se aplica',
        self::ANSWER_UNABLE => 'Não consigo atender',
    ];

    public function open(
        ClosureOrderState $state,
        ClosureIssueCategory $category,
        ClosureIssueSubcategory $subcategory,
        Company $destination,
        User $actor,
        ?string $description,
        bool $blocking = true,
        array $requirements = [],
    ): ClosureIssue {
        return DB::transaction(function () use ($state, $category, $subcategory, $destination, $actor, $description, $blocking, $requirements) {
            $state       = ClosureOrderState::query()->whereKey($state->id)->lockForUpdate()->firstOrFail();
            $category    = ClosureIssueCategory::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();
            $subcategory = ClosureIssueSubcategory::query()->whereKey($subcategory->id)->lockForUpdate()->firstOrFail();

            $this->assertTaxonomyCanBeUsed($category, $subcategory);
            $description = $this->validatedDescription($subcategory, $description);

            $issue = ClosureIssue::query()->create([
                'order_id'                     => $state->order_id,
                'closure_order_state_id'       => $state->id,
                'closure_issue_category_id'    => $category->id,
                'closure_issue_subcategory_id' => $subcategory->id,
                'destination_company_id'       => $destination->id,
                'opened_by'                    => $actor->id,
                'status'                       => ClosureIssue::STATUS_OPEN,
                'cycle'                        => 1,
                'blocking'                     => $blocking,
                'description'                  => $description,
                ...($this->supportsRequirements() ? ['requirements' => $this->normalizedRequirements($requirements) ?: null] : []),
                'block_state_snapshot' => $blocking ? $this->stateSnapshot($state) : null,
                'opened_at'            => now(),
            ]);

            $this->event($issue, $actor, 'OPENED', self::ORIGIN_INTERNAL, null, ClosureIssue::STATUS_OPEN);

            if ($blocking) {
                app(ClosureOrderWorkflowService::class)->blockForIssue($state, $actor, $issue->id, $subcategory->name);
            }

            return $issue->fresh();
        }, 3);
    }

    /**
     * Abre a pendência e já a envia à parceira. Categoria e subcategoria são
     * obrigatórias (e validadas por open()).
     */
    public function requestToPartner(
        ClosureOrderState $state,
        ClosureIssueCategory $category,
        ClosureIssueSubcategory $subcategory,
        Company $destination,
        User $actor,
        ?string $description,
        ?string $message = null,
        bool $blocking = true,
        array $requirements = [],
    ): ClosureIssue {
        return DB::transaction(function () use ($state, $category, $subcategory, $destination, $actor, $description, $message, $blocking, $requirements) {
            $issue = $this->open($state, $category, $subcategory, $destination, $actor, $description, $blocking, $requirements);

            return $this->request($issue, $actor, $message ?? $description);
        }, 3);
    }

    public function request(ClosureIssue $issue, User $actor, ?string $message = null): ClosureIssue
    {
        return $this->transition($issue, $actor, 'REQUESTED', self::ORIGIN_INTERNAL, [ClosureIssue::STATUS_OPEN, ClosureIssue::STATUS_RETURNED], ClosureIssue::STATUS_REQUESTED, $message);
    }

    /**
     * Retorno da parceira. Rico: resposta por exigência (`$answers`, na ordem das exigências do pedido: status + justificativa),
     * mensagem geral e anexos de evidência. Chamadores antigos que passam só a mensagem continuam válidos (`$answers = null`
     * não valida item a item); quando `$answers` é informado, TODA exigência precisa de resposta e itens "não se aplica"/"não
     * consigo" exigem justificativa.
     *
     * @param array<int, array{status?: string, note?: string}>|null $answers
     * @param array<int, mixed> $attachments arquivos enviados (UploadedFile)
     */
    /** Origem gravada nas evidências de um retorno: prefixo + id do evento RESPONDED. */
    public const REPLY_ORIGIN_PREFIX = 'CLOSURE_REPLY:';

    public function respond(ClosureIssue $issue, Company $company, User $actor, string $message, ?array $answers = null, array $attachments = []): ClosureIssue
    {
        $normalized = $this->normalizedAnswers($issue, $answers);
        $message    = trim($message);

        if ($message === '' && $normalized === []) {
            throw new ClosureIssueWorkflowException('Informe o retorno da pendência.');
        }

        // O chat da obra recebe sempre um texto: a mensagem da parceira ou, sem ela, o resumo das respostas por item.
        $body = $message !== '' ? $message : $this->answersSummary($normalized);
        $meta = array_filter(['answers' => $normalized ?: null, 'attachments' => $attachments !== [] ? count($attachments) : null]);

        return DB::transaction(function () use ($issue, $company, $actor, $body, $meta, $attachments) {
            $issue = $this->transition($issue, $actor, 'RESPONDED', self::ORIGIN_PARTNER, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], ClosureIssue::STATUS_RESPONDED, $body, $company, $meta);

            if ($attachments !== []) {
                $this->storeReplyEvidence($issue, $actor, $attachments);
            }

            return $issue;
        }, 3);
    }

    /**
     * Guarda as evidências na mensagem do chat criada por ESTE retorno (a última da parceira nesta pendência, dentro da mesma
     * transação) e marca cada arquivo com a origem `CLOSURE_REPLY:{id do evento RESPONDED}`: assim cada retorno sabe exatamente
     * quais arquivos são dele (eventos são imutáveis, então a ligação fica no arquivo). Se algo falhar depois do upload, os
     * arquivos físicos são removidos pelo próprio uploader.
     *
     * @param array<int, mixed> $attachments
     */
    private function storeReplyEvidence(ClosureIssue $issue, User $actor, array $attachments): void
    {
        $chatMessage = ClosureWorkMessage::query()
            ->where('closure_issue_id', $issue->id)->where('author_id', $actor->id)
            ->orderByDesc('id')->first();
        $eventId = $issue->Events()->where('type', 'RESPONDED')->orderByDesc('id')->value('id');

        if (!$chatMessage || !$eventId) {
            return;
        }

        $ref = ($issue->Order?->Note?->note ?? 'nota') . '-' . ($issue->Order?->ordem ?? 'ordem');

        app(EvidenceFileUploader::class)->storeClosureWorkMessageEvidence($chatMessage, $attachments, $actor, (string) $ref, self::REPLY_ORIGIN_PREFIX . $eventId);
    }

    /**
     * @param array<int, array{status?: string, note?: string}>|null $answers
     *
     * @return array<int, array{index: int, text: string, status: string, note: ?string}>
     */
    private function normalizedAnswers(ClosureIssue $issue, ?array $answers): array
    {
        $requirements = array_values($issue->requirements ?? []);

        if ($answers === null || $requirements === []) {
            return [];
        }

        $normalized = [];

        foreach ($requirements as $position => $text) {
            $number = $position + 1;
            $status = $answers[$position]['status'] ?? null;
            $note   = trim((string) ($answers[$position]['note'] ?? ''));

            if (!isset(self::ANSWER_LABELS[$status])) {
                throw new ClosureIssueWorkflowException("Responda o item {$number} do pedido.");
            }

            if ($status !== self::ANSWER_DONE && $note === '') {
                throw new ClosureIssueWorkflowException("Explique o item {$number}: informe o motivo de ele não ter sido atendido.");
            }

            $normalized[] = ['index' => $number, 'text' => (string) $text, 'status' => $status, 'note' => $note !== '' ? $note : null];
        }

        return $normalized;
    }

    /** @param array<int, array{index: int, text: string, status: string, note: ?string}> $answers */
    private function answersSummary(array $answers): string
    {
        return collect($answers)
            ->map(fn (array $a) => "{$a['index']}. " . self::ANSWER_LABELS[$a['status']] . ($a['note'] ? " — {$a['note']}" : ''))
            ->implode("\n");
    }

    public function validate(ClosureIssue $issue, User $actor, ?string $message = null): ClosureIssue
    {
        return $this->transition($issue, $actor, 'VALIDATING', self::ORIGIN_INTERNAL, [ClosureIssue::STATUS_RESPONDED], ClosureIssue::STATUS_VALIDATING, $message);
    }

    public function returnForCorrection(ClosureIssue $issue, User $actor, string $reason): ClosureIssue
    {
        if (trim($reason) === '') {
            throw new ClosureIssueWorkflowException('Informe o motivo da devolução para correção.');
        }

        return DB::transaction(function () use ($issue, $actor, $reason) {
            $issue = ClosureIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($issue, [ClosureIssue::STATUS_RESPONDED, ClosureIssue::STATUS_VALIDATING]);
            $before = $issue->status;
            $issue->update(['status' => ClosureIssue::STATUS_RETURNED, 'cycle' => $issue->cycle + 1]);
            $this->event($issue, $actor, 'RETURNED_FOR_CORRECTION', self::ORIGIN_INTERNAL, $before, $issue->status, $reason);

            $work = app(ClosureWorkService::class);
            $work->recordIssueMessage($issue, $actor, $work->internalRoleFor($actor), $reason);
            app(ClosureNotifier::class)->issueReturnedToPartner($issue, $actor);

            return $issue->fresh();
        }, 3);
    }

    public function resolve(ClosureIssue $issue, User $actor, ?string $message = null): ClosureIssue
    {
        return DB::transaction(function () use ($issue, $actor, $message) {
            $issue = ClosureIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($issue, [ClosureIssue::STATUS_RESPONDED, ClosureIssue::STATUS_VALIDATING]);
            $before = $issue->status;
            $issue->update(['status' => ClosureIssue::STATUS_RESOLVED, 'resolved_at' => now()]);
            $this->event($issue, $actor, 'RESOLVED', self::ORIGIN_INTERNAL, $before, $issue->status, $message);

            if ($issue->blocking && !$this->hasAnotherOpenBlockingIssue($issue)) {
                app(ClosureOrderWorkflowService::class)->restoreAfterIssue($issue->State()->firstOrFail(), $actor, $issue->id, $issue->block_state_snapshot ?: []);
            }

            return $issue->fresh();
        }, 3);
    }

    /**
     * Desbloqueio pelo operador, sem depender da parceira: encerra a pendência em qualquer ponto do ciclo
     * (aberta, solicitada, respondida, em validação ou devolvida) e devolve a Ordem ao ponto anterior.
     */
    public function release(ClosureIssue $issue, User $actor, string $reason): ClosureIssue
    {
        if (trim($reason) === '') {
            throw new ClosureIssueWorkflowException('Informe o motivo do desbloqueio.');
        }

        return DB::transaction(function () use ($issue, $actor, $reason) {
            $issue = ClosureIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($issue, [
                ClosureIssue::STATUS_OPEN,
                ClosureIssue::STATUS_REQUESTED,
                ClosureIssue::STATUS_RESPONDED,
                ClosureIssue::STATUS_VALIDATING,
                ClosureIssue::STATUS_RETURNED,
            ]);
            $before = $issue->status;
            $issue->update(['status' => ClosureIssue::STATUS_RESOLVED, 'resolved_at' => now()]);
            $this->event($issue, $actor, 'RELEASED_BY_OPERATOR', self::ORIGIN_INTERNAL, $before, $issue->status, $reason);

            $state = $issue->State()->firstOrFail();

            if ($issue->blocking && !$this->hasAnotherOpenBlockingIssue($issue) && $state->operational_status === ClosureOrderWorkflowService::STATUS_WAITING_PARTNER) {
                app(ClosureOrderWorkflowService::class)->restoreAfterIssue($state, $actor, $issue->id, $issue->block_state_snapshot ?: []);
            }

            $work = app(ClosureWorkService::class);
            $work->recordIssueMessage($issue, $actor, $work->internalRoleFor($actor), 'Pendência desbloqueada pelo operador: ' . trim($reason));

            return $issue->fresh();
        }, 3);
    }

    private function supportsRequirements(): bool
    {
        // Sem cache: roda só ao abrir uma pendência e tolera o esquema mudar entre execuções (migration ainda pendente).
        return \Illuminate\Support\Facades\Schema::hasColumn('closure_issues', 'requirements');
    }

    /** @return array<int, string> itens de exigência limpos (sem vazios), até 30 de 500 caracteres */
    private function normalizedRequirements(array $requirements): array
    {
        return collect($requirements)
            ->map(fn ($text) => mb_substr(trim((string) $text), 0, 500))
            ->filter()
            ->values()
            ->take(30)
            ->all();
    }

    private function transition(ClosureIssue $issue, User $actor, string $type, string $origin, array $allowed, string $next, ?string $message, ?Company $partnerCompany = null, array $meta = []): ClosureIssue
    {
        return DB::transaction(function () use ($issue, $actor, $type, $origin, $allowed, $next, $message, $partnerCompany, $meta) {
            $issue = ClosureIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($issue, $allowed);

            if ($partnerCompany && (string) $issue->destination_company_id !== (string) $partnerCompany->id) {
                throw new ClosureIssueWorkflowException('A empresa informada não é a destinatária desta pendência.');
            }

            $before = $issue->status;
            $dates  = ['status' => $next];

            if ($next === ClosureIssue::STATUS_REQUESTED) {
                $dates['requested_at'] = now();
            }

            if ($next === ClosureIssue::STATUS_RESPONDED) {
                $dates['responded_at'] = now();
            }
            $issue->update($dates);
            $this->event($issue, $actor, $type, $origin, $before, $next, $message, $meta);
            $this->collaborate($issue, $actor, $next, $message);

            return $issue->fresh();
        }, 3);
    }

    /** Espelha a transição no chat da obra e notifica o destinatário certo (após commit). */
    private function collaborate(ClosureIssue $issue, User $actor, string $next, ?string $message): void
    {
        $work     = app(ClosureWorkService::class);
        $notifier = app(ClosureNotifier::class);

        if ($next === ClosureIssue::STATUS_REQUESTED) {
            $work->recordIssueMessage($issue, $actor, $work->internalRoleFor($actor), (string) $message);
            $notifier->issueRequestedToPartner($issue, $actor);
        }

        if ($next === ClosureIssue::STATUS_RESPONDED) {
            $work->recordIssueMessage($issue, $actor, ClosureWorkMessage::ROLE_PARTNER, (string) $message);
            $notifier->partnerResponded($issue, $actor);
        }
    }

    private function assertTaxonomyCanBeUsed(ClosureIssueCategory $category, ClosureIssueSubcategory $subcategory): void
    {
        if (!$category->active || !$subcategory->active) {
            throw new ClosureIssueWorkflowException('A categoria ou subcategoria selecionada está inativa.');
        }

        if ((int) $subcategory->closure_issue_category_id !== (int) $category->id) {
            throw new ClosureIssueWorkflowException('A subcategoria não pertence à categoria selecionada.');
        }
    }

    private function validatedDescription(ClosureIssueSubcategory $subcategory, ?string $description): ?string
    {
        $description = trim($description ?? '');

        if ($subcategory->requires_description && $description === '') {
            throw new ClosureIssueWorkflowException('Esta subcategoria exige uma descrição.');
        }

        return $description === '' ? null : $description;
    }

    private function requireStatus(ClosureIssue $issue, array $allowed): void
    {
        if (!in_array($issue->status, $allowed, true)) {
            throw new ClosureIssueWorkflowException('A transição não é permitida no estado atual da pendência.');
        }
    }

    private function hasAnotherOpenBlockingIssue(ClosureIssue $issue): bool
    {
        return ClosureIssue::query()
            ->where('closure_order_state_id', $issue->closure_order_state_id)
            ->where('blocking', true)
            ->where('id', '!=', $issue->id)
            ->where('status', '!=', ClosureIssue::STATUS_RESOLVED)
            ->exists();
    }

    /** @param array<string, mixed> $extra dados estruturados do evento (ex.: respostas por exigência), gravados em meta */
    private function event(ClosureIssue $issue, User $actor, string $type, string $origin, ?string $before, ?string $after, ?string $message = null, array $extra = []): void
    {
        $meta = array_filter(['message' => $message === null || trim($message) === '' ? null : trim($message)] + $extra, fn ($value) => $value !== null);

        ClosureIssueEvent::query()->create([
            'closure_issue_id' => $issue->id,
            'actor_id'         => $actor->id,
            'type'             => $type,
            'origin'           => $origin,
            'before_status'    => $before,
            'after_status'     => $after,
            'meta'             => $meta ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    private function stateSnapshot(ClosureOrderState $state): array
    {
        return [
            'operational_status'        => $state->operational_status,
            'assigned_user_id'          => $state->assigned_user_id,
            'current_holder_type'       => $state->current_holder_type,
            'current_holder_user_id'    => $state->current_holder_user_id,
            'current_holder_company_id' => $state->current_holder_company_id,
        ];
    }
}
