@php
    use App\Http\Livewire\Reports\TeamPanel as Screen;
    use App\Services\Home\MyPanel\MyPanelAnalytics as Panel;
@endphp

@php
    $ok       = fn (string ...$parts) => collect($parts)->every(fn (string $p) => $loaded[$p] ?? false);
    $s        = $snapshot['summary'];
    $filters  = $snapshot['filters'];
    $next     = $snapshot['next'];
    $hasOpen  = $s['open'] > 0;
    $hasDone  = $s['done'] > 0;
    $serviceUrl = fn (array $row) => $row['folder'] === 'contratacao'
        ? route('construction.main', ['service' => $row['service_id']])
        : route('services.main', ['service' => $row['service_id']]);
    $days     = fn (?int $d) => $d === null ? '—' : ($d === 0 ? 'hoje' : $d . ' ' . ($d === 1 ? 'dia' : 'dias'));
    $dec      = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
    $delta    = fn (?float $d) => $d === null ? 'sem período anterior' : ($d > 0 ? '+' : '') . number_format($d, 1, ',', '.') . '% vs. período anterior';
    $maxAge   = max(1, collect($snapshot['open'])->max('age_days') ?? 1);
    $daily    = $snapshot['daily'];
    $dayLabels = array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $daily['dates']);
    $buckets  = $snapshot['age_buckets'];
    $period   = $filters['from']->format('d/m/Y') . ' a ' . $filters['to']->format('d/m/Y');
    $groupTone = ['project' => 'group-project', 'construction' => 'group-construction', 'post_construction' => 'group-post', 'other' => 'group-other'];
    $maxOwnerNotas = max(1, collect($snapshot['users'])->max('notas') ?? 1);

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

    // Quanto tempo cada etapa leva
    $maxStage  = max(1, collect($snapshot['stages'])->where('key', '!=', 'total')->max('seconds') ?? 1);
    $stageRows = collect($snapshot['stages'])->where('key', '!=', 'total')->filter(fn ($r) => $r['seconds'] !== null)->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => Panel::duration($r['seconds']),
        'pct'   => $r['seconds'] / $maxStage * 100,
        'tone'  => $r['key'] === 'stopped' ? 'amber' : 'blue',
    ])->values()->all();
    $totalStage = collect($snapshot['stages'])->firstWhere('key', 'total')['seconds'] ?? null;

    $deltaBadge = function (?float $d) {
        if ($d === null) {
            return '<span class="text-muted">—</span>';
        }

        return '<span class="mp-delta ' . ($d >= 0 ? 'is-up' : 'is-down') . '"><i class="' . ($d >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line') . '" aria-hidden="true"></i>' . number_format(abs($d), 0) . '%</span>';
    };
@endphp

<div class="closure-dash my-panel team-panel">

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">{{ $greeting }}</div>
            <h1 class="mp-hero-title">Painel da equipe</h1>
            <p class="mp-hero-sub">Visão da gerência: quem está com o quê, há quanto tempo, e como foi a produção da equipe. Filtre por empresa, atividade ou pessoa.</p>
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
                <label class="form-label small mb-1" for="tp-company">Empresa</label>
                <select id="tp-company" class="form-select form-select-sm" wire:model="selectedCompany">
                    <option value="">Todas as empresas</option>
                    @foreach ($companyList as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small mb-1" for="tp-service">Atividade</label>
                <select id="tp-service" class="form-select form-select-sm" wire:model="selectedService">
                    <option value="">Todas as atividades</option>
                    @foreach ($activityList as $activity)
                        <option value="{{ $activity->uuid }}">{{ $activity->service }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small mb-1" for="tp-user">Pessoa</label>
                <select id="tp-user" class="form-select form-select-sm" wire:model="selectedUser">
                    <option value="">Toda a equipe</option>
                    @foreach ($peopleList as $person)
                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-3">
                <div class="form-check form-switch mb-0">
                    <input id="tp-ri" class="form-check-input" type="checkbox" role="switch" wire:model="includeRi">
                    <label class="form-check-label small" for="tp-ri">Incluir RI</label>
                </div>
                @if ($hasFilters)
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" wire:click="clearFilters"><i class="ri-close-circle-line"></i> Limpar filtros</button>
                @endif
            </div>
            <div class="col-12 col-xl-6">
                <span class="form-label small d-block mb-1">Período</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (Screen::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <label class="form-label small mb-1" for="tp-from">De</label>
                <input id="tp-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_in" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-xl-3">
                <label class="form-label small mb-1" for="tp-to">Até</label>
                <input id="tp-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_out" max="{{ now()->toDateString() }}">
            </div>
        </div>
        <div class="mp-period-note">
            Período exibido: <strong>{{ $period }}</strong> ({{ $filters['days'] }} {{ $filters['days'] === 1 ? 'dia' : 'dias' }}). As atividades abertas mostram sempre a situação de agora.
        </div>
    </section>

    @if ($ok('open', 'production') && !$hasOpen && !$hasDone)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-inbox-line" title="Nada nos filtros atuais" text="Não há atividades abertas nem concluídas neste recorte. Troque a empresa, a atividade ou a pessoa, ou amplie o período." />
        </section>
    @endif

    <div class="tp-body">
        <div class="tp-skeleton-overlay" wire:loading.block wire:target="selectedCompany,selectedService,selectedUser,setPreset,dt_in,dt_out,includeRi,focusUser,focusCompany,clearFilters">
            @include('livewire.reports.team-panel-skeleton')
        </div>

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('open', 'daily'))
                <x-home.hero-card tone="blue" icon="ri-folder-open-line" label="Atividades abertas" :value="$s['open']"
                    :note="$s['open_people'] . ' ' . ($s['open_people'] === 1 ? 'pessoa' : 'pessoas') . ' · ' . $s['running'] . ' em andamento · ' . $s['paused'] . ' em pausa' . ($s['unassigned'] ? ' · ' . $s['unassigned'] . ' sem responsável' : '') . ($s['ri'] ? ' · ' . $s['ri'] . ' RI' : '')"
                    :values="$daily['received']" :labels="$dayLabels" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('production', 'daily'))
                <x-home.hero-card tone="green" icon="ri-check-double-line" label="Concluídas no período" :value="$s['done']"
                    :note="$s['done_postes'] . ' postes/ativos · ' . $delta($s['done_delta'])"
                    :values="$daily['completed']" :labels="$dayLabels" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('open'))
                <x-home.hero-card tone="red" icon="ri-hourglass-2-line" label="Mais antiga em aberto" :value="$s['oldest'] === null ? '—' : $days($s['oldest'])"
                    :note="'Idade média ' . ($s['average'] !== null ? number_format($s['average'], 1, ',', '.') . ' dias' : '—') . ' · ' . $s['over_30'] . ' com mais de 30 dias'"
                    :values="$buckets['values']" :labels="$buckets['labels']" :ticks="5" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('production', 'daily'))
                <x-home.hero-card tone="amber" icon="ri-team-line" label="Ritmo da equipe" :value="$dec($s['per_person'])"
                    :note="'notas por pessoa/dia · ' . $s['people'] . ' ' . ($s['people'] === 1 ? 'pessoa produziu' : 'pessoas produziram') . ($s['idle_people'] ? ' · ' . $s['idle_people'] . ' sem conclusões' : '')"
                    :values="$daily['people']" :labels="$dayLabels" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
        </div>

