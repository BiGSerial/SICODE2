@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
    use App\Enum\ProtestJobStatus;
@endphp

<div class="monitoring-page">
    <div class="container-fluid">
        <x-show-loading />

        <div class="monitoring-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>RECLAMAÇÕES EM ABERTO</h2>
                <div class="meta">Fila de despacho sem atividade válida em andamento</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Itens na fila</div>
                <div><strong>{{ $lists->total() ?? 0 }}</strong></div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent filters-grid">
            <div class="card-body px-0">
                <div class="filter-card">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <label for="perPageSelect">Registros por página</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-7 col-lg-6">
                            <div class="form-floating position-relative">
                                <input wire:model.debounce.500ms="search" class="form-control border border-secondary"
                                    id="searchInput" placeholder="Buscar por nota, cidade ou classificação..." />
                                <label for="searchInput">Buscar por nota, cidade ou classificação</label>

                                <button type="button"
                                    class="btn btn-outline-secondary position-absolute top-50 translate-middle-y me-2 border-0"
                                    style="right: .35rem;" data-bs-toggle="modal" data-bs-target="#buscarMultiModal"
                                    title="Busca múltipla">
                                    <i class="ri-checkbox-multiple-blank-line"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" id="filterTypeNote" wire:model="selectedTipoNota" multiple size="4">
                                    @foreach ($tipoNotas as $tipo)
                                        <option value="{{ $tipo->tipoNota }}">{{ $tipo->tipoNota }}</option>
                                    @endforeach
                                </select>
                                <label for="filterTypeNote">Tipo de nota</label>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" id="filterProtestType"
                                    wire:model="selectedProtestType" multiple size="4">
                                    @foreach ($protest_Types as $type)
                                        <option value="{{ $type->protest_type }}">{{ $type->protest_type_label }}</option>
                                    @endforeach
                                </select>
                                <label for="filterProtestType">Tipo de Reclamação</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-0 mt-md-3">
                        <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" id="filterCity" wire:model="cityFilter" multiple size="4">
                                    @foreach ($cityOptions as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                                <label for="filterCity">Município</label>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" id="filterCodf" wire:model="selectedCodf" multiple size="4">
                                    @foreach ($codfOptions as $codf)
                                        <option value="{{ $codf }}">{{ $codf }}</option>
                                    @endforeach
                                </select>
                                <label for="filterCodf">CodF</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary"
                                wire:click="$set('search',''); $set('advanceSearch',''); $set('multisearch',[]); $set('selectedTipoNota',[]); $set('selectedProtestType',[]); $set('cityFilter', []); $set('selectedCodf', []); $set('statusCardFilter', null); $set('histogramBucket', null); $set('page',1)">
                                <i class="ri-eraser-line me-1"></i>
                                Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-8">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body histogram-card-body">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Histograma de Previsões Mensais (Em aberto)</div>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" wire:model="histogramSource" style="min-width: 170px;">
                                    <option value="desired">Data desejada</option>
                                    <option value="sla">Data SLA (se existir)</option>
                                </select>
                                @if (!empty($histogramData['selectedBucket']))
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearHistogramFilter">
                                        Limpar mês
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div id="lists-histogram-data" data-payload='@json($histogramData)'></div>
                        <div class="histogram-chart-wrap" wire:ignore>
                            <canvas id="listsHistogram"></canvas>
                        </div>
                        @php
                            $selectedBucket = (string) ($histogramData['selectedBucket'] ?? '');
                            $monthKeys = (array) ($histogramData['monthKeys'] ?? []);
                            $monthTotals = (array) ($histogramData['monthTotals'] ?? []);
                            $monthLabels = (array) ($histogramData['monthLabels'] ?? []);
                        @endphp
                        <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                            @foreach ($monthKeys as $monthKey)
                                @php
                                    $monthTotal = (int) ($monthTotals[$monthKey] ?? 0);
                                    $isActive = $selectedBucket !== '' && $selectedBucket === $monthKey;
                                    $monthLabel = $monthLabels[$monthKey] ?? $monthKey;
                                @endphp
                                <button type="button" class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    @disabled($monthTotal <= 0)
                                    wire:click="setHistogramBucket('{{ $monthKey }}')">
                                    {{ $monthLabel }}
                                </button>
                            @endforeach
                        </div>
                        <small class="text-muted">Clique no stack da barra para filtrar a lista por tipo de prazo. Use os botões abaixo para isolar o mês.</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="d-flex flex-column gap-3 h-100">
                    <button type="button"
                        class="status-summary-card status-summary-card--warning {{ $statusCardFilter === 'due_today' ? 'is-active' : '' }}"
                        wire:click="setStatusCardFilter('due_today')">
                        <div class="status-summary-icon">
                            <i class="ri-timer-2-line"></i>
                        </div>
                        <div class="status-summary-body">
                            <span class="status-summary-label">Vencendo hoje</span>
                            <span class="status-summary-value">{{ $dueTodayCount }}</span>
                            <small>Data desejada = hoje</small>
                        </div>
                    </button>
                    <button type="button"
                        class="status-summary-card status-summary-card--danger {{ $statusCardFilter === 'overdue' ? 'is-active' : '' }}"
                        wire:click="setStatusCardFilter('overdue')">
                        <div class="status-summary-icon">
                            <i class="ri-error-warning-line"></i>
                        </div>
                        <div class="status-summary-body">
                            <span class="status-summary-label">Vencidos</span>
                            <span class="status-summary-value">{{ $overdueCount }}</span>
                            <small>Data desejada anterior a hoje</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        @if ($lists->count())
            <div class="summary-bar mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    {{ $lists->links() }}
                    <div class="text-muted small">
                        Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                        {{ $lists->total() }} registros
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="card-header table-title-hero d-flex justify-content-between align-items-center">
                    <h5 class="card-title my-0">RECLAMAÇÕES EM ABERTO</h5>
                    <button wire:click="exportToExcel" class="btn btn-sm btn-outline-light">
                        <i class="ri-file-excel-2-line me-1"></i>Exportar Excel
                    </button>
                </div>

                <div class="table-scroll-shell">
                <table class="table table-sm table-striped table-condensed mb-0">
                    <thead class="table-dark">
                        <tr class="align-middle text-center sticky-top" style="top: 0;">
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('med_id')">M @if($sortBy==='med_id')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('nota')">Nota @if($sortBy==='nota')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('tipo_nota')">Tipo @if($sortBy==='tipo_nota')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('cod')">Cód @if($sortBy==='cod')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('codf')">CodF @if($sortBy==='codf')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('tipo_reclamacao')">Tipo Reclamação @if($sortBy==='tipo_reclamacao')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('tx_cod_medida')">TxCodeMedida @if($sortBy==='tx_cod_medida')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('causa_raiz')">CausaRaiz @if($sortBy==='causa_raiz')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('origem')">Origem @if($sortBy==='origem')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('municipio')">Município @if($sortBy==='municipio')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('abertura_nota')">Abertura Reclamação @if($sortBy==='abertura_nota')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('abertura_medida')">Abertura Medida @if($sortBy==='abertura_medida')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th>Tempo Medida</th>
                            <th><button type="button" class="btn btn-link p-0 text-white text-decoration-none fw-bold" wire:click="sortByColumn('vencimento')">Desejada @if($sortBy==='vencimento')<i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>@endif</button></th>
                            <th>Status Resposta</th>
                            <th style="width:48px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $medProtest)
                            @php
                                $protest = $medProtest->Protest;
                                $startDate = $protest?->dtAberturaNota;
                                $startMedDate = $medProtest->dtCriacaoMedida;
                                $deadline =
                                    ($protest?->tipoNota === 'NA')
                                        ? $protest?->dtConclusaoDesej
                                        : $medProtest->dtFimMedidaDesej;
                                $elapsedMed = $startMedDate
                                    ? Carbon::parse($startMedDate)->startOfDay()->diffInDays(now()->startOfDay())
                                    : '—';

                                $deadlineBadge = ['label' => 'Sem prazo', 'class' => 'badge text-bg-secondary', 'date' => '—'];
                                if ($deadline) {
                                    $deadlineDate = Carbon::parse($deadline);
                                    $deadlineBadge['date'] = $deadlineDate->format('d/m/Y');
                                    if ($deadlineDate->endOfDay()->isPast()) {
                                        $deadlineBadge['label'] = 'Vencido';
                                        $deadlineBadge['class'] = 'badge text-bg-danger';
                                    } elseif ($deadlineDate->diffInDays() <= 2) {
                                        $deadlineBadge['label'] = 'Vencendo';
                                        $deadlineBadge['class'] = 'badge text-bg-warning';
                                    } else {
                                        $deadlineBadge['label'] = 'No prazo';
                                        $deadlineBadge['class'] = 'badge text-bg-success';
                                    }
                                }

                                $latestJob = $medProtest?->ProtestJobs->first();
                                $jobStatusLabel = 'Sem Job';
                                $jobStatusClass = 'badge text-bg-secondary';
                                if ($latestJob) {
                                    $statusValue = $latestJob->status;
                                    $enum = $statusValue instanceof ProtestJobStatus ? $statusValue : ProtestJobStatus::tryFrom((string) $statusValue);
                                    $jobStatusLabel = $enum ? $enum->label() : Str::headline((string) $statusValue);
                                    $jobStatusClass = match ($enum?->value ?? (string) $statusValue) {
                                        'done' => 'badge text-bg-success',
                                        'waiting' => 'badge text-bg-dark',
                                        'in_progress' => 'badge text-bg-warning',
                                        'canceled' => 'badge text-bg-danger',
                                        default => 'badge text-bg-primary',
                                    };
                                }
                            @endphp
                            <tr class="align-middle text-center" wire:key="list-med-{{ $medProtest->id }}"
                                ondblclick="window.location.href='{{ route('protests.dispatch.view', ['protest' => $medProtest->id]) }}'">
                                <td>{{ $medProtest->med_id ?? '—' }}</td>
                                <td class="fw-semibold">{{ $protest?->nota ?? '—' }}</td>
                                <td>{{ $protest?->tipoNota ?? '—' }}</td>
                                <td>{{ $medProtest->codMedida ?? '—' }}</td>
                                <td>{{ $protest?->codecodf ?? '—' }}</td>
                                <td class="small text-uppercase">{{ $protest?->txtGrpCodificacao ?? '—' }}</td>
                                <td>{{ $medProtest->txtCodMedida ?? '—' }}</td>
                                <td class="small">{{ Str::limit($protest?->descCausa ?? '—', 22) }}</td>
                                <td class="small">{{ Str::limit($protest?->descricao ?? '—', 22) }}</td>
                                <td class="small">{{ $protest?->cidade ?? '—' }}</td>
                                <td>{{ optional($startDate)->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ optional($startMedDate)->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="badge text-bg-secondary">{{ $elapsedMed }} d</span></td>
                                <td><span class="{{ $deadlineBadge['class'] }}">{{ $deadlineBadge['date'] }} · {{ $deadlineBadge['label'] }}</span></td>
                                <td><span class="{{ $jobStatusClass }}">{{ $jobStatusLabel }}</span></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($medProtest)
                                                <li>
                                                    <button class="dropdown-item" type="button"
                                                        wire:click.prevent="$emitTo('protests.dispatch.actions.control-med-protest', 'openModProtestControl', {{ $medProtest->id }})">
                                                        <i class="ri-send-plane-line me-1"></i> Gerenciar / Criar atividade
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" type="button"
                                                        wire:click="confirmAutoDemand({{ $medProtest->id }})">
                                                        <i class="ri-robot-line me-1"></i> Auto demanda
                                                    </button>
                                                </li>
                                            @endif
                                            <li>
                                                <button class="dropdown-item" type="button" wire:click="goTo({{ $medProtest->id }})">
                                                    <i class="ri-external-link-line me-1"></i> Abrir protesto
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center py-4 text-muted">Nenhuma reclamação encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="summary-bar mt-2">
                <div class="d-flex justify-content-between align-items-center">
                    {{ $lists->links() }}
                    <div class="text-muted small">
                        Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                        {{ $lists->total() }} registros
                    </div>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center">
                    <p class="mb-0">Não há registros para exibir com os filtros atuais.</p>
                </div>
            </div>
        @endif

        <div wire:ignore.self class="modal fade" id="buscarMultiModal" tabindex="-1" aria-labelledby="buscarMultiLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="buscarMultiLabel">
                            <i class="ri-search-2-line me-2"></i>
                            Busca Múltipla de Notas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <textarea class="form-control" id="advanceSearch" style="height: 200px;"
                                placeholder="Cole aqui vários valores (vírgula ou quebra de linha)" wire:model.defer="advanceSearch"></textarea>
                            <label for="advanceSearch">Números / valores</label>
                        </div>
                        <div class="form-text">
                            Separe por vírgula <strong>,</strong> ou por quebra de linha.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">
                            <i class="ri-check-line me-1"></i>Aplicar Filtro
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @livewire('protests.dispatch.actions.control-med-protest', key('control-med-protest'))

        @push('scripts')
            <script>
                document.addEventListener('livewire:load', () => {
                    let listsHistogramChart = null;
                    let lastSignature = null;

                    document.addEventListener('show.bs.dropdown', (event) => {
                        const shell = event.target.closest('.table-scroll-shell');
                        if (shell) {
                            shell.classList.add('is-dropdown-open');
                        }
                    });

                    document.addEventListener('hide.bs.dropdown', (event) => {
                        const shell = event.target.closest('.table-scroll-shell');
                        if (shell) {
                            shell.classList.remove('is-dropdown-open');
                        }
                    });

                    const buildListsHistogram = () => {
                        const canvas = document.getElementById('listsHistogram');
                        const payloadNode = document.getElementById('lists-histogram-data');

                        if (!canvas || !payloadNode || typeof Chart === 'undefined') {
                            return;
                        }

                        const raw = payloadNode.dataset.payload || '{}';
                        if (listsHistogramChart && lastSignature === raw) {
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
                        const overdueData = series.overdue || [];
                        const dueSoonData = series.dueSoon || [];
                        const withinData = series.within || [];
                        const selectedBucket = payload.selectedBucket || null;
                        const selectedStack = payload.selectedStack || null;
                        const displayOverdueData = (series.displayOverdue || overdueData).map((n) => Number(n ?? 0));
                        const displayDueSoonData = (series.displayDueSoon || dueSoonData).map((n) => Number(n ?? 0));
                        const displayWithinData = (series.displayWithin || withinData).map((n) => Number(n ?? 0));
                        const displayMonthKeys = payload.monthKeys || [];
                        const totalsByMonth = labels.map((_, i) =>
                            Number(displayOverdueData[i] ?? 0) + Number(displayDueSoonData[i] ?? 0) + Number(displayWithinData[i] ?? 0)
                        );

                        const sum = (arr) => (arr || []).reduce((acc, n) => acc + Number(n || 0), 0);
                        const overdueTotal = sum(displayOverdueData);
                        const dueSoonTotal = sum(displayDueSoonData);
                        const withinTotal = sum(displayWithinData);

                        const segmentIsActive = (key) => !selectedStack || selectedStack === key;
                        const marine = segmentIsActive('overdue') ? 'rgba(33,46,62,0.85)' : 'rgba(33,46,62,0.18)';
                        const electric = segmentIsActive('due_soon') ? 'rgba(40,255,82,0.85)' : 'rgba(40,255,82,0.18)';
                        const slate = segmentIsActive('within') ? 'rgba(124,149,153,0.85)' : 'rgba(124,149,153,0.18)';

                        if (listsHistogramChart) {
                            listsHistogramChart.destroy();
                        }

                        const totalsPlugin = {
                            id: 'listsHistogramTotals',
                            afterDatasetsDraw(chart) {
                                const { ctx } = chart;
                                const datasetMeta = chart.getDatasetMeta(0);
                                if (!datasetMeta || !datasetMeta.data) return;
                                ctx.save();
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                ctx.fillStyle = '#1f2937';
                                ctx.font = '600 11px sans-serif';

                                datasetMeta.data.forEach((bar, index) => {
                                    const total = Number(totalsByMonth[index] ?? 0);
                                    if (total <= 0) return;
                                    const x = bar.x;
                                    const y = bar.y - 6;
                                    ctx.fillText(String(total), x, y);
                                });
                                ctx.restore();
                            }
                        };

                        listsHistogramChart = new Chart(canvas.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [{
                                    label: `Vencidos (${overdueTotal})`,
                                    data: displayOverdueData,
                                    backgroundColor: marine,
                                    borderColor: '#212E3E',
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    stack: 'prazo',
                                }, {
                                    label: `Vencendo (${dueSoonTotal})`,
                                    data: displayDueSoonData,
                                    backgroundColor: electric,
                                    borderColor: '#28FF52',
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    stack: 'prazo',
                                }, {
                                    label: `A vencer (${withinTotal})`,
                                    data: displayWithinData,
                                    backgroundColor: slate,
                                    borderColor: '#7C9599',
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    stack: 'prazo',
                                }],
                            },
                            plugins: [totalsPlugin],
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: {
                                    padding: { top: 10 }
                                },
                                scales: {
                                    x: { stacked: true },
                                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grace: '20%' },
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: { padding: 14 }
                                    }
                                },
                                onClick: (evt) => {
                                    const exact = listsHistogramChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                                    if (!exact.length) return;
                                    const element = exact[0];
                                    const bucket = displayMonthKeys[Number(element.index)] || null;
                                    const segment = ['overdue', 'due_soon', 'within'][Number(element.datasetIndex)] || null;
                                    if (!segment) return;
                                    const root = canvas.closest('[wire\\:id]');
                                    if (!root) return;
                                    const componentId = root.getAttribute('wire:id');
                                    if (!componentId) return;
                                    Livewire.find(componentId).call('setHistogramStackSelection', bucket, segment);
                                },
                            },
                        });

                        lastSignature = raw;
                    };

                    buildListsHistogram();
                    Livewire.hook('message.processed', () => buildListsHistogram());
                });
            </script>
        @endpush
    </div>
