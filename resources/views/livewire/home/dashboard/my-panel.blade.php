@php
    use App\Services\Home\MyPanel\MyPanelAnalytics as Panel;

    $s        = $snapshot['summary'];
    $filters  = $snapshot['filters'];
    $next     = $snapshot['next'];
    $hasOpen  = $s['open'] > 0;
    $hasDone  = $s['done'] > 0;
    $serviceUrl = fn (array $row) => $row['folder'] === 'contratacao'
        ? route('construction.main', ['service' => $row['service_id']])
        : route('services.main', ['service' => $row['service_id']]);
    $days     = fn (?int $d) => $d === null ? '—' : ($d === 0 ? 'hoje' : $d . ' ' . ($d === 1 ? 'dia' : 'dias'));
    $delta    = fn (?float $d) => $d === null ? 'sem período anterior' : ($d > 0 ? '+' : '') . number_format($d, 1, ',', '.') . '% vs. período anterior';
    $maxAge   = max(1, collect($snapshot['open'])->max('age_days') ?? 1);
    $daily    = $snapshot['daily'];
    $buckets  = $snapshot['age_buckets'];
    $period   = $filters['from']->format('d/m/Y') . ' a ' . $filters['to']->format('d/m/Y');
    $groupTone = ['project' => 'group-project', 'construction' => 'group-construction', 'post_construction' => 'group-post', 'other' => 'group-other'];

    // Onde está o trabalho: abertas por atividade
    $openRows = collect($snapshot['open_by'])->sortByDesc('total')->map(fn ($r) => [
        'label' => $r['service'],
        'text'  => (string) $r['total'],
        'pct'   => $r['total'] / max(1, collect($snapshot['open_by'])->max('total')) * 100,
        'tone'  => $groupTone[$r['group']],
        'sub'   => Panel::GROUP_LABELS[$r['group']] . ($r['ri'] ? ' · ' . $r['ri'] . ' RI' : ''),
    ])->values()->all();

    // Há quanto tempo: mais antiga por atividade (marcador = idade média)
    $ageRows = collect($snapshot['open_by'])->map(fn ($r) => [
        'label' => $r['service'],
        'text'  => $days($r['oldest']),
        'pct'   => ($r['oldest'] ?? 0) / $maxAge * 100,
        'mark'  => ($r['average'] ?? 0) / $maxAge * 100,
        'tone'  => 'blue',
        'sub'   => 'Média ' . ($r['average'] !== null ? number_format($r['average'], 1, ',', '.') . ' dias' : '—') . ' · ' . $r['total'] . ' abertas',
    ])->all();

    // Onde está parado: por situação
    $statusRows = collect($snapshot['status'])->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => $r['total'] . ' · ' . round($r['total'] / max(1, $s['open']) * 100) . '%',
        'pct'   => $r['total'] / max(1, $s['open']) * 100,
        'tone'  => match ($r['color']) { 'danger' => 'red', 'warning' => 'amber', 'success' => 'green', 'primary' => 'blue', default => 'muted' },
    ])->all();

    // Quanto tempo cada etapa leva
    $maxStage  = max(1, collect($snapshot['stages'])->where('key', '!=', 'total')->max('seconds') ?? 1);
    $stageRows = collect($snapshot['stages'])->where('key', '!=', 'total')->filter(fn ($r) => $r['seconds'] !== null)->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => Panel::duration($r['seconds']),
        'pct'   => $r['seconds'] / $maxStage * 100,
        'tone'  => $r['key'] === 'stopped' ? 'amber' : 'blue',
    ])->values()->all();
    $totalStage = collect($snapshot['stages'])->firstWhere('key', 'total')['seconds'] ?? null;
@endphp

