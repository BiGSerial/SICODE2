<div wire:poll.180s>
    @if ($count)
        <div class="d-flex align-items-center">
            <span class="badge bg-danger align-middle ms-1">{{ $count }}</span>
        </div>
    @endif
</div>
