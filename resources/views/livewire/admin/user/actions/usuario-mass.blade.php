@php
    use App\Models\Service;
@endphp

<div>
    <x-show-loading />

    <div wire:ignore.self wire:key="modal_mass_user" class="modal fade" id="userMassEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background: linear-gradient(130deg, #1f2937 0%, #0f766e 80%);">
                    <h5 class="modal-title">Editar usuarios em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body bg-light">
                    @if ($this->users)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <strong>Total de usuarios afetados:</strong> {{ $users->count() }}
                                </div>
                                <span class="badge text-bg-warning">Usuarios removidos serao ignorados no salvamento</span>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white"><strong>Dados gerais</strong></div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Empresa</label>
                                        <select class="form-select" wire:model="company">
                                            <option value="">Selecione a empresa</option>
                                            @if ($companyList)
                                                @foreach ($companyList as $cList)
                                                    <option value="{{ $cList->id }}">{{ $cList->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Contrato</label>
                                        <select class="form-select" wire:model="contract">
                                            <option value="">Selecione o contrato</option>
                                            @if ($contractList)
                                                @foreach ($contractList as $cList)
                                                    <option value="{{ $cList->id }}">{{ $cList->number }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <strong>Permissoes</strong>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="changePermission" wire:model.defer="changePermission">
                                    <label class="form-check-label" for="changePermission">Aplicar alteracoes de permissao</label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massSuperadm" wire:model.defer="permissions.superadm"><label class="form-check-label" for="massSuperadm">Super Admin</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massAdmin" wire:model.defer="permissions.admin"><label class="form-check-label" for="massAdmin">Admin</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massManagement" wire:model.defer="permissions.management"><label class="form-check-label" for="massManagement">Gerente</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massEngineer" wire:model.defer="permissions.engineer"><label class="form-check-label" for="massEngineer">Engenheiro</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massResponsible" wire:model.defer="permissions.responsible"><label class="form-check-label" for="massResponsible">Responsável</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massOperator" wire:model.defer="permissions.operator"><label class="form-check-label" for="massOperator">Operador</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massUser" wire:model.defer="permissions.user"><label class="form-check-label" for="massUser">Usuario</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massBtzero" wire:model.defer="permissions.btzero"><label class="form-check-label" for="massBtzero">BTzero</label></div></div>
                                    <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massOnlypartner" wire:model.defer="permissions.onlyparner"><label class="form-check-label" for="massOnlypartner">Empreiteira (visão exclusiva)</label></div></div>
                                    @if (Auth()->user()->superadm)
                                        <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="massAnalyst" wire:model.defer="permissions.analyst"><label class="form-check-label" for="massAnalyst">Analista</label></div></div>
                                        <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="contractMass" wire:model.defer="permissions.contract"><label class="form-check-label" for="contractMass">Terceirizado</label></div></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white"><strong>Atividades para aplicar a todos</strong></div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-9">
                                        <select class="form-select" wire:model.defer="serviceSelect">
                                            <option value="">Selecione atividade</option>
                                            @if ($this->serviceList)
                                                @foreach ($this->serviceList as $sList)
                                                    <option value="{{ $sList->uuid }}">{{ $sList->service }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button type="button" class="btn btn-success" wire:click="addService"><i class="ri-add-line"></i> Adicionar</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <tbody>
                                            @if (count($this->temporaryServices))
                                                @foreach ($this->temporaryServices as $index => $tempService)
                                                    @php
                                                        $service = Service::where('uuid', $tempService['service_id'])->first();
                                                    @endphp
                                                    @if ($service)
                                                        <tr wire:key="service_list_{{ $index }}">
                                                            <td>{{ $service->service }}</td>
                                                            <td><div class="form-check"><input class="form-check-input" type="checkbox" wire:model="temporaryServices.{{ $index }}.service"> <label class="form-check-label">Serviço</label></div></td>
                                                            <td><div class="form-check"><input class="form-check-input" type="checkbox" wire:model.defer="temporaryServices.{{ $index }}.dispatch"> <label class="form-check-label">Despacho</label></div></td>
                                                            <td class="text-end"><i class="ri-delete-bin-line text-danger" wire:click="removeService({{ $index }})" style="cursor: pointer;"></i></td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Sem atividade liberada</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-warning" wire:click.prevent="toResetMassPassword"><i class="ri-lock-password-line align-middle"></i> Resetar senha</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="toSave">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('userMassEditModal');
            if (!modalEl || modalEl.dataset.closeBound === '1') {
                return;
            }

            modalEl.dataset.closeBound = '1';
            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.user.actions.usuario-mass', 'closeAll');
            });
        });
    </script>
</div>
