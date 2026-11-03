@props([
    'chips',
    'removeAction' => 'removeListFilter',
    'clearAction'  => 'clearListFilters',
])

{{-- Filtros ativos removíveis. Cada chip: ['key', 'label', 'value']. Não renderiza nada sem filtros. --}}
@if (count($chips) > 0)
    <div class="ops-chips" aria-label="Filtros ativos">
        @foreach ($chips as $chip)
            <span class="ops-chip" wire:key="chip-{{ $chip['key'] }}">
                <span><strong>{{ $chip['label'] }}:</strong> {{ $chip['value'] }}</span>
                <button type="button" class="btn-close" aria-label="Remover filtro {{ $chip['label'] }}" wire:click="{{ $removeAction }}('{{ $chip['key'] }}')"></button>
            </span>
        @endforeach
        <button type="button" class="ops-chips-clear" wire:click="{{ $clearAction }}">Limpar filtros</button>
    </div>
@endif
