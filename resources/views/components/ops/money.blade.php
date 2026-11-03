@props([
    'value'  => null,
    'signed' => false,
])

{{-- Valor em R$ (pt-BR, algarismos tabulares). Com `signed`, negativo fica vermelho e positivo verde (saldo). --}}
@if ($value === null)
    <span class="ops-empty" aria-label="Sem valor">—</span>
@else
    @php $tone = $signed ? ($value < 0 ? 'red' : ($value > 0 ? 'green' : 'muted')) : null; @endphp
    <span {{ $attributes->class(['ops-money', 'tone-' . $tone => $tone]) }}>R$ {{ number_format((float) $value, 2, ',', '.') }}</span>
@endif
