@php
    use Carbon\Carbon;

    $ageHistogramChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $openViabilityAgeHistogram['labels'] ?? [],
            'datasets' => [[
                'label' => 'Viabilidades em aberto',
                'data' => $openViabilityAgeHistogram['data'] ?? [],
                'backgroundColor' => '#0f766e',
                'borderColor' => '#0f766e',
                'borderRadius' => 6,
                'maxBarThickness' => 34,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['display' => false],
                'datalabels' => [
                    'display' => true,
                    'anchor' => 'end',
                    'align' => 'top',
                    'color' => '#0f172a',
                    'font' => ['weight' => '700', 'size' => 11],
                    'formatter' => '__VALUE_LABEL_NONZERO__',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => '__VALUE_LABEL__',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'title' => ['display' => true, 'text' => 'Dias desde o envio'],
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Quantidade'],
                    'ticks' => ['precision' => 0],
                ],
            ],
        ],
    ];

    $d5AgeHistogramChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $openD5AgeHistogram['labels'] ?? [],
            'datasets' => $openD5AgeHistogram['datasets'] ?? [],
        ],
        'options' => [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'generateLabels' => '__LEGEND_WITH_TOTAL__',
                    ],
                ],
                'datalabels' => [
                    'display' => true,
                    'color' => '#0f172a',
                    'font' => ['weight' => '700', 'size' => 10],
                    'formatter' => '__VALUE_LABEL_NONZERO__',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => '__VALUE_LABEL__',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'title' => ['display' => true, 'text' => 'Dias desde o despacho'],
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Quantidade'],
                    'ticks' => ['precision' => 0],
                ],
            ],
        ],
    ];

    $rejectedWorkReasonChartConfig = [
        'type' => 'bar',
        'data' => [
            'labels' => $rejectedWorkReasons['labels'] ?? [],
            'datasets' => [[
                'label' => 'Informes rejeitados',
                'data' => $rejectedWorkReasons['data'] ?? [],
                'backgroundColor' => '#dc2626',
                'borderColor' => '#dc2626',
                'borderRadius' => 6,
                'maxBarThickness' => 26,
            ]],
        ],
        'options' => [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
                'datalabels' => [
                    'display' => true,
                    'anchor' => 'end',
                    'align' => 'right',
                    'color' => '#0f172a',
                    'font' => ['weight' => '700', 'size' => 11],
                    'formatter' => '__VALUE_LABEL_NONZERO__',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => '__VALUE_LABEL__',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Quantidade'],
                    'ticks' => ['precision' => 0],
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
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

        .metric-card,
        .pd-kpi {
            background: var(--pd-surface);
            border: 1px solid var(--pd-border);
            border-radius: 12px;
            padding: 0.95rem;
            box-shadow: var(--pd-shadow);
            min-height: 108px;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, var(--pd-primary), var(--pd-primary-2));
        }

        .metric-label,
        .pd-kpi small {
            color: var(--pd-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .metric-value,
        .pd-kpi .value {
            color: var(--pd-ink);
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1;
            margin-top: 0.25rem;
        }

        .metric-subtitle,
        .pd-kpi .hint {
            margin-top: 0.35rem;
            color: var(--pd-muted);
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .metric-card.warn .metric-value,
        .pd-kpi.warn .value {
            color: var(--pd-warn);
        }

        .metric-card.danger .metric-value,
        .pd-kpi.danger .value {
            color: var(--pd-danger);
        }

        .metric-card.money {
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 72%, #0891b2 100%);
            border-color: transparent;
            color: #f8fafc;
        }

        .metric-card.money::before {
            background: rgba(255, 255, 255, 0.35);
        }

        .metric-card.money .metric-label,
        .metric-card.money .metric-subtitle {
            color: rgba(248, 250, 252, 0.78);
        }

        .metric-card.money .metric-value {
            color: #ffffff;
            font-size: 1.45rem;
            line-height: 1.15;
        }

        .pd-section-title {
            margin: 0 0 0.75rem;
            color: var(--pd-ink);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .pd-disclaimer {
            border: 1px solid rgba(180, 83, 9, 0.35);
            border-left: 5px solid var(--pd-warn);
            border-radius: 12px;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            box-shadow: 0 10px 24px rgba(180, 83, 9, 0.12);
            color: #78350f;
            font-size: 0.82rem;
            line-height: 1.35;
            padding: 0.85rem 1rem;
            max-width: 760px;
        }

        .pd-subsection-title {
            color: #334155;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
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

        .pd-chart-donut {
            height: 290px;
            max-width: 380px;
            margin: 0 auto;
        }

        .chart-card {
            background: var(--pd-surface);
            border: 1px solid var(--pd-border);
            border-radius: 12px;
            box-shadow: var(--pd-shadow);
            overflow: hidden;
        }

        .chart-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--pd-border);
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(8, 145, 178, 0.08));
        }

        .chart-card-body {
            padding: 1.25rem;
        }

        .pd-chart-histogram {
            height: 360px;
        }

        .pd-chart-horizontal {
            height: 380px;
        }

        .pd-legal-note {
            margin: 0.75rem;
            padding: 0.85rem 1rem;
            border: 1px solid #cbd5e1;
            border-left: 4px solid var(--pd-primary);
            border-radius: 10px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .pd-legal-note-title {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .pd-legal-note p {
            margin: 0;
            color: #334155;
            font-size: 0.8rem;
            line-height: 1.35;
        }

        .pd-legal-note p + p {
            margin-top: 0.5rem;
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
            <div class="metric-card">
                <div class="metric-label">Viabilidade pendente</div>
                <div class="metric-value">{{ $kpis['pending_viability'] ?? 0 }}</div>
                <div class="metric-subtitle">Ainda não concluídas</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card warn">
                <div class="metric-label">Viabilidade a vencer</div>
                <div class="metric-value">{{ $kpis['viability_due_soon'] ?? 0 }}</div>
                <div class="metric-subtitle">Vence em até {{ $daysAhead }} dias</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card warn">
                <div class="metric-label">Entregas de ADS a vencer</div>
                <div class="metric-value">{{ $kpis['work_without_ads_due_soon'] ?? 0 }}</div>
                <div class="metric-subtitle">A vencer</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card">
                <div class="metric-label">D5 aguardando solução</div>
                <div class="metric-value">{{ $kpis['d5_pending'] ?? 0 }}</div>
                <div class="metric-subtitle">Pendentes de fechamento</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card">
                <div class="metric-label">D5 devolvidos</div>
                <div class="metric-value">{{ $kpis['d5_returned'] ?? 0 }}</div>
                <div class="metric-subtitle">Aguardando retorno</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card danger">
                <div class="metric-label">Viabilidades rejeitadas</div>
                <div class="metric-value">{{ $kpis['viability_rejected_waiting'] ?? 0 }}</div>
                <div class="metric-subtitle">Aguardando resposta</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card danger">
                <div class="metric-label">Informes rejeitados</div>
                <div class="metric-value">{{ $kpis['informs_rejected'] ?? 0 }}</div>
                <div class="metric-subtitle">Com devolução</div>
            </div>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="metric-card warn">
                <div class="metric-label">Reclamações pendentes</div>
                <div class="metric-value">{{ $kpis['reclaims_pending'] ?? 0 }}</div>
                <div class="metric-subtitle">Aguardando solução</div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 align-items-lg-start mb-2">
            <h3 class="pd-section-title">Informes</h3>
            <div class="pd-disclaimer">
    <strong class="fs-5">Valores meramente informativos.</strong>
    Os valores apresentados correspondem exclusivamente às informações fornecidas pela parceira nas ADS dos informes e nos parciais solicitados válidos do período filtrado.
    <strong>Esses valores não constituem validação financeira, medição aprovada ou autorização para pagamento.</strong>
</div>
        </div>
        <div class="pd-subsection-title mb-2">Informes parciais</div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-2 mb-3">
            <div class="col">
                <div class="metric-card">
                    <div class="metric-label">Parciais totais</div>
                    <div class="metric-value">{{ $workReportKpis['partials_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Solicitados no período</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card danger">
                    <div class="metric-label">Parciais rejeitadas</div>
                    <div class="metric-value">{{ $workReportKpis['partials_rejected_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Não aprovadas para pagamento</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card">
                    <div class="metric-label">Parciais finalizadas</div>
                    <div class="metric-value">{{ $workReportKpis['partials_completed_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Aprovadas e pagas no fluxo</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card money">
                    <div class="metric-label">Valor total parcial solicitado</div>
                    <div class="metric-value">
                        R$ {{ number_format($workReportKpis['partials_amount_total'] ?? 0, 2, ',', '.') }}
                    </div>
                    <div class="metric-subtitle">Somente parciais válidas</div>
                </div>
            </div>
        </div>

        <div class="pd-subsection-title mb-2">Informes finais</div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-2">
            <div class="col">
                <div class="metric-card">
                    <div class="metric-label">Obras informadas</div>
                    <div class="metric-value">{{ $workReportKpis['informed_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Total por conclusão informada</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card danger">
                    <div class="metric-label">Cancelados/Rejeitados</div>
                    <div class="metric-value">{{ $workReportKpis['canceled_rejected_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Dentro dos informes do período</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card">
                    <div class="metric-label">ADS entregues</div>
                    <div class="metric-value">{{ $workReportKpis['ads_delivered_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Vinculadas aos informes válidos*</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card warn">
                    <div class="metric-label">ADS não entregues</div>
                    <div class="metric-value">{{ $workReportKpis['ads_not_delivered_total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Informes válidos* sem ADS entregue</div>
                </div>
            </div>
            <div class="col">
                <div class="metric-card money">
                    <div class="metric-label">Valor solicitado</div>
                    <div class="metric-value">
                        R$ {{ number_format($workReportKpis['ads_amount_total'] ?? 0, 2, ',', '.') }}
                    </div>
                    <div class="metric-subtitle">Somatório das ADS válidas*</div>
                </div>
            </div>
        </div>
        <div class="small text-muted mt-2">
            * Válidos são informes que não foram cancelados nem rejeitados.
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="chart-card" wire:ignore.self>
                <div class="chart-card-header d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1">Histograma de viabilidades em aberto</h6>
                        <div class="text-muted small">Distribuição por dias desde o envio para a parceira.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="badge text-bg-light border text-dark">
                            Total: {{ $openViabilityAgeHistogram['total'] ?? 0 }}
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Média: {{ number_format($openViabilityAgeHistogram['average'] ?? 0, 1, ',', '.') }} dias
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Maior idade: {{ $openViabilityAgeHistogram['oldest'] ?? 0 }} dias
                        </div>
                    </div>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div class="pd-chart-histogram">
                        <x-grafico.apex :chart="$ageHistogramChart" :chartId="$viabilityAgeChart" :showDataLabels="true" class="w-100" />
                    </div>
                </div>
            </div>

            <div class="chart-card mt-3" wire:ignore.self>
                <div class="chart-card-header d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1">Histograma de D5 em espera</h6>
                        <div class="text-muted small">Distribuição por dias desde o despacho para a parceira.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="badge text-bg-light border text-dark">
                            Total: {{ $openD5AgeHistogram['total'] ?? 0 }}
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Em espera: {{ $openD5AgeHistogram['waiting_total'] ?? 0 }}
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Rejeitados: {{ $openD5AgeHistogram['rejected_total'] ?? 0 }}
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Passivos: {{ $openD5AgeHistogram['passive_total'] ?? 0 }}
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Média: {{ number_format($openD5AgeHistogram['average'] ?? 0, 1, ',', '.') }} dias
                        </div>
                        <div class="badge text-bg-light border text-dark">
                            Maior idade: {{ $openD5AgeHistogram['oldest'] ?? 0 }} dias
                        </div>
                    </div>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div class="pd-chart-histogram">
                        <x-grafico.apex :chart="$d5AgeHistogramChart" :chartId="$d5AgeChart" :showDataLabels="true" class="w-100" />
                    </div>
                </div>
            </div>

            <div class="chart-card mt-3" wire:ignore.self>
                <div class="chart-card-header d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1">Motivos de informes rejeitados</h6>
                        <div class="text-muted small">Quantidade de devoluções por motivo no período selecionado.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="badge text-bg-light border text-dark">
                            Total: {{ $rejectedWorkReasons['total'] ?? 0 }}
                        </div>
                    </div>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div class="pd-chart-horizontal">
                        <x-grafico.apex :chart="$rejectedWorkReasonChartConfig" :chartId="$rejectedWorkReasonChart" :showDataLabels="true" class="w-100" />
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
                    <h6 class="mb-0">Entregas de ADS a vencer</h6>
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
                                        $dueDate = $item->informed_at?->copy()->addDays(6)->endOfDay();
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
                    <div class="card-body text-center text-muted">Sem entregas de ADS vencendo no horizonte atual.</div>
                @endif

                <div class="pd-legal-note">
                    <div class="pd-legal-note-title">Base contratual</div>
                    <p>
                        <strong>ES.DT.PDN.02.01.006 - item 6.3.4.d</strong>: para a EDP ES, a CONTRATADA dispõe do
                        <strong>prazo de 6 (seis) dias</strong>, contados da conclusão da obra ou serviço, para a
                        entrega do inventário; <strong>expirado esse prazo, prevalecerá o inventário elaborado pela
                            CONTRATANTE</strong>.
                    </p>
                    <p>
                        <strong>Observação: a adoção do inventário da CONTRATANTE não exime a CONTRATADA da obrigação
                            de entrega, permanecendo a contagem do prazo até a regularização integral da
                            pendência.</strong>
                    </p>
                </div>
            </div>

            <div class="card pd-panel mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Entregas de ADS em Atraso</h6>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('partner.report.workedlist') }}">Ver informes</a>
                </div>

                @if ($tacitAdsOverdueWithoutDelivery->isNotEmpty())
                    <div class="table-responsive pd-table">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Obra</th>
                                    <th class="text-center">Venceu em</th>
                                    <th class="text-center">Dias em atraso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tacitAdsOverdueWithoutDelivery as $item)
                                    @php
                                        $dueDate = $item->Adsform?->tacit_due_at;
                                        $daysLate = $dueDate
                                            ? max(0, $dueDate->copy()->startOfDay()->diffInDays(now()->startOfDay()))
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold">{{ $item->note->note ?? '-' }}</td>
                                        <td class="text-center text-danger">{{ $dueDate?->format('d/m/Y H:i:s') }}</td>
                                        <td class="text-center fw-bold text-danger">{{ $daysLate }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-center text-muted">Sem entregas de ADS em atraso.</div>
                @endif

                <div class="pd-legal-note">
                    <div class="pd-legal-note-title">Base contratual</div>
                    <p>
                        <strong>ES.DT.PDN.02.01.006 - item 6.8 (Penalidades)</strong>: inclui, quando aplicável,
                        <strong>glosa de medições</strong>, <strong>impedimento de faturamento</strong> e
                        <strong>bloqueio de pagamento das obras correntes</strong>, nos termos do
                        <strong>item 6.3.5</strong>, até a completa regularização das pendências.
                    </p>
                </div>
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
