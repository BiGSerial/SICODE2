<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <form>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input wire:model.defer="email" type="email" class="form-control" name="email" id="email"
                placeholder="name@example.com">
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Matrícula</label>
            <input wire:model.defer="registration" type="text" class="form-control" name="registration"
                id="registration">
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Nome</label>
            <input wire:model.defer="name" type="text" class="form-control" name="name" id="name">
        </div>

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
            @can('superadm')
                <div class="form-check form-check-inline">
                    <input wire:model.defer="contract" class="form-check-input" type="checkbox" id="conract">
                    <label class="form-check-label" for="conract">Contratado</label>
                </div>
            @endcan

            <div class="mb-3">
                <label for="email" class="form-label">Serviço Principal</label>
                <select class="form-select form-select-sm" wire:model="service_s">
                    @if ($services->count())
                        <option selected>Selecione um Serviço</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                        @endforeach
                    @else
                        <option selected disabled>Nenhuma empresa com contrato</option>
                    @endif
                </select>
            </div>

            <div class="card">
                <n5 class="card-header edp-bg-sprucegreen-20 text-white">PERMISSÕES</n5>
            </div>
            @can('superadm')
                <div class="form-check form-check-inline">
                    <input wire:model.defer="superadm" class="form-check-input" type="checkbox" id="superadmin">
                    <label class="form-check-label" for="superadmin">Super Admin</label>
                </div>
            @endcan
            <div class="form-check form-check-inline mb-3">
                <input wire:model.defer="admin" class="form-check-input" type="checkbox" id="admin">
                <label class="form-check-label" for="admin">Admin</label>
            </div>
            <div class="form-check form-check-inline">
                <input wire:model.defer="management" class="form-check-input" type="checkbox" id="management">
                <label class="form-check-label" for="management">Gerente</label>
            </div>
            <div class="form-check form-check-inline">
                <input wire:model.defer="engineer" class="form-check-input" type="checkbox" id="engineer">
                <label class="form-check-label" for="management">Engenheiro</label>
            </div>
            <div class="form-check form-check-inline">
                <input wire:model.defer="operator" class="form-check-input" type="checkbox" id="operator">
                <label class="form-check-label" for="operator">Operador</label>
            </div>
            <div class="form-check form-check-inline">
                <input wire:model.defer="user" class="form-check-input" type="checkbox" id="user">
                <label class="form-check-label" for="user">Usuário</label>
            </div>

            @can('superadm')
                <div class="card">
                    <n5 class="card-header edp-bg-sprucegreen-20 text-white">ESPECIAL</n5>
                </div>

                <div class="form-check form-check-inline">
                    <input wire:model.defer="bypasspro" class="form-check-input" type="checkbox" id="bypassprod">
                    <label class="form-check-label" for="superadmin">Auto Aprovar Produção</label>
                </div>

                <div class="form-check form-check-inline">
                    <input wire:model.defer="onlyparner" class="form-check-input" type="checkbox" id="user">
                    <label class="form-check-label" for="user">Empreitera</label>
                </div>

                <div class="form-check form-check-inline">
                    <input wire:model.defer="can_dispatch" class="form-check-input" type="checkbox" id="canDispatch">
                    <label class="form-check-label" for="canDispatch">Pode Despachar</label>
                </div>
            @endcan

        @endif

        <a href="#" class="table-link my-4"
            wire:click.prevent="$emit('toResetPass', '{{ $user_update->id }}')">
            <span class="fa-stack">
                <i class="ri-lock-password-line btn btn-info btn-sm"></i>
            </span>
        </a>

    </form>




</div>
