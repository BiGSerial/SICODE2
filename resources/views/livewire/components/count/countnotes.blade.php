<div wire:poll.180s>
    @if ($count)
        <div class="d-flex align-items-center">

            @if ($geral)
                <span class="badge rounded-pill bg-danger badge-number mb-5 ms-1"
                    style="font-size: 10px;">{{ $count }}</span>
            @else
                <span class="badge bg-danger align-middle ms-1">{{ $count }}</span>
            @endif
        </div>
    @endif
</div>
