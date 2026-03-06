<div wire:poll.15s>
    <x-show-loading />

    <div class="card mb-3">
        <div class="card-header edp-bg-seoweedgreen-100 text-white d-flex justify-content-between align-items-center">
            <h5 class="my-0">Monitoramento ADS</h5>
            <small>Atualizado em {{ $lastUpdateAt->format('d/m/Y H:i:s') }}</small>
        </div>
        <div class="card-body py-2">
            <div class="row">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <span class="badge text-bg-warning">Na fila</span>
                    <strong class="ms-1">{{ $queueCount }}</strong>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <span class="badge text-bg-success">Concluídas (DONE)</span>
                    <strong class="ms-1">{{ $doneCount }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row align-items-end g-2">
                <div class="col">
                    <h6 class="my-0">Fila ADS</h6>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label">Buscar nota</label>
                    <input type="text" class="form-control border border-secondary"
                        wire:model.debounce.500ms="queueSearch" placeholder="Numero da nota">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Por pagina</label>
                    <select class="form-select border border-secondary" wire:model="queuePerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th class="text-center">Nota</th>
                        <th>Empresa</th>
                        <th>Solicitante</th>
                        <th>Status Local</th>
                        <th>Status SQL</th>
                        <th>ADS</th>
                        <th class="text-center">Criado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queueRequests as $request)
                        @php
                            $sqlRow = $sqlStatusBySicodeId->get($request->id);
                        @endphp
                        <tr>
                            <td class="text-center">#{{ $request->id }}</td>
                            <td class="text-center fw-bold">{{ $request->note?->note ?? $request->note_id }}</td>
                            <td>{{ $request->company?->name ?? '-' }}</td>
                            <td>{{ $request->requestedBy?->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $request->status?->badgeClass() ?? 'text-bg-secondary' }}">
                                    {{ $request->status?->label() ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @if (!$sqlRow)
                                    <span class="badge text-bg-warning">Nao encontrado</span>
                                @else
                                    <span class="badge text-bg-info">{{ $sqlRow->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($request->url)
                                    <a href="{{ $request->url }}" class="btn btn-sm btn-outline-primary" target="_self">Baixar ADS</a>
                                @else
                                    <span class="text-muted">Sem link</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $request->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">Nenhuma solicitação na fila.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2">
            {{ $queueRequests->links() }}
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row align-items-end g-2">
                <div class="col">
                    <h6 class="my-0">Concluídas ADS</h6>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label">Buscar nota</label>
                    <input type="text" class="form-control border border-secondary"
                        wire:model.debounce.500ms="doneSearch" placeholder="Numero da nota">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Por pagina</label>
                    <select class="form-select border border-secondary" wire:model="donePerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th class="text-center">Nota</th>
                        <th>Empresa</th>
                        <th>Solicitante</th>
                        <th>Status Local</th>
                        <th>Status SQL</th>
                        <th>ADS</th>
                        <th class="text-center">Concluído</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($doneRequests as $request)
                        @php
                            $sqlRow = $sqlStatusBySicodeId->get($request->id);
                        @endphp
                        <tr>
                            <td class="text-center">#{{ $request->id }}</td>
                            <td class="text-center fw-bold">{{ $request->note?->note ?? $request->note_id }}</td>
                            <td>{{ $request->company?->name ?? '-' }}</td>
                            <td>{{ $request->requestedBy?->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $request->status?->badgeClass() ?? 'text-bg-secondary' }}">
                                    {{ $request->status?->label() ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @if (!$sqlRow)
                                    <span class="badge text-bg-warning">Nao encontrado</span>
                                @else
                                    <span class="badge text-bg-success">{{ $sqlRow->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($request->url)
                                    <a href="{{ $request->url }}" class="btn btn-sm btn-outline-primary" target="_self">Baixar ADS</a>
                                @else
                                    <span class="text-muted">Sem link</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $request->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">Nenhuma solicitação concluída.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2">
            {{ $doneRequests->links() }}
        </div>
    </div>
</div>
