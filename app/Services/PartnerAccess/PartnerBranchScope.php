<?php

namespace App\Services\PartnerAccess;

use App\Models\Andresscompany;
use App\Models\PartnerUserBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PartnerBranchScope
{
    public function applyToNoteRelation(Builder $query, User $user, ?string $companyId = null, string $relation = 'Note'): Builder
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$this->shouldRestrict($user, $companyId)) {
            return $query;
        }

        $labels = $this->branchLabelsFor($user, $companyId);

        if ($labels->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas($relation, function (Builder $noteQuery) use ($labels) {
            $noteQuery->whereIn('lexp', $labels->all())
                ->orWhereIn('nexp', $labels->all());
        });
    }

    public function applyToNotes(Builder $query, User $user, ?string $companyId = null): Builder
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$this->shouldRestrict($user, $companyId)) {
            return $query;
        }

        $labels = $this->branchLabelsFor($user, $companyId);

        if ($labels->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $noteQuery) use ($labels) {
            $noteQuery->whereIn('lexp', $labels->all())
                ->orWhereIn('nexp', $labels->all());
        });
    }

    public function applyToProtests(Builder $query, User $user, ?string $companyId = null): Builder
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$this->shouldRestrict($user, $companyId)) {
            return $query;
        }

        $labels = $this->branchLabelsFor($user, $companyId);

        if ($labels->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('cidade', $labels->all());
    }

    public function applyToProtestJobs(Builder $query, User $user, ?string $companyId = null): Builder
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$this->shouldRestrict($user, $companyId)) {
            return $query;
        }

        $labels = $this->branchLabelsFor($user, $companyId);

        if ($labels->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $jobQuery) use ($labels) {
            $jobQuery->whereHas('protest', function (Builder $protestQuery) use ($labels) {
                $protestQuery->whereIn('cidade', $labels->all());
            })->orWhereHas('medProtest.Protest', function (Builder $protestQuery) use ($labels) {
                $protestQuery->whereIn('cidade', $labels->all());
            });
        });
    }

    public function applyToMedProtests(Builder $query, User $user, ?string $companyId = null): Builder
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$this->shouldRestrict($user, $companyId)) {
            return $query;
        }

        $labels = $this->branchLabelsFor($user, $companyId);

        if ($labels->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('Protest', function (Builder $protestQuery) use ($labels) {
            $protestQuery->whereIn('cidade', $labels->all());
        });
    }

    public function applyToFiveNotes(Builder $query, User $user, ?string $companyId = null): Builder
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$this->shouldRestrict($user, $companyId)) {
            return $query;
        }

        $labels = $this->branchLabelsFor($user, $companyId);

        if ($labels->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $branchQuery) use ($labels) {
            $branchQuery->whereIn('loc_install', $labels->all())
                ->orWhereHas('note', function (Builder $noteQuery) use ($labels) {
                    $noteQuery->whereIn('lexp', $labels->all())
                        ->orWhereIn('nexp', $labels->all());
                });
        });
    }

    public function branchLabelsFor(User $user, ?string $companyId = null): Collection
    {
        $companyId ??= PartnerAccessGate::companyIdFor($user);

        if (!$companyId) {
            return collect();
        }

        return Andresscompany::query()
            ->where('company_id', $companyId)
            ->whereHas('partnerUserBranches', fn (Builder $query) => $query->where('user_id', $user->id))
            ->get()
            ->flatMap(fn (Andresscompany $branch) => [
                $branch->city,
                $branch->street,
                $branch->complement,
                trim("{$branch->city} {$branch->street} {$branch->complement}"),
            ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();
    }

    private function shouldRestrict(User $user, ?string $companyId): bool
    {
        if (!$companyId || $user->superadm || $user->admin) {
            return false;
        }

        return PartnerUserBranch::query()
            ->where('company_id', $companyId)
            ->exists();
    }
}
