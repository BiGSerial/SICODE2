<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <form>

        <div class="mb-3">
            <label for="email" class="form-label">Empresa</label>
            <select wire:model.defer="company_s" class="form-select" aria-label="Default select example">
                <option selected>Selecione uma Empresa</option>
                @if ($companies_l->count())
                    @foreach ($companies_l as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Numero Contrato</label>
            <input wire:model.defer="number" type="text" class="form-control" name="number" id="number">
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Validade</label>
            <input wire:model.defer="date_end" type="date" class="form-control" name="date" id="date">
        </div>
        <div class="card">
            <n5 class="card-header edp-bg-sprucegreen-20 text-white">TIPO CONTRATO</n5>
        </div>
        <div class="form-check form-check-inline">
            <input wire:model.defer="service" class="form-check-input" type="checkbox" id="service">
            <label class="form-check-label" for="service">Serviços</label>
        </div>
        <div class="form-check form-check-inline mb-3">
            <input wire:model.defer="construction" class="form-check-input" type="checkbox" id="construction">
            <label class="form-check-label" for="construction">Construção</label>
        </div>
    </form>


</div>
