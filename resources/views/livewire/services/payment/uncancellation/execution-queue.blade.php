<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page { background: #f6f7fb; padding: 1.5rem 0; }
            .oexterno-header { background: linear-gradient(120deg, #0f172a, #0f766e 70%); color: #f8fafc; border-radius: 1rem; padding: 1.5rem 2rem; box-shadow: 0 16px 40px rgba(15, 23, 42, .2); margin-bottom: 1.5rem; }
            .oexterno-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .9rem; box-shadow: 0 12px 24px rgba(15, 23, 42, .06); }
        </style>

        <div class="oexterno-header">
            <h2 class="mb-0">Lista para Descancelamento</h2>
            <span class="text-white-50">Solicitações disponíveis para assumir.</span>
        </div>

        <div class="oexterno-card p-3">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-8">
                    <label class="form-label">Buscar</label>
                    <input class="form-control" type="text" wire:model.debounce.500ms="search" placeholder="Nota/OV ou ordem">
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
                            <th>Solicitante</th>
                            <th>Solicitado em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Orders->pluck('ordem')->implode(', ') ?: '-' }}</td>
                                <td><span class="badge {{ $request->scope?->badgeClass() ?? 'bg-secondary' }}">{{ $request->scope?->label() ?? '-' }}</span></td>
                                <td>{{ $request->Requester->name ?? '-' }}</td>
                                <td>{{ optional($request->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-success" wire:click="claim({{ $request->id }})">Assumir</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">Nenhuma solicitação disponível.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</div>
