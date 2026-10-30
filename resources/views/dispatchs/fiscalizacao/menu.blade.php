{{-- Menu lateral padrão: a estrutura vive em App\Support\Menu\DispatchNav (docs/PADRAO_MENU_LATERAL.md). Não escreva HTML de sidebar aqui. --}}
<x-nav.sidebar :nav="\App\Support\Menu\DispatchNav::class" :route="request()->route()?->getName()" label="Menu de despacho"
    :context="['service' => $service ?? null, 'variant' => 'fiscalizacao']">
    @if (!empty($service))
        @livewire('production.users.occupation', ['service_id' => $service->uuid], key('production-' . $service->uuid))
    @endif
</x-nav.sidebar>
