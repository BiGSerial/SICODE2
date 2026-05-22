@php
    use Carbon\CarbonInterval;
@endphp

<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-bg: #f7f8fb;
                --ri-border: #e5e7eb;
                background: radial-gradient(circle at 12% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 15%, #ecfeff, transparent 35%),
                    var(--ri-bg);
                padding: 1.5rem 0;
                font-family: var(--bs-body-font-family, var(--bs-font-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif));
            }

            .ri-page,
            .ri-page * {
                font-family: var(--bs-body-font-family, var(--bs-font-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif)) !important;
            }

            .ri-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.3rem 1.6rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1rem;
            }

            .panel {
                background: #fff;
                border: 1px solid var(--ri-border);
                border-radius: 1rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .table thead th {
                font-size: 0.74rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                white-space: nowrap;
            }
        </style>
    @endpush

    <div class="container-fluid">
        <div class="ri-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div>
                <h4 class="mb-1">Relatório de Produção</h4>
                <div class="small text-white-50">Painel com filtros por serviço, período e busca múltipla.</div>
            </div>
            <button class="btn btn-light btn-sm" wire:click.prevent="Export">
                <i class="ri-file-excel-2-line me-1"></i> Exportar
            </button>
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <label for="serviceSelect" class="form-label small mb-1">Serviços</label>
                    <select class="form-select form-select-sm border border-secondary" wire:model="service" multiple id="serviceSelect" size="8">
                        @if (count($service_list))
                            @foreach ($service_list as $list)
                                <option value="{{ $list->service_id }}">{{ $list->Service->service }}</option>
                            @endforeach
                        @endif
                    </select>
                    <small class="text-muted">Use `Ctrl` para selecionar múltiplos.</small>
                </div>

                <div class="col-md-9">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="search" class="form-label small mb-1">Buscar</label>
                            <div class="input-group input-group-sm">
                                <input wire:model.bounce.2s="search" type="text" class="form-control border border-secondary"
                                    id="search" placeholder="Nota, material, grupo...">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#buscar_multi" type="button" title="Busca múltipla">
                                    <i class="ri-file-copy-line"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="monthYear" class="form-label small mb-1">Mês Referência</label>
                            <input type="month" class="form-control form-control-sm border border-secondary" id="monthYear"
                                min="{{ $month_list->oldest }}" max="{{ $month_list->newest }}" wire:model="monthYear">
                        </div>

                        @if (!$monthYear)
                            <div class="col-md-2">
                                <label for="dt_init" class="form-label small mb-1">A partir de</label>
                                <input type="date" class="form-control form-control-sm border border-secondary" id="dt_init" wire:model="dt_init">
                            </div>

                            <div class="col-md-2">
                                <label for="dt_end" class="form-label small mb-1">Até</label>
                                <input type="date" class="form-control form-control-sm border border-secondary" id="dt_end" wire:model="dt_end" min="{{ $dt_init }}">
                            </div>
                        @endif

                        @if (!Auth()->User()->contract)
                            <div class="col-md-4">
                                <label for="companySelect" class="form-label small mb-1">Empresa</label>
                                <select class="form-select form-select-sm border border-secondary" wire:model="company" id="companySelect">
                                    <option value="" selected>Selecione a Empresa</option>
                                    @if ($company_list)
                                        @foreach ($company_list as $company)
                                            <option value="{{ $company->company_id }}">{{ explode(' ', $company->Company->name)[0] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        @endif

                        <div class="col-md-8 d-flex align-items-end gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input border-secondary" type="checkbox" id="complete" wire:model="complete">
                                <label class="form-check-label" for="complete">Incluir em Aberto</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input border-secondary" type="checkbox" id="d5" wire:model="d5">
                                <label class="form-check-label" for="d5">Incluir (RI)</label>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" wire:click="cleanAll" type="button">Limpar filtros</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="small text-muted">
                    Exibindo {{ $lists->firstItem() ?? 0 }} até {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }} registros.
                </div>
                <div>
                    {{ $lists->links() }}
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Company</th>
                            <th>Serviço</th>
                            <th>Nota</th>
                            <th>DOE</th>
                            <th>Grp2</th>
                            <th>Grp5</th>
                            <th>Material</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Parado</th>
                            <th>Postes</th>
                            <th>D5</th>
                            <th>Situação</th>
                            <th>Conclusão</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $list)
                            <tr>
                                <td>{{ isset($list->User->name) ? $list->User->name : 'Desconhecido' }}</td>
                                <td>{{ explode(' ', $list->Company->name)[0] }}</td>
                                <td>{{ $list->Service->service }}</td>
                                <td>{{ $list->Note->note }}</td>
                                <td>{{ $list->Note->doe ? 'SIM' : 'NÃO' }}</td>
                                <td>{{ $list->Note->group2 }}</td>
                                <td>{{ $list->Note->group5 }}</td>
                                <td>{{ $list->Note->material }}</td>
                                <td>{{ $list->att_at ? date('d/m/Y H:i:s', strToTime($list->att_at)) : '-' }}</td>
                                <td>{{ $list->completed_at ? date('d/m/Y H:i:s', strToTime($list->completed_at)) : '-' }}</td>
                                <td>{{ $list->stopped ? CarbonInterval::seconds($list->stopped)->cascade()->forHumans(['short' => true]) : '-' }}</td>
                                <td>
                                    @if ($list->postes_u)
                                        @if ($list->eo + $list->iproject != 0)
                                            {{ ($list->eo + $list->iproject) * $list->postes_u }}
                                        @else
                                            {{ $list->postes_u }}
                                        @endif
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>{{ $list->d5 ? 'SIM' : 'NÃO' }}</td>
                                <td>{{ $list->confirmed ? 'Contabilizado' : 'Não Contabilizado' }}</td>
                                <td>
                                    <span class="fw-bold" style="font-size: 10px">{{ $list->Analise ? $list->Analise->conclusion : '' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center text-muted">Nenhum registro encontrado com os filtros atuais.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $lists->links() }}
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title">Buscar Multi-Notas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">Aplicar</button>
                </div>
            </div>
        </div>
    </div>
</div>
