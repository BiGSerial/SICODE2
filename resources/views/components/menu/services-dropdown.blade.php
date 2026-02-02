@props([
    'paymentService' => null,
    'reportsLinks' => [],
])

<li class="nav-item dropdown mx-2">
    <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" aria-expanded="false">
        SERVIÇOS
    </a>
    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
        style="background-color: #dbd8d8; width: 340px;">
        @include('components.menu.partials.services-dropdown-style')

        <li class="dropdown-header" style="background-color: #ffffff;">SERVIÇOS GERAIS</li>
            <li><a class="dropdown-item" href="{{ route('cancellations.create') }}"><i
                        class="ri-close-circle-line align-middle text-primary"></i>CANCELAMENTO DE NOTAS</a>
            </li>
            <li><a class="dropdown-item" href="{{ route('cancellations.my') }}"><i
                        class="ri-folder-user-line align-middle text-primary"></i>MINHAS SOLICITAÇÕES</a>
            </li>

        @if (count($reportsLinks))
            <li class="menu-item js-menu-toggle" data-target="#panel-relatorios">
                RELATÓRIOS <i class="ri-arrow-right-s-line"></i>
            </li>
            <div id="panel-relatorios" class="menu-panel">
                <div class="submenu">
                    <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-relatorios"
                        type="button">
                        RELATÓRIOS <i class="ri-arrow-right-s-line"></i>
                    </button>
                    <div id="submenu-relatorios" class="submenu-panel">
                        @foreach ($reportsLinks as $report)
                            <a class="dropdown-item" href="{{ route($report['route']) }}">
                                <i class="ri-file-chart-line align-middle text-primary"></i>{{ $report['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @include('components.menu.partials.services-dropdown-script')
    </ul>
</li>
