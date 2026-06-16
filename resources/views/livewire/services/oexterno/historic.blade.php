@php
    use App\Helpers\SelectOptions;

    $selectOptions = collect(SelectOptions::getProtocolReasons());
@endphp

<div class="user-activity-page historic-activity-page">
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')
    @include('livewire.services.partials.user-activity-hero', [
        'context' => 'Histórico de órgão externo',
        'subtitle' => 'Consulta de tratativas concluídas, entidades e protocolos registrados',
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
                    <label class="form-label">Buscar no histórico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" placeholder="Nota, protocolo ou entidade"
                            wire:model.debounce.600ms="search" autocomplete="off">
                    </div>
                </div>

                <div class="col-12 col-md-8 col-xl-4">
                    <label class="form-label">Período de conclusão</label>
                    <div class="row g-2">
                        <div class="col">
                            <input type="date" class="form-control" wire:model="dtIn" aria-label="Data inicial">
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" wire:model="dtOut"
                                min="{{ $dtIn }}" aria-label="Data final">
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4 col-xl-4">
                    <label class="form-label">Tipo de nota</label>
                    <div class="btn-group w-100" role="group" aria-label="Tipo de nota">
                        <input type="radio" class="btn-check" id="historicNote1" value="1" wire:model="typeNote">
                        <label class="btn btn-outline-primary" for="historicNote1">Nota</label>

                        <input type="radio" class="btn-check" id="historicNote2" value="2" wire:model="typeNote">
                        <label class="btn btn-outline-primary" for="historicNote2">OV</label>

                        <input type="radio" class="btn-check" id="historicNoteAll" value="" wire:model="typeNote">
                        <label class="btn btn-outline-primary" for="historicNoteAll">Ambos</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="historic-secondary-filters d-flex flex-wrap align-items-center gap-2">
                        @livewire('components.filter.filter', [
                            'myKey' => 'rubrica',
                            'sendFilter' => '',
                            'model' => 'App\Models\Note',
                            'column' => 'rubrica',
                            'filter' => 'Rubrica',
                            'group_filter' => 'oexterno',
                            'values' => 'rubrica',
                            'direction' => 'ASC',
                            'query' => '',
                        ], key('historic.rubrica'))

                        @livewire('components.filter.filter', [
                            'myKey' => 'region',
                            'sendFilter' => 'city',
                            'model' => 'App\Models\Edp_depc\City',
                            'column' => 'regiao',
                            'filter' => 'Região',
                            'group_filter' => 'oexterno',
                            'values' => 'regiao',
                            'direction' => 'ASC',
                            'query' => '',
                        ], key('historic.region'))

                        @livewire('components.filter.filter', [
                            'myKey' => 'city',
                            'sendFilter' => '',
                            'model' => 'App\Models\Edp_depc\City',
                            'column' => 'cidade',
                            'filter' => 'Município',
                            'group_filter' => 'oexterno',
                            'values' => 'municipio',
                            'direction' => 'ASC',
                            'query' => '',
                        ], key('historic.city'))

                        @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('historic.removeAll'))
                    </div>
                </div>

                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label">Registros por página</label>
                    <select class="form-select" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
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
            <div class="user-activity-table-header historic-table-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h5 class="user-activity-table-title">
                        <i class="ri-history-line me-2"></i>Histórico de conclusões
                    </h5>
                    <div class="user-activity-table-subtitle">
                        Tratativas encerradas e respectivos protocolos externos.
                    </div>
                </div>
                <span class="badge text-bg-warning">
                    {{ $lists->total() }} {{ $lists->total() === 1 ? 'conclusão' : 'conclusões' }}
                </span>
            </div>

            <div wire:loading.delay.class.remove="d-none"
                class="position-absolute top-0 start-0 w-100 h-100 d-none historic-loading">
                <div class="d-flex h-100 align-items-center justify-content-center gap-2">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <span>Atualizando histórico...</span>
                </div>
            </div>

            @if ($lists->isEmpty())
                <div class="historic-empty-state">
                    <i class="ri-history-line"></i>
                    <h5>Nenhuma conclusão encontrada</h5>
                    <p>Revise os filtros ou o período informado.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nota</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Entidade</th>
                                <th>Tipo de entidade</th>
                                <th>Responsável</th>
                                <th>Status</th>
                                <th>Concluído em</th>
                                <th>Protocolos</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $external)
                                @php
                                    $status = $selectOptions->firstWhere('value', $external->status);
                                @endphp
                                <tr wire:key="historic-row-{{ $external->id }}"
                                    wire:dblclick="navigateTo('{{ $external->Note->note }}', {{ $external->id }})">
                                    <td class="fw-semibold text-nowrap">{{ $external->Note->note ?? '—' }}</td>
                                    <td>{{ $external->Note->rubrica ?? '—' }}</td>
                                    <td>{{ $external->Note->lexp ?? '—' }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">
                                                {{ $external->Entity->nick ?? $external->Entity->name ?? '—' }}
                                            </span>
                                            @if ($external->Entity?->nick && $external->Entity?->name && $external->Entity->nick !== $external->Entity->name)
                                                <small class="text-muted">{{ $external->Entity->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border text-dark">
                                            {{ $external->Entity->Type->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $external->User->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $status?->colorbg ?? 'text-bg-secondary' }}">
                                            {{ $status?->reason ?? $status?->label ?? $external->status ?? 'Finalizado' }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        {{ optional($external->updated_at)->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($external->Protocols->isNotEmpty())
                                            <div class="historic-protocols">
                                                @foreach ($external->Protocols->sortByDesc('created_at') as $protocol)
                                                    <span class="historic-protocol-code">{{ $protocol->protocol }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">Sem protocolo</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            wire:click.prevent="navigateTo('{{ $external->Note->note }}', {{ $external->id }})"
                                            data-bs-toggle="tooltip"
                                            data-bs-title="Abrir entidade concluída">
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
        .historic-secondary-filters > div {
            margin: 0 !important;
        }

        .historic-secondary-filters .position-absolute {
            z-index: 1080 !important;
        }

        .historic-loading {
            background: rgba(255, 255, 255, .78);
            backdrop-filter: blur(2px);
            z-index: 4;
        }

        .historic-table-header {
            background: linear-gradient(120deg, #0f5f66, #0f766e);
        }

        .historic-empty-state {
            color: var(--activity-muted);
            padding: 3rem 1rem;
            text-align: center;
        }

        .historic-empty-state i {
            color: #0f766e;
            display: block;
            font-size: 2.5rem;
            margin-bottom: .75rem;
        }

        .historic-empty-state h5 {
            color: var(--activity-ink);
            font-weight: 600;
        }

        .historic-empty-state p {
            margin: 0;
        }

        .historic-protocols {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            min-width: 8rem;
        }

        .historic-protocol-code {
            background: var(--bs-secondary-bg-subtle);
            border: 1px solid var(--bs-border-color);
            border-radius: .45rem;
            color: var(--bs-secondary-text-emphasis);
            font-family: var(--bs-font-monospace);
            font-size: .72rem;
            font-weight: 700;
            padding: .3rem .5rem;
            white-space: nowrap;
        }
    </style>
@endpush
