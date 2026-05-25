<div class="oexterno-page">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
            padding: 1.5rem 0;
        }
        .oexterno-header { background: linear-gradient(120deg, #0f172a, #0f766e 70%); color:#f8fafc; border-radius:1rem; padding:1.5rem 2rem; margin-bottom:1.5rem; }
        .card-soft { background: var(--oe-surface); border:1px solid var(--oe-border); border-radius: .8rem; box-shadow:0 12px 24px rgba(15,23,42,.06); }
        .summary-bar {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            margin-bottom: 1rem;
        }
        .summary-item {
            color: var(--oe-muted);
            font-size: 0.92rem;
        }
        .summary-item strong {
            color: var(--oe-ink);
        }
        .cost-chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border: 1px solid #e2e8f0;
            border-radius: .35rem;
            padding: .15rem .4rem;
            background: #fff;
        }
        .cost-trend-up {
            color: #b91c1c;
        }
        .cost-trend-down {
            color: #047857;
        }
        .cost-trend-neutral {
            color: #64748b;
        }
        .history-table th,
        .history-table td {
            vertical-align: middle;
        }
        .history-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .history-table thead th {
            background: linear-gradient(180deg, #0f172a, #1e293b);
            color: #f8fafc;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            border: 0;
            white-space: nowrap;
            padding-top: .6rem;
            padding-bottom: .6rem;
        }
        .history-table thead th:first-child {
            border-top-left-radius: .6rem;
        }
        .history-table thead th:last-child {
            border-top-right-radius: .6rem;
        }
        .history-table tbody td {
            border-color: #e2e8f0;
            background: #ffffff;
        }
        .history-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .history-table tbody tr:hover td {
            background: #eef6ff;
        }
        .history-table .small.border {
            border-color: #cbd5e1 !important;
            border-radius: .35rem;
            background: #fff;
        }
        .history-table tbody tr:nth-child(even) .small.border {
            background: #f8fafc;
        }

        .back-to-top {
            display: none !important;
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header">
            <h2 class="mb-0">ANÁLISE PROJETO</h2>
            <div>Histórico das análises</div>
        </div>

        <div class="card-soft p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-4"><input type="text" class="form-control" wire:model.debounce.500ms="search" placeholder="Buscar por nota/pedido/descrição"></div>
                <div class="col-md-3">
                    <select class="form-select" wire:model="company_id">
                        <option value="">Todas as empresas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" class="form-control" wire:model="from"></div>
                <div class="col-md-2"><input type="date" class="form-control" wire:model="to"></div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="exportList">
                        Exportar
                    </button>
                </div>
            </div>
        </div>

        @php
            $currentPageCount = method_exists($rows, 'count') ? $rows->count() : count($rows);
            $totalCount = method_exists($rows, 'total') ? (int) $rows->total() : $currentPageCount;
        @endphp

        <div class="summary-bar d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="summary-item">
                Exibindo <strong>{{ $currentPageCount }}</strong> registro(s) nesta página.
            </div>
            <div class="summary-item">
                Total do histórico: <strong>{{ $totalCount }}</strong>.
            </div>
        </div>

        <div class="card-soft overflow-hidden">
            <div class="table-responsive w-100">
                <table class="table table-sm table-hover mb-0 history-table w-100">
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Desenhista</th>
                            <th>Empresa</th>
                            <th>Ordens</th>
                            <th>Custo total</th>
                            <th>Custo empresa</th>
                            <th>Custo cliente</th>
                            <th>Variação (%)</th>
                            <th>Status</th>
                            <th>Analista</th>
                            <th>Quando foi enviado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $cycles = collect($row->ProjectReviewCycles)->sortByDesc('round_number')->values();
                                $cycle = $cycles->first();
                                $previousCycle = $cycles->skip(1)->first();
                                $cyclesAsc = collect($row->ProjectReviewCycles)->sortBy('round_number')->values();
                                $firstCycle = $cyclesAsc->first();
                                $lastCycle = $cyclesAsc->last();
                                $orders = $cycle?->Orders ?? collect();
                                if ($orders->isEmpty()) {
                                    $orders = $row->ProjectReviewCycles->first(function ($c) {
                                        return $c->Orders->count() > 0;
                                    })?->Orders ?? collect();
                                }
                                $previousOrdersByNumber = collect($previousCycle?->Orders ?? collect())
                                    ->keyBy(fn($ord) => (string) ($ord->order_number ?? ''));
                                $sumCycleByPrefixRule = function ($orders) {
                                    $allowedPrefixes = ['170', '190', '150', '200'];
                                    $latestByPrefix = [];
                                    $fallbackTotal = 0.0;

                                    foreach (collect($orders ?? collect()) as $ord) {
                                        $value = (float) ($ord->total_cost ?? 0);
                                        $fallbackTotal += $value;
                                        $digits = preg_replace('/\D+/', '', (string) ($ord->order_number ?? ''));
                                        $prefix = strlen($digits) >= 3 ? substr($digits, 0, 3) : '';
                                        if (in_array($prefix, $allowedPrefixes, true)) {
                                            $latestByPrefix[$prefix] = $value;
                                        }
                                    }

                                    if (count($latestByPrefix)) {
                                        return (float) array_sum($latestByPrefix);
                                    }

                                    return $fallbackTotal;
                                };
                                $prefixSeries = collect(['170', '190', '150', '200'])->mapWithKeys(fn($p) => [$p => []])->all();
                                foreach ($cyclesAsc as $cycleRow) {
                                    $roundPrefixTotals = [];
                                    foreach (collect($cycleRow->Orders ?? collect()) as $ordRow) {
                                        $digits = preg_replace('/\D+/', '', (string) ($ordRow->order_number ?? ''));
                                        $prefix = strlen($digits) >= 3 ? substr($digits, 0, 3) : '';
                                        if (in_array($prefix, ['170', '190', '150', '200'], true)) {
                                            $roundPrefixTotals[$prefix] = (float) ($ordRow->total_cost ?? 0);
                                        }
                                    }
                                    foreach (['170', '190', '150', '200'] as $prefix) {
                                        if (array_key_exists($prefix, $roundPrefixTotals)) {
                                            $prefixSeries[$prefix][] = (float) $roundPrefixTotals[$prefix];
                                        }
                                    }
                                }

                                $totalPrevious = 0.0;
                                $totalCurrent = 0.0;
                                $hasPrefixData = false;
                                foreach (['170', '190', '150', '200'] as $prefix) {
                                    $values = collect($prefixSeries[$prefix] ?? [])->values();
                                    if ($values->isEmpty()) {
                                        continue;
                                    }
                                    $hasPrefixData = true;
                                    $totalPrevious += (float) $values->first();
                                    $totalCurrent += (float) $values->last();
                                }

                                if (!$hasPrefixData) {
                                    $totalCurrent = $sumCycleByPrefixRule($lastCycle?->Orders ?? collect());
                                    $totalPrevious = $sumCycleByPrefixRule($firstCycle?->Orders ?? collect());
                                }
                                $variationPercent = null;
                                if ($totalPrevious > 0) {
                                    $variationPercent = (($totalCurrent - $totalPrevious) / $totalPrevious) * 100;
                                }
                                $variationDirection = 'neutral';
                                if (!is_null($variationPercent)) {
                                    if ($variationPercent > 0.00001) {
                                        $variationDirection = 'up';
                                    } elseif ($variationPercent < -0.00001) {
                                        $variationDirection = 'down';
                                    }
                                }
                                $status = \App\Custom\Notestatus::status((int) $row->status);
                            @endphp
                            <tr>
                                <td>{{ $row->Note?->note ?? '---' }}</td>
                                <td>{{ $row->User?->name ?? '---' }}</td>
                                <td>{{ $row->Company?->name ?? '---' }}</td>
                                <td>
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1"><strong>{{ $ord->order_number }}</strong></div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                @php
                                                    $prevOrd = $previousOrdersByNumber->get((string) ($ord->order_number ?? ''));
                                                    $currVal = (float) ($ord->total_cost ?? 0);
                                                    $prevVal = is_null($prevOrd) ? null : (float) ($prevOrd->total_cost ?? 0);
                                                    $trend = 'neutral';
                                                    if (!is_null($prevVal)) {
                                                        if ($currVal > $prevVal) {
                                                            $trend = 'up';
                                                        } elseif ($currVal < $prevVal) {
                                                            $trend = 'down';
                                                        }
                                                    }
                                                @endphp
                                                <div class="small border px-2 py-1 d-flex align-items-center justify-content-between gap-2">
                                                    <span>{{ number_format($currVal, 2, ',', '.') }}</span>
                                                    <span class="cost-chip cost-trend-{{ $trend }}" title="{{ $trend === 'up' ? 'Aumento' : ($trend === 'down' ? 'Diminuição' : 'Sem mudança') }}">
                                                        <i class="{{ $trend === 'up' ? 'ri-arrow-up-line' : ($trend === 'down' ? 'ri-arrow-down-line' : 'ri-subtract-line') }}"></i>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                @php
                                                    $prevOrd = $previousOrdersByNumber->get((string) ($ord->order_number ?? ''));
                                                    $currVal = (float) ($ord->company_cost ?? 0);
                                                    $prevVal = is_null($prevOrd) ? null : (float) ($prevOrd->company_cost ?? 0);
                                                    $trend = 'neutral';
                                                    if (!is_null($prevVal)) {
                                                        if ($currVal > $prevVal) {
                                                            $trend = 'up';
                                                        } elseif ($currVal < $prevVal) {
                                                            $trend = 'down';
                                                        }
                                                    }
                                                @endphp
                                                <div class="small border px-2 py-1 d-flex align-items-center justify-content-between gap-2">
                                                    <span>{{ number_format($currVal, 2, ',', '.') }}</span>
                                                    <span class="cost-chip cost-trend-{{ $trend }}" title="{{ $trend === 'up' ? 'Aumento' : ($trend === 'down' ? 'Diminuição' : 'Sem mudança') }}">
                                                        <i class="{{ $trend === 'up' ? 'ri-arrow-up-line' : ($trend === 'down' ? 'ri-arrow-down-line' : 'ri-subtract-line') }}"></i>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                @php
                                                    $prevOrd = $previousOrdersByNumber->get((string) ($ord->order_number ?? ''));
                                                    $currVal = (float) ($ord->client_cost ?? 0);
                                                    $prevVal = is_null($prevOrd) ? null : (float) ($prevOrd->client_cost ?? 0);
                                                    $trend = 'neutral';
                                                    if (!is_null($prevVal)) {
                                                        if ($currVal > $prevVal) {
                                                            $trend = 'up';
                                                        } elseif ($currVal < $prevVal) {
                                                            $trend = 'down';
                                                        }
                                                    }
                                                @endphp
                                                <div class="small border px-2 py-1 d-flex align-items-center justify-content-between gap-2">
                                                    <span>{{ number_format($currVal, 2, ',', '.') }}</span>
                                                    <span class="cost-chip cost-trend-{{ $trend }}" title="{{ $trend === 'up' ? 'Aumento' : ($trend === 'down' ? 'Diminuição' : 'Sem mudança') }}">
                                                        <i class="{{ $trend === 'up' ? 'ri-arrow-up-line' : ($trend === 'down' ? 'ri-arrow-down-line' : 'ri-subtract-line') }}"></i>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $variationIcon = $variationDirection === 'up'
                                            ? 'ri-arrow-up-line'
                                            : ($variationDirection === 'down' ? 'ri-arrow-down-line' : 'ri-subtract-line');
                                        $variationValue = is_null($variationPercent) ? 0.0 : abs((float) $variationPercent);
                                    @endphp
                                    <span class="badge {{ $variationDirection === 'up' ? 'text-bg-danger' : ($variationDirection === 'down' ? 'text-bg-success' : 'text-bg-secondary') }} d-inline-flex align-items-center gap-1">
                                        <i class="{{ $variationIcon }}"></i>
                                        <span>{{ number_format($variationValue, 2, ',', '.') }}%</span>
                                    </span>
                                </td>
                                <td><span class="badge {{ $status->colorbg }}">{{ $status->status }}</span></td>
                                <td>{{ $cycle?->DecidedBy?->name ?? '---' }}</td>
                                <td>{{ $cycle?->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" wire:click="openProduction({{ $row->id }})">Abrir</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted">Sem registros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator || $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                <div class="p-3 border-top">
                    <div class="small text-muted mb-2">
                        Mostrando {{ $rows->firstItem() ?? 0 }} até {{ $rows->lastItem() ?? 0 }} de {{ $rows->total() }} registros.
                    </div>
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="historyProjectReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title">Histórico da Análise - {{ $selectedProduction?->Note?->note }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body oexterno-page">
                    @if ($selectedCycle)
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="card card-soft mb-3">
                                    <div class="card-header">Informações</div>
                                    <div class="card-body small">
                                        <div><strong>Nota:</strong> {{ $selectedProduction?->Note?->note ?? '---' }}</div>
                                        <div><strong>Desenhista:</strong> {{ $selectedProduction?->User?->name ?? '---' }}</div>
                                        <div><strong>Empresa:</strong> {{ $selectedProduction?->Company?->name ?? '---' }}</div>
                                        <div><strong>Serviço:</strong> {{ $selectedProduction?->Service?->service ?? '---' }}</div>
                                        <div><strong>Rodada:</strong> {{ $selectedCycle->round_number }}</div>
                                        <div><strong>Enviado:</strong> {{ $selectedCycle->submitted_at ? date('d/m/Y H:i', strtotime($selectedCycle->submitted_at)) : '---' }}</div>
                                        <div>
                                            <strong>Decisão:</strong>
                                            @switch($selectedCycle->decision)
                                                @case('APPROVED')
                                                    Aprovado
                                                    @break
                                                @case('APPROVED_WITH_REMARKS')
                                                    Aprovado com ressalvas
                                                    @break
                                                @case('REJECTED')
                                                    Reprovado
                                                    @break
                                                @default
                                                    {{ $selectedCycle->decision ?? '---' }}
                                            @endswitch
                                        </div>
                                        <div><strong>Decidido:</strong> {{ $selectedCycle->decided_at ? date('d/m/Y H:i', strtotime($selectedCycle->decided_at)) : '---' }}</div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Laudos Técnicos (Histórico)</div>
                                    <div class="card-body small">
                                        @php
                                            $laudos = collect($selectedProduction?->ProjectReviewCycles ?? [])
                                                ->sortByDesc('round_number');
                                        @endphp
                                        @forelse($laudos as $laudoCycle)
                                            <div class="border rounded p-2 mb-2">
                                                <div class="d-flex justify-content-between flex-wrap gap-2 mb-1">
                                                    <div>
                                                        <strong>Rodada {{ $laudoCycle->round_number }}</strong>
                                                        -
                                                        @switch($laudoCycle->decision)
                                                            @case('PENDING')
                                                                Em análise
                                                                @break
                                                            @case('APPROVED')
                                                                Aprovado
                                                                @break
                                                            @case('APPROVED_WITH_REMARKS')
                                                                Aprovado com ressalvas
                                                                @break
                                                            @case('REJECTED')
                                                                Reprovado
                                                                @break
                                                            @default
                                                                {{ $laudoCycle->decision ?? '---' }}
                                                        @endswitch
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="text-muted">{{ $laudoCycle->decided_at ? date('d/m/Y H:i', strtotime($laudoCycle->decided_at)) : '---' }}</div>
                                                        <button type="button"
                                                            class="btn btn-sm {{ (int) $selectedCycle->id === (int) $laudoCycle->id ? 'btn-primary' : 'btn-outline-primary' }}"
                                                            wire:click="selectCycle({{ $laudoCycle->id }})">
                                                            {{ (int) $selectedCycle->id === (int) $laudoCycle->id ? 'Selecionada' : 'Abrir' }}
                                                        </button>
                                                    </div>
                                                </div>
                                                <div><strong>Analista:</strong> {{ $laudoCycle->DecidedBy?->name ?? '---' }}</div>
                                                <div class="mt-1"><strong>Laudo:</strong> {{ $laudoCycle->analyst_note ?: '---' }}</div>
                                            </div>
                                        @empty
                                            <div class="text-muted">Sem laudo técnico registrado.</div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Ordens e valores da rodada</div>
                                    <div class="card-body small">
                                        @php
                                            $cyclesAsc = collect($selectedProduction?->ProjectReviewCycles ?? [])
                                                ->sortBy('round_number')
                                                ->values();

                                            $historyByOrder = [];
                                            foreach ($cyclesAsc as $cy) {
                                                foreach ($cy->Orders as $ord) {
                                                    $orderNumber = trim((string) $ord->order_number);
                                                    if ($orderNumber === '') {
                                                        continue;
                                                    }

                                                    if (!isset($historyByOrder[$orderNumber])) {
                                                        $historyByOrder[$orderNumber] = [];
                                                    }

                                                    $previousEntry = collect($historyByOrder[$orderNumber])->last();
                                                    $previousTotal = $previousEntry['total_cost'] ?? null;
                                                    $currentTotal = (float) $ord->total_cost;

                                                    $historyByOrder[$orderNumber][] = [
                                                        'round' => (int) $cy->round_number,
                                                        'submitted_at' => $cy->submitted_at,
                                                        'total_cost' => $currentTotal,
                                                        'company_cost' => (float) $ord->company_cost,
                                                        'client_cost' => (float) $ord->client_cost,
                                                        'delta' => is_null($previousTotal) ? null : ($currentTotal - (float) $previousTotal),
                                                    ];
                                                }
                                            }

                                            ksort($historyByOrder);

                                            // Totalizador por rodada para evitar distorção em troca de número de ordem.
                                            $sumEconomy = 0.0;
                                            $sumIncrease = 0.0;
                                            $hasPrefixDataForTotals = false;
                                            foreach (['170', '190', '150', '200'] as $prefix) {
                                                $values = collect($prefixSeries[$prefix] ?? [])->values();
                                                if ($values->count() < 2) {
                                                    continue;
                                                }
                                                $hasPrefixDataForTotals = true;
                                                $deltaPrefix = round(((float) $values->last()) - ((float) $values->first()), 2);
                                                if ($deltaPrefix < 0) {
                                                    $sumEconomy += abs($deltaPrefix);
                                                } elseif ($deltaPrefix > 0) {
                                                    $sumIncrease += $deltaPrefix;
                                                }
                                            }

                                            if (!$hasPrefixDataForTotals) {
                                                $roundTotals = $cyclesAsc
                                                    ->map(fn($cy) => (float) collect($cy->Orders ?? collect())->sum('total_cost'))
                                                    ->values();
                                                for ($i = 1; $i < $roundTotals->count(); $i++) {
                                                    $deltaRound = round(((float) $roundTotals[$i]) - ((float) $roundTotals[$i - 1]), 2);
                                                    if ($deltaRound < 0) {
                                                        $sumEconomy += abs($deltaRound);
                                                    } elseif ($deltaRound > 0) {
                                                        $sumIncrease += $deltaRound;
                                                    }
                                                }
                                            }
                                            $sumNet = round($sumIncrease - $sumEconomy, 2);
                                        @endphp
                                        <div class="fw-semibold mb-2">Histórico por ordem</div>
                                        <div class="table-responsive border rounded" style="max-height: 240px;">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Ordem</th>
                                                        <th>Rodada</th>
                                                        <th>Enviado</th>
                                                        <th>Total</th>
                                                        <th>Empresa</th>
                                                        <th>Cliente</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($historyByOrder as $orderNumber => $entries)
                                                        @foreach($entries as $entry)
                                                            <tr class="{{ (int) $entry['round'] === (int) $selectedCycle->round_number ? 'table-active' : '' }}">
                                                                <td>{{ $orderNumber }}</td>
                                                                <td>{{ $entry['round'] }}</td>
                                                                <td>{{ $entry['submitted_at'] ? date('d/m/Y H:i', strtotime($entry['submitted_at'])) : '---' }}</td>
                                                                <td>{{ number_format((float) $entry['total_cost'], 2, ',', '.') }}</td>
                                                                <td>{{ number_format((float) $entry['company_cost'], 2, ',', '.') }}</td>
                                                                <td>{{ number_format((float) $entry['client_cost'], 2, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @empty
                                                        <tr><td colspan="6" class="text-center text-muted">Sem histórico de ordens.</td></tr>
                                                    @endforelse
                                                    @if(!empty($historyByOrder))
                                                        <tr class="table-light fw-semibold">
                                                            <td colspan="2">Totalizador da diferença</td>
                                                            <td colspan="2" class="text-success">Economia: {{ number_format($sumEconomy, 2, ',', '.') }}</td>
                                                            <td class="text-danger">Aumento: {{ number_format($sumIncrease, 2, ',', '.') }}</td>
                                                            <td class="{{ $sumNet < 0 ? 'text-success' : ($sumNet > 0 ? 'text-danger' : 'text-muted') }}">
                                                                Saldo: {{ number_format(abs($sumNet), 2, ',', '.') }}
                                                                @if($sumNet < 0)
                                                                    (economia)
                                                                @elseif($sumNet > 0)
                                                                    (aumento)
                                                                @else
                                                                    (mantido)
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Arquivos</div>
                                    <div class="card-body small">
                                        @php
                                            $noteFiles = ($selectedProduction?->Note?->Files ?? collect())
                                                ->sortBy(fn($f) => [($f->service->service ?? 'OUTROS'), ($f->file_name ?? '')])
                                                ->values();
                                            $fileServices = $noteFiles
                                                ->map(fn($f) => [
                                                    'id' => (string) ($f->service_id ?? 'others'),
                                                    'name' => $f->service->service ?? 'OUTROS',
                                                ])
                                                ->unique('id')
                                                ->values();
                                        @endphp
                                        <div x-data="{ serviceFilter: 'all' }">
                                            <div class="row g-2 mb-2">
                                                <div class="col-md-6">
                                                    <label class="form-label">Filtrar por serviço</label>
                                                    <select class="form-select form-select-sm" x-model="serviceFilter">
                                                        <option value="all">Todos</option>
                                                        @foreach($fileServices as $svc)
                                                            <option value="{{ $svc['id'] }}">{{ $svc['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="table-responsive border rounded" style="max-height: 240px;">
                                                <table class="table table-sm mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Serviço</th>
                                                            <th>Arquivo</th>
                                                            <th>Data</th>
                                                            <th style="width: 90px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($noteFiles as $file)
                                                            <tr x-show="serviceFilter === 'all' || serviceFilter === '{{ $file->service_id ?? 'others' }}'">
                                                                <td>{{ $file->service->service ?? 'OUTROS' }}</td>
                                                                <td class="text-break">{{ $file->file_name . ($file->ext ? '.' . $file->ext : '') }}</td>
                                                                <td>{{ $file->created_at ? date('d/m/Y H:i', strtotime($file->created_at)) : '---' }}</td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-outline-primary w-100" wire:click="downloadFile({{ $file->id }})">Baixar</button>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="4" class="text-center text-muted">Sem anexos.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-soft">
                                    <div class="card-header">Chat da rodada</div>
                                    <div class="card-body">
                                        @forelse ($this->reviewMessages as $msg)
                                            <div class="border rounded p-2 mb-1 {{ $msg->user_id === auth()->id() ? 'bg-light' : '' }}">
                                                <div class="small text-muted">{{ $msg->User->name ?? 'Usuário' }} - {{ date('d/m/Y H:i', strtotime($msg->created_at)) }}</div>
                                                <div>{{ $msg->message }}</div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">Sem mensagens nesta rodada.</div>
                                        @endforelse

                                        @if($this->canReply)
                                            <textarea
                                                class="form-control mt-2"
                                                rows="2"
                                                wire:model.defer="newReply"
                                                placeholder="Escreva uma mensagem"></textarea>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="addReply">
                                                Enviar mensagem
                                            </button>
                                        @else
                                            <div class="alert alert-light border mt-2 mb-0 py-2 small text-muted">
                                                Chat bloqueado para esta produção encerrada.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="card card-soft h-100">
                                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                                        <span>Estrutura da análise (histórico)</span>
                                        @if($this->canEditSelectedCycle)
                                            <div class="d-flex gap-2">
                                                @if(!$editingFindings)
                                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="startFindingsEdit">
                                                        Editar estrutura
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-success" wire:click="saveFindingsEdit">
                                                        Salvar alterações
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelFindingsEdit">
                                                        Cancelar
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        @if($editingFindings)
                                            <div class="alert alert-info py-2 small">
                                                Edição habilitada para rodadas não aprovadas. Ao salvar, será registrado um evento na timeline.
                                            </div>
                                            <div class="card border mb-3">
                                                <div class="card-header py-2">Adicionar apontamento</div>
                                                <div class="card-body py-2">
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-md-3">
                                                            <label class="form-label mb-1">Ref:</label>
                                                            <input type="text" class="form-control form-control-sm" wire:model.defer="selectedPointLabel" placeholder="Ex.: P1">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label mb-1">Categoria:</label>
                                                            <select class="form-select form-select-sm" wire:model="selectedCategoryId">
                                                                <option value="">Selecione...</option>
                                                                @foreach($categories as $cat)
                                                                    <option value="{{ data_get($cat, 'id') }}">{{ data_get($cat, 'name') }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label mb-1">Subcategoria:</label>
                                                            <select class="form-select form-select-sm" wire:model="selectedSubcategoryId">
                                                                <option value="">Selecione...</option>
                                                                @foreach($availableSubcategories as $sub)
                                                                    <option value="{{ data_get($sub, 'id') }}">{{ data_get($sub, 'name') }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label mb-1">Origem:</label>
                                                            <select class="form-select form-select-sm" wire:model="selectedOrigin">
                                                                <option value="LEVANTAMENTO">Levantamento</option>
                                                                <option value="PROJETO">Projeto</option>
                                                                <option value="AMBOS">Ambos</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label mb-1">Movimento:</label>
                                                            <select class="form-select form-select-sm" wire:model="selectedActionType">
                                                                <option value="FALTA">FALTA</option>
                                                                <option value="ADICIONAR">ADICIONAR</option>
                                                                <option value="REMOVER">REMOVER</option>
                                                                <option value="ALTERAR">ALTERAR</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-9 d-flex flex-wrap gap-2">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                wire:click="addHistoryEmptySubcategory">
                                                                Adicionar subcategoria sem item
                                                            </button>
                                                            @foreach($availableItems as $item)
                                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                                    wire:click="addHistoryItemToFindings({{ data_get($item, 'id') }})">
                                                                    + {{ data_get($item, 'name') }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive border rounded mb-3">
                                                <table class="table table-sm mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Ref:</th>
                                                            <th>Subcategoria</th>
                                                            <th>Ação</th>
                                                            <th>Qtd.</th>
                                                            <th>Origem</th>
                                                            <th>Observação</th>
                                                            <th style="width: 70px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($historyFindingRows as $idx => $row)
                                                            <tr>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm"
                                                                        wire:model.defer="historyFindingRows.{{ $idx }}.point_label">
                                                                </td>
                                                                <td>
                                                                    <span class="small">{{ $row['subcategory_name'] ?? '---' }}</span>
                                                                    @if(!empty($row['item_name']))
                                                                        <div class="text-muted small">{{ $row['item_name'] }}</div>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" wire:model.defer="historyFindingRows.{{ $idx }}.action_type">
                                                                        <option value="FALTA">FALTA</option>
                                                                        <option value="ADICIONAR">ADICIONAR</option>
                                                                        <option value="REMOVER">REMOVER</option>
                                                                        <option value="ALTERAR">ALTERAR</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="number" min="1" class="form-control form-control-sm"
                                                                        wire:model.defer="historyFindingRows.{{ $idx }}.quantity">
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" wire:model.defer="historyFindingRows.{{ $idx }}.origin">
                                                                        <option value="LEVANTAMENTO">Levantamento</option>
                                                                        <option value="PROJETO">Projeto</option>
                                                                        <option value="AMBOS">Ambos</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <textarea class="form-control form-control-sm" rows="2"
                                                                        wire:model.defer="historyFindingRows.{{ $idx }}.note"></textarea>
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                                        wire:click="removeHistoryFindingRow({{ $idx }})" title="Excluir">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="text-center text-muted py-3">Sem apontamentos para editar.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif

                                        @php
                                            $historyFilteredFindings = collect($this->filteredHistoryFindings ?? [])->values();
                                        @endphp

                                        @if ($historyFilteredFindings->count() > 0)
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1">Filtrar por ref:</label>
                                                    <select class="form-select form-select-sm" wire:model="selectedHistoryPointFilter">
                                                        <option value="">Todas as refs</option>
                                                        @foreach ($this->availableHistoryPoints as $ref)
                                                            <option value="{{ $ref }}">{{ $ref }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            @php
                                                $pointsTree = $historyFilteredFindings->groupBy(function ($f) {
                                                    $label = trim((string) ($f->point_label ?? ''));
                                                    return $label !== '' ? mb_strtoupper($label, 'UTF-8') : 'SEM REFERENCIA';
                                                });
                                            @endphp

                                            @foreach($pointsTree as $pointLabel => $pointRows)
                                                @php($pointId = 'hist_point_' . md5($pointLabel))
                                                <div class="card border-primary-subtle mb-2">
                                                    <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
                                                        <button class="btn btn-link text-decoration-none p-0 fw-semibold text-primary"
                                                            data-bs-toggle="collapse" data-bs-target="#{{ $pointId }}">
                                                            Ref: {{ $pointLabel }}
                                                        </button>
                                                        <span class="badge bg-primary">{{ $pointRows->count() }} item(ns)</span>
                                                    </div>
                                                    <div class="collapse show" id="{{ $pointId }}">
                                                        <div class="card-body py-2">
                                                            @foreach($pointRows->groupBy(fn($f) => optional(optional($f->Subcategory)->Category)->name ?: 'Sem categoria') as $catName => $catRows)
                                                                @php($catId = 'hist_cat_' . md5($pointLabel . '_' . $catName))
                                                                <div class="card border mb-2">
                                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                                        <button class="btn btn-link text-decoration-none p-0 fw-semibold"
                                                                            data-bs-toggle="collapse" data-bs-target="#{{ $catId }}">
                                                                            {{ $catName }}
                                                                        </button>
                                                                        <span class="badge bg-light text-dark">{{ $catRows->count() }} item(ns)</span>
                                                                    </div>
                                                                    <div class="collapse show" id="{{ $catId }}">
                                                                        <div class="card-body py-2">
                                                                            @foreach($catRows->groupBy(fn($f) => optional($f->Subcategory)->name ?: 'Sem subcategoria') as $subName => $subRows)
                                                                                @php($subId = 'hist_sub_' . md5($pointLabel . '_' . $catName . '_' . $subName))
                                                                                <div class="border rounded mb-2">
                                                                                    <div class="px-2 py-1 border-bottom d-flex justify-content-between align-items-center">
                                                                                        <button class="btn btn-link text-decoration-none p-0 fw-semibold"
                                                                                            data-bs-toggle="collapse" data-bs-target="#{{ $subId }}">
                                                                                            {{ $subName }}
                                                                                        </button>
                                                                                        <span class="small text-muted">{{ $subRows->count() }} apontamento(s)</span>
                                                                                    </div>
                                                                                    <div class="collapse show" id="{{ $subId }}">
                                                                                        <div class="table-responsive">
                                                                                            <table class="table table-sm mb-0">
                                                                                                <thead class="table-light">
                                                                                                    <tr>
                                                                                                        <th>Ação</th>
                                                                                                        <th>Qtd.</th>
                                                                                                        <th>Origem</th>
                                                                                                        <th>Observação</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    @foreach($subRows as $f)
                                                                                                        <tr>
                                                                                                            <td>
                                                                                                                @if($f->item_id)
                                                                                                                    {{ $f->action_type ?? 'FALTA' }} {{ optional($f->Item)->name }}
                                                                                                                @else
                                                                                                                    ---
                                                                                                                @endif
                                                                                                            </td>
                                                                                                            <td>{{ $f->quantity ?? '---' }}</td>
                                                                                                            <td>{{ $f->origin ?? '---' }}</td>
                                                                                                            <td>{{ $f->note ?: '---' }}</td>
                                                                                                        </tr>
                                                                                                    @endforeach
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="alert alert-light border mb-0">Sem apontamentos registrados nesta rodada.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click="closeModal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
