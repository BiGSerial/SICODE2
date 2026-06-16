@php
    use App\Helpers\SelectOptions;

    $selectOptions = collect(SelectOptions::getProtocolReasons());

    $filtersConfig = [
        [
            'type' => 'select',
            'label' => 'Por página',
            'model' => 'perPage',
            'placeholder' => 'Selecione',
            'multiple' => false,
            'size' => 1,
            'source' => [
                'mode' => 'array',
                'data' => [
                    ['value' => 10, 'label' => '10'],
                    ['value' => 25, 'label' => '25'],
                    ['value' => 50, 'label' => '50'],
                    ['value' => 100, 'label' => '100'],
                ],
            ],
            'option_value' => 'value',
            'option_label' => 'label',
        ],

        [
            'type' => 'multiselect', // dropdown com checkboxes
            'label' => 'Rubrica',
            'model' => 'rubricas', // array no componente pai
            'placeholder' => 'Selecione Rubrica',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Note::class,
                'key' => 'rubrica',
                'label' => 'rubrica',
                'orderBy' => ['rubrica', 'asc'],
                // 'where' => [['active','=',1]]
            ],
            'option_value' => 'id', // não se aplica aqui
            'option_label' => 'nick', // não se aplica aqui
        ],

        [
            'type' => 'multiselect', // dropdown com checkboxes
            'label' => 'Entidade',
            'model' => 'entities', // array no componente pai
            'placeholder' => 'Selecione Entidade',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Entity::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
                // 'where' => [['active','=',1]]
            ],
            'option_value' => 'id', // não se aplica aqui
            'option_label' => 'nick', // não se aplica aqui
        ],

        [
            'type' => 'multiselect', // dropdown com checkboxes
            'label' => 'Tipo de Entidade',
            'model' => 'types', // array no componente pai
            'placeholder' => 'Selecione tipos',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\EntityType::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
                // 'where' => [['active','=',1]]
            ],
            'option_value' => 'id', // não se aplica aqui
            'option_label' => 'name', // não se aplica aqui
        ],
    ];

@endphp

