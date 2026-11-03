{{-- <li class="nav-item dropdown mx-2">
    <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        ADMINISTRAÇÃO
    </a>
    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">
        <li><a class="dropdown-item" href="{{ route('admin.user.list') }}"><i
                    class="ri-account-pin-box-fill align-middle text-primary"></i>USUARIOS</a>
        </li>
        @can('superadm')
            <li><a class="dropdown-item" href="{{ route('admin.company.list') }}"><i
                        class="ri-building-4-fill align-middle text-primary"></i>EMPRESAS</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="{{ route('config.main') }}"><i
                        class="ri-home-gear-fill align-middle text-dark"></i>CONFIGURAÇÕES</a></li>
        @endcan

    </ul>
</li> --}}

@stack('modals')

@if (request()->routeIs('partner.*'))
    @php
        // Mesma definição do menu lateral da parceira (App\Support\Menu\PartnerNav): só os itens que a parceira pode abrir.
        $partner_search_items = collect(\App\Support\Menu\PartnerNav::topItems())
            ->filter(fn (array $item) => \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), $item['can']))
            ->map(fn (array $item) => collect($item)->except('can')->all())
            ->values()
            ->all();
    @endphp
    @if ($partner_search_items !== [])
        <x-menu.dynamic-dropdown title="BUSCAR" :sections="[['items' => $partner_search_items]]" id-prefix="partner-buscar" layout="inline" />
    @endif
@endif
