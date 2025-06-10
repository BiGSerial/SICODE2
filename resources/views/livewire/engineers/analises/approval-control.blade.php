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

    <div class="container-fluid mb-4">
        <div class="d-flex flex-wrap align-items-center filter-row" style="gap: .5rem;">

            <!-- 1) Buscar + Multinotas -->
            <div class="input-group flex-grow-1 flex-shrink-1" style="min-width: 200px; max-width: 600px;">
                <input type="text" class="form-control" placeholder="Buscar..." wire:model.debounce.1s="search">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                    data-bs-target="#modal_multi_notas" title="Multinotas">
                    <i class="ri-checkbox-multiple-blank-fill"></i>
                </button>
            </div>

            <!-- 2) Finalizados -->
            <button type="button" class="btn {{ $onlyFinished ? 'btn-success' : 'btn-outline-success' }} flex-shrink-0"
                wire:click="$toggle('onlyFinished')"
                title="{{ $onlyFinished ? 'Mostrar todos' : 'Mostrar somente finalizados' }}">
                Finalizados
            </button>

            <!-- 3) Tipo de Nota (radio) -->
            <div class="btn-group flex-shrink-0" role="group">
                <input type="radio" class="btn-check" name="tipo" id="nota" wire:model="typeNote"
                    value="1">
                <label class="btn btn-outline-primary" for="nota">Nota</label>

                <input type="radio" class="btn-check" name="tipo" id="ov" wire:model="typeNote"
                    value="2">
                <label class="btn btn-outline-primary" for="ov">Ov</label>

                <input type="radio" class="btn-check" name="tipo" id="ambas" wire:model="typeNote"
                    value="">
                <label class="btn btn-outline-primary" for="ambas">Ambas</label>
            </div>

            <!-- 4) Filtros Livewire + Limpar -->
            <div class="d-flex flex-wrap align-items-center controls flex-shrink-1" style="gap: .5rem;">
                <!-- cada filtro é só um dropdown-button -->
                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'operacao',
                        'sendFilter' => '',
                        'model' => 'App\Models\Operation',
                        'column' => 'cenTrab',
                        'filter' => 'Empreiteira',
                        'group_filter' => 'analises',
                        'values' => 'cenTrab',
                        'direction' => 'ASC',
                        'query' => "operacao = '0010'",
                    ],
                    key('operacao')
                )

                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'rubrica',
                        'sendFilter' => '',
                        'model' => 'App\Models\Note',
                        'column' => 'rubrica',
                        'filter' => 'Rubrica',
                        'group_filter' => 'analises',
                        'values' => 'rubrica',
                        'direction' => 'ASC',
                        'query' => '',
                    ],
                    key('rubrica')
                )

                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'region',
                        'sendFilter' => 'city',
                        'model' => 'App\Models\Edp_depc\City',
                        'column' => 'baseConstrucao',
                        'filter' => 'Região',
                        'group_filter' => 'analises',
                        'values' => 'baseConstrucao',
                        'direction' => 'ASC',
                        'query' => '',
                    ],
                    key('region')
                )

                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'city',
                        'sendFilter' => '',
                        'model' => 'App\Models\Edp_depc\City',
                        'column' => 'cidade',
                        'filter' => 'Município',
                        'group_filter' => 'analises',
                        'values' => 'cidade',
                        'direction' => 'ASC',
                        'query' => '',
                    ],
                    key('city')
                )

                <!-- seu dropdown “Mais Filtros” -->
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Usuarios
                        @if ($usersSelected)
                            <span class="badge text-bg-secondary">{{ count($usersSelected) }}</span>
                        @endif


                    </button>
                    <div class="dropdown-menu p-0" style="min-width: 220px;" wire:ignore.self>
                        <!-- Fixed Search Input -->
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control" id="filterSearch" placeholder="Buscar filtros..."
                                wire:model.debounce.1s="userSearch">
                        </div>

                        <!-- Scrollable Content -->
                        <div style="max-height: 300px; overflow-y: auto; scrollbar-width: thin;" class="p-2">
                            @if ($users)
                                @foreach ($users as $theUser)
                                    <div class="form-check mb-2" wire:key='user-{{ $theUser->id }}'>
                                        <input class="form-check-input border-primary" type="checkbox" id="filter1"
                                            wire:model.defer="usersSelected" value="{{ $theUser->id }}">
                                        @php
                                            $name = explode(' ', $theUser->name);
                                            $name = $name[0] . ' ' . end($name);
                                        @endphp
                                        <label class="form-check-label" for="filter1">{{ $name }}</label>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Fixed Buttons -->
                        <div class="p-2 border-top d-flex gap-2">
                            <button class="btn btn-primary btn-sm flex-grow-1"
                                wire:click="applyUserFilter">Aplicar</button>
                            <button class="btn btn-danger btn-sm flex-grow-1"
                                wire:click="clearUserFilter">Limpar</button>
                        </div>
                    </div>
                </div>

                <!-- botão Limpar Todos -->
                @livewire('components.filter.remove-all', ['group_filter' => 'analises'], key('removeAll'))
            </div>

        </div>
    </div>




    @push('scripts')
        <script>
            document.getElementById('filterSearch').addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                document.querySelectorAll('.form-check').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(searchText) ? '' : 'none';
                });
            });
        </script>
    @endpush

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
                            <th class="text-center align-middle">Responsável</th>
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
                            <th class="text-center align-middle">Status Rslc</th>
                            <th class="text-center align-middle"></th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr wire:key="linha-{{ $list->id }}">
                                <td class="text-center align-middle">
                                    <div class="form-check">
                                        <input type="checkbox"
                                            class="form-check-input border-1 border-secondary select"
                                            wire:model.defer="selected" value='{{ $list->id }}'>

                                    </div>
                                </td>
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
                                    {{ $list->type_note == 2 ? $list->nstats : $list->centerjob }}</td>
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
                                    $bdays = $list->dt_status->diffInDaysFiltered(function (Carbon $date) {
                                        return $date->isWeekday(); // Segunda a sexta
                                    }, now());

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

                                        if ($reclaim->created_at->isToday()) {
                                            $rclBdays = 0;
                                        }

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
                                    @if ($reclaim && $reclaim->production)
                                        <span
                                            class="badge {{ Notestatus::status($reclaim->production->status)->colorbg }}">{{ Notestatus::status($reclaim->production->status)->status }}</span>
                                    @elseif ($reclaim && !$reclaim->production)
                                        <span class="badge text-secondary">Não Depachado</span>
                                    @else
                                        ----
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <div class="row">
                                        <span>
                                            <i class="ri-play-circle-line text-success fs-4 fw-bold"
                                                wire:click.bounced.500ms.prevent="$emitTo('responsible.actions.reject-project', 'getInfoResponse', {{ $list->id }})"
                                                style="cursor: pointer;"></i>
                                        </span>
                                        <span>
                                            <i class="ri-delete-bin-line text-danger fs-4 fw-bold"
                                                wire:click.bounced.500ms.prevent="deleteApproval({{ $list->id }})"
                                                style="cursor: pointer;"></i>
                                        </span>
                                    </div>
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
