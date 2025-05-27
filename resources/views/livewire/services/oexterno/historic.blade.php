@php
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
    use App\Helpers\SelectOptions;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    {{-- START SearchBar and Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-2">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.2s="search">
                </div>
                <div class="col-2">
                    <input type="date" class="form-control border border-secondary" wire:model.debounce.2s="dtIn">
                </div>
                <div class="col-2">
                    <input type="date" class="form-control border border-secondary" wire:model.debounce.2s="dtOut"
                        min="{{ $dtIn }}">
                </div>


                <div class="col-5 d-flex justify-content-end">
                    <div class="form-check form-check-inline align-middle">
                        <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote"
                            value="1">
                        <label class="form-check-label" for="inlineRadio1">Nota</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote"
                            value="2">
                        <label class="form-check-label" for="inlineRadio1">OV</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote"
                            value="">
                        <label class="form-check-label" for="inlineRadio1">Ambos</label>
                    </div>
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'oexterno', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'oexterno', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'oexterno', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}{{-- START SearchBar and Filters --}}



    <div class="row">

        @if (!$lists->count())
        @elseif ($lists->count())
            <div class="col-6">
                {{ $lists->links() }}
            </div>
        @endif
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.

            </span>
        </div>


    </div>
    <div class="card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM DADOS EM {{ $service->service }}</h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-secondary">HISTÓRICO DE {{ mb_strtoupper($service->service) }}

            </h4>
            <div class="table-responsive">
                <table class="table table-sm  table-condensed table-hover table-striped">
                    <thead class="table-dark">
                        <tr class="sticky-top">
                            <th scope="col" class="fw-bold text-center">Usuario</th>
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">Files</th>
                            <th scope="col" class="fw-bold text-center">Protocolo</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center">Entidade</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Descrição</th>
                            <th scope="col" class="fw-bold text-center">Data</th>
                            {{-- <th scope="col" class="fw-bold text-center">Pze</th> --}}


                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php

                                $daysleft = (new DaysLeft($list))->getDaysLeft();

                            @endphp
                            {{-- @dump($list->Productions) --}}
                            <tr class="align-middle" wire:dblclick="navigateTo('{{ $list->note }}')"
                                wire:key='hist-{{ $list->id }}'>

                                <td class="fw-light text-center">{{ $list->externals->first()?->user?->name }}</td>
                                <td class="fw-bold copy-text text-center" data-value="{{ $list->note }}">
                                    {{ $list->note }}
                                </td>


                                <td class="text-center align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    <x-files.select-download-list :files='$list->Files' />
                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->externals->first())
                                        <p class="my-0 py-0"><i class="ri-file-text-fill text-success fs-5"></i></p>
                                        <p class="my-0 py-0">
                                            {{ $list->externals->first()->Protocols->count() ? $list->externals->first()->Protocols->last()->protocol : '' }}
                                        </p>
                                    @else
                                        <i class="ri-file-text-line text-danger fs-5"></i>
                                    @endif

                                </td>
                                <td class="fw-light text-center">
                                    {{ $list->externals->first()->Comments->count() ? $list->externals->first()->Comments->last()->title : ' --- ' }}
                                </td>
                                <td class="fw-light text-center">
                                    {{ $list->externals->first()->completed ? 'SIM' : 'NÃO' }}
                                </td>
                                <td class="fw-light text-center">{{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light text-center">{{ $list->rubrica }}</td>
                                <td class="fw-light text-center">{{ $list->lexp }}</td>
                                <td class="fw-light text-center">{{ $list->material }}</td>

                                <td class="fw-light text-center">
                                    {{ date('d/m/Y H:i:s', strToTime($list->externals->first()->updated_at)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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

    {{-- Livewire Components --}}
    @livewire('services.oexterno.actions.protocols', key('externals->first()_protocols'))
</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy',
                    `Valor "${value}" copiado para a área de transferência.`);
                // alert(`Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    </script>
@endpush
