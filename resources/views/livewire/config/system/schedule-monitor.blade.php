@php use Carbon\Carbon; @endphp

<div class="schedule-monitor" wire:poll.30000ms="refreshData">
    <style>
        .schedule-monitor {
            --sm-bg: #f6f7fb;
            --sm-surface: #ffffff;
            --sm-border: #e5e7eb;
            --sm-ink: #1f2937;
            --sm-muted: #6b7280;
            background: var(--sm-bg);
            padding: 1.5rem 0;
        }

        .schedule-monitor .page-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: .8rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
            margin-bottom: 1rem;
        }

        .schedule-monitor .page-header h2 {
            font-weight: 700;
            letter-spacing: 0;
            margin: 0;
        }

        .schedule-monitor .page-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: .92rem;
        }

        .schedule-monitor .panel {
            background: var(--sm-surface);
            border: 1px solid var(--sm-border);
            border-radius: .75rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .schedule-monitor .panel > .card-header {
            padding: 1rem 1.15rem;
        }

        .schedule-monitor .panel .list-group-item {
            padding: .85rem 1.15rem;
        }

        .schedule-monitor .timeline {
            max-height: 780px;
            overflow-y: auto;
        }

        .schedule-monitor .timeline-item {
            display: grid;
            grid-template-columns: 62px 1fr;
            gap: .75rem;
            padding: .8rem 1rem;
            border-bottom: 1px solid var(--sm-border);
        }

        .schedule-monitor .timeline-time {
            font-weight: 700;
            color: #0f766e;
            font-variant-numeric: tabular-nums;
        }

        .schedule-monitor .command-name {
            font-weight: 650;
            color: var(--sm-ink);
            overflow-wrap: anywhere;
        }

        .schedule-monitor .command-detail {
            color: var(--sm-muted);
            font-size: .82rem;
        }

        .schedule-monitor .table-wrap {
            overflow-x: auto;
        }

        .schedule-monitor .table-wrap table {
            min-width: 940px;
        }

        .schedule-monitor .status-dot {
            width: .65rem;
            height: .65rem;
            border-radius: 50%;
            display: inline-block;
        }

        .schedule-monitor .refresh-indicator {
            width: 1rem;
            height: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .schedule-monitor .refresh-indicator .spinner-border {
            width: .85rem;
            height: .85rem;
            border-width: .12rem;
        }

        .schedule-monitor .agenda-table {
            table-layout: fixed;
            min-width: 0 !important;
            width: 100%;
            font-size: .78rem;
        }

        .schedule-monitor .agenda-table th,
        .schedule-monitor .agenda-table td {
            padding-left: .35rem;
            padding-right: .35rem;
        }

        .schedule-monitor .agenda-table .col-time {
            width: 52px;
        }

        .schedule-monitor .agenda-table .col-command {
            width: 116px;
        }

        .schedule-monitor .agenda-table .col-next {
            width: 54px;
        }

        .schedule-monitor .agenda-table .col-status {
            width: 64px;
        }

        .schedule-monitor .agenda-table .col-action {
            width: 38px;
        }

        .schedule-monitor .agenda-command {
            max-width: 116px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        .schedule-monitor .agenda-next {
            max-width: 54px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .schedule-monitor .schedule-countdown {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            padding: .16rem .32rem;
            border-radius: .35rem;
            background: #e0f2fe;
            color: #075985;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            line-height: 1.15;
        }

        .schedule-monitor .schedule-countdown.is-soon {
            background: #fef3c7;
            color: #92400e;
        }

        .schedule-monitor .schedule-countdown.is-due {
            background: #fee2e2;
            color: #991b1b;
        }

        .schedule-monitor .agenda-table .badge {
            max-width: 62px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .schedule-monitor .running-command-title {
            overflow-wrap: anywhere;
        }

        .schedule-monitor .pid-badge {
            font-variant-numeric: tabular-nums;
        }
    </style>

    @php
        $active = (bool) ($supervisor['active'] ?? false);
        $statusClass = $active ? 'text-bg-success' : 'text-bg-danger';
        $statusText = $active ? 'SCHEDULE ATIVO' : 'SCHEDULE PARADO';
    @endphp

    <div class="container-fluid">
        <div class="page-header d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
            <div>
                <h2>Monitor do Schedule</h2>
                <div class="meta">Programacao real do Laravel Scheduler, execucoes em andamento e historico recente</div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                <span class="badge text-bg-secondary">Fonte: {{ $supervisor['source'] ?? 'N/A' }}</span>
                @can('superadm')
                    <button class="btn btn-sm btn-warning text-dark" type="button"
                        wire:click="restartScheduleSupervisor" wire:loading.attr="disabled"
                        wire:target="restartScheduleSupervisor">
                        <i class="bi bi-power me-1"></i>
                        Reiniciar Schedule
                        <span class="spinner-border spinner-border-sm ms-1" wire:loading
                            wire:target="restartScheduleSupervisor" aria-hidden="true"></span>
                    </button>
                @endcan
            </div>
        </div>

        @if ($restartMessage)
            <div class="alert alert-{{ $restartStatus }} py-2">
                {{ $restartMessage }}
            </div>
        @endif

        @if ($forceMessage)
            <div class="alert alert-{{ $forceStatus }} py-2">
                {{ $forceMessage }}
            </div>
        @endif

        @if ($stopMessage)
            <div class="alert alert-{{ $stopStatus }} py-2">
                {{ $stopMessage }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="panel mb-3" wire:poll.2000ms.visible="refreshRunningCommands">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Em execucao agora</strong>
                            <div class="small text-muted">Somente comandos agendados em execucao</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="refresh-indicator text-muted">
                                <span class="spinner-border" wire:loading wire:target="refreshRunningCommands" aria-hidden="true"></span>
                            </span>
                            <span class="badge text-bg-warning">{{ count($runningCommands ?? []) }}</span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($runningCommands as $run)
                            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between gap-2"
                                wire:key="running-command-{{ $run['source'] }}-{{ $run['id'] }}">
                                <div class="running-command-title">
                                    <span class="badge text-bg-warning me-2">RUNNING</span>
                                    @if (!empty($run['pid']))
                                        <span class="badge text-bg-secondary pid-badge me-2">PID {{ $run['pid'] }}</span>
                                    @endif
                                    <span class="fw-semibold">{{ $run['command'] }}</span>
                                    @if (!empty($run['command_detail']) && $run['command_detail'] !== $run['command'])
                                        <div class="small text-muted mt-1 ps-md-5">
                                            {{ $run['command_detail'] }}
                                        </div>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center justify-content-md-end gap-2">
                                    <div class="small text-muted text-md-end">
                                        @if (!empty($run['started_at']))
                                            inicio: {{ Carbon::parse($run['started_at'])->format('d/m/Y H:i:s') }} |
                                        @endif
                                        rodando ha {{ $run['elapsed'] }}
                                    </div>
                                    @can('superadm')
                                        @if (!empty($run['can_stop']) && !empty($run['pid']))
                                            @php
                                                $stopAction = !empty($run['log_id'])
                                                    ? "stopRunningCommand('{$run['log_id']}', '{$run['pid']}')"
                                                    : "stopDetectedProcess('{$run['pid']}')";
                                            @endphp
                                            <button class="btn btn-sm btn-outline-danger" type="button"
                                                wire:click="{{ $stopAction }}"
                                                wire:loading.attr="disabled"
                                                wire:target="{{ $stopAction }}"
                                                title="Parar processo">
                                                <i class="bi bi-stop-fill"></i>
                                                <span class="visually-hidden">Parar</span>
                                                <span class="spinner-border spinner-border-sm ms-1" wire:loading
                                                    wire:target="{{ $stopAction }}"
                                                    aria-hidden="true"></span>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                Nenhum comando em execucao no momento.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="panel">
                    <div class="card-header bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                        <div>
                            <strong>Historico recente</strong>
                            <div class="small text-muted">Inicio, fim e resultado oficial dos eventos do Scheduler</div>
                        </div>
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                            <input type="search"
                                class="form-control form-control-sm"
                                style="min-width: 220px"
                                placeholder="Buscar processo, PID ou status"
                                wire:model.debounce.500ms="recentSearch">
                            <select class="form-select form-select-sm" style="width: 86px" wire:model="recentPerPage">
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="refresh-indicator text-muted">
                                <span class="spinner-border" wire:loading wire:target="refreshData" aria-hidden="true"></span>
                            </span>
                            <span class="badge text-bg-secondary">
                                {{ method_exists($recentLogs, 'total') ? $recentLogs->total() : count($recentLogs ?? []) }}
                            </span>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarefa</th>
                                    <th>Status</th>
                                    <th>PID</th>
                                    <th>Previsto</th>
                                    <th>Inicio</th>
                                    <th>Fim</th>
                                    <th>Duracao</th>
                                    <th>Cron</th>
                                    <th class="text-end">Exit</th>
                                    <th class="text-end">Erros</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLogs as $log)
                                    @php
                                        $status = strtoupper((string) ($log['status'] ?? 'DONE'));
                                        $badge = match ($status) {
                                            'RUNNING' => 'text-bg-warning',
                                            'FAIL' => 'text-bg-danger',
                                            'SKIPPED' => 'text-bg-secondary',
                                            default => 'text-bg-success',
                                        };
                                    @endphp
                                    <tr wire:key="recent-log-{{ $log['id'] }}" title="{{ $log['fail_reason'] ?? '' }}">
                                        <td class="fw-semibold">{{ $log['task'] }}</td>
                                        <td><span class="badge {{ $badge }}">{{ $status }}</span></td>
                                        <td class="pid-badge">{{ $log['process_id'] ?? 'N/A' }}</td>
                                        <td>{{ !empty($log['scheduled_at']) ? Carbon::parse($log['scheduled_at'])->format('d/m H:i:s') : 'N/A' }}</td>
                                        <td>{{ !empty($log['started_at']) ? Carbon::parse($log['started_at'])->format('d/m H:i:s') : 'N/A' }}</td>
                                        <td>{{ !empty($log['finished_at']) ? Carbon::parse($log['finished_at'])->format('d/m H:i:s') : 'N/A' }}</td>
                                        <td>{{ $log['duration'] }}</td>
                                        <td>{{ $log['expression'] ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $log['exit_code'] ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $log['errors'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">Nenhum log encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (method_exists($recentLogs, 'links') && $recentLogs->hasPages())
                        <div class="px-3 py-2 border-top bg-white">
                            {{ $recentLogs->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="panel mb-3">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Agenda de hoje</strong>
                            <div class="small text-muted">Proximas execucoes do dia</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="refresh-indicator text-muted">
                                <span class="spinner-border" wire:loading wire:target="refreshData" aria-hidden="true"></span>
                            </span>
                            <span class="badge text-bg-primary">{{ count($scheduledEvents ?? []) }}</span>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0 agenda-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="col-time">Hora</th>
                                    <th class="col-command">Nome</th>
                                    <th class="col-next">Próx.</th>
                                    <th class="col-status">Últ.</th>
                                    @can('superadm')
                                        <th class="text-end col-action">Ação</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($scheduledEvents as $event)
                                    @php
                                        $last = $event['last_log'] ?? null;
                                        $lastStatus = strtoupper((string) ($last['status'] ?? 'SEM LOG'));
                                        $badge = match ($lastStatus) {
                                            'RUNNING' => 'text-bg-warning',
                                            'FAIL' => 'text-bg-danger',
                                            'DONE' => 'text-bg-success',
                                            'SKIPPED' => 'text-bg-secondary',
                                            default => 'text-bg-light text-dark',
                                        };
                                    @endphp
                                    <tr wire:key="schedule-side-event-{{ $event['id'] }}">
                                        <td class="fw-bold text-success">{{ $event['next_time'] }}</td>
                                        <td class="fw-semibold">
                                            <div class="agenda-command" title="{{ $event['command_label'] }}">
                                                {{ $event['label'] }}
                                            </div>
                                            @if (count($event['commands']) > 1)
                                                <div class="small text-muted">sequencial</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="schedule-countdown"
                                                data-next-run="{{ $event['next_run_iso'] }}"
                                                title="em {{ $event['due_in'] }}">
                                                ...
                                            </span>
                                        </td>
                                        <td><span class="badge {{ $badge }}">{{ $lastStatus }}</span></td>
                                        @can('superadm')
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary" type="button"
                                                    wire:click="forceScheduledEvent('{{ $event['event_hash'] }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="forceScheduledEvent('{{ $event['event_hash'] }}')"
                                                    title="Executar agora">
                                                    <i class="bi bi-play-fill"></i>
                                                    <span class="visually-hidden">Forcar</span>
                                                    <span class="spinner-border spinner-border-sm ms-1" wire:loading
                                                        wire:target="forceScheduledEvent('{{ $event['event_hash'] }}')"
                                                        aria-hidden="true"></span>
                                                </button>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="@can('superadm') 5 @else 4 @endcan" class="text-center text-muted py-4">Nenhum evento restante hoje.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <div class="card-header bg-white">
                        <strong>SupervisorD</strong>
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item small">
                            <strong>Programa:</strong> {{ $supervisor['program'] ?? 'N/A' }}
                        </div>
                        @forelse (($supervisor['lines'] ?? []) as $line)
                            <div class="list-group-item small text-muted">{{ $line }}</div>
                        @empty
                            <div class="list-group-item small text-muted">
                                Nenhuma linha do supervisorctl foi vinculada ao schedule.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        (() => {
            const root = document.currentScript.closest('.schedule-monitor');

            if (!root || root.dataset.countdownBound === '1') {
                return;
            }

            root.dataset.countdownBound = '1';

            const formatRemaining = (seconds) => {
                if (seconds <= 0) {
                    return 'agora';
                }

                if (seconds < 60) {
                    return `${seconds}s`;
                }

                const minutes = Math.floor(seconds / 60);
                const rest = seconds % 60;

                if (minutes < 60) {
                    return `${minutes}m ${String(rest).padStart(2, '0')}s`;
                }

                const hours = Math.floor(minutes / 60);
                const minuteRest = minutes % 60;

                return `${hours}h ${String(minuteRest).padStart(2, '0')}m`;
            };

            const tick = () => {
                root.querySelectorAll('.schedule-countdown[data-next-run]').forEach((el) => {
                    const target = new Date(el.dataset.nextRun).getTime();
                    const seconds = Math.max(0, Math.ceil((target - Date.now()) / 1000));

                    el.textContent = formatRemaining(seconds);
                    el.classList.toggle('is-soon', seconds > 0 && seconds <= 60);
                    el.classList.toggle('is-due', seconds <= 0);
                });
            };

            tick();
            setInterval(tick, 1000);

            document.addEventListener('livewire:update', tick);
        })();
    </script>
</div>
