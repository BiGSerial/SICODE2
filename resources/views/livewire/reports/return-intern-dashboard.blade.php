<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-bg: #f7f8fb;
                --ri-surface: #ffffff;
                --ri-muted: #6b7280;
                --ri-ink: #1f2933;
                --ri-accent: #0f766e;
                --ri-border: #e5e7eb;
                background: radial-gradient(circle at 12% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 15%, #ecfeff, transparent 35%),
                    var(--ri-bg);
                padding: 1.5rem 0;
            }

            .ri-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.6rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1.5rem;
            }

            .ri-header h1 {
                font-size: 1.9rem;
                font-weight: 700;
                margin: 0;
                letter-spacing: 0.02em;
            }

            .ri-header .meta {
                color: rgba(248, 250, 252, 0.75);
                font-size: 0.95rem;
            }

            .filters-grid .filter-card {
                background-color: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: 0.9rem;
                padding: 1rem 1.25rem;
                height: 100%;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .filters-grid .filter-card h6 {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-weight: 600;
                color: var(--ri-muted);
            }

            .summary-bar {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: 0.9rem;
                padding: 0.75rem 1.25rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            }

            .summary-bar .summary-item {
                font-size: 0.92rem;
                color: var(--ri-muted);
            }

            .summary-bar .summary-item strong {
                color: var(--ri-ink);
            }

            .table-card {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: 1rem;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .table-card .table thead th {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                white-space: nowrap;
            }

            .table-card .table tbody td {
                font-size: 0.92rem;
            }

            .metric-card {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: none;
                margin-bottom: 1rem;
                position: relative;
                overflow: hidden;
            }

            .metric-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #667eea, #0f766e);
            }

            .metric-card-body {
                padding: 1.2rem 1.4rem;
            }

            .metric-label {
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.06em;
                color: #6c757d;
                font-weight: 600;
            }

            .metric-value {
                font-size: 1.8rem;
                font-weight: 700;
                color: #1f2937;
            }

            .chart-card {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: none;
            }

            .chart-card-header {
                padding: 1rem 1.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(15, 118, 110, 0.08));
            }

            .chart-card-body {
                padding: 1.5rem;
            }

            .ri-section-title {
                font-weight: 700;
                letter-spacing: 0.02em;
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
                <h1>Relatorio Retorno Interno</h1>
                <div class="meta">Indicadores administrativos e acompanhamento detalhado das ocorrencias.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div>
                    <div class="meta">Inicio</div>
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
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Total retornos</div>
                        <div class="metric-value">{{ $summary['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Concluidos</div>
                        <div class="metric-value">{{ $summary['completed'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Em aberto</div>
                        <div class="metric-value">{{ $summary['open'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Com producao</div>
                        <div class="metric-value">{{ $summary['with_production'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo medio de resolucao</div>
                        <div class="metric-value">{{ $summary['avg_resolution_human'] }}</div>
                        <div class="text-muted small">Criado em x concluido</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Reacao do despachante</div>
                        <div class="metric-value">{{ $summary['avg_reaction_human'] }}</div>
                        <div class="text-muted small">Criado em x att_at producao</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo medio em producao</div>
                        <div class="metric-value">{{ $summary['avg_execution_human'] }}</div>
                        <div class="text-muted small">att_at x completed_at producao</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
        <div class="chart-card h-100">
            <div class="chart-card-header">
                <h6 class="mb-0"><i class="ri-pie-chart-2-line me-2"></i>Origem</h6>
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
                <h6 class="mb-0"><i class="ri-line-chart-line me-2"></i>Volume diario</h6>
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
                        <h6 class="mb-0"><i class="ri-building-line me-2"></i>Empresas executoras</h6>
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
                        <h6 class="mb-0"><i class="ri-checkbox-multiple-line me-2"></i>Status producao</h6>
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
                <h6 class="mb-0"><i class="ri-stack-line me-2"></i>Servicos com mais retornos</h6>
            </div>
            <div class="chart-card-body" wire:ignore>
                <div style="min-height: 320px;">
                    <x-grafico.apex :chart="$servicesChart" chartId="ri_servicos" class="w-100" />
                </div>
            </div>
        </div>
    </div>
</div>