<div class="closure-dash my-panel">

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">{{ now()->translatedFormat('l, d \d\e F') }}</div>
            <h1 class="mp-hero-title">Meu painel</h1>
            <p class="mp-hero-sub">{{ $greeting }}, {{ $firstName }}. O que está com você, há quanto tempo e como foi a sua produção.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel completo do painel, com uma aba por bloco, nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small mb-1" for="mp-service">Atividade</label>
                <select id="mp-service" class="form-select form-select-sm" wire:model="selectedService">
                    <option value="">Todas as atividades</option>
                    @foreach ($activities as $activity)
                        <option value="{{ $activity->uuid }}">{{ $activity->service }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-4">
                <span class="form-label small d-block mb-1">Período</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (\App\Http\Livewire\Home\Dashboard\MyPanel::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="mp-from">De</label>
                <input id="mp-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_in" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="mp-to">Até</label>
                <input id="mp-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_out" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-12 col-xl-1">
                <div class="form-check form-switch mb-0">
                    <input id="mp-ri" class="form-check-input" type="checkbox" role="switch" wire:model="includeRi">
                    <label class="form-check-label small" for="mp-ri">Incluir RI</label>
                </div>
            </div>
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Período exibido: <strong>{{ $period }}</strong> ({{ $filters['days'] }} {{ $filters['days'] === 1 ? 'dia' : 'dias' }}). As atividades abertas mostram sempre a situação de agora.
        </div>
    </section>

    @if (!$hasOpen && !$hasDone)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-inbox-line" title="Nada com você nos filtros atuais" text="Não há atividades abertas nem concluídas neste período. Troque a atividade ou amplie o período." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="selectedService,setPreset,dt_in,dt_out,includeRi">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-folder-open-line" label="Atividades abertas" :value="$s['open']"
                    :note="$s['running'] . ' em andamento · ' . $s['paused'] . ' em pausa · ' . $s['open_services'] . ' ' . ($s['open_services'] === 1 ? 'atividade' : 'atividades') . ($s['ri'] ? ' · ' . $s['ri'] . ' RI' : '')"
                    :values="$daily['received']" :labels="array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $daily['dates'])" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-check-double-line" label="Concluídas no período" :value="$s['done']"
                    :note="$s['done_postes'] . ' postes/ativos · ' . $delta($s['done_delta'])"
                    :values="$daily['completed']" :labels="array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $daily['dates'])" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-hourglass-2-line" label="Mais antiga em aberto" :value="$s['oldest'] === null ? '—' : $days($s['oldest'])"
                    :note="'Idade média ' . ($s['average'] !== null ? number_format($s['average'], 1, ',', '.') . ' dias' : '—') . ($next ? ' · ' . $next['service'] : '')"
                    :values="$buckets['values']" :labels="$buckets['labels']" :ticks="5" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-lightbulb-flash-line" label="Postes/ativos no período" :value="$s['done_postes']"
                    :note="'Hoje: ' . $s['today'] . ' ' . ($s['today'] === 1 ? 'nota' : 'notas') . ' · ' . $s['today_postes'] . ' postes/ativos'"
                    :values="$daily['postes']" :labels="array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $daily['dates'])" />
            </div>
        </div>

        @if ($next)
            <section class="mp-next mb-4" aria-labelledby="mp-next-title">
                <div class="mp-next-body">
                    <span class="mp-chip">Seu próximo passo · mais antiga em aberto</span>
                    <h3 id="mp-next-title" class="mp-next-title">{{ $next['service'] }} · Nota {{ $next['note'] }}</h3>
                    <p class="mp-next-meta mb-0">
                        <span class="badge text-bg-{{ $next['status_color'] }}">{{ $next['status'] }}</span>
                        @if ($next['ri']) <span class="badge text-bg-danger">RI</span> @endif
                        Aberta há <strong>{{ $days($next['age_days']) }}</strong>
                        @if ($next['att_at']) · ATT {{ $next['att_at']->format('d/m/Y') }} @endif
                        @if ($next['informed_at']) · informe {{ $next['informed_at']->format('d/m/Y') }} @endif
                    </p>
                </div>
                <a href="{{ $serviceUrl($next) }}" class="btn btn-success btn-dash">Abrir {{ $next['service'] }} <i class="ri-arrow-right-line"></i></a>
            </section>
        @endif

        <h2 class="mp-section-title">Fila de trabalho</h2>

        {{-- Mais antigas (maior para menor) --}}
        <section class="mp-panel mb-3" aria-labelledby="mp-oldest-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="mp-oldest-title" class="mp-panel-title">Atividades abertas, da mais antiga para a mais recente</h3>
                    <div class="mp-panel-sub">Tempo desde a ATT; Fiscalização, Medição e Publicação contam desde o informe da Nota.</div>
                </div>
                <span class="mp-pill">{{ $s['open'] }} {{ $s['open'] === 1 ? 'aberta' : 'abertas' }}</span>
            </div>
            @if (count($oldest) === 0)
                <x-closure.empty-state icon="ri-checkbox-circle-line" title="Nenhuma atividade aberta" text="Tudo o que estava com você foi concluído." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table mp-oldest">
                        <colgroup>
                            <col class="col-pct-5"><col class="col-pct-20"><col class="col-pct-12"><col class="col-pct-12"><col class="col-pct-10"><col class="col-pct-10"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">#</th>
                                <th scope="col">Atividade</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Situação</th>
                                <th scope="col">ATT</th>
                                <th scope="col">Informe</th>
                                <th scope="col">Tempo em aberto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($oldest as $row)
                                <tr wire:key="oldest-{{ $row['production_id'] }}">
                                    <td class="text-center"><span class="mp-rank">{{ $loop->iteration }}</span></td>
                                    <td>
                                        <a href="{{ $serviceUrl($row) }}" class="mp-link">{{ $row['service'] }}</a>
                                        @if ($row['ri']) <span class="badge text-bg-danger ms-1">RI</span> @endif
                                        <div class="small text-muted">{{ Panel::GROUP_LABELS[$row['group']] }}</div>
                                    </td>
                                    <td class="fw-bold">{{ $row['note'] }}</td>
                                    <td><span class="badge text-bg-{{ $row['status_color'] }}">{{ $row['status'] }}</span></td>
                                    <td>{{ $row['att_at']?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $row['informed_at']?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <div class="mp-age">
                                            <span class="mp-age-bar" aria-hidden="true"><span style="--pct: {{ max(2, ($row['age_days'] ?? 0) / $maxAge * 100) }}"></span></span>
                                            <strong>{{ $days($row['age_days']) }}</strong>
                                            @if ($row['basis'] && $row['basis'] !== 'ATT') <small class="text-muted">desde o {{ $row['basis'] }}</small> @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($oldestMore > 0)
                    <div class="mp-panel-foot">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-dash" wire:click="$toggle('showAllOldest')">
                            {{ $showAllOldest ? 'Mostrar só as ' . \App\Http\Livewire\Home\Dashboard\MyPanel::OLDEST_VISIBLE . ' mais antigas' : 'Ver mais ' . min($oldestMore, \App\Http\Livewire\Home\Dashboard\MyPanel::OLDEST_EXPANDED - \App\Http\Livewire\Home\Dashboard\MyPanel::OLDEST_VISIBLE) }}
                        </button>
                        @if ($s['open'] > \App\Http\Livewire\Home\Dashboard\MyPanel::OLDEST_EXPANDED && $showAllOldest)
                            <span class="small text-muted ms-2">A lista completa está no Excel.</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>

        {{-- Onde está o trabalho / há quanto tempo / situação --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Onde está o trabalho?</h3><div class="mp-panel-sub">Abertas por atividade</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$openRows" caption="Atividades abertas por atividade" :headers="['Atividade', 'Abertas', 'Grupo']" empty="Nenhuma atividade aberta" />
                        @if ($hasOpen)
                            <div class="mp-legend">
                                <span><i class="mp-dot tone-group-project"></i>Projeto</span>
                                <span><i class="mp-dot tone-group-construction"></i>Construção</span>
                                <span><i class="mp-dot tone-group-post"></i>Pós-obra</span>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Há quanto tempo está aberto?</h3><div class="mp-panel-sub">Mais antiga por atividade; o traço marca a idade média</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$ageRows" caption="Tempo em aberto por atividade" :headers="['Atividade', 'Mais antiga', 'Detalhe']" empty="Nenhuma atividade aberta" />
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Em que situação estão?</h3><div class="mp-panel-sub">Abertas por situação</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="situation" data-config="{{ json_encode($charts['situation']) }}" role="img" aria-label="Rosca: atividades abertas por situação"></canvas>
                        </div>
                        @if (!$hasOpen)
                            <div class="mp-chart-empty">Nenhuma atividade aberta</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Produtividade</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100" aria-labelledby="mp-prod-title">
                    <div class="mp-panel-head">
                        <div>
                            <h3 id="mp-prod-title" class="mp-panel-title">Produtividade por atividade</h3>
                            <div class="mp-panel-sub">Concluídas de {{ $period }}. Média da equipe: por pessoa em cada dia trabalhado.</div>
                        </div>
                        <span class="mp-pill">{{ $s['done'] }} notas · {{ $s['done_postes'] }} postes</span>
                    </div>
                    @if (count($snapshot['activities']) === 0)
                        <x-closure.empty-state icon="ri-line-chart-line" title="Nenhuma conclusão no período" text="Amplie o período ou troque a atividade para ver a produtividade." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col class="col-pct-22"><col class="col-pct-20"><col class="col-pct-8"><col class="col-pct-8"><col class="col-pct-10"><col class="col-pct-12"><col class="col-pct-10"><col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Atividade</th>
                                        <th scope="col">Notas</th>
                                        <th scope="col" class="text-end">Postes</th>
                                        <th scope="col" class="text-end">Dias</th>
                                        <th scope="col" class="text-end">Notas/dia</th>
                                        <th scope="col" class="text-end">Equipe</th>
                                        <th scope="col" class="text-end">vs. equipe</th>
                                        <th scope="col" class="text-end">Tempo médio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($snapshot['activities'] as $row)
                                        <tr wire:key="prod-{{ $row['service_id'] }}">
                                            <td><strong>{{ $row['service'] }}</strong><div class="small text-muted">{{ Panel::GROUP_LABELS[$row['group']] }}</div></td>
                                            <td>
                                                <div class="mp-age mp-age-green">
                                                    <span class="mp-age-bar" aria-hidden="true"><span style="--pct: {{ max(2, $row['share']) }}"></span></span>
                                                    <strong>{{ $row['notas'] }}</strong> <small class="text-muted">{{ number_format($row['share'], 0) }}%</small>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $row['postes'] }}</td>
                                            <td class="text-end">{{ $row['dias'] }}</td>
                                            <td class="text-end fw-bold">{{ number_format($row['per_day'], 1, ',', '.') }}</td>
                                            <td class="text-end">{{ $row['team_per_day'] !== null ? number_format($row['team_per_day'], 1, ',', '.') : '—' }}</td>
                                            <td class="text-end">
                                                @if ($row['delta'] === null)
                                                    <span class="text-muted">—</span>
                                                @else
                                                    <span class="mp-delta {{ $row['delta'] >= 0 ? 'is-up' : 'is-down' }}">
                                                        <i class="{{ $row['delta'] >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line' }}" aria-hidden="true"></i>{{ number_format(abs($row['delta']), 0) }}%
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ Panel::duration($row['avg_total']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Você × equipe</h3><div class="mp-panel-sub">Notas por dia trabalhado, por atividade</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="productivity" data-config="{{ json_encode($charts['productivity']) }}" role="img" aria-label="Barras: notas por dia trabalhado por atividade, você contra a média da equipe"></canvas>
                        </div>
                        @if (count($snapshot['activities']) === 0)
                            <div class="mp-chart-empty">Nenhuma conclusão no período</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Como foi o período?</h3><div class="mp-panel-sub">Recebidas (ATT) e concluídas por dia</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="daily" data-config="{{ json_encode($charts['daily']) }}" role="img" aria-label="Gráfico de linhas: atividades recebidas e concluídas por dia no período"></canvas>
                        </div>
                        @if (array_sum($daily['received']) + array_sum($daily['completed']) === 0)
                            <div class="mp-chart-empty">Nada recebido nem concluído neste período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Últimos 7 dias</h3><div class="mp-panel-sub">{{ $snapshot['week']['total'] }} concluídas · linha tracejada: média da equipe por pessoa/dia</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="week" data-config="{{ json_encode($charts['week']) }}" role="img" aria-label="Gráfico de barras: atividades concluídas por dia nos últimos 7 dias"></canvas>
                        </div>
                        @if ($snapshot['week']['total'] === 0)
                            <div class="mp-chart-empty">Nenhuma conclusão nos últimos 7 dias</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div><h3 class="mp-panel-title">Quanto tempo cada etapa leva?</h3><div class="mp-panel-sub">Média das concluídas no período</div></div>
                        @if ($totalStage !== null) <span class="mp-pill" title="Média do início (ATT, ou informe nas pós-obra) até concluir; não é a soma das etapas.">Até concluir: {{ Panel::duration($totalStage) }}</span> @endif
                    </div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$stageRows" caption="Tempo médio por etapa" :headers="['Etapa', 'Tempo médio', '']" empty="Sem conclusões no período" />
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Evolução mensal</h3><div class="mp-panel-sub">Notas concluídas por mês e grupo de atividade (12 meses)</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="monthly" data-config="{{ json_encode($charts['monthly']) }}" role="img" aria-label="Gráfico de barras empilhadas: notas concluídas por mês e grupo de atividade"></canvas>
                        </div>
                        @if (array_sum(array_map('array_sum', $snapshot['monthly']['series'])) === 0)
                            <div class="mp-chart-empty">Nenhuma conclusão nos últimos 12 meses</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Movimentação</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Atividade recente</h3><div class="mp-panel-sub">Últimos registros na linha do tempo</div></div></div>
                    <ul class="mp-recent" role="list">
                        @forelse ($recent as $item)
                            <li wire:key="recent-{{ $item->id }}">
                                <span class="mp-recent-time" title="{{ $item->created_at->format('d/m/Y H:i') }}">{{ $item->created_at->diffForHumans(null, true, true) }}</span>
                                <span class="mp-recent-text">
                                    <strong>{{ $item->note->note ?? 'N/A' }} · {{ $item->service->service ?? 'N/A' }}</strong>
                                    <span>{{ $item->info }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="mp-recent-empty">Nenhum registro recente.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Transferências pendentes <span class="badge bg-warning text-dark ms-2 d-none" id="transfercount"></span></h3></div></div>
                    <div class="mp-panel-body">
                        @livewire('home.tools.transfer-notes', ['idCount' => 'transfercount'], key('transfer-notes'))
                    </div>
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