@if ($ok('open'))
        @if ($next)
            <section class="mp-next mb-4" aria-labelledby="tp-next-title">
                <div class="mp-next-body">
                    <span class="mp-chip">Maior atenção · mais antiga em aberto</span>
                    <h3 id="tp-next-title" class="mp-next-title">{{ $next['service'] }} · Nota {{ $next['note'] }}</h3>
                    <p class="mp-next-meta mb-0">
                        <span class="badge text-bg-{{ $next['status_color'] }}">{{ $next['status'] }}</span>
                        @if ($next['ri']) <span class="badge text-bg-danger">RI</span> @endif
                        Com <strong>{{ $next['user'] }}</strong> ({{ $next['company'] }}) há <strong>{{ $days($next['age_days']) }}</strong>
                        @if ($next['att_at']) · ATT {{ $next['att_at']->format('d/m/Y') }} @endif
                        @if ($next['informed_at']) · informe {{ $next['informed_at']->format('d/m/Y') }} @endif
                    </p>
                </div>
                <a href="{{ $serviceUrl($next) }}" class="btn btn-success btn-dash">Abrir {{ $next['service'] }} <i class="ri-arrow-right-line"></i></a>
            </section>
        @endif

@else
        <div class="tp-skel tp-skel-next mb-4"></div>

@endif
        <h2 class="mp-section-title">Fila de trabalho</h2>

