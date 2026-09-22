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
                    Regionais: {{ $regions->isNotEmpty() ? $regions->implode(', ') : 'Nenhuma regional associada' }}
                </span>
                <input type="text" class="form-control w-auto" placeholder="Buscar nota"
                    wire:model.debounce.500ms="search" />
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Regional</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Solicitante</th>
                            <th>Executante</th>
                            <th>Aberto em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td class="fw-semibold">{{ $item->Note->note ?? '-' }}</td>
                                <td>{{ $item->Note?->city?->regional ?? '—' }}</td>
                                <td>{{ $item->scope?->label() ?? $item->scope }}</td>
                                <td>
                                    <span class="badge {{ $item->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $item->status?->label() ?? $item->status }}
                                    </span>
                                </td>
                                <td>{{ $item->Requester->name ?? '-' }}</td>
                                <td>{{ $item->Assignee->name ?? '-' }}</td>
                                <td>{{ optional($item->submitted_at ?? $item->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    {{ $regions->isEmpty() ? 'Nenhuma regional associada ao usuário.' : 'Nenhuma nota em processo de cancelamento nas suas regionais.' }}
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
