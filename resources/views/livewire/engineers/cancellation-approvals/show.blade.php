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
        </style>

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Solicitação #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Visualize evidências e registre sua decisão.</span>
            </div>
            <a class="btn btn-outline-light" href="{{ route('engineers.cancellations.index') }}">Voltar</a>
        </div>

        <div class="oexterno-card p-3">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="oexterno-subcard">
                        <div><strong>Nota:</strong> {{ $cancellationRequest->Note->note ?? '-' }}</div>
                        <div><strong>Cliente:</strong> {{ $cancellationRequest->Note->client ?? '-' }}</div>
                        <div><strong>Categoria:</strong> {{ $cancellationRequest->Category->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="oexterno-subcard">
                        <div><strong>Solicitante inicial:</strong> {{ $cancellationRequest->Requester->name ?? '-' }}</div>
                        <div><strong>Executante:</strong> {{ $cancellationRequest->Assignee->name ?? '-' }}</div>
                        <div><strong>Solicitação ao engenheiro:</strong> {{ optional($cancellationRequest->engineer_approval_requested_at)->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="oexterno-subcard">
                        <div><strong>Status da solicitação:</strong> {{ $cancellationRequest->status?->label() ?? '-' }}</div>
                        <div><strong>Status da aprovação:</strong>
                            <span class="badge {{ $cancellationRequest->engineer_approval_status?->badgeClass() ?? 'bg-secondary' }}">
                                {{ $cancellationRequest->engineer_approval_status?->label() ?? '-' }}
                            </span>
                        </div>
                        <div><strong>Motivo informado:</strong> {{ $cancellationRequest->engineer_approval_reason ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="oexterno-subcard">
                        <h6>Ordens</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
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

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="oexterno-subcard">
                        <h6>Evidências</h6>
                        <ul class="list-group">
                            @forelse($cancellationRequest->EvidenceFiles as $file)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div>{{ $file->original_name }}</div>
                                        <small class="text-muted">{{ strtoupper($file->extension ?? '-') }} | {{ $file->origin }}</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                </li>
                            @empty
                                <li class="list-group-item">Sem anexos.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            @if($cancellationRequest->engineer_approval_status === \App\Enum\CancellationEngineerApprovalStatus::PENDING)
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Novo status</label>
                        <select class="form-select" wire:model="decision">
                            <option value="APPROVED">Autorizar cancelamento</option>
                            <option value="REJECTED">Rejeitar cancelamento</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Justificativa</label>
                        <textarea class="form-control" rows="3" wire:model.defer="reason"></textarea>
                        @error('reason')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Anexar arquivos/imagens</label>
                        <input type="file" class="form-control" multiple wire:model="files">
                        <ul class="list-group mt-2">
                            @foreach($tempFiles as $index => $file)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $file['original_name'] }}</span>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <button class="btn btn-success" wire:click="decide">Salvar decisão</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
