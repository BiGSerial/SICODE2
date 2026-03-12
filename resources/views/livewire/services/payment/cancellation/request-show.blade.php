<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />

        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-border: #e5e7eb;
                background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                    var(--oe-bg);
                padding: 1.5rem 0;
            }

            .oexterno-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.5rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1.5rem;
            }

            .oexterno-card {
                background: var(--oe-surface);
                border: 1px solid var(--oe-border);
                border-radius: 0.9rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .oexterno-subcard {
                background: #ffffff;
                border: 1px solid var(--oe-border);
                border-radius: 0.85rem;
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
                padding: 1rem;
                height: 100%;
            }

            .section-title {
                font-weight: 700;
                letter-spacing: 0.02em;
                font-size: 0.95rem;
                color: #0f172a;
                margin-bottom: 0.65rem;
                text-transform: uppercase;
            }

            .status-banner {
                border-radius: .8rem;
                padding: .75rem 1rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
            }

            .info-panel {
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                border: 1px solid #dbe5ef;
                border-radius: .9rem;
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
                padding: 1rem;
                height: 100%;
            }

            .info-panel-head {
                display: flex;
                align-items: center;
                gap: .55rem;
                margin-bottom: .8rem;
                padding-bottom: .6rem;
                border-bottom: 1px solid #e2e8f0;
            }

            .info-panel-icon {
                width: 34px;
                height: 34px;
                border-radius: .6rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #e6f7f2;
                color: #0f766e;
                font-size: 1.05rem;
            }

            .info-panel-title {
                margin: 0;
                font-size: .95rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #0f172a;
            }

            .info-kv {
                display: grid;
                gap: .45rem;
            }

            .info-kv-row {
                display: grid;
                grid-template-columns: 130px 1fr;
                gap: .5rem;
                align-items: start;
                font-size: .93rem;
            }

            .info-kv-key {
                color: #64748b;
                font-weight: 600;
            }

            .info-kv-val {
                color: #0f172a;
                font-weight: 600;
                word-break: break-word;
            }

            .text-block {
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                background: #f8fafc;
                padding: 0.85rem;
                white-space: pre-wrap;
            }

            .timeline-wrap {
                border-left: 3px solid #cbd5e1;
                margin-left: .35rem;
                padding-left: 1rem;
                max-height: 420px;
                overflow-y: auto;
            }

            .timeline-item {
                position: relative;
                padding-bottom: .95rem;
            }

            .timeline-item::before {
                content: "";
                position: absolute;
                left: -1.37rem;
                top: .24rem;
                width: .75rem;
                height: .75rem;
                border-radius: 999px;
                background: #0f766e;
                border: 2px solid #fff;
                box-shadow: 0 0 0 2px #0f766e22;
            }

            .evidence-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }

            .evidence-card {
                border: 1px solid var(--oe-border);
                border-radius: 0.75rem;
                background: #fff;
                padding: 0.6rem;
                text-align: center;
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            }

            .evidence-thumb {
                width: 100%;
                height: 110px;
                object-fit: cover;
                border-radius: 0.6rem;
                cursor: pointer;
            }

            .evidence-name {
                display: block;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            @media (max-width: 1200px) {
                .info-kv-row {
                    grid-template-columns: 110px 1fr;
                }
            }
        </style>

        @php
            $imageExts = ['jpg','jpeg','png','gif','bmp','svg','tiff','webp'];

            $imageFiles = $cancellationRequest->EvidenceFiles->filter(function ($file) use ($imageExts) {
                $ext = strtolower((string) $file->extension);
                return in_array($ext, $imageExts, true) || str_starts_with((string) $file->mime, 'image/');
            });

            $otherFiles = $cancellationRequest->EvidenceFiles->filter(function ($file) use ($imageExts) {
                $ext = strtolower((string) $file->extension);
                return !in_array($ext, $imageExts, true) && !str_starts_with((string) $file->mime, 'image/');
            });

            $requestStatusValue = $cancellationRequest->status?->value ?? $cancellationRequest->status;
            $isClosedRequest = in_array($requestStatusValue, ['DONE', 'REJECTED', 'ABORTED'], true);

            $requestedTarget = match ($cancellationRequest->scope?->value ?? $cancellationRequest->scope) {
                \App\Enum\CancellationRequestScope::NOTE_FULL->value => 'Cancelar nota inteira, ordens vinculadas e WorkForm (se existir).',
                \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value => 'Cancelar somente o WorkForm da nota.',
                default => 'Cancelar somente as ordens selecionadas nesta solicitacao.',
            };

            $closureType = $cancellationRequest->closure_type;
            $executantDecision = match ($closureType) {
                \App\Models\CancellationRequest::CLOSURE_DONE => 'Cancelamento executado',
                \App\Models\CancellationRequest::CLOSURE_REJECTED => 'Solicitacao rejeitada pelo executante',
                \App\Models\CancellationRequest::CLOSURE_ABORTED => 'Solicitacao abortada pelo executante',
                default => 'Em andamento',
            };

            $eventLabels = [
                'submitted' => 'Solicitacao enviada',
                'assigned' => 'Solicitacao assumida',
                'paused' => 'Execucao pausada',
                'done' => 'Cancelamento concluido',
                'rejected' => 'Solicitacao rejeitada',
                'aborted' => 'Solicitacao abortada',
                'reopened' => 'Solicitacao reaberta',
                'transferred' => 'Solicitacao transferida',
                'updated' => 'Dados atualizados',
                'comment' => 'Comentario registrado',
                'attachment_added' => 'Anexo adicionado',
                'attachment_removed' => 'Anexo removido',
                'engineer_approval_requested' => 'Aprovacao solicitada ao engenheiro',
                'engineer_approval_reopened' => 'Aprovacao ao engenheiro reenviada',
                'engineer_approval_engineer_changed' => 'Engenheiro alterado',
                'engineer_approval_canceled' => 'Solicitacao ao engenheiro cancelada',
                'engineer_approval_approved' => 'Aprovacao do engenheiro recebida',
                'engineer_approval_rejected' => 'Reprovacao do engenheiro recebida',
            ];

            $timeline = $cancellationRequest->Events
                ->sortByDesc('created_at')
                ->map(function ($event) use ($eventLabels) {
                    $reason = data_get($event->meta, 'reason') ?: data_get($event->meta, 'message');
                    return [
                        'label' => $eventLabels[$event->type] ?? strtoupper((string) $event->type),
                        'time' => $event->created_at,
                        'actor' => $event->Actor->name ?? 'Sistema',
                        'detail' => $reason ?: null,
                    ];
                })
                ->values();

            $approvalStepDate = $cancellationRequest->engineer_approval_decided_at
                ?? $cancellationRequest->engineer_approval_requested_at;

            $progressChecks = [
                [
                    'ok' => $cancellationRequest->submitted_at !== null,
                    'label' => 'Solicitacao criada',
                    'date' => optional($cancellationRequest->submitted_at)->format('d/m/Y H:i') ?? '-',
                ],
                [
                    'ok' => $cancellationRequest->assigned_to !== null,
                    'label' => 'Execucao atribuida',
                    'date' => optional($cancellationRequest->assigned_at)->format('d/m/Y H:i') ?? '-',
                ],
                [
                    'ok' => $cancellationRequest->requires_engineer_approval ? ($cancellationRequest->engineer_approval_status !== null) : true,
                    'label' => 'Regra de aprovacao avaliada',
                    'date' => $cancellationRequest->requires_engineer_approval
                        ? (optional($approvalStepDate)->format('d/m/Y H:i') ?? '-')
                        : 'Nao aplicavel',
                ],
                [
                    'ok' => $isClosedRequest,
                    'label' => 'Fluxo finalizado',
                    'date' => optional($cancellationRequest->closed_at)->format('d/m/Y H:i') ?? '-',
                ],
            ];

            $orderSummary = $cancellationRequest->Orders->pluck('ordem')->filter()->values();
        @endphp

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Solicitacao #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Visao geral do fluxo de cancelamento.</span>
            </div>
            <div class="mt-3">
                <a class="btn btn-outline-light" href="{{ url()->previous() }}">Voltar</a>
            </div>
        </div>

        <div class="oexterno-card p-3 mb-3">
            <div class="status-banner mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="small text-muted text-uppercase">Status atual</div>
                    <div class="fw-bold">
                        {{ $cancellationRequest->status?->label() ?? $cancellationRequest->status?->value ?? $cancellationRequest->status }}
                    </div>
                </div>
                <div>
                    <div class="small text-muted text-uppercase">Execucao</div>
                    <div class="fw-bold">{{ $executantDecision }}</div>
                </div>
                <div>
                    <div class="small text-muted text-uppercase">Aprovacao engenheiro</div>
                    <div class="fw-bold">
                        {{ $cancellationRequest->engineer_approval_status?->label() ?? 'Nao solicitada' }}
                    </div>
                </div>
            </div>

            @if($isClosedRequest)
                <div class="alert alert-secondary mb-3">
                    Fluxo encerrado em {{ optional($cancellationRequest->closed_at)->format('d/m/Y H:i') ?? '-' }}.
                    Tela em modo de consulta.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-icon"><i class="ri-file-text-line"></i></span>
                            <h6 class="info-panel-title">Solicitacao</h6>
                        </div>
                        <div class="info-kv">
                            <div class="info-kv-row">
                                <div class="info-kv-key">Categoria</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Category->name ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Escopo</div>
                                <div class="info-kv-val">{{ $cancellationRequest->scope?->label() ?? $cancellationRequest->scope?->value ?? $cancellationRequest->scope }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Objetivo</div>
                                <div class="info-kv-val">{{ $requestedTarget }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Solicitado por</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Requester->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-icon"><i class="ri-clipboard-line"></i></span>
                            <h6 class="info-panel-title">Nota e Ordens</h6>
                        </div>
                        <div class="info-kv">
                            <div class="info-kv-row">
                                <div class="info-kv-key">Nota</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Note->note ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Cliente</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Note->client ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Ordens</div>
                                <div class="info-kv-val">
                                    @if($orderSummary->isNotEmpty())
                                        {{ $orderSummary->take(3)->join(', ') }}@if($orderSummary->count() > 3) ... @endif
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Total ordens</div>
                                <div class="info-kv-val">{{ $orderSummary->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-icon"><i class="ri-road-map-line"></i></span>
                            <h6 class="info-panel-title">Andamento</h6>
                        </div>
                        @foreach($progressChecks as $check)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="{{ $check['ok'] ? 'ri-checkbox-circle-fill text-success' : 'ri-time-line text-warning' }}"></i>
                                <div>
                                    <div class="fw-semibold">{{ $check['label'] }}</div>
                                    <div class="small text-muted">Data: {{ $check['date'] }}</div>
                                </div>
                            </div>
                        @endforeach
                        <div class="small text-muted mt-2">
                            Assumido por: <strong>{{ $cancellationRequest->Assignee->name ?? '-' }}</strong><br>
                            Engenheiro: <strong>{{ $cancellationRequest->EngineerApprover->name ?? '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3 g-3">
                <div class="col-md-6">
                    <div class="oexterno-subcard">
                        <div class="section-title">Pedido do solicitante</div>
                        <div class="text-block">{{ $cancellationRequest->description ?: 'Sem descricao informada pelo solicitante.' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="oexterno-subcard">
                        <div class="section-title">Ultima orientacao registrada</div>
                        <div class="text-block">{{ $cancellationRequest->closure_note ?: $cancellationRequest->engineer_approval_reason ?: 'Sem orientacao registrada ate o momento.' }}</div>
                    </div>
                </div>
            </div>

            <div class="row mt-3 g-3">
                <div class="col-md-7">
                    <div class="oexterno-subcard">
                        <div class="section-title">Linha do tempo (visao geral)</div>
                        @if($timeline->isEmpty())
                            <div class="text-muted">Sem eventos registrados.</div>
                        @else
                            <div class="timeline-wrap">
                                @foreach($timeline as $event)
                                    <div class="timeline-item">
                                        <div class="fw-bold">{{ $event['label'] }}</div>
                                        <div class="small text-muted">
                                            {{ optional($event['time'])->format('d/m/Y H:i') ?? '-' }}
                                            - {{ $event['actor'] }}
                                        </div>
                                        @if($event['detail'])
                                            <div class="small mt-1">{{ $event['detail'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="oexterno-subcard">
                        <div class="section-title">Anexos</div>

                        @if($imageFiles->isNotEmpty())
                            <div class="evidence-grid mb-3">
                                @foreach($imageFiles as $file)
                                    <div class="evidence-card">
                                        <img
                                            src="{{ Storage::disk($file->disk)->url($file->path) }}"
                                            class="evidence-thumb mb-2"
                                            alt="{{ $file->original_name }}"
                                            data-evidence-src="{{ Storage::disk($file->disk)->url($file->path) }}"
                                            data-evidence-name="{{ $file->original_name }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#evidenceModal"
                                        />
                                        <div class="small text-muted evidence-name" title="{{ $file->original_name }}">
                                            {{ $file->original_name }}
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($otherFiles->isNotEmpty())
                            <ul class="list-group list-group-flush">
                                @foreach($otherFiles as $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="me-2">
                                            <div class="fw-semibold">{{ $file->original_name }}</div>
                                            <small class="text-muted">{{ strtoupper($file->extension ?? '-') }}</small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($imageFiles->isEmpty() && $otherFiles->isEmpty())
                            <div class="text-muted">Nenhum anexo.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evidenceModalTitle">Evidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center">
                <img id="evidenceModalImage" src="" class="img-fluid rounded" alt="Evidencia">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalImg = document.getElementById('evidenceModalImage');
        const modalTitle = document.getElementById('evidenceModalTitle');
        document.querySelectorAll('[data-evidence-src]').forEach((img) => {
            img.addEventListener('click', () => {
                modalImg.src = img.dataset.evidenceSrc;
                modalTitle.textContent = img.dataset.evidenceName || 'Evidencia';
            });
        });
    });
</script>
