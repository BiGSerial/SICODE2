
<div class="d5-page">
    @php
        $filters = [
            [
                'key' => 'company',
                'label' => 'Empreiteira',
                'type' => 'multi',
                'provider' => [
                    'type' => 'eloquent',
                    'model' => \App\Models\Company::class,
                    'value' => 'id',
                    'label' => 'name',
                    'distinct' => true,
                    'orderBy' => ['name' => 'asc'],
                    'limit' => 300,
                ],
            ],
            [
                'key' => 'type',
                'label' => 'Tipo',
                'type' => 'single',
                'provider' => [
                    'type' => 'static',
                    'options' => [['value' => 2, 'label' => 'OV'], ['value' => 1, 'label' => 'NOTA']],
                ],
            ],
            [
                'key' => 'city',
                'label' => 'Municipio',
                'type' => 'multi',
                'provider' => [
                    'type' => 'eloquent',
                    'model' => \App\Models\City::class,
                    'value' => 'rdMunicipio',
                    'label' => 'cidade',
                    'distinct' => true,
                    'orderBy' => ['cidade' => 'asc'],
                    'limit' => 300,
                ],
            ],
            [
                'key' => 'rubrica',
                'label' => 'Rubrica',
                'type' => 'multi',
                'provider' => [
                    'type' => 'eloquent',
                    'model' => \App\Models\Note::class,
                    'value' => 'rubrica',
                    'label' => 'rubrica',
                    'distinct' => true,
                    'orderBy' => ['rubrica' => 'asc'],
                    'limit' => 300,
                ],
            ],
            [
                'key' => 'desired_between',
                'label' => 'Desejada (de/ate)',
                'type' => 'daterange',
                'include_nulls' => false,
                'treat_zero_date_as_null' => false,
            ],
        ];
    @endphp

    {{-- Loading --}}
    <x-show-loading />

    <style>
        .d5-page {
            --d5-bg: #f6f7fb;
            --d5-surface: #ffffff;
            --d5-ink: #1f2933;
            --d5-muted: #6b7280;
            --d5-accent: #0f766e;
            --d5-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--d5-bg);
            padding: 1.5rem 0;
        }

        .d5-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .d5-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .d5-header .meta {
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

        .filters-grid .btn-group .btn {
            min-width: 88px;
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

        @media (max-width: 991px) {
            .d5-header {
                padding: 1.25rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="d5-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>CONSULTA GERAL D5</h2>
                <div class="meta">Acompanhamento completo das notas D5</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Filtros rapidos e busca em massa</div>
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
                                            <option value="200">200</option>
                                            <option value="500">500</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.debounce.600ms="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar">
                                        <label for="search">Buscar D5, nota, empreiteira</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#buscarMultiModal">
                                            <i class="ri-checkbox-multiple-blank-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Use busca multipla para D5 ou numero da nota.</small>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 col-xl-4">
                        <div class="filter-card">
                            <h6>Situacao</h6>
                            <small class="text-muted d-block mb-2">Selecione um status ou veja todos</small>
                            <div class="btn-group w-100 flex-wrap" role="group" aria-label="Status">
                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="" id="statusAll">
                                <label class="btn btn-outline-secondary" for="statusAll">Todas</label>

                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="aguardando_fornecedor" id="statusFornecedor">
                                <label class="btn btn-outline-secondary" for="statusFornecedor">Fornecedor</label>

                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="aguardando_fiscalizacao" id="statusFiscalizacao">
                                <label class="btn btn-outline-secondary" for="statusFiscalizacao">Fiscalizacao</label>

                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="aguardando_pagamento" id="statusPagamento">
                                <label class="btn btn-outline-secondary" for="statusPagamento">Pagamento</label>

                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="finalizado" id="statusFinalizado">
                                <label class="btn btn-outline-secondary" for="statusFinalizado">Finalizado</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="filter-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Filtros adicionais</h6>
                            </div>
                            @livewire('components.filters.bar', ['config' => $filters, 'group' => 'payments', 'manualApply' => true], key('filters-bar'))
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- END SearchBar and Filters --}}

        <div class="summary-bar mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    @if ($lists?->count())
                        {{ $lists?->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="d-flex flex-column flex-lg-row justify-content-lg-end align-items-lg-center gap-2">
                        <button class="btn btn-success btn-sm" wire:click="exportToExcel">
                            <i class="ri-file-excel-2-line me-1"></i>Exportar
                        </button>
                        <div class="summary-item">
                            Exibindo <strong>{{ $lists->firstItem() ?? 0 }}</strong> ate
                            <strong>{{ $lists->lastItem() ?? 0 }}</strong> de
                            <strong>{{ $lists->total() ?? 0 }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            @if (!$lists || !$lists->count())
                <div class="card-body">
                    <h4 class="text-center text-muted">SEM DADOS PARA EXIBIR</h4>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle">
                        <thead class="table-dark">
                            <tr class="align-middle text-center">
                                <th style="width:15px;"> <input class="form-check-input" type="checkbox" wire:model="selectall"
                                        wire:click="setSelectAll" @checked($this->checkAllSelect($lists))></th>
                                <th>Nota D5</th>
                                <th>Nota</th>
                                <th>Rubrica</th>
                                <th>Empreiteira</th>
                                <th>Motivo</th>
                                <th>Cod</th>
                                <th>Data Despacho</th>
                                <th>Em Atividade</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lists as $list)
                                @php
                                    $activityStart = null;

                                    if ($list->is_archived) {
                                        $activityStart = null;
                                    } elseif ($list->is_supervisioned) {
                                        $activityStart = $list->supervisioned_at;
                                    } elseif ($list->is_completed) {
                                        $activityStart = $list->completed_at;
                                    } else {
                                        $activityStart = $list->dispatch_at;
                                    }

                                    $daysOverdue = $activityStart?->diffInDays();
                                    $badgeClass = 'bg-success';
                                    $badgeText = 'Dentro do prazo';

                                    $position = [
                                        'position' => 'Aguardando Fornecedor',
                                        'color' => 'text-bg-danger',
                                    ];

                                    if ($list->is_archived) {
                                        $position = [
                                            'position' => 'Finalizado',
                                            'color' => 'text-bg-success',
                                        ];
                                    } elseif ($list->is_supervisioned) {
                                        $position = [
                                            'position' => 'Aguardando Pagamento',
                                            'color' => 'text-bg-primary',
                                        ];
                                    } elseif ($list->is_completed) {
                                        $position = [
                                            'position' => 'Aguardando Fiscalizacao',
                                            'color' => 'text-bg-warning',
                                        ];
                                    }

                                    if ($daysOverdue > 3 && $daysOverdue <= 5) {
                                        $badgeClass = 'bg-warning';
                                        $badgeText = 'Atencao';
                                    } elseif ($daysOverdue > 5) {
                                        $badgeClass = 'bg-danger';
                                        $badgeText = 'Atrasado';
                                    }
                                @endphp
                                <tr class="text-center {{ $list->is_supervisioned ? 'table-success' : '' }}">
                                    <td><input class="form-check-input border border-1 border-primary " type="checkbox"
                                            value="{{ $list->id }}" wire:model.defer="selected">
                                    </td>
                                    <td>
                                        {{ $list->note_d5 }}
                                        @if ($list->isPassive)
                                            <span class="badge text-bg-info ms-2">Passiva</span>
                                        @endif
                                    </td>
                                    <td>{{ $list->note->note }}</td>
                                    <td>{{ $list->note->rubrica }}</td>
                                    <td class="fw-bold">{{ $list->company?->name }}</td>
                                    <td>{{ $list->reason }}</td>
                                    <td>{{ $list->codify }}</td>
                                    <td>{{ $list->dispatch_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if ($activityStart)
                                            <span class="badge {{ $badgeClass }}">
                                                <i class="ri-time-line me-1"></i> {{ $daysOverdue }} dias
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">
                                                <i class="ri-check-line me-1"></i> Finalizado
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $position['color'] }}">
                                            {{ $position['position'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary p-1"
                                            wire:click="$emitTo('components.five-note.view-d5', 'getInfoResponse', {{ $list->id }})">
                                            Visualizar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-5">
                                        <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                                        Nenhum registro encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="summary-bar mt-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    {{ $lists?->links() }}
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() ?? 0 }}</strong> ate
                        <strong>{{ $lists->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $lists->total() ?? 0 }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        {{-- Drawer lateral de detalhes --}}
        @if ($showDetails && $selected)
            <div class="details-drawer details-drawer--modern shadow">
                <!-- Header -->
                <div class="drawer-header">
                    <div class="drawer-title">
                        <div class="drawer-icon">
                            <i class="ri-file-list-3-line"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Nota #{{ $selected->nota }}</h5>
                            <small class="text-muted">Ficha detalhada</small>
                        </div>
                    </div>

                    <button class="btn btn-light btn-sm drawer-close" wire:click="closeDetails" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <!-- Status Strip -->
                <div class="drawer-strip">
                    <span
                        class="badge rounded-pill bg-{{ !$selected->dtConclusaoDesej->isPast() ? 'success' : 'danger' }} me-2">
                        <i
                            class="{{ !$selected->dtConclusaoDesej->isPast() ? 'ri-check-line' : 'ri-error-warning-line' }} me-1"></i>
                        {{ !$selected->dtConclusaoDesej->isPast() ? 'No Prazo' : 'Vencido' }}
                    </span>

                    <div class="chip">
                        <i class="ri-community-line me-1"></i>{{ $selected->cidade }}
                    </div>
                    <div class="chip">
                        <i class="ri-price-tag-3-line me-1"></i>{{ $selected->txtGrpCodificacao }}
                    </div>
                </div>

                <!-- Content (scrollable) -->
                <div class="drawer-content">
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-label"><i class="ri-map-pin-line me-1"></i>Municipio</div>
                            <div class="info-value">{{ $selected->cidade }}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label"><i class="ri-folder-2-line me-1"></i>Grupo</div>
                            <div class="info-value">{{ $selected->txtGrpCodificacao }}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label"><i class="ri-time-line me-1"></i>Abertura</div>
                            <div class="info-value">{{ $selected->dtAberturaNota?->format('d/m/Y') }}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label"><i class="ri-flag-line me-1"></i>Desejada</div>
                            <div class="info-value">{{ $selected->dtConclusaoDesej?->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="desc-block">
                        <div class="desc-title">
                            <i class="ri-information-line me-2"></i>Descricao
                        </div>
                        <p class="mb-0 text-secondary">
                            {{ $selected->comments->last()?->message }}
                        </p>
                    </div>

                    {{-- Timeline opcional (so exibe se tiver datas) --}}
                    @php
                        $timeline = [
                            [
                                'icon' => 'ri-file-add-line',
                                'label' => 'Abertura',
                                'date' => $selected->dtAberturaNota?->format('d/m/Y'),
                            ],
                            [
                                'icon' => 'ri-flag-2-line',
                                'label' => 'Desejada',
                                'date' => $selected->dtConclusaoDesej?->format('d/m/Y'),
                            ],
                        ];
                    @endphp
                    <div class="divider"></div>
                    <div class="timeline">
                        @foreach ($timeline as $t)
                            @if (!empty($t['date']))
                                <div class="timeline-item">
                                    <div class="timeline-dot"><i class="{{ $t['icon'] }}"></i></div>
                                    <div class="timeline-content">
                                        <div class="timeline-label">{{ $t['label'] }}</div>
                                        <div class="timeline-date">{{ $t['date'] }}</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Footer -->
                <div class="drawer-footer">
                    <button class="btn btn-outline-secondary" wire:click="closeDetails">
                        <i class="ri-arrow-go-back-line me-1"></i> Fechar
                    </button>
                    <button class="btn btn-primary" wire:click="goTo({{ $selected->nota }})">
                        <i class="ri-external-link-line me-1"></i> Abrir Detalhes
                    </button>
                </div>
            </div>
            <div class="details-drawer-backdrop" wire:click="closeDetails"></div>
        @endif

        {{-- Modal: Busca Multipla --}}
        <div wire:ignore.self class="modal fade" id="buscarMultiModal" tabindex="-1" aria-labelledby="buscarMultiLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="buscarMultiLabel">
                            <i class="ri-search-2-line me-2"></i>
                            Busca multipla
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating">
                            <textarea class="form-control" id="advanceSearch" style="height: 200px;"
                                placeholder="Cole aqui varios D5 ou notas (virgula ou quebra de linha)"
                                wire:model.defer="advanceSearch"></textarea>
                            <label for="advanceSearch">Numeros D5 ou Nota</label>
                        </div>
                        <div class="form-text">
                            Separe por virgula <strong>,</strong> ou por quebra de linha.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">
                            <i class="ri-check-line me-1"></i>Aplicar Filtro
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modals --}}
        @livewire('components.five-note.view-d5', key('five-note'))
        @livewire('components.five-note.manual-create', key('manual-create-five'))
        @livewire('components.five-note.edit-d5', key('edit-five-note'))
    </div>

    {{-- Estilos customizados --}}
    <style>
        .details-drawer {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 400px;
            background: #fff;
            border-left: 1px solid #eee;
            z-index: 1201;
            padding: 2rem 1.5rem 1rem 2rem;
            box-shadow: -2px 0 18px rgba(0, 0, 0, 0.10);
            animation: slideInDrawer .21s cubic-bezier(.6, -0.28, .74, .05);
        }

        /* --- Drawer Moderno --- */
        .details-drawer--modern {
            background: #ffffff;
            border-left: 0;
            width: 460px;
            padding: 0;
            overflow: hidden;
            border-radius: 16px 0 0 16px;
            box-shadow: -8px 0 28px rgba(0, 0, 0, .12);
            backdrop-filter: saturate(1.2) blur(6px);
        }

        @media (max-width: 900px) {
            .details-drawer--modern {
                width: 100vw;
                border-radius: 0;
            }
        }

        /* Header com gradiente e blur */
        .details-drawer--modern .drawer-header {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(135deg, #0d6efd 0%, #4f8cff 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(13, 110, 253, .2);
        }

        .details-drawer--modern .drawer-title {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .details-drawer--modern .drawer-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .15);
            display: grid;
            place-items: center;
            font-size: 1.2rem;
        }

        /* Botao fechar */
        .details-drawer--modern .drawer-close {
            background: rgba(255, 255, 255, .15);
            border: 0;
            color: #fff;
            transition: transform .15s ease, background .15s ease;
        }

        .details-drawer--modern .drawer-close:hover {
            transform: rotate(90deg) scale(1.05);
            background: rgba(255, 255, 255, .25);
        }

        /* Faixa de status + chips */
        .details-drawer--modern .drawer-strip {
            padding: .75rem 1.25rem;
            background: linear-gradient(180deg, rgba(13, 110, 253, .06), rgba(13, 110, 253, 0));
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .details-drawer--modern .chip {
            font-size: .82rem;
            background: #f1f5ff;
            color: #2752d3;
            border: 1px solid #e3ebff;
            padding: .25rem .6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
        }

        /* Conteudo rolavel */
        .details-drawer--modern .drawer-content {
            height: calc(100vh - 176px);
            overflow-y: auto;
            padding: 1.25rem 1.25rem 1rem;
            scrollbar-width: thin;
            scrollbar-color: #b8c9ff transparent;
        }

        .details-drawer--modern .drawer-content::-webkit-scrollbar {
            width: 6px;
        }

        .details-drawer--modern .drawer-content::-webkit-scrollbar-thumb {
            background: #b8c9ff;
            border-radius: 3px;
        }

        /* Grid de infos */
        .details-drawer--modern .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        @media (max-width: 480px) {
            .details-drawer--modern .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .details-drawer--modern .info-card {
            border: 1px solid #eef1f6;
            border-radius: 12px;
            padding: .75rem .9rem;
            background: #fff;
            transition: box-shadow .15s ease, transform .15s ease;
        }

        .details-drawer--modern .info-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        }

        .details-drawer--modern .info-label {
            font-size: .78rem;
            color: #6b7a90;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .details-drawer--modern .info-value {
            font-weight: 600;
            color: #2a2f3a;
            margin-top: .15rem;
        }

        /* Descricao */
        .details-drawer--modern .desc-block .desc-title {
            font-weight: 700;
            color: #334155;
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
        }

        .details-drawer--modern .desc-block p {
            background: #f8fafc;
            border: 1px dashed #e5e7eb;
            border-radius: 12px;
            padding: .75rem .9rem;
        }

        /* Timeline */
        .details-drawer--modern .timeline {
            position: relative;
            margin-top: .5rem;
            padding-left: .75rem;
        }

        .details-drawer--modern .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e6ebff;
        }

        .details-drawer--modern .timeline-item {
            display: flex;
            gap: .75rem;
            position: relative;
            margin-bottom: .75rem;
        }

        .details-drawer--modern .timeline-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #eaf0ff;
            color: #345bff;
            display: grid;
            place-items: center;
            z-index: 1;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e6ebff;
        }

        .details-drawer--modern .timeline-content .timeline-label {
            font-size: .82rem;
            color: #64748b;
            margin-bottom: .1rem;
        }

        .details-drawer--modern .timeline-date {
            font-weight: 600;
            color: #1f2937;
        }

        /* Footer fixo */
        .details-drawer--modern .drawer-footer {
            position: sticky;
            bottom: 0;
            background: linear-gradient(0deg, #ffffff 80%, rgba(255, 255, 255, 0));
            padding: .9rem 1.25rem 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            border-top: 1px solid #eef1f6;
        }

        /* Divider suave */
        .details-drawer--modern .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #eef1f6, transparent);
            margin: .9rem 0 1rem;
        }
    </style>
</div>
