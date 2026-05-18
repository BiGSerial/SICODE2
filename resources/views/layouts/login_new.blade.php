<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>


<!doctype html>
<html lang="pt_br">

<head>
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

    @livewireStyles

</head>

@php
    $imagem_bg = ['vitoria1.webp', 'vitoria2.webp', 'vitoria3.webp', 'vitoria4.webp', 'vitoria6.webp'];

    $image = asset('img/bg-vit/' . $imagem_bg[mt_rand(0, 4)]);

@endphp

<style>
    body {
        background-image: url('{{ $image }}');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }
</style>

<body style="background-color: #143f47">

    @yield('content')

    <footer class="text-center mt-5 text-white" style="margin-top: 40px">
        <div class="copyright">
            &copy; Copyright <strong><span>SICODE 2022 - {{ date('Y') }}
                    v{{ $version->appver }}</span></strong>.
            <br>
            Centro Integrado de Projetos - Espirito Santo<br>
            Laravel Framework v{{ app()->version() }}<br>
            PHP v{{ phpversion() }}<br>

            @if (env('APP_QA'))
                <h3>SICODE - AMBIENTE DE QUALIDADE</h3>
            @endif

        </div>
    </footer>


    @livewireScripts
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
