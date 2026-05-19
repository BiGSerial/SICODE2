<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>
<!--
=========================================================
* Soft UI Dashboard - v1.0.6
=========================================================

* Product Page: https://www.creative-tim.com/product/soft-ui-dashboard
* Copyright 2022 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (https://www.creative-tim.com/license)
* Coded by Creative Tim

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Nucleo Icons -->
    <link href="{{ asset('css/nucleo-icons.css" rel="styleshee') }}" />
    <link href="{{ asset('css/nucleo-svg.css" rel="stylesheet') }}" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="{{ asset('css/nucleo-svg.css" rel="stylesheet') }}" />
    <!-- CSS Files -->
    <link id="pagestyle" href="{{ asset('css/soft-ui-dashboard.css?v=1.0.6') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">

    @stack('css')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])


</head>

@livewireStyles

<body class="bg-edp-fundo text-white">

    <main class="main-content mt-0">
        @yield('content')
    </main>
    <!-- -------- START FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->


    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer mt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto mb-0 mt-1 text-center">
                &copy; Copyright <strong><span>SICODE 2022 - {{ date('Y') }}
                        v{{ $version->appver }}</span></strong>.
                Centro Integrado de Projetos - Espirito Santo<br>
                Laravel Framework v{{ app()->version() }}<br>
                @if (env('APP_QA'))
                    <h3>SICODE - AMBIENTE DE QUALIDADE</h3>
                @endif
            </div>
            {{-- <div class="credits">
      <!-- All the links in the footer should remain intact. -->
      <!-- You can delete the links only if you purchased the pro version. -->
      <!-- Licensing information: https://bootstrapmade.com/license/ -->
      <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div> --}}
        </div>
    </footer><!-- End Footer -->
    <!-- -------- END FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->
    <!--   Core JS Files   -->
    <script src="{{ asset('js/core/popper.min.js') }}"></script>
    <script src="{{ asset('js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('js/plugins/smooth-scrollbar.min.js') }}"></script>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="{{ asset('js/soft-ui-dashboard.min.js?v=1.0.6') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

    @stack('script')
    @stack('scripts')

    @livewireScripts

</body>

</html>

{{-- <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
