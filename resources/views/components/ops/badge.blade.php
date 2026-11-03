@props([
    'tone' => 'muted',
    'label' => null,
    'icon' => null,
    'hint' => null,
    'sm'   => false,
    'mono' => false,
])

{{-- Selo único das listas operacionais: mesmo tamanho, raio e peso em qualquer tela. Cor só por tom semântico (green|blue|amber|red|teal|muted). --}}
<span {{ $attributes->class(['ops-badge', 'tone-' . $tone, 'ops-badge--sm' => $sm, 'ops-badge--mono' => $mono]) }} @if ($hint) title="{{ $hint }}" @endif>
    @if ($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $label ?? $slot }}
</span>
