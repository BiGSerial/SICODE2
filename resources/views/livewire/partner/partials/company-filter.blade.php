<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center g-2">
            <div class="col-auto">
                <label class="form-label mb-0 fw-semibold" for="partnerCompanyFilter">Empresa</label>
            </div>
            <div class="col-12 col-md-5 col-lg-4">
                <select id="partnerCompanyFilter" class="form-select" wire:model="partnerCompanyFilter">
                    <option value="">Todas as empresas permitidas</option>
                    @foreach ($this->partnerCompanyFilterOptions() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
