<?php

namespace App\Services\D5;

use App\Models\FiveNote;
use App\Models\Production;
use App\Models\Service;
use App\Models\TimelineEvent;
use DomainException;

class D5WorkflowService
{
    protected ?string $fiscalizationServiceId = null;
    protected ?string $paymentServiceId = null;

    public function currentStage(FiveNote $five): string
    {
        if ($five->is_archived) {
            return 'archived';
        }

        if ($five->is_supervisioned) {
            return 'supervision_approved';
        }

        if ($five->is_completed) {
            return 'partner_done';
        }

        if ($five->visible_partner) {
            return 'released_to_partner';
        }

        return 'created';
    }

    public function onCreatedManual(FiveNote $five, ?string $actorUserId = null, ?Production $production = null): void
    {
        if (!$five->note_id) {
            throw new DomainException('D5 sem nota vinculada nao pode gerar evento de criacao.');
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_created_manual',
            'from_stage' => null,
            'to_stage' => 'created',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'PAGAMENTO',
            'owner_user_id' => $production?->user_id,
            'owner_role' => $production?->user_id ? 'PAGAMENTO' : null,
            'service_id' => $production?->service_id,
            'production_id' => $production?->id,
            'occurred_at' => $five->created_at ?? now(),
            'inferred' => false,
            'metadata' => ['source' => 'manual_create'],
        ]);
    }

    public function onCreatedFromSupervision(FiveNote $five, ?string $actorUserId = null, ?Production $production = null): void
    {
        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_created_from_supervision',
            'from_stage' => null,
            'to_stage' => 'created',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'FISCALIZACAO',
            'owner_user_id' => $production?->user_id,
            'owner_role' => $production?->user_id ? 'FISCALIZACAO' : null,
            'service_id' => $production?->service_id,
            'production_id' => $production?->id,
            'occurred_at' => $five->created_at ?? now(),
            'inferred' => false,
            'metadata' => ['source' => 'supervision_form'],
        ]);
    }

    public function onReleasedToPartner(FiveNote $five, ?string $fromStage, ?string $actorUserId = null, ?Production $production = null): void
    {
        if (!$five->visible_partner || !$five->is_payed) {
            throw new DomainException('Transicao invalida: para liberar a empreiteira, D5 deve estar paga e visivel.');
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_released_to_partner',
            'from_stage' => $fromStage,
            'to_stage' => 'released_to_partner',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'PAGAMENTO',
            'owner_role' => 'EMPREITEIRA',
            'service_id' => $production?->service_id,
            'production_id' => $production?->id,
            'occurred_at' => $five->dispatch_at ?? now(),
            'inferred' => false,
        ]);
    }

    public function onPartnerCompleted(FiveNote $five, ?string $fromStage, ?string $actorUserId = null): void
    {
        if (!$five->is_completed) {
            throw new DomainException('Transicao invalida: D5 precisa estar concluida pela empreiteira.');
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_partner_completed',
            'from_stage' => $fromStage,
            'to_stage' => 'partner_done',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'EMPREITEIRA',
            'owner_role' => 'FISCALIZACAO',
            'occurred_at' => $five->completed_at ?? now(),
            'inferred' => false,
        ]);
    }

    public function onReturnedWithPending(FiveNote $five, ?string $fromStage, ?string $actorUserId = null, ?Production $production = null): void
    {
        if (!$five->returned || $five->is_supervisioned) {
            throw new DomainException('Transicao invalida: retorno com pendencia exige returned=true e sem supervisao aprovada.');
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_returned_with_pending',
            'from_stage' => $fromStage,
            'to_stage' => 'returned_to_partner',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'FISCALIZACAO',
            'owner_role' => 'EMPREITEIRA',
            'service_id' => $production?->service_id,
            'production_id' => $production?->id,
            'occurred_at' => now(),
            'inferred' => false,
        ]);
    }

    public function onSupervisionApproved(FiveNote $five, ?string $fromStage, ?string $actorUserId = null, ?Production $production = null): void
    {
        if (!$five->is_supervisioned) {
            throw new DomainException('Transicao invalida: aprovacao da fiscalizacao exige is_supervisioned=true.');
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_supervision_approved',
            'from_stage' => $fromStage,
            'to_stage' => 'supervision_approved',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'FISCALIZACAO',
            'owner_role' => 'PAGAMENTO',
            'service_id' => $production?->service_id,
            'production_id' => $production?->id,
            'occurred_at' => $five->supervisioned_at ?? now(),
            'inferred' => false,
        ]);
    }

    public function onArchived(FiveNote $five, ?string $fromStage, ?string $actorUserId = null, ?Production $production = null): void
    {
        if (!$five->is_archived) {
            throw new DomainException('Transicao invalida: arquivamento exige is_archived=true.');
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_archived',
            'from_stage' => $fromStage,
            'to_stage' => 'archived',
            'actor_user_id' => $actorUserId,
            'actor_role' => 'PAGAMENTO',
            'owner_role' => 'CLOSED',
            'service_id' => $production?->service_id,
            'production_id' => $production?->id,
            'occurred_at' => now(),
            'inferred' => false,
        ]);
    }

    public function onProductionAssigned(
        FiveNote $five,
        Production $production,
        ?string $actorUserId = null,
        ?string $previousUserId = null
    ): void {
        $serviceRole = $this->resolveServiceRole($production->service_id);
        if (!$serviceRole) {
            return;
        }

        $newUserId = $production->user_id ?: null;
        if (!$newUserId) {
            return;
        }

        $eventType = ($previousUserId && $previousUserId !== $newUserId)
            ? 'd5_user_changed'
            : 'd5_user_assigned';

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => $eventType,
            'from_stage' => $this->currentStage($five),
            'to_stage' => $serviceRole === 'FISCALIZACAO' ? 'supervision_assigned' : 'payment_review',
            'actor_user_id' => $actorUserId,
            'actor_role' => $serviceRole,
            'owner_user_id' => $newUserId,
            'owner_role' => $serviceRole,
            'service_id' => $production->service_id,
            'production_id' => $production->id,
            'occurred_at' => $production->att_at ?? now(),
            'inferred' => false,
            'metadata' => [
                'previous_user_id' => $previousUserId,
                'new_user_id' => $newUserId,
            ],
        ]);
    }

    public function onProductionUnassigned(
        FiveNote $five,
        Production $production,
        ?string $actorUserId = null,
        ?string $previousUserId = null
    ): void {
        $serviceRole = $this->resolveServiceRole($production->service_id);
        if (!$serviceRole || !$previousUserId) {
            return;
        }

        $this->emitIfMissing([
            'five_note_id' => $five->id,
            'note_id' => $five->note_id,
            'event_type' => 'd5_user_unassigned',
            'from_stage' => $this->currentStage($five),
            'to_stage' => $serviceRole === 'FISCALIZACAO' ? 'supervision_queue' : 'payment_review',
            'actor_user_id' => $actorUserId,
            'actor_role' => $serviceRole,
            'owner_user_id' => null,
            'owner_role' => $serviceRole,
            'service_id' => $production->service_id,
            'production_id' => $production->id,
            'occurred_at' => now(),
            'inferred' => false,
            'metadata' => [
                'previous_user_id' => $previousUserId,
                'new_user_id' => null,
            ],
        ]);
    }

    protected function resolveServiceRole(?string $serviceId): ?string
    {
        if (!$serviceId) {
            return null;
        }

        $this->resolveServiceIds();

        if ($serviceId === $this->fiscalizationServiceId) {
            return 'FISCALIZACAO';
        }

        if ($serviceId === $this->paymentServiceId) {
            return 'PAGAMENTO';
        }

        return null;
    }

    protected function resolveServiceIds(): void
    {
        if ($this->fiscalizationServiceId && $this->paymentServiceId) {
            return;
        }

        $this->fiscalizationServiceId = Service::whereIn('service', ['Fiscalizacao', 'Fiscalização'])->value('uuid');
        $this->paymentServiceId = Service::where('service', 'Pagamento')->value('uuid');
    }

    protected function emitIfMissing(array $payload): void
    {
        $exists = TimelineEvent::query()
            ->where('five_note_id', $payload['five_note_id'])
            ->where('event_type', $payload['event_type'])
            ->where('to_stage', $payload['to_stage'])
            ->where('occurred_at', $payload['occurred_at'])
            ->exists();

        if ($exists) {
            return;
        }

        TimelineEvent::create($payload);
    }
}
