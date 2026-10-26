@props([
    'tone'   => 'blue',
    'label',
    'value',
    'note'   => null,
    'icon'   => 'ri-bar-chart-line',
    'values' => [],
    'labels' => [],
])

{{-- Cartão-destaque em gradiente (azul, verde, vermelho, âmbar) com ícone, número, nota e mini-gráfico. --}}
<article {{ $attributes->class(['mp-hero-card', 'tone-' . $tone]) }}>
    <i class="{{ $icon }} mp-hero-card-icon" aria-hidden="true"></i>
    <div class="mp-hero-card-label">{{ $label }}</div>
    <div class="mp-hero-card-value">{{ $value }}</div>
    @if ($note)
        <div class="mp-hero-card-note">{{ $note }}</div>
    @endif
    <x-home.spark :values="$values" :labels="$labels" />
</article>
