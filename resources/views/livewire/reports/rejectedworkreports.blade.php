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
            <div class="row">

                <div class="col-sm-4  col-md-2 col-xxl-1 mb-3">
                    <select name="" id="" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-sm-8 col-md-2 col-xxl-2 mb-3">
                    <div class="input-group">
                        <input wire:model.bounce.2s="search" type="email" class="form-control border-secondary"
                            id="search" placeholder="Buscar">
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal"
                            data-bs-target="#buscar_multi"><i class="ri-checkbox-multiple-blank-line"></i></button>
                    </div>
                </div>



                <div class="col-sm-4 col-md-2 col-xxl-1 mb-3">
                    <input type="date" id="date_in" class="form-control border border-secondary"
                        wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Inicial">
                </div>

                <div class="col-sm-4 col-md-2 col-xxl-1 mb-3">
                    <input type="date" id="date_out" class="form-control border border-secondary"
                        wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Final">
                </div>

                {{-- <div class="col-sm-4 col-md-2 col-xxl-1 mb-3">
                    <select name="" id="" class="form-select border border-secondary"
                        wire:model="dateBy" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data por Coluna">
                        <option value="sended_at">Recebido</option>
                        <option value="returned_at">Viabilizado</option>
                        <option value="completed_at">Completado</option>
                    </select>
                </div> --}}
                <div class='col align-middle'><button class="btn btn-danger btn-sm align-middle"
                        wire:click.prevent='cleanAll()' data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Limpar Busca por Datas"><i class="ri-find-replace-line fs-5"></i></button>
                </div>


                <div class="col d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'reports_worklist', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'reports_worklist', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'reports_worklist', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'reports_worklist', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'reports_worklist'], key('removeAll'))
                </div>

            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}

    @if (!$lists->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUM INFORME REJEITADO</h3>
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
                <div class="col">
                    <h4 class="card-title edp-bg-seoweedgreen-100 text-white">INFORMES REJEITADOS</h4>
                </div>
            </div>
            <table class="table table-sm table-striped table-condensed table-hover">
                <thead>
                    <tr class="table-dark">
                        <th class="text-center align-middle" scope="col">NOTA/OV</th>
                        <th class="text-center align-middle" scope="col">ORDEM</th>
                        <th class="text-center align-middle" scope="col">EMPREITEIRA</th>
                        <th class="text-center align-middle" scope="col">RUBRICA</th>
                        <th class="text-center align-middle" scope="col">MUNICIPIO</th>
                        <th class="text-center align-middle" scope="col">MOTIVO</th>
                        <th class="text-center align-middle" scope="col">DEVOLUÇOES</th>
                        <th class="text-center align-middle" scope="col">DEVOLVIDO POR</th>
                        <th class="text-center align-middle" scope="col">DATA DEVOLUCAO</th>
                        <th class="text-center align-middle" scope="col">TEMPO</th>
                        <th class="text-center align-middle" scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        <tr wire:key='ret-{{ $list->id }}'>
                            <td class="text-center align-middle">{{ $list->Note->note }}</td>
                            <td class="text-center align-middle">
                                @if ($list->Orders->count())
                                    @foreach ($list->Orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-center align-middle">{{ $list->Company->name }}</td>
                            <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                            <td class="text-center align-middle">{{ $list->Note->lexp }}</td>
                            <td class="text-center align-middle text-danger fw-bold"
                                wire:click="$emitTo('components.workform.view-reason-return', 'workReturnViews', {{ $list }})"
                                style="cursor: pointer;">
                                {{ $list->Returnwork->last()->category }}</td>
                            <td class="text-center align-middle text-danger fw-bold">
                                @if ($list->Returnwork->count())
                                    <span class="badge text-bg-dark">{{ $list->Returnwork->count() }}</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">{{ $list->Returnwork->last()->User->name }}</td>
                            <td class="text-center align-middle">
                                {{ date('d/m/Y H:i:s', strToTime($list->Returnwork->last()->created_at)) }}</td>
                            <td class="text-center align-middle text-primary fw-bold">
                                {{ Carbon::parse($list->Returnwork->last()->created_at)->diffForHumans(null, true) }}
                            </td>
                            <td class="text-center align-middle">
                                <i class="ri-eye-line align-middle text-success fs-4" style="cursor: pointer;"
                                    wire:click="$emitTo('partner.show.show-work-form', 'show_form', {{ $list }})"></i>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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

    {{-- LivewireComponent --}}
    @livewire('partner.show.show-work-form', key('FormModdalShow'))
    @livewire('components.workform.view-reason-return', key('WorkReturnsReason'))


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
