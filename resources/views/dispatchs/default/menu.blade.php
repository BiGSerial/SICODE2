{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\DispatchNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\DispatchNav::class" :route="request()->route()?->getName()" label="Menu de despacho"
    :context="['service' => $service ?? null, 'variant' => 'default']">
</x-nav.sidebar>
