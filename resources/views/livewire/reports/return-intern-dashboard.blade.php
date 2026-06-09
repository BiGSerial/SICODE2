<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-navy: #14283d;
                --ri-blue: #315f87;
                --ri-bg: #f3f6f8;
                --ri-surface: #ffffff;
                --ri-muted: #667684;
                --ri-ink: #22303d;
                --ri-border: #dce5ea;
                background: var(--ri-bg);
                font-family: var(--bs-body-font-family);
                min-height: calc(100vh - 70px);
                padding: 1.35rem 0 2rem;
            }

            .ri-page {
                font-family: var(--bs-body-font-family) !important;
            }

            .ri-page * {
                font-family: inherit;
            }

            .ri-page i[class^="ri-"],
            .ri-page i[class*=" ri-"] {
                font-family: "remixicon" !important;
            }

            .ri-header {
                background: linear-gradient(125deg, var(--ri-navy), #28536f 72%, #347985);
                color: #fff;
                border-radius: 1rem;
                padding: 1.4rem 1.6rem;
                box-shadow: 0 14px 30px rgba(20, 40, 61, .18);
                margin-bottom: 1rem;
            }

            .ri-header h1 {
                color: #fff;
                font-size: 1.75rem;
                font-weight: 700;
                margin: 0;
            }

            .ri-header .meta {
                color: rgba(255, 255, 255, .65);
                font-size: .875rem;
            }

            .ri-eyebrow {
                color: #9edce0;
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .12em;
                text-transform: uppercase;
            }

            .ri-header .form-control {
                border: 1px solid rgba(255, 255, 255, .35);
            }

            .metric-card,
            .chart-card {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: .9rem;
                box-shadow: 0 8px 20px rgba(33, 52, 68, .05);
                overflow: hidden;
            }

            .metric-card {
                min-height: 106px;
                position: relative;
            }

            .metric-card::before {
                background: var(--metric-color, var(--ri-blue));
                bottom: 0;
                content: "";
                height: 4px;
                left: 0;
                position: absolute;
                right: 0;
            }

            .metric-card-body {
                padding: 1rem 1.1rem;
            }

            .metric-label {
                color: var(--ri-muted);
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .07em;
                text-transform: uppercase;
            }

            .metric-value {
                color: var(--ri-ink);
                font-size: 1.65rem;
                font-weight: 750;
                line-height: 1.2;
                margin-top: .35rem;
            }

            .chart-card-header {
                background: #fff;
                border-bottom: 1px solid var(--ri-border);
                color: var(--ri-ink);
                padding: 1rem 1.15rem;
            }

            .chart-card-body {
                padding: 1.15rem;
            }

            @media (max-width: 991px) {
                .ri-header {
                    padding: 1.25rem;
                }

                .ri-header h1 {
                    font-size: 1.6rem;
                }
            }
        </style>
    @endpush

    <div class="container-fluid">
        <div class="ri-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="ri-eyebrow">Reports · análise histórica</div>
                <h1>Indicadores de Retorno Interno</h1>
                <div class="meta">Evolução, origem e desempenho dos retornos internos no período selecionado.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div>
                    <div class="meta">Início</div>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_in"
                        max="{{ date('Y-m-d') }}">
                </div>
                <div>
                    <div class="meta">Fim</div>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_out"
                        max="{{ date('Y-m-d') }}">
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card" style="--metric-color:#315f87">
                    <div class="metric-card-body">
                        <div class="metric-label">Total retornos</div>
                        <div class="metric-value">{{ $summary['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card" style="--metric-color:#16836f">
                    <div class="metric-card-body">
                        <div class="metric-label">Concluídos</div>
                        <div class="metric-value">{{ $summary['completed'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card" style="--metric-color:#d97706">
                    <div class="metric-card-body">
                        <div class="metric-label">Em aberto</div>
                        <div class="metric-value">{{ $summary['open'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card" style="--metric-color:#7c3aed">
                    <div class="metric-card-body">
                        <div class="metric-label">Com produção</div>
                        <div class="metric-value">{{ $summary['with_production'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card" style="--metric-color:#536471">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo médio de resolução</div>
                        <div class="metric-value">{{ $summary['avg_resolution_human'] }}</div>
                        <div class="text-muted small">Da criação até a conclusão</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card" style="--metric-color:#315f87">
                    <div class="metric-card-body">
                        <div class="metric-label">Reação do despachante</div>
                        <div class="metric-value">{{ $summary['avg_reaction_human'] }}</div>
                        <div class="text-muted small">Da criação até a atuação na produção</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card" style="--metric-color:#16836f">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo médio em produção</div>
                        <div class="metric-value">{{ $summary['avg_execution_human'] }}</div>
                        <div class="text-muted small">Da atuação até a conclusão da produção</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0 fw-bold"><i class="ri-pie-chart-2-line me-2"></i>Origem</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 280px;">
                            <x-grafico.apex :chart="$originChart" chartId="ri_origem" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0 fw-bold"><i class="ri-line-chart-line me-2"></i>Volume diário</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 280px;">
                            <x-grafico.apex :chart="$dailyChart" chartId="ri_diario" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0 fw-bold"><i class="ri-building-line me-2"></i>Empresas executoras</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 300px;">
                            <x-grafico.apex :chart="$companiesChart" chartId="ri_empresas" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0 fw-bold"><i class="ri-checkbox-multiple-line me-2"></i>Status da produção</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 300px;">
                            <x-grafico.apex :chart="$statusChart" chartId="ri_status" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-card mb-5">
            <div class="chart-card-header">
                <h6 class="mb-0 fw-bold"><i class="ri-stack-line me-2"></i>Serviços com mais retornos</h6>
            </div>
            <div class="chart-card-body" wire:ignore>
                <div style="min-height: 320px;">
                    <x-grafico.apex :chart="$servicesChart" chartId="ri_servicos" class="w-100" />
                </div>
            </div>
        </div>
    </div>
</div>
