<div class="oexterno-page">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-accent: #0f766e;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
            padding: 1.5rem 0;
        }

        .oexterno-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .filter-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .filter-card .form-label {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: .4rem;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            color: #0f172a;
            border-color: #cbd5e1;
            background: #fff;
        }

        .table-card {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: visible;
        }

        .table-card > .card-header {
            padding: .8rem 1rem;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }

        .table-card > .card-body {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .table-card .table-responsive {
            margin: 0 1rem 1rem 1rem;
            border: 1px solid var(--oe-border);
            border-radius: .75rem;
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

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

        .card-soft {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: .85rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .analysis-items-scroll {
            max-height: none;
            overflow: visible;
        }

        .analysis-findings-scroll {
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }

        .step-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .step-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0f766e;
            color: #fff;
            font-size: .85rem;
        }

        .step-help {
            color: var(--oe-muted);
            font-size: .85rem;
            margin-bottom: .75rem;
        }

        .summary-pill {
            border: 1px solid var(--oe-border);
            border-radius: .75rem;
            padding: .5rem .75rem;
            background: #fff;
            font-size: .85rem;
        }

        .chat-stream {
            max-height: 240px;
            overflow: auto;
            border: 1px solid var(--oe-border);
            border-radius: .75rem;
            padding: .5rem;
            background: #f8fafc;
        }

        .chat-bubble {
            max-width: 90%;
            border-radius: .75rem;
            padding: .5rem .65rem;
            border: 1px solid var(--oe-border);
            background: #fff;
        }

        .chat-bubble.mine {
            background: #ecfeff;
            border-color: #99f6e4;
        }

        .group-head-btn {
            font-weight: 600;
            color: #0f172a;
        }

        .group-head-btn:hover {
            color: #0f766e;
        }

        .project-review-modal-body {
            padding: 1.25rem 1.25rem 1.75rem 1.25rem;
        }

        .cat-card {
            border: 1px solid #dbeafe !important;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .cat-card > .card-header {
            background: linear-gradient(135deg, #eff6ff, #eef2ff);
        }

        .subcat-card {
            border: 1px solid #e5e7eb !important;
            background: #f8fafc;
        }

        .subcat-card > .card-header {
            background: #f1f5f9;
        }

        .origin-card {
            border: 1px solid #e5e7eb;
            border-radius: .6rem;
            background: #fff;
            overflow: hidden;
        }

        .origin-head {
            padding: .45rem .65rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: .82rem;
            letter-spacing: .02em;
        }

        .origin-head.origin-levantamento {
            background: #fff7ed;
            color: #9a3412;
        }

        .origin-head.origin-projeto {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .origin-head.origin-ambos {
            background: #ecfdf5;
            color: #065f46;
        }

        .icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        @media (min-width: 992px) {
            .project-review-modal-body {
                padding: 1.5rem 1.75rem 2rem 1.75rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-0">ANÁLISE PROJETO</h2>
                <div>Lista para analisar</div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="row g-2">
                    <div class="col-12 col-md-3">
                        <div class="filter-card">
                        <label class="form-label">Empresa</label>
                        <select class="form-select" wire:model="company_id">
                            <option value="">Todas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="filter-card">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" wire:model="note_type_filter">
                            <option value="">Todos</option>
                            <option value="retorno">Apenas Retorno</option>
                            <option value="inicial">Apenas Inicial</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="filter-card">
                        <label class="form-label">Custo (51%+)</label>
                        <select class="form-select" wire:model="cost_share_filter">
                            <option value="">Todos</option>
                            <option value="client_51">Cliente</option>
                            <option value="company_51">Empresa</option>
                            <option value="both_51">Cliente ou Empresa (Ambos)</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="filter-card">
                        <label class="form-label">Itens por página</label>
                        <select class="form-select" wire:model="perPage">
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="filter-card">
                        <label class="form-label">Buscar nota</label>
                        <div class="input-group">
                            <input type="text" class="form-control" wire:model.debounce.500ms="search" placeholder="Nota, pedido, descrição...">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#queueMassSearchModal">
                                Em massa
                            </button>
                        </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="filter-card">
                        <label class="form-label">Filtro de Custo</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" wire:model="cost_metric">
                                <option value="">Campo</option>
                                <option value="total_cost">TOTAL</option>
                                <option value="company_cost">EMPRESA</option>
                                <option value="client_cost">CLIENTE</option>
                            </select>
                            <select class="form-select" style="max-width: 90px;" wire:model="cost_operator">
                                <option value=">">&gt;</option>
                                <option value="<">&lt;</option>
                            </select>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model.debounce.500ms="cost_value" placeholder="Valor">
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $currentPageCount = method_exists($lists, 'count') ? $lists->count() : count($lists);
            $totalCount = method_exists($lists, 'total') ? (int) $lists->total() : $currentPageCount;
        @endphp

        <div class="summary-bar d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="summary-item">
                Exibindo <strong>{{ $currentPageCount }}</strong> registro(s) nesta página.
            </div>
            <div class="summary-item">
                Total na fila atual: <strong>{{ $totalCount }}</strong>.
            </div>
        </div>

        <div class="table-card">
            <div class="card-header text-bg-dark fw-bold">Atividades > Análise de Projeto</div>
            <div class="card-body border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-page" wire:model="selectPage">
                        <label class="form-check-label" for="select-page">
                            Selecionar todos da página
                        </label>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary" wire:click="exportList">
                            Exportar
                        </button>
                        <span class="small text-muted">{{ count($selectedProductionIds) }} selecionada(s)</span>
                        <button class="btn btn-sm btn-success" wire:click="approveSelected"
                            @disabled(!count($selectedProductionIds))>
                            Aprovar em massa
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 38px;"></th>
                            <th>Nota</th>
                            <th>Desenhista</th>
                            <th>Empresa</th>
                            <th>Ordens</th>
                            <th>Custo total</th>
                            <th>Custo empresa</th>
                            <th>Custo cliente</th>
                            <th>Status</th>
                            <th>Tipo</th>
                            <th>Prazo Real</th>
                            <th>Quando foi enviado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $prod)
                            @php
                                $cycle = collect($prod->ProjectReviewCycles)->sortByDesc('round_number')->first();
                                $orders = $cycle?->Orders ?? collect();
                                $orders = collect($orders)
                                    ->sortBy(fn ($o) => [(string) ($o->order_number ?? ''), (int) ($o->id ?? 0)])
                                    ->values();
                                $hasDraft = in_array((int) $prod->id, $draftProductionIds, true);
                            @endphp
                            <tr wire:key="pr-queue-row-{{ $prod->id }}">
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                        wire:model="selectedProductionIds" value="{{ $prod->id }}">
                                </td>
                                <td>
                                    {{ $prod->Note->note ?? '---' }}
                                    @if($hasDraft)
                                        <span class="badge text-bg-warning ms-1">Rascunho</span>
                                    @endif
                                </td>
                                <td>{{ $prod->User->name ?? '---' }}</td>
                                <td>{{ $prod->Company->name ?? '---' }}</td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1" wire:key="pr-queue-order-{{ $prod->id }}-{{ $ord->id }}"><strong>{{ $ord->order_number }}</strong></div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1" wire:key="pr-queue-total-{{ $prod->id }}-{{ $ord->id }}">{{ number_format((float) $ord->total_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1" wire:key="pr-queue-company-{{ $prod->id }}-{{ $ord->id }}">{{ number_format((float) $ord->company_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1" wire:key="pr-queue-client-{{ $prod->id }}-{{ $ord->id }}">{{ number_format((float) $ord->client_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $status = \App\Custom\Notestatus::status($prod->status);
                                        $latestRound = (int) ($prod->latest_round_number ?? ($cycle?->round_number ?? 1));
                                        $rejectedCount = (int) ($prod->rejected_cycles_count ?? 0);
                                        $rejectedTimelineCount = (int) ($prod->rejected_status_timeline_count ?? 0);
                                        $isReturnToReview = ($latestRound > 1)
                                            || ($rejectedCount > 0)
                                            || ($rejectedTimelineCount > 0)
                                            || collect($prod->ProjectReviewCycles)->contains(fn ($c) => $c->decision === 'REJECTED');
                                    @endphp
                                    <span class="badge {{ $status->colorbg }}">{{ $status->status }}</span>
                                </td>
                                <td>
                                    @if ($isReturnToReview)
                                        <span class="badge text-bg-warning">Retorno</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inicial</span>
                                    @endif
                                </td>
                                @php
                                    $daysLeft = is_numeric(data_get($prod, 'Note.days_left')) ? (int) data_get($prod, 'Note.days_left') : null;
                                    $prazoRealClass = 'text-bg-secondary';
                                    $prazoRealValue = '---';

                                    if (!is_null($daysLeft)) {
                                        $prazoRealValue = (string) (30 - $daysLeft);
                                        if ($daysLeft < 0) {
                                            $prazoRealClass = 'text-bg-secondary';
                                        } elseif ($daysLeft < 6) {
                                            $prazoRealClass = 'text-bg-danger';
                                        } elseif ($daysLeft < 10) {
                                            $prazoRealClass = 'text-bg-warning';
                                        } else {
                                            $prazoRealClass = 'text-bg-success';
                                        }
                                    }
                                @endphp
                                <td class="text-center {{ $prazoRealClass }}" tabindex="0"
                                    data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="top" data-bs-title="Prazo Real"
                                    data-bs-content="
                                        <p>Os prazos contados já foram expurgado os tempos em status não contabilizáveis.</p>
                                        <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                                        <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                                        <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                                        <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                                    ">
                                    {{ $prazoRealValue }}
                                </td>
                                <td>{{ $cycle?->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---' }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        wire:click.prevent="openReview({{ (int) $prod->id }})"
                                    >
                                        Abrir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                @if($lists instanceof \Illuminate\Contracts\Pagination\Paginator || $lists instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="small text-muted">
                            Mostrando {{ $lists->firstItem() ?? 0 }} até {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }} registros.
                        </div>
                    </div>
                    {{ $lists->links() }}
                @endif
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="queueMassSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar Nota/OV em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Cole os códigos (nota ou OV)</label>
                    <textarea
                        class="form-control"
                        rows="8"
                        wire:model.debounce.500ms="mass_search"
                        placeholder="Separe por vírgula, espaço ou quebra de linha"></textarea>
                    <div class="small text-muted mt-2">
                        Exemplo: 12345, 67890 ou uma linha por código.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="projectReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title">Análise de Projeto - {{ $selectedProduction?->Note?->note }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body oexterno-page project-review-modal-body">
                    @if ($selectedCycle)
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="step-title"><span class="step-badge">1</span>Contexto da Produção</div>
                                <div class="step-help">Dados de referência para validar a análise e baixar os arquivos do projeto.</div>
                                <div class="card card-soft mb-3">
                                    <div class="card-header">Informações da Nota</div>
                                    <div class="card-body small">
                                        <div><strong>Nota:</strong> {{ $selectedProduction?->Note?->note ?? '---' }}</div>
                                        <div><strong>Desenhista:</strong> {{ $selectedProduction?->User?->name ?? '---' }}</div>
                                        <div><strong>Empresa:</strong> {{ $selectedProduction?->Company?->name ?? '---' }}</div>
                                        <div><strong>Serviço:</strong> {{ $selectedProduction?->Service?->service ?? '---' }}</div>
                                        <div><strong>Rodada:</strong> {{ $selectedCycle->round_number }}</div>
                                        <div><strong>Enviado em:</strong> {{ $selectedCycle->submitted_at ? date('d/m/Y H:i', strtotime($selectedCycle->submitted_at)) : '---' }}</div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Ordens e Valores</div>
                                    <div class="card-body small">
                                        @php
                                            $cyclesAsc = ($selectedProduction?->ProjectReviewCycles ?? collect())
                                                ->sortBy('round_number')
                                                ->values();

                                            $orderHistory = collect();
                                            foreach ($cyclesAsc as $cycleRow) {
                                                foreach (($cycleRow->Orders ?? collect()) as $ordRow) {
                                                    $orderHistory->push([
                                                        'round' => (int) $cycleRow->round_number,
                                                        'submitted_at' => $cycleRow->submitted_at,
                                                        'order_number' => (string) $ordRow->order_number,
                                                        'total_cost' => (float) $ordRow->total_cost,
                                                        'company_cost' => (float) $ordRow->company_cost,
                                                        'client_cost' => (float) $ordRow->client_cost,
                                                    ]);
                                                }
                                            }

                                            $historyGrouped = $orderHistory
                                                ->groupBy(fn ($row) => trim((string) ($row['order_number'] ?? '')))
                                                ->map(function ($rows) {
                                                    $rows = collect($rows)->sortBy('round')->values();
                                                    $prev = null;
                                                    return $rows->map(function ($row) use (&$prev) {
                                                        $delta = is_null($prev) ? null : round(((float) $row['total_cost']) - ((float) $prev), 2);
                                                        $row['delta_total'] = $delta;
                                                        $prev = (float) $row['total_cost'];
                                                        return $row;
                                                    });
                                                })
                                                ->sortKeysUsing(fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));

                                            // Totalizador da diferença por rodada (não por número da ordem),
                                            // para cobrir cenários de troca/cancelamento de ordem entre ciclos.
                                            $roundTotals = $cyclesAsc
                                                ->map(function ($cy) {
                                                    $allowedPrefixes = ['170', '190', '150', '200'];
                                                    $latestByPrefix = [];
                                                    $fallbackTotal = 0.0;

                                                    foreach (collect($cy->Orders ?? collect()) as $ord) {
                                                        $fallbackTotal += (float) ($ord->total_cost ?? 0);
                                                        $digits = preg_replace('/\D+/', '', (string) ($ord->order_number ?? ''));
                                                        $prefix = strlen($digits) >= 3 ? substr($digits, 0, 3) : '';

                                                        if (in_array($prefix, $allowedPrefixes, true)) {
                                                            $latestByPrefix[$prefix] = (float) ($ord->total_cost ?? 0);
                                                        }
                                                    }

                                                    if (count($latestByPrefix)) {
                                                        return (float) array_sum($latestByPrefix);
                                                    }

                                                    return $fallbackTotal;
                                                })
                                                ->values();

                                            $sumIncrease = 0.0;
                                            $sumEconomy = 0.0;
                                            $prefixSeries = collect(['170', '190', '150', '200'])->mapWithKeys(fn($p) => [$p => []])->all();
                                            foreach ($cyclesAsc as $cycleRow) {
                                                $roundPrefixTotals = [];
                                                foreach (collect($cycleRow->Orders ?? collect()) as $ord) {
                                                    $digits = preg_replace('/\D+/', '', (string) ($ord->order_number ?? ''));
                                                    $prefix = strlen($digits) >= 3 ? substr($digits, 0, 3) : '';
                                                    if (in_array($prefix, ['170', '190', '150', '200'], true)) {
                                                        $roundPrefixTotals[$prefix] = (float) ($ord->total_cost ?? 0);
                                                    }
                                                }
                                                foreach (['170', '190', '150', '200'] as $prefix) {
                                                    if (array_key_exists($prefix, $roundPrefixTotals)) {
                                                        $prefixSeries[$prefix][] = (float) $roundPrefixTotals[$prefix];
                                                    }
                                                }
                                            }

                                            $hasPrefixDataForTotals = false;
                                            foreach (['170', '190', '150', '200'] as $prefix) {
                                                $values = collect($prefixSeries[$prefix] ?? [])->values();
                                                if ($values->count() < 2) {
                                                    continue;
                                                }
                                                $hasPrefixDataForTotals = true;
                                                $deltaPrefix = round(((float) $values->last()) - ((float) $values->first()), 2);
                                                if ($deltaPrefix > 0) {
                                                    $sumIncrease += $deltaPrefix;
                                                } elseif ($deltaPrefix < 0) {
                                                    $sumEconomy += abs($deltaPrefix);
                                                }
                                            }

                                            if (!$hasPrefixDataForTotals) {
                                                for ($i = 1; $i < $roundTotals->count(); $i++) {
                                                    $deltaRound = round(((float) $roundTotals[$i]) - ((float) $roundTotals[$i - 1]), 2);
                                                    if ($deltaRound > 0) {
                                                        $sumIncrease += $deltaRound;
                                                    } elseif ($deltaRound < 0) {
                                                        $sumEconomy += abs($deltaRound);
                                                    }
                                                }
                                            }

                                            $baseCycle = $cyclesAsc->first();
                                            $currentCycle = $cyclesAsc->first(function ($cy) use ($selectedCycle) {
                                                return (int) ($cy->id ?? 0) === (int) ($selectedCycle->id ?? 0);
                                            }) ?: $selectedCycle;

                                            $normalizeOrderRows = function ($ordersCollection) {
                                                $rows = collect($ordersCollection ?? collect())
                                                    ->map(function ($ord) {
                                                        $orderNumber = (string) ($ord->order_number ?? '');
                                                        $digits = preg_replace('/\D+/', '', $orderNumber);
                                                        $prefix = strlen($digits) >= 3 ? substr($digits, 0, 3) : '';

                                                        return [
                                                            'order_number' => $orderNumber,
                                                            'prefix' => $prefix,
                                                            'total_cost' => (float) ($ord->total_cost ?? 0),
                                                            'company_cost' => (float) ($ord->company_cost ?? 0),
                                                            'client_cost' => (float) ($ord->client_cost ?? 0),
                                                        ];
                                                    })
                                                    ->values();

                                                $withPrefix = $rows->filter(fn ($row) => in_array($row['prefix'], ['170', '190', '150', '200'], true))
                                                    ->groupBy('prefix')
                                                    ->map(fn ($group) => $group->last())
                                                    ->all();

                                                if (count($withPrefix)) {
                                                    return collect($withPrefix)->values();
                                                }

                                                return $rows;
                                            };

                                            $baseRows = $normalizeOrderRows($baseCycle?->Orders ?? collect())->values();
                                            $currentRows = $normalizeOrderRows($currentCycle?->Orders ?? collect())->values();
                                            if ($currentRows->isEmpty()) {
                                                $fallbackCycleWithOrders = $cyclesAsc
                                                    ->filter(function ($cy) use ($currentCycle) {
                                                        return (int) ($cy->round_number ?? 0) <= (int) ($currentCycle->round_number ?? 0)
                                                            && collect($cy->Orders ?? collect())->count() > 0;
                                                    })
                                                    ->sortByDesc('round_number')
                                                    ->first();

                                                if ($fallbackCycleWithOrders) {
                                                    $currentRows = $normalizeOrderRows($fallbackCycleWithOrders->Orders ?? collect())->values();
                                                }
                                            }

                                            $maxRows = max($baseRows->count(), $currentRows->count());
                                            $comparisonRows = collect(range(0, max(0, $maxRows - 1)))->map(function ($idx) use ($baseRows, $currentRows) {
                                                $old = $baseRows->get($idx);
                                                $new = $currentRows->get($idx);

                                                $oldTotal = (float) ($old['total_cost'] ?? 0);
                                                $newTotal = (float) ($new['total_cost'] ?? 0);

                                                return [
                                                    'old_order' => $old['order_number'] ?? '---',
                                                    'new_order' => $new['order_number'] ?? '---',
                                                    'old_total' => $oldTotal,
                                                    'new_total' => $newTotal,
                                                    'delta_total' => round($newTotal - $oldTotal, 2),
                                                ];
                                            })->values();

                                            // O totalizador deve refletir exatamente o comparativo exibido acima.
                                            $sumIncrease = round((float) $comparisonRows->sum(function ($row) {
                                                $delta = (float) ($row['delta_total'] ?? 0);
                                                return $delta > 0 ? $delta : 0;
                                            }), 2);
                                            $sumEconomy = round((float) $comparisonRows->sum(function ($row) {
                                                $delta = (float) ($row['delta_total'] ?? 0);
                                                return $delta < 0 ? abs($delta) : 0;
                                            }), 2);
                                            $sumNet = round($sumIncrease - $sumEconomy, 2);
                                        @endphp
                                        <div class="fw-semibold mb-1">Comparativo de ordens (origem x rodada {{ $selectedCycle->round_number }})</div>
                                        <div class="table-responsive border rounded mb-3" style="max-height: 220px;">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Ordem original</th>
                                                        <th>Total original</th>
                                                        <th>Ordem rodada atual</th>
                                                        <th>Total rodada atual</th>
                                                        <th>Delta</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($comparisonRows as $cmp)
                                                        <tr>
                                                            <td>{{ $cmp['old_order'] }}</td>
                                                            <td>{{ number_format((float) $cmp['old_total'], 2, ',', '.') }}</td>
                                                            <td>{{ $cmp['new_order'] }}</td>
                                                            <td>{{ number_format((float) $cmp['new_total'], 2, ',', '.') }}</td>
                                                            <td class="{{ $cmp['delta_total'] > 0 ? 'text-danger' : ($cmp['delta_total'] < 0 ? 'text-success' : 'text-muted') }}">
                                                                {{ number_format(abs((float) $cmp['delta_total']), 2, ',', '.') }}
                                                                @if($cmp['delta_total'] > 0)
                                                                    (aumento)
                                                                @elseif($cmp['delta_total'] < 0)
                                                                    (economia)
                                                                @else
                                                                    (mantido)
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">Sem dados para comparar ordens.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="fw-semibold mb-1">Histórico por ordem</div>
                                        <div class="table-responsive border rounded" style="max-height: 240px;">
                                            <table class="table table-sm mb-0">
                                                <thead>
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
                                                    @forelse($historyGrouped as $ordNumber => $rows)
                                                        @foreach($rows as $r)
                                                            <tr class="{{ (int) $r['round'] === (int) $selectedCycle->round_number ? 'table-active' : '' }}">
                                                                <td>{{ $ordNumber }}</td>
                                                                <td>{{ $r['round'] }}</td>
                                                                <td>{{ $r['submitted_at'] ? date('d/m/Y H:i', strtotime($r['submitted_at'])) : '---' }}</td>
                                                                <td>{{ number_format((float) $r['total_cost'], 2, ',', '.') }}</td>
                                                                <td>{{ number_format((float) $r['company_cost'], 2, ',', '.') }}</td>
                                                                <td>{{ number_format((float) $r['client_cost'], 2, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Sem histórico de ordens.</td>
                                                        </tr>
                                                    @endforelse
                                                    @if($historyGrouped->isNotEmpty())
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
                                    <div class="card-header">Detalhe do Encerramento</div>
                                    <div class="card-body small">
                                        <div><strong>Finalidade:</strong> {{ $selectedProduction?->Analise?->preresult ?? '---' }}</div>
                                        <div><strong>Conclusão:</strong> {{ $selectedProduction?->Analise?->conclusion ?? '---' }}</div>
                                        <div class="mt-2"><strong>Informações:</strong></div>
                                        <div class="text-muted" style="white-space: pre-line;">{{ $selectedProduction?->Analise?->info ?? '---' }}</div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Arquivos do Projeto</div>
                                    <div class="card-body small">
                                        @php
                                            $noteFiles = ($selectedProduction?->Note?->Files ?? collect())
                                                ->sortByDesc(function ($f) {
                                                    return strtotime((string) ($f->created_at ?? '1970-01-01 00:00:00'));
                                                })
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

                                        @if ($drawingProduction)
                                            <hr>
                                            <div class="mb-2 text-muted">
                                                Upload será vinculado ao serviço:
                                                <strong>{{ $drawingProduction->Service->service ?? 'Desenho' }}</strong>
                                            </div>
                                            @livewire('files.manager.create-prod-files', ['production' => $drawingProduction, 'needFiles' => false], key('project_review_analyst_files_' . $drawingProduction->id))
                                            <div class="d-flex justify-content-end mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-success" wire:click="saveAnalystFiles">
                                                    Salvar uploads do analista
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="card card-soft">
                                    <div class="card-header">Parecer Técnico do Analista</div>
                                    <div class="card-body">
                                        <textarea class="form-control @error('analystNote') is-invalid @enderror" rows="5"
                                            wire:model.defer="analystNote"
                                            placeholder="Obrigatório apenas em 'Aprovar com ressalvas'"></textarea>
                                        @error('analystNote')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="card card-soft mt-3">
                                    <div class="card-header">Chat da Análise</div>
                                    <div class="card-body">
                                        <div class="chat-stream mb-2">
                                            @forelse ($this->reviewMessages as $msg)
                                                @php
                                                    $mine = $msg->user_id === auth()->id();
                                                @endphp
                                                <div class="d-flex mb-2 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                                                    <div class="chat-bubble {{ $mine ? 'mine' : '' }}">
                                                        <div class="small text-muted">{{ $msg->User->name ?? 'Usuário' }} - {{ date('d/m/Y H:i', strtotime($msg->created_at)) }}</div>
                                                        <div>{{ $msg->message }}</div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="small text-muted">Sem mensagens ainda nesta rodada.</div>
                                            @endforelse
                                        </div>
                                        <textarea class="form-control mt-2" rows="2" wire:model.defer="newReply" placeholder="Escreva uma mensagem"></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="addReply">Enviar mensagem</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="step-title"><span class="step-badge">2</span>Montagem da Análise</div>
                                <div class="step-help">Marque como conforme o que foi corrigido. Reprove somente com pendências restantes.</div>
                                <div class="card card-soft h-100">
                                    <div class="card-header">Montagem da Análise do Projeto</div>
                                    <div class="card-body">
                                        @php
                                            $totalRows = count($findingRows);
                                            $conformRows = collect($findingRows)->filter(fn ($r) => !empty($r['is_conform']))->count();
                                            $pendingRows = $totalRows - $conformRows;
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <div class="summary-pill"><strong>Total:</strong> {{ $totalRows }}</div>
                                            <div class="summary-pill"><strong>Conformes:</strong> {{ $conformRows }}</div>
                                            <div class="summary-pill"><strong>Pendentes:</strong> {{ $pendingRows }}</div>
                                            <div class="summary-pill"><strong>Rascunho:</strong> {{ $draftSavedAt ?: 'ainda não salvo' }}</div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-lg-6">
                                                <div class="card border mb-3">
                                                    <div class="card-header">Seleção</div>
                                                    <div class="card-body">
                                                        <div class="mb-2">
                                                            <label class="form-label">Ref: (referência textual)</label>
                                                            <input type="text" class="form-control"
                                                                wire:model.defer="selectedPointLabel"
                                                                placeholder="Ex.: P1, P2, Poste 10-15, Trecho A">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Categoria</label>
                                                            <select class="form-select" wire:model="selectedCategoryId">
                                                                <option value="">Selecione</option>
                                                                @foreach ($categories as $cat)
                                                                    <option value="{{ data_get($cat, 'id') }}">{{ data_get($cat, 'name') }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Subcategoria</label>
                                                            <select class="form-select" wire:model="selectedSubcategoryId">
                                                                <option value="">Selecione</option>
                                                                @foreach ($availableSubcategories as $sub)
                                                                    <option value="{{ data_get($sub, 'id') }}">{{ data_get($sub, 'name') }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Movimento</label>
                                                            <select class="form-select" wire:model="selectedActionType">
                                                                <option value="FALTA">FALTA</option>
                                                                <option value="ADICIONAR">ADICIONAR</option>
                                                                <option value="REMOVER">REMOVER</option>
                                                                <option value="ALTERAR">ALTERAR</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-0">
                                                            <label class="form-label">Origem</label>
                                                            <select class="form-select" wire:model="selectedOrigin">
                                                                <option value="PROJETO">Projeto</option>
                                                                <option value="LEVANTAMENTO">Levantamento</option>
                                                                <option value="AMBOS">Ambos</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="card border">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <span>Itens da subcategoria</span>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            wire:click="addEmptySubcategory" @disabled(!$selectedSubcategoryId)>
                                                            Adicionar subcategoria vazia
                                                        </button>
                                                    </div>
                                                    <div class="card-body analysis-items-scroll">
                                                        @if ($selectedSubcategoryId && $availableItems->count())
                                                            <div class="table-responsive">
                                                                <table class="table table-sm mb-0">
                                                                    <thead><tr><th>Item</th><th style="width: 120px;"></th></tr></thead>
                                                                    <tbody>
                                                                        @foreach ($availableItems as $item)
                                                                            <tr>
                                                                                <td>{{ data_get($item, 'name') }}</td>
                                                                                <td><button type="button" class="btn btn-sm btn-outline-primary w-100" wire:click="addItemToFindings({{ data_get($item, 'id') }})">Adicionar</button></td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @else
                                                            <div class="text-muted small">Selecione uma subcategoria para listar os itens.</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="d-flex flex-wrap align-items-end gap-2 mb-2">
                                                    <div>
                                                        <label class="form-label mb-1">Filtrar por ref:</label>
                                                        <select class="form-select form-select-sm" wire:model="selectedPointFilter">
                                                            <option value="">Todas</option>
                                                            @foreach($this->availablePointLabels as $pointLabel)
                                                                <option value="{{ $pointLabel }}">{{ $pointLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="small text-muted">
                                                        Dica: o mesmo item pode se repetir em refs diferentes.
                                                    </div>
                                                </div>
                                                @if ($duplicateMode !== '')
                                                    <div class="alert alert-info d-flex flex-wrap align-items-end gap-2 py-2">
                                                        <div class="flex-grow-1" style="min-width: 280px;">
                                                            <label class="form-label mb-1">
                                                                Nome da ref: para duplicar
                                                            </label>
                                                            <input type="text" class="form-control form-control-sm"
                                                                wire:model.defer="duplicatePointLabel"
                                                                placeholder="Ex.: P2, Trecho B, Poste 10-15">
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            wire:click="confirmDuplicate">
                                                            Confirmar duplicação
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            wire:click="cancelDuplicate">
                                                            Cancelar
                                                        </button>
                                                    </div>
                                                @endif
                                                <div class="analysis-findings-scroll">
                                                    @forelse($findingsTree as $pointGroup)
                                                        @php
                                                            $pointRenameKey = 'rename_' . md5($pointGroup['point_label']);
                                                        @endphp
                                                        <div class="card border-primary mb-2">
                                                            <div class="card-header d-flex justify-content-between align-items-center py-2 gap-2">
                                                                <div class="fw-semibold">
                                                                    Ref: {{ $pointGroup['point_label'] }}
                                                                </div>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <input type="text" class="form-control form-control-sm"
                                                                        style="width: 180px;"
                                                                        wire:model.defer="pointRenameInputs.{{ $pointRenameKey }}"
                                                                        placeholder="Renomear ref:">
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                        wire:click="renamePointGroup('{{ addslashes($pointGroup['point_label']) }}', '{{ $pointRenameKey }}')"
                                                                        title="Salvar novo nome da ref">
                                                                        Salvar nome
                                                                    </button>
                                                                    <span class="badge text-bg-primary">
                                                                        {{ count($pointGroup['categories']) }} categoria(s)
                                                                    </span>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                                        wire:click="requestDuplicatePointGroup('{{ addslashes($pointGroup['point_label']) }}')"
                                                                        title="Duplicar grupo da ref">
                                                                        Duplicar grupo
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="card-body py-2">
                                                                @foreach($pointGroup['categories'] as $category)
                                                                    @php
                                                                        $catCollapsed = $collapsedCategories[$category['category_key']] ?? false;
                                                                    @endphp
                                                                    <div class="card cat-card mb-2">
                                                                        <div class="card-header d-flex justify-content-between align-items-center py-2 gap-2">
                                                                            <button type="button" class="btn btn-link text-decoration-none p-0 group-head-btn"
                                                                                wire:click="toggleCategoryGroup('{{ $category['category_key'] }}')">
                                                                                <i class="ri-arrow-{{ $catCollapsed ? 'right' : 'down' }}-s-line"></i>
                                                                                {{ $category['category_name'] }}
                                                                            </button>
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <span class="badge text-bg-light">{{ count($category['subcategories']) }} subcategoria(s)</span>
                                                                                <button type="button" class="btn btn-sm btn-outline-danger icon-btn"
                                                                                    wire:click="removeCategoryGroup('{{ $category['category_key'] }}')"
                                                                                    title="Remover categoria">
                                                                                    <i class="ri-delete-bin-line"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        @if (!$catCollapsed)
                                                                            <div class="card-body py-2">
                                                                                @foreach($category['subcategories'] as $subcategory)
                                                                                    @php
                                                                                        $subCollapsed = $collapsedSubcategories[$subcategory['subcategory_key']] ?? false;
                                                                                    @endphp
                                                                                    <div class="card subcat-card mb-2">
                                                                                        <div class="card-header d-flex justify-content-between align-items-center py-2 gap-2">
                                                                                            <button type="button" class="btn btn-link text-decoration-none p-0 group-head-btn"
                                                                                                wire:click="toggleSubcategoryGroup('{{ $subcategory['subcategory_key'] }}')">
                                                                                                <i class="ri-arrow-{{ $subCollapsed ? 'right' : 'down' }}-s-line"></i>
                                                                                                {{ $subcategory['subcategory_name'] }}
                                                                                            </button>
                                                                                            <div class="d-flex align-items-center gap-2">
                                                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                                                    wire:click="removeSubcategoryGroup('{{ $subcategory['subcategory_key'] }}')"
                                                                                                    title="Remover subcategoria">
                                                                                                    Excluir
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                        @if (!$subCollapsed)
                                                                                            <div class="card-body pt-2">
                                                                                                @foreach($subcategory['origins'] as $originGroup)
                                                                                                    @php
                                                                                                        $originClass = match ($originGroup['origin']) {
                                                                                                            'LEVANTAMENTO' => 'origin-levantamento',
                                                                                                            'AMBOS' => 'origin-ambos',
                                                                                                            default => 'origin-projeto',
                                                                                                        };
                                                                                                    @endphp
                                                                                                    <div class="origin-card mb-2">
                                                                                                        <div class="origin-head {{ $originClass }}">
                                                                                                            {{ $originGroup['origin'] }}
                                                                                                        </div>
                                                                                                        <div class="table-responsive">
                                                                                                            <table class="table table-sm align-middle mb-0">
                                                                                                                <thead>
                                                                                                                    <tr>
                                                                                                                        <th style="width: 150px;">Origem</th>
                                                                                                                        <th>Ação</th>
                                                                                                                        <th style="width: 90px;">Qtd.</th>
                                                                                                                        <th>Conforme</th>
                                                                                                                        <th style="min-width: 420px;">Observação</th>
                                                                                                                        <th style="width:100px;"></th>
                                                                                                                    </tr>
                                                                                                                </thead>
                                                                                                                <tbody>
                                                                                                                    @foreach($originGroup['rows'] as $row)
                                                                                                                        @php $idx = $row['index']; @endphp
                                                                                                                        <tr>
                                                                                                                            <td>
                                                                                                                                <select class="form-select form-select-sm" wire:model="findingRows.{{ $idx }}.origin">
                                                                                                                                    <option value="LEVANTAMENTO">Levantamento</option>
                                                                                                                                    <option value="PROJETO">Projeto</option>
                                                                                                                                    <option value="AMBOS">Ambos</option>
                                                                                                                                </select>
                                                                                                                            </td>
                                                                                                                            <td>
                                                                                                                                @if($row['item_name'])
                                                                                                                                    <span class="badge text-bg-light">{{ $row['action_type'] ?? 'FALTA' }}</span>
                                                                                                                                    {{ $row['item_name'] }}
                                                                                                                                @else
                                                                                                                                    ---
                                                                                                                                @endif
                                                                                                                            </td>
                                                                                                                            <td>
                                                                                                                                <input type="number" min="1" class="form-control form-control-sm"
                                                                                                                                    wire:model.defer="findingRows.{{ $idx }}.quantity"
                                                                                                                                    @disabled(!$row['item_name'])>
                                                                                                                            </td>
                                                                                                                            <td>
                                                                                                                                <div class="form-check">
                                                                                                                                    <input class="form-check-input" type="checkbox"
                                                                                                                                        wire:model.defer="findingRows.{{ $idx }}.is_conform"
                                                                                                                                        id="row-conform-{{ $idx }}">
                                                                                                                                    <label class="form-check-label small" for="row-conform-{{ $idx }}">
                                                                                                                                        Em conformidade
                                                                                                                                    </label>
                                                                                                                                </div>
                                                                                                                            </td>
                                                                                                                            <td>
                                                                                                                                <textarea class="form-control form-control-sm"
                                                                                                                                    rows="2"
                                                                                                                                    wire:model.defer="findingRows.{{ $idx }}.note">
                                                                                                                                </textarea>
                                                                                                                            </td>
                                                                                                                            <td>
                                                                                                                                <button type="button" class="btn btn-sm btn-outline-danger icon-btn"
                                                                                                                                    wire:click="removeFindingRow({{ $idx }})"
                                                                                                                                    title="Excluir item">
                                                                                                                                    <i class="ri-delete-bin-line"></i>
                                                                                                                                </button>
                                                                                                                            </td>
                                                                                                                        </tr>
                                                                                                                    @endforeach
                                                                                                                </tbody>
                                                                                                            </table>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="alert alert-light border">Nenhum apontamento adicionado.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-center py-5">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
                                <div class="small text-muted">Carregando dados da análise...</div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex flex-column align-items-start" style="min-width: 280px;">
                        <label class="form-label fw-semibold mb-1">Precisa liberar no SAP?</label>
                        <select class="form-select form-select-sm @error('requiresSapRelease') is-invalid @enderror"
                            wire:model.defer="requiresSapRelease">
                            <option value="">Selecione...</option>
                            <option value="SIM">Sim</option>
                            <option value="NAO">Não</option>
                        </select>
                        @error('requiresSapRelease')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-info" wire:click="saveDraftManually">Salvar rascunho</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Fechar
                        </button>
                        <button class="btn btn-success" wire:click="approve">Aprovar sem ressalvas</button>
                        <button class="btn btn-primary" wire:click="approveWithRemarks">Aprovar com ressalvas</button>
                        <button class="btn btn-danger" wire:click="reject">Reprovar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
