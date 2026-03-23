<div>
    <x-show-loading />

    {{-- ======================= FILTROS / CABEÇALHO ======================= --}}
    <div class="card mb-4">
        <div
            class="card-header edp-bg-seoweedgreen-100 text-white d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h4 class="my-1 d-flex align-items-center gap-2">
                <i class="ri-file-chart-line"></i>
                RESUMO VIABILIDADE
            </h4>

            <button type="button" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1"
                wire:click="resetFilters" wire:loading.attr="disabled">
                <i class="ri-refresh-line"></i>
                Limpar filtros
            </button>
        </div>

        <div class="card-body">
            <form class="form-inline">
                <div class="row">
                    {{-- EMPREITEIRAS (AGORA MULTI) --}}
                    <div class="col-md-4 col-12 mb-2">
                        <label class="mr-2 fw-semibold">Empreiteiras</label>
                        <select class="form-select w-100" wire:model="company_ids" multiple size="6">
                            @if ($companies && $companies->isNotEmpty())
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size:.8rem;">
                            Se nada estiver selecionado, considera TODAS
                        </small>
                    </div>

                    {{-- DATA INÍCIO --}}
                    <div class="col-md-4 col-12 mb-2">
                        <label for="start_date" class="mr-2 fw-semibold">Data de Início</label>
                        <input type="date" id="start_date" class="form-control w-100" wire:model="dt_in">
                    </div>

                    {{-- DATA FIM --}}
                    <div class="col-md-4 col-12 mb-2">
                        <label for="end_date" class="mr-2 fw-semibold">Data de Fim</label>
                        <input type="date" id="end_date" class="form-control w-100" wire:model="dt_out">
                    </div>

                    {{-- EXPORTAÇÃO POR --}}
                    <div class="col-md-4 col-12 mb-2">
                        <label for="export_by" class="mr-2 fw-semibold">Exportar por</label>
                        <select id="export_by" class="form-select w-100" wire:model="export_by">
                            <option value="note">Nota</option>
                            <option value="order">Ordem</option>
                        </select>
                    </div>

                    <div class="col-md-4 col-12 mb-2">
                        <label for="amount_basis" class="mr-2 fw-semibold">Base monetária</label>
                        <select id="amount_basis" class="form-select w-100" wire:model="amount_basis">
                            <option value="moa">MOA - Mão de Obra em Aberto</option>
                            <option value="mop">MOP - Mão de Obra Prevista</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ======================= CARDS KPI ======================= --}}
    <div class="row g-3 mb-4">
        {{-- Valor Realizado --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="modern-card-title">
                            <i class="ri-checkbox-circle-line me-1"></i>
                            Valor Realizado
                        </div>
                        <span class="badge bg-light text-dark">
                            {{ $summary['periodLabel'] ?? '' }}
                        </span>
                    </div>

                    <div class="modern-card-icon"
                        style="background:linear-gradient(135deg,#56ab2f,#a8e6cf);color:#fff;">
                        <i class="ri-money-dollar-circle-line fs-4"></i>
                    </div>

                    <div class="modern-card-value">
                        R$ {{ number_format($summary['realizedValue'] ?? 0, 2, ',', '.') }}
                    </div>

                    <div class="modern-card-growth growth-positive">
                        <i class="ri-check-line me-1"></i>
                        Entregue no período filtrado ({{ $summary['amountBasisLabel'] ?? 'MOA' }})
                    </div>
                </div>
            </div>
        </div>

        {{-- Valor Não Realizado --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="modern-card-title">
                            <i class="ri-alert-line me-1"></i>
                            Valor Não Realizado
                        </div>
                        <span class="badge bg-light text-dark">
                            {{ $summary['periodLabel'] ?? '' }}
                        </span>
                    </div>

                    <div class="modern-card-icon"
                        style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;">
                        <i class="ri-error-warning-line fs-4"></i>
                    </div>

                    <div class="modern-card-value text-warning">
                        R$ {{ number_format($summary['notRealizedValue'] ?? 0, 2, ',', '.') }}
                    </div>

                    <div class="modern-card-growth growth-negative">
                        <i class="ri-arrow-down-line me-1"></i>
                        Potencialmente não executado ({{ $summary['amountBasisLabel'] ?? 'MOA' }})
                    </div>
                </div>
            </div>
        </div>

        {{-- Penalidade Prevista --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="modern-card-title">
                            <i class="ri-scales-2-line me-1"></i>
                            Penalidade Prevista
                        </div>
                        <span class="badge bg-light text-dark">
                            {{ $summary['periodLabel'] ?? '' }}
                        </span>
                    </div>

                    <div class="modern-card-icon"
                        style="background:linear-gradient(135deg,#ffd200,#f7971e);color:#fff;">
                        <i class="ri-hand-coin-line fs-4"></i>
                    </div>

                    <div class="modern-card-value text-danger">
                        R$ {{ number_format($summary['penaltyValue'] ?? 0, 2, ',', '.') }}
                    </div>

                    <div class="modern-card-growth text-danger fw-semibold" style="font-size:.9rem;">
                        Multa estimada (1% s/ Não Realizado {{ $summary['amountBasisLabel'] ?? 'MOA' }})
                    </div>
                </div>
            </div>
        </div>

        {{-- SLA Médio --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="modern-card-title">
                            <i class="ri-time-line me-1"></i>
                            Tempo Médio de Conclusão
                        </div>
                        <span class="badge bg-light text-dark">
                            sem tácito
                        </span>
                    </div>

                    <div class="modern-card-icon"
                        style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;">
                        <i class="ri-timer-2-line fs-4"></i>
                    </div>

                    <div class="modern-card-value">
                        {{ $summary['avgCloseTimeHours'] ?? 0 }}h
                    </div>

                    <div class="modern-card-growth text-muted fw-semibold" style="font-size:.9rem;">
                        (~{{ $summary['avgCloseTimeDays'] ?? 0 }} dia(s) corridos)
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================= LINHA: OPERACIONAL + RANKING ======================= --}}
    <div class="row g-3 mb-4">

        {{-- Gráfico Operacional (6m + previsão) --}}
        <div class="col-12 col-lg-6">
            <div class="chart-card h-100">
                <div class="chart-card-header d-flex justify-content-between align-items-start">
                    <h5 class="chart-card-title mb-0 d-flex align-items-center gap-2">
                        <i class="ri-bar-chart-2-line"></i>
                        <span>Backlog x Entrega (6 meses + previsão)</span>
                    </h5>
                    @if (!empty($company_ids))
                        <span class="badge bg-info text-dark">Filtradas</span>
                    @else
                        <span class="badge bg-secondary text-light">Todas</span>
                    @endif
                </div>

                <div class="chart-card-body">
                    <div style="max-height: 360px;">
                        {{-- IMPORTANTE: chartId precisa bater com o id interno do ApexCharts --}}
                        <x-grafico.apex :chart="$chartSLA" chartId="chartSLA" class="w-100" />
                    </div>
                    <div class="small text-muted mt-2">
                        Barras verdes = viabilidades fechadas no mês (ativo).<br>
                        Barras amarelas = estoque pendente ao fim do mês (passivo).<br>
                        Última coluna “(proj)” é previsão.
                    </div>
                </div>
            </div>
        </div>

        {{-- Ranking de Empreiteiras (todas as filtradas) --}}
        <div class="col-12 col-lg-6">
            <div class="activity-card h-100">
                <div class="activity-header d-flex justify-content-between align-items-center">
                    <h5 class="activity-title mb-0 d-flex align-items-center gap-2">
                        <i class="ri-building-4-line"></i>
                        <span>Empreiteiras no Período</span>
                    </h5>

                    @if (!empty($company_ids))
                        <span class="badge bg-info text-dark">Filtradas</span>
                    @else
                        <span class="badge bg-secondary text-light">Todas</span>
                    @endif
                </div>

                <div class="activity-body">
                    @if ($topCompanies && $topCompanies->isNotEmpty())
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Empreiteira</th>
                                    <th class="text-end text-success">Realizado (R$)</th>
                                    <th class="text-end text-warning">Não Realizado (R$)</th>
                                    <th class="text-end text-danger">Penalidade (R$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topCompanies as $row)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $row['company_name'] ?? 'N/A' }}
                                        </td>
                                        <td class="text-end text-success fw-semibold">
                                            {{ number_format($row['realizado'] ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end text-warning fw-semibold">
                                            {{ number_format($row['nao_realizado'] ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end text-danger fw-semibold">
                                            {{ number_format($row['penalidade'] ?? 0, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="ri-emotion-sad-line" style="font-size: 3rem; color: #dee2e6;"></i>
                            </div>
                            <p class="text-muted mb-0">Nenhuma empreiteira com movimentação nesse período.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ======================= GRÁFICO: CONCLUSÕES DIÁRIAS ======================= --}}
    <div class="chart-card mb-4">
        <div class="chart-card-header d-flex justify-content-between align-items-start">
            <h5 class="chart-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-bar-chart-line"></i>
                <span>Conclusões Diárias (Realizado x Não Realizado x Prevista)</span>
            </h5>
            <span class="badge bg-primary">
                {{ $summary['periodLabel'] ?? '' }}
            </span>
        </div>
        <div class="chart-card-body">
            <div style="max-height: 400px;">
                <x-grafico.apex :chart="$chartDaily" chartId="chartDaily" class="w-100" />
            </div>
            <div class="small text-muted mt-2">
                Barras = Quantidade diária (Realizado, Não Realizado e Conclusão Prevista).<br>
                Linhas = Valor financeiro associado (R$).
            </div>
        </div>
    </div>

    {{-- ======================= GRÁFICO: ÚLTIMOS 12 MESES ======================= --}}
    <div class="chart-card mb-4">
        <div class="chart-card-header d-flex justify-content-between align-items-start">
            <h5 class="chart-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-line-chart-line"></i>
                <span>Últimos 12 Meses (Realizado x Não Realizado)</span>
            </h5>
            @if (!empty($company_ids))
                <span class="badge bg-info text-dark">Filtradas</span>
            @else
                <span class="badge bg-secondary text-light">Todas</span>
            @endif
        </div>
        <div class="chart-card-body">
            <div style="max-height: 400px;">
                <x-grafico.apex :chart="$chartMonthly" chartId="chartMonthly" class="w-100" />
            </div>
            <div class="small text-muted mt-2">
                Barras = Quantidade mensal de viabilidades concluídas.<br>
                Linhas = Valor realizado / não realizado (R$) no mês.
            </div>
        </div>
    </div>

    {{-- ======================= LISTAS DETALHADAS ======================= --}}
    <div class="row g-3">
        {{-- REALIZADO --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header py-0 text-bg-success d-flex justify-content-between align-items-center">
                    <h5 class="my-1 d-flex align-items-center gap-2">
                        <i class="ri-checkbox-circle-line"></i>
                        <span>Realizado</span>
                        <small class="fw-normal">
                            | {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }}
                            -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}
                        </small>
                    </h5>

                    <button wire:click="exportExcelRealized"
                        class="btn btn-light btn-sm text-success d-flex align-items-center gap-1"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="exportExcelRealized">
                            <i class="ri-file-excel-2-line"></i>
                            Exportar
                        </span>
                        <span wire:loading wire:target="exportExcelRealized">
                            <i class="ri-loader-4-line animate-spin"></i>
                            ...
                        </span>
                    </button>
                </div>

                <div class="card-body">
                    @if ($realizeds->isNotEmpty())
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nota</th>
                                    <th>Empreiteira</th>
                                    <th class="text-end">Valor {{ $summary['amountBasisLabel'] ?? 'MOA' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($realizeds as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->Note->note ?? 'N/A' }}</td>
                                        <td>{{ $row->Company->name ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            R$ {{ number_format($row->money_base ?? 0, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-warning my-2">
                            Nenhum registro encontrado para o período.
                        </div>
                    @endif
                </div>

                <div class="card-footer bg-white border-0 pt-0">
                    {{ $realizeds->links() }}
                </div>
            </div>
        </div>

        {{-- NÃO REALIZADO --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header py-0 text-bg-warning d-flex justify-content-between align-items-center">
                    <h5 class="my-1 d-flex align-items-center gap-2">
                        <i class="ri-alert-line"></i>
                        <span>Não Realizado</span>
                        <small class="fw-normal">
                            | {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }}
                            -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}
                        </small>
                    </h5>

                    <button wire:click="exportExcelNotRealized"
                        class="btn btn-light btn-sm text-warning d-flex align-items-center gap-1"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="exportExcelNotRealized">
                            <i class="ri-file-excel-2-line"></i>
                            Exportar
                        </span>
                        <span wire:loading wire:target="exportExcelNotRealized">
                            <i class="ri-loader-4-line animate-spin"></i>
                            ...
                        </span>
                    </button>
                </div>

                <div class="card-body">
                    @if ($notRealizeds->isNotEmpty())
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nota</th>
                                    <th>Empreiteira</th>
                                    <th class="text-end">Valor {{ $summary['amountBasisLabel'] ?? 'MOA' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notRealizeds as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->Note->note ?? 'N/A' }}</td>
                                        <td>{{ $row->Company->name ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            R$ {{ number_format($row->money_base ?? 0, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-warning my-2">
                            Nenhum registro encontrado para o período.
                        </div>
                    @endif
                </div>

                <div class="card-footer bg-white border-0 pt-0">
                    {{ $notRealizeds->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- ======================= SCRIPTS DINÂMICOS PARA ATUALIZAR GRÁFICOS ======================= --}}
    @push('scripts')
        <script>
            /**
             * Converte os datasets (formato interno PHP chartXxxProperty)
             * para o formato ApexCharts esperado em updateSeries/updateOptions.
             */
            function datasetsToSeries(datasets) {
                return datasets.map(ds => {
                    return {
                        name: ds.label ?? '',
                        data: ds.data ?? [],
                        type: ds.type ?? 'bar'
                    };
                });
            }

            function buildApexUpdate(chartPayload) {
                const labels = chartPayload.data.labels || [];
                const datasets = chartPayload.data.datasets || [];

                return {
                    series: datasetsToSeries(datasets),
                    xaxis: {
                        categories: labels
                    },
                };
            }

            // DAILY
            window.addEventListener('grafico-atualizar-chartDaily', (event) => {
                const updateConf = buildApexUpdate(event.detail);

                if (window.ApexCharts && window.ApexCharts.exec) {
                    ApexCharts.exec('chartDaily', 'updateOptions', {
                        xaxis: updateConf.xaxis,
                    }, false, true);

                    ApexCharts.exec('chartDaily', 'updateSeries', updateConf.series, true);
                }
            });

            // MONTHLY
            window.addEventListener('grafico-atualizar-chartMonthly', (event) => {
                const updateConf = buildApexUpdate(event.detail);

                if (window.ApexCharts && window.ApexCharts.exec) {
                    ApexCharts.exec('chartMonthly', 'updateOptions', {
                        xaxis: updateConf.xaxis,
                    }, false, true);

                    ApexCharts.exec('chartMonthly', 'updateSeries', updateConf.series, true);
                }
            });

            // SLA / backlog x entrega
            window.addEventListener('grafico-atualizar-chartSLA', (event) => {
                const updateConf = buildApexUpdate(event.detail);

                if (window.ApexCharts && window.ApexCharts.exec) {
                    ApexCharts.exec('chartSLA', 'updateOptions', {
                        xaxis: updateConf.xaxis,
                    }, false, true);

                    ApexCharts.exec('chartSLA', 'updateSeries', updateConf.series, true);
                }
            });
        </script>
    @endpush

    {{-- ======================= ESTILOS LOCAIS ======================= --}}
    @push('css')
        <style>
            .modern-card {
                background: #fff;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                transition: all .3s ease;
                overflow: hidden;
                position: relative;
            }

            .modern-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #667eea, #764ba2);
            }

            .modern-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            }

            .modern-card-body {
                padding: 1.25rem 1.5rem;
            }

            .modern-card-title {
                font-size: .8rem;
                font-weight: 600;
                color: #6c757d;
                margin-bottom: .5rem;
                text-transform: uppercase;
                letter-spacing: .5px;
                line-height: 1.2;
            }

            .modern-card-icon {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: .75rem;
                font-weight: 600;
            }

            .modern-card-value {
                font-size: 2rem;
                font-weight: 700;
                color: #2c3e50;
                line-height: 1.2;
                margin-bottom: .5rem;
                word-break: break-word;
            }

            .modern-card-growth {
                font-size: .85rem;
                font-weight: 600;
            }

            .growth-positive {
                color: #28a745;
            }

            .growth-negative {
                color: #dc3545;
            }

            .chart-card {
                background: #fff;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }

            .chart-card-header {
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
                padding: 1rem 1.25rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .chart-card-title {
                font-size: 1rem;
                font-weight: 600;
                color: #2c3e50;
                margin: 0;
                line-height: 1.3;
            }

            .chart-card-body {
                padding: 1rem 1.25rem 1.25rem;
            }

            .activity-card {
                background: #fff;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }

            .activity-header {
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
                padding: 1rem 1.25rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .activity-title {
                font-size: 1rem;
                font-weight: 600;
                color: #2c3e50;
                margin: 0;
                line-height: 1.3;
            }

            .activity-body {
                padding: 1rem 1.25rem;
                max-height: 400px;
                overflow-y: auto;
            }

            @media (max-width:768px) {
                .modern-card-value {
                    font-size: 1.6rem;
                }
            }
        </style>
    @endpush
</div>
