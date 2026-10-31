@php
    use App\Http\Livewire\Engineers\InformDashboard as Screen;
    use App\Services\Engineers\{InformDashboardAnalytics as Data, InformDashboardCharts as Charts};
    use Carbon\Carbon;

    $s       = $snapshot['summary'];
    $series  = $snapshot['series'];
    $b       = $s['buckets'];
    $n       = fn (int|float|null $v) => number_format((int) $v, 0, ',', '.');
    $pct     = fn (float|int|null $v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.') . '%';
    $hasData = $s['total'] > 0;
    $topReason = $snapshot['reasons'][0] ?? null;
    $badge   = ['on_time' => 'success', 'late' => 'warning', 'overdue' => 'danger', 'open' => 'info', 'legacy' => 'dark', 'returned' => 'secondary'];
    $companyRows = array_slice($snapshot['companies'], 0, Screen::BREAKDOWN_VISIBLE);
    $maxStatus   = max(1, $snapshot['statuses'][0]['total'] ?? 1);
    $statusBars  = collect($snapshot['statuses'])->take(Screen::BREAKDOWN_VISIBLE)->map(fn ($r) => [
        'label' => $r['label'],
        'text'  => $n($r['total']),
        'pct'   => $r['total'] / $maxStatus * 100,
        'tone'  => 'teal',
        'sub'   => $pct($s['total'] ? $r['total'] / $s['total'] * 100 : 0) . ' dos informes',
    ])->values()->all();
@endphp

<div class="closure-dash my-panel inform-dashboard">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · Informes</div>
            <h1 class="mp-hero-title">Painel de informes</h1>
            <p class="mp-hero-sub">Como estão os informes de conclusão de obra: o que chegou, o que foi devolvido e por quê, a entrega da ADS no prazo e onde cada informe está hoje.</p>
        </div>
        <div class="mp-hero-actions">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="Export" wire:loading.attr="disabled" wire:target="Export" title="Excel completo (indicadores e informes) nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="id-search">Buscar</label>
                <input id="id-search" type="search" class="form-control form-control-sm" wire:model.debounce.700ms="search" placeholder="Nota, ordem ou empreiteira">
            </div>
            @if ($canPickCompany)
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label small mb-1" for="id-company">Empreiteira</label>
                    <select id="id-company" class="form-select form-select-sm" wire:model="company">
                        <option value="">Todas</option>
                        @foreach ($companyList as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-6 col-xl-2">
                <label class="form-label small mb-1" for="id-returned">Devolução</label>
                <select id="id-returned" class="form-select form-select-sm" wire:model="returned">
                    <option value="">Todos</option>
                    <option value="sim">Já devolvidos</option>
                    <option value="nao">Nunca devolvidos</option>
                    <option value="aguardando">Aguardando novo informe</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label class="form-label small mb-1" for="id-retry">Reinforme</label>
                <select id="id-retry" class="form-select form-select-sm" wire:model="retry">
                    <option value="">Todos</option>
                    <option value="sim">Só reinformes</option>
                    <option value="nao">Sem reinforme</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label class="form-label small mb-1" for="id-status">Situação atual</label>
                <select id="id-status" class="form-select form-select-sm" wire:model="status">
                    <option value="">Todas</option>
                    @foreach ($statusList as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-xl-6">
                <span class="form-label small d-block mb-1">Período (data do informe)</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (Screen::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="id-month">Mês</label>
                <input id="id-month" type="month" class="form-control form-control-sm" wire:model.lazy="monthYear">
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="id-from">De</label>
                <input id="id-from" type="date" class="form-control form-control-sm" wire:model.lazy="dt_init">
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label small mb-1" for="id-to">Até</label>
                <input id="id-to" type="date" class="form-control form-control-sm" wire:model.lazy="dt_end" min="{{ $dt_init }}">
            </div>
        </div>
        <div class="mp-period-note d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                Período exibido: <strong>{{ $period }}</strong> ({{ $filters['days'] }} {{ $filters['days'] === 1 ? 'dia' : 'dias' }}).
                @if ($filters['truncated'])
                    O painel cobre no máximo {{ Data::MAX_DAYS }} dias: foi mantido o trecho mais recente.
                @endif
            </span>
            @if ($hasFilters)
                <button type="button" class="btn btn-link btn-sm p-0" wire:click="cleanAll"><i class="ri-close-circle-line"></i> Limpar filtros</button>
            @endif
        </div>
    </section>

    @if (!$hasData)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-inbox-line" title="Nenhum informe nos filtros atuais" text="Nenhum informe de conclusão com data neste período. Amplie o período ou limpe os filtros." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="search,company,returned,retry,status,setPreset,setAds,monthYear,dt_init,dt_end,cleanAll">

        <h2 class="mp-section-title">Painel executivo</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-file-list-3-line" label="Informes recebidos" :value="$n($s['total'])"
                    :note="$n($s['retries']) . ' reinformes · ' . $n($s['waiting_new']) . ' aguardando novo informe'"
                    :values="$series['received']" :labels="$series['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-checkbox-circle-line" label="ADS entregues no prazo" :value="$pct($s['pct_on_time'])"
                    :note="$n($b['on_time']) . ' de ' . $n($s['due_base']) . ' informes com prazo a cobrar'"
                    :values="$series['on_time']" :labels="$series['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-alarm-warning-line" label="ADS vencidas sem entrega" :value="$n($b['overdue'])"
                    :note="$n($s['expiring']) . ' vencem em até 3 dias · ' . $n($b['open']) . ' em aberto no prazo'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-arrow-go-back-line" label="Taxa de devolução" :value="$pct($s['pct_returned'])"
                    :note="$n($s['returned']) . ' informes devolvidos' . ($topReason ? ' · mais comum: ' . mb_strtolower($topReason['reason']) : '')"
                    :values="$series['returned']" :labels="$series['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-time-line" label="ADS entregues em atraso" :value="$n($b['late'])"
                    :note="'Atraso médio ' . Data::days($s['avg_late']) . ' · multa média prevista ' . $pct($s['avg_penalty'])"
                    :values="$series['late']" :labels="$series['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-timer-line" label="Tempo médio até a ADS" :value="Data::days($s['avg_delivery'])"
                    note="Do informe até a entrega da ADS, nos informes com ADS" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-file-warning-line" label="ADS tácitas" :value="$n($s['tacit'])"
                    :note="$n($s['tacit_pending']) . ' ainda sem entrega da ADS definitiva'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-repeat-line" label="Reinformes" :value="$n($s['retries'])"
                    :note="$pct($s['total'] ? $s['retries'] / $s['total'] * 100 : null) . ' dos informes foram reenviados após devolução'" />
            </div>
        </div>

        <h2 class="mp-section-title">Recebimento e entrega da ADS</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Informes {{ $series['unit'] === 'day' ? 'por dia' : 'por mês' }}</h3><div class="mp-panel-sub">Recebidos, devolvidos e com ADS em atraso ou vencida, pela data do informe</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="evolution" data-config="{{ json_encode($charts['evolution']) }}" role="img" aria-label="Linhas: informes recebidos, devolvidos e com ADS em atraso ou vencida no período"></canvas>
                        </div>
                        @if (!$hasData)
                            <div class="mp-chart-empty">Nenhum informe no período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Situação da ADS</h3><div class="mp-panel-sub">Cada informe em um único grupo</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="ads" data-config="{{ json_encode($charts['ads']) }}" role="img" aria-label="Rosca: informes por situação da entrega da ADS"></canvas>
                        </div>
                        @if (!$hasData)
                            <div class="mp-chart-empty">Sem dados no recorte</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Empreiteiras e devoluções</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">ADS por empreiteira</h3><div class="mp-panel-sub">As {{ Data::TOP }} com mais informes, pela situação da ADS</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="companies" data-config="{{ json_encode($charts['companies']) }}" role="img" aria-label="Barras empilhadas: situação da ADS dos informes por empreiteira"></canvas>
                        </div>
                        @if (!count($snapshot['companies']))
                            <div class="mp-chart-empty">Sem dados no recorte</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Motivos de devolução</h3><div class="mp-panel-sub">Os {{ Data::TOP }} mais frequentes nos informes do período</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="reasons" data-config="{{ json_encode($charts['reasons']) }}" role="img" aria-label="Barras: devoluções por motivo"></canvas>
                        </div>
                        @if (!count($snapshot['reasons']))
                            <div class="mp-chart-empty">Nenhuma devolução no recorte</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div><h3 class="mp-panel-title">Quadro por empreiteira</h3><div class="mp-panel-sub">Devolução e cumprimento do prazo da ADS</div></div>
                        <span class="mp-pill">{{ count($snapshot['companies']) }} {{ count($snapshot['companies']) === 1 ? 'empreiteira' : 'empreiteiras' }}</span>
                    </div>
                    @if (!count($companyRows))
                        <x-closure.empty-state icon="ri-building-line" title="Sem empreiteiras no recorte" />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup>
                                    <col><col class="col-pct-10"><col class="col-pct-12"><col class="col-pct-10"><col class="col-pct-10"><col class="col-pct-10"><col class="col-pct-12">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Empreiteira</th>
                                        <th scope="col" class="text-end">Informes</th>
                                        <th scope="col" class="text-end">Devolvidos</th>
                                        <th scope="col" class="text-end">No prazo</th>
                                        <th scope="col" class="text-end">Atraso</th>
                                        <th scope="col" class="text-end">Vencidas</th>
                                        <th scope="col" class="text-end">% no prazo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($companyRows as $row)
                                        <tr wire:key="co-{{ $loop->index }}-{{ md5($row['name']) }}">
                                            <td class="cell-wrap fw-bold">{{ $row['name'] }}</td>
                                            <td class="text-end">{{ $n($row['total']) }}</td>
                                            <td class="text-end">{{ $n($row['returned']) }} <span class="small text-muted">({{ $pct($row['pct_returned']) }})</span></td>
                                            <td class="text-end">{{ $row['on_time'] ? $n($row['on_time']) : '–' }}</td>
                                            <td class="text-end">{{ $row['late'] ? $n($row['late']) : '–' }}</td>
                                            <td class="text-end">{!! $row['overdue'] ? '<span class="text-danger fw-bold">' . $n($row['overdue']) . '</span>' : '–' !!}</td>
                                            <td class="text-end fw-bold">{{ $pct($row['pct_on_time']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (count($snapshot['companies']) > count($companyRows))
                            <div class="mp-panel-foot small text-muted">{{ count($companyRows) }} de {{ count($snapshot['companies']) }} empreiteiras. A lista completa está no Excel.</div>
                        @endif
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Onde os informes estão hoje</h3><div class="mp-panel-sub">Situação atual no fluxo de encerramento</div></div></div>
                    <div class="mp-panel-body">
                        <x-home.bar-list :rows="$statusBars" caption="Informes por situação atual" :headers="['Situação', 'Informes', 'Detalhe']" empty="Sem informes no recorte" />
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Informes do período</h2>

        <section class="mp-panel mb-3">
            <div class="mp-panel-head">
                <div><h3 class="mp-panel-title">Lista de informes</h3><div class="mp-panel-sub">Do mais recente para o mais antigo. Filtre pela situação da ADS.</div></div>
                <span class="mp-pill">{{ $n($listTotal) }} {{ $listTotal === 1 ? 'informe' : 'informes' }}</span>
            </div>
            <div class="px-3 pt-2">
                <div class="mp-presets" role="group" aria-label="Situação da ADS">
                    <button type="button" class="mp-preset {{ $ads === '' ? 'is-active' : '' }}" wire:click="setAds('')" aria-pressed="{{ $ads === '' ? 'true' : 'false' }}">Todos ({{ $n($s['total']) }})</button>
                    @foreach (Data::BUCKETS as $key => $label)
                        <button type="button" class="mp-preset {{ $ads === $key ? 'is-active' : '' }}" wire:click="setAds('{{ $key }}')" aria-pressed="{{ $ads === $key ? 'true' : 'false' }}">{{ $label }} ({{ $n($b[$key]) }})</button>
                    @endforeach
                </div>
            </div>

            @if ($lists->count() === 0)
                <x-closure.empty-state icon="ri-search-line" title="Nenhum informe encontrado" text="Nenhum informe atende aos filtros atuais." />
            @else
                <div class="table-responsive table-card">
                    <table class="table table-sm align-middle table-striped mp-table table-min-lg">
                        <colgroup>
                            <col class="col-pct-8"><col class="col-pct-8"><col class="col-pct-14"><col class="col-pct-12"><col class="col-pct-16">
                            <col class="col-pct-7"><col class="col-pct-14"><col class="col-pct-7"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Informe</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col">Situação atual</th>
                                <th scope="col">ADS</th>
                                <th scope="col" class="text-end">Atraso</th>
                                <th scope="col">Devoluções</th>
                                <th scope="col">Marcas</th>
                                <th scope="col" class="text-end">Detalhe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $row)
                                @php
                                    $informed  = Carbon::parse($row['informed_at']);
                                    $due       = $row['due_at'] ? Carbon::parse($row['due_at']) : null;
                                    $delivered = $row['delivered_at'] ? Carbon::parse($row['delivered_at']) : null;
                                @endphp
                                <tr wire:key="inf-{{ $row['id'] }}">
                                    <td class="cell-wrap" title="{{ $informed->format('d/m/Y H:i:s') }}"><div>{{ $informed->format('d/m/Y') }}</div><div class="small text-muted">{{ $informed->format('H:i') }}</div></td>
                                    <td class="fw-bold">{{ $row['note'] }}</td>
                                    <td class="cell-wrap">{{ $row['company'] }}</td>
                                    <td class="cell-wrap small">{{ $row['status_label'] }}</td>
                                    <td class="cell-wrap">
                                        <span class="badge text-bg-{{ $badge[$row['bucket']] }}">{{ Data::BUCKETS[$row['bucket']] }}</span>
                                        @if ($row['bucket'] !== 'returned')
                                            <div class="small text-muted">
                                                @if ($delivered) Entregue {{ $delivered->format('d/m/Y H:i') }} @elseif ($due) Prazo {{ $due->format('d/m/Y') }} @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($row['delay_days'])
                                            <span class="fw-bold text-danger">{{ $row['delay_days'] }} d</span>
                                            @if ($row['penalty'])<div class="small text-muted">{{ $pct($row['penalty']) }} multa</div>@endif
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td class="cell-wrap small">
                                        @if ($row['returns'])
                                            <strong>{{ $row['returns'] }}×</strong> {{ $row['last_return'] }}
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['retry']) <span class="badge text-bg-primary" title="Informe reenviado após devolução">Reinforme</span> @endif
                                        @if ($row['tacit']) <span class="badge text-bg-warning" title="ADS tácita">Tácita</span> @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="row-action border-0" title="Ver o informe" wire:click.prevent="$emitTo('engineers.inform-detail', 'open_inform', 'final', {{ $row['id'] }})"><i class="ri-eye-line"></i> Ver</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mp-panel-foot">{{ $lists->links() }}</div>
            @endif
        </section>
    </div>

    @livewire('engineers.inform-detail', key('inform-dash-detail'))
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
