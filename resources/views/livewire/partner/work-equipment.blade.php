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
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.2s="search">
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


                {{-- <div class="col d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner_forms', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'partner_forms', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'partner_forms', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner_forms'], key('removeAll'))
                </div> --}}

            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}

    @if (!$equipments->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUMA EQUIPAMENTO ENCONTRADO</h3>
        </div>
    @endif

    @if ($equipments->count())
        <div class="row mt-3">
            <div class="col-6">
                {{ $equipments->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $equipments->firstItem() }} até
                    {{ $equipments->lastItem() }}
                    de {{ $equipments->total() }}
                    registros.</span>
            </div>
        </div>


        <div class="card mb-2 edp-bg-gray">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                <div class="row">
                    <div class="col">
                        <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">EQUIPAMENTOS INFORMADOS</h4>
                    </div>
                    {{-- <div class="col-3 d-flex justify-content-end">

                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line align-middle"></i> Exportar</button>

                    </div> --}}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead>
                        <tr>
                            <th class="text-center" scope="col">Patrimônio</th>
                            <th class="text-center" scope="col">Tipo</th>
                            <th class="text-center" scope="col">Instalação</th>
                            <th class="text-center" scope="col">Nota/OV</th>
                            <th class="text-center" scope="col">Rubrica</th>
                            <th class="text-center" scope="col">Municipio</th>
                            <th class="text-center" scope="col">Empreiteira</th>
                            <th class="text-center" scope="col">Responsável</th>
                            <th class="text-center" scope="col">Informado Em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equipments as $list)
                            <tr wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $list->WorkReport }})"
                                wire:key="{{ $list->id }}">
                                <td class="text-center fw-bold align-middle text-uppercase">{{ $list->patrimony }}</td>
                                <td class="text-center align-middle">
                                    {{ $list->type }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->installed ? 'INSTALAÇÃO' : 'DESINSTALAÇÃO' }}</td>
                                <td class="text-center align-middle">
                                    {{ $list->WorkReport->Note->note }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->WorkReport->Note->rubrica }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->WorkReport->Note->lexp }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->WorkReport->Company->name }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->WorkReport->informer }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ Carbon::parse($list->WorkReport->informer_at)->format('d/m/Y H:i:s') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-6">
                {{ $equipments->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $equipments->firstItem() }} até
                    {{ $equipments->lastItem() }}
                    de {{ $equipments->total() }}
                    registros.</span>
            </div>
        </div>
    @endif



    {{-- LivewireComponent --}}
    @livewire('partner.show.show-work-form', key('FormModdalShow'))

</div>
