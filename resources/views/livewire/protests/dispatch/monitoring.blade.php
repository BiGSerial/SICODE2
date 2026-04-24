@push('css')
    <style>
        .monitoring-page {
            --mp-bg: #f6f7fb;
            --mp-surface: #ffffff;
            --mp-ink: #1f2933;
            --mp-muted: #6b7280;
            --mp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--mp-bg);
            padding: 1.5rem 0;
        }

        .monitoring-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .monitoring-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .monitoring-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .form-floating > .form-select[multiple] {
            height: 8.5rem !important;
            padding-top: 1.9rem;
            padding-bottom: 0.6rem;
        }

        .summary-bar {
            background: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .table-card {
            background: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .card-header {
            padding: 0.65rem 1rem;
            border-bottom: 0;
        }

        .table-card .card-header .card-title {
            padding-left: 0.1rem;
        }

        .histogram-card-body {
            display: flex;
            flex-direction: column;
        }

        .histogram-chart-wrap {
            position: relative;
            width: 100%;
            height: clamp(260px, 34vh, 420px);
            max-height: 420px;
            overflow: hidden;
        }

        .histogram-chart-wrap canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .highlightable-row {
            transition: background-color .2s, box-shadow .2s;
            cursor: pointer;
        }

        .highlightable-row:hover {
            background-color: rgba(15, 118, 110, 0.10) !important;
            box-shadow: inset 0 0 0 999px rgba(15, 118, 110, 0.10);
        }

        .highlightable-row:hover td {
            background-color: transparent !important;
        }

        .highlightable-row td {
            vertical-align: middle;
        }

        .highlightable-row.highlight-active {
            background-color: rgba(59, 130, 246, 0.08) !important;
            box-shadow: inset 0 0 0 999px rgba(59, 130, 246, 0.08);
        }

        .highlightable-row.highlight-active td {
            background-color: transparent !important;
        }

        .status-summary-card {
            border: none;
            border-radius: 16px;
            padding: 1rem 1.2rem;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all .2s ease;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            cursor: pointer;
            position: relative;
            background: #fff;
        }

        .status-summary-card .status-summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.25);
        }

        .status-summary-card .status-summary-label {
            font-size: .9rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
            display: block;
        }

        .status-summary-card .status-summary-value {
            font-size: 1.9rem;
            font-weight: 700;
            line-height: 1;
            display: block;
        }

        .status-summary-card small {
            font-size: .78rem;
            opacity: .8;
        }

        .status-summary-card.is-active {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18);
        }

        .status-summary-card--warning {
            background: linear-gradient(135deg, #fff7e6, #ffe3b3);
            color: #7a4d00;
        }

        .status-summary-card--danger {
            background: linear-gradient(135deg, #ffe4e6, #ffb3c0);
            color: #7c1d2c;
        }

        .status-summary-card--success {
            background: linear-gradient(135deg, #e1f6ea, #a7e3c6);
            color: #0f5132;
        }

        .status-summary-card--warning .status-summary-icon {
            color: #a35d00;
            background: rgba(255, 255, 255, 0.6);
        }

        .status-summary-card--danger .status-summary-icon {
            color: #b4233b;
            background: rgba(255, 255, 255, 0.6);
        }

        .status-summary-card--success .status-summary-icon {
            color: #198754;
            background: rgba(255, 255, 255, 0.6);
        }
    </style>
@endpush

@php
    if (!function_exists('reduceName')) {
        function reduceName($name, bool $first = false): string
        {
            $normalized = trim((string) ($name ?? ''));
            if ($normalized === '') {
                return '';
            }

            $name = preg_split('/\s+/', $normalized) ?: [];

            if ($first) {
                return $name[0] ?? '';
            }

            $firstName = $name[0] ?? '';
            $lastName = count($name) > 1 ? end($name) : '';

            return trim($firstName . ' ' . $lastName);
        }
    }

    if (!function_exists('getWishDate')) {
        function getWishDate($item)
        {
            if ($item->protest?->tipoNota === 'NA') {
                return $item->protest?->dtConclusaoDesej;
            }

            return $item->medProtest?->dtFimMedidaDesej;
        }
    }

    if (!function_exists('getApertureDate')) {
        function getApertureDate($item)
        {
            if ($item->protest?->tipoNota === 'NA') {
                return $item->protest?->dtAberturaNota;
            }

            return $item->medProtest?->dtCriacaoMedida;
        }
    }
@endphp

<div class="monitoring-page">
    <div class="container-fluid">
    {{-- Loading --}}
    <x-show-loading />

    <div class="monitoring-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <h2>MONITORAMENTO RECLAMAÇÕES</h2>
            <div class="meta">Fila de despacho, prazos e acompanhamento de SLA</div>
        </div>
        <div class="text-lg-end">
            <div class="meta">Base em andamento (menu)</div>
            <div>
                <strong>{{ $coreTotal ?? 0 }}</strong>
                <span class="badge bg-success ms-1">{{ $coreDonePending ?? 0 }} done pendente</span>
            </div>
            <div class="meta mt-1">Após filtros variáveis: <strong>{{ $lists->total() ?? 0 }}</strong></div>
        </div>
    </div>

    <div class="alert alert-secondary border mb-3 py-2">
        <div class="small fw-semibold mb-1">Filtros fixos (core da página)</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach (($fixedFilters ?? []) as $fixedFilter)
                <span class="badge text-bg-light border">{{ $fixedFilter }}</span>
            @endforeach
        </div>
    </div>

    {{-- ================== TOP: BUSCA E FILTROS ================== --}}
    <div class="card mb-3 border-0 bg-transparent filters-grid">
        <div class="card-body px-0">
            <div class="filter-card">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="perPageSelect">Registros por página</label>
                    </div>
                </div>

                <div class="col-12 col-md-7 col-lg-6">
                    <div class="form-floating position-relative">
                        <input wire:model.debounce.500ms="search" class="form-control border border-secondary"
                            id="searchInput" placeholder="Buscar por nota, cidade, responsável..." />
                        <label for="searchInput">Buscar por nota, cidade ou responsável</label>

                        <button type="button"
                            class="btn btn-outline-secondary position-absolute top-50 translate-middle-y me-2 border-0"
                            style="right: .35rem;" data-bs-toggle="modal" data-bs-target="#buscarMultiModal"
                            title="Busca múltipla">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterTypeNote" wire:model="typeNote" multiple size="4">
                            @forelse ($noteTypeOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @empty
                                <option value="" disabled>Nenhum tipo disponível</option>
                            @endforelse
                        </select>
                        <label for="filterTypeNote">Tipo de nota</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterProtestType"
                            wire:model="protestType" multiple size="4">
                            @forelse ($protestTypeOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @empty
                                <option value="" disabled>Nenhum tipo disponível</option>
                            @endforelse
                        </select>
                        <label for="filterProtestType">Tipo de Reclamação</label>
                    </div>
                </div>

            </div>

            <div class="row g-3 align-items-end mt-0 mt-md-3">
                <div class="col-12 col-lg-5">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterUser"
                            wire:model="userViewer" multiple size="4">
                            @forelse ($userViewerList as $user)
                                <option value="{{ $user->id }}">
                                    {{ reduceName($user->name) }}
                                </option>
                            @empty
                                <option value="" disabled>Nenhum usuário encontrado</option>
                            @endforelse
                        </select>
                        <label for="filterUser">Responsável / hierarquia</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="form-floating">
                        <input type="text" class="form-control border border-secondary" id="searchName"
                            wire:model.debounce.300ms="searchName" placeholder="Filtrar lista de usuários">
                        <label for="searchName">Filtrar responsáveis</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="border rounded py-3 px-3 h-100">
                        <div class="form-check mb-1">
                            <input type="checkbox" class="form-check-input" id="onlySelectedUser"
                                wire:model.defer="onlySelectedUser" {{ empty($userViewer) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="onlySelectedUser">
                                Apenas usuário selecionado
                            </label>
                        </div>
                        <small class="text-muted">Ignora descendentes na consulta.</small>
                    </div>
                </div>
            </div>

            <div class="row g-3 align-items-end mt-0 mt-md-3">
                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterJobStatus" wire:model="jobStatusFilter" multiple size="4">
                            @foreach ($jobStatusOptions as $statusOption)
                                <option value="{{ $statusOption['value'] }}">{{ $statusOption['label'] }}</option>
                            @endforeach
                        </select>
                        <label for="filterJobStatus">Status do job</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterPriority" wire:model="priorityFilter" multiple size="4">
                            @foreach ($priorityOptions as $priorityOption)
                                <option value="{{ $priorityOption['value'] }}">{{ $priorityOption['label'] }}</option>
                            @endforeach
                        </select>
                        <label for="filterPriority">Prioridade</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterSapStatus" wire:model="sapStatusFilter" multiple size="4">
                            <option value="MEDA">ABER (MEDA)</option>
                            <option value="MEDE">ENC (MEDE)</option>
                        </select>
                        <label for="filterSapStatus">Status SAP</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterOwnerScope" wire:model="ownerScope" multiple size="4">
                            <option value="assigned">Com responsável</option>
                            <option value="unassigned">Sem responsável</option>
                        </select>
                        <label for="filterOwnerScope">Escopo responsável</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterSla" wire:model="slaFilter" multiple size="4">
                            <option value="overdue">SLA vencido</option>
                            <option value="dueSoon">SLA vencendo (até 3 dias)</option>
                            <option value="within">SLA a vencer</option>
                        </select>
                        <label for="filterSla">Faixa SLA</label>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" wire:click="cleanFilters">
                        <i class="ri-eraser-line me-1"></i>
                        Limpar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="applyFilters">
                        <i class="ri-filter-3-line me-1"></i>
                        Aplicar
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body histogram-card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Histograma de Medidas em Aberto</div>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" wire:model="histogramSource" style="min-width: 170px;">
                                <option value="desired">Data desejada (MEDA)</option>
                                <option value="sla">Data SLA (não concluídos)</option>
                            </select>
                            <select class="form-select form-select-sm" wire:model="histogramStatusScope" style="min-width: 190px;">
                                <option value="meda">Status Medida: MEDA</option>
                                <option value="mede">Status Medida: MEDE</option>
                                <option value="both">Status Medida: MEDA + MEDE</option>
                            </select>
                            @if (!empty($histogramData['selectedBucket']))
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearHistogramFilter">
                                    Limpar mês
                                </button>
                            @endif
                        </div>
                    </div>
                    <div id="monitoring-histogram-data" data-payload='@json($histogramData)'></div>
                    <div class="histogram-chart-wrap" wire:ignore>
                        <canvas id="monitoringHistogram"></canvas>
                    </div>
                    @php
                        $selectedBucket = (string) ($histogramData['selectedBucket'] ?? '');
                        $monthKeys = (array) ($histogramData['monthKeys'] ?? []);
                        $monthTotals = (array) ($histogramData['monthTotals'] ?? []);
                        $monthLabels = (array) ($histogramData['monthLabels'] ?? []);
                    @endphp
                    <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                        @foreach ($monthKeys as $monthKey)
                            @php
                                $monthTotal = (int) ($monthTotals[$monthKey] ?? 0);
                                $isActive = $selectedBucket !== '' && $selectedBucket === $monthKey;
                                $monthLabel = $monthLabels[$monthKey] ?? $monthKey;
                            @endphp
                            <button type="button" class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                                @disabled($monthTotal <= 0)
                                wire:click="setHistogramBucket('{{ $monthKey }}')">
                                {{ $monthLabel }}
                            </button>
                        @endforeach
                    </div>
                    <small class="text-muted">Clique no segmento da barra para filtrar a lista por faixa de prazo. Use os botões para isolar o mês.</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="d-flex flex-column gap-3 h-100">
                <button type="button"
                    class="status-summary-card status-summary-card--warning {{ $deadlineCardFilter === 'due_today' ? 'is-active' : '' }}"
                    wire:click="setDeadlineCardFilter('due_today')">
                    <div class="status-summary-icon">
                        <i class="ri-timer-2-line"></i>
                    </div>
                    <div class="status-summary-body">
                        <span class="status-summary-label">Vencendo hoje</span>
                        <span class="status-summary-value">{{ $deadlineSummary['due_today'] ?? 0 }}</span>
                        <small>Apenas medidas em aberto (MEDA)</small>
                    </div>
                </button>
                <button type="button"
                    class="status-summary-card status-summary-card--danger {{ $deadlineCardFilter === 'overdue' ? 'is-active' : '' }}"
                    wire:click="setDeadlineCardFilter('overdue')">
                    <div class="status-summary-icon">
                        <i class="ri-error-warning-line"></i>
                    </div>
                    <div class="status-summary-body">
                        <span class="status-summary-label">Vencidos</span>
                        <span class="status-summary-value">{{ $deadlineSummary['overdue'] ?? 0 }}</span>
                        <small>Apenas medidas em aberto (MEDA)</small>
                    </div>
                </button>
                <button type="button"
                    class="status-summary-card status-summary-card--success {{ $deadlineCardFilter === 'finished_pending' ? 'is-active' : '' }}"
                    wire:click="setDeadlineCardFilter('finished_pending')">
                    <div class="status-summary-icon">
                        <i class="ri-check-double-line"></i>
                    </div>
                    <div class="status-summary-body">
                        <span class="status-summary-label">Finalizados pendentes</span>
                        <span class="status-summary-value">{{ $deadlineSummary['finished_pending'] ?? 0 }}</span>
                        <small>Exceção: finalizados sem confirmação (todos os status)</small>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- ================== LISTA PRINCIPAL ================== --}}
    @if ($lists->count())
        @if (!empty($variableFilters))
            <div class="alert alert-warning border mb-2 py-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small fw-semibold mb-1">Filtros variáveis ativos (estão reduzindo a lista)</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($variableFilters as $filter)
                                <span class="badge text-bg-warning border border-dark-subtle">
                                    {{ $filter['source'] }}: {{ $filter['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark" wire:click="cleanFilters">
                        Limpar filtros variáveis
                    </button>
                </div>
            </div>
        @else
            <div class="alert alert-success border mb-2 py-2">
                <span class="small fw-semibold">Sem filtros variáveis ativos. A lista está sem restrição adicional.</span>
            </div>
        @endif

        {{-- PaginaÃ§Ã£o topo --}}
        <div class="summary-bar mb-2">
            <div class="d-flex justify-content-between align-items-center">
                {{ $lists->links() }}
                <div class="text-muted small">
                    Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                    {{ $lists->total() }} registros
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header text-bg-danger d-flex justify-content-between align-items-center">
                <h5 class="card-title my-0">RECLAMAÇÕES EM ANDAMENTO</h5>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" title="Exportar para Excel"
                        wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                        <span wire:loading.remove wire:target="exportToExcel">
                            <i class="ri-file-excel-line me-1"></i>
                            Exportar Excel
                        </span>
                        <span wire:loading wire:target="exportToExcel">
                            <i class="spinner-border spinner-border-sm me-1" role="status"></i>
                            Exportando...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Tabela --}}
            <table class="table table-sm table-striped table-condensed mb-0">
                <thead class="table-dark">
                    <tr class="align-middle text-center sticky-top" style="top: 60px;">
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('priority')">
                                Prioridade
                                @if ($sortBy === 'priority')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('dispatcher')">
                                Despachante
                                @if ($sortBy === 'dispatcher')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('tipo_nota')">
                                Tipo
                                @if ($sortBy === 'tipo_nota')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th></th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('nota')">
                                Nota
                                @if ($sortBy === 'nota')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('medida')">
                                Medida
                                @if ($sortBy === 'medida')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('cod')">
                                Cód
                                @if ($sortBy === 'cod')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('tipo_reclamacao')">
                                Tipo Reclamação
                                @if ($sortBy === 'tipo_reclamacao')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('municipio')">
                                Município
                                @if ($sortBy === 'municipio')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('responsavel')">
                                Responsável
                                @if ($sortBy === 'responsavel')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('empresa')">
                                Empresa
                                @if ($sortBy === 'empresa')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('abertura')">
                                Abertura
                                @if ($sortBy === 'abertura')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('fim_desejado')">
                                Fim desejado
                                @if ($sortBy === 'fim_desejado')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('sent_at')">
                                Despachado Em
                                @if ($sortBy === 'sent_at')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('sla_due_at')">
                                Sla Definido
                                @if ($sortBy === 'sla_due_at')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('sap_status')">
                                SAP
                                @if ($sortBy === 'sap_status')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold"
                                wire:click="sortByColumn('status')">
                                Status
                                @if ($sortBy === 'status')
                                    <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <i class="ri-message-3-line" title="Mensagens na Medida"></i>
                        </th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lists as $item)
                        @php
                            $wish = getWishDate($item);
                            $todayStart = now()->startOfDay();
                            $statusSist = mb_strtoupper((string) ($item->medProtest?->statusSist ?? ''));
                            $isMede = $statusSist === 'MEDE';
                            $isMeda = $statusSist === 'MEDA';
                            $medFinishedAt = $item->medProtest?->dtFimMedida;
                            $desiredReference = $item->protest?->tipoNota === 'NA'
                                ? $item->protest?->dtConclusaoDesej
                                : $item->medProtest?->dtFimMedidaDesej;
                            $dueLeft = $item->sla_due_at;

                            if ($wish) {
                                $wishStart = $wish->copy()->startOfDay();
                                $daysToWish = $todayStart->diffInDays($wishStart, false);

                                if ($daysToWish < 0) {
                                    $wishClass = 'text-bg-danger';
                                } elseif ($daysToWish <= 3) {
                                    $wishClass = 'text-bg-warning';
                                } else {
                                    $wishClass = 'text-bg-success';
                                }
                            } else {
                                $daysToWish = null;
                                $wishClass = 'text-bg-secondary';
                            }

                            if ($dueLeft) {
                                $dueLeftStart = $dueLeft->copy()->startOfDay();
                                $dueDaysLeft = $todayStart->diffInDays($dueLeftStart, false);

                                if ($dueDaysLeft < 0) {
                                    $dueSlaClass = 'text-bg-danger';
                                } elseif ($dueDaysLeft <= 3) {
                                    $dueSlaClass = 'text-bg-warning';
                                } else {
                                    $dueSlaClass = 'text-bg-success';
                                }
                            } else {
                                $dueDaysLeft = null;
                                $dueSlaClass = 'text-bg-secondary';
                            }

                            $slaFinishedDiff = null;
                            $slaFinishedClass = 'text-bg-secondary';
                            if ($item->finished_at && $dueLeft) {
                                $slaFinishedDiff = $dueLeft->copy()->startOfDay()->diffInDays($item->finished_at->copy()->startOfDay(), false);
                                $slaFinishedClass = $slaFinishedDiff >= 0 ? 'text-bg-success' : 'text-bg-danger';
                            }

                            $sapStatus = match ($statusSist) {
                                'MEDA' => 'ABER',
                                'MEDE' => 'ENC',
                                default => ($statusSist ?: '---'),
                            };

                            $sapStatusClass = $statusSist === 'MEDE'
                                ? 'text-bg-success'
                                : ($statusSist === 'MEDA' ? 'text-bg-warning' : 'text-bg-secondary');

                            // Mensagens (Ãºltima da MedProtest)
                            $currentUserId = auth()->id();
                            $creatorId = $item->created_by ?? ($item->creator_id ?? optional($item->creator)->id);
                            $lastComment = $item->medProtest?->Comments?->first();

                            $hasMessage = false;
                            $pendingForYou = false;

                            if ($creatorId && $lastComment) {
                                $authorId = $lastComment->user_id;

                                if ($authorId) {
                                    $isFromDispatcher = $authorId === $creatorId;
                                    $isFromCurrentUser = $currentUserId && $authorId === $currentUserId;

                                    $hasMessage = !$isFromDispatcher;
                                    $pendingForYou = $hasMessage && !$isFromCurrentUser;
                                }
                            }
                        @endphp

                        <tr class="text-center align-middle highlightable-row" data-row-id="{{ $item->id }}">
                            <td>
                                <span class="badge {{ $item->priority_badge_class }}">
                                    {{ $item->priority_label }}
                                </span>
                            </td>

                            <td class="fw-bold">
                                {{ reduceName($item->creator?->name) }}
                            </td>

                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $item->protest?->tipoNota }}
                                </span>
                            </td>

                            <td>
                                @if ($item->is_advance)
                                    <span class="badge text-bg-info">A</span>
                                @endif
                            </td>

                            <td class="fw-bold">
                                {{ $item->protest?->nota }}
                            </td>

                            <td class="fw-bold">
                                # {{ $item->medProtest?->med_id }}
                            </td>

                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $item->protest?->codecodf }}
                                </span>
                            </td>

                            <td class="text-uppercase">
                                {{ $item->protest?->txtGrpCodificacao }}
                            </td>

                            <td>
                                {{ $item->protest?->cidade }}
                            </td>

                            <td class="text-uppercase fw-bold">
                                {{ reduceName($item->owner?->name) }}
                            </td>

                            <td class="text-uppercase">
                                {{ reduceName($item->owner?->company?->name, true) }}
                            </td>

                            <td>
                                @php $aperture = getApertureDate($item); @endphp
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>{{ $aperture ? $aperture->format('d/m/Y') : '---' }}</span>
                                    <span class="badge text-bg-secondary" title="Dias Aberto">
                                        {{ $aperture?->copy()?->startOfDay()?->diffInDays($todayStart, true) }} d
                                    </span>
                                </div>
                            </td>

                            <td>
                                @if ($isMede)
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span>{{ $medFinishedAt ? $medFinishedAt->format('d/m/Y') : '---' }}</span>
                                        @if ($medFinishedAt && $desiredReference)
                                            @php
                                                $measureDiff = $desiredReference->copy()->startOfDay()->diffInDays($medFinishedAt->copy()->startOfDay(), false);
                                                $measureClass = $measureDiff >= 0 ? 'text-bg-success' : 'text-bg-danger';
                                            @endphp
                                            <span class="badge {{ $measureClass }}"
                                                title="{{ $measureDiff >= 0 ? 'No prazo' : 'Atraso de ' . abs($measureDiff) . ' dia(s)' }}">
                                                {{ $medFinishedAt->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">---</span>
                                        @endif
                                    </div>
                                @elseif ($isMeda)
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span>{{ $wish ? $wish->format('d/m/Y') : '---' }}</span>
                                        @if ($daysToWish !== null)
                                            <span class="badge {{ $wishClass }}" title="Dias passados da data desejada">
                                                {{ $daysToWish }} d
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">---</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span>{{ $wish ? $wish->format('d/m/Y') : '---' }}</span>
                                        @if ($daysToWish !== null)
                                            <span class="badge {{ $wishClass }}" title="Dias para a data desejada">
                                                {{ $daysToWish }} d
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">---</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>{{ $item->sent_at ? $item->sent_at->format('d/m/Y') : '---' }}</span>
                                    <span class="badge text-bg-secondary" title="Dias Aberto">
                                        {{ $item->sent_at?->copy()?->startOfDay()?->diffInDays($todayStart, true) }} d
                                    </span>
                                </div>
                            </td>

                            <td class="fw-bold">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>{{ $dueLeft ? $dueLeft->format('d/m/Y') : '---' }}</span>
                                    @if ($item->finished_at && $dueLeft)
                                        <span class="badge {{ $slaFinishedClass }}"
                                            title="{{ $slaFinishedDiff >= 0 ? 'No prazo' : 'Atraso de ' . abs($slaFinishedDiff) . ' dia(s)' }}">
                                            {{ $item->finished_at->format('d/m/Y') }}
                                        </span>
                                    @elseif ($dueDaysLeft !== null)
                                        <span class="badge {{ $dueSlaClass }}" title="Dias para a data SLA">
                                            {{ $dueDaysLeft }} d
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">---</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="badge {{ $sapStatusClass }}">{{ $sapStatus }}</span>
                            </td>

                            <td>
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span class="badge {{ $item->statusBadgeClass }}">{{ $item->statusLabel }}</span>
                                    <span class="badge text-bg-light text-dark" title="Dias no status atual">
                                        {{ $item->status_age_days }} d
                                    </span>
                                </div>
                            </td>

                            <td>
                                @if ($item->medProtest?->id)
                                    @php
                                        $messageTitle = 'Abrir mensagens da Medida';

                                        if ($pendingForYou) {
                                            $messageTitle =
                                                'Última mensagem da Medida é de outro usuário, aguardando sua resposta';
                                        } elseif ($hasMessage) {
                                            $messageTitle = 'Última mensagem da Medida é sua (respondido por você)';
                                        }
                                    @endphp

                                    <button type="button"
                                        class="btn btn-link p-0 border-0 text-decoration-none align-middle"
                                        title="{{ $messageTitle }}"
                                        wire:click="$emitTo('protests.common.messages', 'openMessagesModal', {{ $item->medProtest->id }})">
                                        @if ($pendingForYou)
                                            <i class="ri-message-3-fill text-info"></i>
                                        @elseif ($hasMessage)
                                            <i class="ri-message-2-line text-muted"></i>
                                        @else
                                            <i class="ri-chat-1-line text-muted"></i>
                                        @endif
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td style="width:48px;">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Visualizar"
                                        wire:click="$emitTo('protests.dispatch.actions.view-protest-job', 'open', {{ $item->id }})">
                                        <i class="ri-eye-line"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        wire:click="goTo({{ $item->medProtest?->id ?? 0 }})" title="Seguir">
                                        <i class="ri-bookmark-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- PaginaÃ§Ã£o base --}}
        <div class="summary-bar mt-2">
            <div class="d-flex justify-content-between align-items-center">
                {{ $lists->links() }}
                <div class="text-muted small">
                    Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                    {{ $lists->total() }} registros
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center">
                <p class="mb-0">Não há registros para exibir com os filtros atuais.</p>
            </div>
        </div>
    @endif

    {{-- Drawer lateral de detalhes --}}
    @livewire('protests.dispatch.actions.view-protest-job', key('view-protest-job'))

    {{-- Modal de mensagens da Medida --}}
    @livewire('protests.common.messages', key('dispatch-messages-modal'))

    {{-- Modal de busca mÃºltipla (jÃ¡ existente em outro lugar) --}}
    @push('scripts')
        <script>
            document.addEventListener('livewire:load', () => {
                let currentHighlightedId = null;
                let monitoringHistogramChart = null;
                let lastHistogramPayloadSignature = null;

                const buildHistogram = () => {
                    const canvas = document.getElementById('monitoringHistogram');
                    const payloadNode = document.getElementById('monitoring-histogram-data');

                    if (!canvas || !payloadNode || typeof Chart === 'undefined') {
                        return;
                    }

                    const payloadRaw = payloadNode.dataset.payload || '{}';
                    if (monitoringHistogramChart && lastHistogramPayloadSignature === payloadRaw) {
                        return;
                    }

                    let payload = null;
                    try {
                        payload = JSON.parse(payloadRaw);
                    } catch (e) {
                        payload = null;
                    }

                    if (!payload) {
                        return;
                    }

                    const labels = payload.labels || [];
                    const series = payload.series || {};
                    const overdueData = (series.overdue || []).map((n) => Number(n ?? 0));
                    const dueSoonData = (series.dueSoon || []).map((n) => Number(n ?? 0));
                    const withinData = (series.within || []).map((n) => Number(n ?? 0));
                    const displayOverdueData = (series.displayOverdue || overdueData).map((n) => Number(n ?? 0));
                    const displayDueSoonData = (series.displayDueSoon || dueSoonData).map((n) => Number(n ?? 0));
                    const displayWithinData = (series.displayWithin || withinData).map((n) => Number(n ?? 0));
                    const displayMonthKeys = payload.monthKeys || [];
                    const selectedStack = payload.selectedStack || null;
                    const totalsByMonth = labels.map((_, i) =>
                        Number(displayOverdueData[i] ?? 0) + Number(displayDueSoonData[i] ?? 0) + Number(displayWithinData[i] ?? 0)
                    );
                    const sum = (arr) => (arr || []).reduce((acc, n) => acc + Number(n || 0), 0);
                    const overdueTotal = sum(displayOverdueData);
                    const dueSoonTotal = sum(displayDueSoonData);
                    const withinTotal = sum(displayWithinData);

                    const segmentIsActive = (key) => !selectedStack || selectedStack === key;
                    const marine = segmentIsActive('overdue') ? 'rgba(33,46,62,0.85)' : 'rgba(33,46,62,0.18)';
                    const electric = segmentIsActive('due_soon') ? 'rgba(40,255,82,0.85)' : 'rgba(40,255,82,0.18)';
                    const slate = segmentIsActive('within') ? 'rgba(124,149,153,0.85)' : 'rgba(124,149,153,0.18)';

                    if (monitoringHistogramChart) {
                        monitoringHistogramChart.destroy();
                    }

                    const totalsPlugin = {
                        id: 'monitoringHistogramTotals',
                        afterDatasetsDraw(chart) {
                            const {
                                ctx
                            } = chart;
                            const datasetMeta = chart.getDatasetMeta(0);
                            if (!datasetMeta || !datasetMeta.data) return;
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillStyle = '#1f2937';
                            ctx.font = '600 11px sans-serif';

                            datasetMeta.data.forEach((bar, index) => {
                                const total = Number(totalsByMonth[index] ?? 0);
                                if (total <= 0) return;
                                const x = bar.x;
                                const y = bar.y - 6;
                                ctx.fillText(String(total), x, y);
                            });
                            ctx.restore();
                        }
                    };

                    monitoringHistogramChart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: `Vencidos (${overdueTotal})`,
                                data: displayOverdueData,
                                backgroundColor: marine,
                                borderColor: '#212E3E',
                                borderWidth: 1,
                                borderRadius: 6,
                                stack: 'prazo',
                            }, {
                                label: `Vencendo (${dueSoonTotal})`,
                                data: displayDueSoonData,
                                backgroundColor: electric,
                                borderColor: '#28FF52',
                                borderWidth: 1,
                                borderRadius: 6,
                                stack: 'prazo',
                            }, {
                                label: `A vencer (${withinTotal})`,
                                data: displayWithinData,
                                backgroundColor: slate,
                                borderColor: '#7C9599',
                                borderWidth: 1,
                                borderRadius: 6,
                                stack: 'prazo',
                            }]
                        },
                        plugins: [totalsPlugin],
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        padding: 14
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => `${ctx.dataset.label.split('(')[0].trim()}: ${ctx.parsed.y} item(ns)`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                },
                                y: {
                                    beginAtZero: true,
                                    stacked: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            },
                            onClick: (evt) => {
                                const exact = monitoringHistogramChart.getElementsAtEventForMode(evt, 'nearest', {
                                    intersect: true
                                }, true);
                                if (!exact.length) {
                                    return;
                                }

                                const element = exact[0];
                                const bucket = displayMonthKeys[Number(element.index)] || null;
                                const segment = ['overdue', 'due_soon', 'within'][Number(element.datasetIndex)] || null;
                                if (!segment) return;
                                const root = canvas.closest('[wire\\:id]');
                                if (!root) {
                                    return;
                                }

                                const componentId = root.getAttribute('wire:id');
                                if (!componentId) {
                                    return;
                                }

                                Livewire.find(componentId).call('setHistogramStackSelection', bucket, segment);
                            }
                        }
                    });

                    lastHistogramPayloadSignature = payloadRaw;
                };

                const applyHighlight = () => {
                    document.querySelectorAll('.highlightable-row').forEach(row => {
                        if (!row.dataset.rowId) {
                            return;
                        }
                        if (row.dataset.rowId === currentHighlightedId) {
                            row.classList.add('highlight-active');
                        } else {
                            row.classList.remove('highlight-active');
                        }
                    });
                };

                const highlightRow = (row) => {
                    if (!row || !row.dataset.rowId) {
                        return;
                    }
                    currentHighlightedId = row.dataset.rowId;
                    applyHighlight();
                };

                document.addEventListener('click', (event) => {
                    const row = event.target.closest('.highlightable-row');
                    if (row) {
                        highlightRow(row);
                    }
                });

                buildHistogram();

                Livewire.hook('message.processed', () => {
                    applyHighlight();
                    buildHistogram();
                });
            });
        </script>
    @endpush
    </div>
</div>
