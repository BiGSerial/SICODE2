<?php

namespace App\Http\Livewire\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Filtros multi-seleção centralizados das listas operacionais (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §5).
 *
 * O componente só declara QUAIS filtros existem e DE ONDE vêm as opções; seleção, cascata, URL, chips e cache ficam aqui:
 *  - `listFilterDefinitions()`: `key => ['label' => 'Região', 'depends' => ['region']?]` (ordem = ordem na tela).
 *  - `listFilterOptions()`: `key => [['v' => valor, 'l' => rótulo, 'p' => ['region' => 'ES']?], ...]`; `p` guarda os
 *    valores-pai de cada opção e é o que alimenta a cascata (Regional só mostra as das Regiões marcadas).
 *  - `$filters` vai para a URL (`'filters' => ['except' => [], 'as' => 'f']` no `$queryString` do componente).
 *  - A seleção fica pendente no navegador (`opsFilterPanel`) e vem numa chamada só (`applyListFilters`) ao fechar o menu.
 * Nenhum filtro é um componente Livewire à parte: abrir a tela não custa roundtrip por filtro.
 */
trait WithListFilters
{
    /** @var array<string, array<int, string>> */
    public array $filters = [];

    /** Opções resolvidas uma vez por requisição (chips, normalização e payload leem a mesma lista). */
    private ?array $listFilterOptionsMemo = null;

    /** @return array<string, array{label: string, depends?: array<int, string>}> */
    abstract protected function listFilterDefinitions(): array;

    /** @return array<string, array<int, array{v: string, l: string, p?: array<string, string>}>> */
    abstract protected function listFilterOptions(): array;

    /** Escopo do cache das opções (ex.: uuid do serviço), para telas com opções diferentes por contexto. */
    protected function listFilterCacheScope(): string
    {
        return static::class;
    }

    /** Tempo de cache das opções, em segundos (as listas dos filtros mudam pouco). */
    protected function listFilterCacheTtl(): int
    {
        return 600;
    }

    private function filterOptionsMemo(): array
    {
        return $this->listFilterOptionsMemo ??= $this->listFilterOptions();
    }

    /** Resolve uma lista de opções uma única vez por TTL; use dentro de `listFilterOptions()`. */
    protected function rememberFilterOptions(string $key, \Closure $resolver): array
    {
        return Cache::remember('ops-filter:' . md5($this->listFilterCacheScope()) . ':' . $key, $this->listFilterCacheTtl(), $resolver);
    }

    /** Seleção vinda do navegador: só chaves conhecidas, só valores existentes, cascata aplicada. */
    public function applyListFilters(array $selection): void
    {
        $this->filters = $this->normalizeFilters($selection);
        $this->afterListFiltersChanged();
    }

    public function removeListFilter(string $key): void
    {
        $filters = $this->filters;
        unset($filters[$key]);

        $this->filters = $this->normalizeFilters($filters);
        $this->afterListFiltersChanged();
    }

    public function clearListFilters(): void
    {
        $this->filters = [];
        $this->afterListFiltersChanged();
    }

    public function hasListFilters(): bool
    {
        return $this->filters !== [];
    }

    /** Valores selecionados de um filtro (sempre lista de strings). */
    protected function filterValues(string $key): array
    {
        return array_values(array_map('strval', (array) ($this->filters[$key] ?? [])));
    }

    /** Dados para o `<x-ops.filters>`: definições, opções e seleção atual. */
    public function listFilterPayload(): array
    {
        $defs = [];

        foreach ($this->listFilterDefinitions() as $key => $def) {
            $defs[] = ['key' => $key, 'label' => $def['label'], 'depends' => $def['depends'] ?? []];
        }

        return [
            'defs'    => $defs,
            'options' => $this->filterOptionsMemo(),
            'sel'     => (object) $this->filters,
        ];
    }

    /** Chips dos filtros ativos: `[['key', 'label', 'value'], ...]` (um por filtro; um valor mostra o nome, vários mostram a contagem). */
    public function listFilterChips(): array
    {
        if (!$this->filters) {
            return [];
        }

        $definitions = $this->listFilterDefinitions();
        $options     = $this->filterOptionsMemo();
        $chips       = [];

        foreach ($this->filters as $key => $values) {
            if (!isset($definitions[$key]) || !$values) {
                continue;
            }

            $labels = collect($options[$key] ?? [])->pluck('l', 'v');
            $value  = count($values) === 1
                ? (string) ($labels[$values[0]] ?? $values[0])
                : count($values) . ' selecionados';

            $chips[] = ['key' => $key, 'label' => $definitions[$key]['label'], 'value' => $value];
        }

        return $chips;
    }

    /** @param array<string, mixed> $selection */
    protected function normalizeFilters(array $selection): array
    {
        $definitions = $this->listFilterDefinitions();
        $options     = $this->filterOptionsMemo();
        $clean       = [];

        // Na ordem das definições, para que o pai seja limpo antes do filho (Região -> Regional -> Município).
        foreach ($definitions as $key => $def) {
            $values = array_values(array_unique(array_map('strval', array_filter((array) ($selection[$key] ?? []), 'is_scalar'))));

            if (!$values) {
                continue;
            }

            $known = collect($options[$key] ?? [])
                ->filter(fn ($option) => $this->optionInScope($option, $def['depends'] ?? [], $clean))
                ->pluck('v')
                ->map(fn ($v) => (string) $v)
                ->all();
            $values = array_values(array_intersect($values, $known));

            if ($values) {
                $clean[$key] = $values;
            }
        }

        return $clean;
    }

    /** Uma opção está no escopo se, para cada filtro-pai com seleção, o valor-pai dela estiver entre os marcados. */
    protected function optionInScope(array $option, array $depends, array $selected): bool
    {
        foreach ($depends as $parent) {
            if (!empty($selected[$parent]) && !in_array((string) ($option['p'][$parent] ?? ''), $selected[$parent], true)) {
                return false;
            }
        }

        return true;
    }

    protected function afterListFiltersChanged(): void
    {
        $this->resetPage();

        if (property_exists($this, 'selected')) {
            $this->selected = [];
        }

        foreach (['selectall', 'selectAll'] as $flag) {
            if (property_exists($this, $flag)) {
                $this->{$flag} = false;
            }
        }

        // O painel de filtros é `wire:ignore` (as opções não viajam a cada resposta); avisa o navegador da seleção final.
        $this->dispatchBrowserEvent('ops-filters', ['sel' => (object) $this->filters]);
    }
}
