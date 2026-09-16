@push('css')
    <style>
        .gsr-page {
            background: #f6f7fb;
            padding: 1.5rem 0;
        }

        .gsr-header {
            background: #0f172a;
            color: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .gsr-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .gsr-panel-pad {
            padding: 1rem;
        }

        .gsr-kpi {
            min-height: 82px;
        }

        .gsr-kpi .label {
            color: #6b7280;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .gsr-kpi .value {
            color: #111827;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .gsr-table th {
            white-space: nowrap;
        }

        .gsr-source {
            color: #6b7280;
            font-size: 0.8rem;
        }
    </style>
@endpush

<div class="gsr-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="gsr-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-1">RELATÓRIO GERAL SICODE</h2>
                <div class="text-light opacity-75">
                    Consolidação operacional por atividade, sem separação ES/SP.
                </div>
            </div>
            <div class="text-lg-end">
                <div class="small text-light opacity-75">Aplicação origem</div>
                <div class="fw-bold">{{ $report['application'] }}</div>
                <button class="btn btn-light btn-sm text-dark mt-2" wire:click="exportReport" wire:loading.attr="disabled"
                    wire:target="exportReport">
                    <span wire:loading.remove wire:target="exportReport">
                        <i class="ri-file-excel-2-line me-1"></i> Exportar Excel
                    </span>
                    <span wire:loading wire:target="exportReport">Gerando...</span>
                </button>
            </div>
        </div>

        <div class="gsr-panel gsr-panel-pad mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Data inicial</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="date_from">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Data final</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="date_to">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted">Empresa parceira</label>
                    <select class="form-select border border-secondary" wire:model="company_id">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-outline-secondary" wire:click="clearFilters">
                        <i class="ri-filter-off-line me-1"></i> Limpar
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="gsr-panel gsr-panel-pad gsr-kpi">
                    <div class="label">Quantidade total</div>
                    <div class="value">{{ number_format($report['totals']['quantity'], 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="gsr-panel gsr-panel-pad gsr-kpi">
                    <div class="label">Valor total</div>
                    <div class="value">R$ {{ number_format($report['totals']['value'], 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="gsr-panel gsr-panel-pad gsr-kpi">
                    <div class="label">Gerado em</div>
                    <div class="value">{{ $report['generated_at']->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="gsr-panel mb-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 gsr-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Descrição</th>
                            <th class="text-end">Quantidade</th>
                            <th class="text-end">Valor</th>
                            <th>Aplicação origem</th>
                            <th>Fonte</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row['description'] }}</div>
                                    <div class="gsr-source">{{ $row['resolution'] }}</div>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row['quantity'], 0, ',', '.') }}</td>
                                <td class="text-end fw-semibold">R$ {{ number_format($row['value'], 2, ',', '.') }}</td>
                                <td>{{ $row['application'] }}</td>
                                <td><span class="badge text-bg-light border">{{ $row['source'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th>Total</th>
                            <th class="text-end">{{ number_format($report['totals']['quantity'], 0, ',', '.') }}</th>
                            <th class="text-end">R$ {{ number_format($report['totals']['value'], 2, ',', '.') }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="gsr-panel gsr-panel-pad">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h5 class="mb-0">Qualidade da medição/pagamento</h5>
                <span class="badge text-bg-info">associações novas podem estar incompletas</span>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Parciais pagas</div>
                    <div class="fw-bold">{{ number_format($report['quality']['partial_quantity'], 0, ',', '.') }}</div>
                    <div class="small">R$ {{ number_format($report['quality']['partial_value'], 2, ',', '.') }}</div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Finais informados</div>
                    <div class="fw-bold">{{ number_format($report['quality']['final_quantity'], 0, ',', '.') }}</div>
                    <div class="small">R$ {{ number_format($report['quality']['final_value'], 2, ',', '.') }}</div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Finais com associação nova</div>
                    <div class="fw-bold">{{ number_format($report['quality']['final_with_new_association'], 0, ',', '.') }}</div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Finais por fallback / sem valor</div>
                    <div class="fw-bold">
                        {{ number_format($report['quality']['final_with_fallback'], 0, ',', '.') }}
                        /
                        {{ number_format($report['quality']['final_without_value'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
