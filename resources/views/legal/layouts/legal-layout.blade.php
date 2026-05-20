<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Jurídico' }} — SICODE</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body style="background: var(--legal-bg);">

    @include('layouts.topbar')

    <div class="container-fluid py-4">
        @yield('content')
        {{ $slot ?? '' }}
    </div>

    @livewireScripts
    @stack('scripts')

    <script>
        document.addEventListener('livewire:load', function () {
            var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });

            Livewire.hook('message.processed', function () {
                var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });
            });
        });

        window.addEventListener('swal', function (e) {
            Swal.fire(e.detail);
        });
    </script>
</body>
</html>
