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

        .workreports-page {
            --wr-bg: #f6f7fb;
            --wr-surface: #ffffff;
            --wr-ink: #1f2933;
            --wr-muted: #6b7280;
            --wr-accent: #0f766e;
            --wr-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--wr-bg);
            padding: 1.5rem 0;
        }

        .workreports-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .workreports-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .workreports-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--wr-surface);
            border: 1px solid var(--wr-border);
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
            color: var(--wr-muted);
        }

        .filters-grid .chip-filters {
            gap: 0.5rem;
        }

        .summary-bar {
            background: var(--wr-surface);
            border: 1px solid var(--wr-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            font-size: 0.92rem;
            color: var(--wr-muted);
        }

        .summary-bar .summary-item strong {
            color: var(--wr-ink);
        }

        .table-card {
            background: var(--wr-surface);
            border: 1px solid var(--wr-border);
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
            .workreports-header {
                padding: 1.25rem;
            }
        }
    </style>
@endpush

<div class="workreports-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="workreports-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>OBRAS INFORMADAS</h2>
                <div class="meta">Relatorio consolidado com filtros e exportacao.</div>
            </div>
            <div class="text-lg-end">
                <button class="btn btn-success" wire:click="exportReport" wire:loading.attr="disabled"
                    wire:target="exportReport">
                    <span wire:loading.remove wire:target="exportReport">
                        <i class="ri-file-excel-2-line me-2"></i>Exportar
                    </span>
                    <span wire:loading wire:target="exportReport">
                        Gerando...
                    </span>
                </button>
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
                            <h6>Datas</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="dateBy"
                                            id="dateBySelect">
                                            <option value="first_informed">Primeiro informe</option>
                                            <option value="informed_at">Informado em</option>
                                            <option value="created_at">Criado em</option>
                                        </select>
                                        <label for="dateBySelect">Buscar por</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-floating w-100">
                                        <input type="date" id="date_in" class="form-control border border-secondary"
                                            wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="Data Inicial">
                                        <label for="date_in">Data inicial</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-floating w-100">
                                        <input type="date" id="date_out" class="form-control border border-secondary"
                                            wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="Data Final">
                                        <label for="date_out">Data final</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-outline-danger w-100" wire:click.prevent="cleanAll()"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-title="Limpar busca e datas">Limpar busca e datas</button>
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
                    @if (!$lists->count())
                    @elseif ($lists->count())
                        {{ $lists->links() }}
                    @endif
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

        <div class="table-card">
            @if (!$lists->count())
                <div class="card-body">
                    <h4 class="text-center text-muted">NENHUMA ATIVIDADE ENCONTRADA</h4>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-condensed table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                <th class="text-center" scope="col">Note</th>
                                <th class="text-center" scope="col">Ordens</th>
                                <th class="text-center" scope="col">Empreiteira</th>
                                <th class="text-center" scope="col">Rubrica</th>
                                <th class="text-center" scope="col">Files</th>
                                <th class="text-center" scope="col">Equipamentos</th>
                                <th class="text-center" scope="col">Alteracao</th>
                                <th class="text-center" scope="col">Equipe WPA</th>
                                <th class="text-center" scope="col">Responsavel</th>
                                <th class="text-center" scope="col">Conclusao Informada</th>
                                <th class="text-center" scope="col">Entregue Em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $list }})"
                                    wire:key="{{ $list->id }}">
                                    <td class="text-center fw-bold align-middle">{{ $list->Note->note }}</td>
                                    <td class="text-center align-middle">
                                        @if ($list->Orders->count())
                                            @foreach ($list->Orders as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $list->Company->name }}</td>
                                    <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                                    <td class="text-center align-middle">
                                        <x-files.select-download-list :files='$list->Note->Files' />
                                    </td>
                                    <td class="text-center align-middle">
                                        {!! $list->Equipment->count() ? "<span class='badge text-bg-dark'>" . $list->Equipment->count() . '</span>' : '' !!}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $list->changes ? 'SIM' : 'NAO' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $list->team ? $list->team : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $list->responsible ? $list->responsible : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $list->date ? date('d/m/Y', strToTime($list->date)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $list->created_at ? date('d/m/Y', strToTime($list->created_at)) : 'Desconhecido' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

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
