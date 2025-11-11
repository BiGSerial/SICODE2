<div>
    {{-- Modal dentro do componente --}}
    <div class="modal fade" id="protestJobViewModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-briefcase-fill"></i>
                        Detalhes do ProtestJob
                        @if ($job)
                            <span class="badge {{ $job->status_badge_class }}">{{ $job->status_label }}</span>
                            <span class="badge {{ $job->priority_badge_class }}">{{ $job->priority_label }}</span>
                        @endif
                    </h6>



                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close"></button>
                </div>

                <div class="modal-body bg-light text-dark">
                    @if (!$job)
                        <div class="text-center text-muted py-5">Carregando…</div>
                    @else
                        <div class="row g-3">
                            {{-- ======================== COL ESQUERDA: RESUMO + OUTCOME ======================== --}}
                            <div class="col-12 col-lg-4">

                                {{-- ====== RESUMO / KPIs ====== --}}
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                                        <strong>Visão Geral</strong>
                                        <div class="d-flex gap-1">
                                            <span class="badge {{ $job->status_badge_class }}" title="Status atual">
                                                {{ $job->status_label }}
                                            </span>
                                            <span class="badge {{ $job->priority_badge_class }}" title="Prioridade">
                                                {{ $job->priority_label }}
                                            </span>
                                        </div>
                                    </div>

                                    @php
                                        $fmt = fn($dt) => optional($dt)?->format('d/m/Y H:i') ?? '—';
                                        $t0 = $job?->accepted_at ?? $job?->sent_at;
                                        $t1 = $job?->sla_due_at;
                                        $now = now();
                                        $slaProgress = null;
                                        if ($t0 && $t1) {
                                            $span = max($t1->diffInSeconds($t0), 1);
                                            $elapsed = min(max($now->diffInSeconds($t0), 0), $span);
                                            $slaProgress = intval(($elapsed / $span) * 100);
                                        }
                                        $slaDanger = filled($job?->sla_breached_at);
                                    @endphp

                                    <div class="card-body small">

                                        {{-- KPIs compactos --}}
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <div
                                                    class="p-2 rounded border bg-light d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-fill text-secondary"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="text-muted">Dono</div>
                                                        <div class="fw-semibold text-truncate"
                                                            title="{{ $job->owner?->name ?? '—' }}">
                                                            {{ $job->owner?->name ?? '—' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div
                                                    class="p-2 rounded border bg-light d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-plus-fill text-secondary"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="text-muted">Criador</div>
                                                        <div class="fw-semibold text-truncate"
                                                            title="{{ $job->creator?->name ?? '—' }}">
                                                            {{ $job->creator?->name ?? '—' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Linha do tempo essencial --}}
                                        <ul class="list-group list-group-flush rounded overflow-hidden mb-3">
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-send me-2 text-secondary"></i>Enviado</span>
                                                <span class="fw-semibold">{{ $fmt($job->sent_at) }}</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i
                                                        class="bi bi-check2-circle me-2 text-secondary"></i>Aceito</span>
                                                <span class="fw-semibold">{{ $fmt($job->accepted_at) }}</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-play-circle me-2 text-secondary"></i>Início</span>
                                                <span class="fw-semibold">{{ $fmt($job->started_at) }}</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-flag-checkered me-2 text-secondary"></i>Fim</span>
                                                <span class="fw-semibold">{{ $fmt($job->finished_at) }}</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-person-badge me-2 text-secondary"></i>Fechado
                                                    por</span>
                                                <span class="fw-semibold text-truncate"
                                                    title="{{ $job->closer?->name ?? '—' }}">
                                                    {{ $job->closer?->name ?? '—' }}
                                                </span>
                                            </li>
                                        </ul>

                                        {{-- SLA / Escalonamento --}}
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-stopwatch"></i>
                                                    <strong class="small m-0">SLA</strong>
                                                </div>
                                                <div class="small text-muted">
                                                    Vence em: <strong>{{ $fmt($job->sla_due_at) }}</strong>
                                                </div>
                                            </div>

                                            @if ($t0 && $t1)
                                                <div class="progress" role="progressbar"
                                                    aria-valuenow="{{ $slaProgress }}" aria-valuemin="0"
                                                    aria-valuemax="100" style="height: .8rem">
                                                    <div class="progress-bar {{ $slaDanger ? 'bg-danger' : ($slaProgress > 85 ? 'bg-warning' : 'bg-success') }}"
                                                        style="width: {{ $slaProgress }}%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <span class="small text-muted">Início: {{ $fmt($t0) }}</span>
                                                    <span class="small text-muted">{{ $slaProgress }}%</span>
                                                </div>
                                            @else
                                                <div class="text-muted small">Sem dados suficientes para calcular o
                                                    progresso.</div>
                                            @endif

                                            <div class="mt-2 d-flex flex-wrap gap-2">
                                                <span
                                                    class="badge {{ $job->sla_breached_at ? 'bg-danger' : 'bg-secondary' }}">
                                                    {{ $job->sla_breached_at ? 'SLA estourado em ' . $job->sla_breached_at->format('d/m/Y H:i') : 'Sem estouro' }}
                                                </span>
                                                <span
                                                    class="badge {{ $job->escalated_at ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                                    {{ $job->escalated_at ? 'Escalonado (Nível ' . $job->escalation_level . ')' : 'Não escalonado' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ====== RESULTADO (Outcome) ====== --}}
                                <div class="card border-0 shadow-sm mt-3">
                                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                                        <strong>Resultado</strong>
                                        @if (empty($outcome))
                                            <span class="badge bg-secondary">—</span>
                                        @endif
                                    </div>
                                    <div class="card-body small">
                                        @php
                                            $isAssoc = static function ($arr) {
                                                if (!is_array($arr)) {
                                                    return false;
                                                }
                                                return array_keys($arr) !== range(0, count($arr) - 1);
                                            };
                                        @endphp

                                        @if ($outcome)
                                            @if ($isAssoc($outcome))
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <tbody>
                                                            @foreach ($outcome as $k => $v)
                                                                <tr>
                                                                    <th class="text-muted" style="width: 40%">
                                                                        <i
                                                                            class="bi bi-tag me-2"></i>{{ \Illuminate\Support\Str::headline($k) }}
                                                                    </th>
                                                                    <td class="fw-semibold">
                                                                        @if (is_array($v) || is_object($v))
                                                                            <code
                                                                                class="small">{{ json_encode($v, JSON_UNESCAPED_UNICODE) }}</code>
                                                                        @else
                                                                            {{ $v === '' ? '—' : (is_bool($v) ? ($v ? 'Sim' : 'Não') : $v) }}
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="mt-2 d-flex flex-wrap gap-1">
                                                    @foreach (array_keys($outcome) as $k)
                                                        <span
                                                            class="badge text-bg-light border">{{ \Illuminate\Support\Str::headline($k) }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <ul class="mb-0">
                                                    @foreach ((array) $outcome as $item)
                                                        <li>
                                                            @if (is_array($item) || is_object($item))
                                                                <code
                                                                    class="small">{{ json_encode($item, JSON_UNESCAPED_UNICODE) }}</code>
                                                            @else
                                                                {{ $item }}
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif

                                            <details class="mt-2">
                                                <summary class="text-muted">Ver JSON bruto</summary>
                                                <pre class="mt-2 mb-0" style="white-space: pre-wrap;">{{ json_encode($outcome, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </details>
                                        @else
                                            <div class="text-muted">Sem resultado informado.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- ======================== COL DIREITA: TABS ======================== --}}
                            <div class="col-12 col-lg-8">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="tab"
                                            data-bs-target="#pj-tab-protest" type="button" role="tab">
                                            Protest / Nota
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pj-tab-med"
                                            type="button" role="tab">
                                            MedProtest
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab"
                                            data-bs-target="#pj-tab-timeline" type="button" role="tab">
                                            Timeline
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pj-tab-msg"
                                            type="button" role="tab">
                                            Mensagens
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content bg-white border border-top-0 p-3">
                                    {{-- ================== TAB: PROTEST ================== --}}
                                    <div class="tab-pane fade show active" id="pj-tab-protest" role="tabpanel">
                                        @if ($protest)
                                            <div class="row g-3">
                                                {{-- KPIs do Protest --}}
                                                <div class="col-12">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-header bg-white">
                                                            <strong>Dados do Protest</strong>
                                                        </div>
                                                        <div class="card-body small">
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-hash me-2"></i>Nota</div>
                                                                        <div class="fw-semibold">
                                                                            {{ $protest->nota ?? '—' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-card-list me-2"></i>Tipo
                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ $protest->tipoNota ?? '—' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-geo-alt me-2"></i>Cidade
                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ $protest->City?->nome ?? ($protest->cidade ?? '—') }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-calendar-event me-2"></i>Abertura
                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ optional($protest->dtAberturaNota)->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-calendar-check me-2"></i>Conclusão
                                                                            Desejada</div>
                                                                        <div class="fw-semibold">
                                                                            {{ optional($protest->dtConclusaoDesej)->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-hourglass-split me-2"></i>Data
                                                                            Final Válida</div>
                                                                        <div class="fw-semibold">
                                                                            {{ optional($protest->data_final_valida)->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <hr>
                                                            <div class="mb-2">
                                                                <div class="text-muted mb-1"><i
                                                                        class="bi bi-text-paragraph me-2"></i>Descrição
                                                                </div>
                                                                <div class="fw-semibold small">
                                                                    {{ $protest->descricao ?? '—' }}</div>
                                                            </div>
                                                            <div>
                                                                <div class="text-muted mb-1"><i
                                                                        class="bi bi-text-left me-2"></i>Resumo</div>
                                                                <div class="fw-semibold small">
                                                                    {{ $protest->resume ?? '—' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Comentários do Protest --}}
                                                <div class="col-12">
                                                    <div class="card border-0 shadow-sm">
                                                        <div
                                                            class="card-header bg-white d-flex align-items-center justify-content-between">
                                                            <strong>Comentários do Protest</strong>
                                                            <span
                                                                class="badge text-bg-light border">{{ count($commentsByOrigin['protest'] ?? []) }}</span>
                                                        </div>
                                                        <div class="card-body p-2">
                                                            @forelse($commentsByOrigin['protest'] as $c)
                                                                <div class="border rounded p-2 mb-2 bg-light-subtle">
                                                                    <div class="small text-muted">
                                                                        {{ $c['user']['name'] ?? '—' }} •
                                                                        {{ \Carbon\Carbon::parse($c['created_at'])->format('d/m/Y H:i') }}
                                                                    </div>
                                                                    <div class="small">{{ $c['message'] }}</div>
                                                                    @if (!empty($c['restrict']))
                                                                        <span
                                                                            class="badge bg-warning text-dark mt-1">Restrito</span>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <div class="text-muted small">Sem comentários.</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-muted small">Sem Protest associado.</div>
                                        @endif
                                    </div>

                                    {{-- ================== TAB: MEDPROTEST ================== --}}
                                    <div class="tab-pane fade" id="pj-tab-med" role="tabpanel">
                                        @if ($medProtest)
                                            <div class="row g-3">
                                                {{-- KPIs da MedProtest --}}
                                                <div class="col-12">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-header bg-white">
                                                            <strong>Dados da MedProtest</strong>
                                                        </div>
                                                        <div class="card-body small">
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-code-slash me-2"></i>Código
                                                                            Medida</div>
                                                                        <div class="fw-semibold">
                                                                            {{ $medProtest->codMedida ?? '—' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-ui-checks-grid me-2"></i>Status
                                                                            Sist.</div>
                                                                        <div class="fw-semibold">
                                                                            {{ $medProtest->statusSist ?? '—' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-calendar-plus me-2"></i>Criação
                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ optional($medProtest->dtCriacaoMedida)->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-calendar2-check me-2"></i>Fim
                                                                            Desejado</div>
                                                                        <div class="fw-semibold">
                                                                            {{ optional($medProtest->dtFimMedidaDesej)->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-calendar2-event me-2"></i>Fim
                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ optional($medProtest->dtFimMedida)->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="p-2 rounded border bg-light">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-check2-all me-2"></i>Completa?
                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ $medProtest->completed ? 'Sim' : 'Não' }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Comentários da MedProtest --}}
                                                <div class="col-12">
                                                    <div class="card border-0 shadow-sm">
                                                        <div
                                                            class="card-header bg-white d-flex align-items-center justify-content-between">
                                                            <strong>Comentários da MedProtest</strong>
                                                            <span
                                                                class="badge text-bg-light border">{{ count($commentsByOrigin['med'] ?? []) }}</span>
                                                        </div>
                                                        <div class="card-body p-2">
                                                            @forelse($commentsByOrigin['med'] as $c)
                                                                <div class="border rounded p-2 mb-2 bg-light-subtle">
                                                                    <div class="small text-muted">
                                                                        {{ $c['user']['name'] ?? '—' }} •
                                                                        {{ \Carbon\Carbon::parse($c['created_at'])->format('d/m/Y H:i') }}
                                                                    </div>
                                                                    <div class="small">{{ $c['message'] }}</div>
                                                                    @if (!empty($c['restrict']))
                                                                        <span
                                                                            class="badge bg-warning text-dark mt-1">Restrito</span>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <div class="text-muted small">Sem comentários.</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-muted small">Sem MedProtest associada.</div>
                                        @endif
                                    </div>

                                    {{-- ================== TAB: TIMELINE (cards semânticos) ================== --}}
                                    <div class="tab-pane fade" id="pj-tab-timeline" role="tabpanel">
                                        @forelse($timeline as $t)
                                            @if ($t['kind'] === 'event')
                                                @php($c = $t['card'])
                                                <div class="card border-0 shadow-sm mb-2">
                                                    <div
                                                        class="card-header bg-{{ $c['variant'] }} text-white d-flex align-items-center gap-2">
                                                        <i class="bi {{ $c['icon'] }}"></i>
                                                        <strong>{{ $c['title'] }}</strong>
                                                        <span
                                                            class="ms-auto small">{{ \Carbon\Carbon::parse($t['at'])->format('d/m/Y H:i') }}</span>
                                                    </div>
                                                    <div class="card-body small">
                                                        @if (!empty($c['chips']))
                                                            <div class="mb-2 d-flex flex-wrap gap-1">
                                                                @foreach ($c['chips'] as $chip)
                                                                    <span
                                                                        class="badge rounded-pill text-bg-light border">{{ $chip }}</span>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        @if (!empty($c['lines']))
                                                            <ul class="list-unstyled mb-0">
                                                                @foreach ($c['lines'] as $line)
                                                                    @if (is_array($line) && isset($line['label']))
                                                                        <li class="mb-1">
                                                                            <strong>{{ $line['label'] }}:</strong>
                                                                            {{ $line['value'] }}
                                                                        </li>
                                                                    @else
                                                                        <li class="mb-1">
                                                                            {{ is_string($line) ? $line : json_encode($line) }}
                                                                        </li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @endif

                                                        @if (!empty($c['raw']))
                                                            <details class="mt-2">
                                                                <summary class="text-muted">Ver JSON</summary>
                                                                <pre class="mt-2 mb-0" style="white-space: pre-wrap;">{{ json_encode($c['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            </details>
                                                        @endif
                                                    </div>
                                                    <div class="card-footer bg-light text-muted small">
                                                        {{ $c['subtitle'] }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="card border-0 shadow-sm mb-2">
                                                    <div
                                                        class="card-header bg-secondary text-white d-flex align-items-center gap-2">
                                                        <i class="bi bi-chat-left-text-fill"></i>
                                                        <strong>Comentário • {{ $t['origin'] }}</strong>
                                                        <span
                                                            class="ms-auto">{{ \Carbon\Carbon::parse($t['at'])->format('d/m/Y H:i') }}</span>
                                                    </div>
                                                    <div class="card-body small">
                                                        <div class="text-muted mb-1">{{ $t['who'] }}</div>
                                                        <div>{{ $t['text'] }}</div>
                                                        @if (!empty($t['restrict']))
                                                            <span
                                                                class="badge bg-warning text-dark mt-2">Restrito</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @empty
                                            <div class="text-muted small">Sem registros.</div>
                                        @endforelse
                                    </div>

                                    {{-- ================== TAB: MENSAGENS ================== --}}
                                    <div class="tab-pane fade" id="pj-tab-msg" role="tabpanel">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-4">
                                                <label class="form-label small">Destino</label>
                                                <select class="form-select form-select-sm" wire:model="messageTarget">
                                                    <option value="job">ProtestJob</option>
                                                    <option value="med" @disabled(!$medProtest)>MedProtest
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <label class="form-label small">Mensagem</label>
                                                <textarea class="form-control form-control-sm" rows="3" wire:model.defer="newMessage"
                                                    placeholder="Escreva sua mensagem…"></textarea>
                                            </div>
                                            <div class="col-12 d-flex align-items-center justify-content-between">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="pjv-chkRestrict" wire:model="restrict">
                                                    <label class="form-check-label small" for="pjv-chkRestrict">
                                                        Restrita (contratados não veem)
                                                    </label>
                                                </div>
                                                <button class="btn btn-sm btn-primary" wire:click="sendMessage"
                                                    wire:loading.attr="disabled" wire:target="sendMessage">
                                                    Enviar
                                                </button>
                                            </div>
                                        </div>

                                        <hr>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <h6 class="small mb-0">Comentários no ProtestJob</h6>
                                                    <span
                                                        class="badge text-bg-light border">{{ count($commentsByOrigin['job'] ?? []) }}</span>
                                                </div>
                                                @forelse($commentsByOrigin['job'] as $c)
                                                    <div class="border rounded p-2 mb-2 bg-light-subtle small">
                                                        <div class="text-muted">
                                                            {{ $c['user']['name'] ?? '—' }} •
                                                            {{ \Carbon\Carbon::parse($c['created_at'])->format('d/m/Y H:i') }}
                                                        </div>
                                                        <div>{{ $c['message'] }}</div>
                                                        @if (!empty($c['restrict']))
                                                            <span
                                                                class="badge bg-warning text-dark mt-1">Restrito</span>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div class="text-muted small">Sem comentários.</div>
                                                @endforelse
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <h6 class="small mb-0">Comentários na MedProtest</h6>
                                                    <span
                                                        class="badge text-bg-light border">{{ count($commentsByOrigin['med'] ?? []) }}</span>
                                                </div>
                                                @forelse($commentsByOrigin['med'] as $c)
                                                    <div class="border rounded p-2 mb-2 bg-light-subtle small">
                                                        <div class="text-muted">
                                                            {{ $c['user']['name'] ?? '—' }} •
                                                            {{ \Carbon\Carbon::parse($c['created_at'])->format('d/m/Y H:i') }}
                                                        </div>
                                                        <div>{{ $c['message'] }}</div>
                                                        @if (!empty($c['restrict']))
                                                            <span
                                                                class="badge bg-warning text-dark mt-1">Restrito</span>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div class="text-muted small">Sem comentários.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- /col dir --}}
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light border-0">
                    @if ($job)
                        <div class="d-flex align-items-center gap-1 me-1">
                            {{-- Reescalar --}}
                            <button class="btn btn-sm btn-warning" title="Reescalar" wire:click="askEscalate"
                                @disabled(!$this->canEscalate)>
                                <i class="bi bi-arrow-up-right-circle"></i>
                            </button>

                            {{-- Reabrir --}}
                            <button class="btn btn-sm btn-outline-success" title="Reabrir" wire:click="askReopen"
                                @disabled(!$this->canReopen)>
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>

                            {{-- Cancelar --}}
                            <button class="btn btn-sm btn-outline-danger" title="Cancelar" wire:click="askCancel"
                                @disabled(!$this->canCancel)>
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    @endif
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal"
                        wire:click="close">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bridge: controlar o modal Bootstrap deste componente --}}
    <script>
        document.addEventListener('livewire:load', () => {
            const el = document.getElementById('protestJobViewModal');
            if (!el) return;

            const modal = new bootstrap.Modal(el);

            window.addEventListener('protestjob-view:show', () => modal.show());
            window.addEventListener('protestjob-view:hide', () => modal.hide());

            // Fecha modal ao destruir (navegação/refresh de lista)
            document.addEventListener('turbo:before-cache', () => modal.hide?.());
        });
    </script>
</div>
