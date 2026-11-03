{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\CancellationNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\CancellationNav::class" :route="request()->route()?->getName()" label="Menu de cancelamentos" />
