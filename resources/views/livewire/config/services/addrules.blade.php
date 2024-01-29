<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($showAddRules)
        <form>

            <div class="row g-2">
                <h4 class="fw-bold">{{ $service->service }}</h4>
                <div class="mb-3">
                    <label for="email" class="form-label">Empresa</label>
                    <select class="form-select form-select-sm" aria-label="" wire:model='company_s'>
                        @if ($companies->count())
                            <option selected>Selecione uma Empresa</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        @else
                            <option selected disabled>Nenhuma empresa com contrato</option>
                        @endif
                    </select>

                </div>

                @if ($company_s)
                    <div class="mb-3">
                        <label for="email" class="form-label">Contrato</label>
                        <select class="form-select form-select-sm" wire:model="contract_s">
                            @if ($contracts->count())
                                <option selected>Selecione uma Empresa</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}">{{ $contract->number }}
                                        ({{ date('m/y', strToTime($contract->date_end)) }})
                                    </option>
                                @endforeach
                            @else
                                <option selected disabled>Nenhuma empresa com contrato</option>
                            @endif
                        </select>
                    </div>
                @endif

                @if ($contract_s)
                    <div class="card">
                        <h5 class="card-header">
                            Regras
                        </h5>
                    </div>
                    <div class="row">
                        <div class="form-check col-3">
                            <input class="form-check-input" type="checkbox" wire:model.defer="posts"
                                id="flexCheckChecked">
                            <label class="form-check-label" for="flexCheckChecked">
                                Por Poste?
                            </label>
                        </div>
                        <div class="form-check col-3">
                            <input class="form-check-input" type="checkbox" wire:model.defer="dispatch"
                                id="flexCheckChecked">
                            <label class="form-check-label" for="flexCheckChecked">
                                Despacho?
                            </label>
                        </div>
                        <div class="mb-3 col-3">
                            <label for="exampleFormControlInput1" class="form-label">Quantidade</label>
                            <input type="number" class="form-control" wire:model.defer="qtd">
                        </div>
                        <div class="mb-3 col-3">
                            <label for="exampleFormControlInput1" class="form-label">Dias</label>
                            <input type="number" class="form-control" wire:model.defer="days">
                        </div>
                    </div>
                @endif
            </div>

        </form>
    @endif
</div>
