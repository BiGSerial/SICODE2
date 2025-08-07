<div>
    {{-- Loading indicator --}}
    <x-show-loading />

    {{-- Filters and Controls Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                {{-- Records per page --}}
                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select" wire:model.live="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                        <label for="perPageSelect">Registros por página</label>
                    </div>
                </div>

                {{-- Search input --}}
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="form-floating position-relative">
                        <input wire:model.live.debounce.500ms="search" type="text" class="form-control"
                            id="search" placeholder="Buscar">
                        <label for="search">Buscar</label>
                        <button type="button"
                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                            data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Busca múltipla">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                {{-- Type filter --}}
                <div class="col-12 col-lg-3">
                    <div class="form-floating">
                        <select class="form-select" wire:model.live="type" id="typeSelect">
                            <option value="">Ambos</option>
                            <option value="NA">NA</option>
                            <option value="OU">OU</option>
                        </select>
                        <label for="typeSelect">Tipo de Nota</label>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="col-12 col-lg-4">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'group',
                                'sendFilter' => '',
                                'modelClass' => \App\Models\Protest::class,
                                'column' => 'txtGrpCodificacao',
                                'filterLabel' => 'Grupo',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'txtGrpCodificacao',
                                'direction' => 'ASC',
                                'searchColumn' => '',
                                'sendSearchColumn' => 'entity_type_id',
                            ],
                            key('entityTypes')
                        )

                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'region',
                                'sendFilter' => 'aRegional',
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
                                'myKey' => 'aRegional',
                                'sendFilter' => 'city',
                                'modelClass' => \App\Models\Edp_depc\City::class,
                                'column' => 'regiao',
                                'filterLabel' => 'Regional',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'regional',
                                'direction' => 'ASC',
                                'searchColumn' => 'regional',
                                'sendSearchColumn' => 'regional',
                            ],
                            key('regional')
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

                        @livewire(
                            'components.filter.remove-all',
                            [
                                'group_filter' => 'oexterno',
                            ],
                            key('removeAll')
                        )
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Results summary and pagination --}}
    @if ($lists->count())
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                {{ $lists->links() }}
            </div>
            <div class="text-muted">
                Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }}
                de {{ $lists->total() }} registros
            </div>
        </div>
    @endif

    {{-- Main content card --}}
    <div class="card shadow-sm">
        @if (!$lists->count())
            <div class="card-body text-center py-5">
                <div class="text-muted">
                    <i class="ri-inbox-line fs-1 d-block mb-3"></i>
                    <h4>Nenhuma reclamação encontrada</h4>
                    <p class="mb-0">Tente ajustar os filtros de busca</p>
                </div>
            </div>
        @else
            <div class="card-header text-bg-secondary d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">
                    <i class="ri-alert-line me-2"></i>
                    Reclamações em Aberto
                </h5>
                <button wire:click="exportToExcel" class="btn btn-success btn-sm">
                    <i class="ri-file-excel-2-line me-1"></i>
                    Exportar Excel
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th scope="col" class="text-center" style="width: 100px;">Status</th>
                            <th scope="col" class="text-center" style="width: 50px;">M</th>
                            <th scope="col" class="text-center" style="width: 120px;">Número</th>
                            <th scope="col" class="text-center" style="width: 80px;">Tipo</th>
                            <th scope="col" class="text-center" style="width: 80px;">Cod</th>
                            <th scope="col" class="text-center" style="width: 120px;">Nota/OV Ref</th>
                            <th scope="col" class="text-center" style="width: 150px;">Grupo</th>
                            <th scope="col" class="text-center" style="width: 120px;">Data Abertura</th>
                            <th scope="col" class="text-center" style="width: 140px;">Data Conclusão Desej</th>
                            <th scope="col" class="text-center" style="width: 100px;">MEDE/Total</th>
                            <th scope="col" class="text-start">Descrição</th>
                            <th scope="col" class="text-center" style="width: 150px;">Município</th>
                            <th scope="col" class="text-center" style="width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                // $statusData = $this->getStatusData($list);
                                // $medData = $this->getMedData($list);

                                $statusData = [
                                    'color' => !$list->dtConclusaoDesej->isPast() ? 'bg-success' : 'bg-danger',
                                    'text' => !$list->dtConclusaoDesej->isPast() ? 'Vencido' : 'No Prazo',
                                ];

                                $medData = [
                                    'total' => $list->medProtests?->count(),
                                    'closed' => $list->medProtests?->where('statusSist', 'MEDE')?->count(),
                                    'status' => 'Desconhecido',
                                    'color' => 'text-bg-secondary',
                                    'monitoring' => false,
                                ];

                                if ($medProtest = $list->medProtests?->where('statusSist', 'MEDA')->first()) {
                                    if ($medProtest->assignments->isNotEmpty()) {
                                        $medData['monitoring'] = $medProtest->needsConfirmation;
                                        if ($assigment = $medProtest->assignments->where('user', true)->first()) {
                                            if ($assigment->completed) {
                                                $medData['status'] = 'Concluído';
                                                $medData['color'] = 'text-bg-success';
                                            } else {
                                                $medData['status'] = 'Em Andamento';
                                                $medData['color'] = 'text-bg-danger';
                                            }
                                        } else {
                                            $medData['status'] = 'Aguardando Responsável';
                                            $medData['color'] = 'text-bg-warning';
                                        }
                                    } else {
                                        $medData['status'] = 'Aguardando';
                                        $medData['color'] = 'text-bg-secondary';
                                    }
                                } else {
                                    $medData['status'] = 'Fechado';
                                    $medData['color'] = 'text-bg-secondary';
                                }

                            @endphp

                            <tr class="align-middle" wire:key="{{ $list->id }}"
                                wire:click="goTo({{ $list->nota }})" style="cursor: pointer;"
                                title="Clique para visualizar detalhes">

                                <td class="text-center">

                                    <span class="badge {{ $medData['color'] }}">

                                        {{ $medData['status'] }}
                                    </span>

                                </td>
                                <td class="text-center">
                                    @if ($medData['monitoring'])
                                        <i class="ri-eye-line text-primary"></i>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">
                                    <span class="user-select-all">{{ $list->nota }}</span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-light text-dark">{{ $list->tipoNota }}</span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-light text-dark">{{ $list->codecodf }}</span>
                                </td>

                                <td class="text-center fw-bold text-primary">
                                    {{ $list->Notes->first()?->note ?? '--' }}
                                </td>

                                <td class="text-start fw-bold text-uppercase small">
                                    {{ $list->txtGrpCodificacao }}
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-secondary">

                                        <p class="my-0 p-0">{{ $list->dtAberturaNota?->format('d/m/Y') }}</p>
                                        <p class="my-0 p-0">{{ $list->dtAberturaNota?->diffInDays() }} Dias</p>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge {{ $statusData['color'] }}">
                                        <p class="my-0 p-0">{{ $list->dtConclusaoDesej?->format('d/m/Y') }}</p>
                                        <p class="my-0 p-0">{{ $list->dtConclusaoDesej?->diffInDays() }} Dias</p>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-dark">
                                        {{ $medData['closed'] }}/{{ $medData['total'] }}
                                    </span>
                                </td>

                                <td class="text-start">
                                    <div class="small">
                                        <div class="fw-bold">{{ $list->descCausa }}</div>
                                        <div class="text-muted">{{ $list->descSubCausa }}</div>
                                    </div>
                                </td>

                                <td class="text-center fw-bold small">
                                    {{ $list->cidade }}
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-info">{{ $list->statUsuar }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Bottom pagination --}}
    @if ($lists->count())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                {{ $lists->links() }}
            </div>
            <div class="text-muted">
                Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }}
                de {{ $lists->total() }} registros
            </div>
        </div>
    @endif

    {{-- Multi-search Modal --}}
    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">
                        <i class="ri-search-2-line me-2"></i>
                        Buscar Múltiplas Notas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating">
                        <textarea class="form-control" wire:model.defer="advanceSearch" id="advanceSearch" style="height: 200px;"
                            placeholder="Digite os números das notas separados por vírgula ou quebra de linha"></textarea>
                        <label for="advanceSearch">Números das notas</label>
                    </div>
                    <div class="form-text">
                        Digite os números das notas separados por vírgula ou quebra de linha
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">
                        <i class="ri-search-line me-1"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
