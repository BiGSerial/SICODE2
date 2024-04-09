@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\Viabilitiesstatus;

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
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'hiring', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'hiring', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
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
                    <div class="col">
                        <h4 class="my-0 align-middle">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col">
                        <div class="row">
                            <select class="form-select my-0 py-0 me-2 col" wire:model="action">
                                <option value="" selected>Selecionar Ação</option>
                                <option value="">Pausar</option>
                                <option value="1">Viabilizar</option>
                                <option value="2">Contratar</option>
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
                            <button class="btn btn-sm btn-primary me-2 col-1 p-1" wire:target="downloadZip"
                                wire:loading.attr="disabled" wire:click.prevent='downloadZip' data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-title="Fazer Download dos Arquivos ZIP"><i
                                    class="bx bx-cloud-download fs-3 m-0 align-middle" wire:target="downloadZip"
                                    wire:loading.remove></i>
                                <div class="spinner-border spinner-border-sm" role="status"
                                    wire:target="downloadZip" wire:loading>
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                            <button class="btn btn-sm btn-primary me-2 col-1 p-1" wire:click.prevent='copyClipboard'
                                wire:target="copyClipboard" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="Copiar Selecionados para área de Transferência"><i
                                    class="bx bxs-copy-alt fs-4 m-0 align-middle" wire:target="copyClipboard"
                                    wire:loading.remove></i>
                                <div class="spinner-border spinner-border-sm" role="status"
                                    wire:target="copyClipboard" wire:loading>
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                        </div>
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
                                <th scope="col" class="fw-bold">Files</th>
                                <th scope="col" class="fw-bold">Rubrica</th>
                                <th scope="col" class="fw-bold">denConjunto</th>
                                <th scope="col" class="fw-bold">Municipio</th>
                                <th scope="col" class="fw-bold">Status Ordem</th>
                                <th scope="col" class="fw-bold">Status OV/NOTA</th>
                                <th scope="col" class="fw-bold">Status OP10</th>
                                <th scope="col" class="fw-bold">Centro OP10</th>
                                <th scope="col" class="fw-bold">Prazo Restante</th>
                                <th scope="col" class="fw-bold">Dias Viab</th>
                                <th scope="col" class="fw-bold">Situação</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $block = false;
                                    $viability = '';
                                    $status = '';
                                    $days_left = 0;

                                    // Dias Restantes
                                    if ($list->Note->type_note == 1) {

                                        if ($list->Note->mesalization && $list->Note->mesalization != 'erro') {
                                            preg_match('/\d+\/\d+/', $list->Note->mesalization, $matches);

                                            if (!empty($matches)) {
                                                [$mes, $ano] = explode('/', $matches[0]);

                                                if ($mes >= 1) {
                                                    $data = "{$ano}-{$mes}-28 23:59:59";

                                                    $hoje = Carbon::now();

                                                    $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                                    $days_left = $hoje->diffInDays($dataCarbon, false);

                                                } else {

                                                    $data = "{$ano}-12-28 23:59:59";

                                                    $hoje = Carbon::now();

                                                    $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                                    $days_left = $hoje->diffInDays($dataCarbon, false);
                                                    
                                                    
                                                }
                                            } 
                                        }
                                        
                                    } elseif ($list->Note->type_note == 2) {
                                        $days_left = $list->Note->days_left;
                                    } 

                                    if ($list->Viabilities->count()) {
                                        if ($list->Viabilities->Where('completed', false)->count()) {
                                            $viability = $list->Viabilities->Where('completed', false)->last();

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

                                @endphp

                                <tr wire:key='{{ $list->id }}'
                                    class="
                                    @if ($block) {{ $status['table'] }} @endif">
                                    <td class="align-middle"><input class="form-check-input border border-secondary"
                                            type="checkbox" wire:model.defer="selected" value="{{ $list->id }}"
                                            @disabled($block)>
                                    </td>
                                    <td class="fw-bold align-middle">{{ $list->ordem }}</td>
                                    <td
                                        class="align-middle @if ($list->Note->type_note == 2 && $list->Note->Orders->count() > 1) text-danger fw-bold @endif">
                                        {{ $list->Note->note }}</td>
                                    <td class="align-middle">
                                        {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                        <x-files.select-download-list :files='$list->Note->Files' />
                                    </td class="align-middle">
                                    <td class="align-middle">{{ $list->Note->rubrica }}</td>
                                    <td class="align-middle">{{ $list->denConjunto }}</td>
                                    <td class="align-middle">{{ $list->Note->lexp }}</td>
                                    <td class="align-middle">{{ $list->statusSist }}
                                    </td>
                                    <td class="align-middle">
                                        @if ($list->Note->type_note == 1)
                                            {{ $list->Note->centerjob }}
                                        @elseif($list->Note->type_note == 2)
                                            {{ $list->Note->nstats }}
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{-- @if ($list->Operations->count())
                                            @dump($list->Operations->where('operacao', '0010'))
                                        @endif --}}
                                        {{ $list->Operations->count() ? ($list->Operations->where('operacao', '0010')->first() ? $list->Operations->where('operacao', '0010')->first()->status : '___') : '---' }}
                                    </td>
                                    <td class="align-middle">
                                        {{ $list->Operations->count() ? ($list->Operations->where('operacao', '0010')->first() ? $list->Operations->where('operacao', '0010')->first()->cenTrab : '___') : '---' }}
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

                                        if ($block) {
                                            if (
                                                isset($list->Viabilities->first()->returned_at) &&
                                                $list->Viabilities->first()->returned_at
                                            ) {
                                                $days = Carbon::parse(
                                                    $list->Viabilities->first()->sended_at,
                                                )->diffInDays(Carbon::parse($list->Viabilities->first()->returned_at));
                                            } else {
                                                $days = Carbon::parse(
                                                    $list->Viabilities->first()->sended_at,
                                                )->diffInDays(Carbon::now());
                                            }
                                            $percent = round(($days / 7) * 100, 1);
                                        }

                                    @endphp
                                    <td
                                        class="progress-cell border-bottom border-start border-end border-3 align-middle justify-content-center overflow-hidden">
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
                                            <span
                                                class="badge text-wrap aling-middle {{ Viabilitiesstatus::status($list->Viabilities->first()->status)->colorbg }}"
                                                style="width: 6rem;">{{ Viabilitiesstatus::status($list->Viabilities->first()->status)->status }}</span>
                                        @endif
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

    <div wire:ignore.self class="modal fade" id="viability_modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog modal-lg">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        @if ($action == 1)
                            VIABILIDADE
                        @elseif ($action == 2)
                            CONTRATAÇÃO
                        @elseif ($action == 3)
                            INTERROMPER
                        @else
                            AÇÃO DESCONHECIDA
                        @endif

                    </h4>
                </div>

                <div class="modal-body"> {{-- Inicio Modal Body --}}

                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-start">
                            <h4 class="my-auto">Dados de Envio</h4>
                        </div>
                        <div class="card-body d-flex justify-content-between">
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione a Empreiteira</label>
                                <select class="form-select" wire:model.defer="company_s">
                                    <option>----</option>
                                    @if ($companies)
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif

                                </select>


                            </div>
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione o Engenheiro
                                    Responsável</label>
                                <select class="form-select" wire:model.defer="engineer_s">
                                    @if ($engineers)
                                        <option>----</option>
                                        @foreach ($engineers as $engineer)
                                            <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                                        @endforeach
                                    @endif

                                </select>


                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between">
                            <h4 class="my-auto">Arquivos</h4>
                            <button class="btn btn-sm btn-primary"
                                onclick="document.getElementById('file-input').click()">Add</button>

                        </div>

                        <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                            x-on:livewire-upload-finish="isUploading = false"
                            x-on:livewire-upload-error="isUploading = false"
                            x-on:livewire-upload-progress="progress = $event.detail.progress">

                            <form wire:submit.prevent="saveFile">
                                <input type="file" id="file-input" multiple wire:model="files" hidden>
                                {{-- <button type="submit" id="id-submit"></button> --}}
                            </form>

                            <div x-show="isUploading" class="mb-3">
                                {{-- <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                    x-bind:style="`width: ${progress}%`">
                                    <span class="align-middle" x-text="`${progress}%`"></span>
                                </div> --}}
                                <div class="progress" role="progressbar" aria-label="Danger example"
                                    aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
                                    style="width: 100%; border-radius: 0;">
                                    <span class="progress-bar bg-danger" x-bind:style="`width: ${progress}%`"
                                        x-text="`${progress}%`">
                                </div>
                            </div>
                        </div>

                        <div class="card-body " id="drop-area">
                            <div class="row g-1 justify-content-between mb-3">

                                @if (count($show_existing_files))
                                    @foreach ($show_existing_files as $file)
                                        <div class="col-6 border border-secondary d-flex justify-content-between p-0">
                                            <div class="p-1 m-0 border-end border-secondary"><i
                                                    class="bx bxs-file-{{ $file['ext'] }} text-success fs-4"></i>
                                            </div>
                                            <div class="p-1 m-0 text-center no-wrap">{{ $file['name'] }}</i>
                                            </div>
                                            <div class="p-1 m-0 border-start border-secondary">
                                                <i class="ri-file-cloud-line text-succes fs-4"></i>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if (count($show_files))
                                    @foreach ($show_files as $file)
                                        <div class="col-6 border border-secondary d-flex justify-content-between p-0">
                                            <div class="p-1 m-0 border-end border-secondary"><i
                                                    class="bx bxs-file-{{ $file['ext'] }} @if ($file['chk']) text-success
                                                    @else text-danger @endif fs-4 align-middle"></i>
                                            </div>
                                            <div class="p-1 m-0 text-center no-wrap">{{ $file['name'] }}</i>
                                            </div>
                                            <div class="p-1 m-0 border-start border-secondary"><i
                                                    class="bx bx-trash text-danger fs-4 align-middle"
                                                    wire:click.prevent="delete_file({{ $file['id'] }})"
                                                    style="cursor: pointer;"></i>
                                            </div>
                                        </div>
                                    @endforeach
                                @elseif (!count($show_files) && !count($show_existing_files))
                                    <h4 class="fs-4 fw-bold my-auto text-center">SEM ARQUIVOS</h4>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="card">

                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between">
                            <h4 class="my-auto">Ordens/Ovs</h4>


                        </div>




                        <div class="card-body ">
                            @if ($show_registers)
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed table-striped">
                                        <thead class="table-dark">

                                            <th scope="col">Ordem</th>
                                            <th scope="col">Note</th>
                                            <th scope="col">File</th>
                                            <th scope="col"></th>
                                        </thead>
                                        <tbody>
                                            @foreach ($show_registers as $register)
                                                <tr>

                                                    <td>{{ $register['order'] }}</td>
                                                    <td>{{ $register['note'] }}</td>
                                                    <td class="fw-bold">
                                                        @if (isset($show_files[$register['file_index']]) && !$register['file_online'])
                                                            {{ $show_files[$register['file_index']]['name'] }}
                                                        @elseif ($register['file_online'])
                                                            Aquivo Existente
                                                        @else
                                                            Sem Arquivo
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <i class="bx bx-trash text-danger fs-4 align-middle"
                                                            wire:click.prevent="delete_note({{ $register['id'] }})"
                                                            style="cursor: pointer;" wire:loading.attr="disabled"
                                                            wire:target='delete_note({{ $register['id'] }})'></i>
                                                    </td>
                                                </tr>
                                            @endforeach


                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                    </div>








                    {{-- <div class="mb-3">
                        <label for="search" class="form-label">Observações</label>
                        <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"></textarea>
                    </div> --}}

                </div> {{-- Fim Modal Body --}}



                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click.prevent="to_viability">Enviar</button>
                </div>

            </div>

        </div>

    </div>

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

            const dropzone = document.getElementById('drop-area');
            const dropitem = document.querySelectorAll('[drop-item]');
            const fileInput = document.getElementById('file-input');


            dropzone.addEventListener('dragenter', (event) => {

                let altura = dropzone.offsetHeight;



                dropitem.forEach(function(e) {

                    dropzone.style.minHeight = altura;
                    e.style.display = "none";

                });

                dropzone.style.minHeight = altura + "px";
                document.getElementById('mensagem').style.display = "block";




            });

            dropzone.addEventListener('dragleave', (event) => {


                document.getElementById('mensagem').style.display = "none";

                dropitem.forEach(function(e) {
                    dropzone.style.minHeight = "";
                    e.style.display = "block";
                });

                dropzone.style.minHeight = "";



            });

            dropzone.addEventListener('dragover', (event) => {
                event.preventDefault();


            });

            dropzone.addEventListener('drop', (event) => {
                event.preventDefault();

                const files = event.dataTransfer.files;
                // const formData = new FormData();

                // for (const file of files) {
                //     formData.append('files[]', file);
                // }
                // 1. Atribuir os arquivos ao input oculto

                @this.uploadMultiple('files', [files], successCallback, errorCallback, progressCallback)

                console.log('soltou');

            });
        </script>

        <script>
            window.addEventListener('copyToBoard', function(e) {
                copyToClipboard();
            });



            function copyToClipboard() {
                const textToCopy = document.getElementById('clipboard-data').innerText;
                const textarea = document.createElement('textarea');
                textarea.textContent = textToCopy;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);


            }
        </script>
    @endpush
</div>
