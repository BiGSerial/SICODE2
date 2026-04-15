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
                <h2>Fila de Cancelamentos</h2>
                <span class="meta">Controle de solicitações e tempo de execução.</span>
            </div>
        </div>

        <div class="oexterno-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Filtros</strong>
                <button class="btn btn-outline-primary btn-sm" wire:click="exportToExcel" wire:loading.attr="disabled">
                    <i class="ri-file-excel-2-line align-middle"></i> Exportar
                </button>
                <select class="form-select w-auto" wire:model="status">
                    <option value="">Todos status</option>
                    <option value="SUBMITTED">SUBMITTED</option>
                    <option value="ASSIGNED">ASSIGNED</option>
                    <option value="PAUSED">PAUSED</option>
                    <option value="DONE">DONE</option>
                    <option value="REJECTED">REJECTED</option>
                    <option value="ABORTED">ABORTED</option>
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
                            <th>Solicitante</th>
                            <th>Executando</th>
                            <th>Status</th>
                            <th>Solicitado em</th>
                            <th>Assumido em</th>
                            <th>Tempo assumido</th>
                            <th>Tempo total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            @php
                                $start = $request->submitted_at ?? $request->created_at;
                                $end = $request->closed_at ?? now();
                                $minutes = $start ? $start->diffInMinutes($end) : null;
                                $assignedMinutes = $request->assigned_at ? $request->assigned_at->diffInMinutes($request->closed_at ?? now()) : null;
                            @endphp
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>
                                    {{ $request->Orders->pluck('ordem')->implode(', ') }}
                                </td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>{{ $request->Requester->name ?? '-' }}</td>
                                <td>{{ $request->Assignee->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $request->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $request->status?->label() ?? $request->status?->value ?? $request->status }}
                                    </span>
                                </td>
                                <td>{{ optional($request->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ optional($request->assigned_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $assignedMinutes !== null ? $assignedMinutes . ' min' : '-' }}</td>
                                <td>{{ $minutes !== null ? $minutes . ' min' : '-' }}</td>
                                <td class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('dispatch.cancellation.show', ['service' => $service, 'request' => $request->id]) }}">Ver</a>
                                    @if($request->status === \App\Enum\CancellationRequestStatus::SUBMITTED && !$request->assigned_to)
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
