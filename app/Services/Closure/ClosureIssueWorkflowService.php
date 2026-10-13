<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureIssueEvent, ClosureIssueSubcategory, ClosureOrderState};
use App\Models\{Company, User};
use Illuminate\Support\Facades\DB;

class ClosureIssueWorkflowService
{
    public const ORIGIN_INTERNAL = 'INTERNAL';
    public const ORIGIN_PARTNER  = 'PARTNER';

    public function open(
        ClosureOrderState $state,
        ClosureIssueCategory $category,
        ClosureIssueSubcategory $subcategory,
        Company $destination,
        User $actor,
        ?string $description,
        bool $blocking = true,
    ): ClosureIssue {
        return DB::transaction(function () use ($state, $category, $subcategory, $destination, $actor, $description, $blocking) {
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
                'block_state_snapshot'         => $blocking ? $this->stateSnapshot($state) : null,
                'opened_at'                    => now(),
            ]);

            $this->event($issue, $actor, 'OPENED', self::ORIGIN_INTERNAL, null, ClosureIssue::STATUS_OPEN);

            if ($blocking) {
                app(ClosureOrderWorkflowService::class)->blockForIssue($state, $actor, $issue->id, $subcategory->name);
            }

            return $issue->fresh();
        }, 3);
    }

    public function request(ClosureIssue $issue, User $actor, ?string $message = null): ClosureIssue
    {
        return $this->transition($issue, $actor, 'REQUESTED', self::ORIGIN_INTERNAL, [ClosureIssue::STATUS_OPEN, ClosureIssue::STATUS_RETURNED], ClosureIssue::STATUS_REQUESTED, $message);
    }

    public function respond(ClosureIssue $issue, Company $company, User $actor, string $message): ClosureIssue
    {
        if (trim($message) === '') {
            throw new ClosureIssueWorkflowException('Informe o retorno da pendência.');
        }

        return $this->transition($issue, $actor, 'RESPONDED', self::ORIGIN_PARTNER, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], ClosureIssue::STATUS_RESPONDED, $message, $company);
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

    private function transition(ClosureIssue $issue, User $actor, string $type, string $origin, array $allowed, string $next, ?string $message, ?Company $partnerCompany = null): ClosureIssue
    {
        return DB::transaction(function () use ($issue, $actor, $type, $origin, $allowed, $next, $message, $partnerCompany) {
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
            $this->event($issue, $actor, $type, $origin, $before, $next, $message);

            return $issue->fresh();
        }, 3);
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
        $description = $description === null ? null : trim($description);

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

    private function event(ClosureIssue $issue, User $actor, string $type, string $origin, ?string $before, ?string $after, ?string $message = null): void
    {
        ClosureIssueEvent::query()->create([
            'closure_issue_id' => $issue->id,
            'actor_id'         => $actor->id,
            'type'             => $type,
            'origin'           => $origin,
            'before_status'    => $before,
            'after_status'     => $after,
            'meta'             => $message === null || trim($message) === '' ? null : ['message' => trim($message)],
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
