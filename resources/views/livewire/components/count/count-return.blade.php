@once
    <style>
        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .blinking {
            animation: blink 1s infinite;
        }
    </style>
@endonce
<div wire:poll.180s>
    @if ($count)
        <div class="d-flex align-items-center">
            
            <span
                class="
                badge
                bg-danger
                align-middle
                ms-1
                @if ($days) blinking @endif
                ">{{ $count }}</span>
            @if ($notAtt)
                <span class="badge text-bg-warning align-middle ms-1 blinking">{{ $notAtt }}</span>
            @endif
        </div>
    @endif
</div>
