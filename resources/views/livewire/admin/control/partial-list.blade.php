<div class="oexterno-page">
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
        .filters-grid .filter-card,
        .summary-bar,
        .table-card {
            background-color: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: .9rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }
        .filters-grid .filter-card { padding: 1rem 1.25rem; height: 100%; }
        .filters-grid .filter-card h6 {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
            color: var(--oe-muted);
        }
        .summary-bar { padding: .75rem 1.25rem; }
        .summary-bar .summary-item { font-size: .92rem; color: var(--oe-muted); }
        .summary-bar .summary-item strong { color: var(--oe-ink); }
        .table-card { overflow: hidden; }
        .table-card .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }
        .table-card .table tbody td { font-size: .92rem; }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-0">CONTROLE DE DADOS</h2>
                <div class="meta">Controle de Informes Parciais</div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12 col-lg-7 col-xl-6">
                        <div class="filter-card">
                            <h6>Pesquisa</h6>
                            <div class="row g-2">
                                <div class="col-12 col-sm-4">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-8">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.debounce.500ms="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar">
                                        <label for="search">Buscar por id, nota, ordem ou responsavel</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                            <i class="ri-checkbox-multiple-blank-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @if (!empty($multiSearch))
                                <div class="mt-2">
                                    <button class="btn btn-outline-secondary btn-sm" wire:click="clearBatch">
                                        Limpar lote
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-lg-5 col-xl-6">
                        <div class="filter-card h-100">
                            <h6>Dicas</h6>
                            <div class="text-muted small">
                                Controle para rejeitar, apagar, editar informacoes e ajustar associacoes das atividades/ordens do informe parcial.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    @if ($lists->count())
                        {{ $lists->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="{{ !empty($multiSearch) && !empty($missing) ? 'col-12 col-xl-9' : 'col-12' }}">
                <div class="table-card">
                    @if (!$lists->count())
                        <div class="card-body">
                            <h4 class="text-center text-muted">SEM DADOS PARA EXIBIR</h4>
                        </div>
                    @else
                        <div class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">LISTA DE INFORMES PARCIAIS</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped mb-0">
                                <thead class="table-dark">
                                    <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                        <th class="fw-bold text-start">ID / Nota</th>
                                        <th class="fw-bold text-center">Empresa</th>
                                        <th class="fw-bold text-center">Responsavel</th>
                                        <th class="fw-bold text-center">Valor</th>
                                        <th class="fw-bold text-center">Atividades</th>
                                        <th class="fw-bold text-center">Status</th>
                                        <th class="fw-bold text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lists as $item)
                                        <tr class="align-middle text-center">
                                            <td class="text-start">
                                                <div class="fw-semibold">#{{ $item->id }}</div>
                                                <div class="small text-muted">{{ $item->Note?->note ?? '---' }}</div>
                                            </td>
                                            <td>{{ $item->company?->name }}</td>
                                            <td>{{ $item->responsible ?: $item->user?->name }}</td>
                                            <td>{{ $item->value !== null ? 'R$ ' . number_format((float) $item->value, 2, ',', '.') : '-' }}</td>
                                            <td>
                                                @forelse ($item->orders as $order)
                                                    <span class="badge text-bg-light">{{ $order->ordem }}</span>
                                                @empty
                                                    <span class="text-muted">Sem atividades</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                <span class="badge {{ $item->allow ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                    {{ $item->allow ? 'Aprovado' : 'Nao aprovado' }}
                                                </span>
                                                <span class="badge {{ $item->deny ? 'text-bg-danger' : 'text-bg-secondary' }}">
                                                    {{ $item->deny ? 'Rejeitado' : 'Nao rejeitado' }}
                                                </span>
                                                <span class="badge {{ $item->complete ? 'text-bg-info' : 'text-bg-light' }}">
                                                    {{ $item->complete ? 'Completo' : 'Aberto' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                    <button class="btn btn-sm btn-primary p-1"
                                                        wire:click="$emitTo('admin.control.partial-edit', 'getInfoResponse', {{ $item->id }})">
                                                        Editar
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success p-1"
                                                        wire:click="approve({{ $item->id }})">
                                                        Aprovar
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning p-1"
                                                        wire:click="reject({{ $item->id }})">
                                                        Rejeitar
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger p-1"
                                                        wire:click="requestDelete({{ $item->id }})">
                                                        Apagar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            @if (!empty($multiSearch) && !empty($missing))
                <div class="col-12 col-xl-3">
                    <div class="table-card">
                        <div class="card-header fw-bold text-bg-secondary">NAO ENCONTRADOS</div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                @foreach ($missing as $item)
                                    <li class="list-group-item">{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="summary-bar mt-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">{{ $lists->links() }}</div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content edp-bg-stategrey-50">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">Buscar Multi-Notas</div>
                    <textarea class="form-control" cols="50" rows="10" wire:model.defer="advanceSearch"></textarea>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                    </div>
                </div>
            </div>
        </div>

        @livewire('admin.control.partial-edit', key('admin-partial-edit'))
    </div>
</div>
