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
                <input type="search" class="form-control" id="search" wire:model.debounce.300ms="search"
                    placeholder="Pesquisar">
                <label for="search">Pesquisar</label>
            </div>
        </div>

        <!-- Type Select -->
        <div class="col-auto">
            <div class="form-floating">
                <select class="form-select" id="searchType" wire:model="typeNote">
                    <option value="note">Note</option>
                    <option value="ov">OV</option>
                    <option value="both">Ambos</option>
                </select>
                <label for="searchType">Tipo de busca</label>
            </div>
        </div>

        <!-- Right-aligned Dropdown -->
        <div class="col d-flex justify-content-end gap-2">
            {{-- Tipos de Entidade → Entidades --}}
            @livewire(
                'components.filter.filter2',
                [
                    'myKey' => 'entityTypes',
                    'sendFilter' => 'entities',
                    'modelClass' => \App\Models\EntityType::class,
                    'column' => 'id',
                    'filterLabel' => 'Tipos de Entidade',
                    'groupFilter' => 'oexterno',
                    'displayColumn' => 'name',
                    'direction' => 'ASC',
                    'customQuery' => null,
                    'searchColumn' => 'name',
                    'sendSearchColumn' => 'entity_type_id',
                    'customBuilderMethod' => null,
                ],
                key('entityTypes')
            )

            {{-- Entidades --}}
            @livewire(
                'components.filter.filter2',
                [
                    'myKey' => 'entities',
                    'sendFilter' => null,
                    'modelClass' => \App\Models\Entity::class,
                    'column' => 'id',
                    'filterLabel' => 'Entidades',
                    'groupFilter' => 'oexterno',
                    'displayColumn' => 'name',
                    'direction' => 'ASC',
                    'customQuery' => null,
                    'searchColumn' => 'name',
                    'sendSearchColumn' => 'entity_id',
                    'customBuilderMethod' => null,
                ],
                key('entities')
            )

            {{-- Rubrica --}}
            @livewire(
                'components.filter.filter2',
                [
                    'myKey' => 'rubrica',
                    'sendFilter' => null,
                    'modelClass' => \App\Models\Note::class,
                    'column' => 'rubrica',
                    'filterLabel' => 'Rúbrica',
                    'groupFilter' => 'oexterno',
                    'displayColumn' => 'rubrica',
                    'direction' => 'ASC',
                    'customQuery' => null,
                    'searchColumn' => 'rubrica',
                    'sendSearchColumn' => 'rubrica',
                    'customBuilderMethod' => null,
                ],
                key('rubrica')
            )

            {{-- Região → Município --}}
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
                    'customQuery' => null,
                    'searchColumn' => 'regiao',
                    'sendSearchColumn' => 'regiao',
                    'customBuilderMethod' => null,
                ],
                key('region')
            )

            {{-- Município --}}
            @livewire(
                'components.filter.filter2',
                [
                    'myKey' => 'city',
                    'sendFilter' => null,
                    'modelClass' => \App\Models\Edp_depc\City::class,
                    'column' => 'cidade',
                    'filterLabel' => 'Município',
                    'groupFilter' => 'oexterno',
                    'displayColumn' => 'municipio',
                    'direction' => 'ASC',
                    'customQuery' => null,
                    'searchColumn' => 'municipio',
                    'sendSearchColumn' => 'cidade',
                    'customBuilderMethod' => null,
                ],
                key('city')
            )

            @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
        </div>
    </div>
    @if (!$lists->isEmpty())
        <div class="d-flex justify-content-between align-items-center me-3 mt-3">
            <div>
                <span class="text-muted">
                    Exibindo {{ $lists->firstItem() ?? 0 }} a {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
                    itens
                </span>
            </div>
            <div>
                {{ $lists->links() }}
            </div>
        </div>
    @endif
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
                        <th scope="col" class="text-center">Rubrica</th>
                        <th scope="col" class="text-center">Service</th>
                        <th scope="col" class="text-center">Entidade</th>
                        <th scope="col" class="text-center">Data</th>
                        <th scope="col" class="text-center">Solicitante</th>
                        <th scope="col" class="text-center">Categoria</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col" class="text-center">Responsável</th>
                        <th scope="col" class="text-center">Tempo em Execução</th>
                        <th scope="col" class="text-center">Tempo Total</th>
                        <th scope="col" class="text-center"></th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($lists as $index => $list)
                        <tr wire:key="return-{{ $list->id }}" wire:dblClick='navigateTo({{ $list->note->note }})'>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center fw-bold">
                                {{ $list->note->note }}
                            </td>
                            <td class="text-center">
                                {{ $list->note->rubrica }}
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
                            <td class="text-center">
                                @if ($list->production?->user?->email)
                                    <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                        style="cursor:pointer"
                                        onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $list->production?->user?->email }}', '_blank')">
                                    </i>
                                @endif {{ $list->production?->user?->name }}
                            </td>
                            <td
                                class="text-center {{ $this->getColor($list->production?->att_at?->startOfDay()->diffInDays()) }}">
                                {{ $list->created_at->startOfDay()->diffInDays() }} dias
                            </td>
                            <td
                                class="text-center {{ $this->getColor($list->production?->att_at?->startOfDay()->diffInDays()) }}">
                                {{ $list->created_at?->startOfDay()->diffInDays() }} dias
                            </td>
                            <td class="text-center">
                                @if ($list->completed)
                                    <button class="btn btn-sm btn-success"
                                        wire:click.prevent="$emitTo('services.oexterno.actions.confirm-work-return', 'openConfirmWorkReturn', {{ $list->id }})"
                                        wire:target="confirmReturn" data-bs-toggle="tooltip" data-bs-placement="left"
                                        title="Aprovar Retorno do Trabalho">
                                        <i class="ri-check-line"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="d-flex justify-content-between align-items-center me-3 mt-3">
        <div>
            <span class="text-muted">
                Exibindo {{ $lists->firstItem() ?? 0 }} a {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
                itens
            </span>
        </div>
        <div>
            {{ $lists->links() }}
        </div>
    </div>
</div>

{{-- Modal de Aprovação de Reclamação --}}
@livewire('services.oexterno.actions.confirm-work-return', key('confirmWorkReturn'))
</div>
