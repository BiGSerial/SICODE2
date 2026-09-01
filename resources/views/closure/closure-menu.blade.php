<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#closure-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-check2-square"></i><span>ENCERRAMENTO</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="closure-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
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
    </ul>
</aside>
