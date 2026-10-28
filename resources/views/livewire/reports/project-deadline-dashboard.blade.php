@php
    use App\Http\Livewire\Reports\ProjectDeadlineDashboard as Screen;
    use App\Models\OvPhaseDuration;
    use App\Services\Indicators\ProjectDeadlineCharts;

    $survey   = $snapshot['phases'][OvPhaseDuration::SURVEY];
    $design   = $snapshot['phases'][OvPhaseDuration::DESIGN];
    $tmep     = $snapshot['tmep'];
    $now      = $snapshot['status_now'];
    $filters  = $snapshot['filters'];
    $period   = $filters['from']->format('d/m/Y') . ' a ' . $filters['to']->format('d/m/Y');
    $hasData  = $survey['summary']['total'] + $design['summary']['total'] > 0;
    $dec      = fn (?float $v, int $d = 1) => $v === null ? '—' : number_format($v, $d, ',', '.');
    $int      = fn ($v) => number_format((int) $v, 0, ',', '.');
    $label    = fn (string $ym) => ProjectDeadlineCharts::monthLabel($ym);
    $monthsOf = fn (array $phase) => array_map(fn ($m) => $label($m['month']), $phase['months']);

    // OVs em status de projeto agora
    $nowMax  = max(1, $now['s27'], $now['s28']);
    $nowRows = [
        ['label' => 'Status 27 · levantamento', 'text' => $int($now['s27']), 'pct' => $now['s27'] / $nowMax * 100, 'tone' => 'blue'],
        ['label' => 'Status 28 · desenho', 'text' => $int($now['s28']), 'pct' => $now['s28'] / $nowMax * 100, 'tone' => 'teal'],
    ];

    // TMEP por fase (barras)
    $tmepMax  = max(1, $survey['summary']['avg_days'], $design['summary']['avg_days'], $tmep['target']);
    $tmepRows = [
        ['label' => 'Levantamento', 'text' => $dec($survey['summary']['avg_days'], 2) . ' dias', 'pct' => $survey['summary']['avg_days'] / $tmepMax * 100, 'mark' => $survey['limit'] / $tmepMax * 100, 'tone' => 'blue', 'sub' => 'Prazo de ' . $survey['limit'] . ' dias'],
        ['label' => 'Desenho', 'text' => $dec($design['summary']['avg_days'], 2) . ' dias', 'pct' => $design['summary']['avg_days'] / $tmepMax * 100, 'mark' => $design['limit'] / $tmepMax * 100, 'tone' => 'teal', 'sub' => 'Prazo de ' . $design['limit'] . ' dias'],
        ['label' => 'TMEP (levantamento + desenho)', 'text' => $dec($tmep['average'], 2) . ' dias', 'pct' => $tmep['average'] / $tmepMax * 100, 'mark' => $tmep['target'] / $tmepMax * 100, 'tone' => $tmep['average'] <= $tmep['target'] ? 'green' : 'red', 'sub' => 'Meta de ' . $tmep['target'] . ' dias'],
    ];

    $tmepGap = $tmep['average'] - $tmep['target'];
    $maxCompanyOvs = max(1, collect($snapshot['companies'])->map(fn ($c) => ($c[OvPhaseDuration::SURVEY]['total'] ?? 0) + ($c[OvPhaseDuration::DESIGN]['total'] ?? 0))->max() ?? 1);

    $phaseBlocks = [
        ['key' => 'survey', 'phase' => $survey, 'icon' => 'ri-map-pin-range-line'],
        ['key' => 'design', 'phase' => $design, 'icon' => 'ri-ruler-2-line'],
    ];
@endphp

