<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#closure-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-check2-square"></i><span>ENCERRAMENTO</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="closure-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    @can('closure.dispatch')
                    <li>
                        <a href="{{ route('closure.dispatch') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-send fs-5 edp-text-verde-dark fw-normal"></i><span>DESPACHO</span>
                        </a>
                    </li>
                    @endcan
                    @can('closure.dispatch')
                    <li>
                        <a href="{{ route('closure.dispatch.dashboard') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-graph-up fs-5 edp-text-verde-dark fw-normal"></i><span>PAINEL ANALÍTICO</span>
                        </a>
                    </li>
                    @endcan
                    @can('closure.work')
                    <li>
                        <a href="{{ route('closure.work') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-person-workspace fs-5 edp-text-verde-dark fw-normal"></i><span>MINHA ATIVIDADE</span>
                        </a>
                    </li>
                    @endcan
                    @can('closure.work')
                    @if (app(\App\Services\Closure\ClosureSettings::class)->selfAssignEnabled())
                    <li>
                        <a href="{{ route('closure.work.available') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-inboxes fs-5 edp-text-verde-dark fw-normal"></i><span>ATIVIDADES DISPONÍVEIS</span>
                        </a>
                    </li>
                    @endif
                    @endcan
                    @can('closure.manager')
                    <li>
                        <a href="{{ route('closure.overview') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-speedometer2 fs-5 edp-text-verde-dark fw-normal"></i><span>VISÃO GERAL</span>
                        </a>
                    </li>
                    @endcan
                    @can('closure.manager')
                    <li>
                        <a href="{{ route('closure.meta') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-bullseye fs-5 edp-text-verde-dark fw-normal"></i><span>META</span>
                        </a>
                    </li>
                    @endcan
                    @can('closure.manager')
                    <li>
                        <a href="{{ route('closure.passive') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-hourglass-split fs-5 edp-text-verde-dark fw-normal"></i><span>PASSIVO</span>
                        </a>
                    </li>
                    @endcan
                </div>
            </ul>
        </li>
    </ul>
</aside>
