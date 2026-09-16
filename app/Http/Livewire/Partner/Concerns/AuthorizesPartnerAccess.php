<?php

namespace App\Http\Livewire\Partner\Concerns;

use App\Services\PartnerAccess\PartnerAccessGate;
use App\Services\PartnerAccess\PartnerBranchScope;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

trait AuthorizesPartnerAccess
{
    public $partnerCompanyFilter = '';

    public function updatedPartnerCompanyFilter(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    protected function partnerCompanyFilterOptions()
    {
        $user = auth()->user();

        if (!$user) {
            return collect();
        }

        $ids = $user->superadm
            ? null
            : PartnerAccessGate::visibleCompanyIdsFor($user);

        return Company::query()
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->orderBy('name')
            ->get();
    }

    protected function authorizePartnerAccess(string $permissionKey): void
    {
        abort_unless(PartnerAccessGate::allows(auth()->user(), $permissionKey), 403);
    }

    protected function applyPartnerCompanyScope(Builder $query, string $column = 'company_id'): Builder
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $companyIds = $user->superadm
            ? null
            : collect(PartnerAccessGate::visibleCompanyIdsFor($user));

        if ($companyIds === null) {
            $selectedCompanyId = (string) ($this->partnerCompanyFilter ?? '');

            return $selectedCompanyId !== ''
                ? $query->where($column, $selectedCompanyId)
                : $query;
        }

        if ($companyIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        $selectedCompanyId = (string) ($this->partnerCompanyFilter ?? '');

        if ($selectedCompanyId !== '' && $companyIds->contains($selectedCompanyId)) {
            return $query->where($column, $selectedCompanyId);
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
