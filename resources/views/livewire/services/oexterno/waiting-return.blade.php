@php
    use App\Custom\Notestatus;
@endphp

<div class="user-activity-page internal-return-activity-page">
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')
    @include('livewire.services.partials.user-activity-hero', [
        'context' => 'Retornos internos de órgão externo',
        'subtitle' => 'Solicitações devolvidas para análise, complementação ou aprovação interna',
        'total' => $lists->total(),
        'accent' => '#0f766e',
    ])

    <div class="container-fluid">
        <div class="activity-filter-card mb-3">
            <div class="activity-filter-title mb-3">
                <i class="ri-filter-3-line me-1"></i> Pesquisa e filtros
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label">Itens por página</label>
                    <select class="form-select" wire:model="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-7">
                    <label class="form-label">Pesquisar nota</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="search" class="form-control" wire:model.debounce.300ms="search"
                            placeholder="Informe a nota ou use * como curinga">
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Tipo de busca</label>
                    <select class="form-select" wire:model="typeNote">
                        <option value="note">Nota</option>
                        <option value="ov">OV</option>
                        <option value="both">Ambos</option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="internal-return-filters d-flex flex-wrap align-items-center gap-2">
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
                                'customQuery' => null,
                                'searchColumn' => 'name',
                                'sendSearchColumn' => 'entity_type_id',
                                'customBuilderMethod' => null,
                            ],
                            key('entityTypes')
                        )

                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'entities',
                                'sendFilter' => null,
                                'modelClass' => \App\Models\Entity::class,
                                'column' => 'id',
                                'filterLabel' => 'Entidades',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'name',
                                'direction' => 'ASC',
                                'customQuery' => null,
                                'searchColumn' => 'name',
                                'sendSearchColumn' => 'entity_id',
                                'customBuilderMethod' => null,
                            ],
                            key('entities')
                        )

                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'rubrica',
                                'sendFilter' => null,
                                'modelClass' => \App\Models\Note::class,
                                'column' => 'rubrica',
                                'filterLabel' => 'Rúbrica',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'rubrica',
                                'direction' => 'ASC',
                                'customQuery' => null,
                                'searchColumn' => 'rubrica',
                                'sendSearchColumn' => 'rubrica',
                                'customBuilderMethod' => null,
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
                                'customQuery' => null,
                                'searchColumn' => 'regiao',
                                'sendSearchColumn' => 'regiao',
                                'customBuilderMethod' => null,
                            ],
                            key('region')
                        )

                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'city',
                                'sendFilter' => null,
                                'modelClass' => \App\Models\Edp_depc\City::class,
                                'column' => 'cidade',
                                'filterLabel' => 'Município',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'municipio',
                                'direction' => 'ASC',
                                'customQuery' => null,
                                'searchColumn' => 'municipio',
                                'sendSearchColumn' => 'cidade',
                                'customBuilderMethod' => null,
                            ],
                            key('city')
                        )

                        @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>

        @if ($lists->isNotEmpty())
            <div class="user-activity-summary mb-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-7">{{ $lists->onEachSide(1)->links() }}</div>
                    <div class="col-12 col-lg-5 text-lg-end">
                        <span class="activity-summary-text">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <div class="user-activity-table-card position-relative">
            <div class="user-activity-table-header bg-danger d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h5 class="user-activity-table-title">
                        <i class="ri-hourglass-line me-2"></i>Aguardando retorno interno
                    </h5>
                    <div class="user-activity-table-subtitle">
                        Acompanhe execução, responsável e tempo total de cada retorno.
                    </div>
                </div>
                <span class="badge text-bg-warning">
                    {{ $lists->total() }} {{ $lists->total() === 1 ? 'retorno' : 'retornos' }}
                </span>
            </div>

            <div wire:loading.delay.class.remove="d-none"
                class="position-absolute top-0 start-0 w-100 h-100 d-none internal-return-loading">
                <div class="d-flex h-100 align-items-center justify-content-center gap-2">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <span>Atualizando retornos...</span>
                </div>
            </div>

            @if ($lists->isEmpty())
                <div class="internal-return-empty-state">
                    <i class="ri-inbox-line"></i>
                    <h5>Nenhum retorno interno pendente</h5>
                    <p>Revise os filtros ou aguarde novas solicitações nesta etapa.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nota</th>
                                <th>Rubrica</th>
                                <th>Serviço</th>
                                <th>Entidade</th>
                                <th>Solicitado em</th>
                                <th>Solicitante</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Responsável</th>
                                <th>Em execução</th>
                                <th>Tempo total</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $index => $list)
                                @php
                                    $external = $list->externals?->first();
                                    $executionDays = $list->production?->att_at
                                        ? $list->production->att_at->startOfDay()->diffInDays()
                                        : $list->created_at->startOfDay()->diffInDays();
                                    $totalDays = $list->created_at->startOfDay()->diffInDays();
                                    $productionStatus = $list->production
                                        ? Notestatus::status($list->production->status)
                                        : null;
                                @endphp
                                <tr wire:key="internal-return-{{ $list->id }}"
                                    wire:dblclick="navigateTo('{{ $list->note->note }}', {{ $external?->id ?? 'null' }})">
                                    <td class="fw-semibold text-nowrap">{{ $list->note->note }}</td>
                                    <td>{{ $list->note->rubrica ?? '—' }}</td>
                                    <td>{{ $list->service->service ?? '—' }}</td>
                                    <td>
                                        <span class="fw-semibold">
                                            {{ $external?->entity?->nick ?? $external?->entity?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">{{ $list->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $list->comments?->first()?->user?->name ?? '—' }}</td>
                                    <td>{{ $list->subcategory?->category?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $productionStatus?->colorbg ?? 'text-bg-secondary' }}">
                                            {{ $productionStatus?->status ?? 'AGUARDANDO DESPACHO' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($list->production?->user?->email)
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $list->production->user->email }}', '_blank')"
                                                    data-bs-toggle="tooltip" data-bs-title="Conversar pelo Teams">
                                                    <i class="bx bxl-microsoft-teams"></i>
                                                </button>
                                            @endif
                                            <span>{{ $list->production?->user?->name ?? 'Não atribuído' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="internal-return-time {{ $this->getColor($executionDays) }}">
                                            {{ $executionDays }} {{ $executionDays === 1 ? 'dia' : 'dias' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="internal-return-time {{ $this->getColor($totalDays) }}">
                                            {{ $totalDays }} {{ $totalDays === 1 ? 'dia' : 'dias' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                wire:click.prevent="navigateTo('{{ $list->note->note }}', {{ $external?->id ?? 'null' }})"
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Abrir retorno na entidade relacionada">
                                                <i class="ri-external-link-line"></i>
                                            </button>
                                            @if ($list->completed)
                                                <button type="button" class="btn btn-sm btn-success"
                                                    wire:click.prevent="$emitTo('services.oexterno.actions.confirm-work-return', 'openConfirmWorkReturn', {{ $list->id }})"
                                                    wire:target="confirmReturn" data-bs-toggle="tooltip"
                                                    data-bs-title="Aprovar retorno do trabalho">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                            @endif
                                        </div>
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
                    <div class="col-12 col-lg-7">{{ $lists->onEachSide(1)->links() }}</div>
                    <div class="col-12 col-lg-5 text-lg-end">
                        <span class="activity-summary-text">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @livewire('services.oexterno.actions.confirm-work-return', key('confirmWorkReturn'))
</div>

@push('css')
    <style>
        .internal-return-filters > div {
            margin: 0 !important;
        }

        .internal-return-filters .btn-secondary {
            background: #0f5f66;
            border-color: #0f5f66;
        }

        .internal-return-filters .position-absolute {
            z-index: 1080 !important;
        }

        .internal-return-loading {
            background: rgba(255, 255, 255, .78);
            backdrop-filter: blur(2px);
            z-index: 4;
        }

        .internal-return-empty-state {
            color: var(--activity-muted);
            padding: 3rem 1rem;
            text-align: center;
        }

        .internal-return-empty-state i {
            color: #0f766e;
            display: block;
            font-size: 2.5rem;
            margin-bottom: .75rem;
        }

        .internal-return-empty-state h5 {
            color: var(--activity-ink);
            font-weight: 600;
        }

        .internal-return-empty-state p {
            margin: 0;
        }

        .internal-return-time {
            border-radius: .55rem;
            display: inline-block;
            font-size: .8rem;
            font-weight: 700;
            min-width: 6.5rem;
            padding: .5rem .65rem;
            text-align: center;
            white-space: nowrap;
        }

    </style>
@endpush
