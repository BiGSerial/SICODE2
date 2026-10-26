<div class="closure-dash">
    @include('livewire.closure._styles')

    @php
        $fmtDur = function (?\Carbon\Carbon $from, ?\Carbon\Carbon $to = null): string {
            if (!$from) {
                return '—';
            }
            $min = max(0, (int) $from->diffInMinutes($to ?? now()));

            return $min >= 1440 ? intdiv($min, 1440) . 'd ' . intdiv($min % 1440, 60) . 'h' : intdiv($min, 60) . 'h ' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT) . 'm';
        };
        $tokens      = preg_split('/\s+/', trim($status), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hasToken    = fn (array $prefixes) => collect($tokens)->contains(fn ($t) => collect($prefixes)->contains(fn ($p) => str_starts_with(strtoupper($t), $p)));
        $sapFlags    = [
            'ABER'   => ['Aberta', $hasToken(['ABER']), '#f59e0b'],
            'LIB'    => ['Liberada', $hasToken(['LIB']), '#0f8a77'],
            'ENC'    => ['Encerrada', $hasToken(['ENCE', 'ENTE', 'ENC']), '#263cc8'],
            'BLOQ'   => ['Bloqueada', $hasToken(['BLOQ']), '#c0392b'],
            'CANCEL' => ['Cancelada', $hasToken(['CANC']) || (bool) $order->canceled, '#64748b'],
        ];
        $city        = $order->Note?->City;
        $ops         = $order->Operations ?? collect();
        $opsConf     = $ops->filter(fn ($op) => str_starts_with(strtoupper((string) $op->status), 'CONF'))->count();
        $cycle       = $target?->Cycle;
        $daysIn      = $cycle ? (int) $cycle->startDate()->diffInDays(now()) : null;
        $cycleLen    = $cycle ? (int) $cycle->startDate()->daysInMonth : 30;
        $overdue     = $daysIn !== null && $daysIn > $cycleLen;
        $pctDays     = $daysIn !== null ? min(100, (int) round($daysIn / max(1, $cycleLen) * 100)) : 0;
        $openPeriods = $state ? $state->Periods->sortByDesc('started_at') : collect();
        $totalMin    = $openPeriods->sum(fn ($p) => $p->started_at ? max(0, (int) $p->started_at->diffInMinutes($p->ended_at ?? now())) : 0);
        $events      = $state ? $state->Events->sortByDesc('created_at')->values() : collect();
    @endphp

    <div class="od-crumbs">
        <nav aria-label="breadcrumb"><a href="{{ url('/home') }}"><i class="ri-home-4-line"></i> Home</a> <span class="text-muted">/</span> <a href="{{ \App\Services\Closure\ClosureNavContext::homeUrl($ctx) }}">Encerramento</a> <span class="text-muted">/</span> <strong>Ordem {{ $order->ordem }} ({{ $order->id }})</strong></nav>
        <a href="{{ \App\Services\Closure\ClosureNavContext::homeUrl($ctx) }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line"></i> Voltar para a lista</a>
    </div>

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <h2 class="dash-title mb-0">Ordem {{ $order->ordem }}</h2>
                    <span class="od-chip">ID local: {{ $order->id }}</span>
                </div>
                <div class="od-hero-meta">
                    {!! \App\Services\Closure\ClosureMetaClassifier::badge($metaType) !!}
                    <span>Nota/OV {{ $order->Note?->note ?? '—' }}</span>
                    <span>Competência {{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($competence) }}</span>
                    @if ($city?->municipio ?? $order->Note?->lexp)
                        <span><i class="ri-map-pin-2-line"></i> {{ $city?->municipio ?? $order->Note?->lexp }}</span>
                    @endif
                </div>
                @if ($order->descricao)
                    <div class="od-hero-meta" style="opacity:.8;">{{ $order->descricao }}</div>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-start gap-2">
                @if ($order->note_id)
                    <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => $ctx]) }}" class="btn btn-sm btn-light"><i class="ri-tools-line"></i> Gestão da Obra</a>
                @endif
                <span class="badge {{ $isClosed ? 'bg-success' : ($situation === 'PASSIVO' ? 'bg-danger' : 'bg-warning text-dark') }} align-self-start px-3 py-2">{{ $situation }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="od-card od-top" style="--accent: {{ $isClosed ? 'var(--dash-green)' : 'var(--dash-amber)' }};">
                <div class="od-label"><span>SAP</span><i class="ri-database-2-line"></i></div>
                <div class="od-big">{{ $isClosed ? 'Encerrada' : 'Em aberto' }} <span class="od-dot" style="background: {{ $isClosed ? 'var(--dash-green)' : '#f59e0b' }};"></span></div>
                <div class="small text-muted mt-2 mb-1">Flags do sistema</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach ($sapFlags as $code => [$flagLabel, $on, $color])
                        <span class="od-flag {{ $on ? 'is-on' : 'is-off' }}" style="--fc: {{ $color }};" title="{{ $flagLabel }}{{ $on ? ' — acionada' : ' — não acionada' }}">@if ($on)<i class="ri-checkbox-circle-fill"></i> @endif{{ $code }}</span>
                    @endforeach
                </div>
                @if ($status !== '')<div class="small text-muted mt-2 text-truncate" title="{{ $status }}">Status completo: {{ $status }}</div>@endif
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="od-card od-top" style="--accent: var(--dash-teal);">
                <div class="od-label"><span>Tratativa operacional</span><i class="ri-refresh-line"></i></div>
                <div class="od-big d-flex align-items-center gap-2 flex-wrap">{{ $state && $canViewOperational ? \App\Enum\ClosureOperationalStatus::labelFor($state->operational_status) : 'Não despachada' }}
                    @if ($state && $canViewOperational && !in_array($state->operational_status, ['CLOSED', 'CANCELLED'], true))<span class="od-pill">Fila ativa</span>@endif
                </div>
                <div class="small text-muted mt-2">Acompanhamento operacional da Ordem, separado do SAP corporativo.@if ($ops->count()) Operações: <strong>{{ $opsConf }}/{{ $ops->count() }}</strong> confirmadas.@endif</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="od-card od-top" style="--accent: var(--dash-blue);">
                <div class="od-label"><span>Meta original</span><i class="ri-calendar-event-line"></i></div>
                <div class="od-big">{{ $cycle?->label ?? '—' }}</div>
                @if ($cycle)
                    <div class="progress mt-3" style="height: 6px;"><div class="progress-bar {{ $overdue ? 'bg-danger' : 'bg-success' }}" style="width: {{ $pctDays }}%;"></div></div>
                    <div class="d-flex justify-content-between small mt-1"><span class="{{ $overdue ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $daysIn }} dias desde a entrada</span>@if ($overdue)<span class="text-danger fw-bold">Excedido</span>@endif</div>
                @else
                    <div class="small text-muted mt-2">Ainda não entrou em meta</div>
                @endif
            </div>
        </div>
    </div>

    @if ($depositSignal)
        <div class="d-flex flex-wrap align-items-center gap-3 p-3 mb-3 rounded" style="background:#fff5f5;border:1px solid #f5c2c2;" role="alert">
            <i class="ri-lock-line text-danger" style="font-size:1.6rem;"></i>
            <div class="flex-grow-1">
                <div class="fw-bold">Depósito {{ $depositSignal->deposit_code }}@if ($depositSignal->deposit_label) · {{ $depositSignal->deposit_label }}@endif bloqueado</div>
                <div class="small">Desde {{ $depositSignal->created_at?->format('d/m/Y H:i') }}{{ $depositSignal->origin === 'CALENDAR' ? ' · inventário da gestão de ativos' : '' }}. A Ordem volta ao status anterior quando o depósito for liberado. {{ $depositSignal->reason }}</div>
            </div>
            @if ($depositSignal->work_centers)<div class="d-flex gap-1">@foreach ($depositSignal->work_centers as $code)<span class="co-code">{{ $code }}</span>@endforeach</div>@endif
        </div>
    @endif

    @if ($state && $canViewOperational)
        <div class="od-card mb-3">
            <div class="od-section-head"><h3><i class="ri-user-location-line text-muted"></i> Posse atual da Ordem</h3></div>
            <div class="row g-3 px-3 pb-3">
                <div class="col-12 col-md-4"><div class="od-tile"><span class="od-tile-icon"><i class="ri-user-line"></i></span><div><small>Detentor</small><strong>{{ $state->CurrentHolderUser?->name ?? $state->CurrentHolderCompany?->name ?? 'Não informado' }}</strong>@if ($state->CurrentHolderUser && $state->CurrentHolderCompany)<span class="small text-muted">{{ $state->CurrentHolderCompany->name }}</span>@endif</div></div></div>
                <div class="col-12 col-md-4"><div class="od-tile"><span class="od-tile-icon"><i class="ri-time-line"></i></span><div><small>Desde quando</small><strong>{{ $state->entered_current_status_at?->format('d/m/Y H:i') ?? 'Não informado' }}</strong></div></div></div>
                <div class="col-12 col-md-4"><div class="od-tile"><span class="od-tile-icon"><i class="ri-hourglass-line"></i></span><div><small>Tempo em posse</small><strong>{{ $fmtDur($state->entered_current_status_at) }} @if (!in_array($state->operational_status, ['CLOSED', 'CANCELLED'], true))<span class="od-live">Ativo agora</span>@endif</strong></div></div></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <div class="od-card h-100">
                    <div class="od-section-head"><div><h3><i class="ri-line-chart-line text-muted"></i> Linha do tempo</h3><div class="small text-muted mt-1">Eventos registrados para esta Ordem.</div></div><span class="od-count">{{ $events->count() }} eventos</span></div>
                    <div class="table-responsive">
                        <table class="table od-table align-middle">
                            <thead><tr><th>Quando</th><th>Evento</th><th>Responsável</th></tr></thead>
                            <tbody>
                                @forelse ($events as $event)
                                    <tr>
                                        <td class="text-nowrap">@if ($loop->first)<span class="od-dot"></span>@endif{{ $event->created_at?->format('d/m/Y H:i') }}</td>
                                        <td><span class="od-ev"><i class="{{ \App\Services\Closure\ClosureEventLabels::icon($event->type) }}"></i>{{ \App\Services\Closure\ClosureEventLabels::label($event->type) }}</span></td>
                                        <td>{{ $event->Actor?->name ?? 'Sistema' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Ainda não há eventos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="od-card h-100">
                    <div class="od-section-head"><div><h3><i class="ri-history-line text-muted"></i> Períodos de posse</h3><div class="small text-muted mt-1">Onde a Ordem ficou e por quanto tempo.</div></div><span class="od-count">{{ $openPeriods->count() }} registros</span></div>
                    <div class="table-responsive">
                        <table class="table od-table align-middle">
                            <thead><tr><th>Posse</th><th>Início</th><th>Fim</th><th>Duração</th></tr></thead>
                            <tbody>
                                @forelse ($openPeriods as $period)
                                    <tr>
                                        <td>@if (!$period->ended_at)<span class="od-dot"></span>@endif{{ $period->HolderUser?->name ?? $period->HolderCompany?->name ?? 'Não informado' }}</td>
                                        <td class="text-nowrap">{{ $period->started_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-nowrap">@if ($period->ended_at){{ $period->ended_at->format('d/m/Y H:i') }}@else<span class="od-live">Em curso</span>@endif</td>
                                        <td class="fw-semibold text-nowrap">{{ $fmtDur($period->started_at, $period->ended_at) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Ainda não há períodos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($openPeriods->count())
                        <div class="px-3 py-2 border-top small text-muted d-flex justify-content-between"><span>Tempo total computado em posse:</span><strong class="text-dark">{{ $fmtDur(now()->subMinutes($totalMin)) }}</strong></div>
                    @endif
                </div>
            </div>
        </div>
    @elseif ($state)
        <div class="alert alert-secondary">A situação operacional desta Ordem está disponível apenas para o detentor atual ou para a empresa responsável.</div>
    @else
        <div class="alert alert-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Esta Ordem ainda não foi despachada: a tratativa começa no despacho (ou na auto-atribuição).</span>
            @if ($canDispatch)
                <button type="button" class="btn btn-sm btn-primary"
                    onclick="window.dispatchEvent(new CustomEvent('closure-dispatch-open', { detail: { rows: [{ id: {{ $order->id }}, ordem: @js($order->ordem), note: @js($order->Note?->note), type: @js($metaType), comp: @js(\App\Services\Closure\ClosureMetaClassifier::competenceLabel($competence)) }] } }))">Despachar</button>
            @endif
        </div>
    @endif

    <div class="od-card mb-3">
        <div class="od-section-head"><h3><i class="ri-information-line text-muted"></i> Dados de complemento da Ordem e obra</h3></div>
        <div class="row g-3 px-3 pb-3">
            @foreach ([
                ['Município', $city?->municipio ?? $order->Note?->lexp],
                ['Regional', $city?->regional],
                ['Região', $city?->regiao],
                ['Base de construção', $city?->baseConstrucao],
                ['Local de instalação', $order->locInstalacao],
                ['Centro de trabalho', $order->cenTrab],
            ] as [$label, $value])
                <div class="col-6 col-md-4 col-xl-2"><div class="od-info"><small>{{ $label }}</small><strong>{{ $value ?: '—' }}</strong></div></div>
            @endforeach
        </div>
    </div>

    @if ($target?->is_exception)
        <div class="alert alert-warning mt-3 mb-0 small">
            <strong>Caso atípico — entrada por exceção.</strong> Justificativa: {{ $target->exception_reason }}.
        </div>
    @endif

    @if ($canDispatch)
        @livewire('closure.dispatch.dispatch-modal')
        <div x-data x-on:hidden.bs.modal.window="if ($event.target.id === 'closure_dispatch_modal') { $wire.$refresh() }"></div>
    @endif
</div>
