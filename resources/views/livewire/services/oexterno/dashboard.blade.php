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
            padding: .75rem 1rem;
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
    </style>
@endpush

<div>
    <x-show-loading />

    {{-- Filtros --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="ri-filter-3-line me-2"></i>Filtros</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                    Limpar
                </button>
                <button class="btn btn-sm btn-primary" wire:click="$refresh">
                    Aplicar
                </button>
                <button class="btn btn-sm btn-success" wire:click="exportAdminCsv">
                    <i class="ri-file-excel-line me-1"></i> Exportar Admin
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 filters-container">
                {{-- Período --}}
                <div class="col-lg-4">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-3 text-muted"><i class="ri-calendar-line me-1"></i>Período</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="filter-label">Início</label>
                                    <input type="date" max="{{ date('Y-m-d') }}" class="form-control form-control-sm"
                                        wire:model.lazy="dt_in">
                                </div>
                                <div class="col-md-6">
                                    <label class="filter-label">Fim</label>
                                    <input type="date" max="{{ date('Y-m-d') }}" class="form-control form-control-sm"
                                        wire:model.lazy="dt_out">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status (multi) --}}
                <div class="col-lg-2">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-flag-2-line me-1"></i>Status</h6>
                            <select class="form-select form-select-sm" wire:model.defer="status" multiple
                                size="6">
                                @foreach ($statusOptions as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach

                            </select>
                        </div>
                    </div>
                </div>

                {{-- Tipo de Entidade (multi) --}}
                <div class="col-lg-3">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-building-2-line me-1"></i>Tipo de Entidade</h6>
                            <select class="form-select form-select-sm" wire:model.defer="entityTypeIds" multiple
                                size="6">
                                @foreach ($entityTypeOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Entidade (multi) --}}
                <div class="col-lg-3">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-community-line me-1"></i>Entidade</h6>
                            <select class="form-select form-select-sm" wire:model.defer="entityIds" multiple
                                size="6">
                                @foreach ($entityOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Rubrica (multi) --}}
                <div class="col-lg-3">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-bookmark-3-line me-1"></i>Rubrica</h6>
                            <select class="form-select form-select-sm" wire:model.defer="rubrics" multiple
                                size="6">
                                @foreach ($rubricOptions as $rub)
                                    <option value="{{ $rub }}">{{ $rub }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Usuário (multi) --}}
                <div class="col-lg-3">
                    <div class="card border-0 bg-light rounded-3 h-100">
                        <div class="card-body py-3">
                            <h6 class="mb-2 text-muted"><i class="ri-user-voice-line me-1"></i>Usuário (Interações)</h6>
                            <select class="form-select form-select-sm" wire:model.defer="userIds" multiple
                                size="6">
                                @foreach ($userOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text small mt-2">Filtra pelos usuários que comentaram/interagiram.</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- RETORNOS INTERNOS (RECLAIM) --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex align-items-center justify-content-between">
                    <strong><i class="ri-refresh-line me-1"></i>Retornos internos (Reclaim)</strong>
                    <button class="btn btn-sm btn-outline-success" wire:click="exportReclaimRaw">
                        <i class="ri-file-excel-line me-1"></i> Exportar reclaims
                    </button>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-4">
                        <div>
                            <div class="text-muted small">Reclaims no periodo</div>
                            <div class="h4 mb-0">{{ $reclaimStats['total'] ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">Concluidos</div>
                            <div class="h4 mb-0">{{ $reclaimStats['completed'] ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">Em aberto</div>
                            <div class="h4 mb-0">{{ $reclaimStats['open'] ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">Taxa de conclusao</div>
                            <div class="h4 mb-0">{{ $reclaimStats['completion_rate'] ?? 0 }}%</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-muted small mb-2">Principais causas</div>
                        @if (!empty($reclaimTopCausesList))
                            <ul class="list-unstyled mb-0">
                                @foreach ($reclaimTopCausesList as $cause)
                                    <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                                        <span>{{ $cause['cause'] }}</span>
                                        <span class="badge text-bg-light">{{ $cause['total'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-muted small">Sem dados no periodo.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card h-100">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-pie-chart-2-line me-2"></i>Causas dos retornos</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:300px"><canvas id="chartReclaimCauses"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRÁFICOS LINHA 1 --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-time-line me-2"></i>Interações Diárias</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:300px"><canvas id="chartDaily"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-team-line me-2"></i>Top Usuários (diário)</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:300px"><canvas id="chartTopUsers"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRÁFICOS LINHA 2 --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-bar-chart-2-line me-2"></i>Interações Mensais (12m)</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:300px"><canvas id="chartMonthly"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-building-2-line me-2"></i>Top Entidades por Interações
                    </h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:300px"><canvas id="chartTopEntities"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRÁFICOS LINHA 3 --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-shield-check-line me-2"></i>Distribuição por Tipo de
                        Entidade</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:320px"><canvas id="chartByType"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-bookmark-3-line me-2"></i>Rubrica × Entidade</h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:320px"><canvas id="chartRubricEntity"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRÁFICOS LINHA 4 --}}
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title"><i class="ri-hourglass-2-line me-2"></i>Backlog por Faixa de Dias
                    </h5>
                </div>
                <div class="p-3" wire:ignore>
                    <div style="height:320px"><canvas id="chartAge"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- LISTA --}}
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="ri-list-check-2 me-1"></i>Externals (filtrados)</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:120px">Criado em</th>
                        <th style="width:140px">Status</th>
                        <th>Entidade</th>
                        <th>Tipo</th>
                        <th style="width:140px">Nota</th>
                        <th>Rubrica</th>
                        <th style="width:140px">Últ. interação</th>
                        <th style="width:100px" class="text-end">Idade (dias)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $ex)
                        @php
                            $created = \Carbon\Carbon::parse($ex->created_at);
                            $last = $ex->last_comment_at ? \Carbon\Carbon::parse($ex->last_comment_at) : null;
                            $ageDays = $created->diffInDays(now());
                        @endphp
                        <tr>
                            <td>{{ $created->format('d/m/Y H:i') }}</td>
                            <td><span class="badge text-bg-secondary">{{ $ex->status ?? '—' }}</span></td>
                            <td>{{ $ex->Entity->name ?? '—' }}</td>
                            <td>{{ $ex->Entity->Type->name ?? '—' }}</td>
                            <td>{{ $ex->Note->note ?? '—' }}</td>
                            <td>{{ $ex->Note->rubrica ?? '—' }}</td>
                            <td>{{ $last ? $last->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-end">{{ $ageDays }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="ri-emotion-sad-line me-1"></i> Nenhum registro encontrado.
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            window.extDash = window.extDash || {};
            const ctxDaily = document.getElementById('chartDaily').getContext('2d');
            const ctxTopUsers = document.getElementById('chartTopUsers').getContext('2d');
            const ctxMonthly = document.getElementById('chartMonthly').getContext('2d');
            const ctxTopEnt = document.getElementById('chartTopEntities').getContext('2d');
            const ctxByType = document.getElementById('chartByType').getContext('2d');
            const ctxRubEnt = document.getElementById('chartRubricEntity').getContext('2d');
            const ctxAge = document.getElementById('chartAge').getContext('2d');
            const ctxReclaimCauses = document.getElementById('chartReclaimCauses').getContext('2d');

            function ensureOrCreate(id, ctx, cfg) {
                if (window.extDash[id]) {
                    window.extDash[id].config.type = cfg.type;
                    window.extDash[id].config.data = cfg.data;
                    window.extDash[id].config.options = cfg.options || {};
                    window.extDash[id].update();
                } else {
                    window.extDash[id] = new Chart(ctx, cfg);
                }
            }

            // 1ª renderização
            ensureOrCreate('daily', ctxDaily, @json($daily));
            ensureOrCreate('topUsers', ctxTopUsers, @json($topUsers));
            ensureOrCreate('monthly', ctxMonthly, @json($monthly));
            ensureOrCreate('topEnt', ctxTopEnt, @json($topEntities));
            ensureOrCreate('byType', ctxByType, @json($byType));
            ensureOrCreate('rubEnt', ctxRubEnt, @json($rubricEntity));
            ensureOrCreate('age', ctxAge, @json($age));
            ensureOrCreate('reclaimCauses', ctxReclaimCauses, @json($reclaimTopCausesChart));

            // Atualizações
            window.addEventListener('chart-daily', e => ensureOrCreate('daily', ctxDaily, e.detail));
            window.addEventListener('chart-top-users', e => ensureOrCreate('topUsers', ctxTopUsers, e.detail));
            window.addEventListener('chart-monthly', e => ensureOrCreate('monthly', ctxMonthly, e.detail));
            window.addEventListener('chart-top-entities', e => ensureOrCreate('topEnt', ctxTopEnt, e.detail));
            window.addEventListener('chart-etype', e => ensureOrCreate('byType', ctxByType, e.detail));
            window.addEventListener('chart-rubric-entity', e => ensureOrCreate('rubEnt', ctxRubEnt, e.detail));
            window.addEventListener('chart-age', e => ensureOrCreate('age', ctxAge, e.detail));
            window.addEventListener('chart-reclaim-causes', e => ensureOrCreate('reclaimCauses', ctxReclaimCauses, e.detail));
        })();
    </script>
@endpush
