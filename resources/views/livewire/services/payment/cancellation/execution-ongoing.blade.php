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

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Em Andamento</h2>
                <span class="meta">Solicitações atribuídas a você.</span>
            </div>
        </div>

        <div class="oexterno-card p-3 mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Consulta em massa</strong>
            </div>
            <textarea class="form-control mb-3" rows="2"
                placeholder="Notas/Ordens (separe por vírgula, espaço ou linha)"
                wire:model.debounce.600ms="multiSearch"></textarea>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Ordens</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th>Assumido em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Orders->pluck('ordem')->implode(', ') }}</td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $request->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $request->status?->label() ?? $request->status?->value ?? $request->status }}
                                    </span>
                                </td>
                                <td>{{ optional($request->assigned_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" wire:click="selectRequest({{ $request->id }})">Detalhar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Nenhuma solicitação atribuída.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>

        @if($selectedRequest)
            <div class="oexterno-card p-3">
                <h5 class="mb-3">Solicitação #{{ $selectedRequest->id }}</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6>Nota</h6>
                            <p class="mb-1"><strong>Número:</strong> {{ $selectedRequest->Note->note ?? '-' }}</p>
                            <p class="mb-1"><strong>Cliente:</strong> {{ $selectedRequest->Note->client ?? '-' }}</p>
                            <p class="mb-1"><strong>Status:</strong> {{ $selectedRequest->Note->status ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6>Solicitação</h6>
                            <p class="mb-1"><strong>Categoria:</strong> {{ $selectedRequest->Category->name ?? '-' }}</p>
                            <p class="mb-1"><strong>Escopo:</strong> {{ $selectedRequest->scope }}</p>
                            <p class="mb-1">
                                <strong>Status:</strong>
                                <span class="badge {{ $selectedRequest->status?->badgeClass() ?? 'bg-secondary' }}">
                                    {{ $selectedRequest->status?->label() ?? $selectedRequest->status?->value ?? $selectedRequest->status }}
                                </span>
                            </p>
                            <p class="mb-1"><strong>Solicitante:</strong> {{ $selectedRequest->Requester->name ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6>Execução</h6>
                            <p class="mb-1"><strong>Assumido em:</strong> {{ optional($selectedRequest->assigned_at)->format('d/m/Y H:i') }}</p>
                            <p class="mb-1"><strong>Última atualização:</strong> {{ optional($selectedRequest->updated_at)->format('d/m/Y H:i') }}</p>
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
                                    @foreach($selectedRequest->Orders as $order)
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
                    <div class="col-md-6">
                        <h6>Comentários</h6>
                        <ul class="list-group">
                            @forelse($selectedRequest->Comments as $commentItem)
                                <li class="list-group-item">
                                    <strong>{{ $commentItem->User->name ?? '-' }}</strong>
                                    <div class="small text-muted">{{ optional($commentItem->created_at)->format('d/m/Y H:i') }}</div>
                                    <div>{{ $commentItem->message }}</div>
                                </li>
                            @empty
                                <li class="list-group-item">Sem comentários.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Evidências</h6>
                        <ul class="list-group">
                            @forelse($selectedRequest->EvidenceFiles as $file)
                                <li class="list-group-item">
                                    <span class="evidence-name" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                    <small class="text-muted d-block">Origem: {{ $file->origin }}</small>
                                </li>
                            @empty
                                <li class="list-group-item">Nenhum anexo.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="row mt-4 g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ação</label>
                        <select class="form-select" wire:model="action">
                            <option value="DONE">Finalizar</option>
                            <option value="PAUSED">Pausar</option>
                            <option value="ABORTED">Cancelar</option>
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
                                    <span>{{ $file['original_name'] }}</span>
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
            </div>
        @endif
    </div>
</div>
