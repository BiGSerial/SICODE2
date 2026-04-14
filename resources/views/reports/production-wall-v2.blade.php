@extends('layouts.fullscreen-wall')

@section('content')
    <div id="wall-v2" class="wall-v2"
        data-endpoint="{{ isset($screenId) && $screenId ? route('api.v1.reports.production_wall_v2.screen', ['wall' => $wallId, 'screen' => $screenId]) : route('api.v1.reports.production_wall_v2', ['wall' => $wallId]) }}"
        data-wall-endpoint="{{ route('api.v1.reports.production_wall_v2', ['wall' => $wallId]) }}"
        data-screen-endpoint-template="{{ route('api.v1.reports.production_wall_v2.screen', ['wall' => $wallId, 'screen' => '__SCREEN__']) }}"
        data-item-charts-endpoint-template="{{ route('api.v1.reports.production_wall_v2.item_charts', ['wall' => $wallId, 'screen' => '__SCREEN__', 'serviceId' => '__SERVICE__']) }}"
        data-screen-id="{{ $screenId ?? '' }}" data-wall-id="{{ $wallId ?? '' }}">
        <div class="wall-v2__top">
            <div class="wall-v2__brand">
                <div class="wall-v2__brand-logo">
                    <img src="{{ asset('img/EDP-Logo-white.svg') }}" alt="EDP">
                    <span class="wall-v2__brand-sicode">sicode</span>
                </div>
                <h1 class="wall-v2__title">WALL PRODUÇÃO V2</h1>
            </div>
            <div class="wall-v2__meta wall-v2__meta--top" id="w2-updated">Atualizado: -</div>
            <div class="wall-v2__badges">
                <span class="wall-v2__badge">Wall: <strong id="w2-wall-name">#{{ $wallId ?? '-' }}</strong></span>
                <span class="wall-v2__badge">Tela: <strong id="w2-screen-name">-</strong></span>
                @if (!empty($screenId))
                    <span class="wall-v2__badge">Monitor fixo: <strong>#{{ $screenId }}</strong></span>
                @endif
                <span class="wall-v2__badge">Rotação tela: <strong id="w2-rotate">0</strong>s</span>
                <span class="wall-v2__badge">Rotação serviço: <strong id="w2-rotate-service">0</strong>s</span>
            </div>
        </div>

        <div class="wall-v2__grid" id="w2-grid"></div>
    </div>

    @push('css')
        <style>
            .wall-v2 {
                position: fixed;
                inset: 0;
                width: 100vw;
                height: 100vh;
                overflow: hidden;
                background: radial-gradient(circle at 8% 10%, rgba(0, 184, 148, .16), transparent 38%),
                    radial-gradient(circle at 90% 92%, rgba(9, 132, 227, .22), transparent 35%),
                    #061321;
                color: #e4edf7;
                padding: .8rem;
                display: flex;
                flex-direction: column;
                gap: .7rem;
            }

            .wall-v2__top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .8rem;
                flex-wrap: wrap;
            }

            .wall-v2__brand {
                display: flex;
                flex-direction: column;
                gap: .08rem;
            }

            .wall-v2__brand-logo {
                display: flex;
                align-items: flex-end;
                gap: .35rem;
                line-height: 1;
            }

            .wall-v2__brand-logo img {
                height: 24px;
                width: auto;
                display: block;
            }

            .wall-v2__brand-sicode {
                color: #28ff52;
                font-size: 1.6rem;
                font-weight: 700;
                text-transform: lowercase;
                letter-spacing: .02em;
            }

            .wall-v2__title {
                margin: 0;
                font-size: .88rem;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .wall-v2__meta {
                color: #a9bdd3;
                font-size: .88rem;
            }

            .wall-v2__meta--top {
                margin-left: auto;
                align-self: center;
                white-space: nowrap;
            }

            .wall-v2__badges {
                display: flex;
                gap: .45rem;
                flex-wrap: wrap;
            }

            .wall-v2__badge {
                background: rgba(255, 255, 255, .08);
                border: 1px solid rgba(255, 255, 255, .14);
                border-radius: 999px;
                font-size: .82rem;
                padding: .32rem .7rem;
            }

            .wall-v2__grid {
                flex: 1;
                min-height: 0;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(560px, 1fr));
                gap: .7rem;
            }

            .w2-panel {
                border: 1px solid rgba(255,255,255,.12);
                border-radius: 14px;
                background: rgba(15, 31, 51, .92);
                padding: .7rem;
                display: flex;
                flex-direction: column;
                min-height: 0;
                height: 100%;
                overflow: hidden;
            }

            .w2-panel__head {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                gap: .6rem;
                margin-bottom: .5rem;
            }

            .w2-panel__title {
                margin: 0;
                font-size: 1.32rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .03em;
            }

            .w2-panel__sub {
                color: #b4c8dd;
                font-size: .78rem;
            }

            .w2-cards {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: .45rem;
                margin-bottom: .5rem;
            }

            .w2-card {
                background: rgba(255,255,255,.05);
                border: 1px solid rgba(255,255,255,.12);
                border-radius: 10px;
                padding: .42rem .48rem;
            }

            .w2-card__l {
                font-size: .65rem;
                text-transform: uppercase;
                color: #9bb2ca;
            }

            .w2-card__v {
                font-size: 1.05rem;
                font-weight: 700;
            }

            .w2-content {
                flex: 1;
                min-height: 0;
                display: grid;
                grid-template-rows: 1fr 1fr;
                gap: .55rem;
            }

            .w2-charts {
                min-height: 0;
                display: grid;
                grid-template-columns: 5fr 2fr 5fr;
                gap: .55rem;
            }

            .w2-chart--donut-compact .w2-chart__wrap {
                min-height: 210px;
            }

            .w2-bottom {
                min-height: 0;
                display: grid;
                grid-template-columns: 1.35fr 1fr;
                gap: .55rem;
            }

            .w2-bottom-right {
                min-height: 0;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .55rem;
            }

            .w2-chart {
                min-height: 0;
                border: 1px solid rgba(255,255,255,.1);
                border-radius: 10px;
                padding: .45rem;
                display: flex;
                flex-direction: column;
                background: rgba(255,255,255,.03);
                overflow: hidden;
            }

            .w2-chart__t {
                font-size: .75rem;
                color: #b8cce0;
                margin-bottom: .2rem;
            }

            .w2-chart__wrap {
                flex: 1;
                position: relative;
                min-height: 0;
                overflow: hidden;
                border-radius: 8px;
            }

            .w2-chart__wrap canvas {
                width: 100% !important;
                height: 100% !important;
                max-width: 100%;
                max-height: 100%;
                display: block;
            }

            .w2-list {
                border: 1px solid rgba(255,255,255,.1);
                border-radius: 10px;
                background: rgba(255,255,255,.03);
                padding: .45rem;
                min-height: 0;
                display: flex;
                flex-direction: column;
            }

            .w2-list__t {
                font-size: .75rem;
                color: #b8cce0;
                margin-bottom: .35rem;
            }

            .w2-list__wrap {
                min-height: 0;
                flex: 1;
                overflow: auto;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .w2-list__wrap::-webkit-scrollbar {
                width: 0;
                height: 0;
                display: none;
            }

            .w2-chart__empty {
                position: absolute;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                text-align: center;
                color: #9bb2ca;
                font-size: 1rem;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
            }

            .w2-chart__loading {
                position: absolute;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                text-align: center;
                color: #dce8f5;
                background: rgba(6, 19, 33, .55);
                font-size: .86rem;
                font-weight: 700;
                letter-spacing: .03em;
                z-index: 3;
                pointer-events: none;
            }

            .w2-table {
                width: 100%;
                border-collapse: collapse;
                font-size: .78rem;
            }

            .w2-table th,
            .w2-table td {
                border-bottom: 1px solid rgba(255,255,255,.08);
                padding: .26rem .3rem;
            }

            .w2-table th {
                color: #9ec2e8;
                text-transform: uppercase;
                font-size: .65rem;
                letter-spacing: .03em;
                position: sticky;
                top: 0;
                z-index: 2;
                background: rgba(15, 31, 51, .98);
            }

            .w2-tag {
                border: 1px solid rgba(255,255,255,.2);
                border-radius: 999px;
                padding: .05rem .4rem;
                font-size: .66rem;
            }

            .w2-ads {
                flex: 1;
                min-height: 0;
                display: grid;
                grid-template-rows: auto auto 1fr;
                gap: .5rem;
            }

            .w2-ads-top {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: .45rem;
            }

            .w2-ads-mid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: .45rem;
            }

            .w2-ads-body {
                min-height: 0;
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: .55rem;
            }

            .w2-ads-left {
                min-height: 0;
                display: grid;
                grid-template-rows: 1fr 1fr;
                gap: .55rem;
            }

            .w2-ads-right {
                min-height: 0;
                display: grid;
                grid-template-rows: 1fr 1fr;
                gap: .55rem;
            }

            .w2-ads-card {
                background: rgba(255,255,255,.05);
                border: 1px solid rgba(255,255,255,.12);
                border-radius: 10px;
                padding: .42rem .48rem;
            }

            .w2-ads-card__l {
                font-size: .65rem;
                text-transform: uppercase;
                color: #9bb2ca;
            }

            .w2-ads-card__v {
                font-size: 1.05rem;
                font-weight: 700;
            }

            .w2-ads-card__k {
                margin-top: .15rem;
                font-size: .68rem;
                color: #9bb2ca;
            }

            @media (max-width: 1200px) {
                .wall-v2__grid {
                    grid-template-columns: 1fr;
                }

                .w2-cards {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                .w2-content {
                    grid-template-rows: auto auto;
                }

                .w2-bottom {
                    grid-template-columns: 1fr;
                }

                .w2-bottom-right {
                    grid-template-columns: 1fr;
                }

                .w2-ads-top {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .w2-ads-mid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .w2-ads-body {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endpush

    @push('js')
        <script>
            (function() {
                const root = document.getElementById('wall-v2');
                if (!root) return;

                const endpoint = root.dataset.endpoint;
                const wallEndpoint = root.dataset.wallEndpoint;
                const screenEndpointTemplate = root.dataset.screenEndpointTemplate || '';
                const itemChartsEndpointTemplate = root.dataset.itemChartsEndpointTemplate || '';
                const fixedScreenId = Number(root.dataset.screenId || 0);
                const wallId = String(root.dataset.wallId || '');
                const DEBUG = false;
                let payload = {
                    screens: [],
                    updated_at: '',
                    rotation_seconds: 0,
                    refresh_seconds: 0,
                };

                let currentScreenIndex = 0;
                let currentServiceIndex = 0;
                let rotateRemaining = 180;
                let serviceRotateRemaining = 180;
                let timer = null;
                let renderedScreenId = null;
                let renderedPanelKey = '';
                let tickInFlight = false;
                let lastManifestSignature = '';
                let manifestSyncRemaining = 15;
                let initialLoadingPhase = true;
                const primedScreens = new Set();
                const pendingPrimeScreens = new Set();
                const pendingManifestSync = {
                    running: false
                };
                const charts = new Map();
                const listScrollLoops = new Map();
                const componentDataCache = new Map();
                const screenDataCache = new Map();
                const componentCountdowns = new Map();
                const pendingComponentFetch = new Set();
                const pendingScreenFetch = new Set();
                const valueLabelsPluginId = 'valueLabelsPlugin';
                let wallErrorMessage = '';
                const productionComponents = [
                    'cards',
                    'queue_histogram',
                    'note_type_donut',
                    'production_open_histogram',
                    'production_daily',
                    'internal_return_donut',
                    'recent_completed',
                ];
                const fixedPanelComponents = ['ads_dashboard'];
                const CHART_BRAND_COLORS = {
                    primary: '#BF4053',
                    accent: '#FF644B',
                    danger: '#EE162D',
                    warning: '#FFDC00',
                    success: '#28FF52',
                    neutral: '#AAAAAA',
                    slate: '#C9CDD1',
                    magenta: '#E01F69',
                };
                const CHART_TEXT_COLORS = {
                    onDark: '#FFFFFF',
                    onLight: '#222222',
                    accentOnDark: CHART_BRAND_COLORS.success,
                    accentOnLight: CHART_BRAND_COLORS.primary,
                };

                function colorRgba(hex, alpha) {
                    const safeHex = String(hex || '').replace('#', '');
                    if (!/^[\da-fA-F]{6}$/.test(safeHex)) return `rgba(170,170,170,${alpha})`;
                    const r = parseInt(safeHex.slice(0, 2), 16);
                    const g = parseInt(safeHex.slice(2, 4), 16);
                    const b = parseInt(safeHex.slice(4, 6), 16);
                    const a = Number.isFinite(Number(alpha)) ? Math.max(0, Math.min(1, Number(alpha))) : 1;
                    return `rgba(${r},${g},${b},${a})`;
                }

                function parseColorToRgb(input) {
                    const value = String(input || '').trim();
                    if (!value) return null;

                    const hex = value.match(/^#([\da-fA-F]{6})$/);
                    if (hex) {
                        const h = hex[1];
                        return {
                            r: parseInt(h.slice(0, 2), 16),
                            g: parseInt(h.slice(2, 4), 16),
                            b: parseInt(h.slice(4, 6), 16),
                        };
                    }

                    const rgba = value.match(/^rgba?\(([^)]+)\)$/i);
                    if (rgba) {
                        const parts = rgba[1].split(',').map((p) => Number(p.trim()));
                        if (parts.length >= 3 && parts.every((n, idx) => idx < 3 ? Number.isFinite(n) : true)) {
                            return {
                                r: Math.max(0, Math.min(255, parts[0])),
                                g: Math.max(0, Math.min(255, parts[1])),
                                b: Math.max(0, Math.min(255, parts[2])),
                            };
                        }
                    }

                    return null;
                }

                function textColorForBackground(backgroundColor, variant = 'auto') {
                    const rgb = parseColorToRgb(backgroundColor);
                    if (!rgb) {
                        return variant === 'accent' ? CHART_TEXT_COLORS.accentOnDark : CHART_TEXT_COLORS.onDark;
                    }

                    const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
                    const isLightBackground = luminance >= 0.55;

                    if (variant === 'accent') {
                        return isLightBackground ? CHART_TEXT_COLORS.accentOnLight : CHART_TEXT_COLORS.accentOnDark;
                    }

                    return isLightBackground ? CHART_TEXT_COLORS.onLight : CHART_TEXT_COLORS.onDark;
                }

                function payloadStorageKey() {
                    const scr = fixedScreenId > 0 ? String(fixedScreenId) : 'all';
                    return `wall_v2_payload_${wallId}_${scr}`;
                }

                function registerValueLabelsPlugin() {
                    if (!window.Chart || !Chart.register) return;
                    const exists = !!Chart.registry?.plugins?.get?.(valueLabelsPluginId);
                    if (exists) return;

                    Chart.register({
                        id: valueLabelsPluginId,
                        afterDatasetsDraw(chart, args, opts) {
                            if (!opts?.enabled) return;
                            const ctx = chart.ctx;
                            const mode = String(opts.mode || '');
                            const color = opts.color || CHART_TEXT_COLORS.onDark;
                            const font = opts.font || '600 11px sans-serif';
                            const offset = Number(opts.offset || 12);

                            ctx.save();
                            ctx.fillStyle = color;
                            ctx.font = font;
                            ctx.textAlign = 'center';

                            if (mode === 'line') {
                                chart.data.datasets.forEach((dataset, dsIndex) => {
                                    const dsLabel = String(dataset?.label || '');
                                    if (/m[eé]dia/i.test(dsLabel)) return;
                                    const meta = chart.getDatasetMeta(dsIndex);
                                    if (meta.hidden || !meta.data) return;
                                    meta.data.forEach((point, idx) => {
                                        const raw = dataset?.data?.[idx];
                                        const value = Number(raw ?? 0);
                                        if (!Number.isFinite(value) || value === 0) return;
                                        const pos = point.tooltipPosition();
                                        ctx.textBaseline = 'bottom';
                                        ctx.fillText(String(value), pos.x, pos.y - offset);
                                    });
                                });
                            }

                            if (mode === 'bar') {
                                chart.data.datasets.forEach((dataset, dsIndex) => {
                                    const dsType = String(dataset?.type || chart.config?.type || '').toLowerCase();
                                    if (dsType && dsType !== 'bar') return;
                                    const meta = chart.getDatasetMeta(dsIndex);
                                    if (meta.hidden || !meta.data) return;
                                    meta.data.forEach((bar, idx) => {
                                        const raw = dataset?.data?.[idx];
                                        const value = Number(typeof raw === 'object' && raw !== null ? raw.y : raw);
                                        if (!Number.isFinite(value) || value === 0) return;
                                        const pos = bar.tooltipPosition();
                                        ctx.textBaseline = 'bottom';
                                        ctx.fillText(String(value), pos.x, pos.y - offset);
                                    });
                                });
                            }

                            if (mode === 'doughnut') {
                                const dataset = chart.data.datasets?.[0];
                                const meta = chart.getDatasetMeta(0);
                                if (dataset && meta?.data) {
                                    const total = (dataset.data || []).reduce((acc, raw) => {
                                        const value = Number(raw ?? 0);
                                        return acc + (Number.isFinite(value) ? value : 0);
                                    }, 0);

                                    meta.data.forEach((arc, idx) => {
                                        const raw = dataset?.data?.[idx];
                                        const value = Number(raw ?? 0);
                                        if (!Number.isFinite(value) || value === 0) return;
                                        const arcBg = Array.isArray(dataset?.backgroundColor)
                                            ? dataset.backgroundColor[idx]
                                            : dataset?.backgroundColor;
                                        const startAngle = Number(arc.startAngle ?? 0);
                                        const endAngle = Number(arc.endAngle ?? 0);
                                        const angle = (startAngle + endAngle) / 2;
                                        const innerRadius = Number(arc.innerRadius ?? 0);
                                        const outerRadius = Number(arc.outerRadius ?? 0);
                                        const radius = (innerRadius + outerRadius) / 2;
                                        const centerX = Number(arc.x ?? chart.width / 2);
                                        const centerY = Number(arc.y ?? chart.height / 2);

                                        const x = centerX + Math.cos(angle) * radius;
                                        const y = centerY + Math.sin(angle) * radius;

                                        const labelType = String(opts.labelType || 'percent');
                                        const ratio = total > 0 ? (value / total) * 100 : 0;
                                        const label = labelType === 'value'
                                            ? String(Math.round(value))
                                            : `${ratio.toFixed(1)}%`;
                                        ctx.fillStyle = textColorForBackground(arcBg, String(opts.textVariant || 'auto'));
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(label, x, y);
                                    });

                                    if (total > 0 && meta.data[0]) {
                                        const centerArc = meta.data[0];
                                        const centerX = Number(centerArc.x ?? chart.width / 2);
                                        const centerY = Number(centerArc.y ?? chart.height / 2);
                                        const centerLabel = String(Math.round(total));
                                        const centerFont = opts.centerFont || '700 34px sans-serif';
                                        const prevFont = ctx.font;
                                        const prevFill = ctx.fillStyle;

                                        ctx.font = centerFont;
                                        ctx.fillStyle = opts.centerColor || CHART_TEXT_COLORS.onDark;
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(centerLabel, centerX, centerY);
                                        ctx.font = prevFont;
                                        ctx.fillStyle = prevFill;
                                    }
                                }
                            }

                            ctx.restore();
                        }
                    });
                }

                function selectedServiceIds() {
                    const params = new URLSearchParams(window.location.search);
                    const raw = (params.get('services') || '').trim();
                    if (!raw) return [];
                    return raw.split(',').map((v) => v.trim()).filter(Boolean);
                }

                function applyServiceFilter(screen) {
                    const ids = selectedServiceIds();
                    if (!ids.length) return screen;

                    return {
                        ...screen,
                        items: (screen.items || []).filter((item) => ids.includes(String(item.service_id))),
                    };
                }

                function normalize(raw) {
                    return {
                        wall: raw?.wall || null,
                        updated_at: raw?.updated_at || '',
                        rotation_seconds: Number(raw?.rotation_seconds || 180),
                        refresh_seconds: Number(raw?.refresh_seconds || 60),
                        screens: Array.isArray(raw?.screens) ? raw.screens : [],
                    };
                }

                function manifestSyncIntervalSeconds() {
                    const base = Number(payload.refresh_seconds || 60);
                    return Math.max(10, Math.min(30, base));
                }

                function manifestSignature(data) {
                    const screens = Array.isArray(data?.screens) ? data.screens : [];
                    const compact = screens.map((screen) => ({
                        id: Number(screen?.id || 0),
                        type: String(screen?.screen_type || ''),
                        duration: Number(screen?.duration_seconds || 0),
                        service_duration: Number(screen?.service_rotation_seconds || 0),
                        items: (Array.isArray(screen?.items) ? screen.items : []).map((item) => ({
                            service_id: String(item?.service_id || ''),
                            previous_service_id: String(item?.previous_service_id || ''),
                        })),
                    }));
                    return JSON.stringify(compact);
                }

                function buildScreenEndpoint(screenId) {
                    const sid = encodeURIComponent(String(screenId));
                    if (!screenEndpointTemplate.includes('__SCREEN__')) {
                        throw new Error('Template de rota de tela não configurado');
                    }
                    return screenEndpointTemplate.replace('__SCREEN__', sid);
                }

                function buildItemChartsEndpoint(screenId, serviceId, component = null) {
                    const sid = encodeURIComponent(String(screenId));
                    const svc = encodeURIComponent(String(serviceId));
                    let url = '';

                    if (!itemChartsEndpointTemplate.includes('__SCREEN__') || !itemChartsEndpointTemplate.includes('__SERVICE__')) {
                        throw new Error('Template de rota de item/charts não configurado');
                    }
                    url = itemChartsEndpointTemplate
                        .replace('__SCREEN__', sid)
                        .replace('__SERVICE__', svc);

                    if (component) {
                        url += (url.includes('?') ? '&' : '?') + `component=${encodeURIComponent(component)}`;
                    }

                    return url;
                }

                async function fetchPayload(preservePosition = true) {
                    if (!endpoint) return;
                    try {
                        const manifestUrl = endpoint.includes('?') ? `${endpoint}&manifest=1` : `${endpoint}?manifest=1`;
                        const response = await fetch(manifestUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            cache: 'no-store',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            if (response.status === 404) {
                                wallErrorMessage = 'NAO EXISTE WALL CONFIGURADO';
                            }
                            sessionStorage.removeItem(payloadStorageKey());
                            return;
                        }

                        wallErrorMessage = '';
                        const previousScreen = currentScreen();
                        const previousDisplay = currentDisplayScreen();
                        const previousItem = previousDisplay?.items?.[currentServiceIndex] || null;
                        const previousScreenId = Number(previousScreen?.id || 0);
                        const previousServiceId = String(previousItem?.service_id || '');

                        const raw = await response.json();
                        const nextPayload = normalize(raw);
                        const nextSignature = manifestSignature(nextPayload);
                        const changed = nextSignature !== lastManifestSignature;
                        payload = nextPayload;
                        lastManifestSignature = nextSignature;
                        payload.screens = (payload.screens || []).map((screen) => ({
                            ...screen,
                            // Manifest traz estrutura; dados são carregados por item/charts.
                            loaded: false,
                        }));
                        try {
                            sessionStorage.setItem(payloadStorageKey(), JSON.stringify(raw));
                        } catch (e) {}
                        if (preservePosition && previousScreenId > 0) {
                            const nextScreenIndex = payload.screens.findIndex((s) => Number(s?.id || 0) === previousScreenId);
                            currentScreenIndex = nextScreenIndex >= 0 ? nextScreenIndex : 0;
                            const active = payload.screens[currentScreenIndex];
                            const activeItems = Array.isArray(active?.items) ? active.items : [];
                            if (previousServiceId) {
                                const nextServiceIndex = activeItems.findIndex((it) => String(it?.service_id || '') === previousServiceId);
                                currentServiceIndex = nextServiceIndex >= 0 ? nextServiceIndex : 0;
                            } else {
                                currentServiceIndex = 0;
                            }
                        } else if (currentScreenIndex >= payload.screens.length) {
                            currentScreenIndex = 0;
                            currentServiceIndex = 0;
                        }

                        if (changed) {
                            rotateRemaining = Math.min(Math.max(0, rotateRemaining || 0), screenDuration(currentScreen()));
                            serviceRotateRemaining = Math.min(Math.max(0, serviceRotateRemaining || 0), serviceDuration(currentScreen()));
                            render();
                        }
                    } catch (e) {
                        wallErrorMessage = 'NAO EXISTE WALL CONFIGURADO';
                    }
                }

                async function syncManifestIfNeeded() {
                    if (pendingManifestSync.running) return;
                    pendingManifestSync.running = true;
                    try {
                        await fetchPayload(true);
                    } finally {
                        pendingManifestSync.running = false;
                        manifestSyncRemaining = manifestSyncIntervalSeconds();
                    }
                }

                function currentScreen() {
                    return payload.screens[currentScreenIndex] || null;
                }

                function currentDisplayScreen() {
                    const screen = currentScreen();
                    return screen ? applyServiceFilter(screen) : null;
                }

                async function fetchScreenPayload(screenId, force = false) {
                    const key = String(screenId);
                    if (!force && screenDataCache.has(key)) {
                        return screenDataCache.get(key);
                    }

                    const url = buildScreenEndpoint(screenId);
                    const res = await fetch(url, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!res.ok) {
                        return null;
                    }

                    const data = await res.json();
                    screenDataCache.set(key, data || null);
                    return data || null;
                }

                function applyScreenPayload(screenId, remote) {
                    const remoteScreen = Array.isArray(remote?.screens) ? (remote.screens[0] || null) : null;
                    if (!remoteScreen) return false;
                    const idx = payload.screens.findIndex((s) => Number(s.id) === Number(screenId));
                    if (idx < 0) return false;

                    payload.screens[idx] = {
                        ...remoteScreen,
                        loaded: true,
                    };
                    return true;
                }

                async function ensureCurrentScreenLoaded(force = false) {
                    const screen = currentScreen();
                    if (!screen) return;
                    const screenId = Number(screen.id || 0);
                    if (!screenId) return;
                    if (!force && screen.loaded) return;
                    if (pendingScreenFetch.has(screenId)) return;

                    pendingScreenFetch.add(screenId);
                    try {
                        const remote = await fetchScreenPayload(screenId, force);
                        if (!remote) return;
                        if (applyScreenPayload(screenId, remote)) {
                            payload.updated_at = remote.updated_at || payload.updated_at || '';
                            render();
                        }
                    } finally {
                        pendingScreenFetch.delete(screenId);
                    }
                }

                function setCounters() {
                    document.getElementById('w2-rotate').textContent = String(rotateRemaining);
                    document.getElementById('w2-rotate-service').textContent = String(serviceRotateRemaining);
                    document.getElementById('w2-updated').textContent = `Atualizado: ${payload.updated_at || '-'}`;
                    const wallName = payload?.wall?.name || `#${root.dataset.wallId || '-'}`;
                    const wallNode = document.getElementById('w2-wall-name');
                    if (wallNode) wallNode.textContent = wallName;
                    writeComponentCounters();
                }

                function panelKey(screenId, serviceId) {
                    return `s${screenId}_svc${serviceId}`;
                }

                function screenDuration(screen) {
                    return Number(screen?.duration_seconds || payload.rotation_seconds || 180);
                }

                function serviceDuration(screen) {
                    return Number(screen?.service_rotation_seconds || 180);
                }

                function canRotateScreens() {
                    return (payload.screens?.length || 0) > 1;
                }

                function canRotateServices(screen) {
                    if (!screen) return false;
                    if (String(screen?.screen_type || '') !== 'production_services') return false;
                    const items = screen.items || [];
                    return items.length > 1;
                }

                function ensureChart(chartId, canvas, config) {
                    if (!charts.has(chartId)) {
                        charts.set(chartId, new Chart(canvas.getContext('2d'), config));
                    }
                    return charts.get(chartId);
                }

                function clearPanelCharts(panel) {
                    stopListAutoScroll(panel);
                    [
                        'ads_line_',
                        'ads_bar_',
                        'ads_queue_',
                        'ads_reuse_',
                        'q_',
                        'nd_',
                        'p_',
                        'f_',
                        'd_',
                    ].forEach((prefix) => charts.delete(`${prefix}${panel}`));
                }

                function stopListAutoScroll(panel) {
                    const state = listScrollLoops.get(panel);
                    if (!state) return;
                    if (state.timer) clearTimeout(state.timer);
                    if (state.raf) cancelAnimationFrame(state.raf);
                    listScrollLoops.delete(panel);
                }

                function startListAutoScroll(panel, node) {
                    if (!node) return;
                    stopListAutoScroll(panel);

                    const state = {
                        timer: null,
                        raf: null,
                    };
                    listScrollLoops.set(panel, state);

                    const TOP_HOLD_MS = 5000;
                    const BOTTOM_HOLD_MS = 5000;

                    const schedule = (fn, ms) => {
                        if (!listScrollLoops.has(panel)) return;
                        state.timer = setTimeout(fn, ms);
                    };

                    const cycle = () => {
                        if (!listScrollLoops.has(panel)) return;
                        const maxScroll = Math.max(0, node.scrollHeight - node.clientHeight);
                        if (maxScroll <= 2) return;

                        node.scrollTop = 0;
                        schedule(() => {
                            const startTop = node.scrollTop;
                            const endTop = Math.max(0, node.scrollHeight - node.clientHeight);
                            const distance = Math.max(0, endTop - startTop);
                            if (distance <= 2) return;

                            const duration = Math.max(7000, Math.min(26000, distance * 14));
                            const startTs = performance.now();

                            const step = (now) => {
                                if (!listScrollLoops.has(panel)) return;
                                const elapsed = now - startTs;
                                const progress = Math.max(0, Math.min(1, elapsed / duration));
                                node.scrollTop = startTop + (distance * progress);
                                if (progress < 1) {
                                    state.raf = requestAnimationFrame(step);
                                    return;
                                }
                                schedule(() => {
                                    if (!listScrollLoops.has(panel)) return;
                                    node.scrollTop = 0;
                                    cycle();
                                }, BOTTOM_HOLD_MS);
                            };

                            state.raf = requestAnimationFrame(step);
                        }, TOP_HOLD_MS);
                    };

                    cycle();
                }

                function updateChartData(chart, labels, datasets, stacked = false) {
                    const safeLabels = (Array.isArray(labels) ? labels : []).map((v) => String(v ?? ''));
                    const safeDatasets = (Array.isArray(datasets) ? datasets : []).map((ds) => {
                        const normalizedData = normalizeSeriesData(ds?.data, safeLabels);
                        return {
                            ...ds,
                            data: normalizedData,
                        };
                    });

                    chart.data.labels = safeLabels;
                    chart.data.datasets = safeDatasets;
                    chart.update();
                }

                function updateChartDataAsync(chart, labels, datasets, stacked = false) {
                    setTimeout(() => {
                        try {
                            updateChartData(chart, labels, datasets, stacked);
                        } catch (error) {
                            console.error('wall-v2 chart update error', error);
                        }
                    }, 0);
                }

                function normalizeNumber(value) {
                    const n = Number(value);
                    return Number.isFinite(n) ? n : 0;
                }

                function sumValues(values) {
                    return (values || []).reduce((acc, v) => acc + normalizeNumber(v), 0);
                }

                function setPointsBadge(panel, component, points, sum) {
                    const node = document.getElementById(`pts_${component}_${panel}`);
                    if (node) node.textContent = `pts:${Number(points || 0)} sum:${Number(sum || 0)}`;
                }

                function normalizeSeriesData(rawData, labels) {
                    const safeLabels = (Array.isArray(labels) ? labels : []).map((v) => String(v ?? ''));
                    const data = Array.isArray(rawData) ? rawData : [];

                    if (data.length && typeof data[0] === 'object' && data[0] !== null && ('y' in data[0])) {
                        return data.map((point, index) => ({
                            x: point.x ?? safeLabels[index] ?? '',
                            y: normalizeNumber(point.y),
                        }));
                    }

                    return safeLabels.map((_, index) => normalizeNumber(data[index] ?? 0));
                }

                function isFixedPanel(screen, item) {
                    if (String(screen?.screen_type || '') === 'fixed_chart') return true;
                    if (item?.ads_chart) return true;
                    const serviceId = String(item?.service_id || '');
                    return serviceId.startsWith('fixed-') || serviceId === 'ads-dashboard';
                }

                function activeComponentsForItem(screen, item) {
                    return isFixedPanel(screen, item) ? fixedPanelComponents : productionComponents;
                }

                function fixedDashboardDefaults(item) {
                    const serviceId = String(item?.service_id || '');
                    if (serviceId === 'fixed-project_review_dashboard') {
                        return {
                            subtitle: `Tela fixa: ${item?.service_name || 'ANALISE DE PROJETO'}`,
                            lineTitle: 'Histograma da fila sem análise associada (dias na pilha)',
                            barTitle: 'Entradas x Respostas por dia',
                            queueTitle: 'Composição da fila pendente',
                            reuseTitle: 'Composição do valor revisado',
                        };
                    }

                    return {
                        subtitle: `Tela fixa: ${item?.service_name || 'ADS - Dashboard'}`,
                        lineTitle: 'Acumulado e Atrasadas (linha) - visão diária',
                        barTitle: 'Entradas x Saídas (barras) - visão diária',
                        queueTitle: 'Fila atual (status pendentes)',
                        reuseTitle: 'Economia por reaproveitamento de ADS',
                    };
                }

                function counterNodeId(panel, component) {
                    return `ctr_${component}_${panel}`;
                }

                function setNodeLoading(nodeId, loading) {
                    const node = document.getElementById(nodeId);
                    if (!node) return;
                    node.style.display = loading ? 'flex' : 'none';
                }

                function setPanelComponentLoading(panel, component, loading) {
                    if (!panel || !component) return;
                    if (component === 'ads_dashboard') {
                        setNodeLoading(`loading_ads_line_${panel}`, loading);
                        setNodeLoading(`loading_ads_bar_${panel}`, loading);
                        setNodeLoading(`loading_ads_queue_${panel}`, loading);
                        setNodeLoading(`loading_ads_reuse_${panel}`, loading);
                        return;
                    }
                    if (component === 'queue_histogram') setNodeLoading(`loading_q_${panel}`, loading);
                    if (component === 'note_type_donut') setNodeLoading(`loading_nd_${panel}`, loading);
                    if (component === 'production_open_histogram') setNodeLoading(`loading_p_${panel}`, loading);
                    if (component === 'production_daily') setNodeLoading(`loading_f_${panel}`, loading);
                    if (component === 'internal_return_donut') setNodeLoading(`loading_d_${panel}`, loading);
                }

                function setActiveComponentsLoading(loading, onlyComponent = null) {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    if (loading && !initialLoadingPhase) return;
                    const components = activeComponentsForItem(state.screen, state.item);
                    if (onlyComponent) {
                        setPanelComponentLoading(state.panel, onlyComponent, loading);
                        return;
                    }
                    components.forEach((component) => setPanelComponentLoading(state.panel, component, loading));
                }

                function currentPanelAndItem() {
                    const screen = currentDisplayScreen();
                    const item = screen?.items?.[currentServiceIndex];
                    if (!screen || !item) return null;
                    return {
                        screen,
                        item,
                        panel: panelKey(screen.id, item.service_id),
                    };
                }

                function resetComponentCountdowns(item) {
                    const screen = currentDisplayScreen();
                    componentCountdowns.clear();
                    const seconds = Number(payload.refresh_seconds || 60);
                    activeComponentsForItem(screen, item).forEach((component) => {
                        componentCountdowns.set(component, seconds);
                    });
                    writeComponentCounters();
                }

                function writeComponentCounters() {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    const components = activeComponentsForItem(state.screen, state.item);
                    components.forEach((component) => {
                        const seconds = Number(componentCountdowns.get(component) ?? payload.refresh_seconds ?? 60);
                        const node = document.getElementById(counterNodeId(state.panel, component));
                        if (node) node.textContent = `${seconds}s`;
                    });
                    const head = document.getElementById(`refresh_${state.panel}`);
                    if (head) head.textContent = 'Refresh individual por card';
                }

                async function fetchItemComponent(screenId, serviceId, component, force = false) {
                    const key = `${screenId}:${serviceId}:${component}`;
                    if (!force && componentDataCache.has(key)) {
                        return componentDataCache.get(key);
                    }
                    try {
                        const screenCandidates = Array.from(new Set([
                            Number(screenId || 0),
                        ].filter((n) => Number.isFinite(n) && n > 0)));

                        for (const sid of screenCandidates) {
                            const url = buildItemChartsEndpoint(sid, serviceId, component);
                            if (DEBUG) {
                                console.log('wall-v2 fetch component', {
                                    url,
                                    wallId,
                                    screenId: sid,
                                    serviceId,
                                    component,
                                });
                            }
                            const res = await fetch(url, {
                                method: 'GET',
                                credentials: 'same-origin',
                                cache: 'no-store',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            if (!res.ok) {
                                if (DEBUG) {
                                    console.warn('wall-v2 component fetch failed', {
                                        url,
                                        status: res.status,
                                        statusText: res.statusText,
                                    });
                                }
                                continue;
                            }
                            const data = await res.json();
                            if (DEBUG && component === 'queue_histogram') {
                                console.log('wall-v2 queue_histogram payload', {
                                    url,
                                    screenId: sid,
                                    serviceId,
                                    component,
                                    response: data,
                                });
                            }
                            componentDataCache.set(key, data || null);
                            return data || null;
                        }

                        return null;
                    } catch (e) {
                        console.error('wall-v2 fetchItemComponent exception', e);
                        return null;
                    }
                }

                async function fetchItemFull(screenId, serviceId, force = false) {
                    const key = `${screenId}:${serviceId}:__full__`;
                    if (!force && componentDataCache.has(key)) {
                        return componentDataCache.get(key);
                    }
                    try {
                        const screenCandidates = Array.from(new Set([
                            Number(screenId || 0),
                        ].filter((n) => Number.isFinite(n) && n > 0)));

                        for (const sid of screenCandidates) {
                            const url = buildItemChartsEndpoint(sid, serviceId, null);
                            if (DEBUG) {
                                console.log('wall-v2 fetch full', {
                                    url,
                                    wallId,
                                    screenId: sid,
                                    serviceId,
                                });
                            }
                            const res = await fetch(url, {
                                method: 'GET',
                                credentials: 'same-origin',
                                cache: 'no-store',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            if (!res.ok) {
                                if (DEBUG) {
                                    console.warn('wall-v2 full fetch failed', {
                                        url,
                                        status: res.status,
                                        statusText: res.statusText,
                                    });
                                }
                                continue;
                            }
                            const data = await res.json();
                            componentDataCache.set(key, data || null);
                            return data || null;
                        }

                        return null;
                    } catch (e) {
                        console.error('wall-v2 fetchItemFull exception', e);
                        return null;
                    }
                }

                function hasMeaningfulData(component, data) {
                    if (data === null || typeof data === 'undefined') return false;
                    if (component === 'cards') return typeof data === 'object' && data !== null;
                    if (component === 'week' || component === 'previous_service_name') return true;
                    if (component === 'recent_completed') return Array.isArray(data);
                    if (component === 'ads_dashboard') return typeof data === 'object' && data !== null;
                    if (typeof data === 'object' && data !== null) {
                        const values = Array.isArray(data.values) ? data.values : [];
                        const labels = Array.isArray(data.labels) ? data.labels : [];
                        const assigned = Array.isArray(data.assigned) ? data.assigned : [];
                        const delivered = Array.isArray(data.delivered) ? data.delivered : [];
                        return labels.length > 0 || values.length > 0 || assigned.length > 0 || delivered.length > 0;
                    }
                    return true;
                }

                function objectNumericSum(obj, keys) {
                    if (!obj || typeof obj !== 'object') return 0;
                    return keys.reduce((acc, key) => acc + normalizeNumber(obj[key]), 0);
                }

                function histogramSum(data) {
                    const values = Array.isArray(data?.values) ? data.values : [];
                    return sumValues(values);
                }

                function flowSum(data) {
                    const assigned = Array.isArray(data?.assigned) ? data.assigned : [];
                    const delivered = Array.isArray(data?.delivered) ? data.delivered : [];
                    return sumValues(assigned) + sumValues(delivered);
                }

                function applyComponentOnItem(screenId, serviceId, component, data) {
                    const screen = payload.screens.find((s) => Number(s.id) === Number(screenId));
                    if (!screen || !Array.isArray(screen.items)) return false;
                    const idx = screen.items.findIndex((it) => String(it.service_id) === String(serviceId));
                    if (idx < 0) return false;
                    const current = screen.items[idx] || {};
                    const next = {
                        ...current
                    };
                    if (component === 'cards' && hasMeaningfulData(component, data)) next.cards = data || current.cards || {};
                    if (component === 'week' && hasMeaningfulData(component, data)) next.week = data || current.week || {};
                    if (component === 'previous_service_name' && hasMeaningfulData(component, data)) next.previous_service_name = data ?? current.previous_service_name;
                    if (component === 'queue_histogram' && hasMeaningfulData(component, data)) next.queue_histogram = data || current.queue_histogram || {};
                    if (component === 'note_type_donut' && hasMeaningfulData(component, data)) next.note_type_donut = data || current.note_type_donut || {};
                    if (component === 'production_open_histogram' && hasMeaningfulData(component, data)) next.production_open_histogram = data || current.production_open_histogram || {};
                    if (component === 'production_daily' && hasMeaningfulData(component, data)) next.production_daily = data || current.production_daily || {};
                    if (component === 'internal_return_donut' && hasMeaningfulData(component, data)) next.internal_return_donut = data || current.internal_return_donut || {};
                    if (component === 'recent_completed' && hasMeaningfulData(component, data)) next.recent_completed = Array.isArray(data) ? data : (current.recent_completed || []);
                    if (component === 'ads_dashboard' && hasMeaningfulData(component, data)) next.ads_dashboard = data || current.ads_dashboard || {};
                    screen.items[idx] = next;
                    return true;
                }

                function applyFullItemOnPayload(screenId, serviceId, remote) {
                    if (!remote) return false;
                    const screen = payload.screens.find((s) => Number(s.id) === Number(screenId));
                    if (!screen || !Array.isArray(screen.items)) return false;
                    const idx = screen.items.findIndex((it) => String(it.service_id) === String(serviceId));
                    if (idx < 0) return false;
                    const current = screen.items[idx] || {};
                    const remoteCards = remote.cards || {};
                    const currentCards = current.cards || {};
                    const remoteCardsSum = objectNumericSum(remoteCards, ['queue_total', 'queue_ov', 'queue_notes', 'returned', 'previous_done', 'next_entry']);
                    const currentCardsSum = objectNumericSum(currentCards, ['queue_total', 'queue_ov', 'queue_notes', 'returned', 'previous_done', 'next_entry']);

                    const remoteQueue = remote.charts?.queue_histogram || {};
                    const currentQueue = current.queue_histogram || {};
                    const remoteNoteTypeDonut = remote.charts?.note_type_donut || {};
                    const currentNoteTypeDonut = current.note_type_donut || {};
                    const remoteOpen = remote.charts?.production_open_histogram || {};
                    const currentOpen = current.production_open_histogram || {};
                    const remoteFlow = remote.charts?.production_daily || {};
                    const currentFlow = current.production_daily || {};
                    const remoteDonut = remote.charts?.internal_return_donut || {};
                    const currentDonut = current.internal_return_donut || {};

                    screen.items[idx] = {
                        ...current,
                        cards: (remoteCardsSum > 0 || currentCardsSum === 0) ? remoteCards : currentCards,
                        week: remote.week || current.week || {},
                        previous_service_name: remote.previous_service_name ?? current.previous_service_name,
                        queue_histogram: (histogramSum(remoteQueue) > 0 || histogramSum(currentQueue) === 0) ? remoteQueue : currentQueue,
                        note_type_donut: (histogramSum(remoteNoteTypeDonut) > 0 || histogramSum(currentNoteTypeDonut) === 0) ? remoteNoteTypeDonut : currentNoteTypeDonut,
                        production_open_histogram: (histogramSum(remoteOpen) > 0 || histogramSum(currentOpen) === 0) ? remoteOpen : currentOpen,
                        production_daily: (flowSum(remoteFlow) > 0 || flowSum(currentFlow) === 0) ? remoteFlow : currentFlow,
                        internal_return_donut: (histogramSum(remoteDonut) > 0 || histogramSum(currentDonut) === 0) ? remoteDonut : currentDonut,
                        recent_completed: Array.isArray(remote.charts?.recent_completed) ? remote.charts.recent_completed : (current.recent_completed || []),
                        ads_dashboard: remote.charts?.ads_dashboard || current.ads_dashboard || {},
                    };
                    return true;
                }

                async function refreshActiveItemFromFull(force = true) {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    setActiveComponentsLoading(true);
                    const remote = await fetchItemFull(state.screen.id, state.item.service_id, force);
                    if (!remote) {
                        render();
                        setActiveComponentsLoading(false);
                        return;
                    }
                    if (applyFullItemOnPayload(state.screen.id, state.item.service_id, remote)) {
                        payload.updated_at = remote.updated_at || payload.updated_at || '';
                        render();
                    }
                    setActiveComponentsLoading(false);
                }

                async function refreshSingleComponent(component, force = true) {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    const lockKey = `${state.screen.id}:${state.item.service_id}:${component}`;
                    if (pendingComponentFetch.has(lockKey)) return;
                    pendingComponentFetch.add(lockKey);
                    setActiveComponentsLoading(true, component);
                    try {
                        const remote = await fetchItemComponent(state.screen.id, state.item.service_id, component, force);
                        if (!remote) return;
                        const changed = applyComponentOnItem(state.screen.id, state.item.service_id, component, remote.data);
                        if (changed) {
                            payload.updated_at = remote.updated_at || payload.updated_at || '';
                            render(component);
                        }
                        componentCountdowns.set(component, Number(payload.refresh_seconds || 60));
                    } finally {
                        setActiveComponentsLoading(false, component);
                        pendingComponentFetch.delete(lockKey);
                    }
                }

                async function refreshAllComponentsNow(force = true) {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    const components = activeComponentsForItem(state.screen, state.item);
                    if (initialLoadingPhase) {
                        components.forEach((component) => setPanelComponentLoading(state.panel, component, true));
                    }
                    try {
                        const requests = components.map((component) =>
                            fetchItemComponent(state.screen.id, state.item.service_id, component, force)
                                .then((remote) => ({ component, remote }))
                                .catch(() => ({ component, remote: null }))
                        );
                        const results = await Promise.all(requests);
                        let changed = false;
                        results.forEach(({ component, remote }) => {
                            if (!remote) return;
                            if (applyComponentOnItem(state.screen.id, state.item.service_id, component, remote.data)) {
                                changed = true;
                            }
                            payload.updated_at = remote.updated_at || payload.updated_at || '';
                            componentCountdowns.set(component, Number(payload.refresh_seconds || 60));
                        });
                        if (changed) {
                            render('all');
                        }
                    } finally {
                        components.forEach((component) => setPanelComponentLoading(state.panel, component, false));
                        if (initialLoadingPhase) {
                            initialLoadingPhase = false;
                        }
                    }
                }

                async function primeScreenOnce(screen) {
                    const screenId = Number(screen?.id || 0);
                    if (!screenId) return;
                    if (primedScreens.has(screenId)) return;
                    if (pendingPrimeScreens.has(screenId)) return;

                    pendingPrimeScreens.add(screenId);
                    try {
                        const items = Array.isArray(screen?.items) ? screen.items : [];
                        if (!items.length) {
                            primedScreens.add(screenId);
                            return;
                        }

                        let changed = false;
                        let lastUpdatedAt = '';

                        const requests = items.map(async (item) => {
                            const serviceId = String(item?.service_id || '');
                            if (!serviceId) return;
                            const remote = await fetchItemFull(screenId, serviceId, true);
                            if (!remote) return;
                            if (applyFullItemOnPayload(screenId, serviceId, remote)) {
                                changed = true;
                            }
                            if (remote.updated_at) {
                                lastUpdatedAt = remote.updated_at;
                            }
                        });

                        await Promise.all(requests);
                        primedScreens.add(screenId);

                        if (changed) {
                            payload.updated_at = lastUpdatedAt || payload.updated_at || '';
                            render('all');
                        }
                    } finally {
                        pendingPrimeScreens.delete(screenId);
                    }
                }

                function render(targetComponent = null) {
                    try {
                        const grid = document.getElementById('w2-grid');
                        const screenName = document.getElementById('w2-screen-name');
                        const baseScreen = currentScreen();
                        const screen = baseScreen ? applyServiceFilter(baseScreen) : null;

                        if (wallErrorMessage) {
                            grid.innerHTML = `
                                <div class="w2-panel" style="display:flex;align-items:center;justify-content:center;">
                                    <div style="font-size:1.2rem;font-weight:700;color:#fda4af;">
                                        ${wallErrorMessage}
                                    </div>
                                </div>
                            `;
                            renderedScreenId = null;
                            screenName.textContent = '-';
                            return;
                        }

                        if (!screen) {
                            grid.querySelectorAll('.w2-panel[data-key]').forEach((panel) => {
                                panel.style.display = 'none';
                            });
                            renderedScreenId = null;
                            renderedPanelKey = '';
                            componentCountdowns.clear();
                            screenName.textContent = '-';
                            return;
                        }

                    screenName.textContent = screen.name || `Tela ${currentScreenIndex + 1}`;
                    const items = screen.items || [];
                    if (!items.length) {
                        grid.querySelectorAll('.w2-panel[data-key]').forEach((panel) => {
                            panel.style.display = 'none';
                        });
                        renderedPanelKey = '';
                        componentCountdowns.clear();
                        return;
                    }

                    if (currentServiceIndex >= items.length) {
                        currentServiceIndex = 0;
                    }

                    const activeItem = items[currentServiceIndex];
                    const renderTarget = String(targetComponent || '').trim();
                    const shouldUpdateComponent = (component) => {
                        if (!renderTarget || renderTarget === 'all') return true;
                        return renderTarget === String(component);
                    };
                    const currentPanel = panelKey(screen.id, activeItem.service_id);
                    const enteringNewScreen = renderedScreenId !== screen.id;
                    if (renderedPanelKey !== currentPanel) {
                        renderedPanelKey = currentPanel;
                        resetComponentCountdowns(activeItem);
                        setActiveComponentsLoading(true);
                        setTimeout(() => {
                            refreshAllComponentsNow(true);
                        }, 0);
                    }
                    if (enteringNewScreen) {
                        setTimeout(() => {
                            primeScreenOnce(screen);
                        }, 0);
                    }
                    grid.style.gridTemplateColumns = '1fr';

                    grid.querySelectorAll('.w2-panel[data-key]').forEach((panelNode) => {
                        panelNode.style.display = 'none';
                    });

                    [activeItem].forEach((item) => {
                        const key = panelKey(screen.id, item.service_id);
                        const isFixed = isFixedPanel(screen, item);
                        const fixedDefaults = fixedDashboardDefaults(item);
                        let panel = grid.querySelector(`[data-key="${key}"]`);
                        if (!panel) {
                            panel = document.createElement('div');
                            panel.className = 'w2-panel';
                            panel.dataset.key = key;
                            panel.innerHTML = isFixed ? `
                                <div class="w2-panel__head">
                                    <h3 class="w2-panel__title" id="t_${key}">-</h3>
                                    <div class="w2-panel__sub" id="refresh_${key}">Refresh individual por card</div>
                                </div>
                                <div class="w2-panel__sub" id="s_${key}" style="margin-bottom:.4rem;">Tela fixa</div>
                                <div class="w2-ads">
                                    <div class="w2-ads-top" id="ads_top_${key}"></div>
                                    <div class="w2-ads-mid" id="ads_mid_${key}"></div>
                                    <div class="w2-ads-body">
                                        <div class="w2-ads-left">
                                            <div class="w2-chart">
                                            <div class="w2-chart__t"><span id="ads_line_title_${key}">${fixedDefaults.lineTitle}</span> <span id="pts_ads_dashboard_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_ads_dashboard_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_line_${key}"></canvas><div class="w2-chart__loading" id="loading_ads_line_${key}">Carregando dados...</div></div>
                                            </div>
                                            <div class="w2-chart">
                                                <div class="w2-chart__t"><span id="ads_bar_title_${key}">${fixedDefaults.barTitle}</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_bar_${key}"></canvas><div class="w2-chart__loading" id="loading_ads_bar_${key}">Carregando dados...</div></div>
                                            </div>
                                        </div>
                                        <div class="w2-ads-right">
                                            <div class="w2-chart">
                                                <div class="w2-chart__t"><span id="ads_queue_title_${key}">${fixedDefaults.queueTitle}</span> <span id="ads_queue_total_${key}" style="float:right;color:#9bb2ca">Total: 0</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_queue_${key}"></canvas><div class="w2-chart__loading" id="loading_ads_queue_${key}">Carregando dados...</div></div>
                                            </div>
                                            <div class="w2-chart">
                                                <div class="w2-chart__t"><span id="ads_reuse_title_${key}">${fixedDefaults.reuseTitle}</span> <span id="ads_reuse_total_${key}" style="float:right;color:#9bb2ca">Total: 0</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_reuse_${key}"></canvas><div class="w2-chart__loading" id="loading_ads_reuse_${key}">Carregando dados...</div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ` : `
                                <div class="w2-panel__head">
                                    <h3 class="w2-panel__title" id="t_${key}">-</h3>
                                    <div class="w2-panel__sub" id="refresh_${key}">Refresh individual por card</div>
                                </div>
                                <div class="w2-panel__sub" id="s_${key}" style="margin-bottom:.4rem;">Anterior: -</div>
                                <div class="w2-cards">
                                    <div class="w2-card"><div class="w2-card__l">Total Pilha <span id="ctr_cards_${key}" style="float:right;color:#9bb2ca">--</span></div><div class="w2-card__v" id="c_queue_total_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">Pilha OV</div><div class="w2-card__v" id="c_queue_ov_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">Pilha Notas</div><div class="w2-card__v" id="c_queue_notes_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l" id="l_prev_done_${key}">Finalizados (semana)</div><div class="w2-card__v" id="c_prev_done_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">Próxima Entrada</div><div class="w2-card__v" id="c_next_entry_${key}">0</div></div>
                                </div>
                                <div class="w2-content">
                                    <div class="w2-charts">
                                        <div class="w2-chart">
                                            <div class="w2-chart__t">Pilha da atividade (OV, última semana) <span id="pts_queue_histogram_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_queue_histogram_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="q_${key}"></canvas><div class="w2-chart__loading" id="loading_q_${key}">Carregando dados...</div></div>
                                        </div>
                                        <div class="w2-chart w2-chart--donut-compact">
                                            <div class="w2-chart__t">Notas x Produção Associada <span id="note_type_total_${key}" style="float:right;color:#9bb2ca">Total: 0</span><span id="pts_note_type_donut_${key}" style="float:right;color:#9bb2ca;margin-right:.5rem;">pts:0 sum:0</span><span id="ctr_note_type_donut_${key}" style="float:right;color:#9bb2ca;margin-right:.5rem;">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="nd_${key}"></canvas><div class="w2-chart__loading" id="loading_nd_${key}">Carregando dados...</div></div>
                                        </div>
                                        <div class="w2-chart">
                                            <div class="w2-chart__t">Pilha de produção atribuída sem finalizar <span id="pts_production_open_histogram_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_production_open_histogram_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="p_${key}"></canvas><div class="w2-chart__loading" id="loading_p_${key}">Carregando dados...</div></div>
                                        </div>
                                    </div>
                                    <div class="w2-bottom">
                                        <div class="w2-chart">
                                            <div class="w2-chart__t">Produção dia a dia (atribuído x entregue) <span id="pts_production_daily_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_production_daily_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="f_${key}"></canvas><div class="w2-chart__loading" id="loading_f_${key}">Carregando dados...</div></div>
                                        </div>
                                        <div class="w2-bottom-right">
                                            <div class="w2-chart">
                                                <div class="w2-chart__t">Retorno interno por tipo <span id="pts_internal_return_donut_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_internal_return_donut_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                                <div class="w2-chart__wrap"><canvas id="d_${key}"></canvas><div class="w2-chart__empty" id="d_empty_${key}">SEM DADOS</div><div class="w2-chart__loading" id="loading_d_${key}">Carregando dados...</div></div>
                                            </div>
                                            <div class="w2-list">
                                                <div class="w2-list__t">Últimas produções entregues (semana atual) <span id="pts_recent_completed_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_recent_completed_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                                <div class="w2-list__wrap">
                                                    <table class="w2-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Nota</th>
                                                                <th>Usuário</th>
                                                                <th>Empresa</th>
                                                                <th>Tipo</th>
                                                                <th>Entrega</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="list_${key}"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            grid.appendChild(panel);
                        }
                        panel.style.display = 'flex';

                        const titleNode = document.getElementById(`t_${key}`);
                        const subNode = document.getElementById(`s_${key}`);
                        const queueTotalNode = document.getElementById(`c_queue_total_${key}`);
                        const queueOvNode = document.getElementById(`c_queue_ov_${key}`);
                        const queueNotesNode = document.getElementById(`c_queue_notes_${key}`);
                        const prevDoneNode = document.getElementById(`c_prev_done_${key}`);
                        const nextEntryNode = document.getElementById(`c_next_entry_${key}`);
                        const prevDoneLabelNode = document.getElementById(`l_prev_done_${key}`);

                        if (titleNode) titleNode.textContent = item.service_name || '-';
                        if (subNode) {
                            if (isFixed) {
                                subNode.textContent = fixedDefaults.subtitle;
                            } else {
                                const weekLabel = item.week?.label ? ` | Janela: ${item.week.label}` : '';
                                subNode.textContent = `Anterior: ${item.previous_service_name || '-'}${weekLabel}`;
                            }
                        }
                        if (queueTotalNode) queueTotalNode.textContent = String(item.cards?.queue_total ?? 0);
                        if (queueOvNode) queueOvNode.textContent = String(item.cards?.queue_ov ?? 0);
                        if (queueNotesNode) queueNotesNode.textContent = String(item.cards?.queue_notes ?? 0);
                        if (prevDoneNode) prevDoneNode.textContent = String(item.cards?.previous_done ?? 0);
                        if (nextEntryNode) nextEntryNode.textContent = String(item.cards?.next_entry ?? 0);
                        if (prevDoneLabelNode) prevDoneLabelNode.textContent = `${item.previous_service_name || 'Anterior'} finalizados`;

                        if (isFixed) {
                            const topCards = panel.querySelector(`#ads_top_${key}`);
                            const midCards = panel.querySelector(`#ads_mid_${key}`);
                            const lineCanvas = panel.querySelector(`#ads_line_${key}`);
                            const barCanvas = panel.querySelector(`#ads_bar_${key}`);
                            const queueCanvas = panel.querySelector(`#ads_queue_${key}`);
                            const reuseCanvas = panel.querySelector(`#ads_reuse_${key}`);

                            const dashboard = item.ads_dashboard || {};
                            const top = Array.isArray(dashboard.top_cards) ? dashboard.top_cards : [];
                            const mid = Array.isArray(dashboard.middle_cards) ? dashboard.middle_cards : [];
                            const adsLineTitleNode = panel.querySelector(`#ads_line_title_${key}`);
                            const adsBarTitleNode = panel.querySelector(`#ads_bar_title_${key}`);
                            const adsQueueTitleNode = panel.querySelector(`#ads_queue_title_${key}`);
                            const adsReuseTitleNode = panel.querySelector(`#ads_reuse_title_${key}`);
                            const isProjectReviewFixed = String(item?.service_id || '') === 'fixed-project_review_dashboard';
                            const barChartBox = barCanvas ? barCanvas.closest('.w2-chart') : null;
                            const adsLeftGrid = lineCanvas ? lineCanvas.closest('.w2-ads-left') : null;

                            if (subNode) {
                                subNode.textContent = String(dashboard.subtitle || fixedDefaults.subtitle);
                            }
                            if (adsLineTitleNode) adsLineTitleNode.textContent = String(dashboard.line_chart_title || fixedDefaults.lineTitle);
                            if (adsBarTitleNode) adsBarTitleNode.textContent = String(dashboard.bar_chart_title || fixedDefaults.barTitle);
                            if (adsQueueTitleNode) adsQueueTitleNode.textContent = String(dashboard.queue_donut_title || fixedDefaults.queueTitle);
                            if (adsReuseTitleNode) adsReuseTitleNode.textContent = String(dashboard.reuse_donut_title || fixedDefaults.reuseTitle);
                            if (barChartBox) barChartBox.style.display = isProjectReviewFixed ? 'none' : 'flex';
                            if (adsLeftGrid) adsLeftGrid.style.gridTemplateRows = isProjectReviewFixed ? '1fr' : '1fr 1fr';

                            if (topCards) {
                                topCards.innerHTML = top.map((card) => `
                                    <div class="w2-ads-card" style="background:${card.card_bg || 'rgba(255,255,255,.05)'};border-color:${card.card_border || 'rgba(255,255,255,.12)'};">
                                        <div class="w2-ads-card__l">${card.label || '-'}</div>
                                        <div class="w2-ads-card__v">${card.value ?? 0}</div>
                                        ${card.trend ? `<div class="w2-ads-card__k" style="color:${card.trend_color || '#9bb2ca'}">${card.trend}</div>` : ''}
                                    </div>
                                `).join('');
                            }

                            if (midCards) {
                                midCards.innerHTML = mid.map((card) => `
                                    <div class="w2-ads-card" style="background:${card.card_bg || 'rgba(255,255,255,.05)'};border-color:${card.card_border || 'rgba(255,255,255,.12)'};">
                                        <div class="w2-ads-card__l">${card.label || '-'}</div>
                                        <div class="w2-ads-card__v">${card.value ?? 0}</div>
                                        ${card.trend ? `<div class="w2-ads-card__k" style="color:${card.trend_color || '#9bb2ca'}">${card.trend}</div>` : ''}
                                    </div>
                                `).join('');
                            }

                            if (!lineCanvas || !queueCanvas || !reuseCanvas) {
                                return;
                            }

                            const adsLineChart = ensureChart(`ads_line_${key}`, lineCanvas, {
                                type: 'line',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { color: CHART_TEXT_COLORS.onDark, precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                        },
                                        x: {
                                            ticks: { color: CHART_TEXT_COLORS.onDark },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: CHART_TEXT_COLORS.onDark },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'line',
                                            offset: 12,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            });

                            const adsBarChart = (!isProjectReviewFixed && barCanvas) ? ensureChart(`ads_bar_${key}`, barCanvas, {
                                type: 'bar',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { color: CHART_TEXT_COLORS.onDark, precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                        },
                                        x: {
                                            ticks: { color: CHART_TEXT_COLORS.onDark },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: CHART_TEXT_COLORS.onDark },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'bar',
                                            offset: 8,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            }) : null;

                            const adsQueueDonut = ensureChart(`ads_queue_${key}`, queueCanvas, {
                                type: 'doughnut',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    plugins: {
                                        legend: {
                                            labels: { color: CHART_TEXT_COLORS.onDark },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'doughnut',
                                            offset: 14,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            });

                            const adsReuseDonut = ensureChart(`ads_reuse_${key}`, reuseCanvas, {
                                type: 'doughnut',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    plugins: {
                                        legend: {
                                            labels: { color: CHART_TEXT_COLORS.onDark },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'doughnut',
                                            offset: 14,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            });

                            if (shouldUpdateComponent('ads_dashboard')) {
                                updateChartData(
                                    adsLineChart,
                                    dashboard.line_chart?.labels || [],
                                    (dashboard.line_chart?.datasets || []).map((ds) => ({
                                        ...ds,
                                        borderWidth: ds.borderWidth ?? 2,
                                        pointRadius: ds.pointRadius ?? 2.5,
                                    })),
                                    false
                                );

                                if (adsBarChart) {
                                    updateChartData(
                                        adsBarChart,
                                        dashboard.bar_chart?.labels || [],
                                        (dashboard.bar_chart?.datasets || []).map((ds) => ({
                                            ...ds,
                                            borderWidth: ds.borderWidth ?? 1,
                                        })),
                                        false
                                    );
                                }

                                updateChartData(
                                    adsQueueDonut,
                                    dashboard.queue_donut?.labels || [],
                                    [{
                                        label: 'Fila atual',
                                        data: dashboard.queue_donut?.values || [],
                                        backgroundColor: dashboard.queue_donut?.colors || [
                                            colorRgba(CHART_BRAND_COLORS.accent, 0.82),
                                            colorRgba(CHART_BRAND_COLORS.slate, 0.82),
                                            colorRgba(CHART_BRAND_COLORS.warning, 0.82),
                                        ],
                                        borderColor: '#ffffff',
                                        borderWidth: 1,
                                    }],
                                    false
                                );

                                updateChartData(
                                    adsReuseDonut,
                                    dashboard.reuse_donut?.labels || [],
                                    [{
                                        label: 'Economia ADS',
                                        data: dashboard.reuse_donut?.values || [],
                                        backgroundColor: dashboard.reuse_donut?.colors || [
                                            colorRgba(CHART_BRAND_COLORS.success, 0.82),
                                            colorRgba(CHART_BRAND_COLORS.primary, 0.82),
                                        ],
                                        borderColor: '#ffffff',
                                        borderWidth: 1,
                                    }],
                                    false
                                );
                            }

                            const queueTotalNode = panel.querySelector(`#ads_queue_total_${key}`);
                            const reuseTotalNode = panel.querySelector(`#ads_reuse_total_${key}`);
                            const queueTotal = normalizeNumber(dashboard.queue_donut?.total ?? sumValues(dashboard.queue_donut?.values || []));
                            const reuseTotal = normalizeNumber(dashboard.reuse_donut?.total ?? sumValues(dashboard.reuse_donut?.values || []));
                            if (queueTotalNode) queueTotalNode.textContent = `Total: ${queueTotal}`;
                            if (reuseTotalNode) reuseTotalNode.textContent = `Total: ${reuseTotal}`;

                            const adsPoints = Array.isArray(dashboard.line_chart?.labels) ? dashboard.line_chart.labels.length : 0;
                            const adsSum = (dashboard.line_chart?.datasets || []).reduce((acc, ds) => {
                                return acc + sumValues(ds?.data || []);
                            }, 0);
                            setPointsBadge(key, 'ads_dashboard', adsPoints, adsSum);
                        } else {
                            const queueCanvas = panel.querySelector(`#q_${key}`);
                            const noteTypeDonutCanvas = panel.querySelector(`#nd_${key}`);
                            const prodCanvas = panel.querySelector(`#p_${key}`);
                            const flowCanvas = panel.querySelector(`#f_${key}`);
                            const donutCanvas = panel.querySelector(`#d_${key}`);
                            const donutEmptyNode = panel.querySelector(`#d_empty_${key}`);
                            const listNode = panel.querySelector(`#list_${key}`);
                            const noteTypeTotalNode = panel.querySelector(`#note_type_total_${key}`);

                            const queueChart = queueCanvas ? ensureChart(`q_${key}`, queueCanvas, {
                                type: 'bar',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { color: CHART_TEXT_COLORS.onDark, precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                            stacked: true,
                                        },
                                        x: {
                                            ticks: { color: CHART_TEXT_COLORS.onDark },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                            stacked: true,
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: CHART_TEXT_COLORS.onDark },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'bar',
                                            offset: 8,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            }) : null;

                            const noteTypeDonutChart = noteTypeDonutCanvas ? ensureChart(`nd_${key}`, noteTypeDonutCanvas, {
                                type: 'doughnut',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: {
                                        duration: 450
                                    },
                                    plugins: {
                                        legend: {
                                            labels: {
                                                color: CHART_TEXT_COLORS.onDark
                                            },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'doughnut',
                                            offset: 14,
                                            color: CHART_TEXT_COLORS.onDark,
                                            labelType: 'value',
                                        },
                                    },
                                },
                            }) : null;

                            const prodChart = prodCanvas ? ensureChart(`p_${key}`, prodCanvas, {
                                type: 'bar',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { color: CHART_TEXT_COLORS.onDark, precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                            stacked: true,
                                        },
                                        x: {
                                            ticks: { color: CHART_TEXT_COLORS.onDark },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                            stacked: true,
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: CHART_TEXT_COLORS.onDark },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'bar',
                                            offset: 8,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            }) : null;

                            const flowChart = flowCanvas ? ensureChart(`f_${key}`, flowCanvas, {
                                type: 'bar',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: {
                                        duration: 450
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                color: CHART_TEXT_COLORS.onDark,
                                                precision: 0
                                            },
                                            grid: {
                                                color: 'rgba(255,255,255,.12)'
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                color: CHART_TEXT_COLORS.onDark
                                            },
                                            grid: {
                                                color: 'rgba(255,255,255,.05)'
                                            },
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: {
                                                color: CHART_TEXT_COLORS.onDark
                                            },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'bar',
                                            offset: 8,
                                            color: CHART_TEXT_COLORS.onDark,
                                        },
                                    },
                                },
                            }) : null;

                            const donutChart = donutCanvas ? ensureChart(`d_${key}`, donutCanvas, {
                                type: 'doughnut',
                                data: {
                                    labels: [],
                                    datasets: [],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: {
                                        duration: 450
                                    },
                                    plugins: {
                                        legend: {
                                            labels: {
                                                color: CHART_TEXT_COLORS.onDark
                                            },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'doughnut',
                                            offset: 14,
                                            color: CHART_TEXT_COLORS.onDark,
                                            labelType: 'value',
                                        },
                                    },
                                },
                            }) : null;

                            // Captura os dados brutos do backend
                            const queueLabelsRaw = Array.isArray(item.queue_histogram?.labels) ? item.queue_histogram.labels : [];
                            const queueValuesRaw = Array.isArray(item.queue_histogram?.values) ? item.queue_histogram.values.map(normalizeNumber) : [];
                            const queueAssignedRaw = Array.isArray(item.queue_histogram?.assigned_values) ? item.queue_histogram.assigned_values.map(normalizeNumber) : [];
                            const queueWithoutAssignedRaw = Array.isArray(item.queue_histogram?.without_assigned_values) ? item.queue_histogram.without_assigned_values.map(normalizeNumber) : [];

                            // NOVA REGRA: Se o backend enviou os dias 0 a 30 (labels), usamos eles sempre!
                            const queueHasLabels = queueLabelsRaw.length > 0;

                            const queueLabels = queueHasLabels ? queueLabelsRaw : ['Total'];
                            const queueValues = queueHasLabels ? queueValuesRaw : [normalizeNumber(item.cards?.queue_ov ?? 0)];

                            const queueAssignedValues = queueHasLabels
                                ? (queueAssignedRaw.length ? queueAssignedRaw : queueLabels.map(() => 0))
                                : [0];

                            const queueWithoutAssignedValues = queueHasLabels
                                ? (queueWithoutAssignedRaw.length ? queueWithoutAssignedRaw : queueValues.map((v, i) => Math.max(0, normalizeNumber(v) - normalizeNumber(queueAssignedValues[i] ?? 0))))
                                : [normalizeNumber(item.cards?.queue_ov ?? 0)];

                            if (DEBUG) {
                                console.log('wall-v2 PilhaAtividade card-data', {
                                    wallId,
                                    screenId: screen.id,
                                    serviceId: item.service_id,
                                    serviceName: item.service_name,
                                    queueHistogramRaw: item.queue_histogram || {},
                                    queueLabels,
                                    queueValues,
                                    queueAssignedValues,
                                    queueWithoutAssignedValues,
                                    cards: item.cards || {},
                                });
                            }

                            const noteTypeLabels = Array.isArray(item.note_type_donut?.labels) && item.note_type_donut.labels.length
                                ? item.note_type_donut.labels
                                : ['Com produção associada', 'Sem produção associada'];
                            const noteTypeValuesRaw = Array.isArray(item.note_type_donut?.values) ? item.note_type_donut.values.map(normalizeNumber) : [];
                            const noteTypeTotal = normalizeNumber(item.note_type_donut?.total ?? normalizeNumber(item.cards?.queue_total));
                            const noteTypeValues = noteTypeValuesRaw.length
                                ? noteTypeValuesRaw
                                : [0, Math.max(0, noteTypeTotal)];

                            const prodLabelsRaw = Array.isArray(item.production_open_histogram?.labels) ? item.production_open_histogram.labels : [];
                            const prodValuesRaw = Array.isArray(item.production_open_histogram?.values) ? item.production_open_histogram.values.map(normalizeNumber) : [];
                            const prodNormalRaw = Array.isArray(item.production_open_histogram?.normal_values) ? item.production_open_histogram.normal_values.map(normalizeNumber) : [];
                            const prodRiRaw = Array.isArray(item.production_open_histogram?.ri_values) ? item.production_open_histogram.ri_values.map(normalizeNumber) : [];
                            const prodHasData = prodLabelsRaw.length && (sumValues(prodValuesRaw) > 0 || sumValues(prodNormalRaw) > 0 || sumValues(prodRiRaw) > 0);
                            const prodLabels = prodHasData ? prodLabelsRaw : ['Total'];
                            const fallbackRi = normalizeNumber(item.cards?.returned);
                            const fallbackTotal = sumValues(prodValuesRaw);
                            const fallbackNormal = Math.max(0, fallbackTotal - fallbackRi);
                            const prodNormalValues = prodHasData
                                ? (prodNormalRaw.length ? prodNormalRaw : prodValuesRaw.map((v, i) => Math.max(0, v - normalizeNumber(prodRiRaw[i] ?? 0))))
                                : [fallbackNormal];
                            const prodRiValues = prodHasData
                                ? (prodRiRaw.length ? prodRiRaw : prodValuesRaw.map((v, i) => Math.max(0, v - normalizeNumber(prodNormalRaw[i] ?? 0))))
                                : [fallbackRi];

                            const flowLabelsRaw = Array.isArray(item.production_daily?.labels) ? item.production_daily.labels : [];
                            const flowAssignedRaw = Array.isArray(item.production_daily?.assigned) ? item.production_daily.assigned.map(normalizeNumber) : [];
                            const flowDeliveredRaw = Array.isArray(item.production_daily?.delivered) ? item.production_daily.delivered.map(normalizeNumber) : [];
                            const flowHasData = flowLabelsRaw.length && (sumValues(flowAssignedRaw) > 0 || sumValues(flowDeliveredRaw) > 0);
                            const flowLabels = flowHasData ? flowLabelsRaw : ['Total'];
                            const flowAssigned = flowHasData ? flowAssignedRaw : [0];
                            const flowDelivered = flowHasData ? flowDeliveredRaw : [normalizeNumber(item.cards?.previous_done)];

                            const donutRawValues = Array.isArray(item.internal_return_donut?.values) ? item.internal_return_donut.values.map(normalizeNumber) : [];
                            const donutTotal = sumValues(donutRawValues);
                            const donutHasData = donutRawValues.length > 0 && donutTotal > 0;
                            const donutValues = donutHasData ? donutRawValues : [];
                            const donutRawLabels = Array.isArray(item.internal_return_donut?.labels) && item.internal_return_donut.labels.length ? item.internal_return_donut.labels : [];
                            const donutLabels = donutRawLabels.map((lbl, idx) => {
                                const qty = normalizeNumber(donutRawValues[idx] ?? 0);
                                return `${lbl} (${qty})`;
                            });
                            const donutColors = [
                                CHART_BRAND_COLORS.primary,
                                CHART_BRAND_COLORS.accent,
                                CHART_BRAND_COLORS.warning,
                                CHART_BRAND_COLORS.danger,
                                CHART_BRAND_COLORS.magenta,
                                CHART_BRAND_COLORS.success,
                                CHART_BRAND_COLORS.neutral,
                                CHART_BRAND_COLORS.slate,
                            ];

                            if (queueChart && shouldUpdateComponent('queue_histogram')) {
                                queueChart.data.labels = queueLabels.map((v) => String(v ?? ''));
                                if (!Array.isArray(queueChart.data.datasets) || !queueChart.data.datasets[0]) {
                                    queueChart.data.datasets = [
                                        {
                                            label: 'Sem produção atribuída',
                                            data: [],
                                            backgroundColor: colorRgba(CHART_BRAND_COLORS.accent, 0.55),
                                            borderColor: CHART_BRAND_COLORS.accent,
                                            borderWidth: 1,
                                            borderSkipped: false,
                                            categoryPercentage: 0.82,
                                            barPercentage: 0.9,
                                            maxBarThickness: 42,
                                        },
                                        {
                                            label: 'Com produção atribuída',
                                            data: [],
                                            backgroundColor: colorRgba(CHART_BRAND_COLORS.success, 0.65),
                                            borderColor: CHART_BRAND_COLORS.success,
                                            borderWidth: 1,
                                            borderSkipped: false,
                                            categoryPercentage: 0.82,
                                            barPercentage: 0.9,
                                            maxBarThickness: 42,
                                        }
                                    ];
                                }
                                queueChart.data.datasets[0].data = queueWithoutAssignedValues.map(normalizeNumber);
                                if (!queueChart.data.datasets[1]) {
                                    queueChart.data.datasets[1] = {
                                        label: 'Com produção atribuída',
                                        data: [],
                                        backgroundColor: colorRgba(CHART_BRAND_COLORS.success, 0.65),
                                        borderColor: CHART_BRAND_COLORS.success,
                                        borderWidth: 1,
                                        borderSkipped: false,
                                        categoryPercentage: 0.82,
                                        barPercentage: 0.9,
                                        maxBarThickness: 42,
                                    };
                                }
                                queueChart.data.datasets[1].data = queueAssignedValues.map(normalizeNumber);
                                queueChart.update();
                            }

                            if (noteTypeDonutChart && shouldUpdateComponent('note_type_donut')) {
                                updateChartDataAsync(
                                    noteTypeDonutChart,
                                    noteTypeLabels.map((v) => String(v ?? '')),
                                    [{
                                        label: 'Notas OV',
                                        data: noteTypeValues.map(normalizeNumber),
                                        backgroundColor: [
                                            colorRgba(CHART_BRAND_COLORS.success, 0.82),
                                            colorRgba(CHART_BRAND_COLORS.primary, 0.82),
                                        ],
                                        borderColor: '#ffffff',
                                        borderWidth: 1,
                                    }],
                                    false
                                );
                            }
                            if (noteTypeTotalNode) {
                                noteTypeTotalNode.textContent = `Total: ${noteTypeTotal}`;
                            }

                            if (prodChart && shouldUpdateComponent('production_open_histogram')) {
                                prodChart.data.labels = prodLabels.map((v) => String(v ?? ''));
                                if (!Array.isArray(prodChart.data.datasets) || !prodChart.data.datasets[0]) {
                                    prodChart.data.datasets = [
                                        {
                                            label: 'Normal',
                                            data: [],
                                            backgroundColor: colorRgba(CHART_BRAND_COLORS.accent, 0.65),
                                            borderColor: CHART_BRAND_COLORS.accent,
                                            borderWidth: 1,
                                            borderSkipped: false,
                                            categoryPercentage: 0.82,
                                            barPercentage: 0.9,
                                            maxBarThickness: 42,
                                        },
                                        {
                                            label: 'RI',
                                            data: [],
                                            backgroundColor: colorRgba(CHART_BRAND_COLORS.warning, 0.75),
                                            borderColor: CHART_BRAND_COLORS.warning,
                                            borderWidth: 1,
                                            borderSkipped: false,
                                            categoryPercentage: 0.82,
                                            barPercentage: 0.9,
                                            maxBarThickness: 42,
                                        }
                                    ];
                                }
                                prodChart.data.datasets[0].data = prodNormalValues.map(normalizeNumber);
                                if (!prodChart.data.datasets[1]) {
                                    prodChart.data.datasets[1] = {
                                        label: 'RI',
                                        data: [],
                                        backgroundColor: colorRgba(CHART_BRAND_COLORS.warning, 0.75),
                                        borderColor: CHART_BRAND_COLORS.warning,
                                        borderWidth: 1,
                                        borderSkipped: false,
                                        categoryPercentage: 0.82,
                                        barPercentage: 0.9,
                                        maxBarThickness: 42,
                                    };
                                }
                                prodChart.data.datasets[1].data = prodRiValues.map(normalizeNumber);
                                prodChart.update();
                            }

                            if (flowChart && shouldUpdateComponent('production_daily')) {
                                flowChart.data.labels = flowLabels.map((v) => String(v ?? ''));
                                if (!Array.isArray(flowChart.data.datasets) || flowChart.data.datasets.length < 2) {
                                    flowChart.data.datasets = [{
                                            label: 'Atribuído',
                                            data: [],
                                            backgroundColor: colorRgba(CHART_BRAND_COLORS.accent, 0.65),
                                            borderColor: CHART_BRAND_COLORS.accent,
                                            borderWidth: 1,
                                            borderSkipped: false,
                                        },
                                        {
                                            label: 'Entregue',
                                            data: [],
                                            backgroundColor: colorRgba(CHART_BRAND_COLORS.success, 0.65),
                                            borderColor: CHART_BRAND_COLORS.success,
                                            borderWidth: 1,
                                            borderSkipped: false,
                                        },
                                    ];
                                }
                                flowChart.data.datasets[0].data = flowAssigned.map(normalizeNumber);
                                flowChart.data.datasets[1].data = flowDelivered.map(normalizeNumber);
                                flowChart.update();
                            }

                            if (donutChart && shouldUpdateComponent('internal_return_donut')) {
                                if (donutHasData) {
                                    if (donutEmptyNode) donutEmptyNode.style.display = 'none';
                                    updateChartDataAsync(
                                        donutChart,
                                        donutLabels,
                                        [{
                                            label: 'Retorno interno',
                                            data: donutValues,
                                            backgroundColor: donutLabels.map((_, i) => donutColors[i % donutColors.length]),
                                            borderColor: '#ffffff',
                                            borderWidth: 1,
                                        }],
                                        false
                                    );
                                } else {
                                    if (donutEmptyNode) donutEmptyNode.style.display = 'flex';
                                    updateChartDataAsync(donutChart, [], [], false);
                                }
                            }

                            if (listNode && shouldUpdateComponent('recent_completed')) {
                                const recentRows = (item.recent_completed || []);
                                const rows = recentRows.map((row) => `
                                    <tr>
                                        <td>${row.note || '-'}</td>
                                        <td>${row.user_name || '-'}</td>
                                        <td>${row.company_name || '-'}</td>
                                        <td><span class="w2-tag">${row.type || '-'}</span></td>
                                        <td>${row.completed_at || '-'}</td>
                                    </tr>
                                `).join('');
                                listNode.innerHTML = rows || '<tr><td colspan="5">Sem entregas no período.</td></tr>';
                                const listWrap = panel.querySelector('.w2-list__wrap');
                                if (listWrap) {
                                    const signature = `${recentRows.length}:${recentRows[0]?.note || '-'}:${recentRows[recentRows.length - 1]?.note || '-'}`;
                                    if (listWrap.dataset.scrollSignature !== signature) {
                                        listWrap.dataset.scrollSignature = signature;
                                        startListAutoScroll(key, listWrap);
                                    } else if (!listScrollLoops.has(key)) {
                                        startListAutoScroll(key, listWrap);
                                    }
                                }
                            }

                            setPointsBadge(
                                key,
                                'queue_histogram',
                                queueLabels.length,
                                sumValues(queueWithoutAssignedValues) + sumValues(queueAssignedValues)
                            );
                            setPointsBadge(key, 'note_type_donut', noteTypeLabels.length, sumValues(noteTypeValues));
                            setPointsBadge(key, 'production_open_histogram', prodLabels.length, sumValues(prodNormalValues) + sumValues(prodRiValues));
                            setPointsBadge(
                                key,
                                'production_daily',
                                flowLabels.length,
                                sumValues(flowAssigned) + sumValues(flowDelivered)
                            );
                            setPointsBadge(key, 'internal_return_donut', donutLabels.length, sumValues(donutValues));
                            setPointsBadge(key, 'recent_completed', (item.recent_completed || []).length, (item.recent_completed || []).length);

                        }
                    });

                        renderedScreenId = screen.id;
                    } catch (error) {
                        console.error('wall-v2 render error', error);
                        const node = document.getElementById('w2-updated');
                        if (node) {
                            node.textContent = `Erro render: ${error?.message || 'desconhecido'}`;
                        }
                    } finally {
                        setCounters();
                    }
                }

                function nextScreen() {
                    const screen = currentScreen();
                    if (!canRotateScreens()) {
                        rotateRemaining = screenDuration(screen);
                        return;
                    }

                    currentScreenIndex = (currentScreenIndex + 1) % payload.screens.length;
                    currentServiceIndex = 0;
                    const next = currentScreen();
                    rotateRemaining = screenDuration(next);
                    serviceRotateRemaining = serviceDuration(next);
                    render();
                }

                function nextServiceInScreen() {
                    const screen = currentDisplayScreen();
                    if (!screen) return;
                    if (!canRotateServices(screen)) {
                        serviceRotateRemaining = serviceDuration(screen);
                        return;
                    }
                    const items = screen.items || [];
                    currentServiceIndex = (currentServiceIndex + 1) % items.length;
                    serviceRotateRemaining = serviceDuration(screen);
                    render();
                }

                async function bootstrap() {
                    registerValueLabelsPlugin();
                    try {
                        sessionStorage.removeItem(payloadStorageKey());
                    } catch (e) {}

                    render();
                    setCounters();

                    await fetchPayload();
                    const first = currentDisplayScreen();
                    if (first) {
                        await primeScreenOnce(first);
                    }
                    rotateRemaining = screenDuration(currentScreen());
                    serviceRotateRemaining = serviceDuration(currentScreen());
                    manifestSyncRemaining = manifestSyncIntervalSeconds();
                    render();
                    setCounters();

                    if (timer) clearInterval(timer);
                    timer = setInterval(async () => {
                        if (tickInFlight) return;
                        tickInFlight = true;
                        try {
                        componentCountdowns.forEach((value, component) => {
                            componentCountdowns.set(component, Math.max(0, Number(value || 0) - 1));
                        });
                        rotateRemaining = Math.max(0, (rotateRemaining || 0) - 1);
                        serviceRotateRemaining = Math.max(0, (serviceRotateRemaining || 0) - 1);
                        manifestSyncRemaining = Math.max(0, Number(manifestSyncRemaining || 0) - 1);

                        const screen = currentScreen();
                        if (rotateRemaining <= 0) {
                            if (canRotateScreens()) {
                                nextScreen();
                            } else {
                                rotateRemaining = screenDuration(screen);
                            }
                        }

                        if (serviceRotateRemaining <= 0) {
                            if (canRotateServices(currentDisplayScreen())) {
                                nextServiceInScreen();
                            } else {
                                serviceRotateRemaining = serviceDuration(currentDisplayScreen());
                            }
                        }

                        const dueComponents = [];
                        componentCountdowns.forEach((value, component) => {
                            if (Number(value || 0) <= 0) dueComponents.push(component);
                        });
                        if (dueComponents.length) {
                            await Promise.all(
                                dueComponents.map((component) => refreshSingleComponent(component, true))
                            );
                        }

                        if (manifestSyncRemaining <= 0) {
                            await syncManifestIfNeeded();
                        }

                        setCounters();
                        } finally {
                            tickInFlight = false;
                        }
                    }, 1000);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootstrap, {
                        once: true
                    });
                } else {
                    bootstrap();
                }
            })();
        </script>
    @endpush
@endsection
