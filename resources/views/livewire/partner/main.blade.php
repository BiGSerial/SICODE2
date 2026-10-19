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
    @include('livewire.partner.partials.company-filter')

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

        .pd-exec-tile { border: 1px solid var(--pd-border); border-radius: 14px; background: var(--pd-surface); box-shadow: var(--pd-shadow); padding: 1rem 1.1rem; height: 100%; border-left: 6px solid #94a3b8; }
        .pd-exec-tile.ok { border-left-color: #15803d; }
        .pd-exec-tile.warn { border-left-color: var(--pd-warn); }
        .pd-exec-tile.bad { border-left-color: var(--pd-danger); }
        .pd-exec-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--pd-muted); }
        .pd-exec-value { font-size: 2rem; font-weight: 800; line-height: 1.1; color: var(--pd-ink); }
        .pd-exec-tile.ok .pd-exec-value { color: #15803d; }
        .pd-exec-tile.warn .pd-exec-value { color: var(--pd-warn); }
        .pd-exec-tile.bad .pd-exec-value { color: var(--pd-danger); }
        .pd-exec-note { font-size: 0.78rem; color: var(--pd-muted); }
        .pd-branch-table th, .pd-branch-table td { white-space: nowrap; vertical-align: middle; }
        .pd-branch-table thead th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--pd-muted); }
        .pd-branch-table .grp { text-align: center; border-bottom: 2px solid var(--pd-primary); color: var(--pd-ink); }
        .pd-branch-table tfoot td { font-weight: 800; background: #f1f5f9; border-top: 2px solid var(--pd-ink); }
        .pd-cell-bad { color: var(--pd-danger); font-weight: 700; }
        .pd-cell-warn { color: var(--pd-warn); font-weight: 700; }
        .pd-cell-zero { color: #94a3b8; }
        .pd-bar { height: 6px; border-radius: 4px; background: #e2e8f0; overflow: hidden; min-width: 70px; }
        .pd-bar > span { display: block; height: 100%; }

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

@php
        $pct = fn ($part, $whole) => $whole > 0 ? round(($part / $whole) * 100) : null;
        $tone = fn ($value, $good, $warn, $higherIsBetter = true) => $value === null
            ? ''
            : ($higherIsBetter
                ? ($value >= $good ? 'ok' : ($value >= $warn ? 'warn' : 'bad'))
                : ($value <= $good ? 'ok' : ($value <= $warn ? 'warn' : 'bad')));
        $actionKeys = ['work_without_ads_overdue', 'work_without_ads_due_soon', 'viability_due_soon', 'viability_rejected_waiting', 'd5_returned', 'informs_rejected'];
        $actionTotal = collect($actionKeys)->sum(fn ($key) => (int) ($kpis[$key] ?? 0));
        $adsRate = $pct($workReportKpis['ads_delivered_total'] ?? 0, $workReportKpis['valid_informed_total'] ?? 0);
        $rejectRate = $pct($workReportKpis['canceled_rejected_total'] ?? 0, $workReportKpis['informed_total'] ?? 0);
        $partialRate = $pct($workReportKpis['partials_completed_total'] ?? 0, $workReportKpis['partials_total'] ?? 0);
        $selectedCompany = (string) ($partnerCompanyFilter ?? '') !== ''
            ? $this->partnerCompanyFilterOptions()->firstWhere('id', $partnerCompanyFilter)
            : null;
    @endphp

    @if ($selectedCompany)
        <div class="alert alert-info d-flex justify-content-between align-items-center py-2">
            <span>Exibindo somente <strong>{{ $selectedCompany->name }}</strong>.</span>
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="$set('partnerCompanyFilter', '')">
                Ver todas as empresas
            </button>
        </div>
    @endif

    <div class="mb-1 pd-section-title">Resumo executivo{{ $selectedCompany ? ' · ' . $selectedCompany->name : (count($companyBreakdown) ? ' · total consolidado' : '') }}</div>
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="pd-exec-tile {{ $actionTotal === 0 ? 'ok' : ($actionTotal <= 10 ? 'warn' : 'bad') }}">
                <div class="pd-exec-label">Exigem ação agora</div>
                <div class="pd-exec-value">{{ $actionTotal }}</div>
                <div class="pd-exec-note">
                    {{ $kpis['work_without_ads_overdue'] ?? 0 }} ADS em atraso ·
                    {{ ($kpis['work_without_ads_due_soon'] ?? 0) + ($kpis['viability_due_soon'] ?? 0) }} a vencer ·
                    {{ ($kpis['informs_rejected'] ?? 0) + ($kpis['viability_rejected_waiting'] ?? 0) + ($kpis['d5_returned'] ?? 0) }} devolvidos
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="pd-exec-tile {{ $tone($adsRate, 90, 70) }}">
                <div class="pd-exec-label">Entrega de ADS no período</div>
                <div class="pd-exec-value">{{ $adsRate === null ? '—' : $adsRate . '%' }}</div>
                <div class="pd-exec-note">{{ $workReportKpis['ads_delivered_total'] ?? 0 }} de {{ $workReportKpis['valid_informed_total'] ?? 0 }} informes válidos</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="pd-exec-tile {{ $tone($rejectRate, 5, 15, false) }}">
                <div class="pd-exec-label">Rejeição/cancelamento de informes</div>
                <div class="pd-exec-value">{{ $rejectRate === null ? '—' : $rejectRate . '%' }}</div>
                <div class="pd-exec-note">{{ $workReportKpis['canceled_rejected_total'] ?? 0 }} de {{ $workReportKpis['informed_total'] ?? 0 }} informes do período</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="pd-exec-tile {{ $tone($partialRate, 70, 40) }}">
                <div class="pd-exec-label">Parciais finalizadas</div>
                <div class="pd-exec-value">{{ $partialRate === null ? '—' : $partialRate . '%' }}</div>
                <div class="pd-exec-note">{{ $workReportKpis['partials_completed_total'] ?? 0 }} de {{ $workReportKpis['partials_total'] ?? 0 }} · R$ {{ number_format($workReportKpis['partials_amount_total'] ?? 0, 2, ',', '.') }} pagos</div>
            </div>
        </div>
    </div>

    @if (count($companyBreakdown))
        @php
            $rows = collect($companyBreakdown)->map(function ($row) use ($pct, $actionKeys) {
                $row['action'] = collect($actionKeys)->sum(fn ($key) => (int) ($row['kpis'][$key] ?? 0));
                $row['ads_rate'] = $pct($row['period']['ads_delivered_total'] ?? 0, $row['period']['valid_informed_total'] ?? 0);
                $row['reject_rate'] = $pct($row['period']['canceled_rejected_total'] ?? 0, $row['period']['informed_total'] ?? 0);

                return $row;
            });
            $worst = $rows->sortByDesc('action')->first();
            $sum = fn ($group, $key) => $rows->sum(fn ($row) => (float) ($row[$group][$key] ?? 0));
            $cell = fn ($value, $class = 'pd-cell-bad') => $value > 0 ? $class : 'pd-cell-zero';
        @endphp
        <div class="card pd-panel mb-3">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-1">
                <div>
                    <h6 class="mb-0">Desempenho por empresa / filial</h6>
                    <div class="small text-muted">Período e pendências de cada empresa, com o total consolidado no rodapé. Clique em “Detalhar” para abrir o painel só daquela empresa.</div>
                </div>
                @if ($worst && $worst['action'] > 0)
                    <div class="small align-self-md-center">
                        <span class="badge text-bg-danger">Maior atenção</span> {{ $worst['name'] }} ({{ $worst['action'] }} itens)
                    </div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 pd-branch-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Empresa</th>
                            <th colspan="6" class="grp">Período</th>
                            <th colspan="7" class="grp">Pendências atuais</th>
                            <th rowspan="2"></th>
                        </tr>
                        <tr>
                            <th class="text-end">Obras informadas</th>
                            <th class="text-end">Rejeição/cancel.</th>
                            <th>ADS entregues</th>
                            <th class="text-end">Valor ADS</th>
                            <th class="text-end">Parciais</th>
                            <th class="text-end">Valor parciais</th>
                            <th class="text-end">ADS atrasadas</th>
                            <th class="text-end">A vencer</th>
                            <th class="text-end">Viab. pend.</th>
                            <th class="text-end">D5 pend.</th>
                            <th class="text-end">D5 devolv.</th>
                            <th class="text-end">Inf. rejeit.</th>
                            <th class="text-end">Reclam.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $k = $row['kpis'];
                                $p = $row['period'];
                                $dueSoon = (int) ($k['work_without_ads_due_soon'] ?? 0) + (int) ($k['viability_due_soon'] ?? 0);
                                $barColor = $row['ads_rate'] === null ? '#cbd5e1' : ($row['ads_rate'] >= 90 ? '#15803d' : ($row['ads_rate'] >= 70 ? '#b45309' : '#b91c1c'));
                            @endphp
                            <tr wire:key="branch-{{ $row['id'] }}">
                                <td class="fw-semibold">
                                    {{ $row['name'] }}
                                    @if ($row['is_branch'])
                                        <span class="badge text-bg-light border ms-1">Filial</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $p['informed_total'] ?? 0 }}</td>
                                <td class="text-end {{ ($row['reject_rate'] ?? 0) > 15 ? 'pd-cell-bad' : (($row['reject_rate'] ?? 0) > 5 ? 'pd-cell-warn' : '') }}">{{ $row['reject_rate'] === null ? '—' : $row['reject_rate'] . '%' }}</td>
                                <td>
                                    <div class="small">{{ $row['ads_rate'] === null ? '—' : $row['ads_rate'] . '%' }}</div>
                                    <div class="pd-bar"><span style="width: {{ $row['ads_rate'] ?? 0 }}%; background: {{ $barColor }}"></span></div>
                                </td>
                                <td class="text-end">R$ {{ number_format($p['ads_amount_total'] ?? 0, 2, ',', '.') }}</td>
                                <td class="text-end">{{ $p['partials_total'] ?? 0 }}</td>
                                <td class="text-end">R$ {{ number_format($p['partials_amount_total'] ?? 0, 2, ',', '.') }}</td>
                                <td class="text-end {{ $cell($k['work_without_ads_overdue'] ?? 0) }}">{{ $k['work_without_ads_overdue'] ?? 0 }}</td>
                                <td class="text-end {{ $cell($dueSoon, 'pd-cell-warn') }}">{{ $dueSoon }}</td>
                                <td class="text-end">{{ $k['pending_viability'] ?? 0 }}</td>
                                <td class="text-end">{{ $k['d5_pending'] ?? 0 }}</td>
                                <td class="text-end {{ $cell($k['d5_returned'] ?? 0, 'pd-cell-warn') }}">{{ $k['d5_returned'] ?? 0 }}</td>
                                <td class="text-end {{ $cell($k['informs_rejected'] ?? 0) }}">{{ $k['informs_rejected'] ?? 0 }}</td>
                                <td class="text-end {{ $cell($k['reclaims_pending'] ?? 0, 'pd-cell-warn') }}">{{ $k['reclaims_pending'] ?? 0 }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="$set('partnerCompanyFilter', '{{ $row['id'] }}')">Detalhar</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total consolidado</td>
                            <td class="text-end">{{ (int) $sum('period', 'informed_total') }}</td>
                            <td class="text-end">{{ $rejectRate === null ? '—' : $rejectRate . '%' }}</td>
                            <td>{{ $adsRate === null ? '—' : $adsRate . '%' }}</td>
                            <td class="text-end">R$ {{ number_format($sum('period', 'ads_amount_total'), 2, ',', '.') }}</td>
                            <td class="text-end">{{ (int) $sum('period', 'partials_total') }}</td>
                            <td class="text-end">R$ {{ number_format($sum('period', 'partials_amount_total'), 2, ',', '.') }}</td>
                            <td class="text-end">{{ (int) $sum('kpis', 'work_without_ads_overdue') }}</td>
                            <td class="text-end">{{ (int) ($sum('kpis', 'work_without_ads_due_soon') + $sum('kpis', 'viability_due_soon')) }}</td>
                            <td class="text-end">{{ (int) $sum('kpis', 'pending_viability') }}</td>
                            <td class="text-end">{{ (int) $sum('kpis', 'd5_pending') }}</td>
                            <td class="text-end">{{ (int) $sum('kpis', 'd5_returned') }}</td>
                            <td class="text-end">{{ (int) $sum('kpis', 'informs_rejected') }}</td>
                            <td class="text-end">{{ (int) $sum('kpis', 'reclaims_pending') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <div class="mb-1 pd-section-title">Pendências detalhadas</div>
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
            <div class="metric-card danger">
                <div class="metric-label">Entregas de ADS em atraso</div>
                <div class="metric-value">{{ $kpis['work_without_ads_overdue'] ?? 0 }}</div>
                <div class="metric-subtitle">Sem ADS há mais de 3 dias</div>
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
                {!! app(\App\Services\Partner\DashboardLegalNotices::class)->valuesDisclaimerText() !!}
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
                                        $dueDate = $item->ads_due_at;
                                        $daysLeft = $item->ads_days_left;
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

                @php
                    $dashboardLegalNotices = app(\App\Services\Partner\DashboardLegalNotices::class);
                @endphp
                <div class="pd-legal-note">
                    <div class="pd-legal-note-title">Base contratual</div>
                    {!! $dashboardLegalNotices->adsDueText() !!}
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
                                        $daysLate = $item->ads_late_days ?? 0;
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
                    {!! $dashboardLegalNotices->adsOverdueText() !!}
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
