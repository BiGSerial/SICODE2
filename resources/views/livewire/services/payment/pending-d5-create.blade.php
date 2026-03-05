<div class="oexterno-page">
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
                'key' => 'desired_between',
                'label' => 'Despacho (de/ate)',
                'type' => 'daterange',
                'include_nulls' => false,
                'treat_zero_date_as_null' => false,
            ],
        ];
    @endphp

    <x-show-loading />

    <style>
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

        .filters-grid .btn-group .btn {
            min-width: 72px;
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

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>PAGAMENTO</h2>
                <div class="meta">D5 pendentes para criacao</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Exportacao</div>
                <button class="btn btn-warning btn-sm mt-2 me-2" data-bs-toggle="modal" data-bs-target="#bulkD5Modal"
                    wire:click="resetBulkD5">
                    <i class="ri-link-m me-1"></i>
                    Associar D5
                </button>
                <button class="btn btn-light btn-sm mt-2" wire:click="exportExcel">
                    <i class="ri-file-excel-2-line me-1"></i>
                    Exportar Excel
                </button>
            </div>
        </div>

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
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.debounce.500ms="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar">
                                        <label for="search">Buscar</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#buscarMultiModal">
                                            <i class="ri-checkbox-multiple-blank-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7 col-xl-8">
                        <div class="filter-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Filtros adicionais</h6>
                            </div>
                            @livewire('components.filters.bar', ['config' => $filters, 'group' => 'payments_pending_d5', 'manualApply' => true], key('filters-bar'))
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
            @if (!empty($lists) && $lists->count() > 0)
                <div class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">D5 PENDENTES PARA CRIACAO</h4>
                    <button class="btn btn-success" wire:click="exportExcel">
                        <i class="ri-file-excel-2-line me-2"></i>Exportar
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                <th class="fw-bold text-start">Nota</th>
                                <th class="fw-bold text-center">Empresa</th>
                                <th class="fw-bold text-center">Local</th>
                                <th class="fw-bold text-center">Conjunto</th>
                                <th class="fw-bold text-center">PEP</th>
                                <th class="fw-bold text-center">Codificacao</th>
                                <th class="fw-bold text-center">Motivo</th>
                                <th class="fw-bold text-start">Descricao</th>
                                <th class="fw-bold text-center">Despacho</th>
                                <th class="fw-bold text-center">Nota D5</th>
                                <th class="fw-bold text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr class="align-middle text-center">
                                    <td class="text-start">
                                        <div class="fw-semibold">{{ $list->note?->note ?? '---' }}</div>
                                        <div class="small text-muted">ID: {{ $list->note_id }}</div>
                                    </td>
                                    <td>{{ $list->company?->name }}</td>
                                    <td>{{ $list->loc_install }}</td>
                                    <td>{{ $list->conjunto }}</td>
                                    <td>{{ $list->pep }}</td>
                                    <td>{{ $list->codify }}</td>
                                    <td>{{ $list->reason }}</td>
                                    <td class="text-start">{{ $list->description }}</td>
                                    <td>{{ $list->dispatch_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge text-bg-warning">SEM D5</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary p-1"
                                            wire:click="$emitTo('components.five-note.edit-d5', 'getInfoResponse', {{ $list->id }})">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body">
                    <h4 class="text-center text-muted">SEM DADOS PARA EXIBIR</h4>
                </div>
            @endif
        </div>

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
    </div>

    <div wire:ignore.self class="modal fade" id="bulkD5Modal" tabindex="-1" aria-labelledby="bulkD5ModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content shadow">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title" id="bulkD5ModalLabel">
                        <i class="ri-link-m me-2"></i>
                        Associar D5 em massa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="form-floating">
                                <textarea class="form-control" id="bulkD5Input" style="height: 260px;"
                                    placeholder="Cole aqui os valores (Nota e D5)"
                                    wire:model.debounce.500ms="bulkD5Input"></textarea>
                                <label for="bulkD5Input">Cole os pares (Nota/Ordem e D5)</label>
                            </div>
                            <div class="form-text">
                                Separe com espaco, virgula, ponto e virgula, quebra de linha ou qualquer separador nao
                                numerico. A sequencia sempre sera: <strong>Nota ou Ordem</strong> e <strong>Nota D5</strong>.
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="small text-uppercase text-muted mb-2">Resumo do processamento</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="d-flex justify-content-between">
                                                <span>Prontas</span>
                                                <strong>{{ count($bulkD5Ready ?? []) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex justify-content-between">
                                                <span>Divergentes</span>
                                                <strong>{{ count($bulkD5Divergent ?? []) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex justify-content-between">
                                                <span>Sem solicitacao</span>
                                                <strong>{{ count($bulkD5Missing ?? []) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex justify-content-between">
                                                <span>Ignoradas</span>
                                                <strong>{{ count($bulkD5Ignored ?? []) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($bulkD5Processed && count($bulkD5Invalid ?? []))
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <strong>Aviso:</strong>
                                            {{ implode(' ', $bulkD5Invalid) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($bulkD5Processed)
                        <div class="row g-3 mt-2">
                            <div class="col-12 col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header text-bg-success">
                                        Prontas para atualizar
                                    </div>
                                    <div class="card-body p-0">
                                        @if (count($bulkD5Ready ?? []))
                                            <ul class="list-group list-group-flush">
                                                @foreach ($bulkD5Ready as $item)
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>Nota {{ $item['note'] }}</span>
                                                        <span class="badge text-bg-success">D5 {{ $item['d5'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="p-3 text-muted">Nenhum registro pronto.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header text-bg-warning">
                                        Notas D5 divergentes
                                    </div>
                                    <div class="card-body p-0">
                                        @if (count($bulkD5Divergent ?? []))
                                            <ul class="list-group list-group-flush">
                                                @foreach ($bulkD5Divergent as $item)
                                                    <li class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="fw-semibold">Nota {{ $item['note'] }}</div>
                                                                <div class="small text-muted">
                                                                    Atual: {{ $item['current_d5'] }} · Novo:
                                                                    {{ $item['new_d5'] }}
                                                                </div>
                                                                @if (!empty($item['locked']))
                                                                    <div class="small text-muted">Nao sera alterada (nota despachada).</div>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                @if (!empty($item['locked']))
                                                                    <span class="badge text-bg-secondary">DESPACHADA</span>
                                                                @endif
                                                                <button class="btn btn-outline-danger btn-sm"
                                                                    wire:click="removeBulkD5Divergent('{{ $item['note'] }}')">
                                                                    Remover
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="p-3 text-muted">Nenhuma divergencia encontrada.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header text-bg-danger">
                                        Sem solicitacao de D5
                                    </div>
                                    <div class="card-body p-0">
                                        @if (count($bulkD5Missing ?? []))
                                            <ul class="list-group list-group-flush">
                                                @foreach ($bulkD5Missing as $noteNumber)
                                                    <li class="list-group-item">Nota {{ $noteNumber }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="p-3 text-muted">Nenhuma nota sem solicitacao.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header text-bg-light">
                                        Ignoradas
                                    </div>
                                    <div class="card-body p-0">
                                        @if (count($bulkD5Ignored ?? []))
                                            <ul class="list-group list-group-flush">
                                                @foreach ($bulkD5Ignored as $item)
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>Nota {{ $item['note'] }}</span>
                                                        <span class="small text-muted">
                                                            {{ $item['reason'] ?? 'Ignorada' }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="p-3 text-muted">Nenhum registro ignorado.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mt-3 mb-0">
                            Cole os pares e clique em <strong>Processar</strong> para validar antes de confirmar.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="processBulkD5"
                        @disabled(!trim($bulkD5Input ?? ''))>
                        <i class="ri-cpu-line me-1"></i>Processar
                    </button>
                    <button class="btn btn-success" wire:click="confirmBulkD5"
                        @disabled(!$bulkD5Processed || (count($bulkD5Ready ?? []) + count($bulkD5Divergent ?? [])) === 0)>
                        <i class="ri-check-double-line me-1"></i>Confirmar alteracoes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="buscarMultiModal" tabindex="-1" aria-labelledby="buscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">
                        <i class="ri-search-2-line me-2"></i>
                        Busca multipla de notas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating">
                        <textarea class="form-control" id="advanceSearch" style="height: 200px;"
                            placeholder="Cole aqui varios valores (virgula ou quebra de linha)"
                            wire:model.defer="advanceSearch"></textarea>
                        <label for="advanceSearch">Numeros / valores</label>
                    </div>
                    <div class="form-text">
                        Separe por virgula <strong>,</strong> ou por quebra de linha.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">
                        <i class="ri-check-line me-1"></i>Aplicar filtro
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewire('components.five-note.edit-d5', key('edit-five-note'))

    <script>
        const bulkD5ModalEl = document.getElementById('bulkD5Modal');

        if (bulkD5ModalEl) {
            bulkD5ModalEl.addEventListener('show.bs.modal', () => {
                Livewire.emit('bulkD5Reset');
            });

            bulkD5ModalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emit('bulkD5Reset');
            });
        }

        window.addEventListener('bulk-d5-close', () => {
            const modalEl = document.getElementById('bulkD5Modal');
            if (!modalEl) return;

            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
        });
    </script>

</div>
