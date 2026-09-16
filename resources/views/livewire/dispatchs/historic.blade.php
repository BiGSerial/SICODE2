@php
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp

<div class="dispatch-history-page">
    <x-show-loading />

    <style>
        .dispatch-history-page {
            --history-bg: #f4f7fb;
            --history-surface: #ffffff;
            --history-border: #dbe4ef;
            --history-ink: #1f2937;
            --history-muted: #64748b;
            background: var(--history-bg);
            padding: 1.5rem 0;
        }

        .dispatch-history-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
            margin-bottom: 1rem;
        }

        .dispatch-history-header h2 {
            font-weight: 700;
            letter-spacing: 0;
            margin: 0;
        }

        .dispatch-history-header .meta {
            color: rgba(248, 250, 252, 0.78);
            font-size: 0.9rem;
        }

        .dispatch-history-header .count {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1;
        }

        .dispatch-history-panel {
            background: var(--history-surface);
            border: 1px solid var(--history-border);
            border-radius: 0.75rem;
            padding: 1rem;
            height: 100%;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .dispatch-history-panel h6 {
            color: var(--history-muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .dispatch-history-table {
            background: var(--history-surface);
            border: 1px solid var(--history-border);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .dispatch-history-table .table {
            margin-bottom: 0;
        }

        .dispatch-history-table thead th {
            font-size: 0.76rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dispatch-history-table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .dispatch-history-note {
            color: var(--history-muted);
            font-size: 0.8rem;
        }
    </style>

    <div class="container-fluid">
        <div class="dispatch-history-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="meta text-uppercase">Histórico de despachos</div>
                <h2>{{ mb_strtoupper($service->service) }}</h2>
                <div class="meta mt-1">Notas e OVs despachadas por usuário</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Registros encontrados</div>
                <div class="count">{{ $lists->total() }}</div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-4">
                <div class="dispatch-history-panel">
                    <h6>Pesquisa</h6>
                    <div class="row g-2">
                        <div class="col-12 col-sm-4">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="perPage" id="dispatchHistoryPerPage">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                </select>
                                <label for="dispatchHistoryPerPage">Por página</label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-8">
                            <div class="form-floating">
                                <input wire:model.bounce.700ms="search" type="text" class="form-control border border-secondary"
                                    id="dispatchHistorySearch" placeholder="Buscar nota, material ou rubrica">
                                <label for="dispatchHistorySearch">Nota, material ou rubrica</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="dispatch-history-panel">
                    <h6>Período de despacho</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="date_prod_s" id="dispatchHistoryPeriod">
                                    <option value="">Todos</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->mes_ano }}">
                                            {{ $meses[date('n', strtotime($period->mes_ano))] }} {{ date('Y', strtotime($period->mes_ano)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="dispatchHistoryPeriod">Mês</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <input id="dispatchHistoryFrom" type="date" class="form-control border border-secondary" wire:model="date_from">
                                <label for="dispatchHistoryFrom">Data inicial</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <input id="dispatchHistoryTo" type="date" class="form-control border border-secondary" wire:model="date_to">
                                <label for="dispatchHistoryTo">Data final</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="dispatch-history-panel">
                    <h6>Usuários</h6>
                    <div class="row g-2">
                        @can('superadm')
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input wire:model.bounce.700ms="dispatcher_search" type="text" class="form-control border border-secondary"
                                        id="dispatchHistoryDispatcherSearch" placeholder="Buscar despachante">
                                    <label for="dispatchHistoryDispatcherSearch">Buscar despachante</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="dispatcher_s" id="dispatchHistoryDispatcher">
                                        <option value="">Meus despachos</option>
                                        @foreach ($dispatcher_l as $dispatcher)
                                            <option value="{{ $dispatcher->id }}">
                                                {{ $dispatcher->name }}{{ $dispatcher->trashed() ? ' (inativo)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="dispatchHistoryDispatcher">Despachante</label>
                                </div>
                            </div>
                        @endcan
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input wire:model.bounce.700ms="assigned_search" type="text" class="form-control border border-secondary"
                                    id="dispatchHistoryAssignedSearch" placeholder="Buscar atribuído">
                                <label for="dispatchHistoryAssignedSearch">Buscar atribuído</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="assigned_s" id="dispatchHistoryAssigned">
                                    <option value="">Todos</option>
                                    @foreach ($assigned_l as $assigned)
                                        <option value="{{ $assigned->id }}">
                                            {{ $assigned->name }}{{ $assigned->trashed() ? ' (inativo)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="dispatchHistoryAssigned">Atribuído</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-secondary w-100" type="button" wire:click="clearFilters">
                                <i class="ri-filter-off-line me-1"></i>Limpar filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($lists->count())
            <div class="bg-white border rounded-3 px-3 py-2 mb-3 d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>{{ $lists->links() }}</div>
                <div class="dispatch-history-note">
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> até <strong>{{ $lists->lastItem() }}</strong>
                    de <strong>{{ $lists->total() }}</strong> registros.
                </div>
            </div>
        @endif

        <div class="dispatch-history-table">
            @if (!$lists->count())
                <div class="py-5 text-center">
                    <i class="ri-history-line display-5 text-secondary"></i>
                    <h4 class="mt-3 mb-1">Nenhum despacho encontrado</h4>
                    <p class="text-muted mb-0">Não existem despachos de {{ mb_strtoupper($service->service) }} para os filtros informados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th>Nota/OV</th>
                                <th>Serviço</th>
                                <th>Empresa</th>
                                <th>Atribuído</th>
                                <th>Despachado em</th>
                                <th>Atribuído em</th>
                                <th>Concluído em</th>
                                <th>Tempo até conclusão</th>
                                <th>Tempo parado</th>
                                <th>Situação</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $item)
                                @php
                                    $status = $item->status === null ? '' : strtr(Notestatus::status($item->status)->status ?? '', [
                                        'Nao' => 'Não',
                                        'Atribuido' => 'Atribuído',
                                    ]);
                                    $tempoConclusao = ($item->dispatch_at && $item->completed_at)
                                        ? $item->dispatch_at->diffForHumans($item->completed_at, true, false, 2)
                                        : '';
                                    $tempoParado = $item->stopped
                                        ? Carbon::now()->startOfDay()->addSeconds((int) $item->stopped)->diffForHumans(Carbon::now()->startOfDay(), true, false, 2)
                                        : '';
                                @endphp
                                <tr>
                                    <td class="fw-bold">
                                        <div class="d-flex align-items-center gap-1">
                                            <span>{{ $item->Note?->note ?? '---' }}</span>
                                            @if ($item->d5)
                                                <span class="badge text-bg-primary">RI</span>
                                            @endif
                                        </div>
                                        <div class="dispatch-history-note">{{ $item->Note?->material }}</div>
                                    </td>
                                    <td>{{ $service->service }}</td>
                                    <td>{{ $item->Company?->name ?? '---' }}</td>
                                    <td>{{ $item->User?->name ?? '---' }}</td>
                                    <td>{{ $item->dispatch_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td>{{ $item->att_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td>{{ $item->completed_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td>{{ $tempoConclusao ?: '---' }}</td>
                                    <td>{{ $tempoParado ?: '---' }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $item->completed ? 'success' : 'warning' }}">
                                            {{ $status ?: ($item->completed ? 'Concluído' : 'Em andamento') }}
                                        </span>
                                        @if ($item->transferred)
                                            <span class="badge text-bg-info">Transferido</span>
                                        @endif
                                        @if ($item->rejected)
                                            <span class="badge text-bg-danger">Rejeitado</span>
                                        @endif
                                    </td>
                                    <td class="text-wrap" style="min-width: 280px;">
                                        {{ $item->Analise?->conclusion ?: '---' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
