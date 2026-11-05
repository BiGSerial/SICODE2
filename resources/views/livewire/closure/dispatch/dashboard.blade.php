@php
    use App\Enum\ClosureOperationalStatus as OpStatus;
    use App\Services\Closure\Analytics\{ClosureManagementAnalytics as Mgmt, ClosureManagementCharts as Charts};

    $f           = $management['filters'];
    $cmp         = $management['comparator'];
    $sum         = $management['summary'];
    $problems    = $management['problems'];
    $partners    = $management['partners'];
    $activities  = $management['activities'];
    $period      = $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y');
    $n           = fn ($v) => number_format((int) $v, 0, ',', '.');
    $dec         = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.');
    $daysText    = fn ($v) => $v === null ? '—' : $dec($v) . ' d';
    $pct         = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.') . '%';
    $duration    = function (?int $seconds): string {
        if ($seconds === null) {
            return '—';
        }

        return $seconds < 60 ? '< 1 min' : \Carbon\CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 2, 'short' => true, 'join' => ' ']);
    };
    $typeTones   = ['P' => 'red', 'M' => 'green', 'F' => 'blue', 'N' => 'muted'];
    $statusCount = count($statuses);
    $hasData     = $matrix['totals']['total'] > 0;
    $cellActive  = fn ($user, $type, $status) => $cell['user'] === $user && $cell['type'] === $type && $cell['status'] === $status;
    $overallText = ['better' => 'Melhoramos', 'worse' => 'Pioramos', 'stable' => 'Estável', 'none' => 'Sem dados'][$cmp['overall']];
    $overallTone = ['better' => 'green', 'worse' => 'red', 'stable' => 'amber', 'none' => 'blue'][$cmp['overall']];
    $sourceTone  = fn (string $source) => match (true) {
        str_contains($source, 'Parceiras')    => 'blue',
        str_contains($source, 'Medição')      => 'teal',
        str_contains($source, 'Fiscalização') => 'amber',
        str_contains($source, 'RI')           => 'red',
        default                               => 'muted',
    };
    $arrow       = fn (?float $delta) => $delta === null || $delta == 0 ? 'ri-subtract-line' : ($delta > 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line');
    $movers      = collect($cmp['rows'])->filter(fn ($r) => in_array($r['verdict']['key'], ['better', 'worse'], true));
    $moverRows   = fn (string $key) => $movers->where('verdict.key', $key)->sortByDesc(fn ($r) => abs($r['verdict']['percent']))->take(5)
        ->map(fn ($r) => ['label' => $r['label'], 'text' => ($r['verdict']['percent'] > 0 ? '+' : '') . number_format($r['verdict']['percent'], 0, ',', '.') . '%', 'pct' => min(100, abs($r['verdict']['percent'])), 'tone' => $key === 'better' ? 'green' : 'red', 'sub' => $r['hint']])->values()->all();
    $maxProblem  = max(1, collect($problems['ranking'])->max('total') ?? 1);
    $problemRows = collect($problems['ranking'])->take(Charts::TOP)->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => $n($r['total']),
        'pct'   => $r['total'] / $maxProblem * 100,
        'tone'  => $sourceTone($r['source']),
        'sub'   => $r['source'] . ' · ' . $n($r['open']) . ' em aberto' . (!empty($r['avg_days']) ? ' · resposta média ' . $dec($r['avg_days']) . ' dias' : '') . (!empty($r['affected']) ? ' · ' . $n($r['affected']) . ' Ordens afetadas' : ''),
    ])->values()->all();
    $bySource    = collect($problems['ranking'])->groupBy('source')->map(fn ($g) => ['total' => $g->sum('total'), 'open' => $g->sum('open')])->sortByDesc('total');
    $custom      = $periodFrom !== now()->startOfMonth()->toDateString() || $periodTo !== now()->endOfMonth()->toDateString() || $filterType !== '' || $filterCompany || $dateFrom || $dateTo;
@endphp

