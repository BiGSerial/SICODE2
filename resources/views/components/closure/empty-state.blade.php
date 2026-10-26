@props([
    'title',
    'text' => null,
    'icon' => 'ri-inbox-line',
])

<div {{ $attributes->class(['dash-empty']) }}>
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <div class="fw-bold mt-2">{{ $title }}</div>
    @if ($text)
        <div class="small">{{ $text }}</div>
    @endif
</div>
