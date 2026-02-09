@php use Carbon\Carbon; @endphp

<div>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="bi bi-clock-history me-2"></i>Histórico de Atualizações
            </h5>

            <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm">
                    <label class="input-group-text" for="taskSelect">Tarefa</label>
                    <select id="taskSelect" class="form-select form-select-sm" wire:model.live="singleTask">
                        <option value="">Todas</option>
                        @foreach ($tasks as $task)
                            <option value="{{ $task }}">{{ $task }}</option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-sm btn-light" wire:click="resetCursor" title="Limpar e recarregar">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($logs as $log)
                @php
                    $id = $log['id'] ?? '—';
                    $tarefa = $log['tarefa'] ?? 'N/A';
                    $status = strtoupper((string) ($log['status'] ?? 'DONE'));

                    $statusClass = match ($status) {
                        'RUNNING' => 'text-bg-warning',
                        'FAIL' => 'text-bg-danger',
                        default => 'text-bg-success',
                    };

                    $start = !empty($log['date_inicio'] ?? null) ? Carbon::parse($log['date_inicio']) : null;
                    $end = !empty($log['date_fim'] ?? null) ? Carbon::parse($log['date_fim']) : null;

                    $difference = 'N/A';
                    if ($start && $end) {
                        $sec = $start->diffInSeconds($end);
                        if ($sec < 60) {
                            $difference = $sec . ' seg';
                        } elseif ($sec < 3600) {
                            $difference = intdiv($sec, 60) . ' min';
                        } elseif ($sec < 86400) {
                            $difference = intdiv($sec, 3600) . ' h';
                        } else {
                            $difference = intdiv($sec, 86400) . ' dias';
                        }
                    }

                    $collapseId = 'log-details-' . $id;
                @endphp

                <div class="list-group-item" wire:key="log-{{ $id }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary">#{{ $id }}</span>
                            <span class="badge text-bg-primary">{{ $tarefa }}</span>
                            <span class="badge {{ $statusClass }}">{{ $status }}</span>
                            <small class="text-muted">{{ $start ? $start->format('d/m/Y H:i') : 'N/A' }}</small>
                        </div>

                        <div class="d-flex gap-3 align-items-center">
                            <span class="badge bg-info text-dark">
                                <i class="bi bi-stopwatch me-1"></i> {{ $difference }}
                            </span>

                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                data-bs-target="#{{ $collapseId }}">
                                <i class="bi bi-eye"></i> Ver mais
                            </button>
                        </div>
                    </div>

                    <div class="collapse mt-3" id="{{ $collapseId }}">
                        <div class="card card-body p-2 bg-light border">
                            <div class="row g-2">
                                <div class="col-md-3"><strong>Criados:</strong> {{ $log['created'] ?? 0 }}</div>
                                <div class="col-md-3"><strong>Atualizados:</strong> {{ $log['updated'] ?? 0 }}</div>
                                <div class="col-md-3"><strong>Total:</strong> {{ $log['total'] ?? 0 }}</div>
                                <div class="col-md-3"><strong>Erros:</strong> {{ $log['erros'] ?? 0 }}</div>

                                <div class="col-md-6"><strong>Início:</strong>
                                    {{ $start ? $start->format('d/m/Y H:i:s') : 'N/A' }}</div>
                                <div class="col-md-6"><strong>Fim:</strong>
                                    {{ $end ? $end->format('d/m/Y H:i:s') : 'N/A' }}</div>

                                <div class="col-md-6">
                                    <strong>Executado:</strong> {{ $end ? $end->diffForHumans() : 'N/A' }}
                                </div>

                                <div class="col-md-6">
                                    <strong>Note Updated:</strong> {{ $log['noteupdated'] ?? 'N/A' }}
                                </div>

                                @if (!empty($log['fail_reason'] ?? null))
                                    <div class="col-12 text-danger">
                                        <strong>Falha:</strong> {{ $log['fail_reason'] }}
                                    </div>
                                @endif

                                <div class="col-12">
                                    <strong>Opções:</strong>
                                    <pre class="mb-0" style="white-space:pre-wrap">{{ json_encode($log['options'] ?? new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>

                                @if (!empty($log['errosMSGs'] ?? []))
                                    <div class="col-12">
                                        <strong>Mensagens de Erro:</strong>
                                        <ul class="mb-0">
                                            @foreach ($log['errosMSGs'] ?? [] as $msg)
                                                <li>{{ $msg }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">
                    Nenhum registro encontrado.
                </div>
            @endforelse
        </div>

        <div class="card-footer text-center">
            @if ($hasMore)
                <button class="btn btn-sm btn-outline-primary" wire:click="loadMore" wire:loading.attr="disabled">
                    <i class="bi bi-chevron-down me-1"></i> Carregar mais
                </button>
            @else
                <span class="text-muted small">Fim do histórico</span>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-3" wire:poll.10000ms="refreshRunningLogs">
        <div class="card-header bg-warning-subtle d-flex align-items-center justify-content-between">
            <h6 class="mb-0">
                <i class="bi bi-play-circle me-2"></i>Execuções em andamento
            </h6>
            <span class="badge text-bg-warning">{{ count($runningLogs ?? []) }}</span>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($runningLogs as $run)
                @php
                    $runStart = !empty($run['date_inicio'] ?? null) ? Carbon::parse($run['date_inicio']) : null;
                    $elapsed = $runStart ? $runStart->diffForHumans(null, true) : 'N/A';
                @endphp
                <div class="list-group-item d-flex justify-content-between align-items-center"
                    wire:key="running-{{ $run['id'] }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary">#{{ $run['id'] }}</span>
                        <span class="badge text-bg-primary">{{ $run['tarefa'] }}</span>
                        <span class="badge text-bg-warning">RUNNING</span>
                    </div>
                    <div class="small text-muted">
                        início: {{ $runStart ? $runStart->format('d/m/Y H:i:s') : 'N/A' }} | rodando há {{ $elapsed }}
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-3">
                    Nenhuma execução em andamento no momento.
                </div>
            @endforelse
        </div>
    </div>

    <div wire:loading.flex class="w-100 py-3 justify-content-center">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <span class="ms-2 small text-muted">Carregando…</span>
    </div>
</div>
