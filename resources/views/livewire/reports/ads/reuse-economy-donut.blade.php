<div id="ads-reuse-economy-donut-card"
    data-endpoint="{{ route('ads.realtime.reuse_economy_donut') }}"
    data-filters='@json($filters ?? [])'
    wire:ignore
    class="iat-summary-card d-flex flex-column"
    style="height: 340px; overflow: hidden;">
    <div class="d-flex justify-content-end mb-1">
        <span class="badge bg-light text-dark border" id="ads-reuse-economy-next-poll-badge">02:00</span>
    </div>
    <div class="small text-muted mb-1 text-end">
        Economia no período: <strong id="ads-reuse-economy-rate">{{ number_format($reuseRate, 1, ',', '.') }}%</strong>
    </div>
    <div class="flex-grow-1" style="min-height: 0; overflow: hidden;">
        <div class="w-100 h-100" style="height: 285px; max-height: 285px; overflow: hidden;">
            <x-grafico.apex :chart="$chart" chartId="adsReuseEconomyDonut" class="w-100 h-100" />
        </div>
    </div>
</div>

@once
    @push('script')
        <script>
            (function() {
                const cardId = 'ads-reuse-economy-donut-card';
                const rateId = 'ads-reuse-economy-rate';
                const reusedId = 'ads-reuse-economy-reused';
                const queuedId = 'ads-reuse-economy-queued';
                const chartEvent = 'grafico-atualizar-adsReuseEconomyDonut';
                const chartUpdateEvent = 'chart-update';
                const chartId = 'adsReuseEconomyDonut';
                const pollMs = 120000;
                const pollBadgeId = 'ads-reuse-economy-next-poll-badge';
                const pollSeconds = Math.floor(pollMs / 1000);
                let initialChart = @json($chart ?? []);
                let lastChartSignature = null;
                let lastRate = null;
                let lastReused = null;
                let lastQueued = null;
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
                    if (window.__adsReuseEconomyCountdownTimer) {
                        clearInterval(window.__adsReuseEconomyCountdownTimer);
                    }
                    renderCountdown();
                    window.__adsReuseEconomyCountdownTimer = window.setInterval(() => {
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
                    const rateNode = document.getElementById(rateId);
                    const reusedNode = document.getElementById(reusedId);
                    const queuedNode = document.getElementById(queuedId);
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
                            if (rateNode && typeof payload.reuse_rate !== 'undefined') {
                                const nextRate = `${Number(payload.reuse_rate).toFixed(1).replace('.', ',')}%`;
                                if (nextRate !== lastRate) {
                                    lastRate = nextRate;
                                    rateNode.textContent = nextRate;
                                }
                            }
                            if (reusedNode && typeof payload.reused !== 'undefined') {
                                const nextReused = String(payload.reused);
                                if (nextReused !== lastReused) {
                                    lastReused = nextReused;
                                    reusedNode.textContent = nextReused;
                                }
                            }
                            if (queuedNode && typeof payload.queued !== 'undefined') {
                                const nextQueued = String(payload.queued);
                                if (nextQueued !== lastQueued) {
                                    lastQueued = nextQueued;
                                    queuedNode.textContent = nextQueued;
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

                    if (window.__adsReuseEconomyDonutPoller) {
                        clearInterval(window.__adsReuseEconomyDonutPoller);
                    }
                    window.__adsReuseEconomyDonutPoller = window.setInterval(fetchAndUpdate, pollMs);

                    if (!window.__adsReuseEconomyDonutFiltersListener) {
                        window.addEventListener('ads-filters-updated', (event) => {
                            filters = event?.detail || {};
                            fetchAndUpdate();
                        });
                        window.__adsReuseEconomyDonutFiltersListener = true;
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
