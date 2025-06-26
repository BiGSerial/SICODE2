<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <!-- Per Page Select -->
                <div class="col-12 col-sm-6 col-md-2 d-flex align-items-center">
                    <div class="form-floating w-100">
                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                        <label for="perPageSelect">Registros por página</label>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="col-12 col-sm-6 col-md-3 d-flex align-items-center">
                    <div class="form-floating w-100">
                        <input wire:model.bounce.2s="search" type="text" class="form-control border border-secondary"
                            id="search" placeholder="Buscar">
                        <label for="search">Buscar</label>
                        <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                            data-bs-toggle="modal" data-bs-target="#buscar_multi">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                <!-- Type Note Buttons -->
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="btn-group w-100" role="group" aria-label="Tipo de Nota">
                        <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="1"
                            id="typeNote1">
                        <label class="btn btn-outline-primary" for="typeNote1">NA</label>

                        <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value="2"
                            id="typeNote2">
                        <label class="btn btn-outline-primary" for="typeNote2">OU</label>

                        <input type="radio" class="btn-check" name="typeNote" wire:model="typeNote" value=""
                            id="typeNote3">
                        <label class="btn btn-outline-primary" for="typeNote3">Ambos</label>
                    </div>
                </div>

                <!-- Filters -->
                <div class="col-12 col-md-4">
                    <div class="d-flex flex-wrap justify-content-center gap-2">
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

                        {{-- @livewire(
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
                        ) --}}

                        {{-- @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'rubrica',
                                'sendFilter' => '',
                                'modelClass' => \App\Models\Note::class,
                                'column' => 'rubrica',
                                'filterLabel' => 'Rúbrica',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'rubrica',
                                'direction' => 'ASC',
                                'searchColumn' => 'rubrica',
                                'sendSearchColumn' => 'rubrica',
                            ],
                            key('rubrica')
                        ) --}}

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

                        @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
                    </div>
                </div>
            </div>
        </div>
    </div>

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

            </span>
        </div>


    </div>


    <div class="card">
        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM DADOS EM RECLAMAÇÕES</h4>
            </div>
        @else
            <div class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
                <h4 class="mb-0">RECLAMAÇÕES EM ABERTO</h4>
                <button wire:click="exportToExcel" class="btn btn-success">
                    <i class="ri-file-excel-2-line me-2"></i>Exportar
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm  table-condensed table-hover table-striped">
                    <thead class="table-dark">
                        <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                            {{-- <th scope="col" class="fw-bold text-center">#</th> --}}
                            <th scope="col" class="fw-bold text-center">Numero</th>
                            <th scope="col" class="fw-bold text-center">Tipo</th>
                            <th scope="col" class="fw-bold text-center">Nota/Ov Ref</th>
                            <th scope="col" class="fw-bold text-center">Grupo</th>
                            <th scope="col" class="fw-bold text-center">Data Abertura</th>
                            <th scope="col" class="fw-bold text-center">Data Conclusao Desejada</th>
                            <th scope="col" class="fw-bold text-center">MEDE/TOTAL</th>
                            <th scope="col" class="fw-bold text-center">Descrição</th>
                            <th scope="col" class="fw-bold text-center">Município</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                    </thead>
                    <tbody>
                        @foreach ($lists as $index => $list)
                            @php

                                $vencimento = 'Indefinido';
                                $color = 'text-bg-secondary';

                                if ($list->dtConclusaoDesej) {
                                    $hoje = now()->startOfDay();
                                    $dataConclusao = $list->dtConclusaoDesej->startOfDay();

                                    if ($hoje->gt($dataConclusao)) {
                                        $vencimento = 'ATRASADO';
                                        $color = 'text-bg-danger';
                                    } else {
                                        $diasRestantes = $hoje->diffInDays($dataConclusao);

                                        if ($diasRestantes <= 3) {
                                            $vencimento = 'VENCENDO';
                                            $color = 'text-bg-warning';
                                        } else {
                                            $vencimento = 'NO PRAZO';
                                            $color = 'text-bg-success';
                                        }
                                    }
                                }

                                $totalMed = [
                                    'closed' => $list->medProtests?->where('statusSist', 'MEDE')->count(),
                                    'total' => $list->medProtests?->count(),
                                ];

                            @endphp
                            {{-- @dump($list->Productions) --}}
                            <tr class="align-middle text-center" wire:key="{{ $list->id }}-{{ $list->nota }}"
                                wire:dblClick='goTo({{ $list->nota }})'>
                                {{-- <td class="fw-bold copy-text text-center" data-value="{{ $list->nota }}">
                                    <span class="badge {{ $color }}">{{ $vencimento }}</span>
                                </td> --}}

                                <td class="fw-bold copy-text text-center" data-value="{{ $list->nota }}">
                                    {{ $list->nota }}
                                </td>


                                <td class="text-center align-middle "> {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    {{ $list->tipoNota }}
                                </td>

                                <td class="text-center align-middle text-primary fw-bold">
                                    {{ $list->Notes->isNotEmpty() ? $list->Notes[0]->note : '--' }}
                                </td>

                                <td class="text-start align-middle text-uppercase fw-bold"> {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    {{ $list->txtGrpCodificacao }}
                                </td>
                                <td class="text-center align-middle text-bg-secondary">
                                    {{ $list->dtAberturaNota?->format('d/m/Y') }}
                                </td>
                                <td class="fw-light text-center {{ $color ?? '' }}">
                                    {{-- Se a data de conclusão desejada for passada, exibe a data --}}
                                    {{ $list->dtConclusaoDesej?->format('d/m/Y') }}
                                </td>
                                <td class="fw-light text-center">
                                    <span class="badge text-bg-dark">{{ $totalMed['closed'] }} /
                                        {{ $totalMed['total'] }}</span>
                                </td>

                                <td class="fw-light text-start">
                                    <p class="my-0 py-0 fs-6"> {{ $list->descCausa }}</p>
                                    <p class="my-0 py-0 fs-6"> {{ $list->descSubCausa }}</p>
                                </td>

                                <td class="fw-light text-start fw-bold">
                                    {{ $list->cidade }}
                                </td>
                                <td class="fw-light text-center">{{ $list->statUsuar }}</td>



                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

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

            </span>
        </div>


    </div>
</div>
