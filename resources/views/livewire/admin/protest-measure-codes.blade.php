<div class="monitoring-page">
    <div class="container-fluid">
        <x-show-loading />

        <div class="monitoring-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>CÓDIGOS CIP/CONSTRUÇÃO</h2>
                <div class="meta">Configuração dinâmica por código da medida</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Códigos configurados</div>
                <div><strong>{{ $codes->count() }}</strong></div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent filters-grid">
            <div class="card-body px-0">
                <div class="filter-card">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-5">
                            <div class="form-floating">
                                <textarea class="form-control text-uppercase border border-secondary" id="bulkCodes"
                                    style="height: 8.5rem;" wire:model.defer="bulkCodes"
                                    placeholder="AL36, CA03, CC05"></textarea>
                                <label for="bulkCodes">Códigos para cadastrar/atualizar</label>
                            </div>
                            @error('bulkCodes')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        <div class="col-12 col-md-4 col-lg-2">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" id="bulkClassification"
                                    wire:model.defer="bulkClassification">
                                    <option value="construction">Construção</option>
                                    <option value="cip">CIP</option>
                                </select>
                                <label for="bulkClassification">Classificação</label>
                            </div>
                            @error('bulkClassification')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        <div class="col-12 col-md-3 col-lg-1">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="pmcc-bulk-active"
                                    wire:model.defer="bulkActive">
                                <label class="form-check-label" for="pmcc-bulk-active">Ativo</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-5 col-lg-2">
                            <button type="button" class="btn btn-primary w-100" wire:click="bulkSave">
                                <i class="ri-save-3-line me-1"></i>Salvar
                            </button>
                        </div>

                        <div class="col-12 col-md-5 col-lg-2">
                            <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearBulkForm">
                                <i class="ri-eraser-line me-1"></i>Limpar
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-0 mt-md-3">
                        <div class="col-12 col-lg-6">
                            <div class="form-floating">
                                <input type="text" class="form-control text-uppercase border border-secondary"
                                    id="bulkRemoveCodes" wire:model.defer="bulkRemoveCodes"
                                    placeholder="AL36, CA03, CC05">
                                <label for="bulkRemoveCodes">Códigos para remover</label>
                            </div>
                            @error('bulkRemoveCodes')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        <div class="col-12 col-md-4 col-lg-2">
                            <button type="button" class="btn btn-outline-danger w-100" wire:click="bulkDelete">
                                <i class="ri-delete-bin-line me-1"></i>Remover
                            </button>
                        </div>

                        <div class="col-12 col-md-8 col-lg-4">
                            <div class="form-floating">
                                <input type="search" class="form-control border border-secondary" id="searchCodes"
                                    wire:model.debounce.400ms="search" placeholder="Buscar código">
                                <label for="searchCodes">Buscar código</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header table-title-hero d-flex justify-content-between align-items-center">
                <h5 class="card-title my-0">CÓDIGOS CONFIGURADOS</h5>
            </div>

            <div class="table-scroll-shell">
                <table class="table table-sm table-striped table-condensed mb-0">
                    <thead class="table-dark">
                        <tr class="align-middle text-center sticky-top" style="top: 0;">
                            <th>Código</th>
                            <th>Classificação</th>
                            <th>Ativo</th>
                            <th style="width: 300px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($codes as $item)
                            <tr class="align-middle text-center" wire:key="protest-measure-code-{{ $item->id }}">
                                <td class="fw-semibold">{{ $item->code }}</td>
                                <td>
                                    @if($item->classification === 'construction')
                                        <span class="badge bg-primary">Construção</span>
                                    @else
                                        <span class="badge bg-warning text-dark">CIP</span>
                                    @endif
                                </td>
                                <td>{{ $item->active ? 'Sim' : 'Não' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary"
                                            wire:click="toggleClassification({{ $item->id }})">
                                            Alternar tipo
                                        </button>
                                        <button type="button" class="btn btn-outline-warning"
                                            wire:click="toggleActive({{ $item->id }})">
                                            {{ $item->active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                        <button type="button" class="btn btn-outline-danger"
                                            wire:click="delete({{ $item->id }})">
                                            Remover
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhum código cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        .monitoring-page {
            --mp-bg: #f6f7fb;
            --mp-surface: #ffffff;
            --mp-muted: #6b7280;
            --mp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--mp-bg);
            padding: 1.5rem 0;
        }

        .monitoring-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .monitoring-header h2 { font-weight: 700; margin: 0; }
        .monitoring-header .meta { color: rgba(248, 250, 252, 0.75); font-size: .95rem; }

        .filters-grid .filter-card {
            background-color: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: .9rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .table-card {
            background: var(--mp-surface);
            border: 1px solid var(--mp-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: visible;
        }

        .table-scroll-shell {
            overflow: auto;
            position: relative;
        }

        .table-card .card-header {
            padding: .75rem 1.25rem;
            border-bottom: 0;
            margin: 0;
            border-radius: 1rem 1rem 0 0;
        }

        .table-card .card-header.table-title-hero {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
        }

        .table-card .card-header .card-title { padding-left: .15rem; }
        .table-card .table { margin-top: 0; margin-bottom: 0; }
        .table-card .table thead th { border-top: 0; }
    </style>
@endpush
