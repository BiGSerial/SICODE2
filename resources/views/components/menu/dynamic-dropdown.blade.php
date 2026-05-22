@props([
    'title',
    'sections' => [],
    'nodes' => [],
    'width' => '340px',
    'idPrefix' => null,
    'itemClass' => 'mx-2',
    'simple' => false,
    'layout' => 'panel',
    'panelTitle' => null,
])

@php
    $user = auth()->user();

    $isVisible = function (array $node) use ($user): bool {
        if (array_key_exists('visible', $node) && !$node['visible']) {
            return false;
        }

        if (!empty($node['can']) && (!$user || !$user->can($node['can']))) {
            return false;
        }

        return true;
    };

    $buildItem = function (array $item) use ($isVisible): ?array {
        if (!$isVisible($item)) {
            return null;
        }

        $item['kind'] = $item['kind'] ?? 'item';

        return $item;
    };

    $buildChild = function (array $child) use ($isVisible, $buildItem): ?array {
        if (!$isVisible($child)) {
            return null;
        }

        $items = collect($child['items'] ?? [])
            ->map(fn(array $item) => $buildItem($item))
            ->filter()
            ->values()
            ->all();

        $children = collect($child['children'] ?? [])
            ->map(fn(array $nestedChild) => $buildChild($nestedChild))
            ->filter()
            ->values()
            ->all();

        if (empty($items) && empty($children)) {
            return null;
        }

        $child['kind'] = 'group';
        $child['nodes'] = array_merge($items, $children);
        unset($child['items'], $child['children']);

        return $child;
    };

    $buildSection = function (array $section) use ($isVisible, $buildItem, $buildChild): ?array {
        if (!$isVisible($section)) {
            return null;
        }

        $items = collect($section['items'] ?? [])
            ->map(fn(array $item) => $buildItem($item))
            ->filter()
            ->values()
            ->all();

        $children = collect($section['children'] ?? [])
            ->map(fn(array $child) => $buildChild($child))
            ->filter()
            ->values()
            ->all();

        if (empty($items) && empty($children)) {
            return null;
        }

        $section['items'] = $items;
        $section['children'] = $children;

        return $section;
    };

    $visibleSections = collect($sections)
        ->map(fn(array $section) => $buildSection($section))
        ->filter()
        ->values();

    $buildNode = function (array $node) use (&$buildNode, $isVisible, $buildItem): ?array {
        if (!$isVisible($node)) {
            return null;
        }

        $kind = $node['kind'] ?? ((isset($node['nodes']) || isset($node['items']) || isset($node['children'])) ? 'group' : 'item');

        if ($kind === 'header') {
            return $node;
        }

        if ($kind === 'item') {
            return $buildItem($node);
        }

        $rawNodes = $node['nodes'] ?? array_merge($node['items'] ?? [], $node['children'] ?? []);

        $children = collect($rawNodes)
            ->map(fn(array $child) => $buildNode($child))
            ->filter()
            ->values()
            ->all();

        if (empty($children)) {
            return null;
        }

        $node['kind'] = 'group';
        $node['nodes'] = $children;
        unset($node['items'], $node['children']);

        return $node;
    };

    $panelHeading = mb_strtoupper(trim($panelTitle ?: $title));

    $nodesFromSections = $visibleSections
        ->flatMap(function (array $section) use ($panelHeading) {
            $sectionNodes = [];

            if (!empty($section['label']) && mb_strtoupper(trim($section['label'])) !== $panelHeading) {
                $sectionNodes[] = [
                    'kind' => 'header',
                    'label' => $section['label'],
                ];
            }

            foreach ($section['items'] ?? [] as $item) {
                $item['kind'] = 'item';
                $sectionNodes[] = $item;
            }

            foreach ($section['children'] ?? [] as $child) {
                $child['kind'] = 'group';
                $child['nodes'] = $child['nodes'] ?? array_merge($child['items'] ?? [], $child['children'] ?? []);
                unset($child['items'], $child['children']);
                $sectionNodes[] = $child;
            }

            return $sectionNodes;
        })
        ->values()
        ->all();

    $sourceNodes = collect($nodes)->isNotEmpty() ? $nodes : $nodesFromSections;

    $visibleNodes = collect($sourceNodes)
        ->map(fn(array $node) => $buildNode($node))
        ->filter()
        ->values();

    $menuUid = (\Illuminate\Support\Str::slug($idPrefix ?: $title) ?: 'menu') . '-' . uniqid();

    $resolveHref = function (array $item): string {
        if (!empty($item['route'])) {
            return route($item['route'], $item['routeParams'] ?? []);
        }

        return $item['href'] ?? '#';
    };
@endphp

