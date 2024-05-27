@php
    use Carbon\Carbon;
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
            transition: width 0.3s ease;
            /* Adicionando uma transição suave */
        }

        .progress-cell .progress-text {
            position: relative;
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
            @livewire('components.filter.filter', ['myKey' => 'empreiteira', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'hiring', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('empreiteira'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'hiring', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'regiao', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'hiring', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'cidade', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'hiring', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'hiring'], key('removeAll'))
        </div>

    </div>

    {{-- @dump($lists) --}}

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
            <div class="card-header text-bg-danger">
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
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_to_hiring'><i
                                class="ri-checkbox-multiple-fill align-middle"></i> Contratar</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line align-middle"></i> Exportar</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='downloadZip'><i
                                class="ri-file-zip-line align-middle"></i> DownloadFiles</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th class="align-middle text-center">
                                    <input class="form-check-input border-1 border-secondary" type="checkbox"
                                        wire:model="selectAll">
                                </th>
                                <th scope="col" class="fw-bold text-center">Nota</th>
                                <th scope="col" class="fw-bold text-center">Ordem</th>
                                <th scope="col" class="fw-bold text-center">Files</th>
                                <th scope="col" class="fw-bold text-center">Rubrica</th>
                                <th scope="col" class="fw-bold text-center">Municipio</th>
                                <th scope="col" class="fw-bold text-center">Empreitaira</th>
                                <th scope="col" class="fw-bold text-center">Responsável</th>
                                <th scope="col" class="fw-bold text-center">Dt Envio</th>
                                <th scope="col" class="fw-bold text-center">Dt Estimada</th>
                                <th scope="col" class="fw-bold text-center">Restantes</th>
                                <th scope="col" class="fw-bold text-center">Dt Real Viab</th>
                                <th scope="col" class="fw-bold text-center">Contratado</th>
                                <th scope="col" class="fw-bold text-center">Status</th>
                                <th scope="col" class="fw-bold text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists->sortBy(function ($note) {
        // Acessar a primeira 'Viability' e o campo 'sended_at'
        return $note->Viabilities->first()->sended_at ?? null;
    }) as $list)
                                <tr wire:key="acompany-{{ $list }}">
                                    <td class="align-middle text-center"><input
                                            class="form-check-input border-1 border-secondary" type="checkbox"
                                            wire:model.defer="selected"></td>
                                    <td class="align-middle text-center fw-bold">{{ $list->note }}</td>

                                    <td class="align-middle text-center">
                                        @if ($list->Viabilities->count())
                                            @foreach ($list->Viabilities->sortBy('Order.ordem') as $viab)
                                                <p class="my-1 py-0">
                                                    {{ $viab->Order->ordem }}
                                                </p>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <x-files.select-download-list :files='$list->Files' />
                                    </td>
                                    <td class="align-middle text-center">{{ $list->rubrica }}</td>
                                    <td class="align-middle text-center">{{ $list->lexp }}</td>
                                    <td class="align-middle text-center">
                                        {{ isset($list->Viabilities->last()->Company->name) ? $list->Viabilities->last()->Company->name : '---' }}
                                    </td>
                                    <td class="align-middle text-center">
                                        {{ isset($list->Viabilities->last()->Engineer->name) ? $list->Viabilities->last()->Engineer->name : '---' }}
                                    </td>
                                    <td class="align-middle text-center fw-bold">
                                        {{ isset($list->Viabilities->last()->sended_at) ? Carbon::parse($list->Viabilities->last()->sended_at)->format('d/m/Y') : '---' }}
                                    </td>
                                    @php
                                        $daysAdded = $list->Viabilities->last()->Days->count();

                                        if (isset($list->Viabilities->last()->sended_at) && $daysAdded) {
                                            $days = 7 + $list->Viabilities->last()->Days->sum('days');
                                            $estimated = Carbon::parse($list->Viabilities->last()->sended_at)->addDays(
                                                $days,
                                            );
                                        } else {
                                            $estimated = '---';
                                        }
                                    @endphp
                                    <td class="align-middle text-center fw-bold text-danger">
                                        {{ $estimated != '---' ? $estimated->format('d/m/Y') : '---' }}
                                    </td>
                                    <td class="align-middle text-center fw-bold">
                                        {{ $estimated != '---' ? Carbon::now()->startOfDay()->diffInDays($estimated->startOfDay(), false) : '---' }}
                                    </td>
                                    <td class="align-middle text-center fw-bold text-primary">
                                        {{ isset($list->Viabilities->last()->returned_at) ? Carbon::parse($list->Viabilities->last()->returned_at)->format('d/m/Y') : '---' }}
                                    </td>
                                    <td class="align-middle text-center">
                                        @if ($list->Viabilities->count())
                                            @if ($list->Viabilities->last()->hired)
                                                <span class="text-success fw-bold">SIM</span>
                                            @else
                                                <span class="text-danger fw-bold">NÃO</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        @if ($list->Viabilities->count())
                                            <span
                                                class="badge text-wrap aling-middle {{ Viabilitiesstatus::status($list->Viabilities->first()->status)->colorbg }}"
                                                style="width: 6rem;">{{ mb_strToUpper(Viabilitiesstatus::status($list->Viabilities->first()->status)->status) }}</span>
                                        @endif
                                    </td>

                                    <td class="align-middle text-center">
                                        <i class="ri-pencil-fill text-primary fs-5" style="cursor: pointer;"
                                            wire:click.prevent="$emitTo('construction.hiring.actions.edit', 'edit_hiring', {{ $list->id }})"></i>
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

    {{-- Modal para Contratação de Obras --}}
    <div wire:ignore.self class="modal fade" id="hiring_jobs" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog modal-lg">

            <div class="modal-content edp-bg-stategrey-50">

                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="fw-bold">
                        CONTRATAÇÃO DE OBRAS
                    </h4>
                </div>

                <div class="modal-body">
                    <div class="table-responsible">
                        <table class="table table-sm table-striped">
                            <thead class="table-dark">
                                <th scope="col" class="text-center"><input class="form-check-input"
                                        type="checkbox" wire:model="selectAllHirings"></th>
                                <th scope="col">Obra</th>
                                <th scope="col">Ordens</th>
                                <th scope="col">Empreiteira</th>
                            </thead>
                            <tbody>


                                <div class="text-bg-light rounded p-2 mb-3 shadown shadown-sm">
                                    <p class="text-center fs-4 fw-bold">LEMBRETE</p>
                                    <p class="fs-5">

                                        "A contratação de obras no SICODE é apenas para a sumarização de processos.
                                        Espera-se que tenha realizado todos os procedimentos necessários no SAP desta
                                        atividade antes de 'contratar' ou 'encerrar' a atividade no SICODE."
                                    </p>
                                </div>
                                <p class="fs-4 fw-bold mb-3">
                                    Selecione as Obras Aptas a Contratar
                                </p>
                                @if ($hirings && $hirings->count())
                                    @foreach ($hirings as $hiring)
                                        <tr>
                                            <th scope="row" class="text-center align-middle"><input
                                                    class="form-check-input border border-1 border-secondary "
                                                    type="checkbox" wire:model.defer="hiringSelected"
                                                    value="{{ $hiring->id }}"></th>
                                            <td class="fw-bold align-middle">{{ $hiring->note }}</td>
                                            <td class="align-middle">
                                                @if ($hiring->Viabilities->count())
                                                    @foreach ($hiring->Viabilities as $viab)
                                                        <p class="my-0 py-0">{{ $viab->Order->ordem }}</p>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <p class="my-0 py-0">{{ $hiring->Viabilities->last()->Company->name }}
                                                </p>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button type="button" class="btn btn-danger" wire:click.prevent="closeall">CANCELAR</button>
                    <button type="button" class="btn btn-info" wire:click.prenvet="export_excel_hiring">EXPORTAR
                        LISTA</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prenvet="to_contract">CONTRATAR</button>
                </div>
            </div>

        </div>

    </div>

    {{-- Livewire Components --}}
    @livewire('construction.hiring.actions.edit', key('hiring-edit'))

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
    @endpush
</div>
