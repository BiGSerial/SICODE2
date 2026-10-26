@props([
    'label',
    'value',
    'tone'   => 'teal',
    'icon'   => null,
    'note'   => null,
    'active' => false,
])

{{-- Cartão de contagem clicável que funciona como filtro. Passe o clique por atributo: <x-closure.stat-card wire:click="setTab('x')" … />.
     Estilo: .kpi (resources/sass/closure/_kpi.scss) + .tone-* (_tones.scss). --}}
<button type="button" {{ $attributes->class(['kpi', 'tone-' . $tone, 'is-active' => $active]) }} aria-pressed="{{ $active ? 'true' : 'false' }}">
    <span class="kpi-top">
        <span>{{ $label }}</span>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
    </span>
    <span class="kpi-value d-block">{{ $value }}</span>
    @if ($note)
        <span class="kpi-note d-block">{{ $note }}</span>
    @endif
</button>
