@extends('layouts.fullscreen-wall')

@section('content')
    <div id="wall-v2-vue-app"
        data-endpoint="{{ isset($screenId) && $screenId ? route('api.v1.reports.production_wall_v2.screen', ['wall' => $wallId, 'screen' => $screenId]) : route('api.v1.reports.production_wall_v2', ['wall' => $wallId]) }}"
        data-item-charts-endpoint-template="{{ route('api.v1.reports.production_wall_v2.item_charts', ['wall' => $wallId, 'screen' => '__SCREEN__', 'serviceId' => '__SERVICE__']) }}"
        data-screen-id="{{ $screenId ?? '' }}"
        data-wall-id="{{ $wallId ?? '' }}">
        <div class="wall-vue-shell">Carregando painel Vue...</div>
    </div>

    @push('css')
        <style>
            .wall-vue-shell {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #061321;
                color: #fff;
                font-weight: 700;
                letter-spacing: .02em;
            }
        </style>
    @endpush

    @push('js')
        @vite('resources/js/wall-vue/main.js')
    @endpush
@endsection
