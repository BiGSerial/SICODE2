@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />


    <div class="d-flex align-items-center justify-content-between mb-3">
        <!-- Campo de busca com botão e tooltip -->
        <div class="input-group me-3">
            <input type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar">
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="tooltip" title="multinotas">
                <i class="bi bi-search"></i>
            </button>
        </div>

        <!-- Botões do tipo radio para seleção individual -->
        <div class="btn-group me-3" role="group" aria-label="Seleção de Opções">
            <input type="radio" class="btn-check" name="selecao" id="nota" autocomplete="off"
                wire:model="typeNote" value="1">
            <label class="btn btn-outline-primary" for="nota">Nota</label>

            <input type="radio" class="btn-check" name="selecao" id="ov" autocomplete="off"
                wire:model="typeNote" value="2">
            <label class="btn btn-outline-primary" for="ov">Ov</label>

            <input type="radio" class="btn-check" name="selecao" id="ambas" autocomplete="off"
                wire:model="typeNote" value="">
            <label class="btn btn-outline-primary" for="ambas">Ambas</label>
        </div>

        <!-- Quatro botões alinhados -->
        <div class="btn-group" role="group" aria-label="Ações">
            @livewire('components.filter.filter', ['myKey' => 'operacao', 'sendFilter' => '', 'model' => 'App\Models\Operation', 'column' => 'cenTrab', 'filter' => 'Empreiteira', 'group_filter' => 'analises', 'values' => 'cenTrab', 'direction' => 'ASC', 'query' => "operacao = '0010'"], key('operacao'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'analises', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'analises', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'analises', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'analises', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'analises'], key('removeAll'))
        </div>
    </div>

    @if ($lists->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center mt-3">

            <div>
                {{ $lists->links() }}
            </div>
            <div>
                Exibindo página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}, total de
                {{ $lists->total() }} registros.
            </div>
        </div>
    @endif
    <div class="card edp-bg-gray">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">OBRAS ANALISE DE PROJETO</h4>
        </div>
        @if ($lists->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed table-sm">
                    <thead>
                        <tr class="table-dark">
                            <th class="text-center align-middle"></th>
                            <th class="text-center align-middle">Nota</th>
                            <th class="text-center align-middle">Ordem</th>
                            <th class="text-center align-middle">Files</th>
                            <th class="text-center align-middle">Rubrica</th>
                            <th class="text-center align-middle">Município</th>
                            <th class="text-center align-middle">Empreiteira</th>
                            <th class="text-center align-middle">Status</th>
                            <th class="text-center align-middle">Tempo</th>
                            <th class="text-center align-middle"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr>
                                <td class="text-center align-middle">

                                </td>
                                <td class="text-center align-middle">{{ $list->note }}</td>
                                <td class="text-center align-middle">
                                    @if ($list->orders->isNotEmpty())
                                        @foreach ($list->orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle"></td>
                                <td class="text-center align-middle">{{ $list->rubrica }}</td>
                                <td class="text-center align-middle">{{ $list->lexp }}</td>
                                <td class="text-center align-middle">
                                    @if ($list->orders->isNotEmpty())
                                        {{ isset($list->orders->last()->operations->first()->cenTrab) ? $list->orders->last()->operations->first()->cenTrab : '---' }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->type_note == 2 ? $list->nstats : $list->centerjob }}</td>
                                @php
                                    $color = '';
                                    $days = '';

                                    if ($list->type_note == 2) {
                                        $days = $list->dt_status->diffInDays(now());

                                        if ($days > 5) {
                                            $color = 'text-bg-danger';
                                        } elseif ($days <= 3) {
                                            $color = 'text-bg-success';
                                        } else {
                                            $color = 'text-bg-warning';
                                        }
                                    }
                                @endphp
                                <td class="text-center align-middle {{ $color }}">
                                    {{ $days }}
                                </td>
                                <td class="text-center align-middle">

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <h5 class="text-center">SEM OBRA PARA AVALIAÇÃO DE PROJETO</h5>
            </div>
        @endif
    </div>
    @if ($lists->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center mt-3">

            <div>
                {{ $lists->links() }}
            </div>
            <div>
                Exibindo página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}, total de
                {{ $lists->total() }} registros.
            </div>
        </div>
    @endif

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="modal_multi_notas" tabindex="-1" aria-labelledby="exampleModalLabel"
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





</div>
