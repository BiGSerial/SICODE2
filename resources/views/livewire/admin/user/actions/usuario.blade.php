@php
    use App\Models\Service;
@endphp
<div>
    <x-show-loading />
    <!-- Modal para Criar/Editar Usuário -->

    <div wire:ignore.self class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h5 class="modal-title" id="userModalLabel">Criar/Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($this->user)
                        <div class="mb-3">
                            <h6 class="text-primary">Dados do Usuário</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email"
                                        wire:model.defer="user.email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="matricula" class="form-label">Matrícula</label>
                                    <input type="text" class="form-control" id="matricula"
                                        wire:model.defer="user.Registration">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome"
                                        wire:model.defer="user.name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="empresa" class="form-label">Empresa</label>
                                    <select class="form-select" id="empresa" wire:model="user.company_id" required>
                                        <option value="" selected>Selecione a Empresa</option>
                                        @if ($companyList)
                                            @foreach ($companyList as $cList)
                                                <option wire:key='listCompany_{{ $cList->id }}'
                                                    value="{{ $cList->id }}">{{ $cList->name }}</option>
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
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="contratado"
                                            wire:model.defer="user.contract">
                                        <label class="form-check-label" for="contratado">
                                            Contratado (Terceirizado)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção 2: Permissões de Usuário -->
                        <div class="mb-3">
                            <h6 class="text-primary">Permissões de Usuário</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="hidden" wire:model="user.superadm" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="superAdmin"
                                            wire:model="user.superadm" value="1" @disabled(!Auth()->User()->superadm)>
                                        <label class="form-check-label" for="superAdmin">Super Admin</label>
                                    </div>

                                    <input type="hidden" wire:model="user.admin" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="admin"
                                            wire:model="user.admin" value="1">
                                        <label class="form-check-label" for="admin">Admin</label>
                                    </div>

                                    <input type="hidden" wire:model="user.management" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="gerente"
                                            wire:model="user.management" value="1">
                                        <label class="form-check-label" for="gerente">Gerente</label>
                                    </div>
                                </div>

                                <!-- Coluna 2 -->
                                <div class="col-md-4">
                                    <input type="hidden" wire:model="user.engineer" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="engenheiro"
                                            wire:model="user.engineer" value="1">
                                        <label class="form-check-label" for="engenheiro">Engenheiro</label>
                                    </div>

                                    <input type="hidden" wire:model="user.responsible" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="responsavel"
                                            wire:model="user.responsible" value="1">
                                        <label class="form-check-label" for="responsavel">Responsável</label>
                                    </div>

                                    <input type="hidden" wire:model="user.operator" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="operador"
                                            wire:model="user.operator" value="1">
                                        <label class="form-check-label" for="operador">Operador</label>
                                    </div>
                                </div>

                                <!-- Coluna 3 -->
                                <div class="col-md-4">
                                    <input type="hidden" wire:model="user.user" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="user"
                                            wire:model="user.user" value="1">
                                        <label class="form-check-label" for="user">Usuário</label>
                                    </div>

                                    <input type="hidden" wire:model="user.btzero" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="btzero"
                                            wire:model="user.btzero" value="1">
                                        <label class="form-check-label" for="btzero">BTZero</label>
                                    </div>

                                    <input type="hidden" wire:model="user.onlyparner" value="0">
                                    <!-- Campo hidden para garantir o envio de 0 -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="empreiteira"
                                            wire:model="user.onlyparner" value="1">
                                        <label class="form-check-label" for="empreiteira">Empreiteira (Visão
                                            Exclusiva)</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="dispatch"
                                            wire:model="user.can_dispatch" value="1">
                                        <label class="form-check-label" for="dispatch">Pode Despachar</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção 3: Adicionar Serviços -->
                        <div class="mb-3">
                            <h6 class="text-primary">Adicionar Atividade</h6>
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
                                            @if ($this->user->ToServices->count())
                                                @foreach ($this->user->ToServices as $toService)
                                                    <tr wire:key='service_single_{{ $toService->id }}'>
                                                        <td>{{ $toService->Service->service }}</td>
                                                        <td>
                                                            <div class="form-check">
                                                                <input
                                                                    class="form-check-input border border-1 border-secondary"
                                                                    type="checkbox" id="service"
                                                                    wire:click.prevent="ServiceOption({{ $toService->id }}, 'service')"
                                                                    @checked($toService->service)>
                                                                <label class="form-check-label"
                                                                    for="engenheiro">Serviço</label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check">
                                                                <input
                                                                    class="form-check-input  border border-1 border-secondary"
                                                                    type="checkbox" id="dispatch"t
                                                                    wire:click.prevent="ServiceOption({{ $toService->id }}, 'dispatch')"
                                                                    @checked($toService->dispatch)>
                                                                <label class="form-check-label"
                                                                    for="engenheiro">Despacho</label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <i class="ri-delete-bin-line fs-5 text-danger cursor-pointer"
                                                                wire:click="removeService({{ $toService->id }})"
                                                                title="Excluir" style="cursor: pointer;"></i>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @elseif(count($this->temporaryServices))
                                                @foreach ($this->temporaryServices as $index => $tempService)
                                                    @php
                                                        $service = Service::where(
                                                            'uuid',
                                                            $tempService['service_id'],
                                                        )->first();
                                                    @endphp
                                                    @if ($service)
                                                        <tr wire:key='service_single_{{ $index }}'>
                                                            <td>{{ $service->service }}</td>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input border border-1 border-secondary"
                                                                        type="checkbox" id="service"
                                                                        wire:model.defer="temporaryServices.{{ $index }}.service">
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

                        <!-- Seção 4: Região de Controle -->
                        {{-- @if ($this->user->engineer)
                            <div class="mb-3">
                                <h6 class="text-primary">Região de Controle (Engenheiro)</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="norte">
                                            <label class="form-check-label" for="norte">Norte</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="centroNorte">
                                            <label class="form-check-label" for="centroNorte">Centro Norte</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="centroSul">
                                            <label class="form-check-label" for="centroSul">Centro Sul</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="sul">
                                            <label class="form-check-label" for="sul">Sul</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif --}}


                        <div class="mb-3">
                            <h6 class="text-primary">Adicionar Empresas de Responsabilidade</h6>
                            <div class="row">
                                <div class="col-md-10">
                                    <select class="form-select" id="servicosDisponiveis"
                                        wire:model.defer="companySelect">
                                        <option value="" selected>Selecione Atividade</option>
                                        @if ($companyList && $companyList->count())
                                            @foreach ($companyList as $cList)
                                                <option value="{{ $cList->id }}">{{ $cList->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-center">
                                    <button type="button" class="btn btn-success" id="addServico"
                                        wire:click="addCompany"><i class="ri-add-line"></i> Adicionar</button>
                                </div>
                            </div>
                            <div class="mt-3 col-sm-6">

                                <div class="card edp-bg-gray">
                                    <div class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                                        Empresas sob Responsabilidade
                                    </div>
                                    <table class="table table-sm table-condensed table-striped">
                                        <tbody>
                                            @if ($user->Companies->count())
                                                @foreach ($user->Companies as $toCompany)
                                                    <tr wire:key='company-list-{{ $toCompany->id }}'
                                                        class="text-center align-middle">
                                                        @php
                                                            if ($toCompany->name) {
                                                                $name = explode(' ', $toCompany->name);
                                                                $name = $name[0] . ' ' . end($name);
                                                            } else {
                                                                $name = 'Desconhecido';
                                                            }
                                                        @endphp
                                                        <td>{{ $name }}</td>
                                                        {{-- <td>
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input border border-1 border-secondary"
                                                            type="checkbox" id="service"
                                                            wire:click.prevent="ServiceOption({{ $toCompany->id }}, 'service')"
                                                            @checked($toCompany->service)>
                                                        <label class="form-check-label"
                                                            for="engenheiro">Serviço</label>
                                                    </div>
                                                </td> --}}
                                                        {{-- <td>
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input  border border-1 border-secondary"
                                                            type="checkbox" id="dispatch"t
                                                            wire:click.prevent="ServiceOption({{ $toCompany->id }}, 'dispatch')"
                                                            @checked($toCompany->dispatch)>
                                                        <label class="form-check-label"
                                                            for="engenheiro">Despacho</label>
                                                    </div>
                                                </td> --}}
                                                        <td>
                                                            <i class="ri-delete-bin-line fs-5 text-danger cursor-pointer"
                                                                wire:click="removeCompany('{{ $toCompany->id }}')"
                                                                title="Excluir" style="cursor: pointer;"></i>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <div class="card-body py-2">
                                                    <h5 class="text-center my-0 py-0">NENHUMA EMPRESA RESPONSAVEL</h5>
                                                </div>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer edp-bg-sprucegreen-100">
                    {{-- @if ($this->user)
                        @livewire('admin.user.actions.user-access-info', ['email' => $this->user->email, 'password' => '123456', 'url' => 'http//edpbr1204/es/'], key('user-Copy-' . $this->user->id))
                    @endif --}}
                    <button type="button" class="btn btn-primary" wire:click="copyClipboarder"><i
                            class="ri-lock-password-line align-middle"></i> Copiar Acessos</button>
                    <button type="button" class="btn btn-warning" wire:click.prevent="resetPassword"><i
                            class="ri-lock-password-line align-middle"></i> Resetar Senha</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="saveUser"
                        wire:click.prevent='Save'>Salvar</button>
                </div>
            </div>
        </div>

    </div>


    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('userModal').addEventListener('hidden.bs.modal', () => {

            Livewire.emitTo('admin.user.actions.usuario', 'closeAll');
        });
    </script>

</div>
