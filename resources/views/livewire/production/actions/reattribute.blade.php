@php
    use Carbon\Carbon;
@endphp
<div>
    @if (
        (!$production->confirmed &&
            $production->completed &&
            Carbon::parse($production->completed_at)->isSameDay(Carbon::now())) ||
            (!$production->confirmed && $production->completed) ||
            Auth()->User()->superadm)
        <li><a class="dropdown-item" href="#" wire:click.prevent="ask_reatt"><i
                    class="ri-refresh-line text-primary align-middle"></i>
                Re-atribuir</a>
        </li>
    @endif
</div>
