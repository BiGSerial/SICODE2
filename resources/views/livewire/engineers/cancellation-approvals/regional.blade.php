<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-border: #e5e7eb;
                background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
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

        <div class="oexterno-header">
            <h2>Notas em Cancelamento</h2>
            <span class="meta">Notas em processo de cancelamento nas regionais associadas ao engenheiro.</span>
        </div>

        <div class="oexterno-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Fila Regional</strong>
                <span class="text-muted small">
                    Bases: {{ $baseConstructions->isNotEmpty() ? $baseConstructions->implode(', ') : 'Nenhuma base de construção associada' }}
                </span>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label small text-muted mb-1">Busca</label>
                    <input type="text" class="form-control" placeholder="Nota, solicitante ou executante"
                        wire:model.debounce.500ms="search" />
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1">Base de construção</label>
                    <select class="form-select" wire:model="baseConstructionFilter">
                        <option value="">Todas as minhas bases</option>
                        @foreach ($baseConstructions as $baseConstruction)
                            <option value="{{ $baseConstruction }}">{{ $baseConstruction }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model="scopeFilter">
                        <option value="">Todos os tipos</option>
                        @foreach ($scopes as $scope)
                            <option value="{{ $scope->value }}">{{ $scope->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select" wire:model="statusFilter">
                        <option value="">Todos os status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-grid align-items-end">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters" title="Limpar filtros">
                        <i class="ri-filter-off-line"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Base de construção</th>
                            <th>Município</th>
                            <th>Rubrica</th>
                            <th>Material</th>
                            <th>Motivo cancelamento</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Solicitante</th>
                            <th>Executante</th>
                            <th>Aberto em</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td class="fw-semibold">{{ $item->Note->note ?? '-' }}</td>
                                <td>{{ $item->Note?->city?->municipio ?? $item->Note?->city?->cidade ?? '—' }}</td>
                                <td>{{ $item->Note?->rubrica ?? '—' }}</td>
                                <td>{{ $item->Note?->material ?? '—' }}</td>
                                <td title="{{ $item->description ?? '' }}">
                                    <div>{{ $item->Category->name ?? '—' }}</div>
                                    @if(filled($item->description))
                                        <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $item->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $item->scope?->label() ?? $item->scope }}</td>
                                <td>
                                    <span class="badge {{ $item->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $item->status?->label() ?? $item->status }}
                                    </span>
                                </td>
                                <td>{{ $item->Requester->name ?? '-' }}</td>
                                <td>{{ $item->Assignee->name ?? '-' }}</td>
                                <td>{{ optional($item->submitted_at ?? $item->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('engineers.cancellations.show', ['request' => $item->id]) }}">
                                        Detalhes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4">
                                    {{ $baseConstructions->isEmpty() ? 'Nenhuma base de construção associada ao usuário.' : 'Nenhuma nota em processo de cancelamento nas suas bases.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $items->links() }}
        </div>
    </div>
</div>
