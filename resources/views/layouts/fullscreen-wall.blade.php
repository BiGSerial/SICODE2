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
    <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

    @stack('css')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @livewireStyles

    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        .wall-wakelock-controls {
            position: fixed;
            left: 10px;
            bottom: 10px;
            z-index: 9999;
            display: flex;
            gap: 8px;
        }

        .wall-wakelock-controls button {
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            color: #e5edf7;
            background: rgba(6, 19, 33, 0.85);
            cursor: pointer;
        }
    </style>
</head>

<body class="wall-layout-body">
    @yield('content')

    <div class="wall-wakelock-controls">
        <button id="ativar">Manter tela ativa</button>
        <button id="desativar">Liberar</button>
    </div>

    @livewireScripts

    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>

    <script>
        let wakeLock = null;
        let wakeLockEnabledByUser = false;

        async function ativarWakeLock() {
            wakeLockEnabledByUser = true;
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');

                    wakeLock.addEventListener('release', () => {
                        console.log('Wake lock liberado');
                        wakeLock = null;
                    });

                    console.log('Wake lock ativado');
                } else {
                    console.log('Wake Lock API não suportada neste navegador');
                }
            } catch (err) {
                console.error('Erro ao ativar wake lock:', err.name, err.message);
            }
        }

        async function desativarWakeLock() {
            wakeLockEnabledByUser = false;
            if (wakeLock) {
                await wakeLock.release();
                wakeLock = null;
            }
        }

        document.getElementById('ativar')?.addEventListener('click', ativarWakeLock);
        document.getElementById('desativar')?.addEventListener('click', desativarWakeLock);

        document.addEventListener('visibilitychange', async () => {
            if (document.visibilityState === 'visible' && wakeLock === null && wakeLockEnabledByUser) {
                await ativarWakeLock();
            }
        });
    </script>

    @stack('js')
</body>

</html>
