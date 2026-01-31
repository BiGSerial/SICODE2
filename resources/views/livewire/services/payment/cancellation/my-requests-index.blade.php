<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <strong class="me-auto">Minhas Solicitações</strong>
            <select class="form-select w-auto" wire:model="status">
                <option value="">Todos status</option>
                <option value="SUBMITTED">SUBMITTED</option>
                <option value="ASSIGNED">ASSIGNED</option>
                <option value="DONE">DONE</option>
                <option value="REJECTED">REJECTED</option>
            </select>
            <input type="text" class="form-control w-auto" placeholder="Buscar nota" wire:model.debounce.500ms="search" />
        </div>
        <div class="card-body">
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
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('services.cancellation.show', ['service' => $service, 'request' => $request->id]) }}">Ver</a>
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
