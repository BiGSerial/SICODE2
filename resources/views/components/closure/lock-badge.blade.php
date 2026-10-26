@props([
    'reason',
    'since' => null,
])

{{-- Selo de Ordem travada (ex.: RI com o Desenho). O motivo também vai no title para leitores de tela e hover. --}}
<span {{ $attributes->class(['lock-badge']) }} title="{{ $reason }}">
    <i class="ri-lock-line" aria-hidden="true"></i>
    <span>Travada · {{ $reason }}</span>
    @if ($since)
        <x-closure.age-chip :since="$since" />
    @endif
</span>
