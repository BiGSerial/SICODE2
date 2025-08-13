<div wire:poll.2000ms="updateSystemStatus">
    <style>
        /* Grid fluido: adapta à largura real do container (inclusive col-4) */
        .auto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .75rem;
        }

        /* Para contêineres muito estreitos, permita colunas ainda menores */
        @container (min-width: 0px)

            {

            /* será ignorado onde não houver container queries */
            .auto-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        /* Fallback via media query para casos sem container queries */
        @media (max-width: 420px) {
            .auto-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards mais compactos quando o espaço é curto */
        .kpi-card .card-body {
            padding: .75rem;
        }

        .kpi-card .progress {
            height: 8px;
        }

        /* Tabela com scroll suave quando estreito */
        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-wrap table {
            min-width: 420px;
        }
    </style>

    <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-activity me-2"></i>Status do Servidor
            </h6>
            <span class="badge text-bg-light">Uptime: {{ $uptimeHuman }}</span>
        </div>

        <div class="card-body">
            {{-- KPIs em grid fluido --}}
            <div class="auto-grid">
                {{-- CPU --}}
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">CPU</div>
                            <span class="badge {{ $this->badgeClass($cpuUsage) }}">{{ $cpuUsage }}%</span>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar {{ $this->barClass($cpuUsage) }}"
                                style="width: {{ $cpuUsage }}%;"></div>
                        </div>
                        <div class="mt-2 text-muted small">
                            Núcleos: <strong>{{ $cpuCores }}</strong>
                            @if (!is_null($cpuTempC))
                                <span class="ms-2">• Temp: <strong>{{ $cpuTempC }}°C</strong></span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Memória --}}
                @php $memPct = $memTotal>0 ? round(($memUsed/max(1,$memTotal))*100,1) : 0; @endphp
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">Memória</div>
                            <span class="badge {{ $this->badgeClass($memPct) }}">{{ $memPct }}%</span>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar {{ $this->barClass($memPct) }}"
                                style="width: {{ $memPct }}%;"></div>
                        </div>
                        <div class="mt-2 text-muted small">
                            Usada: <strong>{{ $memUsed }} MB</strong> • Total: <strong>{{ $memTotal }}
                                MB</strong><br>
                            Buff/Cached/SRecl: <strong>{{ $memBuffers }} / {{ $memCached }} /
                                {{ $memSReclaim }} MB</strong>
                        </div>
                    </div>
                </div>

                {{-- Swap --}}
                @php $swapPct = $swapTotal>0 ? round(($swapUsed/max(1,$swapTotal))*100,1) : 0; @endphp
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">Swap</div>
                            <span class="badge {{ $this->badgeClass($swapPct) }}">{{ $swapPct }}%</span>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar {{ $this->barClass($swapPct) }}"
                                style="width: {{ $swapPct }}%;"></div>
                        </div>
                        <div class="mt-2 text-muted small">
                            Usada: <strong>{{ $swapUsed }} MB</strong> • Livre: <strong>{{ $swapFree }}
                                MB</strong>
                        </div>
                    </div>
                </div>

                {{-- Carga --}}
                <div class="card shadow-sm kpi-card">
                    <div class="card-body">
                        <div class="fw-semibold mb-1">Carga</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge {{ $this->loadBadge($load['1min']) }}">1m: {{ $load['1min'] }}</span>
                            <span class="badge {{ $this->loadBadge($load['5min']) }}">5m: {{ $load['5min'] }}</span>
                            <span class="badge {{ $this->loadBadge($load['15min']) }}">15m:
                                {{ $load['15min'] }}</span>
                        </div>
                        <div class="small text-muted mt-2">Ideal ≲ núcleos ({{ $cpuCores }}).</div>
                    </div>
                </div>
            </div>

            {{-- Discos em grid fluido --}}
            <div class="mt-4">
                <h6 class="text-uppercase text-muted mb-2">Discos</h6>
                <div class="auto-grid">
                    @forelse($disks as $d)
                        @php $pct = $d['used_pct']; @endphp
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-semibold">{{ $d['mount'] }}</div>
                                    <span class="badge {{ $this->badgeClass($pct) }}">{{ $pct }}%</span>
                                </div>
                                <div class="progress mt-2" style="height:8px;">
                                    <div class="progress-bar {{ $this->barClass($pct) }}"
                                        style="width: {{ $pct }}%;"></div>
                                </div>
                                <div class="small text-muted mt-2">
                                    FS: <strong>{{ $d['fs'] }}</strong><br>
                                    Usado: <strong>{{ $d['used'] }}</strong> • Livre:
                                    <strong>{{ $d['free'] }}</strong> • Total: <strong>{{ $d['total'] }}</strong>
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

            {{-- Top Processos (com scroll quando estreito) --}}
            <div class="mt-4">
                <h6 class="text-uppercase text-muted mb-2">Top Processos por CPU</h6>
                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>PID</th>
                                <th>Comando</th>
                                <th class="text-end">%CPU</th>
                                <th class="text-end">%MEM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProcs as $p)
                                <tr>
                                    <td>{{ $p['pid'] }}</td>
                                    <td class="text-truncate" style="max-width: 280px;" title="{{ $p['cmd'] }}">
                                        {{ $p['cmd'] }}</td>
                                    <td class="text-end">{{ number_format($p['cpu'], 1) }}</td>
                                    <td class="text-end">{{ number_format($p['mem'], 1) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center">Sem dados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
</div>
