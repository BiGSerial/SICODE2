<div id="ads-demand-delivery-card"
    data-endpoint="{{ route('ads.realtime.demand_delivery') }}"
    data-filters='@json($filters ?? [])'
    wire:ignore
    class="iat-summary-card d-flex flex-column"
    style="min-height: 620px;">
    <div class="d-flex justify-content-end mb-2">
        <span class="badge bg-light text-dark border" id="ads-demand-delivery-next-poll-badge">02:00</span>
    </div>
    @php
        $a = (array) ($analytics ?? []);
    @endphp
    <div class="row g-2 mb-2">
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Solicitadas</div>
                <div class="fw-bold" id="ads-ana-requested">{{ (int) ($a['requested_total'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Concluídas</div>
                <div class="fw-bold" id="ads-ana-delivered">{{ (int) ($a['delivered_total'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Taxa de conclusão</div>
                <div class="fw-bold" id="ads-ana-rate">{{ number_format((float) ($a['completion_rate'] ?? 0), 1, ',', '.') }}%</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Abertas agora</div>
                <div class="fw-bold" id="ads-ana-open">{{ (int) ($a['current_open'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Atrasadas agora</div>
                <div class="fw-bold text-danger" id="ads-ana-overdue">{{ (int) ($a['current_overdue'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Média em aberto</div>
                <div class="fw-bold" id="ads-ana-backlog-avg">{{ number_format((float) ($a['backlog_avg'] ?? 0), 1, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Pico em aberto</div>
                <div class="fw-bold" id="ads-ana-backlog-peak">{{ (int) ($a['backlog_peak'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border rounded px-2 py-1 h-100">
                <div class="small text-muted">Média atrasadas</div>
                <div class="fw-bold text-danger" id="ads-ana-overdue-avg">{{ number_format((float) ($a['overdue_avg'] ?? 0), 1, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="mb-2">
        <div class="d-flex align-items-center gap-3 small text-muted mb-1">
            <span class="d-inline-flex align-items-center">
                <span style="display:inline-block;width:24px;height:0;border-top:3px solid rgba(124,58,237,0.95);margin-right:6px;"></span>
                Acumulado em aberto
            </span>
            <span class="d-inline-flex align-items-center">
                <span style="display:inline-block;width:24px;height:0;border-top:3px solid rgba(239,68,68,0.95);margin-right:6px;"></span>
                Atrasadas (&gt;24h)
            </span>
        </div>
        <div class="w-100" style="height: 230px; min-height: 230px;">
            <x-grafico.apex :chart="$lineChart" chartId="adsDemandDeliveryLineChart" class="w-100 h-100" :showDataLabels="true" />
        </div>
    </div>
    <div class="w-100 flex-grow-1" style="height: 230px; min-height: 230px;">
        <x-grafico.apex :chart="$barChart" chartId="adsDemandDeliveryBarChart" class="w-100 h-100" :showDataLabels="true" />
    </div>
</div>

@once
    @push('script')
        <script>
            (function() {
            const cardId = 'ads-demand-delivery-card';
            const lineChartEvent = 'grafico-atualizar-adsDemandDeliveryLineChart';
            const barChartEvent = 'grafico-atualizar-adsDemandDeliveryBarChart';
            const chartUpdateEvent = 'chart-update';
            const lineChartId = 'adsDemandDeliveryLineChart';
            const barChartId = 'adsDemandDeliveryBarChart';
            const pollMs = 120000;
            const pollBadgeId = 'ads-demand-delivery-next-poll-badge';
            const pollSeconds = Math.floor(pollMs / 1000);
            let initialLineChart = @json($lineChart ?? []);
            let initialBarChart = @json($barChart ?? []);
            let lastLineSignature = null;
            let lastBarSignature = null;
            let lastAnalyticsSignature = null;
            let remainingSeconds = pollSeconds;

            const parseFilters = (raw) => {
                try {
                    return JSON.parse(raw || '{}') || {};
                } catch (e) {
                    return {};
                }
            };

            const buildQuery = (filters) => {
                const p = new URLSearchParams();
                Object.entries(filters || {}).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        value.filter(v => v !== null && v !== '').forEach(v => p.append(`${key}[]`, String(v)));
                        return;
                    }
                    if (value !== null && value !== '') {
                        p.set(key, String(value));
                    }
                });
                return p.toString();
            };

            const toSignature = (payload) => {
                try {
                    return JSON.stringify(payload || {});
                } catch (e) {
                    return null;
                }
            };

            const renderCountdown = () => {
                const badge = document.getElementById(pollBadgeId);
                if (!badge) return;
                const mm = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
                const ss = String(remainingSeconds % 60).padStart(2, '0');
                badge.textContent = `${mm}:${ss}`;
            };

            const resetCountdown = () => {
                remainingSeconds = pollSeconds;
                renderCountdown();
            };

            const startCountdown = () => {
                if (window.__adsDemandDeliveryCountdownTimer) {
                    clearInterval(window.__adsDemandDeliveryCountdownTimer);
                }
                renderCountdown();
                window.__adsDemandDeliveryCountdownTimer = window.setInterval(() => {
                    if (remainingSeconds > 0) {
                        remainingSeconds -= 1;
                    }
                    renderCountdown();
                }, 1000);
            };

            const startRealtime = () => {
                const card = document.getElementById(cardId);
                if (!card) return;

                const endpoint = card.dataset.endpoint;
                let filters = parseFilters(card.dataset.filters);
                if (!endpoint) return;

                const setText = (id, value) => {
                    const node = document.getElementById(id);
                    if (node) node.textContent = String(value ?? 0);
                };

                const renderNow = (chartPayload) => {
                    if (!chartPayload || typeof chartPayload !== 'object') return;
                    const signature = toSignature(chartPayload);
                    if (signature !== null && signature === lastLineSignature) return;
                    lastLineSignature = signature;
                    window.dispatchEvent(new CustomEvent(lineChartEvent, {
                        detail: chartPayload
                    }));
                    window.dispatchEvent(new CustomEvent(chartUpdateEvent, {
                        detail: {
                            chartId: lineChartId,
                            chart: chartPayload
                        }
                    }));
                };

                const renderBarNow = (chartPayload) => {
                    if (!chartPayload || typeof chartPayload !== 'object') return;
                    const signature = toSignature(chartPayload);
                    if (signature !== null && signature === lastBarSignature) return;
                    lastBarSignature = signature;
                    window.dispatchEvent(new CustomEvent(barChartEvent, {
                        detail: chartPayload
                    }));
                    window.dispatchEvent(new CustomEvent(chartUpdateEvent, {
                        detail: {
                            chartId: barChartId,
                            chart: chartPayload
                        }
                    }));
                };

                const fetchAndUpdate = async () => {
                    try {
                        resetCountdown();
                        const query = buildQuery(filters);
                        const url = query ? `${endpoint}?${query}` : endpoint;
                        const res = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) return;
                        const payload = await res.json();
                        const analytics = payload.analytics || {};
                        const analyticsSignature = toSignature(analytics);
                        if (analyticsSignature !== null && analyticsSignature !== lastAnalyticsSignature) {
                            lastAnalyticsSignature = analyticsSignature;
                            setText('ads-ana-requested', Number(analytics.requested_total || 0));
                            setText('ads-ana-delivered', Number(analytics.delivered_total || 0));
                            setText('ads-ana-rate', `${Number(analytics.completion_rate || 0).toFixed(1).replace('.', ',')}%`);
                            setText('ads-ana-open', Number(analytics.current_open || 0));
                            setText('ads-ana-overdue', Number(analytics.current_overdue || 0));
                            setText('ads-ana-backlog-avg', Number(analytics.backlog_avg || 0).toFixed(1).replace('.', ','));
                            setText('ads-ana-backlog-peak', Number(analytics.backlog_peak || 0));
                            setText('ads-ana-overdue-avg', Number(analytics.overdue_avg || 0).toFixed(1).replace('.', ','));
                        }
                        if (payload.line_chart) {
                            renderNow(payload.line_chart);
                        }
                        if (payload.bar_chart) {
                            renderBarNow(payload.bar_chart);
                        }
                    } catch (e) {}
                };

                renderNow(initialLineChart);
                renderBarNow(initialBarChart);
                fetchAndUpdate();
                startCountdown();

                if (window.__adsDemandDeliveryPoller) {
                    clearInterval(window.__adsDemandDeliveryPoller);
                }
                window.__adsDemandDeliveryPoller = window.setInterval(fetchAndUpdate, pollMs);

                if (!window.__adsDemandDeliveryFiltersListener) {
                    window.addEventListener('ads-filters-updated', (event) => {
                        filters = event?.detail || {};
                        fetchAndUpdate();
                    });
                    window.__adsDemandDeliveryFiltersListener = true;
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startRealtime, {
                    once: true
                });
            } else {
                startRealtime();
            }

            document.addEventListener('livewire:load', startRealtime);
            })();
        </script>
    @endpush
@endonce
