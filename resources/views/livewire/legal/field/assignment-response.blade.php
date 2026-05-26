<div class="ldd-page">
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
        .hero-title {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: .35rem;
        }
        .hero-subtitle {
            font-size: .95rem;
            opacity: .85;
            margin-bottom: .45rem;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .hero-note {
            font-size: .92rem;
            opacity: .9;
        }
        .hero-sla-card {
            min-width: 260px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: .9rem;
            padding: .8rem .95rem;
        }
        .hero-sla-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            opacity: .85;
            margin-bottom: .25rem;
        }
        .hero-sla-date {
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.2;
        }
        .hero-sla-meta {
            font-size: .82rem;
            opacity: .88;
            margin-top: .25rem;
        }
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
        .action-panel {
            position: sticky;
            top: 1rem;
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
        .assign-timeline-wrap {
            max-height: 300px;
            overflow-y: auto;
            padding-right: .35rem;
        }
        .assign-timeline-wrap::-webkit-scrollbar {
            width: 6px;
        }
        .assign-timeline-wrap::-webkit-scrollbar-track {
            background: transparent;
        }
        .assign-timeline-wrap::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        .assign-timeline-wrap::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
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
        .assign-timeline-item:last-child {
            padding-bottom: 0;
        }
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
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 12px;
        }
        .legal-evidence-card {
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            background: #fff;
            padding: .6rem;
            text-align: center;
        }
        .legal-evidence-thumb {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: .55rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            cursor: pointer;
        }
        .legal-evidence-name {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        .controller-message {
            border-left: 4px solid #2563eb;
            background: #f8fafc;
            border-radius: .5rem;
            padding: .7rem .8rem;
        }
        .upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .upload-item {
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            background: #fff;
            overflow: hidden;
        }
        .upload-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .upload-placeholder {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: .82rem;
            font-weight: 700;
        }
        @media (max-width: 992px) {
            .proc-grid { grid-template-columns: 1fr; }
            .proc-grid-3 { grid-template-columns: 1fr; }
            .action-panel { position: static; }
        }
    </style>

    <div class="container-fluid">
        @php
            $sourceTypeEnum = $demand->source_type instanceof \App\Enum\LegalDemandSourceType
                ? $demand->source_type
                : (\App\Enum\LegalDemandSourceType::tryFrom((string) ($demand->source_type ?? '')) ?: null);
            $sourceTypeLabel = $sourceTypeEnum?->label() ?? 'Não informado';
            $sourceTypeClass = $sourceTypeEnum?->badgeClass() ?? 'badge bg-secondary';

            $demandStatusEnum = $demand->internal_status instanceof \App\Enum\LegalDemandInternalStatus
                ? $demand->internal_status
                : (\App\Enum\LegalDemandInternalStatus::tryFrom((string) ($demand->internal_status ?? '')) ?: null);
            $demandStatusLabel = $demandStatusEnum?->label() ?? 'Sem status';
            $demandStatusClass = $demandStatusEnum?->badgeClass() ?? 'badge bg-secondary';

            $assignStatusEnum = $assignment->status instanceof \App\Enum\LegalDemandAssignmentStatus
                ? $assignment->status
                : (\App\Enum\LegalDemandAssignmentStatus::tryFrom((string) ($assignment->status ?? '')) ?: null);
            $assignStatus = $assignStatusEnum?->value ?? (string) ($assignment->status ?? '');
            $assignStatusLabel = $assignStatusEnum?->label() ?? 'Sem status';
            $assignStatusClass = $assignStatusEnum?->badgeClass() ?? 'badge bg-secondary';
            $controllerDueAt = $assignment->due_at ? \Carbon\Carbon::parse($assignment->due_at) : null;
            $isControllerOverdue = $controllerDueAt ? $controllerDueAt->isPast() : false;
            $externalAnsweredName = data_get($assignment->metadata ?? [], 'external_executor_name');
            $executorName = $externalAnsweredName
                ?? $assignment->toUser?->name
                ?? ($externalAccess ? 'Executante externo (nome será informado no envio)' : 'Executante não identificado');

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

            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'tiff', 'webp'];
            $sharedImages = $sharedFiles->filter(function ($file) use ($imageExts) {
                $name = (string) ($file->original_name ?? $file->file_name ?? $file->path ?? $file->file_path ?? '');
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $mime = strtolower((string) ($file->mime_type ?? ''));
                return in_array($ext, $imageExts, true) || str_starts_with($mime, 'image/');
            })->values();
            $sharedOthers = $sharedFiles->filter(function ($file) use ($sharedImages) {
                return !$sharedImages->contains('id', $file->id);
            })->values();
        @endphp

        <div class="ldd-hero">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div class="flex-grow-1">
                    @unless($externalAccess)
                        <div class="mb-2">
                            <a href="{{ route('legal.field.queue') }}" class="btn btn-sm btn-outline-light btn-sm py-0 px-2" style="font-size:.78rem; opacity:.8">
                                <i class="bi bi-arrow-left me-1"></i>Voltar para Minhas Atribuições
                            </a>
                        </div>
                    @endunless
                    <div class="hero-title">
                        Processo {{ $demand->source_process_number_masked ?? 'Não informado' }}
                        @if($demand->legalCase?->company_name)
                            — {{ $demand->legalCase->company_name }}
                        @endif
                    </div>
                    <div class="hero-subtitle">
                        Caso {{ $demand->source_case_number ?? 'Não informado' }} · {{ $sourceTypeLabel }}
                    </div>
                    <div class="hero-note">
                        Detalhamento da demanda, interação com o controlador e resposta do campo.
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="{{ $sourceTypeClass }}">{{ $sourceTypeLabel }}</span>
                        <x-legal.due-date-chip :date="$demand->source_due_at" />
                        <span class="{{ $demandStatusClass }}">{{ $demandStatusLabel }}</span>
                        <span class="{{ $assignStatusClass }}">Atribuição: {{ $assignStatusLabel }}</span>
                    </div>
                </div>
                <div class="hero-sla-card">
                    <div class="hero-sla-label">Prazo SLA do Controlador</div>
                    @if($controllerDueAt)
                        <div class="hero-sla-date">{{ $controllerDueAt->format('d/m/Y H:i') }}</div>
                        <div class="hero-sla-meta">
                            @if($isControllerOverdue)
                                <span class="badge bg-danger">Vencido</span>
                                {{ $controllerDueAt->diffForHumans(now(), ['parts' => 2, 'short' => false]) }}
                            @else
                                <span class="badge bg-success">No prazo</span>
                                vence {{ $controllerDueAt->diffForHumans() }}
                            @endif
                        </div>
                    @else
                        <div class="hero-sla-date">Não informado</div>
                        <div class="hero-sla-meta">O controlador não definiu SLA para esta atribuição.</div>
                    @endif
                </div>
            </div>
        </div>

    <div class="row g-4">
        {{-- Coluna Esquerda (col-7) --}}
        <div class="col-lg-7">

            {{-- Solicitação do Controlador --}}
            <div class="table-card controller-card">
                <div class="card-header text-bg-dark fw-bold">
                    <i class="bi bi-megaphone me-2"></i>Solicitação do Controlador
                </div>
                <div class="card-body">
                    <div class="controller-highlight mb-3">
                        <div class="small text-muted mb-1">Controlador responsável</div>
                        <div class="fw-semibold">{{ $assignment->sentBy?->name ?? '—' }}</div>
                        <div class="small text-muted mt-1">
                            Encaminhado em {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    <div class="proc-grid-3 mb-3">
                        <div class="proc-item">
                            <div class="proc-label">Quem executa esta demanda</div>
                            <div class="proc-value">{{ $executorName }}</div>
                        </div>
                        <div class="proc-item">
                            <div class="proc-label">Data limite para resposta</div>
                            <div class="proc-value">
                                @if($controllerDueAt)
                                    {{ $controllerDueAt->format('d/m/Y H:i') }}
                                    @if($isControllerOverdue)
                                        <span class="badge bg-danger ms-1">Prazo vencido</span>
                                    @else
                                        <span class="badge bg-success ms-1">No prazo</span>
                                    @endif
                                @else
                                    Não informada
                                @endif
                            </div>
                        </div>
                        <div class="proc-item">
                            <div class="proc-label">Status da atribuição</div>
                            <div class="proc-value"><span class="{{ $assignStatusClass }}">{{ $assignStatusLabel }}</span></div>
                        </div>
                    </div>

                    @if($assignment->message)
                        <div class="controller-message mb-2">
                            <div class="small text-muted mb-1">Informações solicitadas pelo controlador</div>
                            <p class="mb-0">{{ $assignment->message }}</p>
                        </div>
                    @else
                        <div class="controller-message mb-2">
                            <div class="small text-muted mb-1">Informações solicitadas pelo controlador</div>
                            <p class="mb-0 text-muted">Sem orientação textual registrada.</p>
                        </div>
                    @endif
                    @if($assignStatus === 'returned_for_correction' && $assignment->response_summary)
                        <hr>
                        <div class="alert alert-danger small mb-0">
                            <div class="fw-semibold mb-1">Sua resposta anterior:</div>
                            <p class="mb-2">{{ $assignment->response_summary }}</p>
                            @if($assignment->correction_note)
                                <div class="fw-semibold text-danger">Motivo da devolução:</div>
                                <p class="mb-0">"{{ $assignment->correction_note }}"</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dados do Processo --}}
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
                            <div class="proc-value">{{ $sourceTypeLabel }}</div>
                        </div>
                        <div class="proc-item">
                            <div class="proc-label">Status Processo (Externo)</div>
                            <div class="proc-value">{{ $demand->external_status ?? 'Não informado' }}</div>
                        </div>
                        <div class="proc-item">
                            <div class="proc-label">Prazo Judicial (Fonte)</div>
                            <div class="proc-value"><x-legal.due-date-chip :date="$demand->source_due_at" /></div>
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

            {{-- Arquivos Compartilhados --}}
            <div class="table-card">
                <div class="card-header text-bg-dark fw-bold">
                    <i class="bi bi-paperclip me-2"></i>Documentos Compartilhados
                </div>
                <div class="card-body">
                    @if($sharedFiles->isEmpty())
                        <p class="text-muted small mb-0">Nenhum documento foi compartilhado para esta tarefa.</p>
                    @else
                        @if($sharedImages->isNotEmpty())
                            <div class="legal-evidence-grid mb-3">
                                @foreach($sharedImages as $index => $file)
                                    @php
                                        $filePath = $file->path ?? $file->file_path ?? null;
                                        $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                    @endphp
                                    @if($fileUrl)
                                        <div class="legal-evidence-card">
                                            <img src="{{ $fileUrl }}"
                                                 class="legal-evidence-thumb"
                                                 alt="{{ $file->original_name ?? basename($filePath) }}"
                                                 data-bs-toggle="modal"
                                                 data-bs-target="#legalSharedFilesModal"
                                                 data-carousel-slide="{{ $index }}">
                                            <div class="small text-muted legal-evidence-name mt-2"
                                                 title="{{ $file->original_name ?? basename($filePath) }}">
                                                {{ $file->original_name ?? basename($filePath) }}
                                            </div>
                                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="bi bi-download me-1"></i>Baixar
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        @if($sharedOthers->isNotEmpty())
                            <ul class="list-group">
                                @foreach($sharedOthers as $file)
                                    @php
                                        $filePath = $file->path ?? $file->file_path ?? null;
                                        $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                        $name = $file->original_name ?? ($filePath ? basename($filePath) : 'Arquivo');
                                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                    @endphp
                                    @if($fileUrl)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-file-earmark"></i>
                                                <div>
                                                    <div class="legal-evidence-name" title="{{ $name }}">{{ $name }}</div>
                                                    <small class="text-muted">{{ strtoupper($ext ?: '-') }}</small>
                                                </div>
                                            </div>
                                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download me-1"></i>Baixar
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>

            @if($sharedImages->isNotEmpty())
                <div class="modal fade" id="legalSharedFilesModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Visualização de Imagens</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="legalSharedFilesCarousel" class="carousel slide" data-bs-ride="false">
                                    <div class="carousel-inner">
                                        @foreach($sharedImages as $index => $file)
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
                                    @if($sharedImages->count() > 1)
                                        <button class="carousel-control-prev" type="button" data-bs-target="#legalSharedFilesCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Anterior</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#legalSharedFilesCarousel" data-bs-slide="next">
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

            {{-- Histórico desta Atribuição --}}
            <div class="table-card mb-0">
                <div class="card-header text-bg-dark fw-bold">
                    <i class="bi bi-clock-history me-2"></i>Histórico da Atribuição
                </div>
                <div class="card-body">
                    <div class="assign-timeline-wrap">
                        <ol class="assign-timeline">
                            <li class="assign-timeline-item">
                                <span class="assign-timeline-dot success"></span>
                                <div class="assign-timeline-title">Atribuição enviada</div>
                                <div class="assign-timeline-meta">
                                    {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                                    · {{ $assignment->sentBy?->name ?? 'Controlador' }}
                                </div>
                            </li>

                            @if($assignment->received_at)
                                <li class="assign-timeline-item">
                                    <span class="assign-timeline-dot info"></span>
                                    <div class="assign-timeline-title">Recebimento confirmado</div>
                                    <div class="assign-timeline-meta">
                                        {{ \Carbon\Carbon::parse($assignment->received_at)->format('d/m/Y H:i') }}
                                        · {{ $executorName }}
                                    </div>
                                </li>
                            @endif

                            @if($assignStatus === 'returned_for_correction')
                                <li class="assign-timeline-item">
                                    <span class="assign-timeline-dot warn"></span>
                                    <div class="assign-timeline-title">Devolvida para correção</div>
                                    <div class="assign-timeline-meta">
                                        {{ \Carbon\Carbon::parse($assignment->updated_at)->format('d/m/Y H:i') }}
                                        · Controlador
                                    </div>
                                </li>
                            @endif

                            @if(in_array($assignStatus, ['answered', 'returned_to_controller']))
                                <li class="assign-timeline-item">
                                    <span class="assign-timeline-dot success"></span>
                                    <div class="assign-timeline-title">Resposta enviada</div>
                                    <div class="assign-timeline-meta">
                                        {{ $assignment->answered_at ? \Carbon\Carbon::parse($assignment->answered_at)->format('d/m/Y H:i') : \Carbon\Carbon::parse($assignment->updated_at)->format('d/m/Y H:i') }}
                                        · {{ $executorName }}
                                    </div>
                                </li>
                            @else
                                <li class="assign-timeline-item">
                                    <span class="assign-timeline-dot warn"></span>
                                    <div class="assign-timeline-title">Aguardando sua resposta</div>
                                    <div class="assign-timeline-meta">A demanda permanece pendente de retorno.</div>
                                </li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coluna Direita (col-5) — Formulário de Resposta --}}
        <div class="col-lg-5">
            <div class="table-card">
                <div class="card-header text-bg-dark fw-bold">
                    <i class="bi bi-cloud-upload me-2"></i>Anexar Arquivos da Tarefa
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                        <div class="form-text">Pré-carga antes do salvamento. Máx. 10MB por arquivo.</div>
                    </div>

                    @if(!empty($uploadFiles))
                        <div class="upload-grid mb-3">
                            @foreach($uploadFiles as $i => $file)
                                @php
                                    $mime = strtolower((string) $file->getMimeType());
                                    $isImage = str_starts_with($mime, 'image/');
                                @endphp
                                <div class="upload-item">
                                    @if($isImage)
                                        <img src="{{ $file->temporaryUrl() }}" class="upload-thumb" alt="{{ $file->getClientOriginalName() }}">
                                    @else
                                        <div class="upload-placeholder">
                                            <i class="bi bi-file-earmark me-1"></i>{{ strtoupper(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'ARQ') }}
                                        </div>
                                    @endif
                                    <div class="p-2">
                                        <div class="small text-truncate" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</div>
                                        <div class="small text-muted mb-2">{{ round($file->getSize() / 1024) }} KB</div>
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100" wire:click="removeUploadFile({{ $i }})">
                                            Remover
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($confirmingFileSave)
                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold mb-2">Confirmar salvamento dos anexos?</div>
                            <div class="small mb-2">{{ count($uploadFiles) }} arquivo(s) será(ão) anexado(s) à tarefa.</div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-secondary flex-fill" wire:click="cancelSaveFilesConfirm">Cancelar</button>
                                <button class="btn btn-sm btn-success flex-fill" wire:click="saveFilesToTask">Salvar Arquivos</button>
                            </div>
                        </div>
                    @else
                        <button class="btn btn-sm btn-primary w-100" wire:click="startSaveFilesConfirm" @if(empty($uploadFiles)) disabled @endif>
                            <i class="bi bi-save me-1"></i>Salvar Anexos na Tarefa
                        </button>
                    @endif
                </div>
            </div>

            <div class="table-card mb-0 action-panel">
                <div class="card-header text-bg-dark fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Resposta da Atribuição
                </div>
                <div class="card-body">

                    @if(in_array($assignStatus, ['answered', 'returned_to_controller']))
                        <div class="alert alert-success small">
                            <i class="bi bi-check-circle me-1"></i>
                            Resposta já enviada. Aguardando revisão do controlador.
                        </div>
                        @if($assignment->response_summary)
                            <blockquote class="blockquote small">{{ $assignment->response_summary }}</blockquote>
                        @endif
                    @else
                        {{-- Toggle tipo de resposta --}}
                        @if($externalAccess)
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Seu nome (executante externo) *</label>
                                <input type="text"
                                       class="form-control"
                                       wire:model.defer="externalExecutorName"
                                       maxlength="120"
                                       placeholder="Informe seu nome completo">
                                @error('externalExecutorName')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" wire:model="isImpossibility" value="0" id="rNormal" />
                                <label class="form-check-label" for="rNormal">Enviar Parecer / Evidências</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" wire:model="isImpossibility" value="1" id="rImposs" />
                                <label class="form-check-label" for="rImposs">Impossibilidade de Atendimento</label>
                            </div>
                        </div>

                        @if(!$isImpossibility)
                            {{-- Formulário A: Parecer Normal --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Resumo da resposta *</label>
                                <textarea class="form-control" rows="6" wire:model="responseSummary"
                                          placeholder="Descreva sua resposta detalhadamente (mín. 20 caracteres)"></textarea>
                                <div class="form-text small text-muted">
                                    "Seu texto é salvo automaticamente enquanto você digita"
                                </div>
                            </div>

                            <div class="alert alert-light border small">
                                <i class="bi bi-info-circle me-1"></i>
                                Anexos devem ser enviados no card <strong>Anexar Arquivos da Tarefa</strong>.
                            </div>
                        @else
                            {{-- Formulário B: Impossibilidade --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Motivo da impossibilidade *</label>
                                <textarea class="form-control" rows="5" wire:model="impossibilityReason"
                                          placeholder="Descreva por que não é possível atender (mín. 20 caracteres)"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Documentação de suporte (opcional)</label>
                                <div class="alert alert-light border small mb-0">
                                    Envie os arquivos no card <strong>Anexar Arquivos da Tarefa</strong> antes de concluir.
                                </div>
                            </div>
                        @endif

                        {{-- Modal de Confirmação --}}
                        @if($confirmingSend)
                            <div class="alert alert-warning mb-3">
                                <div class="fw-semibold mb-2">Confirmar Envio?</div>
                                <div class="small mb-1">Processo: {{ $demand->source_process_number_masked ?? 'Não informado' }}</div>
                                <div class="small mb-1">Caso: {{ $demand->source_case_number ?? 'Não informado' }}</div>
                                @if(!$isImpossibility)
                                    <div class="small mb-1">Resumo: "{{ Str::limit($responseSummary, 100) }}"</div>
                                    @if(!empty($uploadFiles))
                                        <div class="small mb-1">Arquivos: {{ count($uploadFiles) }} arquivo(s)</div>
                                    @endif
                                @else
                                    <div class="small mb-1">Tipo: Impossibilidade de atendimento</div>
                                @endif
                                <div class="small text-muted mt-2">
                                    Após o envio, você não poderá editar esta resposta.
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-sm btn-secondary flex-fill" wire:click="cancelConfirm">Cancelar</button>
                                    <button class="btn btn-sm btn-success flex-fill" wire:click="submitResponse">Confirmar e Enviar</button>
                                </div>
                            </div>
                        @else
                            @if($externalAccess)
                                <button class="btn btn-primary w-100 btn-sm" wire:click="startConfirm">
                                    <i class="bi bi-send me-1"></i>Enviar Resposta
                                </button>
                            @else
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-secondary flex-fill btn-sm" wire:click="saveDraft">
                                        Salvar Rascunho
                                    </button>
                                    <button class="btn btn-primary flex-fill btn-sm" wire:click="startConfirm">
                                        <i class="bi bi-send me-1"></i>Enviar Resposta
                                    </button>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-show-loading />
    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-carousel-slide]');
            if (!trigger) return;

            const index = parseInt(trigger.getAttribute('data-carousel-slide') || '0', 10);
            const carouselEl = document.querySelector('#legalSharedFilesCarousel');
            if (!carouselEl || typeof bootstrap === 'undefined') return;

            const carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, { interval: false });
            carousel.to(index);
        });
    </script>
</div>
</div>
