@php
    use Carbon\Carbon;
@endphp
<div>

    @if (Auth()->User()->superadm)
        <i class="ri-refresh-line text-primary align-middle" wire:click.prevent="ask_reatt" style="cursor: pointer;"
            x-data="{ clicked: false }"
            x-on:click="if (!clicked) { clicked = true; setTimeout(() => { clicked = false; }, 500) } else { $event.preventDefault(); }"></i>
    @endif

</div>
