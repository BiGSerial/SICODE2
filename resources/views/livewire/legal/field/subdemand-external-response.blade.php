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
            margin-bottom: 1rem;
        }
        .hero-title { font-size: 1.9rem; font-weight: 800; line-height: 1.1; margin-bottom: .35rem; }
        .hero-subtitle { font-size: .92rem; opacity: .85; margin-bottom: .45rem; text-transform: uppercase; letter-spacing: .02em; }
        .table-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15,23,42,.07);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .table-card .card-header.text-bg-dark {
            font-size: .88rem;
            padding: .85rem 1.1rem;
            letter-spacing: .01em;
        }
        .table-card .card-body { padding: 1.1rem; }
        .proc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem 1rem; }
        .proc-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .65rem;
            padding: .65rem .8rem;
        }
        .proc-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-bottom: .2rem; font-weight: 700; }
        .proc-value { font-size: .9rem; color: #0f172a; font-weight: 600; line-height: 1.3; }
        .event-box {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: .65rem;
            padding: .55rem .7rem;
            margin-bottom: .45rem;
        }
        .right-panel { position: sticky; top: 1rem; }
        .queue-item {
            border: 1px solid #e2e8f0;
            border-radius: .55rem;
            padding: .45rem .55rem;
            margin-bottom: .4rem;
            background: #f8fafc;
        }
        @media (max-width: 992px) {
            .proc-grid { grid-template-columns: 1fr; }
            .right-panel { position: static; }
        }
    </style>

    @php
        $demand = $subdemand->demand;
        $sourceTypeValue = $demand?->source_type instanceof \BackedEnum ? $demand->source_type->value : (string) ($demand?->source_type ?? '');
        $sourceTypeLabel = match($sourceTypeValue) {
            'subsidy' => 'Subsídio',
            'sentence' => 'Sentença',
            'injunction' => 'Liminar',
            default => $sourceTypeValue !== '' ? \Illuminate\Support\Str::headline($sourceTypeValue) : 'Demanda',
        };
        $sourceTypeClass = match($sourceTypeValue) {
            'subsidy' => 'badge bg-info text-dark',
            'sentence' => 'badge bg-warning text-dark',
            'injunction' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
        $subStatusValue = $subdemand->status instanceof \BackedEnum ? $subdemand->status->value : (string) $subdemand->status;
        $subStatusLabel = match($subStatusValue) {
            'aberta' => 'Aberta',
            'em_andamento' => 'Em andamento',
            'aguardando_retorno' => 'Aguardando retorno',
            'concluida' => 'Concluída',
            'encerrada_controlador' => 'Encerrada pelo controlador',
            default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $subStatusValue)),
        };
        $subStatusClass = match($subStatusValue) {
            'concluida' => 'badge bg-success',
            'encerrada_controlador' => 'badge bg-secondary',
            'em_andamento' => 'badge bg-warning text-dark',
            'aguardando_retorno' => 'badge bg-info text-dark',
            default => 'badge bg-primary',
        };
        $requiresEvidence = (bool) data_get($subdemand->metadata ?? [], 'requires_evidence', false);
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
                    <div class="hero-subtitle">
                        Caso {{ $demand?->source_case_number ?? 'Não informado' }} · {{ $sourceTypeLabel }}
                    </div>
                    <div class="small opacity-90">Canal de execução externa para resposta da subdemanda.</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="{{ $sourceTypeClass }}">{{ $sourceTypeLabel }}</span>
                        <span class="{{ $subStatusClass }}">Subdemanda: {{ $subStatusLabel }}</span>
                        <span class="badge bg-light text-dark border">Subdemanda #{{ $subdemand->id }}</span>
                        <span class="badge bg-light text-dark border">Prazo: {{ $subdemand->deadline_at ? $subdemand->deadline_at->format('d/m/Y H:i') : 'Não informado' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-diagram-3 me-2"></i>Contexto da Subdemanda
                    </div>
                    <div class="card-body">
                        <div class="proc-grid">
                            <div class="proc-item">
                                <div class="proc-label">Número do caso</div>
                                <div class="proc-value">{{ $demand?->source_case_number ?? '—' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Processo</div>
                                <div class="proc-value">{{ $demand?->source_process_number ?? '—' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Assunto da demanda</div>
                                <div class="proc-value">{{ $demand?->source_subject ?? '—' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Descrição da subdemanda</div>
                                <div class="proc-value">{{ $subdemand->description ?: '—' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Prazo da demanda principal</div>
                                <div class="proc-value">{{ $demand?->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at)->format('d/m/Y H:i') : 'Não informado' }}</div>
                            </div>
                            <div class="proc-item">
                                <div class="proc-label">Exigência de evidência</div>
                                <div class="proc-value">
                                    @if($requiresEvidence)
                                        <span class="badge bg-warning text-dark">Evidência obrigatória</span>
                                    @else
                                        <span class="badge bg-secondary">Evidência opcional</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-chat-left-text me-2"></i>Histórico de Comentários
                    </div>
                    <div class="card-body">
                        @forelse($subdemand->comments->sortByDesc('created_at')->take(20) as $commentItem)
                            <div class="event-box">
                                <div class="small">{{ $commentItem->comment }}</div>
                                <div class="small text-muted mt-1">
                                    {{ $commentItem->user?->name ?: 'Executante externo' }}
                                    · {{ $commentItem->created_at?->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Sem comentários registrados até o momento.</div>
                        @endforelse
                    </div>
                </div>

                <div class="table-card">
                    <div class="card-header text-bg-dark fw-bold">
                        <i class="bi bi-paperclip me-2"></i>Arquivos da Subdemanda
                    </div>
                    <div class="card-body">
                        @forelse($subdemand->files->where('removed_at', null)->sortByDesc('created_at') as $fileItem)
                            @php
                                $filePath = $fileItem->path ?? $fileItem->file_path ?? null;
                                $fileUrl = $filePath ? \Illuminate\Support\Facades\Storage::url($filePath) : null;
                            @endphp
                            @if($fileUrl)
                                <div class="event-box d-flex justify-content-between align-items-center">
                                    <div class="small text-truncate me-2">{{ $fileItem->original_name ?? basename($filePath) }}</div>
                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2">Abrir</a>
                                </div>
                            @endif
                        @empty
                            <div class="text-muted small">Sem arquivos anexados até o momento.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="right-panel">
                    <div class="table-card">
                        <div class="card-header text-bg-dark fw-bold">
                            <i class="bi bi-send-check me-2"></i>Enviar Retorno Externo
                        </div>
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success py-2">{{ session('success') }}</div>
                            @endif

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

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Pré-carga para envio</label>
                                <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                                @error('uploadFiles.*') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                @if(!empty($uploadFiles))
                                    <div class="mt-2">
                                        @foreach($uploadFiles as $i => $file)
                                            @php
                                                $qName = (string) ($uploadNames[$i] ?? $file->getClientOriginalName());
                                                $qExt = strtolower(pathinfo($qName, PATHINFO_EXTENSION));
                                                $qSize = (int) ($file->getSize() ?? 0);
                                            @endphp
                                            <div class="queue-item">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-paperclip"></i>
                                                    <input type="text" class="form-control form-control-sm" wire:model.defer="uploadNames.{{ $i }}" placeholder="Nome do arquivo">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeUploadFile({{ $i }})" title="Remover da fila">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                                <div class="small text-muted mt-1">{{ strtoupper($qExt ?: '-') }} · {{ number_format($qSize / 1024, 1, ',', '.') }} KB</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <button class="btn btn-primary w-100" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,uploadFiles">
                                <i class="bi bi-send me-1"></i>Enviar retorno
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
