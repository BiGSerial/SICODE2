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
        @endphp

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Execução #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Tudo à vista para executar a solicitação.</span>
            </div>
            <a class="btn btn-outline-light" href="{{ route('services.cancellations.ongoing', ['service' => $service]) }}">Voltar</a>
        </div>

        <div class="oexterno-card p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="oexterno-subcard">
                        <div class="section-title">Nota</div>
                        <p class="mb-1"><strong>Número:</strong> {{ $cancellationRequest->Note->note ?? '-' }}</p>
                        <p class="mb-1"><strong>Cliente:</strong> {{ $cancellationRequest->Note->client ?? '-' }}</p>
                        <p class="mb-1"><strong>Status:</strong> {{ $cancellationRequest->Note->status ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="oexterno-subcard">
                        <div class="section-title">Solicitação</div>
                        <p class="mb-1"><strong>Categoria:</strong> {{ $cancellationRequest->Category->name ?? '-' }}</p>
                        <p class="mb-1">
                            <strong>Escopo:</strong>
                            <span class="badge {{ $cancellationRequest->scope?->badgeClass() ?? 'bg-secondary' }}">
                                {{ $cancellationRequest->scope?->label() ?? $cancellationRequest->scope?->value ?? $cancellationRequest->scope }}
                            </span>
                        </p>
                        <p class="mb-1">
                            <strong>Status:</strong>
                            <span class="badge {{ $cancellationRequest->status?->badgeClass() ?? 'bg-secondary' }}">
                                {{ $cancellationRequest->status?->label() ?? $cancellationRequest->status?->value ?? $cancellationRequest->status }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Solicitante:</strong> {{ $cancellationRequest->Requester->name ?? '-' }}</p>
                        <p class="mb-1">
                            <strong>Aprovação Eng.:</strong>
                            @if($cancellationRequest->engineer_approval_status)
                                <span class="badge {{ $cancellationRequest->engineer_approval_status?->badgeClass() ?? 'bg-secondary' }}">
                                    {{ $cancellationRequest->engineer_approval_status?->label() ?? $cancellationRequest->engineer_approval_status }}
                                </span>
                            @else
                                <span class="badge bg-secondary">Não solicitada</span>
                            @endif
                        </p>
                        <p class="mb-1"><strong>Engenheiro:</strong> {{ $cancellationRequest->EngineerApprover->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="oexterno-subcard">
                        <div class="section-title">Execução</div>
                        <p class="mb-1"><strong>Assumido em:</strong> {{ optional($cancellationRequest->assigned_at)->format('d/m/Y H:i') }}</p>
                        <p class="mb-1"><strong>Última atualização:</strong> {{ optional($cancellationRequest->updated_at)->format('d/m/Y H:i') }}</p>
                        <p class="mb-1"><strong>Solicitada em:</strong> {{ optional($cancellationRequest->engineer_approval_requested_at)->format('d/m/Y H:i') ?? '-' }}</p>
                        <p class="mb-1"><strong>Decidida em:</strong> {{ optional($cancellationRequest->engineer_approval_decided_at)->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="oexterno-subcard">
                        <div class="section-title">Aprovação de Engenheiro</div>
                        @if($approvalPending)
                            <div class="alert alert-warning py-2">
                                O cancelamento está aguardando decisão do engenheiro. Você pode trocar o engenheiro ou cancelar esta solicitação.
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

                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Engenheiro</label>
                                <select class="form-select" wire:model="engineerId">
                                    <option value="">Selecione</option>
                                    @foreach($engineers as $engineer)
                                        <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                                    @endforeach
                                </select>
                                @error('engineerId')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Motivo / justificativa</label>
                                <textarea class="form-control" rows="2" wire:model.defer="engineerReason"></textarea>
                                @if($cancellationRequest->engineer_approval_reason)
                                    <div class="small text-muted mt-1">
                                        Último motivo: {{ $cancellationRequest->engineer_approval_reason }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
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
                <div class="col-md-6">
                    <div class="oexterno-subcard">
                        <div class="section-title">Comentários</div>
                        <ul class="list-group">
                        @forelse($cancellationRequest->Comments as $commentItem)
                            <li class="list-group-item">
                                <strong>{{ $commentItem->User->name ?? '-' }}</strong>
                                <div class="small text-muted">{{ optional($commentItem->created_at)->format('d/m/Y H:i') }}</div>
                                <div class="comment-text">{{ $commentItem->message }}</div>
                                <button class="btn btn-link btn-sm p-0"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#comment-full-{{ $commentItem->id }}">
                                    Ver comentário
                                </button>
                                <div class="collapse mt-1" id="comment-full-{{ $commentItem->id }}">
                                    <div class="small">{{ $commentItem->message }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item">Sem comentários.</li>
                        @endforelse
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
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

            @can('admin')
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="oexterno-subcard">
                            <div class="section-title">Linha do tempo</div>
                            <div class="accordion" id="timelineAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="timelineHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#timelineCollapse" aria-expanded="false" aria-controls="timelineCollapse">
                                            Ver eventos
                                        </button>
                                    </h2>
                                    <div id="timelineCollapse" class="accordion-collapse collapse" aria-labelledby="timelineHeading"
                                        data-bs-parent="#timelineAccordion">
                                        <div class="accordion-body p-0">
                                            <div class="timeline-box">
                                                <ul class="list-group list-group-flush">
                                                    @forelse($cancellationRequest->Events as $event)
                                                        <li class="list-group-item">
                                                            <strong>{{ strtoupper($event->type) }}</strong>
                                                            <div class="small text-muted">{{ optional($event->created_at)->format('d/m/Y H:i') }} - {{ $event->Actor->name ?? 'Sistema' }}</div>
                                                            @if(!empty($event->meta))
                                                                <div class="small">{{ json_encode($event->meta) }}</div>
                                                            @endif
                                                        </li>
                                                    @empty
                                                        <li class="list-group-item">Sem eventos.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

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
                        <textarea class="form-control" rows="2" wire:model.defer="comment"></textarea>
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
                    <button class="btn btn-success"
                        onclick="if(!confirm('Confirmar ação na solicitação?')){event.stopImmediatePropagation();}"
                        wire:click="runAction">
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
