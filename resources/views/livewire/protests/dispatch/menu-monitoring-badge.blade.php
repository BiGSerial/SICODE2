<span wire:poll.60s class="badge bg-light text-dark ms-2">
    {{ $openCount }}
</span>
@if ($donePending > 0)
    <span wire:poll.60s class="badge bg-success text-white ms-1">
        {{ $donePending }}
    </span>
@endif
