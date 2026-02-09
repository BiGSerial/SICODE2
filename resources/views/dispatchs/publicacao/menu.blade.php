<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>ACOMPANHAMENTO</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">

                    <li>
                        <a href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 edp-text-verde-dark fw-normal"></i><span>LISTA PARA
                                {{ mb_strtoupper($service->service) }}</span>
                        </a>
                    </li>


                    <li>
                        <a href="{{ route('dispatch.stack', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-eye fs-5 edp-text-verde-dark fw-normal"></i><span>CONTROLE DE
                                {{ mb_strtoupper($service->service) }}</span>
                        </a>
                    </li>


                    <li>
                        <a href="{{ route('reports.workreport') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 edp-text-verde-dark fw-normal"></i><span>INFORMES DE
                                OBRA</span>
                        </a>
                    </li>
                                    
</div>
            </ul>
        </li>
    </ul>

    <div class="col-12">
        @livewire('production.users.occupation-construction', ['service_id' => $service->uuid], key('production-' . $service->uuid))
    </div>

</aside>
