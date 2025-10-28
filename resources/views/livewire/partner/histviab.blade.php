@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        .item {
            animation: slideIn 0.5s forwards;
            opacity: 0;
        }

        .item.hidden {
            animation: slideOut 0.5s forwards;
        }

        .detail-item {
            opacity: 0;
            animation: growDown 0.5s forwards;
            transform-origin: top;
        }

        @keyframes growDown {
            from {

                transform: scaleY(0);
                /* Escala vertical inicial: 0 */
            }

            to {

                transform: scaleY(1);
                /* Escala vertical final: 1 (sem mudança de tamanho) */
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateX(100%);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .blink {
            animation: blink 2s infinite;
        }
    </style>
@endpush

<div>
    <x-show-loading />

    <div class="d-flex flex-column mb-3">

        <!-- Linha 1: Busca, Tipo de Nota e Data Selection -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">

            <!-- Campo de busca com botão e tooltip -->
            <div class="col-md-4">
                <div class="input-group  me-3 mb-2">
                    <input type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar"
                        wire:model.debounce.1s="search">
                    <span data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top"
                        data-bs-content="Multinotas">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                            data-bs-target="#modal_multi_notas" title="Multinotas">
                            <i class="ri-checkbox-multiple-blank-fill"></i>
                        </button>

                        @if ($multinotas)
                            <button class="btn btn-outline-danger" type="button" wire:click="$set('multinotas', [])"
                                title="Limpar Pesquisa">
                                <i class="ri-filter-off-line"></i>
                            </button>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Botões do tipo radio para seleção individual -->
            <div class="btn-group me-3 mb-2" role="group" aria-label="Seleção de Opções">
                <input type="radio" class="btn-check" name="selecao" id="nota" autocomplete="off"
                    wire:model="typeNote" value="1">
                <label class="btn btn-outline-primary" for="nota">Nota</label>

                <input type="radio" class="btn-check" name="selecao" id="ov" autocomplete="off"
                    wire:model="typeNote" value="2">
                <label class="btn btn-outline-primary" for="ov">Ov</label>

                <input type="radio" class="btn-check" name="selecao" id="ambas" autocomplete="off"
                    wire:model="typeNote" value="">
                <label class="btn btn-outline-primary" for="ambas">Ambas</label>
            </div>

            <!-- Date Selection (Month/Year, Start Date, End Date) -->
            <div class="d-flex flex-wrap align-items-center">

                <!-- Select Mês/Ano -->
                <div class="me-3 mb-2">
                    <label for="month" class="form-label visually-hidden">Mês/Ano</label>
                    <input type="month" class="form-control" id="month" wire:model="month" min="2023-06"
                        max="{{ date('Y-m') }}">
                </div>

                <!-- Data Inicial -->
                <div class="me-3 mb-2">
                    <label for="date_in" class="form-label visually-hidden">Data Inicial</label>
                    <input type="date" class="form-control" id="date_in" wire:model="date_in">
                </div>

                <!-- Data Final -->
                <div class="me-3 mb-2">
                    <label for="date_out" class="form-label visually-hidden">Data Final</label>
                    <input type="date" class="form-control" id="date_out" wire:model="date_out">
                </div>

                <div class="me-3 mb-2">
                    <label for="dateBy" class="form-label visually-hidden">Coluna a Filtrar</label>
                    <select name="dateBy" id="dateBy" class="form-select" wire:model="dateBy"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Data por Coluna">
                        <option value="sended_at">Recebido</option>
                        <option value="returned_at">Viabilizado</option>
                        <option value="completed_at">Completado</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Linha 2: Filtros -->
        <div class="d-flex flex-wrap align-items-center justify-content-end">
            <div class="btn-group" role="group" aria-label="Ações">

                @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'analises', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'analises', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'analises', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
                @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'analises', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                @livewire('components.filter.remove-all', ['group_filter' => 'analises'], key('removeAll'))
            </div>
        </div>

    </div>


    {{-- <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-2">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.2s="search">
                </div>

                <div class="col-1">
                    <input type="date" id="date_in" class="form-control border border-secondary"
                        wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Inicial">
                </div>

                <div class="col-1">
                    <input type="date" id="date_out" class="form-control border border-secondary"
                        wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Final">
                </div>

                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary"
                        wire:model="dateBy" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data por Coluna">
                        <option value="sended_at">Recebido</option>
                        <option value="returned_at">Viabilizado</option>
                        <option value="completed_at">Completado</option>
                    </select>
                </div>
                <div class='col align-middle'><button class="btn btn-danger btn-sm align-middle"
                        wire:click.prevent='cleanAll()' data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Limpar Busca por Datas"><i class="ri-find-replace-line fs-5"></i></button></div>
                <div class="col-5 d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner_hist', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner_hist'], key('removeAll'))
                </div>
            </div>
        </div>
    </div> --}}
    {{-- END SearchBar and Filters --}}

    {{-- START LIST --}}
    @if (!$lists->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
        </div>
    @endif

    @if ($lists->count())
        {{-- Paginador --}}
        <div class="row mt-3">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>
        </div>
        {{-- FIM Paginador --}}
        <div class="card mb-2 edp-bg-gray">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                <div class="row">
                    <div class="col">
                        <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">HISTÓRICO DE VIABILIDADE</h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">

                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line align-middle"></i> Exportar</button>

                    </div>
                </div>
            </div>


            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead>
                        <th scope="col" class="text-center align-middle"></th>
                        <th scope="col" class="text-center align-middle">Nota/OV</th>
                        <th scope="col" class="text-center align-middle">Arquivos</th>
                        <th scope="col" class="text-center align-middle">Ordem</th>
                        <th scope="col" class="text-center align-middle">Contratado</th>
                        <th scope="col" class="text-center align-middle">Recebido</th>
                        <th scope="col" class="text-center align-middle">Viabilizado</th>
                        <th scope="col" class="text-center align-middle">Completado em</th>
                        <th scope="col" class="text-center align-middle">Rubrica</th>
                        {{-- <th scope="col" class="text-center align-middle">Regiao</th> --}}
                        <th scope="col" class="text-center align-middle">Municipio</th>
                        <th scope="col" class="text-center align-middle">Status Tacit</th>
                        <th scope="col" class="text-center align-middle">Status</th>
                        <th scope="col" class="text-center align-middle">Empreiteira</th>
                        <th scope="col" class="text-center align-middle"></th>
                    </thead>
                    <tbody class="table-group-divider">
                        @foreach ($lists as $index => $viability)
                            @php
                                $status = null;

                                $dueDate = Carbon::parse($viability?->sended_at)->addDays($viability?->getDays() + 7);

                                $today = Carbon::now();
                                $daysDifference = 0;

                                if ($dueDate) {
                                    $daysDifference = $today->diffInDays($dueDate);

                                    if ($dueDate->isBefore($today)) {
                                        $daysDifference *= -1;
                                    }

                                    if ($daysDifference < 1) {
                                        $status = [
                                            'color' => 'text-bg-danger',
                                            'info' => 'VENCIDO',
                                        ];
                                    } elseif ($daysDifference >= 1 && $daysDifference < 3) {
                                        $status = [
                                            'color' => 'text-bg-warning',
                                            'info' => 'VENCENDO',
                                        ];
                                    } elseif ($daysDifference >= 3) {
                                        $status = [
                                            'color' => 'text-bg-success',
                                            'info' => 'NO PRAZO',
                                        ];
                                    }
                                }

                                $block = null;
                                $color = 'grey';
                                // $days_left = (new DaysLeft($viability?->Note?))->getDaysLeft();
                                $count = 0;

                                if ($viability?->approved) {
                                    $count++;
                                    $block = [
                                        'color' => 'success',
                                        'command' => true,
                                    ];

                                    $color = 'green';
                                } elseif ($viability?->rejected) {
                                    $count++;
                                    $block = [
                                        'color' => 'danger',
                                        'command' => true,
                                    ];

                                    $color = 'red';
                                }

                                if (($viability?->rejected || $viability?->approved) && !$viability?->completed) {
                                    $status = [
                                        'color' => 'text-bg-primary',
                                        'info' => 'EM AVALIAÇÂO',
                                    ];
                                }

                                $color = '';

                                if ($viability?->approved && !$viability?->rejected && !$viability?->tacit) {
                                    $color = 'green';
                                } elseif (!$viability?->approved && $viability?->rejected && !$viability?->tacit) {
                                    $color = 'red';
                                } elseif ($viability?->tacit) {
                                    $color = 'yellow';
                                }

                                $tcolor = '';

                                if ($viability?->hired) {
                                    $tcolor = 'table-success';
                                }
                            @endphp
                            <tr wire:key="viability-{{ $viability?->id }}"
                                wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $viability }})"
                                style="cursor: pointer; border-left: 8px solid {{ $color }};"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="Duplo Clique para mais Opções">
                                <td>
                                </td>
                                <td class="text-center align-middle">{{ $viability?->Note?->note }}</td>
                                <td class="text-center align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    @if ($viability?->Note?->Files?->isNotEmpty())
                                        <x-files.select-download-list :files="$viability?->Note?->Files" />
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if ($viability?->Orders->isNotEmpty())
                                        @foreach ($viability?->Orders as $order)
                                            <p class="p-0 m-1">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @elseif ($viability?->Note?->Orders?->isNotEmpty())
                                        @foreach ($viability?->Note?->Orders?->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            <p class="p-0 m-1">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif
                                </td>

                                <td class="text-center align-middle">
                                    {{ $viability?->hired ? 'SIM' : 'NÃO' }}</td>
                                <td class="text-center align-middle fw-bold">
                                    {{ Carbon::parse($viability?->sended_at)->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle fw-bold">
                                    {{ isset($viability?->returned_at) ? Carbon::parse($viability?->returned_at)->format('d/m/Y') : '---' }}
                                </td>
                                <td class="text-center align-middle fw-bold">
                                    {{ isset($viability?->completed_at) ? Carbon::parse($viability?->completed_at)->format('d/m/Y') : '---' }}
                                </td>
                                <td class="text-center align-middle">{{ $viability?->Note?->rubrica }}</td>
                                {{-- <td class="text-center align-middle">
                                    {{ $cities->Where('rdMunicipio', $viability?->nexp) ? $cities->Where('rdMunicipio', $viability?->nexp)->regiao : '' }}
                                </td> --}}
                                <td class="text-center align-middle">{{ $viability?->Note?->lexp }}</td>

                                <td class="text-center align-middle">
                                    @if ($viability?->Justification)
                                        @if ($viability?->Justification->granted && !$viability?->Justification->dismissed)
                                            <span class="badge bg-success"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability?->id }}')"
                                                style="cursos: pointer;">DEFERIDO</span>
                                        @elseif ($viability?->Justification->dismissed && !$viability?->Justification->granted)
                                            <span class="badge bg-danger"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability?->id }}')"
                                                style="cursos: pointer;">INDEFERIDO</span>
                                        @elseif (!$viability?->Justification->dismissed && !$viability?->Justification->granted)
                                            <span class="badge bg-primary"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability?->id }}')"
                                                style="cursos: pointer;">EM AVALIAÇÃO</span>
                                        @else
                                            <span class="badge bg-warning"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability?->id }}')"
                                                style="cursos: pointer;">INCONSISTÊNCIA</span>
                                        @endif
                                    @else
                                        @if ($viability?->tacit)
                                            <span class="badge bg-secondary">SEM JUSTIFICATIVA</span>
                                        @else
                                            ---
                                        @endif
                                    @endif
                                </td>

                                <td class="text-center align-middle"><span
                                        class="badge {{ Viabilitiesstatus::status($viability?->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($viability?->status)->status }}</span>
                                </td>
                                <td class="text-center align-middle">{{ $viability?->Company->name }}</span>
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('pdf.checklist', ['id' => $viability?->id]) }}" target="_BLANK"
                                        class="text-primary"><i class="bx bx-printer text-primary fs-4 me-2"
                                            role="group" aria-label="Basic example" tabindex="0"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="right" data-bs-title="Imprimir Check-List FTVEO"
                                            data-bs-content="<p>Abre para impressão a Ficha Técnica de Viabilidade e Execução de Obras.</p>"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


        </div>

        {{-- Paginador --}}
        <div class="row mt-3">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>
        </div>
        {{-- FIM Paginador --}}
    @endif
    {{-- END LIST --}}


    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="modal_multi_notas" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"
                        placeholder="Separar valores, por linha, virgula ou ponto e virgula. Colar direto do excel também funciona."></textarea>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-primary" wire:click="buscarMultinotas">OK</button>
                </div>
            </div>

        </div>

    </div>

    {{-- Livewire Components --}}
    @livewire('partner.actions.responserviab', key('reesponser_modal_viab'))
    @livewire('partner.show.tacitjusfy-show', key('tacitjusfy-show'))




    {{-- Scripts --}}
    <script>
        document.addEventListener('livewire:load', function() {
            const dateIn = document.getElementById('date_in');
            const dateOut = document.getElementById('date_out');

            dateIn.addEventListener('change', function() {
                dateOut.min = dateIn.value;
            });

            // Optionally, you can also set the initial state on page load
            if (dateIn.value) {
                dateOut.min = dateIn.value;
            }

            // Prevent manual date entry
            dateIn.addEventListener('keydown', function(e) {
                e.preventDefault();
            });

            dateOut.addEventListener('keydown', function(e) {
                e.preventDefault();
            });
        });
    </script>
</div>