</div>

@push('css')
    <style>
        .monitoring-page {
            --mp-bg: #f6f7fb;
            --mp-surface: #ffffff;
            --mp-muted: #6b7280;
            --mp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--mp-bg);
            padding: 1.5rem 0;
        }

        .monitoring-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .monitoring-header h2 { font-weight: 700; margin: 0; }
        .monitoring-header .meta { color: rgba(248, 250, 252, 0.75); font-size: .95rem; }

        .filters-grid .filter-card {
            background-color: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: .9rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .form-floating > .form-select[multiple] {
            height: 8.5rem !important;
            padding-top: 1.9rem;
            padding-bottom: .6rem;
        }

        .summary-bar {
            background: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: .9rem;
            padding: .75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        }

        .table-card {
            background: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: visible;
        }
        .table-scroll-shell {
            overflow: auto;
            position: relative;
        }
        .table-scroll-shell.is-dropdown-open {
            overflow: visible;
        }
        .table-scroll-shell .dropdown-menu {
            z-index: 1080;
        }

        .table-card .card-header {
            padding: .75rem 1.25rem;
            border-bottom: 0;
            margin: 0;
            border-radius: 1rem 1rem 0 0;
        }
        .table-card .card-header.table-title-hero {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
        }
        .table-card .card-header .card-title { padding-left: .15rem; }
        .table-card .table { margin-top: 0; margin-bottom: 0; }
        .table-card .table thead th { border-top: 0; }

        .histogram-card-body { display: flex; flex-direction: column; }
        .histogram-chart-wrap { position: relative; width: 100%; height: clamp(260px, 34vh, 420px); max-height: 420px; overflow: hidden; }
        .histogram-chart-wrap canvas { width: 100% !important; height: 100% !important; }

        .status-summary-card {
            border: none;
            border-radius: 16px;
            padding: 1rem 1.2rem;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all .2s ease;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
            cursor: pointer;
            background: #fff;
        }

        .status-summary-card .status-summary-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 1.5rem; background: rgba(255,255,255,.25); }
        .status-summary-card .status-summary-label { font-size: .9rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 600; display: block; }
        .status-summary-card .status-summary-value { font-size: 1.9rem; font-weight: 700; line-height: 1; display: block; }
        .status-summary-card small { font-size: .78rem; opacity: .8; }
        .status-summary-card.is-active { transform: translateY(-4px); box-shadow: 0 16px 30px rgba(15,23,42,.18); }
        .status-summary-card--warning { background: linear-gradient(135deg, #fff7e6, #ffe3b3); color: #7a4d00; }
        .status-summary-card--danger { background: linear-gradient(135deg, #ffe4e6, #ffb3c0); color: #7c1d2c; }

        .table th .btn-link { font-size: .85rem; }
    </style>
@endpush
