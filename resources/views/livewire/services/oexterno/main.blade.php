<div>
    @php
        use Carbon\Carbon;
        use App\Helpers\DaysLeft;
        use App\Helpers\SelectOptions;
    @endphp

    {{-- Carrega o Loading da pagina --}}
    <x-show-loading />

    <style>
        .filters-grid .filter-card {
            background-color: #f9fbfd;
            border: 1px solid #dde2eb;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            height: 100%;
        }

        .filters-grid .filter-card h6 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
            color: #6c757d;
        }

        .filters-grid .btn-group .btn {
            min-width: 72px;
        }

        .filters-grid .chip-filters {
            gap: 0.5rem;
        }
    </style>

    {{-- START SearchBar and Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 filters-grid">
                <div class="col-12 col-lg-5 col-xl-4">
                    <div class="filter-card">
                        <h6>Pesquisa</h6>
                        <div class="row g-2">
                            <div class="col-12 col-sm-5">
                                <div class="form-floating w-100">
                                    <select class="form-select border border-secondary" wire:model="perPage"
                                        id="perPageSelect">
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="200">200</option>
                                        <option value="500">500</option>
                                    </select>
                                    <label for="perPageSelect">Registros por pagina</label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-7">
                                <div class="form-floating w-100 position-relative">
                                    <input wire:model.bounce.2s="search" type="text"
                                        class="form-control border border-secondary" id="search" placeholder="Buscar">
                                    <label for="search">Buscar</label>
                                    <button
                                        class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                        data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                        <i class="ri-checkbox-multiple-blank-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4 col-xl-3">
                    <div class="filter-card">
                        <h6>Classificacao</h6>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">Tipo de nota</small>
                            <div class="btn-group w-100" role="group" aria-label="Tipo de Nota">
                                <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="1"
                                    id="typeNote1">
                                <label class="btn btn-outline-primary" for="typeNote1">Nota</label>

                                <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="2"
                                    id="typeNote2">
                                <label class="btn btn-outline-primary" for="typeNote2">OV</label>

                                <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value=""
                                    id="typeNote3">
                                <label class="btn btn-outline-primary" for="typeNote3">Ambos</label>
                            </div>
                        </div>
                        <div>
                            <small class="text-muted d-block mb-2">Status</small>
                            <div class="btn-group w-100" role="group" aria-label="Status">
                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="" id="statusAll">
                                <label class="btn btn-outline-secondary" for="statusAll">Todos</label>

                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="11" id="status11">
                                <label class="btn btn-outline-secondary" for="status11">11</label>

                                <input type="radio" class="btn-check" name="statusFilter" wire:model="statusFilter"
                                    value="20" id="status20">
                                <label class="btn btn-outline-secondary" for="status20">20</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="filter-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0">Filtros adicionais</h6>
                            @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
                        </div>
                        <div class="d-flex flex-wrap chip-filters">
                            @livewire(
                                'components.filter.filter2',
                                [
                                    'myKey' => 'entityTypes',
                                    'sendFilter' => 'entities',
                                    'modelClass' => \App\Models\EntityType::class,
                                    'column' => 'id',
                                    'filterLabel' => 'Tipos de Entidade',
                                    'groupFilter' => 'oexterno',
                                    'displayColumn' => 'name',
                                    'direction' => 'ASC',
                                    'searchColumn' => 'name',
                                    'sendSearchColumn' => 'entity_type_id',
                                ],
                                key('entityTypes')
                            )

                            @livewire(
                                'components.filter.filter2',
                                [
                                    'myKey' => 'entities',
                                    'sendFilter' => '',
                                    'modelClass' => \App\Models\Entity::class,
                                    'column' => 'id',
                                    'filterLabel' => 'Entidades',
                                    'groupFilter' => 'oexterno',
                                    'displayColumn' => 'name',
                                    'direction' => 'ASC',
                                    'searchColumn' => 'name',
                                    'sendSearchColumn' => 'entity_id',
                                ],
                                key('entities')
                            )

                            @livewire(
                                'components.filter.filter2',
                                [
                                    'myKey' => 'rubrica',
                                    'sendFilter' => '',
                                    'modelClass' => \App\Models\Note::class,
                                    'column' => 'rubrica',
                                    'filterLabel' => 'Rubrica',
                                    'groupFilter' => 'oexterno',
                                    'displayColumn' => 'rubrica',
                                    'direction' => 'ASC',
                                    'searchColumn' => 'rubrica',
                                    'sendSearchColumn' => 'rubrica',
                                ],
                                key('rubrica')
                            )

                            @livewire(
                                'components.filter.filter2',
                                [
                                    'myKey' => 'region',
                                    'sendFilter' => 'city',
                                    'modelClass' => \App\Models\Edp_depc\City::class,
                                    'column' => 'regiao',
                                    'filterLabel' => 'Regiao',
                                    'groupFilter' => 'oexterno',
                                    'displayColumn' => 'regiao',
                                    'direction' => 'ASC',
                                    'searchColumn' => 'regiao',
                                    'sendSearchColumn' => 'regiao',
                                ],
                                key('region')
                            )

                            @livewire(
                                'components.filter.filter2',
                                [
                                    'myKey' => 'city',
                                    'sendFilter' => '',
                                    'modelClass' => \App\Models\Edp_depc\City::class,
                                    'column' => 'cidade',
                                    'filterLabel' => 'Municipio',
                                    'groupFilter' => 'oexterno',
                                    'displayColumn' => 'municipio',
                                    'direction' => 'ASC',
                                    'searchColumn' => 'municipio',
                                    'sendSearchColumn' => 'cidade',
                                ],
                                key('city')
                            )
                        </div>
                    </div>
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
                @if ($update)
                    Ultima Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </span>
        </div>


    </div>
    <div class="card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM DADOS EM {{ $service->service }}</h4>
            </div>
        @else
            <div class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ mb_strtoupper($service->service) }} A PROTOCOLAR</h4>
                <button wire:click="exportToExcel" class="btn btn-success">
                    <i class="ri-file-excel-2-line me-2"></i>Exportar
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm  table-condensed table-hover table-striped">
                    <thead class="table-dark">
                        <tr class="sticky-top bg-dark" style="z-index:1; top:0;">

                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">Files</th>
                            <th scope="col" class="fw-bold text-center">Protocolo</th>
                            <th scope="col" class="fw-bold text-center">Ultimo Protocolo</th>
                            <th scope="col" class="fw-bold text-center">Dt Protocolo</th>
                            <th scope="col" class="fw-bold text-center">Sts Protocolo</th>
                            <th scope="col" class="fw-bold text-center">Entidade</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Grp 2</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Pedido</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center">Ult Movimantação</th>
                            <th scope="col" class="fw-bold text-center" wire:click="setColumn('dt_status')"
                                style="cursor: pointer;">Dias no
                                Status @if ($column == 'dt_status')
                                    <i
                                        class="{{ $direction == 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                @endif
                            </th>
                            <th scope="col" class="fw-bold text-center" wire:click="setColumn('dt_created')"
                                style="cursor: pointer;">Total
                                Dias @if ($column == 'dt_created')
                                    <i
                                        class="{{ $direction == 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                @endif
                            </th>
                            <th scope="col" class="fw-bold text-center">Situação</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php

                                $daysleft = (new DaysLeft($list))->getDaysLeft();
                                $getLastMovement = $list->externals
                                    ?->sortbydesc('updated_at')
                                    ->first()
                                    ?->Comments?->sortbydesc('updated_at')
                                    ->first()?->created_at;

                                $countDays = $list->dt_created->startOfDay()->diffInDays(now()->startOfDay());

                                if ($countDays > 30) {
                                    $color2 = 'text-bg-danger';
                                } elseif ($countDays < 27) {
                                    $color2 = 'text-bg-success';
                                } else {
                                    $color2 = 'text-bg-warning';
                                }

                            @endphp
                            {{-- @dump($list->Productions) --}}
                            <tr class="align-middle" wire:key="{{ $list->id }}"
                                wire:dblclick="navigateTo('{{ $list->note }}')">

                                <td class="fw-bold copy-text text-center" data-value="{{ $list->note }}">
                                    {{ $list->note }}
                                </td>


                                <td class="text-center align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    <x-files.select-download-list :files='$list->Files' />
                                </td>
                                <td class="text-center align-middle">

                                    @if ($list->externals->isNotEmpty())
                                        @php
                                            $completed = $list->externals->where('completed', true)->count();
                                            $total = $list->externals->count();
                                        @endphp
                                        <span
                                            class="badge @if ($completed == $total) text-bg-success @else text-bg-danger @endif">
                                            {{ $completed }} / {{ $total }}</span>
                                    @else
                                        <span class="badge text-bg-dark">0/0</span>
                                    @endif
                                </td>
                                <td class="fw-light text-center">
                                    {{ $list->externals?->last()?->protocols?->last()?->protocol }}
                                </td>
                                <td class="fw-light text-center">
                                    {{ $list->externals?->last()?->protocols?->last()?->created_at?->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="fw-light text-center fw-bold">
                                    {{ $list->externals?->last()?->Comments?->last()?->title }}
                                </td>
                                <td class="fw-light text-center">
                                    {{ $list->externals?->last()?->entidade }}
                                </td>

                                <td class="fw-light text-center">{{ $list->rubrica }}</td>
                                 <td class="fw-light text-center">{{ $list->group2 }}</td>
                                <td class="fw-light text-center">{{ $list->lexp }}</td>


                                <td class="fw-light text-center">{{ $list->numPedido }}</td>


                                <td class="fw-light text-center">{{ $list->nstats }}</td>

                                <td class="fw-light text-center ">

                                    <p class="my-0 py-0 fw-bold">
                                        {{ $getLastMovement?->diffForHumans(['parts' => 2, 'join' => ' e ', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                    </p>


                                </td>
                                @php
                                    $days = $list->dt_status?->startOfDay()->diffInDays();

                                    if ($days > 120) {
                                        $color = 'text-bg-danger';
                                    } elseif ($days <= 60) {
                                        $color = 'text-bg-success';
                                    } else {
                                        $color = 'text-bg-warning';
                                    }
                                @endphp
                                <td class="fw-light text-center {{ $color }}">

                                    <p class="my-0 py-0 fw-bold">
                                        {{ $list->dt_status->startOfDay()->diffInDays() }} dias</p>
                                    <p class="my-0 py-0">{{ $list->dt_status->format('d/m/Y') }}</p>

                                </td>
                                <td class="fw-light text-center {{ $color2 }}">

                                    <p class="my-0 py-0 fw-bold">
                                        {{ $list->dt_created->startOfDay()->diffInDays() }} dias</p>
                                    <p class="my-0 py-0">{{ $list->dt_created->format('d/m/Y') }}</p>

                                </td>

                                <td class="fw-light text-center fw-bold">
                                    @if ($list->externals->isNotEmpty())
                                        @php
                                            $completed = $list->externals->where('completed', true)->count();
                                            $total = $list->externals->count();
                                        @endphp
                                        @if ($completed == $total)
                                            <span class="badge text-bg-success">COMPLETADO</span>
                                        @else
                                            <span class="badge text-bg-primary">EM ANDAMENTO</span>
                                        @endif
                                    @else
                                        <span class="badge text-bg-dark">SEM REGISTRO</span>
                                    @endif
                                </td>



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

    {{-- Livewire Components --}}
    @livewire('services.oexterno.actions.protocols', key('external_protocols'))


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
</div>
