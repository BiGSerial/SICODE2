<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>


    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">

    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">



    <!-- Template Main CSS File -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

    @stack('css')

    <style>
        .popover {
            background-color: #143f47;
            color: #28FF52;
        }

        .popover .popover-header {
            background-color: #365960;
            color: #28FF52;
            font-weight: bold;
        }

        .popover .popover-body {
            background-color: #143f47;
            color: #fff;
        }

        .popover.bs-popover-top>.arrow::after,
        .popover.bs-popover-bottom>.arrow::after,
        .popover.bs-popover-left>.arrow::after,
        .popover.bs-popover-right>.arrow::after {
            background-color: #143f47;
            border-color: #143f47;

        }

        .avatar-circle {
            width: var(--avatar-size, 120px) !important;
            height: var(--avatar-size, 120px) !important;
            min-width: var(--avatar-size, 120px) !important;
            min-height: var(--avatar-size, 120px) !important;
            object-fit: cover;
            border-radius: 50% !important;
            display: inline-block;
        }
    </style>

    {{-- @if ($_SERVER['SERVER_NAME'] != 'localhost')
        @php
            $path = $_SERVER['CONTEXT_DOCUMENT_ROOT'] . '/build/assets';

            $files = scandir($path);

            $acss = '';
            $ajs = '';

            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $arquivo = explode('.', $file);

                    if ($arquivo[2] == 'css') {
                        $acss = $file;
                    }

                    if ($arquivo[2] == 'js') {
                        $ajs = $file;
                    }
                }
            }

            // dd($acss, $ajs);

        @endphp


        <link href="{{ asset('build/assets') . '/' . $acss }}" rel="stylesheet">
        <script src="{{ asset('build/assets') . '/' . $ajs }}"></script>
    @endif --}}

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @livewireStyles



    {{-- <link rel="stylesheet" href="{{ asset('/build/assets/app-*.css') }}" />


    @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

</head>


<!-- Scripts -->


<body class="g-sidenav-show edp-bg-gray toggle-sidebar">




    <header id="header"
        class="header fixed-top d-flex align-items-center @if (session('impersonate')) text-bg-danger @else edp-bg-marineblue-100 text-white @endif">
        <i class="bi bi-list toggle-sidebar-btn text-white me-3"></i>

        @if (env('APP_QA') && env('APP_DEBUG'))
            <i class="text-white fs-6">Desenvolvimento</i>
        @elseif (env('APP_QA'))
            <i class="text-white fs-6">Qualidade</i>
        @else
            <i class="text-white fs-6">Produção</i>
        @endif

        @if (session('impersonate'))
            <h5 class="text-warning text-uppercase mx-4 align-middle">VOCÊ ESTÁ NA VISÂO DE
                <strong>{{ Auth()->User()->name }}</strong>.
                <a href="{{ route('stopImpersonating') }}" class="btn btn-sm btn-primary mx-2 align-middle">PARAR
                    VISÃO</a>
            </h5>
        @endif
        <nav class="header-nav ms-auto me-2">
            <ul class="d-flex align-items-center">

                {{-- ITENS DE MENU --}}
                @include('layouts.menu_itens')


                @if (!Auth()->User()->contract)
                    {{-- <li class="nav-item mx-2">
                        <a href="" class="logo d-flex mx-0">
                            <img src="{{ asset('img/EDP-Logo-white.svg') }}" class="m-0 p-0" alt="">
                            <span class="d-none d-lg-block text-edp-verde">sicode</span>
                        </a>
                    </li> --}}
                    <li class="nav-item mx-2"><div class="d-flex align-items-end justify-content-between">
                        <a href="{{ route('home') }}" class="d-flex align-items-end">
                            <img src="{{ asset('img/EDP-Logo-white.svg') }}" class="align-middle" alt=""
                                height="30">
                            <span class="d-none d-lg-block text-edp-verde fw-bold fs-4">sicode</span>
                        </a>
                    </div></li><!-- End Logo -->
                @else
                    {{-- <li class="nav-item mx-2">
                        <a href="" class="logo d-flex mx-0">
                            <span
                                class="d-none d-lg-block text-white">{{ isset(Auth()->user()->Employee->Contract->company->name) ? mb_strtolower(explode(' ', Auth()->user()->Employee->Contract->company->name)[0]) : '' }}</span>
                            <span class="d-none d-lg-block text-edp-verde">sicode</span>
                        </a>
                    </li> --}}
                    <li class="nav-item mx-2"><div class="d-flex align-items-end justify-content-between">
                        <a href="{{ route('home') }}" class="d-flex align-items-end">
                            <span
                                class="d-none d-lg-block text-white fw-bold fs-4">{{ isset(Auth()->user()->Employee->Contract->company->name) ? mb_strtolower(explode(' ', Auth()->user()->Employee->Contract->company->name)[0]) : '' }}</span>
                            <span class="d-none d-lg-block text-edp-verde fw-bold fs-4">sicode</span>
                        </a>
                    </div></li><!-- End Logo -->
                @endif

                @livewire('components.notify.notifys', key('notify.menu.sicode'))

                {{-- USER MENU --}}
                <li class="nav-item mx-2">
                    <div class="dropdown  d-flex align-items-center">
                        @php
                            $authUser = auth()->user();
                            $navDelegations = $authUser?->delegationsReceived()->active()->with('principal')->get();
                            $navDelegationsNames = $navDelegations
                                ? $navDelegations->map(fn($delegation) => $delegation->principal?->name)->filter()->implode(', ')
                                : '';
                        @endphp
                        <a class="nav-link nav-profile" href="#" data-bs-toggle="dropdown">
                            <div class="position-relative d-inline-block">
                                <img src="{{ $authUser?->avatar_url }}" alt="Avatar"
                                    class="rounded-circle avatar-circle" style="--avatar-size: 42px;">
                                @if ($navDelegations && $navDelegations->isNotEmpty())
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"
                                        data-bs-toggle="tooltip"
                                        title="Delegado por {{ $navDelegationsNames ?: 'outros usuarios' }}">
                                        <span class="visually-hidden">Delegado ativo</span>
                                    </span>
                                @endif
                            </div>
                        </a><!-- End Profile Iamge Icon -->

                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                            <li class="dropdown-header">
                                <h6>{{ auth()->user()->name }}</h6>
                                <span></span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            @if ($navDelegations && $navDelegations->isNotEmpty())
                                <li class="px-3">
                                    <small class="text-muted d-block">Cobrindo</small>
                                    <span class="small">{{ $navDelegationsNames }}</span>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            @endif

                            <li>
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('profile', ['id' => auth()->user()->id]) }}">
                                    <i class="bi bi-person"></i>
                                    <span>Meu Perfil</span>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Sair</span>

                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul><!-- End Profile Dropdown Items -->
                    </div>
                </li>

            </ul>
        </nav>
    </header>

    @yield('menu')

    <main id="main" class="main">



        @yield('breadcrumb')
        @yield('content')





        <footer class="footer" class="text-center">
            <div class="copyright">
                &copy; Copyright <strong><span>SICODE 2022 - {{ date('Y') }}
                        v{{ $version->appver }}</span></strong>.
                <br>
                Centro Integrado de Projetos - Espirito Santo<br>
                Laravel Framework v{{ app()->version() }}<br>
                PHP v{{ phpversion() }}<br>
                @livewire('status.bancosicode', key('banco-sicode'))
                @if (env('APP_QA'))
                    <h3>SICODE - AMBIENTE DE QUALIDADE</h3>
                @endif
                @livewire('watchdog', key('whatdog-sicode'))
            </div>

        </footer>


        @livewire('components.modal.priority', key('modal.priority.sicode'))
        @livewire('components.notify.all-notifies', key('notify.all.sicode'))








        <div aria-live="polite" aria-atomic="true" class="position-fixed" style="margin-right: 10%; z-index: 1060;">
            <div class="toast-container position-fixed end-0 bottom-0 p-3"
                style="margin-right: 40px; margin-bottom: 20px;">
                <div id="torrada" class="toast align-items-center border-0" role="alert" aria-live="assertive"
                    aria-atomic="true">
                </div>
            </div>
        </div>

        @stack('modals')

        @livewireScripts

        <!--   Core JS Files   -->
        <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
                class="bi bi-arrow-up-short"></i></a>

        <!-- Vendor JS Files -->
        <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
        {{-- <script defer scr="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script> --}}
        <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>

        {{-- <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script> --}}
        <script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>

        <!-- Template Main JS File -->
        <script src="{{ asset('assets/js/main.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

        <!-- Alpine v3 -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Focus plugin -->
        <script defer src="https://unpkg.com/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>



        <script>
            window.addEventListener('swal', function(e) {
                Swal.fire(e.detail);
            });

            window.addEventListener('torrada', function(e) {



                showToast(createToast(e.detail.menssage, ' text-bg-' + e.detail.status));

            });

            window.addEventListener("showModal", function(e) {

                const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
                myModal.show();
            })

            window.addEventListener("hideModal", function(e) {
                const modals = document.getElementsByClassName("modal show");
                const modalsArray = Array.from(modals);

                modalsArray.forEach(modalEl => {
                    try {
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        } else {
                            console.warn('Modal element found without Bootstrap Modal instance:', modalEl);
                        }
                    } catch (error) {
                        console.error('Error hiding modal:', modalEl, error);
                    }
                });

                // Check for and remove lingering modal backdrops
                const backdrops = document.getElementsByClassName('modal-backdrop fade show');
                const backdropsArray = Array.from(backdrops);

                backdropsArray.forEach(backdrop => {
                    try {
                        backdrop.remove();
                    } catch (error) {
                        console.error('Error removing backdrop:', backdrop, error);
                    }
                });

                // Check for and remove the 'modal-open' class from the body
                if (document.body.classList.contains('modal-open')) {
                    document.body.classList.remove('modal-open');
                }
            });


            document.addEventListener('hidden.bs.modal', function(event) {
                const modals = document.getElementsByClassName('modal show');
                const modalsArray = Array.from(modals);

                modalsArray.forEach(modalEl => {
                    try {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        } else {
                            console.warn('Elemento modal sem instância Bootstrap encontrada:', modalEl);
                        }
                    } catch (error) {
                        console.error('Erro ao ocultar modal:', modalEl, error);
                    }
                });

                // Remover eventuais backdrops que restarem
                const backdrops = document.getElementsByClassName('modal-backdrop fade show');
                const backdropsArray = Array.from(backdrops);

                backdropsArray.forEach(backdrop => {
                    try {
                        backdrop.remove();
                    } catch (error) {
                        console.error('Erro ao remover backdrop:', backdrop, error);
                    }
                });

                // Remover classe 'modal-open' do corpo da página
                if (document.body.classList.contains('modal-open')) {
                    document.body.classList.remove('modal-open');
                }
            });



            function createToast(message, bgClass) {


                var toast = document.createElement('div');
                toast.className = 'toast';
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');

                var toastHeader = document.createElement('div');
                toastHeader.className = 'toast-header ' + bgClass;
                toastHeader.innerHTML =
                    '<strong class="me-auto">System Message</strong><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>';

                var toastBody = document.createElement('div');
                toastBody.className = 'toast-body';
                toastBody.textContent = message;

                toast.appendChild(toastHeader);
                toast.appendChild(toastBody);

                return toast;
            }

            function showToast(toast) {
                var toastContainer = document.querySelector('.toast-container');
                toastContainer.appendChild(toast);

                var bootstrapToast = new bootstrap.Toast(toast);
                bootstrapToast.show();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const localStorageKey = 'activeMenu'; // Chave única para o menu ativo

                // Restaurar o estado do menu ativo
                const activeMenuId = localStorage.getItem(localStorageKey);
                if (activeMenuId) {
                    const activeMenu = document.querySelector(activeMenuId);
                    const activeLink = document.querySelector(`.nav-link[data-bs-target="${activeMenuId}"]`);
                    if (activeMenu) {
                        activeMenu.classList.add('show'); // Expande o menu
                        activeLink?.classList.remove('collapsed'); // Remove a classe de colapso
                    }
                }

                // Adicionar evento de clique em todos os links com submenu
                document.querySelectorAll('.nav-link[data-bs-toggle="collapse"]').forEach(function(link) {
                    link.addEventListener('click', function() {
                        const targetId = link.getAttribute('data-bs-target');
                        const targetElement = document.querySelector(targetId);

                        // Fechar o menu atualmente ativo
                        const previouslyActiveId = localStorage.getItem(localStorageKey);
                        if (previouslyActiveId && previouslyActiveId !== targetId) {
                            const previouslyActiveMenu = document.querySelector(previouslyActiveId);
                            const previouslyActiveLink = document.querySelector(
                                `.nav-link[data-bs-target="${previouslyActiveId}"]`);
                            previouslyActiveMenu?.classList.remove('show');
                            previouslyActiveLink?.classList.add('collapsed');
                        }

                        // Salvar o novo menu ativo no localStorage
                        if (targetElement.classList.contains('show')) {
                            localStorage.removeItem(localStorageKey); // Remove o estado ativo
                        } else {
                            localStorage.setItem(localStorageKey, targetId); // Define o novo ativo
                        }
                    });
                });
            });
        </script>



        @stack('script')
        @stack('scripts')

</body>

</html>
