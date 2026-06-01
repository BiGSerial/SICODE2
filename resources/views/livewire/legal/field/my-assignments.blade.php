<div class="lma-page">
    <x-show-loading />

    <style>
        .lma-page {
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        #f6f7fb;
            padding: 1.5rem 0;
        }
        .lma-header {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .22);
            margin-bottom: 1.5rem;
        }
        .lma-kpi {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: .75rem;
            padding: .6rem 1rem;
            min-width: 100px;
            cursor: pointer;
            transition: background .15s;
        }
        .lma-kpi:hover { background: rgba(255,255,255,.18); }
        .lma-kpi .val { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .lma-kpi .lbl { font-size: .72rem; opacity: .8; text-transform: uppercase; letter-spacing: .05em; }
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
        .summary-bar {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .9rem;
            padding: .65rem 1rem;
            box-shadow: 0 8px 20px rgba(15,23,42,.05);
            margin-bottom: 1rem;
        }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15,23,42,.08);
            overflow: hidden;
        }
        .table-card .card-header.text-bg-dark {
            font-size: .88rem;
            padding: .85rem 1.1rem;
            letter-spacing: .01em;
        }
        .assign-item { border-bottom: 1px solid #f1f5f9; padding: .65rem .9rem; }
        .assign-item:last-child { border-bottom: none; }
        .assign-item:hover { background: #f8fafc; }
        .assign-item.correction { border-left: 4px solid #ef4444; }
        .assign-item.overdue { border-left: 4px solid #f97316; }
        .assign-row {
            display: grid;
            grid-template-columns: 1.5fr 1.2fr 1.8fr 1.1fr 1fr auto;
            gap: .65rem .9rem;
            align-items: center;
        }
        .assign-col .lbl {
            font-size: .67rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: .1rem;
        }
        .assign-col .val {
            font-size: .84rem;
            color: #0f172a;
            line-height: 1.25;
        }
        .assign-col .val.muted { color: #475569; }
        .assign-col .val.deadline {
            font-size: .95rem;
            font-weight: 800;
        }
        .deadline-badge {
            font-size: .82rem;
            padding: .35rem .55rem;
            border-radius: .55rem;
        }
        .assign-actions {
            display: flex;
            justify-content: flex-end;
        }
        @media (max-width: 1200px) {
            .assign-row {
                grid-template-columns: 1fr 1fr;
            }
            .assign-actions {
                grid-column: 1 / -1;
                justify-content: flex-start;
            }
        }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="lma-header">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">MÓDULO JURÍDICO</h4>
                    <div class="mb-3 opacity-75" style="font-size:.9rem">Minhas Atribuições — Campo</div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="lma-kpi" wire:click="setTab('pending')">
                            <div class="val text-warning">{{ $kpis['pending'] }}</div>
                            <div class="lbl">Aguardando</div>
                        </div>
                        <div class="lma-kpi" wire:click="setTab('in_progress')">
                            <div class="val" style="color:#93c5fd">{{ $kpis['in_progress'] }}</div>
                            <div class="lbl">Em Andamento</div>
                        </div>
                        <div class="lma-kpi" wire:click="setTab('correction')">
                            <div class="val text-danger">{{ $kpis['correction'] }}</div>
                            <div class="lbl">Para Corrigir</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="filter-card">
                        <label class="form-label">Buscar atribuição</label>
                        <input type="text" class="form-control"
                               wire:model.debounce.400ms="search"
                               placeholder="Nº processo ou assunto..." />
                    </div>
                </div>
            </div>
        </div>

        {{-- ABAS --}}
        <div class="summary-bar d-flex flex-wrap align-items-center gap-1">
            @foreach([
                ['pending',     'Pendentes',     $kpis['pending']],
                ['in_progress', 'Em Andamento',  $kpis['in_progress']],
                ['correction',  'Para Corrigir', $kpis['correction']],
                ['answered',    'Respondidas',   null],
                ['all',         'Todas',         null],
            ] as [$key, $label, $count])
                <button class="btn btn-sm {{ $tab === $key ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="setTab('{{ $key }}')">
                    {{ $label }}
                    @if($count !== null && $count > 0)
                        <span class="badge {{ $key === 'correction' ? 'bg-danger' : 'bg-light text-dark' }} ms-1">{{ $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- CONTEÚDO --}}
        <div class="table-card" wire:loading.class="opacity-50">
            <div class="card-header text-bg-dark fw-bold">
                Jurídico › Minhas Atribuições
            </div>

            @forelse($assignments as $assignment)
                @php
                    $demand       = $assignment->legalDemand;
                    $status       = $assignment->status instanceof \BackedEnum ? $assignment->status->value : $assignment->status;
                    $isOverdue    = $demand?->source_due_at && \Carbon\Carbon::parse($demand->source_due_at)->isPast();
                    $isCorrection = $status === 'returned_for_correction';
                    $srcType      = $demand?->source_type instanceof \BackedEnum ? $demand->source_type->value : ($demand?->source_type ?? null);
                    $tipoBg       = match($srcType) { 'injunction' => 'bg-danger text-white', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary text-white' };
                    $tipoLabel    = match($srcType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $srcType ?? '—' };
                @endphp
                <div class="assign-item {{ $isCorrection ? 'correction' : ($isOverdue ? 'overdue' : '') }}"
                     wire:key="assign-{{ $assignment->id }}">
                    <div class="assign-row">
                        <div class="assign-col">
                            <div class="lbl">Processo</div>
                            <div class="val fw-semibold">{{ $demand?->source_process_number_masked ?? $demand?->source_case_number ?? 'S/N' }}</div>
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                @if($srcType)<span class="badge {{ $tipoBg }}">{{ $tipoLabel }}</span>@endif
                                @if($isCorrection)<span class="badge bg-danger">Correção</span>@endif
                            </div>
                        </div>

                        <div class="assign-col">
                            <div class="lbl">Parte / Empresa</div>
                            <div class="val">{{ $demand?->opposing_party ?? '—' }}</div>
                            <div class="val muted">{{ $demand?->legalCase?->company_name ?? '—' }}</div>
                        </div>

                        <div class="assign-col">
                            <div class="lbl">Descrição</div>
                            <div class="val muted">{{ Str::limit($demand?->description ?: ($demand?->subject ?: 'Sem descrição'), 120) }}</div>
                        </div>

                        <div class="assign-col">
                            <div class="lbl">Enviado em</div>
                            <div class="val">{{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}</div>
                            <div class="val muted">{{ $assignment->sentBy?->name ?? '—' }}</div>
                        </div>

                        <div class="assign-col">
                            <div class="lbl">Prazo resposta</div>
                            @if($assignment->due_at)
                                <div class="val deadline">
                                    <span class="badge bg-warning text-dark deadline-badge">{{ \Carbon\Carbon::parse($assignment->due_at)->format('d/m/Y H:i') }}</span>
                                </div>
                            @else
                                <div class="val muted">Não informado</div>
                            @endif
                        </div>

                        <div class="assign-actions">
                            @if($status === 'sent')
                                <button class="btn btn-sm btn-warning" wire:click="confirmReceipt({{ $assignment->id }})">
                                    <i class="bi bi-check-lg me-1"></i>Confirmar Recebimento
                                </button>
                            @else
                                <a href="{{ route('legal.field.response', $assignment->uuid) }}"
                                   class="btn btn-sm {{ $isCorrection ? 'btn-danger' : 'btn-primary' }}">
                                    <i class="bi bi-pencil me-1"></i>{{ $isCorrection ? 'Corrigir e Reenviar' : 'Abrir e Responder' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                @php
                    $emptyMsg = match($tab) {
                        'pending'     => 'Nenhuma solicitação aguardando aceite.',
                        'in_progress' => 'Nenhuma atribuição em andamento.',
                        'correction'  => 'Nenhuma devolução para corrigir. Continue assim!',
                        'answered'    => 'Nenhuma atribuição respondida.',
                        default       => 'Nenhuma atribuição encontrada.',
                    };
                @endphp
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
                    {{ $emptyMsg }}
                </div>
            @endforelse

            <div class="card-body py-2">
                @if($assignments->hasPages())
                    {{ $assignments->links() }}
                @endif
            </div>
        </div>

    </div>
</div>
