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
            margin-bottom: 0;
        }
        .hero-title { font-size: 2rem; font-weight: 800; line-height: 1.1; margin-bottom: .35rem; }
        .hero-subtitle { font-size: .95rem; opacity: .85; margin-bottom: .45rem; text-transform: uppercase; letter-spacing: .02em; }
        .hero-note { font-size: .92rem; opacity: .9; }
        .hero-sla-card { min-width: 240px; border-radius: .9rem; padding: .8rem .95rem; border: 2px solid rgba(255,255,255,.22); }
        .hero-sla-card.sla-ok { background: rgba(22,163,74,.25); border-color: rgba(134,239,172,.35); }
        .hero-sla-card.sla-warn { background: rgba(217,119,6,.28); border-color: rgba(253,230,138,.4); }
        .hero-sla-card.sla-danger { background: rgba(220,38,38,.28); border-color: rgba(252,165,165,.4); }
        .hero-sla-card.sla-none { background: rgba(255,255,255,.1); }
        .hero-sla-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; opacity: .85; margin-bottom: .25rem; font-weight: 700; }
        .hero-sla-date { font-size: 1rem; font-weight: 800; line-height: 1.2; }
        .hero-sla-meta { font-size: .82rem; opacity: .88; margin-top: .25rem; }
        .hero-overdue-banner, .hero-warn-banner { color: #fff; border-radius: 0 0 .75rem .75rem; padding: .65rem 2rem; font-size: .9rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; margin-bottom: 1.5rem; }
        .hero-overdue-banner { background: linear-gradient(90deg, #dc2626, #b91c1c); }
        .hero-warn-banner { background: linear-gradient(90deg, #d97706, #b45309); }
        .table-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.07); overflow: hidden; margin-bottom: 1.25rem; }
        .table-card .card-header.text-bg-dark { font-size: .88rem; padding: .85rem 1.1rem; letter-spacing: .01em; }
        .table-card .card-body { padding: 1.25rem; }
        .controller-card-new { border: 1px solid #bfdbfe; border-left: 5px solid #2563eb; box-shadow: 0 10px 26px rgba(37, 99, 235, .12); }
        .controller-avatar { width: 44px; height: 44px; border-radius: 999px; background: linear-gradient(135deg, #1e3a5f, #2563eb); color: #fff; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .proc-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .9rem 1.2rem; }
        .proc-grid-compact { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem .75rem; }
        .proc-item, .proc-item-compact { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .65rem; padding: .7rem .8rem; }
        .proc-item-compact { border-radius: .5rem; padding: .45rem .6rem; }
        .proc-label, .proc-label-sm { font-size: .73rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-bottom: .25rem; font-weight: 700; }
        .proc-label-sm { font-size: .68rem; margin-bottom: .15rem; }
        .proc-value { font-size: .92rem; color: #0f172a; font-weight: 600; line-height: 1.35; }
        .proc-value-sm { font-size: .82rem; color: #0f172a; font-weight: 600; line-height: 1.3; }
        .right-panel { position: sticky; top: 1rem; }
        .controller-message { border-left: 4px solid #2563eb; background: #f8fafc; border-radius: .5rem; padding: .7rem .8rem; }
        .controller-general-comments { border-left: 4px solid #0f766e; background: linear-gradient(90deg, #f0fdfa, #ffffff); border-radius: .5rem; padding: .7rem .8rem; }
        .subd-events { max-height: 210px; overflow-y: auto; }
        .subd-event { border: 1px solid #e2e8f0; border-radius: .55rem; padding: .45rem .55rem; background: #fff; margin-bottom: .4rem; }
        .subd-event:last-child { margin-bottom: 0; }
        .conversation-thread { display: flex; flex-direction: column; gap: 7px; max-height: 240px; overflow-y: auto; padding-right: 2px; }
        .conversation-bubble { max-width: 88%; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 7px 10px; font-size: 12px; line-height: 1.45; }
        .conversation-bubble.mine { align-self: flex-end; background: #eff6ff; border-color: #bfdbfe; border-right: 3px solid #3b82f6; text-align: right; }
        .conversation-bubble.theirs { align-self: flex-start; background: #f0fdf4; border-color: #bbf7d0; border-left: 3px solid #10b981; text-align: left; }
        .conversation-meta { font-size: 10px; color: #64748b; margin-top: 3px; display: flex; gap: 8px; flex-wrap: wrap; }
        .conversation-bubble.mine .conversation-meta { justify-content: flex-end; }
        .upload-zone { border: 2px dashed #93c5fd; background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%); border-radius: 12px; padding: 12px; }
        .upload-zone-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .upload-chip { font-size: 11px; font-weight: 700; color: #1e3a8a; background: #dbeafe; border: 1px solid #93c5fd; border-radius: 999px; padding: 3px 9px; }
        .queue-list { display: grid; gap: 8px; }
        .queue-item { border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; border-radius: 10px; background: #fff; padding: 10px; box-shadow: 0 3px 10px rgba(15, 23, 42, .05); }
        .queue-meta { font-size: 11px; color: #64748b; margin-top: 6px; }
        .legal-evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .legal-evidence-card { border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; padding: .55rem; text-align: center; }
        .legal-evidence-thumb { width: 100%; height: 120px; object-fit: cover; border-radius: .55rem; border: 1px solid #e2e8f0; background: #f8fafc; cursor: pointer; }
        .legal-evidence-name { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .shared-doc-filters { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
        .shared-doc-filter { border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 999px; padding: 3px 9px; font-size: 11px; font-weight: 700; }
        .shared-doc-filter.active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }
        .shared-doc-tag { display: inline-flex; align-items: center; gap: 3px; border-radius: 999px; padding: 2px 7px; font-size: 10px; font-weight: 800; background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .shared-doc-tag.shared { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .shared-doc-list-item { border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; padding: 9px; display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 7px; }
        .shared-doc-icon { width: 34px; height: 34px; border-radius: 9px; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        @media (max-width: 992px) { .proc-grid-3, .proc-grid-compact { grid-template-columns: 1fr; } .right-panel { position: static; } }
    </style>

    @php
        $demand = $subdemand->demand;
        $sourceTypeValue = $demand?->source_type instanceof \BackedEnum ? $demand->source_type->value : (string) ($demand?->source_type ?? '');
        $sourceTypeLabel = match($sourceTypeValue) { 'subsidy' => 'Subsídio', 'sentence' => 'Sentença', 'injunction' => 'Liminar', default => $sourceTypeValue !== '' ? \Illuminate\Support\Str::headline($sourceTypeValue) : 'Demanda' };
        $sourceTypeClass = match($sourceTypeValue) { 'subsidy' => 'badge bg-info text-dark', 'sentence' => 'badge bg-warning text-dark', 'injunction' => 'badge bg-danger', default => 'badge bg-secondary' };
        $subStatusValue = $subdemand->status instanceof \BackedEnum ? $subdemand->status->value : (string) $subdemand->status;
        $subStatusLabel = match($subStatusValue) { 'aberta' => 'Aberta', 'em_andamento' => 'Em andamento', 'aguardando_retorno' => 'Aguardando retorno', 'concluida' => 'Concluída', 'encerrada_controlador' => 'Encerrada pelo controlador', default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $subStatusValue)) };
        $subStatusClass = match($subStatusValue) { 'concluida' => 'badge bg-success', 'encerrada_controlador' => 'badge bg-secondary', 'em_andamento' => 'badge bg-warning text-dark', 'aguardando_retorno' => 'badge bg-info text-dark', default => 'badge bg-primary' };
        $requiresEvidence = (bool) data_get($subdemand->metadata ?? [], 'requires_evidence', false);
        $deadlineAt = $subdemand->deadline_at ? \Carbon\Carbon::parse($subdemand->deadline_at) : null;
        $isDeadlineOverdue = $deadlineAt?->isPast() ?? false;
        $slaCardClass = !$deadlineAt ? 'sla-none' : ($isDeadlineOverdue ? 'sla-danger' : ($deadlineAt->lte(now()->addDay()) ? 'sla-warn' : 'sla-ok'));
        $heroBannerType = $deadlineAt ? ($isDeadlineOverdue ? 'overdue' : ($deadlineAt->lte(now()->addDay()) ? 'warn' : null)) : null;
        $heroBannerDays = $isDeadlineOverdue ? (int) now()->diffInDays($deadlineAt) : null;
        $controllerName = $subdemand->createdBy?->name ?? data_get($subdemand->metadata ?? [], 'controller_name') ?? 'Controlador';
        $controllerInitial = mb_strtoupper(mb_substr($controllerName, 0, 1));
        $queuedCount = is_array($uploadFiles ?? null) ? count($uploadFiles) : 0;
        $controllerGeneralComments = $demand?->comments
            ? $demand->comments->where('legal_demand_subdemand_id', null)->where('visibility', 'shared')->sortByDesc('created_at')->values()
            : collect();
        $conversationComments = $subdemand->comments
            ->where('visibility', 'shared')
            ->sortBy(fn ($commentItem) => $commentItem->created_at?->timestamp ?? 0)
            ->values();
        $sharedFiles = $demand?->files ? $demand->files->where('removed_at', null)->where('visibility', 'shared')->values() : collect();
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'tiff', 'webp'];
        $sharedImages = $sharedFiles->filter(function ($file) use ($imageExts) {
            $name = (string) ($file->original_name ?? $file->file_name ?? $file->path ?? '');
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = strtolower((string) ($file->mime_type ?? ''));
            return in_array($ext, $imageExts, true) || str_starts_with($mime, 'image/');
        })->values();
        $sharedOthers = $sharedFiles->reject(fn ($file) => $sharedImages->contains('id', $file->id))->values();
    @endphp

    <div class="container-fluid">
        <div class="ldd-hero">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div class="flex-grow-1">
                    <div class="hero-title">
                        Processo {{ $demand?->source_process_number_masked ?? $demand?->source_process_number ?? 'Não informado' }}
                        @if($demand?->legalCase?->company_name)
                            — {{ $demand->legalCase->company_name }}
                        @endif
                    </div>
                    <div class="hero-subtitle">Caso {{ $demand?->source_case_number ?? 'Não informado' }} · {{ $sourceTypeLabel }}</div>
                    <div class="hero-note">Detalhamento da subdemanda, interação com o controlador e envio externo.</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="{{ $sourceTypeClass }}">{{ $sourceTypeLabel }}</span>
                        <span class="{{ $subStatusClass }}">Subdemanda: {{ $subStatusLabel }}</span>
                        <span class="badge bg-light text-dark border">Execução externa</span>
                    </div>
                </div>
                <div class="hero-sla-card {{ $slaCardClass }}">
                    <div class="hero-sla-label">Prazo SLA do Controlador</div>
                    @if($deadlineAt)
                        <div class="hero-sla-date">{{ $deadlineAt->format('d/m/Y H:i') }}</div>
                        <div class="hero-sla-meta">
                            @if($isDeadlineOverdue)
                                <span class="badge bg-danger">Vencido</span> {{ $deadlineAt->diffForHumans(now(), ['parts' => 2, 'short' => false]) }}
                            @elseif($slaCardClass === 'sla-warn')
                                <span class="badge bg-warning text-dark">Atenção</span> vence {{ $deadlineAt->diffForHumans() }}
                            @else
                                <span class="badge bg-success">No prazo</span> vence {{ $deadlineAt->diffForHumans() }}
                            @endif
                        </div>
                    @else
                        <div class="hero-sla-date">Não informado</div>
                        <div class="hero-sla-meta">O controlador não definiu SLA para esta subdemanda.</div>
                    @endif
                </div>
            </div>
        </div>

        @if($heroBannerType === 'overdue')
            <div class="hero-overdue-banner"><i class="bi bi-exclamation-triangle-fill"></i>PRAZO VENCIDO HÁ {{ $heroBannerDays }} {{ $heroBannerDays === 1 ? 'DIA' : 'DIAS' }} — Esta tarefa está fora do SLA</div>
        @elseif($heroBannerType === 'warn')
            <div class="hero-warn-banner"><i class="bi bi-clock-fill"></i>PRAZO CRÍTICO — O SLA desta subdemanda vence hoje ou amanhã.</div>
        @else
            <div style="margin-bottom:1.5rem;"></div>
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card controller-card-new">
                    <div class="card-header text-bg-dark fw-bold"><i class="bi bi-megaphone me-2"></i>Solicitação do Controlador</div>
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div class="controller-avatar">{{ $controllerInitial }}</div>
                            <div>
                                <div class="fw-bold" style="font-size:.97rem;">{{ $controllerName }}</div>
                                <div class="small text-muted">Subdemanda criada em {{ $subdemand->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="proc-grid-3 mb-3">
                            <div class="proc-item"><div class="proc-label">Quem executa</div><div class="proc-value">{{ $executorName ?: 'Executante externo' }}</div></div>
                            <div class="proc-item"><div class="proc-label">Data limite para resposta</div><div class="proc-value">{{ $deadlineAt ? $deadlineAt->format('d/m/Y H:i') : 'Não informada' }}</div></div>
                            <div class="proc-item"><div class="proc-label">Status da subdemanda</div><div class="proc-value"><span class="{{ $subStatusClass }}">{{ $subStatusLabel }}</span></div></div>
                        </div>

                        @if($controllerGeneralComments->isNotEmpty())
                            <div class="controller-general-comments mb-3">
                                <div class="small text-muted mb-2">Comentários compartilhados do controlador</div>
                                <div class="subd-events">
                                    @foreach($controllerGeneralComments as $commentItem)
                                        <div class="subd-event">
                                            <div class="d-flex justify-content-between gap-2">
                                                <div class="small fw-semibold">{{ $commentItem->user?->name ?? 'Controlador' }}</div>
                                                <div class="small text-muted">{{ $commentItem->created_at?->format('d/m/Y H:i') }}</div>
                                            </div>
                                            <div class="small mt-1">{{ $commentItem->comment }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="controller-message mb-2">
                            <div class="small text-muted mb-1">Informações solicitadas pelo controlador</div>
                            <p class="mb-0">{{ $subdemand->description ?: 'Sem orientação textual registrada.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold"><i class="bi bi-chat-dots me-2"></i>Conversa da Subdemanda</div>
                    <div class="card-body">
                        <div class="small text-muted mb-3">Canal operacional entre executante externo e controlador sobre esta subdemanda. Conversa ordenada do mais antigo para o mais novo.</div>
                        <div class="conversation-thread auto-scroll-chat mb-0">
                            @forelse($conversationComments as $commentItem)
                                @php
                                    $isMine = empty($commentItem->user_id);
                                    $author = $commentItem->user?->name ?: 'Executante externo';
                                @endphp
                                <div class="conversation-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                    <div>{{ $commentItem->comment }}</div>
                                    <div class="conversation-meta">
                                        <span class="fw-semibold">{{ $author }}</span>
                                        <span>{{ $commentItem->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-light border small mb-0">Nenhum comentário compartilhado nesta subdemanda ainda.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="table-card" x-data="{ docFilter: 'all' }">
                    <div class="card-header text-bg-dark fw-bold"><i class="bi bi-paperclip me-2"></i>Documentos Compartilhados</div>
                    <div class="card-body">
                        @if($sharedFiles->isEmpty())
                            <p class="text-muted small mb-0">Nenhum documento compartilhado.</p>
                        @else
                            <div class="shared-doc-filters">
                                <button type="button" class="shared-doc-filter" :class="{ active: docFilter === 'all' }" @click="docFilter = 'all'">Todos ({{ $sharedFiles->count() }})</button>
                                @if($sharedImages->isNotEmpty())
                                    <button type="button" class="shared-doc-filter" :class="{ active: docFilter === 'images' }" @click="docFilter = 'images'">Imagens ({{ $sharedImages->count() }})</button>
                                @endif
                                @if($sharedOthers->isNotEmpty())
                                    <button type="button" class="shared-doc-filter" :class="{ active: docFilter === 'files' }" @click="docFilter = 'files'">Arquivos ({{ $sharedOthers->count() }})</button>
                                @endif
                            </div>

                            @if($sharedImages->isNotEmpty())
                                <div x-show="docFilter === 'all' || docFilter === 'images'">
                                    <div class="small fw-semibold text-muted mb-2">Galeria de imagens</div>
                                    <div class="legal-evidence-grid mb-3">
                                        @foreach($sharedImages as $index => $fileItem)
                                            @php
                                                $filePath = $fileItem->path ?? $fileItem->file_path ?? null;
                                                $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                                $fileType = $fileItem->legalDemand?->source_type instanceof \BackedEnum ? $fileItem->legalDemand->source_type->value : (string) ($fileItem->legalDemand?->source_type ?? $sourceTypeValue);
                                                $fileTypeLabel = match($fileType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => 'Demanda' };
                                            @endphp
                                            @if($fileUrl)
                                                <div class="legal-evidence-card">
                                                    <img src="{{ $fileUrl }}" class="legal-evidence-thumb" alt="{{ $fileItem->original_name ?? basename($filePath) }}" data-bs-toggle="modal" data-bs-target="#legalExternalSharedFilesModal" data-carousel-slide="{{ $index }}">
                                                    <div class="small text-muted legal-evidence-name mt-2" title="{{ $fileItem->original_name ?? basename($filePath) }}">{{ $fileItem->original_name ?? basename($filePath) }}</div>
                                                    <div class="d-flex flex-wrap gap-1 justify-content-center mt-1"><span class="shared-doc-tag">{{ $fileTypeLabel }}</span><span class="shared-doc-tag shared">Compartilhado</span></div>
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-download me-1"></i>Baixar</a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($sharedOthers->isNotEmpty())
                                <div x-show="docFilter === 'all' || docFilter === 'files'">
                                    <div class="small fw-semibold text-muted mb-2">Lista de arquivos</div>
                                    @foreach($sharedOthers as $fileItem)
                                        @php
                                            $filePath = $fileItem->path ?? $fileItem->file_path ?? null;
                                            $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                            $name = $fileItem->original_name ?? ($filePath ? basename($filePath) : 'Arquivo');
                                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                            $fileType = $fileItem->legalDemand?->source_type instanceof \BackedEnum ? $fileItem->legalDemand->source_type->value : (string) ($fileItem->legalDemand?->source_type ?? $sourceTypeValue);
                                            $fileTypeLabel = match($fileType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => 'Demanda' };
                                            $fileIcon = match($ext) { 'pdf' => 'bi-filetype-pdf text-danger', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => 'bi-file-image text-info', 'xls', 'xlsx', 'csv' => 'bi-file-earmark-spreadsheet text-success', 'doc', 'docx', 'odt' => 'bi-file-earmark-word text-primary', 'zip', 'rar', '7z' => 'bi-file-earmark-zip text-warning', default => 'bi-file-earmark text-secondary' };
                                        @endphp
                                        @if($fileUrl)
                                            <div class="shared-doc-list-item">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="shared-doc-icon"><i class="bi {{ $fileIcon }}"></i></span>
                                                    <div>
                                                        <div class="legal-evidence-name small" title="{{ $name }}">{{ $name }}</div>
                                                        <div class="d-flex flex-wrap gap-1 mt-1"><span class="shared-doc-tag">{{ $fileTypeLabel }}</span><span class="shared-doc-tag shared">{{ strtoupper($ext ?: 'ARQ') }}</span></div>
                                                    </div>
                                                </div>
                                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="right-panel">
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold" style="font-size:.82rem;"><i class="bi bi-file-text me-2"></i>Resumo do Processo</div>
                        <div class="card-body" style="padding:.9rem;">
                            <div class="proc-grid-compact">
                                <div class="proc-item-compact"><div class="proc-label-sm">Número Processo</div><div class="proc-value-sm">{{ $demand?->source_process_number_masked ?? $demand?->source_process_number ?? 'Não informado' }}</div></div>
                                <div class="proc-item-compact"><div class="proc-label-sm">Número Caso</div><div class="proc-value-sm">{{ $demand?->source_case_number ?? 'Não informado' }}</div></div>
                                <div class="proc-item-compact"><div class="proc-label-sm">Tipo</div><div class="proc-value-sm">{{ $sourceTypeLabel }}</div></div>
                                <div class="proc-item-compact"><div class="proc-label-sm">Status Subdemanda</div><div class="proc-value-sm">{{ $subStatusLabel }}</div></div>
                                <div class="proc-item-compact"><div class="proc-label-sm">Prazo Judicial</div><div class="proc-value-sm">{{ $demand?->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at)->format('d/m/Y H:i') : '—' }}</div></div>
                                <div class="proc-item-compact"><div class="proc-label-sm">Evidência</div><div class="proc-value-sm">{{ $requiresEvidence ? 'Obrigatória' : 'Opcional' }}</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold"><i class="bi bi-send-check me-2"></i>Enviar Retorno Externo</div>
                        <div class="card-body">
                            @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Seu nome *</label>
                                <input type="text" class="form-control form-control-sm" wire:model="executorName">
                                @error('executorName') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Mensagem da resposta</label>
                                <textarea class="form-control form-control-sm" rows="5" wire:model="comment" placeholder="Descreva o retorno da subdemanda..."></textarea>
                                @error('comment') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2 upload-zone">
                                <div class="upload-zone-head"><div class="small fw-semibold text-muted">Selecione, revise nomes e envie</div><span class="upload-chip">{{ $queuedCount }} na fila</span></div>
                                <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                                @error('uploadFiles.*') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                @if(!empty($uploadFiles))
                                    <div class="queue-list mt-2">
                                        @foreach($uploadFiles as $i => $file)
                                            @php $qName = (string) ($uploadNames[$i] ?? $file->getClientOriginalName()); $qExt = strtolower(pathinfo($qName, PATHINFO_EXTENSION)); $qSize = (int) ($file->getSize() ?? 0); @endphp
                                            <div class="queue-item">
                                                <div class="d-flex align-items-center gap-2"><i class="bi bi-paperclip"></i><input type="text" class="form-control form-control-sm" wire:model.defer="uploadNames.{{ $i }}" placeholder="Nome do arquivo"><button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeUploadFile({{ $i }})" title="Remover da fila"><i class="bi bi-x-lg"></i></button></div>
                                                <div class="queue-meta">{{ strtoupper($qExt ?: '-') }} · {{ number_format($qSize / 1024, 1, ',', '.') }} KB</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-primary w-100" data-livewire-id="{{ $_instance->id }}" onclick="confirmExternalSubdemandSubmit(this)" wire:loading.attr="disabled" wire:target="submit,uploadFiles"><i class="bi bi-send me-1"></i>Enviar retorno</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($sharedImages->isNotEmpty())
            <div class="modal fade" id="legalExternalSharedFilesModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Visualização de Imagens</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div id="legalExternalSharedFilesCarousel" class="carousel slide" data-bs-ride="false"><div class="carousel-inner">
                    @foreach($sharedImages as $index => $fileItem)
                        @php $filePath = $fileItem->path ?? $fileItem->file_path ?? null; $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null; @endphp
                        @if($fileUrl)<div class="carousel-item @if($index === 0) active @endif"><div class="text-center"><img src="{{ $fileUrl }}" class="img-fluid rounded border" alt="{{ $fileItem->original_name ?? basename($filePath) }}" style="max-height:70vh;object-fit:contain"></div><div class="d-flex justify-content-between align-items-center mt-2"><div class="small text-muted">{{ $fileItem->original_name ?? basename($filePath) }}</div><a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Baixar</a></div></div>@endif
                    @endforeach
                </div>@if($sharedImages->count() > 1)<button class="carousel-control-prev" type="button" data-bs-target="#legalExternalSharedFilesCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button><button class="carousel-control-next" type="button" data-bs-target="#legalExternalSharedFilesCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>@endif</div></div></div></div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        function scrollLegalChatsToBottom() {
            document.querySelectorAll('.auto-scroll-chat').forEach((el) => {
                el.scrollTop = el.scrollHeight;
            });
        }

        document.addEventListener('DOMContentLoaded', scrollLegalChatsToBottom);
        document.addEventListener('livewire:load', function () {
            scrollLegalChatsToBottom();
            if (window.Livewire) {
                Livewire.hook('message.processed', scrollLegalChatsToBottom);
            }
        });

        function confirmExternalSubdemandSubmit(button) {
            Swal.fire({
                icon: 'question',
                title: 'Confirmar envio?',
                html: 'Após confirmar, seu retorno será enviado ao controlador e este link de acesso será revogado.',
                showCancelButton: true,
                confirmButtonText: 'Confirmar envio',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (!result.isConfirmed) return;
                const component = Livewire.find(button.dataset.livewireId);
                if (component) component.call('submit');
            });
        }
    </script>
@endpush
