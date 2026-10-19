<div class="closure-dash">
    @include('livewire.closure._styles')

    @php
        use App\Enum\ClosureOperationalStatus as OpStatus;
        use App\Services\Closure\ClosureMetaClassifier as Meta;

        $duration = function (?int $seconds): string {
            if ($seconds === null) {
                return '—';
            }

            return $seconds < 60
                ? '< 1 min'
                : \Carbon\CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 2, 'short' => true, 'join' => ' ']);
        };
        $typeColors = ['P' => 'danger', 'M' => 'success', 'F' => 'primary', 'N' => 'secondary'];
        $statusCount = count($statuses);
        $hasData = $matrix['totals']['total'] > 0;
        $cellActive = fn ($user, $type, $status) => $cell['user'] === $user && $cell['type'] === $type && $cell['status'] === $status;
    @endphp

    <style>
        .closure-dash .matrix-cell { background: transparent; border: 0; border-radius: 6px; font-weight: 700; min-width: 34px; padding: 3px 6px; }
        .closure-dash .matrix-cell:hover:not(:disabled) { background: rgba(21, 95, 103, .12); }
        .closure-dash .matrix-cell.active { background: var(--dash-teal); color: #fff; }
        .closure-dash .matrix-cell:disabled { color: #b8c8d0; cursor: default; }
        .closure-dash .type-chip { border: 0; border-radius: 4px; color: #fff; font-size: .72rem; font-weight: 800; min-width: 30px; padding: 2px 6px; }
        .closure-dash .type-chip:disabled { opacity: .3; cursor: default; }
        .closure-dash .type-chip.active { box-shadow: 0 0 0 2px #102033; }
        .closure-dash .chart-body-lg { height: 340px; position: relative; padding: 12px 16px; }
        .closure-dash .chart-empty { align-items: center; background: rgba(255, 255, 255, .92); color: var(--dash-muted); display: flex; font-weight: 700; inset: 0; justify-content: center; position: absolute; z-index: 2; }
        .closure-dash .totals-row td { background: var(--dash-soft, #f4f7f9); border-top: 2px solid var(--dash-line); font-weight: 800; }
    </style>

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Painel analítico do Encerramento</h2>
                <div class="dash-subtitle">Visão do todo: volumetria por usuário, interação com a parceira, prioridades e obras críticas.</div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small opacity-75">Atualizado em {{ now()->format('d/m/Y H:i') }}</span>
                <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.stack') }}" class="btn btn-light btn-sm btn-dash">Lista de controle</a>
            </div>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert" id="closure-dash-feedback">{{ $feedbackMessage }}</div>
    @endif

    @include('livewire.closure.dispatch._transfer-receipt')

    {{-- Filtros globais --}}
    <div class="table-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Tipo</label>
                <select class="form-select form-select-sm" wire:model="filterType">
                    <option value="">Todos</option>
                    <option value="P">P - Passivo</option>
                    <option value="M">M - Meta atual</option>
                    <option value="F">F - Meta futura</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1">Empresa (posse atual)</label>
                <select class="form-select form-select-sm" wire:model="filterCompany">
                    <option value="">Todas</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">No status desde</label>
                <input type="date" class="form-control form-control-sm" wire:model="dateFrom">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Até</label>
                <input type="date" class="form-control form-control-sm" wire:model="dateTo">
            </div>
            <div class="col-6 col-md-2">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash w-100" wire:click="clearFilters">Limpar filtros</button>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Total ativo</div>
                <div class="metric-value" id="dash-total">{{ number_format($summary['total'], 0, ',', '.') }}</div>
                <div class="metric-note">
                    <span class="badge bg-danger">P {{ $summary['types']['P'] }}</span>
                    <span class="badge bg-success">M {{ $summary['types']['M'] }}</span>
                    <span class="badge bg-primary">F {{ $summary['types']['F'] }}</span>
                    @if ($summary['types']['N'])
                        <span class="badge bg-secondary">sem tipo {{ $summary['types']['N'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Meta concluída</div>
                <div class="metric-value" id="dash-meta-percent">{{ number_format($summary['meta_percent'], 1, ',', '.') }}%</div>
                <div class="metric-note">{{ $summary['meta_closed'] }} de {{ $summary['meta_total'] }} Ordens M do ciclo encerradas</div>
                <div class="progress mt-2"><div class="progress-bar bg-success" style="width: {{ min(100, $summary['meta_percent']) }}%"></div></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="metric-card h-100">
                <div class="metric-label">Aguardando parceira</div>
                <div class="metric-value" id="dash-waiting-partner">{{ $summary['waiting_partner'] }}</div>
                <div class="metric-note">Com pendência na parceira</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="metric-card h-100">
                <div class="metric-label">Pausadas</div>
                <div class="metric-value" id="dash-paused">{{ $summary['paused'] }}</div>
                <div class="metric-note">Com motivo registrado</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="metric-card h-100">
                <div class="metric-label">Finalizadas / Aguardando</div>
                <div class="metric-value"><span id="dash-finished">{{ $summary['finished'] }}</span> <span class="text-muted fs-5">/</span> <span id="dash-waiting">{{ $summary['waiting'] }}</span></div>
                <div class="metric-note">Aguardam o SAP / a meta chegar</div>
            </div>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="chart-card h-100">
                <div class="chart-head">
                    <div><h3 class="chart-title">Volumetria por usuário</h3><div class="chart-subtitle">Barras empilhadas por tipo (P/M/F)</div></div>
                </div>
                <div class="chart-body-lg">
                    @unless ($hasData)
                        <div class="chart-empty">Sem Ordens ativas para os filtros</div>
                    @endunless
                    <div wire:ignore class="h-100">
                        <x-grafico.apex :chart="$charts['users']" :chartId="$userChartId" :showDataLabels="true" class="w-100 h-100" />
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="chart-card h-100">
                <div class="chart-head">
                    <div><h3 class="chart-title">Ordens por status</h3><div class="chart-subtitle">Distribuição do estoque ativo</div></div>
                </div>
                <div class="chart-body-lg">
                    @unless ($hasData)
                        <div class="chart-empty">Sem Ordens ativas para os filtros</div>
                    @endunless
                    <div wire:ignore class="h-100">
                        <x-grafico.apex :chart="$charts['status']" :chartId="$statusChartId" :showDataLabels="true" class="w-100 h-100" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Matriz usuário x tipo x status --}}
    <div class="table-card mb-3" id="closure-matrix">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Matriz de volumetria</h3>
                <div class="chart-subtitle">Usuário × tipo × status. Clique em um número para filtrar a lista abaixo.</div>
            </div>
            @if (array_filter($cell))
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearCell">Limpar seleção</button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 text-center">
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
                        <th class="text-start ps-3">Usuário</th>
                        <th>Tipo</th>
                        @foreach ($statuses as $status)
                            <th title="{{ OpStatus::labelFor($status) }}">{{ match ($status) {
                                'WAITING_PARTNER' => 'Aguard. Parceira',
                                'DEPOSIT_BLOCKED' => 'Dep. Bloq.',
                                'DEPOSIT_RELEASED' => 'Dep. Lib.',
                                default => OpStatus::labelFor($status),
                            } }}</th>
                        @endforeach
                        <th class="pe-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matrix['rows'] as $row)
                        @php $uid = $row['user_id'] ?? \App\Services\Closure\Analytics\ClosureDispatcherAnalytics::NO_USER; @endphp
                        <tr wire:key="matrix-{{ $uid }}">
                            <td class="text-start ps-3 fw-semibold" title="{{ $row['name'] }}">{{ $row['name'] }}</td>
                            <td>
                                @foreach (['P', 'M', 'F'] as $type)
                                    <button type="button" class="type-chip bg-{{ $typeColors[$type] }} {{ $cellActive($uid, $type, null) ? 'active' : '' }}"
                                        wire:click="selectCell('{{ $uid }}', '{{ $type }}', '')" @disabled($row['types'][$type] === 0)>{{ $type }} {{ $row['types'][$type] }}</button>
                                @endforeach
                            </td>
                            @foreach ($statuses as $status)
                                @php $n = $row['status'][$status]; @endphp
                                <td>
                                    <button type="button" class="matrix-cell {{ $cellActive($uid, null, $status) ? 'active' : '' }}"
                                        wire:click="selectCell('{{ $uid }}', '', '{{ $status }}')" @disabled($n === 0)
                                        title="P {{ $row['cells'][$status]['P'] }} · M {{ $row['cells'][$status]['M'] }} · F {{ $row['cells'][$status]['F'] }}">{{ $n }}</button>
                                </td>
                            @endforeach
                            <td class="pe-3 fw-bold">
                                <button type="button" class="matrix-cell {{ $cellActive($uid, null, null) ? 'active' : '' }}" wire:click="selectCell('{{ $uid }}', '', '')">{{ $row['total'] }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $statusCount + 3 }}" class="text-muted py-4">Nenhuma Ordem ativa para os filtros.</td></tr>
                    @endforelse
                </tbody>
                @if ($hasData)
                    <tfoot>
                        <tr class="totals-row">
                            <td class="text-start ps-3">Total geral</td>
                            <td>
                                @foreach (['P', 'M', 'F'] as $type)
                                    <span class="badge bg-{{ $typeColors[$type] }}">{{ $type }} {{ $matrix['totals']['types'][$type] }}</span>
                                @endforeach
                            </td>
                            @foreach ($statuses as $status)
                                <td>{{ $matrix['totals']['status'][$status] }}</td>
                            @endforeach
                            <td class="pe-3">{{ $matrix['totals']['total'] }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Lista filtrada pela célula --}}
    <div class="table-card mb-3" id="closure-cell-orders">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens {{ array_filter($cell) ? 'da seleção' : 'ativas' }}</h3>
                <div class="chart-subtitle">
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
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <colgroup>
                    <col style="width: 60px"><col style="width: 120px"><col style="width: 140px"><col style="width: 170px">
                    <col style="width: 190px"><col style="width: 190px"><col style="width: 140px"><col style="width: 170px">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3">Tipo</th><th>Nota</th><th>Ordem</th><th>Status</th><th>Responsável</th><th>Empresa</th><th>No status desde</th><th class="pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="cell-order-{{ $order->state_id }}">
                            <td class="ps-3">{!! Meta::badge($order->meta_type) !!}</td>
                            <td>{{ $order->note_number }}</td>
                            <td class="fw-semibold">{{ $order->ordem }} @if ($order->priority)<span class="badge bg-warning text-dark" title="Prioridade">!</span>@endif</td>
                            <td><span class="badge {{ OpStatus::badgeClassFor($order->status) }}">{{ OpStatus::labelFor($order->status) }}</span></td>
                            <td title="{{ $order->responsible_name }}">{{ $order->responsible_name ?? '—' }}</td>
                            <td title="{{ $order->company_name }}">{{ $order->company_name ?? '—' }}</td>
                            <td>{{ $order->entered_at ? \Carbon\Carbon::parse($order->entered_at)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="pe-3"><a class="row-action" href="{{ route('closure.work.show', $order->note_id) }}">Abrir obra</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted py-4 text-center">Nenhuma Ordem na seleção.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('livewire.closure.dispatch._priority-requests')

    {{-- Interação usuário x parceira --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="table-card h-100" id="closure-partner-interaction">
                <div class="chart-head">
                    <div><h3 class="chart-title">Por parceira</h3><div class="chart-subtitle">Pendências enviadas e tempo de espera por resposta</div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <colgroup><col style="width: 190px"><col style="width: 70px"><col style="width: 90px"><col style="width: 110px"><col style="width: 110px"><col style="width: 90px"><col style="width: 100px"></colgroup>
                        <thead><tr><th class="ps-3">Parceira</th><th>Abertas</th><th>Aguard.</th><th>Espera média</th><th>Maior espera</th><th>Devolv.</th><th class="pe-3">Respond.</th></tr></thead>
                        <tbody>
                            @forelse ($interaction['partners'] as $partner)
                                <tr wire:key="partner-{{ $partner['company_id'] }}">
                                    <td class="ps-3 fw-semibold" title="{{ $partner['name'] }}">{{ $partner['name'] }}</td>
                                    <td>{{ $partner['open'] }}</td>
                                    <td>{{ $partner['awaiting'] }}</td>
                                    <td>{{ $duration($partner['avg_wait']) }}</td>
                                    <td>{{ $duration($partner['max_wait']) }}</td>
                                    <td>{{ $partner['returns'] }}</td>
                                    <td class="pe-3">{{ $partner['responded'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted py-4 text-center">Sem pendências com parceiras.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="table-card h-100" id="closure-user-interaction">
                <div class="chart-head">
                    <div><h3 class="chart-title">Por usuário</h3><div class="chart-subtitle">Pendências enviadas e resposta média da parceira</div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <colgroup><col style="width: 190px"><col style="width: 80px"><col style="width: 90px"><col style="width: 100px"><col style="width: 130px"></colgroup>
                        <thead><tr><th class="ps-3">Usuário</th><th>Enviadas</th><th>Aguard.</th><th>Respond.</th><th class="pe-3">Resposta média</th></tr></thead>
                        <tbody>
                            @forelse ($interaction['users'] as $user)
                                <tr wire:key="iuser-{{ $user['user_id'] }}">
                                    <td class="ps-3 fw-semibold" title="{{ $user['name'] }}">{{ $user['name'] }}</td>
                                    <td>{{ $user['sent'] }}</td>
                                    <td>{{ $user['awaiting'] }}</td>
                                    <td>{{ $user['responded'] }}</td>
                                    <td class="pe-3">{{ $duration($user['avg_response']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted py-4 text-center">Sem pendências enviadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Obras críticas --}}
    <div class="table-card mb-3" id="closure-critical-works">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Obras críticas</h3>
                <div class="chart-subtitle">Ordens P paradas ou pendência sem resposta há mais de {{ $staleDays }} dias</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small mb-0" for="staleDays">Dias</label>
                <input id="staleDays" type="number" min="1" max="365" class="form-control form-control-sm" style="width: 80px" wire:model.lazy="staleDays">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <colgroup><col style="width: 130px"><col style="width: 110px"><col style="width: 110px"><col style="width: 140px"><col style="width: 130px"><col style="width: 140px"><col style="width: 330px"></colgroup>
                <thead><tr><th class="ps-3">Obra</th><th>Ordens abertas</th><th>P</th><th>P paradas</th><th>Mais antiga</th><th>Pendência parada</th><th class="pe-3">Ações</th></tr></thead>
                <tbody>
                    @forelse ($critical as $work)
                        <tr wire:key="critical-{{ $work['note_id'] }}">
                            <td class="ps-3 fw-semibold">{{ $work['note'] }}</td>
                            <td>{{ $work['open_orders'] }}</td>
                            <td>{{ $work['passive_orders'] }}</td>
                            <td>{{ $work['stale_passive'] }}</td>
                            <td>{{ $work['oldest_days'] !== null ? $work['oldest_days'] . ' d' : '—' }}</td>
                            <td>{{ $work['stuck_issues'] ? $work['stuck_issues'] . ' (' . $work['stuck_days'] . ' d)' : '—' }}</td>
                            <td class="pe-3">
                                <a class="row-action" href="{{ route('closure.work.show', $work['note_id']) }}">Abrir obra</a>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="prioritizeNote({{ $work['note_id'] }})">Priorizar obra</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="prioritizeNote({{ $work['note_id'] }}, false)">Tirar prioridade</button>
                                <button type="button" class="btn btn-outline-dark btn-sm btn-dash" wire:click="openTransfer({{ $work['note_id'] }})">Transferir</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted py-4 text-center">Nenhuma obra crítica.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('livewire.closure.dispatch._transfer-form')

    @include('livewire.closure.dispatch._decide-script')
</div>
