<div>
    {{-- @can('superadm')
        <li><a class="dropdown-item" href="#" wire:click.prevent="to_delete"><i
                    class="ri-delete-bin-2-line text-danger align-middle"></i>
                Remover</a></li>
    @endcan --}}
    @if (!$production->completed && $production->status <= 2)
        <li><a class="dropdown-item" href="#" wire:click.prevent="to_delete"><i
                    class="ri-delete-bin-2-line text-danger align-middle"></i> Remover Despacho</a></li>
    @endif


</div>
