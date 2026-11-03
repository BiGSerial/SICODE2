@props([
    'date' => null,
    'days' => null,
    'rule' => 'dispatch',
    'ok'   => null,
    'warn' => null,
    'what' => null,
])

{{-- Padrão "data + dias" das telas de controle (stack): o selo de <x-ops.date> com a data em cima e "N d" embaixo, na cor do prazo.
     `rule` = dispatch | assigned | informed (faixas em App\Support\Ops\ControlAge); `ok`/`warn` trocam a faixa de uma tela com regra própria;
     `days` já calculado (se a tela precisa do mesmo número em outro lugar), senão sai da data. Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §13. --}}
@php
    $date  = $date ? ($date instanceof \Carbon\CarbonInterface ? $date : \Carbon\Carbon::parse($date)) : null;
    $limit = \App\Support\Ops\ControlAge::rule($rule, $ok, $warn, $what);
    $days  = $date ? ($days ?? \App\Support\Ops\ControlAge::days($date)) : null;
@endphp

<x-ops.date :date="$date" :tone="\App\Support\Ops\ControlAge::tone($days, $limit)" :sub="$days !== null ? $days . ' d' : null" :hint="\App\Support\Ops\ControlAge::hint($days, $limit)" {{ $attributes }} />
