@php
    use App\Models\Service;
@endphp
<div>
    <x-show-loading />
    <!-- Modal para Criar/Editar Usuário -->

    <div wire:ignore.self class="modal fade" id="userMassEditModal" tabindex="-1" aria-labelledby="userModalLabel"
        aria-hidden="true">
        @if ($this->users)
            <div class="modal-dialog modal-lg">
                <div class="modal-content edp-bg-gray">
                    <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                        <h5 class="modal-title" id="userModalLabel">EDITAR USUARIOS EM MASSA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">


                        <!-- Seção 1: Dados do Usuário -->
                        <div class="mb-3">
                            <h6 class="text-primary">Dados Gerais</h6>
                            <h5>Total Usuarios Afetados: {{ $users->count() }} <span class="text-danger">(Usuarios
                                    Removidos são Ignorados)</span></h5>
                            <div class="row mt-3">

                                <div class="col-md-6">
                                    <label for="empresa" class="form-label">Empresa</label>
                                    <select class="form-select" id="empresa" wire:model="company" required>
                                        <option value="" selected>Selecione a Empresa</option>
                                        @if ($companyList)
                                            @foreach ($companyList as $cList)
                                                <option value="{{ $cList->id }}">{{ $cList->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="contrato" class="form-label">Contrato</label>
                                    <select class="form-select" id="contrato" wire:model="contract" required>
                                        <option selected>Selecione o Contrato</option>
                                        @if ($contractList)
                                            @foreach ($contractList as $cList)
                                                <option value="{{ $cList->id }}">{{ $cList->number }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="contratado"
                                            wire:model.defer="permissions.contract">
                                        <label class="form-check-label" for="contratado">
                                            Contratado (Terceirizado)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção 2: Permissões de Usuário -->
                        <div class="mb-3">


                            <h6 class="text-primary">Permissões de Usuário <span class="text-danger align-middle"><input
                                        class="form-check-input align-middle" type="checkbox" id="contratado"
                                        wire:model.defer="changePermission"> Permitir
                                    Mudanças</span>
                            </h6>



                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="superAdmin"
                                            wire:model.defer="permissions.superadm">
                                        <label class="form-check-label" for="superAdmin">Super Admin</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="admin"
                                            wire:model.defer="permissions.admin">
                                        <label class="form-check-label" for="admin">Admin</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="gerente"
                                            wire:model.defer="permissions.management">
                                        <label class="form-check-label" for="gerente">Gerente</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="engenheiro"
                                            wire:model="permissions.engineer">
                                        <label class="form-check-label" for="engenheiro">Engenheiro</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="responsavel"
                                            wire:model.defer="permissions.responsible">>
                                        <label class="form-check-label" for="responsavel">Responsável</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="operador"
                                            wire:model.defer="permissions.operator">
                                        <label class="form-check-label" for="operador">Operador</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="user"
                                            wire:model.defer="permissions.user">
                                        <label class="form-check-label" for="user">Usuario</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="empreiteira"
                                            wire:model.defer="permissions.onlyparner">
                                        <label class="form-check-label" for="empreiteira">Empreiteira (Visão
                                            Exclusiva)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção 3: Adicionar Serviços -->
                        <div class="mb-3">
                            <h6 class="text-primary">Adicionar Atividade <span class="text-danger">(Todos usuários
                                    terão as mesmas permissões)</span></h6>
                            <div class="row">
                                <div class="col-md-10">
                                    <select class="form-select" id="servicosDisponiveis"
                                        wire:model.defer="serviceSelect">
                                        <option value="" selected>Selecione Atividade</option>
                                        @if ($this->serviceList)
                                            @foreach ($this->serviceList as $sList)
                                                <option value="{{ $sList->uuid }}">{{ $sList->service }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-center">
                                    <button type="button" class="btn btn-success" id="addServico"
                                        wire:click="addService"><i class="ri-add-line"></i> Adicionar</button>
                                </div>
                            </div>
                            <div class="mt-3 col-sm-6">

                                <div class="card edp-bg-gray">
                                    <div class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                                        Atividades Liberadas
                                    </div>
                                    <table class="table table-sm table-condensed table-striped">
                                        <tbody>
                                            @if (count($this->temporaryServices))
                                                @foreach ($this->temporaryServices as $index => $tempService)
                                                    @php
                                                        $service = Service::where(
                                                            'uuid',
                                                            $tempService['service_id'],
                                                        )->first();
                                                    @endphp
                                                    @if ($service)
                                                        <tr>
                                                            <td>{{ $service->service }}</td>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input border border-1 border-secondary"
                                                                        type="checkbox" id="service"
                                                                        wire:model="temporaryServices.{{ $index }}.service">
                                                                    <label class="form-check-label"
                                                                        for="engenheiro">Serviço</label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input border border-1 border-secondary"
                                                                        type="checkbox" id="dispatch"
                                                                        wire:model.defer="temporaryServices.{{ $index }}.dispatch">
                                                                    <label class="form-check-label"
                                                                        for="engenheiro">Despacho</label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <i class="ri-delete-bin-line fs-5 text-danger cursor-pointer"
                                                                    wire:click="removeService({{ $index }})"
                                                                    title="Excluir" style="cursor: pointer;"></i>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @else
                                                <div class="card-body py-2">
                                                    <h5 class="text-center my-0 py-0">SEM ATIVIDADE LIBERADA</h5>
                                                </div>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>

                    </div>
                    <div class="modal-footer edp-bg-sprucegreen-100">
                        <button type="button" class="btn btn-warning" wire:click.prevent="toResetMassPassword"><i
                                class="ri-lock-password-line align-middle"></i> Resetar Senha</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveUser"
                            wire:click.prevent='toSave'>Salvar</button>
                    </div>
                </div>
            </div>
        @endif
    </div>


</div>

<script>
    // Capturando o evento de fechamento do modal
    document.getElementById('userModal').addEventListener('hidden.bs.modal', () => {

        Livewire.emitTo('admin.user.actions.usuario', 'closeAll');
    });
</script>
