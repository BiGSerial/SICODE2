@props([
    'label',
    'tone' => 'muted',
])

{{-- Selo de status com cor semântica (green = concluído/positivo, blue = informativo, amber = atenção, red = problema/bloqueio, muted = neutro). --}}
<span {{ $attributes->class(['status-badge', 'tone-' . $tone]) }}>{{ $label }}</span>
