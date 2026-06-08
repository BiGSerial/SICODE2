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
            min-width: 120px;
        }

        .lq-kpi .lq-kpi-val { font-size: 1.35rem; font-weight: 700; line-height: 1; }
        .lq-kpi .lq-kpi-lbl { font-size: .72rem; opacity: .8; text-transform: uppercase; letter-spacing: .05em; }

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
        .badge-soft {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            padding: .35rem .5rem;
            border-radius: .5rem;
        }
    </style>

    <div class="container-fluid">
        <div class="lq-header">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
                    <div class="mb-3 opacity-75" style="font-size:.9rem">Monitor de Subdemandas</div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="lq-kpi" wire:click="$set('scope','open')" style="cursor:pointer;">
                            <div class="lq-kpi-val">{{ $kpis['open'] ?? 0 }}</div>
                            <div class="lq-kpi-lbl">Em aberto</div>
                        </div>
                        <div class="lq-kpi" wire:click="$set('scope','overdue')" style="cursor:pointer;">
                            <div class="lq-kpi-val">{{ $kpis['overdue'] ?? 0 }}</div>
                            <div class="lq-kpi-lbl">Vencidas</div>
                        </div>
                        <div class="lq-kpi" wire:click="$set('scope','today')" style="cursor:pointer;">
                            <div class="lq-kpi-val">{{ $kpis['today'] ?? 0 }}</div>
                            <div class="lq-kpi-lbl">Vence hoje</div>
                        </div>
                        <div class="lq-kpi" wire:click="$set('scope','all')" style="cursor:pointer;">
                            <div class="lq-kpi-val">{{ $kpis['all'] ?? 0 }}</div>
                            <div class="lq-kpi-lbl">Total</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Escopo</label>
                                <select class="form-select form-select-sm" wire:model="scope">
                                    <option value="open">Em aberto</option>
                                    <option value="overdue">Vencidas</option>
                                    <option value="today">Vence hoje</option>
                                    <option value="all">Todas</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Status</label>
                                <select class="form-select form-select-sm" wire:model="status">
                                    <option value="">Todos</option>
                                    <option value="aberta">Aberta</option>
                                    <option value="em_andamento">Em andamento</option>
                                    <option value="aguardando_retorno">Aguardando retorno</option>
                                    <option value="concluida">Concluída</option>
                                    <option value="encerrada_controlador">Encerrada controlador</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Responsável</label>
                                <select class="form-select form-select-sm" wire:model="responsible">
                                    <option value="">Todos</option>
                                    @foreach($responsibles as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Área</label>
                                <input type="text" class="form-control form-control-sm" wire:model.debounce.400ms="area" placeholder="Área">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control form-control-sm" wire:model.debounce.400ms="type" placeholder="subsidy/sentence...">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="filter-card">
                                <label class="form-label">Nº do Caso</label>
                                <input type="text" class="form-control form-control-sm" wire:model.debounce.400ms="process" placeholder="Ex.: 192483">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <button class="btn btn-light btn-sm w-100"
                                    wire:click="$set('scope','open'); $set('status',''); $set('responsible',''); $set('area',''); $set('type',''); $set('process','')">
                                <i class="bi bi-x-circle me-1"></i>Limpar filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header bg-dark text-white fw-semibold">Subdemandas</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Subdemanda</th>
                                <th>Caso</th>
                                <th>Parte adversa</th>
                                <th>Tipo</th>
                                <th>Responsável</th>
                                <th>Canal</th>
                                <th>Área</th>
                                <th>Data da atribuição</th>
                                <th>SLA Subdemanda</th>
                                <th>Prazo Demanda</th>
                                <th>Status</th>
                                <th>Criticidade</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subdemands as $sub)
                                @php
                                    $statusValue = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
                                    $badge = match($statusValue) {
                                        'concluida' => 'bg-success',
                                        'encerrada_controlador' => 'bg-secondary',
                                        'em_andamento' => 'bg-warning text-dark',
                                        'aguardando_retorno' => 'bg-info text-dark',
                                        default => 'bg-primary',
                                    };
                                    $statusLabel = match($statusValue) {
                                        'aberta' => 'Aberta',
                                        'em_andamento' => 'Em andamento',
                                        'aguardando_retorno' => 'Aguardando retorno',
                                        'concluida' => 'Concluída',
                                        'encerrada_controlador' => 'Encerrada pelo controlador',
                                        default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $statusValue)),
                                    };
                                    $tipoValue = $sub->demand?->source_type?->value ?? $sub->demand?->source_type ?? '';
                                    $tipoLabel = match((string) $tipoValue) {
                                        'subsidy' => 'Subsídio',
                                        'sentence' => 'Sentença',
                                        'injunction' => 'Liminar',
                                        default => ((string) $tipoValue !== '' ? \Illuminate\Support\Str::headline((string) $tipoValue) : '—'),
                                    };
                                    $isOverdue = $sub->deadline_at && !in_array($statusValue, ['concluida','encerrada_controlador'], true) && $sub->deadline_at->isPast();
                                    $isDueToday = $sub->deadline_at && !in_array($statusValue, ['concluida','encerrada_controlador'], true) && $sub->deadline_at->isToday();
                                    $demandDueAt = $sub->demand?->source_due_at ? \Carbon\Carbon::parse($sub->demand->source_due_at) : null;
                                    $isDemandOverdue = $demandDueAt ? $demandDueAt->isPast() : false;
                                    $isDemandDueToday = $demandDueAt ? $demandDueAt->isToday() : false;
                                    $demandDeadlineBadge = $isDemandOverdue ? 'bg-danger' : ($isDemandDueToday ? 'bg-warning text-dark' : 'bg-success');
                                    $criticality = $sub->demand?->subdemand_criticality ?? ($isOverdue ? 'high' : ($isDueToday ? 'medium' : 'low'));
                                    $criticalityBadge = match((string) $criticality) {
                                        'high' => 'bg-danger',
                                        'medium' => 'bg-warning text-dark',
                                        default => 'bg-success',
                                    };
                                    $criticalityLabel = match((string) $criticality) {
                                        'high' => 'Alta',
                                        'medium' => 'Média',
                                        default => 'Baixa',
                                    };
                                    $isExternal = (bool) data_get($sub->metadata ?? [], 'external_dispatch', false);
                                    $externalName = (string) data_get($sub->metadata ?? [], 'external_contact_name', '');
                                    $externalEmail = (string) data_get($sub->metadata ?? [], 'external_contact_email', '');
                                @endphp
                                <tr>
                                    <td>#{{ $sub->id }}</td>
                                    <td>{{ $sub->demand?->source_case_number ?? '—' }}</td>
                                    <td>
                                        <x-legal.adverse-party-names :legal-case="$sub->demand?->legalCase" :fallback="$sub->demand?->opposing_party" />
                                    </td>
                                    <td><span class="badge badge-soft">{{ $tipoLabel }}</span></td>
                                    <td>
                                        @if($isExternal)
                                            —
                                        @else
                                            {{ $sub->assignedTo?->name ?? '—' }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $isExternal ? 'bg-info text-dark' : 'badge-soft' }}">
                                            {{ $isExternal ? 'Externo' : 'Interno' }}
                                        </span>
                                    </td>
                                    <td>{{ $sub->assigned_area_name ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-soft">
                                            {{ $sub->created_at ? $sub->created_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $isOverdue ? 'bg-danger' : ($isDueToday ? 'bg-warning text-dark' : 'badge-soft') }}">
                                            {{ $sub->deadline_at ? $sub->deadline_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $demandDueAt ? $demandDeadlineBadge : 'badge-soft' }}">
                                            {{ $demandDueAt ? $demandDueAt->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>
                                    <td><span class="badge {{ $badge }}">{{ $statusLabel }}</span></td>
                                    <td><span class="badge {{ $criticalityBadge }}">{{ $criticalityLabel }}</span></td>
                                    <td class="text-end">
                                        @if($sub->demand?->uuid)
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('legal.demand.detail', $sub->demand->uuid) }}">Abrir demanda</a>
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('legal.subdemand.detail', $sub->uuid) }}">Abrir subdemanda</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="13" class="text-muted">Nenhuma subdemanda encontrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">{{ $subdemands->links() }}</div>
            </div>
        </div>
    </div>
</div>
