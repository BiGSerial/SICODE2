@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
@push('css')
    <style>
        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu>.dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -6px;
            margin-left: -1px;
            border-radius: 0 6px 6px 6px;
        }

        .dropdown-submenu:hover>.dropdown-menu {
            display: block;
        }

        .dropdown-submenu>a:after {
            content: " ";
            float: right;
            width: 0;
            height: 0;
            border-color: transparent;
            border-style: solid;
            border-width: 5px 0 5px 5px;
            border-left-color: #ccc;
            margin-top: 5px;
            margin-right: -10px;
        }

        .dropdown-submenu:hover>a:after {
            border-left-color: #fff;
        }

        .dropdown-submenu.pull-left {
            float: none;
        }

        .dropdown-submenu.pull-left>.dropdown-menu {
            left: -100%;
            margin-left: 10px;
            border-radius: 6px 0 6px 6px;
        }

        /* Adicionando classe para mudar de lado quando próximo ao canto da tela */
        .dropdown-submenu.change-side>.dropdown-menu {
            left: auto;
            right: 100%;
            margin-left: 0;
            margin-right: -1px;
            /* ajuste se necessário */
            border-radius: 6px 6px 6px 0;
        }
    </style>
@endpush

<div>
    <x-show-loading />
    <x-showselected :count="$selected" />


    <div class="row mb-3 justify-content-end">
        <div class="col-1">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>

        <div class="col-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="text"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>

        <div class="col-md-9 d-flex mb-3 justify-content-end py-4">
            <label for="search" class="form-label"> </label>
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'hiring', 'values' => 'regiao', 'direction' => 'ASC'])
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'hiring', 'values' => 'municipio', 'direction' => 'ASC'])
        </div>
    </div>


    @if (!$lists->count())
        <div class="card">
            <div class="card-body">
                <h3 class="text-center">NENHUM REGISTRO ENCONTRADO</h3>
            </div>
        </div>
    @else
        <div class="row">
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
        <div class="card">
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll">
                                </th>
                                <th scope="col" class="fw-bold">Ordem</th>
                                <th scope="col" class="fw-bold">Nota</th>

                                <th scope="col" class="fw-bold">denConjunto</th>
                                <th scope="col" class="fw-bold">Rubrica</th>
                                <th scope="col" class="fw-bold">Municipio</th>
                                <th scope="col" class="fw-bold">Status</th>
                                <th scope="col" class="fw-bold">Prazo Restante</th>
                                <th scope="col" class="fw-bold">Situação</th>
                                <th scope="col" class="fw-bold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr>
                                    <td><input class="form-check-input" type="checkbox" wire:model.defer="selected"
                                            value="{{ $list->id }}">
                                    </td>
                                    <td class="fw-bold">{{ $list->ordem }}</td>
                                    <td>{{ $list->Note->note }}</td>

                                    <td>{{ $list->denConjunto }}</td>
                                    <td>{{ $list->Note->rubrica }}</td>
                                    <td>{{ $list->Note->lexp }}</td>
                                    <td>{{ isset($list->Operations()->where('operacao', '0010')->first()->status) ? $list->Operations()->where('operacao', '0010')->first()->status : '' }}
                                    </td>
                                    <td class="text-center 
                                    @if ($list->days_left < 0) text-bg-secondary
                                    @elseif($list->days_left >= 0 && $list->days_left < 6)
                                    table-danger
                                    @elseif($list->days_left >= 6 && $list->days_left < 10)
                                        table-warning
                                    @else
                                        table-success @endif
                                "
                                        tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                        data-bs-placement="top" data-bs-title="Prazo Restante"
                                        data-bs-content="
                            <p>Os prazos contados já foram expurgados os tempos em status não contabilizáveis.</p>
                            <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                            <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                            <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                            <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                            ">
                                        {{ $list->Note->days_left }}</td>
                                    <td></td>
                                    <td>
                                        {{-- <div class="dropdown" style="position: inherit">
                                            <button class="btn btn-danger dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-menu-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">Action</a></li>
                                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                                <li class="dropdown-divider"></li>
                                                <div x-data="{ isNearRightEdge: false }" @mousemove="checkPosition($event)">
                                                    <li class="dropdown-submenu"
                                                        :class="{ 'change-side': isNearRightEdge }">
                                                        <a class="dropdown-item" tabindex="-1" href="#">Hover me
                                                            for
                                                            more options</a>
                                                        <ul class="dropdown-menu">
                                                            <li class="dropdown-item"><a tabindex="-1"
                                                                    href="#">Second level</a></li>
                                                            <li class="dropdown-submenu">
                                                                <a class="dropdown-item" href="#">Even More..</a>
                                                                <ul class="dropdown-menu">
                                                                    <li class="dropdown-item"><a href="#">3rd
                                                                            level</a></li>
                                                                    <li class="dropdown-submenu"><a
                                                                            class="dropdown-item"
                                                                            href="#">another
                                                                            level</a>
                                                                        <ul class="dropdown-menu">
                                                                            <li class="dropdown-item"><a
                                                                                    href="#">4th
                                                                                    level</a></li>
                                                                            <li class="dropdown-item"><a
                                                                                    href="#">4th
                                                                                    level</a></li>
                                                                            <li class="dropdown-item"><a
                                                                                    href="#">4th level</a></li>
                                                                        </ul>
                                                                    </li>
                                                                    <li class="dropdown-item"><a href="#">3rd
                                                                            level</a></li>
                                                                </ul>
                                                            </li>
                                                        </ul>
                                                    <li>
                                                </div>
                                                <a class="dropdown-item" href="#">Something else
                                                    here</a>
                                                </li>

                                            </ul>
                                        </div> --}}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
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
    @endif




    {{-- MODALS --}}
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

    @push('script')
        <script>
            function checkPosition(event) {
                const submenu = event.target.closest('.dropdown-submenu');
                const submenuRect = submenu.getBoundingClientRect();
                const windowWidth = window.innerWidth;

                if (submenuRect.right > windowWidth - 250) {
                    submenu.classList.add('change-side');
                } else {
                    submenu.classList.remove('change-side');
                }
            }
        </script>
    @endpush
</div>
