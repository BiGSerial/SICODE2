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
        .table-card .card-header {
            padding: .85rem 1rem .8rem 1rem;
            border-bottom: 1px solid #dbe3ee;
            background: #1f3148;
            color: #fff;
            font-size: .97rem;
            letter-spacing: .01em;
        }
        .table-card .card-body {
            padding: 1rem;
        }
        .table-card .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }
        .sla-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: .75rem;
        }
        .sla-item {
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-radius: .8rem;
            padding: .7rem .85rem;
        }
        .sla-item .k { font-size: .72rem; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .sla-item .v { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
        @media (max-width: 1200px) {
            .sla-strip { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }
        .ld-pulse { animation: ld-pulse-anim 2s infinite; }
        @keyframes ld-pulse-anim { 0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.3); } 50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); } }
        .critical-table .proc-link,
        .critical-table .proc-link:visited {
            color: #1e3a8a !important;
            font-weight: 700;
            text-decoration: none;
        }
        .critical-table .proc-link:hover,
        .critical-table .proc-link:focus {
            color: #1e40af !important;
            text-decoration: underline;
        }
        .critical-table .type-chip {
            display: inline-block;
            border-radius: 999px;
            padding: .18rem .5rem;
            font-size: .72rem;
            font-weight: 700;
            line-height: 1;
        }
        .critical-table .type-injunction { background: #fee2e2; color: #991b1b; }
        .critical-table .type-sentence { background: #fef3c7; color: #92400e; }
        .critical-table .type-subsidy { background: #dbeafe; color: #1e40af; }
        .critical-table .type-default { background: #e5e7eb; color: #374151; }
        .critical-table .status-chip {
            display: inline-block;
            border-radius: 999px;
            padding: .18rem .5rem;
            font-size: .72rem;
            font-weight: 700;
            line-height: 1;
            background: #e2e8f0;
            color: #334155;
        }
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
                    <div class="card-header fw-bold">Funil por Status</div>
                    <div class="card-body">
                        <x-grafico.apex
                            chartId="legal_funnel_chart"
                            :chart="[
                                'type' => 'bar',
                                'data' => [
                                    'labels' => $funnelLabels,
                                    'datasets' => [[
                                        'label' => 'Demandas',
                                        'data' => $funnelData,
                                        'borderRadius' => 6,
                                        'maxBarThickness' => 30,
                                    ]],
                                ],
                                'options' => [
                                    'indexAxis' => 'y',
                                    'plugins' => [
                                        'legend' => ['display' => false],
                                    ],
                                    'scales' => [
                                        'x' => ['beginAtZero' => true],
                                    ],
                                ],
                            ]"
                            class="w-100"
                            :showDataLabels="true"
                        />
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header fw-bold">Por Tipo de Fonte</div>
                    <div class="card-body">
                        <x-grafico.apex
                            chartId="legal_type_donut"
                            :chart="[
                                'type' => 'doughnut',
                                'data' => [
                                    'labels' => $typeLabels,
                                    'datasets' => [[
                                        'label' => 'Tipos',
                                        'data' => $typeData,
                                        'borderWidth' => 1,
                                    ]],
                                ],
                                'options' => [
                                    'plugins' => [
                                        'legend' => ['position' => 'bottom'],
                                    ],
                                ],
                            ]"
                            class="w-100"
                            :showDataLabels="false"
                        />
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header fw-bold">Criticidade × Tipo</div>
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
                    <div class="card-header fw-bold">Top 5 Áreas</div>
                    <div class="card-body">
                        @if(count($areaLabels) > 0)
                            <x-grafico.apex
                                chartId="legal_areas_chart"
                                :chart="[
                                    'type' => 'bar',
                                    'data' => [
                                        'labels' => $areaLabels,
                                        'datasets' => [[
                                            'label' => 'Demandas',
                                            'data' => $areaData,
                                            'borderRadius' => 6,
                                            'maxBarThickness' => 34,
                                        ]],
                                    ],
                                    'options' => [
                                        'plugins' => ['legend' => ['display' => false]],
                                        'scales' => ['y' => ['beginAtZero' => true]],
                                    ],
                                ]"
                                class="w-100"
                                :showDataLabels="true"
                            />
                        @else
                            <p class="text-muted small">Sem dados.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="table-card h-100">
                    <div class="card-header fw-bold">Ranking de Executantes</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 critical-table">
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
                    <div class="card-header fw-bold">Top 10 Processos Críticos</div>
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
                                        $dTypeClass = match($dType) {
                                            'injunction' => 'type-injunction',
                                            'sentence' => 'type-sentence',
                                            'subsidy' => 'type-subsidy',
                                            default => 'type-default'
                                        };
                                        $externalStatusRaw = trim((string) ($d->source_status ?? $d->process_status_at_import ?? ''));
                                        $statusLabel = $externalStatusRaw !== ''
                                            ? $externalStatusRaw
                                            : 'Sem status externo';
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('legal.demand.detail', $d->uuid) }}" class="small proc-link" style="color:#1e3a8a;text-decoration:none;">
                                                {{ $d->source_case_number ?? $d->source_process_number_masked ?? 'S/N' }}
                                            </a>
                                        </td>
                                        <td><span class="type-chip {{ $dTypeClass }}">{{ $dLabel }}</span></td>
                                        <td><x-legal.due-date-chip :date="$d->source_due_at" :executedAt="$d->source_executed_at" :showIcon="false" /></td>
                                        <td><span class="status-chip">{{ $statusLabel }}</span></td>
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

        <div class="table-card mb-4">
            <div class="card-header fw-bold">SLA Operacional</div>
            <div class="card-body">
                <div class="sla-strip">
                    <div class="sla-item">
                        <div class="k">Tempo méd. despacho controlador</div>
                        <div class="v">{{ $slaOps['controller_dispatch_avg_h'] !== null ? $slaOps['controller_dispatch_avg_h'].'h' : '—' }}</div>
                    </div>
                    <div class="sla-item">
                        <div class="k">Tempo méd. recebimento executante</div>
                        <div class="v">{{ $slaOps['executor_receive_avg_h'] !== null ? $slaOps['executor_receive_avg_h'].'h' : '—' }}</div>
                    </div>
                    <div class="sla-item">
                        <div class="k">Tempo méd. resposta executante</div>
                        <div class="v">{{ $slaOps['executor_answer_avg_h'] !== null ? $slaOps['executor_answer_avg_h'].'h' : '—' }}</div>
                    </div>
                    <div class="sla-item">
                        <div class="k">Respostas dentro do prazo (SLA)</div>
                        <div class="v">{{ $slaOps['answer_on_due_rate'] !== null ? $slaOps['answer_on_due_rate'].'%' : '—' }}</div>
                    </div>
                </div>
                <div class="sla-strip mt-3">
                    <div class="sla-item">
                        <div class="k">Taxa de recebimento executante</div>
                        <div class="v">{{ $slaOps['executor_receive_rate'] }}%</div>
                    </div>
                    <div class="sla-item">
                        <div class="k">Taxa de resposta executante</div>
                        <div class="v">{{ $slaOps['executor_answer_rate'] }}%</div>
                    </div>
                    <div class="sla-item">
                        <div class="k">Taxa de encerramento controlador</div>
                        <div class="v">{{ $slaOps['controller_close_rate'] }}%</div>
                    </div>
                    <div class="sla-item">
                        <div class="k">SLA fechamento no prazo</div>
                        <div class="v">{{ $kpis['sla'] !== null ? $kpis['sla'].'%' : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end small text-muted">
            Última atualização: {{ now()->format('d/m/Y H:i') }}
        </div>

    </div>
</div>
