<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SICODE Jurídico - Execução Externa</title>

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}?v={{ $version->appver }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --le-bg: radial-gradient(circle at 8% 0%, #e0ecff 0%, transparent 40%), radial-gradient(circle at 92% 8%, #d9f9f1 0%, transparent 32%), #f3f6fb;
            --le-brand: #0f2d5f;
            --le-brand-2: #1f4a85;
            --le-surface: #ffffff;
            --le-text: #0f172a;
            --le-muted: #64748b;
        }
        body {
            background: var(--le-bg);
            color: var(--le-text);
            min-height: 100vh;
        }
        .le-topbar {
            background: linear-gradient(110deg, var(--le-brand), var(--le-brand-2));
            color: #fff;
            padding: 14px 0;
            box-shadow: 0 8px 30px rgba(15, 45, 95, .25);
        }
        .le-brand {
            max-width: 1220px;
            margin: 0 auto;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .le-brand-title {
            font-size: .93rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            opacity: .9;
        }
        .le-main {
            max-width: 1240px;
            margin: 20px auto 34px;
            padding: 0 14px;
        }
        .le-shell {
            background: rgba(255,255,255,.72);
            border: 1px solid #dbe5f0;
            border-radius: 16px;
            padding: 14px;
            backdrop-filter: blur(2px);
        }
        .le-foot {
            text-align: center;
            color: var(--le-muted);
            font-size: .8rem;
            margin: 24px 0 14px;
        }
    </style>
</head>
<body>
    <header class="le-topbar">
        <div class="le-brand">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('img/EDP-Logo-white.svg') }}" alt="EDP" height="26">
                <strong class="fs-5">sicode</strong>
            </div>
            <div class="le-brand-title">Canal de Execução Externa - Jurídico</div>
        </div>
    </header>

    <main class="le-main">
        <div class="le-shell">
            @yield('content')
        </div>
    </main>

    <div class="le-foot">SICODE v{{ $version->appver }} · Acesso restrito à demanda recebida por link</div>

    @livewireScripts
</body>
</html>
