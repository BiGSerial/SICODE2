<div class="user-activity-page protocol-activity-page">
    @php
        use Carbon\Carbon;
        use App\Helpers\DaysLeft;
        use App\Helpers\SelectOptions;
    @endphp

    {{-- Carrega o Loading da pagina --}}
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')

    <div class="container-fluid">
        @include('livewire.services.partials.user-activity-hero', [
            'context' => 'Entrada de órgão externo',
            'subtitle' => 'Notas e OVs disponíveis para iniciar uma nova tratativa',
            'total' => $lists->total(),
            'accent' => '#0f766e',
            'extraLabel' => $update ? 'Última atualização' : null,
            'extraValue' => $update ? Carbon::parse($last_update)->diffForHumans() : null,
        ])

        {{-- START SearchBar and Filters --}}
        <div class="mb-3">
            <div class="row g-3 protocol-filters-grid">
                    <div class="col-12 col-lg-6 col-xl-5">
                        <div class="activity-filter-card">
                            <div class="activity-filter-title mb-2">Pesquisa de Nota/OV</div>
                            <div class="small text-muted mb-2">
                                Busca em qualquer nota com status 11/20 ou que já passou por entidade externa.
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-sm-4">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="perPage"
                                            id="perPageSelect">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                            <option value="500">500</option>
                                        </select>
                                        <label for="perPageSelect">Registros por página</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-8">
                                    <div class="form-floating w-100 position-relative">
                                        <input wire:model.debounce.500ms="search" type="text"
                                            class="form-control border border-secondary" id="search"
                                            placeholder="Buscar nota, pedido, material, protocolo...">
                                        <label for="search">Buscar nota / pedido / protocolo</label>
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

                    <div class="col-12 col-lg-3 col-xl-3">
                        <div class="activity-filter-card">
                            <div class="activity-filter-title mb-2">Classificação rápida</div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Tipo de nota</small>
                                <div class="btn-group w-100" role="group" aria-label="Tipo de Nota">
                                    <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote"
                                        value="1" id="typeNote1">
                                    <label class="btn btn-outline-primary" for="typeNote1">Nota</label>

                                    <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote"
                                        value="2" id="typeNote2">
                                    <label class="btn btn-outline-primary" for="typeNote2">OV</label>

                                    <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote"
                                        value="" id="typeNote3">
                                    <label class="btn btn-outline-primary" for="typeNote3">Ambos</label>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-2">Status</small>
                                <div class="btn-group w-100" role="group" aria-label="Status">
                                    <input type="radio" class="btn-check" name="statusFilter"
                                        wire:model="statusFilter" value="" id="statusAll">
                                    <label class="btn btn-outline-secondary" for="statusAll">Todos</label>

                                    <input type="radio" class="btn-check" name="statusFilter"
                                        wire:model="statusFilter" value="11" id="status11">
                                    <label class="btn btn-outline-secondary" for="status11">11</label>

                                    <input type="radio" class="btn-check" name="statusFilter"
                                        wire:model="statusFilter" value="20" id="status20">
                                    <label class="btn btn-outline-secondary" for="status20">20</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3 col-xl-4">
                        <div class="activity-filter-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="activity-filter-title">Filtros adicionais</div>
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
                                        'filterLabel' => 'Região',
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
                                        'filterLabel' => 'Município',
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
        {{-- END SearchBar and Filters --}}

        @if ($lists->isNotEmpty())
            <div class="user-activity-summary mb-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-6">
                        {{ $lists->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="activity-summary-text">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="user-activity-table-card position-relative">
            @if (!$lists->count())
                <div class="protocol-empty-state">
                    <i class="ri-inbox-line"></i>
                    <h5>Nenhuma nota disponível para protocolar</h5>
                    <p>Revise os filtros ou aguarde novas notas nesta etapa.</p>
                </div>
            @else
                <div
                    class="user-activity-table-header protocol-table-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h5 class="user-activity-table-title">
                            <i class="ri-file-add-line me-2"></i>Entidade externa a protocolar
                        </h5>
                        <div class="user-activity-table-subtitle">
                            Selecione uma nota para iniciar ou consultar a tratativa externa.
                        </div>
                    </div>
                    <button wire:click="exportToExcel" class="btn btn-light">
                        <i class="ri-file-excel-2-line me-2"></i>Exportar
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" class="fw-bold text-center">Nota</th>
                                <th scope="col" class="fw-bold text-center">Arquivos</th>
                                <th scope="col" class="fw-bold text-center">Protocolo</th>
                                <th scope="col" class="fw-bold text-center">Último protocolo</th>
                                <th scope="col" class="fw-bold text-center">Entidade</th>
                                <th scope="col" class="fw-bold text-center">Rubrica</th>
                                <th scope="col" class="fw-bold text-center">Grp 2</th>
                                <th scope="col" class="fw-bold text-center">Município</th>
                                <th scope="col" class="fw-bold text-center">Pedido</th>
                                <th scope="col" class="fw-bold text-center">Status</th>
                                <th scope="col" class="fw-bold text-center">Pasta atual</th>
                                <th scope="col" class="fw-bold text-center" wire:click="setColumn('dt_status')"
                                    style="cursor: pointer;">Dias no Status
                                    @if ($column == 'dt_status')
                                        <i
                                            class="{{ $direction == 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                    @endif
                                </th>
                                <th scope="col" class="fw-bold text-center" wire:click="setColumn('dt_created')"
                                    style="cursor: pointer;">Total Dias
                                    @if ($column == 'dt_created')
                                        <i
                                            class="{{ $direction == 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                    @endif
                                </th>
                                <th scope="col" class="fw-bold text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $daysleft = new DaysLeft($list);
                                    $daysleft = $daysleft->getDaysLeft();
                                    $color = 'text-bg-secondary';
                                    $color2 = 'text-bg-secondary';
                                    $statusDays = $list->dt_status?->startOfDay()->diffInDays() ?? 0;
                                    $countDays = $list->dt_created->startOfDay()->diffInDays(now()->startOfDay());

                                    if ($countDays > 30) {
                                        $color2 = 'text-bg-danger';
                                    } elseif ($countDays < 27) {
                                        $color2 = 'text-bg-success';
                                    } else {
                                        $color2 = 'text-bg-warning';
                                    }

                                    if ($statusDays > 120) {
                                        $color = 'text-bg-danger';
                                    } elseif ($statusDays <= 60) {
                                        $color = 'text-bg-success';
                                    } else {
                                        $color = 'text-bg-warning';
                                    }
                                @endphp
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
                                        <p class="my-0 py-0">{{ $list->externals?->last()?->protocols?->last()?->protocol }}</p>
                                        <p class="my-0 py-0">{{ $list->externals?->last()?->protocols?->last()?->created_at?->format('d/m/Y H:i:s') }}</p>
                                    </td>
                                    <td class="fw-light text-center">
                                        {{ $list->externals?->last()?->entidade }}
                                    </td>

                                    <td class="fw-light text-center">{{ $list->rubrica }}</td>
                                    <td class="fw-light text-center">{{ $list->group2 }}</td>
                                    <td class="fw-light text-center">{{ $list->lexp }}</td>


                                    <td class="fw-light text-center">{{ $list->numPedido }}</td>


                                    <td class="fw-light text-center">
                                        <p class="my-0 py-0">{{ $list->nstats }}</p>
                                        <p class="my-0 py-0"><span class="test">{{ $list->centerjob }}</span></p>
                                    </td>
                                    <td class="fw-light text-center">
                                        @php($folder = $this->resolveFolderLabel($list))
                                        <span class="badge {{ $folder['badge'] }}">{{ $folder['label'] }}</span>
                                    </td>

                                    <td class="fw-light text-center {{ $color }}">

                                        <p class="my-0 py-0 fw-bold">
                                            {{ $statusDays }} dias</p>
                                        <p class="my-0 py-0">{{ $list->dt_status->format('d/m/Y') }}</p>

                                    </td>
                                    <td class="fw-light text-center {{ $color2 }}">

                                        <p class="my-0 py-0 fw-bold">
                                            {{ $list->dt_created->startOfDay()->diffInDays() }} dias</p>
                                        <p class="my-0 py-0">{{ $list->dt_created->format('d/m/Y') }}</p>

                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            wire:click.prevent="navigateTo('{{ $list->note }}')"
                                            data-bs-toggle="tooltip" data-bs-title="Abrir nota para protocolar">
                                            <i class="ri-external-link-line"></i>
                                        </button>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($lists->isNotEmpty())
            <div class="user-activity-summary mt-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-6">
                        {{ $lists->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="activity-summary-text">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODALS --}}
        <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1"
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
        @livewire('services.oexterno.actions.protocols', key('external_protocols'))

        @push('css')
            <style>
                .protocol-filters-grid .activity-filter-card {
                    height: 100%;
                }

                .protocol-filters-grid .btn-group .btn {
                    min-width: 4.5rem;
                }

                .protocol-filters-grid .chip-filters {
                    gap: .5rem;
                }

                .protocol-filters-grid .chip-filters > div {
                    margin: 0 !important;
                }

                .protocol-filters-grid .chip-filters .position-absolute {
                    z-index: 1080 !important;
                }

                .protocol-table-header {
                    background: linear-gradient(120deg, #0f5f66, #0f766e);
                }

                .protocol-empty-state {
                    color: var(--activity-muted);
                    padding: 3rem 1rem;
                    text-align: center;
                }

                .protocol-empty-state i {
                    color: #0f766e;
                    display: block;
                    font-size: 2.5rem;
                    margin-bottom: .75rem;
                }

                .protocol-empty-state h5 {
                    color: var(--activity-ink);
                    font-weight: 600;
                }

                .protocol-empty-state p {
                    margin: 0;
                }
            </style>
        @endpush

        @push('script')
            <script>
                const copyTextCells = document.querySelectorAll('.copy-text');

                copyTextCells.forEach(cell => {
                    cell.addEventListener('click', () => {
                        const value = cell.getAttribute('data-value');
                        copyToClipboard(value);
                        livewire.emit('getCopy',
                            `Valor "${value}" copiado para a area de transferencia.`);
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
</div>
