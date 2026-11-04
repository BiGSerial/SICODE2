<?php

namespace App\Http\Livewire\Closure\Work\Concerns;

use App\Enum\ClosureOperationalStatus;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Services\Closure\ClosureMetaClassifier;
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtros no padrão das listas operacionais para as listas do Serviço baseadas em ClosureWorkListing (Disponíveis e Histórico):
 * Status usuário e Rubrica (e Status, quando a lista define `statusFilterValues()`), pelo WithListFilters, na URL.
 * Os filtros do trait viram as propriedades que `baseListingQuery()` lê em `syncWorkFilters()`.
 */
trait WithOpsWorkFilters
{
    use WithListFilters;

    /** Método (não propriedade): uma propriedade `$queryString` no trait colide com a de `Livewire\Component` em alguns PHP (fatal). */
    protected function queryString(): array
    {
        return [
            'search'  => ['except' => ''],
            'type'    => ['except' => 'all'],
            'filters' => ['except' => [], 'as' => 'f'],
        ];
    }

    /** Rótulo do filtro de status (null = a lista não tem). */
    protected function statusFilterLabel(): ?string
    {
        return null;
    }

    /** @return array<int, string> valores de status oferecidos no filtro */
    protected function statusFilterValues(): array
    {
        return [];
    }

    protected function listFilterDefinitions(): array
    {
        $defs = ['statusUser' => ['label' => 'Status usuário'], 'rubric' => ['label' => 'Rubrica']];

        if ($this->statusFilterLabel()) {
            $defs['status'] = ['label' => $this->statusFilterLabel()];
        }

        return $defs;
    }

    protected function listFilterOptions(): array
    {
        $options = [
            'statusUser' => array_map(fn ($o) => ['v' => $o['v'], 'l' => $o['v'] . ' (' . $o['n'] . ')'], $this->statusUserOptions()),
            'rubric'     => $this->rememberFilterOptions('rubric', fn () => array_map(fn ($r) => ['v' => $r, 'l' => $r], ClosureDispatchListQuery::rubricOptions())),
        ];

        if ($this->statusFilterLabel()) {
            $options['status'] = array_map(fn ($v) => ['v' => $v, 'l' => ClosureOperationalStatus::labelFor($v)], $this->statusFilterValues());
        }

        return $options;
    }

    /** Traz os filtros do trait para as propriedades que a consulta herdada lê. */
    protected function syncWorkFilters(): void
    {
        $this->statusUsers = $this->filterValues('statusUser');
        $this->status      = 'all';
    }

    protected function applyRubricFilter(Builder $query): void
    {
        $rubrics = $this->filterValues('rubric');

        if ($rubrics !== []) {
            $query->whereIn('orders.note_id', fn ($sub) => $sub->select('id')->from('notes')->whereIn('rubrica', $rubrics));
        }
    }

    /** "Opções contam a lista sem o próprio filtro de Status usuário." */
    public function statusUserOptions(): array
    {
        $saved = $this->filters;
        unset($this->filters['statusUser']);

        try {
            return $this->memoStatusUserOptions ??= $this->statusUserOptionsFromListing();
        } finally {
            $this->filters = $saved;
        }
    }

    protected function afterListFiltersChanged(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        $this->selectedStateIds = [];
        $this->dispatchBrowserEvent('ops-filters', ['sel' => (object) $this->filters]);
    }

    public function clearFilters(): void
    {
        $this->clearListingFilters();
        $this->clearListFilters();
    }

    /** Remove um chip: critério da tela ou filtro do trait. */
    public function removeAnyFilter(string $key): void
    {
        match ($key) {
            'search' => $this->search = '',
            'bulk'   => $this->clearBulkSearch(),
            'type'   => $this->type = 'all',
            default  => $this->removeListFilter($key),
        };

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || trim($this->search) !== '' || $this->type !== 'all' || $this->bulkTerms !== [];
    }

    /** @return array<int, array{key: string, label: string, value: string}> */
    protected function criteriaChips(): array
    {
        $chips = [];

        if (trim($this->search) !== '') {
            $chips[] = ['key' => 'search', 'label' => 'Busca', 'value' => trim($this->search)];
        }

        if ($this->bulkTerms !== []) {
            $chips[] = ['key' => 'bulk', 'label' => 'Busca em massa', 'value' => count($this->bulkTerms) . ' termo(s)'];
        }

        if ($this->type !== 'all') {
            $chips[] = ['key' => 'type', 'label' => 'Tipo', 'value' => ClosureMetaClassifier::label($this->type)];
        }

        return $chips;
    }
}
