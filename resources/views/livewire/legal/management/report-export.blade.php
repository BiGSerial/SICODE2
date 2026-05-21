<div class="lre-page">
    <x-show-loading />

    <style>
        .lre-page {
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        #f6f7fb;
            padding: 1.5rem 0;
        }
        .lre-header {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .22);
            margin-bottom: 1.5rem;
        }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15,23,42,.08);
            overflow: hidden;
        }
        .report-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 4px 12px rgba(15,23,42,.06);
            padding: 1.25rem;
            height: 100%;
            transition: box-shadow .15s;
        }
        .report-card:hover { box-shadow: 0 8px 24px rgba(15,23,42,.12); }
        .report-card .report-icon { font-size: 2rem; color: #1e3a5f; opacity: .8; }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="lre-header">
            <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
            <div class="opacity-75" style="font-size:.9rem">
                Relatórios — Selecione o relatório desejado e configure os parâmetros antes de gerar.
            </div>
        </div>

        {{-- Cards de Relatórios --}}
        <div class="table-card mb-4">
            <div class="card-header text-bg-dark fw-bold">
                Jurídico › Relatórios Disponíveis
            </div>
            <div class="card-body">
                @php
                    $reports = [
                        ['key' => 'position',    'icon' => 'bi-graph-up',            'title' => 'Posição Geral',              'desc' => 'Estado atual de todo o portfólio jurídico. Inclui KPIs, listagem completa e distribuição por status.'],
                        ['key' => 'criticality', 'icon' => 'bi-exclamation-triangle', 'title' => 'Criticidade de Vencimentos', 'desc' => 'Processos que precisam de ação urgente: vencidos, próximos do prazo e sem prazo.'],
                        ['key' => 'by_assignee', 'icon' => 'bi-people-fill',          'title' => 'Por Executante',             'desc' => 'Carga e performance de cada executante: ativas, respondidas, tempo médio e qualidade.'],
                        ['key' => 'by_area',     'icon' => 'bi-building',             'title' => 'Por Área / Regional',        'desc' => 'Concentração de riscos por área e regional. Ranking por risco.'],
                        ['key' => 'case_full',   'icon' => 'bi-folder-fill',          'title' => 'Completo de Caso',           'desc' => 'Histórico completo de um caso: demandas, atribuições, respostas e timeline.'],
                        ['key' => 'monthly',     'icon' => 'bi-calendar-range',       'title' => 'Evolução Mensal',            'desc' => 'Evolução mês a mês: novas, encerradas, vencidas e SLA.'],
                    ];
                @endphp

                <div class="row g-3">
                    @foreach($reports as $report)
                        <div class="col-md-6 col-lg-4">
                            <div class="report-card">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="{{ $report['icon'] }} report-icon"></i>
                                    <h6 class="mb-0 fw-semibold">{{ $report['title'] }}</h6>
                                </div>
                                <p class="small text-muted mb-3">{{ $report['desc'] }}</p>
                                <button class="btn btn-outline-primary btn-sm w-100"
                                        wire:click="openConfig('{{ $report['key'] }}')">
                                    <i class="bi bi-gear me-1"></i>Configurar e Gerar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="alert alert-light border small">
            <i class="bi bi-info-circle me-1"></i>
            Os relatórios são gerados em segundo plano. Você receberá uma notificação quando estiverem prontos para download.
        </div>

    </div>

    {{-- Modal de Configuração --}}
    <div class="modal fade {{ $showConfigModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showConfigModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-gear me-2"></i>Configurar Relatório —
                        @switch($activeReport)
                            @case('position')    Posição Geral @break
                            @case('criticality') Criticidade de Vencimentos @break
                            @case('by_assignee') Por Executante @break
                            @case('by_area')     Por Área / Regional @break
                            @case('case_full')   Completo de Caso @break
                            @case('monthly')     Evolução Mensal @break
                        @endswitch
                    </h5>
                    <button class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Período --}}
                    @if(!in_array($activeReport, ['case_full']))
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Período de *</label>
                                <input type="date" class="form-control" wire:model="reportPeriodFrom" />
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Período até *</label>
                                <input type="date" class="form-control" wire:model="reportPeriodTo" />
                            </div>
                        </div>
                    @endif

                    {{-- Filtros gerais --}}
                    @if(!in_array($activeReport, ['case_full', 'monthly']))
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Tipo de Fonte</label>
                                <select class="form-select" wire:model="reportSourceType">
                                    <option value="">Todos</option>
                                    <option value="injunction">Liminar</option>
                                    <option value="sentence">Sentença</option>
                                    <option value="subsidy">Subsídio</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Área</label>
                                <input type="text" class="form-control" wire:model="reportArea" placeholder="Filtrar por área..." />
                            </div>
                        </div>
                    @endif

                    {{-- Criticidade --}}
                    @if($activeReport === 'criticality')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Janela de criticidade</label>
                            <select class="form-select" wire:model="criticalityWindow">
                                <option value="overdue">Vencidas</option>
                                <option value="3d">≤ 3 dias</option>
                                <option value="7d">≤ 7 dias</option>
                                <option value="15d">≤ 15 dias</option>
                            </select>
                        </div>
                    @endif

                    {{-- Caso Completo --}}
                    @if($activeReport === 'case_full')
                        <div class="mb-3 position-relative">
                            <label class="form-label small fw-semibold">Buscar Caso *</label>
                            <input type="text" class="form-control" wire:model.debounce.400ms="caseSearch"
                                   placeholder="Número do processo ou empresa..." />
                            @if(!empty($caseOptions))
                                <div class="position-absolute w-100 bg-white border rounded shadow-sm mt-1" style="z-index:1000">
                                    @foreach($caseOptions as $opt)
                                        <button class="dropdown-item small py-2"
                                                wire:click="selectCase({{ $opt['id'] }}, '{{ addslashes($opt['label']) }}')">
                                            {{ $opt['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Mensal --}}
                    @if($activeReport === 'monthly')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Intervalo</label>
                            <select class="form-select" wire:model="monthlyRange">
                                <option value="3">Últimos 3 meses</option>
                                <option value="6">Últimos 6 meses</option>
                                <option value="12">Últimos 12 meses</option>
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="generate">
                        <i class="bi bi-play-fill me-1"></i>Gerar Relatório
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
