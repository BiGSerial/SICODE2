<div wire:poll.300s>
    @if ($count)
        {{-- <span class="badge text-bg-danger ms-2 ncount">{{ $count }}</span> --}}
        @if ($geral)
            <span class="badge rounded-pill bg-danger badge-number mb-5"
                style="font-size: 10px;">{{ $count }}</span>
        @else
            <span class="badge bg-danger">{{ $count }}</span>
        @endif
    @endif
</div>
