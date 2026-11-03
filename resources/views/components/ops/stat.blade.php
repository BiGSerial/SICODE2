@props([
    'tone'   => 'muted',
    'label',
    'value',
    'active' => false,
    'icon'   => null,
])

{{-- Cartão-resumo discreto de lista de controle: número + rótulo; clicável (`wire:click`) quando serve de filtro. --}}
<button type="button" {{ $attributes->class(['ops-stat', 'tone-' . $tone, 'is-active' => $active]) }} aria-pressed="{{ $active ? 'true' : 'false' }}">
    @if ($icon)
        <i class="{{ $icon }} ops-stat-icon" aria-hidden="true"></i>
    @endif
    <span class="ops-stat-value">{{ $value }}</span>
    <span class="ops-stat-label">{{ $label }}</span>
</button>
