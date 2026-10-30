{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\ServiceNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\ServiceNav::class" :route="request()->route()?->getName()" label="Menu do serviço"
    :context="['service' => $service ?? null, 'variant' => 'fiscalizacao']">
</x-nav.sidebar>
