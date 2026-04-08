<div id="ads-queue-donut-card"
    data-endpoint="{{ route('ads.realtime.queue_donut') }}"
    data-filters='@json($filters ?? [])'
    wire:ignore
    class="iat-summary-card d-flex flex-column"
    style="height: 340px; overflow: hidden;">
    <div class="d-flex justify-content-end mb-1">
        <span class="badge bg-light text-dark border" id="ads-queue-donut-next-poll-badge">02:00</span>
    </div>
    <div class="small text-muted mb-1 text-end">Total na fila atual: <strong id="ads-queue-donut-total">{{ $total }}</strong></div>
    <div class="flex-grow-1" style="min-height: 0; overflow: hidden;">
        <div class="w-100 h-100" style="height: 285px; max-height: 285px; overflow: hidden;">
            <x-grafico.apex :chart="$chart" chartId="adsQueueStatusDonut" class="w-100 h-100" />
        </div>
    </div>
</div>

@once
    @push('script')
        <script>
            (function() {
            const cardId = 'ads-queue-donut-card';
            const totalId = 'ads-queue-donut-total';
            const chartEvent = 'grafico-atualizar-adsQueueStatusDonut';
            const chartUpdateEvent = 'chart-update';
            const chartId = 'adsQueueStatusDonut';
            const pollMs = 120000;
            const pollBadgeId = 'ads-queue-donut-next-poll-badge';
            const pollSeconds = Math.floor(pollMs / 1000);
            let initialChart = @json($chart ?? []);
            let lastChartSignature = null;
            let lastTotal = null;
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
                if (window.__adsQueueDonutCountdownTimer) {
                    clearInterval(window.__adsQueueDonutCountdownTimer);
                }
                renderCountdown();
                window.__adsQueueDonutCountdownTimer = window.setInterval(() => {
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
                const totalNode = document.getElementById(totalId);
                if (!endpoint) return;

                const renderNow = (chartPayload) => {
                    if (!chartPayload || typeof chartPayload !== 'object') return;
                    const signature = toSignature(chartPayload);
                    if (signature !== null && signature === lastChartSignature) return;
                    lastChartSignature = signature;
                    window.dispatchEvent(new CustomEvent(chartEvent, {
                        detail: chartPayload
                    }));
                    window.dispatchEvent(new CustomEvent(chartUpdateEvent, {
                        detail: {
                            chartId,
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
                        if (totalNode && typeof payload.total !== 'undefined') {
                            const nextTotal = String(payload.total);
                            if (nextTotal !== lastTotal) {
                                lastTotal = nextTotal;
                                totalNode.textContent = nextTotal;
                            }
                        }
                        if (payload.chart) {
                            renderNow(payload.chart);
                        }
                    } catch (e) {}
                };

                renderNow(initialChart);
                fetchAndUpdate();
                startCountdown();

                if (window.__adsQueueDonutPoller) {
                    clearInterval(window.__adsQueueDonutPoller);
                }
                window.__adsQueueDonutPoller = window.setInterval(fetchAndUpdate, pollMs);

                if (!window.__adsQueueDonutFiltersListener) {
                    window.addEventListener('ads-filters-updated', (event) => {
                        filters = event?.detail || {};
                        fetchAndUpdate();
                    });
                    window.__adsQueueDonutFiltersListener = true;
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
