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

            .dashboard-histogram-wrap {
                position: relative;
                width: 100%;
                height: clamp(260px, 34vh, 420px);
                max-height: 420px;
                overflow: hidden;
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
        <div class="header-content mb-3">
            <div class="d-flex align-items-center mb-2">
                <div class="header-icon me-3">
                    <i class="ri-pie-chart-2-line"></i>
                </div>
                <div>
                    <h1 class="header-title mb-0">
                        Produtividade x Reclamacoes
                    </h1>
                    <div class="header-subtitle">
                        Indicadores semanais dos 5 paineis solicitados
                    </div>
                </div>
            </div>
            <p class="header-description mb-0">
                Acompanhe despachos por usuario, saude da pilha MEDA, cumprimento geral de SLA
                e gargalos por categoria/tipo de nota no periodo filtrado.
            </p>
        </div>

        <div class="small text-white-50">
            Filtros detalhados movidos para o bloco destacado após a lista consolidada de medidas em aberto.
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xxl-8">
    <div class="modern-card mb-0 h-100">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-bar-chart-box-line me-1"></i> Medidas em aberto consolidadas
                    </div>
                    <div class="small text-muted">
                        Histograma mensal (mês/ano) de medidas em aberto por data desejada (regra NA / OU-PR), sem recorte
                        temporal. O filtro aplicado aqui reflete a lista abaixo.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model="medaHistogramSource" class="form-select form-select-sm" style="min-width: 170px;">
                        <option value="desired">Data desejada</option>
                        <option value="sla">Data SLA (se existir)</option>
                    </select>
                    <select wire:model="medaDispatchFilter" class="form-select form-select-sm" style="min-width: 170px;">
                        <option value="all">Despachado + Não Despachado</option>
                        <option value="with_job">Despachado</option>
                        <option value="without_job">Não Despachado</option>
                    </select>
                    <select wire:model="medaHistogramBtzeroFilter" class="form-select form-select-sm" style="min-width: 170px;">
                        <option value="all">BT Zero: Todos</option>
                        <option value="without_btzero">BT Zero: Sem BT Zero</option>
                        <option value="only_btzero">BT Zero: Apenas BT Zero</option>
                    </select>
                    @if (!empty($medaOpenHistogram['selectedBucket']))
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearMedaHistogramFilter">
                            Limpar mês
                        </button>
                    @endif
                    <span class="badge bg-light text-dark">Total: {{ $medaOpenHistogram['total'] ?? 0 }}</span>
                </div>
            </div>

            <div class="row text-center gy-3 mb-3">
                <div class="col-md-3 col-12">
                    <div class="metric-label">MEDA em aberto</div>
                    <div class="metric-value">{{ $medaOpenHistogram['total'] ?? 0 }}</div>
                    <div class="metric-subtitle">Em aberto no momento (incluindo BT Zero)</div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="metric-label">Despachado</div>
                    <div class="metric-value">{{ $medaOpenHistogram['total_with_job'] ?? 0 }}</div>
                    <div class="metric-subtitle">Com Atividade de Reclamação</div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="metric-label">Não Despachado</div>
                    <div class="metric-value">{{ $medaOpenHistogram['total_without_job'] ?? 0 }}</div>
                    <div class="metric-subtitle">Sem Atividade de Reclamação</div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="metric-label">BT Zero</div>
                    <div class="metric-value">{{ $medaOpenHistogram['total_btzero'] ?? 0 }}</div>
                    <div class="metric-subtitle">
                        {{ $medaOpenHistogram['total_btzero_with_job'] ?? 0 }} com atividade /
                        {{ $medaOpenHistogram['total_btzero_without_job'] ?? 0 }} não despachado
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center gap-2 flex-wrap mb-2">
                <button type="button"
                    class="btn btn-sm {{ ($medaOpenNoteSummary['selected'] ?? null) === 'NA' ? 'btn-primary' : 'btn-outline-secondary' }}"
                    wire:click="setMedaOpenNoteTypeFilter('NA')">
                    NA: {{ $medaOpenNoteSummary['NA'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaOpenNoteSummary['selected'] ?? null) === 'OU' ? 'btn-primary' : 'btn-outline-secondary' }}"
                    wire:click="setMedaOpenNoteTypeFilter('OU')">
                    OU: {{ $medaOpenNoteSummary['OU'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaOpenNoteSummary['selected'] ?? null) === 'PR' ? 'btn-primary' : 'btn-outline-secondary' }}"
                    wire:click="setMedaOpenNoteTypeFilter('PR')">
                    PR: {{ $medaOpenNoteSummary['PR'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaDueSummary['selected'] ?? null) === 'overdue' ? 'btn-danger' : 'btn-outline-danger' }}"
                    wire:click="setMedaDueWindowFilter('overdue')">
                    Vencido: {{ $medaDueSummary['overdue'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaDueSummary['selected'] ?? null) === 'due_soon' ? 'btn-warning' : 'btn-outline-warning' }}"
                    wire:click="setMedaDueWindowFilter('due_soon')">
                    Vencendo: {{ $medaDueSummary['due_soon'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaDueSummary['selected'] ?? null) === 'today' ? 'btn-warning' : 'btn-outline-warning' }}"
                    wire:click="setMedaDueWindowFilter('today')">
                    Hoje: {{ $medaDueSummary['today'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaDueSummary['selected'] ?? null) === 'tomorrow' ? 'btn-warning' : 'btn-outline-warning' }}"
                    wire:click="setMedaDueWindowFilter('tomorrow')">
                    Amanhã: {{ $medaDueSummary['tomorrow'] ?? 0 }}
                </button>
                <button type="button"
                    class="btn btn-sm {{ ($medaDueSummary['selected'] ?? null) === 'in_3_days' ? 'btn-warning' : 'btn-outline-warning' }}"
                    wire:click="setMedaDueWindowFilter('in_3_days')">
                    Em 3 dias: {{ $medaDueSummary['in_3_days'] ?? 0 }}
                </button>
                @if (!empty($medaOpenNoteSummary['selected']) || !empty($medaDueSummary['selected']))
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        wire:click="clearMedaQuickFilters">
                        Limpar filtros de tipo/prazo
                    </button>
                @endif
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-xxl-11">
                    <div class="dashboard-histogram-wrap" wire:ignore>
                        <x-grafico.apex :chart="$medaOpenHistogram['chart']" chartId="medaOpenDesiredHistogram" class="w-100" />
                    </div>
                </div>
            </div>
            @php
                $selectedBucket = (string) ($medaOpenHistogram['selectedBucket'] ?? '');
                $monthTotals = (array) ($medaOpenHistogram['monthTotals'] ?? []);
                $monthLabels = (array) ($medaOpenHistogram['monthLabels'] ?? []);
            @endphp
            <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                @foreach ($monthLabels as $bucketKey => $monthLabel)
                    @php
                        $isActive = $selectedBucket === $bucketKey;
                        $hasData = ((int) ($monthTotals[$bucketKey] ?? 0)) > 0;
                    @endphp
                    <button type="button"
                        class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                        @disabled(!$hasData)
                        wire:click="toggleMedaHistogramBucket('{{ $bucketKey }}')">
                        {{ $monthLabel }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
        </div>
        <div class="col-12 col-xxl-4">
            <div class="modern-card mb-0 h-100">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <div>
                            <div class="modern-card-title mb-1">
                                <i class="ri-alert-line me-1"></i> Concluído e Aberto no SAP
                            </div>
                            <div class="small text-muted">
                                Medidas com todos os jobs concluídos, porém ainda em MEDA. Responsável aqui é o despachante (created_by).
                            </div>
                            <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                                <i class="ri-information-line me-1"></i>Os dados podem divergir devido ao tempo de atualização das informações com o SAP.
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark">Total medidas: {{ $medaDoneOpenByOwner['total'] ?? 0 }}</span>
                            @if (!empty($medaDoneOpenByOwner['selected_creator_id']))
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="setMedaDoneOpenCreator(null)">
                                    Limpar responsável
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Despachante (created_by)</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">NA</th>
                                    <th class="text-center">OU</th>
                                    <th class="text-center">PR</th>
                                    <th class="text-center">OU/PR > 24h</th>
                                    <th class="text-center">Filtrar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($medaDoneOpenByOwner['groups'] ?? []) as $group)
                                    @php
                                        $selectedCreatorId = (string) ($medaDoneOpenByOwner['selected_creator_id'] ?? '');
                                        $groupCreatorId = (string) ($group['creator_id'] ?? '');
                                        $isActiveOwner = $selectedCreatorId !== '' && $selectedCreatorId === $groupCreatorId;
                                    @endphp
                                    <tr>
                                        <td>{{ $group['creator_name'] }}</td>
                                        <td class="text-center">{{ $group['total'] }}</td>
                                        <td class="text-center">{{ $group['na'] }}</td>
                                        <td class="text-center">{{ $group['ou'] }}</td>
                                        <td class="text-center">{{ $group['pr'] }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ ($group['ou_pr_over24h'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }}">
                                                {{ $group['ou_pr_over24h'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if (!empty($group['creator_id']))
                                                <button type="button"
                                                    class="btn btn-sm {{ $isActiveOwner ? 'btn-primary' : 'btn-outline-primary' }}"
                                                    wire:click="setMedaDoneOpenCreator('{{ $group['creator_id'] }}')">
                                                    {{ $isActiveOwner ? 'Aplicado' : 'Filtrar' }}
                                                </button>
                                            @else
                                                <span class="text-muted">---</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            Nenhuma medida encontrada neste critério.
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

    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-table-2 me-1"></i> Lista consolidada de medidas em aberto
                    </div>
                    <div class="small text-muted">
                        Base de medidas em aberto sem recorte temporal, refletindo exatamente o gráfico selecionado.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model="medaHistogramBtzeroFilter" class="form-select form-select-sm"
                        style="min-width: 180px;">
                        <option value="all">Todos</option>
                        <option value="without_btzero">Sem BT Zero</option>
                        <option value="only_btzero">Apenas BT Zero</option>
                    </select>
                    <span class="badge bg-light text-dark">Total: {{ $medaOpenDispatchList['total'] ?? 0 }}</span>
                    <span class="badge bg-success">Despachado em aberto:
                        {{ $medaOpenDispatchList['total_dispatched_open'] ?? 0 }}</span>
                    <span class="badge bg-secondary">Não Despachado:
                        {{ $medaOpenDispatchList['total_without_dispatch'] ?? 0 }}</span>
                    <span class="badge bg-light text-dark">NA: {{ $medaOpenDispatchList['note_type_counts']['NA'] ?? 0 }}</span>
                    <span class="badge bg-light text-dark">OU: {{ $medaOpenDispatchList['note_type_counts']['OU'] ?? 0 }}</span>
                    <span class="badge bg-light text-dark">PR: {{ $medaOpenDispatchList['note_type_counts']['PR'] ?? 0 }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-compact mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>M</th>
                            <th>Nota</th>
                            <th>Tipo</th>
                            <th>CodF</th>
                            <th>Tipo Reclamação</th>
                            <th>Classificação Reclamação</th>
                            <th>Abertura Reclamação</th>
                            <th>Abertura Medida</th>
                            <th>Data Desejada</th>
                            <th>Despachado Em</th>
                            <th>Encerrado / SLA</th>
                            <th>Status SLA</th>
                            <th>SAP</th>
                            <th>Despachante / Responsável</th>
                            <th>Abrir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($medaOpenDispatchList['items'] ?? collect()) as $row)
                            <tr>
                                <td>{{ $row['med_id'] }}</td>
                                <td>{{ $row['nota'] }}</td>
                                <td>{{ $row['tipo_nota'] }}</td>
                                <td>{{ $row['codf'] }}</td>
                                <td>{{ $row['tipo_reclamacao'] }}</td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span>{{ $row['classificacao_reclamacao'] }}</span>
                                        @if ($row['is_btzero'])
                                            <span class="badge bg-primary">BT Zero</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $row['abertura_reclamacao'] }}</td>
                                <td>{{ $row['abertura_medida'] }}</td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span>{{ $row['desired_info']['date'] }}</span>
                                        <span class="badge {{ $row['desired_info']['class'] }}">
                                            {{ $row['desired_info']['detail'] }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if ($row['has_dispatch'])
                                        {{ $row['despachado_em'] }}
                                    @else
                                        <span class="badge bg-secondary">Não Despachado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span>{{ $row['sla_info']['due_date'] }}</span>
                                        <span class="badge {{ $row['sla_info']['class'] }}">
                                            {{ $row['sla_info']['detail'] }}
                                        </span>
                                    </div>
                                </td>
                                <td><span class="{{ $row['status_sla_class'] }}">{{ $row['status_sla_label'] }}</span></td>
                                <td><span class="badge {{ $row['sap_class'] }}">{{ $row['sap_status'] }}</span></td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span><strong>Despachante:</strong> {{ $row['despachante'] ?? 'Sem despachante' }}</span>
                                        <span><strong>Responsável:</strong> {{ $row['responsavel'] ?? 'Sem responsável' }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if (!empty($row['id']))
                                        <a href="{{ route('protests.dispatch.view', ['protest' => $row['id']]) }}"
                                            class="btn btn-sm btn-outline-secondary" title="Abrir protesto/medida">
                                            <i class="ri-external-link-line"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">---</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center text-muted py-3">
                                    Nenhuma medida encontrada para os critérios atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($medaOpenDispatchList['items']))
                <div class="mt-3">
                    {{ $medaOpenDispatchList['items']->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-header mb-4">
        <div class="header-content mb-3">
            <div class="d-flex align-items-center mb-2">
                <div class="header-icon me-3">
                    <i class="ri-filter-2-line"></i>
                </div>
                <div>
                    <h3 class="header-title mb-0" style="font-size:1.4rem;">Filtros Analíticos</h3>
                    <div class="header-subtitle">
                        Ajuste período, usuário e universo da análise
                    </div>
                </div>
            </div>
        </div>

        <div class="filters-container">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="filter-label"><i class="ri-calendar-line me-1"></i> Início</label>
                    <input type="date" wire:model="dt_in" class="filter-select" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="filter-label"><i class="ri-calendar-line me-1"></i> Fim</label>
                    <input type="date" wire:model="dt_out" class="filter-select" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="filter-label"><i class="ri-filter-2-line me-1"></i> Tipo</label>
                    <select wire:model="advanceFilter" class="filter-select">
                        <option value="all">Todos</option>
                        <option value="advance">Avanço Parceiro</option>
                        <option value="normal">Reclamações normais</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="filter-label"><i class="ri-user-3-line me-1"></i> Usuário</label>
                    <select wire:model="userId" class="filter-select">
                        <option value="">Todos</option>
                        @foreach ($usersOptions as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 col-md-12">
                    <label class="filter-label"><i class="ri-search-line me-1"></i> Buscar por reclamação</label>
                    <input type="text" wire:model.debounce.500ms="complaintSearch" class="filter-select"
                        placeholder="Nota, CodF, grupo, classificação, cidade...">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="filter-label"><i class="ri-file-list-3-line me-1"></i> Tipo de nota</label>
                    <select wire:model="complaintNoteTypes" multiple class="filter-select" size="4">
                        @foreach ($complaintNoteTypeOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label"><i class="ri-price-tag-3-line me-1"></i> Classificação reclamação</label>
                    <select wire:model="complaintClassifications" multiple class="filter-select" size="4">
                        @foreach ($complaintClassificationOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label"><i class="ri-map-pin-line me-1"></i> Município</label>
                    <select wire:model="complaintCities" multiple class="filter-select" size="4">
                        @foreach ($complaintCityOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label"><i class="ri-map-pin-user-line me-1"></i> Tipo de protesto</label>
                    <select wire:model="protestTypes" multiple class="filter-select" size="4">
                        @foreach ($protestTypeOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <button class="btn btn-light fw-semibold text-primary px-4" wire:click="exportJobs"
                        wire:loading.attr="disabled" wire:target="exportJobs">
                        <span wire:loading.remove wire:target="exportJobs">
                            <i class="ri-file-excel-2-line me-1"></i>
                            Exportar Atividades de Reclamação
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

    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-list-check-3 me-1"></i> Lista geral de medidas
                    </div>
                    <div class="small text-muted">
                        Baseada em MedProtest. Sem busca: respeita período. Com busca: ignora período.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model="generalMeasuresOpenFilter" class="form-select form-select-sm"
                        style="min-width: 150px;">
                        <option value="all">Abertura: Todos</option>
                        <option value="open">Em aberto</option>
                        <option value="not_open">Não em aberto</option>
                    </select>
                    <select wire:model="generalMeasuresBtzeroFilter" class="form-select form-select-sm"
                        style="min-width: 170px;">
                        <option value="all">BT Zero: Todos</option>
                        <option value="with_btzero">Com BT Zero</option>
                        <option value="without_btzero">Sem BT Zero</option>
                    </select>
                    <span class="badge bg-light text-dark">Total: {{ $generalProtestsList['total'] ?? 0 }}</span>
                    @if (!empty($generalProtestsList['search_mode']))
                        <span class="badge bg-info text-dark">Busca ativa: sem recorte de período</span>
                    @else
                        <span class="badge bg-primary">
                            Período do recorte: {{ $generalProtestsList['period_label'] ?? '-' }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-compact mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>M</th>
                            <th>Nota</th>
                            <th>Tipo</th>
                            <th>CodF</th>
                            <th>Tipo Reclamação</th>
                            <th>Classificação Reclamação</th>
                            <th>Abertura Reclamação</th>
                            <th>Abertura Medida</th>
                            <th>Data Desejada</th>
                            <th>SAP</th>
                            <th>Responsável</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($generalProtestsList['items'] ?? collect()) as $row)
                            <tr>
                                <td>{{ $row['med_id'] }}</td>
                                <td>{{ $row['nota'] }}</td>
                                <td>{{ $row['tipo_nota'] }}</td>
                                <td>{{ $row['codf'] }}</td>
                                <td>{{ $row['tipo_reclamacao'] }}</td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span>{{ $row['classificacao_reclamacao'] }}</span>
                                        @if ($row['is_btzero'])
                                            <span class="badge bg-primary">BT Zero</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $row['abertura_reclamacao'] }}</td>
                                <td>{{ $row['abertura_medida'] }}</td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span>{{ $row['desired_info']['date'] }}</span>
                                        <span class="badge {{ $row['desired_info']['class'] }}">
                                            {{ $row['desired_info']['detail'] }}
                                        </span>
                                    </div>
                                </td>
                                <td><span class="badge {{ $row['sap_class'] }}">{{ $row['sap_status'] }}</span></td>
                                <td>{{ $row['responsavel'] }}</td>
                                <td>{{ $row['resultado'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-3">
                                    Nenhuma medida encontrada para os critérios atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($generalProtestsList['items']))
                <div class="mt-3">
                    {{ $generalProtestsList['items']->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-bar-chart-grouped-line me-1"></i> Reclamações (NA)
                    </div>
                    <div class="small text-muted">
                        Janela fixa de 6 meses (mensalização por <code>dtConclusaoDesej</code>), com classificação
                        procedente/improcedente por <code>statUsuar</code> (ENCP/ENCI).
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model="complaintsBtzeroFilter" class="form-select form-select-sm"
                        style="min-width: 180px;">
                        <option value="without_btzero">BT Zero: Sem BT Zero</option>
                        <option value="all">BT Zero: Todos</option>
                        <option value="only_btzero">BT Zero: Apenas BT Zero</option>
                    </select>
                    <span class="badge bg-light text-dark">Período: {{ $complaintsNaPanel['window_label'] ?? '-' }}</span>
                    <span class="badge bg-primary">Encerramentos: {{ $complaintsNaPanel['total'] ?? 0 }}</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div style="height: 270px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsNaPanel['charts']['sla_stack']" chartId="complaintsNaSlaStack"
                            class="w-100" />
                    </div>
                </div>
                <div class="col-12">
                    <div style="height: 230px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsNaPanel['charts']['sla_line']" chartId="complaintsNaSlaLine"
                            class="w-100" />
                    </div>
                </div>
                <div class="col-12">
                    <div style="height: 250px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsNaPanel['charts']['procedency_stack']"
                            chartId="complaintsNaProcedencyStack" class="w-100" />
                    </div>
                </div>
                <div class="col-12">
                    <div style="height: 230px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsNaPanel['charts']['procedency_line']"
                            chartId="complaintsNaProcedencyLine" class="w-100" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-bar-chart-grouped-line me-1"></i> Ouvidoria (OU)
                    </div>
                    <div class="small text-muted">
                        Janela fixa de 6 meses (mensalização por <code>dtConclusaoDesej</code>). Fora do prazo se
                        alguma medida estourar o prazo; procedente se alguma medida tiver
                        <code>result = Procedente</code>; sem result nas medidas, usa <code>statUsuar</code>.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model="complaintsBtzeroFilter" class="form-select form-select-sm"
                        style="min-width: 180px;">
                        <option value="without_btzero">BT Zero: Sem BT Zero</option>
                        <option value="all">BT Zero: Todos</option>
                        <option value="only_btzero">BT Zero: Apenas BT Zero</option>
                    </select>
                    <span class="badge bg-light text-dark">Período: {{ $complaintsOuPanel['window_label'] ?? '-' }}</span>
                    <span class="badge bg-primary">Encerramentos: {{ $complaintsOuPanel['total'] ?? 0 }}</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div style="height: 270px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsOuPanel['charts']['sla_stack']" chartId="complaintsOuSlaStack"
                            class="w-100" />
                    </div>
                </div>
                <div class="col-12">
                    <div style="height: 230px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsOuPanel['charts']['sla_line']" chartId="complaintsOuSlaLine"
                            class="w-100" />
                    </div>
                </div>
                <div class="col-12">
                    <div style="height: 250px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsOuPanel['charts']['procedency_stack']"
                            chartId="complaintsOuProcedencyStack" class="w-100" />
                    </div>
                </div>
                <div class="col-12">
                    <div style="height: 230px;" wire:ignore>
                        <x-grafico.apex :chart="$complaintsOuPanel['charts']['procedency_line']"
                            chartId="complaintsOuProcedencyLine" class="w-100" />
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
                    <div class="metric-label">Reclamacoes enviadas</div>
                    <div class="metric-value">{{ $productivity['total_dispatched'] }}</div>
                    <div class="metric-subtitle">
                        Periodo atual · media diaria {{ number_format($productivity['avg_daily_dispatch'], 1) }}
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Concluidas (Meta)</div>
                    <div class="metric-value">{{ $productivity['finished_meta'] }}</div>
                    <div class="metric-subtitle">Despachos do periodo atual</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Concluidas (Passivo)</div>
                    <div class="metric-value">{{ $productivity['finished_passive'] }}</div>
                    <div class="metric-subtitle">Despachos anteriores finalizados</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Passivo em aberto</div>
                    <div class="metric-value">{{ $productivity['passivo_aberto'] }}</div>
                    <div class="metric-subtitle">Enviados antes do periodo e ainda abertos</div>
                </div>
            </div>

            <div class="row text-center gy-3 mt-3">
                <div class="col-md-3 col-6">
                    <div class="metric-label">Reacao despachante</div>
                    <div class="metric-value">{{ $summary['avg_reaction_human'] }}</div>
                    <div class="metric-subtitle">Criacao da MEDA ate envio da Atividade de Reclamação</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Reacao responsavel</div>
                    <div class="metric-value">{{ $summary['avg_user_reaction_human'] }}</div>
                    <div class="metric-subtitle">Envio ate aceite (accepted_at)</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Execucao media</div>
                    <div class="metric-value">{{ $summary['avg_exec_human'] }}</div>
                    <div class="metric-subtitle">Envio ate conclusao</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">SLA e encerramento</div>
                    <div class="metric-value">{{ number_format($summary['sla_rate'], 1) }}%</div>
                    <div class="metric-subtitle">
                        Auto encerradas: {{ number_format($summary['self_closure_rate'], 1) }}%
                    </div>
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
                        <span class="modern-card-title">Responsaveis (owner_id)</span>
                        <span class="badge bg-light text-dark">SLA e execucao</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-center">Abertos</th>
                                    <th class="text-center">Concluidos</th>
                                    <th class="text-center">SLA</th>
                                    <th class="text-center">Exec. media</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ownerStats as $row)
                                    <tr>
                                        <td>{{ $row['user_name'] }}</td>
                                        <td class="text-center">{{ $row['total_jobs'] }}</td>
                                        <td class="text-center">{{ $row['open_jobs'] }}</td>
                                        <td class="text-center">{{ $row['finished_jobs'] }}</td>
                                        <td class="text-center">{{ number_format($row['sla_rate'], 1) }}%</td>
                                        <td class="text-center">{{ $row['avg_exec_human'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Nenhum responsavel com dados no periodo.
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

    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-donut-chart-line me-1"></i> Reclamações por classificacao
                    </div>
                    <div class="small text-muted">
                        Distribuicao de <code>protests.type</code> para os protestos despachados no período filtrado.
                    </div>
                </div>
                <span class="badge bg-light text-dark">Total de reclamações: {{ $protestTypeDonut['total'] ?? 0 }}</span>
            </div>

            @if (($protestTypeDonut['total'] ?? 0) > 0)
                <div class="row g-3 align-items-center">
                    <div class="col-lg-7">
                        <div style="width: 100%; aspect-ratio: 1 / 1;" wire:ignore>
                            <x-grafico.apex :chart="$protestTypeDonut['chart']" chartId="protestTypeDonut"
                                class="w-100" />
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-compact mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Classificacao</th>
                                        <th class="text-end">Qtd.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (($protestTypeDonut['rows'] ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ $row['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center text-muted py-4">
                    Sem dados de <code>protests.type</code> para os filtros atuais.
                </div>
            @endif
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

    {{-- Painel 5 --}}
    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-team-line me-1"></i> Painel 5 - Produtividade dos despachantes (MEDE)
                    </div>
                    <div class="small text-muted">
                        Medidas encerradas (MEDE) com vencimento em <code>dtFimMedidaDesej</code>.
                        Para tipoNota NA, o SLA usa <code>dtConclusaoDesej</code>.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">{{ $dispatcherMeasuresPanel['period_label'] }}</span>
                    <button class="btn btn-outline-primary btn-sm fw-semibold" wire:click="exportDispatcherMeasures"
                        wire:loading.attr="disabled" wire:target="exportDispatcherMeasures">
                        <span wire:loading.remove wire:target="exportDispatcherMeasures">
                            <i class="ri-file-excel-2-line me-1"></i>
                            Exportar MEDE
                        </span>
                        <span wire:loading wire:target="exportDispatcherMeasures">
                            <i class="ri-loader-4-line me-1"></i>
                            Preparando...
                        </span>
                    </button>
                </div>
            </div>

            <div class="row text-center gy-3">
                <div class="col-md-3 col-6">
                    <div class="metric-label">Medidas no periodo</div>
                    <div class="metric-value">{{ $dispatcherMeasuresPanel['total_measures'] }}</div>
                    <div class="metric-subtitle">Base total do periodo</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Dentro do prazo</div>
                    <div class="metric-value">{{ $dispatcherMeasuresPanel['on_time'] }}</div>
                    <div class="metric-subtitle">
                        {{ number_format($dispatcherMeasuresPanel['on_time_rate'], 1) }}% dentro do prazo
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Fora do prazo</div>
                    <div class="metric-value">{{ $dispatcherMeasuresPanel['late'] }}</div>
                    <div class="metric-subtitle">SLA geral do periodo</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Despachadas</div>
                    <div class="metric-value">{{ $dispatcherMeasuresPanel['dispatched_total'] }}</div>
                    <div class="metric-subtitle">Com Atividade de Reclamação registrada</div>
                </div>
            </div>

            <div class="row text-center gy-3 mt-2">
                <div class="col-md-3 col-6">
                    <div class="metric-label">Despachadas no prazo</div>
                    <div class="metric-value">{{ $dispatcherMeasuresPanel['dispatched_on'] }}</div>
                    <div class="metric-subtitle">SLA despachadas</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">Despachadas fora do prazo</div>
                    <div class="metric-value">{{ $dispatcherMeasuresPanel['dispatched_late'] }}</div>
                    <div class="metric-subtitle">Atrasos apurados</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">SLA despachadas</div>
                    <div class="metric-value">{{ number_format($dispatcherMeasuresPanel['dispatched_rate'], 1) }}%</div>
                    <div class="metric-subtitle">Dentro do prazo</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="metric-label">SLA geral</div>
                    <div class="metric-value">{{ number_format($dispatcherMeasuresPanel['on_time_rate'], 1) }}%</div>
                    <div class="metric-subtitle">Todas as medidas</div>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-compact mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Despachante</th>
                            <th class="text-center">Medidas</th>
                            <th class="text-center">Dentro do prazo</th>
                            <th class="text-center">Fora do prazo</th>
                            <th class="text-center">SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dispatcherMeasuresPanel['dispatchers'] as $row)
                            <tr>
                                <td>{{ $row['user_name'] }}</td>
                                <td class="text-center">{{ $row['total'] }}</td>
                                <td class="text-center text-success">{{ $row['on_time'] }}</td>
                                <td class="text-center text-danger">{{ $row['late'] }}</td>
                                <td class="text-center">{{ number_format($row['sla_rate'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Sem despachos registrados no periodo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="modern-card-title">
                        <i class="ri-table-line me-1"></i> Medidas despachadas por usuario
                    </span>
                    @if ($dispatcherMeasuresPanel['selected_user'])
                        <span class="badge bg-light text-dark">
                            {{ $dispatcherMeasuresPanel['selected_user']['name'] }}
                        </span>
                    @else
                        <span class="badge bg-light text-dark">Selecione um usuario</span>
                    @endif
                </div>

                @if ($dispatcherMeasuresPanel['selected_user'])
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reclamacao</th>
                                    <th class="text-center">Medida</th>
                                    <th class="text-center">Vencimento</th>
                                    <th class="text-center">Finalizado</th>
                                    <th class="text-center">Atividade</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dispatcherMeasuresPanel['selected_user']['measures'] as $measure)
                                    <tr>
                                        <td>{{ $measure['protest_number'] }}</td>
                                        <td class="text-center">{{ $measure['med_id'] }}</td>
                                        <td class="text-center">{{ $measure['due_date'] }}</td>
                                        <td class="text-center">{{ $measure['finished_at'] }}</td>
                                        <td class="text-center">#{{ $measure['job_id'] ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $measure['status_badge'] }} text-white px-3">
                                                {{ $measure['status_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Nenhuma medida encontrada para este usuario.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $dispatcherMeasuresPanel['selected_user']['measures']->links() }}
                    </div>
                @else
                    <div class="text-muted small">
                        Selecione um usuario no filtro para listar as medidas despachadas.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Painel 2 --}}
    <div class="modern-card insight-card mb-4">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="modern-card-title mb-1">
                        <i class="ri-pulse-line me-1"></i> Painel 2 - Saude do backlog (MEDIDAS)
                    </div>
                    <div class="small text-muted">
                        Totais calculados por <code>dtCriacaoMedida</code> no periodo selecionado.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $medaSnapshot['status_badge_class'] }}">
                        Saude: {{ $medaSnapshot['status_label'] }}
                    </span>
                    <span class="badge bg-light text-dark">
                        Atualizado em {{ now()->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="row text-center gy-3">
                <div class="col-md-4 col-6">
                    <div class="metric-label">Medidas criadas</div>
                    <div class="metric-value">{{ $backlogPanel['period_total'] }}</div>
                    <div class="metric-subtitle">Período: {{ $summary['period_label'] }}</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">Despachadas</div>
                    <div class="metric-value">{{ $backlogPanel['period_with_job'] }}</div>
                    <div class="metric-subtitle">MEDA com Atividade de Reclamação</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">Não despachadas</div>
                    <div class="metric-value">{{ $backlogPanel['period_without_job'] }}</div>
                    <div class="metric-subtitle">FECHADAS SAP ou sem Atividade de Reclamação</div>
                </div>
            </div>

            <hr>

            <div class="row text-center gy-3">
                <div class="col-md-4 col-6">
                    <div class="metric-label">Em aberto</div>
                    <div class="metric-value">{{ $backlogPanel['current_open'] }}</div>
                    <div class="metric-subtitle">Total status MEDA</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">Não Despachado</div>
                    <div class="metric-value">{{ $backlogPanel['current_open_without_job'] }}</div>
                    <div class="metric-subtitle">Em aberto e sem Atividade de Reclamação</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">Passivo</div>
                    <div class="metric-value">{{ $backlogPanel['passive_open'] }}</div>
                    <div class="metric-subtitle">Abertas em {{ $backlogPanel['passive_month_label'] }}</div>
                </div>
            </div>

            <div class="row text-center gy-3 mt-2">
                <div class="col-md-4 col-6">
                    <div class="metric-label">Não Despachado há 5+ dias</div>
                    <div class="metric-value">{{ $backlogPanel['older_than_5'] }}</div>
                    <div class="metric-subtitle">MEDA em atraso sem despacho</div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="metric-label">Vencidas</div>
                    <div class="metric-value">{{ $backlogPanel['expired_open'] }}</div>
                    <div class="metric-subtitle">dtFimMedidaDesej ultrapassado</div>
                </div>
                <div class="col-md-4 col-6">

                </div>

            </div>

            <div class="row text-center gy-3 mt-3">
                <div class="col-md-4 col-12">
                    <div class="metric-label">Despachos por dia</div>
                    <div class="metric-value">{{ number_format($medaSnapshot['avg_dispatch_daily'], 1) }}</div>
                    <div class="metric-subtitle">
                        {{ $medaSnapshot['dispatcher_users'] }} despachantes ·
                        {{ number_format($medaSnapshot['avg_dispatch_per_user'], 1) }} cada
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="metric-label">Conclusoes por dia</div>
                    <div class="metric-value">{{ number_format($medaSnapshot['avg_finish_daily'], 1) }}</div>
                    <div class="metric-subtitle">
                        {{ $medaSnapshot['executor_users'] }} responsaveis ·
                        {{ number_format($medaSnapshot['avg_finish_per_user'], 1) }} cada
                    </div>
                </div>
                <div class="col-md-4 col-12">
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
            <p class="small text-muted mt-2 mb-0">
                Legenda: "Não despachadas" inclui MEDA resolvidas diretamente no SAP (MEDE)
                ou sem Atividade de Reclamação registrada.
            </p>
        </div>
    </div>

    <div class="chart-card mb-4">
        <div class="chart-card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-stack-line me-2"></i> Medidas criadas por dia (Total MEDA e MEDE)</h5>
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
                        <i class="ri-time-line me-1"></i> Painel 3 - Analise mensal e SLA
                    </div>
                    <div class="small text-muted">
                        Volumetria das MEDA criadas e cumprimento dos SLAs solicitado x medida no período.
                    </div>
                </div>
                <span class="badge bg-light text-dark">
                    {{ $summary['period_label'] }}
                </span>
            </div>

            <div class="row text-center gy-3">
                <div class="col-md-4 col-12">
                    <div class="metric-label">Medidas criadas</div>
                    <div class="metric-value">{{ $slaPanel['med_created'] }}</div>
                    <div class="metric-subtitle">dtCriacaoMedida no período</div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="metric-label">Abertas (MEDA)</div>
                    <div class="metric-value">{{ $slaPanel['med_open_status'] }}</div>
                    <div class="metric-subtitle">Ainda aguardando execução</div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="metric-label">Encerradas (MEDE)</div>
                    <div class="metric-value">{{ $slaPanel['med_closed_status'] }}</div>
                    <div class="metric-subtitle">Concluídas no período</div>
                </div>
            </div>

            <div class="row text-center gy-3 mt-3">
                <div class="col-md-4 col-12">
                    <div class="metric-label">Conclusões</div>
                    <div class="metric-value">{{ $slaPanel['concluded_total'] }}</div>
                    <div class="metric-subtitle">
                        {{ number_format($slaPanel['concluded_rate'], 1) }}% dentro do prazo
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="metric-label">SLA solicitado (Atividades de Reclamação)</div>
                    <div class="metric-value">
                        {{ $slaPanel['job_sla']['on_time'] }} / {{ $slaPanel['job_sla']['total'] }}
                    </div>
                    <div class="metric-subtitle">
                        {{ number_format($slaPanel['job_sla']['rate'], 1) }}% cumprido
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="metric-label">SLA da medida</div>
                    <div class="metric-value">
                        {{ $slaPanel['measure_sla']['on_time'] }} / {{ $slaPanel['measure_sla']['total'] }}
                    </div>
                    <div class="metric-subtitle">
                        {{ number_format($slaPanel['measure_sla']['rate'], 1) }}% cumprido
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-lg-6">
                    <div style="min-height: 280px;" wire:ignore>
                        <x-grafico.apex :chart="$slaPanel['volumetry_chart']" chartId="medaVolumetry" class="w-100" />
                    </div>
                </div>
                <div class="col-lg-6">
                    <div style="min-height: 280px;" wire:ignore>
                        <x-grafico.apex :chart="$slaPanel['sla_chart']" chartId="slaComparison" class="w-100" />
                    </div>
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
                                <th class="text-center">SLA Medida</th>
                                <th class="text-center">SLA Solicitado</th>
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
                                        <div class="small text-muted">Atividade #{{ $job['job_id'] }}</div>
                                    </td>
                                    <td class="text-center">{{ $job['med_id'] }}</td>
                                    <td class="text-center">{{ $job['med_sla_due_at'] }}</td>
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
                                        Nenhuma Atividade de Reclamação com SLA encontrada no período/filtro.
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
                                    <th class="text-center">Total<br>no período</th>
                                    <th class="text-center">Abertas</th>
                                    <th class="text-center">Passivo</th>
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
                                        <td class="text-center">{{ $row['passivo'] }}</td>
                                        <td class="text-center">{{ $row['vencidas'] }}</td>
                                        <td class="text-center">{{ number_format($row['percent'], 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Sem dados para o
                                            período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $bottlenecks['categories_totals']['total'] }}</td>
                                    <td class="text-center">{{ $bottlenecks['categories_totals']['abertas'] }}</td>
                                    <td class="text-center">{{ $bottlenecks['categories_totals']['passivo'] }}</td>
                                    <td class="text-center">{{ $bottlenecks['categories_totals']['vencidas'] }}</td>
                                    <td class="text-center">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Legenda: % = participação da categoria no total de MEDA criadas no período.
                        Passivo = MEDA abertas no mês anterior que ainda permanecem em status MEDA.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <h6 class="text-uppercase text-muted small">Tipos de nota (criadas no período)</h6>
                            <ul class="list-group list-group-flush">
                                @forelse ($bottlenecks['tipo_nota'] as $tipo)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>{{ $tipo['tipo'] }}</span>
                                        <span class="badge bg-primary">{{ $tipo['total'] }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">Sem registros.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-uppercase text-muted small">Tipos de nota vencidas no período</h6>
                            <ul class="list-group list-group-flush">
                                @forelse ($bottlenecks['tipo_nota_late'] as $tipo)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
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
            <h5 class="mb-0"><i class="ri-bar-chart-2-line me-2"></i> Aberturas diárias (Reclamações x Medidas)
            </h5>
        </div>
        <div class="chart-card-body" wire:ignore>
            <div style="max-height: 380px;">
                <x-grafico.apex :chart="$dailyOpenings" chartId="dailyOpenings" class="w-100" />
            </div>
        </div>
    </div>

</div>
