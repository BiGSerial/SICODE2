@php
    $A = \App\Services\Engineers\D5ConsultAnalytics::class;
    $R = \App\Services\D5\D5StageResolver::class;

    $s       = $snapshot['summary'];
    $stages  = $snapshot['stages'];
    $flow    = $snapshot['flow'];
    $buckets = $snapshot['age_buckets'];
    $hasOpen = $s['open'] > 0;
    $days    = fn (?float $d) => $d === null ? '—' : ($d < 1 ? 'hoje' : (int) round($d) . ' ' . ((int) round($d) === 1 ? 'dia' : 'dias'));
    $decimal = fn (?float $d) => $d === null ? '—' : number_format($d, 1, ',', '.') . ' dias';
    $pct     = fn (int $part, int $whole) => $whole > 0 ? number_format($part / $whole * 100, 0) . '%' : '0%';

    $stageValues = array_values(array_map(fn ($x) => $x['total'], $stages));
    $stageLabels = array_values(array_map(fn ($x) => $x['label'], $stages));
    $oldestByStage = array_values(array_map(fn ($x) => $x['oldest'] ?? 0, $stages));

    $tone = fn (int $late, int $total) => $late === 0 ? 'blue' : ($total > 0 && $late / $total >= 0.5 ? 'red' : 'amber');

    $rank = function (array $rows) use ($tone) {
        $max = max(1, collect($rows)->max('total'));

        return collect($rows)->take(10)->map(fn ($r) => [
            'label' => $r['label'],
            'text'  => (string) $r['total'],
            'pct'   => $r['total'] / $max * 100,
            'tone'  => $tone($r['late'], $r['total']),
            'sub'   => $r['late'] . ' atrasada(s) · mais antiga há ' . ($r['oldest'] ?? 0) . ' d',
        ])->all();
    };

    $companyRows = $rank($snapshot['by_company']);
    $reasonRows  = $rank($snapshot['by_reason']);

    $chips = [
        ''                              => ['Todas', $s['total']],
        $R::SUPPLIER       => ['Fornecedor', $stages[$R::SUPPLIER]['total']],
        $R::FISCALIZATION  => ['Fiscalização', $stages[$R::FISCALIZATION]['total']],
        $R::PAYMENT        => ['Medição', $stages[$R::PAYMENT]['total']],
        $R::FINALIZED      => ['Finalizado', $s['finalized']],
    ];

    $filters = [
        [
            'key' => 'company',
            'label' => 'Empreiteira',
            'type' => 'multi',
            'provider' => [
                'type' => 'eloquent',
                'model' => \App\Models\Company::class,
                'value' => 'id',
                'label' => 'name',
                'distinct' => true,
                'orderBy' => ['name' => 'asc'],
                'limit' => 300,
            ],
        ],
        [
            'key' => 'type',
            'label' => 'Tipo',
            'type' => 'single',
            'provider' => [
                'type' => 'static',
                'options' => [['value' => 2, 'label' => 'OV'], ['value' => 1, 'label' => 'NOTA']],
            ],
        ],
        [
            'key' => 'passive_mode',
            'label' => 'Meta / Passivo',
            'type' => 'single',
            'provider' => [
                'type' => 'static',
                'options' => [
                    ['value' => 'both', 'label' => 'Meta + Passivo'],
                    ['value' => 'meta', 'label' => 'Meta'],
                    ['value' => 'passive', 'label' => 'Passivo'],
                ],
            ],
        ],
        [
            'key' => 'city',
            'label' => 'Municipio',
            'type' => 'multi',
            'provider' => [
                'type' => 'eloquent',
                'model' => \App\Models\City::class,
                'value' => 'rdMunicipio',
                'label' => 'cidade',
                'distinct' => true,
                'orderBy' => ['cidade' => 'asc'],
                'limit' => 300,
            ],
        ],
        [
            'key' => 'rubrica',
            'label' => 'Rubrica',
            'type' => 'multi',
            'provider' => [
                'type' => 'eloquent',
                'model' => \App\Models\Note::class,
                'value' => 'rubrica',
                'label' => 'rubrica',
                'distinct' => true,
                'orderBy' => ['rubrica' => 'asc'],
                'limit' => 300,
            ],
        ],
        [
            'key' => 'period_column',
            'label' => 'Período por',
            'type' => 'single',
            'provider' => [
                'type' => 'static',
                'options' => [
                    ['value' => 'dispatch', 'label' => 'Despacho'],
                    ['value' => 'completed', 'label' => 'Conclusão'],
                    ['value' => 'both', 'label' => 'Despacho ou Conclusão'],
                ],
            ],
        ],
        [
            'key' => 'desired_between',
            'label' => 'Período (de/ate)',
            'type' => 'daterange',
            'include_nulls' => false,
            'treat_zero_date_as_null' => false,
        ],
    ];
