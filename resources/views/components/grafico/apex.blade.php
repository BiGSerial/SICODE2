@props([
    'chart' => [],
    'chartId' => null,
    'class' => 'w-full h-64',
    'showDataLabels' => false,
])

@php
    $finalId = $chartId ?? 'chart_' . uniqid();
    $type = $chart['type'] ?? 'bar';
    $data = $chart['data'] ?? [];
    $options = $chart['options'] ?? [];
    $options = array_merge(
        [
            'responsive' => true,
            'maintainAspectRatio' => false,
        ],
        $options,
    );
@endphp

<canvas id="{{ $finalId }}" class="{{ $class }} w-full h-full block"></canvas>


@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    @endpush
@endonce

@push('script')
    <script>
        (function() {
        const chartId = @json($finalId);
        const showDataLabels = @json((bool) $showDataLabels);
        const eventName = 'grafico-atualizar-' + chartId;
        const genericEventName = 'chart-update';
        const initialPayload = {
            data: @json($data),
            options: @json($options),
            type: @json($type)
        };

        window.__chartJsRegistry = window.__chartJsRegistry || {};
        const registry = window.__chartJsRegistry;

        registry.instances = registry.instances || {};
        registry.payloads = registry.payloads || {};
        registry.listeners = registry.listeners || {};

        const edpChartPalette = [
            '#263CC8', '#225E66', '#E32C2C', '#F7D200',
            '#A8B1E9', '#91AFB3', '#EDD5D3', '#FFF1BE',
            '#212E3E', '#143F47', '#7C9599', '#0CD3F8'
        ];

        function hexToRgba(hex, alpha) {
            const cleaned = String(hex || '').replace('#', '').trim();
            const full = cleaned.length === 3 ?
                cleaned.split('').map((c) => c + c).join('') :
                cleaned;
            const num = parseInt(full, 16);
            if (!Number.isFinite(num)) {
                return hex;
            }
            const r = (num >> 16) & 255;
            const g = (num >> 8) & 255;
            const b = num & 255;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        function applyEdpDefaults(data, chartType) {
            if (!data || !Array.isArray(data.datasets)) {
                return data;
            }

            const radialTypes = ['pie', 'doughnut', 'polarArea'];
            data.datasets.forEach((dataset, idx) => {
                const baseColor = edpChartPalette[idx % edpChartPalette.length];
                const isRadial = radialTypes.includes(String(chartType || '').toLowerCase());

                if (isRadial && !dataset.backgroundColor && Array.isArray(dataset.data)) {
                    dataset.backgroundColor = dataset.data.map((_, itemIdx) => edpChartPalette[itemIdx % edpChartPalette.length]);
                }

                if (!dataset.borderColor) {
                    dataset.borderColor = baseColor;
                }
                if (!dataset.backgroundColor) {
                    dataset.backgroundColor = hexToRgba(baseColor, 0.25);
                }
                if (typeof dataset.borderWidth === 'undefined') {
                    dataset.borderWidth = 1;
                }
            });

            return data;
        }

        function applyDefaultDataLabelsOption(options) {
            if (!options || typeof options !== 'object') {
                return options;
            }

            options.plugins = options.plugins || {};
            options.plugins.datalabels = options.plugins.datalabels || {};

            if (typeof options.plugins.datalabels.display === 'undefined') {
                options.plugins.datalabels.display = showDataLabels;
            }

            return options;
        }

        function reviveChartFunctions(node) {
            if (Array.isArray(node)) {
                node.forEach(reviveChartFunctions);
                return;
            }

            if (!node || typeof node !== 'object') {
                return;
            }

            Object.keys(node).forEach((key) => {
                const value = node[key];

                if (value === '__VALUE_LABEL__') {
                    node[key] = function(v) {
                        const numeric = Number(v ?? 0);
                        return Number.isFinite(numeric) ? String(numeric) : '';
                    };
                    return;
                }

                if (value === '__PERCENT_LABEL__') {
                    node[key] = function(v) {
                        const numeric = Number(v ?? 0);
                        return Number.isFinite(numeric) ? `${numeric.toFixed(1)}%` : '';
                    };
                    return;
                }

                if (value === '__TOTAL_FROM_SERIES__') {
                    node[key] = function(_v, context) {
                        const cfg = context?.dataset?.datalabels?.labels?.total ?? {};
                        const totals = Array.isArray(cfg.totalSeries) ? cfg.totalSeries : [];
                        const idx = context?.dataIndex ?? -1;
                        const raw = idx >= 0 ? totals[idx] : null;
                        const numeric = Number(raw ?? 0);
                        return Number.isFinite(numeric) ? String(numeric) : '';
                    };
                    return;
                }

                if (value === '__ADS_AVG_DASHED_LEGEND__') {
                    node[key] = function(chart) {
                        const datasets = chart?.data?.datasets ?? [];
                        return datasets
                            .map((ds, i) => ({ ds, i }))
                            .filter(({ ds }) => String(ds?.label ?? '').toLowerCase().includes('média'))
                            .map(({ ds, i }) => ({
                                text: String(ds?.label ?? ''),
                                fillStyle: 'rgba(0,0,0,0)',
                                strokeStyle: ds?.borderColor ?? '#334155',
                                lineWidth: Number(ds?.borderWidth ?? 2),
                                lineDash: Array.isArray(ds?.borderDash) ? ds.borderDash : [6, 6],
                                lineCap: 'butt',
                                lineJoin: 'miter',
                                hidden: !chart.isDatasetVisible(i),
                                datasetIndex: i
                            }));
                    };
                    return;
                }

                if (value === '__ADS_MIXED_DATASET_LEGEND__') {
                    node[key] = function(chart) {
                        const datasets = chart?.data?.datasets ?? [];
                        return datasets.map((ds, i) => {
                            const type = String(ds?.type ?? chart?.config?.type ?? '').toLowerCase();
                            const isLine = type === 'line';

                            const bgRaw = Array.isArray(ds?.backgroundColor) ? (ds.backgroundColor[0] ?? 'rgba(148,163,184,0.5)') : (ds?.backgroundColor ?? 'rgba(148,163,184,0.5)');
                            const borderRaw = Array.isArray(ds?.borderColor) ? (ds.borderColor[0] ?? '#475569') : (ds?.borderColor ?? '#475569');

                            return {
                                text: String(ds?.label ?? ''),
                                fillStyle: isLine ? 'rgba(0,0,0,0)' : bgRaw,
                                strokeStyle: borderRaw,
                                lineWidth: isLine ? Number(ds?.borderWidth ?? 2) : Number(ds?.borderWidth ?? 1),
                                lineDash: isLine && Array.isArray(ds?.borderDash) ? ds.borderDash : [],
                                lineCap: 'butt',
                                lineJoin: 'miter',
                                hidden: !chart.isDatasetVisible(i),
                                datasetIndex: i
                            };
                        });
                    };
                    return;
                }

                reviveChartFunctions(value);
            });
        }

        function bindClickHandler(chart, ctx, safeOptions) {
            const clickFilter = safeOptions?.onClickFilter ?? null;
            if (!(clickFilter?.enabled)) {
                ctx.canvas.onclick = null;
                return;
            }

            ctx.canvas.onclick = function(evt) {
                if (!chart) return;

                const mode = clickFilter?.mode ?? 'nearest';
                const intersect = clickFilter?.intersect ?? true;
                const axis = clickFilter?.axis ?? undefined;
                const queryOptions = axis ? {
                    intersect,
                    axis
                } : {
                    intersect
                };

                const elements = chart.getElementsAtEventForMode(evt, mode, queryOptions, true);

                let index = elements?.length ? elements[0].index : null;

                if (index === null && clickFilter?.allowLabelFallback) {
                    const xScale = chart.scales?.x;
                    const labelsCount = chart.data?.labels?.length ?? 0;
                    const rect = ctx.canvas.getBoundingClientRect();
                    const canvasX = evt?.offsetX ?? (evt?.clientX != null ? evt.clientX - rect.left : null);
                    const canvasY = evt?.offsetY ?? (evt?.clientY != null ? evt.clientY - rect.top : null);
                    const scaleX = rect.width > 0 ? (ctx.canvas.width / rect.width) : 1;
                    const scaleY = rect.height > 0 ? (ctx.canvas.height / rect.height) : 1;
                    const x = canvasX != null ? canvasX * scaleX : null;
                    const y = canvasY != null ? canvasY * scaleY : null;

                    if (xScale && labelsCount > 0 && x !== null && y !== null) {
                        const left = Math.min(xScale.left, xScale.right);
                        const right = Math.max(xScale.left, xScale.right);
                        const chartBottom = chart.chartArea?.bottom ?? xScale.top;
                        const scaleBottom = xScale.bottom ?? chartBottom;
                        const labelTop = Math.min(chartBottom, scaleBottom) - 6;
                        const labelBottom = Math.max(scaleBottom, chartBottom) + 22;

                        const inHorizontalRange = x >= (left - 12) && x <= (right + 12);
                        const inLabelBand = y >= labelTop && y <= labelBottom;

                        if (inHorizontalRange && inLabelBand) {
                            let nearestIndex = 0;
                            let nearestDistance = Number.POSITIVE_INFINITY;

                            for (let i = 0; i < labelsCount; i++) {
                                const px = xScale.getPixelForTick(i);
                                const dist = Math.abs(px - x);
                                if (dist < nearestDistance) {
                                    nearestDistance = dist;
                                    nearestIndex = i;
                                }
                            }

                            index = nearestIndex;
                        }
                    }
                }

                if (index === null) return;

                const keys = clickFilter?.keys ?? [];
                const value = keys[index] ?? chart.data?.labels?.[index] ?? null;
                if (!value) return;

                if (clickFilter?.jsEvent) {
                    window.dispatchEvent(new CustomEvent(String(clickFilter.jsEvent), {
                        detail: {
                            value,
                            index,
                            label: chart.data?.labels?.[index] ?? null,
                            chartId
                        }
                    }));
                }

                if (clickFilter?.method) {
                    const root = ctx.canvas.closest('[wire\\:id]');
                    if (!root) return;
                    const componentId = root.getAttribute('wire:id');
                    if (!componentId) return;
                    Livewire.find(componentId).call(clickFilter.method, value);
                }
            };
        }

        function renderChart(payload) {
            if (!window.Chart) {
                return false;
            }

            const canvas = document.getElementById(chartId);
            if (!canvas) {
                return false;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return false;
            }

            try {
                const safeData = JSON.parse(JSON.stringify(payload?.data ?? {}));
                const safeOptions = JSON.parse(JSON.stringify(payload?.options ?? {}));
                reviveChartFunctions(safeData);
                reviveChartFunctions(safeOptions);
                applyEdpDefaults(safeData, payload?.type);
                applyDefaultDataLabelsOption(safeOptions);

                if (window.ChartDataLabels && window.Chart && !window.__chartDataLabelsRegistered) {
                    window.Chart.register(window.ChartDataLabels);
                    window.__chartDataLabelsRegistered = true;
                }

                const existing = registry.instances[chartId];
                if (existing) {
                    existing.config.type = payload?.type ?? existing.config.type ?? 'bar';
                    existing.config.data = safeData;
                    existing.config.options = safeOptions;
                    bindClickHandler(existing, ctx, safeOptions);
                    existing.update();
                    return true;
                }

                registry.instances[chartId] = new Chart(ctx, {
                    type: payload?.type ?? 'bar',
                    data: safeData,
                    options: safeOptions
                });

                window['chartInstance_' + chartId] = registry.instances[chartId];

                bindClickHandler(registry.instances[chartId], ctx, safeOptions);
            } catch (error) {
                return false;
            }

            return true;
        }

        function scheduleRender(payload, attempt = 0) {
            registry.payloads[chartId] = payload;
            const rendered = renderChart(payload);
            if (rendered || attempt >= 8) {
                return;
            }

            setTimeout(function() {
                scheduleRender(payload, attempt + 1);
            }, 80);
        }

        function redrawFromCache() {
            const payload = registry.payloads[chartId] || initialPayload;
            scheduleRender(payload);
        }

        if (!registry.listeners[chartId]) {
            window.addEventListener(eventName, function(e) {
                const detail = e?.detail ?? {};
                const payload = {
                    data: detail?.data ?? {},
                    options: detail?.options ?? {},
                    type: detail?.type ?? 'bar'
                };
                scheduleRender(payload);
            });
            registry.listeners[chartId] = true;
        }

        const genericListenerKey = chartId + ':generic';
        if (!registry.listeners[genericListenerKey]) {
            window.addEventListener(genericEventName, function(e) {
                const detail = e?.detail ?? {};
                const targetChartId = String(detail?.chartId ?? detail?.id ?? '');
                if (targetChartId !== chartId) {
                    return;
                }

                const source = detail?.chart ?? detail ?? {};
                const payload = {
                    data: source?.data ?? {},
                    options: source?.options ?? {},
                    type: source?.type ?? 'bar'
                };
                scheduleRender(payload);
            });
            registry.listeners[genericListenerKey] = true;
        }

        registry.payloads[chartId] = registry.payloads[chartId] || initialPayload;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                redrawFromCache();
            });
        } else {
            redrawFromCache();
        }
        })();
    </script>
@endpush
