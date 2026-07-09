@php
    use Illuminate\Support\Carbon;
@endphp

<div class="dispatch-dashboard">
    <x-show-loading />

    <style>
        .dispatch-dashboard {
            --dash-ink: #102033;
            --dash-muted: #64748b;
            --dash-line: #dbe4ea;
            --dash-teal: #155f67;
            --dash-green: #28f66a;
            --dash-blue: #263cc8;
            --dash-soft: #f7fafc;
        }

        .dispatch-dashboard .dash-hero {
            background: linear-gradient(120deg, #102033 0%, #155f67 58%, #0f8a77 100%);
            border-radius: 8px;
            color: #fff;
            padding: 22px 24px;
            box-shadow: 0 18px 40px rgba(16, 32, 51, .16);
        }

        .dispatch-dashboard .dash-title {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: 0;
            margin: 0;
        }

        .dispatch-dashboard .dash-subtitle {
            color: rgba(255, 255, 255, .78);
            font-size: .92rem;
            margin-top: 4px;
        }

        .dispatch-dashboard .filter-panel,
        .dispatch-dashboard .metric-card,
        .dispatch-dashboard .chart-card,
        .dispatch-dashboard .table-card {
            background: #fff;
            border: 1px solid var(--dash-line);
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(16, 32, 51, .08);
        }

        .dispatch-dashboard .filter-panel {
            padding: 16px;
        }

        .dispatch-dashboard .metric-card {
            padding: 16px 18px;
            min-height: 112px;
        }

        .dispatch-dashboard .metric-label {
            color: var(--dash-muted);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .dispatch-dashboard .metric-value {
            color: var(--dash-ink);
            font-size: 2rem;
            font-weight: 850;
            line-height: 1.1;
            margin-top: 8px;
        }

        .dispatch-dashboard .metric-note {
            color: var(--dash-muted);
            font-size: .82rem;
            margin-top: 6px;
        }

        .dispatch-dashboard .chart-card {
            padding: 0;
            overflow: hidden;
        }

        .dispatch-dashboard .chart-head {
            align-items: center;
            border-bottom: 1px solid var(--dash-line);
            display: flex;
            justify-content: space-between;
            min-height: 58px;
            padding: 14px 18px;
        }

        .dispatch-dashboard .chart-title {
            color: var(--dash-ink);
            font-size: 1rem;
            font-weight: 800;
            margin: 0;
        }

        .dispatch-dashboard .chart-subtitle {
            color: var(--dash-muted);
            font-size: .8rem;
        }

        .dispatch-dashboard .chart-body {
            height: 360px;
            padding: 18px;
        }

        .dispatch-dashboard .chart-body-sm {
            height: 360px;
        }

        .dispatch-dashboard .table-card {
            overflow: hidden;
        }

        .dispatch-dashboard .table-card .table {
            margin-bottom: 0;
        }

        .dispatch-dashboard .table-card thead th {
            background: #102033;
            color: #fff;
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dispatch-dashboard .badge-stack {
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            padding: 5px 9px;
        }

        .dispatch-dashboard .badge-assigned {
            background: rgba(38, 60, 200, .1);
            color: var(--dash-blue);
        }

        .dispatch-dashboard .badge-open {
            background: rgba(40, 246, 106, .14);
            color: #087233;
        }

        .dispatch-dashboard .form-label {
            color: var(--dash-muted);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .dispatch-dashboard .form-select,
        .dispatch-dashboard .form-control {
            border-color: #b8c8d0;
            border-radius: 6px;
        }
    </style>

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Dashboard {{ mb_strtoupper($service->service) }}</h2>
                <div class="dash-subtitle">
                    Pilha operacional por tempo no status, prazo real e atribuição atual.
                </div>
            </div>
            <div class="text-end small opacity-75">
                Atualizado em {{ $lastUpdatedAt->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div class="filter-panel mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3 col-xl-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" wire:model="noteType">
                    <option value="2">OV</option>
                    <option value="1">Nota</option>
                    <option value="">Notas e OVs</option>
                </select>
            </div>
            <div class="col-12 col-md-9 col-xl-10">
                <div class="text-muted small">
                    Tempo no status usa <strong>dt_status</strong>. Prazo real usa <strong>days_left</strong>
                    e separa os atribuídos por empresa.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Total na pilha</div>
                <div class="metric-value">{{ number_format($summary['total'], 0, ',', '.') }}</div>
                <div class="metric-note">{{ $noteType === '2' ? 'Somente OVs' : ($noteType === '1' ? 'Somente notas' : 'Notas e OVs') }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Em atribuição</div>
                <div class="metric-value">{{ number_format($summary['assigned'], 0, ',', '.') }}</div>
                <div class="metric-note">Productions abertas no serviço</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Sem atribuição</div>
                <div class="metric-value">{{ number_format($summary['unassigned'], 0, ',', '.') }}</div>
                <div class="metric-note">Pilha disponível para despacho</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card">
                <div class="metric-label">Prazo vencido</div>
                <div class="metric-value">{{ number_format($summary['overdue'], 0, ',', '.') }}</div>
                <div class="metric-note">Média no status: {{ number_format($summary['avg_status_days'], 1, ',', '.') }} dias</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Tempo no Status</h3>
                        <div class="chart-subtitle">Distribuição por dias desde dt_status</div>
                    </div>
                    <i class="ri-bar-chart-grouped-line text-secondary fs-4"></i>
                </div>
                <div class="chart-body" wire:ignore>
                    <x-grafico.apex :chart="$statusAgeChart" :chartId="$statusChartId" :showDataLabels="true" class="w-100 h-100" />
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Prazo Real</h3>
                        <div class="chart-subtitle">Distribuição por days_left e empresa atribuída</div>
                    </div>
                    <i class="ri-timer-line text-secondary fs-4"></i>
                </div>
                <div class="chart-body" wire:ignore>
                    <x-grafico.apex :chart="$deadlineChart" :chartId="$deadlineChartId" :showDataLabels="true" class="w-100 h-100" />
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="chart-card h-100">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Qtd por Colaborador</h3>
                        <div class="chart-subtitle">Atribuições abertas por tempo com o colaborador</div>
                    </div>
                    <i class="ri-user-3-line text-secondary fs-4"></i>
                </div>
                <div class="chart-body chart-body-sm" wire:ignore>
                    <x-grafico.apex :chart="$userChart" :chartId="$userChartId" :showDataLabels="true" class="w-100 h-100" />
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="table-card h-100">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Pilha Crítica</h3>
                        <div class="chart-subtitle">Ordenada por menor prazo real</div>
                    </div>
                    <i class="ri-table-line text-secondary fs-4"></i>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Nota/OV</th>
                                <th>Tipo</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Prazo</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($criticalItems as $item)
                                <tr>
                                    <td class="fw-bold">{{ $item->note }}</td>
                                    <td>{{ (int) $item->type_note === 2 ? 'OV' : 'Nota' }}</td>
                                    <td>{{ $item->rubrica ?: '-' }}</td>
                                    <td>{{ $item->lexp ?: '-' }}</td>
                                    <td class="text-center">
                                        @if ((int) $item->assigned === 1)
                                            <span class="badge-stack badge-assigned">Em atribuição</span>
                                        @else
                                            <span class="badge-stack badge-open">Na pilha</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold @if (($item->days_left ?? 0) < 0) text-danger @endif">
                                        {{ $item->days_left ?? '-' }}
                                    </td>
                                    <td>{{ $item->assigned_user ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Sem registros na pilha para o filtro atual.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
