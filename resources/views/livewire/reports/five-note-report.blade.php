@push('css')
    <style>
        .d5r-page {
            --d5r-bg: #f6f7fb;
            --d5r-surface: #ffffff;
            --d5r-ink: #1f2933;
            --d5r-muted: #6b7280;
            --d5r-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--d5r-bg);
            padding: 1.5rem 0;
        }

        .d5r-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .d5r-filter-card {
            background-color: var(--d5r-surface);
            border: 1px solid var(--d5r-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .d5r-summary-card {
            background: var(--d5r-surface);
            border: 1px solid var(--d5r-border);
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            height: 100%;
        }

        .d5r-summary-card .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--d5r-muted);
            letter-spacing: 0.06em;
        }

        .d5r-summary-card .value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--d5r-ink);
            line-height: 1.25;
        }

        .d5r-table-card {
            background: var(--d5r-surface);
            border: 1px solid var(--d5r-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .d5r-pagination {
            background: #fff;
            border: 1px solid var(--d5r-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
        }

        .d5r-search-wrap {
            position: relative;
        }

        .d5r-search-wrap .form-control {
            padding-right: 2.4rem;
        }

        .d5r-search-btn {
            position: absolute;
            right: 0.4rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
        }
    </style>
@endpush

<div class="d5r-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="d5r-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-1">RELATÓRIO NOTAS D5</h2>
                <div class="text-light opacity-75">Despacho, conclusão, fiscalização, pagamento e passivo das Notas D5
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" wire:click="clearFilters">
                    <i class="ri-filter-off-line me-1"></i> Limpar
                </button>
                <button class="btn btn-light btn-sm text-dark" wire:click="exportReport" wire:loading.attr="disabled"
                    wire:target="exportReport">
                    <span wire:loading.remove wire:target="exportReport">
                        <i class="ri-file-excel-2-line me-1"></i> Exportar
                    </span>
                    <span wire:loading wire:target="exportReport">Gerando...</span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="d5r-filter-card">
                    <label class="form-label small text-muted">Busca Nota/D5</label>
                    <div class="row g-2">
                        <div class="col-12 col-lg-8">
                            <div class="d5r-search-wrap">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.debounce.500ms="search" placeholder="Nota ou D5 (ignora filtros)">
                                <button type="button" class="btn btn-outline-secondary btn-sm d5r-search-btn"
                                    data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Busca múltipla">
                                    <i class="ri-list-check-2"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <select class="form-select border border-secondary" wire:model="perPage">
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    @if (count($direct_terms ?? []) > 0)
                        <div class="small mt-1">
                            <span class="badge bg-primary">Busca múltipla ativa: {{ count($direct_terms) }}</span>
                            <button class="btn btn-link btn-sm p-0 ms-1" wire:click="clearBatchSearch">limpar</button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="d5r-filter-card">
                    <label class="form-label small text-muted">Período de despacho</label>
                    <div class="row g-2">
                        <div class="col-12 col-lg-6">
                            <input type="date" class="form-control border border-secondary"
                                wire:model.lazy="dispatch_from" max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-12 col-lg-6">
                            <input type="date" class="form-control border border-secondary"
                                wire:model.lazy="dispatch_to" max="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="d5r-filter-card">
                    <label class="form-label small text-muted">Empresa parceira</label>
                    <select class="form-select border border-secondary" wire:model="company_id">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="d5r-filter-card">
                    <label class="form-label small text-muted">Tipo</label>
                    <select class="form-select border border-secondary mb-2" wire:model="passive_mode">
                        <option value="both">Meta + Passivo</option>
                        <option value="meta">Meta</option>
                        <option value="passive">Passivo</option>
                    </select>
                    <label class="form-label small text-muted">Situação</label>
                    <select class="form-select border border-secondary" wire:model="open_only">
                        <option value="0">Todos</option>
                        <option value="1">Somente em abertos</option>
                    </select>
                    <small class="text-muted d-block mt-1">Em abertos ignora o período de despacho.</small>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="d5r-summary-card">
                    <div class="label">Registros filtrados</div>
                    <div class="value">{{ $summary['total'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d5r-summary-card">
                    <div class="label">Passivos</div>
                    <div class="value">{{ $summary['passive'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d5r-summary-card">
                    <div class="label">Concluídas pelo parceiro</div>
                    <div class="value">{{ $summary['completed'] }}</div>
                </div>
            </div>
        </div>

        @if ($rows->count())
            <div class="d5r-pagination">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <small>Exibindo {{ $rows->firstItem() }} até {{ $rows->lastItem() }} de {{ $rows->total() }}</small>
                    </div>
                </div>
            </div>
        @endif

        <div class="d5r-table-card">
            @if (!$rows->count())
                <div class="card-body">
                    <h5 class="text-center text-muted mb-0">Nenhum dado encontrado para os filtros informados.</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Nota D5</th>
                                <th>Nota/OV</th>
                                <th>Empresa parceira</th>
                                <th>Despachada em</th>
                                <th>Concluída parceiro</th>
                                <th>Fiscalizada em</th>
                                <th>Paga em</th>
                                <th>Fiscalizada por</th>
                                <th>Paga por</th>
                                <th>Passivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['nota_d5'] }}</td>
                                    <td>{{ $row['nota_ov'] }}</td>
                                    <td>{{ $row['empresa_parceira'] }}</td>
                                    <td>{{ $row['dispatch_at'] }}</td>
                                    <td>{{ $row['completed_at'] }}</td>
                                    <td>{{ $row['supervisioned_at'] }}</td>
                                    <td>{{ $row['payed_at'] }}</td>
                                    <td>{{ $row['fiscalizado_por'] }}</td>
                                    <td>{{ $row['pago_por'] }}</td>
                                    <td>
                                        @if ($row['passivo'] === 'SIM')
                                            <span class="badge bg-warning text-dark">SIM</span>
                                        @else
                                            <span class="badge bg-success">NAO</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="multiSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Busca múltipla por Nota/D5</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" rows="8" wire:model.defer="batch_search"
                        placeholder="Informe notas ou D5 separados por vírgula, espaço ou quebra de linha"></textarea>
                    <small class="text-muted d-block mt-2">Essa busca ignora os demais filtros.</small>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="applyBatchSearch" data-bs-dismiss="modal">
                        Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
