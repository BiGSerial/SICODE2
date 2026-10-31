{{-- Painel de lista de jobs (pendentes, em execução, agendados): mesmo contrato, só muda a coluna de tempo.
     Variáveis: $list, $kind, $title, $sub, $timeLabel, $empty, $retryAfter --}}
@php($rows = $list['rows'])

<div class="mp-panel h-100">
    <div class="mp-panel-head">
        <div>
            <h3 class="mp-panel-title">{{ $title }}</h3>
            <div class="mp-panel-sub">{{ $sub }}</div>
        </div>
        <span class="mp-pill" title="Total na fila">{{ $list['total'] }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 mp-table jobs-table">
            <colgroup>
                <col class="jobs-col-id">
                <col class="jobs-col-queue">
                <col>
                <col class="jobs-col-num">
                <col class="jobs-col-time">
            </colgroup>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fila</th>
                    <th>Job</th>
                    <th class="text-end">Tent.</th>
                    <th class="text-end">{{ $timeLabel }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $job)
                    <tr wire:key="{{ $kind }}-job-{{ $job->id }}">
                        <td class="text-muted">#{{ $job->id }}</td>
                        <td><span class="mp-chip">{{ $job->queue }}</span></td>
                        <td>
                            <div class="fw-semibold" title="{{ $job->name }}">{{ $job->short }}</div>
                            <button class="btn btn-link btn-sm p-0 jobs-payload-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#payload-{{ $kind }}-{{ $job->id }}">
                                <i class="ri-code-s-slash-line" aria-hidden="true"></i> payload
                            </button>
                            <div class="collapse" id="payload-{{ $kind }}-{{ $job->id }}" wire:ignore.self>
                                <pre class="jobs-payload">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </td>
                        <td class="text-end">{{ $job->attempts }}</td>
                        <td class="text-end" title="{{ $job->at->format('d/m/Y H:i:s') }}">
                            {{ $kind === 'delayed' ? 'em ' : '' }}{{ \App\Services\System\QueueMonitorService::human($job->seconds) }}
                            @if ($job->long)
                                <span class="badge text-bg-warning" title="Reservado há mais que o retry_after ({{ $retryAfter }} s): pode ser devolvido à fila">longo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ $empty }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($list['total'] > $rows->count())
        <div class="mp-panel-foot small text-muted">Mostrando {{ $rows->count() }} de {{ $list['total'] }}</div>
    @endif
</div>
