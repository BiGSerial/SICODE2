<div class="sys-specs-page" wire:poll.2000ms="updateSystemStatus">
    <style>
        .sys-specs-page {
            --sp-bg: #f6f7fb;
            --sp-surface: #ffffff;
            --sp-ink: #1f2933;
            --sp-muted: #6b7280;
            --sp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--sp-bg);
            padding: 1.5rem 0;
        }

        .sys-specs-page .page-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .sys-specs-page .page-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .sys-specs-page .page-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        /* Grid fluido: adapta à largura real do container (inclusive col-4) */
        .sys-specs-page .auto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .75rem;
        }

        .sys-specs-page .disks-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        /* Para contêineres muito estreitos, permita colunas ainda menores */
        @@container (min-width: 0px)

            {

            /* será ignorado onde não houver container queries */
            .sys-specs-page .auto-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        /* Fallback via media query para casos sem container queries */
        @media (max-width: 420px) {
            .sys-specs-page .auto-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1600px) {
            .sys-specs-page .disks-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (max-width: 1400px) {
            .sys-specs-page .disks-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .sys-specs-page .disks-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sys-specs-page .disks-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .sys-specs-page .disks-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards mais compactos quando o espaço é curto */
        .sys-specs-page .kpi-card .card-body {
            padding: .75rem;
        }

        .sys-specs-page .kpi-head {
            font-size: 1rem;
            font-weight: 600;
        }

        .sys-specs-page .ring-metric {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            position: relative;
            background: conic-gradient(var(--ring-color) var(--ring-value), #d9e2f0 0);
            flex-shrink: 0;
        }

        .sys-specs-page .ring-metric::after {
            content: "";
            position: absolute;
            inset: 14px;
            background: #fff;
            border-radius: 50%;
            z-index: 1;
        }

        .sys-specs-page .ring-center {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            line-height: 1.05;
        }

        .sys-specs-page .ring-center strong {
            font-size: 1.9rem;
            font-weight: 700;
            color: #111827;
        }

        .sys-specs-page .kpi-card .progress {
            height: 8px;
        }

        .sys-specs-page .process-card .progress {
            height: 8px;
        }

        .sys-specs-page .process-card .proc-title {
            font-size: 0.92rem;
            font-weight: 600;
            color: #111827;
        }

        .sys-specs-page .process-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .sys-specs-page .process-table {
            table-layout: fixed;
            width: 100%;
            min-width: 760px;
        }

        .sys-specs-page .process-cpu-cell .progress {
            height: 8px;
        }
    </style>

    <div class="container-fluid">
        @php
            $sysspecsUid = 'sysspecs_' . str_replace('.', '_', $this->id);
            $payloadId = $sysspecsUid . '_payload';
            $usageCanvasId = $sysspecsUid . '_usage_chart';
            $loadCanvasId = $sysspecsUid . '_load_chart';
        @endphp

        @php
            $sysSpecsRealtimePayload = e(json_encode([
                'labels' => $chartLabels,
                'cpu' => $cpuSeries,
                'memory' => $memorySeries,
                'swap' => $swapSeries,
                'load' => $loadSeries,
                'maxLoadScale' => max(1, round($cpuCores * 1.5, 1)),
                'pointLimit' => $maxPoints,
            ]));
        @endphp
        <div id="{{ $payloadId }}" class="d-none" data-payload="{{ $sysSpecsRealtimePayload }}"></div>

        <div class="page-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>Status do Servidor</h2>
                <div class="meta">Monitoramento de recursos e processos da instancia</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Uptime atual</div>
                <div><strong>{{ $uptimeHuman }}</strong></div>
            </div>
        </div>

        <div>
            {{-- KPIs em grid fluido --}}
            <div class="auto-grid">
                {{-- CPU --}}
                @php
                    $cpuPct = max(0, min(100, round($cpuUsage, 1)));
                @endphp
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="kpi-head">CPU</div>
                            <span class="badge {{ $this->badgeClass($cpuUsage) }}">{{ $cpuUsage }}%</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="ring-metric" style="--ring-value: {{ $cpuPct }}%; --ring-color: #2563eb;">
                                <div class="ring-center">
                                    <strong>{{ (int) round($cpuPct) }}%</strong>
                                </div>
                            </div>
                            <div class="small text-muted">
                                Núcleos: <strong>{{ $cpuCores }}</strong><br>
                                @if (!is_null($cpuTempC))
                                    Temp: <strong>{{ $cpuTempC }}°C</strong><br>
                                @endif
                                Uso atual da CPU no host.
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar {{ $this->barClass($cpuUsage) }}" style="width: {{ $cpuPct }}%;"></div>
                        </div>
                    </div>
                </div>

                {{-- Memória --}}
                @php $memPct = $memTotal>0 ? round(($memUsed/max(1,$memTotal))*100,1) : 0; @endphp
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="kpi-head">Memória</div>
                            <span class="badge {{ $this->badgeClass($memPct) }}">{{ $memPct }}%</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="ring-metric" style="--ring-value: {{ $memPct }}%; --ring-color: #0d9488;">
                                <div class="ring-center">
                                    <strong>{{ (int) round($memPct) }}%</strong>
                                </div>
                            </div>
                            <div class="small text-muted">
                                Usada: <strong>{{ $memUsed }} MB</strong><br>
                                Total: <strong>{{ $memTotal }} MB</strong><br>
                                Buff/Cached/SRecl: <strong>{{ $memBuffers }} / {{ $memCached }} / {{ $memSReclaim }} MB</strong>
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar {{ $this->barClass($memPct) }}" style="width: {{ $memPct }}%;"></div>
                        </div>
                    </div>
                </div>

                {{-- Swap --}}
                @php $swapPct = $swapTotal>0 ? round(($swapUsed/max(1,$swapTotal))*100,1) : 0; @endphp
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="kpi-head">Swap</div>
                            <span class="badge {{ $this->badgeClass($swapPct) }}">{{ $swapPct }}%</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="ring-metric" style="--ring-value: {{ $swapPct }}%; --ring-color: #d97706;">
                                <div class="ring-center">
                                    <strong>{{ (int) round($swapPct) }}%</strong>
                                </div>
                            </div>
                            <div class="small text-muted">
                                Usada: <strong>{{ $swapUsed }} MB</strong><br>
                                Livre: <strong>{{ $swapFree }} MB</strong><br>
                                Total: <strong>{{ $swapTotal }} MB</strong>
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar {{ $this->barClass($swapPct) }}" style="width: {{ $swapPct }}%;"></div>
                        </div>
                    </div>
                </div>

                {{-- Carga --}}
                @php
                    $loadPct = $cpuCores > 0 ? round((($load['1min'] ?? 0) / $cpuCores) * 100, 1) : 0;
                    $loadPctChart = max(0, min(100, $loadPct));
                @endphp
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="kpi-head">Carga</div>
                            <span class="badge {{ $this->badgeClass($loadPct) }}">{{ $loadPct }}%</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="ring-metric" style="--ring-value: {{ $loadPctChart }}%; --ring-color: #7c3aed;">
                                <div class="ring-center">
                                    <strong>{{ (int) round($loadPctChart) }}%</strong>
                                </div>
                            </div>
                            <div class="small text-muted">
                                1m: <strong>{{ $load['1min'] }}</strong><br>
                                5m: <strong>{{ $load['5min'] }}</strong><br>
                                15m: <strong>{{ $load['15min'] }}</strong><br>
                                Ideal até ~<strong>{{ $cpuCores }}</strong>.
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar {{ $this->barClass($loadPctChart) }}" style="width: {{ $loadPctChart }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h6 class="text-uppercase text-muted mb-2">Graficos em tempo real</h6>
                <div class="row g-3">
                    <div class="col-12 col-xl-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3">Uso (%) CPU, Memoria e Swap</h6>
                                <div style="height: 280px;">
                                    <canvas id="{{ $usageCanvasId }}" wire:ignore></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3">Carga (load 1m)</h6>
                                <div style="height: 280px;">
                                    <canvas id="{{ $loadCanvasId }}" wire:ignore></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Discos em grid fluido --}}
            <div class="mt-4">
                <h6 class="text-uppercase text-muted mb-2">Discos</h6>
                <div class="disks-grid">
                    @forelse($disks as $d)
                        @php $pct = $d['used_pct']; @endphp
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-semibold">{{ $d['mount'] }}</div>
                                    <span class="badge {{ $this->badgeClass($pct) }}">{{ $pct }}%</span>
                                </div>
                                <div class="d-flex align-items-center gap-3 mt-2">
                                    <div class="ring-metric" style="--ring-value: {{ $pct }}%; --ring-color: #1d4ed8;">
                                        <div class="ring-center">
                                            <strong>{{ (int) round($pct) }}%</strong>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        FS: <strong>{{ $d['fs'] }}</strong><br>
                                        Usado: <strong>{{ $d['used'] }}</strong><br>
                                        Livre: <strong>{{ $d['free'] }}</strong><br>
                                        Total: <strong>{{ $d['total'] }}</strong>
                                    </div>
                                </div>
                                <div class="progress mt-3" style="height:8px;">
                                    <div class="progress-bar {{ $this->barClass($pct) }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="card shadow-sm">
                            <div class="card-body small text-muted">Sem dados de disco.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Top Processos --}}
            <div class="mt-4">
                <h6 class="text-uppercase text-muted mb-2">Top Processos por CPU</h6>
                <div class="card shadow-sm">
                    <div class="process-table-wrap">
                        <table class="table table-sm align-middle mb-0 process-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">PID</th>
                                    <th>Comando</th>
                                    <th style="width: 270px;">Uso de CPU</th>
                                    <th style="width: 110px;" class="text-end">%MEM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProcs as $p)
                                    @php $cpuProc = max(0, min(100, (float) $p['cpu'])); @endphp
                                    <tr>
                                        <td><span class="badge text-bg-secondary">{{ $p['pid'] }}</span></td>
                                        <td class="text-truncate" style="max-width: 320px;" title="{{ $p['cmd'] }}">
                                            {{ $p['cmd'] }}
                                        </td>
                                        <td class="process-cpu-cell">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>%CPU</span>
                                                <span>{{ number_format($p['cpu'], 1) }}%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" style="width: {{ $cpuProc }}%;"></div>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ number_format($p['mem'], 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center">Sem dados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="small text-muted mt-2">* Atualiza a cada 2s.</div>
            </div>

            {{-- Processo PHP --}}
            <div class="mt-4">
                <h6 class="text-uppercase text-muted mb-2">Processo PHP Atual</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-secondary">Mem usada: {{ $phpMemUsed }} MB</span>
                    <span class="badge text-bg-secondary">Mem pico: {{ $phpMemPeak }} MB</span>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            (function() {
                const uid = @json($sysspecsUid);
                const payloadId = @json($payloadId);
                const usageCanvasId = @json($usageCanvasId);
                const loadCanvasId = @json($loadCanvasId);
                const bootKey = '__sysSpecsRealtimeBooted_' + uid;

                if (window[bootKey]) {
                    return;
                }
                window[bootKey] = true;

                window.sysSpecsCharts = window.sysSpecsCharts || {};
                window.sysSpecsCharts[uid] = window.sysSpecsCharts[uid] || { usage: null, load: null };

                function getPayloadFromDom() {
                    const el = document.getElementById(payloadId);
                    if (!el) return null;
                    const raw = el.getAttribute('data-payload');
                    if (!raw) return null;
                    try {
                        return JSON.parse(raw);
                    } catch (e) {
                        return null;
                    }
                }

                function createOrUpdateChart(chartKey, canvasId, configFactory) {
                    const canvas = document.getElementById(canvasId);
                    if (!canvas) return;

                    const existing = window.sysSpecsCharts[uid][chartKey];
                    if (existing && existing.canvas !== canvas) {
                        existing.destroy();
                        window.sysSpecsCharts[uid][chartKey] = null;
                    }

                    if (!window.sysSpecsCharts[uid][chartKey]) {
                        window.sysSpecsCharts[uid][chartKey] = new Chart(canvas.getContext('2d'), configFactory());
                    }
                }

                function normalizePayload(payload) {
                    const pointLimit = Math.max(5, parseInt(payload?.pointLimit ?? 30, 10));
                    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
                    const start = Math.max(0, labels.length - pointLimit);

                    return {
                        labels: labels.slice(start),
                        cpu: (Array.isArray(payload?.cpu) ? payload.cpu : []).slice(start),
                        memory: (Array.isArray(payload?.memory) ? payload.memory : []).slice(start),
                        swap: (Array.isArray(payload?.swap) ? payload.swap : []).slice(start),
                        load: (Array.isArray(payload?.load) ? payload.load : []).slice(start),
                        maxLoadScale: payload?.maxLoadScale || 1,
                        pointLimit: pointLimit,
                    };
                }

                function renderRealtimeCharts(payload) {
                    if (!payload || !window.Chart) return;
                    payload = normalizePayload(payload);

                    createOrUpdateChart('usage', usageCanvasId, () => ({
                        type: 'line',
                        data: {
                            labels: payload.labels || [],
                            datasets: [{
                                    label: 'CPU %',
                                    data: payload.cpu || [],
                                    borderColor: '#0d6efd',
                                    backgroundColor: 'rgba(13,110,253,0.15)',
                                    tension: 0.3,
                                    pointRadius: 0,
                                    borderWidth: 2
                                },
                                {
                                    label: 'Memoria %',
                                    data: payload.memory || [],
                                    borderColor: '#20c997',
                                    backgroundColor: 'rgba(32,201,151,0.15)',
                                    tension: 0.3,
                                    pointRadius: 0,
                                    borderWidth: 2
                                },
                                {
                                    label: 'Swap %',
                                    data: payload.swap || [],
                                    borderColor: '#ffc107',
                                    backgroundColor: 'rgba(255,193,7,0.15)',
                                    tension: 0.3,
                                    pointRadius: 0,
                                    borderWidth: 2
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            scales: {
                                y: {
                                    min: 0,
                                    max: 100
                                }
                            }
                        }
                    }));

                    createOrUpdateChart('load', loadCanvasId, () => ({
                        type: 'line',
                        data: {
                            labels: payload.labels || [],
                            datasets: [{
                                label: 'Load 1m',
                                data: payload.load || [],
                                borderColor: '#6f42c1',
                                backgroundColor: 'rgba(111,66,193,0.15)',
                                tension: 0.3,
                                pointRadius: 0,
                                borderWidth: 2,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            scales: {
                                y: {
                                    min: 0,
                                    max: payload.maxLoadScale || 1
                                }
                            }
                        }
                    }));

                    const usage = window.sysSpecsCharts[uid].usage;
                    if (usage) {
                        usage.data.labels = payload.labels || [];
                        usage.data.datasets[0].data = payload.cpu || [];
                        usage.data.datasets[1].data = payload.memory || [];
                        usage.data.datasets[2].data = payload.swap || [];
                        usage.update('none');
                    }

                    const load = window.sysSpecsCharts[uid].load;
                    if (load) {
                        load.data.labels = payload.labels || [];
                        load.data.datasets[0].data = payload.load || [];
                        load.options.scales.y.max = payload.maxLoadScale || 1;
                        load.update('none');
                    }
                }

                function renderFromDom() {
                    const payload = getPayloadFromDom();
                    if (payload) {
                        renderRealtimeCharts(payload);
                    }
                }

                document.addEventListener('DOMContentLoaded', renderFromDom);
                document.addEventListener('livewire:load', renderFromDom);
                document.addEventListener('livewire:navigated', renderFromDom);

                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('message.processed', function() {
                        renderFromDom();
                    });
                }

                window.addEventListener('config-sysspecs-realtime', function(event) {
                    renderRealtimeCharts(event.detail || {});
                });

                const payloadEl = document.getElementById(payloadId);
                if (payloadEl && window.MutationObserver) {
                    const observer = new MutationObserver(function() {
                        renderFromDom();
                    });
                    observer.observe(payloadEl, {
                        attributes: true,
                        attributeFilter: ['data-payload']
                    });
                }

                renderFromDom();
            })();
        </script>
    @endpush
</div>
