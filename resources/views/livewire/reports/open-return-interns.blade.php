<div class="open-ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .open-ri-page {
                --ri-navy: #14283d;
                --ri-blue: #315f87;
                --ri-cyan: #e9f5f7;
                --ri-ink: #22303d;
                --ri-muted: #667684;
                --ri-border: #dce5ea;
                background: #f3f6f8;
                font-family: var(--bs-body-font-family);
                min-height: calc(100vh - 70px);
                padding: 1.35rem 0 2rem;
            }

            .open-ri-page {
                font-family: var(--bs-body-font-family) !important;
            }

            .open-ri-page * {
                font-family: inherit;
            }

            .open-ri-page i[class^="ri-"],
            .open-ri-page i[class*=" ri-"] {
                font-family: "remixicon" !important;
            }

            .open-ri-hero {
                background: linear-gradient(125deg, var(--ri-navy), #28536f 72%, #347985);
                border-radius: 1rem;
                color: #fff;
                padding: 1.4rem 1.6rem;
                box-shadow: 0 14px 30px rgba(20, 40, 61, .18);
            }

            .open-ri-eyebrow {
                color: #9edce0;
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .12em;
                text-transform: uppercase;
            }

            .open-ri-card {
                background: #fff;
                border: 1px solid var(--ri-border);
                border-radius: .9rem;
                box-shadow: 0 8px 20px rgba(33, 52, 68, .05);
            }

            .open-ri-metric {
                min-height: 106px;
                padding: 1rem 1.1rem;
                position: relative;
                overflow: hidden;
            }

            .open-ri-metric::after {
                background: var(--metric-color, var(--ri-blue));
                content: "";
                height: 4px;
                inset: auto 0 0;
                position: absolute;
            }

            .open-ri-metric-label {
                color: var(--ri-muted);
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .07em;
                text-transform: uppercase;
            }

            .open-ri-metric-value {
                color: var(--ri-ink);
                font-size: 1.65rem;
                font-weight: 750;
                line-height: 1.2;
                margin-top: .35rem;
            }

            .open-ri-filters {
                padding: 1rem;
            }

            .open-ri-table thead th {
                background: var(--ri-navy);
                color: #fff;
                font-size: .7rem;
                font-weight: 700;
                letter-spacing: .055em;
                padding: .85rem .7rem;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .open-ri-table tbody td {
                border-color: #e8eef1;
                color: var(--ri-ink);
                font-size: .84rem;
                padding: .85rem .7rem;
                vertical-align: middle;
            }

            .open-ri-table tbody tr:hover td {
                background: #f5fafb;
            }

            .open-ri-age {
                font-size: .95rem;
                font-weight: 750;
            }

            .open-ri-age.is-alert {
                color: #b42318;
            }

            .open-ri-production {
                min-width: 220px;
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
                border: 1px solid var(--ri-border);
                border-left: 4px solid var(--ri-blue);
                border-radius: .75rem;
                color: var(--ri-ink);
                line-height: 1.6;
                min-height: 110px;
                padding: 1rem;
                white-space: pre-wrap;
            }

            @media (max-width: 767px) {
                .open-ri-hero {
                    padding: 1.15rem;
                }
            }
        </style>
    @endpush

    <div class="container-fluid">
        <section class="open-ri-hero mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="open-ri-eyebrow">Reports · acompanhamento operacional</div>
                    <h1 class="h3 fw-bold mb-1">Retorno Interno</h1>
                    <p class="mb-0 text-white-50">
                        Todos os retornos internos abertos, da origem até a situação atual da produção.
                    </p>
                </div>
                <div class="align-self-lg-center text-lg-end">
                    <div class="small text-white-50">Atualizado em</div>
                    <div class="fw-semibold">{{ now()->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-3">
            <div class="col-6 col-lg-2">
                <div class="open-ri-card open-ri-metric" style="--metric-color:#315f87">
                    <div class="open-ri-metric-label">RI abertos</div>
                    <div class="open-ri-metric-value">{{ $summary['total'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="open-ri-card open-ri-metric" style="--metric-color:#d97706">
                    <div class="open-ri-metric-label">Sem produção</div>
                    <div class="open-ri-metric-value">{{ $summary['without_production'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="open-ri-card open-ri-metric" style="--metric-color:#7c3aed">
                    <div class="open-ri-metric-label">Sem responsável</div>
                    <div class="open-ri-metric-value">{{ $summary['unassigned'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="open-ri-card open-ri-metric" style="--metric-color:#16836f">
                    <div class="open-ri-metric-label">Com responsável</div>
                    <div class="open-ri-metric-value">{{ $summary['assigned'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="open-ri-card open-ri-metric" style="--metric-color:#b42318">
                    <div class="open-ri-metric-label">Acima de 7 dias</div>
                    <div class="open-ri-metric-value">{{ $summary['overdue'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="open-ri-card open-ri-metric" style="--metric-color:#536471">
                    <div class="open-ri-metric-label">Mais antigo</div>
                    <div class="open-ri-metric-value fs-4">{{ $summary['oldest'] }}</div>
                </div>
            </div>
        </section>

        <section class="open-ri-card open-ri-filters mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="form-label small fw-semibold mb-1">Buscar</label>
                    <input type="search" class="form-control" wire:model.debounce.400ms="search"
                        placeholder="Nota, categoria, responsável ou empresa">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Origem</label>
                    <select class="form-select" wire:model="origin">
                        <option value="">Todas</option>
                        <option value="viability">Viabilidade</option>
                        <option value="waiting">Contratação</option>
                        <option value="approval">Aprovação</option>
                        <option value="external">Órgão externo</option>
                        <option value="unknown">Não identificada</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Serviço</label>
                    <select class="form-select" wire:model="serviceId">
                        <option value="">Todos</option>
                        @foreach ($serviceOptions as $service)
                            <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Produção</label>
                    <select class="form-select" wire:model="productionState">
                        <option value="">Todas</option>
                        <option value="none">Sem produção</option>
                        <option value="unassigned">Sem responsável</option>
                        <option value="assigned">Com responsável</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Tempo em aberto</label>
                    <select class="form-select" wire:model="age">
                        <option value="">Todos</option>
                        <option value="today">Hoje</option>
                        <option value="1-3">1 a 3 dias</option>
                        <option value="4-7">4 a 7 dias</option>
                        <option value="8-15">8 a 15 dias</option>
                        <option value="16+">Acima de 15 dias</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-1">
                    <label class="form-label small fw-semibold mb-1">Linhas</label>
                    <select class="form-select" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-12 col-md-8 col-lg-4">
                    <label class="form-label small fw-semibold mb-1">Responsável pela produção</label>
                    <select class="form-select" wire:model="productionUserId">
                        <option value="">Todos</option>
                        @foreach ($productionUserOptions as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                        <i class="ri-filter-off-line me-1"></i>Limpar filtros
                    </button>
                </div>
            </div>
        </section>

        <section class="open-ri-card overflow-hidden">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 p-3 border-bottom">
                <div>
                    <h2 class="h6 fw-bold mb-0">Fila de retornos internos</h2>
                    <div class="small text-muted">Ordenada do mais antigo para o mais recente.</div>
                </div>
                <div class="small text-muted">
                    {{ $reclaims->total() }} {{ $reclaims->total() === 1 ? 'registro' : 'registros' }}
                </div>
            </div>

            @if ($reclaims->isEmpty())
                <div class="text-center py-5 px-3">
                    <i class="ri-checkbox-circle-line fs-1 text-success"></i>
                    <h3 class="h6 mt-2 mb-1">Nenhum retorno interno aberto</h3>
                    <p class="small text-muted mb-0">Não há registros para os filtros selecionados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table open-ri-table mb-0">
                        <thead>
                            <tr>
                                <th>Nota / Serviço</th>
                                <th>Origem do retorno</th>
                                <th>Motivo</th>
                                <th>Aberto há</th>
                                <th>Produção associada</th>
                                <th>Com quem</th>
                                <th>Tempo na produção</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reclaims as $reclaim)
                                @php
                                    $origin = $this->originMeta($reclaim);
                                    $openDays = $reclaim->created_at?->diffInDays(now()) ?? 0;
                                    $production = $reclaim->Production;
                                    $productionReference = $production?->att_at
                                        ?? $production?->dispatch_at
                                        ?? $production?->created_at;
                                    $productionStatus = $production && $production->status !== null
                                        ? $this->productionStatus($production->status)
                                        : null;
                                @endphp
                                <tr wire:key="open-ri-{{ $reclaim->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $reclaim->Note?->note ?? 'Nota não informada' }}</div>
                                        <div class="small text-muted">{{ $reclaim->Service?->service ?? 'Serviço não informado' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $origin['class'] }}">
                                            <i class="{{ $origin['icon'] }} me-1"></i>{{ $origin['label'] }}
                                        </span>
                                        <div class="small text-muted mt-1">
                                            RI #{{ $reclaim->id }}
                                            @if ($origin['reference'])
                                                · origem #{{ $origin['reference'] }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $reclaim->category ?: 'Sem categoria' }}</div>
                                        <div class="small text-muted">{{ $reclaim->created_at?->format('d/m/Y H:i') }}</div>
                                        <button type="button" class="btn btn-sm btn-link px-0 mt-1 text-decoration-none"
                                            wire:click="showReason({{ $reclaim->id }})"
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
                                    <td>
                                        <div class="open-ri-age {{ $openDays >= 8 ? 'is-alert' : '' }}">
                                            {{ $this->humanDuration($reclaim->created_at) }}
                                        </div>
                                        @if ($openDays >= 8)
                                            <div class="small text-danger">Atenção prioritária</div>
                                        @endif
                                    </td>
                                    <td class="open-ri-production">
                                        @if ($production)
                                            <div class="fw-semibold">Produção #{{ $production->id }}</div>
                                            <div class="small text-muted">
                                                {{ $production->Company?->name ?? 'Empresa não informada' }}
                                            </div>
                                        @else
                                            <span class="badge text-bg-warning">Não associada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($production?->User)
                                            <div class="fw-semibold">{{ $production->User->name }}</div>
                                            <div class="small text-muted">Responsável atual</div>
                                        @elseif ($production)
                                            <span class="text-danger fw-semibold">Sem responsável</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($productionReference)
                                            <div class="fw-semibold">{{ $this->humanDuration($productionReference) }}</div>
                                            <div class="small text-muted">desde {{ $productionReference->format('d/m H:i') }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($productionStatus)
                                            <span class="badge {{ $productionStatus->colorbg }}">
                                                {{ $productionStatus->status }}
                                            </span>
                                        @elseif ($production)
                                            <span class="badge text-bg-secondary">Sem status</span>
                                        @else
                                            <span class="badge text-bg-light text-dark border">Aguardando produção</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top">
                    {{ $reclaims->links() }}
                </div>
            @endif
        </section>

        @include('livewire.reports.partials.return-intern-details-modal')
    </div>
</div>
