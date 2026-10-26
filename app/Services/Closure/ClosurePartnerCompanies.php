<?php

namespace App\Services\Closure;

use App\Models\{Company, User};
use Illuminate\Support\Collection;

/**
 * Destinos válidos de uma pendência ("Enviar à parceira"). O cadastro não tem flag de parceira/empreiteira;
 * a regra do sistema é a empresa ter contrato. Fica de fora a empresa de quem envia, e a empresa atual da
 * Ordem (`$alwaysInclude`) é sempre mantida como opção.
 *
 * @return Collection<int, Company>
 */
class ClosurePartnerCompanies
{
    public function for(?User $actor, ?string $alwaysInclude = null): Collection
    {
        $own = $actor?->company_id;

        return Company::query()
            ->with('parent')
            ->where(fn ($q) => $q
                ->where(fn ($w) => $w->whereHas('contracts')->when($own, fn ($x) => $x->where('id', '!=', $own)))
                ->when($alwaysInclude, fn ($w) => $w->orWhere('id', $alwaysInclude)))
            ->get(['id', 'name', 'parent_id'])
            ->sortBy(fn (Company $c) => mb_strtolower($c->display_name))
            ->values();
    }
}
