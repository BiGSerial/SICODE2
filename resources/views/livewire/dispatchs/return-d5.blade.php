@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card edp-bg-gray">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">RETORNO INTERNO (RI) {{ $service->service }}</h4>
        </div>
        <div class="card-body py-3 mt-3">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex flex-wrap align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <label for="perPage" class="me-2 mb-0 fw-bold">Páginas:</label>
                        <select id="perPage" wire:model="perPage" class="form-select form-select-sm"
                            style="width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label for="search" class="me-2 mb-0 fw-bold">Busca:</label>
                        <input id="search" type="text" wire:model="search" class="form-control form-control-sm"
                            placeholder="Buscar...">
                    </div>
                    <button class="btn {{ $notAtt ? 'btn-primary' : 'btn-outline-primary' }} btn-sm"
                        wire:click.prevent="setNotAtt" data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ $notAtt ? 'Filtro ativado' : 'Filtro desativado' }}">
                        <i class="ri-filter-line me-1"></i> Sem Atribuição
                    </button>
                </div>
                <div
                    class="col-md-6 d-flex justify-content-md-end justify-content-start align-items-center gap-2 mt-2 mt-md-0">

                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'd5controls', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'd5controls', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'd5controls', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'd5controls', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'd5controls'], key('removeAll'))
                    {{-- <button class="btn btn-sm btn-danger" wire:click.prevent="cleanUser" wire:target="cleanUser"
                        @disabled(!$filterUser) wire:loading.attr="disabled" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Limpar Filtro Usuario">
                        <span wire:target="cleanUser" wire:loading.remove>
                            <i class="ri-filter-off-line fs-5"></i>
                        </span>
                        <span wire:target="cleanUser" wire:loading>
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </span>
                    </button> --}}
                    <button class="btn btn-sm btn-primary" wire:click.prevent="exportToExcel"
                        wire:target="exportToExcel" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Exportar para Excel">
                        <span wire:target="exportToExcel" wire:loading.remove>
                            <i class="ri-file-excel-2-line fs-5"></i>
                        </span>
                        <span wire:target="exportToExcel" wire:loading>
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mx-3">
        <div class="col-6">
            {{ $lists->links() }}
        </div>
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.</span>
        </div>
    </div>

    <div class="card">
        <div
            class="card-header edp-bg-sprucegreen-70 edp-text-verde-dark d-flex justify-content-between align-items-center">
            <h4 class="my-1 py-0">LISTA EM RETONO INTERNO</h4>
            <button class="btn btn-sm btn-primary" wire:click.prevent="massAssign" wire:target="massAssign"
                data-bs-toggle="tooltip" data-bs-placement="left" title="Atribuição em Massa">
                <i class="ri-user-shared-line me-1"></i> Atribuir em Massa
            </button>
        </div>
        <table class="table table-sm table-condensed table-striped-columns">
            <thead>
                <th class="text-center"><input type="checkbox" class="form-checkbox" wire:model="selectAll"></th>
                <th scope="col" class="text-center" data-bs-container="body" data-bs-toggle="popover"
                    data-bs-trigger="hover" data-bs-placement="left" title="Legenda das Cores"
                    data-bs-content="<ul class='list-unstyled mb-0'>
                    <li>
                        <span class='fs-4 me-2'>■</span>
                         Contratação
                    </li>
                    <li>
                          <span class='fs-4 me-2 text-warning'>■</span>
                         Analise de Projeto
                    </li>
                    <li>
                          <span class='fs-4 me-2 text-info'>■</span>
                         Viabilidade
                    </li>
                </ul>">
                    <span href="#" wire:click.prevent="sortBy('note')" style="cursor: pointer;">Nota</span>
                    @if ($sortField == 'note')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">Files</th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('rubrica')" style="cursor: pointer;">Rubrica</span>
                    @if ($sortField == 'rubrica')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('lexp')" style="cursor: pointer;">Municipio</span>
                    @if ($sortField == 'lexp')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">Grp5
                    <span href="#" wire:click.prevent="sortBy('group5')" style="cursor: pointer;">Grp5</span>
                    @if ($sortField == 'group5')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('material')"
                        style="cursor: pointer;">Material</span>
                    @if ($sortField == 'material')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('category')"
                        style="cursor: pointer;">Categoria</span>
                    @if ($sortField == 'category')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('created_at')" style="cursor: pointer;">Data
                        Envio</span>
                    @if ($sortField == 'created_at')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">Em Atividade</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-center">Responsável</th>
                <th scope="col" class="text-center">Empresa</th>
                <th scope="col" class="text-center"></th>
            </thead>
            <tbody class="table-group-divider">
                @if ($lists)
                    @foreach ($lists as $list)
                        @php
                            $vencido = false;
                            $vencimento = Carbon::now()->subHours(24)->toDateTimeString();
                            if ($list->updated_at < $vencimento) {
                                $vencido = true;
                            }

                            $color = '';

                            if ($list->Approvals->isNotEmpty()) {
                                $color = 'text-bg-warning';
                            }

                            if ($list->Waiting) {
                                $color = '';
                            }

                            if ($list->Viabilities->isNotEmpty()) {
                                $color = 'text-bg-info';
                            }

                            if ($list->Externals->isNotEmpty()) {
                                $color = 'text-bg-primary';
                            }

                        @endphp

                        <tr wire:key="row-{{ $list->id }}">
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-checkbox" wire:model.defer="selected"
                                    value="{{ $list->id }}">
                            </td>
                            <td class="{{ $color }} text-center align-middle fw-bold" data-bs-container="body"
                                data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="left"
                                title="Legenda das Cores"
                                data-bs-content="<ul class='list-unstyled mb-0'>
                                    <li>
                                        <span class='fs-4 me-2'>■</span>
                                         Contratação
                                    </li>
                                    <li>
                                          <span class='fs-4 me-2 text-warning'>■</span>
                                         Analise de Projeto
                                    </li>
                                    <li>
                                          <span class='fs-4 me-2 text-info'>■</span>
                                         Viabilidade
                                    </li>
                                    <li>
                                          <span class='fs-4 me-2 text-primary'>■</span>
                                         Orgão Externo
                                    </li>
                                </ul>">
                                {{ $list->Note->note }}
                            </td>
                            <td class="text-center align-middle">
                                {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                <x-files.select-download-list :files='$list->Note->Files' />

                            </td>
                            <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                            <td class="text-center align-middle">{{ $list->Note->lexp }}</td>
                            <td class="text-center align-middle">{{ $list->Note->group5 }}</td>
                            <td class="text-center align-middle">{{ $list->Note->material }}</td>
                            <td class="text-center align-middle" style="cursor: pointer; color: inherit;"
                                wire:dblclick="$emitTo('dispatchs.common.reclaim-info', 'getInfoResponse', '{{ $list->id }}')"
                                onmouseover="this.style.color='blue';" onmouseout="this.style.color='inherit';"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Duplo clique para detalhes">
                                {{ $list->category }}
                            </td>
                            <td class="text-center align-middle">
                                {{ Carbon::parse($list->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td
                                class="text-center align-middle
                                @if ($vencido) text-bg-danger @endif
                                ">
                                {{ Carbon::parse($list->created_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($list->Production)
                                    <span class="badge {{ Notestatus::status($list->Production->status)->colorbg }}">
                                        {{ Notestatus::status($list->Production->status)->status }}</span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Aguardando Atribuição</span>
                                @endif

                            </td>
                            <td class="text-center align-middle">
                                {{ $list->Production ? ($list->Production->User ? $list->Production->User->name : 'Desconhecido') : '' }}
                            </td>
                            <td class="text-center align-middle">
                                {{ $list->Production ? ($list->Production->Company ? $list->Production->Company->name : 'Desconhecido') : '' }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($list->Production)
                                    <i class="ri-arrow-left-right-fill text-danger fs-5"
                                        wire:click.prevent="$emitTo('dispatchs.users.richange-user','goChangeUser' , {{ $list->id }})"
                                        style='cursor: pointer;'></i>
                                @else
                                    <i class="ri-user-add-line text-primary fs-5"
                                        wire:click.prevent="$emitTo('dispatchs.users.riatt-user','goAttUser' , {{ $list->id }})"
                                        style='cursor: pointer;'></i>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                @endif

            </tbody>
        </table>
    </div>
    <div class="row mx-3">
        <div class="col-6">
            {{ $lists->links() }}
        </div>
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.</span>
        </div>
    </div>
</div>


{{-- Livewires Components Functions --}}
@livewire('dispatchs.users.richange-user', key('change-users-intern-return'))
@livewire('dispatchs.users.riatt-user', ['service' => $service], key('att-users-intern-return'))
@livewire('dispatchs.common.reclaim-info', key('reclaim-info-intern-return'))
@livewire('dispatchs.common.return-in-mass', ['service' => $service], key('return-in-mass-table'))

<!-- Exibir os dados do clipboard com formatação para Excel -->
<textarea id="clipboard-data" style="display: none;">
            @if (count($clipboardData))
@foreach ($clipboardData as $row)
{{ implode("\t", $row) }}
@endforeach
@else
SEM DADOS
@endif
        </textarea>
</div>
