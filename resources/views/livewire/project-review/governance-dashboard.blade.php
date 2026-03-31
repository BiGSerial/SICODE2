<div class="oexterno-page">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
            padding: 1.5rem 0;
        }

        .oexterno-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            margin-bottom: 1rem;
        }

        .card-soft {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: .8rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .chart-card {
            height: 100%;
        }

        .chart-wrap {
            position: relative;
            width: 100%;
            min-height: 260px;
            height: 260px;
        }

        .chart-wrap.is-tall {
            min-height: 340px;
            height: 340px;
        }

        .chart-wrap canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .cost-flow {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto 1fr auto 1fr;
            align-items: center;
            gap: .75rem;
        }

        .cost-flow .symbol {
            font-size: 1.5rem;
            font-weight: 700;
            color: #64748b;
            text-align: center;
        }

        .cost-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .7rem;
            padding: .75rem;
        }

        @media (max-width: 992px) {
            .cost-flow {
                grid-template-columns: 1fr;
            }

            .cost-flow .symbol {
                display: none;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header">
            <h2 class="mb-0">ANÁLISE PROJETO</h2>
            <div>Dashboard de Governança</div>
        </div>

        <div class="card-soft p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Período envio de análise (de)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="period_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Período envio de análise (até)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="period_to">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Empresa</label>
                    <select class="form-select form-select-sm" wire:model="company_id">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Usuário</label>
                    <select class="form-select form-select-sm" wire:model="user_id">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status final</label>
                    <select class="form-select form-select-sm" wire:model="final_status">
                        <option value="">Todos</option>
                        @foreach ($statusOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Com reprovação</label>
                    <select class="form-select form-select-sm" wire:model="rejection_filter">
                        <option value="all">Todos</option>
                        <option value="with">Com reprovação</option>
                        <option value="without">Sem reprovação</option>
                    </select>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Data finalização (de)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="finalized_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Data finalização (até)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="finalized_to">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Data aprovação (de)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="approved_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Data aprovação (até)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="approved_to">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Data reprovação (de)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="rejected_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Data reprovação (até)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="rejected_to">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button class="btn btn-sm btn-success me-2" wire:click="exportReport">
                    <i class="ri-file-excel-2-line me-1"></i> Exportar relatório
                </button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">Limpar filtros</button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card-soft p-3"><small>Total de productions</small><h4 class="mb-0">{{ $summary['total_productions'] }}</h4></div></div>
            <div class="col-md-3"><div class="card-soft p-3"><small>Productions com reprovação</small><h4 class="mb-0">{{ $summary['with_rejection'] }}</h4></div></div>
            <div class="col-md-3"><div class="card-soft p-3"><small>Productions sem reprovação</small><h4 class="mb-0">{{ $summary['without_rejection'] }}</h4></div></div>
            <div class="col-md-3"><div class="card-soft p-3"><small>% aprovação de primeira</small><h4 class="mb-0">{{ $summary['first_pass_approval_pct'] }}%</h4></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-2"><div class="card-soft p-3"><small>Enviadas para análise</small><h5 class="mb-0">{{ $summary['cycles_submitted_count'] }}</h5></div></div>
            <div class="col-md-2"><div class="card-soft p-3"><small>Aguardando análise</small><h5 class="mb-0 text-warning">{{ $summary['cycles_waiting_analysis_count'] }}</h5></div></div>
            <div class="col-md-2"><div class="card-soft p-3"><small>Reprovadas</small><h5 class="mb-0 text-danger">{{ $summary['cycles_rejected_count'] }}</h5></div></div>
            <div class="col-md-2"><div class="card-soft p-3"><small>Aprovadas (total)</small><h5 class="mb-0 text-success">{{ $summary['cycles_approved_count'] }}</h5></div></div>
            <div class="col-md-2"><div class="card-soft p-3"><small>Aprovadas com ressalvas</small><h5 class="mb-0">{{ $summary['cycles_approved_with_remarks_count'] }}</h5></div></div>
            <div class="col-md-2"><div class="card-soft p-3"><small>Aprovadas sem ressalvas</small><h5 class="mb-0">{{ $summary['cycles_approved_without_remarks_count'] }}</h5></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card-soft p-3"><small>Tempo médio envio > análise (h)</small><h5 class="mb-0">{{ $summary['avg_send_to_decision_hours'] }}</h5></div></div>
            <div class="col-md-4"><div class="card-soft p-3"><small>Tempo médio reprovação > reenvio (h)</small><h5 class="mb-0">{{ $summary['avg_reject_to_resubmit_hours'] }}</h5></div></div>
            <div class="col-md-4"><div class="card-soft p-3"><small>Tempo médio total até aprovação final (h)</small><h5 class="mb-0">{{ $summary['avg_total_until_final_approval_hours'] }}</h5></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card-soft p-3">
                    <h6 class="mb-2">Composição do valor revisado</h6>
                    @php
                        $plannedTotal = (float) $summary['planned_total_cost'];
                        $netVariation = (float) $summary['net_variation_cost']; // revisado - planejado
                        $netVariationAbs = abs($netVariation);
                        $netVariationPct = $plannedTotal > 0 ? ($netVariationAbs / $plannedTotal) * 100 : 0;
                        $netIsIncrease = $netVariation >= 0;

                        $companyPlanned = (float) $summary['planned_company_total_cost'];
                        $companyDelta = (float) $summary['company_net_variation_cost']; // revisado - planejado
                        $companyDeltaAbs = abs($companyDelta);
                        $companyDeltaPct = $companyPlanned > 0 ? ($companyDeltaAbs / $companyPlanned) * 100 : 0;
                        $companyIsIncrease = $companyDelta >= 0;

                        $clientPlanned = (float) $summary['planned_client_total_cost'];
                        $clientDelta = (float) $summary['client_net_variation_cost']; // revisado - planejado
                        $clientDeltaAbs = abs($clientDelta);
                        $clientDeltaPct = $clientPlanned > 0 ? ($clientDeltaAbs / $clientPlanned) * 100 : 0;
                        $clientIsIncrease = $clientDelta >= 0;
                    @endphp
                    <div class="cost-flow">
                        <div class="cost-box">
                            <small class="text-muted">Valor planejado</small>
                            <h5 class="mb-0">R$ {{ number_format((float) $summary['planned_total_cost'], 2, ',', '.') }}</h5>
                        </div>
                        <div class="symbol">+</div>
                        <div class="cost-box">
                            <small class="text-muted">Somatório de acréscimos</small>
                            <h5 class="mb-0 text-danger">R$ {{ number_format((float) $summary['increase_total_cost'], 2, ',', '.') }}</h5>
                        </div>
                        <div class="symbol">-</div>
                        <div class="cost-box">
                            <small class="text-muted">Somatório de reduções</small>
                            <h5 class="mb-0 text-success">R$ {{ number_format((float) $summary['economy_total_cost'], 2, ',', '.') }}</h5>
                        </div>
                        <div class="symbol">=</div>
                        <div class="cost-box">
                            <small class="text-muted">Valor revisado total</small>
                            <h5 class="mb-0">R$ {{ number_format((float) $summary['revised_total_cost'], 2, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-3">
                        <div>
                            <small class="text-muted d-block">Diferença líquida (acréscimos - reduções)</small>
                            <strong class="{{ $netIsIncrease ? 'text-danger' : 'text-success' }}">
                                <i class="{{ $netIsIncrease ? 'ri-arrow-up-line' : 'ri-arrow-down-line' }}"></i>
                                R$ {{ number_format($netVariationAbs, 2, ',', '.') }}
                                ({{ number_format($netVariationPct, 2, ',', '.') }}%)
                            </strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Ordens sem alteração de custo</small>
                            <strong>{{ (int) $summary['maintained_orders_count'] }}</strong>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="cost-box">
                                <small class="text-muted d-block">Planejado empresa</small>
                                <strong>R$ {{ number_format((float) $summary['planned_company_total_cost'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cost-box">
                                <small class="text-muted d-block">Planejado cliente</small>
                                <strong>R$ {{ number_format((float) $summary['planned_client_total_cost'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cost-box">
                                <small class="text-muted d-block">Revisado empresa</small>
                                <strong>R$ {{ number_format((float) $summary['revised_company_total_cost'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cost-box">
                                <small class="text-muted d-block">Revisado cliente</small>
                                <strong>R$ {{ number_format((float) $summary['revised_client_total_cost'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-3">
                        <div>
                            <small class="text-muted d-block">Custo empresa (planejado x revisado)</small>
                            <strong class="{{ $companyIsIncrease ? 'text-danger' : 'text-success' }}">
                                <i class="{{ $companyIsIncrease ? 'ri-arrow-up-line' : 'ri-arrow-down-line' }}"></i>
                                R$ {{ number_format($companyDeltaAbs, 2, ',', '.') }}
                                ({{ number_format($companyDeltaPct, 2, ',', '.') }}%)
                            </strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Custo cliente (planejado x revisado)</small>
                            <strong class="{{ $clientIsIncrease ? 'text-danger' : 'text-success' }}">
                                <i class="{{ $clientIsIncrease ? 'ri-arrow-up-line' : 'ri-arrow-down-line' }}"></i>
                                R$ {{ number_format($clientDeltaAbs, 2, ',', '.') }}
                                ({{ number_format($clientDeltaPct, 2, ',', '.') }}%)
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Erros por categoria</h6><div class="chart-wrap"><canvas id="prChartCategories"></canvas></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Erros por subcategoria</h6><div class="chart-wrap"><canvas id="prChartSubcategories"></canvas></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Itens mais apontados</h6><div class="chart-wrap is-tall"><canvas id="prChartItems"></canvas></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Usuários com mais erros (qtd)</h6><div class="chart-wrap"><canvas id="prChartUsersCount"></canvas></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>% representatividade por usuário (Top 8 + Outros)</h6><div class="chart-wrap"><canvas id="prChartUsersPct"></canvas></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Empresas com mais erros</h6><div class="chart-wrap"><canvas id="prChartCompanies"></canvas></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Origem dos erros (volume de itens)</h6><div class="chart-wrap"><canvas id="prChartOrigins"></canvas></div></div>
            </div>
            <div class="col-12">
                <div class="card-soft p-3 chart-card" wire:ignore><h6>Top 15 obras com mais itens errados</h6><div class="chart-wrap"><canvas id="prChartRejections"></canvas></div></div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <div class="card-soft p-3">
                    <h6>Erros mais recorrentes</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Categoria</th><th>Subcategoria</th><th>Item</th><th>Total</th></tr></thead>
                            <tbody>
                            @forelse($tables['top_items'] as $row)
                                <tr><td>{{ $row->category }}</td><td>{{ $row->subcategory }}</td><td>{{ $row->item }}</td><td>{{ $row->total }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Sem dados</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-soft p-3">
                    <h6>Percentual de erro por usuário</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Usuário</th><th>Enviadas</th><th>Analisadas</th><th>Reprovações</th><th>% erro</th><th>Principal tipo de erro</th></tr></thead>
                            <tbody>
                            @forelse($tables['user_error_percent'] as $row)
                                <tr>
                                    <td>{{ $row->user_name }}</td>
                                    <td>{{ (int) ($row->submitted_cycles ?? 0) }}</td>
                                    <td>{{ (int) ($row->analyzed_cycles ?? 0) }}</td>
                                    <td>{{ $row->rejected_cycles }}</td>
                                    <td>{{ $row->error_pct }}%</td>
                                    <td>{{ $row->main_error ?? '---' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Sem dados</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card-soft p-3">
                    <h6>Empresas no período (erros x análises)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Erros</th>
                                    <th>Análises</th>
                                    <th>Erros/Análise</th>
                                    <th>Principal erro</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($tables['company_error_summary'] as $row)
                                <tr>
                                    <td>{{ $row->company_name }}</td>
                                    <td>{{ $row->error_total }}</td>
                                    <td>{{ $row->analysis_total }}</td>
                                    <td>{{ $row->errors_per_analysis }}</td>
                                    <td>{{ $row->main_error }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Sem dados</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card-soft p-3">
                    <h6>Histórico temporal da análise (por dia)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Dia</th>
                                    <th>Enviadas</th>
                                    <th>Reprovadas</th>
                                    <th>Aprovadas com ressalvas</th>
                                    <th>Aprovadas sem ressalvas</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($tables['timeline'] as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                                    <td>{{ (int) $row->submitted }}</td>
                                    <td>{{ (int) $row->rejected }}</td>
                                    <td>{{ (int) $row->approved_with_remarks }}</td>
                                    <td>{{ (int) $row->approved_without_remarks }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Sem dados</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script id="projectReviewDashboardPayload" type="application/json">@json($charts)</script>

    <script>
        (function() {
            window.projectReviewDashCharts = window.projectReviewDashCharts || {};

            function destroyChart(key) {
                if (window.projectReviewDashCharts[key]) {
                    window.projectReviewDashCharts[key].destroy();
                    delete window.projectReviewDashCharts[key];
                }
            }

            function barColorsByKey(key) {
                const map = {
                    categories: { bg: 'rgba(15,118,110,.70)', border: 'rgba(15,118,110,1)' },
                    subcategories: { bg: 'rgba(14,165,233,.70)', border: 'rgba(14,165,233,1)' },
                    items: { bg: 'rgba(59,130,246,.70)', border: 'rgba(59,130,246,1)' },
                    users_count: { bg: 'rgba(99,102,241,.70)', border: 'rgba(99,102,241,1)' },
                    users_pct: { bg: 'rgba(139,92,246,.70)', border: 'rgba(139,92,246,1)' },
                    companies: { bg: 'rgba(34,197,94,.70)', border: 'rgba(34,197,94,1)' },
                    rejections: { bg: 'rgba(239,68,68,.70)', border: 'rgba(239,68,68,1)' },
                };

                return map[key] || { bg: 'rgba(15,118,110,.65)', border: 'rgba(15,118,110,1)' };
            }

            function doughnutPaletteByKey(key) {
                const common = ['#0f766e', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280', '#22c55e', '#f97316'];
                const map = {
                    origins: ['#0ea5e9', '#0f766e', '#f59e0b', '#6b7280', '#f97316', '#8b5cf6'],
                    status: ['#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#6b7280', '#8b5cf6'],
                };
                return map[key] || common;
            }

            function upsertBar(id, key, labels, data, horizontal = false) {
                const canvas = document.getElementById(id);
                if (!canvas || !window.Chart) return;
                const color = barColorsByKey(key);
                const isPercent = key === 'users_pct';

                const wrapper = canvas.closest('.chart-wrap');
                if (wrapper) {
                    const base = horizontal ? 250 : 260;
                    const dynamic = horizontal ? Math.min(680, Math.max(base, ((labels || []).length * 30) + 80)) : base;
                    wrapper.style.height = dynamic + 'px';
                }

                destroyChart(key);
                window.projectReviewDashCharts[key] = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels || [],
                        datasets: [{
                            label: 'Total',
                            data: data || [],
                            backgroundColor: color.bg,
                            borderColor: color.border,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: horizontal ? 'y' : 'x',
                        animation: false,
                        plugins: { legend: { display: false } },
                        scales: horizontal ? {
                            x: {
                                beginAtZero: true,
                                suggestedMax: isPercent ? 100 : undefined,
                                ticks: {
                                    precision: isPercent ? 2 : 0,
                                    callback: function(value) {
                                        return isPercent ? value + '%' : value;
                                    }
                                }
                            },
                            y: {
                                ticks: {
                                    autoSkip: false
                                }
                            }
                        } : {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: isPercent ? 2 : 0,
                                    callback: function(value) {
                                        return isPercent ? value + '%' : value;
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxRotation: 0,
                                    minRotation: 0
                                }
                            }
                        }
                    }
                });
            }

            function upsertDoughnut(id, key, labels, data) {
                const canvas = document.getElementById(id);
                if (!canvas || !window.Chart) return;
                const palette = doughnutPaletteByKey(key);
                const wrapper = canvas.closest('.chart-wrap');
                if (wrapper) {
                    wrapper.style.height = '260px';
                }
                destroyChart(key);
                window.projectReviewDashCharts[key] = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: labels || [],
                        datasets: [{
                            data: data || [],
                            backgroundColor: palette
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, animation: false }
                });
            }

            function renderProjectReviewDashboardCharts() {
                const payloadEl = document.getElementById('projectReviewDashboardPayload');
                if (!payloadEl) return;

                let payload = {};
                try { payload = JSON.parse(payloadEl.textContent || '{}'); } catch (e) { payload = {}; }

                upsertBar('prChartCategories', 'categories', payload.categories?.labels, payload.categories?.data, true);
                upsertBar('prChartSubcategories', 'subcategories', payload.subcategories?.labels, payload.subcategories?.data, true);
                upsertBar('prChartItems', 'items', payload.items?.labels, payload.items?.data, true);
                upsertBar('prChartUsersCount', 'users_count', payload.users_error_count?.labels, payload.users_error_count?.data, true);
                upsertBar('prChartUsersPct', 'users_pct', payload.users_error_pct?.labels, payload.users_error_pct?.data, true);
                upsertBar('prChartCompanies', 'companies', payload.companies?.labels, payload.companies?.data, true);
                upsertDoughnut('prChartOrigins', 'origins', payload.origins?.labels, payload.origins?.data);
                upsertBar('prChartRejections', 'rejections', payload.rejections_per_production?.labels, payload.rejections_per_production?.data, true);
            }

            document.addEventListener('livewire:load', function() {
                renderProjectReviewDashboardCharts();
                Livewire.hook('message.processed', function() {
                    renderProjectReviewDashboardCharts();
                });
            });
        })();
    </script>
</div>
