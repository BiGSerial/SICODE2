@php
    $s        = $snapshot['summary'];
    $filters  = $snapshot['filters'];
    $options  = $snapshot['options'];
    $daily    = $snapshot['daily'];
    $buckets  = $snapshot['age_buckets'];
    $hasOpen  = $s['open'] > 0;
    $hasData  = $hasOpen || $s['entered'] > 0 || $s['handled'] > 0;
    $period   = $filters['from']->format('d/m/Y') . ' a ' . $filters['to']->format('d/m/Y');
    $money    = fn (?float $v) => $v === null ? '—' : 'R$ ' . number_format($v, 2, ',', '.');
    $days     = fn (?int $d) => $d === null ? '—' : ($d === 0 ? 'hoje' : $d . ' ' . ($d === 1 ? 'dia' : 'dias'));
    $dates    = array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $daily['dates']);
    $isOpen   = $tab === 'open';
    $maxAge   = max(1, collect($snapshot['open'])->max('age_days') ?? 1);

    $rank = function (array $rows, string $valueNote) {
        $max = max(1, collect($rows)->max('total'));

        return collect($rows)->take(10)->map(fn ($r) => [
            'label' => $r['label'],
            'text'  => (string) $r['total'],
            'pct'   => $r['total'] / $max * 100,
            'tone'  => $r['oldest'] >= 16 ? 'red' : ($r['oldest'] >= 8 ? 'amber' : 'blue'),
            'sub'   => ($r['value'] > 0 ? 'R$ ' . number_format($r['value'], 2, ',', '.') . ' em ADS · ' : '') . 'mais antigo há ' . $r['oldest'] . ' d',
        ])->all();
    };

    $regionRows  = $rank($snapshot['by_region'], 'ADS');
    $companyRows = $rank($snapshot['by_company'], 'ADS');
    $typeRows    = collect($snapshot['by_type'])->map(fn ($t) => [
        'label' => $t['label'],
        'text'  => (string) $t['total'],
        'pct'   => $t['total'] / max(1, $s['open']) * 100,
        'tone'  => $t['label'] === 'Parcial' ? 'blue' : 'teal',
    ])->all();
@endphp

