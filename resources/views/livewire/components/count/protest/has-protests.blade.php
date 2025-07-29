<div wire:poll.60s>
    @if ($hasProtests)
        <span
            class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"
            style="padding: 0.375rem !important; top: -0.25rem !important;">
            <span class="visually-hidden">New alerts</span>
        </span>
    @endif
</div>
