@props([
    'title',
    'value',
    'sub'   => null,
    'color' => 'primary',
    'icon'  => null,
    'trend' => null,
])
<div class="legal-kpi-card border-top border-4 border-{{ $color }} {{ $attributes->has('wire:click') ? 'cursor-pointer' : '' }}"
     {{ $attributes->only('wire:click') }}>
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="text-muted small fw-semibold text-uppercase">{{ $title }}</div>
            <div class="fs-2 fw-bold text-{{ $color }}">{{ $value }}</div>
            @if($sub)
                <div class="small text-muted">{{ $sub }}</div>
            @endif
        </div>
        @if($icon)
            <i class="{{ $icon }} fs-3 text-{{ $color }} opacity-25"></i>
        @endif
    </div>
    @if($trend)
        <div class="small mt-1 {{ str_starts_with($trend, '+') ? 'text-danger' : 'text-success' }}">
            {{ $trend }} vs. período anterior
        </div>
    @endif
</div>
