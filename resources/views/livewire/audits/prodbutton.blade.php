<div>
    @if ($audit)
        @if (Auth()->User()->superadm)
            <li><a class="dropdown-item bg-secondary text-white" href="#" wire:click.prevent="audit()"><i
                        class="ri-auction-line text-warning align-middle"></i>
                    Auditar</a>
            </li>
        @endif
    @endif
</div>
