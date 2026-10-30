@php
    $s       = $snapshot['summary'];
    $filters = $snapshot['filters'];
    $daily   = $snapshot['daily'];
    $buckets = $snapshot['age_buckets'];
    $hasOpen = $s['open'] > 0;
    $hasData = $hasOpen || $s['entered'] > 0 || $s['closed'] > 0 || $s['rejected'] > 0;
    $period  = $filters['from']->format('d/m/Y') . ' a ' . $filters['to']->format('d/m/Y');
    $money   = fn (?float $v) => $v === null ? '—' : 'R$ ' . number_format($v, 2, ',', '.');
    $dec     = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
    $days    = fn (?int $d) => $d === null ? '—' : ($d === 0 ? 'hoje' : $d . ' ' . ($d === 1 ? 'dia' : 'dias'));
    $dates   = array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $daily['dates']);
    $maxAge  = max(1, collect($snapshot['oldest'])->max('age_days') ?? 1);

    $engMax  = max(1, collect($snapshot['by_engineer'])->max('total') ?? 1);
    $engRows = collect($snapshot['by_engineer'])->take(10)->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => (string) $r['total'],
        'pct'   => $r['total'] / $engMax * 100,
        'tone'  => $r['late'] > 0 ? ($r['late'] / max(1, $r['total']) >= .5 ? 'red' : 'amber') : 'blue',
        'sub'   => $r['late'] . ' acima de ' . \App\Services\Engineers\ViabilityDashboardAnalytics::LATE_DAYS . ' dias · mais antiga há ' . $r['oldest'] . ' d',
    ])->all();

    $reasonMax  = max(1, collect($snapshot['reasons'])->max('total') ?? 1);
    $reasonRows = collect($snapshot['reasons'])->take(10)->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => $r['total'] . ' · ' . round($r['total'] / max(1, $s['rejected']) * 100) . '%',
        'pct'   => $r['total'] / $reasonMax * 100,
        'tone'  => 'amber',
    ])->all();
@endphp

