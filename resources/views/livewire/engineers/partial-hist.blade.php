@php
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        .partial-history-page {
            --ph-bg: #f6f7fb;
            --ph-surface: #ffffff;
            --ph-ink: #1f2933;
            --ph-muted: #6b7280;
            --ph-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--ph-bg);
            padding: 1.5rem 0;
        }

        .partial-history-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .partial-history-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .partial-history-meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .partial-history-filter,
        .partial-history-summary,
        .partial-history-table-card {
            background: var(--ph-surface);
            border: 1px solid var(--ph-border);
            border-radius: 0.9rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .partial-history-filter {
            padding: 1rem 1.25rem;
            height: 100%;
        }

        .partial-history-filter h6,
        .partial-history-summary .label {
            color: var(--ph-muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .partial-history-filter .form-control,
        .partial-history-filter .form-select {
            min-height: 44px;
        }

        .partial-history-summary {
            padding: 0.9rem 1.25rem;
        }

        .partial-history-summary .value {
            color: var(--ph-ink);
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .partial-history-table-card {
            border-radius: 1rem;
            overflow: hidden;
        }

        .partial-history-table-card .table thead th {
            background: #0f766e;
            color: #ffffff;
            font-size: 0.74rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .partial-history-table-card .table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .partial-history-modal textarea {
            min-height: 260px;
        }

        @media (max-width: 991px) {
            .partial-history-header {
                padding: 1.25rem;
            }
        }
    </style>
@endpush

<div class="partial-history-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="partial-history-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>HISTÓRICO DE INFORMES PARCIAIS</h2>
                <div class="partial-history-meta">Consulta por período, empreiteira, status e busca em massa.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-sm text-dark" wire:click.prevent="exportToExcel"
                    wire:loading.attr="disabled" wire:target="exportToExcel">
                    <span wire:loading.remove wire:target="exportToExcel">
                        <i class="ri-file-excel-2-line me-1"></i> Exportar
                    </span>
                    <span wire:loading wire:target="exportToExcel">Enviando...</span>
                </button>
                <button type="button" class="btn btn-outline-light btn-sm" wire:click="clearLocalFilters">
                    <i class="ri-filter-off-line me-1"></i> Limpar
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-4">
                <div class="partial-history-filter">
                    <h6>Pesquisa</h6>
                    <div class="input-group">
                        <input type="text" class="form-control border border-secondary" id="searchText"
                            placeholder="Nota, OV, ordem ou DR" wire:model.defer="search">
                        <button type="button" class="btn btn-primary" wire:click.prevent="pesquisar">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#partialBulkSearchModal">
                            <i class="ri-list-check-2 me-1"></i> Busca em massa
                        </button>
                        @if ($bulk_search)
                            <button type="button" class="btn btn-link btn-sm p-0" wire:click="clearBulkSearch">
                                limpar busca em massa
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="partial-history-filter">
                    <h6>Empreiteira</h6>
                    <select class="form-select border border-secondary" id="companyFilter" wire:model="company_id">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <div class="partial-history-filter">
                    <h6>Status</h6>
                    <select class="form-select border border-secondary" id="statusFilter" wire:model="status">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="partial-history-filter">
                    <h6>Período</h6>
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="month" class="form-control border border-secondary" id="monthFilter"
                                wire:model="month">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control border border-secondary" id="startDate"
                                wire:model.defer="dt_in">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control border border-secondary" id="endDate"
                                wire:model.defer="dt_out">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="partial-history-summary">
                    <div class="label">Registros encontrados</div>
                    <div class="value">{{ $lists->total() }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="partial-history-summary">
                    <div class="label">Busca em massa</div>
                    <div class="value">{{ $bulkSearchCount }} termo(s)</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="partial-history-summary">
                    <div class="label">Rubrica</div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partial', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                        @livewire('components.filter.remove-all', ['group_filter' => 'partial'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>

    <div class="modal fade partial-history-modal" id="partialBulkSearchModal" tabindex="-1" aria-labelledby="partialBulkSearchModalLabel"
        aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="partialBulkSearchModalLabel">Busca em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label for="bulkSearchText" class="form-label">Notas, OVs, ordens ou DRs</label>
                    <textarea class="form-control" id="bulkSearchText" rows="10"
                        placeholder="Cole um item por linha ou separe por vírgula, ponto e vírgula ou espaço"
                        wire:model.defer="bulk_search"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger" wire:click="clearBulkSearch"
                        data-bs-dismiss="modal">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click="applyBulkSearch">
                        <i class="bi bi-search"></i> Aplicar busca
                    </button>
                </div>
            </div>
        </div>
    </div>


    @if ($lists->total() === 0)
        <div class="partial-history-table-card mt-4 p-4">
            <h4 class="text-center">SEM HISTÓRICO INFORMES PARCIAL</h4>
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small>
                Página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}
                ({{ $lists->total() }} registros)
            </small>
            <div>
                {{ $lists->links() }}
            </div>
        </div>
        <div class="partial-history-table-card">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap px-3 py-3 border-bottom">
                <div>
                    <h5 class="mb-0 fw-bold">Obras parciais informadas</h5>
                    <div class="text-muted small">Duplo clique em uma linha para abrir os detalhes.</div>
                </div>
                <select class="form-select form-select-sm border border-secondary" wire:model="perPage"
                    style="max-width: 120px;">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="250">250</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0">
                    <thead>
                        <tr class="text-center">
                            <th scope="col">Nota/OV</th>
                            <th scope="col">Ordem</th>
                            <th scope="col">Rubrica</th>
                            <th scope="col">Empreiteira</th>
                            <th scope="col">Envio</th>
                            <th scope="col">Aprovação</th>
                            <th scope="col">Fiscalização</th>
                            <th scope="col">Pagamento</th>
                            <th scope="col">Valor ADS</th>
                            <th scope="col">Status</th>
                            <th scope="col">Finalizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr class="text-center {{ $selectedRow === $list->id ? 'table-primary' : '' }}"
                                style="cursor: pointer;" data-bs-toggle="popover" data-bs-placement="left"
                                data-bs-trigger="hover" data-bs-content="duplo clique para mais detalhes"
                                wire:dblClick.prevent="$emitTo('partner.show.show-partial-info', 'show_form', {{ $list->id }})"
                                wire:click="$set('selectedRow', {{ $list->id }})">
                                <td class="fw-bold">{{ $list->Note->note }}</td>
                                <td>
                                    @if ($list->Orders)
                                        @foreach ($list->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $list->Note->rubrica }}</td>
                                @php
                                    $company = $list->Company ? $list->Company->name : 'Desconhecido';
                                    $approvalReject = $this->rejectedStageInfo($list, 'approval');
                                    $supervisionReject = $this->rejectedStageInfo($list, 'supervision');
                                    $paymentReject = $this->rejectedStageInfo($list, 'payment');
                                    $rejectedStage = $this->rejectedStage($list);
                                    $blockSupervision = $rejectedStage === 'approval';
                                    $blockPayment = in_array($rejectedStage, ['approval', 'supervision'], true);
                                @endphp
                                <td class="fw-bold text-start">{{ $company }}</td>
                                <td>{{ Carbon::parse($list->created_at)->format('d/m/Y H:i:s') }}</td>
                                <td class="@if ($approvalReject['active']) table-danger fw-bold @elseif (!$list->allow && !$list->deny) text-bg-info fw-bold @endif">
                                    @if ($approvalReject['active'])
                                        {{ $approvalReject['date'] ? Carbon::parse($approvalReject['date'])->format('d/m/Y H:i:s') : '---' }}
                                        <div class="small text-danger">{{ $approvalReject['user'] ?? '---' }}</div>
                                    @elseif (!$list->allow && !$list->deny)
                                        {{ Carbon::parse($list->created_at)->diffInDays() }}
                                    @else
                                        {{ $list->decision_at ? Carbon::parse($list->decision_at)->format('d/m/Y H:i:s') : '---' }}
                                        <div class="text-muted small">{{ $list->engineer?->name ?? '---' }}</div>
                                    @endif
                                </td>
                                <td class="@if ($supervisionReject['active']) table-danger fw-bold @elseif (!$blockSupervision && !$list->deny && $list->allow && !$list->supervision) text-bg-info fw-bold @endif">
                                    @if ($blockSupervision)
                                        ---
                                    @elseif ($supervisionReject['active'])
                                        {{ $supervisionReject['date'] ? Carbon::parse($supervisionReject['date'])->format('d/m/Y H:i:s') : '---' }}
                                        <div class="small text-danger">{{ $supervisionReject['user'] ?? '---' }}</div>
                                    @elseif (!$list->deny && $list->allow && !$list->supervision)
                                        {{ Carbon::parse($list->decision_at)->diffInDays() }}
                                    @else
                                        {{ $list->supervision_at ? Carbon::parse($list->supervision_at)->format('d/m/Y H:i:s') : '---' }}
                                        <div class="text-muted small">{{ $list->supervisor?->name ?? '---' }}</div>
                                    @endif
                                </td>
                                <td class="@if ($paymentReject['active']) table-danger fw-bold @elseif (!$blockPayment && !$list->deny && $list->supervision && !$list->payment) text-bg-info fw-bold @endif">
                                    @if ($blockPayment)
                                        ---
                                    @elseif ($paymentReject['active'])
                                        {{ $paymentReject['date'] ? Carbon::parse($paymentReject['date'])->format('d/m/Y H:i:s') : '---' }}
                                        <div class="small text-danger">{{ $paymentReject['user'] ?? '---' }}</div>
                                    @elseif (!$list->deny && $list->supervision && !$list->payment)
                                        {{ Carbon::parse($list->supervision_at)->diffInDays() }}
                                    @else
                                        {{ $list->payment_at ? Carbon::parse($list->payment_at)->format('d/m/Y H:i:s') : '---' }}
                                        <div class="text-muted small">{{ $list->payer?->name ?? '---' }}</div>
                                    @endif
                                </td>
                                <td class="fs-6 fw-bold">
                                    {{ 'R$ ' . number_format($list->value, 2, ',', '.') }}</td>
                                <td class="{{ $this->partialStatus($list)['color'] }} fs-6 fw-bold">
                                    {{ $this->partialStatus($list)['status'] }}</td>
                                <td>{{ $list->complete ? 'SIM' : '---' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <small>
                Página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}
                ({{ $lists->total() }} registros)
            </small>
            <div>
                {{ $lists->links() }}
            </div>
        </div>
    @endif

    @livewire('partner.show.show-partial-info', key('show_partial_info'))

    <script>
        window.addEventListener('hide-bulk-search-modal', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('partialBulkSearchModal'));
            if (modal) {
                modal.hide();
            }
        });

        window.addEventListener('notify', function(event) {
            const detail = event.detail || {};
            if (window.Swal) {
                Swal.fire({
                    icon: detail.type || 'info',
                    text: detail.message || 'Operação concluída.',
                    timer: 3500,
                    showConfirmButton: false
                });
            }
        });
    </script>
    </div>
</div>
