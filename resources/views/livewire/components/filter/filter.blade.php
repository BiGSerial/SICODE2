<div>
    <div class="dropdown mx-1">
        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ $this->filter }}
            @if (count($items))
                <span class="badge text-bg-light">{{ count($items) }}</span>
            @endif
        </button>

        <div wire:ignore.self class="dropdown-menu">

            <!-- Barra de busca -->
            <input type="text" wire:model="search" class="form-control border-1 border-secondary"
                placeholder="Buscar...">

            <div style="max-height: 350px; overflow-y: auto;">
                <!-- Itens filtrados -->
                @if (isset($filterLists) && $filterLists->count() > 0)
                    @foreach ($filterLists as $item)
                        @if ($item->{$valores})
                            <div class="dropdown-item">
                                <input type="checkbox" wire:model.defer="items" value="{{ $item->{$valores} }}">
                                <label for="opcao1">{{ $item->{$valores} }}</label>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            <!-- Botões fixos -->
            <div class="dropdown-item">
                <button wire:click="applyFilter" class="btn btn-primary">Aplicar Filtro</button>
                <button wire:click="removeFilter" class="btn btn-danger">Limpar</button>
            </div>

        </div>
    </div>

</div>
