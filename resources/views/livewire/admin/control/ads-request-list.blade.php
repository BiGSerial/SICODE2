<div class="oexterno-page">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
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

        .oexterno-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .filters-grid .filter-card {
            background-color: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .filters-grid .filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--oe-muted);
        }

        .summary-bar {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            font-size: 0.92rem;
            color: var(--oe-muted);
        }

        .summary-bar .summary-item strong {
            color: var(--oe-ink);
        }

        .table-card {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>CONTROLE DE DADOS</h2>
                <div class="meta">Controle ADS Solicitadas</div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12 col-lg-8">
                        <div class="filter-card">
                            <h6>Pesquisa</h6>
                            <div class="row g-2">
                                <div class="col-12 col-sm-3">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelectAds">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                        </select>
                                        <label for="perPageSelectAds">Registros por página</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-3">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="statusFilter" id="statusFilterAds">
                                            <option value="all">Todos</option>
                                            @foreach ($statusOptions as $status)
                                                <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <label for="statusFilterAds">Status</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-floating w-100">
                                        <input wire:model.debounce.500ms="search" type="text"
                                            class="form-control border border-secondary" id="searchAds"
                                            placeholder="Buscar">
                                        <label for="searchAds">Buscar por id, nota, empresa, usuário, status</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="filter-card h-100">
                            <h6>Edição</h6>
                            <div class="text-muted small">
                                Use o botão <strong>Editar</strong> para ajustar dados da ADS solicitada, incluindo o usuário destinatário.
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
                        Exibindo <strong>{{ $lists->firstItem() ?? 0 }}</strong> até
                        <strong>{{ $lists->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            @if (!$lists->count())
                <div class="card-body">
                    <h4 class="text-center text-muted">SEM DADOS PARA EXIBIR</h4>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                <th>ID</th>
                                <th>Nota</th>
                                <th>Empresa</th>
                                <th>Usuário</th>
                                <th>Status</th>
                                <th>Versão</th>
                                <th>Criado em</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $item)
                                <tr class="align-middle">
                                    <td class="fw-semibold">#{{ $item->id }}</td>
                                    <td>{{ $item->note?->note ?? '---' }}</td>
                                    <td>{{ $item->company?->name ?? '---' }}</td>
                                    <td>{{ $item->requestedBy?->name ?? '---' }}</td>
                                    <td>
                                        <span class="badge {{ $item->status?->badgeClass() ?? 'text-bg-secondary' }}">
                                            {{ $item->status?->label() ?? $item->status }}
                                        </span>
                                    </td>
                                    <td>{{ $item->version }}</td>
                                    <td>{{ $item->created_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary"
                                            wire:click="$emitTo('admin.control.ads-request-edit', 'getInfoResponse', {{ $item->id }})">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="summary-bar mt-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    {{ $lists->links() }}
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() ?? 0 }}</strong> até
                        <strong>{{ $lists->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        @livewire('admin.control.ads-request-edit', key('admin-ads-request-edit'))
    </div>
</div>
