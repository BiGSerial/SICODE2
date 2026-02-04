@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        .item {
            animation: slideIn 0.5s forwards;
            opacity: 0;
        }

        .item.hidden {
            animation: slideOut 0.5s forwards;
        }

        .detail-item {
            opacity: 0;
            animation: growDown 0.5s forwards;
            transform-origin: top;
        }

        @keyframes growDown {
            from {
                transform: scaleY(0);
            }

            to {
                transform: scaleY(1);
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateX(100%);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .blink {
            animation: blink 2s infinite;
        }

        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-accent: #0f766e;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--oe-bg);
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

        .oexterno-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .oexterno-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--oe-surface);
            border: 1px solid var(--oe-border);
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
            color: var(--oe-muted);
        }

        .filters-grid .chip-filters {
            gap: 0.5rem;
        }

        .summary-bar {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            font-size: 0.92rem;
            color: var(--oe-muted);
        }

        .summary-bar .summary-item strong {
            color: var(--oe-ink);
        }

        .table-card {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
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

        @media (max-width: 991px) {
            .oexterno-header {
                padding: 1.25rem;
            }
        }
    </style>
@endpush

<div class="oexterno-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>INFORMES REJEITADOS</h2>
                <div class="meta">Relatorio de informes rejeitados</div>
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
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="250">250</option>
                                            <option value="500">500</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.bounce.2s="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar">
                                        <label for="search">Buscar</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#buscar_multi">
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
                                    <div class="form-floating">
                                        <input type="date" id="date_in" class="form-control border border-secondary"
                                            wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="Data Inicial">
                                        <label for="date_in">Data inicial</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="date" id="date_out" class="form-control border border-secondary"
                                            wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="Data Final">
                                        <label for="date_out">Data final</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-danger btn-sm w-100" wire:click.prevent="cleanAll()"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-title="Limpar Busca por Datas">
                                        <i class="ri-find-replace-line fs-5"></i>
                                        Limpar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5">
                        <div class="filter-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Filtros adicionais</h6>
                                @livewire('components.filter.remove-all', ['group_filter' => 'reports_worklist'], key('removeAll'))
                            </div>
                            <div class="d-flex flex-wrap chip-filters">
                                @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\\Models\\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'reports_worklist', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
                                @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\\Models\\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'reports_worklist', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                                @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\\Models\\Edp_depc\\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'reports_worklist', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                                @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\\Models\\Edp_depc\\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'reports_worklist', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                                @livewire('components.filter.filter', ['myKey' => 'category', 'sendFilter' => '', 'model' => 'App\\Models\\ReturnWork', 'column' => 'category', 'filter' => 'Motivo', 'group_filter' => 'reports_worklist', 'values' => 'category', 'direction' => 'ASC', 'query' => ''], key('category'))
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
                    @if ($lists->count())
                        {{ $lists->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        @if ($lists->count())
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            @if (!$lists->count())
                <div class="card-body">
                    <h4 class="text-center text-muted">NENHUM INFORME REJEITADO</h4>
                </div>
            @else
                <div class="card-header fw-bold text-bg-secondary">
                    <h4 class="mb-0">INFORMES REJEITADOS</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr class="table-dark">
                                <th class="text-center align-middle" scope="col">NOTA/OV</th>
                                <th class="text-center align-middle" scope="col">STATUS</th>
                                <th class="text-center align-middle" scope="col">CENTRO TRAB</th>
                                <th class="text-center align-middle" scope="col">ORDEM</th>
                                <th class="text-center align-middle" scope="col">EMPREITEIRA</th>
                                <th class="text-center align-middle" scope="col">RUBRICA</th>
                                <th class="text-center align-middle" scope="col">MUNICIPIO</th>
                                <th class="text-center align-middle" scope="col">MOTIVO</th>
                                <th class="text-center align-middle" scope="col">DEVOLUCOES</th>
                                <th class="text-center align-middle" scope="col">DEVOLVIDO POR</th>
                                <th class="text-center align-middle" scope="col">DATA DEVOLUCAO</th>
                                <th class="text-center align-middle" scope="col">TEMPO</th>
                                <th class="text-center align-middle" scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr wire:key='ret-{{ $list->id }}'>
                                    <td class="text-center align-middle fw-bold">{{ $list->Note->note }}</td>
                                    <td class="text-center align-middle fw-bold text-primary">{{ $list->Note->nstats }}</td>
                                    <td class="text-center align-middle fw-bold text-primary">{{ $list->Note->centerjob }}</td>
                                    <td class="text-center align-middle">
                                        @if ($list->Orders->count())
                                            @foreach ($list->Orders as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $list->Company->name }}</td>
                                    <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                                    <td class="text-center align-middle">{{ $list->Note->lexp }}</td>
                                    <td class="text-center align-middle text-danger fw-bold"
                                        wire:click="$emitTo('components.workform.view-reason-return', 'workReturnViews', {{ $list }})"
                                        style="cursor: pointer;">
                                        {{ $list->Returnwork->last()->category }}</td>
                                    <td class="text-center align-middle text-danger fw-bold">
                                        @if ($list->Returnwork->count())
                                            <span class="badge text-bg-dark">{{ $list->Returnwork->count() }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $list->Returnwork->last()->User->name }}</td>
                                    <td class="text-center align-middle">
                                        {{ date('d/m/Y H:i:s', strToTime($list->Returnwork->last()->created_at)) }}</td>
                                    <td class="text-center align-middle text-primary fw-bold">
                                        {{ Carbon::parse($list->Returnwork->last()->created_at)->diffForHumans(null, true) }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <i class="ri-eye-line align-middle text-success fs-4" style="cursor: pointer;"
                                            wire:click="$emitTo('partner.show.show-work-form', 'show_form', {{ $list }})"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($lists->count())
            <div class="summary-bar mt-3">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="summary-item">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content edp-bg-stategrey-50">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        Buscar Multi-Notas
                    </div>
                    <div>
                        <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                            wire:model.defer="advanceSearch"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- LivewireComponent --}}
        @livewire('partner.show.show-work-form', key('FormModdalShow'))
        @livewire('components.workform.view-reason-return', key('WorkReturnsReason'))

        {{-- Scripts --}}
        <script>
            document.addEventListener('livewire:load', function() {
                const dateIn = document.getElementById('date_in');
                const dateOut = document.getElementById('date_out');

                dateIn.addEventListener('change', function() {
                    dateOut.min = dateIn.value;
                });

                if (dateIn.value) {
                    dateOut.min = dateIn.value;
                }

                dateIn.addEventListener('keydown', function(e) {
                    e.preventDefault();
                });

                dateOut.addEventListener('keydown', function(e) {
                    e.preventDefault();
                });
            });
        </script>
    </div>
</div>
