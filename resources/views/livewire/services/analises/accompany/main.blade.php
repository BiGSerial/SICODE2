@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div class="oexterno-page">
    {{-- Carrega o Loading da página --}}
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

        .filters-grid .chip-filters {
            gap: 0.5rem;
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
            border-radius: 0.2rem;
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
                <h2>{{ mb_strtoupper($service->service) }}</h2>
                <div class="meta">Acompanhamento de análises</div>
            </div>
            <div class="text-lg-end">
                @if ($service->Status->count())
                    <div class="meta">Status ativos</div>
                    <div>
                        <strong>
                            @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                ({{ $sts->value }})
                            @endforeach
                        </strong>
                    </div>
                @endif
            </div>
        </div>

        {{-- START SearchBar and Filters --}}
        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12 col-lg-8 col-xl-9">
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
                                            <option value="500">500</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.bounce.2s="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar">
                                        <label for="search">Buscar</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                            <i class="ri-checkbox-multiple-blank-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 col-xl-3 ms-lg-auto">
                        <div class="filter-card">
                            <h6>Rubrica</h6>
                            <div class="dropdown mb-3">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Rubrica
                                    @if (count($rubrica_s))
                                        <span class="badge text-bg-light">{{ count($rubrica_s) }}</span>
                                    @endif
                                </button>

                                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                                    <form wire:submit.prevent="filter_save">
                                        @if (isset($rubrica_l) && $rubrica_l->count() > 0)
                                            @foreach ($rubrica_l as $rubrica)
                                                @if ($rubrica->rubrica)
                                                    <div class="dropdown-item">
                                                        <input type="checkbox" wire:model.defer="rubrica_s"
                                                            wire:key="{{ $rubrica->rubrica }}"
                                                            value="{{ $rubrica->rubrica }}">
                                                        <label for="opcao1">{{ $rubrica->rubrica }}</label>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </form>
                                </div>
                            </div>

                            <div class="btn-group w-100">
                                <button class="btn btn-primary" wire:click.prevent="filter_save"><i
                                        class="ri-filter-fill"></i>
                                    Aplicar Filtro</button>
                                <button class="btn btn-primary" wire:click.prevent="filter_clean"><i
                                        class="ri-filter-off-fill"></i> Limpar Filtro</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        {{-- END SearchBar and Filters --}}

        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-production-tab" data-bs-toggle="tab"
                    data-bs-target="#my_production" type="button" role="tab" aria-controls="nav-home"
                    aria-selected="true" wire:click.prevent="$emit('refresh_accomany')">Produção</button>
                <button class="nav-link" id="nav-transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer"
                    type="button" role="tab" aria-controls="nav-profile" aria-selected="false"
                    wire:click.prevent="$emit('refresh_translist')">Transferências @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))</button>

            </div>
        </nav>

        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="my_production" role="tabpanel"
                aria-labelledby="nav-home-tab" tabindex="0">
                @if ($lists->count())
                    <div class="summary-bar my-3">
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
                @endif

                <div class="table-card">
                    @if (!$lists->count())
                        <div class="card-body">
                            <h4 class="text-center text-muted">VOCÊ NAO TEM TAREFA ATRIBUÍDA
                                <strong>{{ mb_strtoupper($service->service) }}</strong></h4>
                        </div>
                    @else
                        <div class="card-header fw-bold text-bg-danger d-flex flex-column flex-lg-row gap-2 align-items-lg-center justify-content-between">
                            <h4 class="fw-bold my-0">ACOMPANHAMENTO -
                                {{ mb_strtoupper($service->service) }}
                            </h4>
                            @if (count($selected))
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#bulk_finish_modal">
                                    <i class="ri-checkbox-multiple-line"></i>
                                    Encerrar selecionados ({{ count($selected) }})
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-condensed mb-0">
                                <thead class="table-dark">
                                    <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                        <th scope="col" class="fw-bold text-center">
                                            <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                                wire:click="setSelectAll()" @checked($this->checkAllSelect($lists))>
                                        </th>
                                        <th scope="col" class="fw-bold">Note</th>
                                        <th scope="col" class="fw-bold">Criado Em</th>
                                        <th scope="col" class="fw-bold">numPedido</th>
                                        <th scope="col" class="fw-bold">Rubrica</th>
                                        <th scope="col" class="fw-bold">Municipio</th>
                                        <th scope="col" class="fw-bold">Zona</th>
                                        <th scope="col" class="fw-bold">Grp2</th>
                                        <th scope="col" class="fw-bold">Descrição</th>
                                        <th scope="col" class="fw-bold">Dias Atribuido</th>
                                        <th scope="col" class="fw-bold">Dias da Nota</th>
                                        <th scope="col" class="fw-bold">Status</th>
                                        <th scope="col" class="fw-bold"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lists->sortBy([['priority', 'desc'], ['Note.days_left', 'asc']]) as $list)
                                        <tr class="align-middle @if ($list->block) table-primary @endif">
                                            <td class="text-center">
                                                <input class="form-check-input border border-1 border-primary"
                                                    type="checkbox" value="{{ $list->id }}"
                                                    wire:model.defer="selected">
                                            </td>
                                            <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                                                {{ $list->Note->note }}
                                                <span class="copy-text" data-value="{{ $list->Note->note }}"
                                                    style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                                    data-bs-content="Copiar Número da Nota"> <i
                                                        class="ri-file-copy-line"></i></span>

                                                @if ($list->priority)
                                                    <i class="ri-alert-fill align-middle"
                                                        wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                        style="cursor: pointer;" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="top" data-bs-title="Exibir Prioridade"
                                                        data-bs-content="Clique para visualizar a informação da prioridade desta nota/ov."></i>
                                                @endif
                                            </td>
                                            <td class="fw-light">
                                                {{ date('d/m/Y', strToTime($list->Note->dt_created)) }}</td>
                                            <td class="fw-light">{{ $list->Note->numPedido }}</td>
                                            <td class="fw-light">{{ $list->Note->rubrica }}</td>
                                            <td class="fw-light">{{ $list->Note->lexp }}</td>
                                            <td class="fw-light">{{ $list->Note->group1 }}</td>
                                            <td class="fw-light">{{ $list->Note->group2 }}</td>
                                            <td class="fw-light">{{ $list->Note->material }}</td>
                                            <td class="fw-light">
                                                {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                                            </td>
                                            <td scope="col" class="text-center
                                        @if ($list->Note->days_left < 0) text-bg-secondary
                                        @elseif($list->Note->days_left >= 0 && $list->Note->days_left < 6)
                                        table-danger
                                        @elseif($list->Note->days_left >= 6 && $list->Note->days_left < 10)
                                            table-warning
                                        @else
                                            table-success @endif
                                    "
                                                tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-placement="top" data-bs-title="Prazo Real"
                                                data-bs-content="
                                <p>Os prazos contados já foram expurgado os tempos em status não contabilizáveis.</p>
                                <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                                <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                                <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                                <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                                ">
                                                {{ 30 - $list->Note->days_left }}
                                            </td>
                                            <td class="fw-light text-center">

                                                <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                                    wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                                    style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                            </td>
                                            <td class="fw-bold fs-5">
                                                @if (!$list->block)
                                                    @if (!$list->completed)
                                                        <span class="d-inline-block" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Iniciar.">
                                                            <i class="ri-play-circle-line m-0 align-middle text-success"
                                                                style="cursor: pointer;"
                                                                wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})"></i>
                                                        </span>
                                                        <span class="d-inline-block" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Transferir.">
                                                            <i class="ri-exchange-fill m-0 align-middle text-primary"
                                                                style="cursor: pointer;"
                                                                wire:click.prevent="goTransferProd({{ $list->id }})"></i>
                                                        </span>
                                                    @endif
                                                @endif
                                            </td>


                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($lists->count())
                    <div class="summary-bar my-3">
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
                @endif
            </div>


            <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="nav-profile-tab"
                tabindex="0">
                @livewire('components.transprod.translist', ['service' => $service->id])
            </div>
        </div>

        <!-- Modal -->
        <div wire:ignore.self class="modal fade" id="analise_form" data-bs-backdrop="static"
            data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
                <div class="modal-content h-100">
                    <div class="modal-header text-bg-success">
                        <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                            {{ mb_strtoupper($service->service) }}
                        </h1>
                    </div>
                    <div class="modal-body">
                        @livewire('services.analises.forms.analise', key('analise-form'))
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div wire:ignore.self class="modal fade" id="pause_note" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content h-100">
                    <div class="modal-header text-bg-warning">
                        <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                            PARAR {{ mb_strtoupper($service->service) }}
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @livewire('components.pausenote.pausenote')
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL COMPLEMENTS TRANSFER NOTE --}}
        @livewire('components.transprod.transprod', key('Transfer_production'))
        @livewire('components.status.show-status', key('show_status_note'))

        <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content edp-bg-stategrey-50">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        Buscar Multi-Notas
                    </div>
                    <div>
                        <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                            wire:model.defer="advanceSearch"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="bulk_finish_modal" tabindex="-1"
            aria-labelledby="bulkFinishModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-bg-danger">
                        <h5 class="modal-title" id="bulkFinishModalLabel">Encerramento em massa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    Você selecionou <strong>{{ count($selected) }}</strong> registro(s) para encerrar.
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="bulkMmgd" wire:model.defer="bulkMmgd">
                                        <option value="" selected>Selecione</option>
                                        <option value="SIM">SIM</option>
                                        <option value="NAO">NÃO</option>
                                    </select>
                                    <label for="bulkMmgd">MMGD?</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="bulkIs45" wire:model.defer="bulkIs45">
                                        <option value="" selected>Selecione</option>
                                        <option value="1">SIM</option>
                                        <option value="0">NÃO</option>
                                    </select>
                                    <label for="bulkIs45">Art.90 (45 dias)?</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="bulkConclusion" wire:model.defer="bulkConclusion">
                                        <option value="" selected>Selecione</option>
                                        <option value="ISR - LIBERADO">ISR - LIBERADO</option>
                                        <option value="ENVIADO A CAMPO">ENVIADO A CAMPO</option>
                                        <option value="ENVIADO AO DESENHO">ENVIADO AO DESENHO</option>
                                        <option value="ENVIADO CARTA AO CLIENTE">ENVIADO CARTA AO CLIENTE</option>
                                        <option value="ENVIADO RESPOSTA EMPRESA">ENVIADO RESPOSTA EMPRESA</option>
                                        <option value="ENVIADO PARA O STATUS 21">ENVIADO PARA O STATUS 21</option>
                                    </select>
                                    <label for="bulkConclusion">Conclusão</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="bulkInfo" style="height: 140px"
                                        wire:model.defer="bulkInfo"></textarea>
                                    <label for="bulkInfo">Informações</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" wire:click.prevent="confirmBulkClose">
                            Encerrar em massa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div wire:init="checkOpen"></div>
    </div>
</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy',
                    `Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        window.addEventListener("showModal2", function(e) {
            alert('Funciona')
            const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
            myModal.show();
        })
    </script>
@endpush
