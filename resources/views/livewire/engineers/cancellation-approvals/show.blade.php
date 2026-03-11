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

            .text-block {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 0.75rem;
                white-space: pre-wrap;
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

            .audit-entry {
                border: 1px solid #e2e8f0;
                border-radius: .7rem;
                background: #f8fafc;
                padding: .7rem;
            }

            .audit-entry + .audit-entry {
                margin-top: .55rem;
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
            $isPending = $cancellationRequest->engineer_approval_status === \App\Enum\CancellationEngineerApprovalStatus::PENDING;

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

            $decisionChecks = [
                [
                    'ok' => $isPending,
                    'label' => 'Solicitação pendente para decisão',
                    'detail' => $isPending ? 'Você pode decidir agora.' : 'Solicitação não está pendente para nova decisão.',
                ],
                [
                    'ok' => filled($cancellationRequest->description),
                    'label' => 'Pedido do solicitante identificado',
                    'detail' => filled($cancellationRequest->description) ? 'Descrição preenchida.' : 'Sem descrição do solicitante.',
                ],
                [
                    'ok' => $requestEvents->isNotEmpty(),
                    'label' => 'Solicitação do executante registrada',
                    'detail' => $requestEvents->isNotEmpty() ? 'Há texto do executante para análise.' : 'Sem envio registrado do executante.',
                ],
            ];

            $approvalCards = $responseEvents->map(function ($event) use ($requestEvents) {
                $requestContext = $requestEvents
                    ->filter(fn ($req) => $req->created_at && $event->created_at && $req->created_at->lte($event->created_at))
                    ->last();

                return [
                    'request_reason' => data_get($requestContext, 'meta.reason') ?: 'Sem texto enviado pelo executante.',
                    'request_by' => $requestContext?->Actor?->name ?? '-',
                    'request_at' => $requestContext?->created_at,
                    'decision' => match ($event->type) {
                        'engineer_approval_approved' => 'Aprovado',
                        'engineer_approval_rejected' => 'Rejeitado',
                        default => 'Solicitação cancelada',
                    },
                    'decision_badge' => match ($event->type) {
                        'engineer_approval_approved' => 'bg-success',
                        'engineer_approval_rejected' => 'bg-danger',
                        default => 'bg-secondary',
                    },
                    'decision_reason' => data_get($event, 'meta.reason') ?: 'Sem decisão registrada.',
                    'decision_at' => $event->created_at,
                ];
            })->values();

            $engineerTimeline = collect();
            foreach ($requestEvents as $requestEvent) {
                $engineerTimeline->push([
                    'label' => 'Solicitação do executante',
                    'time' => $requestEvent->created_at,
                    'detail' => data_get($requestEvent, 'meta.reason') ?: 'Sem texto do executante.',
                ]);

                $decisionEvent = $responseEvents
                    ->first(fn ($responseEvent) => $responseEvent->created_at && $requestEvent->created_at && $responseEvent->created_at->gte($requestEvent->created_at));

                if ($decisionEvent) {
                    $engineerTimeline->push([
                        'label' => 'Decisão registrada',
                        'time' => $decisionEvent->created_at,
                        'detail' => data_get($decisionEvent, 'meta.reason') ?: 'Sem justificativa da decisão.',
                    ]);
                }
            }

            $imageFiles = $cancellationRequest->EvidenceFiles->filter(function ($file) use ($imageExts) {
                $ext = strtolower((string) $file->extension);
                return in_array($ext, $imageExts, true) || str_starts_with((string) $file->mime, 'image/');
            });

            $otherFiles = $cancellationRequest->EvidenceFiles->filter(function ($file) use ($imageExts) {
                $ext = strtolower((string) $file->extension);
                return !in_array($ext, $imageExts, true) && !str_starts_with((string) $file->mime, 'image/');
            });
        @endphp

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Solicitação #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Visão operacional do engenheiro.</span>
            </div>
            <a class="btn btn-outline-light" href="{{ route('engineers.cancellations.index') }}">Voltar</a>
        </div>

        <div class="oexterno-card p-3">
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="oexterno-subcard mb-3">
                        <div class="section-title">Solicitação de Cancelamento</div>
                        <div class="ticket-grid">
                            <div class="ticket-item"><div class="ticket-label">Usuário solicitante</div><div class="ticket-value">{{ $cancellationRequest->Requester->name ?? '-' }}</div></div>
                            <div class="ticket-item"><div class="ticket-label">Data da solicitação</div><div class="ticket-value">{{ optional($cancellationRequest->submitted_at)->format('d/m/Y H:i') ?? '-' }}</div></div>
                            <div class="ticket-item"><div class="ticket-label">Motivo (categoria)</div><div class="ticket-value">{{ $cancellationRequest->Category->name ?? '-' }}</div></div>
                            <div class="ticket-item"><div class="ticket-label">Escopo</div><div class="ticket-value">{{ $requestedTarget }}</div></div>
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
                                @foreach($decisionChecks as $check)
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
                                        <div><div class="evidence-name" title="{{ $file->original_name }}">{{ $file->original_name }}</div><small class="text-muted">{{ strtoupper($file->extension ?? '-') }} | {{ $file->origin }}</small></div>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($imageFiles->isEmpty() && $otherFiles->isEmpty())
                            <div class="text-muted">Sem anexos nesta solicitação.</div>
                        @endif
                    </div>

                    @if($approvalCards->isNotEmpty())
                        <div class="oexterno-subcard mb-3">
                            <div class="section-title">Histórico de solicitações e decisões</div>
                            @foreach($approvalCards as $card)
                                <div class="audit-entry">
                                    <div class="small text-muted mb-1">Solicitação do executante em {{ optional($card['request_at'])->format('d/m/Y H:i') ?? '-' }} por {{ $card['request_by'] }}</div>
                                    <div class="text-block mb-2">{{ $card['request_reason'] }}</div>
                                    <div class="d-flex justify-content-between align-items-center mb-1"><div class="small text-muted">Decisão</div><span class="badge {{ $card['decision_badge'] }}">{{ $card['decision'] }}</span></div>
                                    <div class="text-block">{{ $card['decision_reason'] }}</div>
                                    <div class="small text-muted mt-1">{{ optional($card['decision_at'])->format('d/m/Y H:i') ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-12 col-lg-4">
                    @if($isPending)
                        <div class="oexterno-subcard">
                            <div class="section-title">Decisão do Engenheiro</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Decisão</label>
                                    <select class="form-select" wire:model="decision">
                                        <option value="APPROVED">Autorizar cancelamento</option>
                                        <option value="REJECTED">Rejeitar cancelamento</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Justificativa da decisão</label>
                                    <textarea class="form-control" rows="5" wire:model.defer="reason"></textarea>
                                    @error('reason')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Upload de evidências da decisão</label>
                                    <input type="file" class="form-control" multiple wire:model="files">
                                    <ul class="list-group mt-2">
                                        @foreach($tempFiles as $index => $file)
                                            <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $file['original_name'] }}</span><button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="col-12"><button class="btn btn-success w-100" wire:click="decide">Salvar decisão</button></div>
                            </div>
                        </div>
                    @else
                        <div class="oexterno-subcard">
                            <div class="section-title">Decisão do Engenheiro</div>
                            <div class="text-muted">Solicitação não está pendente para nova decisão.</div>
                        </div>
                    @endif

                    <div class="oexterno-subcard mt-3">
                        <div class="section-title">Ações</div>
                        <div class="small text-muted">As ações do engenheiro ficam sempre nesta coluna.</div>
                    </div>

                    <div class="oexterno-subcard mt-3">
                        <div class="section-title">Timeline da Solicitação do Executante</div>
                        <div class="timeline-scroll">
                            <div class="phase-timeline">
                                @forelse($engineerTimeline as $phase)
                                    <div class="phase-item">
                                        <div class="fw-semibold">{{ $phase['label'] }}</div>
                                        <div class="small text-muted mb-1">{{ optional($phase['time'])->format('d/m/Y H:i') }}</div>
                                        <div class="small">{{ $phase['detail'] }}</div>
                                    </div>
                                @empty
                                    <div class="text-muted">Sem eventos de solicitação/decisão registrados.</div>
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
