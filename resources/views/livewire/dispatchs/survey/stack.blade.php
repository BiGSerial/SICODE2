@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;
    use App\Helpers\DaysLeft;

    $filtersConfig = [
        'company' => [
            'type' => 'single', // 'single' | 'multi'
            'button_label' => 'Empresas',
            'model' => \App\Models\Company::class,
            'value_field' => 'id',
            'label_field' => 'name',
            'query' => fn($q, $state) => $q->where('active', 1),
            'placeholder' => 'Todas',
            'debounce' => 250, // ms
        ],

        'city' => [
            'type' => 'multi',
            'button_label' => 'Cidades',
            'model' => \App\Models\City::class,
            'value_field' => 'id',
            'label_field' => 'name',
            'depends_on' => ['company'], // restringe cidades pela(s) empresa(s) selecionada(s)
            'query' => function ($q, $state) {
                // $state = valores atuais de TODOS filtros
                if (!empty($state['company'])) {
                    $q->whereIn('company_id', (array) $state['company']);
                }
                return $q->where('enabled', 1);
            },
            'placeholder' => 'Todas',
            'debounce' => 250,
        ],

        'status' => [
            'type' => 'multi',
            'button_label' => 'Status',
            'values' => [
                // pode ser estático também
                ['value' => 'open', 'label' => 'Aberta'],
                ['value' => 'paused', 'label' => 'Pausada'],
                ['value' => 'done', 'label' => 'Concluída'],
            ],
            'placeholder' => 'Todos',
        ],
    ];

