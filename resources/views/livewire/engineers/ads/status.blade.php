@push('css')
    <style>
        .ads-status-page {
            --ads-bg: #f6f7fb;
            --ads-surface: #ffffff;
            --ads-ink: #1f2933;
            --ads-muted: #6b7280;
            --ads-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--ads-bg);
            padding: 1.5rem 0;
        }

        .ads-status-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .ads-status-filter-card,
        .ads-status-summary-card {
            background: var(--ads-surface);
            border: 1px solid var(--ads-border);
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            height: 100%;
        }

        .ads-status-filter-card .form-control,
        .ads-status-filter-card .form-select {
            min-height: 44px;
        }

        .ads-status-company-select {
            min-height: 150px;
        }

        .ads-status-summary-card .label {
            font-size: 0.75rem;
            color: var(--ads-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .ads-status-summary-card .value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ads-ink);
            line-height: 1.2;
        }

        .ads-status-table-card {
            background: var(--ads-surface);
            border: 1px solid var(--ads-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .ads-status-summary-card.filterable {
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }

        .ads-status-summary-card.filterable:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(15, 23, 42, 0.12);
        }

        .ads-status-summary-card.active-filter {
            border-color: #0f766e;
            box-shadow: 0 0 0 1px rgba(15, 118, 110, 0.15), 0 14px 26px rgba(15, 23, 42, 0.14);
        }
    </style>
@endpush

<div class="ads-status-page">
    <x-show-loading />
    <div class="container-fluid">
            <div class="ads-status-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h3 class="mb-1">SITUAÇÃO DE ADS</h3>
                <div class="opacity-75">Monitoramento de prazo tácito, vencimento e passivo</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm text-dark" wire:click="exportReport" wire:loading.attr="disabled"
                    wire:target="exportReport">
                    <span wire:loading.remove wire:target="exportReport">
                        <i class="ri-file-excel-2-line me-1"></i> Exportar
                    </span>
                    <span wire:loading wire:target="exportReport">Gerando...</span>
                </button>
                <button class="btn btn-outline-light btn-sm" wire:click="clearFilters">
                    <i class="ri-filter-off-line me-1"></i> Limpar
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-2">
                <div class="ads-status-filter-card">
                    <label class="form-label small text-muted">Data inicial (informe)</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="date_in">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="ads-status-filter-card">
                    <label class="form-label small text-muted">Data final (informe)</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="date_out">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="ads-status-filter-card">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select border border-secondary" wire:model="statusFilter">
                        <option value="disabled">Selecione (padrão desabilitado)</option>
                        <option value="passivo">Passivo</option>
                        <option value="atual">Atual</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="ads-status-filter-card">
                    <label class="form-label small text-muted">Busca / página</label>
                    <div class="input-group">
                        <input type="text" class="form-control border border-secondary" wire:model.debounce.500ms="search"
                            placeholder="Nota, ordem ou empreiteira">
                        <select class="form-select border border-secondary" wire:model="perPage" style="max-width: 120px;">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="ads-status-filter-card">
                    <label class="form-label small text-muted">Empreiteira(s)</label>
                    <select class="form-select border border-secondary ads-status-company-select" wire:model="companyIds"
                        multiple size="6">
                        @foreach ($companies as $company)
                            <option value="{{ data_get($company, 'id') }}">{{ data_get($company, 'name') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="ads-status-filter-card">
                    <label class="form-label small text-muted">Empresas com mais ADS em atraso (top 5)</label>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Empreiteira</th>
                                    <th class="text-end">Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary['top_companies_overdue'] as $company)
                                    <tr>
                                        <td>{{ $company['name'] }}</td>
                                        <td class="text-end fw-semibold">{{ $company['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">Sem atraso no período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl">
                <div class="ads-status-summary-card"><div class="label">Total de informes</div><div class="value">{{ $summary['total'] }}</div></div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div class="ads-status-summary-card"><div class="label">Passivo</div><div class="value">{{ $summary['passivo'] }}</div></div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div @class(['ads-status-summary-card filterable', 'active-filter' => $detailStatusFilter === 'a_informar']) wire:click="setDetailStatusFilter('a_informar')">
                    <div class="label">A informar</div>
                    <div class="value">{{ $summary['a_informar'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div @class(['ads-status-summary-card filterable', 'active-filter' => $detailStatusFilter === 'no_prazo']) wire:click="setDetailStatusFilter('no_prazo')">
                    <div class="label">No prazo</div>
                    <div class="value">{{ $summary['no_prazo'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div @class(['ads-status-summary-card filterable', 'active-filter' => $detailStatusFilter === 'vencendo_3_dias']) wire:click="setDetailStatusFilter('vencendo_3_dias')">
                    <div class="label">Vencendo (3 dias)</div>
                    <div class="value">{{ $summary['vencendo_3_dias'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div @class(['ads-status-summary-card filterable', 'active-filter' => $detailStatusFilter === 'vencida_sem_entrega']) wire:click="setDetailStatusFilter('vencida_sem_entrega')">
                    <div class="label">Vencida sem entrega</div>
                    <div class="value">{{ $summary['vencida_sem_entrega'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div @class(['ads-status-summary-card filterable', 'active-filter' => $detailStatusFilter === 'com_entrega']) wire:click="setDetailStatusFilter('com_entrega')">
                    <div class="label">Com entrega</div>
                    <div class="value">{{ $summary['com_entrega'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div @class(['ads-status-summary-card filterable', 'active-filter' => $detailStatusFilter === 'entregue_atraso']) wire:click="setDetailStatusFilter('entregue_atraso')">
                    <div class="label">Entregue em atraso</div>
                    <div class="value">{{ $summary['entregue_atraso'] }}</div>
                </div>
            </div>
        </div>

        <div class="ads-status-table-card">
            @if (!$rows->count())
                <div class="card-body">
                    <h5 class="text-center text-muted mb-0">Nenhum registro para os filtros selecionados.</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nota</th>
                                <th>Empreiteira</th>
                                <th>Informe</th>
                                <th>Vencimento tácito</th>
                                <th>Entrega ADS</th>
                                <th>Status</th>
                                <th>Tempo (dias)</th>
                                <th>Base</th>
                                <th>Multa diária</th>
                                <th>Multa prevista</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="ads-status-{{ $row['work_report_id'] }}">
                                    <td class="fw-semibold">#{{ $row['work_report_id'] }}</td>
                                    <td class="fw-semibold">{{ $row['note_number'] }}</td>
                                    <td>{{ $row['company_name'] }}</td>
                                    <td>{{ $row['informed_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $row['due_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $row['delivered_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td><span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                                    <td class="text-center">
                                        @if (in_array($row['status_code'], ['no_prazo', 'vencendo_3_dias']) && $row['days_to_due'] !== null)
                                            {{ $row['days_to_due'] }} para vencer
                                        @else
                                            {{ $row['delay_days'] }}
                                        @endif
                                    </td>
                                    <td>
                                        @if (isset($rowFineData[$row['work_report_id']]))
                                            R$ {{ number_format($rowFineData[$row['work_report_id']]['base_amount'], 2, ',', '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if (isset($rowFineData[$row['work_report_id']]))
                                            R$ {{ number_format($rowFineData[$row['work_report_id']]['daily_fine_amount'], 2, ',', '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        @if (isset($rowFineData[$row['work_report_id']]))
                                            R$ {{ number_format($rowFineData[$row['work_report_id']]['total_fine_amount'], 2, ',', '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="refreshFine({{ $row['work_report_id'] }})"
                                            wire:loading.attr="disabled" wire:target="refreshFine({{ $row['work_report_id'] }})">
                                            <span wire:loading.remove wire:target="refreshFine({{ $row['work_report_id'] }})">Atualizar</span>
                                            <span wire:loading wire:target="refreshFine({{ $row['work_report_id'] }})">...</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer py-2">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-6">{{ $rows->onEachSide(1)->links() }}</div>
                        <div class="col-12 col-lg-6 text-lg-end">
                            Exibindo {{ $rows->firstItem() }} até {{ $rows->lastItem() }} de {{ $rows->total() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
