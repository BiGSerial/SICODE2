<?php

namespace App\Http\Livewire\Partner\Concerns;

use App\Services\PartnerAccess\PartnerAccessGate;
use App\Services\PartnerAccess\PartnerBranchScope;
use Illuminate\Database\Eloquent\Builder;

trait AuthorizesPartnerAccess
{
    protected function authorizePartnerAccess(string $permissionKey): void
    {
        abort_unless(PartnerAccessGate::allows(auth()->user(), $permissionKey), 403);
    }

    protected function applyPartnerCompanyScope(Builder $query, string $column = 'company_id'): Builder
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->superadm) {
            return $query;
        }

        $companyIds = $user->Companies()
            ->select('companies.id')
            ->pluck('companies.id')
            ->push(PartnerAccessGate::companyIdFor($user))
            ->filter()
            ->unique()
            ->values();

        if ($companyIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn($column, $companyIds->all());
    }

    protected function applyPartnerBranchScopeToNoteRelation(Builder $query, ?string $companyId = null, string $relation = 'Note'): Builder
    {
        return app(PartnerBranchScope::class)->applyToNoteRelation($query, auth()->user(), $companyId, $relation);
    }

    protected function applyPartnerBranchScopeToNotes(Builder $query, ?string $companyId = null): Builder
    {
        return app(PartnerBranchScope::class)->applyToNotes($query, auth()->user(), $companyId);
    }

    protected function applyPartnerBranchScopeToProtests(Builder $query, ?string $companyId = null): Builder
    {
        return app(PartnerBranchScope::class)->applyToProtests($query, auth()->user(), $companyId);
    }

    protected function applyPartnerBranchScopeToProtestJobs(Builder $query, ?string $companyId = null): Builder
    {
        return app(PartnerBranchScope::class)->applyToProtestJobs($query, auth()->user(), $companyId);
    }

    protected function applyPartnerBranchScopeToMedProtests(Builder $query, ?string $companyId = null): Builder
    {
        return app(PartnerBranchScope::class)->applyToMedProtests($query, auth()->user(), $companyId);
    }

    protected function applyPartnerBranchScopeToFiveNotes(Builder $query, ?string $companyId = null): Builder
    {
        return app(PartnerBranchScope::class)->applyToFiveNotes($query, auth()->user(), $companyId);
    }
}
