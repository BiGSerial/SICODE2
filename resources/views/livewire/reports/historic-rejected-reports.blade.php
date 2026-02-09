@push('css')
    <style>
        .chart-card {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .chart-card-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, .1), rgba(118, 75, 162, .1));
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, .05);
        }

        .chart-card-title {
            font-weight: 600;
            margin: 0;
        }

        .filters-container .filter-label {
            font-size: .85rem;
            font-weight: 600;
            color: #6c757d;
        }

        .filters-container input {
            width: 100%;
        }
    </style>
@endpush

<div>
    <x-show-loading />

    {{-- Filtros --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="ri-filter-3-line me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 filters-container">
                <!-- Date Range Controls -->
                <div class="col-lg-5">
                    <div class="card border-0 bg-light rounded-3">
                        <div class="card-body py-3">
                            <h6 class="mb-3 text-muted"><i class="ri-calendar-line me-1"></i>Período</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="filter-label">Início</label>
                                    <input type="date" max="{{ date('Y-m-d') }}" wire:model.lazy="dt_in"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="filter-label">Fim</label>
                                    <input type="date" max="{{ date('Y-m-d') }}" wire:model.lazy="dt_out"
                                        class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Companies Filter -->
                <div class="col-lg-4">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-building-2-line me-1"></i>Empreiteira(s)</h6>
                            <select class="form-select form-select-sm" wire:model="companyIds" multiple size="5">
                                @foreach ($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text small mt-2">
                                <i class="ri-information-line text-muted me-1"></i>
                                Nenhuma seleção = todas empreiteiras | <span class="fw-medium">Ctrl+clique</span> para
                                selecionar múltiplas
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Actions -->
                <div class="col-lg-3">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-search-line me-1"></i>Pesquisa</h6>
                            <input type="text" wire:model.debounce.500ms="searchNote"
                                class="form-control form-control-sm mb-3" placeholder="Número da nota">
                            <input type="text" wire:model.debounce.500ms="reason"
                                class="form-control form-control-sm mb-3" placeholder="Motivo ou observação">

                            <div class="d-grid gap-2">
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center"
                                    wire:click="$refresh">
                                    <i class="ri-refresh-line me-1"></i> Atualizar
                                </button>
                                <button type="button"
                                    class="btn btn-sm btn-primary d-flex align-items-center justify-content-center"
                                    wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                                    <span wire:loading.remove wire:target="exportToExcel">
                                        <i class="ri-download-line me-1"></i> Exportar Excel
                                    </span>
                                    <span wire:loading wire:target="exportToExcel">
                                        <i class="ri-loader-4-line animate-spin me-1"></i> Gerando...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Gráficos --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-bar-chart-line me-2"></i>Rejeições por Mês</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:320px">
                        <canvas id="chartMonthly"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-building-2-line me-2"></i>Volumetria por Empreiteira</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:320px">
                        <canvas id="chartCompany"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-pie-chart-2-line me-2"></i>Distribuição por Categoria</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:360px">
                        <canvas id="chartCategory"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="ri-list-check-2 me-1"></i>Retornos</strong>
            <button class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Exportar Lista para Excel"
                wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <i class="ri-file-excel-line"></i>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px">Abertura</th>
                        <th style="width: 140px">Nota</th>
                        <th>Empreiteira</th>
                        <th style="width: 220px">Categoria</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $rw)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($rw->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $rw->Workreport->Note->note ?? '—' }}</td>
                            <td>{{ $rw->Workreport->Company->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $rw->category ?? 'Sem categoria' }}</span>
                            </td>
                            <td>{{ $rw->text_obs }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="ri-emotion-sad-line me-1"></i> Nenhum retorno encontrado no período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($list->hasPages())
            <div class="card-footer">
                {{ $list->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
    {{-- Chart.js (se já tiver global, remova esse include) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        (function() {
            window.sicodeCharts = window.sicodeCharts || {};

            // 🔸 injeta datalabels + tooltip com %
            function enhanceDonutWithPercents(cfg) {
                // garante estruturas
                cfg.options = cfg.options || {};
                cfg.options.plugins = cfg.options.plugins || {};
                cfg.options.plugins.legend = cfg.options.plugins.legend || {
                    position: 'right'
                };

                // tooltip com %
                cfg.options.plugins.tooltip = cfg.options.plugins.tooltip || {};
                cfg.options.plugins.tooltip.callbacks = cfg.options.plugins.tooltip.callbacks || {};
                cfg.options.plugins.tooltip.callbacks.label = function(context) {
                    const dataset = context.dataset;
                    const total = dataset.data.reduce((a, b) => a + b, 0) || 1;
                    const value = context.parsed;
                    const pct = ((value / total) * 100).toFixed(1);
                    const label = (context.label ?? '').toString();
                    return `${label}: ${value} (${pct}%)`;
                };

                // datalabels com %
                cfg.options.plugins.datalabels = {
                    formatter: function(value, ctx) {
                        const data = ctx.chart.data.datasets[0].data || [];
                        const total = data.reduce((a, b) => a + b, 0) || 1;
                        const pct = (value / total) * 100;
                        // só mostra se for >= 3% (evita poluição visual)
                        return pct >= 1 ? pct.toFixed(1) + '%' : '';
                    },
                    anchor: 'end',
                    align: 'end',
                    offset: 4,
                    borderRadius: 4,
                    backgroundColor: 'rgba(0,0,0,0.06)',
                    padding: 4,
                    color: '#000',
                    font: {
                        weight: '600',
                        size: 10
                    }
                };

                // registra o plugin
                if (!Chart.registry.plugins.get('datalabels')) {
                    Chart.register(ChartDataLabels);
                }
                return cfg;
            }

            const ctxMonthly = document.getElementById('chartMonthly').getContext('2d');
            const ctxCompany = document.getElementById('chartCompany').getContext('2d');
            const ctxCategory = document.getElementById('chartCategory').getContext('2d');

            function ensureOrCreate(id, ctx, cfg) {
                if (window.sicodeCharts[id]) {
                    window.sicodeCharts[id].config.type = cfg.type;
                    window.sicodeCharts[id].config.data = cfg.data;
                    window.sicodeCharts[id].config.options = cfg.options || {};
                    window.sicodeCharts[id].update();
                } else {
                    window.sicodeCharts[id] = new Chart(ctx, cfg);
                }
            }

            // primeira render
            let firstMonthly = @json($monthly);
            let firstCompany = @json($company);
            let firstCategory = @json($category);

            // aplica as porcentagens só no donut
            firstCategory = enhanceDonutWithPercents(firstCategory);

            ensureOrCreate('monthly', ctxMonthly, firstMonthly);
            ensureOrCreate('company', ctxCompany, firstCompany);
            ensureOrCreate('category', ctxCategory, firstCategory);

            // atualizações via Livewire
            window.addEventListener('chart-monthly-openings', (e) => {
                ensureOrCreate('monthly', ctxMonthly, e.detail);
            });
            window.addEventListener('chart-by-company', (e) => {
                ensureOrCreate('company', ctxCompany, e.detail);
            });
            window.addEventListener('chart-by-category', (e) => {
                // ✅ sempre garantir que o donut continue com %
                const cfg = enhanceDonutWithPercents(e.detail);
                ensureOrCreate('category', ctxCategory, cfg);
            });
        })();
    </script>
@endpush
