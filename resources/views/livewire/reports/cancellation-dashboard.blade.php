<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-bg: #f7f8fb;
                --ri-surface: #ffffff;
                --ri-muted: #6b7280;
                --ri-ink: #1f2933;
                --ri-border: #e5e7eb;
                background: radial-gradient(circle at 12% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 15%, #ecfeff, transparent 35%),
                    var(--ri-bg);
                padding: 1.5rem 0;
                font-family: var(--bs-body-font-family, var(--bs-font-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif));
            }

            .ri-page,
            .ri-page * {
                font-family: var(--bs-body-font-family, var(--bs-font-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif)) !important;
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
                background: linear-gradient(90deg, #1d4ed8, #0f766e);
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
                background: linear-gradient(135deg, rgba(29, 78, 216, 0.08), rgba(15, 118, 110, 0.08));
            }

            .chart-card-body {
                padding: 1.5rem;
            }

            .rank-table th {
                font-size: 0.76rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
            }

            .rank-table td {
                vertical-align: middle;
                font-weight: 600;
            }
        </style>
    @endpush

    <div class="container-fluid">
        <div class="ri-header d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-3">
            <div>
                <h1>Dashboard Gerencial de Cancelamentos</h1>
                <div class="meta">Classificacao por tipo, demanda, solicitante principal e tempos de fluxo.</div>
            </div>
            <div class="row g-2 w-100 w-xl-auto">
                <div class="col-6 col-md-auto">
                    <label class="small text-white-50 mb-1">Inicio</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_in" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-auto">
                    <label class="small text-white-50 mb-1">Fim</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_out" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-auto">
                    <label class="small text-white-50 mb-1">Tipo</label>
                    <select class="form-select form-select-sm" wire:model="scope">
                        <option value="">Todos</option>
                        <option value="NOTE_FULL">Nota inteira</option>
                        <option value="ORDERS_PARTIAL">Ordens especificas</option>
                        <option value="WORK_FORM_ONLY">Somente WorkForm</option>
                    </select>
                </div>
                <div class="col-6 col-md-auto">
                    <label class="small text-white-50 mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model="status">
                        <option value="">Todos</option>
                        <option value="SUBMITTED">Enviado</option>
                        <option value="ASSIGNED">Em execucao</option>
                        <option value="PAUSED">Pausado</option>
                        <option value="DONE">Concluido</option>
                        <option value="REJECTED">Rejeitado</option>
                        <option value="ABORTED">Abortado</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <label class="small text-white-50 mb-1">Categoria</label>
                    <select class="form-select form-select-sm" wire:model="categoryId">
                        <option value="">Todas</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Visão</label>
                    <select class="form-select form-select-sm" wire:model="visibilityMode">
                        @foreach($visibilityOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label small mb-1">Solicitante (um ou mais)</label>
                    <select class="form-select form-select-sm" wire:model="requesterIds" multiple size="4">
                        @foreach($requesterOptions as $requester)
                            <option value="{{ $requester->id }}">{{ $requester->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Dica: segure `Ctrl` para múltipla seleção.</small>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Quantidade de demanda</div>
                        <div class="metric-value">{{ $summary['total_demand'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Encerradas</div>
                        <div class="metric-value">{{ $summary['closed'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Aprovação engenheiro pendente</div>
                        <div class="metric-value">{{ $summary['engineer_pending'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Finalizadas</div>
                        <div class="metric-value">{{ $summary['finalized'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo de execução</div>
                        <div class="metric-value">{{ $summary['avg_execution_human'] }}</div>
                        <div class="small text-muted">Assumida ate encerramento</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo de encerramento</div>
                        <div class="metric-value">{{ $summary['avg_closure_human'] }}</div>
                        <div class="small text-muted">Envio ate encerramento</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo aprovação engenheiro</div>
                        <div class="metric-value">{{ $summary['avg_engineer_approval_human'] }}</div>
                        <div class="small text-muted">Solicitação ate decisão</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-card-body">
                        <div class="metric-label">Tempo de finalização</div>
                        <div class="metric-value">{{ $summary['avg_finalization_human'] }}</div>
                        <div class="small text-muted">Decisão do engenheiro ate encerramento</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0"><i class="ri-pie-chart-2-line me-2"></i>Classificação por tipo</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 280px;">
                            <x-grafico.apex :chart="$typeChart" chartId="cxl_tipo" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0"><i class="ri-line-chart-line me-2"></i>Quantidade de demanda por dia</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 280px;">
                            <x-grafico.apex :chart="$dailyChart" chartId="cxl_diario" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0"><i class="ri-file-list-3-line me-2"></i>Classificação por categoria</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 300px;">
                            <x-grafico.apex :chart="$categoryChart" chartId="cxl_categoria" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0"><i class="ri-bar-chart-grouped-line me-2"></i>Status das solicitações</h6>
                    </div>
                    <div class="chart-card-body" wire:ignore>
                        <div style="min-height: 300px;">
                            <x-grafico.apex :chart="$statusChart" chartId="cxl_status" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0"><i class="ri-user-star-line me-2"></i>Principal solicitante</h6>
                    </div>
                    <div class="chart-card-body">
                        <div class="d-flex align-items-center justify-content-between p-3 rounded border mb-3 bg-light">
                            <div>
                                <div class="small text-muted">Solicitante líder do período</div>
                                <div class="fw-bold fs-5">{{ $summary['principal_requester'] }}</div>
                            </div>
                            <span class="badge bg-primary fs-6">{{ $summary['principal_requester_total'] }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm rank-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Solicitante</th>
                                        <th class="text-end">Demandas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topRequesters as $idx => $row)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td>{{ $row->requester_name }}</td>
                                            <td class="text-end">{{ (int) $row->total }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Sem dados para o período.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="chart-card-header">
                        <h6 class="mb-0"><i class="ri-user-settings-line me-2"></i>Executantes no período</h6>
                    </div>
                    <div class="chart-card-body">
                        <div class="d-flex align-items-center justify-content-between p-3 rounded border mb-3 bg-light">
                            <div>
                                <div class="small text-muted">Executante líder do período</div>
                                <div class="fw-bold fs-5">{{ $principalExecutor }}</div>
                            </div>
                            <span class="badge bg-success fs-6">{{ $principalExecutorTotal }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm rank-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Executante</th>
                                        <th class="text-end">Demandas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topExecutors as $idx => $row)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td>{{ $row->executor_name }}</td>
                                            <td class="text-end">{{ (int) $row->total }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Sem dados para o período.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
