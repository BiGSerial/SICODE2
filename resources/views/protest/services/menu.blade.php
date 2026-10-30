{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\ProtestNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\ProtestNav::class" :route="request()->route()?->getName()" label="Menu de reclamações"
    :context="['variant' => 'services']" />