@endphp

<div class="closure-dash my-panel">

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Acompanhamento de D5</div>
            <h1 class="mp-hero-title">Consulta geral D5</h1>
            <p class="mp-hero-sub">Onde cada D5 está (fornecedor, fiscalização ou medição), há quantos dias espera e quem a está segurando.</p>
        </div>
        <div class="mp-hero-actions">
            <a href="#d5-lista" class="btn btn-light btn-sm btn-dash"><i class="ri-list-check-2"></i> Ir para a lista</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel da lista, nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros da consulta">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-8 col-xl-5">
                <label class="form-label small mb-1" for="d5-search">Buscar D5, nota, motivo ou empreiteira</label>
                <div class="input-group input-group-sm">
                    <input id="d5-search" type="text" class="form-control" wire:model.debounce.600ms="search" placeholder="Digite parte do número ou do nome">
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscarMultiModal" title="Busca múltipla: cole vários números de D5 ou de nota">
                        <i class="ri-checkbox-multiple-blank-line"></i> Busca múltipla
                    </button>
                </div>
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label small mb-1" for="d5-per-page">Linhas por página</label>
                <select id="d5-per-page" class="form-select form-select-sm" wire:model="perPage">
                    @foreach ([25, 50, 100, 200, 500] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            @livewire('components.filters.bar', ['config' => $filters, 'group' => 'payments', 'manualApply' => true], key('filters-bar'))
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Os cartões e gráficos mostram <strong>todas as etapas</strong> nos filtros acima; a etapa escolhida na lista só filtra a tabela.
            Dias parada = dias desde a entrada da D5 na etapa atual.
            @if ($search || count($multiD5))
                <button type="button" class="btn btn-link btn-sm p-0 ms-2" wire:click="clearSearch">limpar busca</button>
            @endif
        </div>
    </section>

    @if ($s['total'] === 0)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-file-search-line" title="Nenhuma D5 nos filtros atuais" text="Ajuste a busca, o período ou os filtros adicionais para ver as D5." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="search,perPage,buscarMulti,clearSearch">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-file-list-3-line" label="D5 em andamento" :value="$s['open']"
                    :note="$stages[$R::SUPPLIER]['total'] . ' fornecedor · ' . $stages[$R::FISCALIZATION]['total'] . ' fiscalização · ' . $stages[$R::PAYMENT]['total'] . ' medição'"
                    :values="$stageValues" :labels="$stageLabels" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-alarm-warning-line" label="Atrasadas" :value="$s['late']"
                    :note="$pct($s['late'], $s['open']) . ' das em andamento · mais de ' . $A::LATE_DAYS . ' dias na etapa · ' . $s['warn'] . ' em atenção'"
                    :values="$buckets['values']" :labels="$buckets['labels']" :ticks="4" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-timer-line" label="Mais antiga parada" :value="$days($s['oldest'])"
                    :note="'Média de ' . $decimal($s['average']) . ' na etapa atual'"
                    :values="$oldestByStage" :labels="$stageLabels" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-reply-line" label="Retorno da empreiteira" :value="$decimal($s['return_days'])"
                    :note="'Tempo médio do despacho ao retorno · ' . $s['finalized'] . ' ' . ($s['finalized'] === 1 ? 'finalizada' : 'finalizadas')"
                    :values="$flow['returned']" :labels="$flow['labels']" />
            </div>
        </div>

        <h2 class="mp-section-title">Onde estão as D5</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por etapa</h3><div class="mp-panel-sub">Distribuição das D5 nos filtros atuais</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="stage" data-config="{{ json_encode($charts['stage']) }}" role="img" aria-label="Rosca: D5 por etapa (fornecedor, fiscalização, medição e finalizado)"></canvas>
                        </div>
                        @if ($s['total'] === 0)
                            <div class="mp-chart-empty">Nenhuma D5 nos filtros</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Tempo parado em cada etapa</h3><div class="mp-panel-sub">Dias desde a entrada da D5 na etapa; atrasada = mais de {{ $A::LATE_DAYS }} dias</div></div></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 mp-table">
                            <caption class="visually-hidden">D5 em andamento por etapa, com atrasadas, mais antiga e tempo médio</caption>
                            <colgroup>
                                <col class="col-pct-20"><col class="col-pct-10"><col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-12"><col>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th scope="col">Etapa</th>
                                    <th scope="col" class="text-end">D5</th>
                                    <th scope="col" class="text-end">Em atenção</th>
                                    <th scope="col" class="text-end">Atrasadas</th>
                                    <th scope="col" class="text-end">% atrasadas</th>
                                    <th scope="col" class="text-end">Mais antiga</th>
                                    <th scope="col" class="text-end">Média</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stages as $x)
                                    <tr>
                                        <th scope="row" class="fw-semibold">{{ $x['label'] }}</th>
                                        <td class="text-end fw-bold">{{ $x['total'] }}</td>
                                        <td class="text-end">{{ $x['warn'] }}</td>
                                        <td class="text-end">@if ($x['late'] > 0)<span class="badge text-bg-danger">{{ $x['late'] }}</span>@else 0 @endif</td>
                                        <td class="text-end">{{ $pct($x['late'], $x['total']) }}</td>
                                        <td class="text-end">{{ $days($x['oldest']) }}</td>
                                        <td class="text-end">{{ $decimal($x['average']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Quem está segurando</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por empreiteira</h3><div class="mp-panel-sub">Top 10 com D5 em andamento; o detalhe traz as atrasadas e a mais antiga</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$companyRows" caption="D5 em andamento por empreiteira" :headers="['Empreiteira', 'D5', 'Detalhe']" empty="Nenhuma D5 em andamento" />
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por motivo</h3><div class="mp-panel-sub">Top 10 motivos das D5 em andamento</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$reasonRows" caption="D5 em andamento por motivo" :headers="['Motivo', 'D5', 'Detalhe']" empty="Nenhuma D5 em andamento" />
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Fluxo e envelhecimento</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Despachadas x retornadas por mês</h3><div class="mp-panel-sub">Últimos {{ $A::FLOW_MONTHS }} meses, nos filtros atuais</div></div><span class="mp-pill">{{ array_sum($flow['sent']) }} despachadas · {{ array_sum($flow['returned']) }} retornadas</span></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="flow" data-config="{{ json_encode($charts['flow']) }}" role="img" aria-label="Gráfico de linhas: D5 despachadas e retornadas pela empreiteira por mês"></canvas>
                        </div>
                        @if (array_sum($flow['sent']) === 0 && array_sum($flow['returned']) === 0)
                            <div class="mp-chart-empty">Nada despachado nem retornado neste período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Há quantos dias estão paradas?</h3><div class="mp-panel-sub">D5 em andamento por faixa de dias na etapa atual</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="age" data-config="{{ json_encode($charts['age']) }}" role="img" aria-label="Gráfico de barras: D5 em andamento por faixa de dias parada na etapa"></canvas>
                        </div>
                        @if (!$hasOpen)
                            <div class="mp-chart-empty">Nenhuma D5 em andamento</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    <h2 class="mp-section-title" id="d5-lista">Lista de D5</h2>

    <section class="mp-panel mb-3" aria-labelledby="d5-list-title" wire:loading.class="is-loading-soft" wire:target="setStage,search,perPage,buscarMulti,clearSearch,gotoPage,nextPage,previousPage">
        <div class="mp-panel-head">
            <div>
                <h3 id="d5-list-title" class="mp-panel-title">Exibindo {{ $lists->firstItem() ?? 0 }} a {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }} D5</h3>
                <div class="mp-panel-sub">Ordenada pelo retorno da empreiteira (mais antigo primeiro; sem retorno por último). “Tempo” conta os dias na etapa atual: até {{ $A::WARN_DAYS }} no prazo, até {{ $A::LATE_DAYS }} atenção, acima disso atrasada.</div>
            </div>
            <div class="mp-presets" role="group" aria-label="Etapa">
                @foreach ($chips as $key => [$label, $count])
                    <button type="button" class="mp-preset {{ $statusFilter === (string) $key ? 'is-active' : '' }}" wire:click="setStage('{{ $key }}')" aria-pressed="{{ $statusFilter === (string) $key ? 'true' : 'false' }}">{{ $label }} · {{ $count }}</button>
                @endforeach
            </div>
        </div>

        @if (!$lists->count())
            <x-closure.empty-state icon="ri-inbox-line" title="Nenhuma D5 nesta etapa" text="Troque a etapa ou ajuste os filtros." />
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 mp-table">
                    <colgroup>
                        <col class="col-pct-7"><col class="col-pct-8"><col class="col-pct-9"><col class="col-pct-11"><col class="col-pct-11"><col class="col-pct-7">
                        <col class="col-pct-7"><col class="col-pct-7"><col class="col-pct-9"><col class="col-pct-7"><col class="col-pct-9"><col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Nota D5</th>
                            <th scope="col">Informe</th>
                            <th scope="col">Nota</th>
                            <th scope="col">Empreiteira</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Despacho</th>
                            <th scope="col">Retorno</th>
                            <th scope="col">Tempo</th>
                            <th scope="col">Etapa</th>
                            <th scope="col">Status</th>
                            <th scope="col">Responsável</th>
                            <th scope="col" class="text-end">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $meta     = $list->tracking_meta ?? [];
                                $activity = $meta['activity'] ?? [];
                                $assignee = $meta['assignee'] ?? [];
                                $stageKey = $activity['key'] ?? null;

                                $activityStart = match ($stageKey) {
                                    'aguardando_pagamento'    => $list->supervisioned_at,
                                    'aguardando_fiscalizacao' => $list->completed_at,
                                    'aguardando_geracao_d5'   => $list->created_at,
                                    'aguardando_fornecedor'   => $list->dispatch_at,
                                    default                   => null,
                                };

                                // Etapa vinda do status do informe pode não ter a data própria preenchida na D5.
                                if (!$activityStart && $stageKey !== 'finalizado') {
                                    $activityStart = $list->supervisioned_at ?? $list->completed_at ?? $list->dispatch_at;
                                }

                                $waiting = $activityStart?->diffInDays();
                                [$ageTone, $ageText] = match (true) {
                                    $waiting > $A::LATE_DAYS => ['danger', 'Atrasada'],
                                    $waiting > $A::WARN_DAYS => ['warning', 'Atenção'],
                                    default                 => ['success', 'No prazo'],
                                };

                                $phaseLabel = match ($stageKey ?? '') {
                                    'aguardando_geracao_d5', 'aguardando_pagamento' => 'Medição',
                                    'aguardando_fiscalizacao'                       => 'Fiscalização',
                                    'aguardando_fornecedor'                         => 'Fornecedor',
                                    'finalizado'                                    => 'Finalizado',
                                    default                                         => '—',
                                };

                                $d5Associations = collect();
                                if ($list->work_report_id) {
                                    foreach (collect($list->WorkReport?->selected_final_scopes ?? ['general'])->filter() as $scope) {
                                        $d5Associations->push(['report' => $list->work_report_id, 'scope' => $scope]);
                                    }
                                } else {
                                    foreach (($list->productions ?? collect()) as $production) {
                                        foreach (($production->WorkReportFlowProductions ?? collect())->where('is_current', true)->whereNotNull('work_report_id') as $link) {
                                            $d5Associations->push(['report' => $link->work_report_id, 'scope' => $link->final_scope ?: 'general']);
                                        }
                                    }
                                }
                                $d5Associations = $d5Associations->unique(fn ($item) => $item['report'] . '|' . $item['scope'])->values();
                            @endphp
                            <tr wire:key="d5-{{ $list->id }}">
                                <td class="fw-bold">
                                    {{ $list->note_d5 }}
                                    @if ($list->isPassive)
                                        <div><span class="badge text-bg-info">Passiva</span></div>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($d5Associations as $association)
                                        @php
                                            $scopeLabel = match ($association['scope']) {
                                                'connection' => 'Ligação',
                                                'network'    => 'Rede',
                                                default      => 'Geral',
                                            };
                                        @endphp
                                        <div>
                                            <span class="badge bg-light text-dark border">#{{ $association['report'] }}</span>
                                            <span class="badge text-bg-{{ $association['scope'] === 'connection' ? 'warning' : 'success' }}">{{ $scopeLabel }}</span>
                                        </div>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td>
                                    {{ $list->note?->note }}
                                    <div class="small text-muted text-truncate" title="{{ $list->note?->rubrica }}">{{ $list->note?->rubrica }}</div>
                                </td>
                                <td class="fw-semibold text-truncate" title="{{ $list->company?->name }}">{{ $list->company?->name ?? '—' }}</td>
                                <td>
                                    <div class="text-truncate" title="{{ $list->reason }}">{{ $list->reason }}</div>
                                    <div class="small text-muted text-truncate" title="{{ $list->codify }}">{{ $list->codify }}</div>
                                </td>
                                <td>
                                    @if ($list->dispatch_at)
                                        {{ $list->dispatch_at->format('d/m/Y') }}
                                        <div class="small text-muted">{{ $list->dispatch_at->format('H:i') }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($list->completed_at)
                                        {{ $list->completed_at->format('d/m/Y') }}
                                        <div class="small text-muted">{{ $list->completed_at->format('H:i') }}</div>
                                    @else
                                        <span class="badge text-bg-secondary">Sem retorno</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($activityStart)
                                        <span class="badge text-bg-{{ $ageTone }}"><i class="ri-time-line me-1"></i>{{ $waiting }} {{ $waiting === 1 ? 'dia' : 'dias' }}</span>
                                        <div class="small text-muted mt-1">{{ $ageText }}</div>
                                    @else
                                        <span class="badge text-bg-secondary"><i class="ri-check-line me-1"></i>Finalizado</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-bg-primary">{{ $phaseLabel }}</span>
                                    <div>
                                        @if (($meta['cycle'] ?? 1) === 2)
                                            <span class="badge text-bg-info mt-1">Ciclo 2 · Retorno D5</span>
                                        @else
                                            <span class="badge bg-light text-dark border mt-1">Ciclo 1</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="badge {{ $activity['color'] ?? 'text-bg-secondary' }}">{{ $activity['label'] ?? 'Sem status' }}</span></td>
                                <td>
                                    @if ($assignee['has_assignee'] ?? false)
                                        <div class="fw-semibold">{{ $assignee['name'] }}</div>
                                        <div class="small text-muted">{{ $assignee['company'] ?? 'Sem empresa' }}</div>
                                    @else
                                        <div class="small text-muted">Sem responsável</div>
                                    @endif
                                    <span class="badge {{ $assignee['assignment_badge'] ?? 'text-bg-secondary' }} mt-1">{{ $assignee['assignment_status'] ?? 'Não atribuído' }}</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="row-action border-0" wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $list->note_id }})">
                                        <i class="ri-eye-line"></i> Visualizar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mp-panel-foot d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>{{ $lists->links() }}</div>
                <span class="small text-muted">{{ $lists->total() }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}</span>
            </div>
        @endif
    </section>

    {{-- Modal: Busca múltipla --}}
    <div wire:ignore.self class="modal fade" id="buscarMultiModal" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel"><i class="ri-search-2-line me-2"></i>Busca múltipla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="advanceSearch">Números de D5 ou de nota</label>
                    <textarea class="form-control" id="advanceSearch" rows="8" placeholder="Cole aqui vários números (vírgula ou quebra de linha)" wire:model.defer="advanceSearch"></textarea>
                    <div class="form-text">Separe por vírgula <strong>,</strong> ou por quebra de linha.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal"><i class="ri-check-line me-1"></i>Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modais --}}
    @livewire('components.d5.d5details', key('five-note-details'))
    @livewire('components.five-note.manual-create', key('manual-create-five'))
    @livewire('components.five-note.edit-d5', key('edit-five-note'))
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
