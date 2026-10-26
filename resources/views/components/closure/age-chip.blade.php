@props([
    'since',
    'prefix'    => 'há',
    'estimated' => false,
    'warnAfter' => null,
    'dangerAfter' => null,
])

@php
    $date = $since ? \Carbon\Carbon::parse($since) : null;
    $days = $date ? (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
    $tone = match (true) {
        $days === null                                    => 'muted',
        $dangerAfter !== null && $days >= $dangerAfter    => 'red',
        $warnAfter !== null && $days >= $warnAfter        => 'amber',
        default                                           => 'muted',
    };
    $text = $days === null ? '—' : ($days === 0 ? 'hoje' : $prefix . ' ' . $days . ' ' . ($days === 1 ? 'dia' : 'dias'));
@endphp

{{-- Tempo decorrido em dias corridos (informativo). A cor por tempo só aparece se warnAfter/dangerAfter forem informados (desligada por padrão). --}}
<span {{ $attributes->class(['age-chip', 'tone-' . $tone]) }} @if ($date) title="Desde {{ $date->format('d/m/Y H:i') }}" @endif>
    <i class="ri-time-line" aria-hidden="true"></i>{{ $text }}
    @if ($estimated)
        <span class="age-estimated" title="Primeira vez que o sistema viu esta Ordem neste status; o início real pode ser anterior.">desde o 1º registro</span>
    @endif
</span>
