@push('css')
    <style>
        .iat-page {
            --iat-bg: #f6f7fb;
            --iat-surface: #ffffff;
            --iat-ink: #1f2933;
            --iat-muted: #6b7280;
            --iat-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--iat-bg);
            padding: 1.5rem 0;
        }

        .iat-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .iat-filter-card {
            background-color: var(--iat-surface);
            border: 1px solid var(--iat-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .iat-filter-card .form-label {
            margin-bottom: 0.55rem;
        }

        .iat-filter-card .form-control,
        .iat-filter-card .form-select {
            min-height: 46px;
            font-size: 1rem;
        }

        .iat-filter-card .iat-company-select {
            min-height: 170px;
        }

        .iat-summary-card {
            background: var(--iat-surface);
            border: 1px solid var(--iat-border);
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            height: 100%;
        }

        .iat-summary-card .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--iat-muted);
            letter-spacing: 0.06em;
        }

        .iat-summary-card .value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--iat-ink);
            line-height: 1.25;
        }

        .iat-table-card {
            background: var(--iat-surface);
            border: 1px solid var(--iat-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .iat-pagination {
            background: #fff;
            border: 1px solid var(--iat-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
        }
    </style>
@endpush

<div class="iat-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="iat-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-1">ADS SOLICITADAS</h2>
                <div class="text-light opacity-75">Solicitações de ADS abertas pelos usuários</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" wire:click="clearFilters">
                    <i class="ri-filter-off-line me-1"></i> Limpar
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="iat-filter-card">
                    <label class="form-label small text-muted">Data inicial (solicitação)</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="date_in"
                        max="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="iat-filter-card">
                    <label class="form-label small text-muted">Data final (solicitação)</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="date_out"
                        max="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="iat-filter-card">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select border border-secondary" wire:model="statusFilter">
                        <option value="all">Todos</option>
                        <option value="active">Em atividade</option>
                        <option value="delivered">Entregues</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="iat-filter-card">
                    <label class="form-label small text-muted">Empreiteira(s)</label>
                    <select class="form-select border border-secondary iat-company-select" wire:model="companyIds" multiple
                        size="6">
                        @foreach ($companies as $company)
                            <option value="{{ data_get($company, 'id') }}">{{ data_get($company, 'name') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="iat-filter-card">
                    <label class="form-label small text-muted">Busca</label>
                    <div class="input-group">
                        <input type="text" class="form-control border border-secondary" wire:model.debounce.500ms="search"
                            placeholder="Nota, empresa ou status">
                        <select class="form-select border border-secondary" wire:model="perPage" style="max-width: 120px;">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl">
                <div class="iat-summary-card">
                    <div class="label">Abertas no período</div>
                    <div class="value">{{ $summary['opened_count'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div class="iat-summary-card">
                    <div class="label">Média de aberturas/dia</div>
                    <div class="value">{{ number_format($summary['opened_daily_avg'], 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div class="iat-summary-card">
                    <div class="label">Média de entregas/dia</div>
                    <div class="value">{{ number_format($summary['delivered_daily_avg'], 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div class="iat-summary-card">
                    <div class="label">Tempo médio de entrega</div>
                    <div class="value">{{ $summary['delivered_avg_label'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div class="iat-summary-card">
                    <div class="label">Em execução agora</div>
                    <div class="value">{{ $summary['in_progress_now_count'] }}</div>
                </div>
            </div>
        </div>

        @if ($rows->count())
            <div class="iat-pagination">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <small>
                            Exibindo {{ $rows->firstItem() }} até {{ $rows->lastItem() }} de {{ $rows->total() }}
                        </small>
                    </div>
                </div>
            </div>
        @endif

        <div class="iat-table-card">
            @if (!$rows->count())
                <div class="card-body">
                    <h5 class="text-center text-muted mb-0">Nenhum dado encontrado para os filtros informados.</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nota</th>
                                <th>Empreiteira</th>
                                <th>Foi tácita?</th>
                                <th>Status</th>
                                <th>Quando pediu</th>
                                <th>Quando entregou</th>
                                <th>Tempo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="ads-requested-{{ $row['id'] }}">
                                    <td class="fw-semibold">#{{ $row['id'] }}</td>
                                    <td class="fw-semibold">{{ $row['note_number'] }}</td>
                                    <td>{{ $row['company_name'] }}</td>
                                    <td>
                                        @if ($row['is_tacit'])
                                            <span class="badge bg-warning text-dark">Sim</span>
                                        @else
                                            <span class="badge bg-secondary">Não</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span>
                                    </td>
                                    <td>{{ $row['requested_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $row['delivered_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="fw-semibold">{{ $row['elapsed_label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