@if ($ok('open'))
        {{-- Mais antigas (maior para menor) --}}
        <section class="mp-panel mb-3" aria-labelledby="tp-oldest-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="tp-oldest-title" class="mp-panel-title">Atividades abertas, da mais antiga para a mais recente</h3>
                    <div class="mp-panel-sub">Tempo desde a ATT; Fiscalização, Medição e Publicação contam desde o informe da Nota.</div>
                </div>
                <span class="mp-pill">{{ $s['open'] }} {{ $s['open'] === 1 ? 'aberta' : 'abertas' }}</span>
            </div>
            @if (count($oldest) === 0)
                <x-closure.empty-state icon="ri-checkbox-circle-line" title="Nenhuma atividade aberta" text="Não há nada em aberto neste recorte." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table mp-oldest">
                        <colgroup>
                            <col class="col-pct-5"><col class="col-pct-18"><col class="col-pct-10"><col class="col-pct-18"><col class="col-pct-10"><col class="col-pct-8"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">#</th>
                                <th scope="col">Atividade</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Responsável</th>
                                <th scope="col">Situação</th>
                                <th scope="col">ATT</th>
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
                                    <td>
                                        @if ($row['user_id'])
                                            <button type="button" class="btn btn-link p-0 mp-link text-start" wire:click="focusUser('{{ $row['user_id'] }}')" title="Filtrar o painel nesta pessoa">{{ $row['user'] }}</button>
                                        @else
                                            <span class="text-muted">{{ $row['user'] }}</span>
                                        @endif
                                        <div class="small text-muted">{{ $row['company'] }}</div>
                                    </td>
                                    <td><span class="badge text-bg-{{ $row['status_color'] }}">{{ $row['status'] }}</span></td>
                                    <td>{{ $row['att_at']?->format('d/m/Y') ?? '—' }}</td>
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
                            {{ $showAllOldest ? 'Mostrar só as ' . Screen::OLDEST_VISIBLE . ' mais antigas' : 'Ver mais ' . min($oldestMore, Screen::OLDEST_EXPANDED - Screen::OLDEST_VISIBLE) }}
                        </button>
                        @if ($s['open'] > Screen::OLDEST_EXPANDED && $showAllOldest)
                            <span class="small text-muted ms-2">A lista completa está no Excel.</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>

        {{-- Carga por pessoa / onde está / situação --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Carga em aberto por pessoa</h3><div class="mp-panel-sub">As {{ \App\Services\Reports\TeamPanel\TeamPanelCharts::TOP }} pessoas com mais abertas, por idade</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="load" data-config="{{ json_encode($charts['load']) }}" role="img" aria-label="Barras empilhadas: atividades abertas por pessoa e faixa de idade"></canvas>
                        </div>
                        @if (!$hasOpen)
                            <div class="mp-chart-empty">Nenhuma atividade aberta</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
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
            <div class="col-12 col-md-6 col-xl-3">
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

        <div class="row g-3 mb-4">
            <div class="col-12">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Há quanto tempo está aberto?</h3><div class="mp-panel-sub">Mais antiga por atividade; o traço marca a idade média</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$ageRows" caption="Tempo em aberto por atividade" :headers="['Atividade', 'Mais antiga', 'Detalhe']" empty="Nenhuma atividade aberta" />
                    </div>
                </section>
            </div>
        </div>

@else
        <div class="tp-skel tp-skel-chart mb-3"></div>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-5"><div class="tp-skel tp-skel-chart"></div></div>
            <div class="col-12 col-md-6 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="tp-skel tp-skel-chart"></div></div>
        </div>

@endif
        <h2 class="mp-section-title">Pessoas</h2>

@if ($ok('production'))
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100" aria-labelledby="tp-users-title">
                    <div class="mp-panel-head">
                        <div>
                            <h3 id="tp-users-title" class="mp-panel-title">Produção e carga por pessoa</h3>
                            <div class="mp-panel-sub">Concluídas de {{ $period }}; média da equipe: {{ $dec($snapshot['team_per_day']) }} notas por pessoa em cada dia trabalhado. Clique no nome para filtrar.</div>
                        </div>
                        <span class="mp-pill">{{ count($snapshot['users']) }} {{ count($snapshot['users']) === 1 ? 'pessoa' : 'pessoas' }}</span>
                    </div>
                    @if (count($userRows) === 0)
                        <x-closure.empty-state icon="ri-team-line" title="Nenhuma pessoa no recorte" text="Amplie o período ou troque os filtros." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col class="col-pct-24"><col class="col-pct-18"><col class="col-pct-7"><col class="col-pct-7"><col class="col-pct-9"><col class="col-pct-9"><col class="col-pct-9"><col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Pessoa</th>
                                        <th scope="col">Concluídas</th>
                                        <th scope="col" class="text-end">Postes</th>
                                        <th scope="col" class="text-end">Dias</th>
                                        <th scope="col" class="text-end">Notas/dia</th>
                                        <th scope="col" class="text-end">vs. equipe</th>
                                        <th scope="col" class="text-end">Abertas</th>
                                        <th scope="col" class="text-end">Mais antiga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($userRows as $row)
                                        <tr wire:key="user-{{ $row['user_id'] }}">
                                            <td>
                                                <button type="button" class="btn btn-link p-0 mp-link text-start fw-bold" wire:click="focusUser('{{ $row['user_id'] }}')" title="Filtrar o painel nesta pessoa">{{ $row['name'] }}</button>
                                                <div class="small text-muted">
                                                    {{ $row['company'] }}
                                                    @if ($row['idle']) · <span class="text-danger">sem conclusões no período</span> @elseif ($row['last_done']) · última {{ $row['last_done']->format('d/m') }} @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="mp-age mp-age-green">
                                                    <span class="mp-age-bar" aria-hidden="true"><span style="--pct: {{ $row['notas'] > 0 ? max(2, $row['notas'] / $maxOwnerNotas * 100) : 0 }}"></span></span>
                                                    <strong>{{ $row['notas'] }}</strong> <small class="text-muted">{{ number_format($row['share'], 0) }}%</small>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $row['postes'] }}</td>
                                            <td class="text-end">{{ $row['dias'] }}</td>
                                            <td class="text-end fw-bold">{{ $dec($row['per_day']) }}</td>
                                            <td class="text-end">{!! $deltaBadge($row['delta']) !!}</td>
                                            <td class="text-end">{{ $row['open'] }}@if ($row['open_ri']) <small class="text-danger">({{ $row['open_ri'] }} RI)</small> @endif</td>
                                            <td class="text-end">{{ $days($row['open_oldest']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($usersMore > 0)
                            <div class="mp-panel-foot">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-dash" wire:click="$toggle('showAllUsers')">
                                    {{ $showAllUsers ? 'Mostrar só as ' . Screen::USERS_VISIBLE . ' primeiras' : 'Ver mais ' . min($usersMore, Screen::USERS_EXPANDED - Screen::USERS_VISIBLE) }}
                                </button>
                                @if (count($snapshot['users']) > Screen::USERS_EXPANDED && $showAllUsers)
                                    <span class="small text-muted ms-2">A lista completa está no Excel.</span>
                                @endif
                            </div>
                        @endif
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Quem mais produziu</h3><div class="mp-panel-sub">Concluídas no período × abertas agora</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="users" data-config="{{ json_encode($charts['users']) }}" role="img" aria-label="Barras: notas concluídas e abertas por pessoa"></canvas>
                        </div>
                        @if (!$hasDone && !$hasOpen)
                            <div class="mp-chart-empty">Sem dados no recorte</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

@else
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8"><div class="tp-skel tp-skel-chart"></div></div>
            <div class="col-12 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
        </div>

@endif
        <h2 class="mp-section-title">Empresas</h2>

@if ($ok('production'))
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100" aria-labelledby="tp-companies-title">
                    <div class="mp-panel-head">
                        <div><h3 id="tp-companies-title" class="mp-panel-title">Produção e carga por empresa</h3><div class="mp-panel-sub">Concluídas de {{ $period }} e o que está aberto agora. Clique na empresa para filtrar.</div></div>
                        <span class="mp-pill">{{ count($snapshot['companies']) }} {{ count($snapshot['companies']) === 1 ? 'empresa' : 'empresas' }}</span>
                    </div>
                    @if (count($snapshot['companies']) === 0)
                        <x-closure.empty-state icon="ri-building-line" title="Nenhuma empresa no recorte" text="Amplie o período ou troque os filtros." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col class="col-pct-28"><col class="col-pct-8"><col class="col-pct-10"><col class="col-pct-9"><col class="col-pct-10"><col class="col-pct-9"><col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Empresa</th>
                                        <th scope="col" class="text-end">Pessoas</th>
                                        <th scope="col" class="text-end">Concluídas</th>
                                        <th scope="col" class="text-end">Postes</th>
                                        <th scope="col" class="text-end">vs. equipe</th>
                                        <th scope="col" class="text-end">Abertas</th>
                                        <th scope="col" class="text-end">Mais antiga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (array_slice($snapshot['companies'], 0, 15) as $row)
                                        <tr wire:key="company-{{ $row['company_id'] }}">
                                            <td><button type="button" class="btn btn-link p-0 mp-link text-start fw-bold" wire:click="focusCompany('{{ $row['company_id'] }}')" title="Filtrar o painel nesta empresa">{{ $row['name'] }}</button></td>
                                            <td class="text-end">{{ $row['pessoas'] }}</td>
                                            <td class="text-end fw-bold">{{ $row['notas'] }} <small class="text-muted">{{ number_format($row['share'], 0) }}%</small></td>
                                            <td class="text-end">{{ $row['postes'] }}</td>
                                            <td class="text-end">{!! $deltaBadge($row['delta']) !!}</td>
                                            <td class="text-end">{{ $row['open'] }}</td>
                                            <td class="text-end">{{ $days($row['open_oldest']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (count($snapshot['companies']) > 15)
                            <div class="mp-panel-foot"><span class="small text-muted">As 15 maiores; a lista completa está no Excel.</span></div>
                        @endif
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Empresas em comparação</h3><div class="mp-panel-sub">Concluídas no período × abertas agora</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="companies" data-config="{{ json_encode($charts['companies']) }}" role="img" aria-label="Barras: notas concluídas e abertas por empresa"></canvas>
                        </div>
                        @if (count($snapshot['companies']) === 0)
                            <div class="mp-chart-empty">Sem dados no recorte</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

@else
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7"><div class="tp-skel tp-skel-chart"></div></div>
            <div class="col-12 col-xl-5"><div class="tp-skel tp-skel-chart"></div></div>
        </div>

@endif
        <h2 class="mp-section-title">Produtividade</h2>

@if ($ok('production'))
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100" aria-labelledby="tp-prod-title">
                    <div class="mp-panel-head">
                        <div>
                            <h3 id="tp-prod-title" class="mp-panel-title">Produtividade por atividade</h3>
                            <div class="mp-panel-sub">Concluídas de {{ $period }}. Notas por pessoa em cada dia trabalhado; "vs. equipe" compara com a média geral do recorte.</div>
                        </div>
                        <span class="mp-pill">{{ $s['done'] }} notas · {{ $s['done_postes'] }} postes</span>
                    </div>
                    @if (count($snapshot['activities']) === 0)
                        <x-closure.empty-state icon="ri-line-chart-line" title="Nenhuma conclusão no período" text="Amplie o período ou troque os filtros para ver a produtividade." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col class="col-pct-24"><col class="col-pct-20"><col class="col-pct-8"><col class="col-pct-8"><col class="col-pct-10"><col class="col-pct-10"><col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Atividade</th>
                                        <th scope="col">Notas</th>
                                        <th scope="col" class="text-end">Postes</th>
                                        <th scope="col" class="text-end">Pessoas</th>
                                        <th scope="col" class="text-end">Notas/pessoa/dia</th>
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
                                            <td class="text-end">{{ $row['pessoas'] }}</td>
                                            <td class="text-end fw-bold">{{ $dec($row['per_day']) }}</td>
                                            <td class="text-end">{!! $deltaBadge($row['delta']) !!}</td>
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
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Atividade × média da equipe</h3><div class="mp-panel-sub">Notas por pessoa por dia trabalhado</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="productivity" data-config="{{ json_encode($charts['productivity']) }}" role="img" aria-label="Barras: notas por pessoa por dia trabalhado, por atividade, contra a média da equipe"></canvas>
                        </div>
                        @if (count($snapshot['activities']) === 0)
                            <div class="mp-chart-empty">Nenhuma conclusão no período</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

@else
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7"><div class="tp-skel tp-skel-chart"></div></div>
            <div class="col-12 col-xl-5"><div class="tp-skel tp-skel-chart"></div></div>
        </div>

@endif
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                @if ($ok('daily'))
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
                @else
                    <div class="tp-skel tp-skel-chart"></div>
                @endif
            </div>
            <div class="col-12 col-xl-4">
                @if ($ok('production'))
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div><h3 class="mp-panel-title">Quanto tempo cada etapa leva?</h3><div class="mp-panel-sub">Média das concluídas no período</div></div>
                        @if ($totalStage !== null) <span class="mp-pill" title="Média do início (ATT, ou informe nas pós-obra) até concluir; não é a soma das etapas.">Até concluir: {{ Panel::duration($totalStage) }}</span> @endif
                    </div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$stageRows" caption="Tempo médio por etapa" :headers="['Etapa', 'Tempo médio', '']" empty="Sem conclusões no período" />
                    </div>
                </section>
                @else
                    <div class="tp-skel tp-skel-chart"></div>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12">
                @if ($ok('monthly'))
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
                @else
                    <div class="tp-skel tp-skel-chart"></div>
                @endif
            </div>
        </div>
    </div>

    @if ($nextPart)
        <div wire:key="load-{{ $nextPart }}" wire:init="loadNext" class="visually-hidden" aria-hidden="true"></div>
    @endif
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
