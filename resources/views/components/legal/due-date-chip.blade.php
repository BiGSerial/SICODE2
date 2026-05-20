@props([
    'date',
    'showIcon' => true,
])
@php
    if (!$date) {
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
<span class="{{ $class }}" title="{{ $tooltip }}" data-bs-toggle="tooltip">
    @if($showIcon && $icon) {{ $icon }} @endif
    {{ $label }}
</span>