{{-- Painel analítico do Encerramento. Padrão: docs/PADRAO_DASHBOARDS.md; regras de cálculo: docs/encerramento/PAINEL_GERENCIAL.md. --}}
<div class="closure-dash my-panel ops-list">

    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Gestão do ciclo · Encerramento</div>
            <h1 class="mp-hero-title">Painel analítico do Encerramento</h1>
            <p class="mp-hero-sub">Quais são os principais problemas, quem os causa (parceira e atividade) e se estamos melhorando ou piorando em relação ao mês anterior.</p>
        </div>
        <div class="mp-hero-actions">
            <span class="mp-hero-sub me-2">Atualizado em {{ $management['updated_at']->format('d/m/Y H:i') }}</span>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel com uma aba por bloco do painel e as atividades detalhadas por status, nos filtros atuais"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.stack') }}" class="btn btn-outline-light btn-sm btn-dash"><i class="ri-list-check-2" aria-hidden="true"></i> Lista de controle</a>
        </div>
    </header>

    @if ($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert" id="closure-dash-feedback">{{ $feedbackMessage }}</div>
    @endif

    @include('livewire.closure.dispatch._transfer-receipt')

    <section class="mp-filters filter-bar" aria-label="Período do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-xl">
                <span class="form-label small d-block mb-1">Período dos problemas, parceiras e atividades</span>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                        @foreach (\App\Http\Livewire\Closure\Dispatch\Dashboard::PRESETS as $key => $label)
                            <button type="button" class="mp-preset" wire:click="setPreset('{{ $key }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="periodFrom" aria-label="De">
                    <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="periodTo" aria-label="Até">
                </div>
            </div>
            @if ($custom)
                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
                </div>
            @endif
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Problemas, parceiras e atividades usam <strong>{{ $period }}</strong>. O comparador mensal usa sempre os seis meses até <strong>{{ $cmp['reference'] }}</strong>. A operação mostra a situação de agora.
        </div>
    </section>

    <div wire:loading.class="is-loading-soft" wire:target="periodFrom,periodTo,setPreset,clearFilters">

        <h2 class="mp-section-title">Painel executivo</h2>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card :tone="$overallTone" icon="ri-line-chart-line" label="Melhoramos ou pioramos?" :value="$overallText"
                    :note="$cmp['count']['better'] . ' melhoraram · ' . $cmp['count']['worse'] . ' pioraram · ' . $cmp['count']['stable'] . ' estáveis (' . $cmp['reference'] . ' × ' . $cmp['previous'] . ')'"
                    :values="$cmp['series']['issues_opened']" :labels="array_column($cmp['months'], 'label')" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-chat-warning-line" label="Pendências em aberto" :value="$n($sum['open_issues'])"
                    :note="$n($sum['awaiting']) . ' aguardando resposta · ' . $n($sum['ri_open']) . ' RI com o Desenho'"
                    :values="$cmp['series']['issues_opened']" :labels="array_column($cmp['months'], 'label')" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-timer-line" label="Resposta média" :value="$sum['avg_response'] === null ? '—' : $dec($sum['avg_response']) . ' dias'"
                    :note="$n($sum['answered']) . ' respondidas no período · ' . $sum['verdicts']['response_days']['label'] . ' vs mês anterior'"
                    :values="array_map(fn ($v) => $v ?? 0, $cmp['series']['response_days'])" :labels="array_column($cmp['months'], 'label')" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-repeat-line" label="Devoluções para correção" :value="$n($sum['returns'])"
                    :note="$sum['rework'] === null ? 'Nenhum retorno recebido no período' : $pct($sum['rework']) . ' dos retornos voltaram para correção'"
                    :values="$cmp['series']['returns']" :labels="array_column($cmp['months'], 'label')" />
            </div>
        </div>

        <h2 class="mp-section-title">Melhoramos ou pioramos? Comparador mensal</h2>
        <section class="mp-panel mb-3">
            <div class="mp-panel-head">
                <div>
                    <h3 class="mp-panel-title">Indicadores dos últimos {{ count($cmp['months']) }} meses</h3>
                    <div class="mp-panel-sub">
                        {{ $cmp['reference'] }} contra {{ $cmp['previous'] }}. Verde = melhorou, vermelho = piorou (mudança abaixo de {{ number_format(Mgmt::STABLE_PERCENT, 0) }}% é estável).
                        @if ($cmp['partial']) <span class="ops-compare-partial">O mês de referência ainda está em andamento: a comparação é parcial.</span> @endif
                    </div>
                </div>
            </div>
            <div class="ops-table-wrap">
                <table class="table mp-table align-middle mb-0">
                    <colgroup>
                        <col style="width: 250px">
                        @foreach ($cmp['months'] as $month)
                            <col style="width: 86px">
                        @endforeach
                        <col style="width: 140px"><col style="width: 130px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-start">Indicador</th>
                            @foreach ($cmp['months'] as $month)
                                <th class="text-center">{{ $month['label'] }}</th>
                            @endforeach
                            <th class="text-center">Variação</th>
                            <th class="text-center">Veredito</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cmp['rows'] as $row)
                            @php $v = $row['verdict']; @endphp
                            <tr wire:key="cmp-{{ $row['key'] }}">
                                <td class="text-start" title="{{ $row['hint'] }}">
                                    <span class="fw-semibold">{{ $row['label'] }}</span>
                                    <span class="d-block small text-muted">{{ ['up' => 'mais é melhor', 'down' => 'menos é melhor', 'neutral' => 'sem direção'][$row['better']] }}</span>
                                </td>
                                @foreach ($row['values'] as $i => $value)
                                    <td class="text-center @if ($i === count($row['values']) - 1) ops-compare-ref @endif">{{ $value === null ? '—' : ($row['unit'] === 'days' ? $dec($value) : $n($value)) }}</td>
                                @endforeach
                                <td class="text-center">
                                    @if ($v['delta'] === null)
                                        <span class="ops-empty">—</span>
                                    @else
                                        <span class="ops-delta tone-{{ $v['tone'] }}"><i class="{{ $arrow($v['delta']) }}" aria-hidden="true"></i> {{ ($v['delta'] > 0 ? '+' : '') . ($row['unit'] === 'days' ? $dec($v['delta']) : $n($v['delta'])) }} ({{ ($v['percent'] > 0 ? '+' : '') . number_format($v['percent'], 0, ',', '.') }}%)</span>
                                    @endif
                                </td>
                                <td class="text-center"><x-ops.badge :tone="$v['tone']" :label="$v['label']" :hint="$row['hint']" sm /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Pendências por mês</h3><div class="mp-panel-sub">Abertas, resolvidas e devolvidas para correção: se as abertas passam das resolvidas, o estoque cresce.</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="trend" data-config="{{ json_encode($charts['trend']) }}" role="img" aria-label="Barras por mês: pendências abertas, resolvidas e devoluções para correção"></canvas>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-3">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">O que mais melhorou</h3><div class="mp-panel-sub">{{ $cmp['reference'] }} × {{ $cmp['previous'] }}</div></div></div>
                    <div class="mp-panel-body"><x-home.bar-list :rows="$moverRows('better')" caption="Indicadores que mais melhoraram" empty="Nenhum indicador melhorou." :headers="['Indicador', 'Variação', 'Significado']" /></div>
                </section>
            </div>
            <div class="col-12 col-xl-3">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">O que mais piorou</h3><div class="mp-panel-sub">{{ $cmp['reference'] }} × {{ $cmp['previous'] }}</div></div></div>
                    <div class="mp-panel-body"><x-home.bar-list :rows="$moverRows('worse')" caption="Indicadores que mais pioraram" empty="Nenhum indicador piorou." :headers="['Indicador', 'Variação', 'Significado']" /></div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Principais problemas</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Ranking de problemas</h3><div class="mp-panel-sub">{{ $period }} · {{ $n($problems['total']) }} ocorrência(s): motivos de pendência, de RI e depósitos bloqueados. Os 10 maiores; o Excel traz todos.</div></div></div>
                    <div class="mp-panel-body"><x-home.bar-list :rows="$problemRows" caption="Principais problemas do período" empty="Nenhum problema registrado no período." :headers="['Problema', 'Ocorrências', 'Origem e situação']" /></div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">De onde vêm</h3><div class="mp-panel-sub">Ocorrências do período por origem</div></div></div>
                    <div class="ops-table-wrap">
                        <table class="table mp-table align-middle mb-0">
                            <colgroup><col><col style="width: 90px"><col style="width: 90px"><col style="width: 80px"></colgroup>
                            <thead><tr><th class="text-start">Origem</th><th class="text-center">Total</th><th class="text-center">Em aberto</th><th class="text-center">%</th></tr></thead>
                            <tbody>
                                @forelse ($bySource as $source => $row)
                                    <tr wire:key="src-{{ $loop->index }}">
                                        <td class="text-start"><x-ops.badge :tone="$sourceTone($source)" :label="$source" sm /></td>
                                        <td class="text-center fw-semibold">{{ $n($row['total']) }}</td>
                                        <td class="text-center">{{ $n($row['open']) }}</td>
                                        <td class="text-center">{{ $problems['total'] > 0 ? number_format($row['total'] / $problems['total'] * 100, 0, ',', '.') . '%' : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Sem ocorrências no período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Problemas por parceira</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Parceiras com mais pendências</h3><div class="mp-panel-sub">Recebidas no período × em aberto agora × devoluções (top {{ Charts::TOP }})</div></div></div>
                    <div class="mp-chart-wrap mp-chart-tall">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="partners" data-config="{{ json_encode($charts['partners']) }}" role="img" aria-label="Barras horizontais por parceira: pendências recebidas, em aberto e devoluções"></canvas>
                        </div>
                        @if (count($partners) === 0)<div class="mp-chart-empty">Nenhuma pendência com parceiras</div>@endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Quadro por parceira</h3><div class="mp-panel-sub">Retrabalho = devoluções para correção ÷ retornos recebidos. Resposta média em dias corridos.</div></div></div>
                    <div class="ops-table-wrap">
                        <table class="table mp-table align-middle mb-0">
                            <colgroup><col style="width: 170px"><col style="width: 80px"><col style="width: 76px"><col style="width: 90px"><col style="width: 80px"><col style="width: 90px"><col style="width: 90px"><col style="width: 150px"><col style="width: 84px"></colgroup>
                            <thead><tr><th class="text-start">Parceira</th><th class="text-center">Recebidas</th><th class="text-center">Em aberto</th><th class="text-center">Aguardando</th><th class="text-center">Resposta</th><th class="text-center">Devoluções</th><th class="text-center">Retrabalho</th><th class="text-start">Principal motivo</th><th class="text-center">Depósitos</th></tr></thead>
                            <tbody>
                                @forelse ($partners as $partner)
                                    <tr wire:key="mgmt-partner-{{ $partner['company_id'] }}">
                                        <td class="text-start fw-semibold" title="{{ $partner['name'] }}"><span class="d-block text-truncate">{{ $partner['name'] }}</span></td>
                                        <td class="text-center">{{ $n($partner['received']) }}</td>
                                        <td class="text-center">{{ $n($partner['open']) }}</td>
                                        <td class="text-center">{{ $n($partner['awaiting']) }}</td>
                                        <td class="text-center">{{ $daysText($partner['avg_days']) }}</td>
                                        <td class="text-center">{{ $n($partner['returns']) }}</td>
                                        <td class="text-center">@if ($partner['rework'] === null)<span class="ops-empty">—</span>@else<x-ops.badge :tone="$partner['rework'] >= 30 ? 'red' : ($partner['rework'] >= 10 ? 'amber' : 'green')" :label="$pct($partner['rework'])" sm />@endif</td>
                                        <td class="text-start" title="{{ $partner['top_reason'] }}"><span class="d-block text-truncate">{{ $partner['top_reason'] ?? '—' }}</span></td>
                                        <td class="text-center">{{ $n($partner['deposits']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">Sem pendências nem bloqueios com parceiras.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Problemas por atividade</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Pendências por atividade</h3><div class="mp-panel-sub">Parceiras, Medição, Fiscalização e Desenho (RI)</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="activities" data-config="{{ json_encode($charts['activities']) }}" role="img" aria-label="Barras por atividade: recebidas, resolvidas e em aberto"></canvas>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Quadro por atividade</h3><div class="mp-panel-sub">Variação das recebidas contra o período anterior de mesma duração (menos é melhor).</div></div></div>
                    <div class="ops-table-wrap">
                        <table class="table mp-table align-middle mb-0">
                            <colgroup><col style="width: 160px"><col style="width: 86px"><col style="width: 130px"><col style="width: 86px"><col style="width: 86px"><col style="width: 96px"><col style="width: 90px"><col style="width: 96px"><col style="width: 96px"></colgroup>
                            <thead><tr><th class="text-start">Atividade</th><th class="text-center">Recebidas</th><th class="text-center">vs anterior</th><th class="text-center">Resolvidas</th><th class="text-center">Em aberto</th><th class="text-center">Resposta</th><th class="text-center">Devoluções</th><th class="text-center">Retrabalho</th><th class="text-center">Ordens na caixa</th></tr></thead>
                            <tbody>
                                @foreach ($activities as $row)
                                    @php
                                        $delta = $row['received'] - $row['received_prev'];
                                        $tone  = $delta == 0 ? 'muted' : ($delta < 0 ? 'green' : 'red');
                                    @endphp
                                    <tr wire:key="activity-{{ $row['code'] }}">
                                        <td class="text-start fw-semibold">{{ $row['name'] }}<span class="d-block small text-muted">{{ $row['unit'] }}</span></td>
                                        <td class="text-center fw-semibold">{{ $n($row['received']) }}</td>
                                        <td class="text-center"><span class="ops-delta tone-{{ $tone }}"><i class="{{ $arrow((float) $delta) }}" aria-hidden="true"></i> {{ ($delta > 0 ? '+' : '') . $n($delta) }} <span class="text-muted fw-normal">({{ $n($row['received_prev']) }})</span></span></td>
                                        <td class="text-center">{{ $n($row['resolved']) }}</td>
                                        <td class="text-center">{{ $n($row['open']) }}</td>
                                        <td class="text-center">{{ $daysText($row['avg_days']) }}</td>
                                        <td class="text-center">{{ $n($row['returns']) }}</td>
                                        <td class="text-center">{{ $pct($row['rework']) }}</td>
                                        <td class="text-center">{{ $row['in_box'] === null ? '—' : $n($row['in_box']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <h2 class="mp-section-title">Operação agora</h2>
    <section class="mp-filters filter-bar" aria-label="Filtros da operação">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="dash-type">Tipo</label>
                <select id="dash-type" class="form-select form-select-sm" wire:model="filterType">
                    <option value="">Todos</option>
                    <option value="P">P - Passivo</option>
                    <option value="M">M - Meta atual</option>
                    <option value="F">F - Meta futura</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1" for="dash-company">Empresa (posse atual)</label>
                <select id="dash-company" class="form-select form-select-sm" wire:model="filterCompany">
                    <option value="">Todas</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="dash-from">No status desde</label>
                <input id="dash-from" type="date" class="form-control form-control-sm" wire:model="dateFrom">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="dash-to">Até</label>
                <input id="dash-to" type="date" class="form-control form-control-sm" wire:model="dateTo">
            </div>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <x-home.hero-card tone="blue" icon="ri-stack-line" label="Total ativo" :value="$n($summary['total'])"
                :note="'P ' . $summary['types']['P'] . ' · M ' . $summary['types']['M'] . ' · F ' . $summary['types']['F'] . ($summary['types']['N'] ? ' · sem tipo ' . $summary['types']['N'] : '')" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-home.hero-card tone="green" icon="ri-flag-line" label="Meta concluída" :value="number_format($summary['meta_percent'], 1, ',', '.') . '%'"
                :note="$summary['meta_closed'] . ' de ' . $summary['meta_total'] . ' Ordens M do ciclo encerradas'" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-home.hero-card tone="amber" icon="ri-team-line" label="Aguardando parceira" :value="$n($summary['waiting_partner'])" note="Ordens com pendência na parceira" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-home.hero-card tone="red" icon="ri-pause-circle-line" label="Pausadas" :value="$n($summary['paused'])" :note="$n($summary['finished']) . ' finalizadas · ' . $n($summary['waiting']) . ' aguardando a meta'" />
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <section class="mp-panel h-100">
                <div class="mp-panel-head"><div><h3 class="mp-panel-title">Volumetria por usuário</h3><div class="mp-panel-sub">Barras empilhadas por tipo (P/M/F), top {{ Charts::TOP }}</div></div></div>
                <div class="mp-chart-wrap mp-chart-tall">
                    <div class="mp-chart" wire:ignore>
                        <canvas data-my-panel-chart="users" data-config="{{ json_encode($charts['users']) }}" role="img" aria-label="Barras horizontais por usuário, empilhadas por tipo de Ordem"></canvas>
                    </div>
                    @unless ($hasData)<div class="mp-chart-empty">Sem Ordens ativas para os filtros</div>@endunless
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-5">
            <section class="mp-panel h-100">
                <div class="mp-panel-head"><div><h3 class="mp-panel-title">Ordens por status</h3><div class="mp-panel-sub">Distribuição do estoque ativo</div></div></div>
                <div class="mp-chart-wrap mp-chart-tall">
                    <div class="mp-chart" wire:ignore>
                        <canvas data-my-panel-chart="status" data-config="{{ json_encode($charts['status']) }}" role="img" aria-label="Rosca com as Ordens ativas por status operacional"></canvas>
                    </div>
                    @unless ($hasData)<div class="mp-chart-empty">Sem Ordens ativas para os filtros</div>@endunless
                </div>
            </section>
        </div>
    </div>

    <section class="mp-panel mb-4" id="closure-matrix">
        <div class="mp-panel-head">
            <div>
                <h3 class="mp-panel-title">Matriz de volumetria</h3>
                <div class="mp-panel-sub">Usuário × tipo × status. Clique em um número para filtrar a lista abaixo.</div>
            </div>
            @if (array_filter($cell))
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearCell">Limpar seleção</button>
            @endif
        </div>
        <div class="ops-table-wrap">
            <table class="table mp-table align-middle mb-0 text-center">
                <colgroup>
                    <col style="width: 200px">
                    <col style="width: 150px">
                    @foreach ($statuses as $status)
                        <col style="width: 86px">
                    @endforeach
                    <col style="width: 70px">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-start">Usuário</th>
                        <th>Tipo</th>
                        @foreach ($statuses as $status)
                            <th title="{{ OpStatus::labelFor($status) }}">{{ match ($status) {
                                'WAITING_PARTNER'  => 'Aguard. Parceira',
                                'DEPOSIT_BLOCKED'  => 'Dep. Bloq.',
                                'DEPOSIT_RELEASED' => 'Dep. Lib.',
                                default            => OpStatus::labelFor($status),
                            } }}</th>
                        @endforeach
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matrix['rows'] as $row)
                        @php $uid = $row['user_id'] ?? \App\Services\Closure\Analytics\ClosureDispatcherAnalytics::NO_USER; @endphp
                        <tr wire:key="matrix-{{ $uid }}">
                            <td class="text-start fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                            <td>
                                @foreach (['P', 'M', 'F'] as $type)
                                    <button type="button" class="ops-type-chip tone-{{ $typeTones[$type] }} @if ($cellActive($uid, $type, null)) is-active @endif"
                                        wire:click="selectCell('{{ $uid }}', '{{ $type }}', '')" @disabled($row['types'][$type] === 0)>{{ $type }} {{ $row['types'][$type] }}</button>
                                @endforeach
                            </td>
                            @foreach ($statuses as $status)
                                @php $count = $row['status'][$status]; @endphp
                                <td>
                                    <button type="button" class="ops-matrix-cell @if ($cellActive($uid, null, $status)) is-active @endif"
                                        wire:click="selectCell('{{ $uid }}', '', '{{ $status }}')" @disabled($count === 0)
                                        title="P {{ $row['cells'][$status]['P'] }} · M {{ $row['cells'][$status]['M'] }} · F {{ $row['cells'][$status]['F'] }}">{{ $count }}</button>
                                </td>
                            @endforeach
                            <td class="fw-bold">
                                <button type="button" class="ops-matrix-cell @if ($cellActive($uid, null, null)) is-active @endif" wire:click="selectCell('{{ $uid }}', '', '')">{{ $row['total'] }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $statusCount + 3 }}" class="text-muted py-4">Nenhuma Ordem ativa para os filtros.</td></tr>
                    @endforelse
                </tbody>
                @if ($hasData)
                    <tfoot>
                        <tr class="ops-matrix-total">
                            <td class="text-start">Total geral</td>
                            <td>
                                @foreach (['P', 'M', 'F'] as $type)
                                    <x-ops.badge :tone="$typeTones[$type]" :label="$type . ' ' . $matrix['totals']['types'][$type]" sm />
                                @endforeach
                            </td>
                            @foreach ($statuses as $status)
                                <td>{{ $matrix['totals']['status'][$status] }}</td>
                            @endforeach
                            <td>{{ $matrix['totals']['total'] }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    <section class="mp-panel mb-4" id="closure-cell-orders">
        <div class="mp-panel-head">
            <div>
                <h3 class="mp-panel-title">Ordens {{ array_filter($cell) ? 'da seleção' : 'ativas' }}</h3>
                <div class="mp-panel-sub">
                    @if (array_filter($cell))
                        Filtro:
                        {{ $cell['user'] ? ($matrix['rows'] ? (collect($matrix['rows'])->firstWhere('user_id', $cell['user'])['name'] ?? 'Sem responsável (pilha)') : '') : 'todos os usuários' }}
                        {{ $cell['type'] ? ' · tipo ' . $cell['type'] : '' }}
                        {{ $cell['status'] ? ' · ' . OpStatus::labelFor($cell['status']) : '' }}
                    @else
                        Primeiras 50 Ordens por prioridade e tempo no status.
                    @endif
                </div>
            </div>
        </div>
        <div class="ops-table-wrap">
            <table class="table mp-table align-middle mb-0">
                <colgroup><col style="width: 60px"><col style="width: 120px"><col style="width: 140px"><col style="width: 170px"><col style="width: 190px"><col style="width: 190px"><col style="width: 150px"><col style="width: 130px"></colgroup>
                <thead><tr><th>Tipo</th><th>Nota</th><th>Ordem</th><th>Status</th><th>Responsável</th><th>Empresa</th><th>No status desde</th><th><span class="visually-hidden">Ação</span></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="cell-order-{{ $order->state_id }}">
                            <td><x-ops.badge :tone="$typeTones[$order->meta_type] ?? 'muted'" :label="$order->meta_type ?? '—'" sm mono /></td>
                            <td>{{ $order->note_number }}</td>
                            <td class="fw-semibold">{{ $order->ordem }} @if ($order->priority)<x-ops.badge tone="amber" label="!" hint="Prioridade" sm />@endif</td>
                            <td><x-ops.badge :tone="\App\Enum\ClosureOperationalStatus::toneFor($order->status)" :label="OpStatus::labelFor($order->status)" sm /></td>
                            <td title="{{ $order->responsible_name }}"><span class="d-block text-truncate">{{ $order->responsible_name ?? '—' }}</span></td>
                            <td title="{{ $order->company_name }}"><span class="d-block text-truncate">{{ $order->company_name ?? '—' }}</span></td>
                            <td>{{ $order->entered_at ? \Carbon\Carbon::parse($order->entered_at)->format('d/m/Y H:i') : '—' }}</td>
                            <td><a class="btn btn-outline-primary btn-sm ops-action" href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'dispatch']) }}"><i class="ri-building-line" aria-hidden="true"></i> Abrir obra</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted py-4 text-center">Nenhuma Ordem na seleção.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('livewire.closure.dispatch._priority-requests')

    <section class="mp-panel mb-4" id="closure-user-interaction">
        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Pendências por usuário do Encerramento</h3><div class="mp-panel-sub">Pendências enviadas e resposta média da parceira (de sempre, não só do período)</div></div></div>
        <div class="ops-table-wrap">
            <table class="table mp-table align-middle mb-0">
                <colgroup><col><col style="width: 110px"><col style="width: 120px"><col style="width: 120px"><col style="width: 160px"></colgroup>
                <thead><tr><th class="text-start">Usuário</th><th class="text-center">Enviadas</th><th class="text-center">Aguardando</th><th class="text-center">Respondidas</th><th class="text-center">Resposta média</th></tr></thead>
                <tbody>
                    @forelse ($interaction['users'] as $user)
                        <tr wire:key="iuser-{{ $user['user_id'] }}">
                            <td class="text-start fw-semibold" title="{{ $user['name'] }}">{{ $user['name'] }}</td>
                            <td class="text-center">{{ $user['sent'] }}</td>
                            <td class="text-center">{{ $user['awaiting'] }}</td>
                            <td class="text-center">{{ $user['responded'] }}</td>
                            <td class="text-center">{{ $duration($user['avg_response']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted py-4 text-center">Sem pendências enviadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mp-panel mb-4" id="closure-critical-works">
        <div class="mp-panel-head">
            <div>
                <h3 class="mp-panel-title">Obras críticas</h3>
                <div class="mp-panel-sub">Ordens P paradas ou pendência sem resposta há mais de {{ $staleDays }} dias</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small mb-0" for="staleDays">Dias</label>
                <input id="staleDays" type="number" min="1" max="365" class="form-control form-control-sm w-auto" wire:model.lazy="staleDays">
            </div>
        </div>
        <div class="ops-table-wrap">
            <table class="table mp-table align-middle mb-0">
                <colgroup><col style="width: 130px"><col style="width: 110px"><col style="width: 90px"><col style="width: 110px"><col style="width: 120px"><col style="width: 150px"><col style="width: 340px"></colgroup>
                <thead><tr><th class="text-start">Obra</th><th class="text-center">Ordens abertas</th><th class="text-center">P</th><th class="text-center">P paradas</th><th class="text-center">Mais antiga</th><th class="text-center">Pendência parada</th><th><span class="visually-hidden">Ações</span></th></tr></thead>
                <tbody>
                    @forelse ($critical as $work)
                        <tr wire:key="critical-{{ $work['note_id'] }}">
                            <td class="text-start fw-semibold">{{ $work['note'] }}</td>
                            <td class="text-center">{{ $work['open_orders'] }}</td>
                            <td class="text-center">{{ $work['passive_orders'] }}</td>
                            <td class="text-center">{{ $work['stale_passive'] }}</td>
                            <td class="text-center">{{ $work['oldest_days'] !== null ? $work['oldest_days'] . ' d' : '—' }}</td>
                            <td class="text-center">{{ $work['stuck_issues'] ? $work['stuck_issues'] . ' (' . $work['stuck_days'] . ' d)' : '—' }}</td>
                            <td>
                                <div class="ops-buttons">
                                    <a class="btn btn-outline-primary btn-sm ops-action" href="{{ route('closure.work.show', ['note' => $work['note_id'], 'ctx' => 'dispatch']) }}"><i class="ri-building-line" aria-hidden="true"></i> Abrir obra</a>
                                    <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click="prioritizeNote({{ $work['note_id'] }})">Priorizar</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click="prioritizeNote({{ $work['note_id'] }}, false)">Tirar prioridade</button>
                                    <button type="button" class="btn btn-outline-dark btn-sm ops-action" wire:click="openTransfer({{ $work['note_id'] }})">Transferir</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted py-4 text-center">Nenhuma obra crítica.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('livewire.closure.dispatch._transfer-form')

    @include('livewire.closure.dispatch._decide-script')
</div>
