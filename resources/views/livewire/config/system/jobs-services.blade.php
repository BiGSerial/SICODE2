<div class="jobs-monitor" wire:poll.3000ms="refreshData">
    <style>
        .jobs-monitor {
            --jm-bg: #f6f7fb;
            --jm-surface: #ffffff;
            --jm-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--jm-bg);
            padding: 1.5rem 0;
        }

        .jobs-monitor .page-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .jobs-monitor .page-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .jobs-monitor .page-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .jobs-monitor .summary-bar {
            background: var(--jm-surface);
            border: 1px solid var(--jm-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            margin-bottom: 1rem;
        }

        .jobs-monitor .table-card {
            background: var(--jm-surface);
            border: 1px solid var(--jm-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

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

    @php
        $statusClass = $workerActive ? 'text-bg-success' : 'text-bg-danger';
        $statusText = $workerActive ? 'Worker ATIVO' : 'Worker PARADO';
    @endphp

    <div class="container-fluid">
        <div class="page-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>Monitor de Fila</h2>
                <div class="meta">Acompanhamento de pendencias, execucao, falhas e historico</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Origem do monitor</div>
                <div><strong>{{ $workerSource }}</strong></div>
            </div>
        </div>

        <div class="summary-bar d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                <span class="badge text-bg-secondary">Fonte: {{ $workerSource }}</span>
            </div>
            <button id="btn-queue-restart" class="btn btn-sm btn-warning text-dark" type="button"
                title="Reiniciar workers">
                <span class="d-inline-flex align-items-center gap-1">
                    <i class="bi bi-power"></i>
                    <span>Reiniciar</span>
                    <span id="queue-restart-spinner" class="spinner-border spinner-border-sm ms-1 d-none"
                        role="status" aria-hidden="true"></span>
                </span>
            </button>
        </div>

        <div class="table-card">
            <div class="card-body">
            <div id="jobs-restart-alert" class="alert d-none mb-3"></div>
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
                                            <td class="text-end"
                                                title="{{ $job->available_at->toDateTimeString() }}">
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
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('btn-queue-restart');
                const spin = document.getElementById('queue-restart-spinner');
                const alertBox = document.getElementById('jobs-restart-alert');

                // Proteção: exige meta CSRF no layout (padrao do Laravel em app.blade)
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                async function showAlert(type, text) {
                    // type: 'success' | 'danger' | 'warning' | 'info'
                    alertBox.className = 'alert alert-' + type + ' mb-3';
                    alertBox.textContent = text;
                    // mostra
                    alertBox.classList.remove('d-none');

                    // some sozinho em 4s quando sucesso/info
                    if (['success', 'info'].includes(type)) {
                        setTimeout(() => {
                            alertBox.classList.add('d-none');
                            alertBox.textContent = '';
                        }, 4000);
                    }
                }

                btn?.addEventListener('click', async () => {
                    try {
                        if (!csrf) {
                            await showAlert('danger', 'CSRF token ausente. Verifique o layout.');
                            return;
                        }
                        // trava botão + mostra spinner
                        btn.disabled = true;
                        spin.classList.remove('d-none');

                        const res = await fetch(@json(route('config.system.restart_jobs')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            }
                        });

                        // tenta ler json, mesmo em status 4xx/5xx
                        let data = {};
                        try {
                            data = await res.json();
                        } catch (_) {}

                        if (res.ok && data?.ok) {
                            await showAlert('success', data.message ?? 'queue:restart enviado.');
                            // opcional: pedir para o Livewire dar um refresh nos dados
                            if (window.Livewire) {
                                // tenta chamar método refreshData do componente atual
                                @this.call('refreshData');
                            }
                        } else {
                            const msg = (data?.message || data?.error || 'Falha ao reiniciar a fila.') +
                                (res.status ? ` (HTTP ${res.status})` : '');
                            await showAlert('danger', msg);
                        }
                    } catch (e) {
                        await showAlert('danger', 'Erro de rede ou permissão ao chamar a rota.');
                    } finally {
                        // restaura botão/spinner
                        spin.classList.add('d-none');
                        btn.disabled = false;
                    }
                });
            });
        </script>
    @endpush

</div>
