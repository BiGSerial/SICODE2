<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">

    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
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

    <link href="{{ asset('build/assets/app-3891f6bf.css') }}" rel="stylesheet">
    <script src="{{ asset('build/assets/app-4ed993c7.js') }}"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])



    @livewireStyles



    {{-- <link rel="stylesheet" href="{{ asset('/build/assets/app-*.css') }}" />


    @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

</head>


<!-- Scripts -->


<body class="g-sidenav edp-bg-gray toggle-sidebar">

    <nav class="navbar navbar-expand-lg fixed-top text-bg-success">
        <div class="container-fluid">
            <span class="navbar-brand text-white fw-bold">

                @hasSection('title')
                    @yield('title')
                @else
                    VIABILIDADE
                @endif

            </span>
        </div>
    </nav>

    <main id="main" class="">



        {{ $slot }}





        <footer class="footer" class="text-center">
            <div class="copyright">
                &copy; Copyright <strong><span>SICODE 2022 - {{ date('Y') }}
                        v{{ $version->appver }}</span></strong>.
                <br>
                Centro Integrado de Projetos - Espirito Santo<br>
                Laravel Framework v{{ app()->version() }}<br>
                PHP v{{ phpversion() }}<br>
                @livewire('status.bancosicode')
                @if (env('APP_QA'))
                    <h3>SICODE - AMBIENTE DE QUALIDADE</h3>
                @endif
            </div>

        </footer>
    </main><!-- End #main -->



    @livewireScripts



    <div aria-live="polite" aria-atomic="true" class="position-fixed" style="margin-right: 10%; z-index: 1060;">
        <div class="toast-container position-fixed end-0 bottom-0 p-3" style="margin-right: 40px; margin-bottom: 20px;">
            <div id="torrada" class="toast align-items-center border-0" role="alert" aria-live="assertive"
                aria-atomic="true">
            </div>
        </div>
    </div>





    <!--   Core JS Files   -->
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
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
        document.addEventListener('DOMContentLoaded', function() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    container: 'body',
                    html: true,
                })
            })
        });

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

            for (const modalEl of modals) {

                var modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
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
    </script>

    @stack('script')
    @stack('scripts')

</body>

</html>