@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="mb-4">
        <div class=" d-flex mb-3 justify-content-end align-middle py-4">

            <div class="input-group mb-3">
                <span class="input-group-text bg-primary text-white">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control form-control-lg" placeholder="Pesquisar..."
                    wire:model.debounce.300ms="search" aria-label="Pesquisar">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                    data-bs-target="#multiSearchModal" title="Busca múltipla">
                    <i class="ri-checkbox-multiple-blank-line"></i>
                </button>
                <button class="btn btn-outline-secondary" type="button" wire:click="resetFilters"
                    title="Limpar filtros">
                    <i class="ri-filter-off-line"></i>
                </button>
            </div>
            {{-- <livewire:components.filter.smart-filters :config="$filtersConfig" wire:key="filters-main" /> --}}
            <div class="btn-group btn-group-sm mx-2 align-self-center" role="group" aria-label="Tipo de nota">
                <input type="radio" class="btn-check" name="note_type" id="note_type_nota" wire:model="note_type"
                    value="1">
                <label class="btn btn-outline-primary btn-sm" for="note_type_nota">Nota</label>

                <input type="radio" class="btn-check" name="note_type" id="note_type_ov" wire:model="note_type"
                    value="2">
                <label class="btn btn-outline-primary btn-sm" for="note_type_ov">OV</label>

                <input type="radio" class="btn-check" name="note_type" id="note_type_ambos" wire:model="note_type"
                    value="">
                <label class="btn btn-outline-primary btn-sm" for="note_type_ambos">Ambos</label>
            </div>


            @livewire('components.filter.filter', ['myKey' => 'regiao', 'sendFilter' => 'regional', 'model' => 'App\Models\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'survey', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('regiao'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'survey', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\City', 'column' => 'rdMunicipio', 'filter' => 'Municipios', 'group_filter' => 'survey', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'survey'], key('removeAll'))

        </div>
    </div>

    {{-- <x-showselected :count="$selected" /> --}}

    <div class="mb-3">
        <div class="btn-group" role="group" aria-label="Filter by status">
            @foreach ($statusList as $key => $value)
                <button type="button"
                    class="btn btn-{{ Notestatus::status($key)->color }} position-relative @if ($statusFilter === $key) border-bottom border-dark border-3 @endif"
                    style="@if ($statusFilter === $key) border-left: none; border-right: none; border-top: none; @endif"
                    wire:click="$set('statusFilter', {{ $statusFilter === $key ? 'null' : $key }})">
                    {{ Notestatus::status($key)->status }}
                    <span class="badge bg-light text-dark">
                        {{ $value }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($lists->isNotEmpty())
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
            <div class="card-header py-0 text-bg-danger d-flex justify-content-between align-items-center">
                <h5 class="card-title my-0">CONTROLE DE LEVANTAMENTO</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" title="Exportar para Excel"
                        wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                        <span wire:loading.remove wire:target="exportToExcel">
                            <i class="ri-file-excel-line me-1"></i>
                            Exportar Excel
                        </span>
                        <span wire:loading wire:target="exportToExcel">
                            <i class="spinner-border spinner-border-sm me-1" role="status"></i>
                            Exportando...
                        </span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" title="DD em Massa"
                        wire:click="$emitTo('dispatchs.common.dd-changes-create', 'openDdChangesCreateModal')"
                        wire:loading.attr="disabled"
                        wire:target="$emitTo('dispatchs.common.dd-changes-create', 'openDdChangesCreateModal')">
                        <span wire:loading.remove
                            wire:target="$emitTo('dispatchs.common.dd-changes-create', 'openDdChangesCreateModal')">
                            <i class="ri-group-line me-1"></i>
                            DD em Massa
                        </span>
                        <span wire:loading
                            wire:target="$emitTo('dispatchs.common.dd-changes-create', 'openDdChangesCreateModal')">
                            <i class="spinner-border spinner-border-sm me-1" role="status"></i>
                            Carregando...
                        </span>
                    </button>
                </div>
            </div>
            <table class="table table-sm table-striped table-condensed">
                <thead>
                    <tr class="text-center align-middle sticky-top shadow-sm table-dark" style="top: 60px;">
                        <th>#</th>
                        <th>Despachante</th>
                        <th>Note</th>
                        <th>DD</th>
                        <th>Rubrica</th>
                        <th>Municipio</th>

                        <th>Grupo2</th>
                        <th>Usuário</th>
                        <th>AttAt</th>

                        <th>PzoReal</th>
                        <th>Em Despacho</th>
                        <th>Em Att</th>
                        <th>Status</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        if (!function_exists('shortUser')) {
                            function shortUser($name)
                            {
                                if (empty($name)) {
                                    return 'Desconhecido';
                                }

                                $parts = explode(' ', $name);
                                $shortName = $parts[0] . ' ' . end($parts); // Começa com o primeiro nome

                                return $shortName;
                            }
                        }

                        if (!function_exists('getColorClass')) {
                            function getColorClass($dateField, int $daysLimit)
                            {
                                $colorClass = '';

                                if ($dateField) {
                                    $daysDiff = Carbon::parse($dateField)
                                        ->startOfDay()
                                        ->diffInDays(Carbon::now()->startOfDay());

                                    $daysWarningLimit = ceil($daysLimit * 0.7);

                                    if ($daysDiff > $daysLimit) {
                                        $colorClass = 'text-bg-danger';
                                    } elseif ($daysDiff <= $daysWarningLimit) {
                                        $colorClass = 'text-bg-success';
                                    } else {
                                        $colorClass = 'text-bg-warning';
                                    }
                                }
                                return $colorClass;
                            }
                        }
                    @endphp
                    @foreach ($lists as $item)
                        @php

                            if ($item->priority) {
                                $rowClass = [
                                    'color' => 'table-danger',
                                    'color-text' => 'text-danger',
                                    'info' => 'Prioridade',
                                ];
                            } else {
                                $rowClass = [
                                    'color' => '',
                                    'color-text' => '',
                                    'info' => '',
                                ];
                            }

                            // if ($item->partial) {
                            //     $type['init'] = 'P';
                            //     $type['info'] = 'Parcial';
                            //     $type['color'] = 'text-bg-warning';
                            // } else {
                            //     if ($item->note?->workform?->reject) {
                            //         $type['init'] = 'RF';
                            //         $type['info'] = 'Final Rejeitado';
                            //         $type['color'] = 'text-bg-dark';
                            //     } else {
                            //         $type['init'] = 'F';
                            //         $type['info'] = 'Final';
                            //         $type['color'] = 'text-bg-success';
                            //     }
                            // }

                            if ($item->d5) {
                                $status['init'] = 'RI';
                                $status['info'] = 'Retorno Interno';
                                $status['color'] = 'text-bg-primary';
                            } elseif ($item->dfive) {
                                $status['init'] = 'D5';
                                $status['info'] = 'D5';
                                $status['color'] = 'text-bg-danger';
                            } else {
                                $status['init'] = '';
                                $status['info'] = '';
                                $status['color'] = '';
                            }

                            $wpaStatus = WpaStatus::status(
                                $item->wpas?->last()?->dd,
                                $item->wpas?->last()?->execstats,
                                $item->wpas?->last()?->completed_at,
                            );

                            $colorColumn = Carbon::parse($item->dt_created)
                                ->startOfDay()
                                ->diffInDays(Carbon::now()->startOfDay());
                            if ($colorColumn > 30) {
                                $colorColumn = 'text-bg-danger';
                            } elseif ($colorColumn <= 20) {
                                $colorColumn = 'text-bg-success';
                            } else {
                                $colorColumn = 'text-bg-warning';
                            }

                            $attColorClass = getColorClass($item->att_at, 9);
                            $dispatchColorClass = getColorClass($item->dispatch_at, 30);

                        @endphp
                        <tr wire:key="row-{{ $item->id }}" class="align-middle text-center">


                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.defer="selected"
                                        id="checkbox-{{ $item->id }}" value="{{ $item->id }}">
                                    <label class="form-check-label" for="checkbox-{{ $item->id }}"></label>
                                </div>
                            </td>


                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                {{ shortUser($item->dispatcher?->name) }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                {{ $item->note?->note }}</td>
                            <td
                                class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} text-center ">
                                <p class="my-0 py-0"> <i
                                        class="{{ $wpaStatus->icon }} fs-4 {{ $wpaStatus->color }} align-middle"></i>
                                </p>
                                <span
                                    class="badge {{ $wpaStatus->bg_color }} align-middle my-0">{!! $wpaStatus->info !!}</span>
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->note?->rubrica }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->note?->lexp }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                {{ $item->note?->group2 ?? '---' }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                {{ shortUser($item->user?->name) }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->att_at?->diffInDays(Carbon::now()) }} dias
                            </td>

                            <td class="{{ $colorColumn }} fw-bold">
                                <p class="my-0 py-0"><span
                                        class="badge text-bg-light">{{ Carbon::parse($item->dt_created)->startOfDay()->diffInDays(Carbon::now()->startOfDay()) }}</span>
                                </p>
                                <p class="my-0 py-0">
                                    {{ Carbon::parse($item->dt_created)->addDays(30)->format('d/m/Y') }}</p>


                            </td>
                            <td class="{{ $dispatchColorClass }} fw-bold border-start">
                                @if ($item->dispatch_at)
                                    <p class="my-0 py-0"><span
                                            class="badge text-bg-light">{{ Carbon::parse($item->dispatch_at)->startOfDay()->diffInDays(Carbon::now()->startOfDay()) }}</span>
                                    </p>
                                    <p class="my-0 py-0">{{ Carbon::parse($item->dispatch_at)->format('d/m/Y') }}</p>
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="{{ $attColorClass }} fw-bold border-start">
                                @if ($item->att_at)
                                    <p class="my-0 py-0"><span
                                            class="badge text-bg-light">{{ Carbon::parse($item->att_at)->startOfDay()->diffInDays(Carbon::now()->startOfDay()) }}</span>
                                    </p>
                                    <p class="my-0 py-0">{{ Carbon::parse($item->att_at)->format('d/m/Y') }}</p>
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                <span class="badge {{ Notestatus::status($item->status)->colorbg }}"
                                    wire:click.prevent="$emitTo('components.status.show-status', 'showStatus', {{ $item->id }}, {{ $item->status }})"
                                    style="cursor: pointer;">{{ Notestatus::status($item->status)->status }}</span>
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                <x-production.action-production :production="$item" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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


    {{-- END MODALS --}}
    {{-- @livewire('audits.info') --}}
    @livewire('components.status.show-status', key('show_status_note'))
    {{-- @livewire('production.return.return-work', key('returnWorkfomr')) --}}

    {{-- ===================== Modal: Busca Multi-notas ===================== --}}
    <div wire:ignore.self class="modal fade" id="multiSearchModal" tabindex="-1"
        aria-labelledby="multiSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="multiSearchModalLabel">Busca Multi-notas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <textarea class="form-control bg-dark text-white opacity-50 border-0 rounded-0" rows="15"
                        wire:model.defer="advancedSearch" wire:keydown.ctrl.enter="buscarMulti"
                        placeholder="Cole aqui várias notas, uma por linha.&#10;Exemplo:&#10;123456&#10;987654&#10;ABC-2024-001"></textarea>
                </div>
                <div class="modal-footer text-bg-secondary">
                    <div class="text-muted small me-auto text-white">
                        Dica: <kbd class="bg-light text-dark">Ctrl</kbd> + <kbd class="bg-light text-dark">Enter</kbd>
                        para buscar.
                    </div>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">
                        <i class="ri-search-line me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ===================== FIM Modal: Busca Multi-notas ===================== --}}

    {{-- ===================== Inicio Modais ===================== --}}
    @livewire('dispatchs.common.dd-changes-create', ['service' => $service, 'control' => true], key('dd-changes-create'))
    {{-- ===================== FIM Modal ===================== --}}

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
