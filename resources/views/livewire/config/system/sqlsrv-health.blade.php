<div>
    <style>
        .sqlsrv-page {
            color: #0f172a;
        }

        .sqlsrv-hero {
            background:
                radial-gradient(circle at 85% 15%, rgba(34, 197, 94, .22), transparent 28%),
                linear-gradient(135deg, #0f172a 0%, #114e50 55%, #0f766e 100%);
            border-radius: 18px;
            color: #fff;
            padding: 30px 36px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, .18);
        }

        .sqlsrv-shell {
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(203, 213, 225, .75);
            border-radius: 16px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .10);
        }

        .sqlsrv-tabs {
            background: #eef5f4;
            border-radius: 12px;
            display: inline-flex;
            gap: 6px;
            padding: 6px;
        }

        .sqlsrv-tab {
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #31545a;
            font-weight: 700;
            padding: 10px 16px;
        }

        .sqlsrv-tab.active {
            background: #fff;
            color: #0f766e;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
        }

        .sqlsrv-card {
            background: #fff;
            border: 1px solid #dce7ee;
            border-radius: 14px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .07);
        }

        .sqlsrv-metric {
            min-height: 118px;
            padding: 18px;
        }

        .sqlsrv-metric span {
            color: #64748b;
            display: block;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sqlsrv-metric strong {
            display: block;
            font-size: 2rem;
            line-height: 1.1;
            margin-top: 8px;
        }

        .sqlsrv-status-pill {
            border-radius: 999px;
            display: inline-flex;
            font-weight: 800;
            gap: 8px;
            padding: 9px 14px;
        }

        .sqlsrv-status-ok {
            background: #dcfce7;
            color: #166534;
        }

        .sqlsrv-status-alert {
            background: #fee2e2;
            color: #991b1b;
        }

        .sqlsrv-chart {
            height: 285px;
        }

        .sqlsrv-scroll-list {
            max-height: 360px;
            overflow-y: auto;
            padding-right: 6px;
        }

        .sqlsrv-scroll-list thead th {
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .sqlsrv-table th {
            color: #64748b;
            font-size: .74rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sqlsrv-soft-label {
            background: #e0f2fe;
            border-radius: 999px;
            color: #075985;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 700;
            padding: 5px 9px;
        }

        .sqlsrv-date-alert td {
            background: #fff1f2;
            color: #991b1b;
        }

        .sqlsrv-date-alert .fw-semibold {
            color: #991b1b;
        }

        .sqlsrv-process-grid {
            max-height: 430px;
            overflow-y: auto;
        }

        .sqlsrv-row-failed td,
        .sqlsrv-row-missing td,
        .sqlsrv-row-late td {
            background: #fff1f2;
        }

        .sqlsrv-row-pending td {
            background: #f8fafc;
        }

        .sqlsrv-row-success td {
            background: #f0fdf4;
        }

        /* ─── Technical tab redesign ─── */

        .tech-kpi {
            border-radius: 14px;
            padding: 20px 22px;
            background: #fff;
            border: 1px solid #dce7ee;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tech-kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .tech-kpi-value {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
        }

        .tech-kpi-label {
            font-size: .78rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .tech-kpi-sub {
            font-size: .8rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .tech-chart-card {
            background: #fff;
            border: 1px solid #dce7ee;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .tech-chart-header {
            padding: 16px 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-chart-header i {
            font-size: 16px;
            color: #0f766e;
        }

        .tech-chart-body {
            padding: 8px 16px 16px;
        }

        .tech-log-card {
            background: #fff;
            border: 1px solid #dce7ee;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .tech-log-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-log-header h2 {
            color: #fff;
            margin: 0;
        }

        .tech-log-body {
            padding: 0;
        }

        .tech-log-body .table {
            margin-bottom: 0;
        }

        .tech-log-body .table thead th {
            background: #f1f5f9;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 14px;
            font-size: .74rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .tech-log-body .table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .tech-log-body .table tbody tr:hover td {
            background: #f8fafc;
        }

        .tech-log-row-error td {
            background: #fef2f2 !important;
        }

        .tech-log-row-error td:first-child {
            border-left: 3px solid #dc2626;
        }

        .tech-log-row-ok td:first-child {
            border-left: 3px solid #16a34a;
        }

        .tech-log-footer {
            padding: 12px 20px;
            border-top: 1px solid #e2e8f0;
        }

        /* ─── Executive redesign ─── */

        .exec-health-banner {
            border-radius: 16px;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .exec-health-banner.is-healthy {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border: 2px solid #86efac;
        }

        .exec-health-banner.is-warning {
            background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%);
            border: 2px solid #fde047;
        }

        .exec-health-banner.is-critical {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #fca5a5;
        }

        .exec-health-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }

        .exec-health-banner.is-healthy .exec-health-icon {
            background: #166534;
            color: #fff;
        }

        .exec-health-banner.is-warning .exec-health-icon {
            background: #854d0e;
            color: #fff;
        }

        .exec-health-banner.is-critical .exec-health-icon {
            background: #991b1b;
            color: #fff;
        }

        .exec-summary-card {
            border-radius: 14px;
            padding: 22px 24px;
            text-align: center;
            border: 2px solid transparent;
            transition: transform .15s;
        }

        .exec-summary-card:hover {
            transform: translateY(-2px);
        }

        .exec-summary-card.card-ok {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .exec-summary-card.card-problem {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .exec-summary-card.card-wait {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .exec-summary-card .exec-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .card-ok .exec-card-icon {
            background: #166534;
            color: #fff;
        }

        .card-problem .exec-card-icon {
            background: #dc2626;
            color: #fff;
        }

        .card-wait .exec-card-icon {
            background: #94a3b8;
            color: #fff;
        }

        .exec-summary-card .exec-card-number {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1;
        }

        .card-ok .exec-card-number {
            color: #166534;
        }

        .card-problem .exec-card-number {
            color: #dc2626;
        }

        .card-wait .exec-card-number {
            color: #64748b;
        }

        .exec-summary-card .exec-card-label {
            font-size: .92rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .exec-alert-card {
            background: #fff;
            border-left: 5px solid #dc2626;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 4px 16px rgba(220, 38, 38, .10);
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .exec-alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .exec-alert-failed .exec-alert-icon {
            background: #fef2f2;
            color: #dc2626;
        }

        .exec-alert-missing .exec-alert-icon {
            background: #fff7ed;
            color: #ea580c;
        }

        .exec-alert-late .exec-alert-icon {
            background: #fefce8;
            color: #ca8a04;
        }

        .exec-alert-missing {
            border-left-color: #ea580c;
        }

        .exec-alert-late {
            border-left-color: #ca8a04;
        }

        .exec-timeline-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 6px;
            border: 1px solid #e2e8f0;
            transition: background .12s;
        }

        .exec-timeline-item:hover {
            background: #f8fafc;
        }

        .exec-timeline-item.tl-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .exec-timeline-item.tl-failed {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .exec-timeline-item.tl-missing {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .exec-timeline-item.tl-late {
            background: #fefce8;
            border-color: #fde68a;
        }

        .exec-timeline-item.tl-pending {
            background: #f8fafc;
            border-color: #e2e8f0;
            opacity: .7;
        }

        .tl-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .tl-success .tl-icon {
            background: #166534;
            color: #fff;
        }

        .tl-failed .tl-icon {
            background: #dc2626;
            color: #fff;
        }

        .tl-missing .tl-icon {
            background: #ea580c;
            color: #fff;
        }

        .tl-late .tl-icon {
            background: #ca8a04;
            color: #fff;
        }

        .tl-pending .tl-icon {
            background: #cbd5e1;
            color: #475569;
        }

        .tl-time {
            font-weight: 800;
            font-size: .92rem;
            width: 50px;
            flex-shrink: 0;
            color: #334155;
        }

        .tl-label {
            font-weight: 600;
            flex: 1;
        }

        .tl-status-text {
            font-size: .82rem;
            font-weight: 700;
            text-align: right;
            min-width: 120px;
        }

        .tl-success .tl-status-text {
            color: #166534;
        }

        .tl-failed .tl-status-text {
            color: #dc2626;
        }

        .tl-missing .tl-status-text {
            color: #ea580c;
        }

        .tl-late .tl-status-text {
            color: #ca8a04;
        }

        .tl-pending .tl-status-text {
            color: #94a3b8;
        }

        .exec-db-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 6px;
            border: 1px solid #e2e8f0;
        }

        .exec-db-ok {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .exec-db-stale {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .exec-db-item .db-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .exec-db-ok .db-dot {
            background: #16a34a;
        }

        .exec-db-stale .db-dot {
            background: #dc2626;
        }

        .exec-section-title {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .exec-section-desc {
            font-size: .88rem;
            color: #64748b;
            margin-bottom: 16px;
        }

        .exec-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
        }

        .exec-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .82rem;
            font-weight: 600;
            color: #475569;
        }

        .exec-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
    </style>

    <div class="sqlsrv-page">
        <div class="sqlsrv-hero mb-4 d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div>
                <div class="text-uppercase small opacity-75 fw-semibold">Atualizações de dados</div>
                <h1 class="h2 fw-bold mb-2">Painel de Saúde</h1>
                <p class="mb-0 opacity-75">Veja se os dados do sistema estão sendo atualizados corretamente.</p>
            </div>
            <div class="text-xl-end">
                <div class="small opacity-75">Verificado pela última vez em</div>
                <div class="fs-5 fw-bold">
                    {{ $kpis['last_collected_at'] ? $kpis['last_collected_at']->format('d/m/Y \à\s H:i') : 'Ainda sem verificação' }}
                </div>
            </div>
        </div>

        <div class="sqlsrv-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 align-items-xl-end">
                <div>
                    <div class="sqlsrv-tabs">
                        <button type="button" class="sqlsrv-tab {{ $activeTab === 'executive' ? 'active' : '' }}" wire:click="setActiveTab('executive')">
                            Visão geral
                        </button>
                        <button type="button" class="sqlsrv-tab {{ $activeTab === 'technical' ? 'active' : '' }}" wire:click="setActiveTab('technical')">
                            Detalhes técnicos
                        </button>
                    </div>
                </div>

                <div class="row g-2 align-items-end">
                    @if ($activeTab === 'technical')
                        <div class="col-md-auto">
                            <label class="form-label small text-muted fw-semibold">Período</label>
                            <select class="form-select" wire:model="periodDays">
                                <option value="1">Últimas 24h</option>
                                <option value="3">Últimos 3 dias</option>
                                <option value="7">Últimos 7 dias</option>
                                <option value="15">Últimos 15 dias</option>
                                <option value="30">Últimos 30 dias</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label class="form-label small text-muted fw-semibold">Logs</label>
                            <select class="form-select" wire:model="statusFilter">
                                <option value="">Todos</option>
                                <option value="error">Somente falhas</option>
                                <option value="ok">Somente sucesso</option>
                            </select>
                        </div>
                    @endif
                    @can('superadm')
                        <div class="col-md-auto d-grid">
                            <button type="button" class="btn btn-outline-primary" wire:click="runCollectNow" wire:loading.attr="disabled">
                                <i class="ri-refresh-line"></i> Verificar agora
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        @if ($activeTab === 'executive')
            @php
                $totalProblems = $pipelineSummary['failed'] + $pipelineSummary['missing'] + $pipelineSummary['usr_late'];
                $isHealthy = $totalProblems === 0 && $pipelineSummary['success'] > 0;
                $isCritical = $totalProblems >= 3;
                $bannerClass = $isHealthy ? 'is-healthy' : ($isCritical ? 'is-critical' : 'is-warning');
            @endphp

            {{-- ══════ 1. BANNER DE SAÚDE ══════ --}}
            <div class="exec-health-banner {{ $bannerClass }} mb-4">
                <div class="exec-health-icon">
                    @if ($isHealthy)
                        <i class="ri-check-line"></i>
                    @elseif ($isCritical)
                        <i class="ri-error-warning-line"></i>
                    @else
                        <i class="ri-alert-line"></i>
                    @endif
                </div>
                <div>
                    @if ($pipelineSummary['due'] === 0)
                        <div class="h4 fw-bold mb-1" style="color: #334155;">Nenhuma atualização prevista ainda</div>
                        <div style="color: #475569;">As atualizações programadas para hoje ainda não chegaram no horário. Aguarde.</div>
                    @elseif ($isHealthy)
                        <div class="h4 fw-bold mb-1" style="color: #166534;">Tudo funcionando!</div>
                        <div style="color: #15803d;">Todas as {{ $pipelineSummary['success'] }} atualizações previstas até agora rodaram com sucesso.</div>
                    @elseif ($isCritical)
                        <div class="h4 fw-bold mb-1" style="color: #991b1b;">{{ $totalProblems }} {{ $totalProblems === 1 ? 'problema encontrado' : 'problemas encontrados' }}</div>
                        <div style="color: #b91c1c;">Existem atualizações que falharam ou estão atrasadas. Veja os detalhes abaixo.</div>
                    @else
                        <div class="h4 fw-bold mb-1" style="color: #854d0e;">Atenção: {{ $totalProblems }} {{ $totalProblems === 1 ? 'ponto' : 'pontos' }} para verificar</div>
                        <div style="color: #92400e;">Algumas atualizações precisam de atenção. Veja os detalhes abaixo.</div>
                    @endif
                </div>
            </div>

            {{-- ══════ 2. RESUMO EM 3 CARDS ══════ --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="exec-summary-card card-ok">
                        <div class="exec-card-icon"><i class="ri-check-line"></i></div>
                        <div class="exec-card-number">{{ $pipelineSummary['success'] }}</div>
                        <div class="exec-card-label text-muted">{{ $pipelineSummary['success'] === 1 ? 'Rodou com sucesso' : 'Rodaram com sucesso' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="exec-summary-card card-problem">
                        <div class="exec-card-icon"><i class="ri-close-line"></i></div>
                        <div class="exec-card-number">{{ $totalProblems }}</div>
                        <div class="exec-card-label text-muted">{{ $totalProblems === 1 ? 'Com problema' : 'Com problemas' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="exec-summary-card card-wait">
                        <div class="exec-card-icon"><i class="ri-time-line"></i></div>
                        <div class="exec-card-number">{{ $pipelineSummary['pending'] }}</div>
                        <div class="exec-card-label text-muted">Ainda {{ $pipelineSummary['pending'] === 1 ? 'vai rodar' : 'vão rodar' }} hoje</div>
                    </div>
                </div>
            </div>

            {{-- ══════ 3. ALERTAS DE PROBLEMAS ══════ --}}
            @php
                $problemRuns = collect($expectedRuns)->filter(fn ($r) => in_array($r['status'], ['failed', 'missing']) || $r['usr_status'] === 'late');
            @endphp
            @if ($problemRuns->isNotEmpty())
                <div class="mb-4">
                    <div class="exec-section-title" style="color: #dc2626;">
                        <i class="ri-error-warning-fill"></i> O que precisa de atenção
                    </div>
                    <div class="exec-section-desc">Estes itens falharam, não rodaram ou estão com dados desatualizados.</div>

                    <div class="row g-3">
                        @foreach ($problemRuns as $run)
                            @php
                                $alertClass = match (true) {
                                    $run['status'] === 'failed'  => 'exec-alert-failed',
                                    $run['status'] === 'missing' => 'exec-alert-missing',
                                    default                      => 'exec-alert-late',
                                };
                                $alertIcon = match (true) {
                                    $run['status'] === 'failed'  => 'ri-close-circle-fill',
                                    $run['status'] === 'missing' => 'ri-question-fill',
                                    default                      => 'ri-time-fill',
                                };
                                $alertMessage = match (true) {
                                    $run['status'] === 'failed'  => 'A atualização rodou mas deu erro.',
                                    $run['status'] === 'missing' => 'A atualização não rodou no horário previsto.',
                                    default                      => 'A atualização rodou, mas os dados ainda estão desatualizados.',
                                };
                            @endphp
                            <div class="col-lg-6">
                                <div class="exec-alert-card {{ $alertClass }}">
                                    <div class="exec-alert-icon">
                                        <i class="{{ $alertIcon }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ $run['label'] }}</div>
                                        <div class="small text-muted mb-1">Previsto para {{ $run['time'] }}h</div>
                                        <div class="small">{{ $alertMessage }}</div>
                                        @if ($run['log']?->error)
                                            <div class="small text-danger mt-1 text-truncate" style="max-width: 400px;" title="{{ $run['log']->error }}">
                                                <i class="ri-information-line"></i> {{ $run['log']->error }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ══════ 4. LINHA DO TEMPO DE HOJE ══════ --}}
            <div class="sqlsrv-card p-4 mb-4">
                <div class="exec-section-title">Atualizações de hoje</div>
                <div class="exec-section-desc">Cada linha mostra uma atualização programada e se ela aconteceu.</div>

                <div class="exec-legend">
                    <div class="exec-legend-item">
                        <div class="exec-legend-dot" style="background: #166534;"></div>
                        Rodou com sucesso
                    </div>
                    <div class="exec-legend-item">
                        <div class="exec-legend-dot" style="background: #dc2626;"></div>
                        Falhou
                    </div>
                    <div class="exec-legend-item">
                        <div class="exec-legend-dot" style="background: #ea580c;"></div>
                        Não rodou
                    </div>
                    <div class="exec-legend-item">
                        <div class="exec-legend-dot" style="background: #ca8a04;"></div>
                        Dados desatualizados
                    </div>
                    <div class="exec-legend-item">
                        <div class="exec-legend-dot" style="background: #cbd5e1;"></div>
                        Ainda vai rodar
                    </div>
                </div>

                <div style="max-height: 520px; overflow-y: auto;">
                    @foreach ($expectedRuns as $run)
                        @php
                            $tlClass = match (true) {
                                $run['status'] === 'failed'      => 'tl-failed',
                                $run['status'] === 'missing'     => 'tl-missing',
                                $run['usr_status'] === 'late'    => 'tl-late',
                                $run['status'] === 'pending'     => 'tl-pending',
                                default                          => 'tl-success',
                            };
                            $tlIcon = match (true) {
                                $run['status'] === 'failed'      => 'ri-close-line',
                                $run['status'] === 'missing'     => 'ri-question-line',
                                $run['usr_status'] === 'late'    => 'ri-time-line',
                                $run['status'] === 'pending'     => 'ri-more-line',
                                default                          => 'ri-check-line',
                            };
                            $tlText = match (true) {
                                $run['status'] === 'failed'      => 'Falhou',
                                $run['status'] === 'missing'     => 'Não rodou',
                                $run['usr_status'] === 'late'    => 'Dados atrasados',
                                $run['status'] === 'pending'     => 'Aguardando',
                                default                          => 'OK',
                            };
                        @endphp
                        <div class="exec-timeline-item {{ $tlClass }}">
                            <div class="tl-icon"><i class="{{ $tlIcon }}"></i></div>
                            <div class="tl-time">{{ $run['time'] }}</div>
                            <div class="tl-label">{{ $run['label'] }}</div>
                            @if ($run['last_run_at'] && $run['status'] === 'success')
                                <div class="small text-muted d-none d-md-block">rodou {{ $run['last_run_at']->format('H:i') }}</div>
                            @endif
                            <div class="tl-status-text">{{ $tlText }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ══════ 5. SITUAÇÃO DAS BASES DE DADOS ══════ --}}
            <div class="sqlsrv-card p-4 mb-4">
                <div class="exec-section-title">Bases de dados</div>
                <div class="exec-section-desc">Mostra quando cada base foi atualizada pela última vez. Bases em vermelho estão com dados antigos.</div>

                @forelse ($latestUsrMetrics as $metric)
                    @php
                        $days = $metric->last_update_at ? $metric->last_update_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
                        $isOneDayOld = $days !== null && $days >= 1;
                        $friendlyName = str_replace(['tbld_usr_', 'tbld_'], '', $metric->source_name);
                        $friendlyName = ucfirst($friendlyName);
                    @endphp
                    <div class="exec-db-item {{ $isOneDayOld ? 'exec-db-stale' : 'exec-db-ok' }}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="db-dot"></div>
                            <div>
                                <div class="fw-bold">{{ $friendlyName }}</div>
                                <div class="small text-muted">
                                    @if ($metric->last_update_at)
                                        Atualizada em {{ $metric->last_update_at->format('d/m/Y \à\s H:i') }}
                                    @else
                                        Sem data de atualização
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            @if ($isOneDayOld)
                                <span class="badge bg-danger" style="font-size: .85rem;">{{ $days }} {{ $days === 1 ? 'dia' : 'dias' }} sem atualizar</span>
                            @elseif ($metric->last_update_at)
                                <span class="badge bg-success" style="font-size: .85rem;">Atualizada hoje</span>
                            @else
                                <span class="badge bg-secondary" style="font-size: .85rem;">Sem informação</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Nenhuma base de dados capturada ainda.</div>
                @endforelse
            </div>

            <div id="sqlsrv-process-chart-data"
                data-process='@json($processChartPayload)'></div>

            {{-- ══════ 6. GRÁFICO RESUMO (compacto) ══════ --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h6 fw-bold mb-3">Resultado das atualizações de hoje</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvProcessStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="sqlsrv-card p-4 h-100">
                        <h2 class="h6 fw-bold mb-3">Situação das bases de dados</h2>
                        <div class="sqlsrv-chart" wire:ignore>
                            <canvas id="sqlsrvProcessUsrChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'technical')
            @php
                $successRateColor = $kpis['success_rate'] >= 90 ? '#166534' : ($kpis['success_rate'] >= 70 ? '#854d0e' : '#dc2626');
                $successRateBg = $kpis['success_rate'] >= 90 ? '#dcfce7' : ($kpis['success_rate'] >= 70 ? '#fef9c3' : '#fee2e2');
            @endphp
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="tech-kpi">
                        <div class="tech-kpi-icon" style="background: {{ $successRateBg }}; color: {{ $successRateColor }};">
                            <i class="ri-pie-chart-line"></i>
                        </div>
                        <div>
                            <div class="tech-kpi-label">Taxa de sucesso</div>
                            <div class="tech-kpi-value" style="color: {{ $successRateColor }};">{{ number_format($kpis['success_rate'], 1, ',', '.') }}%</div>
                            <div class="tech-kpi-sub">{{ $kpis['snapshots'] }} coletas no período</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="tech-kpi">
                        <div class="tech-kpi-icon" style="background: #e0f2fe; color: #0369a1;">
                            <i class="ri-file-list-3-line"></i>
                        </div>
                        <div>
                            <div class="tech-kpi-label">Logs hoje</div>
                            <div class="tech-kpi-value" style="color: #0f172a;">{{ $kpis['logs_today'] }}</div>
                            <div class="tech-kpi-sub">
                                @if ($kpis['errors_today'] > 0)
                                    <span style="color: #dc2626; font-weight: 700;">{{ $kpis['errors_today'] }} com falha</span>
                                @else
                                    nenhuma falha
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="tech-kpi">
                        <div class="tech-kpi-icon" style="background: {{ $kpis['stale_sources'] > 0 ? '#fee2e2' : '#dcfce7' }}; color: {{ $kpis['stale_sources'] > 0 ? '#dc2626' : '#166534' }};">
                            <i class="ri-database-2-line"></i>
                        </div>
                        <div>
                            <div class="tech-kpi-label">Bases atrasadas</div>
                            <div class="tech-kpi-value" style="color: {{ $kpis['stale_sources'] > 0 ? '#dc2626' : '#166534' }};">{{ $kpis['stale_sources'] }}</div>
                            <div class="tech-kpi-sub">> 12h sem atualizar</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="tech-kpi">
                        <div class="tech-kpi-icon" style="background: #f0fdf4; color: #0f766e;">
                            <i class="ri-computer-line"></i>
                        </div>
                        <div>
                            <div class="tech-kpi-label">Hosts ativos</div>
                            <div class="tech-kpi-value" style="color: #0f172a;">{{ $hostSummary->count() }}</div>
                            <div class="tech-kpi-sub">
                                @if ($hostSummary->sum('failures') > 0)
                                    <span style="color: #dc2626; font-weight: 700;">{{ $hostSummary->sum('failures') }} falha(s)</span>
                                @else
                                    sem falhas
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sqlsrv-health-chart-data"
                data-active="{{ $activeTab }}"
                data-main='@json($chartPayload)'
                data-source='@json($sourceChartPayload)'></div>

            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="tech-chart-card h-100">
                        <div class="tech-chart-header">
                            <i class="ri-line-chart-line"></i>
                            <h2 class="h6 fw-bold mb-0">Coletas x falhas no período</h2>
                        </div>
                        <div class="tech-chart-body">
                            <div class="sqlsrv-chart" wire:ignore>
                                <canvas id="sqlsrvTimelineChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="tech-chart-card h-100">
                        <div class="tech-chart-header">
                            <i class="ri-bar-chart-horizontal-line"></i>
                            <h2 class="h6 fw-bold mb-0">Horas sem atualizar por base</h2>
                        </div>
                        <div class="tech-chart-body">
                            <div class="sqlsrv-chart" wire:ignore>
                                <canvas id="sqlsrvSourceAgeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tech-log-card">
                <div class="tech-log-header">
                    <i class="ri-terminal-box-line" style="font-size: 18px;"></i>
                    <h2 class="h6 fw-bold">Logs recentes</h2>
                </div>
                <div class="tech-log-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="padding: 10px 14px; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em;">Job</th>
                                    <th style="padding: 10px 14px; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em;">Status</th>
                                    <th style="padding: 10px 14px; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em;">Execução</th>
                                    <th style="padding: 10px 14px; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em;">Máquina</th>
                                    <th style="padding: 10px 14px; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em;">Erro</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLogs as $log)
                                    <tr class="{{ $log->has_error ? 'tech-log-row-error' : 'tech-log-row-ok' }}">
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                            <div class="fw-semibold">{{ $log->file_name ?: '-' }}</div>
                                            <div class="small text-muted">{{ $log->file_folder ?: '' }}</div>
                                        </td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                            <span class="badge {{ $log->has_error ? 'bg-danger' : 'bg-success' }}" style="font-size: .78rem; padding: 5px 10px;">
                                                <i class="{{ $log->has_error ? 'ri-close-circle-line' : 'ri-checkbox-circle-line' }}"></i>
                                                {{ $log->status ?: 'sem status' }}
                                            </span>
                                        </td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">{{ $log->dt_run ? $log->dt_run->format('d/m/Y H:i') : '-' }}</td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                            <span class="badge bg-light text-dark" style="font-size: .78rem;">{{ $log->host ?: '-' }}</span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 320px; padding: 10px 14px; border-bottom: 1px solid #f1f5f9;" title="{{ $log->error }}">
                                            @if ($log->error)
                                                <span class="small text-danger">{{ $log->error }}</span>
                                            @else
                                                <span class="small text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted text-center" style="padding: 32px;">Nenhum log capturado ainda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tech-log-footer">
                    {{ $recentLogs->links() }}
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:load', () => {
            if (window.sqlsrvHealthChartsBound) {
                return;
            }

            window.sqlsrvHealthChartsBound = true;
            window.sqlsrvHealthCharts = window.sqlsrvHealthCharts || {};
            window.sqlsrvHealthChartsSignature = null;
            window.sqlsrvProcessChartsSignature = null;

            const buildChart = (id, config) => {
                const canvas = document.getElementById(id);
                if (!canvas || typeof Chart === 'undefined') return false;

                if (window.sqlsrvHealthCharts[id]) {
                    window.sqlsrvHealthCharts[id].destroy();
                }

                window.sqlsrvHealthCharts[id] = new Chart(canvas.getContext('2d'), config);
                return true;
            };

            const renderSqlsrvHealthCharts = () => {
                const source = document.getElementById('sqlsrv-health-chart-data');
                if (!source) return;

                const mainRaw = source.dataset.main || '{}';
                const sourceRaw = source.dataset.source || '{}';
                const signature = `${source.dataset.active || ''}|${mainRaw}|${sourceRaw}`;

                if (window.sqlsrvHealthChartsSignature === signature) {
                    return;
                }

                const main = JSON.parse(mainRaw);
                const sourceRows = JSON.parse(sourceRaw);

                buildChart('sqlsrvTimelineChart', {
                    type: 'line',
                    data: {
                        labels: main.timeline?.labels || [],
                        datasets: [{
                            label: 'Logs capturados',
                            data: main.timeline?.logs || [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,.12)',
                            tension: .25,
                            fill: true,
                        }, {
                            label: 'Falhas',
                            data: main.timeline?.errors || [],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,.12)',
                            tension: .25,
                            fill: true,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, animation: false },
                });

                buildChart('sqlsrvSourceAgeChart', {
                    type: 'bar',
                    data: {
                        labels: main.sourceAge?.labels || [],
                        datasets: [{
                            label: 'Horas desde a última atualização',
                            data: main.sourceAge?.hours || [],
                            backgroundColor: 'rgba(15,118,110,.8)',
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: { x: { beginAtZero: true } },
                    },
                });

                window.sqlsrvHealthChartsSignature = signature;
            };

            const renderSqlsrvProcessCharts = () => {
                const source = document.getElementById('sqlsrv-process-chart-data');
                if (!source) return;

                const raw = source.dataset.process || '{}';

                if (window.sqlsrvProcessChartsSignature === raw) {
                    return;
                }

                const process = JSON.parse(raw);

                buildChart('sqlsrvProcessStatusChart', {
                    type: 'doughnut',
                    data: {
                        labels: ['Rodaram', 'Falharam', 'Não rodaram', 'Aguardando'],
                        datasets: [{
                            data: process.status?.data || [],
                            backgroundColor: ['#16a34a', '#dc2626', '#ea580c', '#94a3b8'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });

                buildChart('sqlsrvProcessUsrChart', {
                    type: 'doughnut',
                    data: {
                        labels: ['Atualizadas', 'Atrasadas', 'Sem informação', 'Aguardando'],
                        datasets: [{
                            data: process.usr?.data || [],
                            backgroundColor: ['#0f766e', '#dc2626', '#f59e0b', '#94a3b8'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });

                window.sqlsrvProcessChartsSignature = raw;
            };

            const renderAllSqlsrvCharts = () => {
                renderSqlsrvHealthCharts();
                renderSqlsrvProcessCharts();
            };

            renderAllSqlsrvCharts();
            Livewire.hook('message.processed', () => renderAllSqlsrvCharts());
        });
    </script>
</div>
