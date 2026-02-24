@php use Carbon\Carbon; @endphp

<div class="update-log-page">
    <style>
        .update-log-page {
            --ul-bg: #f6f7fb;
            --ul-surface: #ffffff;
            --ul-ink: #1f2933;
            --ul-muted: #6b7280;
            --ul-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--ul-bg);
            padding: 1.5rem 0;
        }

        .update-log-page .page-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .update-log-page .page-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .update-log-page .page-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .update-log-page .table-card {
            background: var(--ul-surface);
            border: 1px solid var(--ul-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
    </style>

    <div class="container-fluid">
        <div class="page-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>Historico de Atualizacoes</h2>
                <div class="meta">Acompanhamento de execucoes e resultados das tarefas</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Logs carregados</div>
                <div><strong>{{ count($logs ?? []) }}</strong></div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
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

                    <button class="btn btn-sm btn-outline-secondary" wire:click="resetCursor" title="Limpar e recarregar">
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

            <div class="card-footer text-center bg-white">
                @if ($hasMore)
                    <button class="btn btn-sm btn-outline-primary" wire:click="loadMore" wire:loading.attr="disabled">
                        <i class="bi bi-chevron-down me-1"></i> Carregar mais
                    </button>
                @else
                    <span class="text-muted small">Fim do histórico</span>
                @endif
            </div>
        </div>

        <div class="table-card mt-3" wire:poll.10000ms="refreshRunningLogs">
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
            <span class="ms-2 small text-muted">Carregando...</span>
        </div>
    </div>
</div>