<div class="user-activity-page undefined-activity-page">
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')
    @include('livewire.services.partials.user-activity-hero', [
        'context' => 'Triagem de órgão externo',
        'subtitle' => 'Pendências que ainda precisam de classificação',
        'total' => $lists->total(),
        'accent' => '#0f766e',
    ])

    <div class="container-fluid">
        <div class="activity-filter-card mb-3">
            <div class="activity-filter-title mb-3">
                <i class="ri-filter-3-line me-1"></i> Pesquisa e filtros
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl-4">
                    <label class="form-label mb-1 d-flex align-items-center justify-content-between">
                        <span>Buscar pendência</span>
                        <a class="small text-decoration-none" data-bs-toggle="collapse" href="#wildHelp"
                            role="button" aria-expanded="false" aria-controls="wildHelp">
                            Como pesquisar?
                        </a>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Nota, município, entidade ou usuário"
                            wire:model.debounce.400ms="search" autocomplete="off">
                    </div>
                    <div class="collapse mt-2" id="wildHelp">
                        <div class="alert alert-light border small mb-0 py-2">
                            Use <code>*</code> ou <code>?</code> como curinga. Exemplos:
                            <code>123*</code>, <code>*ABC</code> ou <code>A?C</code>.
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <x-filters.dynamic :filters="$filtersConfig" applyAction="applyFilters" />
                </div>
            </div>
        </div>

        @if ($lists->isNotEmpty())
            <div class="user-activity-summary mb-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-7">
                        {{ $lists->onEachSide(1)->links() }}
                    </div>
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
                        <i class="ri-question-line me-2"></i>Pendências não classificadas
                    </h5>
                    <div class="user-activity-table-subtitle">
                        Registros abertos que não estão nas filas de pagamento, órgão externo ou taxa.
                    </div>
                </div>
                <span class="badge text-bg-warning">
                    {{ $lists->total() }} {{ $lists->total() === 1 ? 'pendência' : 'pendências' }}
                </span>
            </div>

            <div wire:loading.delay.class.remove="d-none"
                class="position-absolute top-0 start-0 w-100 h-100 d-none undefined-loading">
                <div class="d-flex h-100 align-items-center justify-content-center gap-2">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <span>Atualizando registros...</span>
                </div>
            </div>

            @if ($lists->isEmpty())
                <div class="undefined-empty-state">
                    <i class="ri-inbox-line"></i>
                    <h5>Nenhuma pendência encontrada</h5>
                    <p>Revise os filtros ou a busca para consultar outros registros.</p>
                </div>
            @else
                <div class="table-responsive undefined-table-scroll">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Nota</th>
                                <th>Rubrica</th>
                                <th>Arquivos</th>
                                <th>Município</th>
                                <th>Centro/Status</th>
                                <th>Entidade</th>
                                <th>Tipo de entidade</th>
                                <th>Usuário</th>
                                <th>Status</th>
                                <th>Última movimentação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $i => $ext)
                                @php
                                    $status =
                                        $selectOptions->firstWhere('value', $ext->status) ??
                                        $selectOptions->firstWhere('value', 'INDEFINIDO');
                                    $nick = $ext->Entity->nick ?? null;
                                    $name = $ext->Entity->name ?? null;
                                    $lastMovement = $ext->last_comment_at
                                        ? \Carbon\Carbon::parse($ext->last_comment_at)
                                        : null;
                                    $daysAgo = $lastMovement?->diffInDays(now());
                                    $movementClass = match (true) {
                                        $daysAgo === null => 'undefined-movement--neutral',
                                        $daysAgo > 30 => 'undefined-movement--danger',
                                        $daysAgo <= 20 => 'undefined-movement--success',
                                        default => 'undefined-movement--warning',
                                    };
                                @endphp
                                <tr wire:key="undefined-external-{{ $ext->id }}">
                                    <td class="text-muted">
                                        {{ ($lists->currentPage() - 1) * $lists->perPage() + $i + 1 }}
                                    </td>
                                    <td class="fw-semibold text-nowrap">{{ $ext->Note->note ?? '—' }}</td>
                                    <td>{{ $ext->Note->rubrica ?? '—' }}</td>
                                    <td>
                                        <x-files.select-download-list :files="$ext->Note->Files" />
                                    </td>
                                    <td>{{ $ext->Note->lexp ?? '—' }}</td>
                                    <td>
                                        <span class="text-muted">
                                            {{ $ext->Note->centerjob ?? $ext->Note->nstats ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($nick || $name)
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $nick ?? $name }}</span>
                                                @if ($nick && $name && $nick !== $name)
                                                    <small class="text-muted">{{ $name }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">Não vinculada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border text-dark">
                                            {{ $ext->Entity->Type->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ $ext->User->name ?? '—' }}</span>
                                            @if (optional($ext->User)->Company)
                                                <small class="text-muted">{{ $ext->User->Company->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <span class="badge {{ $status->colorbg }}">{{ $status->value }}</span>
                                            @if (!$ext->status)
                                                <span class="badge text-bg-danger">
                                                    Anterior: {{ $ext->comments?->last()?->title ?? 'Desconhecido' }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="undefined-movement {{ $movementClass }}">
                                            @if ($lastMovement)
                                                <strong>
                                                    {{ $daysAgo }} {{ $daysAgo === 1 ? 'dia' : 'dias' }}
                                                </strong>
                                                <small>{{ $lastMovement->format('d/m/Y H:i') }}</small>
                                            @else
                                                <span>Sem movimentação</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            wire:click.prevent="redirectTo('{{ $ext->Note->note ?? '' }}')"
                                            data-bs-toggle="tooltip" data-bs-title="Abrir detalhes da nota">
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
                    <div class="col-12 col-lg-7">
                        {{ $lists->onEachSide(1)->links() }}
                    </div>
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
</div>

@push('css')
    <style>
        .undefined-activity-page .activity-filter-card {
            height: auto;
        }

        .undefined-table-scroll {
            max-height: 66vh;
        }

        .undefined-loading {
            background: rgba(255, 255, 255, .78);
            backdrop-filter: blur(2px);
            z-index: 4;
        }

        .undefined-empty-state {
            color: var(--activity-muted);
            padding: 3rem 1rem;
            text-align: center;
        }

        .undefined-empty-state i {
            color: var(--bs-danger);
            display: block;
            font-size: 2.5rem;
            margin-bottom: .75rem;
        }

        .undefined-empty-state h5 {
            color: var(--activity-ink);
            font-weight: 600;
        }

        .undefined-empty-state p {
            margin: 0;
        }

        .undefined-movement {
            border-radius: .65rem;
            min-width: 8.5rem;
            padding: .55rem .7rem;
        }

        .undefined-movement strong,
        .undefined-movement small {
            display: block;
        }

        .undefined-movement strong {
            font-size: .82rem;
        }

        .undefined-movement small {
            color: var(--bs-secondary-color);
            font-size: .7rem;
            margin-top: .12rem;
        }

        .undefined-movement--danger {
            background: var(--bs-danger-bg-subtle);
            color: var(--bs-danger-text-emphasis);
        }

        .undefined-movement--warning {
            background: var(--bs-warning-bg-subtle);
            color: var(--bs-warning-text-emphasis);
        }

        .undefined-movement--success {
            background: var(--bs-success-bg-subtle);
            color: var(--bs-success-text-emphasis);
        }

        .undefined-movement--neutral {
            background: var(--bs-secondary-bg-subtle);
            color: var(--bs-secondary-text-emphasis);
        }
    </style>
@endpush
