<?php

namespace App\Http\Livewire\Dispatchs\Analises\Concerns;

use App\Models\City;

/**
 * Converte os filtros Região / Regional / Município (`$filters`) nos códigos de município (`notes.nexp`) que a consulta
 * das listas de Análises usa. Vazio quando não há filtro de localidade.
 */
trait ResolvesLocationCodes
{
    public function getBaseProperty(): array
    {
        $region = $this->filterValues('region');
        $local  = $this->filterValues('regional');
        $city   = $this->filterValues('city');

        if (!$region && !$local && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($local, fn ($q) => $q->whereIn('baseConstrucao', $local))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }
}
