<div class="legal-queue-page">
    <x-show-loading />

    <style>
        .legal-queue-page {
            --lq-bg: #f6f7fb;
            --lq-surface: #ffffff;
            --lq-ink: #1f2933;
            --lq-muted: #6b7280;
            --lq-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        var(--lq-bg);
            padding: 1.5rem 0;
        }

        .lq-header {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.22);
            margin-bottom: 1.5rem;
        }

        .lq-kpi {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: .75rem;
            padding: .6rem 1rem;
            min-width: 110px;
            cursor: pointer;
            transition: background .15s;
        }
        .lq-kpi:hover { background: rgba(255,255,255,.18); }
        .lq-kpi .lq-kpi-val { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .lq-kpi .lq-kpi-lbl { font-size: .72rem; opacity: .8; text-transform: uppercase; letter-spacing: .05em; }
        .lq-kpi.danger  .lq-kpi-val { color: #f87171; }
        .lq-kpi.warning .lq-kpi-val { color: #fbbf24; }
        .lq-kpi.success .lq-kpi-val { color: #34d399; }

        .filter-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            padding: .85rem 1rem;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15,23,42,.1);
        }
        .filter-card .form-label {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: .3rem;
            font-size: .82rem;
        }
        .filter-card .form-select,
        .filter-card .form-control {
            color: #0f172a;
            border-color: #cbd5e1;
            background: #fff;
            font-size: .85rem;
        }

        .summary-bar {
            background: var(--lq-surface);
            border: 1px solid var(--lq-border);
            border-radius: .9rem;
            padding: .65rem 1rem;
            box-shadow: 0 8px 20px rgba(15,23,42,.05);
            margin-bottom: 1rem;
        }

        .table-card {
            background: var(--lq-surface);
            border: 1px solid var(--lq-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15,23,42,.08);
            overflow: hidden;
        }
        .table-card .card-header {
            padding: .8rem 1rem;
            border-bottom: 1px solid #253247;
        }
        .table-card .card-body {
            padding: 1rem;
        }
        .table-card .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }

        .queue-toolbar {
            display: grid;
            grid-template-columns: 36px minmax(250px, 2.1fr) minmax(90px, .8fr) minmax(180px, 1.3fr) minmax(180px, 1.1fr) minmax(150px, .9fr) minmax(180px, 1fr) minmax(170px, 1fr) 52px;
            gap: .5rem;
            padding: .7rem .9rem;
            background: #f8fafc;
            border-bottom: 1px solid #dbe3ee;
            align-items: center;
        }

        .queue-toolbar .col-label {
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #607087;
            font-weight: 700;
        }

        .queue-list {
            padding: .7rem;
            background: #f8fafc;
        }

        .queue-item {
            display: grid;
            grid-template-columns: 36px minmax(250px, 2.1fr) minmax(90px, .8fr) minmax(180px, 1.3fr) minmax(180px, 1.1fr) minmax(150px, .9fr) minmax(180px, 1fr) minmax(170px, 1fr) 52px;
            gap: .5rem;
            align-items: center;
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-left: 4px solid #cbd5e1;
            border-radius: .8rem;
            padding: .65rem .7rem;
            margin-bottom: .55rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
        }

        .queue-item.queue-overdue {
            border-left-color: #ef4444;
            background: #fff7f7;
        }

        .queue-item.queue-returned {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }

        .queue-meta {
            line-height: 1.25;
        }

        .queue-process-link {
            color: #1e3a5f;
            font-weight: 700;
            text-decoration: none;
            font-size: .95rem;
        }

        .queue-process-link:hover {
            text-decoration: underline;
        }

        .queue-subtext {
            color: #4b5563;
            font-size: .78rem;
            margin-top: .2rem;
        }
        .queue-subdemands-panel {
            margin: -.35rem .15rem .5rem 2.9rem;
            border: 1px solid #dbe3ee;
            border-radius: .7rem;
            background: #f8fafc;
            padding: .5rem .55rem;
        }
        .queue-subdemands-list {
            display: grid;
            gap: .35rem;
        }
        .queue-subdemand-item {
            border: 1px solid #e2e8f0;
            border-radius: .55rem;
            background: #fff;
            padding: .4rem .5rem;
            font-size: .78rem;
        }

        .queue-chip-col {
            display: flex;
            flex-direction: column;
            gap: .2rem;
            align-items: flex-start;
        }

        .queue-user {
            font-size: .84rem;
            color: #1f2937;
            font-weight: 600;
            line-height: 1.25;
        }

        .queue-user-muted {
            font-size: .77rem;
            color: #6b7280;
        }

        @media (max-width: 1500px) {
            .queue-toolbar,
            .queue-item {
                min-width: 1320px;
            }
        }

        .bulk-bar {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: .75rem;
            padding: .6rem 1rem;
        }

        .case-group-card {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: .85rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
            margin-bottom: .75rem;
            overflow: hidden;
        }
        .case-group-head {
            background: #f1f5f9;
            border-bottom: 1px solid #dbe3ee;
            padding: .6rem .8rem;
        }
        .case-group-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.4fr 1.2fr 1.2fr 1fr;
            gap: .7rem;
        }
        .case-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            font-weight: 700;
        }
        .case-value {
            font-size: .85rem;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.25;
            margin-top: .1rem;
        }
        .case-sublist {
            padding: .55rem .7rem .35rem;
            background: #f8fafc;
        }
        .case-subrow {
            display: grid;
            grid-template-columns: minmax(240px, 2fr) minmax(90px, .7fr) minmax(180px, 1fr) minmax(180px, 1fr) 52px;
            gap: .5rem;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #cbd5e1;
            border-radius: .65rem;
            padding: .45rem .55rem;
            margin-bottom: .4rem;
        }
        .case-subrow-open { border-left-color: #16a34a; }
        .case-subrow-closed { border-left-color: #ef4444; }
        .case-subrow-unknown { border-left-color: #f59e0b; }
        @media (max-width: 1400px) {
            .case-group-grid { min-width: 900px; }
            .case-subrow { min-width: 900px; }
        }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="lq-header">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">

                {{-- Título + KPIs --}}
                <div>
                    <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
                    <div class="mb-3 opacity-75" style="font-size:.9rem">Fila de Demandas</div>

                    <div class="d-flex flex-wrap gap-2">
                        <div class="lq-kpi" wire:click="setTab('all')">
                            <div class="lq-kpi-val">
                                {{ $kpis['total_active'] ?? 0 }}
                            </div>
                            <div class="lq-kpi-lbl">Total ativas</div>
                        </div>
                        <div class="lq-kpi danger" wire:click="setTab('overdue')">
                            <div class="lq-kpi-val">
                                {{ $kpis['overdue'] ?? 0 }}
                            </div>
                            <div class="lq-kpi-lbl">Vencidas</div>
                        </div>
                        <div class="lq-kpi warning" wire:click="setTab('in_progress')">
                            <div class="lq-kpi-val">
                                {{ $kpis['awaiting_field'] ?? 0 }}
                            </div>
                            <div class="lq-kpi-lbl">Em andamento</div>
                        </div>
                        <div class="lq-kpi success" wire:click="setTab('triage')">
                            <div class="lq-kpi-val">
                                {{ $kpis['triage'] ?? 0 }}
                            </div>
                            <div class="lq-kpi-lbl">Na triagem</div>
                        </div>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="filter-card">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <label class="form-label mb-0">Buscar demanda</label>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary py-0 px-2"
                                            wire:click="$set('showBulkCaseSearchModal', true)">
                                        <i class="bi bi-list-check me-1"></i>Casos em massa
                                    </button>
                                </div>
                                <input type="text" class="form-control"
                                       wire:model.debounce.400ms="search"
                                       placeholder="Nº processo, empresa, assunto, parte adversa..." />
                                @if(count($bulkCaseSearchTerms) > 0)
                                    <div class="small text-primary mt-1 d-flex justify-content-between align-items-center gap-2">
                                        <span><i class="bi bi-filter-circle me-1"></i>{{ count($bulkCaseSearchTerms) }} caso(s)/processo(s) em filtro em massa</span>
                                        <button type="button" class="btn btn-link btn-sm p-0" wire:click="clearBulkCaseSearch">limpar</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Tipo de demanda</label>
                                <select class="form-select" wire:model="sourceType">
                                    <option value="">Todos</option>
                                    <option value="injunction">Liminar</option>
                                    <option value="sentence">Sentença</option>
                                    <option value="subsidy">Subsídio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Prazo</label>
                                <select class="form-select" wire:model="dueDateFilter">
                                    <option value="">Todos</option>
                                    <option value="overdue">Vencidas</option>
                                    <option value="3days">Vence em até 3 dias</option>
                                    <option value="7days">Vence em até 7 dias</option>
                                    <option value="no_date">Sem prazo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="filter-card">
                                <label class="form-label">Controlador</label>
                                <select class="form-select" wire:model="controllerFilter">
                                    <option value="">Todos</option>
                                    @foreach($controllers as $ctrl)
                                        <option value="{{ $ctrl->id }}">{{ $ctrl->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 d-flex align-items-end">
                            <button class="btn btn-light btn-sm w-100"
                                    wire:click="clearFilters">
                                <i class="bi bi-x-circle me-1"></i>Limpar filtros
                            </button>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <button class="btn btn-outline-light btn-sm w-100"
                                    wire:click="$set('showTransferModal', true)">
                                <i class="bi bi-arrows-move me-1"></i>Transferir Controlador
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ABAS + SUMMARY --}}
        <div class="summary-bar d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-tabs border-0 gap-1 mb-0">
                @foreach([
                    ['all',      'Todas',      false],
                    ['triage',   'Triagem',    true],
                    ['in_progress', 'Em andamento',   true],
                    ['overdue',  'Vencidas',   true],
                    ['closed',   'Encerradas', true],
                ] as [$key, $label, $hasBadge])
                    <li class="nav-item">
                        <button class="nav-link py-1 px-2 {{ $tab === $key ? 'active fw-bold' : '' }}"
                                wire:click="setTab('{{ $key }}')">
                            {{ $label }}
                            @if($hasBadge)
                                @php
                                    $tabBadgeCount = match($key) {
                                        'triage' => $kpis['triage'] ?? 0,
                                        'in_progress' => $kpis['awaiting_field'] ?? 0,
                                        'overdue' => $kpis['overdue'] ?? 0,
                                        'closed' => $kpis['closed'] ?? 0,
                                        default => 0,
                                    };
                                @endphp
                                <span class="badge {{ $key === 'overdue' ? 'bg-danger' : 'bg-secondary' }}">{{ $tabBadgeCount }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="groupByCaseSwitch" wire:model="groupByCase">
                    <label class="form-check-label small" for="groupByCaseSwitch">Agrupar por caso</label>
                </div>
                <div class="small text-muted">
                    Exibindo <strong>{{ $demands->count() }}</strong> de <strong>{{ $demands->total() }}</strong> demandas
                </div>
                <span class="badge bg-warning text-dark">
                    SICODE encerrado + origem aberta: {{ $monitorSicodeClosedButSourceOpen }}
                </span>
                <span class="badge bg-danger">
                    Origem encerrada + SICODE aberto: {{ $monitorSourceClosedButSicodeOpen }}
                </span>
            </div>
        </div>

        {{-- BARRA DE AÇÕES EM LOTE --}}
        @if(count($selectedIds) > 0)
            <div class="bulk-bar d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="fw-semibold text-primary">
                    <i class="bi bi-check2-square me-1"></i>{{ count($selectedIds) }} selecionadas
                </span>
                <button class="btn btn-sm btn-outline-secondary" wire:click="$set('showBulkReassignModal', true)">
                    <i class="bi bi-person-gear me-1"></i>Reatribuir Controlador
                </button>
                <button class="btn btn-sm btn-success" wire:click="$set('showBulkCloseModal', true)">
                    <i class="bi bi-check2-circle me-1"></i>Encerrar em massa
                </button>
                <button class="btn btn-sm btn-outline-danger" wire:click="$set('showBulkIgnoreModal', true)">
                    <i class="bi bi-slash-circle me-1"></i>Ignorar
                </button>
                <button class="btn btn-sm btn-link text-muted ms-auto" wire:click="clearSelection">
                    <i class="bi bi-x"></i> Limpar seleção
                </button>
            </div>
        @endif

        {{-- LISTA --}}
        <div class="table-card" wire:loading.class="opacity-50">
            <div class="card-header text-bg-dark fw-bold">
                Jurídico › Fila de Demandas
            </div>
            <div class="queue-toolbar">
                <div>
                    <input type="checkbox" class="form-check-input"
                           wire:model="selectAll"
                           wire:click="$set('selectedIds', $event.target.checked ? {{ $demands->pluck('id')->toJson() }} : [])" />
                </div>
                <div class="col-label">
                    <button class="btn btn-link btn-sm p-0 text-decoration-none text-dark fw-semibold"
                            wire:click="sortBy('source_case_number')">
                        Processo / Empresa
                        @if($sortBy === 'source_case_number')
                            <i class="bi bi-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </button>
                </div>
                <div class="col-label">Tipo</div>
                <div class="col-label">Parte Adversa</div>
                <div class="col-label">Área Responsável</div>
                <div class="col-label">
                    <button class="btn btn-link btn-sm p-0 text-decoration-none text-dark fw-semibold"
                            wire:click="sortBy('source_due_at')">
                        Prazo
                        @if($sortBy === 'source_due_at')
                            <i class="bi bi-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </button>
                </div>
                <div class="col-label">Situação</div>
                <div class="col-label">Responsáveis</div>
                <div class="col-label text-center">Ação</div>
            </div>

            <div class="queue-list">
                @if($groupByCase)
                    @forelse($groupedDemands ?? collect() as $group)
                        <div class="case-group-card">
                            <div class="case-group-head">
                                <div class="case-group-grid">
                                    <div>
                                        <div class="case-label">Number_case</div>
                                        <div class="case-value">{{ $group['number_case'] }}</div>
                                    </div>
                                    <div>
                                        <div class="case-label">Process_number</div>
                                        <div class="case-value">{{ $group['process_number'] }}</div>
                                    </div>
                                    <div>
                                        <div class="case-label">Empresa</div>
                                        <div class="case-value">{{ $group['empresa'] }}</div>
                                    </div>
                                    <div>
                                        <div class="case-label">Firma</div>
                                        <div class="case-value">{{ $group['firma'] }}</div>
                                    </div>
                                    <div>
                                        <div class="case-label">Deadline aberto (mais próximo)</div>
                                        <div class="case-value">
                                            <x-legal.due-date-chip :date="$group['nearest_open_deadline']" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="case-sublist">
                                @foreach($group['demands'] as $demand)
                                    @php
                                        $sourceTypeVal = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : $demand->source_type;
                                        $tipoLabel = match($sourceTypeVal) {
                                            'injunction' => 'Liminar',
                                            'sentence'   => 'Sentença',
                                            'subsidy'    => 'Subsídio',
                                            default      => $sourceTypeVal ?? '—',
                                        };
                                        $processStatusImport = (string) ($demand->process_status_at_import ?? '');
                                        $processStatusNormalized = mb_strtolower($processStatusImport);
                                        $isOpenByProcessStatus = $processStatusImport !== '' && !str_contains($processStatusNormalized, 'encerrad');
                                        $subRowClass = $processStatusImport === ''
                                            ? 'case-subrow-unknown'
                                            : ($isOpenByProcessStatus ? 'case-subrow-open' : 'case-subrow-closed');
                                    @endphp
                                    <div class="case-subrow {{ $subRowClass }}">
                                        <div class="queue-meta">
                                            <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="queue-process-link">
                                                {{ $demand->source_subject ?: ($demand->title ?: 'Sem assunto') }}
                                            </a>
                                            <div class="queue-subtext">ID {{ $demand->id }} · {{ $demand->created_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div><span class="badge bg-light text-dark border">{{ $tipoLabel }}</span></div>
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <x-legal.status-badge :status="$demand->internal_status" />
                                            <span class="badge {{ $isOpenByProcessStatus ? 'bg-success' : ($processStatusImport === '' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                                Processo: {{ $processStatusImport !== '' ? $processStatusImport : 'Não informado' }}
                                            </span>
                                            @if(($demand->subdemands_count ?? 0) > 0)
                                                <button class="btn btn-link btn-sm p-0 mt-1 text-decoration-none" wire:click="toggleSubdemands({{ $demand->id }})">
                                                    {{ in_array($demand->id, $expandedSubdemands, true) ? 'Ocultar' : 'Ver' }} {{ $demand->subdemands_count }} subdemanda(s)
                                                </button>
                                            @endif
                                        </div>
                                        <div>{{ $demand->controller?->name ?? 'Sem controlador' }}</div>
                                        <div class="text-center">
                                            <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="btn btn-sm btn-outline-secondary" title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    @if(in_array($demand->id, $expandedSubdemands, true) && $demand->subdemands->isNotEmpty())
                                        <div class="queue-subdemands-panel">
                                            <div class="queue-subdemands-list">
                                                @foreach($demand->subdemands->sortByDesc('created_at') as $sub)
                                                    @php
                                                        $subStatus = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
                                                    @endphp
                                                    <div class="queue-subdemand-item">
                                                        <strong>#{{ $sub->id }}</strong> ·
                                                        {{ $sub->assignedTo?->name ?? ($sub->assigned_area_name ?: 'Sem destino') }} ·
                                                        Prazo: {{ $sub->deadline_at ? \Carbon\Carbon::parse($sub->deadline_at)->format('d/m/Y H:i') : '—' }} ·
                                                        <span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $subStatus) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5 bg-white border rounded-3">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
                            Nenhuma demanda encontrada para os filtros aplicados.
                        </div>
                    @endforelse
                @else
                @forelse($demands as $demand)
                    @php
                        $isOverdue  = $demand->source_due_at && $demand->source_due_at->isPast();
                        $isReturned = in_array(
                            $demand->internal_status instanceof \BackedEnum
                                ? $demand->internal_status->value
                                : $demand->internal_status,
                            ['returned_by_field', 'under_controller_review', 'returned_for_correction']
                        );

                        $sourceTypeVal = $demand->source_type instanceof \BackedEnum
                            ? $demand->source_type->value
                            : $demand->source_type;

                        $tipoLabel = match($sourceTypeVal) {
                            'injunction' => 'Liminar',
                            'sentence'   => 'Sentença',
                            'subsidy'    => 'Subsídio',
                            default      => $sourceTypeVal ?? '—',
                        };
                        $tipoBg = match($sourceTypeVal) {
                            'injunction' => 'bg-danger text-white',
                            'sentence'   => 'bg-warning text-dark',
                            'subsidy'    => 'bg-info text-dark',
                            default      => 'bg-secondary text-white',
                        };
                    @endphp

                    <div class="queue-item {{ $isOverdue ? 'queue-overdue' : ($isReturned ? 'queue-returned' : '') }}"
                         wire:key="demand-row-{{ $demand->id }}">
                        <div>
                            <input type="checkbox" class="form-check-input"
                                   wire:model="selectedIds" value="{{ $demand->id }}" />
                        </div>

                        <div class="queue-meta">
                            <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="queue-process-link">
                                {{ $demand->source_case_number ?? $demand->source_process_number_masked ?? 'S/N' }}
                            </a>
                            <div class="queue-subtext">
                                {{ $demand->legalCase?->company_name ?: 'Empresa não informada' }}
                            </div>
                            @if($demand->subject)
                                <div class="queue-subtext" title="{{ $demand->subject }}" data-bs-toggle="tooltip">
                                    <i class="bi bi-card-text me-1"></i>{{ Str::limit($demand->subject, 74) }}
                                </div>
                            @endif
                            @if(($demand->subdemands_count ?? 0) > 0)
                                <div class="queue-subtext">
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none" wire:click="toggleSubdemands({{ $demand->id }})">
                                        {{ in_array($demand->id, $expandedSubdemands, true) ? 'Ocultar' : 'Ver' }} {{ $demand->subdemands_count }} subdemanda(s)
                                    </button>
                                </div>
                            @endif
                            @if($demand->process_manager)
                                <div class="queue-subtext">
                                    <i class="bi bi-person-badge me-1"></i>{{ $demand->process_manager }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <span class="badge {{ $tipoBg }}" style="font-size:.78rem">
                                {{ $tipoLabel }}
                            </span>
                        </div>

                        <div class="queue-meta">
                            @if($demand->opposing_party)
                                <span title="{{ $demand->opposing_party }}" data-bs-toggle="tooltip">
                                    {{ Str::limit($demand->opposing_party, 42) }}
                                </span>
                            @else
                                <span class="text-muted">Não informado</span>
                            @endif
                        </div>

                        <div class="queue-meta">
                            @php $area = $demand->responsible_area_name ?? $demand->origin_area_name; @endphp
                            @if($area)
                                <span title="{{ $area }}" data-bs-toggle="tooltip">{{ Str::limit($area, 40) }}</span>
                            @else
                                <span class="text-muted">Não informada</span>
                            @endif
                        </div>

                        <div>
                            <x-legal.due-date-chip :date="$demand->source_due_at" :executedAt="$demand->source_executed_at" />
                        </div>

                        <div class="queue-chip-col">
                            <x-legal.status-badge :status="$demand->internal_status" />
                            @php $extBadge = $demand->externalStatusBadge(); @endphp
                            <span class="badge {{ $extBadge['class'] }} d-inline-flex align-items-center gap-1"
                                  style="font-size:.72rem; max-width:170px; white-space:normal; line-height:1.2"
                                  title="{{ $demand->external_flow_status ?? $demand->external_status ?? '' }}"
                                  data-bs-toggle="tooltip">
                                <i class="bi {{ $extBadge['icon'] }}"></i>
                                {{ Str::limit($demand->external_flow_status ?? $demand->external_status ?? '—', 32) }}
                            </span>
                        </div>

                        <div class="queue-meta">
                            <div class="queue-user">
                                {{ $demand->controller?->name ?? 'Sem controlador' }}
                            </div>
                            <div class="queue-user-muted">
                                Campo: {{ $demand->currentAssignee?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('legal.demand.detail', $demand->uuid) }}"
                               class="btn btn-sm btn-outline-secondary" title="Ver detalhes">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>
                    @if(in_array($demand->id, $expandedSubdemands, true) && $demand->subdemands->isNotEmpty())
                        <div class="queue-subdemands-panel" wire:key="subdemand-panel-{{ $demand->id }}">
                            <div class="queue-subdemands-list">
                                @foreach($demand->subdemands->sortByDesc('created_at') as $sub)
                                    @php
                                        $subStatus = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
                                    @endphp
                                    <div class="queue-subdemand-item">
                                        <strong>#{{ $sub->id }}</strong> ·
                                        {{ $sub->assignedTo?->name ?? ($sub->assigned_area_name ?: 'Sem destino') }} ·
                                        Prazo: {{ $sub->deadline_at ? \Carbon\Carbon::parse($sub->deadline_at)->format('d/m/Y H:i') : '—' }} ·
                                        <span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $subStatus) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center text-muted py-5 bg-white border rounded-3">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
                        Nenhuma demanda encontrada para os filtros aplicados.
                    </div>
                @endforelse
                @endif
            </div>

            <div class="card-body">
                @if($demands->hasPages())
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="small text-muted">
                            Mostrando {{ $demands->firstItem() }} até {{ $demands->lastItem() }} de {{ $demands->total() }} registros.
                        </div>
                    </div>
                    {{ $demands->links() }}
                @endif
            </div>
        </div>

    </div>{{-- /container-fluid --}}


    {{-- ===== MODAIS ===== --}}

    {{-- Modal: Buscar casos em massa --}}
    <div class="modal fade {{ $showBulkCaseSearchModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showBulkCaseSearchModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-list-check me-2"></i>Buscar casos em massa</h5>
                    <button class="btn-close" wire:click="$set('showBulkCaseSearchModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        Cole números de caso ou processo separados por quebra de linha, vírgula, ponto-e-vírgula ou espaço.
                        A fila exibirá demandas que correspondam a qualquer termo informado.
                    </div>
                    <label class="form-label fw-semibold">Casos/processos para buscar</label>
                    <textarea class="form-control font-monospace"
                              rows="9"
                              wire:model.defer="bulkCaseSearchInput"
                              placeholder="Ex:
192826
40057337520268260224
40057337520268260225"></textarea>
                    @if(count($bulkCaseSearchTerms) > 0)
                        <div class="small text-muted mt-2">
                            Filtro atual: <strong>{{ count($bulkCaseSearchTerms) }}</strong> termo(s) carregado(s).
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" wire:click="clearBulkCaseSearch">
                        <i class="bi bi-x-circle me-1"></i>Limpar busca em massa
                    </button>
                    <button class="btn btn-secondary" wire:click="$set('showBulkCaseSearchModal', false)">Cancelar</button>
                    <button class="btn btn-primary" wire:click="applyBulkCaseSearch">
                        <i class="bi bi-search me-1"></i>Aplicar busca
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Encerrar em Massa --}}
    <div class="modal fade {{ $showBulkCloseModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showBulkCloseModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Encerrar {{ count($selectedIds) }} demandas</h5>
                    <button class="btn-close" wire:click="$set('showBulkCloseModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        As demandas serão encerradas internamente pelo controlador selecionado no histórico de eventos.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parecer/motivo do encerramento *</label>
                        <textarea class="form-control" rows="3" wire:model="bulkCloseReason"
                                  placeholder="Ex: atividade concluída com evidências anexadas..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showBulkCloseModal', false)">Cancelar</button>
                    <button class="btn btn-success" wire:click="closeInternalBatch">
                        <i class="bi bi-check2-circle me-1"></i>Confirmar Encerramento
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Reatribuir Controlador --}}
    <div class="modal fade {{ $showBulkReassignModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showBulkReassignModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Reatribuir controlador — {{ count($selectedIds) }} demandas</h5>
                    <button class="btn-close" wire:click="$set('showBulkReassignModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Novo controlador *</label>
                        <select class="form-select" wire:model="bulkNewControllerId">
                            <option value="">Selecionar...</option>
                            @foreach($controllers as $ctrl)
                                <option value="{{ $ctrl->id }}">{{ $ctrl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showBulkReassignModal', false)">Cancelar</button>
                    <button class="btn btn-primary" wire:click="reassignControllerBatch">Confirmar Reatribuição</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Ignorar Selecionadas --}}
    <div class="modal fade {{ $showBulkIgnoreModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showBulkIgnoreModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-slash-circle me-2"></i>Ignorar {{ count($selectedIds) }} demandas</h5>
                    <button class="btn-close" wire:click="$set('showBulkIgnoreModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        As demandas serão movidas para <strong>IGNORADO</strong> e removidas da fila ativa.
                        A ação fica registrada no histórico.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo (obrigatório)</label>
                        <textarea class="form-control" rows="3" wire:model="bulkIgnoreReason"
                                  placeholder="Descreva o motivo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showBulkIgnoreModal', false)">Cancelar</button>
                    <button class="btn btn-danger" wire:click="ignoreBatch">
                        <i class="bi bi-slash-circle me-1"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Transferência Administrativa --}}
    <div class="modal fade {{ $showTransferModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showTransferModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrows-move me-2"></i>Transferência de Demandas entre Controladores</h5>
                    <button class="btn-close" wire:click="$set('showTransferModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        Transfere <strong>todas</strong> as demandas ativas de um controlador para outro.
                        Use para cobrir ausências, férias ou redistribuição permanente.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Controlador de origem *</label>
                        <select class="form-select" wire:model="transferFromUserId">
                            <option value="">Selecionar...</option>
                            @foreach($controllers as $ctrl)
                                <option value="{{ $ctrl->id }}">{{ $ctrl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($transferFromUserId && $transferPreviewCount > 0)
                        <div class="alert alert-secondary small mb-3">
                            <i class="bi bi-briefcase me-1"></i>
                            Este controlador possui <strong>{{ $transferPreviewCount }}</strong> demandas ativas.
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Controlador de destino *</label>
                        <select class="form-select" wire:model="transferToUserId">
                            <option value="">Selecionar...</option>
                            @foreach($controllers as $ctrl)
                                <option value="{{ $ctrl->id }}">{{ $ctrl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showTransferModal', false)">Cancelar</button>
                    <button class="btn btn-primary" wire:click="transferController"
                            @if(!$transferFromUserId || !$transferToUserId) disabled @endif>
                        <i class="bi bi-arrows-move me-1"></i>
                        Transferir{{ $transferPreviewCount > 0 ? ' '.$transferPreviewCount.' demandas' : '' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
