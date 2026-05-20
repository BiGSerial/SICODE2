<div @if($autoRefresh) wire:poll.60000ms="$refresh" @endif>
    {{-- Cabeçalho --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold mb-0" style="color: var(--legal-primary)">
            <i class="bi bi-graph-up me-2"></i>Dashboard Jurídico
        </h4>
        <div class="d-flex align-items-center gap-3">
            <span class="small text-muted">Última atualização: {{ now()->format('d/m/Y H:i') }}</span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" wire:model="autoRefresh" id="autoRefresh" />
                <label class="form-check-label small" for="autoRefresh">Auto-refresh</label>
            </div>
            <button class="btn btn-sm btn-outline-secondary" wire:click="refreshData">
                <i class="bi bi-arrow-clockwise me-1"></i>Atualizar
            </button>
        </div>
    </div>

    {{-- Filtros Globais --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto small text-muted fw-semibold">Filtros:</div>
                <div class="col-6 col-lg-2">
                    <select class="form-select form-select-sm" wire:model="period">
                        <option value="current_month">Mês Atual</option>
                        <option value="last_30d">Últimos 30 dias</option>
                        <option value="last_90d">Últimos 90 dias</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                @if($period === 'custom')
                    <div class="col-6 col-lg-2">
                        <input type="date" class="form-control form-control-sm" wire:model="periodFrom" />
                    </div>
                    <div class="col-6 col-lg-2">
                        <input type="date" class="form-control form-control-sm" wire:model="periodTo" />
                    </div>
                @endif
                <div class="col-6 col-lg-2">
                    <select class="form-select form-select-sm" wire:model="sourceTypeFilter">
                        <option value="">Todos os tipos</option>
                        <option value="injunction">Liminar</option>
                        <option value="sentence">Sentença</option>
                        <option value="subsidy">Subsídio</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <select class="form-select form-select-sm" wire:model="controllerFilter">
                        <option value="">Todos os controladores</option>
                        @foreach($controllers as $ctrl)
                            <option value="{{ $ctrl->id }}">{{ $ctrl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <button class="btn btn-sm btn-outline-secondary w-100" wire:click="refreshData">Aplicar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 1: KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <x-legal.kpi-card title="Demandas Ativas" :value="$kpis['total_active']" color="primary" icon="bi-briefcase" />
        </div>
        <div class="col-6 col-lg">
            <div class="legal-kpi-card border-top border-4 border-danger {{ $kpis['overdue'] > 0 ? 'legal-pulse' : '' }}">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Vencidas</div>
                        <div class="fs-2 fw-bold text-danger">{{ $kpis['overdue'] }}</div>
                        @if($kpis['overdue_7d'] > 0)
                            <div class="small text-muted">{{ $kpis['overdue_7d'] }} há mais de 7 dias</div>
                        @endif
                    </div>
                    <i class="bi bi-exclamation-triangle fs-3 text-danger opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <x-legal.kpi-card title="Em Campo" :value="$kpis['in_field']" color="warning" icon="bi-send" />
        </div>
        <div class="col-6 col-lg">
            <x-legal.kpi-card title="Resolvidas" :value="$kpis['resolved']" color="success" icon="bi-check-circle"
                              :sub="$kpis['resolved_int'] . ' int. / ' . $kpis['resolved_ext'] . ' ext.'" />
        </div>
        <div class="col-6 col-lg">
            @php $sla = $kpis['sla']; $slaColor = $sla === null ? 'secondary' : ($sla >= 80 ? 'success' : ($sla >= 60 ? 'warning' : 'danger')); @endphp
            <div class="legal-kpi-card border-top border-4 border-{{ $slaColor }}">
                <div class="text-muted small fw-semibold text-uppercase">SLA</div>
                <div class="fs-2 fw-bold text-{{ $slaColor }}">{{ $sla !== null ? $sla . '%' : '—' }}</div>
                <div class="small text-muted">Resolvidas no prazo</div>
            </div>
        </div>
    </div>

    {{-- Row 2: Funil de Status --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Funil por Status</div>
                <div class="card-body">
                    @php
                        $funnelLabels = [
                            'new_imported' => 'Novas',
                            'triage'       => 'Triagem',
                            'in_field'     => 'Em Campo',
                            'returned'     => 'Retornadas',
                            'ready_close'  => 'Prontas Fechar',
                            'closed'       => 'Encerradas',
                        ];
                        $funnelMax = max(1, max($statusFunnel));
                    @endphp
                    @foreach($funnelLabels as $key => $label)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div style="width:100px" class="small text-end text-muted">{{ $label }}</div>
                            <div class="flex-grow-1 bg-light rounded" style="height:20px">
                                <div class="bg-primary rounded" style="height:100%;width:{{ round(($statusFunnel[$key] ?? 0) / $funnelMax * 100) }}%"></div>
                            </div>
                            <div class="small fw-semibold" style="width:30px">{{ $statusFunnel[$key] ?? 0 }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Alertas e Anomalias</div>
                <div class="card-body">
                    @forelse($alerts as $alert)
                        <div class="d-flex align-items-start gap-2 mb-2">
                            @if($alert['level'] === 'danger')
                                <i class="bi bi-exclamation-circle-fill text-danger mt-1"></i>
                            @elseif($alert['level'] === 'warning')
                                <i class="bi bi-exclamation-triangle-fill text-warning mt-1"></i>
                            @else
                                <i class="bi bi-info-circle-fill mt-1" style="color:var(--legal-accent)"></i>
                            @endif
                            <span class="small">{{ $alert['message'] }}</span>
                        </div>
                    @empty
                        <x-legal.empty-state icon="bi-check-circle" message="Sem alertas no momento." />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3: Distribuição --}}
    <div class="row g-4 mb-4">
        {{-- Distribuição por tipo --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Por Tipo de Fonte</div>
                <div class="card-body">
                    @php
                        $typeLabels = ['injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio'];
                        $typeTotal  = max(1, $byType->sum());
                        $typeColors = ['injunction' => 'primary', 'sentence' => 'warning', 'subsidy' => 'success'];
                    @endphp
                    @foreach($typeLabels as $key => $label)
                        @php $count = $byType[$key] ?? 0; $pct = round($count / $typeTotal * 100); @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $label }}</span>
                                <span class="fw-semibold">{{ $count }} ({{ $pct }}%)</span>
                            </div>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar bg-{{ $typeColors[$key] }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Top 5 Áreas --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Top 5 Áreas</div>
                <div class="card-body">
                    @php $areaMax = max(1, $topAreas->max('total')); @endphp
                    @forelse($topAreas as $area)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="small text-muted text-truncate" style="width:120px">{{ $area->origin_area_name ?? '—' }}</div>
                            <div class="flex-grow-1 bg-light rounded" style="height:16px">
                                <div class="bg-primary rounded" style="height:100%;width:{{ round($area->total / $areaMax * 100) }}%"></div>
                            </div>
                            <div class="small fw-semibold" style="width:24px">{{ $area->total }}</div>
                        </div>
                    @empty
                        <p class="text-muted small">Sem dados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Heatmap --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Criticidade × Tipo</div>
                <div class="card-body p-2">
                    <table class="table table-sm table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th class="text-center">Liminar</th>
                                <th class="text-center">Sentença</th>
                                <th class="text-center">Subsídio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['overdue' => '🔴 Vencida', '3d' => '⚡ ≤ 3d', '7d' => '🕐 ≤ 7d', 'no_date' => '⚫ Sem prazo'] as $k => $label)
                                <tr>
                                    <td class="small">{{ $label }}</td>
                                    @foreach(['injunction', 'sentence', 'subsidy'] as $type)
                                        @php $v = $heatmap[$type][$k] ?? 0; @endphp
                                        <td class="text-center fw-semibold {{ $k === 'overdue' && $v > 0 ? 'table-danger' : ($k === '3d' && $v > 0 ? 'table-warning' : '') }}">
                                            {{ $v }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Ranking Executantes + Top 10 Críticos --}}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Ranking de Executantes</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Executante</th>
                                <th class="text-center">Ativas</th>
                                <th class="text-center">Vencidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($executors as $i => $exec)
                                <tr>
                                    <td class="text-muted small">{{ $i + 1 }}</td>
                                    <td>{{ $exec->name }}</td>
                                    <td class="text-center">{{ $exec->active_count }}</td>
                                    <td class="text-center">
                                        @if($exec->overdue_count > 0)
                                            <span class="badge bg-danger">{{ $exec->overdue_count }}</span>
                                        @else
                                            <span class="text-success small">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Top 10 Processos Críticos</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Processo</th>
                                <th>Tipo</th>
                                <th>Prazo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($criticalDemands as $d)
                                <tr>
                                    <td>
                                        <a href="{{ route('legal.demand.detail', $d->uuid) }}" class="small">
                                            {{ $d->source_case_number ?? $d->source_process_number ?? 'S/N' }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-secondary small">{{ $d->source_type }}</span></td>
                                    <td><x-legal.due-date-chip :date="$d->source_due_at" :showIcon="false" /></td>
                                    <td><x-legal.status-badge :status="$d->internal_status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Sem demandas críticas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-show-loading />
</div>
