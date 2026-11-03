@php
    $kind = $node['kind'] ?? 'item';

    // Ícone: a classe `text-*` legada vira cor semântica (danger) e some do HTML.
    $iconClass = fn (array $n) => trim((string) preg_replace('/\btext-\S+\b/', '', (string) ($n['icon'] ?? '')));
    $isDanger  = fn (array $n) => ($n['iconClass'] ?? '') === 'is-danger' || str_contains((string) ($n['iconClass'] ?? ''), 'text-danger') || ($n['tone'] ?? '') === 'danger';
@endphp

{{-- Título de seção (não clicável) --}}
@if ($kind === 'header')
    <li class="tb-header-row" role="presentation">{{ $node['label'] }}</li>

{{-- Link, com contador e ações rápidas opcionais (`actions`: Minha fila, Despacho, Controle…) --}}
@elseif ($kind === 'item')
    @php
        $actions = $node['actions'] ?? [];
        $text    = mb_strtolower(trim($node['label'] . ' ' . implode(' ', array_column($actions, 'label'))));
    @endphp
    <li class="tb-row" role="none" data-tb-text="{{ $text }}">
        <a class="tb-link" role="menuitem" href="{{ $resolveHref($node) }}">
            @if ($iconClass($node))
                <span class="tb-icon {{ $isDanger($node) ? 'is-danger' : '' }}"><i class="{{ $iconClass($node) }}" aria-hidden="true"></i></span>
            @endif
            <span class="tb-label">{{ $node['label'] }}</span>
            @if (!empty($node['count']))
                <span class="tb-count" data-tb-count="{{ $node['count'] }}" hidden></span>
            @endif
            @if (!empty($node['countComponent']))
                @livewire($node['countComponent'], $node['countParams'] ?? [], key($node['countKey'] ?? ($menuUid . '-' . md5(($path ?? '') . '-' . $node['label']))))
            @endif
        </a>
        @foreach ($actions as $action)
            <a class="tb-chip {{ $isDanger($action) ? 'is-danger' : '' }}" role="menuitem" href="{{ $resolveHref($action) }}" title="{{ $action['label'] }}" aria-label="{{ $action['label'] }}">
                @if (!empty($action['icon']))
                    <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                @endif
                @if (empty($action['iconOnly']))
                    <span>{{ $action['label'] }}</span>
                @endif
                @if (!empty($action['count']))
                    <span class="tb-count" data-tb-count="{{ $action['count'] }}" hidden></span>
                @endif
            </a>
        @endforeach
    </li>

{{-- Grupo recolhível: abre no próprio lugar (sem submenu lateral, que era cortado e ruim no toque) --}}
@elseif ($kind === 'group')
    @php
        $open      = array_key_exists('open', $node) && $node['open'] === true;
        $groupId   = 'tb-' . $menuUid . '-' . str_replace('.', '-', $path);
    @endphp
    <li class="tb-group tb-depth-{{ min($depth, 2) }} {{ $open ? 'is-open' : '' }} {{ !empty($node['accent']) && $node['accent'] === 'danger' ? 'is-danger' : '' }}" data-tb-open="{{ $open ? 1 : 0 }}" role="none">
        <button class="tb-group-toggle" type="button" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $groupId }}">
            <span>{{ $node['label'] }}</span>
            <span class="d-inline-flex align-items-center gap-2">
                <span class="tb-count-agg" hidden></span>
                <i class="ri-arrow-right-s-line tb-chevron" aria-hidden="true"></i>
            </span>
        </button>
        <div class="tb-group-body" id="{{ $groupId }}">
            <ul role="group">
                @foreach ($node['nodes'] ?? [] as $childIndex => $childNode)
                    @include('components.menu.partials.dynamic-dropdown-node', [
                        'node'        => $childNode,
                        'depth'       => $depth + 1,
                        'path'        => $path . '.' . $childIndex,
                        'menuUid'     => $menuUid,
                        'resolveHref' => $resolveHref,
                    ])
                @endforeach
            </ul>
        </div>
    </li>
@endif
