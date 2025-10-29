<div class="jobs-monitor" wire:poll.3000ms="refreshData">
    <style>
        /* Escopo do componente para evitar conflito com outras telas (ex.: specs) */
        .jobs-monitor .jobs-auto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: .75rem;
        }

        @media (max-width: 420px) {
            .jobs-monitor .jobs-auto-grid {
                grid-template-columns: 1fr;
            }
        }

        .jobs-monitor .jobs-kpi-card .card-body {
            padding: .75rem;
        }

        .jobs-monitor .jobs-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .jobs-monitor .jobs-prewrap {
            white-space: pre-wrap;
        }

        .jobs-monitor .jobs-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .jobs-monitor .jobs-table-wrap table {
            min-width: 700px;
        }
    </style>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-collection-play me-2"></i>Monitor de Fila</h6>
            <div class="d-flex align-items-center gap-2">
                @php
                    $statusClass = $workerActive ? 'text-bg-success' : 'text-bg-danger';
                    $statusText = $workerActive ? 'Worker ATIVO' : 'Worker PARADO';
                @endphp
                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                <span class="badge text-bg-light">Fonte: {{ $workerSource }}</span>
            </div>
        </div>

        <div class="card-body">
            {{-- KPIs por fila (grid fluido, sem conflito com specs) --}}
            <div class="jobs-auto-grid">
                @forelse($queueCounts as $q)
                    @php
                        $total = $q['pending'] + $q['running'] + $q['delayed'];
                        $pctPending = $total ? round(($q['pending'] / $total) * 100, 1) : 0;
                        $pctRunning = $total ? round(($q['running'] / $total) * 100, 1) : 0;
                        $pctDelayed = $total ? round(($q['delayed'] / $total) * 100, 1) : 0;
                    @endphp
                    <div class="card shadow-sm jobs-kpi-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Fila: {{ $q['queue'] }}</div>
                                <span class="badge text-bg-secondary">Total: {{ $total }}</span>
                            </div>
                            <div class="small mt-2">
                                Pendentes: <strong>{{ $q['pending'] }}</strong> — Execução:
                                <strong>{{ $q['running'] }}</strong> — Atrasados: <strong>{{ $q['delayed'] }}</strong>
                            </div>
                            <div class="progress mt-2" style="height:8px;">
                                <div class="progress-bar bg-secondary" style="width: {{ $pctPending }}%"
                                    title="Pendentes {{ $pctPending }}%"></div>
                                <div class="progress-bar bg-info" style="width: {{ $pctRunning }}%"
                                    title="Em execução {{ $pctRunning }}%"></div>
                                <div class="progress-bar bg-warning" style="width: {{ $pctDelayed }}%"
                                    title="Atrasados {{ $pctDelayed }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card shadow-sm jobs-kpi-card">
                        <div class="card-body small text-muted">Sem dados de filas.</div>
                    </div>
                @endforelse
            </div>

            {{-- Listas --}}
            <div class="row g-3 mt-3">
                {{-- Pendentes --}}
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Pendentes</strong>
                            <span class="badge text-bg-secondary">{{ $pendingJobs->count() }}</span>
                        </div>
                        <div class="jobs-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Queue</th>
                                        <th>Job</th>
                                        <th class="text-end">Tent.</th>
                                        <th class="text-end">Disponível</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pendingJobs as $job)
                                        <tr wire:key="pending-job-{{ $job->id }}">
                                            <td>{{ $job->id }}</td>
                                            <td>{{ $job->queue }}</td>
                                            <td>
                                                <div class="jobs-truncate-2" title="{{ $job->name }}">
                                                    {{ $job->name }}</div>
                                                <button class="btn btn-xs btn-link p-0" data-bs-toggle="collapse"
                                                    data-bs-target="#payload-p-{{ $job->id }}">ver
                                                    payload</button>
                                                <div class="collapse" id="payload-p-{{ $job->id }}"
                                                    wire:ignore.self>
                                                    <pre class="jobs-prewrap small mt-1">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $job->attempts }}</td>
                                            <td class="text-end" title="{{ $job->available_at->toDateTimeString() }}">
                                                {{ $job->available_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Sem jobs pendentes.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Em execução --}}
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Em execução</strong>
                            <span class="badge text-bg-info">{{ $runningJobs->count() }}</span>
                        </div>
                        <div class="jobs-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Queue</th>
                                        <th>Job</th>
                                        <th class="text-end">Tent.</th>
                                        <th class="text-end">Reservado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($runningJobs as $job)
                                        <tr wire:key="running-job-{{ $job->id }}">
                                            <td>{{ $job->id }}</td>
                                            <td>{{ $job->queue }}</td>
                                            <td>
                                                <div class="jobs-truncate-2" title="{{ $job->name }}">
                                                    {{ $job->name }}</div>
                                                <button class="btn btn-xs btn-link p-0" data-bs-toggle="collapse"
                                                    data-bs-target="#payload-r-{{ $job->id }}">ver
                                                    payload</button>
                                                <div class="collapse" id="payload-r-{{ $job->id }}"
                                                    wire:ignore.self>
                                                    <pre class="jobs-prewrap small mt-1">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $job->attempts }}</td>
                                            <td class="text-end"
                                                title="{{ optional($job->reserved_at)->toDateTimeString() }}">
                                                {{ optional($job->reserved_at)->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                Sem jobs em execução (últimos {{ $this->runningThresholdMinutes }}
                                                min).
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Atrasados --}}
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Atrasados (agendados)</strong>
                            <span class="badge text-bg-warning">{{ $delayedJobs->count() }}</span>
                        </div>
                        <div class="jobs-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Queue</th>
                                        <th>Job</th>
                                        <th class="text-end">Disponível em</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($delayedJobs as $job)
                                        <tr wire:key="delayed-job-{{ $job->id }}">
                                            <td>{{ $job->id }}</td>
                                            <td>{{ $job->queue }}</td>
                                            <td>
                                                <div class="jobs-truncate-2" title="{{ $job->name }}">
                                                    {{ $job->name }}</div>
                                                <button class="btn btn-xs btn-link p-0" data-bs-toggle="collapse"
                                                    data-bs-target="#payload-d-{{ $job->id }}">ver
                                                    payload</button>
                                                <div class="collapse" id="payload-d-{{ $job->id }}"
                                                    wire:ignore.self>
                                                    <pre class="jobs-prewrap small mt-1">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </td>
                                            <td class="text-end" title="{{ $job->available_at->toDateTimeString() }}">
                                                {{ $job->available_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Sem jobs atrasados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Falhados --}}
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Falhados</strong>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary"
                                    wire:click="retryAllFailed">Reenfileirar
                                    todos</button>
                            </div>
                        </div>
                        <div class="jobs-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Queue</th>
                                        <th>Job</th>
                                        <th>Erro</th>
                                        <th class="text-end">Falhou</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($failedJobs as $job)
                                        <tr wire:key="failed-job-{{ $job->id }}">
                                            <td>{{ $job->id }}</td>
                                            <td>{{ $job->queue }}</td>
                                            <td>
                                                <div class="jobs-truncate-2" title="{{ $job->name }}">
                                                    {{ $job->name }}</div>
                                                <button class="btn btn-xs btn-link p-0" data-bs-toggle="collapse"
                                                    data-bs-target="#payload-f-{{ $job->id }}">ver
                                                    payload</button>
                                                <div class="collapse" id="payload-f-{{ $job->id }}"
                                                    wire:ignore.self>
                                                    <pre class="jobs-prewrap small mt-1">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </td>
                                            <td><span class="small jobs-truncate-2"
                                                    title="{{ $job->exception }}">{{ $job->exception }}</span></td>
                                            <td class="text-end" title="{{ $job->failed_at->toDateTimeString() }}">
                                                {{ $job->failed_at->diffForHumans() }}</td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary"
                                                        wire:click="restartJob({{ $job->id }})">Reiniciar</button>
                                                    <button class="btn btn-outline-danger"
                                                        wire:click="deleteFailed({{ $job->id }})">Excluir</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Sem jobs falhados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Sucesso (histórico) --}}
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Finalizados com sucesso (histórico)</strong>
                            @if ($succeeded->isEmpty())
                                <span class="badge text-bg-light">Ative o histórico: migration & provider</span>
                            @endif
                        </div>
                        <div class="jobs-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>UUID</th>
                                        <th>Queue</th>
                                        <th>Job</th>
                                        <th class="text-end">Tent.</th>
                                        <th class="text-end">Runtime (ms)</th>
                                        <th class="text-end">Finalizado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($succeeded as $row)
                                        <tr wire:key="succeeded-job-{{ $row->id }}">
                                            <td>{{ $row->id }}</td>
                                            <td class="text-truncate" style="max-width: 180px;"
                                                title="{{ $row->uuid }}">{{ $row->uuid }}</td>
                                            <td>{{ $row->queue }}</td>
                                            <td class="text-truncate" style="max-width: 260px;"
                                                title="{{ $row->name }}">{{ $row->name }}</td>
                                            <td class="text-end">{{ $row->attempts }}</td>
                                            <td class="text-end">{{ $row->runtime_ms }}</td>
                                            <td class="text-end" title="{{ $row->finished_at->toDateTimeString() }}">
                                                {{ $row->finished_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Sem histórico
                                                disponível.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Alerts --}}
            @if (session()->has('message'))
                <div class="alert alert-success mt-3 mb-0">{{ session('message') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger mt-3 mb-0">{{ session('error') }}</div>
            @endif
        </div>
    </div>
</div>
