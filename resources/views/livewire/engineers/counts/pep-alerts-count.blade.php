<div wire:poll.10s>
    @if ($count)
        @if (!$menu)
            <span class="badge text-bg-danger ms-2 text-center align-middle">{{ $count }}</span>
        @else
            <i class="ms-2 ri-checkbox-blank-circle-fill text-danger fs-6 align-middle"></i>
        @endif
    @endif
</div>
