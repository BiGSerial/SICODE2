<div class="five-list-page d-flex flex-column gap-3">
    {{-- === CSS escopado === --}}
    <style>
        .five-list-page .card-soft {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, .06);
        }

        .five-list-page .card-header-slim {
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .five-list-page .c-badge {
            display: inline-block;
            padding: .25rem .5rem;
            font-size: .75rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
        }

        .five-list-page .toolbar .form-control,
        .five-list-page .toolbar .form-select,
        .five-list-page .toolbar .form-switch .form-check-input {
            border-radius: 12px;
        }

        .five-list-page table thead th {
            background: #0ea5e9;
            color: #fff;
            border: 0;
            font-weight: 600;
            vertical-align: middle;
        }

        .five-list-page table tbody tr {
            transition: background .15s ease;
        }

        .five-list-page table tbody tr:hover:not(.passive-row) {
            background: rgba(14, 165, 233, .08);
        }

        .five-list-page table tbody tr.passive-row {
            background: rgba(251, 146, 60, .08);
            box-shadow: inset 3px 0 0 #fb923c;
        }

        .five-list-page table tbody tr.passive-row:hover {
            background: rgba(251, 146, 60, .15);
        }

        .five-list-page .badge-passive {
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            font-weight: 600;
            padding: .15rem .6rem;
            font-size: .7rem;
            border: 1px solid #fdba74;
        }

        .five-list-page .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .five-list-page .legend-dot.passive {
            background: linear-gradient(135deg, #f97316, #facc15);
            box-shadow: 0 0 0 1px rgba(249, 115, 22, .4);
        }

        .five-list-page .legend-pill {
            border-radius: 999px;
            padding: .1rem .6rem;
            background: rgba(14, 165, 233, .1);
            color: #0f172a;
            font-size: .8rem;
        }

        .five-list-page .status-pill {
            border-radius: 999px;
            padding: .25rem .9rem;
            font-size: .78rem;
            font-weight: 600;
        }
    </style>

    <x-show-loading />

    {{-- === Barra de filtros === --}}
    <div class="card card-soft my-2">
        <div class="card-header card-header-slim">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="m-0 d-flex align-items-center gap-2">
                    <i class="ri-history-line text-primary"></i>
                    Histórico de D5 executadas
                </h5>
            </div>
        </div>
        <div class="card-body toolbar">
            <div class="row g-2">

                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model="perPage">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                        <option value="100">100 por página</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 position-relative">
                    <input class="form-control" placeholder="Buscar por nota, PEP, motivo..." wire:model.defer="search"
                        wire:keydown.enter="toSearch">
                    <button type="button"
                        class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                        data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Busca múltipla">
                        <i class="ri-checkbox-multiple-blank-line"></i>
                    </button>
                </div>

                <div class="col-6 col-md-2">
                    <input type="month" class="form-control" wire:model.defer="month">
                </div>

                <div class="col-6 col-md-2">
                    <input type="date" class="form-control" wire:model.defer="startDate">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" class="form-control" wire:model.defer="endDate">
                </div>

                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" wire:click="toSearch()">
                        <i class="ri-search-line me-1"></i> Buscar
                    </button>
                    <button class="btn btn-outline-secondary" wire:click='toClean()'>
                        <i class="ri-eraser-line"></i>
                    </button>
                </div>

                <div class="col-12 col-md-3 ms-auto">
                    <label for="passiveFilterHistoric" class="form-label small text-muted mb-1">Mostrar</label>
                    <select id="passiveFilterHistoric" class="form-select" wire:model="passiveFilter">
                        <option value="current">Metas atuais</option>
                        <option value="passive">Passivos</option>
                        <option value="all">Tudo</option>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                <div class="d-flex align-items-center gap-2 text-muted small flex-wrap">
                    <span class="legend-dot passive"></span>
                    Passivos destacados
                    <span class="legend-pill">
                        {{ $fives->total() }} registros no filtro atual
                    </span>
                </div>
                <button type="button" class="btn btn-outline-primary" wire:click="exportExcel">
                    <i class="ri-download-2-line me-1"></i> Exportar Excel
                </button>
            </div>
        </div>
    </div>

    {{-- === Lista === --}}
    <div class="flex-grow-1 px-1 w-100">
        @if ($fives->isNotEmpty())
            <div class="card card-soft mt-2">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:52px;"></th>
                                    <th>Nota D5</th>
                                    <th>Note</th>
                                    <th>Orders</th>
                                    <th>PEP</th>
                                    <th>Motivo</th>
                                    <th>Codificação</th>
                                    <th class="text-center">Despachado em</th>
                                    <th class="text-center">Concluído em</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:56px;">Ação</th>
                                </tr>
                            </thead>
                            <tbody>

                                @if (!function_exists('historic_get_order'))
                                    @php
                                        function historic_get_order($note): ?string
                                        {
                                            return $note->Orders?->sortBy('ordem')->first()?->ordem;
                                        }
                                    @endphp
                                @endif

                                @foreach ($fives as $index => $five)
                                    @php
                                        $status = '';
                                        $statusClass = 'bg-secondary text-white';

                                        if ($five->is_payed) {
                                            if ($five->is_archived) {
                                                $status = 'Finalizada';
                                                $statusClass = 'bg-success';
                                            } elseif ($five->is_supervisioned) {
                                                $status = 'Aguardando Liberação Pagamento';
                                                $statusClass = 'bg-warning text-dark';
                                            } elseif ($five->is_completed) {
                                                $status = 'Aguardando Fiscalização';
                                                $statusClass = 'bg-info text-dark';
                                            } elseif ($five->visible_partner) {
                                                $status = 'Aguardando Conclusão Parceira';
                                                $statusClass = 'bg-primary';
                                            }
                                        } else {
                                            $status = 'Aguardando Despacho Pagamento';
                                            $statusClass = 'bg-primary';
                                        }
                                    @endphp
                                    <tr wire:key="historic-{{ $five->id }}"
                                        @class(['passive-row' => $five->isPassive])>
                                        <td class="text-center">
                                            <span class="c-badge">#{{ $index + 1 }}</span>
                                        </td>
                                        <td class="cell-tight">
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
                                        <td class="cell-tight">{{ $five->note->note }}</td>
                                        <td class="cell-tight">{{ historic_get_order($five->note) }}</td>
                                        <td class="cell-tight">{{ $five->pep }}</td>
                                        <td class="cell-tight">{{ $five->reason }}</td>
                                        <td class="cell-tight">{{ $five->codify }}</td>
                                        <td class="text-center cell-tight">
                                            {{ $five->dispatch_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-center">
                                            {{ $five->completed_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <span class="status-pill {{ $statusClass }}">{{ $status }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary"
                                                wire:click="$emitTo('components.d5.d5details', 'openD5Details', {{ $five->note_id }})">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach



                            </tbody>
                        </table>
                    </div>

                    {{-- Paginação --}}
                    <div class="d-flex justify-content-between align-items-center px-3 py-3">

                        @if ($fives->links())
                            <small class="text-muted">
                                Exibindo {{ $fives->firstItem() }}–{{ $fives->lastItem() }} de
                                {{ $fives->total() }} registros
                            </small>
                            <nav aria-label="Paginação D5 histórico">

                                {{ $fives->links() }}

                            </nav>
                        @else
                            <small class="text-muted">
                                Exibindo {{ $fives->count() }} de {{ $fives->count() }} registros
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="card card-soft">
                <div class="card-body p-0">
                    <div class="text-center py-5 text-secondary">
                        <i class="ri-folder-2-line d-block fs-2 mb-2"></i>
                        <div>Sem registros no período.</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- === Modal Busca múltipla (textarea 15 linhas) === --}}
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
                    <button class="btn btn-primary" wire:click="multiSearch"><i class="ri-search-line me-1"></i>
                        Buscar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- LIVEWIRE COMPONENTS --}}
    @livewire('components.d5.d5details', key('partner-view-d5-details'))
</div>
