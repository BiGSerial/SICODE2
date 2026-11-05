<?php

namespace App\Http\Livewire\Concerns;

use App\Models\City;

/** Opções de Região / Regional / Município para o `WithListFilters` (listas de Notas/OVs e dashboards). */
trait WithLocationFilterOptions
{
    /** Opções de Região / Regional / Município (com os valores-pai para a cascata). */
    protected function locationFilterOptions(): array
    {
        $cities = $this->rememberFilterOptions('cities', fn () => City::query()
            ->whereNotNull('rdMunicipio')
            ->orderBy('municipio')
            ->get(['rdMunicipio', 'municipio', 'regiao', 'baseConstrucao'])
            ->map(fn ($c) => [
                'v' => (string) $c->rdMunicipio,
                'l' => (string) ($c->municipio ?: $c->rdMunicipio),
                'p' => ['region' => (string) $c->regiao, 'regional' => (string) $c->baseConstrucao],
            ])->unique('v')->values()->all());

        return [
            'region' => collect($cities)->pluck('p.region')->filter()->unique()->sort()->values()
                ->map(fn ($r) => ['v' => $r, 'l' => $r])->all(),
            'regional' => collect($cities)->map(fn ($c) => $c['p'])->filter(fn ($p) => $p['regional'] !== '')
                ->unique('regional')->sortBy('regional')->values()
                ->map(fn ($p) => ['v' => $p['regional'], 'l' => $p['regional'], 'p' => ['region' => $p['region']]])->all(),
            'city' => $cities,
        ];
    }
}
