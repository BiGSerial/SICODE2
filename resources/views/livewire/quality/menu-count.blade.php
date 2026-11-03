<span wire:poll.15s>
    @if ($pill)
        <span class="badge rounded-pill text-bg-{{ $pill['tone'] }}" title="{{ $pill['title'] }}">{{ $pill['count'] }}</span>
    @endif
</span>
