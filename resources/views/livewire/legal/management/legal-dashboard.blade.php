<div class="ld-page" @if($autoRefresh) wire:poll.60000ms="$refresh" @endif>
    <x-show-loading />

    <style>
        .ld-page {
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        #f6f7fb;
            padding: 1.5rem 0;
        }
        .ld-header {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .22);
            margin-bottom: 1.5rem;
        }
        .ld-kpi {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: .75rem;
            padding: .6rem 1rem;
            min-width: 110px;
        }
        .ld-kpi .val { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .ld-kpi .lbl { font-size: .72rem; opacity: .8; text-transform: uppercase; letter-spacing: .05em; }
        .filter-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            padding: .85rem 1rem;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15,23,42,.1);
        }
        .filter-card .form-label { color: #0f172a; font-weight: 700; margin-bottom: .3rem; font-size: .82rem; }
        .filter-card .form-select,
        .filter-card .form-control { color: #0f172a; border-color: #cbd5e1; background: #fff; font-size: .85rem; }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15,23,42,.08);
            overflow: hidden;
        }
        .table-card .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }
        .ld-pulse { animation: ld-pulse-anim 2s infinite; }
        @keyframes ld-pulse-anim { 0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.3); } 50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); } }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="ld-header">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
                    <div class="mb-3 opacity-75" style="font-size:.9rem">Dashboard de Gestão</div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="ld-kpi">
                            <div class="val" style="color:#93c5fd">{{ $kpis['total_active'] }}</div>
                            <div class="lbl">Ativas</div>
                        </div>
                        <div class="ld-kpi {{ $kpis['overdue'] > 0 ? 'ld-pulse' : '' }}">
                            <div class="val text-danger">{{ $kpis['overdue'] }}</div>
                            <div class="lbl">Vencidas</div>
                        </div>
                        <div class="ld-kpi">
                            <div class="val text-warning">{{ $kpis['in_field'] }}</div>
                            <div class="lbl">Em Campo</div>
                        </div>
                        <div class="ld-kpi">
                            <div class="val" style="color:#6ee7b7">{{ $kpis['resolved'] }}</div>
                            <div class="lbl">Resolvidas</div>
                        </div>
                        @php $sla = $kpis['sla']; $slaColor = $sla === null ? '#94a3b8' : ($sla >= 80 ? '#6ee7b7' : ($sla >= 60 ? '#fbbf24' : '#f87171')); @endphp
                        <div class="ld-kpi">
                            <div class="val" style="color:{{ $slaColor }}">{{ $sla !== null ? $sla . '%' : '—' }}</div>
                            <div class="lbl">SLA Prazo</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="row g-2">
                        <div class="col-6 col-md-4">
                            <div class="filter-card">
                                <label class="form-label">Período</label>
                                <select class="form-select" wire:model="period">
                                    <option value="current_month">Mês Atual</option>
                                    <option value="last_30d">Últimos 30 dias</option>
                                    <option value="last_90d">Últimos 90 dias</option>
                                    <option value="custom">Personalizado</option>
                                </select>
                            </div>
                        </div>
                        @if($period === 'custom')
                            <div class="col-6 col-md-4">
                                <div class="filter-card">
                                    <label class="form-label">De</label>
                                    <input type="date" class="form-control" wire:model="periodFrom" />
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="filter-card">
                                    <label class="form-label">Até</label>
                                    <input type="date" class="form-control" wire:model="periodTo" />
                                </div>
                            </div>
                        @endif
                        <div class="col-6 col-md-4">
                            <div class="filter-card">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" wire:model="sourceTypeFilter">
                                    <option value="">Todos</option>
                                    <option value="injunction">Liminar</option>
                                    <option value="sentence">Sentença</option>
                                    <option value="subsidy">Subsídio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="filter-card">
                                <label class="form-label">Controlador</label>
                                <select class="form-select" wire:model="controllerFilter">
                                    <option value="">Todos</option>
                                    @foreach($controllers as $ctrl)
                                        <option value="{{ $ctrl->id }}">{{ $ctrl->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 d-flex gap-2 align-items-end">
                            <button class="btn btn-light btn-sm flex-fill" wire:click="refreshData">
                                <i class="bi bi-arrow-clockwise me-1"></i>Atualizar
                            </button>
                            <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
                                <input class="form-check-input" type="checkbox" wire:model="autoRefresh" id="autoRefresh" />
                                <label class="form-check-label small text-white" for="autoRefresh">Auto</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alertas --}}
        @if(count($alerts) > 0)
            <div class="row g-2 mb-3">
                @foreach($alerts as $alert)
                    <div class="col-12">
                        <div class="alert {{ $alert['level'] === 'danger' ? 'alert-danger' : ($alert['level'] === 'warning' ? 'alert-warning' : 'alert-info') }} py-2 mb-0 small">
                            <i class="bi {{ $alert['level'] === 'danger' ? 'bi-exclamation-circle-fill' : 'bi-exclamation-triangle-fill' }} me-2"></i>
                            {{ $alert['message'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Funil + Distribuição --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header text-bg-dark fw-bold">Funil por Status</div>
                    <div class="card-body">
                        @php
                            $funnelLabels = ['new_imported' => 'Novas', 'triage' => 'Triagem', 'in_field' => 'Em Campo', 'returned' => 'Retornadas', 'ready_close' => 'Prontas Fechar', 'closed' => 'Encerradas'];
                            $funnelMax    = max(1, max($statusFunnel));
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

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header text-bg-dark fw-bold">Por Tipo de Fonte</div>
                    <div class="card-body">
                        @php
                            $typeLabels = ['injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio'];
                            $typeTotal  = max(1, $byType->sum());
                            $typeColors = ['injunction' => 'danger', 'sentence' => 'warning', 'subsidy' => 'info'];
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

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header text-bg-dark fw-bold">Criticidade × Tipo</div>
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
                                @foreach(['overdue' => 'Vencida', '3d' => '≤ 3 dias', '7d' => '≤ 7 dias', 'no_date' => 'Sem prazo'] as $k => $label)
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

        {{-- Top 5 Áreas + Ranking --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header text-bg-dark fw-bold">Top 5 Áreas</div>
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

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header text-bg-dark fw-bold">Ranking de Executantes</div>
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

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header text-bg-dark fw-bold">Top 10 Processos Críticos</div>
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
                                    @php
                                        $dType  = $d->source_type instanceof \BackedEnum ? $d->source_type->value : $d->source_type;
                                        $dLabel = match($dType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $dType ?? '—' };
                                        $dBg    = match($dType) { 'injunction' => 'bg-danger text-white', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary text-white' };
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('legal.demand.detail', $d->uuid) }}" class="small">
                                                {{ $d->source_case_number ?? $d->source_process_number_masked ?? 'S/N' }}
                                            </a>
                                        </td>
                                        <td><span class="badge {{ $dBg }} small">{{ $dLabel }}</span></td>
                                        <td><x-legal.due-date-chip :date="$d->source_due_at" :executedAt="$d->source_executed_at" :showIcon="false" /></td>
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

        <div class="text-end small text-muted">
            Última atualização: {{ now()->format('d/m/Y H:i') }}
        </div>

    </div>
</div>
