<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#config_protest-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CONFIGURAÇÕES</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="config_protest-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('protests.dispatch.config_users') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 edp-text-verde-dark"></i> <span>USER TRIGGERS</span>

                        </a>
                    </li>




                </div>
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#protests-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>RECLAMAÇÃO</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="protests-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('protests.dispatch.lists') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 edp-text-verde-dark"></i> <span>EM ABERTO</span>

                        </a>
                    </li>
                    <li>
                        <a href="{{ route('protests.dispatch.closeds') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 text-warning"></i> <span>FECHADOS</span>
                        </a>
                    </li>



                </div>
            </ul>
        </li>

    </ul>



</aside>
