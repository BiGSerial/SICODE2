<div class="ld-page">
    <style>
        .ld-page { color: #0f172a; padding-bottom: 32px; }
        .ld-wrap { width: 100%; }

        /* Header */
        .sd-header {
            background: linear-gradient(120deg, #0f2d5f, #1f3f6f 70%);
            border-radius: 14px 14px 0 0;
            padding: 20px 24px;
            color: #eff6ff;
            position: relative;
            overflow: hidden;
        }
        .sd-header::after {
            content: '#{{ $subdemand->id }}';
            position: absolute; right: 18px; bottom: -18px;
            font-size: 72px; font-weight: 800; opacity: .12; line-height: 1;
        }
        .sd-title { font-size: 26px; font-weight: 700; line-height: 1.1; margin: 0 0 4px; }
        .sd-sub   { font-size: 13px; opacity: .9; margin-bottom: 10px; }
        .sd-badges { display: flex; gap: 6px; flex-wrap: wrap; }
        .sd-badge {
            font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 999px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .sd-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; }
        .b-open   { background:#fef9c3; color:#78350f; }
        .b-wait   { background:#dbeafe; color:#1e40af; }
        .b-done   { background:#d1fae5; color:#065f46; }
        .b-closed { background:#f1f5f9; color:#475569; }
        .b-gray   { background:rgba(255,255,255,.15); color:#fff; }

        /* Review action bar — full width below header */
        .sd-review-bar {
            background: #fefce8;
            border: 2px solid #fcd34d;
            border-top: none;
            border-radius: 0 0 12px 12px;
            padding: 14px 24px;
            margin-bottom: 14px;
        }
        .sd-review-bar .rv-title {
            font-size: 12px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .07em; color: #78350f;
            display: flex; align-items: center; gap: 7px; margin-bottom: 10px;
        }
        .sd-review-bar .rv-answer {
            background: #fff; border-left: 4px solid #f59e0b; border-radius: 6px;
            padding: 8px 12px; font-size: 13px; color: #0f172a; margin-bottom: 10px;
        }
        .sd-review-bar .rv-meta { font-size: 11px; color: #92400e; margin-bottom: 10px; }

        /* Return form */
        .sd-return-form {
            background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px;
            padding: 12px 14px; margin-top: 10px;
        }

        /* Layout */
        .sd-main { display: grid; grid-template-columns: 1fr 300px; gap: 14px; align-items: start; }

        /* Sections */
        .sd-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 12px; }
        .sd-sh { background: #0f172a; color: #e2e8f0; padding: 9px 14px; font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
        .sd-sb { padding: 12px 14px; }

        .kv { display: grid; grid-template-columns: 180px 1fr; gap: 8px; font-size: 13px; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
        .kv:last-child { border-bottom: 0; }
        .kl { color: #64748b; font-weight: 700; font-size: 11px; }
        .kv-v { color: #0f172a; }

        /* Comment bubbles */
        .comment-thread { display: flex; flex-direction: column; gap: 7px; max-height: 300px; overflow-y: auto; }
        .cb { padding: 8px 11px; border-radius: 8px; font-size: 12px; line-height: 1.45; border: 1px solid #e2e8f0; background: #fff; }
        .cb.ctrl { border-left: 3px solid #1e3a8a; background: #f0f7ff; }
        .cb.exec { border-left: 3px solid #10b981; background: #f0fdf4; }
        .cb-meta { font-size: 10px; color: #64748b; margin-top: 3px; }

        /* File chips */
        .file-chip {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;
            margin-bottom: 6px; font-size: 12px;
        }
        .file-chip a { margin-left: auto; }

        /* Timeline */
        .ev-list { display: flex; flex-direction: column; gap: 8px; }
        .ev-item { display: grid; grid-template-columns: 20px 1fr; gap: 8px; }
        .ev-dot { width: 10px; height: 10px; border-radius: 50%; background: #1d4ed8; margin-top: 4px; flex-shrink: 0; }
        .ev-content { background: #f8fbff; border: 1px solid #dbeafe; border-radius: 8px; padding: 7px 10px; }
        .ev-type { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #1e40af; margin-bottom: 3px; }
        .ev-desc { font-size: 12px; color: #0f172a; }
        .ev-meta { font-size: 11px; color: #64748b; margin-top: 2px; }

        @media (max-width: 800px) { .sd-main { grid-template-columns: 1fr; } }
    </style>

    @php
        $sv = $subdemand->status instanceof \BackedEnum ? $subdemand->status->value : (string) $subdemand->status;
        $sl = $subdemand->status instanceof \App\Enum\LegalDemandSubdemandStatus
            ? $subdemand->status->label()
            : \Illuminate\Support\Str::headline(str_replace('_', ' ', $sv));
        $isOpen    = !in_array($sv, ['concluida','encerrada_controlador']);
        $isOverdue = $isOpen && $subdemand->deadline_at && $subdemand->deadline_at->isPast();
        $isExt     = (bool) data_get($subdemand->metadata ?? [], 'external_dispatch');
        $statusBadge = match($sv) {
            'concluida'             => 'b-done',
            'encerrada_controlador' => 'b-closed',
            'aguardando_retorno'    => 'b-wait',
            default                 => 'b-open',
        };
        $fileIcon = fn(string $n) => match(strtolower(pathinfo($n, PATHINFO_EXTENSION))) {
            'pdf'                   => 'bi-filetype-pdf text-danger',
            'jpg','jpeg','png','webp','gif' => 'bi-file-image text-info',
            'xls','xlsx','csv'      => 'bi-file-earmark-spreadsheet text-success',
            'doc','docx'            => 'bi-file-earmark-word text-primary',
            default                 => 'bi-file-earmark text-secondary',
        };
    @endphp

    <div class="ld-wrap">

        {{-- Header --}}
        <div class="sd-header">
            <a href="{{ route('legal.demand.detail', $subdemand->demand->uuid) }}" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:12px;margin-bottom:8px">
                ← Voltar para demanda
            </a>
            <h1 class="sd-title">Subdemanda #{{ $subdemand->id }}</h1>
            <div class="sd-sub">
                Demanda #{{ $subdemand->demand->source_case_number ?? $subdemand->demand->id }}
                · {{ $subdemand->demand->legalCase->company_name ?? '—' }}
            </div>
            <div class="sd-badges">
                <span class="sd-badge {{ $statusBadge }}">{{ $sl }}</span>
                <span class="sd-badge b-gray">
                    {{ $isExt ? data_get($subdemand->metadata,'external_contact_name','Externo') : ($subdemand->assignedTo?->name ?? 'Sem executante') }}
                </span>
                @if($subdemand->deadline_at)
                    <span class="sd-badge {{ $isOverdue ? 'b-open' : 'b-gray' }}">
                        {{ $isOverdue ? '⚠ Vencido: ' : 'Prazo: ' }}{{ $subdemand->deadline_at->format('d/m/Y H:i') }}
                    </span>
                @endif
                @if($answeredAssignment)
                    <span class="sd-badge" style="background:#fef9c3;color:#78350f">
                        <i class="bi bi-bell-fill me-1"></i>Aguarda sua revisão
                    </span>
                @endif
            </div>
        </div>

        {{-- Bloco de revisão (só aparece quando executante respondeu) --}}
        @if($answeredAssignment && $isController)
            <div class="sd-review-bar">
                <div class="rv-title">
                    <i class="bi bi-bell-fill text-warning"></i>
                    Executante enviou resposta — ação necessária
                </div>
                @if($answeredAssignment->answer)
                    <div class="rv-answer">{{ $answeredAssignment->answer }}</div>
                @endif
                <div class="rv-meta">
                    Respondida por <strong>{{ $answeredAssignment->toUser?->name ?? 'Executante' }}</strong>
                    em {{ $answeredAssignment->answered_at ? \Carbon\Carbon::parse($answeredAssignment->answered_at)->format('d/m/Y H:i') : '—' }}
                </div>

                @if($showReturnForm)
                    <div class="sd-return-form">
                        <label class="form-label small fw-semibold mb-1">Motivo da devolução *</label>
                        <textarea class="form-control form-control-sm mb-2" rows="3" wire:model.defer="returnReason"
                                  placeholder="Descreva o que precisa ser corrigido (mín. 10 caracteres)"></textarea>
                        @error('returnReason')<div class="text-danger small mb-1">{{ $message }}</div>@enderror
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-secondary" wire:click="$set('showReturnForm', false)">Cancelar</button>
                            <button class="btn btn-sm btn-warning" wire:click="returnForCorrection">
                                <i class="bi bi-arrow-return-left me-1"></i>Devolver para Correção
                            </button>
                        </div>
                    </div>
                @elseif($showApproveConfirm)
                    <div class="sd-return-form" style="background:#f0fdf4;border-color:#bbf7d0">
                        <div class="fw-semibold small mb-2">Confirmar aprovação desta resposta?</div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-secondary" wire:click="$set('showApproveConfirm', false)">Cancelar</button>
                            <button class="btn btn-sm btn-success" wire:click="approveReturn">
                                <i class="bi bi-check2-all me-1"></i>Confirmar Aprovação
                            </button>
                        </div>
                    </div>
                @else
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" wire:click="$set('showApproveConfirm', true)">
                            <i class="bi bi-check2-all me-1"></i>Aprovar Retorno
                        </button>
                        <button class="btn btn-sm btn-outline-warning" wire:click="$set('showReturnForm', true)">
                            <i class="bi bi-arrow-return-left me-1"></i>Devolver para Correção
                        </button>
                    </div>
                @endif
            </div>
        @elseif(!$answeredAssignment)
            {{-- Sem resposta ainda: espaço entre header e conteúdo --}}
            <div style="height:14px"></div>
        @endif

        {{-- Conteúdo --}}
        <div class="sd-main">

            {{-- Coluna principal --}}
            <div>
                {{-- Detalhes --}}
                <div class="sd-section">
                    <div class="sd-sh">Informações da Subdemanda</div>
                    <div class="sd-sb">
                        <div class="kv"><div class="kl">Processo</div><div class="kv-v">{{ $subdemand->demand->source_process_number ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Tipo</div><div class="kv-v">{{ $subdemand->demand->source_type instanceof \BackedEnum ? $subdemand->demand->source_type->value : ($subdemand->demand->source_type ?? '—') }}</div></div>
                        <div class="kv"><div class="kl">Executante</div><div class="kv-v">
                            @if($isExt)
                                <i class="bi bi-globe2 me-1 text-warning"></i>
                                {{ data_get($subdemand->metadata,'external_contact_name','Externo') }}
                                @if(data_get($subdemand->metadata,'external_contact_email'))
                                    <span class="text-muted">· {{ data_get($subdemand->metadata,'external_contact_email') }}</span>
                                @endif
                            @else
                                {{ $subdemand->assignedTo?->name ?? '—' }}
                                @if($subdemand->assignedTo?->Company?->name)
                                    <span class="text-muted">· {{ $subdemand->assignedTo->Company->name }}</span>
                                @endif
                            @endif
                        </div></div>
                        <div class="kv"><div class="kl">Criada por</div><div class="kv-v">{{ $subdemand->createdBy?->name ?? 'Sistema' }}</div></div>
                        <div class="kv"><div class="kl">Criada em</div><div class="kv-v">{{ $subdemand->created_at?->format('d/m/Y H:i') ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Prazo</div><div class="kv-v {{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                            {{ $subdemand->deadline_at ? $subdemand->deadline_at->format('d/m/Y H:i') : '—' }}
                            @if($isOverdue) <span class="badge bg-danger ms-1">Vencido</span> @endif
                        </div></div>
                        @if($subdemand->description)
                            <div class="kv"><div class="kl">Instrução</div><div class="kv-v">{{ $subdemand->description }}</div></div>
                        @endif
                    </div>
                </div>

                {{-- Comunicação --}}
                <div class="sd-section">
                    <div class="sd-sh">Comunicação</div>
                    <div class="sd-sb">
                        @if($subdemand->comments->isNotEmpty())
                            <div class="comment-thread">
                                @foreach($subdemand->comments->sortByDesc('created_at') as $comment)
                                    @php $isCtrl = $comment->user_id && $comment->user_id !== ($subdemand->assigned_to_user_id); @endphp
                                    <div class="cb {{ $isCtrl ? 'ctrl' : 'exec' }}">
                                        <div>{{ $comment->comment }}</div>
                                        <div class="cb-meta">
                                            <strong>{{ $comment->user?->name ?: 'Executante externo' }}</strong>
                                            · {{ $comment->created_at?->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-0">Nenhuma mensagem registrada.</p>
                        @endif
                    </div>
                </div>

                {{-- Arquivos enviados pelo executante --}}
                <div class="sd-section">
                    <div class="sd-sh">Arquivos enviados</div>
                    <div class="sd-sb">
                        @forelse($subdemand->files->where('removed_at', null)->sortByDesc('created_at') as $file)
                            @php
                                $fp  = $file->path ?? $file->file_path ?? null;
                                $fu  = $fp ? \Illuminate\Support\Facades\Storage::url($fp) : null;
                                $fn  = $file->original_name ?? ($fp ? basename($fp) : 'Arquivo');
                            @endphp
                            @if($fu)
                                <div class="file-chip">
                                    <i class="bi {{ $fileIcon($fn) }}"></i>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $fn }}">{{ $fn }}</div>
                                        <div style="font-size:11px;color:#64748b">{{ $file->uploadedBy?->name ?: 'Externo' }} · {{ strtoupper(pathinfo($fn, PATHINFO_EXTENSION)) }}</div>
                                    </div>
                                    <a href="{{ $fu }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Baixar
                                    </a>
                                </div>
                            @endif
                        @empty
                            <p class="text-muted small mb-0">Nenhum arquivo enviado.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Coluna lateral: auditoria --}}
            <div>
                <div class="sd-section">
                    <div class="sd-sh">Histórico de eventos</div>
                    <div class="sd-sb">
                        <div class="ev-list">
                            @forelse($subdemand->events->sortByDesc('occurred_at') as $event)
                                <div class="ev-item">
                                    <div><div class="ev-dot"></div></div>
                                    <div class="ev-content">
                                        <div class="ev-type">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string)$event->event_type)) }}</div>
                                        @if($event->description)
                                            <div class="ev-desc">{{ $event->description }}</div>
                                        @endif
                                        @if($event->reason)
                                            <div class="ev-desc text-warning">Motivo: {{ $event->reason }}</div>
                                        @endif
                                        <div class="ev-meta">
                                            {{ $event->actor?->name ?: 'Sistema' }}
                                            · {{ $event->occurred_at ? \Carbon\Carbon::parse($event->occurred_at)->format('d/m/Y H:i') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Sem eventos.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /sd-main --}}
    </div>{{-- /ld-wrap --}}
    <x-show-loading />
</div>
