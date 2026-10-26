@props([
    'filters',
    'removeAction' => 'removeFilter',
    'clearAction'  => 'clearFilters',
])

{{-- Filtros ativos removíveis. Cada filtro: ['key', 'label', 'value']. Não renderiza nada sem filtros ativos. --}}
@if (count($filters) > 0)
    <div class="active-filters" aria-label="Filtros ativos">
        @foreach ($filters as $filter)
            <span class="filter-chip" wire:key="chip-{{ $filter['key'] }}">
                <span><strong>{{ $filter['label'] }}:</strong> {{ $filter['value'] }}</span>
                <button type="button" class="btn-close" aria-label="Remover filtro {{ $filter['label'] }}" wire:click="{{ $removeAction }}('{{ $filter['key'] }}')"></button>
            </span>
        @endforeach
        <button type="button" class="btn btn-link btn-sm text-decoration-none text-muted p-0 ms-1" wire:click="{{ $clearAction }}">Limpar tudo</button>
    </div>
@endif
