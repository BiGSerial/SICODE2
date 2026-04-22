<div class="prod-wall" data-api-endpoint="{{ $apiEndpoint }}">
    @once
        @push('css')
            <style>
                .wall-layout-body {
                    margin: 0;
                    width: 100vw;
                    height: 100vh;
                    overflow: hidden;
                    background: #071422;
                }

                .prod-wall {
                    --pw-bg: #071422;
                    --pw-surface: #0f1f33;
                    --pw-surface-soft: #132941;
                    --pw-text: #e5edf6;
                    --pw-muted: #9fb3c8;
                    --pw-accent: #00b894;
                    --pw-danger: #ff7675;
                    position: fixed;
                    inset: 0;
                    width: 100vw;
                    height: 100vh;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    background: radial-gradient(circle at 10% 10%, rgba(0, 184, 148, .15), transparent 35%),
                        radial-gradient(circle at 90% 90%, rgba(9, 132, 227, .18), transparent 35%),
                        var(--pw-bg);
                    color: var(--pw-text);
                    padding: 1rem;
                }

                .prod-wall .topbar {
                    display: flex;
                    flex-wrap: wrap;
                    gap: .75rem;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: .75rem;
                }

                .prod-wall .title {
                    font-weight: 700;
                    letter-spacing: .06em;
                    text-transform: uppercase;
                    font-size: 1.2rem;
                    margin: 0;
                }

                .prod-wall .subtitle {
                    color: var(--pw-muted);
                    font-size: .9rem;
                }

                .prod-wall .badge-soft {
                    background: rgba(255, 255, 255, .08);
                    border: 1px solid rgba(255, 255, 255, .12);
                    border-radius: 999px;
                    padding: .35rem .75rem;
                    font-size: .82rem;
                    color: #dbe9f5;
                }

                .prod-wall .service-board {
                    flex: 1;
                    min-height: 0;
                    border: 1px solid rgba(255, 255, 255, .08);
                    border-radius: 16px;
                    background: linear-gradient(145deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
                    padding: .9rem;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, .25);
                    display: flex;
                    flex-direction: column;
                }

                .prod-wall .service-name {
                    font-size: 1.3rem;
                    font-weight: 700;
                    margin: 0;
                }

                .prod-wall .metrics {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: .65rem;
                    margin: .8rem 0;
                }

                .prod-wall .metric {
                    background: var(--pw-surface-soft);
                    border: 1px solid rgba(255, 255, 255, .08);
                    border-radius: 12px;
                    padding: .65rem .75rem;
                }

                .prod-wall .metric-label {
                    color: var(--pw-muted);
                    text-transform: uppercase;
                    font-size: .68rem;
                    letter-spacing: .06em;
                }

                .prod-wall .metric-value {
                    font-size: 1.2rem;
                    font-weight: 700;
                    margin-top: .1rem;
                }

                .prod-wall .charts-grid {
                    flex: 1;
                    min-height: 0;
                    display: grid;
                    grid-template-columns: 2fr 1fr;
                    gap: .8rem;
                }

                .prod-wall .chart-card {
                    border: 1px solid rgba(255, 255, 255, .09);
                    background: var(--pw-surface);
                    border-radius: 14px;
                    padding: .8rem;
                    display: flex;
                    flex-direction: column;
                    min-height: 0;
                }

                .prod-wall .chart-title {
                    font-size: .9rem;
                    text-transform: uppercase;
                    letter-spacing: .06em;
                    color: #cde0f2;
                    margin-bottom: .5rem;
                }

                .prod-wall .chart-wrap {
                    flex: 1;
                    min-height: 0;
                    position: relative;
                }

                .prod-wall .chart-wrap canvas {
                    width: 100% !important;
                    height: 100% !important;
                }

                .prod-wall .empty-state {
                    flex: 1;
                    border: 1px dashed rgba(255, 255, 255, .2);
                    border-radius: 12px;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    color: var(--pw-muted);
                    text-align: center;
                    padding: 1rem;
                }

                @media (max-width: 1100px) {
                    .prod-wall .metrics {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }

                    .prod-wall .charts-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        @endpush
    @endonce

    <div class="topbar">
        <div>
            <h1 class="title">Painel de Producao</h1>
            <div class="subtitle">Rotacao automatica por tipo de servico</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-soft" id="pw-updated-at">Atualizado: {{ $initialPayload['updated_at'] ?? '' }}</span>
            <span class="badge-soft">Servicos: <strong id="pw-service-count">{{ count($initialPayload['slides'] ?? []) }}</strong></span>
            <span class="badge-soft">Rotacao em: <strong id="pw-rotation-countdown">{{ $rotationSeconds }}</strong>s</span>
            <span class="badge-soft">Refresh em: <strong id="pw-refresh-countdown">{{ $refreshSeconds }}</strong>s</span>
        </div>
    </div>

    <div class="service-board" id="pw-board">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <h2 class="service-name" id="prod-wall-service-name">-</h2>
            <div class="subtitle" id="prod-wall-slide-position"></div>
        </div>

        <div class="metrics">
            <div class="metric">
                <div class="metric-label">Pilha em aberto</div>
                <div class="metric-value" id="prod-wall-open-total">0</div>
            </div>
            <div class="metric">
                <div class="metric-label">Retorno interno em aberto</div>
                <div class="metric-value" id="prod-wall-internal-open">0</div>
            </div>
            <div class="metric">
                <div class="metric-label">Percentual retorno interno</div>
                <div class="metric-value" id="prod-wall-internal-pct">0%</div>
            </div>
            <div class="metric">
                <div class="metric-label">Demais producoes abertas</div>
                <div class="metric-value" id="prod-wall-normal-open">0</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">Histograma da pilha em aberto (idade da fila)</div>
                <div class="chart-wrap">
                    <canvas id="prod-wall-histogram"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-title">Percentual de retorno interno</div>
                <div class="chart-wrap">
                    <canvas id="prod-wall-return-share"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="empty-state" id="pw-empty">
        <div>
            <h3 class="mb-2">Sem dados de servico para exibir</h3>
            <p class="mb-0">Nao foram encontrados servicos para o painel no momento.</p>
        </div>
    </div>

    <script type="application/json" id="prod-wall-data">@json($initialPayload)</script>

    @once
        @push('js')
            <script>
                (function() {
                    let histogramChart = null;
                    let shareChart = null;
                    let activeIndex = 0;
                    let loopTimer = null;

                    const rotationSeconds = {{ max(1, (int) $rotationSeconds) }};
                    const refreshSeconds = {{ max(5, (int) $refreshSeconds) }};

                    let rotationRemaining = rotationSeconds;
                    let refreshRemaining = refreshSeconds;

                    let payload = {
                        updated_at: '',
                        slides: []
                    };

                    function parseInitialPayload() {
                        const node = document.getElementById('prod-wall-data');

                        if (!node) {
                            return {
                                updated_at: '',
                                slides: []
                            };
                        }

                        try {
                            const raw = JSON.parse(node.textContent || '{}');
                            return normalizePayload(raw);
                        } catch (e) {
                            return {
                                updated_at: '',
                                slides: []
                            };
                        }
                    }

                    function normalizePayload(raw) {
                        return {
                            updated_at: raw?.updated_at || '',
                            slides: Array.isArray(raw?.slides) ? raw.slides : []
                        };
                    }

                    function setPayload(newPayload) {
                        payload = normalizePayload(newPayload);

                        const count = document.getElementById('pw-service-count');
                        const updated = document.getElementById('pw-updated-at');

                        if (count) {
                            count.textContent = String(payload.slides.length);
                        }

                        if (updated) {
                            updated.textContent = `Atualizado: ${payload.updated_at || '-'}`;
                        }

                        if (activeIndex >= payload.slides.length) {
                            activeIndex = 0;
                        }
                    }

                    function syncCounterLabels() {
                        const rotNode = document.getElementById('pw-rotation-countdown');
                        const refNode = document.getElementById('pw-refresh-countdown');

                        if (rotNode) {
                            rotNode.textContent = String(rotationRemaining);
                        }

                        if (refNode) {
                            refNode.textContent = String(refreshRemaining);
                        }
                    }

                    function ensureCharts() {
                        if (typeof Chart === 'undefined') {
                            return false;
                        }

                        const histogramCanvas = document.getElementById('prod-wall-histogram');
                        const shareCanvas = document.getElementById('prod-wall-return-share');

                        if (!histogramCanvas || !shareCanvas) {
                            return false;
                        }

                        if (!histogramChart) {
                            histogramChart = new Chart(histogramCanvas.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: [],
                                    datasets: [{
                                        label: 'Producoes abertas',
                                        data: [],
                                        backgroundColor: 'rgba(0, 184, 148, .45)',
                                        borderColor: '#00b894',
                                        borderWidth: 1,
                                        borderRadius: 8,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: {
                                        duration: 500,
                                    },
                                    plugins: {
                                        legend: {
                                            display: false,
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                precision: 0,
                                                color: '#dce8f5',
                                            },
                                            grid: {
                                                color: 'rgba(255,255,255,.12)',
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                color: '#dce8f5',
                                            },
                                            grid: {
                                                color: 'rgba(255,255,255,.06)',
                                            },
                                        },
                                    },
                                },
                            });
                        }

                        if (!shareChart) {
                            shareChart = new Chart(shareCanvas.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: ['Retorno interno', 'Demais producoes'],
                                    datasets: [{
                                        data: [0, 0],
                                        backgroundColor: ['#ff7675', '#00cec9'],
                                        borderColor: ['#ff7675', '#00cec9'],
                                        borderWidth: 1,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: {
                                        duration: 500,
                                    },
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                color: '#dce8f5',
                                            },
                                        },
                                    },
                                    cutout: '62%',
                                },
                            });
                        }

                        return true;
                    }

                    function toggleState(isEmpty) {
                        const board = document.getElementById('pw-board');
                        const empty = document.getElementById('pw-empty');

                        if (board) {
                            board.style.display = isEmpty ? 'none' : 'flex';
                        }

                        if (empty) {
                            empty.style.display = isEmpty ? 'flex' : 'none';
                        }
                    }

                    function updateText(slide, totalSlides) {
                        const serviceName = document.getElementById('prod-wall-service-name');
                        const position = document.getElementById('prod-wall-slide-position');
                        const openTotal = document.getElementById('prod-wall-open-total');
                        const internalOpen = document.getElementById('prod-wall-internal-open');
                        const internalPct = document.getElementById('prod-wall-internal-pct');
                        const normalOpen = document.getElementById('prod-wall-normal-open');

                        if (!slide || !serviceName) {
                            return;
                        }

                        serviceName.textContent = slide.service_name || '-';

                        if (position) {
                            position.textContent = `Servico ${activeIndex + 1} de ${totalSlides}`;
                        }

                        if (openTotal) {
                            openTotal.textContent = String(slide.open_total ?? 0);
                        }

                        if (internalOpen) {
                            internalOpen.textContent = String(slide.internal_return_open ?? 0);
                        }

                        if (internalPct) {
                            internalPct.textContent = `${Number(slide.internal_return_pct ?? 0).toFixed(1)}%`;
                        }

                        if (normalOpen) {
                            normalOpen.textContent = String(slide.normal_open ?? 0);
                        }
                    }

                    function updateCharts(slide) {
                        if (!slide || !ensureCharts()) {
                            return;
                        }

                        histogramChart.data.labels = slide.histogram?.labels || [];
                        histogramChart.data.datasets[0].data = slide.histogram?.values || [];
                        histogramChart.update();

                        shareChart.data.labels = slide.return_share?.labels || ['Retorno interno', 'Demais producoes'];
                        shareChart.data.datasets[0].data = slide.return_share?.values || [0, 0];
                        shareChart.update();
                    }

                    function renderCurrentSlide() {
                        if (!payload.slides.length) {
                            toggleState(true);
                            return;
                        }

                        toggleState(false);

                        if (activeIndex >= payload.slides.length) {
                            activeIndex = 0;
                        }

                        const slide = payload.slides[activeIndex];
                        updateText(slide, payload.slides.length);
                        updateCharts(slide);
                    }

                    function rotateSlide() {
                        if (payload.slides.length <= 1) {
                            return;
                        }

                        activeIndex = (activeIndex + 1) % payload.slides.length;
                        renderCurrentSlide();
                    }

                    async function fetchPayload() {
                        const root = document.querySelector('.prod-wall');
                        const endpoint = root?.dataset?.apiEndpoint;

                        if (!endpoint) {
                            return;
                        }

                        try {
                            const response = await fetch(endpoint, {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) {
                                return;
                            }

                            const json = await response.json();
                            setPayload(json);
                            renderCurrentSlide();
                        } catch (e) {
                            // Mantem painel rodando mesmo com falha temporaria na API.
                        }
                    }

                    function setupLoop() {
                        if (loopTimer) {
                            clearInterval(loopTimer);
                        }

                        loopTimer = setInterval(function() {
                            rotationRemaining -= 1;
                            refreshRemaining -= 1;

                            if (rotationRemaining <= 0) {
                                rotateSlide();
                                rotationRemaining = rotationSeconds;
                            }

                            if (refreshRemaining <= 0) {
                                fetchPayload();
                                refreshRemaining = refreshSeconds;
                            }

                            syncCounterLabels();
                        }, 1000);
                    }

                    function init() {
                        setPayload(parseInitialPayload());
                        renderCurrentSlide();
                        syncCounterLabels();
                        setupLoop();
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', init, {
                            once: true
                        });
                    } else {
                        init();
                    }
                })();
            </script>
        @endpush
    @endonce
</div>
