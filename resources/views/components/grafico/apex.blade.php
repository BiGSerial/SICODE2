@props([
    'chart' => [],
    'chartId' => null,
    'class' => 'w-full h-64',
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
        const eventName = 'grafico-atualizar-' + chartId;
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
