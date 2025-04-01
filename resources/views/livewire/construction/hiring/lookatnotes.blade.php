<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <!-- Campo de busca com botão e tooltip -->
        <div class="input-group me-3" style="width: 250px;">
            <select class="form-select" wire:model="perPage" aria-label="Itens por página">
                <option value="20">20 itens</option>
                <option value="50">50 itens</option>
                <option value="100">100 itens</option>
                <option value="250">250 itens</option>
                <option value="500">500 itens</option>
            </select>

        </div>
        <div class="input-group me-3">
            <input type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar"
                wire:model.debounce.1s="search">
            <span data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Multinotas">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                    data-bs-target="#modal_multi_notas" title="Multinotas">
                    <i class="ri-checkbox-multiple-blank-fill"></i>
                </button>
            </span>
        </div>

        <!-- Botões do tipo radio para seleção individual -->
        <div class="btn-group me-3" role="group" aria-label="Seleção de Opções">
            <input type="radio" class="btn-check" name="selecao" id="nota" autocomplete="off"
                wire:model="typeNote" value="1">
            <label class="btn btn-outline-primary" for="nota">Nota</label>

            <input type="radio" class="btn-check" name="selecao" id="ov" autocomplete="off"
                wire:model="typeNote" value="2">
            <label class="btn btn-outline-primary" for="ov">Ov</label>

            <input type="radio" class="btn-check" name="selecao" id="ambas" autocomplete="off"
                wire:model="typeNote" value="">
            <label class="btn btn-outline-primary" for="ambas">Ambas</label>
        </div>

        <!-- Botão SEM ATRIBUIÇÃO com indicador de ativado/desativado -->


        <!-- Quatro botões alinhados -->
        <div class="btn-group" role="group" aria-label="Ações">
            @livewire('components.filter.filter', ['myKey' => 'operacao', 'sendFilter' => '', 'model' => 'App\Models\Operation', 'column' => 'cenTrab', 'filter' => 'Empreiteira', 'group_filter' => 'lookatnotes', 'values' => 'cenTrab', 'direction' => 'ASC', 'query' => "operacao = '0010'"], key('operacao'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'lookatnotes', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regiao', 'group_filter' => 'lookatnotes', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('region'))
            {{-- @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'lookatnotes', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional')) --}}
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'lookatnotes', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'lookatnotes'], key('removeAll'))
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            {{ $lists->links() }}
        </div>
        <div>
            <span class="text-muted">Mostrando {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                {{ $lists->total() ?? 0 }} resultados</span>
        </div>
    </div>
    <div class="card">
        <div class="card-header py-1 edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="card-title py-1 my-1">OBRAS EM SITUAÇÃO DE CONTRATAÇÃO</h4>
        </div>
        <div class="table-responsible">
            @if ($lists->count())
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead>
                        <tr class="align-middle table-dark">

                            <th class="text-center" scope="col">Nota</th>
                            <th class="text-center" scope="col">RUBRICA</th>
                            <th class="text-center" scope="col">MUNICÍPIO</th>
                            <th class="text-center" scope="col">DATA STATUS</th>
                            <th class="text-center" scope="col">ANALISE EM</th>
                            <th class="text-center" scope="col">RI EM (1º)</th>
                            <th class="text-center" scope="col">RI FIM (ULTIMO)</th>
                            <th class="text-center" scope="col">APROVADO EM</th>
                            <th class="text-center" scope="col">AGUARDANDO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $init_analise = $list->approval?->created_at
                                    ? $list->approval?->created_at->format('d/m/Y')
                                    : '---';
                                $ri_fim =
                                    $list->approval?->reclaims?->last() &&
                                    $list->approval?->reclaims?->last()->completed
                                        ? $list->approval?->reclaims?->last()->completed_at->format('d/m/Y')
                                        : '---';
                                $ri_init =
                                    $list->approval?->reclaims?->first() &&
                                    $list->approval?->reclaims?->first()->created_at
                                        ? $list->approval?->reclaims?->first()->created_at->format('d/m/Y')
                                        : '---';

                                $approved_at = $list->approval?->approved
                                    ? $list->approval?->approved_at->format('d/m/Y')
                                    : '---';

                                if ($init_analise == '---') {
                                    $responsible = 'PROGRAMADOR';
                                } elseif ($approved_at !== '---') {
                                    $responsible = 'CONTRATANTE';
                                } elseif ($ri_init == '---') {
                                    $responsible = 'PROGRAMADOR';
                                } elseif ($ri_fim == '---') {
                                    $responsible = 'CIP';
                                } else {
                                    $responsible = 'PROGRAMADOR';
                                }

                            @endphp
                            <tr class="align-middle">

                                <td class="text-center fw-bold">
                                    {{ $list->note }}
                                </td>
                                <td class="text-center">
                                    {{ $list->rubrica }}
                                </td>
                                <td class="text-center">
                                    {{ $list->lexp }}
                                </td>
                                <td class="text-center">
                                    {{ $list->dt_status?->format('d/m/Y') }}
                                </td>
                                <td class="text-center">
                                    {{ $init_analise }}
                                </td>
                                <td class="text-center">
                                    {{ $ri_init }}
                                </td>
                                <td class="text-center">
                                    {{ $ri_fim }}
                                </td>
                                <td class="text-center">
                                    {{ $approved_at }}
                                </td>
                                <td class="text-center fw-bold">
                                    {{ $responsible }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="card-body">
                    <div class="alert alert-warning" role="alert">
                        <h4 class="text-center fw-bold">Sem resultados</h4>
                        <p class="text-center">Não foram encontrados resultados para a pesquisa realizada.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            {{ $lists->links() }}
        </div>
        <div>
            <span class="text-muted">Mostrando {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                {{ $lists->total() ?? 0 }} resultados</span>
        </div>
    </div>

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="modal_multi_notas" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advancedSearch" id="advancedSearch" cols="50" rows="10"
                        wire:model.defer="advancedSearch"></textarea>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>
</div>
