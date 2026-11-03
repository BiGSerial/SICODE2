@props([
    'on'    => false,
    'label',
    'icon'  => null,
    'count' => null,
])

{{-- Atalho liga/desliga de filtro (ex.: "Só livres"). A ação (`wire:click`) vem do chamador; o estado aparece como pílula acesa. --}}
<button type="button" {{ $attributes->class(['ops-toggle', 'is-on' => $on]) }} aria-pressed="{{ $on ? 'true' : 'false' }}">
    @if ($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    <span>{{ $label }}</span>
    @if ($count !== null)
        <span class="ops-toggle-count">{{ $count }}</span>
    @endif
</button>
