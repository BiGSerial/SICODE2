<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-ink: #1f2933;
                --oe-muted: #6b7280;
                --oe-accent: #0f766e;
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
            }

            .evidence-name {
                display: block;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .comment-text {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .timeline-box {
                max-height: 280px;
                overflow: auto;
            }

            .control-panel {
                border: 1px dashed #cbd5e1;
                border-radius: 0.75rem;
                background: #f8fafc;
                padding: 0.85rem;
            }

            .text-block {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 0.75rem;
                min-height: 120px;
                white-space: pre-wrap;
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

            .phase-time {
                font-size: .78rem;
                color: #6b7280;
            }

            .timeline-scroll {
                max-height: 320px;
                overflow-y: auto;
                padding-right: .35rem;
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

            @media (max-width: 1200px) {
                .info-kv-row {
                    grid-template-columns: 110px 1fr;
                }
            }
        </style>

        @php
            $imageExts = ['jpg','jpeg','png','gif','bmp','svg','tiff','webp'];
            $engineerRejected = $cancellationRequest->engineer_approval_status === \App\Enum\CancellationEngineerApprovalStatus::REJECTED;
            $canFinalizeCancellation = !$cancellationRequest->requires_engineer_approval
                || in_array($cancellationRequest->engineer_approval_status?->value, ['APPROVED', 'CANCELED'], true);
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
            $canManageApproval = in_array($requestStatusValue, ['ASSIGNED', 'PAUSED'], true);

            $requestedTarget = $cancellationRequest->scope?->value === \App\Enum\CancellationRequestScope::NOTE_FULL->value
                ? 'Cancelar nota inteira e todas as ordens vinculadas.'
                : 'Cancelar somente as ordens selecionadas nesta solicitação.';

            $closureType = $cancellationRequest->closure_type;
            $executantDecision = match ($closureType) {
                \App\Models\CancellationRequest::CLOSURE_DONE => 'Cancelamento executado',
                \App\Models\CancellationRequest::CLOSURE_REJECTED => 'Solicitação rejeitada pelo executante',
                \App\Models\CancellationRequest::CLOSURE_ABORTED => 'Solicitação abortada pelo executante',
                default => 'Em execução',
            };

            $timeline = collect([
                ['label' => 'Solicitação criada', 'time' => $cancellationRequest->submitted_at, 'user' => $cancellationRequest->Requester?->name],
                ['label' => 'Assumida para execução', 'time' => $cancellationRequest->assigned_at, 'user' => $cancellationRequest->Assignee?->name],
                ['label' => 'Aprovação do engenheiro solicitada', 'time' => $cancellationRequest->engineer_approval_requested_at, 'user' => $cancellationRequest->EngineerApprovalRequester?->name],
                ['label' => 'Decisão do engenheiro', 'time' => $cancellationRequest->engineer_approval_decided_at, 'user' => $cancellationRequest->EngineerApprovalDecider?->name],
                ['label' => 'Encerramento da solicitação', 'time' => $cancellationRequest->closed_at, 'user' => $cancellationRequest->Closer?->name],
            ])->filter(fn ($i) => !empty($i['time']))->values();
        @endphp

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Execução #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Tudo à vista para executar a solicitação.</span>
            </div>
            <a class="btn btn-outline-light" href="{{ route('services.cancellations.ongoing', ['service' => $service]) }}">Voltar</a>
        </div>

        <div class="oexterno-card p-3">
            <div class="status-banner mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="small text-muted text-uppercase">Status atual</div>
                    <div class="fw-bold">
                        {{ $cancellationRequest->status?->label() ?? $cancellationRequest->status?->value ?? $cancellationRequest->status }}
                    </div>
                </div>
                <div>
                    <div class="small text-muted text-uppercase">Decisão do executante</div>
                    <div class="fw-bold">{{ $executantDecision }}</div>
                </div>
                <div>
                    <div class="small text-muted text-uppercase">Aprovação engenheiro</div>
                    <div class="fw-bold">
                        {{ $cancellationRequest->engineer_approval_status?->label() ?? 'Não solicitada' }}
                    </div>
                </div>
            </div>

            @if($isClosedRequest)
                <div class="alert alert-secondary mb-3">
                    Fluxo encerrado em {{ optional($cancellationRequest->closed_at)->format('d/m/Y H:i') ?? '-' }}.
                    Nenhuma ação operacional adicional está disponível.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-icon"><i class="ri-file-text-line"></i></span>
                            <h6 class="info-panel-title">Nota</h6>
                        </div>
                        <div class="info-kv">
                            <div class="info-kv-row">
                                <div class="info-kv-key">Número</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Note->note ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Cliente</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Note->client ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Status</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Note->status ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-icon"><i class="ri-file-list-3-line"></i></span>
                            <h6 class="info-panel-title">Solicitação</h6>
                        </div>
                        <div class="info-kv">
                            <div class="info-kv-row">
                                <div class="info-kv-key">Categoria</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Category->name ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Escopo</div>
                                <div class="info-kv-val">
                                    <span class="badge {{ $cancellationRequest->scope?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $cancellationRequest->scope?->label() ?? $cancellationRequest->scope?->value ?? $cancellationRequest->scope }}
                                    </span>
                                </div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Status</div>
                                <div class="info-kv-val">
                                    <span class="badge {{ $cancellationRequest->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $cancellationRequest->status?->label() ?? $cancellationRequest->status?->value ?? $cancellationRequest->status }}
                                    </span>
                                </div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Solicitante</div>
                                <div class="info-kv-val">{{ $cancellationRequest->Requester->name ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Aprovação Eng.</div>
                                <div class="info-kv-val">
                                    @if($cancellationRequest->engineer_approval_status)
                                        <span class="badge {{ $cancellationRequest->engineer_approval_status?->badgeClass() ?? 'bg-secondary' }}">
                                            {{ $cancellationRequest->engineer_approval_status?->label() ?? $cancellationRequest->engineer_approval_status }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Não solicitada</span>
                                    @endif
                                </div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Engenheiro</div>
                                <div class="info-kv-val">{{ $cancellationRequest->EngineerApprover->name ?? '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Objetivo</div>
                                <div class="info-kv-val">{{ $requestedTarget }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-icon"><i class="ri-tools-line"></i></span>
                            <h6 class="info-panel-title">Execução</h6>
                        </div>
                        <div class="info-kv">
                            <div class="info-kv-row">
                                <div class="info-kv-key">Assumido em</div>
                                <div class="info-kv-val">{{ optional($cancellationRequest->assigned_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Atualização</div>
                                <div class="info-kv-val">{{ optional($cancellationRequest->updated_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Solicitada em</div>
                                <div class="info-kv-val">{{ optional($cancellationRequest->engineer_approval_requested_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="info-kv-row">
                                <div class="info-kv-key">Decidida em</div>
                                <div class="info-kv-val">{{ optional($cancellationRequest->engineer_approval_decided_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="oexterno-subcard">
                        <div class="section-title">Pedido do solicitante</div>
                        <div class="mb-2 text-muted small">
                            Texto original informado pelo solicitante ao abrir o cancelamento.
                        </div>
                        <div class="text-block">{{ $cancellationRequest->description ?: 'Sem descrição informada pelo solicitante.' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="oexterno-subcard">
                        <div class="section-title">Orientação do executante</div>
                        <div class="mb-2 text-muted small">
                            Justificativa registrada pelo executante para o fluxo (aprovação/encerramento).
                        </div>
                        <div class="text-block">{{ $cancellationRequest->closure_note ?: $cancellationRequest->engineer_approval_reason ?: 'Sem orientação registrada pelo executante.' }}</div>
                    </div>
                </div>
            </div>

            <div class="row mt-3 g-3">
                <div class="col-12 col-md-9">
                    <div class="oexterno-subcard">
                        <div class="section-title">Painel de Controle da Aprovação de Engenheiro</div>
                        @if($approvalPending)
                            <div class="alert alert-warning py-2">
                                Aguardando decisão do engenheiro. Use os controles abaixo para trocar o engenheiro ou cancelar a solicitação de aprovação.
                            </div>
                        @elseif($cancellationRequest->engineer_approval_status === \App\Enum\CancellationEngineerApprovalStatus::REJECTED)
                            <div class="alert alert-danger py-2">
                                Solicitação rejeitada pelo engenheiro. Reenvie para aprovação ou cancele a exigência para continuar.
                            </div>
                        @elseif($cancellationRequest->engineer_approval_status === \App\Enum\CancellationEngineerApprovalStatus::APPROVED)
                            <div class="alert alert-success py-2">
                                Aprovação do engenheiro concluída. Você já pode finalizar o cancelamento.
                            </div>
                        @endif

                        @if($canManageApproval && !$isClosedRequest)
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label">Engenheiro</label>
                                    <select class="form-select" wire:model="engineerId">
                                        <option value="">Selecione</option>
                                        @foreach($engineers as $engineer)
                                            <option value="{{ $engineer->id }}">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($engineer->name)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('engineerId')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label">Motivo / justificativa da ação</label>
                                    <textarea class="form-control" rows="5" wire:model.defer="engineerReason"></textarea>
                                    @if($cancellationRequest->engineer_approval_reason)
                                        <div class="small text-muted mt-1">
                                            Último motivo: {{ $cancellationRequest->engineer_approval_reason }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="control-panel mt-3">
                                <div class="fw-semibold mb-2">Ações disponíveis</div>
                                <div class="small text-muted mb-3">
                                    Escolha uma ação e informe a justificativa acima para registrar o histórico de forma clara.
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @if(!$cancellationRequest->engineer_approval_status || in_array($cancellationRequest->engineer_approval_status?->value, ['REJECTED', 'CANCELED'], true))
                                        <button class="btn btn-outline-primary" wire:click="requestEngineerApproval">
                                            Solicitar Aprovação
                                        </button>
                                    @endif
                                    @if($approvalPending)
                                        <button class="btn btn-outline-warning" wire:click="changeEngineer">
                                            Alterar Engenheiro
                                        </button>
                                        <button class="btn btn-outline-danger" wire:click="cancelEngineerApproval">
                                            Cancelar Solicitação ao Engenheiro
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">
                                Painel somente leitura: a solicitação não está mais em execução ativa.
                            </div>
                            @if($cancellationRequest->engineer_approval_reason)
                                <div class="mt-2 small text-muted">
                                    <strong>Última orientação registrada:</strong> {{ $cancellationRequest->engineer_approval_reason }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="oexterno-subcard">
                        <div class="section-title">Linha do Tempo</div>
                        <div class="timeline-scroll">
                            <div class="phase-timeline">
                                @forelse($timeline as $phase)
                                    <div class="phase-item">
                                        <div class="fw-semibold">{{ $phase['label'] }}</div>
                                        <div class="phase-time">
                                            {{ optional($phase['time'])->format('d/m/Y H:i') }} · {{ $phase['user'] ?? 'Sistema' }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted">Sem interações registradas até o momento.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="oexterno-subcard">
                        <div class="section-title">Ordens</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Ordem</th>
                                        <th>Status</th>
                                        <th>Cancelada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cancellationRequest->Orders as $order)
                                        <tr>
                                            <td>{{ $order->ordem }}</td>
                                            <td>{{ $order->statusUser ?? $order->statusSist ?? '-' }}</td>
                                            <td>{{ $order->canceled ? 'Sim' : 'Não' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="oexterno-subcard">
                        <div class="section-title">Evidências</div>
                    @if($imageFiles->count())
                        <div class="evidence-grid mb-3">
                            @foreach($imageFiles as $file)
                                <div class="evidence-card">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk($file->disk)->url($file->path) }}"
                                        class="evidence-thumb"
                                        alt="{{ $file->original_name }}"
                                        data-evidence-src="{{ \Illuminate\Support\Facades\Storage::disk($file->disk)->url($file->path) }}"
                                        data-evidence-name="{{ $file->original_name }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#evidenceModal">
                                    <div class="small text-muted evidence-name mt-2" title="{{ $file->original_name }}">
                                        {{ $file->original_name }}
                                    </div>
                                    <div class="small text-muted">
                                        Origem:
                                        @if($file->origin === 'CANCELLATION_CONTROL')
                                            Controle
                                        @elseif($file->origin === 'EXECUCAO_PAGAMENTO')
                                            Execução
                                        @elseif($file->origin === 'ENGINEER_APPROVAL')
                                            Aprovação Engenheiro
                                        @else
                                            Solicitação
                                        @endif
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary mt-2"
                                        wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    <button class="btn btn-link btn-sm p-0 mt-1"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#evidence-name-{{ $file->id }}">
                                        Ver nome
                                    </button>
                                    <div class="collapse mt-1" id="evidence-name-{{ $file->id }}">
                                        <div class="small text-muted">{{ $file->original_name }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($otherFiles->count())
                        <ul class="list-group">
                            @foreach($otherFiles as $file)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column flex-grow-1 me-2">
                                        <span class="evidence-name" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                        <small class="text-muted">Tipo: {{ strtoupper($file->extension ?? '-') }} | Origem:
                                            @if($file->origin === 'CANCELLATION_CONTROL')
                                                Controle
                                            @elseif($file->origin === 'EXECUCAO_PAGAMENTO')
                                                Execução
                                            @elseif($file->origin === 'ENGINEER_APPROVAL')
                                                Aprovação Engenheiro
                                            @else
                                                Solicitação
                                            @endif
                                        </small>
                                        <button class="btn btn-link btn-sm p-0"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#evidence-full-{{ $file->id }}">
                                            Ver nome
                                        </button>
                                        <div class="collapse" id="evidence-full-{{ $file->id }}">
                                            <div class="small text-muted">{{ $file->original_name }}</div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
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

            @if(in_array($cancellationRequest->status, [\App\Enum\CancellationRequestStatus::ASSIGNED, \App\Enum\CancellationRequestStatus::PAUSED], true))
                <div class="row mt-4 g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ação</label>
                        <select class="form-select" wire:model="action">
                            @if($engineerRejected)
                                <option value="ABORTED">Cancelar solicitação</option>
                            @elseif($canFinalizeCancellation)
                                <option value="DONE">Finalizar</option>
                                <option value="PAUSED">Pausar</option>
                                <option value="ABORTED">Cancelar</option>
                            @else
                                <option value="PAUSED">Pausar</option>
                                <option value="ABORTED">Cancelar</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Comentário</label>
                        <textarea class="form-control" rows="5" wire:model.defer="comment"></textarea>
                        @error('comment')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-md-6">
                        <label class="form-label">Anexar evidências</label>
                        <input type="file" class="form-control" multiple wire:model="files" />
                        <ul class="list-group mt-2">
                            @foreach($tempFiles as $index => $file)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="evidence-name">{{ $file['original_name'] }}</span>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-success" wire:click="runAction">
                        Executar
                    </button>
                </div>
            @endif
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
