<div id="ads-queue-donut-card"
    data-endpoint="{{ route('ads.realtime.queue_donut') }}"
    data-filters='@json($filters ?? [])'
    wire:ignore
    class="iat-summary-card d-flex flex-column"
    style="height: 340px; overflow: hidden;">
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
            let initialChart = @json($chart ?? []);

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

            const startRealtime = () => {
                const card = document.getElementById(cardId);
                if (!card) return;

                const endpoint = card.dataset.endpoint;
                let filters = parseFilters(card.dataset.filters);
                const totalNode = document.getElementById(totalId);
                if (!endpoint) return;

                const renderNow = (chartPayload) => {
                    if (!chartPayload || typeof chartPayload !== 'object') return;
                    window.dispatchEvent(new CustomEvent(chartEvent, {
                        detail: chartPayload
                    }));
                };

                const fetchAndUpdate = async () => {
                    try {
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
                            totalNode.textContent = String(payload.total);
                        }
                        if (payload.chart) {
                            renderNow(payload.chart);
                        }
                    } catch (e) {}
                };

                renderNow(initialChart);
                fetchAndUpdate();

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
