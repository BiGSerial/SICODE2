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
        </style>

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Solicitação #{{ $cancellationRequest->id }}</h2>
                <span class="meta">Execução do cancelamento conforme escopo.</span>
            </div>
            @if($cancellationRequest->status === 'SUBMITTED' && !$cancellationRequest->assigned_to)
                <button class="btn btn-outline-light" wire:click="claim">Assumir</button>
            @endif
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
                        <p class="mb-1"><strong>Status:</strong> {{ $cancellationRequest->status }}</p>
                        <p class="mb-1"><strong>Solicitante:</strong> {{ $cancellationRequest->Requester->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Execução</h6>
                        <p class="mb-1"><strong>Assumido:</strong> {{ $cancellationRequest->Assignee->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Assumido em:</strong> {{ optional($cancellationRequest->assigned_at)->format('d/m/Y H:i') }}</p>
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
                    <ul class="list-group">
                        @forelse($cancellationRequest->EvidenceFiles as $file)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $file->original_name }}</span>
                                <button class="btn btn-sm btn-outline-primary" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                            </li>
                        @empty
                            <li class="list-group-item">Nenhum anexo.</li>
                        @endforelse
                    </ul>
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

            @if(in_array($cancellationRequest->status, ['SUBMITTED', 'ASSIGNED'], true))
                <div class="row mt-4">
                    <div class="col-md-6">
                        <label class="form-label">Ação</label>
                        <select class="form-select" wire:model="action">
                            <option value="DONE">Concluir (Cancelar)</option>
                            <option value="REJECTED">Rejeitar</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Observação final</label>
                        <input type="text" class="form-control" wire:model.defer="closureNote" />
                        @error('closureNote')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-12 mt-3">
                        <button class="btn btn-success" wire:click="finalize">Finalizar</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
