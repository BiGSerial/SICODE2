@php
    $kind = $node['kind'] ?? 'item';
@endphp

{{-- TIPO 1 — Identificador setorial --}}
@if ($kind === 'header')
    <li class="sd-section-header">
        <i class="ri-corner-down-right-line"></i>
        {{ $node['label'] }}
    </li>

{{-- TIPO 2 — Link direto (navega para uma rota) --}}
@elseif ($kind === 'item')
    @php
        $iconBaseClass = !empty($node['icon'])
            ? trim((string) preg_replace('/\btext-\S+\b/', '', $node['icon']))
            : null;
        $iconColor = $node['iconClass'] ?? '';
    @endphp
    <a class="sd-item" href="{{ $resolveHref($node) }}">
        @if ($iconBaseClass)
            <i class="sd-icon {{ $iconBaseClass }} {{ $iconColor }}"></i>
        @else
            <span class="sd-item-dot"></span>
        @endif
        <span class="sd-label">{{ $node['label'] }}</span>
        @if (!empty($node['countComponent']))
            @livewire(
                $node['countComponent'],
                $node['countParams'] ?? [],
                key($node['countKey'] ?? ($menuUid . '-' . md5(($path ?? '') . '-' . $node['label'])))
            )
        @endif
        <i class="sd-nav-arrow ri-arrow-right-s-line"></i>
    </a>

{{-- TIPO 3 — Sub-dropdown (abre submenu inline ou lateral) --}}
@elseif ($kind === 'group')
    @php
        $openMode = in_array($node['open'] ?? ($depth === 0 ? 'down' : 'side'), ['down', 'side'], true)
            ? ($node['open'] ?? ($depth === 0 ? 'down' : 'side'))
            : 'side';
        $submenuId  = 'submenu-inline-' . $menuUid . '-' . str_replace('.', '-', $path);
        $chevronIcon = $openMode === 'down' ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-fill';
    @endphp
    <div class="sd-group sd-group--{{ $openMode }}{{ $openMode === 'side' ? ' position-relative' : '' }}">
        <button
            class="sd-group-toggle js-submenu-toggle"
            data-target="#{{ $submenuId }}"
            data-open-mode="{{ $openMode }}"
            type="button"
        >
            <span class="sd-group-label">{{ $node['label'] }}</span>
            {{-- Badge indica visualmente que este item abre um submenu --}}
            <span class="sd-group-badge">
                <i class="sd-group-chevron {{ $chevronIcon }}"></i>
            </span>
        </button>
        <div id="{{ $submenuId }}" class="sd-submenu-panel sd-submenu-panel--{{ $openMode }}">
            @foreach ($node['nodes'] ?? [] as $childIndex => $childNode)
                @include('components.menu.partials.dynamic-dropdown-node', [
                    'node'        => $childNode,
                    'depth'       => $depth + 1,
                    'path'        => $path . '.' . $childIndex,
                    'menuUid'     => $menuUid,
                    'resolveHref' => $resolveHref,
                ])
            @endforeach
        </div>
    </div>
@endif
