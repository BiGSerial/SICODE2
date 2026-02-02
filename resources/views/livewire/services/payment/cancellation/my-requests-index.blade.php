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

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Minhas Solicitações</h2>
                <span class="meta">Acompanhe o andamento das suas solicitações de cancelamento.</span>
            </div>
        </div>

        <div class="oexterno-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Filtros</strong>
                <select class="form-select w-auto" wire:model="status">
                    <option value="">Todos status</option>
                    <option value="SUBMITTED">SUBMITTED</option>
                    <option value="ASSIGNED">ASSIGNED</option>
                    <option value="DONE">DONE</option>
                    <option value="REJECTED">REJECTED</option>
                </select>
                <input type="text" class="form-control w-auto" placeholder="Buscar nota" wire:model.debounce.500ms="search" />
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th>Assumido por</th>
                            <th>Enviado em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>{{ $request->status }}</td>
                                <td>{{ $request->Assignee->name ?? '-' }}</td>
                                <td>{{ optional($request->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('cancellations.show', ['request' => $request->id]) }}">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Nenhuma solicitação encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>
    </div>
</div>
