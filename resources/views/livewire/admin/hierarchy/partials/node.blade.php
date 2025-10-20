<li class="mb-1">
    <div class="node drop-target d-flex justify-content-between align-items-center" data-node-id="{{ $node['id'] }}"
        draggable="false">
        <div class="d-flex flex-column">
            <strong class="select-node" style="cursor:pointer" wire:click="$emit('lwSelectUser','{{ $node['id'] }}')">
                {{ $node['name'] }}
            </strong>
            <span class="small text-muted">{{ $node['email'] }}</span>
        </div>
        <div class="actions d-flex gap-1">
            <button class="btn btn-outline-secondary btn-sm"
                onclick="window.__hier.onSetRoot('{{ $node['id'] }}')">Raiz</button>
        </div>
    </div>

    @if (!empty($node['children']))
        <ul class="list-unstyled ms-3">
            @foreach ($node['children'] as $child)
                @include('livewire.admin.hierarchy.partials.node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
