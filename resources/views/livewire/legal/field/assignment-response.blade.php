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
            min-width: 240px;
            border-radius: .9rem;
            padding: .8rem .95rem;
            border: 2px solid rgba(255,255,255,.22);
        }
        .hero-sla-card.sla-ok {
            background: rgba(22,163,74,.25);
            border-color: rgba(134,239,172,.35);
        }
        .hero-sla-card.sla-warn {
            background: rgba(217,119,6,.28);
            border-color: rgba(253,230,138,.4);
        }
        .hero-sla-card.sla-danger {
            background: rgba(220,38,38,.28);
            border-color: rgba(252,165,165,.4);
        }
        .hero-sla-card.sla-none {
            background: rgba(255,255,255,.1);
        }
        .hero-sla-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            opacity: .85;
            margin-bottom: .25rem;
            font-weight: 700;
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
        .hero-overdue-banner {
            background: linear-gradient(90deg, #dc2626, #b91c1c);
            color: #fff;
            border-radius: 0 0 .75rem .75rem;
            padding: .65rem 2rem;
            font-size: .9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.5rem;
        }
        .hero-warn-banner {
            background: linear-gradient(90deg, #d97706, #b45309);
            color: #fff;
            border-radius: 0 0 .75rem .75rem;
            padding: .55rem 2rem;
            font-size: .88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.5rem;
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
        .controller-card-new {
            border: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
            box-shadow: 0 10px 26px rgba(37, 99, 235, .12);
        }
        .controller-avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
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
        .proc-grid-compact {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem .75rem;
        }
        .proc-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .65rem;
            padding: .7rem .8rem;
        }
        .proc-item-compact {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .5rem;
            padding: .45rem .6rem;
        }
        .proc-label {
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin-bottom: .25rem;
            font-weight: 700;
        }
        .proc-label-sm {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin-bottom: .15rem;
            font-weight: 700;
        }
        .proc-value {
            font-size: .92rem;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.35;
        }
        .proc-value-sm {
            font-size: .82rem;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.3;
        }
        .right-panel {
            position: sticky;
            top: 1rem;
        }
        .evidence-required-block {
            border: 2px solid #f97316;
            background: linear-gradient(180deg, #fff7ed, #fff);
            border-radius: 1rem;
            padding: 1.1rem 1.1rem;
            margin-bottom: 1.25rem;
        }
        .evidence-optional-block {
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            margin-bottom: 1.25rem;
        }
        .evidence-optional-title {
            font-size: .88rem;
            font-weight: 700;
            color: #475569;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem;
        }
        .evidence-ok-block {
            border: 2px solid #16a34a;
            background: linear-gradient(180deg, #f0fdf4, #fff);
            border-radius: 1rem;
            padding: .85rem 1rem;
            margin-bottom: 1.25rem;
        }
        .evidence-required-title {
            font-size: .95rem;
            font-weight: 800;
            color: #c2410c;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .5rem;
        }
        .evidence-ok-title {
            font-size: .9rem;
            font-weight: 700;
            color: #166534;
            display: flex;
            align-items: center;
            gap: .5rem;
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
            max-height: 260px;
            overflow-y: auto;
            padding-right: .35rem;
        }
        .assign-timeline-wrap::-webkit-scrollbar { width: 6px; }
        .assign-timeline-wrap::-webkit-scrollbar-track { background: transparent; }
        .assign-timeline-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .assign-timeline-wrap::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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
        .controller-general-comments {
            border-left: 4px solid #0f766e;
            background: linear-gradient(90deg, #f0fdfa, #ffffff);
            border-radius: .5rem;
            padding: .7rem .8rem;
        }
        .shared-doc-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .shared-doc-filter {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
        }
        .shared-doc-filter.active {
            background: #1e3a8a;
            color: #fff;
            border-color: #1e3a8a;
        }
        .shared-doc-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 800;
            background: #e0f2fe;
            color: #075985;
            border: 1px solid #bae6fd;
        }
        .shared-doc-tag.shared {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        .shared-doc-list-item {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            padding: 9px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 7px;
        }
        .shared-doc-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .upload-zone {
            border: 2px dashed #93c5fd;
            background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
            border-radius: 12px;
            padding: 12px;
        }
        .upload-zone-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .upload-kpis {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .upload-chip {
            font-size: 11px;
            font-weight: 700;
            color: #1e3a8a;
            background: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 999px;
            padding: 3px 9px;
        }
        .upload-actions {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        .queue-list {
            display: grid;
            gap: 8px;
        }
        .queue-item {
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .05);
        }
        .queue-name-input { font-weight: 600; }
        .queue-remove-btn {
            min-width: 38px;
            height: 34px;
            padding: 0;
        }
        .queue-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
        }
        .queue-empty {
            border: 1px dashed #cbd5e1;
            background: #fff;
            border-radius: 10px;
            padding: 12px;
            font-size: 12px;
            color: #64748b;
        }
        .files-block {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            padding: 12px;
            margin-bottom: 12px;
        }
        .files-block-title {
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .subd-card {
            border: 1px solid #dbeafe;
            border-radius: .75rem;
            background: #fff;
            margin-bottom: .65rem;
            overflow: hidden;
        }
        .subd-summary {
            cursor: pointer;
            list-style: none;
            padding: .7rem .85rem;
            background: #f8fbff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .6rem;
        }
        .subd-summary::-webkit-details-marker { display: none; }
        .subd-meta {
            font-size: .77rem;
            color: #64748b;
            margin-top: .15rem;
        }
        .subd-body {
            padding: .75rem .85rem;
            border-top: 1px solid #e2e8f0;
        }
        .subd-events {
            max-height: 180px;
            overflow-y: auto;
        }
        .subd-event {
            border: 1px solid #e2e8f0;
            border-radius: .55rem;
            padding: .45rem .55rem;
            background: #fff;
            margin-bottom: .4rem;
        }
        .subd-event:last-child { margin-bottom: 0; }
        .conversation-thread {
            display: flex;
            flex-direction: column;
            gap: 7px;
            max-height: 240px;
            overflow-y: auto;
            padding-right: 2px;
        }
        .conversation-bubble {
            max-width: 88%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: 7px 10px;
            font-size: 12px;
            line-height: 1.45;
        }
        .conversation-bubble.mine {
            align-self: flex-end;
            background: #eff6ff;
            border-color: #bfdbfe;
            border-right: 3px solid #3b82f6;
            text-align: right;
        }
        .conversation-bubble.theirs {
            align-self: flex-start;
            background: #f0fdf4;
            border-color: #bbf7d0;
            border-left: 3px solid #10b981;
            text-align: left;
        }
        .conversation-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .conversation-bubble.mine .conversation-meta {
            justify-content: flex-end;
        }
        .collapsible-section summary {
            cursor: pointer;
            list-style: none;
            padding: .6rem .85rem;
            background: #f8fafc;
            border-radius: .65rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .83rem;
            font-weight: 700;
            color: #334155;
            user-select: none;
        }
        .collapsible-section summary::-webkit-details-marker { display: none; }
        .collapsible-section[open] summary {
            border-radius: .65rem .65rem 0 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .collapsible-section .collapsible-body {
            padding: .75rem .85rem 0;
        }
        @media (max-width: 992px) {
            .proc-grid { grid-template-columns: 1fr; }
            .proc-grid-3 { grid-template-columns: 1fr; }
            .proc-grid-compact { grid-template-columns: 1fr; }
            .right-panel { position: static; }
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

            // Evidence already saved for this assignment
            $savedEvidenceCount = $demand->files
                ->where('removed_at', null)
                ->where('assignment_id', $assignment->id)
                ->count();

            $requiresEvidence = (bool) data_get($assignment->metadata ?? [], 'requires_evidence', false);

            // SLA card variant
            $slaCardClass = 'sla-none';
            if ($controllerDueAt) {
                if ($isControllerOverdue) {
                    $slaCardClass = 'sla-danger';
                } elseif ($controllerDueAt->lte(now()->addDay())) {
                    $slaCardClass = 'sla-warn';
                } else {
                    $slaCardClass = 'sla-ok';
                }
            }

            // Hero deadline banner
            $heroBannerType = null; // null | 'overdue' | 'warn'
            if ($controllerDueAt) {
                if ($isControllerOverdue) {
                    $heroBannerType = 'overdue';
                    $heroBannerDays = (int) now()->diffInDays($controllerDueAt);
                } elseif ($controllerDueAt->lte(now()->addDay())) {
                    $heroBannerType = 'warn';
                }
            }

            // Controller avatar initial
            $controllerName = $assignment->sentBy?->name ?? 'C';
            $controllerInitial = mb_strtoupper(mb_substr($controllerName, 0, 1));

            // Queued files count
            $queuedCount = is_array($uploadFiles ?? null) ? count($uploadFiles) : 0;

            $activeSubdemand = $activeSubdemandId
                ? $demand->subdemands->firstWhere('id', $activeSubdemandId)
                : null;
            $conversationComments = $activeSubdemand
                ? $demand->comments
                    ->where('legal_demand_subdemand_id', $activeSubdemand->id)
                    ->where('visibility', 'shared')
                    ->sortBy(fn ($comment) => $comment->created_at?->timestamp ?? 0)
                    ->values()
                : collect();
            $controllerGeneralComments = $demand->comments
                ->where('legal_demand_subdemand_id', null)
                ->where('visibility', 'shared')
                ->sortByDesc('created_at')
                ->values();
        @endphp

        {{-- HERO --}}
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
                <div class="hero-sla-card {{ $slaCardClass }}">
                    <div class="hero-sla-label">Prazo SLA do Controlador</div>
                    @if($controllerDueAt)
                        <div class="hero-sla-date">{{ $controllerDueAt->format('d/m/Y H:i') }}</div>
                        <div class="hero-sla-meta">
                            @if($isControllerOverdue)
                                <span class="badge bg-danger">Vencido</span>
                                {{ $controllerDueAt->diffForHumans(now(), ['parts' => 2, 'short' => false]) }}
                            @elseif($slaCardClass === 'sla-warn')
                                <span class="badge bg-warning text-dark">Atenção</span>
                                vence {{ $controllerDueAt->diffForHumans() }}
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

        {{-- DEADLINE BANNER --}}
        @if($heroBannerType === 'overdue')
            <div class="hero-overdue-banner">
                <i class="bi bi-exclamation-triangle-fill"></i>
                PRAZO VENCIDO HÁ {{ $heroBannerDays }} {{ $heroBannerDays === 1 ? 'DIA' : 'DIAS' }} — Esta tarefa está fora do SLA
            </div>
        @elseif($heroBannerType === 'warn')
            <div class="hero-warn-banner">
                <i class="bi bi-clock-fill"></i>
                PRAZO CRÍTICO — O SLA desta atribuição vence hoje ou amanhã. Envie sua resposta com urgência.
            </div>
        @else
            <div style="margin-bottom:1.5rem;"></div>
        @endif

        <div class="row g-4">

            {{-- ================================================
                 COLUNA ESQUERDA col-8 — Fluxo Principal
                 ================================================ --}}
            <div class="col-lg-8">

                {{-- 1. Solicitação do Controlador --}}
                <div class="table-card controller-card-new">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-megaphone me-2"></i>Solicitação do Controlador
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div class="controller-avatar">{{ $controllerInitial }}</div>
                            <div>
                                <div class="fw-bold" style="font-size:.97rem;">{{ $assignment->sentBy?->name ?? '—' }}</div>
                                <div class="small text-muted">
                                    Encaminhado em {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>

                        <div class="proc-grid-3 mb-3">
                            <div class="proc-item">
                                <div class="proc-label">Quem executa</div>
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

                        @if($controllerGeneralComments->isNotEmpty())
                            <div class="controller-general-comments mb-3">
                                <div class="small text-muted mb-2">Comentários compartilhados do controlador</div>
                                <div class="subd-events">
                                    @foreach($controllerGeneralComments as $comment)
                                        <div class="subd-event">
                                            <div class="d-flex justify-content-between gap-2">
                                                <div class="small fw-semibold">{{ $comment->user?->name ?? 'Controlador' }}</div>
                                                <div class="small text-muted">{{ $comment->created_at?->format('d/m/Y H:i') }}</div>
                                            </div>
                                            <div class="small mt-1">{{ $comment->comment }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

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

                @if($activeSubdemand)
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold">
                            <i class="bi bi-chat-dots me-2"></i>Conversa da Subdemanda
                        </div>
                        <div class="card-body">
                            <div class="small text-muted mb-3">
                                Canal operacional entre executante e controlador sobre esta subdemanda. Conversa ordenada do mais antigo para o mais novo.
                            </div>

                            <div class="conversation-thread auto-scroll-chat mb-3">
                                @forelse($conversationComments as $comment)
                                    @php
                                        $isMine = !$externalAccess && (string) ($comment->user_id ?? '') === (string) auth()->id();
                                        $author = $comment->user?->name ?: 'Executante externo';
                                    @endphp
                                    <div class="conversation-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                        <div>{{ $comment->comment }}</div>
                                        <div class="conversation-meta">
                                            <span class="fw-semibold">{{ $author }}</span>
                                            <span>{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="alert alert-light border small mb-0">
                                        Nenhum comentário compartilhado nesta subdemanda ainda.
                                    </div>
                                @endforelse
                            </div>

                            <div class="input-group input-group-sm">
                                <input type="text"
                                       class="form-control"
                                       wire:model.defer="subdemandCommentInput.{{ $activeSubdemand->id }}"
                                       placeholder="Enviar comentário compartilhado ao controlador">
                                <button class="btn btn-outline-primary" wire:click="addSubdemandComment({{ $activeSubdemand->id }})">
                                    <i class="bi bi-send me-1"></i>Enviar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 2. Evidence Block --}}
                @if($savedEvidenceCount === 0 && empty($uploadFiles))
                    <div class="{{ $requiresEvidence ? 'evidence-required-block' : 'evidence-optional-block' }}">
                        <div class="{{ $requiresEvidence ? 'evidence-required-title' : 'evidence-optional-title' }}">
                            <i class="bi bi-{{ $requiresEvidence ? 'exclamation-triangle-fill' : 'paperclip' }}"></i>
                            {{ $requiresEvidence ? 'EVIDÊNCIA OBRIGATÓRIA' : 'ANEXAR ARQUIVOS (opcional)' }}
                        </div>
                        <p class="mb-3 small" style="font-size:.88rem;color:{{ $requiresEvidence ? '#991b1b' : '#475569' }}">
                            {{ $requiresEvidence
                                ? 'O controlador exige ao menos um arquivo de evidência antes do envio da resposta.'
                                : 'Você pode anexar arquivos de suporte à sua resposta.' }}
                        </p>
                        {{-- Upload zone inline --}}
                        <div class="upload-zone">
                            <div class="upload-zone-head">
                                <div class="small fw-semibold text-muted">Selecione, revise nomes e salve em lote</div>
                                <div class="upload-kpis">
                                    <span class="upload-chip">{{ $queuedCount }} na fila</span>
                                    <span class="upload-chip">Compartilhado</span>
                                </div>
                            </div>
                            <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                            <div class="upload-actions">
                                <select class="form-select form-select-sm" wire:model="fileVisibility" disabled>
                                    <option value="shared">Compartilhado</option>
                                </select>
                                <button class="btn btn-sm btn-primary px-3" wire:click="saveFilesToTask" wire:loading.attr="disabled" wire:target="uploadFiles,saveFilesToTask">
                                    <i class="bi bi-cloud-upload me-1"></i>Salvar arquivos
                                </button>
                            </div>
                            <div class="small text-muted mt-2">PDF, JPG, PNG, DOCX, XLSX — máx. 10MB por arquivo.</div>
                        </div>

                        @if(!empty($uploadFiles))
                            <div class="mt-3">
                                <div class="small fw-semibold text-muted mb-2">Lista de envio (editar nome/remover antes de salvar)</div>
                                <div class="queue-list">
                                    @foreach($uploadFiles as $i => $file)
                                        @php
                                            $qName = $uploadNames[$i] ?? $file->getClientOriginalName();
                                            $qExt = strtolower(pathinfo((string) $qName, PATHINFO_EXTENSION));
                                            $qSize = method_exists($file, 'getSize') ? (int) $file->getSize() : 0;
                                            $queueIcon = match($qExt) {
                                                'pdf' => 'bi-filetype-pdf text-danger',
                                                'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => 'bi-file-image text-info',
                                                'xls', 'xlsx', 'csv' => 'bi-file-earmark-spreadsheet text-success',
                                                'doc', 'docx', 'odt' => 'bi-file-earmark-word text-primary',
                                                'zip', 'rar', '7z' => 'bi-file-earmark-zip text-warning',
                                                default => 'bi-file-earmark text-secondary',
                                            };
                                        @endphp
                                        <div class="queue-item">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi {{ $queueIcon }}"></i>
                                                <input type="text"
                                                       class="form-control form-control-sm queue-name-input"
                                                       wire:model.defer="uploadNames.{{ $i }}"
                                                       placeholder="Nome do arquivo">
                                                <button type="button" class="btn btn-sm btn-outline-danger queue-remove-btn" wire:click="removeUploadFile({{ $i }})" title="Remover da fila">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="queue-meta">{{ strtoupper($qExt ?: '-') }} · {{ number_format($qSize / 1024, 1, ',', '.') }} KB</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="evidence-ok-block">
                        <div class="evidence-ok-title">
                            <i class="bi bi-check-circle-fill"></i>
                            {{ $savedEvidenceCount > 0 ? $savedEvidenceCount . ' arquivo(s) de evidência enviado(s)' : $queuedCount . ' arquivo(s) na fila — salve antes de enviar a resposta' }}
                        </div>
                        @if($savedEvidenceCount === 0 && !empty($uploadFiles))
                            <p class="mb-3 small mt-2" style="color:#92400e;">
                                Você tem arquivos na fila. Clique em <strong>Salvar arquivos</strong> para persistir antes de enviar a resposta.
                            </p>
                            {{-- Upload zone for queue management --}}
                            <div class="upload-zone mt-2">
                                <div class="upload-zone-head">
                                    <div class="small fw-semibold text-muted">Selecione, revise nomes e salve em lote</div>
                                    <div class="upload-kpis">
                                        <span class="upload-chip">{{ $queuedCount }} na fila</span>
                                        <span class="upload-chip">Compartilhado</span>
                                    </div>
                                </div>
                                <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                                <div class="upload-actions">
                                    <select class="form-select form-select-sm" wire:model="fileVisibility" disabled>
                                        <option value="shared">Compartilhado</option>
                                    </select>
                                    <button class="btn btn-sm btn-primary px-3" wire:click="saveFilesToTask" wire:loading.attr="disabled" wire:target="uploadFiles,saveFilesToTask">
                                        <i class="bi bi-cloud-upload me-1"></i>Salvar arquivos
                                    </button>
                                </div>
                                <div class="small text-muted mt-2">PDF, JPG, PNG, DOCX, XLSX — máx. 10MB por arquivo.</div>
                            </div>
                            <div class="mt-3">
                                <div class="small fw-semibold text-muted mb-2">Lista de envio (editar nome/remover antes de salvar)</div>
                                <div class="queue-list">
                                    @foreach($uploadFiles as $i => $file)
                                        @php
                                            $qName = $uploadNames[$i] ?? $file->getClientOriginalName();
                                            $qExt = strtolower(pathinfo((string) $qName, PATHINFO_EXTENSION));
                                            $qSize = method_exists($file, 'getSize') ? (int) $file->getSize() : 0;
                                            $queueIcon = match($qExt) {
                                                'pdf' => 'bi-filetype-pdf text-danger',
                                                'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => 'bi-file-image text-info',
                                                'xls', 'xlsx', 'csv' => 'bi-file-earmark-spreadsheet text-success',
                                                'doc', 'docx', 'odt' => 'bi-file-earmark-word text-primary',
                                                'zip', 'rar', '7z' => 'bi-file-earmark-zip text-warning',
                                                default => 'bi-file-earmark text-secondary',
                                            };
                                        @endphp
                                        <div class="queue-item">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi {{ $queueIcon }}"></i>
                                                <input type="text"
                                                       class="form-control form-control-sm queue-name-input"
                                                       wire:model.defer="uploadNames.{{ $i }}"
                                                       placeholder="Nome do arquivo">
                                                <button type="button" class="btn btn-sm btn-outline-danger queue-remove-btn" wire:click="removeUploadFile({{ $i }})" title="Remover da fila">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="queue-meta">{{ strtoupper($qExt ?: '-') }} · {{ number_format($qSize / 1024, 1, ',', '.') }} KB</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Files already saved — offer adding more --}}
                            <div class="mt-2">
                                <div class="upload-zone">
                                    <div class="upload-zone-head">
                                        <div class="small fw-semibold text-muted">Adicionar mais evidências</div>
                                        <div class="upload-kpis">
                                            <span class="upload-chip">{{ $queuedCount }} na fila</span>
                                        </div>
                                    </div>
                                    <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                                    <div class="upload-actions">
                                        <select class="form-select form-select-sm" wire:model="fileVisibility" disabled>
                                            <option value="shared">Compartilhado</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary px-3" wire:click="saveFilesToTask" wire:loading.attr="disabled" wire:target="uploadFiles,saveFilesToTask">
                                            <i class="bi bi-cloud-upload me-1"></i>Salvar arquivos
                                        </button>
                                    </div>
                                    <div class="small text-muted mt-2">PDF, JPG, PNG, DOCX, XLSX — máx. 10MB por arquivo.</div>
                                </div>
                                @if(!empty($uploadFiles))
                                    <div class="mt-3">
                                        <div class="small fw-semibold text-muted mb-2">Lista de envio (editar nome/remover antes de salvar)</div>
                                        <div class="queue-list">
                                            @foreach($uploadFiles as $i => $file)
                                                @php
                                                    $qName = $uploadNames[$i] ?? $file->getClientOriginalName();
                                                    $qExt = strtolower(pathinfo((string) $qName, PATHINFO_EXTENSION));
                                                    $qSize = method_exists($file, 'getSize') ? (int) $file->getSize() : 0;
                                                    $queueIcon = match($qExt) {
                                                        'pdf' => 'bi-filetype-pdf text-danger',
                                                        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => 'bi-file-image text-info',
                                                        'xls', 'xlsx', 'csv' => 'bi-file-earmark-spreadsheet text-success',
                                                        'doc', 'docx', 'odt' => 'bi-file-earmark-word text-primary',
                                                        'zip', 'rar', '7z' => 'bi-file-earmark-zip text-warning',
                                                        default => 'bi-file-earmark text-secondary',
                                                    };
                                                @endphp
                                                <div class="queue-item">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi {{ $queueIcon }}"></i>
                                                        <input type="text"
                                                               class="form-control form-control-sm queue-name-input"
                                                               wire:model.defer="uploadNames.{{ $i }}"
                                                               placeholder="Nome do arquivo">
                                                        <button type="button" class="btn btn-sm btn-outline-danger queue-remove-btn" wire:click="removeUploadFile({{ $i }})" title="Remover da fila">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                    <div class="queue-meta">{{ strtoupper($qExt ?: '-') }} · {{ number_format($qSize / 1024, 1, ',', '.') }} KB</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- 3. Response Form --}}
                <div class="table-card mb-0">
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
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Resumo da resposta *</label>
                                    <textarea class="form-control" rows="6" wire:model="responseSummary"
                                              placeholder="Descreva sua resposta detalhadamente (mín. 20 caracteres)"></textarea>
                                    <div class="form-text small text-muted">
                                        "Seu texto é salvo automaticamente enquanto você digita"
                                    </div>
                                    @error('responseSummary')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Motivo da impossibilidade *</label>
                                    <textarea class="form-control" rows="5" wire:model="impossibilityReason"
                                              placeholder="Descreva por que não é possível atender (mín. 20 caracteres)"></textarea>
                                    @error('impossibilityReason')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Documentação de suporte (opcional)</label>
                                    <div class="alert alert-light border small mb-0">
                                        Os arquivos de evidência devem ser salvos no bloco acima antes de concluir.
                                    </div>
                                </div>
                            @endif

                            {{-- Evidence validation error --}}
                            @error('evidence')
                                <div class="alert alert-danger small d-flex align-items-start gap-2 mb-3">
                                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            {{-- Confirmation inline --}}
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

            </div>{{-- end col-8 --}}

            {{-- ================================================
                 COLUNA DIREITA col-4 — Contexto / Info (sticky)
                 ================================================ --}}
            <div class="col-lg-4">
                <div class="right-panel">

                    {{-- 1. Process summary compact --}}
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold" style="font-size:.82rem;">
                            <i class="bi bi-file-text me-2"></i>Resumo do Processo
                        </div>
                        <div class="card-body" style="padding:.9rem;">
                            <div class="proc-grid-compact">
                                <div class="proc-item-compact">
                                    <div class="proc-label-sm">Número Processo</div>
                                    <div class="proc-value-sm">{{ $demand->source_process_number_masked ?? 'Não informado' }}</div>
                                </div>
                                <div class="proc-item-compact">
                                    <div class="proc-label-sm">Número Caso</div>
                                    <div class="proc-value-sm">{{ $demand->source_case_number ?? 'Não informado' }}</div>
                                </div>
                                <div class="proc-item-compact">
                                    <div class="proc-label-sm">Tipo</div>
                                    <div class="proc-value-sm">{{ $sourceTypeLabel }}</div>
                                </div>
                                <div class="proc-item-compact">
                                    <div class="proc-label-sm">Status Externo</div>
                                    <div class="proc-value-sm">{{ $demand->external_status ?? '—' }}</div>
                                </div>
                                <div class="proc-item-compact">
                                    <div class="proc-label-sm">Prazo Judicial</div>
                                    <div class="proc-value-sm"><x-legal.due-date-chip :date="$demand->source_due_at" /></div>
                                </div>
                                <div class="proc-item-compact">
                                    <div class="proc-label-sm">Status Fluxo</div>
                                    <div class="proc-value-sm">{{ $demand->external_flow_status ?? '—' }}</div>
                                </div>
                            </div>

                            @if($sourceContext->isNotEmpty())
                                <hr class="my-2">
                                @foreach($sourceContext->take(3) as $ctx)
                                    <div class="proc-item-compact mb-1">
                                        <div class="proc-label-sm">{{ $ctx['label'] }}</div>
                                        <div class="proc-value-sm">{{ Str::limit((string) $ctx['value'], 80) }}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- 2. Shared Documents --}}
                    <div class="table-card" x-data="{ docFilter: 'all' }">
                        <div class="card-header text-bg-dark fw-bold" style="font-size:.82rem;">
                            <i class="bi bi-paperclip me-2"></i>Documentos Compartilhados
                        </div>
                        <div class="card-body" style="padding:.9rem;">
                            @if($sharedFiles->isEmpty())
                                <p class="text-muted small mb-0">Nenhum documento compartilhado.</p>
                            @else
                                <div class="shared-doc-filters">
                                    <button type="button" class="shared-doc-filter" :class="{ active: docFilter === 'all' }" @click="docFilter = 'all'">
                                        Todos ({{ $sharedFiles->count() }})
                                    </button>
                                    @if($sharedImages->isNotEmpty())
                                        <button type="button" class="shared-doc-filter" :class="{ active: docFilter === 'images' }" @click="docFilter = 'images'">
                                            Imagens ({{ $sharedImages->count() }})
                                        </button>
                                    @endif
                                    @if($sharedOthers->isNotEmpty())
                                        <button type="button" class="shared-doc-filter" :class="{ active: docFilter === 'files' }" @click="docFilter = 'files'">
                                            Arquivos ({{ $sharedOthers->count() }})
                                        </button>
                                    @endif
                                </div>

                                @if($sharedImages->isNotEmpty())
                                    <div x-show="docFilter === 'all' || docFilter === 'images'">
                                        <div class="small fw-semibold text-muted mb-2">Galeria de imagens</div>
                                        <div class="legal-evidence-grid mb-3">
                                        @foreach($sharedImages as $index => $file)
                                            @php
                                                $filePath = $file->path ?? $file->file_path ?? null;
                                                $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                                $fileDemandType = $file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '');
                                                $fileDemandLabel = match($fileDemandType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => 'Demanda' };
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
                                                    <div class="d-flex flex-wrap gap-1 justify-content-center mt-1">
                                                        <span class="shared-doc-tag">{{ $fileDemandLabel }}</span>
                                                        <span class="shared-doc-tag shared">Compartilhado</span>
                                                    </div>
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                        <i class="bi bi-download me-1"></i>Baixar
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($sharedOthers->isNotEmpty())
                                    <div x-show="docFilter === 'all' || docFilter === 'files'">
                                    <div class="small fw-semibold text-muted mb-2">Lista de arquivos</div>
                                        @foreach($sharedOthers as $file)
                                            @php
                                                $filePath = $file->path ?? $file->file_path ?? null;
                                                $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                                                $name = $file->original_name ?? ($filePath ? basename($filePath) : 'Arquivo');
                                                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                                $fileDemandType = $file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '');
                                                $fileDemandLabel = match($fileDemandType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => 'Demanda' };
                                                $fileIcon = match($ext) {
                                                    'pdf' => 'bi-filetype-pdf text-danger',
                                                    'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => 'bi-file-image text-info',
                                                    'xls', 'xlsx', 'csv' => 'bi-file-earmark-spreadsheet text-success',
                                                    'doc', 'docx', 'odt' => 'bi-file-earmark-word text-primary',
                                                    'zip', 'rar', '7z' => 'bi-file-earmark-zip text-warning',
                                                    default => 'bi-file-earmark text-secondary',
                                                };
                                            @endphp
                                            @if($fileUrl)
                                                <div class="shared-doc-list-item">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="shared-doc-icon"><i class="bi {{ $fileIcon }}"></i></span>
                                                        <div>
                                                            <div class="legal-evidence-name small" title="{{ $name }}" style="max-width:140px;">{{ $name }}</div>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <span class="shared-doc-tag">{{ $fileDemandLabel }}</span>
                                                                <span class="shared-doc-tag shared">{{ strtoupper($ext ?: 'ARQ') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- 3. Assignment History (collapsible) --}}
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold" style="font-size:.82rem;">
                            <i class="bi bi-clock-history me-2"></i>Histórico da Atribuição
                        </div>
                        <div class="card-body" style="padding:.9rem;">
                            <details class="collapsible-section" open>
                                <summary>
                                    <span>Ver histórico</span>
                                    <i class="bi bi-chevron-down small"></i>
                                </summary>
                                <div class="collapsible-body">
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
                            </details>
                        </div>
                    </div>

                    {{-- 4. Subdemands (collapsible) --}}
                    @if($demand->subdemands->isNotEmpty())
                        <div class="table-card mb-0">
                            <div class="card-header text-bg-dark fw-bold" style="font-size:.82rem;">
                                <i class="bi bi-diagram-3 me-2"></i>Subdemandas ({{ $demand->subdemands->count() }})
                            </div>
                            <div class="card-body" style="padding:.9rem;">
                                @foreach($demand->subdemands as $sub)
                                    @php
                                        $subStatus = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
                                        $subStatusLabel = $sub->status instanceof \App\Enum\LegalDemandSubdemandStatus
                                            ? $sub->status->label()
                                            : \Illuminate\Support\Str::headline(str_replace('_', ' ', $subStatus));
                                        $subBadge = match($subStatus) {
                                            'concluida' => 'badge bg-success',
                                            'encerrada_controlador' => 'badge bg-secondary',
                                            'em_andamento' => 'badge bg-warning text-dark',
                                            'aguardando_retorno' => 'badge bg-info text-dark',
                                            default => 'badge bg-primary',
                                        };
                                    @endphp
                                    <details class="subd-card">
                                        <summary class="subd-summary">
                                            <div>
                                                <div class="fw-semibold" style="font-size:.85rem;">
                                                    Subdemanda #{{ $sub->id }}
                                                    <span class="{{ $subBadge }} ms-1">{{ $subStatusLabel }}</span>
                                                </div>
                                                <div class="subd-meta">
                                                    {{ $sub->assignedTo?->name ?? ($sub->assigned_area_name ?: 'Não definido') }}
                                                    · {{ $sub->deadline_at ? \Carbon\Carbon::parse($sub->deadline_at)->format('d/m/Y') : '—' }}
                                                </div>
                                            </div>
                                            <small class="text-muted">expandir</small>
                                        </summary>
                                        <div class="subd-body">
                                            <div class="small mb-2">
                                                Criada em {{ $sub->created_at?->format('d/m/Y H:i') ?? '—' }}
                                                @if($sub->resolution)
                                                    <div class="mt-1"><strong>Resolução:</strong> {{ \Illuminate\Support\Str::limit((string) $sub->resolution, 180) }}</div>
                                                @endif
                                            </div>
                                            <div class="mb-2">
                                                <a href="{{ route('legal.subdemand.detail', $sub->uuid) }}" class="btn btn-sm btn-outline-primary">Abrir detalhe</a>
                                            </div>
                                            <div class="subd-events">
                                                @forelse($sub->events->sortByDesc('occurred_at')->take(6) as $event)
                                                    <div class="subd-event">
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="badge bg-light text-dark border">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $event->event_type)) }}</span>
                                                            <small class="text-muted">{{ $event->occurred_at ? \Carbon\Carbon::parse($event->occurred_at)->format('d/m/Y H:i') : '—' }}</small>
                                                        </div>
                                                        <div class="small mt-1">{{ $event->description ?: 'Atualização registrada.' }}</div>
                                                        <div class="small text-muted">{{ $event->actor?->name ?: 'Sistema' }}</div>
                                                    </div>
                                                @empty
                                                    <div class="small text-muted">Sem histórico nesta subdemanda.</div>
                                                @endforelse
                                            </div>
                                            <hr>
                                            <div class="small fw-semibold mb-2">Comentários</div>
                                            @php
                                                $subComments = $demand->comments
                                                    ->where('legal_demand_subdemand_id', $sub->id)
                                                    ->where('visibility', 'shared')
                                                    ->sortBy(fn ($comment) => $comment->created_at?->timestamp ?? 0)
                                                    ->values();
                                            @endphp
                                            <div class="conversation-thread auto-scroll-chat mb-2">
                                                @forelse($subComments as $comment)
                                                    @php
                                                        $isMine = !$externalAccess && (string) ($comment->user_id ?? '') === (string) auth()->id();
                                                        $author = $comment->user?->name ?: 'Executante externo';
                                                    @endphp
                                                    <div class="conversation-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                                        <div>{{ $comment->comment }}</div>
                                                        <div class="conversation-meta">
                                                            <span class="fw-semibold">{{ $author }}</span>
                                                            <span>{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="small text-muted">Sem comentários.</div>
                                                @endforelse
                                            </div>
                                            @if($externalAccess || (string) $sub->assigned_to_user_id === (string) auth()->id())
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" wire:model.defer="subdemandCommentInput.{{ $sub->id }}" placeholder="Escreva um comentário desta subdemanda">
                                                    <button class="btn btn-outline-primary" wire:click="addSubdemandComment({{ $sub->id }})">Enviar</button>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>{{-- end right-panel --}}
            </div>{{-- end col-4 --}}

        </div>{{-- end row --}}

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

        <x-show-loading />
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
