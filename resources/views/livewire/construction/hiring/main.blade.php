@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\Viabilitiesstatus;
    use App\Helpers\SelectOptions;
    use App\Helpers\DaysLeft;

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

        [x-show.opacity-0] {
            transition: opacity 0.5s ease-in-out;
            opacity: 0;
        }

        [x-show.opacity-0.1] {
            opacity: 0.1;
        }

        [x-show] {
            opacity: 1;
        }

        .progress-cell {
            padding: 0;
            height: 100%;
            border: none;
            position: relative;
        }

        .progress-cell .progress-bg {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 0;
            background-color: #007bff;
            justify-content: center;
            transition: width 0.3s ease;
            /* Adicionando uma transição suave */
        }

        .progress-cell .progress-text {
            position: relative;
            justify-content: center;
            z-index: 1;
        }
    </style>
@endpush

@push('css')
    <style>
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
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>
            @livewire('components.filter.filter', ['myKey' => 'empreiteira', 'sendFilter' => '', 'model' => 'App\Models\Operation', 'column' => 'cenTrab', 'filter' => 'Empreiteira', 'group_filter' => 'hiring', 'values' => 'cenTrab', 'direction' => 'ASC', 'query' => 'operacao = "0010" AND status LIKE "ABER%"'], key('empreiteira'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'hiring', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\City', 'column' => 'baseConstrucao', 'filter' => 'Regiao', 'group_filter' => 'hiring', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'hiring', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'hiring'], key('removeAll'))
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
                <div class="row justify-content-end">
                    <div class="col align-middle">
                        <h4 class="my-0 py-0 align-middle">LISTA PARA {{ mb_strtoupper($service->service) }}

                        </h4>
                    </div>
                    <div class="col">
                        <div class="row align-middle">

                            <select class="form-select form-select-sm my-0 py-0 me-2 col" wire:model="action">
                                <option value="" selected>Selecionar Ação</option>
                                <option value="viabilizar">Viabilizar</option>
                                <option value="ri">Retorno Interno</option>
                            </select>
                            <button class="btn btn-sm btn-primary me-2 col-1" wire:click.prevent='go_att_mass'
                                @disabled(!$action) wire:target="go_att_mass" wire:loading.attr="disabled"
                                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Executar"><i
                                    class="bx bx-send fs-4 m-0 align-middle" wire:target="go_att_mass"
                                    wire:loading.remove></i>
                                <div class="spinner-border spinner-border-sm" role="status" wire:target="go_att_mass"
                                    wire:loading>
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                            <button class="btn btn-sm btn-primary me-2 col-1 p-1" wire:click.prevent='export_excel'
                                wire:target="export_excel" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-title="Exportar para o Excel">
                                <i class="ri-file-excel-2-line fs-4 m-0 align-middle" wire:target="export_excel"
                                    wire:loading.remove>
                                </i>
                                <div class="spinner-border spinner-border-sm" role="status" wire:target="export_excel"
                                    wire:loading>
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                            {{-- <button class="btn btn-sm btn-primary me-2 col-1 p-1" wire:target="downloadZip"
                                wire:loading.attr="disabled" wire:click.prevent='downloadZip' data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-title="Fazer Download dos Arquivos ZIP"><i
                                    class="bx bx-cloud-download fs-3 m-0 align-middle" wire:target="downloadZip"
                                    wire:loading.remove></i>
                                <div class="spinner-border spinner-border-sm" role="status"
                                    wire:target="downloadZip" wire:loading>
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button> --}}
                            {{-- <button class="btn btn-sm btn-primary me-2 col-1 p-1" wire:click.prevent='copyClipboard'
                                wire:target="copyClipboard" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="Copiar Selecionados para área de Transferência"><i
                                    class="bx bxs-copy-alt fs-4 m-0 align-middle" wire:target="copyClipboard"
                                    wire:loading.remove></i>
                                <div class="spinner-border spinner-border-sm" role="status"
                                    wire:target="copyClipboard" wire:loading>
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button> --}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">

                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input border border-secondary" type="checkbox"
                                    wire:model.defer="selectAll" wire:click="setSelectAll()"
                                    @checked($this->checkAllSelect($lists))>

                            </th>
                            <th scope="col" class="fw-bold text-center">Nota</th>
                            <th scope="col" class="fw-bold text-center">Ordem</th>
                            <th scope="col" class="fw-bold text-center">Files</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Tipo</th>
                            <th scope="col" class="fw-bold text-center">Material</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Status Ordem</th>
                            <th scope="col" class="fw-bold text-center">Status OV/NOTA</th>
                            <th scope="col" class="fw-bold text-center">Status OP10</th>
                            <th scope="col" class="fw-bold text-center">Centro OP10</th>
                            <th scope="col" class="fw-bold text-center">Prazo Restante</th>
                            <th scope="col" class="fw-bold text-center">Dias Viab</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $block = false;
                                $viability = '';
                                $status = '';
                                $days_left = (new DaysLeft($list))->getDaysLeft();
                                $waiting = false;

                                if ($list->Viabilities->count()) {
                                    if ($list->Viabilities->count()) {
                                        $viability = $list->Viabilities->last();

                                        $block = true;

                                        if ($viability->approved) {
                                            $status = [
                                                'info' => 'Aprovado',
                                                'color_text' => 'text-bg-succes',
                                                'table' => 'table-success',
                                            ];
                                        } elseif ($viability->rejected && !$viability->approved) {
                                            $status = [
                                                'info' => 'Rejeitado',
                                                'color_text' => 'text-bg-danger',
                                                'table' => 'table-danger',
                                            ];
                                        } elseif (
                                            $viability->canceled &&
                                            !$viability->rejected &&
                                            !$viability->approved
                                        ) {
                                            $status = [
                                                'info' => 'Cancelado',
                                                'color_text' => 'text-bg-secondary',
                                                'table' => 'table-secondary',
                                            ];
                                        } else {
                                            $status = [
                                                'info' => 'Em Viabilidade',
                                                'color_text' => 'text-bg-primary',
                                                'table' => 'table-primary',
                                            ];
                                        }
                                    }
                                }

                                if ($list->Waitings->count() && $list->Waitings->where('complete', false)->count()) {
                                    $block = true;
                                    $waiting = true;
                                }

                            @endphp

                            <tr wire:key='{{ $list->id }}'
                                class=" fade-in text-center
                                    @if ($block && !$waiting) {{ $status['table'] }} @endif
                                    @if ($block && $waiting) table-secondary @endif
                                    ">
                                <td class="align-middle"><input class="form-check-input border border-secondary"
                                        type="checkbox" wire:model.defer="selected" value="{{ $list->id }}"
                                        @disabled($block)>
                                </td>
                                <td
                                    class="fw-bold align-middle @if ($list->is45) text-bg-warning @endif">
                                    {{ $list->note }}
                                    @if ($list->pze == '25')
                                        <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                            data-bs-content="Nota com prazo de execução de {{ $list->pze }} dias"
                                            style="z-index: 9999;" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="ri-fire-line text-danger fw-bold"></i>
                                        </span>
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

                                </td>
                                <td class="align-middle">
                                    @if ($list->Orders->isNotEmpty())
                                        @foreach ($list->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            <p class="py-0 my-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    {{-- <x-files.select-download-list :files='$list->Files' /> --}}
                                    <x-select-download-project-only :files='$list->Files' :filtro="'PROJETO'" />

                                </td>
                                <td class="align-middle">{{ $list->rubrica }}</td>
                                <td class="align-middle">{{ $list->group5 }}{{ $list->txpriority }}</td>
                                <td class="align-middle">{{ $list->material }}</td>
                                <td class="align-middle">{{ $list->lexp }}</td>
                                <td class="align-middle">
                                    @if ($list->Orders->isNotEmpty())
                                        @foreach ($list->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            <p class="py-0 my-0">
                                                {{ explode(' ', $order->statusSist)[0] }}
                                            </p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if ($list->type_note == 1)
                                        {{ $list->centerjob }}
                                    @elseif($list->type_note == 2)
                                        {{ $list->nstats }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if ($list->Orders->isNotEmpty())
                                        @foreach ($list->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            <p class="py-0 my-0">
                                                {{ $order->Operations->isNotEmpty() ? ($order->Operations->where('operacao', '0010')->first() ? explode(' ', $order->Operations->where('operacao', '0010')->first()->status)[0] : '---') : '---' }}
                                            </p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif

                                </td>
                                <td class="align-middle">
                                    @if ($list->Orders->isNotEmpty())
                                        @php
                                            $empresa = '';
                                        @endphp
                                        @foreach ($list->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            @php
                                                $empresa_2 = $order->Operations->isNotEmpty()
                                                    ? ($order->Operations->where('operacao', '0010')->first()
                                                        ? $order->Operations->where('operacao', '0010')->first()
                                                            ->cenTrab
                                                        : '---')
                                                    : '---';
                                            @endphp
                                            @if ($empresa != $empresa_2)
                                                @php
                                                    $empresa = $empresa_2;
                                                @endphp
                                                <p class="py-0 my-0">
                                                    {{ $empresa_2 }}
                                                </p>
                                            @endif
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle
                                    @if ($days_left < 0) text-bg-secondary
                                    @elseif($days_left >= 0 && $days_left < 6)
                                    table-danger
                                    @elseif($days_left >= 6 && $days_left < 10)
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
                            "
                                    style="z-index: 9999;">
                                    {{ $days_left }}</td>
                                {{-- <td class="align-middle text-center">
                                        @if ($block)
                                            {{ Carbon::parse($list->Viabilities->first()->sended_at)->diffInDays(Carbon::now()) }}
                                        @endif


                                    </td> --}}
                                @php

                                    $days = '';
                                    $percent = '';
                                    $somaDays = $list->Viabilities->isNotEmpty()
                                        ? $list->Viabilities->last()->getDays()
                                        : 0;
                                    $totalDays = 7 + $somaDays;

                                    if ($block && !$waiting) {
                                        if (
                                            isset($list->Viabilities->last()->returned_at) &&
                                            $list->Viabilities->last()->returned_at
                                        ) {
                                            $days = Carbon::parse($list->Viabilities->last()->sended_at)->diffInDays(
                                                Carbon::parse($list->Viabilities->last()->returned_at),
                                            );
                                        } else {
                                            $days = Carbon::parse($list->Viabilities->last()->sended_at)->diffInDays(
                                                Carbon::now(),
                                            );
                                        }
                                        $percent = round(($days / $totalDays) * 100, 1);
                                    }

                                @endphp
                                <td
                                    class="progress-cell border-bottom border-start border-end border-3 align-middle justify-content-center overflow-hidden text-center">
                                    <div class="progress-bg text-center"
                                        style="width: {{ $percent }}%;
                                                 @if ($percent > 100.0) background-color: #969595;
                                                 @elseif ($percent > 80.0 && $percent <= 100.0) background-color: #FBC4C4;
                                                @elseif($percent > 70.0 && $percent <= 80.0)
                                                    background-color: #FBF8C4;
                                                @else
                                                    background-color: #85CAF9; @endif
                                            ">
                                    </div>
                                    <span class="text-center progress-text fw-bold">{{ $days }}
                                    </span>
                                </td>
                                <td class="text-break align-middle text-center">
                                    @if ($block)
                                        @if (!$waiting)
                                            <p class="py-0 my-0">
                                                <span
                                                    class="badge text-wrap aling-middle {{ Viabilitiesstatus::status($list->Viabilities->last()->status)->colorbg }}"
                                                    style="width: 6rem;">{{ mb_strToUpper(Viabilitiesstatus::status($list->Viabilities->last()->status)->status) }}</span>
                                            </p>
                                        @else
                                            <span class="badge text-wrap text-bg-danger">EM ESPERA (RI)</span>
                                        @endif
                                    @elseif ($waiting)
                                        <span class="badge text-wrap text-bg-danger">EM ESPERA (RI)</span>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="true" id="flexCheckIndeterminate"
                            wire:model.defer="allCenters">
                        <label class="form-check-label" for="flexCheckIndeterminate">
                            EXIBIR EM TODOS OS CENTROS
                        </label>
                    </div>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>




    {{-- Livewire Componentes --}}
    @livewire('construction.hiring.actions.viability', key('Viability-modal'))
    @livewire('construction.hiring.actions.go-waiting', key('go-waiting'))





    <!-- Exibir os dados do clipboard com formatação para Excel -->
    <textarea id="clipboard-data" style="display: none;">
            @if (count($clipboardData))
@foreach ($clipboardData as $row)
{{ implode("\t", $row) }}
@endforeach
@else
SEM DADOS
@endif
        </textarea>


    {{-- <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('return_modal').addEventListener('hidden.bs.modal', () => {

            // Emitindo o evento para o componente pai
            // Livewire.emitTo('construction.hiring.main', 'closeAll');
            Livewire.emitTo('construction.hiring.main', 'closeAll');
            // Livewire.emitTo('construction.hiring.actions.go-waiting', 'closeAll');


        });
    </script> --}}
</div>
