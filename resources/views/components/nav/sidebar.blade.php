@props([
    'nav',
    'route'   => null,
    'label'   => 'Menu',
    'context' => [],
])

{{-- Menu lateral padrão (docs/PADRAO_MENU_LATERAL.md). `nav` é a classe da área (subclasse de App\Support\Menu\SidebarNav).
     Seletor de módulos no topo, título do módulo, filtro, grupos recolhíveis (<details>) com cor própria e rodapé.
     `context` leva o que a área precisa (ex.: a atividade aberta). O slot vira o painel extra do rodapé (ex.: ocupação da atividade).
     Estilo: resources/sass/nav/_sidebar.scss · comportamento: resources/js/nav/sidebar.js. Nada de CSS/JS inline. --}}
@php
    $modules = $nav::resolve($context);
    $theme   = $nav::THEME;
    // Tema próprio (ex.: parceira): o fundo vem do tema, não do verde padrão da empresa.
    $asideBg = $theme ? '' : 'edp-bg-sprucegreen-100';
@endphp

{{-- Sem nenhum item permitido, mantém só a coluna (o layout reserva o espaço do menu). --}}
@if ($modules === [])
    <div @class(['snav', 'snav--' . $theme => $theme])><aside id="sidebar" class="sidebar {{ $asideBg }} snav-aside" aria-label="{{ $label }}"></aside></div>
@else
@php
    $where        = $nav::locate($route, $context);
    $activeModule = $where['module'];
    $activeGroup  = $where['group'];
    $activeItem   = $where['item'];
    $module       = $modules[$activeModule];
@endphp

<div @class(['snav', 'snav--' . $theme => $theme])>
    <aside id="sidebar" class="sidebar {{ $asideBg }} snav-aside" aria-label="{{ $label }}">

        {{-- Seletor de módulos --}}
        @if (count($modules) > 1)
            <nav class="snav-modules snav-modules-{{ min(count($modules), 4) }}" aria-label="Módulos">
                @foreach ($modules as $key => $mod)
                    @php $entry = $nav::entry($key, $context); @endphp
                    <a href="{{ route($entry['route'], $entry['params']) }}" class="snav-module {{ $key === $activeModule ? 'is-active' : '' }}"
                        @if ($key === $activeModule) aria-current="true" @endif title="{{ $mod['label'] }} · {{ $mod['hint'] }}">
                        <i class="{{ $mod['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $mod['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="snav-title">
            <span class="snav-title-ico"><i class="{{ $module['icon'] }}" aria-hidden="true"></i></span>
            <div>
                <strong>{{ $module['label'] }}</strong>
                <small>{{ $module['hint'] }}</small>
            </div>
        </div>

        <div class="snav-search">
            <i class="ri-search-line" aria-hidden="true"></i>
            <input type="search" class="snav-search-input" placeholder="Filtrar menu…" aria-label="Filtrar itens do menu" data-snav-filter autocomplete="off">
        </div>

        <div class="snav-scroll">
            @foreach ($module['groups'] as $group)
                @if (!empty($group['flat']))
                    <ul class="snav-list snav-list-flat" role="list">
                        @foreach ($group['items'] as $item)
                            <x-nav.item :item="$item" :active-item="$activeItem" />
                        @endforeach
                    </ul>
                @else
                    <details class="snav-group" data-snav-group="{{ $activeModule }}.{{ $group['key'] }}" @if ($group['key'] === $activeGroup || $activeGroup === null) open @endif>
                        <summary class="snav-group-head">
                            <span class="snav-group-ico"><i class="{{ $group['icon'] }}" aria-hidden="true"></i></span>
                            <span class="snav-group-label">{{ $group['label'] }}</span>
                            <i class="ri-arrow-down-s-line snav-chev" aria-hidden="true"></i>
                        </summary>
                        <ul class="snav-list" role="list">
                            @foreach ($group['items'] as $item)
                                <x-nav.item :item="$item" :active-item="$activeItem" />
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endforeach

            <p class="snav-empty" hidden>Nenhum item encontrado.</p>
        </div>

        @if (isset($slot) && trim((string) $slot) !== '')
            <div class="snav-extra">{{ $slot }}</div>
        @endif

        <footer class="snav-foot">
            <a href="{{ route('home') }}" class="snav-foot-link"><i class="ri-home-4-line" aria-hidden="true"></i> Início</a>
        </footer>
    </aside>
</div>
@endif
