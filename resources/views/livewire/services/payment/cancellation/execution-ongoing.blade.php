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
                <button class="btn btn-outline-secondary btn-sm" wire:click="exportUserList">
                    Exportar lista
                </button>
                <button class="btn btn-outline-success btn-sm" wire:click="openBulkCloseModal"
                    @disabled(count($selected) < 1)>
                    Encerrar em massa
                </button>
                <button class="btn btn-outline-warning btn-sm" wire:click="openBulkReopenModal"
                    @disabled(count($selected) < 1)>
                    Reabrir em massa
                </button>
                <button class="btn btn-outline-primary btn-sm" wire:click="goBulkReview"
                    @disabled(count($selected) < 2)>
                    Revisar em lote
                </button>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-8">
                    <textarea class="form-control" rows="2"
                        placeholder="Notas/Ordens (separe por vírgula, espaço ou linha)"
                        wire:model.debounce.600ms="multiSearch"></textarea>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model="scopeFilter">
                        <option value="ALL">Todos os escopos</option>
                        @foreach($scopes as $scope)
                            <option value="{{ $scope->value }}">{{ $scope->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <small class="text-muted">
                        Encerramento em lote só é permitido para solicitações do mesmo escopo (Nota inteira ou WorkForm).
                    </small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input"
                                    wire:model="selectAll"
                                    wire:change="setSelectAll(@json($requests->pluck('id')->all()))">
                            </th>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Ordens</th>
                            <th>Escopo</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th>Aprovação Eng.</th>
                            <th>Assumido em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input" value="{{ $request->id }}"
                                        wire:model="selected">
                                </td>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Orders->pluck('ordem')->implode(', ') }}</td>
                                <td>
                                    <span class="badge {{ $request->scope?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $request->scope?->label() ?? $request->scope }}
                                    </span>
                                </td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $request->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $request->status?->label() ?? $request->status?->value ?? $request->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($request->engineer_approval_status)
                                        <span class="badge {{ $request->engineer_approval_status?->badgeClass() ?? 'bg-secondary' }}">
                                            {{ $request->engineer_approval_status?->label() ?? $request->engineer_approval_status }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Não</span>
                                    @endif
                                </td>
                                <td>{{ optional($request->assigned_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('services.cancellations.ongoing.show', ['service' => $service, 'request' => $request->id]) }}">
                                        Detalhar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">Nenhuma solicitação atribuída.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>

    </div>

    <div wire:ignore.self class="modal fade" id="bulkCloseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Encerrar em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Definição</label>
                        <select class="form-select" wire:model="bulkActionType">
                            <option value="DONE">Finalizar com o processo definido</option>
                            <option value="ENGINEER_APPROVAL">Enviar para aprovação do engenheiro</option>
                        </select>
                    </div>

                    @if($bulkActionType === 'ENGINEER_APPROVAL')
                        <div class="mb-3">
                            <label class="form-label">Engenheiro</label>
                            <select class="form-select" wire:model="bulkEngineerId">
                                <option value="">Selecione</option>
                                @foreach($engineers as $engineer)
                                    <option value="{{ $engineer->id }}">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($engineer->name)) }}</option>
                                @endforeach
                            </select>
                            @error('bulkEngineerId')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                    @endif

                    <div class="mb-1">
                        <label class="form-label">Texto de encerramento (obrigatório)</label>
                        <textarea class="form-control" rows="4" wire:model.defer="bulkComment"></textarea>
                        @error('bulkComment')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>

                    @if($bulkActionType === 'DONE')
                        <div class="small text-muted mt-2">
                            Será adicionado ao final do texto: <strong>Finalizado em Lote X obras</strong>.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" wire:click="runBulkCloseAction">Confirmar ação</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="bulkReopenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Abrir em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Texto de abertura (obrigatório)</label>
                    <textarea class="form-control" rows="4" wire:model.defer="bulkReopenComment"></textarea>
                    @error('bulkReopenComment')<span class="text-danger small">{{ $message }}</span>@enderror
                    <div class="small text-muted mt-2">Permitido apenas para solicitações pausadas.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" wire:click="runBulkReopenAction">Confirmar ação</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const closeModalElement = document.getElementById('bulkCloseModal');
        const reopenModalElement = document.getElementById('bulkReopenModal');

        window.addEventListener('bulk-close-modal-show', () => {
            bootstrap.Modal.getOrCreateInstance(closeModalElement).show();
        });

        window.addEventListener('bulk-close-modal-hide', () => {
            bootstrap.Modal.getOrCreateInstance(closeModalElement).hide();
        });

        window.addEventListener('bulk-reopen-modal-show', () => {
            bootstrap.Modal.getOrCreateInstance(reopenModalElement).show();
        });

        window.addEventListener('bulk-reopen-modal-hide', () => {
            bootstrap.Modal.getOrCreateInstance(reopenModalElement).hide();
        });
    });
</script>
