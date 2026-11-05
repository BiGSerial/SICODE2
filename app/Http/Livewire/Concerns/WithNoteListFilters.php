<?php

namespace App\Http\Livewire\Concerns;

use App\Models\{Company, Note};

/**
 * Filtros e atalhos comuns das listas de Notas/OVs (todas as atividades, Despacho, Parceira e Serviços): mesma definição de filtros
 * (Empreiteira, Rubrica, Região, Regional, Município), mesmo "limpar tudo" e mesma limpeza de seleção.
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 */
trait WithNoteListFilters
{
    use WithListFilters;
    use WithLocationFilterOptions;

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