<div class="closure-dash my-panel">

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Controle de saldo</div>
            <h1 class="mp-hero-title">Informes sem saldo no PEP</h1>
            <p class="mp-hero-sub">Quais informes entraram sem saldo disponível (ou com ADS acima do saldo), onde estão e há quanto tempo esperam tratamento.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel com uma aba por bloco, nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small mb-1" for="pa-company">Empreiteira</label>
                <select id="pa-company" class="form-select form-select-sm" wire:model="company">
                    <option value="">Todas as empreiteiras</option>
                    @foreach ($options['companies'] as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small mb-1" for="pa-region">Região</label>
                <select id="pa-region" class="form-select form-select-sm" wire:model="region">
                    <option value="">Todas as regiões</option>
                    @foreach ($options['regions'] as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-3">
                <span class="form-label small d-block mb-1">Período (entradas e tratados)</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (\App\Http\Livewire\Engineers\PepAlerts::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label small mb-1" for="pa-from">De</label>
                <input id="pa-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_in" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label small mb-1" for="pa-to">Até</label>
                <input id="pa-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_out" max="{{ now()->toDateString() }}">
            </div>
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Período exibido: <strong>{{ $period }}</strong> ({{ $filters['days'] }} {{ $filters['days'] === 1 ? 'dia' : 'dias' }}). A fila de pendentes mostra sempre a situação de agora.
            @if ($company || $region)
                <button type="button" class="btn btn-link btn-sm p-0 ms-2" wire:click="clearFilters">limpar filtros</button>
            @endif
        </div>
    </section>

    @if (!$hasData)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-wallet-3-line" title="Nenhum informe sem saldo nos filtros atuais" text="Quando um informe entrar sem saldo no PEP da sua região, ele aparece aqui e você é avisado." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="company,region,setPreset,dt_in,dt_out">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-wallet-3-line" label="Pendentes de tratamento" :value="$s['open']"
                    :note="$s['no_balance'] . ' sem saldo · ' . $s['ads_exceeds'] . ' com ADS acima do saldo'"
                    :values="$daily['entered']" :labels="$dates" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-money-dollar-circle-line" label="Valor em espera (ADS)" :value="$money($s['open_value'])"
                    :note="$s['with_value'] . ' ' . ($s['with_value'] === 1 ? 'informe com ADS' : 'informes com ADS') . ' na fila'"
                    :values="$daily['entered']" :labels="$dates" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-timer-line" label="Mais antigo na lista" :value="$s['oldest'] === null ? '—' : $days($s['oldest'])"
                    :note="'Idade média ' . ($s['average'] !== null ? number_format($s['average'], 1, ',', '.') . ' dias' : '—') . ' · ' . $s['covered'] . ' já com saldo cobrindo'"
                    :values="$buckets['values']" :labels="$buckets['labels']" :ticks="4" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-check-double-line" label="Tratados no período" :value="$s['handled']"
                    :note="$s['handled_eng'] . ' pelo engenheiro · ' . $s['handled_auto'] . ' saída automática · ' . $s['entered'] . ' entraram'"
                    :values="$daily['handled']" :labels="$dates" />
            </div>
        </div>

        <h2 class="mp-section-title">Fila de tratamento</h2>

        <section class="mp-panel mb-3" aria-labelledby="pa-queue-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="pa-queue-title" class="mp-panel-title">{{ $isOpen ? 'Pendentes, do mais antigo para o mais recente' : 'Tratados no período, do mais recente para o mais antigo' }}</h3>
                    <div class="mp-panel-sub">
                        {{ $isOpen ? 'Saiu da fila sozinho se o informe for removido, ou 48h depois de o saldo passar a cobrir o pedido.' : 'Inclui as saídas automáticas e a observação de quem tratou.' }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="mp-presets" role="group" aria-label="Fila">
                        <button type="button" class="mp-preset {{ $isOpen ? 'is-active' : '' }}" wire:click="setTab('open')" aria-pressed="{{ $isOpen ? 'true' : 'false' }}">Pendentes · {{ $s['open'] }}</button>
                        <button type="button" class="mp-preset {{ !$isOpen ? 'is-active' : '' }}" wire:click="setTab('history')" aria-pressed="{{ !$isOpen ? 'true' : 'false' }}">Histórico · {{ $s['handled'] }}</button>
                    </div>
                </div>
            </div>

            @if ($rowsTotal === 0)
                <x-closure.empty-state :icon="$isOpen ? 'ri-checkbox-circle-line' : 'ri-history-line'"
                    :title="$isOpen ? 'Nenhum informe pendente' : 'Nenhum informe tratado no período'"
                    :text="$isOpen ? 'Tudo o que entrou sem saldo já foi tratado.' : 'Amplie o período para ver o histórico.'" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-8"><col class="col-pct-8"><col class="col-pct-7"><col class="col-pct-9"><col class="col-pct-16">
                            <col class="col-pct-10"><col class="col-pct-12"><col class="col-pct-12"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Obra</th>
                                <th scope="col">Informe</th>
                                <th scope="col">Escopo</th>
                                <th scope="col">Região</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col" class="text-end">Valor (ADS)</th>
                                <th scope="col">Status do informe</th>
                                <th scope="col">{{ $isOpen ? 'Tempo na lista' : 'Tratado' }}</th>
                                <th scope="col">{{ $isOpen ? 'Motivo e ação' : 'Por quem' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="pa-{{ $tab }}-{{ $row['id'] }}">
                                    <td class="fw-bold">{{ $row['note'] }}</td>
                                    <td><span class="badge text-bg-{{ $row['type'] === 'partial' ? 'primary' : 'info' }}">{{ $row['type_label'] }}</span></td>
                                    <td>{{ $row['scope_label'] }}</td>
                                    <td>{{ $row['region'] ?? '—' }}</td>
                                    <td class="text-truncate" title="{{ $row['company'] }}">{{ $row['company'] ?? '—' }}</td>
                                    <td class="text-end">{{ $money($row['value']) }}</td>
                                    <td><span class="badge text-bg-{{ $row['status_color'] }}">{{ $row['status'] }}</span></td>
                                    <td>
                                        @if ($isOpen)
                                            <div class="mp-age">
                                                <span class="mp-age-bar" aria-hidden="true"><span style="--pct: {{ max(4, $row['age_days'] / $maxAge * 100) }}"></span></span>
                                                <strong>{{ $days($row['age_days']) }}</strong>
                                            </div>
                                            <small class="text-muted">desde {{ $row['created_at']->format('d/m/Y') }}</small>
                                        @else
                                            <strong>{{ $row['handled_at']?->format('d/m/Y H:i') }}</strong>
                                            <div class="small text-muted">após {{ $days($row['age_days']) }} na lista</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isOpen)
                                            <span class="badge text-bg-{{ $row['reason'] === 'no_balance' ? 'danger' : 'warning' }}">{{ $row['reason_label'] }}</span>
                                            @if ($row['covered_since'])
                                                <div class="small text-success">Saldo cobre desde {{ $row['covered_since']->format('d/m H:i') }} · sai {{ $row['auto_at']->format('d/m H:i') }}</div>
                                            @endif
                                            <div class="mt-1">
                                                <button type="button" class="row-action border-0" wire:click="askHandle({{ $row['id'] }})"><i class="ri-check-line"></i> Tratado</button>
                                            </div>
                                        @else
                                            <strong>{{ $row['handler'] ?? '—' }}</strong>
                                            @if ($row['notes'])
                                                <div class="small text-muted">{{ $row['notes'] }}</div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($rowsMore > 0)
                    <div class="mp-panel-foot">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-dash" wire:click="$toggle('showAll')">
                            {{ $showAll ? 'Mostrar só os ' . \App\Http\Livewire\Engineers\PepAlerts::VISIBLE . ' primeiros' : 'Ver mais ' . min($rowsMore, \App\Http\Livewire\Engineers\PepAlerts::EXPANDED - \App\Http\Livewire\Engineers\PepAlerts::VISIBLE) }}
                        </button>
                        @if ($rowsTotal > \App\Http\Livewire\Engineers\PepAlerts::EXPANDED && $showAll)
                            <span class="small text-muted ms-2">A lista completa está no Excel.</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>

        <h2 class="mp-section-title">Onde está o problema</h2>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por região</h3><div class="mp-panel-sub">Pendentes por base de construção; vermelho = há item com 16+ dias</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$regionRows" caption="Informes pendentes por região" :headers="['Região', 'Pendentes', 'Detalhe']" empty="Nenhum informe pendente" />
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por empreiteira</h3><div class="mp-panel-sub">Top 10 pendentes; o detalhe traz valor de ADS e o item mais antigo</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$companyRows" caption="Informes pendentes por empreiteira" :headers="['Empreiteira', 'Pendentes', 'Detalhe']" empty="Nenhum informe pendente" />
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por motivo e tipo</h3><div class="mp-panel-sub">Sem saldo no PEP x ADS acima do saldo</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="reason" data-config="{{ json_encode($charts['reason']) }}" role="img" aria-label="Rosca: informes pendentes por motivo"></canvas>
                        </div>
                        @if (!$hasOpen)
                            <div class="mp-chart-empty">Nenhum informe pendente</div>
                        @endif
                    </div>
                    @if ($hasOpen)
                        <div class="mp-panel-body">
                            <x-home.bar-list :rows="$typeRows" caption="Informes pendentes por tipo" :headers="['Tipo', 'Pendentes', '']" />
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Fluxo e envelhecimento</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Entraram x tratados por dia</h3><div class="mp-panel-sub">{{ $period }}</div></div><span class="mp-pill">{{ $s['entered'] }} entraram · {{ $s['handled'] }} tratados</span></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="flow" data-config="{{ json_encode($charts['flow']) }}" role="img" aria-label="Gráfico de linhas: informes que entraram na lista e tratados por dia no período"></canvas>
                        </div>
                        @if ($s['entered'] === 0 && $s['handled'] === 0)
                            <div class="mp-chart-empty">Nada entrou nem foi tratado neste período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Há quanto tempo estão na lista?</h3><div class="mp-panel-sub">Pendentes por faixa de dias</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="age" data-config="{{ json_encode($charts['age']) }}" role="img" aria-label="Gráfico de barras: informes pendentes por faixa de dias na lista"></canvas>
                        </div>
                        @if (!$hasOpen)
                            <div class="mp-chart-empty">Nenhum informe pendente</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Tratar --}}
    <div class="modal fade" id="pepAlertHandleModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Marcar informe como tratado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="pepAlertNotes">Observação (opcional)</label>
                    <textarea id="pepAlertNotes" class="form-control" rows="4" wire:model.defer="handleNotes"
                        placeholder="O que foi feito para tratar o saldo deste informe"></textarea>
                    <small class="text-muted">O item sai da fila e fica registrado no histórico.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="confirmHandle">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
