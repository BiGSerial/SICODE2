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

    {{-- START SearchBar and Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-1 col-sm-2">
                    <select name="" id="" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.2s="search">
                </div>

                <div class="col-md-2 col-sm-3">
                    <input type="month" id="month" class="form-control border border-secondary" wire:model="month"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Mes Referência" min="2023-05"
                        max="{{ date('Y-m') }}">
                </div>

                <div class="col-md-2 col-sm-3">
                    <input type="date" id="date_in" class="form-control border border-secondary"
                        wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Inicial" min="2023-05-01" max="{{ date('Y-m-d') }}">
                </div>

                <div class="col-md-2 col-sm-3">
                    <input type="date" id="date_out" class="form-control border border-secondary"
                        wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Final" max="{{ date('Y-m-d') }}">
                </div>

                <div class="col-md-2 col-sm-3">
                    <select name="" id="" class="form-select border border-secondary"
                        wire:model="dateBy" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data por Coluna">
                        <option value="sended_at">Recebido</option>
                        <option value="returned_at">Viabilizado</option>
                        <option value="completed_at">Completado</option>
                    </select>
                </div>

                <div class="col-md-1 col-sm-2">
                    <button class="btn btn-danger btn-sm" wire:click.prevent='cleanAll()' data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Limpar Busca por Datas">
                        <i class="ri-find-replace-line fs-5"></i>
                    </button>
                </div>


            </div>

            <div class="row row g-2 align-items-center mt-3">
                <div class="d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empresa', 'group_filter' => 'engineer', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
                    @livewire('components.filter.filter', ['myKey' => 'responsible', 'sendFilter' => '', 'model' => 'App\Models\User', 'column' => 'id', 'filter' => 'Responsável', 'group_filter' => 'engineer', 'values' => 'name', 'direction' => 'ASC', 'query' => 'responsible = true'], key('responsavel'))
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'engineer', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'engineer', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'engineer', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                </div>
            </div>
        </div>
        {{-- END SearchBar and Filters --}}
    </div>
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

                    </thead>
                    <tbody class="table-group-divider">
                        @foreach ($lists as $index => $viability)
                            @php
                                $status = null;

                                $dueDate = Carbon::parse($viability->sended_at)->addDays($viability->getDays() + 7);

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
                                // $days_left = (new DaysLeft($viability->Note))->getDaysLeft();
                                $count = 0;

                                if ($viability->approved) {
                                    $count++;
                                    $block = [
                                        'color' => 'success',
                                        'command' => true,
                                    ];

                                    $color = 'green';
                                } elseif ($viability->rejected) {
                                    $count++;
                                    $block = [
                                        'color' => 'danger',
                                        'command' => true,
                                    ];

                                    $color = 'red';
                                }

                                if (($viability->rejected || $viability->approved) && !$viability->completed) {
                                    $status = [
                                        'color' => 'text-bg-primary',
                                        'info' => 'EM AVALIAÇÂO',
                                    ];
                                }

                                $color = '';

                                if ($viability->approved && !$viability->rejected && !$viability->tacit) {
                                    $color = 'green';
                                } elseif (!$viability->approved && $viability->rejected && !$viability->tacit) {
                                    $color = 'red';
                                } elseif ($viability->tacit) {
                                    $color = 'yellow';
                                }

                                $tcolor = '';

                                if ($viability->hired) {
                                    $tcolor = 'table-success';
                                }
                            @endphp
                            <tr wire:key="viability-{{ $viability->id }}"
                                wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $viability }})"
                                style="cursor: pointer; border-left: 8px solid {{ $color }};"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="Duplo Clique para mais Opções">
                                <td>
                                </td>
                                <td class="text-center align-middle">{{ $viability->Note->note }}</td>
                                <td class="text-center align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    <x-files.select-download-list :files='$viability->Note->Files' />
                                </td>
                                <td class="text-center align-middle">
                                    @if ($viability->count())
                                        @foreach ($viability->Note->Orders as $order)
                                            <p class="p-0 m-1">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif
                                </td>

                                <td class="text-center align-middle">
                                    {{ $viability->hired ? 'SIM' : 'NÃO' }}</td>
                                <td class="text-center align-middle fw-bold">
                                    {{ Carbon::parse($viability->sended_at)->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle fw-bold">
                                    {{ isset($viability->returned_at) ? Carbon::parse($viability->returned_at)->format('d/m/Y') : '---' }}
                                </td>
                                <td class="text-center align-middle fw-bold">
                                    {{ isset($viability->completed_at) ? Carbon::parse($viability->completed_at)->format('d/m/Y') : '---' }}
                                </td>
                                <td class="text-center align-middle">{{ $viability->Note->rubrica }}</td>
                                {{-- <td class="text-center align-middle">
                                    {{ $cities->Where('rdMunicipio', $viability->nexp) ? $cities->Where('rdMunicipio', $viability->nexp)->regiao : '' }}
                                </td> --}}
                                <td class="text-center align-middle">{{ $viability->Note->lexp }}</td>

                                <td class="text-center align-middle">
                                    @if ($viability->Justification)
                                        @if ($viability->Justification->granted && !$viability->Justification->dismissed)
                                            <span class="badge bg-success"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability->id }}')"
                                                style="cursos: pointer;">DEFERIDO</span>
                                        @elseif ($viability->Justification->dismissed && !$viability->Justification->granted)
                                            <span class="badge bg-danger"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability->id }}')"
                                                style="cursos: pointer;">INDEFERIDO</span>
                                        @elseif (!$viability->Justification->dismissed && !$viability->Justification->granted)
                                            <span class="badge bg-primary"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability->id }}')"
                                                style="cursos: pointer;">EM AVALIAÇÃO</span>
                                        @else
                                            <span class="badge bg-warning"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show','getTacitInfo', '{{ $viability->id }}')"
                                                style="cursos: pointer;">INCONSISTÊNCIA</span>
                                        @endif
                                    @else
                                        @if ($viability->tacit)
                                            <span class="badge bg-secondary">SEM JUSTIFICATIVA</span>
                                        @else
                                            ---
                                        @endif
                                    @endif
                                </td>

                                <td class="text-center align-middle"><span
                                        class="badge {{ Viabilitiesstatus::status($viability->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($viability->status)->status }}</span>
                                </td>
                                <td class="text-center align-middle">{{ $viability->Company->name }}</span>
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
