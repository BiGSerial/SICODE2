<div class="pd-page">
    <x-show-loading />

    @push('css')
        <style>
            .pd-page { padding: 1rem 0; }
            .pd-header { background: linear-gradient(120deg, #0f172a, #0f766e 70%); color: #f8fafc; border-radius: 1rem; padding: 1.4rem 2rem; margin-bottom: 1.2rem; }
            .pd-header h1 { font-size: 1.6rem; font-weight: 700; margin: 0; }
            .pd-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, .08); margin-bottom: 1.2rem; }
            .pd-card-head { padding: .9rem 1.2rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
            .pd-card-body { padding: 1rem 1.2rem; }
            .pd-chart { height: 340px; }
            .pd-kpi-label { text-transform: uppercase; font-size: .72rem; letter-spacing: .06em; color: #6c757d; font-weight: 600; }
            .pd-kpi-value { font-size: 1.7rem; font-weight: 700; color: #1f2937; }
            .pd-table th, .pd-table td { text-align: center; white-space: nowrap; }
            @media print {
                #sidebar, #header, .no-print, .breadcrumb { display: none !important; }
                #main { margin: 0 !important; padding: 0 !important; }
                .pd-card { box-shadow: none; border: 1px solid #d1d5db; break-inside: avoid; }
                .pd-header { background: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                canvas { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            }
        </style>
    @endpush

    <div class="pd-header">
        <h1>Prazo de Projetos</h1>
        <div class="small opacity-75">
            Aderência de levantamento (status 27) e desenho (status 28) · fases concluídas de
            {{ \Carbon\Carbon::parse($dt_in)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dt_out)->format('d/m/Y') }}
        </div>
    </div>

    <div class="pd-card no-print">
        <div class="pd-card-body row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Término da fase — de</label>
                <input type="date" class="form-control" wire:model.lazy="dt_in" min="2026-01-01">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">até</label>
                <input type="date" class="form-control" wire:model.lazy="dt_out" min="{{ $dt_in }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Empresa executora</label>
                <select class="form-select" wire:model="companyId">
                    <option value="">Todas</option>
                    @foreach ($this->companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2 justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Imprimir / PDF
                </button>
                <button type="button" class="btn btn-success" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </button>
            </div>
        </div>
    </div>

    @foreach ($phases as $phase)
        <div class="pd-card">
            <div class="pd-card-head">
                <h5 class="mb-0">{{ $phase['label'] }} <small class="text-muted">· prazo de {{ $phase['limit'] }} dias</small></h5>
            </div>
            <div class="pd-card-body">
                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <div class="pd-kpi-label">OVs concluídas</div>
                        <div class="pd-kpi-value">{{ number_format($phase['summary']['total'], 0, ',', '.') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="pd-kpi-label">Aderência ao prazo</div>
                        <div class="pd-kpi-value">{{ number_format($phase['summary']['adherence'], 1, ',', '.') }}%</div>
                    </div>
                    <div class="col-4">
                        <div class="pd-kpi-label">Média de dias</div>
                        <div class="pd-kpi-value">{{ number_format($phase['summary']['avg_days'], 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="pd-chart" wire:ignore>
                    <x-grafico.apex :chart="$phase['chart']" chartId="pd_{{ $phase['phase'] }}" class="w-100" />
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped pd-table mb-0">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Total</th>
                                @foreach ($this->bandLabels($phase['phase']) as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th>% aderência</th>
                                <th>Média (dias)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($phase['months'] as $m)
                                <tr>
                                    <td>{{ $this->monthLabel($m['month']) }}</td>
                                    <td>{{ $m['total'] }}</td>
                                    @foreach ($m['bands'] as $band)
                                        <td>{{ $band }}</td>
                                    @endforeach
                                    <td class="fw-bold">{{ number_format($m['adherence'], 1, ',', '.') }}%</td>
                                    <td>{{ number_format($m['avg_days'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">Sem fases concluídas no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
