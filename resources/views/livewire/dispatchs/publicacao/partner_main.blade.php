@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
@endphp

<div class="survey-main-page publication-dispatch-page">
    @include('livewire.dispatchs.partials.list-shell-style')

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        #exemple {
            border-collapse: collapse;
            width: 100%;
        }

        #exemple th,
        #exemple td {
            padding: 8px;
            text-align: center;
            /* border: 1px solid #ddd; */
        }

        #exemple tbody tr {
            position: relative;
            transition: transform 0.5s ease, box-shadow 0.3s ease;
        }

        /* Linha elevada (sombra para parecer "flutuando") */
        #exemple tbody tr.moving {
            z-index: 10;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
    </style>

    @push('css')
        <style>
            .publication-dispatch-page .control-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.9rem;
            }

            .publication-dispatch-page .control-card {
                background: linear-gradient(160deg, #ffffff, #f8fafc);
                border: 1px solid #dbe3ef;
                border-radius: 0.9rem;
                padding: 0.85rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            }

            .publication-dispatch-page .control-card h6 {
                color: var(--sp-muted);
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                margin-bottom: 0.65rem;
                text-transform: uppercase;
            }

            .publication-dispatch-page .quick-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .publication-dispatch-page .quick-actions .btn {
                min-height: 42px;
                font-weight: 700;
            }

            .publication-dispatch-page .filters-row {
                background: #f8fafc;
                border: 1px dashed #cbd5e1;
                border-radius: 0.8rem;
                padding: 0.75rem;
            }

            .publication-dispatch-page .publication-note {
                color: #64748b;
                font-size: 0.82rem;
                font-weight: 600;
                margin-top: 0.35rem;
            }

            .publication-dispatch-page .bulk-search-modal .modal-content {
                border: 0;
                border-radius: 0.75rem;
                box-shadow: 0 22px 60px rgba(15, 23, 42, 0.28);
                overflow: hidden;
            }

            .publication-dispatch-page .bulk-search-modal .modal-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 75%);
                color: #f8fafc;
                border: 0;
                padding: 1rem 1.25rem;
            }

            .publication-dispatch-page .bulk-search-modal .modal-title {
                font-size: 1rem;
                font-weight: 700;
            }

            .publication-dispatch-page .bulk-search-modal textarea {
                min-height: 12rem;
                resize: vertical;
                border-color: #cbd5e1;
            }

            @media (min-width: 992px) {
                .publication-dispatch-page .control-grid {
                    grid-template-columns: minmax(180px, 0.9fr) minmax(260px, 1fr) minmax(280px, 1fr) minmax(320px, 1.25fr);
                }

                .publication-dispatch-page .quick-actions {
                    grid-template-columns: repeat(2, minmax(130px, 1fr));
                }
            }

            @media (min-width: 1400px) {
                .publication-dispatch-page .control-grid {
                    grid-template-columns: minmax(170px, 0.7fr) minmax(260px, 1fr) minmax(260px, 0.9fr) minmax(360px, 1.25fr);
                }
            }

            @keyframes flame {
                0% {
                    transform: scaleX(1) scaleY(1);
                }

                25% {
                    transform: scaleX(1) scaleY(0.8);
                }

                50% {
                    transform: scaleX(-1) scaleY(0.8);
                }

                75% {
                    transform: scaleX(-1) scaleY(1);
                }
            }
        </style>
    @endpush



    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="container-fluid px-3 px-lg-4">
        <div class="survey-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>LISTA PARA {{ mb_strtoupper($service->service) }}
                    @if ($contractCompanyName)
                        - {{ mb_strtoupper($contractCompanyName) }}
                    @endif
                </h2>
                <div class="survey-meta">
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="text-lg-end">
                @if ($update)
                    <div class="survey-meta">Ultima Atualizacao</div>
                    <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </div>
        </div>

        <div class="filter-shell mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="control-grid">
                    <div class="control-card">
                        <h6>Paginacao</h6>
                        <div class="form-floating">
                            <select wire:model="perPage" class="form-select border border-secondary" id="publicationPerPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                            <label for="publicationPerPage">Registros por pagina</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Busca</h6>
                        <div class="position-relative">
                            <input wire:model.bounce.2s="search" type="text"
                                class="form-control border border-secondary pe-5" id="publicationSearch"
                                placeholder="Buscar">
                            <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Tipo de Nota</h6>
                        <div class="btn-group w-100" role="group" aria-label="Tipo de nota">
                            <input type="radio" class="btn-check" name="publicationNoteType" wire:model="note_type" value="1" id="publicationNoteType1">
                            <label class="btn btn-outline-primary" for="publicationNoteType1">Nota</label>
                            <input type="radio" class="btn-check" name="publicationNoteType" wire:model="note_type" value="2" id="publicationNoteType2">
                            <label class="btn btn-outline-primary" for="publicationNoteType2">OV</label>
                            <input type="radio" class="btn-check" name="publicationNoteType" wire:model="note_type" value="" id="publicationNoteType3">
                            <label class="btn btn-outline-primary" for="publicationNoteType3">Ambos</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Acoes Rapidas</h6>
                        <div class="quick-actions">
                            <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                                wire:click.prevent="filterStatus()">
                                {{ Notestatus::status(1)->status }}
                                @if ($not_assigned)
                                    <span class="badge text-bg-success">ON</span>
                                @else
                                    <span class="badge text-bg-danger">OFF</span>
                                @endif
                            </button>

                            <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                                wire:click.prevent="btzeroform()">
                                Info BT Zero
                                @if ($btzeroform)
                                    <span class="badge text-bg-success">ON</span>
                                @else
                                    <span class="badge text-bg-danger">OFF</span>
                                @endif
                            </button>

                            <button class="btn btn-primary" wire:click.prevent='go_att_mass'>
                                <i class="ri-checkbox-multiple-fill"></i> Atribuir
                            </button>
                            <button class="btn btn-primary" wire:click.prevent='export_excel'>
                                <i class="ri-file-excel-2-line"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="filters-row d-flex flex-wrap align-items-center justify-content-end gap-2 mt-3">
                    <span class="small text-uppercase fw-semibold text-secondary me-1">Filtros adicionais</span>
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'publishing', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
                        @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'publishing', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                        @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'publishing', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                        @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'publishing', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
                        @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'publishing', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                        @livewire('components.filter.remove-all', ['group_filter' => 'publishing'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="row align-items-center g-2">
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
                <h4 class="text-center mb-0">SEM NOTAS PARA EXIBIR EM {{ $service->service }} - @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            <div class="card-header fw-bold text-bg-secondary">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg">
                        <h4 class="my-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                        <div class="publication-note">
                            Informe publicavel por OP20 liberada, independente da Fiscalizacao simultanea.
                        </div>
                    </div>
                    <div class="col-12 col-lg-auto d-flex flex-wrap gap-2 justify-content-lg-end">
                        <button class="btn btn-sm btn-primary" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-light" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="exemple" class="table table-sm table-condensed table-hover mb-0 main-table">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall">
                            </th>
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th class="align-middle text-center">Inf Digitacao</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Material</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th class="align-middle text-center">Empresa</th>
                            <th class="align-middle text-center">Município</th>
                            <th class="align-middle text-center">Data Execução</th>
                            <th class="align-middle text-center">Data Informe</th>
                            {{-- <th scope="col" class="fw-bold text-center">Retorno</th> --}}
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th class="align-middle text-center">Dt Vencimento</th>
                            @if (request()->routeIs('dispatch.partner'))<th class="fw-bold text-center">Despachado Em</th><th class="fw-bold text-center">Atribuído Em</th><th class="fw-bold text-center">Finalizado Em</th>@endif
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $block = 0;
                                $command = 0;

                                if ($production = $this->hasPublication($list)) {
                                    if ($production->confirmed) {
                                        $block = 4;
                                    } elseif (
                                        $production->completed ||
                                        ($production->Note->RamalForm &&
                                            !$production->Note->WorkForm &&
                                            $production->status == 28)
                                    ) {
                                        $block = 3;
                                    } elseif ($production->status == 1) {
                                        $block = 2;
                                    } else {
                                        $block = 1;
                                    }

                                    if ($production->confirmed) {
                                        $command = 1;
                                    }
                                }

                                if ($list->workform) {
                                    $dateForm = $list->workform->informed_at
                                        ? $list->workform->informed_at->format('d/m/Y H:i')
                                        : $list->workform->created_at->format('d/m/Y H:i');
                                    $daysForm = $list->workform->informed_at
                                        ? $list->workform->informed_at->startOfDay()->diffInDays(now()->startOfDay())
                                        : $list->workform->created_at->startOfDay()->diffInDays(now()->startOfDay());
                                } elseif ($list->ramalForm) {
                                    $dateForm = $list->ramalform->informed_at
                                        ? $list->ramalform->informed_at->format('d/m/Y H:i')
                                        : $list->ramalform->created_at->format('d/m/Y H:i');
                                    $daysForm = $list->ramalform->informed_at
                                        ? $list->ramalform->informed_at->startOfDay()->diffInDays(now()->startOfDay())
                                        : $list->ramalform->created_at->startOfDay()->diffInDays(now()->startOfDay());
                                } else {
                                    $dateForm = '---';
                                    $daysForm = '---';
                                }

                                // Cores das linhas com base no status
                                $rowClass = '';
                                if ($block == 4) {
                                    $rowClass = 'table-danger';
                                } elseif ($block == 3) {
                                    $rowClass = 'table-success';
                                } elseif ($block == 2) {
                                    $rowClass = 'table-warning';
                                } elseif ($block == 1) {
                                    $rowClass = 'table-primary';
                                }
                            @endphp



                            <tr class="align-middle text-center" id="note-{{ $list->id }}"
                                wire:key="{{ $list->id }}">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->publication_context_key ?? $list->id }}" wire:model.defer="selected"
                                        @disabled($block)>
                                </td>

                                <td class="fw-bold copy-text   @if ($list->is45) text-bg-warning @else {{ $rowClass }} @endif"
                                    data-value="{{ $list->note }}">
                                    {{ $list->note }}
                                    @if ((int) ($list->dispatch_work_report_id ?? 0) > 0)
                                        <small class="d-block text-muted mt-1">Informe #{{ $list->dispatch_work_report_id }}</small>
                                    @endif
                                    @if ($list->is45)
                                        <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                            data-bs-content="Nota com prazo de execução de 45 dias"
                                            style="z-index: 9999;" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="ri-fire-line text-danger fw-bold"
                                                style="display: inline-block; animation: flame 1s steps(1) infinite;"></i>
                                        </span>
                                    @endif
                                    <x-legal.note-demand-tags :note-id="$list->note_id ?? $list->id" :row-key="'dispatchs-publication-main-'.$list->id" />
                                </td>

                                <td class="fw-light {{ $rowClass }}">
                                    @if (!$list->WorkForm && $list->RamalForm)
                                        <i class="ri-alert-line text-danger align-middle fs-4"></i>
                                    @endif

                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    {{ $list->rubrica }}
                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    {{ $list->material }}
                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    {{ $list->numPedido }}
                                </td>

                                <td class="fw-light {{ $rowClass }}">

                                    @if ($list->WorkForm)
                                        {{ $list->WorkForm->Company->name }}
                                    @elseif ($list->RamalForm)
                                        {{ $list->RamalForm->Company->name }}
                                    @endif
                                </td>

                                <td class="fw-light {{ $rowClass }}">{{ $list->lexp }}</td>

                                <td class="fw-light {{ $rowClass }}">
                                    {{ $list->WorkForm ? date('d/m/Y', strToTime($list->WorkForm->date)) : '---' }}
                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    {{ $dateForm }}
                                    @if ($daysForm !== '---')
                                        <br><span class="badge text-bg-secondary">{{ $daysForm }} dia(s)</span>
                                    @endif
                                </td>
                                {{-- <td class="fw-light {{ $rowClass }} text-center" tabindex="0"
                                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Desenhos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($production)
                                        <span
                                            class="badge text-bg-dark">{{ $this->hasPublicationCount($list) }}</span><br>
                                        @php
                                            $name = isset($production->User->name)
                                                ? explode(' ', $production->User->name)
                                                : 'DESCONHECIDO';

                                            if (is_array($name)) {
                                                $name = $name[0] . ' ' . end($name);
                                            } else {
                                                $name = 'DESCONHECIDO';
                                            }
                                        @endphp
                                        {{ $name }}
                                    @else
                                        --
                                    @endif

                                </td> --}}

                                @if ($list->type_note != 1)
                                    <td class="fw-light {{ $rowClass }} text-center">{{ $list->nstats }} </td>
                                @else
                                    <td class="fw-light {{ $rowClass }} text-center">{{ $list->centerjob }} <span
                                            class="text-danger" style="font-size: 8px;">{{ $list->nstats }}</span>
                                    </td>
                                @endif
                                @php
                                    $daysLeft = new DaysLeft($list);
                                    $prazoClass = '';

                                    if ($daysLeft->getDaysLeft() < 0) {
                                        $prazoClass = 'text-bg-danger';
                                    } elseif ($daysLeft->getDaysLeft() > 15) {
                                        $prazoClass = 'text-bg-success';
                                    } else {
                                        $prazoClass = 'text-bg-warning';
                                    }
                                @endphp

                                <!-- Prioridade de estilo da célula 'Prazo Restante' -->
                                <td scope="col" class="text-center {{ $prazoClass }}"
                                    style="background-color: inherit;">
                                    {{ $daysLeft->getLastDate() }}
                                </td>


                                <td class="fw-bold text-center {{ $rowClass }}">
                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="get_single_note({{ json_encode($list->publication_context_key ?? $list->id) }})"></i>
                                    @else
                                        @php
                                            if ($production && $production->User) {
                                                $name = explode(' ', $production->User->name);
                                                $name = $name[0] . ' ' . end($name);
                                            } else {
                                                $name = 'SEM USUARIO';
                                            }

                                        @endphp
                                        <span style="font-size: 11px">{{ $name }}</span>
                                        @if ($command)
                                            <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                                style="cursor: pointer;"
                                                wire:click.prevent="get_single_note({{ json_encode($list->publication_context_key ?? $list->id) }})"></i>
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
    <div class="summary-bar mt-3">
        <div class="row align-items-center g-2">
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


    {{-- MODALS --}}

    <div wire:ignore.self class="modal fade bulk-search-modal" id="buscar_multi" tabindex="-1" aria-labelledby="publicationBulkSearchLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="publicationBulkSearchLabel">Buscar Multi-Notas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch"
                        wire:model.defer="advanceSearch" placeholder="Informe uma nota por linha"></textarea>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="anyStatus"
                                wire:model.defer="all_services">
                            <label class="form-check-label" for="anyStatus">
                                Qualquer Status
                            </label>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-'.$service->uuid))

    </div>

    {{-- END MODALS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('#exemple');
            if (!table) {
                return;
            }

            const headers = table.querySelectorAll('th');
            let currentSortColumn = null;
            let currentSortOrder = 'asc';

            headers.forEach((header, index) => {
                header.addEventListener('click', () => {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));

                    // Alterna a ordem de classificação
                    if (currentSortColumn === index) {
                        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSortColumn = index;
                        currentSortOrder = 'asc';
                    }

                    // Verifica se a coluna é numérica
                    const isNumericColumn = !isNaN(rows[0].cells[index].innerText);

                    // Ordena os elementos
                    const sortedRows = rows.slice().sort((rowA, rowB) => {
                        const cellA = rowA.cells[index].innerText.trim();
                        const cellB = rowB.cells[index].innerText.trim();

                        if (isNumericColumn) {
                            return currentSortOrder === 'asc' ?
                                parseFloat(cellA) - parseFloat(cellB) :
                                parseFloat(cellB) - parseFloat(cellA);
                        } else {
                            return currentSortOrder === 'asc' ?
                                cellA.localeCompare(cellB) :
                                cellB.localeCompare(cellA);
                        }
                    });

                    // Realiza a animação das linhas
                    animateRows(tbody, rows, sortedRows);
                });
            });

            /**
             * Anima as linhas com efeito de elevação e deslocamento
             */
            function animateRows(tbody, originalRows, sortedRows) {
                const originalOrder = originalRows.map(row => row.getBoundingClientRect());
                const sortedOrder = sortedRows.map(row => row.getBoundingClientRect());

                // Aplica deslocamento às linhas
                originalRows.forEach((row, index) => {
                    const offset = sortedOrder[index].top - originalOrder[index].top;
                    if (offset !== 0) {
                        row.style.transform = `translateY(${offset}px)`;
                    }
                });

                // Eleva a linha que está sendo movimentada
                const movingRow = sortedRows.find((row, index) => originalRows[index] !== row);
                if (movingRow) {
                    movingRow.classList.add('moving');
                }

                // Finaliza a animação
                setTimeout(() => {
                    originalRows.forEach(row => {
                        row.style.transform = ''; // Reseta o transform
                    });

                    // Reordena no DOM
                    sortedRows.forEach(row => {
                        tbody.appendChild(row);
                    });

                    // Remove a classe de elevação
                    if (movingRow) {
                        movingRow.classList.remove('moving');
                    }
                }, 500); // Tempo da animação
            }
        });
    </script>






</div>
