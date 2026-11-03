<?php

namespace App\Http\Livewire\Concerns;

use App\Models\{City, Company, Note};

/**
 * Filtros e atalhos comuns das listas de Notas/OVs (todas as atividades, Despacho, Parceira e Serviços): mesma definição de filtros
 * (Empreiteira, Rubrica, Região, Regional, Município), mesmo "limpar tudo" e mesma limpeza de seleção.
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 */
trait WithNoteListFilters
{
    use WithListFilters;

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'company'  => ['label' => 'Empreiteira'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'company' => $this->rememberFilterOptions('company', fn () => Company::query()
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->all()),
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

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

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo e atalhos. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->filter_d5     = false;
        $this->typeNote      = '';
        $this->afterListFiltersChanged();
    }

    public function clearSelection(): void
    {
        $this->selected = [];

        foreach (['selectall', 'selectAll'] as $flag) {
            if (property_exists($this, $flag)) {
                $this->{$flag} = false;
            }
        }
    }
}
