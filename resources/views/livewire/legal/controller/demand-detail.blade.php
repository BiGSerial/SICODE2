<div class="ld-page">
    <x-show-loading />

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg: #eef2f7;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --border: #e2e8f0;
            --border-2: #cbd5e1;
            --text: #0f172a;
            --text-2: #475569;
            --text-3: #64748b;

            --green: #0f766e;
            --green-bg: #e6fffa;
            --green-border: #99f6e4;

            --amber: #a16207;
            --amber-bg: #fef9c3;
            --amber-border: #fde68a;

            --red: #b91c1c;
            --red-bg: #fee2e2;
            --red-border: #fecaca;

            --blue: #1e3a8a;
            --blue-bg: #dbeafe;
            --blue-border: #93c5fd;
        }

        .ld-page {
            background: var(--bg);
            padding: 20px 0 34px;
            color: var(--text);
        }
        .ld-wrap {
            width: min(1420px, calc(100% - 32px));
            margin: 0 auto;
        }
        .ld-breadcrumb {
            font-size: 12px;
            color: var(--text-3);
            margin-bottom: 16px;
        }
        .ld-breadcrumb a { color: var(--text-2); text-decoration: none; }

        .ld-header {
            background: linear-gradient(120deg, #0f2d5f, #1f3f6f 70%);
            border-radius: 14px;
            padding: 24px 28px;
            color: #eff6ff;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }
        .ld-header::after {
            content: '#'+attr(data-case);
            content: '#' attr(data-case);
            position: absolute;
            right: 24px;
            bottom: -22px;
            font-size: 88px;
            font-weight: 800;
            opacity: .14;
            line-height: 1;
            letter-spacing: -.02em;
        }
        .ld-case { font-size: 34px; font-weight: 700; line-height: 1.05; margin: 0; }
        .ld-company { font-size: 12px; opacity: .88; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .05em; }
        .ld-subject { font-size: 16px; opacity: .96; margin-top: 6px; }
        .ld-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
        .ld-badge {
            font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 16px;
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid transparent;
        }
        .ld-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .b-green { color: var(--green); background: var(--green-bg); border-color: var(--green-border); }
        .b-amber { color: var(--amber); background: var(--amber-bg); border-color: var(--amber-border); }
        .b-red { color: var(--red); background: var(--red-bg); border-color: var(--red-border); }
        .b-blue { color: var(--blue); background: var(--blue-bg); border-color: var(--blue-border); }
        .b-gray { color: var(--text-2); background: var(--surface-2); border-color: var(--border); }

        .ld-main { display: grid; grid-template-columns: 1fr 330px; gap: 16px; align-items: start; }

        .ld-section, .ld-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .ld-section-header, .ld-panel-header {
            background: #0f172a;
            color: #e2e8f0;
            padding: 12px 16px;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .ld-section-body, .ld-panel-body { padding: 16px; }

        .field-grid { display: grid; gap: 10px; }
        .fg-2 { grid-template-columns: 1fr 1fr; }
        .fg-3 { grid-template-columns: 1fr 1fr 1fr; }
        .field {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 10px 12px;
        }
        .fl { font-size: 11px; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; }
        .fv { font-size: 22px; color: var(--text); line-height: 1.35; }
        .fv.small { font-size: 14px; }
        .fv.muted { color: var(--text-2); }
        .fv.mono { font-size: 13px; }

        .timeline { display: flex; flex-direction: column; }
        .tl-row { display: flex; gap: 12px; padding: 12px 0; position: relative; }
        .tl-row:not(:last-child)::after { content:''; position:absolute; left:5px; top:24px; bottom:0; width:1px; background: var(--border); }
        .tl-dot { width: 10px; height: 10px; border-radius: 50%; background: #1f3f6f; margin-top: 5px; z-index: 1; }
        .tl-title { font-size: 13px; color: var(--text); }
        .tl-date { font-size: 12px; color: var(--text-2); margin-top: 3px; }

        .kv { border-bottom: 1px solid var(--border); padding: 8px 0; }
        .kv:last-child { border-bottom: 0; }
        .kl { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; }
        .kv-val { font-size: 13px; color: var(--text); margin-top: 2px; }

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

        @media (max-width: 980px) {
            .ld-main { grid-template-columns: 1fr; }
            .fg-2, .fg-3 { grid-template-columns: 1fr; }
            .ld-case { font-size: 28px; }
        }
    </style>

    @php
        $srcType  = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : (string) $demand->source_type;
        $tipoLbl  = match($srcType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $srcType ?: '—' };
        $extBadge = $demand->externalStatusBadge();

        $specificPayload = is_array($demand->source_specific_payload) ? $demand->source_specific_payload : [];
        $rawPayload = is_array($demand->raw_payload) ? $demand->raw_payload : [];

        $sourceSpecificFields = match ($srcType) {
            'injunction' => [
                ['label' => 'Status da liminar', 'value' => $demand->source_status],
                ['label' => 'Área requerida', 'value' => data_get($specificPayload, 'required_area') ?? data_get($rawPayload, 'required_area')],
                ['label' => 'Cidade', 'value' => data_get($specificPayload, 'city') ?? data_get($rawPayload, 'city')],
                ['label' => 'Região', 'value' => data_get($specificPayload, 'region') ?? data_get($rawPayload, 'region')],
                ['label' => 'Regional', 'value' => data_get($specificPayload, 'regional') ?? data_get($rawPayload, 'regional')],
                ['label' => 'Analisado em', 'value' => data_get($specificPayload, 'source_analysis_at') ?? data_get($rawPayload, 'analysis_at')],
            ],
            'sentence' => [
                ['label' => 'Status da sentença', 'value' => $demand->source_status],
                ['label' => 'Serviço', 'value' => data_get($specificPayload, 'service_type') ?? data_get($rawPayload, 'agreement_or_conviction')],
                ['label' => 'Data da decisão', 'value' => $demand->source_decision_at],
                ['label' => 'Data de cumprimento', 'value' => $demand->source_end_at ?? $demand->source_executed_at],
                ['label' => 'Focal point', 'value' => data_get($rawPayload, 'focal_point')],
            ],
            'subsidy' => [
                ['label' => 'Status do subsídio', 'value' => $demand->source_status],
                ['label' => 'Área requerida', 'value' => data_get($specificPayload, 'required_area') ?? data_get($rawPayload, 'required_area')],
                ['label' => 'Prazo da fonte', 'value' => $demand->source_due_at],
                ['label' => 'Data de análise', 'value' => data_get($specificPayload, 'source_analysis_at') ?? data_get($rawPayload, 'analysis_at')],
                ['label' => 'Descrição da fonte', 'value' => $demand->source_description],
            ],
            default => [],
        };

        $sourceContext = [
            ['label' => 'Status processo (externo)', 'value' => $demand->external_status],
            ['label' => 'Status fluxo (origem)', 'value' => $demand->source_status],
            ['label' => 'Status fluxo normalizado', 'value' => $demand->source_status_group],
            ['label' => 'Área solicitante', 'value' => $demand->requesting_area_name],
            ['label' => 'Responsável solicitante', 'value' => $demand->requesting_responsible_name],
            ['label' => 'Área responsável', 'value' => $demand->responsible_area_name],
            ['label' => 'Responsável da demanda', 'value' => $demand->delegated_responsible_name],
            ['label' => 'Delegado por', 'value' => $demand->delegated_by_name],
            ['label' => 'Delegado em', 'value' => $demand->delegated_at],
            ['label' => 'Cidade', 'value' => data_get($specificPayload, 'city') ?? data_get($rawPayload, 'city')],
            ['label' => 'Região', 'value' => data_get($specificPayload, 'region') ?? data_get($rawPayload, 'region')],
            ['label' => 'Regional', 'value' => data_get($specificPayload, 'regional') ?? data_get($rawPayload, 'regional')],
            ['label' => 'Observação origem', 'value' => data_get($specificPayload, 'observation') ?? data_get($rawPayload, 'observation')],
        ];

        $sourceTimeline = collect([
            ['label' => 'Início', 'value' => $demand->source_started_at],
            ['label' => 'Análise', 'value' => $demand->source_analysis_at],
            ['label' => 'Prazo judicial', 'value' => $demand->source_due_at],
            ['label' => 'Execução', 'value' => $demand->source_executed_at],
            ['label' => 'Última alteração', 'value' => $demand->source_changed_at],
        ])->filter(fn ($i) => !empty($i['value']))->values();

        $activeFiles = $demand->files->where('removed_at', null)->values();
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'tiff', 'webp'];
        $imageFiles = $activeFiles->filter(function ($file) use ($imageExts) {
            $name = (string) ($file->original_name ?? $file->file_name ?? $file->path ?? $file->file_path ?? '');
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = strtolower((string) ($file->mime_type ?? ''));
            return in_array($ext, $imageExts, true) || str_starts_with($mime, 'image/');
        })->values();
        $otherFiles = $activeFiles->filter(function ($file) use ($imageFiles) {
            return !$imageFiles->contains('id', $file->id);
        })->values();
    @endphp

    <div class="ld-wrap">
        <div class="ld-breadcrumb">
            <a href="{{ route('legal.queue') }}">Fila de Demandas</a> / Demanda #{{ $demand->id }}
        </div>

        <div class="ld-header" data-case="{{ $demand->source_case_number ?? $demand->id }}">
            <a href="{{ route('legal.queue') }}" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:12px; margin-bottom:10px;">← Voltar para Fila</a>
            <div class="ld-company">{{ $demand->legalCase->company_name ?? '—' }}</div>
            <h1 class="ld-case">Demanda #{{ $demand->source_case_number ?? $demand->id }}</h1>
            <div class="ld-subject">{{ $demand->source_subject ?? 'Sem assunto' }}</div>
            <div class="ld-badges">
                <span class="ld-badge b-blue">{{ $tipoLbl }}</span>
                <span class="ld-badge b-gray">{{ $demand->external_status ?? 'Sem status externo' }}</span>
                <span class="ld-badge {{ str_contains((string)($demand->source_status_group ?? ''), 'closed') ? 'b-green' : 'b-amber' }}">{{ $demand->source_status ?? 'Sem status' }}</span>
                <span class="ld-badge b-gray">{{ $demand->legalCase->law_firm_name ?? 'Sem escritório' }}</span>
            </div>
        </div>

        <div class="ld-main">
            <div>
                <div class="ld-section">
                    <div class="ld-section-header">Identificação do processo</div>
                    <div class="ld-section-body">
                        <div class="field-grid fg-3" style="margin-bottom:10px">
                            <div class="field"><div class="fl">Número do caso</div><div class="fv small">{{ $demand->source_case_number ?? '—' }}</div></div>
                            <div class="field"><div class="fl">Natureza</div><div class="fv small">{{ $demand->legalCase->process_nature ?? '—' }}</div></div>
                            <div class="field"><div class="fl">Status externo</div><div class="fv small">{{ $demand->external_status ?? '—' }}</div></div>
                        </div>
                        <div class="field-grid fg-2" style="margin-bottom:10px">
                            <div class="field"><div class="fl">Número do processo (CNJ)</div><div class="fv mono">{{ $demand->source_process_number ?? '—' }}</div></div>
                            <div class="field"><div class="fl">Comarca / distrito</div><div class="fv small">{{ $demand->legalCase->district ?? '—' }}</div></div>
                        </div>
                        <div class="field-grid fg-2">
                            <div class="field"><div class="fl">Empresa</div><div class="fv small">{{ $demand->legalCase->company_name ?? '—' }}</div></div>
                            <div class="field"><div class="fl">Escritório</div><div class="fv small">{{ $demand->legalCase->law_firm_name ?? '—' }}</div></div>
                        </div>
                    </div>
                </div>

                @if($srcType === 'subsidy')
                    <div class="ld-section">
                        <div class="ld-section-header">Subsídio</div>
                        <div class="ld-section-body">
                            <div class="field-grid fg-3" style="margin-bottom:10px">
                                <div class="field">
                                    <div class="fl">Assunto</div>
                                    <div class="fv small">{{ $demand->source_subject ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Status do subsídio</div>
                                    <div class="fv small">{{ $demand->source_status ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Status atualizado em</div>
                                    <div class="fv small">
                                        {{ $demand->source_status_at ? \Carbon\Carbon::parse($demand->source_status_at)->format('d/m/Y H:i') : '—' }}
                                    </div>
                                </div>
                            </div>
                            <div class="field-grid fg-2">
                                <div class="field">
                                    <div class="fl">Área solicitante</div>
                                    <div class="fv small">{{ $demand->requesting_area_name ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Prazo de devolução</div>
                                    <div class="fv small">{{ $demand->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at)->format('d/m/Y') : '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($srcType === 'sentence')
                    <div class="ld-section">
                        <div class="ld-section-header">Sentença</div>
                        <div class="ld-section-body">
                            <div class="field-grid fg-3" style="margin-bottom:10px">
                                <div class="field">
                                    <div class="fl">Status da sentença</div>
                                    <div class="fv small">{{ $demand->source_status ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Status atualizado em</div>
                                    <div class="fv small">{{ $demand->source_status_at ? \Carbon\Carbon::parse($demand->source_status_at)->format('d/m/Y H:i') : '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Serviço / tipo</div>
                                    <div class="fv small">{{ data_get($specificPayload, 'service_type') ?? data_get($rawPayload, 'agreement_or_conviction') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="field-grid fg-2">
                                <div class="field">
                                    <div class="fl">Data da decisão</div>
                                    <div class="fv small">{{ $demand->source_decision_at ? \Carbon\Carbon::parse($demand->source_decision_at)->format('d/m/Y') : '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Data de cumprimento</div>
                                    <div class="fv small">{{ ($demand->source_end_at ?? $demand->source_executed_at) ? \Carbon\Carbon::parse($demand->source_end_at ?? $demand->source_executed_at)->format('d/m/Y') : '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($srcType === 'injunction')
                    <div class="ld-section">
                        <div class="ld-section-header">Liminar</div>
                        <div class="ld-section-body">
                            <div class="field-grid fg-3" style="margin-bottom:10px">
                                <div class="field">
                                    <div class="fl">Status da liminar</div>
                                    <div class="fv small">{{ $demand->source_status ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Status atualizado em</div>
                                    <div class="fv small">{{ $demand->source_status_at ? \Carbon\Carbon::parse($demand->source_status_at)->format('d/m/Y H:i') : '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="fl">Área requerida</div>
                                    <div class="fv small">{{ data_get($specificPayload, 'required_area') ?? data_get($rawPayload, 'required_area') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="field-grid fg-2">
                                <div class="field">
                                    <div class="fl">Cidade / Região</div>
                                    <div class="fv small">
                                        {{ data_get($specificPayload, 'city') ?? data_get($rawPayload, 'city') ?? '—' }}
                                        @if(data_get($specificPayload, 'region') || data_get($rawPayload, 'region'))
                                            — {{ data_get($specificPayload, 'region') ?? data_get($rawPayload, 'region') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="fl">Prazo judicial</div>
                                    <div class="fv small">{{ $demand->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at)->format('d/m/Y') : '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="ld-section">
                    <div class="ld-section-header">Delegação</div>
                    <div class="ld-section-body">
                        <div class="field-grid fg-2">
                            <div class="field">
                                <div class="fl">Delegado para</div>
                                <div class="fv small">{{ $demand->delegated_responsible_name ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="fl">Delegado em</div>
                                <div class="fv small">{{ $demand->delegated_at ? \Carbon\Carbon::parse($demand->delegated_at)->format('d/m/Y H:i') : '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ld-section">
                    <div class="ld-section-header">Contexto do processo (fonte)</div>
                    <div class="ld-section-body">
                        <div class="field-grid fg-2">
                            @foreach($sourceContext as $ctx)
                                @php
                                    $cv = $ctx['value'];
                                    if ($cv instanceof \Carbon\CarbonInterface) { $cv = $cv->format('d/m/Y H:i'); }
                                    elseif (is_string($cv) && preg_match('/^\d{4}-\d{2}-\d{2}/', $cv)) { try { $cv = \Carbon\Carbon::parse($cv)->format('d/m/Y H:i'); } catch (\Throwable $e) {} }
                                @endphp
                                <div class="field">
                                    <div class="fl">{{ $ctx['label'] }}</div>
                                    <div class="fv small">{{ $cv ?: '—' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ld-section">
                    <div class="ld-section-header">Histórico de eventos</div>
                    <div class="ld-section-body">
                        <div class="timeline">
                            @forelse($demand->events->sortByDesc('occurred_at') as $event)
                                @php $when = $event->occurred_at ?? $event->created_at; @endphp
                                <div class="tl-row">
                                    <div class="tl-dot"></div>
                                    <div>
                                        <div class="tl-title">{{ $event->description ?: \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $event->event_type)) }}</div>
                                        <div class="tl-date">{{ $when ? \Carbon\Carbon::parse($when)->format('d/m/Y H:i') : '—' }} @if($event->actor?->name) · {{ $event->actor->name }} @endif</div>
                                    </div>
                                </div>
                            @empty
                                <div class="tl-row"><div class="tl-dot"></div><div><div class="tl-title">Sem eventos registrados</div></div></div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="ld-section">
                    <div class="ld-section-header">Comentários</div>
                    <div class="ld-section-body">
                        <div class="mb-3">
                            <textarea class="form-control mb-2" rows="3" wire:model="newComment" placeholder="Adicionar comentário..."></textarea>
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

                <div class="ld-section">
                    <div class="ld-section-header">Notes associadas ao processo</div>
                    <div class="ld-section-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Buscar / informar notes (ID, número ou cliente)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control" wire:model.debounce.300ms="noteInput" placeholder="Ex.: 12345 67890 ou 500123;500124" />
                            </div>
                            <div class="small text-muted mb-2">Você pode digitar uma por vez (para usar o ADD) ou várias na mesma linha, separando por espaço, vírgula ou ;</div>
                            @if($searchedNotes->isNotEmpty())
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead><tr><th>ID</th><th>Note</th><th>Cliente</th><th>Status</th><th class="text-end">Ação</th></tr></thead>
                                        <tbody>
                                            @foreach($searchedNotes as $sn)
                                                <tr>
                                                    <td>{{ $sn->id }}</td>
                                                    <td>{{ $sn->note ?? '—' }}</td>
                                                    <td>{{ $sn->client ?? '—' }}</td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">{{ $sn->nstats ?? $sn->status ?? '—' }}</span>
                                                        @if($sn->dt_status)
                                                            <div class="text-muted small">{{ \Carbon\Carbon::parse($sn->dt_status)->format('d/m/Y H:i') }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-end"><button class="btn btn-sm btn-outline-primary" wire:click="attachSingleNote({{ $sn->id }})">ADD</button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <label class="form-label small fw-semibold">Contexto do vínculo (opcional)</label>
                            <input type="text" class="form-control form-control-sm mb-2" wire:model="noteLinkContext" placeholder="Ex.: processo relacionado ao mesmo atendimento" />
                            <button class="btn btn-sm btn-primary" wire:click="linkNotesToCase"><i class="bi bi-plus-circle me-1"></i>Associar Notes</button>
                        </div>

                        @if($linkedNotes->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Note ID</th><th>Número</th><th>Status atual</th><th>Data status</th><th>Vinculada em</th><th class="text-end">Ação</th></tr></thead>
                                    <tbody>
                                        @foreach($linkedNotes as $note)
                                            <tr>
                                                <td>{{ $note->id }}</td>
                                                <td>{{ $note->note ?? '—' }}</td>
                                                <td><span class="badge bg-light text-dark border">{{ $note->nstats ?? $note->status ?? '—' }}</span></td>
                                                <td>{{ $note->dt_status ? \Carbon\Carbon::parse($note->dt_status)->format('d/m/Y H:i') : '—' }}</td>
                                                <td>{{ $note->pivot_linked_at ? \Carbon\Carbon::parse($note->pivot_linked_at)->format('d/m/Y H:i') : '—' }}</td>
                                                <td class="text-end"><button class="btn btn-sm btn-outline-danger" wire:click="unlinkNoteFromCase({{ $note->id }})">Desvincular</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small mb-0">Nenhuma note associada a este processo.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <div class="ld-panel">
                    <div class="ld-panel-header">Painel de ação</div>
                    <div class="ld-panel-body">
                        @if($isExternallyClosed)
                            <div class="alert alert-dark small mb-2">Encerrado no sistema externo: {{ $demand->external_flow_status ?? $demand->external_status ?? '—' }}</div>
                        @else
                            @if($currentAssignment && data_get($currentAssignment->metadata ?? [], 'external_dispatch'))
                                <div class="alert alert-warning small mb-2">
                                    <div class="fw-bold mb-1">DEMANDA EXTERNA</div>
                                    <div class="mb-1">
                                        Esta demanda foi despachada para usuário externo.
                                        @if($currentAssignmentExternalExpiresAt)
                                            Link expira em <strong>{{ $currentAssignmentExternalExpiresAt->format('d/m/Y H:i') }}</strong>.
                                        @endif
                                    </div>
                                    <div class="small mb-1">
                                        Contato: <strong>{{ data_get($currentAssignment->metadata ?? [], 'external_contact_name', 'Não informado') }}</strong>
                                        @if(data_get($currentAssignment->metadata ?? [], 'external_contact_email'))
                                            · {{ data_get($currentAssignment->metadata ?? [], 'external_contact_email') }}
                                        @endif
                                    </div>
                                    @if($currentAssignmentExternalLink)
                                        <div class="input-group input-group-sm mt-2">
                                            <input type="text" class="form-control" readonly value="{{ $currentAssignmentExternalLink }}" id="externalDemandLinkCurrent">
                                            <button class="btn btn-dark"
                                                    type="button"
                                                    onclick="navigator.clipboard?.writeText(document.getElementById('externalDemandLinkCurrent').value); this.innerText='Copiado'; setTimeout(() => this.innerText='Copiar link', 1500);">
                                                Copiar link
                                            </button>
                                        </div>
                                    @else
                                        <div class="small text-danger mt-1">Link externo expirado. Refaça o despacho externo para gerar novo link.</div>
                                    @endif
                                </div>
                            @endif

                            @switch($statusValue)
                                @case('new_imported')
                                    <p class="text-muted small">Esta demanda ainda não entrou em triagem.</p>
                                    <button class="btn btn-warning w-100" wire:click="startTriage"><i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem</button>
                                    @break
                                @case('triage')
                                @case('waiting_controller_action')
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" wire:click="$toggle('showAssignForm')"><i class="bi bi-send me-1"></i>Atribuir Responsável</button>
                                        <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')"><i class="bi bi-lock me-1"></i>Fechar Internamente</button>
                                    </div>
                                    @break
                                @case('sent_to_field')
                                @case('field_received')
                                @case('waiting_field_response')
                                    <div class="alert alert-info small mb-2">
                                        Aguardando retorno de
                                        <strong>
                                            {{ data_get($currentAssignment?->metadata ?? [], 'external_dispatch')
                                                ? (data_get($currentAssignment?->metadata ?? [], 'external_contact_name') ?? 'contato externo')
                                                : ($currentAssignment?->toUser?->name ?? 'executante') }}
                                        </strong>.
                                    </div>
                                    @break
                                @case('returned_by_field')
                                @case('under_controller_review')
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success" wire:click="approveReturn"><i class="bi bi-check2-all me-1"></i>Aprovar Retorno</button>
                                        <button class="btn btn-outline-warning" wire:click="$toggle('showReturnForm')"><i class="bi bi-arrow-return-left me-1"></i>Devolver para Correção</button>
                                        <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')"><i class="bi bi-lock me-1"></i>Fechar Internamente</button>
                                    </div>
                                    @break
                                @case('ready_to_close_external')
                                    <button class="btn btn-success w-100" wire:click="$toggle('showCloseForm')"><i class="bi bi-check-circle me-1"></i>Confirmar Fechamento Externo</button>
                                    @break
                                @case('closed_internal')
                                @case('closed_external')
                                    <div class="alert alert-success small mb-2">Demanda encerrada internamente.</div>
                                    @can('legal.demands.review')
                                        <button class="btn btn-outline-secondary w-100 btn-sm" wire:click="reopen"><i class="bi bi-arrow-clockwise me-1"></i>Reabrir Demanda</button>
                                    @endcan
                                    @break
                                @default
                                    <div class="alert alert-secondary small mb-0">Status não mapeado.</div>
                            @endswitch

                            @if($showAssignForm)
                                <hr>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="assignAsExternal" wire:model="assignAsExternal">
                                        <label class="form-check-label small fw-semibold" for="assignAsExternal">
                                            Despachar para usuário externo (gera link de acesso)
                                        </label>
                                    </div>
                                </div>
                                @if($assignAsExternal)
                                    <div class="mb-2">
                                        <div class="alert alert-warning small mb-0">
                                            A validade do link externo será exatamente o <strong>prazo interno (SLA)</strong> informado abaixo.
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Contato externo já cadastrado</label>
                                        <select class="form-select form-select-sm" wire:model="externalContactId">
                                            <option value="">Novo contato externo</option>
                                            @foreach($externalContacts as $contact)
                                                <option value="{{ $contact->id }}">{{ $contact->name }} — {{ $contact->email }}</option>
                                            @endforeach
                                        </select>
                                        <div class="small text-muted mt-1">Selecione um contato existente ou deixe em branco para cadastrar um novo.</div>
                                    </div>
                                    @if(!$externalContactId)
                                        <div class="mb-2" wire:key="external-contact-name-input">
                                            <label class="form-label small fw-semibold">Nome do contato externo *</label>
                                            <input type="text"
                                                   class="form-control form-control-sm"
                                                   value="{{ $externalContactName }}"
                                                   wire:change="setExternalContactName($event.target.value)"
                                                   placeholder="Nome completo">
                                        </div>
                                        <div class="mb-2" wire:key="external-contact-email-input">
                                            <label class="form-label small fw-semibold">Email do contato externo *</label>
                                            <input type="email"
                                                   class="form-control form-control-sm"
                                                   value="{{ $externalContactEmail }}"
                                                   wire:change="setExternalContactEmail($event.target.value)"
                                                   placeholder="email@dominio.com">
                                        </div>
                                    @endif
                                @else
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Executante interno *</label>
                                        <select class="form-select form-select-sm" wire:model="assignToUserId">
                                            <option value="">Selecionar...</option>
                                            @foreach($fieldUsers as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
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

                            @if($showCloseForm)
                                <hr>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Tipo de fechamento</label>
                                    <div>
                                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" wire:model="closureType" value="internal" id="closeInt" /><label class="form-check-label small" for="closeInt">Interno (SICODE)</label></div>
                                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" wire:model="closureType" value="external" id="closeExt" /><label class="form-check-label small" for="closeExt">Externo (sistema jurídico)</label></div>
                                    </div>
                                </div>
                                <div class="mb-2"><label class="form-label small fw-semibold">Motivo *</label><textarea class="form-control form-control-sm" rows="3" wire:model="closureReason"></textarea></div>
                                @if($closureType === 'external')
                                    <div class="mb-2"><label class="form-label small fw-semibold">Protocolo externo *</label><input type="text" class="form-control form-control-sm" wire:model="externalProtocol" /></div>
                                    <div class="mb-2"><label class="form-label small fw-semibold">Data de fechamento externo</label><input type="date" class="form-control form-control-sm" wire:model="externalClosedAt" /></div>
                                @endif
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showCloseForm', false)">Cancelar</button>
                                    <button class="btn btn-sm btn-dark flex-fill" wire:click="closeDemand">Confirmar Fechamento</button>
                                </div>
                            @endif

                            @if($showReturnForm)
                                <hr>
                                <div class="mb-2"><label class="form-label small fw-semibold">Motivo da devolução *</label><textarea class="form-control form-control-sm" rows="3" wire:model="returnReason"></textarea></div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showReturnForm', false)">Cancelar</button>
                                    <button class="btn btn-sm btn-warning flex-fill" wire:click="returnForCorrection">Devolver</button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="ld-panel">
                    <div class="ld-panel-header">Caso legal</div>
                    <div class="ld-panel-body">
                        <div class="kv"><div class="kl">Empresa</div><div class="kv-val">{{ $demand->legalCase->company_name ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Responsável jurídico</div><div class="kv-val">{{ $demand->legalCase->legal_responsible_name ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Escritório</div><div class="kv-val">{{ $demand->legalCase->law_firm_name ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Natureza</div><div class="kv-val">{{ $demand->legalCase->process_nature ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Processo externo</div><div class="kv-val">{{ $demand->source_process_number ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Status da fonte</div><div class="kv-val">{{ $demand->source_status ?? '—' }}</div></div>
                        <div class="kv"><div class="kl">Prazo de devolução</div><div class="kv-val">{{ $demand->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at)->format('d/m/Y H:i') : '—' }}</div></div>
                        <div class="kv"><div class="kl">Primeira aparição</div><div class="kv-val">{{ $demand->legalCase?->first_seen_at ? \Carbon\Carbon::parse($demand->legalCase->first_seen_at)->format('d/m/Y') : '—' }}</div></div>
                    </div>
                </div>

                <div class="ld-panel">
                    <div class="ld-panel-header">Arquivos</div>
                    <div class="ld-panel-body">
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
                                            <img src="{{ $fileUrl }}" class="legal-evidence-thumb" alt="{{ $name }}" data-bs-toggle="modal" data-bs-target="#legalControllerFilesModal" data-carousel-slide="{{ $index }}">
                                            <div class="small text-muted legal-evidence-name mt-2" title="{{ $name }}">{{ $name }}</div>
                                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-download me-1"></i>Baixar</a>
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
                                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Baixar</a>
                                                @if(($file->uploaded_by ?? null) === auth()->id())
                                                    <button class="btn btn-sm btn-outline-danger" wire:click="removeFile({{ $file->id }})"><i class="bi bi-trash"></i></button>
                                                @endif
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif

                        @if($activeFiles->isEmpty())
                            <p class="text-muted small">Nenhum arquivo anexado.</p>
                        @endif

                        <hr class="my-2">
                        <div class="mb-2"><input type="file" class="form-control form-control-sm" wire:model="uploadFile" /></div>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" wire:model="fileVisibility"><option value="controller">🔒 Interno</option><option value="shared">👁 Compartilhado</option></select>
                            <button class="btn btn-sm btn-outline-primary" wire:click="uploadFile" wire:loading.attr="disabled"><i class="bi bi-upload"></i></button>
                        </div>
                        <div class="small text-muted mt-1">PDF, JPG, PNG, DOCX, XLSX — máx. 10MB</div>
                    </div>
                </div>
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
                                                    <img src="{{ $fileUrl }}" class="img-fluid rounded border" alt="{{ $file->original_name ?? basename($filePath) }}" style="max-height:70vh; object-fit:contain;">
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                    <div class="small text-muted">{{ $file->original_name ?? basename($filePath) }}</div>
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Baixar</a>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                @if($imageFiles->count() > 1)
                                    <button class="carousel-control-prev" type="button" data-bs-target="#legalControllerFilesCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#legalControllerFilesCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Próximo</span></button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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
