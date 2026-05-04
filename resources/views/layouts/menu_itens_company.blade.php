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
        $partner_search_sections = [
            [
                'items' => [
                    ['label' => 'BUSCAR NOTAS', 'route' => 'partner.search.notes', 'icon' => 'ri-search-eye-line'],
                ],
            ],
        ];
    @endphp
    <x-menu.dynamic-dropdown title="BUSCAR" :sections="$partner_search_sections" id-prefix="partner-buscar" layout="inline" />
@endif