<div class="closure-dash my-panel">

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Projetos · Viabilidade</div>
            <h1 class="mp-hero-title">Painel de viabilidade</h1>
            <p class="mp-hero-sub">O que está com a Engenharia agora, quanto foi fechado no período e como cada empresa está entregando.</p>
        </div>
        <div class="mp-hero-actions">
            <a href="{{ route('engineers.viab_list') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-run-line"></i> Em viabilidade</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel com uma aba por bloco, nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small mb-1" for="vd-company">Empreiteira</label>
                <select id="vd-company" class="form-select form-select-sm" wire:model="company">
                    <option value="">Todas as empreiteiras</option>
                    @foreach ($snapshot['options']['companies'] as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-5">
                <span class="form-label small d-block mb-1">Período (entradas, fechamentos e rejeições)</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (\App\Http\Livewire\Engineers\ViabilityDashboard::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label small mb-1" for="vd-from">De</label>
                <input id="vd-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_in" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label small mb-1" for="vd-to">Até</label>
                <input id="vd-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_out" max="{{ now()->toDateString() }}">
            </div>
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Período exibido: <strong>{{ $period }}</strong> ({{ $filters['days'] }} {{ $filters['days'] === 1 ? 'dia' : 'dias' }}). "Em viabilidade" mostra sempre a situação de agora.
            @if ($company)
                <button type="button" class="btn btn-link btn-sm p-0 ms-2" wire:click="clearFilters">limpar empresa</button>
            @endif
        </div>
    </section>

    @if (!$hasData)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-radar-line" title="Nenhuma viabilidade nos filtros atuais" text="Amplie o período ou troque a empreiteira." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="company,setPreset,dt_in,dt_out">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-run-line" label="Em viabilidade agora" :value="$s['open']"
                    :note="$s['open_late'] . ' acima de ' . \App\Services\Engineers\ViabilityDashboardAnalytics::LATE_DAYS . ' dias · mais antiga ' . ($s['oldest'] === null ? '—' : $days($s['oldest']))"
                    :values="$buckets['values']" :labels="$buckets['labels']" :ticks="5" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-check-double-line" label="Realizadas no período" :value="$s['realized']"
                    :note="$money($s['realized_value']) . ' · ' . ($s['realization'] === null ? 'sem fechamentos' : $dec($s['realization']) . '% de realização')"
                    :values="$daily['closed']" :labels="$dates" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-error-warning-line" label="Não realizadas (tácitas)" :value="$s['not_realized']"
                    :note="$money($s['not_realized_value']) . ' · multa estimada ' . $money($s['penalty'])" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-timer-line" label="Tempo médio de retorno" :value="$s['sla_days'] === null ? '—' : $dec($s['sla_days']) . ' d'"
                    :note="$s['entered'] . ' entraram · ' . $s['rejected'] . ' rejeitadas no período · ' . $s['sla_count'] . ' no cálculo'" />
            </div>
        </div>

        <h2 class="mp-section-title">Fila de agora</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100" aria-labelledby="vd-oldest-title">
                    <div class="mp-panel-head">
                        <div>
                            <h3 id="vd-oldest-title" class="mp-panel-title">Mais antigas em viabilidade</h3>
                            <div class="mp-panel-sub">As {{ \App\Services\Engineers\ViabilityDashboardAnalytics::OLDEST_LIMIT }} mais antigas; a fila completa está em Em viabilidade e no Excel.</div>
                        </div>
                        <span class="mp-pill">{{ $s['open'] }} {{ $s['open'] === 1 ? 'aberta' : 'abertas' }} · {{ $money($s['open_value']) }}</span>
                    </div>
                    @if (!$hasOpen)
                        <x-closure.empty-state icon="ri-checkbox-circle-line" title="Nenhuma viabilidade em aberto" text="Tudo o que estava com a Engenharia foi atendido." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col class="col-pct-5"><col class="col-pct-12"><col class="col-pct-22"><col class="col-pct-18"><col class="col-pct-12"><col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center">#</th>
                                        <th scope="col">Nota</th>
                                        <th scope="col">Empreiteira</th>
                                        <th scope="col">Engenheiro</th>
                                        <th scope="col">Enviada</th>
                                        <th scope="col">Tempo em aberto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($snapshot['oldest'] as $row)
                                        <tr wire:key="vd-old-{{ $row['id'] }}">
                                            <td class="text-center"><span class="mp-rank">{{ $loop->iteration }}</span></td>
                                            <td class="fw-bold">{{ $row['note'] }}<div class="small text-muted text-truncate">{{ $row['city'] }}</div></td>
                                            <td class="text-truncate" title="{{ $row['company'] }}">{{ $row['company'] ?? '—' }}</td>
                                            <td class="text-truncate" title="{{ $row['engineer'] }}">{{ $row['engineer'] ?? '—' }}</td>
                                            <td>{{ $row['sended_at']?->format('d/m/Y') }}</td>
                                            <td>
                                                <div class="mp-age">
                                                    <span class="mp-age-bar" aria-hidden="true"><span style="--pct: {{ max(2, $row['age_days'] / $maxAge * 100) }}"></span></span>
                                                    <strong>{{ $days($row['age_days']) }}</strong>
                                                    @if ($row['late']) <span class="badge text-bg-danger">fora do prazo</span> @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mp-panel-foot">
                            <a href="{{ route('engineers.viab_list') }}" class="row-action"><i class="ri-arrow-right-line"></i> Abrir a fila completa</a>
                        </div>
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Carga por engenheiro</h3><div class="mp-panel-sub">Em aberto agora; vermelho = metade ou mais fora do prazo</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$engRows" caption="Viabilidades em aberto por engenheiro" :headers="['Engenheiro', 'Em aberto', 'Detalhe']" empty="Nenhuma viabilidade em aberto" />
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Período</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Entradas x fechadas por dia</h3><div class="mp-panel-sub">{{ $period }}</div></div><span class="mp-pill">{{ $s['entered'] }} entradas · {{ $s['closed'] }} fechadas</span></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="flow" data-config="{{ json_encode($charts['flow']) }}" role="img" aria-label="Gráfico de linhas: viabilidades que entraram e que foram fechadas por dia"></canvas>
                        </div>
                        @if ($s['entered'] === 0 && $s['closed'] === 0)
                            <div class="mp-chart-empty">Nada entrou nem foi fechado neste período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Resultado do período</h3><div class="mp-panel-sub">Realizadas, não realizadas e rejeitadas</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="outcome" data-config="{{ json_encode($charts['outcome']) }}" role="img" aria-label="Rosca: viabilidades do período por resultado"></canvas>
                        </div>
                        @if ($s['realized'] + $s['not_realized'] + $s['rejected'] === 0)
                            <div class="mp-chart-empty">Sem resultados no período</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Há quanto tempo estão abertas?</h3><div class="mp-panel-sub">Em viabilidade agora, por faixa de dias</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="age" data-config="{{ json_encode($charts['age']) }}" role="img" aria-label="Gráfico de barras: viabilidades em aberto por faixa de dias"></canvas>
                        </div>
                        @if (!$hasOpen)
                            <div class="mp-chart-empty">Nenhuma viabilidade em aberto</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Motivos de rejeição</h3><div class="mp-panel-sub">{{ $s['rejected'] }} {{ $s['rejected'] === 1 ? 'rejeição' : 'rejeições' }} no período · top 10</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$reasonRows" caption="Rejeições por motivo" :headers="['Motivo', 'Rejeições', '']" empty="Nenhuma rejeição no período" />
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Produtividade por empresa</h2>

        <section class="mp-panel mb-4" aria-labelledby="vd-company-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="vd-company-title" class="mp-panel-title">Como cada empresa está entregando</h3>
                    <div class="mp-panel-sub">Entradas e rejeições do período; fechamentos pela data de retorno; "Em viabilidade" é a situação de agora.</div>
                </div>
                <span class="mp-pill">{{ count($snapshot['by_company']) }} {{ count($snapshot['by_company']) === 1 ? 'empresa' : 'empresas' }}</span>
            </div>
            @if (count($snapshot['by_company']) === 0)
                <x-closure.empty-state icon="ri-building-2-line" title="Sem movimento no período" text="Nenhuma empresa enviou ou fechou viabilidades nos filtros atuais." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-24"><col class="col-pct-9"><col class="col-pct-10"><col class="col-pct-10"><col class="col-pct-9"><col class="col-pct-10"><col class="col-pct-9"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Empreiteira</th>
                                <th scope="col" class="text-end">Entradas</th>
                                <th scope="col" class="text-end">Realizadas</th>
                                <th scope="col" class="text-end">Não realizadas</th>
                                <th scope="col" class="text-end">Rejeitadas</th>
                                <th scope="col" class="text-end">Em viabilidade</th>
                                <th scope="col" class="text-end">% realização</th>
                                <th scope="col" class="text-end">Retorno médio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($snapshot['by_company'] as $row)
                                <tr wire:key="vd-co-{{ $loop->index }}">
                                    <td class="fw-bold text-truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['entered'] }}</td>
                                    <td class="text-end text-success fw-bold">{{ $row['realized'] }}</td>
                                    <td class="text-end {{ $row['not_realized'] ? 'text-danger fw-bold' : '' }}">{{ $row['not_realized'] }}</td>
                                    <td class="text-end">{{ $row['rejected'] }}</td>
                                    <td class="text-end">{{ $row['in_progress'] }}</td>
                                    <td class="text-end">
                                        @if ($row['rate'] === null)
                                            —
                                        @else
                                            <span class="badge text-bg-{{ $row['rate'] >= 90 ? 'success' : ($row['rate'] >= 70 ? 'warning' : 'danger') }}">{{ $dec($row['rate']) }}%</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $row['sla_days'] === null ? '—' : $dec($row['sla_days']) . ' d' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