@if ($visibleNodes->isNotEmpty() || $visibleSections->isNotEmpty())
    <li class="nav-item dropdown {{ $itemClass }}">
        <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            data-bs-auto-close="outside" aria-expanded="false">
            {{ $title }}
        </a>
        {{ $triggerAppend ?? '' }}
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
            style="width: {{ $width }};">
            @include('components.menu.partials.services-dropdown-style')

            @if ($layout === 'inline')
                <li class="dropdown-header services-dropdown-header">{{ $panelTitle ?: $title }}</li>

                @foreach ($visibleNodes as $nodeIndex => $node)
                    @include('components.menu.partials.dynamic-dropdown-node', [
                        'node' => $node,
                        'depth' => 0,
                        'path' => (string) $nodeIndex,
                        'menuUid' => $menuUid,
                        'resolveHref' => $resolveHref,
                    ])
                @endforeach
                @include('components.menu.partials.services-dropdown-script')
            @elseif ($simple)
                @foreach ($visibleSections as $section)
                    @if (!empty($section['label']))
                        <li class="px-3 py-2 text-uppercase small fw-semibold text-muted">{{ $section['label'] }}</li>
                    @endif

                    @foreach ($section['items'] ?? [] as $item)
                        <a class="dropdown-item" href="{{ $resolveHref($item) }}">
                            @if (!empty($item['icon']))
                                <i class="{{ $item['icon'] }} align-middle text-primary"></i>
                            @endif
                            {{ $item['label'] }}
                            @if (!empty($item['countComponent']))
                                @livewire($item['countComponent'], $item['countParams'] ?? [], key($item['countKey'] ?? ($menuUid . '-' . md5($item['label']))))
                            @endif
                        </a>
                    @endforeach

                    @foreach ($section['children'] ?? [] as $child)
                        @if (!empty($child['label']))
                            <li class="px-3 pt-2 pb-1 text-uppercase small fw-semibold text-muted">{{ $child['label'] }}</li>
                        @endif
                        @foreach ($child['items'] as $item)
                            <a class="dropdown-item" href="{{ $resolveHref($item) }}">
                                @if (!empty($item['icon']))
                                    <i class="{{ $item['icon'] }} align-middle text-primary"></i>
                                @endif
                                {{ $item['label'] }}
                                @if (!empty($item['countComponent']))
                                    @livewire($item['countComponent'], $item['countParams'] ?? [], key($item['countKey'] ?? ($menuUid . '-' . md5($item['label']))))
                                @endif
                            </a>
                        @endforeach
                    @endforeach
                @endforeach
            @else
                @foreach ($visibleSections as $sectionIndex => $section)
                    @php
                        $panelId = '#panel-' . $menuUid . '-' . $sectionIndex;
                    @endphp
                    <li class="menu-item js-menu-toggle" data-target="{{ $panelId }}">
                        {{ $section['label'] }} <i class="ri-arrow-right-s-line"></i>
                    </li>
                    <div id="{{ ltrim($panelId, '#') }}" class="menu-panel">
                        @foreach ($section['items'] ?? [] as $item)
                            <a class="dropdown-item" href="{{ $resolveHref($item) }}">
                                @if (!empty($item['icon']))
                                    <i class="{{ $item['icon'] }} align-middle text-primary"></i>
                                @endif
                                {{ $item['label'] }}
                                @if (!empty($item['countComponent']))
                                    @livewire($item['countComponent'], $item['countParams'] ?? [], key($item['countKey'] ?? ($menuUid . '-' . md5($item['label']))))
                                @endif
                            </a>
                        @endforeach

                        @foreach ($section['children'] ?? [] as $childIndex => $child)
                            @php
                                $submenuId = '#submenu-' . $menuUid . '-' . $sectionIndex . '-' . $childIndex;
                                $submenuClass = $loop->last ? 'submenu' : 'submenu mb-2';
                            @endphp
                            <div class="{{ $submenuClass }}">
                                <button class="submenu-toggle js-submenu-toggle" data-target="{{ $submenuId }}"
                                    type="button">
                                    {{ $child['label'] }} <i class="ri-arrow-right-s-line"></i>
                                </button>
                                <div id="{{ ltrim($submenuId, '#') }}" class="submenu-panel">
                                    @foreach ($child['items'] as $item)
                                        <a class="dropdown-item" href="{{ $resolveHref($item) }}">
                                            @if (!empty($item['icon']))
                                                <i class="{{ $item['icon'] }} align-middle text-primary"></i>
                                            @endif
                                            {{ $item['label'] }}
                                            @if (!empty($item['countComponent']))
                                                @livewire($item['countComponent'], $item['countParams'] ?? [], key($item['countKey'] ?? ($menuUid . '-' . md5($item['label']))))
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @include('components.menu.partials.services-dropdown-script')
            @endif
        </ul>
    </li>
@endif
