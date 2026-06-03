<div class="lti-page">
    <x-show-loading />

    <style>
        .lti-page {
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        #f6f7fb;
            padding: 1.5rem 0;
        }
        .lti-header {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .22);
            margin-bottom: 1.5rem;
        }
        .lti-kpi {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: .75rem;
            padding: .6rem 1rem;
            min-width: 100px;
        }
        .lti-kpi .val { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .lti-kpi .lbl { font-size: .72rem; opacity: .8; text-transform: uppercase; letter-spacing: .05em; }
        .filter-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            padding: .85rem 1rem;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15,23,42,.1);
        }
        .filter-card .form-label { color: #0f172a; font-weight: 700; margin-bottom: .3rem; font-size: .82rem; }
        .filter-card .form-select,
        .filter-card .form-control { color: #0f172a; border-color: #cbd5e1; background: #fff; font-size: .85rem; }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
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
        .triage-toolbar {
            display: grid;
            grid-template-columns: minmax(450px, 2.6fr) minmax(150px, .9fr) minmax(190px, 1fr) minmax(180px, .9fr) minmax(220px, 1.2fr);
            gap: .5rem;
            padding: .7rem .9rem;
            background: #f8fafc;
            border-bottom: 1px solid #dbe3ee;
            align-items: center;
        }
        .triage-toolbar .col-label {
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #607087;
            font-weight: 700;
        }
        .triage-list {
            padding: .7rem;
            background: #f8fafc;
        }
        .triage-item {
            display: grid;
            grid-template-columns: minmax(450px, 2.6fr) minmax(150px, .9fr) minmax(190px, 1fr) minmax(180px, .9fr) minmax(220px, 1.2fr);
            gap: .5rem;
            align-items: start;
            border: 1px solid #dbe3ee;
            border-left: 4px solid #cbd5e1;
            border-radius: .8rem;
            padding: .65rem .7rem;
            margin-bottom: .55rem;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
        }
        .triage-item:hover { background: #f8fafc; }
        .triage-item.overdue { border-left-color: #ef4444; background: #fff7f7; }
        .triage-main .process-link {
            color:#1e3a5f;
            font-size:.95rem;
            font-weight:700;
            text-decoration:none;
        }
        .triage-main .process-link:hover { text-decoration: underline; }
        .triage-sub { font-size:.78rem; color:#4b5563; margin-top:.2rem; line-height:1.25; }
        .triage-actions { display:flex; flex-direction:column; gap:.35rem; }
        @media (max-width: 1500px) {
            .triage-toolbar,
            .triage-item {
                min-width: 1300px;
            }
        }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="lti-header">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
                    <div class="mb-3 opacity-75" style="font-size:.9rem">Inbox de Triagem</div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="lti-kpi">
                            <div class="val text-warning">
                                <livewire:legal.controller.triage-counter metric="new" :wire:key="'triage-kpi-new'" />
                            </div>
                            <div class="lbl">Novas</div>
                        </div>
                        <div class="lti-kpi">
                            <div class="val" style="color:#93c5fd">
                                <livewire:legal.controller.triage-counter metric="triage" :wire:key="'triage-kpi-triage'" />
                            </div>
                            <div class="lbl">Em Triagem</div>
                        </div>
                        <div class="lti-kpi">
                            <div class="val">
                                <livewire:legal.controller.triage-counter metric="pending" :wire:key="'triage-kpi-pending'" />
                            </div>
                            <div class="lbl">Total Pendente</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="filter-card">
                                <label class="form-label">Buscar demanda</label>
                                <input type="text" class="form-control"
                                       wire:model.debounce.400ms="search"
                                       placeholder="Nº processo, assunto, parte adversa..." />
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="filter-card">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" wire:model="sourceTypeFilter">
                                    <option value="">Todos</option>
                                    <option value="injunction">Liminar</option>
                                    <option value="sentence">Sentença</option>
                                    <option value="subsidy">Subsídio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="filter-card">
                                <label class="form-label">Área de origem</label>
                                <input type="text" class="form-control" placeholder="Filtrar área..."
                                       wire:model.debounce.400ms="originAreaFilter" />
                            </div>
                        </div>
                        <div class="col-12 col-md-2">
                            <div class="filter-card">
                                <label class="form-label">Controlador</label>
                                <select class="form-select" wire:model="controllerFilter">
                                    <option value="">Todos</option>
                                    @foreach($controllers as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Último Batch --}}
        @if($lastBatch)
            @php $batchAge = \Carbon\Carbon::parse($lastBatch->created_at)->diffInHours(now()); @endphp
            <div class="alert {{ $batchAge > 24 ? 'alert-warning' : 'alert-light border' }} small mb-3 d-flex align-items-center gap-3">
                <i class="bi bi-cloud-download fs-5 text-muted"></i>
                <div class="flex-grow-1">
                    <strong>Batch #{{ $lastBatch->id }}</strong> —
                    {{ \Carbon\Carbon::parse($lastBatch->created_at)->format('d/m/Y H:i') }}
                    &bull; Novas: {{ $lastBatch->new_count ?? '—' }}
                    &bull; Atualizadas: {{ $lastBatch->updated_count ?? '—' }}
                    &bull; Desaparecidas: {{ $lastBatch->removed_count ?? '—' }}
                </div>
                @if($batchAge > 24)
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $batchAge }}h sem importação
                    </span>
                @endif
            </div>
        @endif

        {{-- CONTEÚDO --}}
        <div class="table-card" wire:loading.class="opacity-50">
            <div class="card-header text-bg-dark fw-bold d-flex justify-content-between align-items-center">
                <span>Jurídico › Inbox de Triagem</span>
                <span class="badge bg-secondary">
                    <livewire:legal.controller.triage-counter metric="pending" :wire:key="'triage-header-pending'" /> pendente(s)
                </span>
            </div>

            <div class="triage-toolbar">
                <div class="col-label">Processo / Dados Principais</div>
                <div class="col-label">Prazo Judicial</div>
                <div class="col-label">Origem / Parte</div>
                <div class="col-label">Situação</div>
                <div class="col-label">Ações</div>
            </div>

            <div class="triage-list">
                @forelse($demands as $demand)
                    @php
                        $isOverdue     = $demand->source_due_at && \Carbon\Carbon::parse($demand->source_due_at)->isPast();
                        $srcType       = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : $demand->source_type;
                        $tipoLabel     = match($srcType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $srcType ?? '—' };
                        $tipoBg        = match($srcType) { 'injunction' => 'bg-danger text-white', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary text-white' };
                    @endphp
                    <div class="triage-item {{ $isOverdue ? 'overdue' : '' }}" wire:key="triage-{{ $demand->id }}">
                        <div class="triage-main">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                <span class="badge {{ $tipoBg }}" style="font-size:.78rem">{{ $tipoLabel }}</span>
                                <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="process-link">
                                    {{ $demand->source_process_number_masked ?? $demand->source_case_number ?? 'S/N' }}
                                </a>
                                @if($demand->source_case_number)
                                    <span class="badge bg-light text-dark border">Caso {{ $demand->source_case_number }}</span>
                                @endif
                            </div>

                            @if($demand->legalCase?->company_name)
                                <div class="triage-sub"><i class="bi bi-building me-1"></i>{{ $demand->legalCase->company_name }}</div>
                            @endif
                            <div class="triage-sub">
                                <i class="bi bi-people me-1"></i>
                                <x-legal.adverse-party-names :legal-case="$demand->legalCase" :fallback="$demand->opposing_party" />
                            </div>
                            @if($demand->subject)
                                <div class="triage-sub"><i class="bi bi-card-text me-1"></i>{{ Str::limit($demand->subject, 95) }}</div>
                            @endif
                            @if($demand->process_manager)
                                <div class="triage-sub"><i class="bi bi-person-badge me-1"></i>{{ $demand->process_manager }}</div>
                            @endif
                        </div>

                        <div>
                            @if($demand->source_executed_at)
                                <x-legal.due-date-chip :date="$demand->source_due_at" :executedAt="$demand->source_executed_at" />
                            @elseif($demand->source_due_at)
                                <x-legal.due-date-chip :date="$demand->source_due_at" />
                            @else
                                <span class="text-muted small">Sem prazo</span>
                            @endif
                        </div>

                        <div>
                            <div class="triage-sub"><strong>Parte:</strong> {{ $demand->opposing_party ?: 'Não informada' }}</div>
                            <div class="triage-sub"><strong>Origem:</strong> {{ $demand->origin_area_name ?: 'Não informada' }}</div>
                        </div>

                        <div>
                            @if($demand->needs_identity_review)
                                <span class="badge bg-warning text-dark mb-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Revisão de identidade
                                </span>
                            @endif
                            <div class="triage-sub"><x-legal.status-badge :status="$demand->internal_status" /></div>
                            <div class="triage-sub"><strong>Controlador:</strong> {{ $demand->controller?->name ?? 'Não definido' }}</div>
                            @if($demand->first_seen_at)
                                <div class="triage-sub">
                                    Primeira importação: {{ \Carbon\Carbon::parse($demand->first_seen_at)->format('d/m/Y') }}
                                    @if($demand->lastSeenImportBatch)
                                        (batch #{{ $demand->lastSeenImportBatch->id }})
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="triage-actions">
                            @if($confirmIgnoreId === $demand->id)
                                <div class="p-2 border rounded bg-light">
                                    <div class="small fw-semibold mb-1">Confirmar Ignorar?</div>
                                    <textarea class="form-control form-control-sm mb-1" rows="2"
                                              wire:model="confirmIgnoreReason"
                                              placeholder="Motivo (obrigatório)..."></textarea>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-secondary flex-fill" wire:click="cancelIgnore">Cancelar</button>
                                        <button class="btn btn-sm btn-danger flex-fill" wire:click="confirmIgnore">Ignorar</button>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i>Detalhes
                                </a>
                                <button class="btn btn-sm btn-warning" wire:click="startTriage({{ $demand->id }})">
                                    <i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem
                                </button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="showIgnoreConfirm({{ $demand->id }})">
                                    <i class="bi bi-x me-1"></i>Ignorar
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted bg-white border rounded-3">
                        <i class="bi bi-check-circle fs-2 d-block mb-2 opacity-40"></i>
                        Nenhuma demanda aguardando triagem.
                    </div>
                @endforelse
            </div>

            @if($loadedCount < $totalCount)
                <div class="text-center p-3 border-top">
                    <button class="btn btn-outline-secondary btn-sm" wire:click="loadMore">
                        <i class="bi bi-arrow-down me-1"></i>Carregar mais
                        ({{ $totalCount - $loadedCount }} restantes)
                    </button>
                </div>
            @endif
        </div>

    </div>
</div>
