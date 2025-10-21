{{-- livewire.admin.hierarchy.partials.simple-node --}}
@php
    $match = $needle ? stripos($node['name'], $needle) !== false || stripos($node['email'], $needle) !== false : false;
    $isSelected = $node['id'] === $selectedManagerId;

    // Extrai a primeira palavra do nome da empresa para o badge
    $companyBadge = '';
    if (!empty($node['company_name'])) {
        $parts = explode(' ', $node['company_name']);
        $companyBadge = $parts[0];
    }
@endphp
<li class="mb-1">
    {{-- A classe .node tem margin-top:30px para criar espaço para a linha vertical que se conecta a ele. --}}
    {{-- Para o primeiro nó de uma sub-árvore (que não tem linha vertical vindo de cima de outro li) --}}
    {{-- removemos esse margin-top para que ele se conecte corretamente ao "ul" pai ou seja o primeiro --}}
    <div class="node mx-auto {{ $isSelected ? 'node-selected' : '' }}" data-match="{{ $match ? '1' : '0' }}"
        wire:click.prevent="selectManager('{{ $node['id'] }}')" title="Clique para focar neste usuário">
        <div class="node-header d-flex justify-content-between align-items-center mb-1">
            @if ($companyBadge)
                <span class="badge bg-secondary small-badge">{{ $companyBadge }}</span>
            @else
                <div></div> {{-- Placeholder para manter alinhamento com justify-content-between --}}
            @endif
            @if ($isSelected)
                <span class="badge bg-primary px-2 py-1 shadow-sm small-badge">FOCO</span>
            @endif
        </div>
        <div class="node-body">
            <div class="node-title">{{ $node['name'] }}</div>
            <div class="node-subtitle">— {{ $node['email'] }}</div>
        </div>
        <div class="node-child-actions">
            {{-- Ação de mover para qualquer nó, exceto a raiz da hierarquia geral --}}
            @if (empty($node['manager_id']) && isset($isRootOfFullHierarchy) && $isRootOfFullHierarchy)
                {{-- Não mostra botão de mover para raízes da visão geral --}}
            @else
                <button class="btn btn-outline-primary btn-sm" wire:click.stop="openMoveModal('{{ $node['id'] }}')"
                    title="Mover este usuário para outro gerente"><i class="bi bi-arrows-move"></i></button>
            @endif
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
