@php
    use App\Services\Ads\{AdsConnectionProbe, AdsDashboardAnalytics};

    $health   = $s['health'];
    $isOk     = fn (string $section) => $s[$section]['state'] === AdsDashboardAnalytics::STATE_OK;
    $summary  = $isOk('summary') ? $s['summary']['data'] : null;
    $flow     = $isOk('flow') ? $s['flow']['data'] : null;
    $queueSec = $s['queue'];
    $reuseSec = $s['reuse'];
    $dates    = $flow['labels'] ?? [];
    $sync     = $health[AdsConnectionProbe::LIVE]['up'];

    // Texto de prazo → cor (a regra de cálculo do prazo é do service; aqui só a apresentação).
    $deadlineTone = fn (string $label) => str_starts_with($label, 'Vencido') ? 'text-danger' : (str_starts_with($label, 'Entregue com atraso') ? 'text-warning' : (str_starts_with($label, 'Entregue no prazo') ? 'text-success' : 'text-muted'));

@endphp

<div class="closure-dash my-panel" wire:poll.keep-alive.120s="refreshCharts">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Engenharia · ADS</div>
            <h1 class="mp-hero-title">ADS solicitadas</h1>
            <p class="mp-hero-sub">Quantas ADS foram pedidas e entregues, quanto tempo levam, o que está na fila agora e o quanto se economiza reaproveitando ADS já disponíveis.</p>
        </div>
        <div class="mp-hero-actions d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click="recheck" wire:loading.attr="disabled" wire:target="recheck" title="Testa agora a conexão com o banco local e com o SQL Server">
                <i class="ri-wifi-line"></i> Testar conexão
            </button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click="syncLast40Days" wire:loading.attr="disabled" wire:target="syncLast40Days" @disabled(!$sync)
                title="{{ $sync ? 'Traz do SQL Server as ADS dos últimos 40 dias' : 'Indisponível: sem conexão com o SQL Server' }}">
                <span wire:loading.remove wire:target="syncLast40Days"><i class="ri-refresh-line"></i> Sincronizar 40 dias</span>
                <span wire:loading wire:target="syncLast40Days"><i class="ri-loader-4-line"></i> Sincronizando...</span>
            </button>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel com resumo, fluxo diário, fila atual e histórico nos filtros atuais">
                <i class="ri-file-excel-2-line"></i> Exportar Excel
            </button>
        </div>
    </header>

    {{-- Conexões --}}
    <div class="mp-conn" role="status" aria-label="Status das conexões">
        @foreach ($health as $connection)
            <span class="mp-conn-item {{ $connection['up'] ? '' : 'is-down' }}">
                <span class="mp-conn-dot" aria-hidden="true"></span>
                {{ $connection['label'] }}: {{ $connection['up'] ? 'online' : 'sem conexão' }}
                <small>testado {{ $connection['checked_at']->format('H:i:s') }}</small>
            </span>
        @endforeach
        @if (collect($health)->contains(fn ($c) => !$c['up']))
            <small class="text-muted">As partes que dependem da conexão ficam desabilitadas; o restante do painel continua funcionando.</small>
        @endif
    </div>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-xl-4">
                <span class="form-label small d-block mb-1">Período</span>
                <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                    @foreach (\App\Http\Livewire\Reports\AdsRequestedReport::PRESETS as $key => $label)
                        <button type="button" class="mp-preset {{ $preset === $key ? 'is-active' : '' }}" wire:click="setPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="ads-dt-in">De</label>
                <input id="ads-dt-in" type="date" class="form-control form-control-sm" wire:model.lazy="dt_in" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="ads-dt-out">Até</label>
                <input id="ads-dt-out" type="date" class="form-control form-control-sm" wire:model.lazy="dt_out" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label small mb-1" for="ads-by">Período pela data de</label>
                <select id="ads-by" class="form-select form-select-sm" wire:model="dateBy">
                    <option value="request">Solicitação</option>
                    <option value="completed">Conclusão</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="ads-company">Empreiteira</label>
                <select id="ads-company" class="form-select form-select-sm" wire:model="company">
                    <option value="">Todas as empreiteiras</option>
                    @foreach ($options['companies'] as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="ads-status">Situação</label>
                <select id="ads-status" class="form-select form-select-sm" wire:model="statusFilter">
                    <option value="all">Todas</option>
                    <option value="active">Em atividade</option>
                    <option value="delivered">Entregues</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="ads-status-exact">Status exato</label>
                <select id="ads-status-exact" class="form-select form-select-sm" wire:model="statusExact">
                    <option value="">Todos</option>
                    @foreach ($options['statuses'] as $status)
                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="ads-origin">Origem</label>
                <select id="ads-origin" class="form-select form-select-sm" wire:model="origin">
                    <option value="">Todas</option>
                    <option value="reused">Reaproveitada</option>
                    <option value="new">Nova solicitação</option>
                </select>
            </div>
            <div class="col-12 col-md-9 col-xl-3">
                <label class="form-label small mb-1" for="ads-search">Buscar</label>
                <input id="ads-search" type="text" class="form-control form-control-sm" wire:model.debounce.500ms="search" placeholder="ID da ADS, nota, empreiteira, status ou link">
            </div>
            <div class="col-12 col-md-3 col-xl-1 text-xl-end">
                <button type="button" class="btn btn-link btn-sm p-0" wire:click="clearFilters">limpar filtros</button>
            </div>
        </div>
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            Período exibido: <strong>{{ $period }}</strong>, pela data de {{ $dateBy === 'completed' ? 'conclusão' : 'solicitação' }}.
            Com 12 meses o gráfico agrega por mês; nos demais, por dia. O personalizado vai até {{ \App\Http\Livewire\Reports\AdsRequestedReport::MAX_DAYS }} dias.
        </div>
    </section>

    <div wire:loading.class="is-loading-soft" wire:target="setPreset,dt_in,dt_out,dateBy,company,origin,statusFilter,statusExact,search,perPage,clearFilters,applyChartFilter">

        {{-- Painel executivo --}}
        <h2 class="mp-section-title">Painel executivo</h2>

        @if ($summary)
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <x-home.hero-card tone="blue" icon="ri-inbox-archive-line" label="Abertas no período" :value="number_format($summary['opened_count'], 0, ',', '.')"
                        :note="'Média de ' . number_format($summary['opened_daily_avg'], 2, ',', '.') . ' por dia'"
                        :values="$flow['requested'] ?? []" :labels="$dates" />
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <x-home.hero-card tone="green" icon="ri-checkbox-circle-line" label="Entregas por dia" :value="number_format($summary['delivered_daily_avg'], 2, ',', '.')"
                        note="Média de ADS concluídas por dia no período"
                        :values="$flow['delivered'] ?? []" :labels="$dates" />
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <x-home.hero-card tone="amber" icon="ri-timer-line" label="Tempo médio de entrega" :value="$summary['delivered_avg_label']"
                        note="Da solicitação à entrega, nas concluídas do período"
                        :values="$flow['open_backlog'] ?? []" :labels="$dates" />
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <x-home.hero-card tone="red" icon="ri-loader-4-line" label="Em execução agora" :value="number_format($summary['in_progress_now_count'], 0, ',', '.')"
                        :note="$isOk('queue') ? number_format($queueSec['data']['total'], 0, ',', '.') . ' na fila em tempo real' : 'Fila em tempo real indisponível (sem conexão)'"
                        :values="$flow['overdue_backlog'] ?? []" :labels="$dates" />
                </div>
            </div>
        @else
            <section class="mp-panel mb-3">
                <x-closure.empty-state icon="ri-wifi-off-line" title="Resumo indisponível" :text="$s['summary']['reason']" />
            </section>
        @endif

        {{-- Fluxo --}}
        <h2 class="mp-section-title">Demanda x entrega</h2>

        {{-- Indicadores analíticos do período --}}
        <section class="mp-panel mb-3" aria-labelledby="ads-stats-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="ads-stats-title" class="mp-panel-title">Indicadores do período</h3>
                    <div class="mp-panel-sub">Calculados sobre a mesma série dos gráficos abaixo ({{ $period }})</div>
                </div>
            </div>
            @if ($flow)
                @php
                    $a   = $flow['analytics'];
                    $dec = fn ($v) => number_format((float) $v, 1, ',', '.');
                @endphp
                <div class="mp-stat-grid">
                    <div class="mp-stat"><span>Solicitadas</span><strong>{{ number_format($a['requested_total'], 0, ',', '.') }}</strong></div>
                    <div class="mp-stat"><span>Concluídas</span><strong>{{ number_format($a['delivered_total'], 0, ',', '.') }}</strong></div>
                    <div class="mp-stat {{ $a['completion_rate'] >= 90 ? 'is-good' : '' }}"><span>Taxa de conclusão</span><strong>{{ $dec($a['completion_rate']) }}%</strong><small>concluídas ÷ solicitadas</small></div>
                    <div class="mp-stat"><span>Abertas agora</span><strong>{{ number_format($a['current_open'], 0, ',', '.') }}</strong></div>
                    <div class="mp-stat {{ $a['current_overdue'] > 0 ? 'is-danger' : '' }}"><span>Atrasadas no período</span><strong>{{ number_format($a['current_overdue'], 0, ',', '.') }}</strong><small>abertas há mais de 24h</small></div>
                    <div class="mp-stat"><span>Média em aberto</span><strong>{{ $dec($a['backlog_avg']) }}</strong><small>por {{ ($flow['bucket_label'] ?? '') === 'mensal' ? 'mês' : 'dia' }}</small></div>
                    <div class="mp-stat"><span>Pico em aberto</span><strong>{{ number_format($a['backlog_peak'], 0, ',', '.') }}</strong></div>
                    <div class="mp-stat {{ $a['overdue_avg'] > 0 ? 'is-danger' : '' }}"><span>Média atrasadas</span><strong>{{ $dec($a['overdue_avg']) }}</strong><small>por {{ ($flow['bucket_label'] ?? '') === 'mensal' ? 'mês' : 'dia' }}</small></div>
                </div>
            @else
                <x-closure.empty-state icon="ri-wifi-off-line" title="Indicadores indisponíveis" :text="$s['flow']['reason']" />
            @endif
        </section>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Acumulado em aberto e atrasadas</h3>
                            <div class="mp-panel-sub">ADS pendentes ao fim de cada {{ $flow && ($flow['bucket_label'] ?? '') === 'mensal' ? 'mês' : 'dia' }}; atrasada passou de 24h</div>
                            <div class="mp-click-hint"><i class="ri-cursor-line"></i> Clique num ponto para filtrar as tabelas por aquele {{ $flow && ($flow['bucket_label'] ?? '') === 'mensal' ? 'mês' : 'dia' }}</div>
                        </div>
                        @if ($flow)
                            <span class="mp-pill">{{ $flow['analytics']['current_open'] }} em aberto · {{ $flow['analytics']['current_overdue'] }} atrasadas</span>
                        @endif
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="backlog" data-my-panel-click="applyChartFilter" data-config="{{ json_encode($charts['backlog']) }}" role="img" aria-label="Gráfico de linhas: ADS em aberto e atrasadas acumuladas por período"></canvas>
                        </div>
                        @if (!$flow)
                            <div class="mp-chart-empty is-offline"><i class="ri-wifi-off-line"></i><span>Sem conexão</span><small>{{ $s['flow']['reason'] }}</small></div>
                        @elseif (array_sum($flow['open_backlog']) === 0 && array_sum($flow['overdue_backlog']) === 0)
                            <div class="mp-chart-empty">Nenhuma ADS em aberto neste período</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Solicitadas x concluídas</h3>
                            <div class="mp-panel-sub">Entradas e saídas por {{ $flow && ($flow['bucket_label'] ?? '') === 'mensal' ? 'mês' : 'dia' }}, com a média do período</div>
                            <div class="mp-click-hint"><i class="ri-cursor-line"></i> Clique numa barra: “Solicitadas” filtra pela data do pedido, “Concluídas” pela da conclusão</div>
                        </div>
                        @if ($flow)
                            <span class="mp-pill">{{ $flow['analytics']['requested_total'] }} solicitadas · {{ $flow['analytics']['delivered_total'] }} concluídas</span>
                        @endif
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="flow" data-my-panel-click="applyChartFilter" data-config="{{ json_encode($charts['flow']) }}" role="img" aria-label="Gráfico de barras: ADS solicitadas e concluídas por período"></canvas>
                        </div>
                        @if (!$flow)
                            <div class="mp-chart-empty is-offline"><i class="ri-wifi-off-line"></i><span>Sem conexão</span><small>{{ $s['flow']['reason'] }}</small></div>
                        @elseif ($flow['analytics']['requested_total'] === 0 && $flow['analytics']['delivered_total'] === 0)
                            <div class="mp-chart-empty">Nenhuma ADS solicitada ou concluída neste período</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Fila e reaproveitamento</h2>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Fila atual por status</h3>
                            <div class="mp-panel-sub">Em tempo real, direto do SQL Server (pendentes: o que não está concluído, cancelado ou com falha)</div>
                            <div class="mp-click-hint"><i class="ri-cursor-line"></i> Clique numa fatia para filtrar as tabelas por aquele status</div>
                        </div>
                        @if ($isOk('queue'))
                            <span class="mp-pill">{{ $queueSec['data']['total'] }} na fila</span>
                        @endif
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="queue" data-my-panel-click="applyChartFilter" data-config="{{ json_encode($charts['queue']) }}" role="img" aria-label="Rosca: ADS na fila atual por status"></canvas>
                        </div>
                        @if (!$isOk('queue'))
                            <div class="mp-chart-empty is-offline"><i class="ri-wifi-off-line"></i><span>Sem conexão</span><small>{{ $queueSec['reason'] }}</small></div>
                        @elseif ($queueSec['data']['total'] === 0)
                            <div class="mp-chart-empty">Nenhuma ADS pendente na fila</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Economia por reaproveitamento de ADS</h3>
                            <div class="mp-panel-sub">Solicitações automáticas que reaproveitaram uma ADS já disponível x novas solicitações</div>
                            <div class="mp-click-hint"><i class="ri-cursor-line"></i> Clique numa fatia para filtrar o histórico por aquela origem</div>
                        </div>
                        @if ($isOk('reuse'))
                            <span class="mp-pill">Economia: {{ number_format($reuseSec['data']['reuse_rate'], 1, ',', '.') }}%</span>
                        @endif
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-donut" wire:ignore>
                            <canvas data-my-panel-chart="reuse" data-my-panel-click="applyChartFilter" data-config="{{ json_encode($charts['reuse']) }}" role="img" aria-label="Rosca: solicitações reaproveitadas e novas solicitações"></canvas>
                        </div>
                        @if (!$isOk('reuse'))
                            <div class="mp-chart-empty is-offline"><i class="ri-wifi-off-line"></i><span>Sem conexão</span><small>{{ $reuseSec['reason'] }}</small></div>
                        @elseif ($reuseSec['data']['total'] === 0)
                            <div class="mp-chart-empty">Nenhuma solicitação automática neste período</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        {{-- Fila atual --}}
        <h2 class="mp-section-title">Fila atual de ADS</h2>

        <section class="mp-panel mb-3" aria-labelledby="ads-queue-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="ads-queue-title" class="mp-panel-title">Requisições pendentes, da mais recente para a mais antiga</h3>
                    <div class="mp-panel-sub">Tudo que não está concluído, cancelado ou com falha. Prazo alvo: 24h depois da solicitação.</div>
                </div>
                @if ($queue['data'])
                    <span class="mp-pill">{{ number_format($queue['data']->total(), 0, ',', '.') }} {{ $queue['data']->total() === 1 ? 'pendente' : 'pendentes' }}</span>
                @endif
            </div>

            @if (!$queue['data'])
                <x-closure.empty-state icon="ri-wifi-off-line" title="Fila em tempo real indisponível" :text="$queue['reason']" />
            @elseif ($queue['data']->isEmpty())
                <x-closure.empty-state icon="ri-checkbox-circle-line" title="Nenhuma ADS pendente" text="Nenhuma requisição pendente para os filtros informados." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-7"><col class="col-pct-10"><col class="col-pct-20"><col class="col-pct-11">
                            <col class="col-pct-13"><col class="col-pct-13"><col class="col-pct-13"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col">Status</th>
                                <th scope="col">Solicitado em</th>
                                <th scope="col">Prazo alvo</th>
                                <th scope="col">Situação do prazo</th>
                                <th scope="col" class="text-end">ADS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($queue['data'] as $row)
                                <tr wire:key="ads-queue-{{ $row['id'] }}">
                                    <td class="fw-bold">#{{ $row['id'] }}</td>
                                    <td class="fw-bold">{{ $row['note_number'] }}</td>
                                    <td class="text-truncate" title="{{ $row['company_name'] }}">{{ $row['company_name'] }}</td>
                                    <td><span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                                    <td>{{ $row['requested_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $row['deadline_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        <span class="fw-semibold {{ $deadlineTone($row['deadline_label']) }}">{{ $row['deadline_label'] }}</span>
                                        <div class="small text-muted">decorrido {{ $row['elapsed_label'] }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if ($row['url'])
                                            <a href="{{ $row['url'] }}" class="row-action border-0 text-decoration-none"><i class="ri-external-link-line"></i> Abrir</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mp-panel-foot">{{ $queue['data']->onEachSide(1)->links() }}</div>
            @endif
        </section>

        {{-- Histórico --}}
        <h2 class="mp-section-title">Histórico de ADS</h2>

        <section class="mp-panel mb-3" aria-labelledby="ads-history-title">
            <div class="mp-panel-head">
                <div>
                    <h3 id="ads-history-title" class="mp-panel-title">Pesquisa por ADS e consulta de prazos e links</h3>
                    <div class="mp-panel-sub">Mais recentes primeiro, nos filtros atuais. O Excel traz o histórico completo.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if ($history['data'])
                        <span class="mp-pill">{{ number_format($history['data']->total(), 0, ',', '.') }} ADS</span>
                    @endif
                    <label class="small text-muted mb-0" for="ads-pp">Por página</label>
                    <select id="ads-pp" class="form-select form-select-sm w-auto" wire:model="perPage">
                        @foreach ([25, 50, 100] as $n)
                            <option value="{{ $n }}">{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (!$history['data'])
                <x-closure.empty-state icon="ri-wifi-off-line" title="Histórico indisponível" :text="$history['reason']" />
            @elseif ($history['data']->isEmpty())
                <x-closure.empty-state icon="ri-search-line" title="Nenhuma ADS nos filtros atuais" text="Amplie o período ou limpe os filtros para ver mais ADS." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 mp-table">
                        <colgroup>
                            <col class="col-pct-6"><col class="col-pct-9"><col class="col-pct-14"><col class="col-pct-11"><col class="col-pct-7">
                            <col class="col-pct-9"><col class="col-pct-14"><col class="col-pct-15"><col class="col-pct-10"><col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col">Destinatário</th>
                                <th scope="col">Tácita?</th>
                                <th scope="col">Status</th>
                                <th scope="col">Pediu / entregou</th>
                                <th scope="col">Prazo</th>
                                <th scope="col">Observação</th>
                                <th scope="col" class="text-end">ADS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history['data'] as $row)
                                <tr wire:key="ads-history-{{ $row['id'] }}">
                                    <td class="fw-bold">#{{ $row['id'] }}</td>
                                    <td class="fw-bold">{{ $row['note_number'] }}</td>
                                    <td class="text-truncate" title="{{ $row['company_name'] }}">{{ $row['company_name'] }}</td>
                                    <td class="text-truncate" title="{{ $row['recipient_name'] }}">{{ $row['recipient_name'] }}</td>
                                    <td><span class="badge {{ $row['is_tacit'] ? 'text-bg-warning' : 'text-bg-light border' }}">{{ $row['is_tacit'] ? 'Sim' : 'Não' }}</span></td>
                                    <td><span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                                    <td>
                                        {{ $row['requested_at']?->format('d/m/Y H:i') ?? '—' }}
                                        <div class="small text-muted">{{ $row['delivered_at']?->format('d/m/Y H:i') ?? 'ainda não entregue' }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold {{ $deadlineTone($row['deadline_label']) }}">{{ $row['deadline_label'] }}</span>
                                        <div class="small text-muted">alvo {{ $row['deadline_at']?->format('d/m/Y H:i') ?? '—' }} · tempo {{ $row['elapsed_label'] }}</div>
                                    </td>
                                    <td class="text-truncate" title="{{ $row['description'] }}">{{ $row['description'] !== '' ? $row['description'] : '—' }}</td>
                                    <td class="text-end">
                                        @if ($row['url'])
                                            <a href="{{ $row['url'] }}" class="row-action border-0 text-decoration-none" target="_blank" rel="noopener"><i class="ri-download-2-line"></i> Baixar</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mp-panel-foot">{{ $history['data']->onEachSide(1)->links() }}</div>
            @endif
        </section>
    </div>
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
