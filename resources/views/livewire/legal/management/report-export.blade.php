<div>
    <h4 class="fw-bold mb-4" style="color: var(--legal-primary)">
        <i class="bi bi-file-earmark-bar-graph me-2"></i>Relatórios Jurídicos
    </h4>

    @php
        $reports = [
            ['key' => 'position',    'icon' => 'bi-graph-up',          'title' => 'Posição Geral',           'desc' => 'Estado atual de todo o portfólio jurídico. Inclui KPIs, listagem completa e distribuição por status.'],
            ['key' => 'criticality', 'icon' => 'bi-exclamation-triangle','title' => 'Criticidade de Vencimentos','desc' => 'Processos que precisam de ação urgente: vencidos, próximos do prazo e sem prazo.'],
            ['key' => 'by_assignee', 'icon' => 'bi-people-fill',        'title' => 'Por Executante',          'desc' => 'Carga e performance de cada executante: ativas, respondidas, tempo médio e qualidade.'],
            ['key' => 'by_area',     'icon' => 'bi-building',           'title' => 'Por Área / Regional',     'desc' => 'Concentração de riscos por área e regional. Ranking por risco.'],
            ['key' => 'case_full',   'icon' => 'bi-folder-fill',        'title' => 'Completo de Caso',        'desc' => 'Histórico completo de um caso específico: demandas, atribuições, respostas e timeline.'],
            ['key' => 'monthly',     'icon' => 'bi-calendar-range',     'title' => 'Evolução Mensal',         'desc' => 'Evolução mês a mês: novas, encerradas, vencidas e SLA.'],
        ];
    @endphp

    {{-- Cards de Relatórios --}}
    <div class="row g-4 mb-5">
        @foreach($reports as $report)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="{{ $report['icon'] }} fs-3" style="color: var(--legal-primary)"></i>
                            <h6 class="mb-0 fw-semibold">{{ $report['title'] }}</h6>
                        </div>
                        <p class="small text-muted flex-grow-1">{{ $report['desc'] }}</p>
                        <button class="btn btn-outline-primary btn-sm w-100 mt-2"
                                wire:click="openConfig('{{ $report['key'] }}')">
                            <i class="bi bi-gear me-1"></i>Configurar e Gerar
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal de Configuração --}}
    <div class="modal fade {{ $showConfigModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showConfigModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--legal-primary); color:white">
                    <h5 class="modal-title">
                        <i class="bi bi-gear me-2"></i>Configurar Relatório
                        @switch($activeReport)
                            @case('position')    — Posição Geral @break
                            @case('criticality') — Criticidade de Vencimentos @break
                            @case('by_assignee') — Por Executante @break
                            @case('by_area')     — Por Área / Regional @break
                            @case('case_full')   — Completo de Caso @break
                            @case('monthly')     — Evolução Mensal @break
                        @endswitch
                    </h5>
                    <button class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Campos comuns --}}
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
                                        <button class="dropdown-item small py-2" wire:click="selectCase({{ $opt['id'] }}, '{{ addslashes($opt['label']) }}')">
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

                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        O relatório será gerado em segundo plano. Você receberá uma notificação quando estiver pronto para download.
                    </div>
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

    <x-show-loading />
</div>
