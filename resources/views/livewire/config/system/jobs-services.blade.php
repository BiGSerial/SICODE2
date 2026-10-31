@php
    use App\Services\System\QueueMonitorService as Monitor;

    $t       = $snap['totals'];
    $ws      = $snap['worker_summary'];
    $failed  = $snap['failed'];
    $hasFail = $failed['total'] > 0;
    $workerLabel = $ws['total'] !== null ? $ws['running'] . ' de ' . $ws['total'] . ' ativos' : ($ws['active'] ? 'ativo' : 'parado');
    $retryAfter  = $snap['retry_after'];
    $failedByType = collect($snap['failed_by_type'])->map(fn ($r) => [
        'label' => $r['name'],
        'text'  => (string) $r['qty'],
        'pct'   => $r['qty'] / max(1, collect($snap['failed_by_type'])->max('qty')) * 100,
        'tone'  => 'red',
    ])->all();
@endphp

<div class="closure-dash my-panel jobs-monitor" wire:poll.5s>

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Sistema · Fila de jobs</div>
            <h1 class="mp-hero-title">Monitor de Fila</h1>
            <p class="mp-hero-sub">
                O que está esperando, rodando, agendado e falhado na fila. Atualiza sozinho a cada 5 s
                (última leitura às {{ $snap['now']->format('H:i:s') }}).
            </p>
        </div>
        <div class="mp-hero-actions d-flex flex-wrap align-items-center gap-2">
            <span class="badge {{ $ws['active'] ? 'text-bg-success' : 'text-bg-danger' }}" title="Fonte da detecção: {{ $ws['source'] }}">
                Workers: {{ $workerLabel }}
            </span>
            @can('superadm')
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="restartAll" wire:loading.attr="disabled" wire:target="restartAll" title="Envia queue:restart: cada worker reinicia ao terminar o job atual">
                    <i class="ri-restart-line"></i> Reiniciar todos
                </button>
            @endcan
        </div>
    </header>

    {{-- Avisos --}}
    @if (session()->has('message'))
        <div class="alert alert-success d-flex align-items-center gap-2" role="status"><i class="ri-checkbox-circle-line" aria-hidden="true"></i> {{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i> {{ session('error') }}</div>
    @endif
    @if (!$ws['active'])
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="ri-alarm-warning-line" aria-hidden="true"></i>
            Nenhum worker ativo: os jobs pendentes não serão processados até um worker subir.
        </div>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="retryAllFailed, deleteAllFailed, restartAll, restartWorker, restartJob, deleteFailed">

        {{-- Resumo --}}
        <h2 class="mp-section-title">Situação agora</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-time-line" label="Pendentes" :value="$t['pending']"
                    :note="$t['pending'] ? 'Espera mais longa: ' . Monitor::human((int) $t['oldest_wait']) : 'Nada esperando'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-play-circle-line" label="Em execução" :value="$t['running']"
                    :note="$t['long'] ? $t['long'] . ' há mais de ' . Monitor::human($retryAfter) : 'Todos dentro do prazo'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-calendar-schedule-line" label="Agendados" :value="$t['delayed']"
                    note="Disponíveis em data futura" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-error-warning-line" label="Falhados" :value="$t['failed']"
                    :note="$hasFail ? 'Precisam de ação' : 'Nenhuma falha'" />
            </div>
        </div>

        {{-- Filas e workers --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-5">
                <div class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Filas</h3>
                            <div class="mp-panel-sub">Contagem real por fila, não só dos itens listados abaixo</div>
                        </div>
                        <span class="mp-pill">{{ count($snap['queues']) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 mp-table">
                            <colgroup>
                                <col>
                                <col class="jobs-col-num">
                                <col class="jobs-col-num">
                                <col class="jobs-col-num">
                                <col class="jobs-col-num">
                                <col class="jobs-col-wait">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th class="text-end">Pend.</th>
                                    <th class="text-end">Exec.</th>
                                    <th class="text-end">Agend.</th>
                                    <th class="text-end">Falh.</th>
                                    <th class="text-end">Espera</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snap['queues'] as $q)
                                    <tr wire:key="queue-{{ $q['queue'] }}">
                                        <td><span class="mp-chip">{{ $q['queue'] }}</span></td>
                                        <td class="text-end">{{ $q['pending'] }}</td>
                                        <td class="text-end">{{ $q['running'] }}</td>
                                        <td class="text-end">{{ $q['delayed'] }}</td>
                                        <td class="text-end {{ $q['failed'] ? 'text-danger fw-bold' : '' }}">{{ $q['failed'] }}</td>
                                        <td class="text-end text-muted">{{ $q['pending'] ? Monitor::human($q['oldest_wait']) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhum job em nenhuma fila.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Workers do Supervisor</h3>
                            <div class="mp-panel-sub">Processos que consomem as filas. Reinicie um só quando ele estiver travado</div>
                        </div>
                        <span class="mp-pill">{{ $ws['total'] !== null ? $ws['running'] . '/' . $ws['total'] : count($snap['workers']) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 mp-table">
                            <colgroup>
                                <col>
                                <col class="jobs-col-status">
                                <col class="jobs-col-id">
                                <col class="jobs-col-wait">
                                <col class="jobs-col-action">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Status</th>
                                    <th>PID</th>
                                    <th>Ativo há</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snap['workers'] as $w)
                                    <tr wire:key="worker-{{ md5($w['name']) }}">
                                        <td class="fw-semibold" title="{{ $w['name'] }}">{{ $w['process'] }}</td>
                                        <td>
                                            <span class="badge {{ $w['running'] ? 'text-bg-success' : 'text-bg-danger' }}" title="{{ $w['description'] }}">
                                                {{ $w['running'] ? 'Rodando' : ($w['status'] === 'STOPPED' ? 'Parado' : $w['status']) }}
                                            </span>
                                        </td>
                                        <td class="text-muted">{{ $w['pid'] ?? '—' }}</td>
                                        <td class="text-muted">{{ $w['uptime'] ?? '—' }}</td>
                                        <td class="text-end">
                                            @can('superadm')
                                                <button class="btn btn-sm btn-outline-warning btn-dash" type="button" wire:click="restartWorker(@js($w['name']))" wire:loading.attr="disabled" wire:target="restartWorker" title="Reiniciar {{ $w['process'] }}">
                                                    <i class="ri-restart-line"></i> Reiniciar
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Supervisor não disponível neste ambiente (fonte: {{ $ws['source'] }}).</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Falhados --}}
        <h2 class="mp-section-title">Falhados</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <div class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Jobs que falharam</h3>
                            <div class="mp-panel-sub">Reenfileirar tenta de novo; excluir descarta o registro para sempre</div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                            <span class="mp-pill">{{ $failed['total'] }}</span>
                            @if ($hasFail)
                                <button class="btn btn-sm btn-outline-primary btn-dash" type="button" wire:click="retryAllFailed" wire:loading.attr="disabled" wire:target="retryAllFailed">
                                    <i class="ri-refresh-line"></i> Reenfileirar tudo
                                </button>
                                @can('superadm')
                                    @if ($confirmingDeleteAll)
                                        <span class="small fw-bold text-danger">Excluir {{ $failed['total'] }} falhado(s)?</span>
                                        <button class="btn btn-sm btn-danger btn-dash" type="button" wire:click="deleteAllFailed" wire:loading.attr="disabled" wire:target="deleteAllFailed">
                                            <i class="ri-delete-bin-line"></i> Sim, excluir tudo
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary btn-dash" type="button" wire:click="cancelDeleteAllFailed">Cancelar</button>
                                    @else
                                        <button class="btn btn-sm btn-outline-danger btn-dash" type="button" wire:click="askDeleteAllFailed">
                                            <i class="ri-delete-bin-line"></i> Excluir tudo
                                        </button>
                                    @endif
                                @endcan
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 mp-table jobs-table">
                            <colgroup>
                                <col class="jobs-col-id">
                                <col class="jobs-col-queue">
                                <col>
                                <col class="jobs-col-time">
                                <col class="jobs-col-action-2">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fila</th>
                                    <th>Job e erro</th>
                                    <th class="text-end">Falhou há</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($failed['rows'] as $job)
                                    <tr wire:key="failed-job-{{ $job->id }}">
                                        <td class="text-muted">#{{ $job->id }}</td>
                                        <td><span class="mp-chip">{{ $job->queue }}</span></td>
                                        <td>
                                            <div class="fw-semibold" title="{{ $job->name }}">{{ $job->short }}</div>
                                            <div class="small text-danger jobs-clamp" title="{{ $job->error }}">
                                                @if ($job->error_class)<strong>{{ $job->error_class }}:</strong>@endif {{ $job->error }}
                                            </div>
                                            <button class="btn btn-link btn-sm p-0 jobs-payload-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#payload-failed-{{ $job->id }}">
                                                <i class="ri-code-s-slash-line" aria-hidden="true"></i> payload
                                            </button>
                                            <div class="collapse" id="payload-failed-{{ $job->id }}" wire:ignore.self>
                                                <pre class="jobs-payload">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        </td>
                                        <td class="text-end" title="{{ $job->failed_at->format('d/m/Y H:i:s') }}">
                                            {{ Monitor::human($snap['now']->diffInSeconds($job->failed_at)) }}
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" type="button" wire:click="restartJob({{ $job->id }})" title="Reenfileirar este job"><i class="ri-refresh-line"></i></button>
                                                <button class="btn btn-outline-danger" type="button" wire:click="deleteFailed({{ $job->id }})" title="Excluir este registro"><i class="ri-delete-bin-line"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum job falhado.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($failed['total'] > $failed['rows']->count())
                        <div class="mp-panel-foot small text-muted">Mostrando os {{ $failed['rows']->count() }} mais recentes de {{ $failed['total'] }}. "Reenfileirar tudo" e "Excluir tudo" valem para todos.</div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Falhas por tipo de job</h3>
                            <div class="mp-panel-sub">Entre os {{ min($failed['total'], 500) }} mais recentes</div>
                        </div>
                    </div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$failedByType" caption="Falhas por tipo de job" :headers="['Job', 'Falhas', '']" empty="Nenhuma falha registrada." />
                    </div>
                </div>
            </div>
        </div>

        {{-- Listas --}}
        <h2 class="mp-section-title">Na fila</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                @include('livewire.config.system.partials.jobs-table', ['list' => $snap['pending'], 'kind' => 'pending', 'title' => 'Pendentes', 'sub' => 'Prontos, esperando um worker (mais antigos primeiro)', 'timeLabel' => 'Esperando há', 'empty' => 'Sem jobs pendentes.'])
            </div>
            <div class="col-12 col-xl-6">
                @include('livewire.config.system.partials.jobs-table', ['list' => $snap['running'], 'kind' => 'running', 'title' => 'Em execução', 'sub' => 'Reservados por um worker neste momento', 'timeLabel' => 'Rodando há', 'empty' => 'Nenhum job em execução.'])
            </div>
            <div class="col-12">
                @include('livewire.config.system.partials.jobs-table', ['list' => $snap['delayed'], 'kind' => 'delayed', 'title' => 'Agendados', 'sub' => 'Só ficam disponíveis na data indicada (não são atraso)', 'timeLabel' => 'Disponível', 'empty' => 'Sem jobs agendados.'])
            </div>
        </div>

        {{-- Histórico --}}
        <h2 class="mp-section-title">Histórico</h2>
        <div class="mp-panel">
            <div class="mp-panel-head">
                <div>
                    <h3 class="mp-panel-title">Finalizados com sucesso</h3>
                    <div class="mp-panel-sub">Últimas {{ Monitor::LIST_LIMIT }} execuções registradas</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 mp-table">
                    <colgroup>
                        <col class="jobs-col-id">
                        <col class="jobs-col-queue">
                        <col>
                        <col class="jobs-col-num">
                        <col class="jobs-col-time">
                        <col class="jobs-col-time">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fila</th>
                            <th>Job</th>
                            <th class="text-end">Tent.</th>
                            <th class="text-end">Duração</th>
                            <th class="text-end">Finalizou há</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($snap['succeeded']['rows'] as $row)
                            <tr wire:key="succeeded-job-{{ $row->id }}">
                                <td class="text-muted">#{{ $row->id }}</td>
                                <td><span class="mp-chip">{{ $row->queue }}</span></td>
                                <td class="fw-semibold" title="{{ $row->name }}">{{ $row->short }}</td>
                                <td class="text-end">{{ $row->attempts }}</td>
                                <td class="text-end">{{ Monitor::human((int) round($row->runtime_ms / 1000)) }}</td>
                                <td class="text-end" title="{{ $row->finished_at->format('d/m/Y H:i:s') }}">{{ Monitor::human($snap['now']->diffInSeconds($row->finished_at)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    {{ $snap['succeeded']['enabled'] ? 'Nenhuma execução registrada ainda.' : 'Histórico de sucessos não habilitado neste ambiente (tabela queue_job_history ausente).' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
