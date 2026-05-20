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
    public const VISIBILITIES = [
        'controller_only',
        'assigned_user_only',
        'internal_all',
        'legal_area',
        'external_ready',
    ];

    public function attach(LegalDemand $demand, File $file, User $actor, array $payload): LegalDemandFile
    {
        $this->ensureAllowed($actor, 'legal.demands.manage_files');
        $this->assertDemandAllowsChanges($demand, $actor);

        $visibility = $payload['visibility'] ?? 'internal_all';
        $assignmentId = $payload['assignment_id'] ?? null;

        $this->assertVisibility($visibility);

        return DB::transaction(function () use ($demand, $file, $actor, $assignmentId, $visibility) {
            $link = LegalDemandFile::create([
                'legal_demand_id' => $demand->id,
                'assignment_id' => $assignmentId,
                'uploaded_by' => $actor->id,
                'file_name' => (string) ($file->name ?? $file->file_name ?? 'arquivo'),
                'original_name' => $file->original_name ?? null,
                'path' => (string) ($file->path ?? ''),
                'mime_type' => $file->mime_type ?? null,
                'size' => $file->size ?? null,
                'visibility' => $visibility,
            ]);

            $this->event($demand->id, 'file_attached', $actor->id, [
                'storage_file_id' => $file->id,
                'legal_demand_file_id' => $link->id,
                'assignment_id' => $assignmentId,
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
            $link->removed_by = $actor->id;
            $link->save();

            $this->event($link->legal_demand_id, 'file_removed', $actor->id, [
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

        $demand = $link->legalDemand;
        $visibility = $link->visibility;

        if ($visibility === 'controller_only') {
            return (string) $demand->controller_user_id === (string) $actor->id || $actor->can('legal.demands.view_controller_files');
        }

        if ($visibility === 'assigned_user_only') {
            return (string) $demand->current_assigned_user_id === (string) $actor->id
                || (string) $demand->controller_user_id === (string) $actor->id
                || $actor->can('legal.demands.view_controller_files');
        }

        if ($visibility === 'legal_area') {
            return $actor->can('legal.demands.review') || (string) $demand->controller_user_id === (string) $actor->id;
        }

        if ($visibility === 'external_ready') {
            return $actor->can('legal.demands.close_external') || (string) $demand->controller_user_id === (string) $actor->id;
        }

        return $actor->can('legal.demands.view');
    }

    public function visibleForDemand(LegalDemand $demand, User $actor): Collection
    {
        return LegalDemandFile::query()
            ->where('legal_demand_id', $demand->id)
            ->active()
            ->get()
            ->filter(fn(LegalDemandFile $file) => $this->canView($file, $actor))
            ->values();
    }

    public function queryExternalReady(LegalDemand $demand)
    {
        return LegalDemandFile::query()
            ->where('legal_demand_id', $demand->id)
            ->active()
            ->where('visibility', 'external_ready');
    }

    private function assertDemandAllowsChanges(LegalDemand $demand, User $actor): void
    {
        $blocked = ['closed_external', 'cancelled', 'ignored'];
        if (in_array((string) $demand->internal_status?->value, $blocked, true) && !$actor->can('legal.demands.close_external')) {
            throw new InvalidArgumentException('Demanda não permite anexar arquivo neste status.');
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
        if (method_exists($actor, 'can') && !$actor->can($permission)) {
            throw new InvalidArgumentException("Sem permissão: {$permission}");
        }
    }

    private function event(int $demandId, string $eventType, ?string $actorUserId, array $metadata): void
    {
        LegalDemandEvent::create([
            'legal_demand_id' => $demandId,
            'event_type' => $eventType,
            'actor_user_id' => $actorUserId,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
