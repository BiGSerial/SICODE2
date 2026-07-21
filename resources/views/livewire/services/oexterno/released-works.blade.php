<div class="user-activity-page protocol-activity-page">
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')
    <style>
        .protocol-table-header {
            background: linear-gradient(120deg, #111827, #334155);
        }

        .protocol-empty-state {
            color: #64748b;
        }

        .protocol-empty-state i {
            display: block;
        }

        .protocol-empty-state h5 {
            color: #1f2937;
            font-weight: 700;
        }
    </style>

    <div class="container-fluid">
        @include('livewire.services.partials.user-activity-hero', [
            'context' => 'Obras liberadas',
            'subtitle' => 'Projetos dependentes de Órgão Externo aguardando saída para 20 ou 11',
            'total' => $releases->total(),
            'accent' => '#334155',
        ])

        <div class="mb-3">
            <div class="row g-3 protocol-filters-grid">
                <div class="col-12 col-lg-6">
                    <div class="activity-filter-card">
                        <div class="activity-filter-title mb-2">Pesquisa de Nota/OV</div>
                        <div class="small text-muted mb-2">
                            Busque por nota, pedido, cliente ou município. Para colar várias notas, use a busca em massa.
                        </div>
                        <div class="form-floating w-100 position-relative">
                            <input type="text" class="form-control border border-secondary"
                                wire:model.debounce.500ms="search" id="releasedWorksSearch"
                                placeholder="Nota, pedido, cliente ou município">
                            <label for="releasedWorksSearch">Buscar nota / pedido / cliente</label>
                            <button type="button"
                                class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Busca em massa">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="activity-filter-card">
                        <div class="activity-filter-title mb-2">Visão</div>
                        <div class="small text-muted mb-2">Separe novas, exportadas e liberadas.</div>
                        <div class="btn-group w-100" role="group" aria-label="Visão das obras liberadas">
                            <input type="radio" class="btn-check" name="releasedWorksTab" wire:model="tab"
                                value="new" id="releasedWorksTabNew">
                            <label class="btn btn-outline-primary" for="releasedWorksTabNew">Novas</label>

                            <input type="radio" class="btn-check" name="releasedWorksTab" wire:model="tab"
                                value="exported" id="releasedWorksTabExported">
                            <label class="btn btn-outline-primary" for="releasedWorksTabExported">Exportadas</label>

                            <input type="radio" class="btn-check" name="releasedWorksTab" wire:model="tab"
                                value="released" id="releasedWorksTabReleased">
                            <label class="btn btn-outline-primary" for="releasedWorksTabReleased">Liberadas</label>

                            <input type="radio" class="btn-check" name="releasedWorksTab" wire:model="tab"
                                value="pending" id="releasedWorksTabPending">
                            <label class="btn btn-outline-primary" for="releasedWorksTabPending">Pendentes</label>

                            <input type="radio" class="btn-check" name="releasedWorksTab" wire:model="tab"
                                value="cancel_90" id="releasedWorksTabCancel90">
                            <label class="btn btn-outline-danger" for="releasedWorksTabCancel90">Cancelamento 90+</label>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="activity-filter-card">
                        <div class="activity-filter-title mb-2">Liberação & Custo</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1" for="releaseStatusDateFrom">Status de</label>
                                <input type="date" class="form-control border border-secondary"
                                    id="releaseStatusDateFrom" wire:model="releaseStatusDateFrom">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1" for="releaseStatusDateTo">Status até</label>
                                <input type="date" class="form-control border border-secondary"
                                    id="releaseStatusDateTo" wire:model="releaseStatusDateTo">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1" for="releasedWorksCostType">Custo</label>
                                <select class="form-select border border-secondary" id="releasedWorksCostType"
                                    wire:model="costType">
                                    <option value="">Todos</option>
                                    <option value="client">Custo cliente</option>
                                    <option value="company">Custo empresa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-2">
                    <div class="activity-filter-card d-flex flex-column justify-content-end gap-2">
                        <button type="button" class="btn btn-primary" wire:click.prevent="exportToExcel"
                            wire:loading.attr="disabled" wire:target="exportToExcel">
                            <i class="ri-file-excel-2-line me-1"></i>
                            <span wire:loading.remove wire:target="exportToExcel">Exportar</span>
                            <span wire:loading wire:target="exportToExcel">Exportando...</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" wire:click.prevent="cleanAll">
                            <i class="ri-filter-off-line me-1"></i>Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if ($releases->isNotEmpty())
            <div class="user-activity-summary mb-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-6">
                        {{ $releases->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="activity-summary-text">
                            Exibindo <strong>{{ $releases->firstItem() }}</strong> até
                            <strong>{{ $releases->lastItem() }}</strong> de
                            <strong>{{ $releases->total() }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="user-activity-table-card position-relative">
            @if (!$releases->count())
                <div class="protocol-empty-state p-5 text-center">
                    <i class="ri-inbox-line fs-1 text-muted"></i>
                    <h5 class="mt-2">Nenhuma obra encontrada</h5>
                    <p class="text-muted mb-0">Revise os filtros ou aguarde novas notas nesta etapa.</p>
                </div>
            @else
                <div
                    class="user-activity-table-header protocol-table-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h5 class="user-activity-table-title">
                            <i class="ri-building-4-line me-2"></i>Obras liberadas para OE
                        </h5>
                        <div class="user-activity-table-subtitle">
                            @if ($tab === 'cancel_90')
                                Notas em status 70 há mais de 90 dias, disponíveis para envio ao cancelamento total.
                            @else
                                Exportar marca a pendência como enviada; a liberação ocorre apenas quando o status chegar em 20 ou 11.
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($tab === 'cancel_90')
                            <button wire:click="sendSelectedToCancellation" class="btn btn-danger"
                                @if (!count($selectedReleaseIds)) disabled @endif>
                                <i class="ri-close-circle-line me-2"></i>Enviar selecionadas para cancelamento
                                @if (count($selectedReleaseIds))
                                    <span class="badge text-bg-light ms-1">{{ count($selectedReleaseIds) }}</span>
                                @endif
                            </button>
                        @endif
                        <button wire:click="exportToExcel" class="btn btn-light">
                            <i class="ri-file-excel-2-line me-2"></i>Exportar
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                @if ($tab === 'cancel_90')
                                    <th class="text-center">Sel.</th>
                                @endif
                                <th>Nota/OV</th>
                                <th>Cliente</th>
                                <th>Município</th>
                                <th>Rubrica</th>
                                <th>Custo</th>
                                <th>Status atual</th>
                                <th>Marcado em</th>
                                <th>Exportação</th>
                                <th>Liberação</th>
                                <th>Origem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($releases as $release)
                                @php
                                    $costSummary = $this->projectReviewCostSummary($release);
                                @endphp
                                <tr>
                                    @if ($tab === 'cancel_90')
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                wire:model="selectedReleaseIds"
                                                value="{{ $release->id }}">
                                        </td>
                                    @endif
                                    <td class="fw-semibold">{{ $release->note?->note ?? '---' }}</td>
                                    <td>{{ $release->note?->client ?? '---' }}</td>
                                    <td>{{ $release->note?->lexp ?? '---' }}</td>
                                    <td>{{ $release->note?->rubrica ?? '---' }}</td>
                                    <td>
                                        @if ($costSummary['has_cycle'])
                                            <span class="badge {{ $costSummary['has_client_cost'] ? 'text-bg-warning' : 'text-bg-success' }}">
                                                Cliente: {{ $costSummary['has_client_cost'] ? 'Sim' : 'Não' }}
                                            </span>
                                            <div class="small mt-1">
                                                Cliente: <strong>{{ $this->formatMoneyBr($costSummary['client_cost']) }}</strong>
                                            </div>
                                            <div class="small">
                                                Empresa: <strong>{{ $this->formatMoneyBr($costSummary['company_cost']) }}</strong>
                                            </div>
                                            <div class="small text-muted">
                                                Rodada {{ $costSummary['round_number'] }}
                                                @if ($costSummary['orders'])
                                                    · {{ $costSummary['orders'] }}
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge text-bg-secondary">Sem análise</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge text-bg-secondary">{{ $release->note?->nstats ?? '---' }}</span>
                                        <div class="small text-muted">{{ optional($release->note?->dt_status)->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td>
                                        {{ optional($release->created_at)->format('d/m/Y H:i') }}
                                        <div class="small text-muted">Status origem: {{ $release->detected_nstats ?? '---' }}</div>
                                    </td>
                                    <td>
                                        @if ($release->exported_at)
                                            {{ $release->exported_at->format('d/m/Y H:i') }}
                                            <div class="small text-muted">{{ $release->exportedBy?->name ?? '---' }}</div>
                                        @else
                                            <span class="badge text-bg-danger">Nova</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($release->released_at)
                                            {{ $release->released_at->format('d/m/Y H:i') }}
                                            <div class="small text-muted">Status {{ $release->release_nstats }}</div>
                                        @else
                                            <span class="badge text-bg-warning">Aguardando 20/11</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $release->production?->Service?->service ?? '---' }}</div>
                                        <div class="small text-muted">{{ $release->production?->User?->name ?? '---' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Busca em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Notas ou pedidos</label>
                    <textarea class="form-control" rows="8" wire:model.defer="advanceSearch"
                        placeholder="Cole uma nota ou pedido por linha"></textarea>
                    <div class="form-text">Aceita valores separados por quebra de linha, espaço, vírgula ou ponto e vírgula.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="buscarMulti">
                        <i class="ri-search-line me-1"></i>Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
