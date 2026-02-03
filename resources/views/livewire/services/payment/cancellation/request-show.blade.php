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

            .evidence-name {
                display: block;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
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
        @endphp

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Solicitação #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Detalhe completo da solicitação de cancelamento.</span>
            </div>
            <div class="mt-3">
                <a class="btn btn-outline-light" href="{{ url()->previous() }}">Voltar</a>
            </div>
        </div>

        <div class="oexterno-card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Nota</h6>
                        <p class="mb-1"><strong>Número:</strong> {{ $cancellationRequest->Note->note ?? '-' }}</p>
                        <p class="mb-1"><strong>Cliente:</strong> {{ $cancellationRequest->Note->client ?? '-' }}</p>
                        <p class="mb-1"><strong>Status:</strong> {{ $cancellationRequest->Note->status ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Solicitação</h6>
                        <p class="mb-1"><strong>Categoria:</strong> {{ $cancellationRequest->Category->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Escopo:</strong> {{ $cancellationRequest->scope }}</p>
                        <p class="mb-1">
                            <strong>Status:</strong>
                            <span class="badge {{ $cancellationRequest->status?->badgeClass() ?? 'bg-secondary' }}">
                                {{ $cancellationRequest->status?->label() ?? $cancellationRequest->status?->value ?? $cancellationRequest->status }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Criada por:</strong> {{ $cancellationRequest->Requester->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Execução</h6>
                        <p class="mb-1"><strong>Assumido:</strong> {{ $cancellationRequest->Assignee->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Finalizado por:</strong> {{ $cancellationRequest->Closer->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Encerrado em:</strong> {{ optional($cancellationRequest->closed_at)->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <h6>Ordens</h6>
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

            <div class="row mt-3">
                <div class="col-12">
                    <h6>Descrição</h6>
                    <p class="mb-0">{{ $cancellationRequest->description ?? '-' }}</p>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>Anexos</h6>
                    @if($imageFiles->isNotEmpty())
                        <div class="row g-2 mb-3">
                            @foreach($imageFiles as $file)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="border rounded p-2 text-center h-100">
                                        <img
                                            src="{{ Storage::disk($file->disk)->url($file->path) }}"
                                            class="img-fluid rounded mb-2"
                                            style="max-height: 140px; object-fit: cover; width: 100%;"
                                            alt="{{ $file->original_name }}"
                                            data-evidence-src="{{ Storage::disk($file->disk)->url($file->path) }}"
                                            data-evidence-name="{{ $file->original_name }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#evidenceModal"
                                        />
                                        <div class="small text-muted evidence-name" title="{{ $file->original_name }}">
                                            {{ $file->original_name }}
                                        </div>
                                        <div class="small text-muted">
                                            Origem:
                                            @if($file->origin === 'CANCELLATION_CONTROL')
                                                Controle
                                            @elseif($file->origin === 'EXECUCAO_PAGAMENTO')
                                                Execução
                                            @else
                                                Solicitação
                                            @endif
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($otherFiles->isNotEmpty())
                        <ul class="list-group">
                            @foreach($otherFiles as $file)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column">
                                        <span class="evidence-name" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                        <small class="text-muted">Tipo: {{ strtoupper($file->extension ?? '-') }}</small>
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
                <div class="col-md-6">
                    <h6>Linha do tempo</h6>
                    <ul class="list-group">
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
