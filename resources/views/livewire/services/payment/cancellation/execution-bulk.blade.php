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

        <div class="oexterno-header d-flex align-items-center">
            <div class="me-auto">
                <h2>Cancelamento em massa</h2>
                <span class="meta">Revise antes de executar.</span>
            </div>
            <a class="btn btn-outline-light" href="{{ route('services.cancellations.ongoing', ['service' => $service]) }}">Voltar</a>
        </div>

        <div class="oexterno-card p-3 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <strong class="me-auto">Ações</strong>
                <button class="btn btn-outline-primary btn-sm" wire:click="exportOrders">
                    <i class="ri-file-excel-2-line align-middle"></i> Exportar por ordem
                </button>
            </div>

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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Nenhuma solicitação para revisão.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="oexterno-card p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Ação</label>
                    <select class="form-select" wire:model="action">
                        <option value="DONE">Finalizar</option>
                        <option value="PAUSED">Pausar</option>
                        <option value="ABORTED">Cancelar</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Comentário (obrigatório p/ pausar ou cancelar)</label>
                    <textarea class="form-control" rows="4" wire:model.defer="comment"></textarea>
                    @error('comment')<span class="text-danger small">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Evidências (1x para todos)</label>
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
                <div class="col-md-6">
                    <div class="alert alert-info h-100 mb-0">
                        Os anexos enviados aqui serão referenciados por todas as solicitações selecionadas.
                        O arquivo é armazenado uma única vez no sistema.
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-success" wire:click="runBulkAction">
                    Executar
                </button>
            </div>
        </div>
    </div>
</div>
