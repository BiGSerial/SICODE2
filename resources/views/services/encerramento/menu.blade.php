@php
    $service = $service ?? \App\Services\Closure\ClosureServiceLocator::service();
@endphp
<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        @if ($service)
            @can('closure.work')
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                        <i class="bi bi-menu-button-wide"></i><span>ACOMPANHAMENTO</span><i
                            class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                        <div class="border-start border-3 mb-1 py-0">
                            <li>
                                <a href="{{ route('services.main', ['service' => $service->uuid]) }}"
                                    class="nav-item text-white fw-normal">
                                    <i class="bi bi-list-ul fs-5 edp-text-verde-dark fw-normal"></i> <span>LISTA
                                        {{ mb_strtoupper($service->service) }}</span>
                                </a>
                            </li>
                            @if (app(\App\Services\Closure\ClosureSettings::class)->selfAssignEnabled())
                                <li>
                                    <a href="{{ route('services.main', ['service' => $service->uuid, 'aba' => 'disponiveis']) }}"
                                        class="nav-item text-white fw-normal">
                                        <i class="bi bi-inboxes fs-5 edp-text-verde-dark fw-normal"></i> <span>ATIVIDADES
                                            DISPONÍVEIS</span>
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a href="{{ route('services.accompany', ['service' => $service->uuid]) }}"
                                    class="nav-item text-white fw-normal">
                                    <i class="bi bi-eye fs-5 edp-text-verde-dark fw-normal"></i> <span>ACOMPANHAMENTO</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('services.transfers', ['service' => $service->uuid]) }}"
                                    class="nav-item text-white fw-normal">
                                    <i class="bi bi-arrow-left-right fs-5 edp-text-verde-dark fw-normal"></i> <span>TRANSFERÊNCIAS</span>@livewire('components.count.closure-transfers', [], key('countClosureTransfers' . $service->uuid))
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('services.historic', ['service' => $service->uuid]) }}"
                                    class="nav-item text-white fw-normal">
                                    <i class="bi bi-clock-history fs-5 edp-text-verde-dark fw-normal"></i> <span>MEU
                                        HISTÓRICO</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </li>
            @endcan
        @endif
    </ul>
</aside>
