@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Helpers\SelectOptions;
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
            <div class="row align-items-center mb-3">
                <!-- Ajuste a margem inferior para melhor espaçamento -->

                <!-- Itens por página -->
                <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                    <!-- Reduz a largura em telas maiores -->
                    <label for="perPage" class="visually-hidden">Itens por página</label>
                    <select name="perPage" id="perPage" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <!-- Campo de Busca com Botão de Copiar -->
                <div class="col-sm-6 col-md-4 col-lg-3 mb-2">
                    <div class="input-group">
                        <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                            wire:model.debounce.2s="search">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                            data-bs-target="#multinotasModal" data-bs-placement="top"
                            data-bs-title="Buscar MultiNotas"><i class="ri-file-copy-line"></i></button>
                        <!-- Ícone de copiar -->
                    </div>
                </div>

                <!-- Seleção de Mês -->
                <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                    <label for="month" class="visually-hidden">Mês</label>
                    <input type="month" id="month" class="form-control border border-secondary" wire:model="month"
                        min="2023-01" max="{{ date('Y-m') }}" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Mes de Referência">
                </div>

                <!-- Data Inicial -->
                <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                    <label for="date_in" class="visually-hidden">Data Inicial</label>
                    <input type="date" id="date_in" class="form-control border border-secondary"
                        wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Inicial">
                </div>

                <!-- Data Final -->
                <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                    <label for="date_out" class="visually-hidden">Data Final</label>
                    <input type="date" id="date_out" class="form-control border border-secondary"
                        wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Final">
                </div>

                <!-- Seleção de Empreiteira -->
                @can('engineer')
                    <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                        <label for="contractor" class="visually-hidden">Empreiteira</label>
                        <select name="contractor" id="contractor" class="form-select border border-secondary"
                            wire:model="companySelected" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="Empreiteira">
                            <option value="">Todas Empreiteiras</option>
                            @if ($company_list)
                                @foreach ($company_list as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                @endcan

                <!-- Tipo de Movimento -->
                <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                    <label for="movementType" class="visually-hidden">Tipo de Movimento</label>
                    <select name="movementType" id="movementType" class="form-select border border-secondary"
                        wire:model="moviment">
                        <option value="">Todos os Movimentos</option>
                        <option value="1">Instalação</option>
                        <option value="0">Desinstalação</option>
                    </select>
                </div>

                <!-- Tipo de Equipamento -->
                <div class="col-sm-6 col-md-3 col-lg-2 mb-2">
                    <label for="equipmentType" class="visually-hidden">Tipo de Equipamento</label>
                    <select name="equipmentType" id="equipmentType" class="form-select border border-secondary"
                        wire:model="equipType">
                        <option value="">Todos os Equipamentos</option>
                        @foreach (SelectOptions::getEquipmentOptions() as $equipmentType)
                            <option value="{{ $equipmentType->nick }}">{{ $equipmentType->info }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Botão de Limpar -->
                <div class="col-sm-6 col-md-3 col-lg-1 mb-2">
                    <button class="btn btn-danger btn-sm w-100" wire:click.prevent='cleanAll()' data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Limpar Busca por Datas">
                        <i class="ri-find-replace-line fs-5"></i>
                    </button>
                </div>

                <div class="col d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'equipment', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'equipment', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'equipment', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'equipment'], key('removeAll'))
                </div>

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
                    <div class="col-auto d-flex justify-content-end">
                        <button class="btn btn-sm btn-primary" wire:click.prevent='export_excel'>
                            <i class="ri-file-excel-2-line align-middle"></i>
                        </button>
                    </div>
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
                                <td class="text-center fw-bold align-middle text-uppercase">{{ $list->patrimony }}
                                </td>
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
                                    {{ Carbon::parse($list->created_at)->format('d/m/Y H:i:s') }}
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



    {{-- Modal MultiNotas --}}
    <div wire:ignore.self class="modal fade" id="multinotasModal" tabindex="-1" role="dialog"
        aria-labelledby="multinotasModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-seoweedgreen-100">
                    <h5 class="modal-title text-white" id="multinotasModalLabel">Buscar MultiNotas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <textarea class="form-control border border-secondary" rows="10" placeholder="Cole as informações aqui..."
                    wire:model.defer="advancedSearch"></textarea>
                <div class="modal-footer edp-bg-gray">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click="multiSearch">Processar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- LivewireComponent --}}
    @livewire('partner.show.show-work-form', key('FormModdalShow'))

</div>
