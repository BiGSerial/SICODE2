@php
    use App\Http\Livewire\Reports\Productions as Screen;
    use App\Services\Reports\Production\{ProductionReportAnalytics as Data, ProductionReportCharts as Charts};
    use Carbon\CarbonInterval;

    $ok       = fn (string ...$parts) => collect($parts)->every(fn (string $p) => $loaded[$p] ?? false);
    $s        = $snapshot['summary'];
    $series   = $snapshot['series'];
    $flags    = $snapshot['flags'];
    $n        = fn (int|float|null $v) => number_format((int) $v, 0, ',', '.');
    $pct      = fn (float|int|null $v) => number_format((float) $v, 1, ',', '.') . '%';
    $total    = $lists instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $lists->total() : null;
    $hasData  = $allowed && $ok('summary') ? ($s['total'] ?? 0) > 0 : $lists->count() > 0;
    $companyRows = collect($snapshot['companies'])->take(Screen::BREAKDOWN_VISIBLE);
    $maxCompany  = max(1, $companyRows->max('total') ?? 1);
    $companyBars = $companyRows->map(fn ($r) => [
        'label' => $r['name'],
        'text'  => $n($r['total']),
        'pct'   => $r['total'] / $maxCompany * 100,
        'tone'  => 'teal',
        'sub'   => $n($r['done']) . ' concluídos · ' . $n($r['open']) . ' em aberto · ' . $n($r['postes']) . ' postes/ativos',
    ])->values()->all();
    $conclusionRows = array_slice($snapshot['conclusions'], 0, 15);
@endphp

