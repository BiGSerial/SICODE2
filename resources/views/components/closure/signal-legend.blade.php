@props(['executor' => false])
@php use App\Services\Closure\ClosureSignal; @endphp

{{-- Legenda das cores das Ordens: mesma regra e mesmos textos em todas as telas (ClosureSignal::legend). --}}
<div {{ $attributes->class(['signal-legend']) }} role="list" aria-label="Legenda das cores">
    @foreach (ClosureSignal::legend($executor) as $item)
        <span class="signal-legend-item signal-{{ $item['key'] }}" role="listitem" title="{{ $item['hint'] }}">{{ $item['label'] }}</span>
    @endforeach
</div>
