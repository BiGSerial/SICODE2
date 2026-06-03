@props([
    'legalCase' => null,
    'fallback' => null,
    'limit' => 2,
])

@php
    $parties = $legalCase?->adverseParties ?? collect();
    $names = $parties
        ->pluck('name')
        ->filter()
        ->values();
    $visibleNames = $names->take((int) $limit);
    $remaining = max(0, $names->count() - $visibleNames->count());
@endphp

@if($visibleNames->isNotEmpty())
    <span title="{{ $names->implode(', ') }}" data-bs-toggle="tooltip">
        {{ $visibleNames->map(fn ($name) => \Illuminate\Support\Str::limit($name, 34))->implode(', ') }}
        @if($remaining > 0)
            <span class="text-muted">+{{ $remaining }}</span>
        @endif
    </span>
@elseif(filled($fallback))
    <span title="{{ $fallback }}" data-bs-toggle="tooltip">
        {{ \Illuminate\Support\Str::limit($fallback, 42) }}
    </span>
@else
    <span class="text-muted">Não informado</span>
@endif
