{{-- livewire.admin.hierarchy.partials.simple-node --}}
@php
    $match = $needle ? stripos($node['name'], $needle) !== false || stripos($node['email'], $needle) !== false : false;
    $isSelected = $node['id'] === $selectedManagerId;
@endphp
<li class="mb-1">
    <div class="node mx-auto {{ $isSelected ? 'node-selected' : '' }}" data-match="{{ $match ? '1' : '0' }}"
        wire:click.prevent="selectManager('{{ $node['id'] }}')" title="Clique para focar neste usuário">
        <div class="node-title">{{ $node['name'] }}</div>
        <div class="node-subtitle">— {{ $node['email'] }}</div>
        <div class="node-child-actions">
            <button class="btn btn-outline-primary btn-sm" wire:click.stop="openMoveModal('{{ $node['id'] }}')"
                title="Mover este usuário para outro gerente"><i class="bi bi-arrows-move"></i></button>
        </div>
    </div>

    @if (!empty($node['children']))
        <div class="connection-line-vertical"></div>
        <ul class="list-unstyled hierarchy-reports-subtree">
            @foreach ($node['children'] as $child)
                @include('livewire.admin.hierarchy.partials.simple-node', [
                    'node' => $child,
                    'needle' => $needle,
                    'selectedManagerId' => $selectedManagerId,
                ])
            @endforeach
        </ul>
    @endif
</li>
