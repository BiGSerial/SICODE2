{{-- Há quanto tempo está no estado atual. Espera $since (Carbon|null). Verde < 2 dias, amarelo 2-4, vermelho 5+. --}}
@php
    if ($since) {
        $minutes = max(0, $since->diffInMinutes(now()));
        $days    = intdiv($minutes, 1440);
        $hours   = intdiv($minutes % 1440, 60);
        $label   = $days ? "{$days} d {$hours} h" : ($hours ? "{$hours} h " . ($minutes % 60) . ' min' : "{$minutes} min");
        $tone    = $days >= 5 ? 'danger' : ($days >= 2 ? 'warning' : 'success');
    }
@endphp
@if ($since)
    <span class="badge text-bg-{{ $tone }}" title="Desde {{ $since->format('d/m/Y H:i') }}">há {{ $label }}</span>
@else
    <span class="text-muted">—</span>
@endif
