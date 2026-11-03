@props([
    'left'    => null,
    'elapsed' => null,
    'source'  => 'created',
    'hint'    => null,
])

{{-- Prazo real de uma Nota/OV (padrão das listas de Serviços): a DATA DE VENCIMENTO em cima e os dias que faltam embaixo ("N d" ou "N dias
     vencidos"), na cor do prazo. `left` = dias que faltam; ou `elapsed` = dias que já passaram dos 30 de execução. A conta e as faixas de cor
     são de App\Support\Ops\PrazoReal; `source` só muda o texto do tooltip (created = desde a criação; days_left = dias do SAP). --}}
@php
    $left = $left ?? ($elapsed === null ? null : \App\Support\Ops\PrazoReal::EXECUTION_DAYS - (int) $elapsed);
@endphp

<x-ops.date :date="\App\Support\Ops\PrazoReal::dueDate($left)" :tone="\App\Support\Ops\PrazoReal::tone($left)" :sub="\App\Support\Ops\ControlAge::remainingLabel($left)"
    :hint="$hint ?? \App\Support\Ops\PrazoReal::hint($left, $source)" />
