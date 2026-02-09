<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>ACOMPANHAMENTO</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">

                    {{-- @if (!Auth()->User()->contract) --}}
                    <li>
                        <a href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul edp-text-verde-dark fs-5"></i><span>LISTA PARA
                                {{ mb_strtoupper($service->service) }}</span>
                        </a>
                    </li>
                    {{-- @endif --}}

                    <li>
                        <a href="{{ route('dispatch.stack', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-clipboard-data edp-text-verde-dark fs-5"></i><span>CONTROLE DE
                                {{ mb_strtoupper($service->service) }}</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('dispatch.transprod', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-arrow-left-right edp-text-verde-dark fs-5"></i><span>TRANSFERÊNCIA DE
                                {{ mb_strtoupper($service->service) }}@livewire('components.count.counttransferdispatch', ['service' => $service->uuid], key('countTransfer' . $service->uuid))</span>
                        </a>
                    </li>


                                    
</div>
            </ul>
        </li>
    </ul>

    <div class="col-12">
        @livewire('production.users.occupation', ['service_id' => $service->uuid], key('production-' . $service->uuid))
    </div>
</aside>
