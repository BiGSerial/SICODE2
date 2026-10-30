{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\AdminNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\AdminNav::class" :route="request()->route()?->getName()" label="Menu da Administração" />
