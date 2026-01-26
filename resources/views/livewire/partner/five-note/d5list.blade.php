<div class="d5list-page">
    {{-- Carrega o Loading da pagina --}}
    <x-show-loading />

    <style>
        .d5list-page {
            --d5-bg: #f5f7fb;
            --d5-surface: #ffffff;
            --d5-ink: #1f2933;
            --d5-muted: #6b7280;
            --d5-accent: #0f766e;
            --d5-border: #e5e7eb;
            background: radial-gradient(circle at 15% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 85% 15%, #ecfeff, transparent 35%),
                var(--d5-bg);
            padding: 1.5rem 0;
        }

        .d5list-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .d5list-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .d5list-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--d5-surface);
            border: 1px solid var(--d5-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .filters-grid .filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--d5-muted);
        }

        .summary-bar {
            background: var(--d5-surface);
            border: 1px solid var(--d5-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            font-size: 0.92rem;
            color: var(--d5-muted);
        }

        .summary-bar .summary-item strong {
            color: var(--d5-ink);
        }

        .table-card {
            background: var(--d5-surface);
            border: 1px solid var(--d5-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .table-card .table tbody td {
            font-size: 0.92rem;
        }

        .d5list-page .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .d5list-page .legend-dot.passive {
            background: linear-gradient(135deg, #f97316, #facc15);
            box-shadow: 0 0 0 1px rgba(249, 115, 22, 0.4);
        }

        .d5list-page .badge-passive {
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            font-weight: 600;
            padding: 0.15rem 0.6rem;
            font-size: 0.7rem;
            border: 1px solid #fdba74;
        }

        .d5list-page .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .d5list-page .chip.warn {
            background: #fef3c7;
            color: #92400e;
        }

        .d5list-page .chip.ok {
            background: #dcfce7;
            color: #166534;
        }

        .d5list-page .chip.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .d5list-page .passive-row {
            background: rgba(251, 146, 60, 0.08);
            box-shadow: inset 3px 0 0 #fb923c;
        }

        .d5list-page .passive-row:hover {
            background: rgba(251, 146, 60, 0.15);
        }

        @media (max-width: 991px) {
            .d5list-header {
                padding: 1.25rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="d5list-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>D5 em espera de execucao</h2>
                <div class="meta">Fila de execucao e acompanhamento</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Registros no filtro atual</div>
                <div><strong>{{ $fives->total() }}</strong></div>
            </div>
        </div>

        {{-- START SearchBar and Filters --}}
        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12 col-lg-5 col-xl-4">
                        <div class="filter-card">
                            <h6>Pesquisa</h6>
                            <div class="row g-2">
                                <div class="col-12 col-sm-5">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="perPage"
                                            id="perPageSelect">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.defer="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar por nota, PEP, motivo" wire:keydown.enter="toSearch">
                                        <label for="search">Buscar</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#multiSearchModal"
                                            title="Busca multipla">
                                            <i class="ri-checkbox-multiple-blank-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 col-xl-3">
                        <div class="filter-card">
                            <h6>Periodo</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <input type="month" class="form-control border border-secondary"
                                            wire:model.defer="month" id="filterMonth">
                                        <label for="filterMonth">Mes</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <input type="date" class="form-control border border-secondary"
                                            wire:model.defer="startDate" id="filterStart">
                                        <label for="filterStart">Inicio</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <input type="date" class="form-control border border-secondary"
                                            wire:model.defer="endDate" id="filterEnd">
                                        <label for="filterEnd">Fim</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5">
                        <div class="filter-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Acoes e exibicao</h6>
                                <button type="button" class="btn btn-outline-primary" wire:click="exportExcel">
                                    <i class="ri-download-2-line me-1"></i> Exportar Excel
                                </button>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-7">
                                    <div class="form-floating w-100">
                                        <select id="passiveFilter" class="form-select border border-secondary"
                                            wire:model="passiveFilter">
                                            <option value="current">Metas atuais</option>
                                            <option value="passive">Passivos</option>
                                            <option value="all">Tudo</option>
                                        </select>
                                        <label for="passiveFilter">Mostrar</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-5 d-flex gap-2">
                                    <button class="btn btn-primary flex-grow-1" wire:click="toSearch()">
                                        <i class="ri-search-line me-1"></i> Buscar
                                    </button>
                                    <button class="btn btn-outline-secondary" wire:click="toClean()">
                                        <i class="ri-eraser-line"></i>
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 text-muted small flex-wrap mt-2">
                                        <span class="legend-dot passive"></span>
                                        Passivos destacados
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- END SearchBar and Filters --}}

        <div class="summary-bar mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    @if ($fives->links())
                        {{ $fives->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $fives->firstItem() }}</strong> ate
                        <strong>{{ $fives->lastItem() }}</strong> de
                        <strong>{{ $fives->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista --}}
        <div class="table-card">
            @if ($fives->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-dark">
                            <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                <th class="text-center" style="width:52px;">#</th>
                                <th>Nota D5</th>
                                <th>Note</th>
                                <th>Orders</th>
                                <th>PEP</th>
                                <th>Motivo</th>
                                <th>Codificacao</th>
                                <th class="text-center">Despachado em</th>
                                <th class="text-center">Dias</th>
                                <th class="text-center">Empresa</th>
                                <th class="text-center" style="width:56px;">Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!function_exists('d5list_get_order'))
                                @php
                                    function d5list_get_order($note): ?string
                                    {
                                        return $note->WorkForm?->Orders?->sortBy('ordem')->first()?->ordem;
                                    }
                                @endphp
                            @endif

                            @foreach ($fives as $index => $five)
                                @php
                                    $diffDays = $five->dispatch_at ? now()->diffInDays($five->dispatch_at) : 0;
                                    $chipClass = $diffDays >= 10 ? 'danger' : ($diffDays >= 5 ? 'warn' : 'ok');
                                @endphp
                                <tr wire:key="five-{{ $five->id }}" @class(['passive-row' => $five->isPassive])>
                                    <td class="text-center">
                                        <span class="badge text-bg-light">#{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-semibold">{{ $five->note_d5 }}</span>
                                                @if ($five->isPassive)
                                                    <span class="badge-passive" title="Registro passivo">
                                                        Passivo
                                                    </span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ $five->loc_install }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $five->note->note }}</td>
                                    <td>{{ d5list_get_order($five->note) }}</td>
                                    <td>{{ $five->pep }}</td>
                                    <td>{{ $five->reason }}</td>
                                    <td>{{ $five->codify }}</td>
                                    <td class="text-center">
                                        {{ $five->dispatch_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="chip {{ $chipClass }}"><i class="ri-timer-line"></i>
                                            {{ $diffDays }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $five->company?->name }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-success"
                                            wire:click="$emitTo('partner.five-note.actions.finish-d5', 'getInfoResponse', {{ $five->id }})">
                                            <i class="ri-play-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body">
                    <div class="text-center py-5 text-secondary">
                        <i class="ri-folder-2-line d-block fs-2 mb-2"></i>
                        <div>Sem D5 para execucao.</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="summary-bar mt-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    @if ($fives->links())
                        {{ $fives->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $fives->firstItem() }}</strong> ate
                        <strong>{{ $fives->lastItem() }}</strong> de
                        <strong>{{ $fives->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Busca multipla --}}
        <div wire:ignore.self class="modal fade" id="multiSearchModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" style="border-radius:16px;">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-file-search-line me-1"></i> Busca Multi-notas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-0">
                        <textarea class="form-control border-0" rows="15" wire:model.defer="multiSearch"
                            placeholder="Cole aqui as notas/OV (uma por linha)"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" wire:click.prevent="multiSearch"><i
                                class="ri-search-line me-1"></i> Buscar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- LIVEWIRE COMPONENTS --}}
        @livewire('partner.five-note.actions.finish-d5')
    </div>
</div>
