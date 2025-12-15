<div>
    <x-show-loading />

    @push('css')
        <style>
            .dashboard-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 16px;
                padding: 2rem;
                color: white;
                box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
                position: relative;
                overflow: hidden;
                margin-bottom: 1.5rem;
            }

            .dashboard-header::before {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 200px;
                height: 200px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                transform: translate(50px, -50px);
            }

            .dashboard-header::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 150px;
                height: 150px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 50%;
                transform: translate(-30px, 30px);
            }

            .header-content {
                position: relative;
                z-index: 2;
            }

            .header-icon {
                width: 60px;
                height: 60px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .header-title {
                font-size: 2rem;
                font-weight: 700;
                color: white;
                margin: 0;
            }

            .header-subtitle {
                font-size: 1rem;
                color: rgba(255, 255, 255, 0.9);
                font-weight: 500;
            }

            .header-description {
                color: rgba(255, 255, 255, 0.8);
                font-size: 0.9rem;
            }

            .filters-container {
                position: relative;
                z-index: 2;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .filter-label {
                display: block;
                font-size: 0.8rem;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.9);
                margin-bottom: 0.35rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .filter-select {
                width: 100%;
                padding: 0.55rem 0.75rem;
                border-radius: 8px;
                border: 1px solid rgba(255, 255, 255, 0.25);
                background: rgba(255, 255, 255, 0.1);
                color: #fff;
                font-size: 0.9rem;
            }

            .filter-select option {
                background: #2c3e50;
                color: #fff;
            }

            .modern-card {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: none;
                margin-bottom: 1.5rem;
                position: relative;
                overflow: hidden;
            }

            .modern-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #667eea, #764ba2);
            }

            .modern-card-body {
                padding: 1.4rem 1.5rem;
            }

            .modern-card-title {
                font-size: 0.9rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #6c757d;
                font-weight: 600;
            }

            .metric-label {
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.06em;
                color: #6c757d;
                font-weight: 600;
            }

            .metric-value {
                font-size: 2rem;
                font-weight: 700;
                color: #1f2937;
            }

            .metric-subtitle {
                font-size: 0.8rem;
                color: #6c757d;
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
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
            }

            .chart-card-body {
                padding: 1.5rem;
            }

            .table-compact th,
            .table-compact td {
                font-size: 0.85rem;
                padding: 0.35rem 0.5rem;
                vertical-align: middle;
            }

            @media (max-width: 768px) {
                .dashboard-header {
                    padding: 1.4rem;
                }

                .header-title {
                    font-size: 1.6rem;
                }
            }
        </style>
    @endpush

    <div class="dashboard-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="header-content">
                    <div class="d-flex align-items-center mb-2">
                        <div class="header-icon me-3">
                            <i class="ri-pie-chart-2-line"></i>
                        </div>
                        <div>
                            <h1 class="header-title mb-0">
                                Produtividade x Reclamações
                            </h1>
                            <div class="header-subtitle">
                                Indicadores semanais dos 4 painéis solicitados
                            </div>
                        </div>
                    </div>
                    <p class="header-description mb-0">
                        Acompanhe despachos por usuário, saúde da pilha MEDA, cumprimento geral de SLA
                        e gargalos por categoria/tipo de nota no período filtrado.
                    </p>
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <div class="filters-container">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="filter-label">
                                <i class="ri-calendar-line me-1"></i> Início
                            </label>
                            <input type="date" wire:model="dt_in" class="filter-select" max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="filter-label">
                                <i class="ri-calendar-line me-1"></i> Fim
                            </label>
                            <input type="date" wire:model="dt_out" class="filter-select" max="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-6 mb-2">
                            <label class="filter-label">
                                <i class="ri-filter-2-line me-1"></i> Tipo
                            </label>
                            <select wire:model="advanceFilter" class="filter-select">
                                <option value="all">Todos</option>
                                <option value="advance">Avanço Parceiro</option>
                                <option value="normal">Reclamações normais</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="filter-label">
                                <i class="ri-user-3-line me-1"></i> Usuário
                            </label>
                            <select wire:model="userId" class="filter-select">
                                <option value="">Todos</option>
                                @foreach ($usersOptions as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mt-2">
                            <button class="btn btn-light w-100 fw-semibold text-primary" wire:click="exportJobs"
                                wire:loading.attr="disabled" wire:target="exportJobs">
                                <span wire:loading.remove wire:target="exportJobs">
                                    <i class="ri-file-excel-2-line me-1"></i>
                                    Exportar ProtestJobs
                                </span>
                                <span wire:loading wire:target="exportJobs">
                                    <i class="ri-loader-4-line me-1"></i>
                                    Preparando arquivo...
                                </span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Painel 1 --}}
    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-user-star-line me-1"></i> Painel 1 - Produtividade da equipe
                    </div>
                    <div class="small text-muted">
                        Reclamações tratadas por pessoa de {{ $summary['period_label'] }}
                    </div>
                </div>
                <span class="badge bg-light text-dark">Média diária:
                    {{ number_format($productivity['avg_daily_dispatch'], 1) }} /
                    {{ number_format($productivity['avg_daily_finish'], 1) }}</span>
            </div>

            <div class="row text-center gy-3">
                <div class="col-md-3 col-6">
                    <div class="metric-label">Reclamações enviadas</div>
                    <div class="metric-value">{{ $productivity['total_dispatched'] }}</div>
                    <div class="metric-subtitle">Jobs criados (sent_at)</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Reclamações concluídas</div>
                    <div class="metric-value">{{ $productivity['total_finished'] }}</div>
                    <div class="metric-subtitle">Status done / closed_at</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Pendentes do período</div>
                    <div class="metric-value">{{ $productivity['open_jobs'] }}</div>
                    <div class="metric-subtitle">Despachadas e ainda abertas</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Média diária</div>
                    <div class="metric-value">{{ number_format($productivity['avg_daily_dispatch'], 1) }}</div>
                    <div class="metric-subtitle">Envios / concl.:
                        {{ number_format($productivity['avg_daily_finish'], 1) }}</div>
                </div>
            </div>

            <div class="row text-center gy-3 mt-3">
                <div class="col-md-3 col-6">
                    <div class="metric-label">Reação média</div>
                    <div class="metric-value">{{ $summary['avg_reaction_human'] }}</div>
                    <div class="metric-subtitle">Criação MEDA até envio do Job</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Execução média</div>
                    <div class="metric-value">{{ $summary['avg_exec_human'] }}</div>
                    <div class="metric-subtitle">Início até conclusão</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">SLA do período</div>
                    <div class="metric-value">{{ number_format($summary['sla_rate'], 1) }}%</div>
                    <div class="metric-subtitle">Jobs concluídos dentro do prazo</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Encerramento pelo responsável</div>
                    <div class="metric-value">{{ number_format($summary['self_closure_rate'], 1) }}%</div>
                    <div class="metric-subtitle">{{ $summary['self_closed'] }} de {{ $summary['total_closed'] }}</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title">Despachantes (created_by)</span>
                        <span class="badge bg-light text-dark">Tempo de reação</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuário</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-center">Avanço</th>
                                    <th class="text-center">%</th>
                                    <th class="text-center">Reação média</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dispatcherStats as $row)
                                    <tr>
                                        <td>{{ $row['user_name'] }}</td>
                                        <td class="text-center">{{ $row['total_jobs'] }}</td>
                                        <td class="text-center">{{ $row['total_advance'] }}</td>
                                        <td class="text-center">{{ number_format($row['advance_ratio'], 1) }}%</td>
                                        <td class="text-center">{{ $row['avg_reaction_human'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Nenhum dado de despachante no período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title">Responsáveis (owner_id)</span>
                        <span class="badge bg-light text-dark">SLA e execução</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuário</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-center">SLA</th>
                                    <th class="text-center">Enc. por si</th>
                                    <th class="text-center">Exec. média</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ownerStats as $row)
                                    <tr>
                                        <td>{{ $row['user_name'] }}</td>
                                        <td class="text-center">{{ $row['total_jobs'] }}</td>
                                        <td class="text-center">{{ number_format($row['sla_rate'], 1) }}%</td>
                                        <td class="text-center">{{ number_format($row['self_closure_rate'], 1) }}%
                                        </td>
                                        <td class="text-center">{{ $row['avg_exec_human'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Nenhum responsável com dados no período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-card mb-4">
        <div class="chart-card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-exchange-line me-2"></i> Despachos x conclusões por dia</h5>
        </div>
        <div class="chart-card-body" wire:ignore>
            <div style="max-height: 320px;">
                <x-grafico.apex :chart="$dailyDispatchCompletion" chartId="dailyDispatchCompletion" class="w-100" />
            </div>
        </div>
    </div>

    {{-- Painel 2 --}}
    <div class="modern-card insight-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-pulse-line me-1"></i> Painel 2 - Saúde do backlog (MEDA)
                    </div>
                    <div class="small text-muted">
                        Medidas com status MEDA sem ProtestJob relacionado.
                    </div>
                </div>
                <span class="badge bg-light text-dark">Atualizado em {{ now()->format('d/m/Y H:i') }}</span>
            </div>

            <div class="row text-center gy-3">
                <div class="col-md-4 col-12">
                    <div class="metric-label">Abertas sem job</div>
                    <div class="metric-value">{{ $backlogPanel['total_open'] }}</div>
                    <div class="metric-subtitle">Status MEDA aguardando despacho</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">> 5 dias sem ação</div>
                    <div class="metric-value">{{ $backlogPanel['older_than_5'] }}</div>
                    <div class="metric-subtitle">Criadas há mais de 5 dias</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">Vencidas</div>
                    <div class="metric-value">{{ $backlogPanel['expired'] }}</div>
                    <div class="metric-subtitle">dtFimMedidaDesej < hoje</div>
                    </div>
                </div>

                <div class="row text-center gy-3 mt-3">
                    <div class="col-md-3 col-6">
                        <div class="metric-label">MEDA em aberto</div>
                        <div class="metric-value">{{ $medaSnapshot['open_measures'] }}</div>
                        <div class="metric-subtitle">{{ $medaSnapshot['open_protests'] }} protestos impactados</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric-label">Despachos/dia</div>
                        <div class="metric-value">{{ number_format($medaSnapshot['avg_dispatch_daily'], 1) }}</div>
                        <div class="metric-subtitle">{{ $medaSnapshot['dispatcher_users'] }} despachantes</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric-label">Conclusões/dia</div>
                        <div class="metric-value">{{ number_format($medaSnapshot['avg_finish_daily'], 1) }}</div>
                        <div class="metric-subtitle">{{ $medaSnapshot['executor_users'] }} responsáveis</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric-label">Dias para zerar</div>
                        <div class="metric-value">
                            @if ($medaSnapshot['days_to_clear'])
                                ~{{ $medaSnapshot['days_to_clear'] }}
                            @else
                                &mdash;
                            @endif
                        </div>
                        <div class="metric-subtitle">{{ $medaSnapshot['status_message'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-card mb-4">
            <div class="chart-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ri-stack-line me-2"></i> Medidas MEDA (com x sem Job)</h5>
            </div>
            <div class="chart-card-body" wire:ignore>
                <div style="max-height: 380px;">
                    <x-grafico.apex :chart="$medaJobsChart" chartId="medaJobs" class="w-100" />
                </div>
            </div>
        </div>

        {{-- Painel 3 --}}
        <div class="modern-card mb-4">
            <div class="modern-card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <div class="modern-card-title mb-1">
                            <i class="ri-time-line me-1"></i> Painel 3 - SLA do processo
                        </div>
                        <div class="small text-muted">Cumprimento por Medida e por Reclamação</div>
                    </div>
                    <span class="badge bg-light text-dark">Taxa geral:
                        {{ number_format($slaPanel['overall_rate'], 1) }}%</span>
                </div>

                <div class="row text-center gy-3">
                    <div class="col-md-4 col-12">
                        <div class="metric-label">MEDA com SLA</div>
                        <div class="metric-value">{{ $slaPanel['med']['on_time'] }} / {{ $slaPanel['med']['total'] }}
                        </div>
                        <div class="metric-subtitle">{{ number_format($slaPanel['med']['rate'], 1) }}% cumprido</div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="metric-label">Reclamações concluídas</div>
                        <div class="metric-value">{{ $slaPanel['protest']['on_time'] }} /
                            {{ $slaPanel['protest']['total'] }}</div>
                        <div class="metric-subtitle">{{ number_format($slaPanel['protest']['rate'], 1) }}% no prazo
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="metric-label">Em atraso</div>
                        <div class="metric-value">{{ $slaPanel['med']['late'] + $slaPanel['protest']['late'] }}</div>
                        <div class="metric-subtitle">Medidas/Reclamações com SLA vencido</div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title"><i class="ri-table-line me-1"></i> Lista de SLA (até 50
                            registros)</span>
                        <span class="badge bg-light text-dark">Ordenado pelo prazo</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reclamação</th>
                                    <th class="text-center">Med ID</th>
                                    <th class="text-center">SLA previsto</th>
                                    <th class="text-center">Finalizado em</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Desvio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($jobSlaList as $job)
                                    <tr>
                                        <td>
                                            <strong>{{ $job['protest_number'] }}</strong>
                                            <div class="small text-muted">Job #{{ $job['job_id'] }}</div>
                                        </td>
                                        <td class="text-center">{{ $job['med_id'] }}</td>
                                        <td class="text-center">{{ $job['sla_due_at'] }}</td>
                                        <td class="text-center">{{ $job['finished_at'] }}</td>
                                        <td class="text-center">
                                            <span
                                                class="badge {{ $job['status_badge'] }} text-white px-3">{{ $job['status_label'] }}</span>
                                        </td>
                                        <td class="text-center">{{ $job['delta_label'] ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Nenhum job com SLA encontrado no período/filtro.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Painel 4 --}}
        <div class="modern-card mb-4">
            <div class="modern-card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <div class="modern-card-title mb-1">
                            <i class="ri-focus-2-line me-1"></i> Painel 4 - Gargalos
                        </div>
                        <div class="small text-muted">Categorias (protest_type) e tipos de nota que mais geram demanda
                        </div>
                    </div>
                    <span class="badge bg-light text-dark">Período: {{ $summary['period_label'] }}</span>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <h6 class="text-uppercase text-muted small">Regional / Célula (protest_type)</h6>
                        <div class="table-responsive mt-2">
                            <table class="table table-striped table-hover table-compact mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoria</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Abertas</th>
                                        <th class="text-center">Vencidas</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($bottlenecks['categories'] as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-center">{{ $row['total'] }}</td>
                                            <td class="text-center">{{ $row['abertas'] }}</td>
                                            <td class="text-center">{{ $row['vencidas'] }}</td>
                                            <td class="text-center">{{ number_format($row['percent'], 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Sem dados para o
                                                período.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <h6 class="text-uppercase text-muted small">Tipos de nota (aberturas)</h6>
                                <ul class="list-group list-group-flush">
                                    @forelse ($bottlenecks['tipo_nota'] as $tipo)
                                        <li
                                            class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span>{{ $tipo['tipo'] }}</span>
                                            <span class="badge bg-primary">{{ $tipo['total'] }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item px-0 text-muted">Sem registros.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-sm-6">
                                <h6 class="text-uppercase text-muted small">Tipos de nota vencidos</h6>
                                <ul class="list-group list-group-flush">
                                    @forelse ($bottlenecks['tipo_nota_late'] as $tipo)
                                        <li
                                            class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span>{{ $tipo['tipo'] }}</span>
                                            <span class="badge bg-danger">{{ $tipo['total'] }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item px-0 text-muted">Sem registros.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-card mb-4">
            <div class="chart-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ri-bar-chart-2-line me-2"></i> Aberturas diárias (Protest x MedProtest)
                </h5>
            </div>
            <div class="chart-card-body" wire:ignore>
                <div style="max-height: 380px;">
                    <x-grafico.apex :chart="$dailyOpenings" chartId="dailyOpenings" class="w-100" />
                </div>
            </div>
        </div>

    </div>
