{{-- Menu superior: definição única em App\Support\Menu\TopbarNav (docs/MenuSuperior.md). Estilo em resources/sass/nav/_topbar.scss e
     comportamento em resources/js/nav/topbar.js; nada de CSS/JS aqui. --}}
@foreach (\App\Support\Menu\TopbarNav::menus(Auth()->user()) as $menu)
    <x-menu.dynamic-dropdown
        :title="$menu['title']"
        :nodes="$menu['nodes'] ?? []"
        :sections="$menu['sections'] ?? []"
        :width="$menu['width'] ?? '320px'"
        :panel-title="$menu['panelTitle'] ?? null"
        :id-prefix="$menu['key']"
        item-class="mx-2 position-relative"
    />
@endforeach

{{-- Contadores de todos os itens, calculados de uma vez depois da página carregar. --}}
@livewire('components.menu.topbar-counts', key('topbar-counts'))
