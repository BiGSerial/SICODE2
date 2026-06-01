<div class="ld-page">
    <style>
        .ld-page { color: #0f172a; }
        .ld-wrap { width: 100%; }
        .ld-header {
            background: linear-gradient(120deg, #0f2d5f, #1f3f6f 70%);
            border-radius: 14px;
            padding: 22px 26px;
            color: #eff6ff;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }
        .ld-header::after {
            content: '#{{ $subdemand->id }}';
            position: absolute;
            right: 20px;
            bottom: -20px;
            font-size: 76px;
            font-weight: 800;
            opacity: .14;
        }
        .ld-title { font-size: 32px; font-weight: 700; line-height: 1.1; margin: 0; }
        .ld-subtitle { font-size: 14px; opacity: .95; margin-top: 6px; }
        .ld-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .ld-badge { font-size: 11px; font-weight: 600; padding: 5px 10px; border-radius: 16px; background: #fff; color: #334155; }
        .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; }
        .panel-h { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; text-transform: uppercase; color: #0f172a; }
        .panel-b { padding: 12px 14px; }
        .kv { display: grid; grid-template-columns: 220px 1fr; gap: 8px; font-size: 13px; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
        .kv:last-child { border-bottom: 0; }
        .k { color: #64748b; font-weight: 700; }
        .v { color: #0f172a; }
    </style>

    @php
        $statusValue = $subdemand->status instanceof \BackedEnum ? $subdemand->status->value : (string) $subdemand->status;
        $statusLabel = $subdemand->status instanceof \App\Enum\LegalDemandSubdemandStatus
            ? $subdemand->status->label()
            : \Illuminate\Support\Str::headline(str_replace('_', ' ', $statusValue));
    @endphp

    <div class="ld-wrap">
        <div class="ld-header">
            <a href="{{ route('legal.demand.detail', $subdemand->demand->uuid) }}" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:12px; margin-bottom:10px;">← Voltar para demanda</a>
            <h1 class="ld-title">Subdemanda #{{ $subdemand->id }}</h1>
            <div class="ld-subtitle">
                Demanda #{{ $subdemand->demand->source_case_number ?? $subdemand->demand->id }} ·
                {{ $subdemand->demand->legalCase->company_name ?? '—' }}
            </div>
            <div class="ld-badges">
                <span class="ld-badge">{{ $statusLabel }}</span>
                <span class="ld-badge">{{ $subdemand->assignedTo?->name ?? 'Sem executante interno' }}</span>
                <span class="ld-badge">Prazo: {{ $subdemand->deadline_at ? $subdemand->deadline_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
        </div>

        <div class="panel">
            <div class="panel-h">Resumo</div>
            <div class="panel-b">
                <div class="kv"><div class="k">Processo</div><div class="v">{{ $subdemand->demand->source_process_number ?? '—' }}</div></div>
                <div class="kv"><div class="k">Tipo</div><div class="v">{{ $subdemand->demand->source_type ?? '—' }}</div></div>
                <div class="kv"><div class="k">Criada por</div><div class="v">{{ $subdemand->createdBy?->name ?? 'Sistema' }}</div></div>
                <div class="kv"><div class="k">Criada em</div><div class="v">{{ $subdemand->created_at?->format('d/m/Y H:i') ?? '—' }}</div></div>
                <div class="kv"><div class="k">Descrição</div><div class="v">{{ $subdemand->description ?: '—' }}</div></div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-h">Comentários</div>
            <div class="panel-b">
                @forelse($subdemand->comments->sortByDesc('created_at') as $comment)
                    <div class="border rounded p-2 mb-2">
                        <div class="small">{{ $comment->comment }}</div>
                        <div class="small text-muted mt-1">{{ $comment->user?->name ?: 'Executante externo' }} · {{ $comment->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Sem comentários registrados.</div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel-h">Arquivos</div>
            <div class="panel-b">
                @forelse($subdemand->files->where('removed_at', null)->sortByDesc('created_at') as $file)
                    @php
                        $filePath = $file->path ?? $file->file_path ?? null;
                        $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                    @endphp
                    @if($fileUrl)
                        <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-2">
                            <div class="small text-truncate me-2">
                                {{ $file->original_name ?? basename($filePath) }}
                                <span class="text-muted">· {{ $file->uploadedBy?->name ?: 'Externo' }}</span>
                            </div>
                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2">Abrir</a>
                        </div>
                    @endif
                @empty
                    <div class="text-muted small">Sem arquivos registrados.</div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel-h">Auditoria</div>
            <div class="panel-b">
                @forelse($subdemand->events->sortByDesc('occurred_at') as $event)
                    <div class="border rounded p-2 mb-2">
                        <div class="small fw-semibold">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $event->event_type)) }}</div>
                        <div class="small">{{ $event->description ?: 'Atualização registrada.' }}</div>
                        <div class="small text-muted mt-1">{{ $event->actor?->name ?: 'Sistema' }} · {{ $event->occurred_at ? \Carbon\Carbon::parse($event->occurred_at)->format('d/m/Y H:i') : '—' }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Sem eventos de auditoria.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

