<?php

namespace App\Services\Legal;

use App\Models\File;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandEvent;
use App\Models\Legal\LegalDemandFile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LegalDemandFileService
{
    public const CATEGORIES = [
        'legal_document',
        'technical_evidence',
        'field_return',
        'controller_note',
        'external_protocol',
        'final_response',
        'other',
    ];

    public const VISIBILITIES = [
        'controller_only',
        'assigned_user_only',
        'internal_all',
        'legal_area',
        'external_ready',
    ];

    public function attach(
        LegalDemand $demand,
        File $file,
        User $actor,
        array $payload
    ): LegalDemandFile {
        $this->ensureAllowed($actor, 'legal.demands.manage_files');
        $this->assertDemandAllowsChanges($demand, $actor);

        $category = $payload['category'] ?? 'other';
        $visibility = $payload['visibility'] ?? 'internal_all';
        $assignmentId = $payload['assignment_id'] ?? null;
        $isEvidence = (bool) ($payload['is_evidence'] ?? false);
        $isFinalResponse = (bool) ($payload['is_final_response'] ?? false);
        $canBeSentExternal = (bool) ($payload['can_be_sent_external'] ?? false);

        $this->assertCategory($category);
        $this->assertVisibility($visibility);

        return DB::transaction(function () use (
            $demand,
            $file,
            $actor,
            $assignmentId,
            $category,
            $visibility,
            $isEvidence,
            $isFinalResponse,
            $canBeSentExternal
        ) {
            $link = LegalDemandFile::create([
                'legal_demand_id' => $demand->id,
                'assignment_id' => $assignmentId,
                'file_id' => $file->id,
                'uploaded_by_user_id' => $actor->id,
                'category' => $category,
                'visibility' => $visibility,
                'can_be_sent_external' => $canBeSentExternal,
                'is_evidence' => $isEvidence,
                'is_final_response' => $isFinalResponse,
            ]);

            $this->event($demand->id, 'file_attached', $actor->id, [
                'file_id' => $file->id,
                'legal_demand_file_id' => $link->id,
                'assignment_id' => $assignmentId,
                'category' => $category,
                'visibility' => $visibility,
            ]);

            return $link;
        });
    }

    public function changeVisibility(LegalDemandFile $link, User $actor, string $newVisibility): LegalDemandFile
    {
        $this->ensureAllowed($actor, 'legal.demands.manage_files');
        $this->assertVisibility($newVisibility);
        $this->assertNotRemoved($link);

        return DB::transaction(function () use ($link, $actor, $newVisibility) {
            $old = $link->visibility;
            $link->visibility = $newVisibility;
            $link->save();

            $this->event($link->legal_demand_id, 'file_visibility_changed', $actor->id, [
                'file_id' => $link->file_id,
                'legal_demand_file_id' => $link->id,
                'old_visibility' => $old,
                'new_visibility' => $newVisibility,
            ]);

            return $link->refresh();
        });
    }

    public function removeLogical(LegalDemandFile $link, User $actor, ?string $reason = null): LegalDemandFile
    {
        $this->ensureAllowed($actor, 'legal.demands.manage_files');
        $this->assertNotRemoved($link);

        return DB::transaction(function () use ($link, $actor, $reason) {
            $link->removed_at = now();
            $link->save();

            $this->event($link->legal_demand_id, 'file_removed', $actor->id, [
                'file_id' => $link->file_id,
                'legal_demand_file_id' => $link->id,
                'reason' => $reason,
            ]);

            return $link->refresh();
        });
    }

    public function canView(LegalDemandFile $link, User $actor): bool
    {
        if ($link->removed_at) {
            return false;
        }

        $demand = $link->LegalDemand;
        $visibility = $link->visibility;

        if ($visibility === 'controller_only') {
            return (string) $demand->controller_user_id === (string) $actor->id
                || $actor->can('legal.demands.view_controller_files');
        }

        if ($visibility === 'assigned_user_only') {
            return (string) $demand->current_assigned_user_id === (string) $actor->id
                || (string) $demand->controller_user_id === (string) $actor->id
                || $actor->can('legal.demands.view_controller_files');
        }

        if ($visibility === 'legal_area') {
            return $actor->can('legal.demands.review')
                || (string) $demand->controller_user_id === (string) $actor->id;
        }

        if ($visibility === 'external_ready') {
            return $actor->can('legal.demands.close_external')
                || (string) $demand->controller_user_id === (string) $actor->id;
        }

        return $actor->can('legal.demands.view');
    }

    public function visibleForDemand(LegalDemand $demand, User $actor): Collection
    {
        return LegalDemandFile::query()
            ->where('legal_demand_id', $demand->id)
            ->active()
            ->get()
            ->filter(fn (LegalDemandFile $file) => $this->canView($file, $actor))
            ->values();
    }

    public function queryExternalReady(LegalDemand $demand)
    {
        return LegalDemandFile::query()
            ->where('legal_demand_id', $demand->id)
            ->active()
            ->where(function ($q) {
                $q->where('visibility', 'external_ready')
                    ->orWhere('can_be_sent_external', true)
                    ->orWhere('is_final_response', true);
            });
    }

    public function countDemandsWithoutEvidence(): int
    {
        return LegalDemand::query()
            ->whereDoesntHave('Files', fn ($q) => $q->active()->where('is_evidence', true))
            ->count();
    }

    public function countDemandsWithFinalResponseReady(): int
    {
        return LegalDemand::query()
            ->whereHas('Files', fn ($q) => $q->active()->where(function ($x) {
                $x->where('is_final_response', true)
                    ->orWhere('visibility', 'external_ready')
                    ->orWhere('can_be_sent_external', true);
            }))
            ->count();
    }

    public function countFilesByCategory(): array
    {
        return LegalDemandFile::query()
            ->active()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
    }

    private function assertDemandAllowsChanges(LegalDemand $demand, User $actor): void
    {
        $blocked = ['closed_external', 'cancelled', 'ignored'];
        if (in_array((string) $demand->internal_status?->value, $blocked, true)
            && !$actor->can('legal.demands.close_external')) {
            throw new InvalidArgumentException('Demanda não permite anexar arquivo neste status.');
        }
    }

    private function assertCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES, true)) {
            throw new InvalidArgumentException("Categoria inválida: {$category}");
        }
    }

    private function assertVisibility(string $visibility): void
    {
        if (!in_array($visibility, self::VISIBILITIES, true)) {
            throw new InvalidArgumentException("Visibilidade inválida: {$visibility}");
        }
    }

    private function assertNotRemoved(LegalDemandFile $link): void
    {
        if ($link->removed_at !== null) {
            throw new InvalidArgumentException('Arquivo já removido logicamente.');
        }
    }

    private function ensureAllowed(User $actor, string $permission): void
    {
        if (!$actor->can($permission)) {
            throw new InvalidArgumentException("Sem permissão: {$permission}");
        }
    }

    private function event(int $demandId, string $type, ?string $actorUserId, array $metadata): void
    {
        LegalDemandEvent::create([
            'legal_demand_id' => $demandId,
            'event_type' => $type,
            'actor_user_id' => $actorUserId,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
