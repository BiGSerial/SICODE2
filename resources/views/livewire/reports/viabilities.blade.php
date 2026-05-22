@php
    use App\Custom\Viabilitiesstatus;
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
                <h4 class="mb-1">Relatório de Viabilidade</h4>
                <div class="small text-white-50">Consulta com filtros de período, busca rápida e busca em massa.</div>
            </div>
            <button class="btn btn-light btn-sm" wire:click.prevent="Export">
                <i class="ri-file-excel-2-line me-1"></i> Exportar
            </button>
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <label for="search" class="form-label small mb-1">Buscar</label>
                    <div class="input-group input-group-sm">
                        <input id="search" type="text" class="form-control" wire:model.bounce.800ms="search"
                            placeholder="Nota, material ou ordem">
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                            data-bs-target="#multiSearchViabModal" title="Busca em massa">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>
                    @if (count($multi_search_terms ?? []))
                        <small class="text-primary">Busca em massa ativa: {{ count($multi_search_terms ?? []) }}</small>
                    @endif
                </div>

                <div class="col-md-2">
                    <label class="form-label small mb-1">Coluna Referência</label>
                    <select class="form-select form-select-sm" wire:model="column">
                        <option value="">Por intervalo</option>
                        <option value="completed_at">Completado em</option>
                        <option value="hired_at">Contratado em</option>
                        <option value="sended_at">Enviado em</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small mb-1">A partir de</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_init">
                </div>

                <div class="col-md-2">
                    <label class="form-label small mb-1">Até</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_end" min="{{ $dt_init }}">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-danger btn-sm" wire:click="clearFilters" type="button">
                        Limpar filtros
                    </button>
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
                            <th>Contratante</th>
                            <th>Empresa</th>
                            <th>Ordem</th>
                            <th>Nota</th>
                            <th>Contratado</th>
                            <th>Enviado em</th>
                            <th>Contratado em</th>
                            <th>Viabilizado em</th>
                            <th>Completado em</th>
                            <th>Responsável</th>
                            <th>Empreiteira</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $list)
                            <tr>
                                <td>{{ $list->User->name }}</td>
                                <td>{{ $list->User->Employee->Contract->Company->name }}</td>
                                <td>
                                    @if ($list->Orders->count())
                                        @foreach ($list->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @elseif ($list->Note->Orders->isNotEmpty())
                                        @foreach ($list->Note->Orders->filter(function ($order) {
                                            return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
                                        }) as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @endif
                                </td>
                                <td>{{ $list->Note->note }}</td>
                                <td>{{ $list->hired ? 'SIM' : 'NÃO' }}</td>
                                <td>{{ $list->sended_at ? date('d/m/Y', strToTime($list->sended_at)) : '---' }}</td>
                                <td>{{ $list->hired_at ? date('d/m/Y', strToTime($list->hired_at)) : '---' }}</td>
                                <td>{{ $list->returned_at ? date('d/m/Y', strToTime($list->returned_at)) : '---' }}</td>
                                <td>{{ $list->completed_at ? date('d/m/Y', strToTime($list->completed_at)) : '---' }}</td>
                                <td>{{ $list->Engineer->name }}</td>
                                <td>{{ $list->Company->name }}</td>
                                <td>
                                    <span class="badge {{ Viabilitiesstatus::status($list->status)->colorbg }}">
                                        {{ Viabilitiesstatus::status($list->status)->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">Nenhum registro encontrado com os filtros atuais.</td>
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

    <div wire:ignore.self class="modal fade" id="multiSearchViabModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title">Busca em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="multi_search_input" class="form-label">Cole múltiplas notas/ordens (separadas por espaço, vírgula, ; ou quebra de linha)</label>
                    <textarea id="multi_search_input" rows="6" class="form-control" wire:model.defer="multi_search_input"
                        placeholder="Ex: 30001234&#10;30001235&#10;450009999"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearMultiSearch">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click="applyMultiSearch" data-bs-dismiss="modal">
                        Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
