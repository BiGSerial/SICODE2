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

            .header-icon i {
                font-size: 28px;
                color: white;
            }

            .header-title {
                font-size: 2.2rem;
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

            .modern-card-value {
                font-size: 2rem;
                font-weight: 700;
                color: #2c3e50;
            }

            .modern-card-subtitle {
                font-size: 0.8rem;
                color: #6c757d;
            }

            .modern-card-growth {
                font-size: 0.85rem;
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

            .insight-card .insight-metric {
                padding: 0.5rem 0.75rem;
                border-right: 1px solid rgba(0, 0, 0, 0.06);
            }

            .insight-card .insight-metric:last-child {
                border-right: none;
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

            @media (max-width: 768px) {
                .dashboard-header {
                    padding: 1.4rem;
                }

                .header-title {
                    font-size: 1.6rem;
                }

                .insight-card .insight-metric {
                    border-right: none;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
                    padding-bottom: 0.75rem;
                    margin-bottom: 0.75rem;
                }

                .insight-card .insight-metric:last-child {
                    border-bottom: none;
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
                                SLA de Reclamações
                            </h1>
                            <div class="header-subtitle">
                                Análise por despachantes e responsáveis
                            </div>
                        </div>
                    </div>
                    <p class="header-description mb-0">
                        Monitore tempo de reação dos despachantes, tempo de execução dos responsáveis,
                        cumprimento de SLA e quem está realmente encerrando as atividades.
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
                                <option value="advance">Avança Parceiro</option>
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

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards de resumo --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title">
                            <i class="ri-alert-line me-1"></i> Reclamações
                        </span>
                        <span class="badge bg-light text-dark">
                            {{ $summary['period_label'] }}
                        </span>
                    </div>
                    <div class="modern-card-value">
                        {{ $summary['total_jobs'] ?? 0 }}
                    </div>
                    <div class="modern-card-subtitle">
                        {{ $summary['finished_jobs'] ?? 0 }} finalizadas no período
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="modern-card-title mb-1">
                        <i class="ri-time-line me-1"></i> Reação Despachantes
                    </div>
                    <div class="modern-card-value">
                        {{ $summary['avg_reaction_human'] ?? '0 min' }}
                    </div>
                    <div class="modern-card-subtitle">
                        Tempo médio entre criação da Medida e envio do Job
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="modern-card-title mb-1">
                        <i class="ri-timer-flash-line me-1"></i> Execução Responsáveis
                    </div>
                    <div class="modern-card-value">
                        {{ $summary['avg_exec_human'] ?? '0 min' }}
                    </div>
                    <div class="modern-card-subtitle">
                        Tempo médio de execução (início → fim)
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="modern-card-title mb-1">
                        <i class="ri-flag-2-line me-1"></i> SLA & Encerramento
                    </div>
                    <div class="modern-card-value">
                        {{ number_format($summary['sla_rate'] ?? 0, 1) }}%
                        <span class="fs-6">SLA</span>
                    </div>
                    <div class="modern-card-subtitle">
                        {{ number_format($summary['self_closure_rate'] ?? 0, 1) }}% encerradas
                        pelo próprio responsável
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Saúde da pilha MEDA --}}
    <div class="modern-card insight-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-pulse-line me-1"></i> Saúde da pilha MEDA
                    </div>
                    <div class="small text-muted">
                        Média dos {{ $medaSnapshot['days_considered'] }} dia(s) filtrados
                        ({{ $medaSnapshot['sample_start'] }} - {{ $medaSnapshot['sample_end'] }})
                        considerando apenas medidas com ProtestJob registrado.
                    </div>
                </div>
                <span class="badge {{ $medaSnapshot['status_badge_class'] }} px-3 py-2">
                    {{ $medaSnapshot['status_label'] }}
                </span>
            </div>

            <div class="row text-center gy-3">
                <div class="col-md-3 col-12">
                    <div class="insight-metric">
                        <div class="metric-label">MEDA em aberto</div>
                        <div class="metric-value">{{ $medaSnapshot['open_measures'] }}</div>
                        <div class="metric-subtitle">
                            {{ $medaSnapshot['open_protests'] }} protestos impactados ·
                            {{ $medaSnapshot['closed_protests'] }} resolvidos
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="insight-metric">
                        <div class="metric-label">Despachos por dia</div>
                        <div class="metric-value">
                            {{ number_format($medaSnapshot['avg_dispatch_daily'], 1) }}
                        </div>
                        <div class="metric-subtitle">
                            {{ $medaSnapshot['dispatcher_users'] }} despachantes ·
                            {{ number_format($medaSnapshot['avg_dispatch_per_user'], 1) }} cada
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="insight-metric">
                        <div class="metric-label">Conclusões por dia</div>
                        <div class="metric-value">
                            {{ number_format($medaSnapshot['avg_finish_daily'], 1) }}
                        </div>
                        <div class="metric-subtitle">
                            {{ $medaSnapshot['executor_users'] }} executantes ·
                            {{ number_format($medaSnapshot['avg_finish_per_user'], 1) }} cada
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="insight-metric">
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
    </div>

    {{-- Gráfico + tabelas --}}
    <div class="row">
        <div class="col-lg-7">
            <div class="chart-card mb-3">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ri-bar-chart-2-line me-2"></i>
                        Aberturas diárias (Protest x MedProtest)
                    </h5>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div style="max-height: 380px;">
                        <x-grafico.apex :chart="$dailyOpenings" chartId="dailyOpenings" class="w-100" />
                    </div>
                </div>
            </div>

            <div class="chart-card mb-3">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ri-stack-line me-2"></i>
                        Medidas MEDA (com x sem Job)
                    </h5>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div style="max-height: 380px;">
                        <x-grafico.apex :chart="$medaJobsChart" chartId="medaJobs" class="w-100" />
                    </div>
                </div>
            </div>

            <div class="chart-card mb-3">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ri-exchange-line me-2"></i>
                        Despachos x conclusões
                    </h5>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div style="max-height: 320px;">
                        <x-grafico.apex :chart="$dailyDispatchCompletion" chartId="dailyDispatchCompletion" class="w-100" />
                    </div>
                </div>
            </div>

            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title">
                            <i class="ri-user-star-line me-1"></i> Despachantes (created_by)
                        </span>
                        <span class="badge bg-light text-dark">
                            Tempo de reação por usuário
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuário</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-center">Avança<br />Parceiro</th>
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
                                        <td class="text-center">
                                            {{ number_format($row['advance_ratio'], 1) }}%
                                        </td>
                                        <td class="text-center">
                                            {{ $row['avg_reaction_human'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Nenhum dado de despachante no período/ filtro.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-lg-5">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title">
                            <i class="ri-user-settings-line me-1"></i> Responsáveis (owner_id)
                        </span>
                        <span class="badge bg-light text-dark">
                            SLA, execução e encerramento
                        </span>
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
                                        <td class="text-center">
                                            {{ number_format($row['sla_rate'], 1) }}%
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($row['self_closure_rate'], 1) }}%
                                        </td>
                                        <td class="text-center">
                                            {{ $row['avg_exec_human'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Nenhum responsável com dados no período/ filtro.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 small text-muted">
                        <strong>Obs.:</strong> SLA considera Jobs com
                        <code>sla_due_at</code> definido e
                        <code>finished_at &lt;= sla_due_at</code>.
                        Encerramento por si compara <code>owner_id</code> com <code>closed_by</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista detalhada de SLA por Job --}}
    <div class="modern-card mt-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="modern-card-title">
                    <i class="ri-time-line me-1"></i> SLA por job
                </span>
                <span class="badge bg-light text-dark">
                    Até 50 registros com SLA no período
                </span>
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
                                    <span class="badge {{ $job['status_badge'] }} text-white px-3">
                                        {{ $job['status_label'] }}
                                    </span>
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
