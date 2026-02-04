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

            .oexterno-header h2 {
                font-weight: 700;
                letter-spacing: 0.02em;
                margin: 0;
            }

            .oexterno-card {
                background: var(--oe-surface);
                border: 1px solid var(--oe-border);
                border-radius: 0.9rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }
        </style>

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Solicitação de Cancelamento</h2>
                <span class="meta">Abra a solicitação com evidências e acompanhe o andamento.</span>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="oexterno-card p-3 mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Número da Nota</label>
                            <input type="text" class="form-control" wire:model.defer="noteSearch" placeholder="Ex: 123456" />
                            @error('noteSearch')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" wire:click="findNote">Buscar</button>
                        </div>
                    </div>

                    @if($note)
                        <hr />
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Nota:</strong> {{ $note->note }}</p>
                                <p class="mb-1"><strong>Cliente:</strong> {{ $note->client ?? '-' }}</p>
                                <p class="mb-1"><strong>Status:</strong> {{ $note->status ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                @if($noteCanceled)
                                    <div class="alert alert-danger">Nota já cancelada. Não é possível abrir nova solicitação.</div>
                                @elseif($hasCanceledOrders)
                                    <div class="alert alert-warning">Existem ordens já canceladas. Selecione ordens específicas.</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <select class="form-select" wire:model.defer="categoryId">
                                    <option value="">Selecione...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('categoryId')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Escopo</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::NOTE_FULL->value }}" id="scopeFull" @if($noteCanceled || $hasCanceledOrders) disabled @endif>
                                    <label class="form-check-label" for="scopeFull">Cancelar nota inteira</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value }}" id="scopePartial">
                                    <label class="form-check-label" for="scopePartial">Cancelar ordens específicas</label>
                                </div>
                                @error('scope')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Ordens</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Ordem</th>
                                                <th>Status</th>
                                                <th>Cancelada</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orders as $order)
                                                <tr>
                                                    <td>
                                                        @if(!$order['canceled'])
                                                            <input class="form-check-input" type="checkbox" value="{{ $order['id'] }}" wire:model="selectedOrders" @if($scope !== \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value) disabled @endif>
                                                        @endif
                                                    </td>
                                                    <td>{{ $order['ordem'] }}</td>
                                                    <td>{{ $order['status'] ?? '-' }}</td>
                                                    <td>{{ $order['canceled'] ? 'Sim' : 'Não' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @error('selectedOrders')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Descrição complementar</label>
                                <textarea class="form-control" rows="3" wire:model.defer="description"></textarea>
                                @error('description')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Evidências</label>
                                <input type="file" class="form-control" multiple wire:model="files">
                                @error('files.*')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-12 mt-2">
                                @if(count($tempFiles))
                                    <ul class="list-group">
                                        @foreach($tempFiles as $index => $file)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>{{ $file['original_name'] }} ({{ number_format($file['size'] / 1024, 1) }} KB)</span>
                                                <button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-4">
                                <button class="btn btn-success w-100" wire:click="submit" @if($noteCanceled) disabled @endif>Enviar solicitação</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="oexterno-card p-3">
                    <div class="fw-semibold mb-2">Regras rápidas</div>
                    <div>
                        <ul class="small mb-0">
                            <li>Selecione a categoria e o escopo antes de enviar.</li>
                            <li>Notas canceladas não permitem novas solicitações.</li>
                            <li>Ordens já canceladas não podem ser selecionadas.</li>
                            <li>Evidências podem ser obrigatórias conforme categoria.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
