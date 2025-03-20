<div>
    <x-show-loading />
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <h4 class="my-1">DASHBOARD ANALISE DE PROJETO</h4">
        </div>
        <div class="card-body">
            <form class="form-inline">
                <div class="row">
                    {{-- <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="contractor" class="mr-2">Empreiteira</label>
                        <select id="contractor" class="form-select w-100" wire:model="company_id">
                            <option value="">Selecione uma empreiteira</option>
                            @if ($companies)
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div> --}}
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="month" class="mr-2">Mês Referência</label>
                        <input type="month" id="month" class="form-control w-100" wire:model="month"
                            max="{{ now()->format('Y-m') }}" value="{{ now()->format('Y-m') }}">
                    </div>
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="start_date" class="mr-2">Data de Início</label>
                        <input type="date" id="start_date" class="form-control w-100" wire:model="dt_ini">
                    </div>
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="end_date" class="mr-2">Data de Fim</label>
                        <input type="date" id="end_date" class="form-control w-100" wire:model="dt_fim">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
