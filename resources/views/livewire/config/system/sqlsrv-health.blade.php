<div>
    <style>
        .sqlsrv-page {
            color: #0f172a;
        }

        .sqlsrv-hero {
            background:
                radial-gradient(circle at 85% 15%, rgba(34, 197, 94, .22), transparent 28%),
                linear-gradient(135deg, #0f172a 0%, #114e50 55%, #0f766e 100%);
            border-radius: 18px;
            color: #fff;
            padding: 30px 36px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, .18);
        }

        .sqlsrv-shell {
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(203, 213, 225, .75);
            border-radius: 16px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .10);
        }

        .sqlsrv-tabs {
            background: #eef5f4;
            border-radius: 12px;
            display: inline-flex;
            gap: 6px;
            padding: 6px;
        }

        .sqlsrv-tab {
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #31545a;
            font-weight: 700;
            padding: 10px 16px;
        }

        .sqlsrv-tab.active {
            background: #fff;
            color: #0f766e;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
        }

        .sqlsrv-card {
            background: #fff;
            border: 1px solid #dce7ee;
            border-radius: 14px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .07);
        }

        .sqlsrv-metric {
            min-height: 118px;
            padding: 18px;
        }

        .sqlsrv-metric span {
            color: #64748b;
            display: block;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sqlsrv-metric strong {
            display: block;
            font-size: 2rem;
            line-height: 1.1;
            margin-top: 8px;
        }

        .sqlsrv-status-pill {
            border-radius: 999px;
            display: inline-flex;
            font-weight: 800;
            gap: 8px;
            padding: 9px 14px;
        }

        .sqlsrv-status-ok {
            background: #dcfce7;
            color: #166534;
        }

        .sqlsrv-status-alert {
            background: #fee2e2;
            color: #991b1b;
        }

        .sqlsrv-chart {
            height: 285px;
        }

        .sqlsrv-scroll-list {
            max-height: 360px;
            overflow-y: auto;
            padding-right: 6px;
        }

        .sqlsrv-scroll-list thead th {
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .sqlsrv-table th {
            color: #64748b;
            font-size: .74rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sqlsrv-soft-label {
            background: #e0f2fe;
            border-radius: 999px;
            color: #075985;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 700;
            padding: 5px 9px;
        }

        .sqlsrv-date-alert td {
            background: #fff1f2;
            color: #991b1b;
        }

        .sqlsrv-date-alert .fw-semibold {
            color: #991b1b;
        }

        .sqlsrv-process-grid {
            max-height: 430px;
            overflow-y: auto;
        }

        .sqlsrv-row-failed td,
        .sqlsrv-row-missing td,
        .sqlsrv-row-late td {
            background: #fff1f2;
        }

        .sqlsrv-row-pending td {
            background: #f8fafc;
        }

        .sqlsrv-row-success td {
            background: #f0fdf4;
        }
    </style>

    <div class="sqlsrv-page">
        <div class="sqlsrv-hero mb-4 d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div>
                <div class="text-uppercase small opacity-75 fw-semibold">SQL Server / sqlsrv1</div>
                <h1 class="h2 fw-bold mb-2">Saúde das Atualizações</h1>
                <p class="mb-0 opacity-75">Acompanhamento das cargas do SICODE com visão executiva e trilha técnica.</p>
            </div>
            <div class="text-xl-end">
                <div class="small opacity-75">Última coleta</div>
                <div class="fs-5 fw-bold">
                    {{ $kpis['last_collected_at'] ? $kpis['last_collected_at']->format('d/m/Y H:i') : 'Sem coleta' }}
                </div>
                <span class="badge bg-light text-dark mt-2">{{ strtoupper($kpis['last_status']) }}</span>
            </div>
        </div>

        <div class="sqlsrv-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 align-items-xl-end">
                <div>
                    <div class="sqlsrv-tabs">
                        <button type="button" class="sqlsrv-tab {{ $activeTab === 'executive' ? 'active' : '' }}" wire:click="setActiveTab('executive')">
                            Resumo executivo
                        </button>
                        <button type="button" class="sqlsrv-tab {{ $activeTab === 'technical' ? 'active' : '' }}" wire:click="setActiveTab('technical')">
                            Técnico
                        </button>
                    </div>
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-md-auto">
                        <label class="form-label small text-muted fw-semibold">Período</label>
                        <select class="form-select" wire:model="periodDays">
                            <option value="1">Últimas 24h</option>
                            <option value="3">Últimos 3 dias</option>
                            <option value="7">Últimos 7 dias</option>
                            <option value="15">Últimos 15 dias</option>
                            <option value="30">Últimos 30 dias</option>
                        </select>
                    </div>
                    @if ($activeTab === 'technical')
                        <div class="col-md-auto">
                            <label class="form-label small text-muted fw-semibold">Base</label>
                            <select class="form-select" wire:model="selectedSource">
                                @foreach ($sourceOptions as $source)
                                    <option value="{{ $source }}">{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label class="form-label small text-muted fw-semibold">Logs</label>
                            <select class="form-select" wire:model="statusFilter">
                                <option value="">Todos</option>
                                <option value="error">Somente falhas</option>
                                <option value="ok">Somente sucesso</option>
                            </select>
                        </div>
                    @endif
                    @can('superadm')
                        <div class="col-md-auto d-grid">
                            <button type="button" class="btn btn-outline-primary" wire:click="runCollectNow" wire:loading.attr="disabled">
                                <i class="ri-refresh-line"></i> Coletar agora
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        @if ($activeTab === 'executive')
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Status do processo hoje</span>
                        <strong>{{ ($pipelineSummary['failed'] + $pipelineSummary['missing'] + $pipelineSummary['usr_late']) === 0 ? 'Saudável' : 'Atenção' }}</strong>
                        <div class="mt-2">
                            <span class="sqlsrv-status-pill {{ ($pipelineSummary['failed'] + $pipelineSummary['missing'] + $pipelineSummary['usr_late']) === 0 ? 'sqlsrv-status-ok' : 'sqlsrv-status-alert' }}">
                                {{ $pipelineSummary['failed'] + $pipelineSummary['missing'] + $pipelineSummary['usr_late'] }} ponto(s) críticos
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Previstas até agora</span>
                        <strong>{{ $pipelineSummary['due'] }}</strong>
                        <div class="text-muted mt-2">{{ $pipelineSummary['success'] }} robôs executaram</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Falhas ou ausências</span>
                        <strong>{{ $pipelineSummary['failed'] + $pipelineSummary['missing'] }}</strong>
                        <div class="text-muted mt-2">{{ $pipelineSummary['failed'] }} falhou, {{ $pipelineSummary['missing'] }} não executou</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Próximas cargas</span>
                        <strong>{{ $pipelineSummary['pending'] }}</strong>
                        <div class="text-muted mt-2">Ainda não chegaram no horário previsto</div>
                    </div>
                </div>
            </div>

            <div id="sqlsrv-process-chart-data"
                data-process='@json($processChartPayload)'></div>

            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h6 fw-bold mb-3">Robôs previstos hoje</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvProcessStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h6 fw-bold mb-3">Resultado nas bases _usr_</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvProcessUsrChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h6 fw-bold mb-3">Leitura executiva</h2>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>Robô falhou</span>
                            <strong class="text-danger">{{ $pipelineSummary['failed'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>Robô não executou no prazo</span>
                            <strong class="text-danger">{{ $pipelineSummary['missing'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>Robô executou, mas _usr_ atrasou</span>
                            <strong class="text-danger">{{ $pipelineSummary['usr_late'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Aguardando horário</span>
                            <strong>{{ $pipelineSummary['pending'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sqlsrv-card p-4 mb-4">
                <h2 class="h5 fw-bold mb-1">Mapa do processo esperado hoje</h2>
                <p class="text-muted small mb-3">RPA executa a extração, a procedure alimenta as tabelas e o resultado final esperado é a base `_usr_` atualizada.</p>
                <div class="table-responsive sqlsrv-process-grid">
                    <table class="table table-sm align-middle sqlsrv-table">
                        <thead>
                            <tr>
                                <th>Previsto</th>
                                <th>Robô</th>
                                <th>Máquina</th>
                                <th>Resultado RPA</th>
                                <th>Base _usr_</th>
                                <th>Última _usr_</th>
                                <th>Continuidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expectedRuns as $run)
                                @php
                                    $rowClass = match (true) {
                                        $run['status'] === 'failed' => 'sqlsrv-row-failed',
                                        $run['status'] === 'missing' => 'sqlsrv-row-missing',
                                        $run['usr_status'] === 'late' => 'sqlsrv-row-late',
                                        $run['status'] === 'pending' => 'sqlsrv-row-pending',
                                        default => 'sqlsrv-row-success',
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="fw-bold">{{ $run['time'] }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $run['label'] }}</div>
                                        <div class="small text-muted">{{ $run['file'] }}</div>
                                    </td>
                                    <td>{{ $run['host'] ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $run['status'] === 'success' ? 'bg-success' : ($run['status'] === 'pending' ? 'bg-secondary' : 'bg-danger') }}">
                                            {{ $run['status_label'] }}
                                        </span>
                                        @if ($run['last_run_at'])
                                            <div class="small text-muted mt-1">{{ $run['last_run_at']->format('H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $run['usr_table'] }}</td>
                                    <td>{{ $run['last_usr_update_at'] ? $run['last_usr_update_at']->format('d/m/Y H:i') : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $run['usr_status'] === 'updated' ? 'bg-success' : ($run['usr_status'] === 'pending' ? 'bg-secondary' : 'bg-danger') }}">
                                            {{ $run['usr_status_label'] }}
                                        </span>
                                        @if ($run['log']?->error)
                                            <div class="small text-danger mt-1 text-truncate" style="max-width: 260px;" title="{{ $run['log']->error }}">
                                                {{ $run['log']->error }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-5">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h5 fw-bold mb-1">Última atualização das bases _usr_</h2>
                        <p class="text-muted small mb-3">Ordenado da base mais antiga para a mais recente.</p>
                        <div class="table-responsive sqlsrv-scroll-list">
                            <table class="table table-sm align-middle sqlsrv-table">
                                <thead>
                                    <tr>
                                        <th>Base</th>
                                        <th>Última data</th>
                                        <th class="text-end">Dias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestUsrMetrics as $metric)
                                        @php
                                            $days = $metric->last_update_at ? $metric->last_update_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
                                            $isOneDayOld = $days !== null && $days >= 1;
                                        @endphp
                                        <tr class="{{ $isOneDayOld ? 'sqlsrv-date-alert' : '' }}">
                                            <td class="fw-semibold">{{ $metric->source_name }}</td>
                                            <td>{{ $metric->last_update_at ? $metric->last_update_at->format('d/m/Y H:i') : '-' }}</td>
                                            <td class="text-end">
                                                @if ($isOneDayOld)
                                                    <span class="badge bg-danger">{{ $days }} dia{{ $days === 1 ? '' : 's' }}</span>
                                                @else
                                                    {{ $days !== null ? $days . ' dia' . ($days === 1 ? '' : 's') : '-' }}
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Nenhuma base `_usr_` capturada ainda.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h5 fw-bold mb-1">Resumo conforme calendário</h2>
                        <p class="text-muted small mb-3">Cada linha representa um horário previsto. Mostra se rodou e se ficou dentro do prazo.</p>
                        <div class="table-responsive sqlsrv-scroll-list">
                            <table class="table table-sm align-middle sqlsrv-table">
                                <thead>
                                    <tr>
                                        <th>Horário</th>
                                        <th>Carga</th>
                                        <th>Rodou</th>
                                        <th>No prazo</th>
                                        <th>Base _usr_</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (collect($expectedRuns)->sortByDesc('scheduled_at') as $run)
                                        @php
                                            $rowClass = match (true) {
                                                $run['status'] === 'failed' => 'sqlsrv-row-failed',
                                                $run['status'] === 'missing' => 'sqlsrv-row-missing',
                                                $run['usr_status'] === 'late' => 'sqlsrv-row-late',
                                                $run['status'] === 'pending' => 'sqlsrv-row-pending',
                                                default => 'sqlsrv-row-success',
                                            };
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td class="fw-bold">{{ $run['time'] }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $run['label'] }}</div>
                                                <div class="small text-muted">{{ $run['file'] }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $run['status'] === 'success' ? 'bg-success' : ($run['status'] === 'pending' ? 'bg-secondary' : 'bg-danger') }}">
                                                    {{ $run['status'] === 'success' ? 'Sim' : ($run['status'] === 'pending' ? 'Aguardando' : 'Não') }}
                                                </span>
                                                @if ($run['last_run_at'])
                                                    <div class="small text-muted mt-1">{{ $run['last_run_at']->format('H:i') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($run['status'] === 'pending')
                                                    <span class="badge bg-secondary">Aguardando</span>
                                                @elseif ($run['status'] === 'missing')
                                                    <span class="badge bg-danger">Não rodou</span>
                                                @elseif ($run['ran_on_time'])
                                                    <span class="badge bg-success">Sim</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Atrasou</span>
                                                    @if ($run['delay_minutes'] !== null)
                                                        <div class="small text-muted mt-1">+{{ $run['delay_minutes'] }} min</div>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $run['usr_status'] === 'updated' ? 'bg-success' : ($run['usr_status'] === 'pending' ? 'bg-secondary' : 'bg-danger') }}">
                                                    {{ $run['usr_status'] === 'updated' ? 'Atualizada' : ($run['usr_status'] === 'pending' ? 'Aguardando' : 'Atrasada') }}
                                                </span>
                                                <div class="small text-muted mt-1">{{ $run['usr_table'] }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sqlsrv-card p-4">
                <h2 class="h5 fw-bold mb-3">Bases _usr_ na última coleta</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle sqlsrv-table">
                        <thead>
                            <tr>
                                <th>Base</th>
                                <th class="text-end">Linhas</th>
                                <th>Última atualização encontrada</th>
                                <th class="text-end">Dias</th>
                                <th>Leitura para gestão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestUsrMetrics as $metric)
                                @php
                                    $isStale = $metric->last_update_at && $metric->last_update_at->lt(now()->subHours(12));
                                    $days = $metric->last_update_at ? $metric->last_update_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
                                    $isOneDayOld = $days !== null && $days >= 1;
                                @endphp
                                <tr class="{{ $isOneDayOld ? 'sqlsrv-date-alert' : '' }}">
                                    <td class="fw-semibold">{{ $metric->source_name }}</td>
                                    <td class="text-end">{{ $metric->row_count !== null ? number_format($metric->row_count, 0, ',', '.') : '-' }}</td>
                                    <td>{{ $metric->last_update_at ? $metric->last_update_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td class="text-end">
                                        @if ($isOneDayOld)
                                            <span class="badge bg-danger">{{ $days }} dia{{ $days === 1 ? '' : 's' }}</span>
                                        @else
                                            {{ $days !== null ? $days . ' dia' . ($days === 1 ? '' : 's') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $isOneDayOld ? 'bg-danger' : ($isStale ? 'bg-warning text-dark' : 'bg-success') }}">
                                            {{ $isOneDayOld ? 'Atrasada' : ($isStale ? 'Pode estar atrasada' : 'Atualizada') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhuma base `_usr_` capturada ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($activeTab === 'technical')
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Taxa geral</span>
                        <strong>{{ number_format($kpis['success_rate'], 1, ',', '.') }}%</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Coletas</span>
                        <strong>{{ $kpis['snapshots'] }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Logs hoje</span>
                        <strong>{{ $kpis['logs_today'] }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Falhas hoje</span>
                        <strong>{{ $kpis['errors_today'] }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Bases atrasadas</span>
                        <strong>{{ $kpis['stale_sources'] }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="sqlsrv-card sqlsrv-metric">
                        <span>Métricas</span>
                        <strong>{{ $lastSnapshot?->metrics_count ?? 0 }}</strong>
                    </div>
                </div>
            </div>

            <div id="sqlsrv-health-chart-data"
                data-active="{{ $activeTab }}"
                data-main='@json($chartPayload)'
                data-source='@json($sourceChartPayload)'></div>

            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="sqlsrv-card p-3">
                        <h2 class="h6 fw-bold mb-3">Coletas x falhas capturadas</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvTimelineChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="sqlsrv-card p-3">
                        <h2 class="h6 fw-bold mb-3">Idade da última atualização por base</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvSourceAgeChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="sqlsrv-card p-3">
                        <h2 class="h6 fw-bold mb-3">Evolução de volume: {{ $selectedSource ?: 'sem fonte' }}</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvSourceRowsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="sqlsrv-card p-3 h-100">
                        <h2 class="h6 fw-bold mb-3">Máquinas que executaram cargas</h2>
                        @forelse ($hostSummary as $host)
                            <div class="d-flex justify-content-between border-bottom py-2 gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $host->host_name }}</div>
                                    <div class="small text-muted">Última execução: {{ $host->last_run ? \Carbon\Carbon::parse($host->last_run)->format('d/m/Y H:i') : '-' }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary">{{ $host->total }} logs</span>
                                    <span class="badge {{ (int) $host->failures === 0 ? 'bg-success' : 'bg-danger' }}">{{ $host->failures }} falhas</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">Nenhum host capturado no período.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="sqlsrv-card p-3">
                        <h2 class="h6 fw-bold mb-3">Bases monitoradas na última coleta</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle sqlsrv-table">
                                <thead>
                                    <tr>
                                        <th>Base</th>
                                        <th class="text-end">Linhas</th>
                                        <th>Última atualização</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestMetrics as $metric)
                                        <tr>
                                            <td>{{ $metric->source_name }}</td>
                                            <td class="text-end">{{ $metric->row_count !== null ? number_format($metric->row_count, 0, ',', '.') : '-' }}</td>
                                            <td>{{ $metric->last_update_at ? $metric->last_update_at->format('d/m/Y H:i') : ($metric->max_reference_value ?: '-') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-muted text-center">Nenhuma métrica coletada ainda.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="sqlsrv-card p-3">
                        <h2 class="h6 fw-bold mb-3">Logs técnicos recentes</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle sqlsrv-table">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Status</th>
                                        <th>Execução</th>
                                        <th>Máquina</th>
                                        <th>Falha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLogs as $log)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $log->file_name ?: '-' }}</div>
                                                <div class="small text-muted">{{ $log->file_folder ?: '' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $log->has_error ? 'bg-danger' : 'bg-success' }}">
                                                    {{ $log->status ?: 'sem status' }}
                                                </span>
                                            </td>
                                            <td>{{ $log->dt_run ? $log->dt_run->format('d/m/Y H:i') : '-' }}</td>
                                            <td>{{ $log->host ?: '-' }}</td>
                                            <td class="text-truncate" style="max-width: 260px;" title="{{ $log->error }}">{{ $log->error ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted text-center">Nenhum log capturado ainda.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $recentLogs->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:load', () => {
            if (window.sqlsrvHealthChartsBound) {
                return;
            }

            window.sqlsrvHealthChartsBound = true;
            window.sqlsrvHealthCharts = window.sqlsrvHealthCharts || {};
            window.sqlsrvHealthChartsSignature = null;
            window.sqlsrvProcessChartsSignature = null;

            const buildChart = (id, config) => {
                const canvas = document.getElementById(id);
                if (!canvas || typeof Chart === 'undefined') return false;

                if (window.sqlsrvHealthCharts[id]) {
                    window.sqlsrvHealthCharts[id].destroy();
                }

                window.sqlsrvHealthCharts[id] = new Chart(canvas.getContext('2d'), config);
                return true;
            };

            const renderSqlsrvHealthCharts = () => {
                const source = document.getElementById('sqlsrv-health-chart-data');
                if (!source) return;

                const mainRaw = source.dataset.main || '{}';
                const sourceRaw = source.dataset.source || '{}';
                const signature = `${source.dataset.active || ''}|${mainRaw}|${sourceRaw}`;

                if (window.sqlsrvHealthChartsSignature === signature) {
                    return;
                }

                const main = JSON.parse(mainRaw);
                const sourceRows = JSON.parse(sourceRaw);

                buildChart('sqlsrvTimelineChart', {
                    type: 'line',
                    data: {
                        labels: main.timeline?.labels || [],
                        datasets: [{
                            label: 'Logs capturados',
                            data: main.timeline?.logs || [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,.12)',
                            tension: .25,
                            fill: true,
                        }, {
                            label: 'Falhas',
                            data: main.timeline?.errors || [],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,.12)',
                            tension: .25,
                            fill: true,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, animation: false },
                });

                buildChart('sqlsrvSourceAgeChart', {
                    type: 'bar',
                    data: {
                        labels: main.sourceAge?.labels || [],
                        datasets: [{
                            label: 'Horas desde a última atualização',
                            data: main.sourceAge?.hours || [],
                            backgroundColor: 'rgba(15,118,110,.8)',
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: { x: { beginAtZero: true } },
                    },
                });

                buildChart('sqlsrvSourceRowsChart', {
                    type: 'line',
                    data: {
                        labels: sourceRows.labels || [],
                        datasets: [{
                            label: 'Total de linhas',
                            data: sourceRows.rows || [],
                            borderColor: '#0f766e',
                            backgroundColor: 'rgba(15,118,110,.12)',
                            tension: .25,
                            fill: true,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, animation: false },
                });

                window.sqlsrvHealthChartsSignature = signature;
            };

            const renderSqlsrvProcessCharts = () => {
                const source = document.getElementById('sqlsrv-process-chart-data');
                if (!source) return;

                const raw = source.dataset.process || '{}';

                if (window.sqlsrvProcessChartsSignature === raw) {
                    return;
                }

                const process = JSON.parse(raw);

                buildChart('sqlsrvProcessStatusChart', {
                    type: 'doughnut',
                    data: {
                        labels: process.status?.labels || [],
                        datasets: [{
                            data: process.status?.data || [],
                            backgroundColor: ['#16a34a', '#dc2626', '#991b1b', '#94a3b8'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });

                buildChart('sqlsrvProcessUsrChart', {
                    type: 'doughnut',
                    data: {
                        labels: process.usr?.labels || [],
                        datasets: [{
                            data: process.usr?.data || [],
                            backgroundColor: ['#0f766e', '#dc2626', '#f59e0b', '#94a3b8'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });

                window.sqlsrvProcessChartsSignature = raw;
            };

            const renderAllSqlsrvCharts = () => {
                renderSqlsrvHealthCharts();
                renderSqlsrvProcessCharts();
            };

            renderAllSqlsrvCharts();
            Livewire.hook('message.processed', () => renderAllSqlsrvCharts());
        });
    </script>
</div>
