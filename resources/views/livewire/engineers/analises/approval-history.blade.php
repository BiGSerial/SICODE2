@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;

@endphp
<div>
    <x-show-loading />
    <x-showselected :count="$selected" />

    <div class="d-flex flex-column mb-3">

        <!-- Linha 1: Busca, Tipo de Nota e Data Selection -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">

            <!-- Campo de busca com botão e tooltip -->
            <div class="col-md-4">
                <div class="input-group  me-3 mb-2">
                    <input type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar"
                        wire:model.debounce.1s="search">
                    <span data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top"
                        data-bs-content="Multinotas">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                            data-bs-target="#modal_multi_notas" title="Multinotas">
                            <i class="ri-checkbox-multiple-blank-fill"></i>
                        </button>
                    </span>
                </div>
            </div>

            <!-- Botões do tipo radio para seleção individual -->
            <div class="btn-group me-3 mb-2" role="group" aria-label="Seleção de Opções">
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

            <!-- Date Selection (Month/Year, Start Date, End Date) -->
            <div class="d-flex flex-wrap align-items-center">

                <!-- Select Mês/Ano -->
                <div class="me-3 mb-2">
                    <label for="month" class="form-label visually-hidden">Mês/Ano</label>
                    <input type="month" class="form-control" id="month" wire:model="month">
                </div>

                <!-- Data Inicial -->
                <div class="me-3 mb-2">
                    <label for="date_init" class="form-label visually-hidden">Data Inicial</label>
                    <input type="date" class="form-control" id="date_init" wire:model="date_init"
                        @if ($month) disabled @endif>
                </div>

                <!-- Data Final -->
                <div class="me-3 mb-2">
                    <label for="date_end" class="form-label visually-hidden">Data Final</label>
                    <input type="date" class="form-control" id="date_end" wire:model="date_end"
                        @if ($month) disabled @endif>
                </div>
            </div>
        </div>

        <!-- Linha 2: Filtros -->
        <div class="d-flex flex-wrap align-items-center justify-content-end">
            <div class="btn-group" role="group" aria-label="Ações">
                @livewire('components.filter.filter', ['myKey' => 'operacao', 'sendFilter' => '', 'model' => 'App\Models\Operation', 'column' => 'cenTrab', 'filter' => 'Empreiteira', 'group_filter' => 'analises', 'values' => 'cenTrab', 'direction' => 'ASC', 'query' => "operacao = '0010'"], key('operacao'))
                @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'analises', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'analises', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'analises', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
                @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'analises', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                @livewire('components.filter.remove-all', ['group_filter' => 'analises'], key('removeAll'))
            </div>
        </div>

    </div>

    @if ($lists->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center mt-3">

            <div>
                {{ $lists->links() }}
            </div>
            <div>
                Exibindo página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}, total de
                {{ $lists->total() }} registros.
            </div>
        </div>
    @endif
    <div class="card edp-bg-gray">
        <div
            class="card-header d-flex justify-content-between align-items-center  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4 mb-0">PROJETOS VALIDADOS</h4>
            <div>
                <button type="button" class="btn btn-primary" wire:click.prevent="export_excel">Exportar</button>

            </div>
        </div>
        @if ($lists->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed table-sm">
                    <thead>
                        <tr class="table-dark">
                            <th class="text-center align-middle">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:click.prevent="setSelectAll"
                                        id="selectAll" title="Select All" @checked($this->chkAllSelected($lists))>

                                </div>
                            </th>
                            <th class="text-center align-middle">Nota</th>
                            <th class="text-center align-middle">Ordem</th>
                            <th class="text-center align-middle">Files</th>
                            <th class="text-center align-middle">Rubrica</th>
                            <th class="text-center align-middle">Município</th>
                            <th class="text-center align-middle">Empreiteira</th>
                            <th class="text-center align-middle">Aprovado Por</th>
                            <th class="text-center align-middle">Approvado Em</th>
                            <th class="text-center align-middle">Contratado Em</th>
                            <th class="text-center align-middle">Viabilizado Em</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr wire:key="linha-{{ $list->id }}">
                                <td class="text-center align-middle">
                                    <div class="form-check">
                                        <input type="checkbox"
                                            class="form-check-input border-1 border-secondary select"
                                            wire:model.defer="selected" value='{{ $list->id }}'>

                                    </div>
                                </td>
                                <td class="text-center align-middle">{{ $list->note }}</td>
                                <td class="text-center align-middle">
                                    @if ($list->orders->isNotEmpty())
                                        @foreach ($list->orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <x-files.select-download-list :files='$list->Files' />
                                </td>
                                <td class="text-center align-middle">{{ $list->rubrica }}</td>
                                <td class="text-center align-middle">{{ $list->lexp }}</td>
                                <td class="text-center align-middle">
                                    @if ($list->orders->isNotEmpty())
                                        {{ isset($list->orders->last()->operations->first()->cenTrab) ? $list->orders->last()->operations->first()->cenTrab : '---' }}
                                    @else
                                        ---
                                    @endif
                                </td>

                                <td class="text-center align-middle">
                                    {{ $list->approval ? $list->approval->user->name : '---' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->approval ? $list->approval->approved_at->format('d/m/Y H:i:s') : '---' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->Viabilities->isNotEmpty() && $list->Viabilities->last()->hired ? $list->Viabilities->last()->hired_at->format('d/m/Y') : '---' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $list->Viabilities->isNotEmpty() && $list->Viabilities->last()->returned_at ? $list->Viabilities->last()->returned_at->format('d/m/Y') : '---' }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <h5 class="text-center">SEM OBRA PARA AVALIAÇÃO DE PROJETO</h5>
            </div>
        @endif
    </div>
    @if ($lists->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center mt-3">

            <div>
                {{ $lists->links() }}
            </div>
            <div>
                Exibindo página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}, total de
                {{ $lists->total() }} registros.
            </div>
        </div>
    @endif

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="modal_multi_notas" tabindex="-1"
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
    @livewire('responsible.actions.reject-project', key('rejectProject'))

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.select');
            const massApproveBtn = document.getElementById('massApprove');

            function updateMassApprove() {
                let selectedCount = 0;
                checkboxes.forEach(chk => {
                    if (chk.checked) {
                        selectedCount++;
                    }
                });
                if (selectedCount > 1) {
                    massApproveBtn.removeAttribute('disabled');
                } else {
                    massApproveBtn.setAttribute('disabled', true);
                }
            }

            checkboxes.forEach(chk => {
                chk.addEventListener('change', updateMassApprove);
            });

            // Initialize button state on page load
            updateMassApprove();
        });
    </script>



</div>
