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
            </div>
        </div>

        <div class="card-soft p-3">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Desenhista</th>
                            <th>Empresa</th>
                            <th>Ordens</th>
                            <th>Custo total</th>
                            <th>Custo empresa</th>
                            <th>Custo cliente</th>
                            <th>Proporcionalidade aplicada</th>
                            <th>Status</th>
                            <th>Analista</th>
                            <th>Quando foi enviado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $cycle = collect($row->ProjectReviewCycles)->sortByDesc('round_number')->first();
                                $orders = $cycle?->Orders ?? collect();
                                if ($orders->isEmpty()) {
                                    $orders = $row->ProjectReviewCycles->first(function ($c) {
                                        return $c->Orders->count() > 0;
                                    })?->Orders ?? collect();
                                }
                                $latestRound = (int) ($row->latest_round_number ?? ($cycle?->round_number ?? 1));
                                $status = \App\Custom\Notestatus::status((int) $row->status);
                            @endphp
                            <tr>
                                <td>{{ $row->Note?->note ?? '---' }}</td>
                                <td>{{ $row->User?->name ?? '---' }}</td>
                                <td>{{ $row->Company?->name ?? '---' }}</td>
                                <td class="align-top">
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
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1">{{ number_format((float) $ord->total_cost, 2, ',', '.') }}</div>
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
                                                <div class="small border px-2 py-1">{{ number_format((float) $ord->company_cost, 2, ',', '.') }}</div>
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
                                                <div class="small border px-2 py-1">{{ number_format((float) $ord->client_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @if (is_null($cycle?->proportionality_ok))
                                        ---
                                    @else
                                        {{ $cycle->proportionality_ok ? 'Sim' : 'Não' }}
                                        @if (!is_null($cycle->proportionality_value))
                                            ({{ number_format((float) $cycle->proportionality_value, 2, ',', '.') }}%)
                                        @endif
                                    @endif
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
            <div class="mt-3">{{ $rows->links() }}</div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="historyProjectReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title">Histórico da Análise - {{ $selectedProduction?->Note?->note }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                                ->filter(fn($c) => !is_null($c->decided_at))
                                                ->sortByDesc('round_number');
                                        @endphp
                                        @forelse($laudos as $laudoCycle)
                                            <div class="border rounded p-2 mb-2">
                                                <div class="d-flex justify-content-between flex-wrap gap-2 mb-1">
                                                    <div>
                                                        <strong>Rodada {{ $laudoCycle->round_number }}</strong>
                                                        -
                                                        @switch($laudoCycle->decision)
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
                                                    <div class="text-muted">{{ $laudoCycle->decided_at ? date('d/m/Y H:i', strtotime($laudoCycle->decided_at)) : '---' }}</div>
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

                                            $sumEconomy = 0.0;
                                            $sumIncrease = 0.0;
                                            foreach ($historyByOrder as $entries) {
                                                foreach ($entries as $entry) {
                                                    if (is_null($entry['delta'])) {
                                                        continue;
                                                    }
                                                    if ((float) $entry['delta'] < 0) {
                                                        $sumEconomy += abs((float) $entry['delta']);
                                                    } elseif ((float) $entry['delta'] > 0) {
                                                        $sumIncrease += (float) $entry['delta'];
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
                                        @forelse ($selectedCycle->Messages->sortByDesc('created_at') as $msg)
                                            <div class="border rounded p-2 mb-1 {{ $msg->user_id === auth()->id() ? 'bg-light' : '' }}">
                                                <div class="small text-muted">{{ $msg->User->name ?? 'Usuário' }} - {{ date('d/m/Y H:i', strtotime($msg->created_at)) }}</div>
                                                <div>{{ $msg->message }}</div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">Sem mensagens nesta rodada.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="card card-soft h-100">
                                    <div class="card-header">Estrutura da análise (histórico)</div>
                                    <div class="card-body">
                                        @php
                                            $tree = collect($selectedCycle->Findings)->groupBy(fn($f) => optional(optional($f->Subcategory)->Category)->name ?: 'Sem categoria');
                                        @endphp

                                        @forelse($tree as $catName => $catRows)
                                            @php($catId = 'hist_cat_' . md5($catName))
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
                                                            @php($subId = 'hist_sub_' . md5($catName . '_' . $subName))
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
                                        @empty
                                            <div class="alert alert-light border mb-0">Sem apontamentos registrados nesta rodada.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
