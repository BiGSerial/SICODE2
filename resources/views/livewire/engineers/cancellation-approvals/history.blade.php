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
        </style>

        <div class="oexterno-header">
            <h2>Histórico de Aprovações</h2>
            <span class="meta">Decisões já concluídas por você nas solicitações de cancelamento.</span>
        </div>

        <div class="oexterno-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Histórico</strong>
                <input type="text" class="form-control w-auto" placeholder="Buscar nota"
                    wire:model.debounce.500ms="search" />
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Status da aprovação</th>
                            <th>Solicitante</th>
                            <th>Executante</th>
                            <th>Engenheiro</th>
                            <th>Conclusão da aprovação</th>
                            <th>Decidido em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->Note->note ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $item->engineer_approval_status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $item->engineer_approval_status?->label() ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $item->Requester->name ?? '-' }}</td>
                                <td>{{ $item->EngineerApprovalRequester->name ?? '-' }}</td>
                                <td>{{ $item->EngineerApprover->name ?? '-' }}</td>
                                <td>{{ $item->engineer_approval_reason ?? '-' }}</td>
                                <td>{{ optional($item->engineer_approval_decided_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('engineers.cancellations.show', ['request' => $item->id]) }}">
                                        Ver detalhes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Nenhuma aprovação concluída.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        </div>
    </div>
</div>
