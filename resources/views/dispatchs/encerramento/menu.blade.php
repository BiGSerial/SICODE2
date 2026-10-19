@php
    $service = $service ?? \App\Services\Closure\ClosureServiceLocator::service();
@endphp
<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        @if ($service)
            @can('closure.dispatch')
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
                                <a href="{{ route('dispatch.transprod', ['service' => $service->uuid]) }}"
                                    class="nav-item text-white fw-normal">
                                    <i class="bi bi-arrow-left-right fs-5 edp-text-verde-dark fw-normal"></i><span>TRANSFERÊNCIA
                                        DE
                                        {{ mb_strtoupper($service->service) }}@livewire('components.count.closure-priority-requests', [], key('countClosurePriority' . $service->uuid))</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('dispatch.dashboard', ['service' => $service->uuid]) }}"
                                    class="nav-item text-white fw-normal">
                                    <i class="bi bi-graph-up fs-5 edp-text-verde-dark fw-normal"></i><span>PAINEL ANALÍTICO</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </li>
            @endcan
        @endif

        @can('closure.manager')
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#gestao-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>GESTÃO DO CICLO</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="gestao-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('closure.overview') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-speedometer2 fs-5 edp-text-verde-dark fw-normal"></i><span>VISÃO GERAL</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('closure.meta') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-bullseye fs-5 edp-text-verde-dark fw-normal"></i><span>META</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('closure.passive') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-hourglass-split fs-5 edp-text-verde-dark fw-normal"></i><span>PASSIVO</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>
        @endcan
    </ul>
</aside>
