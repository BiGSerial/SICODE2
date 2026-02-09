@props([
    'title',
    'sections' => [],
    'width' => '340px',
    'idPrefix' => null,
    'itemClass' => 'mx-2',
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

        if (empty($items)) {
            return null;
        }

        $child['items'] = $items;

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

    $menuUid = (\Illuminate\Support\Str::slug($idPrefix ?: $title) ?: 'menu') . '-' . uniqid();
@endphp

@if ($visibleSections->isNotEmpty())
    <li class="nav-item dropdown {{ $itemClass }}">
        <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            data-bs-auto-close="outside" aria-expanded="false">
            {{ $title }}
        </a>
        {{ $triggerAppend ?? '' }}
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
            style="background-color: #dbd8d8; width: {{ $width }};">
            @include('components.menu.partials.services-dropdown-style')

            @foreach ($visibleSections as $sectionIndex => $section)
                @php
                    $panelId = '#panel-' . $menuUid . '-' . $sectionIndex;
                @endphp
                <li class="menu-item js-menu-toggle" data-target="{{ $panelId }}">
                    {{ $section['label'] }} <i class="ri-arrow-right-s-line"></i>
                </li>
                <div id="{{ ltrim($panelId, '#') }}" class="menu-panel">
                    @foreach ($section['items'] ?? [] as $item)
                        <a class="dropdown-item" href="{{ isset($item['route']) ? route($item['route']) : ($item['href'] ?? '#') }}">
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
                                    <a class="dropdown-item" href="{{ isset($item['route']) ? route($item['route']) : ($item['href'] ?? '#') }}">
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
        </ul>
    </li>
@endif
