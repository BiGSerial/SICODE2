@props([
    'title',
    'sections' => [],
    'nodes' => [],
    'width' => '340px',
    'idPrefix' => null,
    'itemClass' => 'mx-2',
    'simple' => false,
    'layout' => 'inline',
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

    // &$buildChild is required for self-recursion (group-within-group, e.g. OPERAÇÃO DO SISTEMA) — PHP closures don't capture themselves by default.
    $buildChild = function (array $child) use ($isVisible, $buildItem, &$buildChild): ?array {
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

    // Cabeçalho sem nenhum item visível depois dele (até o próximo cabeçalho) não aparece.
    $dropOrphanHeaders = function (array $list) use (&$dropOrphanHeaders): array {
        $out = [];

        foreach ($list as $i => $node) {
            if (($node['kind'] ?? 'item') === 'header') {
                $next = $list[$i + 1] ?? null;

                if ($next === null || ($next['kind'] ?? 'item') === 'header') {
                    continue;
                }
            }

            if (($node['kind'] ?? 'item') === 'group') {
                $node['nodes'] = $dropOrphanHeaders($node['nodes'] ?? []);
            }

            $out[] = $node;
        }

        return $out;
    };

    $visibleNodes = collect($dropOrphanHeaders($visibleNodes->all()));

    $countLeaves = function (array $list) use (&$countLeaves): int {
        return array_sum(array_map(fn ($n) => ($n['kind'] ?? 'item') === 'group' ? $countLeaves($n['nodes'] ?? []) : (($n['kind'] ?? 'item') === 'item' ? 1 : 0), $list));
    };

    // Menus com muitos itens ganham busca no cabeçalho.
    $hasSearch = $countLeaves($visibleNodes->all()) >= 9;

    $menuUid = (\Illuminate\Support\Str::slug($idPrefix ?: $title) ?: 'menu') . '-' . uniqid();

    $resolveHref = function (array $item): string {
        if (!empty($item['route'])) {
            return route($item['route'], $item['routeParams'] ?? []);
        }

        return $item['href'] ?? '#';
    };
@endphp

@if ($visibleNodes->isNotEmpty())
    <li class="nav-item dropdown {{ $itemClass }}">
        <a class="nav-link dropdown-toggle text-white nav-profile tb-trigger" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-haspopup="true">
            {{ $title }}<span class="tb-trigger-count" hidden></span>
        </a>
        {{ $triggerAppend ?? '' }}
        <ul class="dropdown-menu dropdown-menu-end tb-menu" role="menu" aria-label="{{ $panelTitle ?: $title }}" style="width: {{ $width }};">
            <li class="tb-head" role="presentation">
                <span class="tb-title">{{ $panelTitle ?: $title }}</span>
                @if ($hasSearch)
                    <input type="search" class="tb-search" placeholder="Filtrar…" aria-label="Filtrar em {{ $panelTitle ?: $title }}" autocomplete="off">
                @endif
            </li>
            <li role="presentation">
                <ul class="tb-list">
                    @foreach ($visibleNodes as $nodeIndex => $node)
                        @include('components.menu.partials.dynamic-dropdown-node', [
                            'node'        => $node,
                            'depth'       => 0,
                            'path'        => (string) $nodeIndex,
                            'menuUid'     => $menuUid,
                            'resolveHref' => $resolveHref,
                        ])
                    @endforeach
                    @if ($hasSearch)
                        <li class="tb-empty" hidden>Nada encontrado.</li>
                    @endif
                </ul>
            </li>
        </ul>
    </li>
@endif
