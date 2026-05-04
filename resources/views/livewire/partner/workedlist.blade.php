@php
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        .work-informs-page {
            --surface: #fff;
            --muted: #64748b;
            --border: #dbe2ea;
            --ink: #0f172a;
            --primary: #0f766e;
            --danger: #b91c1c;
            --warning: #b45309;
            background: #f4f7fb;
            border-radius: 14px;
            padding: 1rem;
        }

        .work-informs-page .toolbar,
        .work-informs-page .list-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07);
        }

        .work-informs-page .page-title {
            color: var(--ink);
            font-weight: 700;
            margin: 0;
        }

        .work-informs-page .page-subtitle {
            color: var(--muted);
            font-size: .86rem;
        }

        .work-informs-page .table thead th {
            color: #334155;
            background: #f8fafc;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .work-informs-page .table td {
            vertical-align: middle;
            font-size: .86rem;
        }

        .work-informs-page .row-canceled {
            color: #64748b;
            background: #f8fafc;
        }

        .work-informs-page .row-rejected {
            background: #fff7ed;
        }

        .work-informs-page .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
            border-radius: 999px;
            padding: .28rem .55rem;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
    </style>
@endpush

<div class="work-informs-page">
    <x-show-loading />

    <div class="toolbar mb-3">
        <div class="p-3 border-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <h4 class="page-title">Obras informadas</h4>
                    <div class="page-subtitle">Todos os informes, incluindo rejeitados e cancelados.</div>
                </div>
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-primary" wire:click.prevent="exportToExcel">
                        <i class="ri-file-excel-2-line align-middle"></i> Exportar
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3">
            <div class="row align-items-end g-2">
                <div class="col-12 col-sm-4 col-lg-1">
                    <label for="perPage" class="form-label mb-1">Registros</label>
                    <select name="perPage" id="perPage" class="form-select" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-12 col-sm-8 col-lg-4">
                    <label for="search" class="form-label mb-1">Buscar</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search"
                            placeholder="Nota, OV ou ordem" wire:model.debounce.2s="search">
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                            data-bs-target="#workedListMultiSearchModal" data-bs-placement="top"
                            data-bs-title="Buscar múltiplos registros">
                            <i class="ri-file-copy-line align-middle"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="month" class="form-label mb-1">Mês</label>
                    <input type="month" id="month" class="form-control" wire:model="month"
                        max="{{ now()->format('Y-m') }}" min="2023-05">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="date_in" class="form-label mb-1">Data inicial</label>
                    <input type="date" id="date_in" class="form-control" wire:model="date_in" min="2023-05-01">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="date_out" class="form-label mb-1">Data final</label>
                    <input type="date" id="date_out" class="form-control" wire:model="date_out" min="2023-05-01">
                </div>

                <div class="col-12 col-sm-6 col-lg-1">
                    <button class="btn btn-outline-danger w-100" wire:click.prevent="cleanAll()"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Limpar busca">
                        <i class="ri-find-replace-line fs-5 align-middle"></i>
                    </button>
                </div>

                <div class="col-12 d-flex flex-wrap justify-content-end gap-2 pt-2">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner_forms', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'partner_forms', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'partner_forms', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner_forms'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>

    @if (!$lists->count())
        <div class="list-panel text-center my-4 p-4">
            <h5 class="mb-1">Nenhuma atividade encontrada</h5>
            <div class="text-muted">Revise os filtros aplicados.</div>
        </div>
    @else
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mb-2">
            <div>{{ $lists->links() }}</div>
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
            </div>
        </div>

        <div class="list-panel">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Status</th>
                            <th class="text-center">Nota/OV</th>
                            <th class="text-center">Ordens</th>
                            <th class="text-center">Rubrica</th>
                            <th class="text-center">Arquivos</th>
                            <th class="text-center">Equip.</th>
                            <th class="text-center">Alteração</th>
                            <th class="text-center">Equipe WPA</th>
                            <th class="text-center">Responsável</th>
                            <th class="text-center">Conclusão Informada</th>
                            <th class="text-center">ADS Entregue</th>
                            @can('engineer')
                                <th class="text-center">Empreiteira</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                if ($list->canceled) {
                                    $statusLabel = 'Cancelado';
                                    $statusClass = 'text-bg-secondary';
                                    $rowClass = 'row-canceled';
                                } elseif ($list->rejected) {
                                    $statusLabel = 'Rejeitado';
                                    $statusClass = 'text-bg-warning';
                                    $rowClass = 'row-rejected';
                                } else {
                                    $statusLabel = 'Informado';
                                    $statusClass = 'text-bg-success';
                                    $rowClass = '';
                                }
                            @endphp
                            <tr wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $list }})"
                                wire:key="work-report-{{ $list->id }}" class="{{ $rowClass }}">
                                <td class="text-center">
                                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ $list->Note->note }}</td>
                                <td class="text-center">
                                    @forelse ($list->Orders as $order)
                                        <div>{{ $order->ordem }}</div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td class="text-center">{{ $list->Note->rubrica }}</td>
                                <td class="text-center">
                                    <x-files.select-download-list :files="$list->Note->Files" />
                                </td>
                                <td class="text-center">
                                    @if ($list->Equipment->count())
                                        <span class="badge text-bg-dark">{{ $list->Equipment->count() }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $list->changes ? 'SIM' : 'NÃO' }}</td>
                                <td class="text-center">{{ $list->team ?: 'Desconhecido' }}</td>
                                <td class="text-center">{{ $list->responsible ?: 'Desconhecido' }}</td>
                                <td class="text-center">
                                    {{ $list->informed_at ? $list->informed_at->format('d/m/Y H:i') : 'Desconhecido' }}
                                </td>
                                <td class="text-center">
                                    @if ($list->Adsform)
                                        {{ ($list->Adsform->tacit ? $list->Adsform->tacit_delivered_at : $list->Adsform->created_at)?->format('d/m/Y H:i') ?? '-' }}
                                    @elseif ($list->Note->OldAds->isNotEmpty())
                                        {{ $list->Note->OldAds->last()->date?->format('d/m/Y H:i') ?? '-' }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @can('engineer')
                                    <td class="text-center">{{ $list->Company?->name }}</td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mt-3">
            <div>{{ $lists->links() }}</div>
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
            </div>
        </div>
    @endif

    @livewire('partner.show.show-work-form', key('FormModdalShow'))

    <div class="modal fade" id="workedListMultiSearchModal" tabindex="-1"
        aria-labelledby="workedListMultiSearchModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header edp-bg-violeta-100 text-white">
                    <h5 class="modal-title" id="workedListMultiSearchModalLabel">Buscar múltiplos registros</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label for="multiSearch" class="form-label">Notas, OVs ou ordens</label>
                    <textarea id="multiSearch" class="form-control" rows="8"
                        placeholder="Cole um registro por linha, ou separe por vírgula, ponto e vírgula ou espaço"
                        wire:model.defer="multiSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="applyMultiSearch"
                        data-bs-dismiss="modal">
                        <i class="ri-search-line align-middle"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:load', function() {
            const dateIn = document.getElementById('date_in');
            const dateOut = document.getElementById('date_out');

            if (!dateIn || !dateOut) {
                return;
            }

            dateIn.addEventListener('change', function() {
                dateOut.min = dateIn.value;
            });

            if (dateIn.value) {
                dateOut.min = dateIn.value;
            }

            dateIn.addEventListener('keydown', function(e) {
                e.preventDefault();
            });

            dateOut.addEventListener('keydown', function(e) {
                e.preventDefault();
            });
        });
    </script>
</div>
