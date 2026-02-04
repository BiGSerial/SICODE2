<li class="nav-item dropdown mx-2">
    <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" aria-expanded="false">
        RESPONSÁVEL
    </a>
    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
        style="background-color: #dbd8d8; width: 320px;">
        @include('components.menu.partials.services-dropdown-style')

        <li class="dropdown-header" style="background-color: #ffffff;">RESPONSÁVEL</li>

        <a class="menu-link" href="{{ route('responsible.validation') }}">
            VALIDAÇÃO DE PROJETOS <i class="ri-arrow-right-s-line"></i>
        </a>
        <a class="menu-link" href="{{ route('responsible.viability') }}">
            VIABILIDADE <i class="ri-arrow-right-s-line"></i>
        </a>
        <a class="menu-link" href="{{ route('responsible.informes') }}">
            INFORMES CONCLUSÃO <i class="ri-arrow-right-s-line"></i>
        </a>
        <a class="menu-link" href="{{ route('responsible.parciais') }}">
            INFORMES PARCIAIS <i class="ri-arrow-right-s-line"></i>
        </a>
        <a class="menu-link" href="{{ route('responsible.d5') }}">
            NOTAS D5 <i class="ri-arrow-right-s-line"></i>
        </a>

        @include('components.menu.partials.services-dropdown-script')
    </ul>
</li>
