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
        let chartInstance_{{ $finalId }};

        function reviveChartFunctions_{{ $finalId }}(node) {
            if (Array.isArray(node)) {
                node.forEach(reviveChartFunctions_{{ $finalId }});
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

                reviveChartFunctions_{{ $finalId }}(value);
            });
        }

        function renderChart_{{ $finalId }}(data, options, type) {
            const ctx = document.getElementById('{{ $finalId }}').getContext('2d');
            if (chartInstance_{{ $finalId }}) {
                chartInstance_{{ $finalId }}.destroy();
            }

            const safeData = JSON.parse(JSON.stringify(data ?? {}));
            const safeOptions = JSON.parse(JSON.stringify(options ?? {}));
            reviveChartFunctions_{{ $finalId }}(safeData);
            reviveChartFunctions_{{ $finalId }}(safeOptions);

            if (window.ChartDataLabels && window.Chart && !window.__chartDataLabelsRegistered) {
                window.Chart.register(window.ChartDataLabels);
                window.__chartDataLabelsRegistered = true;
            }
            chartInstance_{{ $finalId }} = new Chart(ctx, {
                type: type,
                data: safeData,
                options: safeOptions
            });

            window['chartInstance_{{ $finalId }}'] = chartInstance_{{ $finalId }};

            const clickFilter = safeOptions?.onClickFilter ?? null;
            if (clickFilter?.enabled && clickFilter?.method) {
                ctx.canvas.onclick = function(evt) {
                    const chart = window['chartInstance_{{ $finalId }}'];
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

                    // Fallback: permite clique no texto do eixo X (rótulo do mês).
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

                    const root = ctx.canvas.closest('[wire\\:id]');
                    if (!root) return;
                    const componentId = root.getAttribute('wire:id');
                    if (!componentId) return;

                    Livewire.find(componentId).call(clickFilter.method, value);
                };
            } else {
                ctx.canvas.onclick = null;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderChart_{{ $finalId }}(@json($data), @json($options),
                @json($type));
        });

        // OUVIR O EVENTO PARA ATUALIZAR O GRÁFICO
        window.addEventListener('grafico-atualizar-{{ $finalId }}', function(e) {
            // Exemplo: o backend deve disparar esse evento passando data/options/type atualizados
            renderChart_{{ $finalId }}(e.detail.data, e.detail.options, e.detail.type);
        });
    </script>
@endpush
