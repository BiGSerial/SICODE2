@php
    use App\Helpers\SelectOptions;

    $selectOptions = collect(SelectOptions::getProtocolReasons());
@endphp

<div>
    <x-show-loading />

    <style>
        .historic-filter-card {
            background-color: #f9fbfd;
            border: 1px solid #dde2eb;
            border-radius: 0.75rem;
            padding: 1rem;
        }
    </style>

    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <div class="historic-filter-card h-100">
                        <label class="form-label mb-1">Buscar</label>
                        <input type="text" class="form-control" placeholder="Nota, protocolo, entidade..."
                            wire:model.debounce.600ms="search" autocomplete="off">
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="historic-filter-card h-100">
                        <label class="form-label mb-1">Periodo de conclusao</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="date" class="form-control" wire:model="dtIn">
                            </div>
                            <div class="col">
                                <input type="date" class="form-control" wire:model="dtOut"
                                    min="{{ $dtIn }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="historic-filter-card h-100">
                        <label class="form-label mb-1">Tipo de nota</label>
                        <div class="btn-group w-100" role="group" aria-label="Tipo nota">
                            <input type="radio" class="btn-check" id="historicNote1" value="1" wire:model="typeNote">
                            <label class="btn btn-outline-primary" for="historicNote1">Nota</label>

                            <input type="radio" class="btn-check" id="historicNote2" value="2" wire:model="typeNote">
                            <label class="btn btn-outline-primary" for="historicNote2">OV</label>

                            <input type="radio" class="btn-check" id="historicNoteAll" value="" wire:model="typeNote">
                            <label class="btn btn-outline-primary" for="historicNoteAll">Ambos</label>
                        </div>
                        <div class="mt-3 d-flex flex-wrap gap-2">
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
                                'filter' => 'Regiao',
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
                                'filter' => 'Municipio',
                                'group_filter' => 'oexterno',
                                'values' => 'municipio',
                                'direction' => 'ASC',
                                'query' => '',
                            ], key('historic.city'))

                            @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('historic.removeAll'))
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Historico de conclusoes</h5>
                <small class="text-muted">Serviço: {{ $service->service ?? 'N/D' }}</small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label class="text-muted small mb-0">Por pagina</label>
                <select class="form-select form-select-sm" wire:model="perPage">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>

        <div class="card-body p-0 position-relative">
            <div wire:loading.delay.class.remove="d-none"
                class="position-absolute top-0 start-0 w-100 h-100 d-none"
                style="background: rgba(255,255,255,.65); z-index: 2;">
                <div class="d-flex h-100 align-items-center justify-content-center gap-2">
                    <div class="spinner-border text-primary" role="status"></div>
                    <span>Carregando...</span>
                </div>
            </div>

            @if (!$lists->count())
                <div class="p-4 text-center text-muted">
                    Nenhum registro encontrado com os filtros atuais.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nota</th>
                                <th>Rubrica</th>
                                <th>Municipio</th>
                                <th>Entidade</th>
                                <th>Tipo Entidade</th>
                                <th>Usuario</th>
                                <th>Status</th>
                                <th>Concluido em</th>
                                <th>Protocolos</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $index => $external)
                                @php
                                    $status = $selectOptions->firstWhere('value', $external->status);
                                @endphp
                                <tr wire:key="historic-row-{{ $external->id }}"
                                    wire:dblclick="navigateTo('{{ $external->Note->note }}')">
                                    <td class="text-muted">
                                        {{ ($lists->currentPage() - 1) * $lists->perPage() + $index + 1 }}
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $external->Note->note ?? 'N/D' }}
                                    </td>
                                    <td>{{ $external->Note->rubrica ?? 'N/D' }}</td>
                                    <td>{{ $external->Note->lexp ?? 'N/D' }}</td>
                                    <td>{{ $external->Entity->name ?? 'N/D' }}</td>
                                    <td>{{ $external->Entity->Type->name ?? 'N/D' }}</td>
                                    <td>{{ $external->User->name ?? 'N/D' }}</td>
                                    <td>
                                        @if ($status)
                                            <span class="badge bg-secondary">{{ $status['label'] ?? $external->status }}</span>
                                        @else
                                            <span class="badge bg-light text-muted">{{ $external->status ?? 'N/D' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($external->updated_at)->format('d/m/Y H:i') ?? 'N/D' }}
                                    </td>
                                    <td>
                                        @if ($external->Protocols->isNotEmpty())
                                            <div class="d-flex flex-column gap-1">
                                                @foreach ($external->Protocols as $protocol)
                                                    <span class="badge bg-light text-dark">
                                                        {{ $protocol->protocol }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary"
                                            wire:click="navigateTo('{{ $external->Note->note }}')">
                                            Abrir nota
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 p-3">
                    <div>
                        {{ $lists->links() }}
                    </div>
                    <div class="text-muted small">
                        Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
