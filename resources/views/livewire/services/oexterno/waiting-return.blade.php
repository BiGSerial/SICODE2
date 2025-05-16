<div>
    @php
        use App\Custom\Notestatus;
    @endphp

    <x-show-loading />

    <div class="row g-3 mb-4 align-items-center">
        <!-- Per Page Select -->
        <div class="col-auto">
            <div class="form-floating">
                <select class="form-select" id="perPage" wire:model="perPage">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <label for="perPage">Itens por página</label>
            </div>
        </div>

        <!-- Search Input -->
        <div class="col">
            <div class="form-floating">
                <input type="search" class="form-control" id="searchTerm" wire:model.debounce.300ms="searchTerm"
                    placeholder="Pesquisar">
                <label for="searchTerm">Pesquisar</label>
            </div>
        </div>

        <!-- Type Select -->
        <div class="col-auto">
            <div class="form-floating">
                <select class="form-select" id="searchType" wire:model="searchType">
                    <option value="note">Note</option>
                    <option value="ov">OV</option>
                    <option value="both">Ambos</option>
                </select>
                <label for="searchType">Tipo de busca</label>
            </div>
        </div>

        <!-- Right-aligned Dropdown -->
        <div class="col text-end d-flex justify-content-end gap-2">
            @livewire('components.filter.filter', ['myKey' => 'entity', 'sendFilter' => '', 'model' => 'App\Models\Entity', 'column' => 'id', 'filter' => 'Entidade', 'group_filter' => 'oexterno', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('entities'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'oexterno', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'oexterno', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'oexterno', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
        </div>
    </div>

    <div class="card">
        <div
            class="card-header edp-bg-sprucegreen-70 edp-text-verde-dark d-flex justify-content-between align-items-center">
            <h4 class="my-1 py-0">LISTA EM RETONO INTERNO</h4>
            {{-- <button class="btn btn-sm btn-primary" wire:click.prevent="massAssign" wire:target="massAssign"
                data-bs-toggle="tooltip" data-bs-placement="left" title="Atribuição em Massa">
                <i class="ri-user-shared-line me-1"></i> Atribuir em Massa
            </button> --}}
        </div>
        @if ($lists->isEmpty())
        @else
            <table class="table table-sm table-striped table-hover table-condensed">
                <thead>
                    <tr class="sticky-top table-dark" style="z-index:1;">
                        <th scope="col" class="text-center">#</th>
                        <th scope="col" class="text-center">Note</th>
                        <th scope="col" class="text-center">Service</th>
                        <th scope="col" class="text-center">Entidade</th>
                        <th scope="col" class="text-center">Data</th>
                        <th scope="col" class="text-center">Solicitante</th>
                        <th scope="col" class="text-center">Categoria</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col" class="text-center">Responsável</th>
                        <th scope="col" class="text-center">Tempo em Execução</th>
                        <th scope="col" class="text-center">Tempo Total</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($lists as $index => $list)
                        <tr wire:key="return-{{ $list->id }}">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center">
                                {{ $list->note->note }}
                            </td>
                            <td class="text-center">
                                {{ $list->service->service }}
                            </td>
                            <td class="text-center">
                                {{ $list->externals?->first()?->entity?->nick }}
                            </td>
                            <td class="text-center">{{ $list->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="text-center">{{ $list->comments?->first()->user->name }}</td>
                            <td class="text-center">{{ $list->subcategory?->category->name }}</td>
                            <td class="text-center"><span
                                    class="badge {{ $list->production ? Notestatus::status($list->production->status)->colorbg : 'text-bg-secondary' }}">{{ $list->production ? Notestatus::status($list->production->status)->status : 'AGUARDANDO DESPACHO' }}</span>
                            </td>
                            <td class="text-center">{{ $list->production?->user?->name }}</td>
                            <td
                                class="text-center {{ $this->getColor($list->production?->att_at?->startOfDay()->diffInDays()) }}">
                                {{ $list->created_at->startOfDay()->diffInDays() }} dias
                            </td>
                            <td
                                class="text-center {{ $this->getColor($list->production?->att_at?->startOfDay()->diffInDays()) }}">
                                {{ $list->created_at?->startOfDay()->diffInDays() }} dias</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="d-flex justify-content-end me-3">
                {{ $lists->links() }}
                <span class="text-muted">
                    Exibindo {{ $lists->firstItem() ?? 0 }} a {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }} itens
                </span>
            </div>
</div>


</div>
