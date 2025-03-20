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
            <div class="row align-items-center g-2">

                <!-- Per Page Select -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="perPage" class="visually-hidden">Itens por página</label>
                    <select name="perPage" id="perPage" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                    <label for="search" class="visually-hidden">Buscar</label>
                    <input type="text" class="form-control border border-secondary" id="search"
                        placeholder="Buscar" wire:model.debounce.2s="search">
                </div>

                <!-- Month Select -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="month" class="visually-hidden">Mês</label>
                    <input type="month" id="month" class="form-control border border-secondary" wire:model="month"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Mês de Referencia"
                        max="{{ \Carbon\Carbon::now()->format('Y-m') }}" min="2023-05">
                </div>

                <!-- Date In Input -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="date_in" class="visually-hidden">Data Inicial</label>
                    <input type="date" id="date_in" class="form-control border border-secondary"
                        wire:model="date_in" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Inicial" min="2023-05-01">
                </div>

                <!-- Date Out Input -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="date_out" class="visually-hidden">Data Final</label>
                    <input type="date" id="date_out" class="form-control border border-secondary"
                        wire:model="date_out" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Data Final" min="2023-05-01">
                </div>

                <!-- Clean Button -->
                <div class="col-12 col-md-3 col-lg-1">
                    <button class="btn btn-danger btn-sm w-100" wire:click.prevent='cleanAll()' data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Limpar Busca por Datas">
                        <i class="ri-find-replace-line fs-5 align-middle"></i>
                    </button>
                </div>

                <!-- Filters -->
                <div class="col-12 col-lg-12 d-flex flex-wrap justify-content-end gap-2">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner_forms', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'partner_forms', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'partner_forms', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner_forms'], key('removeAll'))
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
                        <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">OBRAS INFORMADAS</h4>
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
                            <th class="text-center" scope="col">Note</th>
                            <th class="text-center" scope="col">Ordens</th>
                            <th class="text-center" scope="col">Rubrica</th>
                            <th class="text-center" scope="col">Files</th>
                            <th class="text-center" scope="col">Equipamentos</th>
                            <th class="text-center" scope="col">Alteração</th>
                            <th class="text-center" scope="col">Equipe WPA</th>
                            <th class="text-center" scope="col">Responsável</th>
                            <th class="text-center" scope="col">Conclusão Informada</th>
                            <th class="text-center" scope="col">Entregue Em</th>
                            <th class="text-center" scope="col">ADS</th>
                            <th class="text-center" scope="col">DT ADS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $color = '';
                                if ($list->Adsform || $list->note->oldAds->isNotEmpty()) {
                                    $color = 'table-success';
                                }
                            @endphp
                            <tr wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $list }})"
                                wire:key="{{ $list->id }}" class="{{ $color }}">
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
                                    {!! $list->Equipment->count() ? "<span class='badge text-bg-dark'>" . $list->Equipment->count() . '</span>' : '' !!}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->changes ? 'SIM' : 'NÂO' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->team ? $list->team : 'Desconhecido' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->responsible ? $list->responsible : 'Desconhecido' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->date ? date('d/m/Y', strToTime($list->date)) : 'Desconhecido' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->informed_at ? date('d/m/Y', strToTime($list->informed_at)) : 'Desconhecido' }}
                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->Adsform || $list->note->oldAds->isNotEmpty())
                                        SIM
                                    @else
                                        NÃO
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->Adsform)
                                        {{ $list->Adsform ? $list->Adsform->created_at->format('d/m/Y H:i:s') : '---' }}
                                    @elseif($list->note->oldAds->isNotEmpty())
                                        {{ $list->note->oldAds->isNotEmpty() ? $list->note->oldAds->first()->date->format('d/m/Y H:i:s') : '---' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- LivewireComponent --}}
    @livewire('partner.show.show-work-form', key('FormModdalShow'))

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