<div class="closure-dash my-panel">

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">{{ $greeting }}</div>
            <h1 class="mp-hero-title">Prazo de Projetos</h1>
            <p class="mp-hero-sub">Aderência de prazo do levantamento (status 27) e do desenho (status 28), o TMEP contra a meta de {{ $tmep['target'] }} dias e quem executa. Por mês de término da fase.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel completo do relatório, com uma aba por bloco, nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do relatório">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small mb-1" for="pd-company">Empresa executora</label>
                <select id="pd-company" class="form-select form-select-sm" wire:model="selectedCompany">
                    <option value="">Todas as empresas</option>
                    @foreach ($companyList as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                @if ($hasFilters)
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" wire:click="clearFilters"><i class="ri-close-circle-line"></i> Limpar filtros</button>
                @endif
            </div>
            <div class="col-12 col-xl-5">
                <span class="form-label small d-block mb-1">Período (término da fase)</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (Screen::PRESETS as $key => $presetLabel)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $presetLabel }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label small mb-1" for="pd-from">De</label>
                <input id="pd-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_in" min="{{ substr(\App\Services\Notes\OvStatusHistoryService::FLOOR, 0, 10) }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label small mb-1" for="pd-to">Até</label>
                <input id="pd-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_out" max="{{ now()->toDateString() }}">
            </div>
        </div>
        <div class="row g-3 align-items-end mt-1" aria-label="Expurgo">
            <div class="col-12 col-xl-auto">
                <span class="form-label small d-block mb-1">Expurgar dos indicadores</span>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <x-filters.multi-dropdown label="Rubrica" model="excludeRubrics" :options="$options['rubrics']" :selected="$excludeRubrics" key-prefix="pd-ex-rubrica" />
                    <x-filters.multi-dropdown label="OV começa com" model="excludePrefixes" :options="$options['prefixes']" :selected="$excludePrefixes" key-prefix="pd-ex-prefix" />
                    <x-filters.multi-dropdown label="Fase seguida do status" model="excludeStatuses" :options="$options['next_stats']" :selected="$excludeStatuses" key-prefix="pd-ex-status" empty-text="Sem fases calculadas (rode sicode:rebuild_ov_phases)." />
                    <button type="button" class="btn btn-primary" wire:click.prevent="applyExclusions"><i class="ri-filter-fill me-1"></i>Aplicar expurgo</button>
                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="clearExclusions"><i class="ri-filter-off-line me-1"></i>Limpar expurgo</button>
                </div>
            </div>
        </div>
        @if (!empty($exclusion['rubrics']) || !empty($exclusion['prefixes']) || !empty($exclusion['next_stats']))
            <div class="mp-period-note">
                Expurgado:
                @if (!empty($exclusion['rubrics'])) <strong>rubrica {{ implode(', ', $exclusion['rubrics']) }}</strong> @endif
                @if (!empty($exclusion['prefixes'])) · <strong>OVs que começam com {{ implode(', ', $exclusion['prefixes']) }}</strong> @endif
                @if (!empty($exclusion['next_stats'])) · <strong>fases seguidas do status {{ implode(', ', $exclusion['next_stats']) }}</strong> @endif
            </div>
        @endif
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Período exibido: <strong>{{ $period }}</strong>. Só o ano corrente, em dias corridos. "OVs em projeto agora" mostra sempre a situação de agora, sem filtro de empresa e só com o expurgo de rubrica e de número da OV.
        </div>
    </section>

    @if (!$hasData)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-inbox-line" title="Nenhuma fase concluída nos filtros atuais" text="Amplie o período ou limpe o filtro de empresa. Se for a primeira vez, o histórico precisa ter sido carregado (sicode:backfill_ov_history)." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="selectedCompany,setPreset,dt_in,dt_out,focusCompany,clearFilters,applyExclusions,clearExclusions">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-map-pin-range-line" label="Aderência · levantamento" :value="$dec($survey['summary']['adherence']) . '%'"
                    :note="$int($survey['summary']['total']) . ' OVs · média ' . $dec($survey['summary']['avg_days'], 2) . ' dias · prazo ' . $survey['limit'] . ' dias'"
                    :values="array_column($survey['months'], 'adherence')" :labels="$monthsOf($survey)" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-ruler-2-line" label="Aderência · desenho" :value="$dec($design['summary']['adherence']) . '%'"
                    :note="$int($design['summary']['total']) . ' OVs · média ' . $dec($design['summary']['avg_days'], 2) . ' dias · prazo ' . $design['limit'] . ' dias'"
                    :values="array_column($design['months'], 'adherence')" :labels="$monthsOf($design)" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="{{ $tmepGap <= 0 ? 'green' : 'amber' }}" icon="ri-timer-flash-line" label="TMEP no período" :value="$dec($tmep['average'], 2) . ' d'"
                    :note="($tmepGap <= 0 ? 'Dentro da meta de ' : 'Acima da meta de ') . $tmep['target'] . ' dias (' . ($tmepGap > 0 ? '+' : '') . $dec($tmepGap, 2) . ' d)'"
                    :values="array_column($tmep['months'], 'total')" :labels="array_map($label, array_column($tmep['months'], 'month'))" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-folder-open-line" label="OVs em projeto agora" :value="$int($now['total'])"
                    :note="'Levantamento (27): ' . $int($now['s27']) . ' · Desenho (28): ' . $int($now['s28'])"
                    :values="[$now['s27'], $now['s28']]" :labels="['Status 27', 'Status 28']" />
            </div>
        </div>

        <h2 class="mp-section-title">Aderência ao prazo</h2>

        <div class="row g-3 mb-3">
            @foreach ($phaseBlocks as $block)
                @php($p = $block['phase'])
                <div class="col-12 col-xl-6">
                    <section class="mp-panel h-100" aria-labelledby="pd-{{ $block['key'] }}-title">
                        <div class="mp-panel-head">
                            <div>
                                <h3 id="pd-{{ $block['key'] }}-title" class="mp-panel-title">Faixa de dias · {{ $p['label'] }}</h3>
                                <div class="mp-panel-sub">OVs concluídas por mês e faixa de dias; a linha é a % dentro do prazo de {{ $p['limit'] }} dias.</div>
                            </div>
                            <span class="mp-pill">{{ $dec($p['summary']['adherence']) }}% no prazo</span>
                        </div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart mp-chart-tall" wire:ignore>
                                <canvas data-my-panel-chart="{{ $block['key'] }}" data-config="{{ json_encode($charts[$block['key']]) }}" role="img" aria-label="Barras empilhadas: OVs de {{ strtolower($p['label']) }} concluídas por mês e faixa de dias, com a linha de aderência ao prazo"></canvas>
                            </div>
                            @if ($p['summary']['total'] === 0)
                                <div class="mp-chart-empty">Nenhuma fase concluída</div>
                            @endif
                        </div>
                        <details class="mp-table-toggle">
                            <summary>Ver como tabela</summary>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 mp-table">
                                    <caption class="visually-hidden">{{ $p['label'] }}: OVs por mês e faixa de dias</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Mês</th>
                                            <th scope="col" class="text-end">OVs</th>
                                            @foreach ($p['labels'] as $bandLabel)
                                                <th scope="col" class="text-end">{{ $bandLabel }}</th>
                                            @endforeach
                                            <th scope="col" class="text-end">% no prazo</th>
                                            <th scope="col" class="text-end">Média</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($p['months'] as $m)
                                            <tr>
                                                <td>{{ $label($m['month']) }}</td>
                                                <td class="text-end">{{ $int($m['total']) }}</td>
                                                @foreach ($m['bands'] as $band)
                                                    <td class="text-end">{{ $int($band) }}</td>
                                                @endforeach
                                                <td class="text-end fw-bold">{{ $dec($m['adherence']) }}%</td>
                                                <td class="text-end">{{ $dec($m['avg_days'], 2) }} d</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </section>
                </div>
            @endforeach
        </div>

        <h2 class="mp-section-title">TMEP e situação de agora</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">TMEP mensal</h3>
                            <div class="mp-panel-sub">Média de dias do levantamento + do desenho, por mês de término, contra a meta de {{ $tmep['target'] }} dias (linha tracejada).</div>
                        </div>
                        <span class="mp-pill">Meta {{ $tmep['target'] }} dias</span>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="tmep" data-config="{{ json_encode($charts['tmep']) }}" role="img" aria-label="Barras empilhadas: média de dias do levantamento e do desenho por mês, com a linha do TMEP e a meta de {{ $tmep['target'] }} dias"></canvas>
                        </div>
                        @if (!$hasData)
                            <div class="mp-chart-empty">Sem fases concluídas</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <div class="row g-3 h-100">
                    <div class="col-12">
                        <section class="mp-panel h-100">
                            <div class="mp-panel-head"><div><h3 class="mp-panel-title">OVs em status de projeto</h3><div class="mp-panel-sub">Situação de agora</div></div></div>
                            <div class="mp-panel-body">
                                <x-home.bar-list :rows="$nowRows" caption="OVs em status de projeto agora" :headers="['Status', 'OVs', '']" empty="Nenhuma OV em projeto" />
                            </div>
                        </section>
                    </div>
                    <div class="col-12">
                        <section class="mp-panel h-100">
                            <div class="mp-panel-head"><div><h3 class="mp-panel-title">Tempo médio × prazo</h3><div class="mp-panel-sub">O traço marca o prazo de cada fase e a meta do TMEP</div></div></div>
                            <div class="mp-panel-body">
                                <x-home.bar-list :rows="$tmepRows" caption="Tempo médio por fase" :headers="['Fase', 'Média', 'Detalhe']" empty="Sem fases concluídas" />
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mp-section-title">Quem executa</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Aderência por empresa</h3><div class="mp-panel-sub">As {{ ProjectDeadlineCharts::TOP }} empresas com mais OVs; % dentro do prazo em cada fase</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="companies" data-config="{{ json_encode($charts['companies']) }}" role="img" aria-label="Barras horizontais: percentual de aderência ao prazo do levantamento e do desenho por empresa"></canvas>
                        </div>
                        @if (count($snapshot['companies']) === 0)
                            <div class="mp-chart-empty">Sem empresas no período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100" aria-labelledby="pd-companies-title">
                    <div class="mp-panel-head">
                        <div>
                            <h3 id="pd-companies-title" class="mp-panel-title">Empresas executoras</h3>
                            <div class="mp-panel-sub">Fases concluídas em {{ $period }}. Clique no nome para filtrar o relatório. "Sem empresa" são fases sem produção concluída vinculada.</div>
                        </div>
                        <span class="mp-pill">{{ count($snapshot['companies']) }} {{ count($snapshot['companies']) === 1 ? 'empresa' : 'empresas' }}</span>
                    </div>
                    @if (count($snapshot['companies']) === 0)
                        <x-closure.empty-state icon="ri-building-line" title="Nenhuma empresa no recorte" text="Amplie o período ou limpe o filtro." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col class="col-pct-28"><col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-12"><col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Empresa</th>
                                        <th scope="col" class="text-end">Lev. OVs</th>
                                        <th scope="col" class="text-end">Lev. no prazo</th>
                                        <th scope="col" class="text-end">Lev. média</th>
                                        <th scope="col" class="text-end">Des. OVs</th>
                                        <th scope="col" class="text-end">Des. no prazo</th>
                                        <th scope="col" class="text-end">Des. média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($snapshot['companies'] as $c)
                                        @php($lev = $c[OvPhaseDuration::SURVEY] ?? null)
                                        @php($des = $c[OvPhaseDuration::DESIGN] ?? null)
                                        <tr wire:key="company-{{ $c['id'] ?? 'none' }}">
                                            <td>
                                                @if ($c['id'])
                                                    <button type="button" class="btn btn-link p-0 mp-link text-start fw-bold" wire:click="focusCompany('{{ $c['id'] }}')" title="Filtrar o relatório nesta empresa">{{ $c['name'] }}</button>
                                                @else
                                                    <span class="fw-bold text-muted">{{ $c['name'] }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $lev ? $int($lev['total']) : '—' }}</td>
                                            <td class="text-end fw-bold">{{ $lev ? $dec($lev['adherence']) . '%' : '—' }}</td>
                                            <td class="text-end">{{ $lev ? $dec($lev['avg_days'], 2) . ' d' : '—' }}</td>
                                            <td class="text-end">{{ $des ? $int($des['total']) : '—' }}</td>
                                            <td class="text-end fw-bold">{{ $des ? $dec($des['adherence']) . '%' : '—' }}</td>
                                            <td class="text-end">{{ $des ? $dec($des['avg_days'], 2) . ' d' : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
