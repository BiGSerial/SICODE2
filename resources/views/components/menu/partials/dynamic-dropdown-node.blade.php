@php
    $kind = $node['kind'] ?? 'item';
@endphp

@if ($kind === 'header')
    <li class="dropdown-header services-dropdown-header">{{ $node['label'] }}</li>
@elseif ($kind === 'item')
    <a class="dropdown-item {{ $depth === 0 ? 'services-dropdown-direct-item' : '' }}" href="{{ $resolveHref($node) }}">
        <span class="d-flex align-items-center gap-2">
            @if (!empty($node['icon']))
                @php
                    $iconBaseClass = trim((string) preg_replace('/\btext-\S+\b/', '', $node['icon']));
                @endphp
                <i class="{{ $iconBaseClass }} align-middle {{ $node['iconClass'] ?? 'text-primary' }}"></i>
            @endif
            {{ $node['label'] }}
        </span>
        @if (!empty($node['countComponent']))
            @livewire($node['countComponent'], $node['countParams'] ?? [], key($node['countKey'] ?? ($menuUid . '-' . md5(($path ?? '') . '-' . $node['label']))))
        @endif
    </a>
@elseif ($kind === 'group')
    @php
        $openMode = in_array($node['open'] ?? ($depth === 0 ? 'down' : 'side'), ['down', 'side'], true)
            ? ($node['open'] ?? ($depth === 0 ? 'down' : 'side'))
            : 'side';
        $submenuId = '#submenu-inline-' . $menuUid . '-' . str_replace('.', '-', $path);
    @endphp
    <div class="submenu {{ $openMode === 'down' ? 'submenu-down' : 'submenu-side' }}">
        <button class="submenu-toggle js-submenu-toggle"
            data-target="{{ $submenuId }}"
            data-open-mode="{{ $openMode }}"
            type="button">
            {{ $node['label'] }} <i class="ri-arrow-right-s-line"></i>
        </button>
        <div id="{{ ltrim($submenuId, '#') }}" class="submenu-panel submenu-panel--{{ $openMode }}">
            @foreach ($node['nodes'] ?? [] as $childIndex => $childNode)
                @include('components.menu.partials.dynamic-dropdown-node', [
                    'node' => $childNode,
                    'depth' => $depth + 1,
                    'path' => $path . '.' . $childIndex,
                    'menuUid' => $menuUid,
                    'resolveHref' => $resolveHref,
                ])
            @endforeach
        </div>
    </div>
@endif
