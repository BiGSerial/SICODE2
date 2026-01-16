<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-bg: #f7f8fb;
                --ri-surface: #ffffff;
                --ri-muted: #6b7280;
                --ri-ink: #1f2933;
                --ri-accent: #0f766e;
                --ri-border: #e5e7eb;
                background: radial-gradient(circle at 12% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 15%, #ecfeff, transparent 35%),
                    var(--ri-bg);
                padding: 1.5rem 0;
            }

            .ri-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.6rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1.5rem;
            }

            .ri-header h1 {
                font-size: 1.9rem;
                font-weight: 700;
                margin: 0;
                letter-spacing: 0.02em;
            }

            .ri-header .meta {
                color: rgba(248, 250, 252, 0.75);
                font-size: 0.95rem;
            }

            .filters-grid .filter-card {
                background-color: var(--ri-surface);
                border: 1px solid var(--ri-border);
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
                color: var(--ri-muted);
            }

            .summary-bar {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
                border-radius: 0.9rem;
                padding: 0.75rem 1.25rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            }

            .summary-bar .summary-item {
                font-size: 0.92rem;
                color: var(--ri-muted);
            }

            .summary-bar .summary-item strong {
                color: var(--ri-ink);
            }

            .table-card {
                background: var(--ri-surface);
                border: 1px solid var(--ri-border);
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
                <h1>Relatorio Retorno Interno</h1>
                <div class="meta">Lista consolidada com filtros e exportacao.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div>
                    <div class="meta">Inicio</div>
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
                            <h6>Classificacao</h6>
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
                                        <label for="serviceSelect">Servicos</label>
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
                                            <option value="">Usuario producao</option>
                                            @foreach ($productionUserOptions as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <label for="productionUserSelect">Usuario producao</label>
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
                                            <option value="">Status producao</option>
                                            @foreach ($statusOptions as $status)
                                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <label for="statusSelect">Status producao</label>
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
                                            <i class="ri-file-excel-2-line me-1"></i>Exportar relatorio
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
                    <h4 class="text-center text-muted">Sem dados para os filtros atuais</h4>
                </div>
            @else
                <div class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">RETORNOS INTERNOS</h4>
                    <span class="text-white-50 small">Atualizado em {{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                <th class="text-center">Nota</th>
                                <th class="text-center">Origem</th>
                                <th class="text-center">Servico</th>
                                <th class="text-center">Despachante</th>
                                <th class="text-center">Categoria</th>
                                <th class="text-center">Descricao</th>
                                <th class="text-center">Criado em</th>
                                <th class="text-center">Att producao</th>
                                <th class="text-center">Concluido</th>
                                <th class="text-center">Usuario producao</th>
                                <th class="text-center">Empresa producao</th>
                                <th class="text-center">Status producao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $origin = 'Sem Origem';
                                    if ($list->Viabilities->isNotEmpty()) {
                                        $origin = 'Viabilidade';
                                    } elseif ($list->Waiting) {
                                        $origin = 'Contratacao';
                                    } elseif ($list->Approvals->isNotEmpty()) {
                                        $origin = 'Aprovacao';
                                    } elseif ($list->Externals->isNotEmpty()) {
                                        $origin = 'Orgao Externo';
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
                                    <td class="text-center">{{ $firstComment?->message ?? '-' }}</td>
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
                                            <span class="badge text-bg-secondary">Aguardando atribuicao</span>
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
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
