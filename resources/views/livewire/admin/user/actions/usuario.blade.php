@php
    use App\Models\Service;
@endphp

<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background: linear-gradient(130deg, #0f172a 0%, #0f766e 80%);">
                    <h5 class="modal-title" id="userModalLabel">Cadastro de Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body bg-light">
                    @if ($this->user)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body d-flex align-items-center gap-3">
                                <img src="{{ $this->user->avatar_url }}" alt="Avatar" class="rounded-circle border" style="width: 62px; height: 62px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-1">{{ $this->user->name ?: 'Novo usuario' }}</h6>
                                    <div class="text-muted small">{{ $this->user->email ?: 'Sem e-mail cadastrado' }}</div>
                                </div>
                                <div class="ms-auto text-end">
                                    <span class="badge text-bg-secondary">ID {{ $this->user->id ?: 'novo' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white">
                                <strong>Dados principais</strong>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" wire:model.defer="user.email" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Matrícula</label>
                                        <input type="text" class="form-control" wire:model.defer="user.Registration">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" wire:model.defer="user.name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Empresa</label>
                                        <select class="form-select" wire:model="user.company_id" required>
                                            <option value="">Selecione a empresa</option>
                                            @if ($companyList)
                                                @foreach ($companyList as $cList)
                                                    <option wire:key="listCompany_{{ $cList->id }}" value="{{ $cList->id }}">{{ $cList->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Contrato</label>
                                        <select class="form-select" wire:model="contract" required>
                                            <option value="">Selecione o contrato</option>
                                            @if ($contractList)
                                                @foreach ($contractList as $cList)
                                                    <option value="{{ $cList->id }}">{{ $cList->number }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="contractUser" wire:model.defer="user.contract">
                                            <label class="form-check-label" for="contractUser">Terceirizado</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white">
                                <strong>Permissoes</strong>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="superAdmin" wire:model="user.superadm" @disabled(!Auth()->User()->superadm)><label class="form-check-label" for="superAdmin">Super Admin</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="admin" wire:model="user.admin"><label class="form-check-label" for="admin">Admin</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="management" wire:model="user.management"><label class="form-check-label" for="management">Gerente</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="engineer" wire:model="user.engineer"><label class="form-check-label" for="engineer">Engenheiro</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="responsible" wire:model="user.responsible"><label class="form-check-label" for="responsible">Responsável</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="operator" wire:model="user.operator"><label class="form-check-label" for="operator">Operador</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="basicUser" wire:model="user.user"><label class="form-check-label" for="basicUser">Usuario</label></div></div>
                                    <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="btzero" wire:model="user.btzero"><label class="form-check-label" for="btzero">BTZero</label></div></div>
                                    <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="onlyparner" wire:model="user.onlyparner"><label class="form-check-label" for="onlyparner">Empreiteira (visão exclusiva)</label></div></div>
                                    <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="dispatch" wire:model="user.can_dispatch"><label class="form-check-label" for="dispatch">Pode despachar</label></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white">
                                        <strong>Atividades liberadas</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2 mb-3">
                                            <div class="col-9">
                                                <select class="form-select" wire:model.defer="serviceSelect">
                                                    <option value="">Selecione atividade</option>
                                                    @if ($this->serviceList)
                                                        @foreach ($this->serviceList as $sList)
                                                            <option value="{{ $sList->uuid }}">{{ $sList->service }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-3 d-grid">
                                                <button type="button" class="btn btn-success" wire:click="addService"><i class="ri-add-line"></i> Adicionar</button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <tbody>
                                                    @if ($this->user->ToServices->count())
                                                        @foreach ($this->user->ToServices as $toService)
                                                            <tr wire:key="service_single_{{ $toService->id }}">
                                                                <td>{{ $toService->Service->service }}</td>
                                                                <td><div class="form-check"><input class="form-check-input" type="checkbox" wire:click.prevent="ServiceOption({{ $toService->id }}, 'service')" @checked($toService->service)> <label class="form-check-label">Serviço</label></div></td>
                                                                <td><div class="form-check"><input class="form-check-input" type="checkbox" wire:click.prevent="ServiceOption({{ $toService->id }}, 'dispatch')" @checked($toService->dispatch)> <label class="form-check-label">Despacho</label></div></td>
                                                                <td class="text-end"><i class="ri-delete-bin-line text-danger" style="cursor: pointer;" wire:click="removeService({{ $toService->id }})"></i></td>
                                                            </tr>
                                                        @endforeach
                                                    @elseif(count($this->temporaryServices))
                                                        @foreach ($this->temporaryServices as $index => $tempService)
                                                            @php
                                                                $service = Service::where('uuid', $tempService['service_id'])->first();
                                                            @endphp
                                                            @if ($service)
                                                                <tr wire:key="service_single_{{ $index }}">
                                                                    <td>{{ $service->service }}</td>
                                                                    <td><div class="form-check"><input class="form-check-input" type="checkbox" wire:model.defer="temporaryServices.{{ $index }}.service"> <label class="form-check-label">Serviço</label></div></td>
                                                                    <td><div class="form-check"><input class="form-check-input" type="checkbox" wire:model.defer="temporaryServices.{{ $index }}.dispatch"> <label class="form-check-label">Despacho</label></div></td>
                                                                    <td class="text-end"><i class="ri-delete-bin-line text-danger" style="cursor: pointer;" wire:click="removeService({{ $index }})"></i></td>
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
                            </div>

                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white">
                                        <strong>Empresas sob responsabilidade</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2 mb-3">
                                            <div class="col-9">
                                                <select class="form-select" wire:model.defer="companySelect">
                                                    <option value="">Selecione empresa</option>
                                                    @if ($companyList && $companyList->count())
                                                        @foreach ($companyList as $cList)
                                                            <option value="{{ $cList->id }}">{{ $cList->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-3 d-grid">
                                                <button type="button" class="btn btn-success" wire:click="addCompany"><i class="ri-add-line"></i> Add</button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <tbody>
                                                    @if ($user->Companies->count())
                                                        @foreach ($user->Companies as $toCompany)
                                                            @php
                                                                $name = $toCompany->name ? collect(explode(' ', $toCompany->name))->filter()->values() : collect();
                                                                $prettyName = $name->count() > 1 ? $name->first().' '.$name->last() : ($name->first() ?: 'Desconhecido');
                                                            @endphp
                                                            <tr wire:key="company-list-{{ $toCompany->id }}">
                                                                <td>{{ $prettyName }}</td>
                                                                <td class="text-end">
                                                                    <i class="ri-delete-bin-line text-danger" style="cursor: pointer;" wire:click="removeCompany('{{ $toCompany->id }}')"></i>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td colspan="2" class="text-center text-muted">Nenhuma empresa vinculada</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-outline-primary" wire:click="copyClipboarder"><i class="ri-file-copy-line align-middle"></i> Copiar acessos</button>
                    <button type="button" class="btn btn-warning" wire:click.prevent="resetPassword"><i class="ri-lock-password-line align-middle"></i> Resetar senha</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="Save">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('userModal');
            if (!modalEl || modalEl.dataset.closeBound === '1') {
                return;
            }

            modalEl.dataset.closeBound = '1';
            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.user.actions.usuario', 'closeAll');
            });
        });
    </script>
</div>
