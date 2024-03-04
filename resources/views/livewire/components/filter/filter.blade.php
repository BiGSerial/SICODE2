<div>
    <x-show-loading />

    <div class="mx-1 position-relative" x-data="{ isShow: false }">
        <button class="btn btn-secondary dropdown-toggle" type="button" aria-expanded="false" @click="isShow = true">
            {{ $this->filter }}
            @if ($isRefreshing)
                <div class="spinner-border" role="status" style="height: 14px; width: 14px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            @endif
            @if (count($items))
                <span class="badge text-bg-light">{{ count($items) }}</span>
            @endif
        </button>

        <div x-show="isShow" @click.away="isShow = false" style="display: none;"
            class="card position-absolute top-50 end-0 mt-4 z-3">

            <div class="card-body">
                <input type="text" wire:model="search" class="form-control border-1 border-secondary mb-3"
                    placeholder="Buscar...">

                <div style="max-height: 350px; overflow-y: auto; scrollbar-width: thin;">

                    @if (isset($filterLists) && $filterLists->count() > 0)
                        @foreach ($filterLists->unique($column) as $item)
                            @if ($item->{$values})
                                <div class="dropdown-item">
                                    <input type="checkbox" class="form-check-input border border-primary"
                                        wire:model.defer="items" value="{{ $item->$column }}">
                                    @php
                                        $valor = $item->$column;
                                    @endphp
                                    <label for="opcao1">{{ $item->$values }}</label>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>


                <div class="dropdown-item mt-3">
                    <button wire:click="applyFilter" class="btn btn-primary" @click="isShow = false"
                        data-bs-toggle="dropdown">Aplicar
                        Filtro</button>
                    <button wire:click="removeFilter" class="btn btn-danger" @click="isShow = false"
                        data-bs-toggle="dropdown">Limpar</button>
                </div>
            </div>

        </div>
    </div>

</div>
