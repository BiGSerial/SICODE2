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
            'type' => 'multiselect',
            'label' => 'Rubrica',
            'model' => 'rubricas',
            'placeholder' => 'Selecione Rubrica',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Note::class,
                'key' => 'rubrica',
                'label' => 'rubrica',
                'orderBy' => ['rubrica', 'asc'],
            ],
            'option_value' => 'id',
            'option_label' => 'nick',
        ],

        [
            'type' => 'multiselect',
            'label' => 'Entidade',
            'model' => 'entities',
            'placeholder' => 'Selecione Entidade',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Entity::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
            ],
            'option_value' => 'id',
            'option_label' => 'nick',
        ],

        [
            'type' => 'multiselect',
            'label' => 'Tipo de Entidade',
            'model' => 'types',
            'placeholder' => 'Selecione tipos',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\EntityType::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
            ],
            'option_value' => 'id',
            'option_label' => 'name',
        ],
    ];
@endphp

<div class="user-activity-page payment-activity-page">
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')
    @include('livewire.services.partials.user-activity-hero', [
        'context' => 'Fluxo financeiro de órgão externo',
        'subtitle' => 'Acompanhamento e atualização das solicitações de pagamento',
        'total' => $lists->total(),
        'accent' => '#0f766e',
    ])

    <div class="container-fluid">
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
                <div class="activity-filter-card">
                    <div class="activity-filter-title mb-3">
                        <i class="ri-filter-3-line me-1"></i> Pesquisa e filtros
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-12">
                            <label class="form-label mb-1 d-flex align-items-center justify-content-between">
                                <span>Buscar solicitação</span>
                                <a class="small text-decoration-none" data-bs-toggle="collapse"
                                    href="#paymentWildHelp" role="button" aria-expanded="false"
                                    aria-controls="paymentWildHelp">
                                    Como pesquisar?
                                </a>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                                <input type="text" class="form-control"
                                    placeholder="Nota, entidade, município ou responsável"
                                    wire:model.debounce.400ms="search" autocomplete="off">
                            </div>
                            <div class="collapse mt-2" id="paymentWildHelp">
                                <div class="alert alert-light border small mb-0 py-2">
                                    Use <code>*</code> ou <code>?</code> como curinga. Exemplos:
                                    <code>123*</code>, <code>*ABC</code> ou <code>A?C</code>.
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <x-filters.dynamic :filters="$filtersConfig" applyAction="applyFilters" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="payment-import-card h-100">
                    <div class="payment-import-card__header">
                        <span class="payment-import-card__icon"><i class="ri-file-upload-line"></i></span>
                        <div>
                            <h5>Atualizar retorno financeiro</h5>
                            <p>Importe o relatório para atualizar os pools já cadastrados.</p>
                        </div>
                    </div>
                    <div class="payment-import-card__body">
                        @livewire('services.oexterno.helpers.pool-payment-updater', key('pool-payment-updater'))
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
                        <i class="ri-bank-card-line me-2"></i>Aguardando pagamento
                    </h5>
                    <div class="user-activity-table-subtitle">
                        Solicitações vinculadas ao portal financeiro e aguardando processamento ou confirmação.
                    </div>
                </div>
                <span class="badge text-bg-warning">
                    {{ $lists->total() }} {{ $lists->total() === 1 ? 'solicitação' : 'solicitações' }}
                </span>
            </div>

            <div wire:loading.delay.class.remove="d-none"
                class="position-absolute top-0 start-0 w-100 h-100 d-none payment-loading">
                <div class="d-flex h-100 align-items-center justify-content-center gap-2">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <span>Atualizando pagamentos...</span>
                </div>
            </div>

            @if ($lists->isEmpty())
                <div class="payment-empty-state">
                    <i class="ri-bank-card-line"></i>
                    <h5>Nenhuma solicitação aguardando pagamento</h5>
                    <p>Revise os filtros ou aguarde novos registros nesta etapa.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Pool ID</th>
                                <th>Entidade</th>
                                <th>Nota</th>
                                <th>Status do pagamento</th>
                                <th>Confirmação FI</th>
                                <th>Solicitante</th>
                                <th>Última interação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $ext)
                                @php
                                    $payment = $ext->PoolPayments->last();
                                    $paymentStatus = $payment->status_pedido ?? 'Novo Pedido';
                                    $statusClass = match ($paymentStatus) {
                                        'Concluído', 'Pago' => 'text-bg-success',
                                        'Rejeitado' => 'text-bg-danger',
                                        'Em Elaboração' => 'text-bg-warning',
                                        default => 'text-bg-secondary',
                                    };
                                    $lastMovement = $ext->last_comment_at
                                        ? \Carbon\Carbon::parse($ext->last_comment_at)
                                        : null;
                                    $daysAgo = $lastMovement?->startOfDay()->diffInDays(now()->startOfDay());
                                    $movementClass = match (true) {
                                        $daysAgo === null => 'payment-movement--neutral',
                                        $daysAgo > 30 => 'payment-movement--danger',
                                        $daysAgo <= 20 => 'payment-movement--success',
                                        default => 'payment-movement--warning',
                                    };
                                @endphp
                                <tr wire:key="payment-external-{{ $ext->id }}">
                                    <td>
                                        <span class="payment-pool-id">{{ $payment->pool_id ?? 'Não informado' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">{{ $ext->Entity->nick ?? $ext->Entity->name ?? '—' }}</span>
                                            @if ($ext->Entity?->nick && $ext->Entity?->name && $ext->Entity->nick !== $ext->Entity->name)
                                                <small class="text-muted">{{ $ext->Entity->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-nowrap">{{ $ext->Note->note ?? '—' }}</td>
                                    <td><span class="badge {{ $statusClass }}">{{ $paymentStatus }}</span></td>
                                    <td>
                                        @if ($payment?->fi_fbv0)
                                            <span class="badge text-bg-success">{{ $payment->fi_fbv0 }}</span>
                                        @else
                                            <span class="text-muted">Pendente</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment?->user?->name ?? $ext->User?->name ?? '—' }}</td>
                                    <td>
                                        <div class="payment-movement {{ $movementClass }}">
                                            @if ($lastMovement)
                                                <strong>{{ $daysAgo }} {{ $daysAgo === 1 ? 'dia' : 'dias' }}</strong>
                                                <small>{{ $lastMovement->format('d/m/Y H:i') }}</small>
                                            @else
                                                <span>Sem interação</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            wire:click.prevent="redirectTo('{{ $ext->Note->note ?? '' }}', {{ $ext->id }})"
                                            data-bs-toggle="tooltip"
                                            data-bs-title="Abrir pagamento na entidade relacionada">
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
</div>

@push('css')
    <style>
        .payment-activity-page .activity-filter-card {
            height: 100%;
        }

        .payment-import-card {
            background: var(--activity-surface);
            border: 1px solid var(--activity-border);
            border-radius: .9rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
            overflow: hidden;
        }

        .payment-import-card__header {
            align-items: center;
            background: linear-gradient(135deg, #f8fafc, #eef7f5);
            border-bottom: 1px solid var(--activity-border);
            display: flex;
            gap: .75rem;
            padding: 1rem 1.1rem;
        }

        .payment-import-card__icon {
            align-items: center;
            background: var(--bs-success-bg-subtle);
            border-radius: .7rem;
            color: var(--bs-success);
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.2rem;
            height: 2.7rem;
            justify-content: center;
            width: 2.7rem;
        }

        .payment-import-card__header h5 {
            color: var(--activity-ink);
            font-size: .95rem;
            font-weight: 700;
            margin: 0;
        }

        .payment-import-card__header p {
            color: var(--activity-muted);
            font-size: .72rem;
            margin: .12rem 0 0;
        }

        .payment-import-card__body {
            padding: .75rem;
        }

        .payment-import-card__body>.card {
            border: 0 !important;
            box-shadow: none !important;
            margin: 0;
        }

        .payment-loading {
            background: rgba(255, 255, 255, .78);
            backdrop-filter: blur(2px);
            z-index: 4;
        }

        .payment-empty-state {
            color: var(--activity-muted);
            padding: 3rem 1rem;
            text-align: center;
        }

        .payment-empty-state i {
            color: var(--bs-danger);
            display: block;
            font-size: 2.5rem;
            margin-bottom: .75rem;
        }

        .payment-empty-state h5 {
            color: var(--activity-ink);
            font-weight: 600;
        }

        .payment-empty-state p {
            margin: 0;
        }

        .payment-pool-id {
            background: var(--bs-light);
            border: 1px solid var(--bs-border-color);
            border-radius: .5rem;
            display: inline-block;
            font-family: var(--bs-font-monospace);
            font-size: .82rem;
            font-weight: 700;
            padding: .38rem .55rem;
            white-space: nowrap;
        }

        .payment-movement {
            border-radius: .65rem;
            min-width: 8.5rem;
            padding: .55rem .7rem;
        }

        .payment-movement strong,
        .payment-movement small {
            display: block;
        }

        .payment-movement strong {
            font-size: .82rem;
        }

        .payment-movement small {
            color: var(--bs-secondary-color);
            font-size: .7rem;
            margin-top: .12rem;
        }

        .payment-movement--danger {
            background: var(--bs-danger-bg-subtle);
            color: var(--bs-danger-text-emphasis);
        }

        .payment-movement--warning {
            background: var(--bs-warning-bg-subtle);
            color: var(--bs-warning-text-emphasis);
        }

        .payment-movement--success {
            background: var(--bs-success-bg-subtle);
            color: var(--bs-success-text-emphasis);
        }

        .payment-movement--neutral {
            background: var(--bs-secondary-bg-subtle);
            color: var(--bs-secondary-text-emphasis);
        }
    </style>
@endpush
