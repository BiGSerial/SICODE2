
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
                'key' => 'passive_mode',
                'label' => 'Meta / Passivo',
                'type' => 'single',
                'provider' => [
                    'type' => 'static',
                    'options' => [
                        ['value' => 'both', 'label' => 'Meta + Passivo'],
                        ['value' => 'meta', 'label' => 'Meta'],
                        ['value' => 'passive', 'label' => 'Passivo'],
                    ],
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
                'key' => 'period_column',
                'label' => 'Período por',
                'type' => 'single',
                'provider' => [
                    'type' => 'static',
                    'options' => [
                        ['value' => 'dispatch', 'label' => 'Despacho'],
                        ['value' => 'completed', 'label' => 'Conclusão'],
                        ['value' => 'both', 'label' => 'Despacho ou Conclusão'],
                    ],
                ],
            ],
            [
                'key' => 'desired_between',
                'label' => 'Período (de/ate)',
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
                            <h6>Etapa</h6>
                            <small class="text-muted d-block mb-2">Selecione uma etapa ou veja todas</small>
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
                                <th>Retorno Empreiteira</th>
                                <th>Tempo</th>
                                <th>Etapa</th>
                                <th>Status</th>
                                <th>Responsável</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lists as $list)
                                @php
                                    $meta = $list->tracking_meta ?? [];
                                    $activity = $meta['activity'] ?? [];
                                    $assignee = $meta['assignee'] ?? [];

                                    $activityStart = null;
                                    if (($activity['key'] ?? null) === 'aguardando_pagamento') {
                                        $activityStart = $list->supervisioned_at;
                                    } elseif (($activity['key'] ?? null) === 'aguardando_fiscalizacao') {
                                        $activityStart = $list->completed_at;
                                    } elseif (($activity['key'] ?? null) === 'aguardando_geracao_d5') {
                                        $activityStart = $list->created_at;
                                    } elseif (($activity['key'] ?? null) === 'aguardando_fornecedor') {
                                        $activityStart = $list->dispatch_at;
                                    }

                                    $daysOverdue = $activityStart?->diffInDays();
                                    $badgeClass = 'bg-success';
                                    $badgeText = 'No prazo';
                                    $phaseLabel = match ($activity['key'] ?? '') {
                                        'aguardando_geracao_d5', 'aguardando_pagamento' => 'Pagamento',
                                        'aguardando_fiscalizacao' => 'Fiscalizacao',
                                        'aguardando_fornecedor' => 'Fornecedor',
                                        'finalizado' => 'Finalizado',
                                        default => '---',
                                    };

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
                                        @if ($list->completed_at)
                                            {{ $list->completed_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="badge text-bg-secondary">Sem retorno</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($activityStart)
                                            <span class="badge {{ $badgeClass }}">
                                                <i class="ri-time-line me-1"></i> {{ $daysOverdue }} dias
                                            </span>
                                            <div class="small text-muted mt-1">{{ $badgeText }}</div>
                                        @else
                                            <span class="badge text-bg-secondary">
                                                <i class="ri-check-line me-1"></i> Finalizado
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge text-bg-primary">
                                            {{ $phaseLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $activity['color'] ?? 'text-bg-secondary' }}">
                                            {{ $activity['label'] ?? 'Sem status' }}
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        @if ($assignee['has_assignee'] ?? false)
                                            <div class="fw-semibold">{{ $assignee['name'] }}</div>
                                            <div class="small text-muted">{{ $assignee['company'] ?? 'Sem empresa' }}</div>
                                        @else
                                            <div class="small text-muted">Sem responsável</div>
                                        @endif
                                        <span class="badge {{ $assignee['assignment_badge'] ?? 'text-bg-secondary' }} mt-1">
                                            {{ $assignee['assignment_status'] ?? 'Nao Atribuido' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary p-1"
                                            wire:click="$emitTo('components.d5.d5details', 'openD5Details', {{ $list->note_id }})">
                                            Visualizar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center py-5">
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
        @livewire('components.d5.d5details', key('five-note-details'))
        @livewire('components.five-note.manual-create', key('manual-create-five'))
        @livewire('components.five-note.edit-d5', key('edit-five-note'))
    </div>

</div>
