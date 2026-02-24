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

    $hasDeleg = !empty($node['delegation']);
    $principalName = $hasDeleg ? $node['delegation']['principal']['name'] ?? null : null;
    $observingCount = (int) ($node['observing_count'] ?? 0);
@endphp

<li class="mb-1" @isset($wireKey) wire:key="{{ $wireKey }}" @endisset>
    <div class="node {{ $isSelected ? 'node-selected' : '' }} {{ $hasDeleg ? 'node-acting' : '' }}"
        data-match="{{ $match ? '1' : '0' }}" wire:click.prevent="selectManager('{{ $node['id'] }}')"
        title="{{ $hasDeleg ? 'Em delegação: ' . ($node['delegation']['delegate']['name'] ?? '') : 'Clique para focar neste usuário' }}">

        <div class="node-header d-flex justify-content-between align-items-center mb-1">
            @if ($companyBadge)
                <span class="badge bg-secondary small-badge">{{ $companyBadge }}</span>
            @else
                <span></span>
            @endif

            @if ($hasDeleg)
                <span class="badge badge-delegacao small-badge">EM DELEGAÇÃO</span>
            @elseif ($observingCount > 0)
                <span class="badge bg-info text-dark small-badge">OBS: {{ $observingCount }}</span>
            @elseif ($isSelected)
                <span class="badge bg-primary px-2 py-1 shadow-sm small-badge">FOCO</span>
            @else
                <span></span>
            @endif
        </div>

        <div class="node-body">
            <div class="node-title">{{ $node['name'] }}</div>
            <div class="node-subtitle">— {{ $node['email'] }}</div>

            @if ($hasDeleg && $principalName)
                <div class="mt-1">
                    <span class="chip-principal" title="Titular desta função">
                        Titular: {{ $principalName }}
                    </span>
                </div>
            @endif

            @if ($observingCount > 0)
                <div class="mt-1">
                    <span class="chip-with" title="Quantidade de vínculos de observação ativos">
                        Observa {{ $observingCount }} vínculo(s)
                    </span>
                </div>
            @endif
        </div>

        <div class="node-child-actions">
            <button class="btn btn-outline-primary btn-sm" wire:click.stop="openMoveModal('{{ $node['id'] }}')"
                title="Mover este usuário para outro gerente">
                <i class="bi bi-arrows-move"></i>
            </button>
        </div>
    </div>

    @if (!empty($node['children']))
        <ul>
            @foreach ($node['children'] as $child)
                @include('livewire.admin.hierarchy.partials.simple-node', [
                    'node' => $child,
                    'needle' => $needle,
                    'selectedManagerId' => $selectedManagerId,
                    'wireKey' => 'node-' . $child['id'],
                ])
            @endforeach
        </ul>
    @endif
</li>
