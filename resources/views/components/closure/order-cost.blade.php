@props(['planned' => null, 'actual' => null])

@php
    $money = fn ($value) => $value === null ? '—' : 'R$ ' . number_format((float) $value, 2, ',', '.');
@endphp
<td {{ $attributes->merge(['class' => 'small text-end text-nowrap', 'title' => 'Custo planejado (acima) e custo realizado (abaixo) da Ordem']) }}>
    <div><span class="text-muted">Plan.</span> {{ $money($planned) }}</div>
    <div><span class="text-muted">Real.</span> {{ $money($actual) }}</div>
</td>
