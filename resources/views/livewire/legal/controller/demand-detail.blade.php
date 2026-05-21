<div class="ldd-page">
    <x-show-loading />

    <style>
        .ldd-page {
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                        radial-gradient(circle at 90% 10%, #e0f2fe, transparent 35%),
                        #f6f7fb;
            padding: 1.5rem 0;
        }
        .ldd-hero {
            background: linear-gradient(120deg, #0f172a, #1e3a5f 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .22);
            margin-bottom: 1.5rem;
        }
        .ldd-hero .process-number {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: .02em;
            line-height: 1.2;
        }
        .ldd-hero .meta { font-size: .85rem; opacity: .8; }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15,23,42,.07);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .table-card .card-header.text-bg-dark {
            font-size: .88rem;
            padding: .85rem 1.1rem;
            letter-spacing: .01em;
        }
        .table-card .card-body { padding: 1.25rem; }
        .proc-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem 1.2rem;
        }
        .proc-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .9rem 1.2rem;
        }
        .proc-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .65rem;
            padding: .7rem .8rem;
        }
        .proc-label {
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin-bottom: .25rem;
            font-weight: 700;
        }
        .proc-value {
            font-size: .92rem;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.35;
        }
        .timeline-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .timeline-item {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            padding: .5rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .timeline-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .timeline-dot {
            width: .6rem;
            height: .6rem;
            border-radius: 999px;
            background: #1e3a5f;
            margin-top: .35rem;
            flex: 0 0 .6rem;
        }
        .timeline-title {
            font-size: .83rem;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .timeline-date {
            font-size: .94rem;
            color: #0f172a;
            font-weight: 700;
        }
        .timeline-date.deadline-overdue { color: #dc2626; }
        .timeline-date.deadline-soon { color: #d97706; }
        .timeline-date.deadline-ontrack { color: #059669; }
        .timeline-badge {
            display: inline-block;
            font-size: .7rem;
            font-weight: 700;
            padding: .15rem .45rem;
            border-radius: 999px;
            margin-left: .4rem;
            vertical-align: middle;
        }
        .timeline-badge.overdue { background: #fee2e2; color: #991b1b; }
        .timeline-badge.soon { background: #fef3c7; color: #92400e; }
        .timeline-badge.ontrack { background: #dcfce7; color: #166534; }
        .ldd-dt { font-size: .78rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .15rem; }
        .ldd-dd { font-size: .92rem; color: #1f2937; margin-bottom: 0; }
        .action-panel {
            position: sticky;
            top: 1.25rem;
        }
        .locked-banner {
            background: linear-gradient(120deg, #1e293b, #374151);
            color: #f8fafc;
            border-radius: .75rem;
            padding: 1rem 1.25rem;
        }
        .controller-card {
            border: 1px solid #bfdbfe;
            box-shadow: 0 10px 26px rgba(37, 99, 235, .12);
        }
        .controller-highlight {
            background: linear-gradient(90deg, #eff6ff, #f8fbff);
            border: 1px solid #dbeafe;
            border-radius: .7rem;
            padding: .75rem;
        }
        .history-scroll {
            max-height: 360px;
            overflow-y: auto;
            padding-right: .35rem;
        }
        .history-scroll::-webkit-scrollbar { width: 6px; }
        .history-scroll::-webkit-scrollbar-track { background: transparent; }
        .history-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .history-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .assign-timeline {
            position: relative;
            margin: 0;
            padding: .25rem 0 .25rem 1.35rem;
            list-style: none;
        }
        .assign-timeline::before {
            content: "";
            position: absolute;
            left: .45rem;
            top: .2rem;
            bottom: .2rem;
            width: 2px;
            background: linear-gradient(180deg, #cbd5e1 0%, #e2e8f0 100%);
            border-radius: 999px;
        }
        .assign-timeline-item {
            position: relative;
            padding: 0 0 .9rem .5rem;
        }
        .assign-timeline-item:last-child { padding-bottom: 0; }
        .assign-timeline-dot {
            position: absolute;
            left: -1.2rem;
            top: .2rem;
            width: .72rem;
            height: .72rem;
            border-radius: 999px;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #cbd5e1;
            background: #64748b;
        }
        .assign-timeline-dot.success { background: #16a34a; box-shadow: 0 0 0 2px #bbf7d0; }
        .assign-timeline-dot.warn { background: #f59e0b; box-shadow: 0 0 0 2px #fde68a; }
        .assign-timeline-dot.info { background: #2563eb; box-shadow: 0 0 0 2px #bfdbfe; }
        .assign-timeline-title {
            font-size: .9rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .assign-timeline-meta {
            font-size: .82rem;
            color: #64748b;
            margin-top: .15rem;
        }
        .legal-evidence-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
            gap: 10px;
        }
        .legal-evidence-card {
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            background: #fff;
            padding: .55rem;
            text-align: center;
        }
        .legal-evidence-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: .55rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            cursor: pointer;
        }
        .legal-evidence-name {
            display: block;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 992px) {
            .proc-grid, .proc-grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container-fluid">

        {{-- HERO --}}
        <div class="ldd-hero">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div class="flex-grow-1">
                    {{-- Breadcrumb / voltar --}}
                    <div class="mb-2">
                        <a href="{{ route('legal.queue') }}" class="btn btn-sm btn-outline-light btn-sm py-0 px-2"
                           style="font-size:.78rem; opacity:.8">
                            <i class="bi bi-arrow-left me-1"></i>Voltar para Fila
                        </a>
                    </div>

                    {{-- Número + Empresa --}}
                    <div class="process-number">
                        {{ $demand->source_case_number ?? $demand->source_process_number_masked ?? 'Sem número' }}
                    </div>
                    @if($demand->legalCase?->company_name)
                        <div class="meta mt-1">
                            <i class="bi bi-building me-1"></i>{{ $demand->legalCase->company_name }}
                            @if($demand->regional)
                                &bull; {{ $demand->regional }}
                            @endif
                        </div>
                    @endif
                    @if($demand->subject)
                        <div class="mt-1" style="font-size:.88rem; opacity:.85">{{ Str::limit($demand->subject, 100) }}</div>
                    @endif

                    {{-- Badges de status --}}
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @php
                            $srcType  = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : $demand->source_type;
                            $tipoBg   = match($srcType) { 'injunction' => 'bg-danger', 'sentence' => 'bg-warning text-dark', 'subsidy' => 'bg-info text-dark', default => 'bg-secondary' };
                            $tipoLbl  = match($srcType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $srcType ?? '—' };
                            $extBadge = $demand->externalStatusBadge();
                        @endphp
                        <span class="badge {{ $tipoBg }}" style="font-size:.85rem">{{ $tipoLbl }}</span>
                        <x-legal.status-badge :status="$demand->internal_status" size="md" />
                        <span class="badge {{ $extBadge['class'] }} d-inline-flex align-items-center gap-1" style="font-size:.82rem">
                            <i class="bi {{ $extBadge['icon'] }}"></i>
                            {{ $demand->external_flow_status ?? $demand->external_status ?? 'Sem status externo' }}
                        </span>
                        @if($isExternallyClosed)
                            <span class="badge bg-dark border border-secondary" style="font-size:.82rem">
                                <i class="bi bi-lock-fill me-1"></i>Encerrado Externamente
                            </span>
                        @endif
                    </div>
                </div>

                {{-- KPIs de prazo --}}
                <div class="d-flex flex-column gap-2" style="min-width:200px">
                    <div class="p-2 rounded" style="background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15)">
                        <div style="font-size:.72rem; opacity:.8; text-transform:uppercase; letter-spacing:.05em" class="mb-1">
                            Prazo Judicial
                        </div>
                        <x-legal.due-date-chip
                            :date="$demand->source_due_at"
                            :executedAt="$demand->source_executed_at" />
                    </div>
                    @if($demand->source_started_at)
                        <div class="p-2 rounded" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12)">
                            <div style="font-size:.72rem; opacity:.7; text-transform:uppercase; letter-spacing:.05em" class="mb-1">Início</div>
                            <span style="font-size:.88rem">{{ \Carbon\Carbon::parse($demand->source_started_at)->format('d/m/Y') }}</span>
                        </div>
                    @endif
                    @if($demand->source_executed_at)
                        <div class="p-2 rounded" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12)">
                            <div style="font-size:.72rem; opacity:.7; text-transform:uppercase; letter-spacing:.05em" class="mb-1">Cumprido em</div>
                            <span style="font-size:.88rem">{{ \Carbon\Carbon::parse($demand->source_executed_at)->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Banner: encerrado externamente --}}
        @if($isExternallyClosed)
            <div class="alert alert-dark d-flex align-items-center gap-3 mb-3 border-0"
                 style="background:linear-gradient(90deg,#1e293b,#374151); color:#f8fafc; border-radius:.75rem">
                <i class="bi bi-lock-fill fs-4"></i>
                <div>
                    <strong>Demanda encerrada no sistema externo</strong>
                    <div class="small opacity-75 mt-1">
                        Status: {{ $demand->external_flow_status ?? $demand->external_status ?? '—' }}.
                        Ações de despacho estão bloqueadas. Comentários e arquivos ainda são permitidos.
                    </div>
                </div>
            </div>
        @elseif($demand->controller_user_id && $demand->controller_user_id !== auth()->id())
            <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-info-circle-fill"></i>
                Demanda sob responsabilidade de <strong class="ms-1">{{ $demand->controller?->name }}</strong>. Você pode agir mesmo assim.
            </div>
        @endif

        @php
            $sourceTimeline = collect([
                ['key' => 'start', 'label' => 'Início', 'value' => $demand->source_started_at],
                ['key' => 'analysis', 'label' => 'Análise', 'value' => $demand->source_analysis_at],
                ['key' => 'deadline', 'label' => 'Prazo judicial', 'value' => $demand->source_due_at],
                ['key' => 'execution', 'label' => 'Execução', 'value' => $demand->source_executed_at],
                ['key' => 'changed', 'label' => 'Última alteração', 'value' => $demand->source_changed_at],
            ])->filter(fn ($i) => !empty($i['value']))->values();

            $sourceContext = collect([
                ['label' => 'Parte adversa', 'value' => $demand->opposing_party],
                ['label' => 'Gestor do processo', 'value' => $demand->process_manager],
                ['label' => 'Área de origem', 'value' => $demand->origin_area_name],
                ['label' => 'Status processo (externo)', 'value' => $demand->external_status],
                ['label' => 'Status fluxo (externo)', 'value' => $demand->external_flow_status],
                ['label' => 'Descrição', 'value' => $demand->description],
            ])->filter(fn ($i) => filled($i['value']))->values();
        @endphp

        <div class="row g-4">

            {{-- COLUNA ESQUERDA (col-8) --}}
            <div class="col-lg-8">

                {{-- Resumo do Processo --}}
                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-file-text me-2"></i>Resumo do Processo (Fonte)
                    </div>
                    <div class="card-body">
                        <div class="proc-grid-3">
                            <div class="proc-item">
                                <div class="proc-label">Número do Processo</div>
                                <div class="proc-value">{{ $demand->source_process_number_masked ?? 'Não informado' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Número do Caso</div>
                                <div class="proc-value">{{ $demand->source_case_number ?? 'Não informado' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Tipo</div>
                                <div class="proc-value">{{ $tipoLbl ?? 'Não informado' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Status Processo (Externo)</div>
                                <div class="proc-value">{{ $demand->external_status ?? 'Não informado' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Prazo Judicial (Fonte)</div>
                                <div class="proc-value">
                                    <x-legal.due-date-chip :date="$demand->source_due_at" :executedAt="$demand->source_executed_at" />
                                </div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Status Fluxo (Externo)</div>
                                <div class="proc-value">{{ $demand->external_flow_status ?? 'Não informado' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($sourceTimeline->isNotEmpty())
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold">
                            <i class="bi bi-calendar3 me-2"></i>Linha do Tempo da Fonte
                        </div>
                        <div class="card-body">
                            <ul class="timeline-list">
                                @foreach($sourceTimeline as $item)
                                    @php
                                        $isDeadline = $item['key'] === 'deadline';
                                        $dateValue = \Carbon\Carbon::parse($item['value']);
                                        $deadlineClass = '';
                                        $deadlineBadge = null;

                                        if ($isDeadline) {
                                            if ($demand->source_executed_at) {
                                                $deadlineClass = 'deadline-ontrack';
                                                $deadlineBadge = 'Cumprido';
                                            } elseif ($dateValue->isPast()) {
                                                $deadlineClass = 'deadline-overdue';
                                                $deadlineBadge = 'Vencido';
                                            } elseif ($dateValue->lte(now()->addDays(2))) {
                                                $deadlineClass = 'deadline-soon';
                                                $deadlineBadge = 'Atenção';
                                            } else {
                                                $deadlineClass = 'deadline-ontrack';
                                                $deadlineBadge = 'No prazo';
                                            }
                                        }
                                    @endphp
                                    <li class="timeline-item">
                                        <span class="timeline-dot"></span>
                                        <div>
                                            <div class="timeline-title">{{ $item['label'] }}</div>
                                            <div class="timeline-date {{ $deadlineClass }}">
                                                {{ $dateValue->format('d/m/Y H:i') }}
                                                @if($deadlineBadge)
                                                    <span class="timeline-badge {{ str_replace('deadline-', '', $deadlineClass) }}">{{ $deadlineBadge }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if($sourceContext->isNotEmpty())
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold">
                            <i class="bi bi-journal-text me-2"></i>Contexto do Processo (Fonte)
                        </div>
                        <div class="card-body">
                            <div class="proc-grid">
                                @foreach($sourceContext as $ctx)
                                    <div class="proc-item">
                                        <div class="proc-label">{{ $ctx['label'] }}</div>
                                        <div class="proc-value">{{ $ctx['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Atribuição Atual --}}
                @if($currentAssignment)
                    <div class="table-card controller-card">
                        <div class="card-header text-bg-dark fw-bold">
                            <i class="bi bi-person-check me-2"></i>Atribuição Atual
                        </div>
                        <div class="card-body">
                            <div class="controller-highlight mb-3">
                                <div class="small text-muted mb-1">Executante atual</div>
                                <div class="fw-semibold">
                                    <i class="bi bi-person-fill me-1"></i>
                                    {{ $currentAssignment->toUser?->name ?? 'Usuário não definido' }}
                                </div>
                                <div class="small text-muted mt-1">
                                    Enviado por {{ $currentAssignment->sentBy?->name ?? '—' }}
                                    em {{ \Carbon\Carbon::parse($currentAssignment->sent_at ?? $currentAssignment->created_at)->format('d/m/Y H:i') }}
                                </div>
                                @if($currentAssignment->due_at)
                                    <div class="mt-2">
                                        <span class="badge bg-warning text-dark">
                                            SLA interno: {{ \Carbon\Carbon::parse($currentAssignment->due_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-grow-1">
                                    <div class="mt-1">
                                        <x-legal.status-badge :status="$currentAssignment->status" />
                                    </div>
                                    @if($currentAssignment->message)
                                        <blockquote class="small mt-2 border-start border-2 ps-3 text-muted">
                                            "{{ $currentAssignment->message }}"
                                        </blockquote>
                                    @endif
                                </div>
                            </div>

                            @if($currentAssignment->response_summary)
                                <hr>
                                <div class="small fw-semibold text-muted mb-1">Resposta do Executante</div>
                                <p class="mb-1">{{ $currentAssignment->response_summary }}</p>
                                @if($currentAssignment->answered_at)
                                    <div class="small text-muted">
                                        Respondido em {{ \Carbon\Carbon::parse($currentAssignment->answered_at)->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Histórico de Eventos --}}
                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-clock-history me-2"></i>Histórico de Eventos
                    </div>
                    <div class="card-body">
                        <div class="history-scroll">
                            <ol class="assign-timeline">
                                @forelse($demand->events->sortByDesc('occurred_at') as $event)
                                    @php
                                        $eventType = (string) ($event->event_type ?? '');
                                        $dotClass = str_contains($eventType, 'closed') ? 'success'
                                            : (str_contains($eventType, 'returned') || str_contains($eventType, 'correction') ? 'warn'
                                            : (str_contains($eventType, 'sent') || str_contains($eventType, 'received') ? 'info' : ''));
                                        $when = $event->occurred_at ?? $event->created_at;
                                    @endphp
                                    <li class="assign-timeline-item">
                                        <span class="assign-timeline-dot {{ $dotClass }}"></span>
                                        <div class="assign-timeline-title">
                                            {{ $event->description ?: \Illuminate\Support\Str::headline(str_replace('_', ' ', $eventType)) }}
                                        </div>
                                        <div class="assign-timeline-meta">
                                            {{ $when ? \Carbon\Carbon::parse($when)->format('d/m/Y H:i') : '—' }}
                                            @if($event->actor?->name)
                                                · {{ $event->actor->name }}
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                    <li class="assign-timeline-item">
                                        <span class="assign-timeline-dot"></span>
                                        <div class="assign-timeline-title">Sem eventos registrados</div>
                                    </li>
                                @endforelse
                            </ol>
                        </div>
                    </div>
                </div>

                {{-- Comentários --}}
                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-chat me-2"></i>Comentários
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <textarea class="form-control mb-2" rows="3" wire:model="newComment"
                                      placeholder="Adicionar comentário..."></textarea>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select form-select-sm" style="width:220px" wire:model="commentVisibility">
                                    <option value="controller">🔒 Interno (só controlador)</option>
                                    <option value="shared">👁 Compartilhado (executante vê)</option>
                                </select>
                                <button class="btn btn-sm btn-primary" wire:click="addComment">Comentar</button>
                            </div>
                        </div>

                        @forelse($demand->comments->sortByDesc('created_at') as $comment)
                            <div class="d-flex gap-2 mb-3">
                                <div class="flex-grow-1 bg-light rounded p-2">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong class="small">{{ $comment->user?->name ?? '—' }}</strong>
                                        <span class="text-muted small">{{ \Carbon\Carbon::parse($comment->created_at)->format('d/m/Y H:i') }}</span>
                                        @if(($comment->visibility ?? 'controller') === 'controller')
                                            <span class="badge bg-secondary small">🔒 Interno</span>
                                        @else
                                            <span class="badge bg-info text-white small">👁 Compartilhado</span>
                                        @endif
                                    </div>
                                    <p class="mb-0 small">{{ $comment->comment }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small">Nenhum comentário.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- COLUNA DIREITA (col-4) --}}
            <div class="col-lg-4">
                <div class="action-panel">

                    {{-- Painel de Ação --}}
                    <div class="table-card">
                        <div class="card-header fw-bold {{ $isExternallyClosed ? 'bg-dark text-white' : 'text-bg-dark' }}">
                            <i class="bi bi-lightning-charge me-2"></i>Painel de Ação
                            @if($isExternallyClosed)
                                <i class="bi bi-lock-fill ms-2 opacity-75"></i>
                            @endif
                        </div>
                        <div class="card-body">

                            @if($isExternallyClosed)
                                {{-- BLOQUEADO: encerrado externamente --}}
                                <div class="locked-banner mb-3">
                                    <div class="fw-semibold mb-1">
                                        <i class="bi bi-lock-fill me-2"></i>Encerrado no sistema externo
                                    </div>
                                    <div class="small opacity-75">
                                        {{ $demand->external_flow_status ?? $demand->external_status ?? '—' }}
                                    </div>
                                    @if($demand->source_executed_at)
                                        <div class="small opacity-75 mt-1">
                                            Cumprido em {{ \Carbon\Carbon::parse($demand->source_executed_at)->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </div>
                                <p class="small text-muted mb-0">
                                    Despachos e atribuições estão bloqueados. Use os comentários e arquivos para registrar informações adicionais.
                                </p>

                            @else
                                {{-- ATIVO: ações disponíveis por status --}}
                                @switch($statusValue)
                                    @case('new_imported')
                                        <p class="text-muted small">Esta demanda ainda não entrou em triagem.</p>
                                        <button class="btn btn-warning w-100" wire:click="startTriage">
                                            <i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem
                                        </button>
                                        @break

                                    @case('triage')
                                    @case('waiting_controller_action')
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary" wire:click="$toggle('showAssignForm')">
                                                <i class="bi bi-send me-1"></i>Enviar para Campo
                                            </button>
                                            <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')">
                                                <i class="bi bi-lock me-1"></i>Fechar Internamente
                                            </button>
                                        </div>
                                        @break

                                    @case('sent_to_field')
                                    @case('field_received')
                                    @case('waiting_field_response')
                                        <div class="alert alert-info small mb-2">
                                            <i class="bi bi-hourglass me-1"></i>
                                            Aguardando retorno de
                                            <strong>{{ $currentAssignment?->toUser?->name ?? 'executante' }}</strong>.
                                        </div>
                                        @break

                                    @case('returned_by_field')
                                    @case('under_controller_review')
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-success" wire:click="approveReturn">
                                                <i class="bi bi-check2-all me-1"></i>Aprovar Retorno
                                            </button>
                                            <button class="btn btn-outline-warning" wire:click="$toggle('showReturnForm')">
                                                <i class="bi bi-arrow-return-left me-1"></i>Devolver para Correção
                                            </button>
                                            <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')">
                                                <i class="bi bi-lock me-1"></i>Fechar Internamente
                                            </button>
                                        </div>
                                        @break

                                    @case('ready_to_close_external')
                                        <button class="btn btn-success w-100" wire:click="$toggle('showCloseForm')">
                                            <i class="bi bi-check-circle me-1"></i>Confirmar Fechamento Externo
                                        </button>
                                        @break

                                    @case('closed_internal')
                                    @case('closed_external')
                                        <div class="alert alert-success small mb-2">
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            Demanda encerrada internamente.
                                            @if($demand->closed_at)
                                                Em {{ \Carbon\Carbon::parse($demand->closed_at)->format('d/m/Y') }}.
                                            @endif
                                        </div>
                                        @can('legal.demands.review')
                                            <button class="btn btn-outline-secondary w-100 btn-sm" wire:click="reopen">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Reabrir Demanda
                                            </button>
                                        @endcan
                                        @break

                                    @case('cancelled')
                                    @case('ignored')
                                        <button class="btn btn-outline-secondary w-100" wire:click="reopen">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Reabrir Demanda
                                        </button>
                                        @break

                                    @default
                                        <div class="alert alert-secondary small mb-0">Status não mapeado.</div>
                                @endswitch

                                {{-- Formulário: Enviar para Campo --}}
                                @if($showAssignForm)
                                    <hr>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Executante *</label>
                                        <select class="form-select form-select-sm" wire:model="assignToUserId">
                                            <option value="">Selecionar...</option>
                                            @foreach($fieldUsers as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Mensagem</label>
                                        <textarea class="form-control form-control-sm" rows="3" wire:model="assignMessage"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Prazo interno (data e hora)</label>
                                        <input type="datetime-local" class="form-control form-control-sm" wire:model="assignDueAt" />
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showAssignForm', false)">Cancelar</button>
                                        <button class="btn btn-sm btn-primary flex-fill" wire:click="sendToField">Enviar</button>
                                    </div>
                                @endif

                                {{-- Formulário: Fechar Demanda --}}
                                @if($showCloseForm)
                                    <hr>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Tipo de fechamento</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" wire:model="closureType" value="internal" id="closeInt" />
                                                <label class="form-check-label small" for="closeInt">Interno (SICODE)</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" wire:model="closureType" value="external" id="closeExt" />
                                                <label class="form-check-label small" for="closeExt">Externo (sistema jurídico)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Motivo *</label>
                                        <textarea class="form-control form-control-sm" rows="3" wire:model="closureReason"
                                                  placeholder="Mín. 10 caracteres"></textarea>
                                    </div>
                                    @if($closureType === 'external')
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Protocolo externo *</label>
                                            <input type="text" class="form-control form-control-sm" wire:model="externalProtocol" />
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Data de fechamento externo</label>
                                            <input type="date" class="form-control form-control-sm" wire:model="externalClosedAt" />
                                        </div>
                                    @endif
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showCloseForm', false)">Cancelar</button>
                                        <button class="btn btn-sm btn-dark flex-fill" wire:click="closeDemand">Confirmar Fechamento</button>
                                    </div>
                                @endif

                                {{-- Formulário: Devolver para Correção --}}
                                @if($showReturnForm)
                                    <hr>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Motivo da devolução *</label>
                                        <textarea class="form-control form-control-sm" rows="3" wire:model="returnReason"
                                                  placeholder="Descreva o que precisa ser corrigido (mín. 10 caracteres)"></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showReturnForm', false)">Cancelar</button>
                                        <button class="btn btn-sm btn-warning flex-fill" wire:click="returnForCorrection">Devolver</button>
                                    </div>
                                @endif
                            @endif

                        </div>
                    </div>

                    {{-- Caso Legal --}}
                    @if($demand->legalCase)
                        <div class="table-card">
                            <div class="card-header text-bg-dark fw-bold">
                                <i class="bi bi-building me-2"></i>Caso Legal
                            </div>
                            <div class="card-body">
                                <dl class="mb-0">
                                    <div class="ldd-dt">Empresa</div>
                                    <div class="ldd-dd mb-2">{{ $demand->legalCase->company_name ?? '—' }}</div>
                                    <div class="ldd-dt">Responsável Jurídico</div>
                                    <div class="ldd-dd mb-2">{{ $demand->legalCase->legal_responsible_name ?? '—' }}</div>
                                    <div class="ldd-dt">Escritório</div>
                                    <div class="ldd-dd mb-2">{{ $demand->legalCase->law_firm_name ?? '—' }}</div>
                                    <div class="ldd-dt">Primeira aparição</div>
                                    <div class="ldd-dd">{{ $demand->legalCase->first_seen_at ? \Carbon\Carbon::parse($demand->legalCase->first_seen_at)->format('d/m/Y') : '—' }}</div>
                                </dl>
                            </div>
                        </div>
                    @endif

                    {{-- Arquivos --}}
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold">
                            <i class="bi bi-paperclip me-2"></i>Arquivos
                        </div>
                        <div class="card-body">
                            @php
                                $controllerFiles = $demand->files->where('removed_at', null)->where('visibility', 'controller');
                                $sharedFiles     = $demand->files->where('removed_at', null)->where('visibility', 'shared');
                                $imageExts       = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'tiff', 'webp'];
                                $activeFiles     = $demand->files->where('removed_at', null)->values();
                                $imageFiles      = $activeFiles->filter(function ($file) use ($imageExts) {
                                    $name = (string) ($file->original_name ?? $file->file_name ?? $file->path ?? $file->file_path ?? '');
                                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                    $mime = strtolower((string) ($file->mime_type ?? ''));
                                    return in_array($ext, $imageExts, true) || str_starts_with($mime, 'image/');
                                })->values();
                                $otherFiles      = $activeFiles->filter(function ($file) use ($imageFiles) {
                                    return !$imageFiles->contains('id', $file->id);
                                })->values();
                            @endphp

                            @if($imageFiles->isNotEmpty())
                                <div class="small fw-semibold text-muted mb-2">🖼️ Imagens</div>
                                <div class="legal-evidence-grid mb-3">
                                    @foreach($imageFiles as $index => $file)
                                        @php
                                            $filePath = $file->path ?? $file->file_path ?? null;
                                            $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                            $name = $file->original_name ?? ($filePath ? basename($filePath) : 'Imagem');
                                        @endphp
                                        @if($fileUrl)
                                            <div class="legal-evidence-card">
                                                <img src="{{ $fileUrl }}"
                                                     class="legal-evidence-thumb"
                                                     alt="{{ $name }}"
                                                     data-bs-toggle="modal"
                                                     data-bs-target="#legalControllerFilesModal"
                                                     data-carousel-slide="{{ $index }}">
                                                <div class="small text-muted legal-evidence-name mt-2" title="{{ $name }}">{{ $name }}</div>
                                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="bi bi-download me-1"></i>Baixar
                                                </a>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if($otherFiles->isNotEmpty())
                                <div class="small fw-semibold text-muted mb-2">📄 Arquivos</div>
                                <ul class="list-group mb-2">
                                    @foreach($otherFiles as $file)
                                        @php
                                            $filePath = $file->path ?? $file->file_path ?? null;
                                            $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                            $name = $file->original_name ?? ($filePath ? basename($filePath) : 'Arquivo');
                                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                            $isControllerOnly = ($file->visibility ?? null) === 'controller';
                                        @endphp
                                        @if($fileUrl)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="legal-evidence-name" title="{{ $name }}">{{ $name }}</div>
                                                    <small class="text-muted">{{ strtoupper($ext ?: '-') }} · {{ $isControllerOnly ? 'Interno' : 'Compartilhado' }}</small>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download me-1"></i>Baixar
                                                    </a>
                                                    @if(($file->uploaded_by ?? null) === auth()->id())
                                                        <button class="btn btn-sm btn-outline-danger" wire:click="removeFile({{ $file->id }})">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif

                            @if($demand->files->where('removed_at', null)->isEmpty())
                                <p class="text-muted small">Nenhum arquivo anexado.</p>
                            @endif

                            <hr class="my-2">
                            <div class="mb-2">
                                <input type="file" class="form-control form-control-sm" wire:model="uploadFile" />
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select form-select-sm" wire:model="fileVisibility">
                                    <option value="controller">🔒 Interno</option>
                                    <option value="shared">👁 Compartilhado</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" wire:click="uploadFile"
                                        wire:loading.attr="disabled">
                                    <i class="bi bi-upload"></i>
                                </button>
                            </div>
                            <div class="small text-muted mt-1">PDF, JPG, PNG, DOCX, XLSX — máx. 10MB</div>
                        </div>
                    </div>

                    @if($imageFiles->isNotEmpty())
                        <div class="modal fade" id="legalControllerFilesModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Visualização de Imagens</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="legalControllerFilesCarousel" class="carousel slide" data-bs-ride="false">
                                            <div class="carousel-inner">
                                                @foreach($imageFiles as $index => $file)
                                                    @php
                                                        $filePath = $file->path ?? $file->file_path ?? null;
                                                        $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                                    @endphp
                                                    @if($fileUrl)
                                                        <div class="carousel-item @if($index === 0) active @endif">
                                                            <div class="text-center">
                                                                <img src="{{ $fileUrl }}" class="img-fluid rounded border"
                                                                     alt="{{ $file->original_name ?? basename($filePath) }}"
                                                                     style="max-height:70vh; object-fit:contain;">
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                                <div class="small text-muted">{{ $file->original_name ?? basename($filePath) }}</div>
                                                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-primary">
                                                                    <i class="bi bi-download me-1"></i>Baixar
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            @if($imageFiles->count() > 1)
                                                <button class="carousel-control-prev" type="button" data-bs-target="#legalControllerFilesCarousel" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Anterior</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#legalControllerFilesCarousel" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Próximo</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-carousel-slide]');
            if (!trigger) return;

            const index = parseInt(trigger.getAttribute('data-carousel-slide') || '0', 10);
            const carouselEl = document.querySelector('#legalControllerFilesCarousel');
            if (!carouselEl || typeof bootstrap === 'undefined') return;

            const carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, { interval: false });
            carousel.to(index);
        });
    </script>
</div>
