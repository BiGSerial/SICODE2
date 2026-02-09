@php
    use Carbon\Carbon;

    $palette = ['#0f766e', '#0891b2', '#334155', '#16a34a', '#ca8a04', '#dc2626', '#7c3aed', '#ea580c'];

    $viabilityChart = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $dadospizza1['labels'],
            'datasets' => [[
                'data' => $dadospizza1['data'],
                'backgroundColor' => array_slice($palette, 0, max(count($dadospizza1['data']), 1)),
                'borderWidth' => 0,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ],
    ];

    $backlogChartData = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $dadosBacklog['labels'],
            'datasets' => [[
                'data' => $dadosBacklog['data'],
                'backgroundColor' => array_slice($palette, 0, max(count($dadosBacklog['data']), 1)),
                'borderWidth' => 0,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ],
    ];

    $dailyChart = [
        'type' => 'line',
        'data' => [
            'labels' => $dadosDailyViability['labels'],
            'datasets' => [[
                'label' => 'Viabilidades',
                'data' => $dadosDailyViability['data'],
                'borderColor' => '#0f766e',
                'backgroundColor' => 'rgba(15,118,110,0.20)',
                'fill' => true,
                'tension' => 0.25,
                'pointRadius' => 3,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'top'],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ],
    ];

    $rejectionChart = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $dadospizza2['labels'],
            'datasets' => [[
                'data' => $dadospizza2['data'],
                'backgroundColor' => array_slice($palette, 0, max(count($dadospizza2['data']), 1)),
                'borderWidth' => 0,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ],
    ];
@endphp

<div class="partner-dashboard">
    <x-show-loading />

    <style>
        .partner-dashboard {
            --pd-bg: #f4f7fb;
            --pd-surface: #ffffff;
            --pd-ink: #0f172a;
            --pd-muted: #64748b;
            --pd-border: #dbe2ea;
            --pd-primary: #0f766e;
            --pd-primary-2: #0891b2;
            --pd-warn: #b45309;
            --pd-danger: #b91c1c;
            --pd-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
            background:
                radial-gradient(circle at 10% 0%, #e0f2fe 0, transparent 35%),
                radial-gradient(circle at 90% 10%, #dcfce7 0, transparent 30%),
                var(--pd-bg);
            border-radius: 16px;
            padding: 1rem;
        }

        .pd-hero {
            background: linear-gradient(120deg, #0f172a 0%, #0f766e 55%, #0891b2 100%);
            color: #f8fafc;
            border-radius: 14px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.26);
            margin-bottom: 1rem;
        }

        .pd-hero-title {
            font-size: 1.15rem;
            letter-spacing: 0.03em;
            font-weight: 700;
            margin: 0;
        }

        .pd-hero-sub {
            font-size: 0.9rem;
            opacity: 0.85;
        }

        .pd-panel {
            background: var(--pd-surface);
            border: 1px solid var(--pd-border);
            border-radius: 12px;
            box-shadow: var(--pd-shadow);
        }

        .pd-panel .card-header {
            background: transparent;
            border-bottom: 1px solid var(--pd-border);
            color: var(--pd-ink);
        }

        .pd-kpi {
            background: var(--pd-surface);
            border: 1px solid var(--pd-border);
            border-radius: 12px;
            padding: 0.95rem;
            box-shadow: var(--pd-shadow);
            min-height: 108px;
        }

        .pd-kpi small {
            color: var(--pd-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .pd-kpi .value {
            color: var(--pd-ink);
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1;
            margin-top: 0.25rem;
        }

        .pd-kpi .hint {
            margin-top: 0.35rem;
            color: var(--pd-muted);
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .pd-kpi.warn .value {
            color: var(--pd-warn);
        }

        .pd-kpi.danger .value {
            color: var(--pd-danger);
        }

        .pd-table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .pd-table tbody td {
            font-size: 0.86rem;
            vertical-align: middle;
        }

        @media (max-width: 991px) {
            .partner-dashboard {
                padding: 0.75rem;
            }
        }
    </style>

    <div class="pd-hero d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h2 class="pd-hero-title">Dashboard da Parceira</h2>
            <div class="pd-hero-sub">Visão operacional de viabilidade, ADS, D5, rejeições e pendências</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-light btn-sm" wire:click="exportSummaryCsv">
                <i class="ri-file-download-line me-1"></i> Exportar resumo
            </button>
            <button class="btn btn-outline-light btn-sm" wire:click="exportPendenciesCsv">
                <i class="ri-download-2-line me-1"></i> Exportar pendências
            </button>
        </div>
    </div>

    <div class="card pd-panel mb-3">
        <div class="card-body py-3">
            <form class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-xl-2">
                    <label for="month" class="form-label mb-1">Mês referência</label>
                    <input type="month" id="month" class="form-control" wire:model="month"
                        max="{{ now()->format('Y-m') }}">
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label for="start_date" class="form-label mb-1">Data inicial</label>
                    <input type="date" id="start_date" class="form-control" wire:model="dt_ini">
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label for="end_date" class="form-label mb-1">Data final</label>
                    <input type="date" id="end_date" class="form-control" wire:model="dt_fim">
                </div>
                <div class="col-12 col-xl-6 text-xl-end">
                    <div class="small text-muted">
                        Período exibido: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi">
                <small>Viabilidade pendente</small>
                <div class="value">{{ $kpis['pending_viability'] ?? 0 }}</div>
                <div class="hint">Ainda não concluídas</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi warn">
                <small>Viabilidade a vencer</small>
                <div class="value">{{ $kpis['viability_due_soon'] ?? 0 }}</div>
                <div class="hint">Vence em até {{ $daysAhead }} dias</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi warn">
                <small>Informe sem ADS</small>
                <div class="value">{{ $kpis['work_without_ads_due_soon'] ?? 0 }}</div>
                <div class="hint">A vencer</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi danger">
                <small>Informe sem ADS vencido</small>
                <div class="value">{{ $kpis['work_without_ads_overdue'] ?? 0 }}</div>
                <div class="hint">Acima do prazo</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi">
                <small>D5 aguardando solução</small>
                <div class="value">{{ $kpis['d5_pending'] ?? 0 }}</div>
                <div class="hint">Pendentes de fechamento</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi">
                <small>D5 devolvidos</small>
                <div class="value">{{ $kpis['d5_returned'] ?? 0 }}</div>
                <div class="hint">Aguardando retorno</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi danger">
                <small>Viabilidades rejeitadas</small>
                <div class="value">{{ $kpis['viability_rejected_waiting'] ?? 0 }}</div>
                <div class="hint">Aguardando resposta</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi danger">
                <small>Informes rejeitados</small>
                <div class="value">{{ $kpis['informs_rejected'] ?? 0 }}</div>
                <div class="hint">Com devolução</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="pd-kpi warn">
                <small>Reclamações pendentes</small>
                <div class="value">{{ $kpis['reclaims_pending'] ?? 0 }}</div>
                <div class="hint">Aguardando solução</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="card pd-panel" wire:ignore.self>
                        <div class="card-header">
                            <h6 class="mb-0">Status de Viabilidade</h6>
                        </div>
                        <div class="card-body" wire:ignore>
                            <div style="min-height: 290px;">
                                <x-grafico.apex :chart="$viabilityChart" chartId="partner_viability_status" class="w-100" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card pd-panel" wire:ignore.self>
                        <div class="card-header">
                            <h6 class="mb-0">Backlog Geral</h6>
                        </div>
                        <div class="card-body" wire:ignore>
                            <div style="min-height: 290px;">
                                <x-grafico.apex :chart="$backlogChartData" chartId="partner_backlog" class="w-100" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card pd-panel" wire:ignore.self>
                        <div class="card-header">
                            <h6 class="mb-0">Entrada diária de Viabilidades</h6>
                        </div>
                        <div class="card-body" wire:ignore>
                            <div style="min-height: 290px;">
                                <x-grafico.apex :chart="$dailyChart" chartId="partner_daily_viability" class="w-100" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card pd-panel" wire:ignore.self>
                        <div class="card-header">
                            <h6 class="mb-0">Motivos de rejeição de Informes</h6>
                        </div>
                        <div class="card-body" wire:ignore>
                            <div style="min-height: 290px;">
                                <x-grafico.apex :chart="$rejectionChart" chartId="partner_rejection_reasons" class="w-100" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card pd-panel mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Viabilidades vencendo</h6>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('partner.todo.viability') }}">Abrir lista</a>
                </div>

                @if ($dueSoon->isNotEmpty())
                    <div class="table-responsive pd-table">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Nota</th>
                                    <th class="text-center">Recebido</th>
                                    <th class="text-center">Vence</th>
                                    <th class="text-center">Dias</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dueSoon as $item)
                                    @php
                                        $dueDate = $item->sended_at->copy()->addDays(7 + $item->getDays());
                                        $daysLeft = now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold">{{ $item->note->note ?? '-' }}</td>
                                        <td class="text-center">{{ $item->sended_at?->format('d/m/Y') }}</td>
                                        <td class="text-center text-danger">{{ $dueDate->format('d/m/Y') }}</td>
                                        <td class="text-center fw-bold">{{ $daysLeft }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-center text-muted">Nenhuma viabilidade vencendo em breve.</div>
                @endif
            </div>

            <div class="card pd-panel mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Informes sem ADS a vencer</h6>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('partner.report.workedlist') }}">Ver informes</a>
                </div>

                @if ($workReportsWithoutAdsDueSoon->isNotEmpty())
                    <div class="table-responsive pd-table">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Nota</th>
                                    <th class="text-center">Informe</th>
                                    <th class="text-center">Prazo ADS</th>
                                    <th class="text-center">Dias</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workReportsWithoutAdsDueSoon as $item)
                                    @php
                                        $dueDate = $item->informed_at?->copy()->addDays(7);
                                        $daysLeft = $dueDate ? now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false) : null;
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold">{{ $item->note->note ?? '-' }}</td>
                                        <td class="text-center">{{ $item->informed_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-center text-danger">{{ $dueDate?->format('d/m/Y H:i') }}</td>
                                        <td class="text-center fw-bold">{{ $daysLeft }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-center text-muted">Sem informes sem ADS vencendo no horizonte atual.</div>
                @endif
            </div>

            <div class="card pd-panel">
                <div class="card-header">
                    <h6 class="mb-0">Atalhos</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('partner.note_d5.list') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="ri-file-list-3-line me-1"></i> D5 pendentes
                    </a>
                    <a href="{{ route('partner.note_d5.returned') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="ri-arrow-go-back-line me-1"></i> D5 devolvidos
                    </a>
                    <a href="{{ route('partner.rejected.viability') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="ri-close-circle-line me-1"></i> Viabilidades rejeitadas
                    </a>
                    <a href="{{ route('partner.report.rejectedWorked') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="ri-file-warning-line me-1"></i> Informes rejeitados
                    </a>
                    <a href="{{ route('partner.tacit.viability') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="ri-time-line me-1"></i> Tacitativas sem justificativa
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