<div class="closure-dash my-panel production-report">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Serviços · Relatórios</div>
            <h1 class="mp-hero-title">Relatório de Produção</h1>
            <p class="mp-hero-sub">O que a equipe produziu, do mais novo para o mais antigo: filtre por atividade, empresa, pessoa, conclusão, dependência de órgão externo e meio ambiente.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="Export" wire:loading.attr="disabled" wire:target="Export" title="Excel completo (lista e indicadores) nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do relatório">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small mb-1" for="pr-search">Buscar</label>
                <div class="input-group input-group-sm">
                    <input id="pr-search" type="search" class="form-control" wire:model.debounce.700ms="search" placeholder="Nota, ordem, material, grupo 2 ou 5 (use * como curinga)">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#buscar_multi" type="button" title="Busca por várias notas, materiais ou ordens">
                        <i class="ri-file-copy-line"></i>
                    </button>
                </div>
                @if (count($multisearch))
                    <div class="mt-1 small">
                        <span class="badge text-bg-primary">Busca múltipla: {{ count($multisearch) }} {{ count($multisearch) === 1 ? 'termo' : 'termos' }}</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-1" wire:click="clearMulti"><i class="ri-close-circle-line"></i> remover</button>
                    </div>
                @endif
            </div>
            @if ($canPickCompany)
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label small mb-1" for="pr-company">Empresa</label>
                    <select id="pr-company" class="form-select form-select-sm" wire:model="company">
                        <option value="">Todas</option>
                        @foreach ($companyList as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="pr-user">Pessoa</label>
                <select id="pr-user" class="form-select form-select-sm" wire:model="selectedUser">
                    <option value="">Todas as pessoas</option>
                    @foreach ($peopleList as $person)
                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="pr-confirmed">Contabilização</label>
                <select id="pr-confirmed" class="form-select form-select-sm" wire:model="confirmed">
                    <option value="">Todas</option>
                    <option value="sim">Contabilizado</option>
                    <option value="nao">Não contabilizado</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small mb-1" for="pr-services">Atividades <span class="text-muted">(Ctrl para várias)</span></label>
                <select id="pr-services" class="form-select form-select-sm" wire:model="service" multiple size="5">
                    @foreach ($serviceList as $item)
                        <option value="{{ $item->uuid }}">{{ $item->service }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small mb-1" for="pr-conclusions">Conclusões <span class="text-muted">(Ctrl para várias)</span></label>
                <select id="pr-conclusions" class="form-select form-select-sm" wire:model="conclusions" multiple size="5">
                    <option value="{{ Data::NO_CONCLUSION }}">{{ Data::NO_CONCLUSION }}</option>
                    @foreach ($conclusionList as $item)
                        <option value="{{ $item['value'] }}">{{ $item['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-4">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small mb-1" for="pr-doe">Órgão externo</label>
                        <select id="pr-doe" class="form-select form-select-sm" wire:model="doe">
                            <option value="">Todos</option>
                            <option value="sim">Depende</option>
                            <option value="nao">Não depende</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="pr-ma">Meio ambiente</label>
                        <select id="pr-ma" class="form-select form-select-sm" wire:model="ma">
                            <option value="">Todos</option>
                            <option value="sim">Depende</option>
                            <option value="nao">Não depende</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mb-1">
                            <input id="pr-open" class="form-check-input" type="checkbox" role="switch" wire:model="complete">
                            <label class="form-check-label small" for="pr-open">Incluir em aberto</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input id="pr-ri" class="form-check-input" type="checkbox" role="switch" wire:model="d5">
                            <label class="form-check-label small" for="pr-ri">Incluir RI</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <span class="form-label small d-block mb-1">Período (data de conclusão)</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (Screen::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="pr-month">Mês</label>
                <input id="pr-month" type="month" class="form-control form-control-sm" wire:model.lazy="monthYear">
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="pr-from">De</label>
                <input id="pr-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_init">
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="pr-to">Até</label>
                <input id="pr-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_end" min="{{ $dt_init }}">
            </div>
        </div>
        <div class="mp-period-note d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                Período exibido: <strong>{{ $period }}</strong>. {{ $complete ? 'As atividades em aberto entram sempre, em qualquer período.' : 'Só atividades concluídas.' }}
            </span>
            @if ($hasFilters)
                <button type="button" class="btn btn-link btn-sm p-0" wire:click="cleanAll"><i class="ri-close-circle-line"></i> Limpar filtros</button>
            @endif
        </div>
    </section>

    @if (!$hasData)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-inbox-line" title="Nada nos filtros atuais" text="Nenhum registro de produção neste recorte. Amplie o período, inclua as atividades em aberto ou limpe os filtros." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="search,company,selectedUser,confirmed,service,conclusions,doe,ma,complete,d5,setPreset,monthYear,dt_init,dt_end,cleanAll,focusConclusion,buscarMulti,clearMulti">

        @if (!$allowed)
            <section class="mp-panel mb-3">
                <x-closure.empty-state icon="ri-calendar-schedule-line" :title="'Indicadores e gráficos para períodos de até ' . $maxDays . ' dias'" text="Escolha um período mais curto (por exemplo, Mês atual ou um mês específico) para ver o painel. A lista abaixo e o Excel cobrem qualquer período." />
            </section>
        @else
        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('summary'))
                    <x-home.hero-card tone="blue" icon="ri-file-list-3-line" label="Registros no filtro" :value="$n($s['total'])"
                        :note="$n($s['done']) . ' concluídos · ' . $n($s['open']) . ' em aberto · ' . $n($s['postes']) . ' postes/ativos' . ($s['ri'] ? ' · ' . $n($s['ri']) . ' RI' : '')"
                        :values="$series['done']" :labels="$series['labels']" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('summary'))
                    <x-home.hero-card tone="amber" icon="ri-government-line" label="Dependem de órgão externo" :value="$n($s['doe'])"
                        :note="$pct($s['pct_doe']) . ' dos registros do filtro'"
                        :values="$series['doe']" :labels="$series['labels']" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('summary'))
                    <x-home.hero-card tone="green" icon="ri-plant-line" label="Meio ambiente" :value="$n($s['ma'])"
                        :note="$pct($s['pct_ma']) . ' dos registros do filtro'"
                        :values="$series['ma']" :labels="$series['labels']" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                @if ($ok('summary'))
                    <x-home.hero-card tone="red" icon="ri-timer-line" label="Tempo médio de execução" :value="Data::duration($s['avg_total'])"
                        :note="'Do início ao fim das concluídas · parada média ' . Data::duration($s['avg_stopped']) . ' · ' . $pct($s['pct_counted']) . ' contabilizadas'" />
                @else
                    <div class="tp-skel tp-skel-hero"></div>
                @endif
            </div>
        </div>

        <h2 class="mp-section-title">Evolução e dependências</h2>

        @if ($ok('summary'))
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-8">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Concluídas {{ $series['unit'] === 'day' ? 'por dia' : 'por mês' }}</h3><div class="mp-panel-sub">Com a parcela que depende de órgão externo e de meio ambiente</div></div></div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart" wire:ignore>
                                <canvas data-my-panel-chart="evolution" data-config="{{ json_encode($charts['evolution']) }}" role="img" aria-label="Linhas: registros concluídos, dependentes de órgão externo e de meio ambiente no período"></canvas>
                            </div>
                            @if (array_sum($series['done']) === 0)
                                <div class="mp-chart-empty">Nenhuma conclusão no período</div>
                            @endif
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-4">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Órgão externo × meio ambiente</h3><div class="mp-panel-sub">Cada registro em um único grupo</div></div></div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart mp-chart-donut" wire:ignore>
                                <canvas data-my-panel-chart="flags" data-config="{{ json_encode($charts['flags']) }}" role="img" aria-label="Rosca: registros que dependem de órgão externo, de meio ambiente, dos dois ou de nenhum"></canvas>
                            </div>
                            @if (!($s['total'] ?? 0))
                                <div class="mp-chart-empty">Sem dados no recorte</div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        @else
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-8"><div class="tp-skel tp-skel-chart"></div></div>
                <div class="col-12 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
            </div>
        @endif

        <h2 class="mp-section-title">Conclusões</h2>

        @if ($ok('conclusions'))
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-5">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Conclusões mais frequentes</h3><div class="mp-panel-sub">As {{ Charts::TOP }} maiores, nos filtros atuais</div></div></div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart mp-chart-tall" wire:ignore>
                                <canvas data-my-panel-chart="conclusions" data-config="{{ json_encode($charts['conclusions']) }}" role="img" aria-label="Barras: registros por conclusão da análise"></canvas>
                            </div>
                            @if (!count($snapshot['conclusions']))
                                <div class="mp-chart-empty">Sem dados no recorte</div>
                            @endif
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-7">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head">
                            <div><h3 class="mp-panel-title">Todas as conclusões</h3><div class="mp-panel-sub">Clique numa conclusão para filtrar a lista nela</div></div>
                            <span class="mp-pill">{{ count($snapshot['conclusions']) }} {{ count($snapshot['conclusions']) === 1 ? 'tipo' : 'tipos' }}</span>
                        </div>
                        @if (!count($conclusionRows))
                            <x-closure.empty-state icon="ri-list-check-2" title="Sem conclusões no recorte" />
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 mp-table">
                                    <colgroup>
                                        <col><col class="col-pct-14"><col class="col-pct-12"><col class="col-pct-18">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th scope="col">Conclusão</th>
                                            <th scope="col" class="text-end">Registros</th>
                                            <th scope="col" class="text-end">%</th>
                                            <th scope="col" class="text-end">Meio ambiente</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($conclusionRows as $row)
                                            <tr wire:key="concl-{{ $loop->index }}-{{ md5($row['conclusion']) }}">
                                                <td class="cell-wrap">
                                                    <button type="button" class="btn btn-link p-0 mp-link text-start" wire:click="focusConclusion({{ json_encode($row['conclusion']) }})" title="Filtrar a lista nesta conclusão">{{ $row['conclusion'] }}</button>
                                                </td>
                                                <td class="text-end fw-bold">{{ $n($row['total']) }}</td>
                                                <td class="text-end text-muted">{{ $pct($row['pct']) }}</td>
                                                <td class="text-end">{{ $row['ma'] ? $n($row['ma']) : '–' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if (count($snapshot['conclusions']) > count($conclusionRows))
                                <div class="mp-panel-foot small text-muted">{{ count($conclusionRows) }} de {{ count($snapshot['conclusions']) }} tipos. A lista completa está no Excel.</div>
                            @endif
                        @endif
                    </section>
                </div>
            </div>
        @else
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-5"><div class="tp-skel tp-skel-chart"></div></div>
                <div class="col-12 col-xl-7"><div class="tp-skel tp-skel-chart"></div></div>
            </div>
        @endif

        <h2 class="mp-section-title">Atividades, pessoas e empresas</h2>

        @if ($ok('breakdown'))
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-4">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por atividade</h3><div class="mp-panel-sub">Concluídas × em aberto ({{ Charts::TOP }} maiores)</div></div></div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart mp-chart-tall" wire:ignore>
                                <canvas data-my-panel-chart="services" data-config="{{ json_encode($charts['services']) }}" role="img" aria-label="Barras: registros concluídos e em aberto por atividade"></canvas>
                            </div>
                            @if (!count($snapshot['services']))
                                <div class="mp-chart-empty">Sem dados no recorte</div>
                            @endif
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-4">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por pessoa</h3><div class="mp-panel-sub">Concluídas × em aberto ({{ Charts::TOP }} maiores)</div></div></div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart mp-chart-tall" wire:ignore>
                                <canvas data-my-panel-chart="users" data-config="{{ json_encode($charts['users']) }}" role="img" aria-label="Barras: registros concluídos e em aberto por pessoa"></canvas>
                            </div>
                            @if (!count($snapshot['users']))
                                <div class="mp-chart-empty">Sem dados no recorte</div>
                            @endif
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-4">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">Por empresa</h3><div class="mp-panel-sub">Registros no filtro</div></div></div>
                        <div class="mp-panel-body">
                            <x-home.bar-list :rows="$companyBars" caption="Registros por empresa" :headers="['Empresa', 'Registros', 'Detalhe']" empty="Sem dados no recorte" />
                        </div>
                    </section>
                </div>
            </div>
        @else
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
                <div class="col-12 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
                <div class="col-12 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
            </div>
        @endif

        @endif

        <h2 class="mp-section-title">Registros</h2>

        <section class="mp-panel mb-4" aria-labelledby="pr-list-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="pr-list-title" class="mp-panel-title">Produção, da mais recente para a mais antiga</h3>
                    <div class="mp-panel-sub">
                        @if ($total !== null)
                            Exibindo {{ $n($lists->firstItem()) }} a {{ $n($lists->lastItem()) }} de {{ $n($total) }} registros.
                        @else
                            Exibindo {{ $n($lists->firstItem()) }} a {{ $n($lists->lastItem()) }}. O total e os indicadores aparecem em períodos de até {{ $maxDays }} dias; o Excel traz tudo.
                        @endif
                    </div>
                </div>
                @if ($total !== null)
                    <span class="mp-pill">{{ $n($total) }} {{ $total === 1 ? 'registro' : 'registros' }}</span>
                @endif
            </div>

            @if ($lists->count() === 0)
                <x-closure.empty-state icon="ri-search-line" title="Nenhum registro encontrado" text="Nenhum registro atende aos filtros atuais." />
            @else
                <div class="table-responsive table-card">
                    <table class="table table-sm align-middle table-striped mp-table table-min-lg">
                        <colgroup>
                            <col class="col-pct-7"><col class="col-pct-7"><col class="col-pct-9"><col class="col-pct-10"><col class="col-pct-8">
                            <col class="col-pct-8"><col class="col-pct-14"><col class="col-pct-7"><col class="col-pct-5"><col class="col-pct-5"><col class="col-pct-9"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Fim</th>
                                <th scope="col">Início</th>
                                <th scope="col">Usuário</th>
                                <th scope="col">Empresa</th>
                                <th scope="col">Atividade</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Material · Grupos</th>
                                <th scope="col">Marcas</th>
                                <th scope="col">Parado</th>
                                <th scope="col" class="text-end">Postes</th>
                                <th scope="col">Situação</th>
                                <th scope="col">Conclusão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $row)
                                @php $note = $row->Note; @endphp
                                <tr wire:key="prod-{{ $row->id }}">
                                    <td class="cell-wrap" title="{{ $row->completed_at?->format('d/m/Y H:i:s') }}">
                                        @if ($row->completed_at)
                                            <div>{{ $row->completed_at->format('d/m/Y') }}</div><div class="small text-muted">{{ $row->completed_at->format('H:i') }}</div>
                                        @else
                                            <span class="badge text-bg-light border">Em aberto</span>
                                        @endif
                                    </td>
                                    <td class="cell-wrap" title="{{ $row->att_at?->format('d/m/Y H:i:s') }}">
                                        @if ($row->att_at)
                                            <div>{{ $row->att_at->format('d/m/Y') }}</div><div class="small text-muted">{{ $row->att_at->format('H:i') }}</div>
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td title="{{ $row->User->name ?? '' }}">{{ $row->User->name ?? 'Desconhecido' }}</td>
                                    <td class="cell-wrap">{{ $row->Company->name ?? '—' }}</td>
                                    <td title="{{ $row->Service->service ?? '' }}">{{ $row->Service->service ?? '—' }}</td>
                                    <td class="fw-bold">{{ $note->note ?? '—' }}</td>
                                    <td class="cell-wrap">
                                        <div>{{ $note->material ?? '—' }}</div>
                                        <div class="small text-muted">{{ collect([$note->group2 ?? null, $note->group5 ?? null])->filter()->implode(' · ') }}</div>
                                    </td>
                                    <td>
                                        @if ($note?->doe) <span class="badge text-bg-warning" title="Depende de órgão externo">DOE</span> @endif
                                        @if ($row->ma) <span class="badge text-bg-success" title="Meio ambiente">MA</span> @endif
                                        @if ($row->d5) <span class="badge text-bg-danger" title="Retorno interno">RI</span> @endif
                                    </td>
                                    <td>{{ $row->stopped ? CarbonInterval::seconds($row->stopped)->cascade()->forHumans(['short' => true, 'parts' => 2]) : '–' }}</td>
                                    <td class="text-end">{{ $row->postes_u ? $n(Data::postes($row)) : '–' }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $row->confirmed ? 'success' : 'secondary' }}">{{ $row->confirmed ? 'Contabilizado' : 'Não contabilizado' }}</span>
                                    </td>
                                    <td class="cell-wrap fw-bold small">{{ $row->Analise->conclusion ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mp-panel-foot">{{ $lists->links() }}</div>
            @endif
        </section>
    </div>

    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title">Buscar várias notas, materiais ou ordens</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small" for="advanceSearch">Um valor por linha (ou separados por vírgula/espaço)</label>
                    <textarea class="form-control" id="advanceSearch" rows="10" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">Aplicar</button>
                </div>
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
