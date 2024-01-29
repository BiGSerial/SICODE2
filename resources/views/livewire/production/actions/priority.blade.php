<div>
    @if (!$production->priority)
        <li><a class="dropdown-item" href="#"
                wire:click.prevent="$emit('setPriority', '{{ $this->production->id }}')"><i
                    class="ri-alert-fill text-danger align-middle"></i>
                Priorizar</a>
        </li>
    @else
        <li><a class="dropdown-item" href="#"
                wire:click.prevent="$emit('removePriority', '{{ $this->production->id }}')"><i
                    class="ri-alert-line
            text-success align-middle"></i>
                Remover Prioridade</a>
        </li>
    @endif
</div>
