<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page { background: #f6f7fb; padding: 1.5rem 0; }
            .oexterno-header { background: linear-gradient(120deg, #0f172a, #0f766e 70%); color: #f8fafc; border-radius: 1rem; padding: 1.5rem 2rem; box-shadow: 0 16px 40px rgba(15, 23, 42, .2); margin-bottom: 1.5rem; }
            .oexterno-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .9rem; box-shadow: 0 12px 24px rgba(15, 23, 42, .06); }
        </style>

        <div class="oexterno-header">
            <h2 class="mb-0">Histórico de Descancelamento</h2>
            <span class="text-white-50">Solicitações concluídas ou rejeitadas.</span>
        </div>

        <div class="oexterno-card p-3">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label">Buscar</label>
                    <input class="form-control" type="text" wire:model.debounce.500ms="search" placeholder="Nota/OV ou ordem">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data inicial</label>
                    <input class="form-control" type="date" wire:model="dateFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data final</label>
                    <input class="form-control" type="date" wire:model="dateTo">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota/OV</th>
                            <th>Ordens</th>
                            <th>Escopo</th>
                            <th>Status</th>
                            <th>Solicitante</th>
                            <th>Finalizado por</th>
                            <th>Finalizado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Orders->pluck('ordem')->implode(', ') ?: '-' }}</td>
                                <td><span class="badge {{ $request->scope?->badgeClass() ?? 'bg-secondary' }}">{{ $request->scope?->label() ?? '-' }}</span></td>
                                <td><span class="badge {{ $request->status?->badgeClass() ?? 'bg-secondary' }}">{{ $request->status?->label() ?? '-' }}</span></td>
                                <td>{{ $request->Requester->name ?? '-' }}</td>
                                <td>{{ $request->Closer->name ?? '-' }}</td>
                                <td>{{ optional($request->closed_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">Nenhum histórico encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</div>
