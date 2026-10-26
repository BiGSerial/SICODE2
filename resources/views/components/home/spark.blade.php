@props([
    'values' => [],
    'labels' => [],
    'ticks'  => 5,
])

{{-- Mini-gráfico de área (SVG) para cartões em gradiente: traço branco sobre área translúcida; rótulos do eixo embaixo.
     values: série numérica; labels: um rótulo por valor (mostra só `ticks`, igualmente espaçados). --}}
@php
    $count = count($values);
    $max   = max(1, ...($count ? $values : [1]));
    $points = [];

    foreach ($values as $i => $v) {
        $x        = $count > 1 ? $i / ($count - 1) * 100 : 50;
        $y        = 48 - ($v / $max) * 42;
        $points[] = round($x, 2) . ',' . round($y, 2);
    }

    $line   = implode(' ', $points);
    $shown  = [];

    if ($count > 0 && count($labels) === $count) {
        $step = $count > 1 ? max(1, (int) floor(($count - 1) / max(1, $ticks - 1))) : 1;

        for ($i = 0; $i < $count; $i += $step) {
            $shown[] = $labels[$i];
        }
    }
@endphp

@if ($count > 0)
    <svg class="mp-spark" viewBox="0 0 100 52" preserveAspectRatio="none" role="img" aria-label="Evolução no período">
        <polygon points="0,52 {{ $line }} 100,52" class="mp-spark-area" />
        <polyline points="{{ $line }}" class="mp-spark-line" vector-effect="non-scaling-stroke" />
    </svg>
    @if ($shown)
        <div class="mp-spark-labels" aria-hidden="true">@foreach ($shown as $label)<span>{{ $label }}</span>@endforeach</div>
    @endif
@endif
