<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <strong class="me-auto">Fila de Cancelamentos</strong>
            <select class="form-select w-auto" wire:model="status">
                <option value="">Todos status</option>
                <option value="SUBMITTED">SUBMITTED</option>
                <option value="ASSIGNED">ASSIGNED</option>
                <option value="DONE">DONE</option>
                <option value="REJECTED">REJECTED</option>
            </select>
            <select class="form-select w-auto" wire:model="categoryId">
                <option value="">Todas categorias</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <input type="date" class="form-control w-auto" wire:model="dateFrom" />
            <input type="date" class="form-control w-auto" wire:model="dateTo" />
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Nota" wire:model.debounce.500ms="noteSearch" />
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Ordem" wire:model.debounce.500ms="orderSearch" />
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Solicitante" wire:model.debounce.500ms="requesterSearch" />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Ordens</th>
                            <th>Categoria</th>
                            <th>Motivo</th>
                            <th>Solicitante</th>
                            <th>Status</th>
                            <th>Assumido por</th>
                            <th>Enviado em</th>
                            <th>Tempo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            @php
                                $start = $request->submitted_at ?? $request->created_at;
                                $end = $request->closed_at ?? now();
                                $minutes = $start ? $start->diffInMinutes($end) : null;
                            @endphp
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>
                                    {{ $request->Orders->pluck('ordem')->implode(', ') }}
                                </td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>{{ $request->description ?? '-' }}</td>
                                <td>{{ $request->Requester->name ?? '-' }}</td>
                                <td>{{ $request->status }}</td>
                                <td>{{ $request->Assignee->name ?? '-' }}</td>
                                <td>{{ optional($request->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $minutes !== null ? $minutes . ' min' : '-' }}</td>
                                <td class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('dispatch.cancellation.show', ['service' => $service, 'request' => $request->id]) }}">Ver</a>
                                    @if($request->status === 'SUBMITTED' && !$request->assigned_to)
                                        <button class="btn btn-sm btn-outline-success" wire:click="claim({{ $request->id }})">Assumir</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">Nenhuma solicitação encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>
    </div>
</div>
