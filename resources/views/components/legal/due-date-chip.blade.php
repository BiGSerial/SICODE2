@props([
    'date',
    'showIcon'   => true,
    'executedAt' => null,
])
@php
    if ($executedAt) {
        $ex     = \Carbon\Carbon::parse($executedAt);
        $onTime = !$date || $ex->lte(\Carbon\Carbon::parse($date));
        $dueFmt = $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : null;
        $tooltip = 'Cumprido em ' . $ex->format('d/m/Y') . ($dueFmt ? ' · prazo era ' . $dueFmt : '');
    } elseif (!$date) {
        $label   = 'Sem prazo';
        $icon    = '';
        $class   = 'text-muted';
        $tooltip = 'Sem prazo definido';
    } else {
        $d    = \Carbon\Carbon::parse($date);
        $diff = now()->diffInDays($d, false);

        if ($diff < 0) {
            $label   = 'Vencida há ' . abs((int) $diff) . 'd';
            $icon    = '⚠';
            $class   = 'text-danger fw-bold';
            $tooltip = 'Venceu em ' . $d->format('d/m/Y');
        } elseif ($diff <= 3) {
            $label   = 'Vence em ' . (int) $diff . 'd';
            $icon    = '⚡';
            $class   = 'text-warning fw-bold';
            $tooltip = 'Vence em ' . $d->format('d/m/Y');
        } elseif ($diff <= 7) {
            $label   = $d->format('d/m/Y');
            $icon    = '🕐';
            $class   = 'text-legal-accent';
            $tooltip = 'Vence em ' . (int) $diff . ' dias';
        } else {
            $label   = $d->format('d/m/Y');
            $icon    = '';
            $class   = 'text-muted';
            $tooltip = 'Prazo: ' . $d->format('d/m/Y');
        }
    }
@endphp

@if($executedAt)
    {{-- Demanda encerrada: mostra prazo original + se foi no prazo --}}
    <span title="{{ $tooltip }}" data-bs-toggle="tooltip" style="line-height:1.3">
        @if($dueFmt)
            <span class="text-muted d-block" style="font-size:.8em">Prazo: {{ $dueFmt }}</span>
        @else
            <span class="text-muted d-block" style="font-size:.8em">Sem prazo</span>
        @endif
        <span class="{{ $onTime ? 'text-success' : 'text-danger' }} fw-semibold">
            {{ $onTime ? '✓ No prazo' : '⚠ Fora do prazo' }}
        </span>
    </span>
@else
    <span class="{{ $class }}" title="{{ $tooltip }}" data-bs-toggle="tooltip">
        @if($showIcon && $icon) {{ $icon }} @endif
        {{ $label }}
    </span>
@endif
