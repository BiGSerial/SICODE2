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
            }

            .section-title {
                font-weight: 700;
                letter-spacing: 0.02em;
                font-size: 0.95rem;
                color: #0f172a;
                margin-bottom: 0.65rem;
                text-transform: uppercase;
            }

            .text-block {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 0.75rem;
                white-space: pre-wrap;
            }

            .ticket-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .55rem;
                margin-bottom: .75rem;
            }

            .ticket-item {
                border: 1px solid #e2e8f0;
                border-radius: .65rem;
                padding: .55rem .65rem;
                background: #fff;
            }

            .ticket-label {
                font-size: .74rem;
                color: #64748b;
                text-transform: uppercase;
                font-weight: 700;
                letter-spacing: .04em;
            }

            .ticket-value {
                margin-top: .1rem;
                font-weight: 700;
                color: #0f172a;
                word-break: break-word;
            }

            .ops-check {
                display: flex;
                align-items: flex-start;
                gap: .55rem;
                padding: .45rem 0;
                border-bottom: 1px dashed #e2e8f0;
            }

            .ops-check:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .item-list {
                list-style: none;
                margin: 0;
                padding: 0;
                display: grid;
                gap: .45rem;
            }

            .item-list li {
                border: 1px solid #e2e8f0;
                border-radius: .6rem;
                padding: .5rem .6rem;
                background: #f8fafc;
            }

            .evidence-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }

            .evidence-card {
                border: 1px solid var(--oe-border);
                border-radius: 0.75rem;
                background: #fff;
                padding: 0.6rem;
                text-align: center;
            }

            .evidence-thumb {
                width: 100%;
                height: 120px;
                object-fit: cover;
                border-radius: 0.6rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                cursor: pointer;
            }

            .evidence-name {
                display: block;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .phase-timeline {
                border-left: 3px solid #cbd5e1;
                margin-left: .35rem;
                padding-left: 1rem;
            }

            .phase-item {
                position: relative;
                padding-bottom: .9rem;
            }

            .phase-item::before {
                content: "";
                position: absolute;
                left: -1.37rem;
                top: .2rem;
                width: .75rem;
                height: .75rem;
                border-radius: 999px;
                background: #0f766e;
                border: 2px solid #fff;
                box-shadow: 0 0 0 2px #0f766e22;
            }

            .timeline-scroll {
                max-height: 900px;
                overflow-y: auto;
                padding-right: .35rem;
            }

            @media (max-width: 992px) {
                .ticket-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        @php
            $imageExts = ['jpg','jpeg','png','gif','bmp','svg','tiff','webp'];
            $requestStatusValue = $cancellationRequest->status?->value ?? $cancellationRequest->status;
            $isClosedRequest = in_array($requestStatusValue, ['DONE', 'REJECTED', 'ABORTED'], true);
            $canManageApproval = in_array($requestStatusValue, ['ASSIGNED', 'PAUSED'], true);

            $approvalStatusValue = $cancellationRequest->engineer_approval_status?->value ?? $cancellationRequest->engineer_approval_status;
            $approvalPending = $approvalStatusValue === \App\Enum\CancellationEngineerApprovalStatus::PENDING->value;
            $canFinalizeCancellation = !$cancellationRequest->requires_engineer_approval
                || in_array($approvalStatusValue, ['APPROVED', 'CANCELED'], true);

            $requestedTarget = match ($cancellationRequest->scope?->value ?? $cancellationRequest->scope) {
                \App\Enum\CancellationRequestScope::NOTE_FULL->value => 'Cancelar nota inteira, ordens vinculadas e WorkForm (se existir).',
                \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value => 'Cancelar somente o WorkForm da nota.',
                default => 'Cancelar somente as ordens selecionadas nesta solicitação.',
            };

            $requestEvents = $cancellationRequest->Events
                ->whereIn('type', ['engineer_approval_requested', 'engineer_approval_reopened', 'engineer_approval_engineer_changed'])
                ->sortBy('created_at')
                ->values();

            $responseEvents = $cancellationRequest->Events
                ->whereIn('type', ['engineer_approval_approved', 'engineer_approval_rejected', 'engineer_approval_canceled'])
                ->sortBy('created_at')
                ->values();

            $approvalCards = $responseEvents->map(function ($event) use ($requestEvents, $cancellationRequest) {
                $requestContext = $requestEvents
                    ->filter(fn ($req) => $req->created_at && $event->created_at && $req->created_at->lte($event->created_at))
                    ->last();

                $decisionType = match ($event->type) {
                    'engineer_approval_approved' => 'Aprovado',
                    'engineer_approval_rejected' => 'Rejeitado',
                    default => 'Solicitação cancelada',
                };

                $decisionBadge = match ($event->type) {
                    'engineer_approval_approved' => 'bg-success',
                    'engineer_approval_rejected' => 'bg-danger',
                    default => 'bg-secondary',
                };

                return [
                    'engineer' => $event->Actor->name ?? ($cancellationRequest->EngineerApprover->name ?? 'Usuário não identificado'),
                    'decision' => $decisionType,
                    'decision_badge' => $decisionBadge,
                    'decision_reason' => data_get($event, 'meta.reason') ?: 'Sem resposta registrada.',
                    'decision_at' => $event->created_at,
                    'request_reason' => data_get($requestContext, 'meta.reason') ?: 'Sem texto enviado pelo executante.',
                    'request_by' => $requestContext?->Actor?->name ?? ($cancellationRequest->EngineerApprovalRequester->name ?? '-'),
                    'request_at' => $requestContext?->created_at,
                ];
            })->values();

            if ($approvalCards->isEmpty() && $requestEvents->isNotEmpty()) {
                $latestRequest = $requestEvents->last();
                $approvalCards = collect([[
                    'engineer' => $cancellationRequest->EngineerApprover->name ?? 'Não definido',
                    'decision' => 'Aguardando decisão',
                    'decision_badge' => 'bg-warning text-dark',
                    'decision_reason' => 'Ainda sem resposta do engenheiro.',
                    'decision_at' => null,
                    'request_reason' => data_get($latestRequest, 'meta.reason') ?: 'Sem texto enviado pelo executante.',
                    'request_by' => $latestRequest?->Actor?->name ?? ($cancellationRequest->EngineerApprovalRequester->name ?? '-'),
                    'request_at' => $latestRequest?->created_at,
                ]]);
            }

            $itemsToCancel = collect();
            $orderRefs = $cancellationRequest->Orders->pluck('ordem')->filter()->values();
            $hasWorkForm = (bool) optional($cancellationRequest->Note)->WorkForm;

            if (($cancellationRequest->scope?->value ?? $cancellationRequest->scope) === \App\Enum\CancellationRequestScope::NOTE_FULL->value) {
                $itemsToCancel->push('Nota/OV: ' . ($cancellationRequest->Note->note ?? '-') . ' / ' . ($cancellationRequest->Note->ov ?? '-'));
                if ($orderRefs->isNotEmpty()) {
                    $itemsToCancel->push('Ordens: ' . $orderRefs->join(', '));
                }
                if ($hasWorkForm) {
                    $itemsToCancel->push('Informe de Obra: será cancelado junto com a nota.');
                }
            } elseif (($cancellationRequest->scope?->value ?? $cancellationRequest->scope) === \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value) {
                $itemsToCancel->push('Informe de Obra da nota ' . ($cancellationRequest->Note->note ?? '-') . '.');
            } else {
                foreach ($orderRefs as $ordem) {
                    $itemsToCancel->push('Ordem: ' . $ordem);
                }
            }

            $executionChecks = [
                [
                    'ok' => filled($cancellationRequest->description),
                    'label' => 'Pedido do solicitante identificado',
                    'detail' => filled($cancellationRequest->description) ? 'Descrição preenchida.' : 'Falta descrição do solicitante.',
                ],
                [
                    'ok' => !$cancellationRequest->requires_engineer_approval || !$approvalPending,
                    'label' => 'Dependência de engenharia',
                    'detail' => $approvalPending ? 'Aguardando retorno do engenheiro.' : 'Sem pendência de engenharia.',
                ],
                [
                    'ok' => !$cancellationRequest->requires_engineer_approval || $canFinalizeCancellation,
                    'label' => 'Condição para finalizar',
                    'detail' => $canFinalizeCancellation ? 'Finalização permitida no estado atual.' : 'Finalização bloqueada no momento.',
                ],
            ];

            $imageFiles = $cancellationRequest->EvidenceFiles->filter(function ($file) use ($imageExts) {
                $ext = strtolower((string) $file->extension);
                return in_array($ext, $imageExts, true) || str_starts_with((string) $file->mime, 'image/');
            });

            $otherFiles = $cancellationRequest->EvidenceFiles->filter(function ($file) use ($imageExts) {
                $ext = strtolower((string) $file->extension);
                return !in_array($ext, $imageExts, true) && !str_starts_with((string) $file->mime, 'image/');
            });

            $timeline = collect([
                ['label' => 'Solicitação criada', 'time' => $cancellationRequest->submitted_at, 'user' => $cancellationRequest->Requester?->name],
                ['label' => 'Assumida para execução', 'time' => $cancellationRequest->assigned_at, 'user' => $cancellationRequest->Assignee?->name],
                ['label' => 'Solicitação ao engenheiro', 'time' => $cancellationRequest->engineer_approval_requested_at, 'user' => $cancellationRequest->EngineerApprovalRequester?->name],
                ['label' => 'Decisão do engenheiro', 'time' => $cancellationRequest->engineer_approval_decided_at, 'user' => $cancellationRequest->EngineerApprovalDecider?->name],
                ['label' => 'Encerramento', 'time' => $cancellationRequest->closed_at, 'user' => $cancellationRequest->Closer?->name],
            ])->filter(fn ($i) => !empty($i['time']))->values();
        @endphp

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Execução #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Controle operacional de solicitação de cancelamento.</span>
            </div>
            <button class="btn btn-outline-light me-2" wire:click="exportRequest" wire:loading.attr="disabled">
                <i class="ri-file-excel-2-line align-middle"></i> Exportar
            </button>
            <a class="btn btn-outline-light" href="{{ route('services.cancellations.ongoing', ['service' => $service]) }}">Voltar</a>
        </div>

        <div class="oexterno-card p-3">
            @if($isClosedRequest)
                <div class="alert alert-secondary mb-3">
                    Fluxo encerrado em {{ optional($cancellationRequest->closed_at)->format('d/m/Y H:i') ?? '-' }}.
                    Nenhuma ação operacional adicional está disponível.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="oexterno-subcard mb-3">
                        <div class="section-title">Solicitação de Cancelamento</div>

                        <div class="ticket-grid">
                            <div class="ticket-item">
                                <div class="ticket-label">Usuário solicitante</div>
                                <div class="ticket-value">{{ $cancellationRequest->Requester->name ?? '-' }}</div>
                            </div>
                            <div class="ticket-item">
                                <div class="ticket-label">Data da solicitação</div>
                                <div class="ticket-value">{{ optional($cancellationRequest->submitted_at)->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                            <div class="ticket-item">
                                <div class="ticket-label">Motivo (categoria)</div>
                                <div class="ticket-value">{{ $cancellationRequest->Category->name ?? '-' }}</div>
                            </div>
                            <div class="ticket-item">
                                <div class="ticket-label">Escopo</div>
                                <div class="ticket-value">{{ $requestedTarget }}</div>
                            </div>
                        </div>

                        <div class="ticket-label mb-1">Descrição do pedido</div>
                        <div class="text-block">{{ $cancellationRequest->description ?: 'Sem descrição informada pelo solicitante.' }}</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="oexterno-subcard h-100">
                                <div class="section-title">Itens a cancelar neste chamado</div>
                                <ul class="item-list mb-0">
                                    @forelse($itemsToCancel as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>Nenhum item identificado para cancelamento.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="oexterno-subcard h-100">
                                <div class="section-title">Condições de execução</div>
                                @foreach($executionChecks as $check)
                                    <div class="ops-check">
                                        <i class="{{ $check['ok'] ? 'ri-checkbox-circle-fill text-success' : 'ri-error-warning-fill text-danger' }}"></i>
                                        <div>
                                            <div class="fw-semibold">{{ $check['label'] }}</div>
                                            <div class="small text-muted">{{ $check['detail'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="oexterno-subcard mb-3">
                        <div class="section-title">Evidências do chamado</div>
                        @if($imageFiles->count())
                            <div class="evidence-grid mb-3">
                                @foreach($imageFiles as $file)
                                    @php($fileUrl = \Illuminate\Support\Facades\Storage::disk($file->disk)->url($file->path))
                                    <div class="evidence-card">
                                        <img src="{{ $fileUrl }}" class="evidence-thumb" alt="{{ $file->original_name }}"
                                             data-evidence-src="{{ $fileUrl }}" data-evidence-name="{{ $file->original_name }}"
                                             data-bs-toggle="modal" data-bs-target="#evidenceModal">
                                        <div class="small text-muted evidence-name mt-2" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                                        <div class="small text-muted">{{ strtoupper($file->extension ?? '-') }} | {{ $file->origin }}</div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($otherFiles->count())
                            <ul class="list-group">
                                @foreach($otherFiles as $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="evidence-name" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                                            <small class="text-muted">{{ strtoupper($file->extension ?? '-') }} | {{ $file->origin }}</small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($imageFiles->isEmpty() && $otherFiles->isEmpty())
                            <div class="text-muted">Nenhum anexo neste chamado.</div>
                        @endif
                    </div>

                    @if($approvalCards->isNotEmpty())
                        <div class="oexterno-subcard mb-3">
                            <div class="section-title">Aprovação do Engenheiro</div>
                            @if($approvalCards->count() > 1)
                                <div id="engineerApprovalCarousel" class="carousel slide" data-bs-ride="false">
                                    <div class="carousel-inner">
                                        @foreach($approvalCards as $index => $card)
                                            <div class="carousel-item @if($index === 0) active @endif">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="ticket-item h-100">
                                                            <div class="ticket-label">Engenheiro avaliador</div>
                                                            <div class="ticket-value">{{ $card['engineer'] }}</div>
                                                            <div class="small text-muted mt-2">Encaminhado por {{ $card['request_by'] }} em {{ optional($card['request_at'])->format('d/m/Y H:i') ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="ticket-label mb-1">Texto do executante ao engenheiro</div>
                                                        <div class="text-block">{{ $card['request_reason'] }}</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <div class="ticket-label mb-0">Resposta do engenheiro</div>
                                                            <span class="badge {{ $card['decision_badge'] }}">{{ $card['decision'] }}</span>
                                                        </div>
                                                        <div class="text-block">{{ $card['decision_reason'] }}</div>
                                                        <div class="small text-muted mt-1">{{ optional($card['decision_at'])->format('d/m/Y H:i') ?? '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#engineerApprovalCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Anterior</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#engineerApprovalCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Próximo</span>
                                    </button>
                                </div>
                            @else
                                @php($card = $approvalCards->first())
                                <div class="row g-3">
                                    <div class="col-md-4"><div class="ticket-item h-100"><div class="ticket-label">Engenheiro avaliador</div><div class="ticket-value">{{ $card['engineer'] }}</div><div class="small text-muted mt-2">Encaminhado por {{ $card['request_by'] }} em {{ optional($card['request_at'])->format('d/m/Y H:i') ?? '-' }}</div></div></div>
                                    <div class="col-md-4"><div class="ticket-label mb-1">Texto do executante ao engenheiro</div><div class="text-block">{{ $card['request_reason'] }}</div></div>
                                    <div class="col-md-4"><div class="d-flex justify-content-between align-items-center mb-1"><div class="ticket-label mb-0">Resposta do engenheiro</div><span class="badge {{ $card['decision_badge'] }}">{{ $card['decision'] }}</span></div><div class="text-block">{{ $card['decision_reason'] }}</div><div class="small text-muted mt-1">{{ optional($card['decision_at'])->format('d/m/Y H:i') ?? '-' }}</div></div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="oexterno-subcard">
                        <div class="section-title">Ordens</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Ordem</th><th>Status</th><th>Cancelada</th></tr></thead>
                                <tbody>
                                    @foreach($cancellationRequest->Orders as $order)
                                        <tr><td>{{ $order->ordem }}</td><td>{{ $order->statusUser ?? $order->statusSist ?? '-' }}</td><td>{{ $order->canceled ? 'Sim' : 'Não' }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    @if(in_array($cancellationRequest->status, [\App\Enum\CancellationRequestStatus::ASSIGNED, \App\Enum\CancellationRequestStatus::PAUSED], true) && $showDecisionForm)
                        <div class="oexterno-subcard" id="final-action">
                            <div class="section-title">Decisão da execução</div>
                            <div class="alert alert-light border py-2">Ação selecionada: <strong>@if($action === 'DONE') Finalizar @elseif($action === 'ABORTED') Cancelar @else Pausar @endif</strong></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Comentário da decisão</label>
                                    <textarea class="form-control" rows="5" wire:model.defer="comment"></textarea>
                                    @error('comment')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Upload de evidências da decisão</label>
                                    <input type="file" class="form-control" multiple wire:model="files" />
                                    <ul class="list-group mt-2">
                                        @foreach($tempFiles as $index => $file)
                                            <li class="list-group-item d-flex justify-content-between align-items-center"><span class="evidence-name">{{ $file['original_name'] }}</span><button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="col-12"><button class="btn btn-success w-100" wire:click="runAction">Executar decisão</button></div>
                            </div>
                        </div>
                    @endif

                    <div class="oexterno-subcard mt-3">
                        <div class="section-title">Ações</div>
                        <div class="small text-muted">
                            Bloco operacional fixo. As ações de envio e finalização ficam sempre abaixo desta seção.
                        </div>
                    </div>

                    @if($canManageApproval && !$isClosedRequest)
                        <div class="oexterno-subcard mt-3">
                            <div class="section-title">Executar ações</div>
                            <div class="d-grid gap-2">
                                @if(!$approvalPending)
                                    <button class="btn btn-outline-primary" wire:click="startEngineerRequest">Solicitar Aprovação de Engenheiro</button>
                                @else
                                    <button class="btn btn-outline-warning" wire:click="startEngineerChange">Alterar Engenheiro</button>
                                    <button class="btn btn-outline-danger" wire:click="cancelEngineerApproval">Cancelar Solicitação ao Engenheiro</button>
                                @endif

                                <button class="btn btn-success"
                                        wire:click="prepareAction('DONE')"
                                        @if($isClosedRequest || $approvalPending || !$canFinalizeCancellation) disabled @endif>
                                    Finalizar
                                </button>

                                <button class="btn btn-outline-danger"
                                        wire:click="prepareAction('ABORTED')"
                                        @if($isClosedRequest) disabled @endif>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($showEngineerActionForm && $canManageApproval && !$isClosedRequest)
                        <div class="oexterno-subcard mt-3">
                            <div class="section-title">{{ $engineerActionMode === 'change' ? 'Alterar Engenheiro da Aprovação' : 'Solicitar Aprovação de Engenheiro' }}</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Engenheiro</label>
                                    <select class="form-select" wire:model="engineerId">
                                        <option value="">Selecione</option>
                                        @foreach($engineers as $engineer)
                                            <option value="{{ $engineer->id }}">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($engineer->name)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('engineerId')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Texto para o engenheiro</label>
                                    <textarea class="form-control" rows="4" wire:model.defer="engineerReason"></textarea>
                                    @if($cancellationRequest->engineer_approval_reason)
                                        <div class="small text-muted mt-1">Último texto registrado: {{ $cancellationRequest->engineer_approval_reason }}</div>
                                    @endif
                                </div>
                                <div class="col-12 d-grid gap-2">
                                    @if($engineerActionMode === 'change')
                                        <button class="btn btn-warning" wire:click="changeEngineer">Confirmar alteração</button>
                                    @else
                                        <button class="btn btn-primary" wire:click="requestEngineerApproval">Enviar solicitação</button>
                                    @endif
                                    <button class="btn btn-outline-secondary" wire:click="cancelEngineerActionForm">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="oexterno-subcard">
                        <div class="section-title">Timeline</div>
                        <div class="timeline-scroll">
                            <div class="phase-timeline">
                                @forelse($timeline as $phase)
                                    <div class="phase-item">
                                        <div class="fw-semibold">{{ $phase['label'] }}</div>
                                        <div class="small text-muted">{{ optional($phase['time'])->format('d/m/Y H:i') }} · {{ $phase['user'] ?? 'Sistema' }}</div>
                                    </div>
                                @empty
                                    <div class="text-muted">Sem interações registradas até o momento.</div>
                                @endforelse
                            </div>
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
                    <h5 class="modal-title" id="evidenceModalTitle">Evidência</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="evidenceModalImage" src="" class="img-fluid rounded" alt="Evidência">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
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
                modalTitle.textContent = img.dataset.evidenceName || 'Evidência';
            });
        });
    });
</script>
