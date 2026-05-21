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
                                <livewire:legal.controller.kpi-value metric="total_active" :wire:key="'kpi-total-active'" />
                            </div>
                            <div class="lq-kpi-lbl">Total ativas</div>
                        </div>
                        <div class="lq-kpi danger" wire:click="setTab('overdue')">
                            <div class="lq-kpi-val">
                                <livewire:legal.controller.kpi-value metric="overdue" :wire:key="'kpi-overdue'" />
                            </div>
                            <div class="lq-kpi-lbl">Vencidas</div>
                        </div>
                        <div class="lq-kpi warning" wire:click="setTab('in_field')">
                            <div class="lq-kpi-val">
                                <livewire:legal.controller.kpi-value metric="awaiting_field" :wire:key="'kpi-awaiting-field'" />
                            </div>
                            <div class="lq-kpi-lbl">Aguard. Campo</div>
                        </div>
                        <div class="lq-kpi success" wire:click="setTab('returned')">
                            <div class="lq-kpi-val">
                                <livewire:legal.controller.kpi-value metric="returned_today" :wire:key="'kpi-returned-today'" />
                            </div>
                            <div class="lq-kpi-lbl">Retornaram hoje</div>
                        </div>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="filter-card">
                                <label class="form-label">Buscar demanda</label>
                                <input type="text" class="form-control"
                                       wire:model.debounce.400ms="search"
                                       placeholder="Nº processo, empresa, assunto, parte adversa..." />
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
                                    wire:click="$set('sourceType',''); $set('dueDateFilter',''); $set('controllerFilter',''); $set('search','')">
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
                    ['in_field', 'Em Campo',   true],
                    ['returned', 'Retornadas', true],
                    ['overdue',  'Vencidas',   true],
                    ['closed',   'Encerradas', true],
                ] as [$key, $label, $hasBadge])
                    <li class="nav-item">
                        <button class="nav-link py-1 px-2 {{ $tab === $key ? 'active fw-bold' : '' }}"
                                wire:click="setTab('{{ $key }}')">
                            {{ $label }}
                            @if($hasBadge)
                                <livewire:legal.controller.tab-count-badge :tab="$key" :wire:key="'tab-count-'.$key" />
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
            <div class="small text-muted">
                Exibindo <strong>{{ $demands->count() }}</strong> de <strong>{{ $demands->total() }}</strong> demandas
            </div>
        </div>

        {{-- BARRA DE AÇÕES EM LOTE --}}
        @if(count($selectedIds) > 0)
            <div class="bulk-bar d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="fw-semibold text-primary">
                    <i class="bi bi-check2-square me-1"></i>{{ count($selectedIds) }} selecionadas
                </span>
                <button class="btn btn-sm btn-primary" wire:click="$set('showBulkFieldModal', true)">
                    <i class="bi bi-send me-1"></i>Enviar para Campo
                </button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="$set('showBulkReassignModal', true)">
                    <i class="bi bi-person-gear me-1"></i>Reatribuir Controlador
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
                @empty
                    <div class="text-center text-muted py-5 bg-white border rounded-3">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
                        Nenhuma demanda encontrada para os filtros aplicados.
                    </div>
                @endforelse
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

    {{-- Modal: Enviar para Campo --}}
    <div class="modal fade {{ $showBulkFieldModal ? 'show d-block' : '' }}" tabindex="-1"
         style="{{ $showBulkFieldModal ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-send me-2"></i>Enviar {{ count($selectedIds) }} demandas para o campo</h5>
                    <button class="btn-close" wire:click="$set('showBulkFieldModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Executante responsável *</label>
                        <select class="form-select" wire:model="bulkAssignToUserId">
                            <option value="">Selecionar usuário...</option>
                            @foreach($fieldUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Instrução (válida para todas)</label>
                        <textarea class="form-control" rows="3" wire:model="bulkAssignMessage"
                                  placeholder="Instruções para o executante..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Prazo interno (data e hora)</label>
                        <input type="datetime-local" class="form-control" wire:model="bulkAssignDueAt" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showBulkFieldModal', false)">Cancelar</button>
                    <button class="btn btn-primary" wire:click="sendBatchToField">
                        <i class="bi bi-send me-1"></i>Confirmar Envio
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
