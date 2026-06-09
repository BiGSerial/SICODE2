<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-navy: #14283d;
                --ri-blue: #315f87;
                --ri-bg: #f3f6f8;
                --ri-surface: #ffffff;
                --ri-muted: #667684;
                --ri-ink: #22303d;
                --ri-border: #dce5ea;
                background: var(--ri-bg);
                font-family: var(--bs-body-font-family);
                min-height: calc(100vh - 70px);
                padding: 1.35rem 0 2rem;
            }

            .ri-page {
                font-family: var(--bs-body-font-family) !important;
            }

            .ri-page * {
                font-family: inherit;
            }

            .ri-page i[class^="ri-"],
            .ri-page i[class*=" ri-"] {
                font-family: "remixicon" !important;
            }

            .ri-header {
                background: linear-gradient(125deg, var(--ri-navy), #28536f 72%, #347985);
                color: #fff;
                border-radius: 1rem;
                padding: 1.4rem 1.6rem;
                box-shadow: 0 14px 30px rgba(20, 40, 61, .18);
                margin-bottom: 1rem;
            }

            .ri-header h1 {
                color: #fff;
                font-size: 1.75rem;
                font-weight: 700;
                margin: 0;
            }

            .ri-header .meta {
                color: rgba(255, 255, 255, .65);
                font-size: .875rem;
            }

            .ri-eyebrow {
                color: #9edce0;
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .12em;
                text-transform: uppercase;
            }

            .ri-header .form-control {
                border: 1px solid rgba(255, 255, 255, .35);
            }

            .filters-grid .filter-card {
                background-color: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: .9rem;
                padding: 1rem;
                height: 100%;
                box-shadow: 0 8px 20px rgba(33, 52, 68, .05);
            }

            .filters-grid .filter-card h6 {
                font-size: .72rem;
                text-transform: uppercase;
                letter-spacing: .07em;
                font-weight: 700;
                color: var(--ri-muted);
            }

            .ri-page .form-control,
            .ri-page .form-select,
            .ri-page .btn {
                font-size: .875rem;
            }

            .summary-bar {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: .9rem;
                padding: .75rem 1rem;
                box-shadow: 0 8px 20px rgba(33, 52, 68, .05);
            }

            .summary-bar .summary-item {
                font-size: .84rem;
                color: var(--ri-muted);
            }

            .summary-bar .summary-item strong {
                color: var(--ri-ink);
            }

            .table-card {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: .9rem;
                box-shadow: 0 8px 20px rgba(33, 52, 68, .05);
                overflow: hidden;
            }

            .table-card .table thead th {
                background: var(--ri-navy);
                color: #fff;
                font-size: .7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .055em;
                padding: .85rem .7rem;
                white-space: nowrap;
            }

            .table-card .table tbody td {
                border-color: #e8eef1;
                color: var(--ri-ink);
                font-size: .84rem;
                padding: .85rem .7rem;
            }

            .table-card .table tbody tr:hover td {
                background: #f5fafb;
            }

            .table-title {
                background: #fff;
                border-bottom: 1px solid var(--ri-border);
                color: var(--ri-ink);
                padding: 1rem;
            }

            .ri-reason-trigger {
                max-width: 220px;
            }

            .ri-reason-modal .modal-content {
                border: 0;
                border-radius: 1rem;
                font-family: var(--bs-body-font-family) !important;
                box-shadow: 0 24px 60px rgba(20, 40, 61, .22);
                overflow: hidden;
            }

            .ri-reason-modal .modal-header {
                background: linear-gradient(125deg, var(--ri-navy), #28536f 72%, #347985);
                border: 0;
                color: #fff;
                padding: 1.25rem 1.4rem;
            }

            .ri-reason-modal .modal-title {
                color: #fff;
                font-weight: 700;
            }

            .ri-reason-meta {
                background: #f3f6f8;
                border: 1px solid var(--ri-border);
                border-radius: .75rem;
                padding: .9rem 1rem;
            }

            .ri-reason-text {
                background: #fff;
                border: 1px solid var(--ri-border);
                border-left: 4px solid var(--ri-blue);
                border-radius: .75rem;
                color: var(--ri-ink);
                line-height: 1.6;
                min-height: 110px;
                padding: 1rem;
                white-space: pre-wrap;
            }

            @media (max-width: 991px) {
                .ri-header {
                    padding: 1.25rem;
                }

                .ri-header h1 {
                    font-size: 1.6rem;
                }
            }
        </style>
    @endpush

    <div class="container-fluid">
        <div class="ri-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="ri-eyebrow">Reports · consulta histórica</div>
                <h1>Relatório Histórico de Retorno Interno</h1>
                <div class="meta">Consulta consolidada com filtros detalhados e exportação.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div>
                    <div class="meta">Início</div>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_in"
                        max="{{ date('Y-m-d') }}">
                </div>
                <div>
                    <div class="meta">Fim</div>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_out"
                        max="{{ date('Y-m-d') }}">
                </div>
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
                                            <option value="200">200</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100">
                                        <input wire:model.debounce.500ms="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar nota ou categoria">
                                        <label for="search">Nota ou categoria</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 col-xl-3">
                        <div class="filter-card">
                            <h6>Classificação</h6>
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Origem</label>
                                <select class="form-select border border-secondary" wire:model="originFilters"
                                    multiple size="5">
                                    @foreach ($originOptions as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-muted small mb-1">Status do retorno</label>
                                <select class="form-select border border-secondary" wire:model="completedFilter">
                                    <option value="">Todos</option>
                                    <option value="open">Em aberto</option>
                                    <option value="closed">Concluido</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5">
                        <div class="filter-card h-100">
                            <h6 class="mb-3">Filtros adicionais</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select border border-secondary" wire:model="serviceIds"
                                            multiple size="4" id="serviceSelect">
                                            @foreach ($serviceOptions as $service)
                                                <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                            @endforeach
                                        </select>
                                        <label for="serviceSelect">Serviços</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control border border-secondary"
                                            wire:model.debounce.500ms="category" id="categoryInput"
                                            placeholder="Categoria">
                                        <label for="categoryInput">Categoria</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select border border-secondary"
                                            wire:model="dispatcherUserId" id="dispatcherSelect">
                                            <option value="">Quem despachou</option>
                                            @foreach ($dispatcherOptions as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <label for="dispatcherSelect">Despachante</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select border border-secondary" wire:model="productionUserId"
                                            id="productionUserSelect">
                                            <option value="">Usuário da produção</option>
                                            @foreach ($productionUserOptions as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <label for="productionUserSelect">Usuário da produção</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select border border-secondary" wire:model="companyId"
                                            id="companySelect">
                                            <option value="">Empresa</option>
                                            @foreach ($companyOptions as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                        <label for="companySelect">Empresa executora</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select border border-secondary" wire:model="productionStatus"
                                            id="statusSelect">
                                            <option value="">Status da produção</option>
                                            @foreach ($statusOptions as $status)
                                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <label for="statusSelect">Status da produção</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control border border-secondary"
                                            wire:model="resolutionMin" id="resolutionMin"
                                            placeholder="Min dias">
                                        <label for="resolutionMin">Prazo min (dias)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control border border-secondary"
                                            wire:model="resolutionMax" id="resolutionMax"
                                            placeholder="Max dias">
                                        <label for="resolutionMax">Prazo max (dias)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-success w-100" wire:click="exportReport"
                                        wire:loading.attr="disabled" wire:target="exportReport">
                                        <span wire:loading.remove wire:target="exportReport">
                                            <i class="ri-file-excel-2-line me-1"></i>Exportar relatório
                                        </span>
                                        <span wire:loading wire:target="exportReport">
                                            <i class="ri-loader-4-line me-1"></i>Preparando arquivo...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    @if ($lists->count())
                        {{ $lists->links() }}
                    @endif
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            @if (!$lists->count())
                <div class="card-body">
                    <h4 class="text-center text-muted">Sem dados para os filtros atuais</h4>
                </div>
            @else
                <div class="table-title d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="h6 fw-bold mb-0">Retornos internos</h4>
                        <span class="text-muted small">Dados históricos do período selecionado</span>
                    </div>
                    <span class="text-muted small">Atualizado em {{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="sticky-top" style="z-index:1; top:0;">
                                <th class="text-center">Nota</th>
                                <th class="text-center">Origem</th>
                                <th class="text-center">Serviço</th>
                                <th class="text-center">Despachante</th>
                                <th class="text-center">Categoria</th>
                                <th class="text-center">Descrição</th>
                                <th class="text-center">Criado em</th>
                                <th class="text-center">Atuação na produção</th>
                                <th class="text-center">Concluído</th>
                                <th class="text-center">Usuário da produção</th>
                                <th class="text-center">Empresa da produção</th>
                                <th class="text-center">Status da produção</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $origin = 'Sem Origem';
                                    if ($list->Viabilities->isNotEmpty()) {
                                        $origin = 'Viabilidade';
                                    } elseif ($list->Waiting) {
                                        $origin = 'Contratação';
                                    } elseif ($list->Approvals->isNotEmpty()) {
                                        $origin = 'Aprovação';
                                    } elseif ($list->Externals->isNotEmpty()) {
                                        $origin = 'Órgão Externo';
                                    }
                                    $firstComment = $list->Comments->sortBy('created_at')->first();
                                    $productionStatus = $list->Production
                                        ? \App\Custom\Notestatus::status($list->Production->status)
                                        : null;
                                @endphp
                                <tr wire:key="ri-{{ $list->id }}" class="align-middle">
                                    <td class="text-center fw-bold">{{ $list->Note->note ?? '-' }}</td>
                                    <td class="text-center">{{ $origin }}</td>
                                    <td class="text-center">{{ $list->Service->service ?? '-' }}</td>
                                    <td class="text-center">
                                        {{ $firstComment?->User?->name ?? '-' }}
                                    </td>
                                    <td class="text-center">{{ $list->category ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary ri-reason-trigger"
                                            wire:click="showReason({{ $list->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="showReason">
                                            <i class="ri-message-3-line me-1"></i>
                                            <span wire:loading.remove wire:target="showReason">
                                                Ver detalhes
                                            </span>
                                            <span wire:loading wire:target="showReason">
                                                Carregando...
                                            </span>
                                        </button>
                                    </td>
                                    <td class="text-center">{{ $list->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">{{ $list->Production?->att_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $list->completed_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $list->Production?->User?->name ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $list->Production?->Company?->name ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($productionStatus)
                                            <span class="badge {{ $productionStatus->colorbg }}">
                                                {{ $productionStatus->status }}
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">Aguardando atribuição</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

        @include('livewire.reports.partials.return-intern-details-modal')
    </div>
</div>
