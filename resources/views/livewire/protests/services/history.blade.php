@push('css')
    <style>
        .protest-page {
            --pp-bg: #f6f7fb;
            --pp-surface: #ffffff;
            --pp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--pp-bg);
            padding: 1.5rem 0;
        }

        .protest-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .protest-filter-shell {
            background: var(--pp-surface);
            border: 1px solid var(--pp-border);
            border-radius: 1rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .histogram-card-body {
            display: flex;
            flex-direction: column;
        }

        .histogram-chart-wrap {
            position: relative;
            width: 100%;
            height: clamp(260px, 34vh, 420px);
            max-height: 420px;
            overflow: hidden;
        }

        .histogram-chart-wrap canvas {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
@endpush

<div class="protest-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="protest-header">
            <h4 class="mb-0">Histórico de Atividades do Time</h4>
            <small class="text-white-50">Reclamações concluídas com filtros por período</small>
        </div>

        <div class="protest-filter-shell">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="form-floating">
                        <select wire:model="perPage" id="perPage" class="form-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="perPage">Registros por página</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-floating">
                        <input wire:model.debounce.400ms="search" type="text" id="search" class="form-control"
                            placeholder="Digite para buscar...">
                        <label for="search">Buscar por nota ou observação</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-floating">
                        <input wire:model="month" type="month" id="month" class="form-control"
                            max="{{ date('Y-m') }}">
                        <label for="month">Mês de encerramento</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-floating">
                        <input wire:model="dt_start" type="date" id="dt_start" class="form-control"
                            max="{{ $dt_end ?? date('Y-m-d') }}">
                        <label for="dt_start">Data início</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-floating">
                        <input wire:model="dt_end" type="date" id="dt_end" class="form-control"
                            min="{{ $dt_start }}" max="{{ date('Y-m-d') }}">
                        <label for="dt_end">Data fim</label>
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="clearFilters" type="button" class="btn btn-outline-secondary w-100"
                        title="Limpar filtros">
                        <i class="ri-refresh-line me-1"></i> Limpar
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body histogram-card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                    <div class="fw-semibold">Histograma de Encerramentos no Prazo</div>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" wire:model="histogramSource" style="min-width: 210px;">
                            <option value="measure">Prazo Medida (MEDE)</option>
                            <option value="sla">SLA do Job</option>
                        </select>
                        <select class="form-select form-select-sm" wire:model="histogramYear" style="min-width: 110px;">
                            @forelse (($histogramData['years'] ?? []) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @empty
                                <option value="{{ now()->year }}">{{ now()->year }}</option>
                            @endforelse
                        </select>
                        @if (!empty($histogramData['selectedMonth']))
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearHistogramFilter">
                                Limpar mês
                            </button>
                        @endif
                    </div>
                </div>
                <div id="history-histogram-data" data-payload='@json($histogramData)'></div>
                <div class="histogram-chart-wrap" wire:ignore>
                    <canvas id="historyHistogram"></canvas>
                </div>
                @php
                    $selectedMonth = (int) ($histogramData['selectedMonth'] ?? 0);
                    $series = (array) ($histogramData['series'] ?? []);
                    $onTime = array_values((array) ($series['onTime'] ?? []));
                    $late = array_values((array) ($series['late'] ?? []));
                    $monthLabels = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
                @endphp
                <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                    @foreach ($monthLabels as $monthNumber => $monthLabel)
                        @php
                            $index = $monthNumber - 1;
                            $monthTotal = (int) ($onTime[$index] ?? 0) + (int) ($late[$index] ?? 0);
                            $isActive = $selectedMonth === $monthNumber;
                        @endphp
                        <button type="button" class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                            @disabled($monthTotal <= 0)
                            wire:click="setHistogramBucket({{ $monthNumber }})">
                            {{ $monthLabel }}
                        </button>
                    @endforeach
                </div>
                <small class="text-muted">Clique em uma barra para filtrar a lista por mês/ano previsto.</small>
            </div>
        </div>

        @if ($list->count() > 0)
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">
                    <i class="ri-information-line"></i>
                    Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
                </div>
                <div>
                    {{ $list->links() }}
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header text-bg-primary">
                <h5 class="mb-0">HISTÓRICO DE ATIVIDADES DO TIME</h5>
            </div>
            <div class="table-responsive">
                @if ($list->count() > 0)
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr class="text-center">
                                <th class="col-1">Reclamação</th>
                                <th class="col-1">Tipo</th>
                                <th class="col-1">Medida</th>
                                <th class="col-2">Responsável</th>
                                <th class="col-1">Abertura Recl.</th>
                                <th class="col-1">Prazo Oficial</th>
                                <th class="col-1">Medida Enc.</th>
                                <th class="col-1">Encerrada SICODE</th>
                                <th class="col-1">SLA</th>
                                <th class="col-2">Nota Ref.</th>
                                <th class="col-2">Conclusão</th>
                                <th class="col-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list as $job)
                                @php
                                    $med = $job->medProtest;
                                    $protest = $med?->protest;
                                    $deadline = $this->deadlineFor($job);
                                    $closedAt = $job->closed_at ?? $job->finished_at;
                                    $withinMeasureDeadline = $this->measureFinishedWithinDeadline($job);
                                    $withinJobSla = $this->jobFinishedWithinSla($job);
                                    $noteRef = $protest?->notes?->last() ?? $med?->notes?->last();
                                @endphp
                                <tr class="text-center" wire:key="job-{{ $job->id }}">
                                    <td class="fw-semibold">{{ $protest?->nota ?? '-' }}</td>
                                    <td class="fw-semibold">{{ $protest?->tipoNota ?? '-' }}</td>
                                    <td>{{ $med?->med_id ?? '-' }}</td>
                                    <td>{{ $job->owner?->name ?? '–' }}</td>
                                    <td>{{ optional($protest?->dtAberturaNota)->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ optional($deadline)->format('d/m/Y') ?? 'Sem prazo' }}</td>
                                    <td>{{ optional($med?->dtFimMedida)->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ optional($closedAt)->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        @if (is_null($withinJobSla))
                                            <span class="badge bg-secondary-subtle text-secondary">Sem prazo</span>
                                        @elseif ($withinJobSla)
                                            <span class="badge bg-success-subtle text-success">Dentro do prazo</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Fora do prazo</span>
                                        @endif
                                    </td>
                                    <td>{{ $noteRef?->note ?? 'Sem anotação' }}</td>
                                    <td class="text-start">
                                        {{ \Illuminate\Support\Str::limit($job->close_reason ?? '-', 80) }}
                                        <div class="mt-1">
                                            @if (is_null($withinMeasureDeadline))
                                                <span class="badge bg-secondary-subtle text-secondary">Medida sem prazo</span>
                                            @elseif ($withinMeasureDeadline)
                                                <span class="badge bg-success-subtle text-success">Medida no prazo</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Medida fora do prazo</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($job->med_protest_id)
                                            <a href="{{ route('protests.services.view', $job->id) }}" class="text-primary"
                                                title="Visualizar">
                                                <i class="ri-play-circle-fill fs-4"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info mb-0">
                        Nenhum registro encontrado para os filtros informados.
                    </div>
                @endif
            </div>
        </div>

        @if ($list->count() > 0)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">
                    <i class="ri-information-line"></i>
                    Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
                </div>
                <div>
                    {{ $list->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:load', () => {
            let historyHistogramChart = null;
            let lastSignature = null;

            const buildHistoryHistogram = () => {
                const canvas = document.getElementById('historyHistogram');
                const payloadNode = document.getElementById('history-histogram-data');

                if (!canvas || !payloadNode || typeof Chart === 'undefined') {
                    return;
                }

                const raw = payloadNode.dataset.payload || '{}';
                if (historyHistogramChart && lastSignature === raw) {
                    return;
                }

                let payload;
                try {
                    payload = JSON.parse(raw);
                } catch (e) {
                    return;
                }

                const labels = payload.labels || [];
                const series = payload.series || {};
                const onTimeData = series.onTime || [];
                const lateData = series.late || [];
                const selectedMonth = payload.selectedMonth ? Number(payload.selectedMonth) : null;
                const sourceLabel = payload.source === 'sla' ? 'SLA do Job' : 'Prazo Medida (MEDE)';
                const filterBySelectedMonth = (data) => selectedMonth
                    ? labels.map((_, i) => ((i + 1) === selectedMonth ? Number(data[i] ?? 0) : 0))
                    : data;
                const displayOnTimeData = filterBySelectedMonth(onTimeData);
                const displayLateData = filterBySelectedMonth(lateData);

                const colorize = (base) => labels.map((_, i) => selectedMonth === (i + 1) ? base.replace('0.8', '1') : base);
                const border = labels.map((_, i) => selectedMonth === (i + 1) ? 'rgba(15,23,42,1)' : 'rgba(15,23,42,.45)');

                if (historyHistogramChart) {
                    historyHistogramChart.destroy();
                }

                historyHistogramChart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: `No prazo - ${sourceLabel} (${payload.selectedYear ?? ''})`,
                            data: displayOnTimeData,
                            backgroundColor: colorize('rgba(25,135,84,0.8)'),
                            borderColor: border,
                            borderWidth: 1,
                            borderRadius: 6,
                            stack: 'prazo',
                        }, {
                            label: `Fora do prazo - ${sourceLabel} (${payload.selectedYear ?? ''})`,
                            data: displayLateData,
                            backgroundColor: colorize('rgba(220,53,69,0.8)'),
                            borderColor: border,
                            borderWidth: 1,
                            borderRadius: 6,
                            stack: 'prazo',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            },
                        },
                        onClick: (evt, elements) => {
                            if (!elements.length) return;
                            const month = elements[0].index + 1;
                            const root = canvas.closest('[wire\\:id]');
                            if (!root) return;
                            const componentId = root.getAttribute('wire:id');
                            if (!componentId) return;
                            Livewire.find(componentId).call('setHistogramBucket', month);
                        },
                    },
                });

                lastSignature = raw;
            };

            buildHistoryHistogram();
            Livewire.hook('message.processed', () => buildHistoryHistogram());
        });
    </script>
@endpush
