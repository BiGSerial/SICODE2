@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
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
    <x-show-loading />
    <x-showselected :count="$selected" />

    <div class="d-flex align-items-center justify-content-between mb-3">
        <!-- Campo de busca com botão e tooltip -->
        <div class="input-group me-3">
            <input type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar"
                wire:model.debounce.1s="search">
            <span data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Multinotas">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                    data-bs-target="#modal_multi_notas" title="Multinotas">
                    <i class="ri-checkbox-multiple-blank-fill"></i>
                </button>
            </span>
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

        <!-- Botão SEM ATRIBUIÇÃO com indicador de ativado/desativado -->
        <div class="me-3">
            <button type="button" class="btn btn-sm {{ $noAttribution ? 'btn-success' : 'btn-outline-secondary' }}"
                wire:click="toggleAtrtibution">
                SEM ATRIBUIÇÃO

            </button>
        </div>

        <!-- Quatro botões alinhados -->
        <div class="btn-group" role="group" aria-label="Ações">
            @livewire('components.filter.filter', ['myKey' => 'operacao', 'sendFilter' => '', 'model' => 'App\Models\Operation', 'column' => 'cenTrab', 'filter' => 'Empreiteira', 'group_filter' => 'analises', 'values' => 'cenTrab', 'direction' => 'ASC', 'query' => "operacao = '0010'"], key('operacao'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'analises', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regiao', 'group_filter' => 'analises', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('region'))
            {{-- @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'analises', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional')) --}}
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
        <div
            class="card-header d-flex justify-content-between align-items-center edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4 mb-0">OBRAS À VALIDAR PROJETO</h4>
            {{-- <button type="button" class="btn btn-outline-info" wire:click.prevent="preAtt"
                @disabled(!count($selected))>Assumir em
                Massa</button> --}}
        </div>
        @if ($lists->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed table-sm">
                    <thead>
                        <tr class="table-dark">
                            <th class="text-center align-middle">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:click.prevent="setSelectAll"
                                        id="selectAll" title="Select All" @checked($this->chkAllSelected($lists))>

                                </div>
                            </th>
                            <th class="text-center align-middle">Nota</th>
                            <th class="text-center align-middle">Ordem</th>
                            <th class="text-center align-middle">Files</th>
                            <th class="text-center align-middle">Rubrica</th>
                            <th class="text-center align-middle">Priority</th>
                            <th class="text-center align-middle">Grupo</th>
                            <th class="text-center align-middle">Município</th>
                            <th class="text-center align-middle">Empreiteira</th>
                            <th class="text-center align-middle">Status</th>
                            <th class="text-center align-middle">Tempo</th>
                            <th class="text-center align-middle">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $user = '';
                                $colorAtt = '';

                                if ($list->approval) {
                                    $user = explode(' ', $list->approval->user->name);
                                    $user = $user[0] . ' ' . end($user);
                                    $colorAtt = 'text-bg-info';
                                }
                            @endphp
                            <tr wire:key="linha-{{ $list->id }}">
                                <td class="text-center align-middle {{ $colorAtt }}">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input border-1 border-secondary"
                                            wire:model.defer="selected" value='{{ $list->id }}'>

                                    </div>
                                </td>
                                <td
                                    class="text-center align-middle  @if ($list->is45) text-bg-warning @else {{ $colorAtt }} @endif">
                                    {{ $list->note }}
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
                                <td class="text-center align-middle {{ $colorAtt }}">
                                    @if ($list->orders->isNotEmpty())
                                        @foreach ($list->orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle {{ $colorAtt }}">
                                    <x-files.select-download-list :files='$list->Files' />
                                </td>
                                <td class="text-center align-middle {{ $colorAtt }}">{{ $list->rubrica }}</td>
                                <td class="text-center align-middle {{ $colorAtt }}">{{ $list->txpriority }}</td>
                                <td class="text-center align-middle {{ $colorAtt }}">
                                    {{ $list->group5 }}{{ $list->txpriority }}</td>
                                <td class="text-center align-middle {{ $colorAtt }}">{{ $list->lexp }}</td>
                                <td class="text-center align-middle {{ $colorAtt }}">
                                    @if ($list->orders->isNotEmpty())
                                        {{ isset($list->orders->last()->operations->first()->cenTrab) ? $list->orders->last()->operations->first()->cenTrab : '---' }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle {{ $colorAtt }}">
                                    {{ $list->type_note == 2 ? $list->nstats : $list->centerjob }}</td>
                                @php
                                    $color = '';
                                    $days = '';

                                    $days = $list->dt_status->diffInDays(now());

                                    if ($days > 5) {
                                        $color = 'text-bg-danger';
                                    } elseif ($days <= 3) {
                                        $color = 'text-bg-success';
                                    } else {
                                        $color = 'text-bg-warning';
                                    }

                                @endphp
                                <td class="text-center align-middle {{ $color }}">
                                    {{ $days }}
                                </td>
                                <td class="text-center align-middle {{ $colorAtt }}">

                                    @if ($user)
                                        {{ $user }}
                                    @else
                                        <span wire:loading wire:target="onlySelected({{ $list->id }})">
                                            <i class="ri-loader-line text-success fs-4 fw-bold animate-spin"
                                                style="cursor: not-allowed;"></i>
                                        </span>
                                        <span wire:loading.remove wire:target="onlySelected({{ $list->id }})">
                                            <i class="ri-play-circle-line text-success fs-4 fw-bold"
                                                wire:click.debounce.500ms.prevent="onlySelected({{ $list->id }})"
                                                style="cursor: pointer;"></i>
                                        </span>
                                    @endif
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
    <div wire:ignore.self class="modal fade" id="modal_multi_notas" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">


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
