<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />
    <div class="card">

        <div class="card-body">
            <div class="row">
                @if (!Auth()->User()->contract)
                    <div class="mb-3 col-2">
                        <label for="exampleFormControlInput1" class="form-label">Empresa:</label>
                        <select class="form-select form-select-sm" aria-label="Small select example"
                            wire:model="company_s">
                            <option value="" selected>Todos</option>
                            @if ($company_l->count())
                                @foreach ($company_l as $list)
                                    <option value="{{ $list->Company->id }}">{{ $list->Company->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                @endif
                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Usuario:</label>
                    <select class="form-select form-select-sm" aria-label="Small select example"
                        wire:model.defer="user_s">
                        <option value="" selected>Todos</option>
                        @if ($user_l->count())
                            @foreach ($user_l as $list)
                                @if ($list->User)
                                    <option value="{{ $list->User->id }}">{{ $list->User->name }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Serviço:</label>
                    <select class="form-select form-select-sm" aria-label="Small select example"
                        wire:model.defer="service_s">
                        <option value="" selected>Todos</option>
                        @if ($service_l->count())
                            @foreach ($service_l as $list)
                                <option value="{{ $list->service_id }}">{{ $list->Service->service }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="mb-3 col-1">
                    <label for=""></label>
                    <button class="btn btn-sm btn-primary form-control mt-2" wire:click.prevent="Search">Buscar</button>
                </div>
            </div>
        </div>
    </div>
</div>
