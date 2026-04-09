@extends('layouts.fullscreen-wall')

@section('content')
    <div id="wall-v2" class="wall-v2"
        data-endpoint="{{ isset($screenId) && $screenId ? route('api.v1.reports.production_wall_v2.screen', ['wall' => $wallId, 'screen' => $screenId]) : route('api.v1.reports.production_wall_v2', ['wall' => $wallId]) }}"
        data-screen-id="{{ $screenId ?? '' }}" data-wall-id="{{ $wallId ?? '' }}">
        <div class="wall-v2__top">
            <div>
                <h1 class="wall-v2__title">WALL Produção V2</h1>
                <div class="wall-v2__meta" id="w2-updated">Atualizado: -</div>
            </div>
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

            .wall-v2__title {
                margin: 0;
                font-size: 1.2rem;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .wall-v2__meta {
                color: #a9bdd3;
                font-size: .88rem;
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
                font-size: 1rem;
                font-weight: 700;
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
                grid-template-columns: 1fr 1fr;
                gap: .55rem;
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
                const fixedScreenId = Number(root.dataset.screenId || 0);
                const wallId = String(root.dataset.wallId || '');
                let chartsEndpointBase = `/api/v1/reports/walls/${encodeURIComponent(wallId)}/production-v2`;
                try {
                    const epPath = new URL(endpoint, window.location.origin).pathname;
                    const match = epPath.match(/\/api\/v1\/reports\/walls\/[^/]+\/production-v2/);
                    if (match && match[0]) {
                        chartsEndpointBase = match[0];
                    }
                } catch (e) {}
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
                const charts = new Map();
                const componentDataCache = new Map();
                const componentCountdowns = new Map();
                const pendingComponentFetch = new Set();
                const valueLabelsPluginId = 'valueLabelsPlugin';
                let wallErrorMessage = '';
                const productionComponents = [
                    'cards',
                    'queue_histogram',
                    'production_open_histogram',
                    'production_daily',
                    'internal_return_donut',
                    'recent_completed',
                ];
                const adsComponents = ['ads_dashboard'];

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
                            const color = opts.color || '#dce8f5';
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
                                    meta.data.forEach((arc, idx) => {
                                        const raw = dataset?.data?.[idx];
                                        const value = Number(raw ?? 0);
                                        if (!Number.isFinite(value) || value === 0) return;
                                        const pos = arc.tooltipPosition();
                                        ctx.textBaseline = 'top';
                                        ctx.fillText(String(value), pos.x, pos.y + offset);
                                    });
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
                        updated_at: raw?.updated_at || '',
                        rotation_seconds: Number(raw?.rotation_seconds || 180),
                        refresh_seconds: Number(raw?.refresh_seconds || 60),
                        screens: Array.isArray(raw?.screens) ? raw.screens : [],
                    };
                }

                async function fetchPayload() {
                    if (!endpoint) return;
                    try {
                        const response = await fetch(endpoint, {
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
                        const raw = await response.json();
                        payload = normalize(raw);
                        try {
                            sessionStorage.setItem(payloadStorageKey(), JSON.stringify(raw));
                        } catch (e) {}
                        if (currentScreenIndex >= payload.screens.length) {
                            currentScreenIndex = 0;
                        }
                    } catch (e) {
                        wallErrorMessage = 'NAO EXISTE WALL CONFIGURADO';
                    }
                }

                function currentScreen() {
                    return payload.screens[currentScreenIndex] || null;
                }

                function currentDisplayScreen() {
                    const screen = currentScreen();
                    return screen ? applyServiceFilter(screen) : null;
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
                    [
                        'ads_line_',
                        'ads_bar_',
                        'ads_queue_',
                        'ads_reuse_',
                        'q_',
                        'p_',
                        'f_',
                        'd_',
                    ].forEach((prefix) => charts.delete(`${prefix}${panel}`));
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

                function activeComponentsForItem(item) {
                    return item?.ads_chart ? adsComponents : productionComponents;
                }

                function counterNodeId(panel, component) {
                    return `ctr_${component}_${panel}`;
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
                    componentCountdowns.clear();
                    const seconds = Number(payload.refresh_seconds || 60);
                    activeComponentsForItem(item).forEach((component) => {
                        componentCountdowns.set(component, seconds);
                    });
                    writeComponentCounters();
                }

                function writeComponentCounters() {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    const components = activeComponentsForItem(state.item);
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
                        const url = `${chartsEndpointBase}/${encodeURIComponent(String(screenId))}/items/${encodeURIComponent(String(serviceId))}/charts?component=${encodeURIComponent(component)}`;
                        const res = await fetch(url, {
                            method: 'GET',
                            credentials: 'same-origin',
                            cache: 'no-store',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        if (!res.ok) return null;
                        const data = await res.json();
                        componentDataCache.set(key, data || null);
                        return data || null;
                    } catch (e) {
                        return null;
                    }
                }

                async function fetchItemFull(screenId, serviceId, force = false) {
                    const key = `${screenId}:${serviceId}:__full__`;
                    if (!force && componentDataCache.has(key)) {
                        return componentDataCache.get(key);
                    }
                    try {
                        const url = `${chartsEndpointBase}/${encodeURIComponent(String(screenId))}/items/${encodeURIComponent(String(serviceId))}/charts`;
                        const res = await fetch(url, {
                            method: 'GET',
                            credentials: 'same-origin',
                            cache: 'no-store',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        if (!res.ok) return null;
                        const data = await res.json();
                        componentDataCache.set(key, data || null);
                        return data || null;
                    } catch (e) {
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
                    const remoteCardsSum = objectNumericSum(remoteCards, ['queue_total', 'in_analysis', 'returned', 'previous_done', 'previous_ready']);
                    const currentCardsSum = objectNumericSum(currentCards, ['queue_total', 'in_analysis', 'returned', 'previous_done', 'previous_ready']);

                    const remoteQueue = remote.charts?.queue_histogram || {};
                    const currentQueue = current.queue_histogram || {};
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
                    const remote = await fetchItemFull(state.screen.id, state.item.service_id, force);
                    if (!remote) {
                        render();
                        return;
                    }
                    if (applyFullItemOnPayload(state.screen.id, state.item.service_id, remote)) {
                        payload.updated_at = remote.updated_at || payload.updated_at || '';
                        render();
                    }
                }

                async function refreshSingleComponent(component, force = true) {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    const lockKey = `${state.screen.id}:${state.item.service_id}:${component}`;
                    if (pendingComponentFetch.has(lockKey)) return;
                    pendingComponentFetch.add(lockKey);
                    try {
                        const remote = await fetchItemComponent(state.screen.id, state.item.service_id, component, force);
                        if (!remote) return;
                        const changed = applyComponentOnItem(state.screen.id, state.item.service_id, component, remote.data);
                        if (changed) {
                            payload.updated_at = remote.updated_at || payload.updated_at || '';
                            render();
                        }
                        componentCountdowns.set(component, Number(payload.refresh_seconds || 60));
                    } finally {
                        pendingComponentFetch.delete(lockKey);
                    }
                }

                async function refreshAllComponentsNow(force = true) {
                    const state = currentPanelAndItem();
                    if (!state) return;
                    const components = activeComponentsForItem(state.item);
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
                        render();
                    }
                }

                function render() {
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
                            grid.innerHTML = '';
                            renderedScreenId = null;
                            renderedPanelKey = '';
                            componentCountdowns.clear();
                            screenName.textContent = '-';
                            return;
                        }

                    const sameScreen = renderedScreenId === screen.id;
                    if (!sameScreen) {
                        grid.querySelectorAll('.w2-panel').forEach((panelNode) => {
                            const key = panelNode.dataset.key || '';
                            if (key) clearPanelCharts(key);
                        });
                        grid.innerHTML = '';
                    }

                    screenName.textContent = screen.name || `Tela ${currentScreenIndex + 1}`;
                    const items = screen.items || [];
                    if (!items.length) {
                        grid.innerHTML = '';
                        renderedPanelKey = '';
                        componentCountdowns.clear();
                        return;
                    }

                    if (currentServiceIndex >= items.length) {
                        currentServiceIndex = 0;
                    }

                    const activeItem = items[currentServiceIndex];
                    const currentPanel = panelKey(screen.id, activeItem.service_id);
                    if (renderedPanelKey !== currentPanel) {
                        renderedPanelKey = currentPanel;
                        resetComponentCountdowns(activeItem);
                        setTimeout(() => {
                            refreshActiveItemFromFull(true);
                        }, 0);
                    }
                    grid.style.gridTemplateColumns = '1fr';

                    const activeKeys = new Set();

                    [activeItem].forEach((item) => {
                        const key = panelKey(screen.id, item.service_id);
                        activeKeys.add(key);
                        const isAds = !!item.ads_chart;
                        let panel = grid.querySelector(`[data-key="${key}"]`);
                        if (!panel) {
                            panel = document.createElement('div');
                            panel.className = 'w2-panel';
                            panel.dataset.key = key;
                            panel.innerHTML = isAds ? `
                                <div class="w2-panel__head">
                                    <h3 class="w2-panel__title" id="t_${key}">-</h3>
                                    <div class="w2-panel__sub" id="refresh_${key}">Refresh individual por card</div>
                                </div>
                                <div class="w2-panel__sub" id="s_${key}" style="margin-bottom:.4rem;">Tela ADS</div>
                                <div class="w2-ads">
                                    <div class="w2-ads-top" id="ads_top_${key}"></div>
                                    <div class="w2-ads-mid" id="ads_mid_${key}"></div>
                                    <div class="w2-ads-body">
                                        <div class="w2-ads-left">
                                            <div class="w2-chart">
                                                <div class="w2-chart__t">Acumulado e Atrasadas (linha) - visão diária <span id="pts_ads_dashboard_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_ads_dashboard_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_line_${key}"></canvas></div>
                                            </div>
                                            <div class="w2-chart">
                                                <div class="w2-chart__t">Entradas x Saídas (barras) - visão diária</div>
                                                <div class="w2-chart__wrap"><canvas id="ads_bar_${key}"></canvas></div>
                                            </div>
                                        </div>
                                        <div class="w2-ads-right">
                                            <div class="w2-chart">
                                                <div class="w2-chart__t">Fila atual (status pendentes) <span id="ads_queue_total_${key}" style="float:right;color:#9bb2ca">Total: 0</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_queue_${key}"></canvas></div>
                                            </div>
                                            <div class="w2-chart">
                                                <div class="w2-chart__t">Economia por reaproveitamento de ADS <span id="ads_reuse_total_${key}" style="float:right;color:#9bb2ca">Total: 0</span></div>
                                                <div class="w2-chart__wrap"><canvas id="ads_reuse_${key}"></canvas></div>
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
                                    <div class="w2-card"><div class="w2-card__l">Pilha OV <span id="ctr_cards_${key}" style="float:right;color:#9bb2ca">--</span></div><div class="w2-card__v" id="c_queue_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">Atribuído aberto</div><div class="w2-card__v" id="c_analysis_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">RI aberto</div><div class="w2-card__v" id="c_returned_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">Anterior done (semana)</div><div class="w2-card__v" id="c_prev_done_${key}">0</div></div>
                                    <div class="w2-card"><div class="w2-card__l">Próx. entrada</div><div class="w2-card__v" id="c_prev_ready_${key}">0</div></div>
                                </div>
                                <div class="w2-content">
                                    <div class="w2-charts">
                                        <div class="w2-chart">
                                            <div class="w2-chart__t">Pilha da atividade (OV, última semana) <span id="pts_queue_histogram_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_queue_histogram_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="q_${key}"></canvas></div>
                                        </div>
                                        <div class="w2-chart">
                                            <div class="w2-chart__t">Pilha de produção atribuída sem finalizar <span id="pts_production_open_histogram_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_production_open_histogram_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="p_${key}"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="w2-bottom">
                                        <div class="w2-chart">
                                            <div class="w2-chart__t">Produção dia a dia (atribuído x entregue) <span id="pts_production_daily_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_production_daily_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                            <div class="w2-chart__wrap"><canvas id="f_${key}"></canvas></div>
                                        </div>
                                        <div class="w2-bottom-right">
                                            <div class="w2-chart">
                                                <div class="w2-chart__t">Retorno interno por tipo <span id="pts_internal_return_donut_${key}" style="float:right;color:#9bb2ca;margin-left:.5rem;">pts:0 sum:0</span><span id="ctr_internal_return_donut_${key}" style="float:right;color:#9bb2ca">--</span></div>
                                                <div class="w2-chart__wrap"><canvas id="d_${key}"></canvas></div>
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

                        const titleNode = document.getElementById(`t_${key}`);
                        const subNode = document.getElementById(`s_${key}`);
                        const queueNode = document.getElementById(`c_queue_${key}`);
                        const analysisNode = document.getElementById(`c_analysis_${key}`);
                        const returnedNode = document.getElementById(`c_returned_${key}`);
                        const prevDoneNode = document.getElementById(`c_prev_done_${key}`);
                        const prevReadyNode = document.getElementById(`c_prev_ready_${key}`);

                        if (titleNode) titleNode.textContent = item.service_name || '-';
                        if (subNode) {
                            if (isAds) {
                                subNode.textContent = 'Tela ADS fixa';
                            } else {
                                const weekLabel = item.week?.label ? ` | Janela: ${item.week.label}` : '';
                                subNode.textContent = `Anterior: ${item.previous_service_name || '-'}${weekLabel}`;
                            }
                        }
                        if (queueNode) queueNode.textContent = String(item.cards?.queue_total ?? 0);
                        if (analysisNode) analysisNode.textContent = String(item.cards?.in_analysis ?? 0);
                        if (returnedNode) returnedNode.textContent = String(item.cards?.returned ?? 0);
                        if (prevDoneNode) prevDoneNode.textContent = String(item.cards?.previous_done ?? 0);
                        if (prevReadyNode) prevReadyNode.textContent = String(item.cards?.previous_ready ?? 0);

                        if (isAds) {
                            const topCards = panel.querySelector(`#ads_top_${key}`);
                            const midCards = panel.querySelector(`#ads_mid_${key}`);
                            const lineCanvas = panel.querySelector(`#ads_line_${key}`);
                            const barCanvas = panel.querySelector(`#ads_bar_${key}`);
                            const queueCanvas = panel.querySelector(`#ads_queue_${key}`);
                            const reuseCanvas = panel.querySelector(`#ads_reuse_${key}`);

                            const dashboard = item.ads_dashboard || {};
                            const top = Array.isArray(dashboard.top_cards) ? dashboard.top_cards : [];
                            const mid = Array.isArray(dashboard.middle_cards) ? dashboard.middle_cards : [];

                            if (topCards) {
                                topCards.innerHTML = top.map((card) => `
                                    <div class="w2-ads-card">
                                        <div class="w2-ads-card__l">${card.label || '-'}</div>
                                        <div class="w2-ads-card__v">${card.value ?? 0}</div>
                                    </div>
                                `).join('');
                            }

                            if (midCards) {
                                midCards.innerHTML = mid.map((card) => `
                                    <div class="w2-ads-card">
                                        <div class="w2-ads-card__l">${card.label || '-'}</div>
                                        <div class="w2-ads-card__v">${card.value ?? 0}</div>
                                    </div>
                                `).join('');
                            }

                            if (!lineCanvas || !barCanvas || !queueCanvas || !reuseCanvas) {
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
                                            ticks: { color: '#dce8f5', precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                        },
                                        x: {
                                            ticks: { color: '#dce8f5' },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: '#dce8f5' },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'line',
                                            offset: 12,
                                            color: '#dce8f5',
                                        },
                                    },
                                },
                            });

                            const adsBarChart = ensureChart(`ads_bar_${key}`, barCanvas, {
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
                                            ticks: { color: '#dce8f5', precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                        },
                                        x: {
                                            ticks: { color: '#dce8f5' },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: '#dce8f5' },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'bar',
                                            offset: 8,
                                            color: '#dce8f5',
                                        },
                                    },
                                },
                            });

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
                                            labels: { color: '#dce8f5' },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'doughnut',
                                            offset: 14,
                                            color: '#dce8f5',
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
                                            labels: { color: '#dce8f5' },
                                        },
                                        valueLabelsPlugin: {
                                            enabled: true,
                                            mode: 'doughnut',
                                            offset: 14,
                                            color: '#dce8f5',
                                        },
                                    },
                                },
                            });

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

                            updateChartData(
                                adsBarChart,
                                dashboard.bar_chart?.labels || [],
                                (dashboard.bar_chart?.datasets || []).map((ds) => ({
                                    ...ds,
                                    borderWidth: ds.borderWidth ?? 1,
                                })),
                                false
                            );

                            updateChartData(
                                adsQueueDonut,
                                dashboard.queue_donut?.labels || [],
                                [{
                                    label: 'Fila atual',
                                    data: dashboard.queue_donut?.values || [],
                                    backgroundColor: dashboard.queue_donut?.colors || ['#0ea5e9', '#6b7280', '#f59e0b'],
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
                                    backgroundColor: dashboard.reuse_donut?.colors || ['#059669', '#3b82f6'],
                                    borderColor: '#ffffff',
                                    borderWidth: 1,
                                }],
                                false
                            );

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
                            const prodCanvas = panel.querySelector(`#p_${key}`);
                            const flowCanvas = panel.querySelector(`#f_${key}`);
                            const donutCanvas = panel.querySelector(`#d_${key}`);
                            const listNode = panel.querySelector(`#list_${key}`);

                            const queueChart = queueCanvas ? ensureChart(`q_${key}`, queueCanvas, {
                                type: 'bar',
                                data: {
                                    labels: [],
                                    datasets: [{
                                        label: 'Pilha',
                                        data: [],
                                        backgroundColor: 'rgba(52, 152, 219, .55)',
                                        borderColor: '#3498db',
                                        borderWidth: 1,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 450 },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { color: '#dce8f5', precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                        },
                                        x: {
                                            ticks: { color: '#dce8f5' },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                        },
                                    },
                                    plugins: { legend: { display: false } },
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
                                            ticks: { color: '#dce8f5', precision: 0 },
                                            grid: { color: 'rgba(255,255,255,.12)' },
                                            stacked: true,
                                        },
                                        x: {
                                            ticks: { color: '#dce8f5' },
                                            grid: { color: 'rgba(255,255,255,.05)' },
                                            stacked: true,
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: { color: '#dce8f5' },
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
                                                color: '#dce8f5',
                                                precision: 0
                                            },
                                            grid: {
                                                color: 'rgba(255,255,255,.12)'
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                color: '#dce8f5'
                                            },
                                            grid: {
                                                color: 'rgba(255,255,255,.05)'
                                            },
                                        },
                                    },
                                    plugins: {
                                        legend: {
                                            labels: {
                                                color: '#dce8f5'
                                            },
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
                                                color: '#dce8f5'
                                            },
                                        },
                                    },
                                },
                            }) : null;

                            const queueLabelsRaw = Array.isArray(item.queue_histogram?.labels) ? item.queue_histogram.labels : [];
                            const queueValuesRaw = Array.isArray(item.queue_histogram?.values) ? item.queue_histogram.values.map(normalizeNumber) : [];
                            const queueHasData = queueLabelsRaw.length && sumValues(queueValuesRaw) > 0;
                            const queueLabels = queueHasData ? queueLabelsRaw : ['Total'];
                            const queueValues = queueHasData ? queueValuesRaw : [normalizeNumber(item.cards?.queue_total)];

                            const prodLabelsRaw = Array.isArray(item.production_open_histogram?.labels) ? item.production_open_histogram.labels : [];
                            const prodValuesRaw = Array.isArray(item.production_open_histogram?.values) ? item.production_open_histogram.values.map(normalizeNumber) : [];
                            const prodNormalRaw = Array.isArray(item.production_open_histogram?.normal_values) ? item.production_open_histogram.normal_values.map(normalizeNumber) : [];
                            const prodRiRaw = Array.isArray(item.production_open_histogram?.ri_values) ? item.production_open_histogram.ri_values.map(normalizeNumber) : [];
                            const prodHasData = prodLabelsRaw.length && (sumValues(prodValuesRaw) > 0 || sumValues(prodNormalRaw) > 0 || sumValues(prodRiRaw) > 0);
                            const prodLabels = prodHasData ? prodLabelsRaw : ['Total'];
                            const fallbackRi = normalizeNumber(item.cards?.returned);
                            const fallbackTotal = normalizeNumber(item.cards?.in_analysis);
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
                            const flowAssigned = flowHasData ? flowAssignedRaw : [normalizeNumber(item.cards?.in_analysis)];
                            const flowDelivered = flowHasData ? flowDeliveredRaw : [normalizeNumber(item.cards?.previous_done)];

                            const donutRawValues = Array.isArray(item.internal_return_donut?.values) ? item.internal_return_donut.values : [];
                            const donutValues = donutRawValues.length ? donutRawValues.map(normalizeNumber) : [normalizeNumber(item.cards?.returned)];
                            const donutRelations = Array.isArray(item.internal_return_donut?.relation) ? item.internal_return_donut.relation : [];
                            const donutRawLabels = Array.isArray(item.internal_return_donut?.labels) && item.internal_return_donut.labels.length ? item.internal_return_donut.labels : ['Retorno interno'];
                            const donutLabels = donutRawLabels.map((lbl, idx) => {
                                const rel = Number(donutRelations[idx] ?? 0);
                                return rel > 0 ? `${lbl} (${rel.toFixed(1)}%)` : lbl;
                            });
                            const donutColors = [
                                '#60a5fa',
                                '#34d399',
                                '#fbbf24',
                                '#f87171',
                                '#a78bfa',
                                '#22d3ee',
                                '#fb7185',
                                '#4ade80'
                            ];

                            if (queueChart) {
                                queueChart.data.labels = queueLabels.map((v) => String(v ?? ''));
                                if (!Array.isArray(queueChart.data.datasets) || !queueChart.data.datasets[0]) {
                                    queueChart.data.datasets = [{
                                        label: 'Pilha',
                                        data: [],
                                        backgroundColor: 'rgba(52, 152, 219, .55)',
                                        borderColor: '#3498db',
                                        borderWidth: 1,
                                        borderSkipped: false,
                                        categoryPercentage: 0.82,
                                        barPercentage: 0.9,
                                        maxBarThickness: 42,
                                    }];
                                }
                                queueChart.data.datasets[0].data = queueValues.map(normalizeNumber);
                                queueChart.update();
                            }

                            if (prodChart) {
                                prodChart.data.labels = prodLabels.map((v) => String(v ?? ''));
                                if (!Array.isArray(prodChart.data.datasets) || !prodChart.data.datasets[0]) {
                                    prodChart.data.datasets = [
                                        {
                                            label: 'Normal',
                                            data: [],
                                            backgroundColor: 'rgba(0, 206, 201, .65)',
                                            borderColor: '#00cec9',
                                            borderWidth: 1,
                                            borderSkipped: false,
                                            categoryPercentage: 0.82,
                                            barPercentage: 0.9,
                                            maxBarThickness: 42,
                                        },
                                        {
                                            label: 'RI',
                                            data: [],
                                            backgroundColor: 'rgba(250, 204, 21, .75)',
                                            borderColor: '#facc15',
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
                                        backgroundColor: 'rgba(250, 204, 21, .75)',
                                        borderColor: '#facc15',
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

                            if (flowChart) {
                                flowChart.data.labels = flowLabels.map((v) => String(v ?? ''));
                                if (!Array.isArray(flowChart.data.datasets) || flowChart.data.datasets.length < 2) {
                                    flowChart.data.datasets = [{
                                            label: 'Atribuído',
                                            data: [],
                                            backgroundColor: 'rgba(96,165,250,.65)',
                                            borderColor: '#60a5fa',
                                            borderWidth: 1,
                                            borderSkipped: false,
                                        },
                                        {
                                            label: 'Entregue',
                                            data: [],
                                            backgroundColor: 'rgba(34,197,94,.65)',
                                            borderColor: '#22c55e',
                                            borderWidth: 1,
                                            borderSkipped: false,
                                        },
                                    ];
                                }
                                flowChart.data.datasets[0].data = flowAssigned.map(normalizeNumber);
                                flowChart.data.datasets[1].data = flowDelivered.map(normalizeNumber);
                                flowChart.update();
                            }

                            if (donutChart) {
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
                            }

                            if (listNode) {
                                const rows = (item.recent_completed || []).map((row) => `
                                    <tr>
                                        <td>${row.note || '-'}</td>
                                        <td>${row.user_name || '-'}</td>
                                        <td>${row.company_name || '-'}</td>
                                        <td><span class="w2-tag">${row.type || '-'}</span></td>
                                        <td>${row.completed_at || '-'}</td>
                                    </tr>
                                `).join('');
                                listNode.innerHTML = rows || '<tr><td colspan="5">Sem entregas no período.</td></tr>';
                            }

                            setPointsBadge(key, 'queue_histogram', queueLabels.length, sumValues(queueValues));
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

                    if (sameScreen) {
                        grid.querySelectorAll('.w2-panel').forEach((panel) => {
                            const key = panel.dataset.key || '';
                            if (!activeKeys.has(key)) {
                                clearPanelCharts(key);
                                panel.remove();
                            }
                        });
                    }

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
                    rotateRemaining = screenDuration(currentScreen());
                    serviceRotateRemaining = serviceDuration(currentScreen());
                    render();
                    setCounters();

                    if (timer) clearInterval(timer);
                    timer = setInterval(async () => {
                        componentCountdowns.forEach((value, component) => {
                            componentCountdowns.set(component, Math.max(0, Number(value || 0) - 1));
                        });
                        rotateRemaining = Math.max(0, (rotateRemaining || 0) - 1);
                        serviceRotateRemaining = Math.max(0, (serviceRotateRemaining || 0) - 1);

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
                            await refreshActiveItemFromFull(true);
                            const secs = Number(payload.refresh_seconds || 60);
                            dueComponents.forEach((component) => componentCountdowns.set(component, secs));
                        }

                        setCounters();
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
