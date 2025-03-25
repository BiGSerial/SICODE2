<div>
    <div class="dropdown" style="position: inherit">
        <button class="btn btn-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="ri-menu-fill"></i>
        </button>
        <ul class="dropdown-menu edp-bg-gray">

            @if (!$production->block)
                @if (!$production->completed)
                    <li>
                        <a class="dropdown-item" href="#"
                            wire:click.prevent="$emitTo('production.actions.set-priority', 'setPriority', {{ $production->id }})">
                            <i
                                class="ri-alert-fill {{ !$production->priority ? 'text-danger' : 'text-primary' }} align-middle"></i>
                            {{ $production->priority ? 'Remover' : '' }} Prioridade
                        </a>
                    </li>
                @endif
                <li>
                    @if (!$production->completed && $production->user_id)
                        <a class="dropdown-item" href="#"
                            wire:click.prevent="$emitTo('production.actions.to-assign', 'toAssign', {{ $production->id }})">
                            <i class="ri-user-unfollow-line text-danger align-middle"></i>
                            Desatribuir
                        </a>
                    @elseif(!$production->user_id)
                        <a class="dropdown-item" href="#"
                            wire:click.prevent="$emitTo('production.actions.to-assign', 'toAssign', {{ $production->id }})">
                            <i class="ri-user-add-fill text-primary align-middle"></i>
                            Atribuir
                        </a>
                    @else
                        <a class="dropdown-item" href="#"
                            wire:click.prevent="$emitTo('production.actions.to-return', 'toReturn', {{ $production->id }})">
                            <i class="ri-restart-line text-success align-middle"></i>
                            Retornar
                        </a>
                    @endif
                </li>

                <li>
                    <a class="dropdown-item" href="#"
                        wire:click.prevent="$emitTo('production.actions.new-production', 'editProduction', {{ $production->id }})">
                        <i class="ri-edit-2-line align-middle"></i>
                        Editar
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="#"
                        wire:click.prevent="$emitTo('production.actions.to-remove', 'toRemove', {{ $production->id }})">
                        <i class="ri-delete-bin-2-line text-danger align-middle"></i>
                        Remover Atividade
                    </a>
                </li>
            @else
                <li>
                    <a class="dropdown-item" href="#"
                        wire:click.prevent="$emitTo('production.actions.to-remove-transfer', 'toRemoveTransfer', {{ $production->id }})">
                        <i class="ri-delete-row text-danger align-middle"></i>
                        Remover Transferencia
                    </a>
                </li>
            @endif


        </ul>
    </div>

    @once
        @livewire('production.actions.set-priority', key('set_priority_note'))
        @livewire('production.actions.to-assign', key('to_assign_note'))
        @livewire('production.actions.new-production', key('new_production_note'))
        @livewire('production.actions.to-return', key('to_return_note'))
        @livewire('production.actions.to-remove', key('to_remove_note'))
        @livewire('production.actions.to-remove-transfer', key('to_remove_transfer_note'))
    @endonce
</div>
