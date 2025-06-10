@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;

@endphp
<div>
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

        <!-- Botão de filtro para somente finalizados -->
        <div class="me-3">
            <button type="button" class="btn {{ $onlyFinished ? 'btn-success' : 'btn-outline-success' }}"
                wire:click="$toggle('onlyFinished')"
                title="{{ $onlyFinished ? 'Mostrar todos' : 'Mostrar somente finalizados' }}">

                Finalizados
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
        <div class="card-header d-flex justify-content-between align-items-center text-bg-danger">
            <h4 class="fs-4 mb-0">OBRAS VALIDAÇÃO DE PROJETOS</h4>
            <div>
                <!-- Botão Exportar -->
                <button type="button" class="btn btn-primary" wire:click.prevent="export_excel"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="export_excel">Exportar</span>
                    <span wire:loading wire:target="export_excel">
                        <i class="ri-loader-line animate-spin"></i> Carregando...
                    </span>
                </button>

                <!-- Botão Aprovar em Massa -->
                <button type="button" class="btn btn-primary" wire:click.prevent="preMassApprove" id="massApprove"
                    @disabled(!count($selected)) wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="preMassApprove">Aprovar em Massa</span>
                    <span wire:loading wire:target="preMassApprove">
                        <i class="ri-loader-line animate-spin"></i> Carregando...
                    </span>
                </button>
            </div>
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
                            @can('superadm')
                                <th class="text-center align-middle">Responsável</th>
                            @endcan
                            <th class="text-center align-middle">Nota</th>
                            <th class="text-center align-middle">Ordem</th>
                            <th class="text-center align-middle">Files</th>
                            <th class="text-center align-middle">Rubrica</th>
                            <th class="text-center align-middle">Município</th>
                            <th class="text-center align-middle">Empreiteira</th>
                            <th class="text-center align-middle">Sts Nota</th>
                            <th class="text-center align-middle">Tempo</th>
                            <th class="text-center align-middle">Em Atvd</th>
                            <th class="text-center align-middle">Em Rslc</th>
                            <th class="text-center align-middle">Motivo Rslc</th>
                            <th class="text-center align-middle">Status</th>
                            <th class="text-center align-middle"></th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr wire:key="linha-{{ $list->id }}" onclick="handleRowClick(this)" class="">
                                <td class="text-center align-middle">
                                    <div class="form-check">
                                        <input type="checkbox"
                                            class="form-check-input border-1 border-secondary select"
                                            wire:model.defer="selected" value="{{ $list->id }}">
                                    </div>
                                </td>
                                @can('superadm')
                                    @php
                                        $name = '---';
                                        if ($list->approval) {
                                            $name = explode(' ', $list->approval->user->name);
                                            $name = $name[0] . ' ' . end($name);
                                        }
                                    @endphp
                                    <td class="text-center align-middle">
                                        {{ $list->approval ? $list->approval->user->name : '---' }}
                                    </td>
                                @endcan
                                <td
                                    class="text-center align-middle @if ($list->is45) text-bg-warning @endif">
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
                                <td class="text-center align-middle">
                                    @if ($list->orders->isNotEmpty())
                                        @foreach ($list->orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <x-files.select-download-list :files='$list->Files' />
                                </td>
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
                                    {{ $list->type_note == 2 ? $list->nstats : $list->centerjob }}
                                </td>
                                @php

                                    $color = '';
                                    $attColor = '';
                                    $rclColor = '';
                                    $days = '';
                                    $attDays = '';
                                    $reclaim = $list->approval->reclaims->isNotEmpty()
                                        ? $list->approval->reclaims->last()
                                        : null;
                                    $rclDays = '---';

                                    $days = $list->dt_status->startOfDay()->diffInDays(Carbon::now());
                                    $bdays = $list->dt_status
                                        ->startOfDay()
                                        ->diffInDaysFiltered(function (Carbon $date) {
                                            return $date->isWeekday(); // Segunda a sexta
                                        }, now()->startOfDay());

                                    if ($bdays > 2) {
                                        $color = 'text-bg-danger';
                                    } elseif ($bdays <= 1) {
                                        $color = 'text-bg-success';
                                    } else {
                                        $color = 'text-bg-warning';
                                    }

                                    $attDays = $list->approval->created_at->startOfDay()->diffInDays(Carbon::now());
                                    $attBdays = $list->approval->created_at
                                        ->startOfDay()
                                        ->diffInDaysFiltered(function (Carbon $date) {
                                            return $date->isWeekday(); // Segunda a sexta
                                        }, now()->startOfDay());

                                    if ($attBdays > 2) {
                                        $attColor = 'text-bg-danger';
                                    } elseif ($attBdays <= 1) {
                                        $attColor = 'text-bg-success';
                                    } else {
                                        $attColor = 'text-bg-warning';
                                    }

                                    if (
                                        $reclaim &&
                                        ($rclDays = $reclaim->created_at->startOfDay()->diffInDays(Carbon::now()))
                                    ) {
                                        $rclBdays = $reclaim->created_at
                                            ->startOfDay()
                                            ->diffInDaysFiltered(function (Carbon $date) {
                                                return $date->isWeekday(); // Segunda a sexta
                                            }, now()->startOfDay());

                                        if ($rclBdays > 2) {
                                            $rclColor = 'text-bg-danger';
                                        } elseif ($rclBdays <= 1) {
                                            $rclColor = 'text-bg-success';
                                        } else {
                                            $rclColor = 'text-bg-warning';
                                        }
                                    }
                                @endphp
                                <td class="text-center align-middle border-right border-1 {{ $color }}">
                                    {{ $days }}
                                </td>
                                <td class="text-center align-middle border-right border-1 {{ $attColor }}">
                                    {{ $attDays }}
                                </td>
                                <td class="text-center align-middle {{ $rclColor }}">
                                    {{ $rclDays }}
                                </td>
                                <td class="text-center align-middle ">
                                    @if ($reclaim)
                                        {{ $reclaim->category }}
                                    @else
                                        ----
                                    @endif
                                </td>
                                <td class="text-center align-middle ">
                                    @if ($reclaim && $reclaim->production)
                                        <span
                                            class="badge {{ Notestatus::status($reclaim->production->status)->colorbg }}">
                                            {{ Notestatus::status($reclaim->production->status)->status }}
                                        </span>
                                    @elseif ($reclaim && !$reclaim->production)
                                        <span class="badge text-secondary">Não Depachado</span>
                                    @else
                                        ----
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    {{-- <span wire:loading wire:target="onlySelected({{ $list->id }})">
                                        <i class="ri-loader-line text-success fs-4 fw-bold animate-spin"
                                            style="cursor: not-allowed;"></i>
                                    </span>
                                    <span wire:loading.remove wire:target="onlySelected({{ $list->id }})">
                                        <i class="ri-checkbox-circle-line text-success fs-4 fw-bold"
                                            wire:click.debounce.500ms.prevent="onlySelected({{ $list->id }})"
                                            style="cursor: pointer;"></i>
                                    </span> --}}
                                    <span>
                                        <i class="ri-play-circle-line text-success fs-4 fw-bold"
                                            wire:click.bounced.500ms.prevent="$emitTo('responsible.actions.reject-project', 'getInfoResponse', {{ $list->id }})"
                                            style="cursor: pointer;"></i>
                                    </span>
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

    {{-- Livewire Components --}}
    @livewire('responsible.actions.reject-project', key('rejectProject'))

    <script>
        function handleRowClick(row) {
            const oldRow = document.querySelector('.row-active');

            if (oldRow) {
                oldRow.classList.remove('table-primary');
                oldRow.classList.remove('row-active');
            }

            if (row != oldRow) {
                row.classList.add('table-primary');
                row.classList.add('row-active');
            } else {
                row.classList.remove('table-primary');
                row.classList.remove('row-active');
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.select');
            const massApproveBtn = document.getElementById('massApprove');

            function updateMassApprove() {
                let selectedCount = 0;
                checkboxes.forEach(chk => {
                    if (chk.checked) {
                        selectedCount++;
                    }
                });
                if (selectedCount > 1) {
                    massApproveBtn.removeAttribute('disabled');
                } else {
                    massApproveBtn.setAttribute('disabled', true);
                }
            }

            checkboxes.forEach(chk => {
                chk.addEventListener('change', updateMassApprove);
            });

            // Initialize button state on page load
            updateMassApprove();
        });
    </script>



</div>
