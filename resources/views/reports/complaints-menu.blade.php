{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\ReportsNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\ReportsNav::class" :route="request()->route()?->getName()" label="Menu de relatórios de reclamações" :context="['variant' => 'reclamacoes']" />
