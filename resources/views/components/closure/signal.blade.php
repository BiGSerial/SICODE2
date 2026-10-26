@props([
    'signal' => null,
    'edge'   => false,
])

{{-- Sinal da Ordem (regra única em ClosureSignal): chip colorido com ícone e a explicação no tooltip. Sem sinal, não renderiza nada. --}}
@if ($signal)
    <span {{ $attributes->class(['signal', 'signal-' . $signal['key']]) }} title="{{ $signal['hint'] }}" data-signal="{{ $signal['key'] }}">
        <i class="{{ $signal['icon'] }}" aria-hidden="true"></i>{{ $signal['label'] }}
    </span>
@endif
