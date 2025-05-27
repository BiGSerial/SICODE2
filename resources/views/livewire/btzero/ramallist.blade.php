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
            <div class="row g-3">
                <!-- Per Page Select -->
                <div class="col-sm-6 col-md-4 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                        <label for="perPageSelect">Registros por página</label>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="form-floating">
                        <input type="text" class="form-control border border-secondary" id="searchInput"
                            wire:model.debounce.2s="search" placeholder="Buscar">
                        <label for="searchInput">Buscar</label>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="col-sm-6 col-md-4 col-lg-2">
                    <div class="form-floating">
                        <input type="date" id="date_in" class="form-control border border-secondary"
                            wire:model="date_in" placeholder="Data Inicial">
                        <label for="date_in">Data Inicial</label>
                    </div>
                </div>

                <div class="col-sm-6 col-md-4 col-lg-2">
                    <div class="form-floating">
                        <input type="date" id="date_out" class="form-control border border-secondary"
                            wire:model="date_out" placeholder="Data Final">
                        <label for="date_out">Data Final</label>
                    </div>
                </div>

                <!-- Informer Select -->
                <div class="col-sm-6 col-md-4 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" wire:model="informer" id="informerSelect">
                            <option value="">Selecione...</option>
                            @if ($informers)
                                @foreach ($informers as $informer)
                                    <option value="{{ $informer->id }}">{{ $informer->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <label for="informerSelect">Informado por</label>
                    </div>
                </div>

                <!-- Clear Button -->
                <div class="col-sm-6 col-md-4 col-lg-1 d-flex align-items-center">
                    <button class="btn btn-danger w-100" wire:click.prevent='cleanAll()' data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Limpar Busca por Datas">
                        <i class="ri-find-replace-line"></i>
                    </button>
                </div>

                <!-- Filters -->
                <div class="col-12 mt-3 d-flex flex-wrap gap-2 justify-content-end">
                    @livewire(
                        'components.filter.filter',
                        [
                            'myKey' => 'rubrica',
                            'sendFilter' => '',
                            'model' => 'App\Models\Note',
                            'column' => 'rubrica',
                            'filter' => 'Rubrica',
                            'group_filter' => 'partner_forms',
                            'values' => 'rubrica',
                            'direction' => 'ASC',
                            'query' => '',
                        ],
                        key('rubrica')
                    )

                    @livewire(
                        'components.filter.filter',
                        [
                            'myKey' => 'region',
                            'sendFilter' => 'city',
                            'model' => 'App\Models\Edp_depc\City',
                            'column' => 'regiao',
                            'filter' => 'Regiao',
                            'group_filter' => 'partner_forms',
                            'values' => 'regiao',
                            'direction' => 'ASC',
                            'query' => '',
                        ],
                        key('region')
                    )

                    @livewire(
                        'components.filter.filter',
                        [
                            'myKey' => 'city',
                            'sendFilter' => '',
                            'model' => 'App\Models\Edp_depc\City',
                            'column' => 'cidade',
                            'filter' => 'Municipio',
                            'group_filter' => 'partner_forms',
                            'values' => 'municipio',
                            'direction' => 'ASC',
                            'query' => '',
                        ],
                        key('city')
                    )

                    @livewire(
                        'components.filter.remove-all',
                        [
                            'group_filter' => 'partner_forms',
                        ],
                        key('removeAll')
                    )
                </div>
            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}

    @if (!$lists->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
        </div>
    @endif

    @if ($lists->count())
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


        <div class="card mb-2 edp-bg-gray">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                <div class="row">
                    <div class="col">
                        <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">PATRIOMÔNIOS SMC INFORMADOS</h4>
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
                        <tr>
                            <th class="text-center" scope="col">Note</th>
                            <th class="text-center" scope="col">Ordens</th>
                            <th class="text-center" scope="col">Rubrica</th>
                            <th class="text-center" scope="col">Files</th>
                            <th class="text-center" scope="col">Equip SMC</th>
                            <th class="text-center" scope="col">Equip Obra</th>
                            <th class="text-center" scope="col">Digitado em</th>
                            <th class="text-center" scope="col">Informado em</th>
                            <th class="text-center" scope="col">Digitador</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr wire:dblclick="$emitTo('btzero.view.compare-form', 'showCompareForm', {{ $list->Note }})"
                                wire:key="{{ $list->id }}">
                                <td class="text-center fw-bold align-middle">{{ $list->Note->note }}</td>
                                <td class="text-center align-middle">
                                    @if ($list->Orders->count())
                                        @foreach ($list->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                                <td class="text-center align-middle">
                                    <x-files.select-download-list :files='$list->Note->Files' />
                                </td>
                                <td class="text-center align-middle">
                                    {!! $list->BtzeroEquipment->count()
                                        ? "<span class='badge text-bg-dark'>" . $list->BtzeroEquipment->count() . '</span>'
                                        : '' !!}
                                </td>
                                <td class="text-center align-middle">
                                    {!! $list->Note->WorkForm && $list->Note->WorkForm->Equipment->isNotEmpty()
                                        ? "<span class='badge text-bg-dark'>" . $list->Note->WorkForm->Equipment->count() . '</span>'
                                        : '' !!}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->created_at ? date('d/m/Y', strToTime($list->created_at)) : 'Desconhecido' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->Note->WorkForm && $list->Note->WorkForm->informed_at ? date('d/m/Y', strToTime($list->Note->WorkForm->informed_at)) : 'Não Informado' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->User ? $list->User->name : 'Desconhecido' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- LivewireComponent --}}
    @livewire('btzero.view.compare-form', key('compare-form'))

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
