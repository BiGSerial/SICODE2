<div>
    <style>
        .avatar-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .result-highlight {
            border-width: 2px;
            border-style: solid;
            border-radius: .75rem;
            padding: .65rem .8rem;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .result-highlight.is-procedente {
            background: #eafaf1;
            border-color: #198754;
            color: #146c43;
        }

        .result-highlight.is-improcedente {
            background: #fdecec;
            border-color: #dc3545;
            color: #b02a37;
        }

        .result-highlight.is-empty {
            background: #f1f3f5;
            border-color: #6c757d;
            color: #495057;
        }

        .confirm-result-card {
            border: 2px solid #0d6efd;
            border-radius: .8rem;
            background: #f8fbff;
            padding: .9rem;
            min-width: 360px;
        }
    </style>

    @php
        if (!function_exists('reduceName')) {
            function reduceName($name, bool $first = false)
            {
                $partName = explode(' ', trim($name));
                if (count($partName) === 0) {
                    return '';
                }

                if (count($partName) === 1) {
                    return $name;
                }

                if ($first) {
                    return $partName[0];
                }

                return $partName[0] . ' ' . end($partName);
            }
        }
    @endphp
    {{-- Modal dentro do componente --}}
    <x-show-loading />
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
                                                            {{ reduceName($job->owner?->name) ?? '—' }}
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
                                                            {{ reduceName($job->creator?->name) ?? '—' }}
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
                                                <span><i class="bi bi-flag-fill me-2 text-secondary"></i>Fim</span>
                                                <span class="fw-semibold">{{ $fmt($job->finished_at) }}</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-person-badge me-2 text-secondary"></i>Fechado
                                                    por</span>
                                                <span class="fw-semibold text-truncate"
                                                    title="{{ $job->closer?->name ?? '—' }}">
                                                    {{ reduceName($job->closer?->name) ?? '—' }}
                                                </span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i
                                                        class="bi bi-check-circle me-2 text-secondary"></i>Confirmado</span>
                                                <span class="fw-semibold text-truncate"
                                                    title="{{ $job->confirmed_at?->format('d/m/Y H:i') ?? '—' }}">
                                                    {{ $job->confirmed_at?->format('d/m/Y H:i') ?? '—' }}
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
                                    <div
                                        class="card-header  d-flex align-items-center justify-content-between {{ $job->status->value == 'done' ? 'text-bg-success shadow' : 'bg-white' }}">
                                        <strong>Resultado</strong>

                                    </div>
                                    <div class="card-body small">
                                        @php
                                            $activeResult = $result ?? $medProtest?->result;
                                            $resultClass = $activeResult === 'procedente'
                                                ? 'is-procedente'
                                                : ($activeResult === 'improcedente' ? 'is-improcedente' : 'is-empty');
                                        @endphp

                                        <div class="result-highlight {{ $resultClass }} mb-3">
                                            <span>Resultado da medida</span>
                                            <span>{{ strtoupper($activeResult ?? 'nao informada') }}</span>
                                        </div>

                                        <div class="text-muted mb-2">{{ $job->close_reason ?? 'Sem resultado informado.' }}
                                        </div>

                                    </div>

                                </div>
                            </div>

                            {{-- ======================== COL DIREITA: TABS ======================== --}}
                            <div class="col-12 col-lg-8">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $tabIndex === 0 ? 'active' : '' }}"
                                            data-bs-toggle="tab" data-bs-target="#pj-tab-protest" type="button"
                                            role="tab" wire:click.prevent="$set('tabIndex', 0)">
                                            Nota
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $tabIndex === 1 ? 'active' : '' }}"
                                            data-bs-toggle="tab" data-bs-target="#pj-tab-med" type="button"
                                            role="tab" wire:click.prevent="$set('tabIndex', 1)">
                                            Medida
                                        </button>
                                    </li>
                                    <li class="nav-item {{ $tabIndex === 2 ? 'active' : '' }}" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab"
                                            data-bs-target="#pj-tab-timeline" type="button" role="tab"
                                            wire:click.prevent="$set('tabIndex', 2)">
                                            Timeline
                                        </button>
                                    </li>
                                    <li class="nav-item {{ $tabIndex === 3 ? 'active' : '' }}" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pj-tab-msg"
                                            type="button" role="tab" wire:click.prevent="$set('tabIndex', 3)">
                                            Mensagens
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content bg-white border border-top-0 p-3">
                                    {{-- ================== TAB: PROTEST ================== --}}
                                    <div class="tab-pane fade {{ $tabIndex === 0 ? 'show active' : '' }}"
                                        id="pj-tab-protest" role="tabpanel">
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
                                    <div class="tab-pane fade {{ $tabIndex === 1 ? 'show active' : '' }}"
                                        id="pj-tab-med" role="tabpanel">
                                        @if ($medProtest)
                                            <div class="row g-3">
                                                {{-- KPIs da MedProtest --}}
                                                <div class="col-12">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-header bg-white">
                                                            <strong>Dados da Medida #{{ $medProtest->med_id }}</strong>
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
                                                                <div class="col-12">
                                                                    <div class="p-2 rounded border bg-white shadow-sm">
                                                                        <div class="text-muted"><i
                                                                                class="bi bi-info-circle me-2"></i>Instrução
                                                                            ao usuario

                                                                        </div>
                                                                        <div class="fw-semibold">
                                                                            {{ $job->notes ?? null }}
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
                                    <div class="tab-pane fade {{ $tabIndex === 2 ? 'show active' : '' }}"
                                        id="pj-tab-timeline" role="tabpanel">
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
                                    <div class="tab-pane fade {{ $tabIndex === 3 ? 'show active' : '' }}"
                                        id="pj-tab-msg" role="tabpanel">

                                        @if ($medProtest)
                                            <div class="row g-2" x-data x-init="$wire.set('messageTarget', 'med')">
                                                <div class="col-12">
                                                    <label class="form-label small fw-bold">Nova mensagem para
                                                        MedProtest
                                                        #{{ $medProtest->med_id }}</label>
                                                    <textarea class="form-control form-control-sm" rows="3" wire:model.defer="newMessage"
                                                        placeholder="Escreva sua mensagem para a medida…"></textarea>
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
                                        @else
                                            <div class="alert alert-warning small">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Não há MedProtest vinculada para enviar mensagens.
                                            </div>
                                        @endif

                                        <hr>

                                        <div class="row">
                                            <div class="col-12">
                                                <div
                                                    class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                                    <h6 class="small mb-0 fw-bold">Histórico de Mensagens (MedProtest)
                                                    </h6>
                                                    <span
                                                        class="badge text-bg-light border">{{ count($commentsByOrigin['med'] ?? []) }}</span>
                                                </div>

                                                @forelse($commentsByOrigin['med'] as $c)
                                                    <div class="d-flex gap-3 mb-4">
                                                        {{-- Avatar --}}
                                                        <div class="flex-shrink-0">
                                                            <div class="avatar-circle"
                                                                title="{{ $c['user']['name'] ?? 'Usuário' }}">
                                                                <img src="{{ $c['user']['avatar_url'] ?? asset('images/default-avatar.png') }}"
                                                                    alt="Avatar de {{ $c['user']['name'] ?? 'Usuário' }}">
                                                            </div>
                                                        </div>

                                                        {{-- Conteúdo --}}
                                                        <div class="flex-grow-1">
                                                            <div class="card border-0 bg-light-subtle shadow-sm">
                                                                <div class="card-body p-2">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center mb-1">
                                                                        <strong
                                                                            class="small">{{ $c['user']['name'] ?? '—' }}</strong>
                                                                        <span
                                                                            class="small text-muted">{{ \Carbon\Carbon::parse($c['created_at'])->format('d/m/Y H:i') }}</span>
                                                                    </div>
                                                                    <div class="small text-break">
                                                                        {!! nl2br(e($c['message'])) !!}
                                                                    </div>
                                                                    @if (!empty($c['restrict']))
                                                                        <span
                                                                            class="badge bg-warning text-dark mt-1">Restrito</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted small py-3">Sem mensagens na
                                                        MedProtest.</div>
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

                <div class="modal-footer bg-light border-0 d-flex justify-content-between">
                    @if ($job && !$job->confirmed)
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if ($showConfirmCard && $job->status->value === 'done')
                                <div class="confirm-result-card me-2">
                                    <div class="fw-semibold mb-2">Confirmar conclusão da atividade</div>
                                    <div class="small text-muted mb-2">Escolha a procedência e confirme.</div>

                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button"
                                            class="btn btn-sm flex-fill {{ $result === 'procedente' ? 'btn-success' : 'btn-outline-success' }}"
                                            wire:click="$set('result', 'procedente')">
                                            Procedente
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm flex-fill {{ $result === 'improcedente' ? 'btn-danger' : 'btn-outline-danger' }}"
                                            wire:click="$set('result', 'improcedente')">
                                            Improcedente
                                        </button>
                                    </div>
                                    @error('result')
                                        <small class="text-danger d-block mb-2">{{ $message }}</small>
                                    @enderror

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm flex-fill"
                                            wire:click="doConfirm" @disabled(!$result)>
                                            Confirmar produção
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            wire:click="cancelConfirmCard">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($job->status->value === 'done')
                                <span
                                    class="badge {{ ($result ?? $medProtest?->result) === 'procedente' ? 'bg-success' : (($result ?? $medProtest?->result) === 'improcedente' ? 'bg-danger' : 'bg-secondary') }}">
                                    Procedência: {{ strtoupper($result ?? $medProtest?->result ?? 'nao informada') }}
                                </span>
                            @endif

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

                            {{-- Confirmar --}}
                            <button class="btn btn-sm btn-outline-success" title="Confirmar" wire:click="askConfirm"
                                @disabled($job->status->value !== 'done')>
                                <i class="bi bi-check-circle"></i>
                            </button>

                            {{-- Cancelar --}}
                            <button class="btn btn-sm btn-outline-danger" title="Cancelar" wire:click="askCancel"
                                @disabled(!$this->canCancel)>
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    @else
                        <div></div>
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
