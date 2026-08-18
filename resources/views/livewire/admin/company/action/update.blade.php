<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content edp-bg-gray rounded shadow">
                <!-- Cabeçalho do Modal -->
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h5 class="modal-title fw-bold" id="companyModalLabel">Atualizar Dados da Empresa</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light" wire:click.prevent="downloadUserRegistrationWorkbook" wire:loading.attr="disabled" wire:target="downloadUserRegistrationWorkbook">
                            <i class="ri-file-excel-2-line" wire:loading.remove wire:target="downloadUserRegistrationWorkbook"></i>
                            <span class="spinner-border spinner-border-sm" wire:loading wire:target="downloadUserRegistrationWorkbook"></span>
                            Ficha de usuários
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                </div>

                <!-- Corpo do Modal -->
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Coluna 1: Dados da Empresa -->
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Dados da Empresa</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input wire:model.defer="company.email" type="email" class="form-control"
                                            id="email">
                                    </div>
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome</label>
                                        <input wire:model.defer="company.name" type="text" class="form-control"
                                            id="name">
                                    </div>
                                    <div class="mb-3">
                                        <label for="telephone" class="form-label">Telefone</label>
                                        <input wire:model.defer="company.telephone" type="text" class="form-control"
                                            id="telephone">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna 2: Cadastro de Endereços e Depósitos -->
                        <div class="col-md-6">
                            <!-- Endereços -->
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Endereços</h5>
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="addAddress">
                                        <i class="ri-add-line"></i> Adicionar
                                    </button>
                                </div>
                                <div class="card-body">

                                    @if ($newAddress)
                                        <style>
                                            .slide-down-animation {
                                                animation: slideDown 0.5s ease-out;
                                            }

                                            @keyframes slideDown {
                                                from {
                                                    transform: translateY(-20px);
                                                    opacity: 0;
                                                }

                                                to {
                                                    transform: translateY(0);
                                                    opacity: 1;
                                                }
                                            }
                                        </style>
                                        <div class="slide-down-animation">
                                            <div class="row g-2 mb-3">
                                                <div class="col-sm-8">
                                                    <label for="street" class="form-label">Rua</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="street" wire:model.defer="newAddress.street">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="complement" class="form-label">Complemento</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="complement" wire:model.defer="newAddress.complement">
                                                </div>
                                                <div class="col-sm-8">
                                                    <label for="city" class="form-label">Cidade</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="city" wire:model.defer="newAddress.city">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="uf" class="form-label">UF</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="uf" wire:model.defer="newAddress.uf">
                                                </div>
                                                <div class="col-12 d-flex justify-content-center gap-2 mt-3">
                                                    <button type="button" class="btn btn-sm btn-secondary"
                                                        wire:click.prevent="cancelAddress">Cancelar</button>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        wire:click.prevent="saveAddress">Salvar</button>
                                                </div>
                                            </div>
                                            <hr>
                                        </div>
                                    @endif

                                    <ul class="list-group">
                                        @foreach ($addresses as $index => $address)
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong>{{ $address->street }}</strong>{{ $address->complement ? ', ' . $address->complement : '' }},
                                                    {{ $address->city }} -
                                                    {{ $address->uf }}
                                                </span>
                                                <button class="btn btn-sm btn-danger"
                                                    wire:click="removeAddress({{ $address->id }})">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <!-- Depósitos -->
                            <div class="card shadow-sm border-0 mt-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Depósitos</h5>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        wire:click.prevent="addCenterjob">
                                        <i class="ri-add-line"></i> Adicionar
                                    </button>
                                </div>
                                <div class="card-body">
                                    @if ($centerjob)
                                        <style>
                                            .slide-down-animation {
                                                animation: slideDown 0.5s ease-out;
                                            }

                                            @keyframes slideDown {
                                                from {
                                                    transform: translateY(-20px);
                                                    opacity: 0;
                                                }

                                                to {
                                                    transform: translateY(0);
                                                    opacity: 1;
                                                }
                                            }
                                        </style>
                                        <div class="slide-down-animation">
                                            <div class="row g-2 mb-3">
                                                <div class="col-sm-6">
                                                    <label for="street" class="form-label">Centro Trabalho</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="street" wire:model.defer="centerjob.centerjob">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="complement" class="form-label">Centro</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="complement" wire:model.defer="centerjob.center">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="city" class="form-label">Deposito</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="city" wire:model.defer="centerjob.deposit">
                                                </div>
                                                <div class="col-12 d-flex justify-content-center gap-2 mt-3">
                                                    <button type="button" class="btn btn-sm btn-secondary"
                                                        wire:click.prevent="cancelCenterjob">Cancelar</button>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        wire:click.prevent="saveCenterjob">Salvar</button>
                                                </div>
                                            </div>
                                            <hr>
                                        </div>
                                    @endif
                                    <ul class="list-group">
                                        @if ($company && $company->Centerjobs->count() > 0)
                                            @foreach ($company->Centerjobs as $centerjob)
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <strong>{{ $centerjob->centerjob }}</strong> -
                                                        {{ $centerjob->center }}
                                                        - {{ $centerjob->deposit }}
                                                    </span>
                                                    <button class="btn btn-sm btn-danger">
                                                        <i class="ri-delete-bin-line"
                                                            wire:click="removeCenterjob({{ $centerjob->id }})"></i>
                                                    </button>
                                                </li>
                                            @endforeach
                                        @else
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><strong>SEM DEPOSITO REGISTRADO</strong></span>

                                            </li>

                                        @endif
                                    </ul>
                                </div>
                            </div>

                        </div>

                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Permissões liberadas pelo contratante</h5>
                                        <div class="text-muted small">
                                            Configuração da matriz/concentradora usada como teto para os admins da empresa.
                                        </div>
                                    </div>
                                    @if (!$partnerGrantConfigured)
                                        <span class="badge text-bg-info">Acesso legado completo</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        @foreach ($partnerPermissionCatalog as $groupKey => $group)
                                            <div class="col-md-6 col-xl-4" wire:key="contractor_permission_group_{{ $groupKey }}">
                                                <div class="border rounded-2 p-3 h-100 bg-white">
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="contractor_permission_{{ $groupKey }}"
                                                            wire:model.defer="partnerGrantPermissions.{{ $this->permissionInputKey($groupKey) }}">
                                                        <label class="form-check-label fw-bold" for="contractor_permission_{{ $groupKey }}">
                                                            {{ $group['label'] }}
                                                        </label>
                                                    </div>

                                                    <div class="row g-1">
                                                        @foreach ($group['items'] as $permissionKey => $label)
                                                            <div class="col-12" wire:key="contractor_permission_item_{{ $permissionKey }}">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="contractor_permission_{{ str_replace('.', '_', $permissionKey) }}"
                                                                        wire:model.defer="partnerGrantPermissions.{{ $this->permissionInputKey($permissionKey) }}">
                                                                    <label class="form-check-label small" for="contractor_permission_{{ str_replace('.', '_', $permissionKey) }}">
                                                                        {{ $label }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Cadastro em massa</h5>
                                        <div class="text-muted small">Baixe a ficha, preencha os usuários e processe a validação antes de gravar.</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success" wire:click.prevent="downloadUserRegistrationWorkbook" wire:loading.attr="disabled" wire:target="downloadUserRegistrationWorkbook">
                                        <i class="ri-file-excel-2-line" wire:loading.remove wire:target="downloadUserRegistrationWorkbook"></i>
                                        <span class="spinner-border spinner-border-sm" wire:loading wire:target="downloadUserRegistrationWorkbook"></span>
                                        Baixar ficha
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-5">
                                            <label class="form-label">Ficha preenchida</label>
                                            <input type="file" class="form-control" wire:model="registrationWorkbook" accept=".xlsx,.xls">
                                            @error('registrationWorkbook')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3 d-grid">
                                            <button type="button" class="btn btn-primary" wire:click.prevent="processUserRegistrationWorkbook" wire:loading.attr="disabled" wire:target="processUserRegistrationWorkbook,registrationWorkbook">
                                                <span class="spinner-border spinner-border-sm" wire:loading wire:target="processUserRegistrationWorkbook,registrationWorkbook"></span>
                                                <i class="ri-shield-check-line" wire:loading.remove wire:target="processUserRegistrationWorkbook,registrationWorkbook"></i>
                                                Processar ficha
                                            </button>
                                        </div>
                                        @if ($registrationValidation)
                                            @php($summary = $registrationValidation['summary'] ?? [])
                                            <div class="col-lg-4">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge text-bg-success">Usuários válidos: {{ $summary['users_valid'] ?? 0 }}</span>
                                                    <span class="badge text-bg-danger">Usuários erro: {{ $summary['users_invalid'] ?? 0 }}</span>
                                                    <span class="badge text-bg-success">Filiais válidas: {{ $summary['units_valid'] ?? 0 }}</span>
                                                    <span class="badge text-bg-danger">Filiais erro: {{ $summary['units_invalid'] ?? 0 }}</span>
                                                </div>
                                            </div>
                                            <div class="col-12 d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-outline-danger" wire:click.prevent="exportUserRegistrationErrors" @disabled(!($summary['users_invalid'] ?? 0) && !($summary['units_invalid'] ?? 0))>
                                                    <i class="ri-file-warning-line"></i> Exportar inconsistências
                                                </button>
                                                <button type="button" class="btn btn-success" wire:click.prevent="confirmUserRegistrationWorkbook" @disabled(!($summary['users_valid'] ?? 0) && !($summary['units_valid'] ?? 0))>
                                                    <i class="ri-check-line"></i> Confirmar válidos
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Unidades</h5>
                                        <div class="text-muted small">
                                            @if ($company?->parent)
                                                Esta empresa é unidade de {{ $company->parent->name }}.
                                            @else
                                                Agrupe filiais sem duplicar a empresa principal.
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="newBranch">
                                        <i class="ri-add-line"></i> Unidade
                                    </button>
                                </div>
                                <div class="card-body">
                                    @if ($showBranchForm)
                                        <div class="border rounded-2 p-3 mb-3 bg-white">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label">Nome da unidade</label>
                                                    <input type="text" class="form-control" wire:model.defer="branchName" placeholder="Linhares, São Mateus...">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" wire:model.defer="branchEmail">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Telefone</label>
                                                    <input type="text" class="form-control" wire:model.defer="branchTelephone">
                                                </div>
                                                <div class="col-md-2 d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="cancelBranch">Cancelar</button>
                                                    <button type="button" class="btn btn-primary" wire:click.prevent="saveBranch">Salvar</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="border rounded-2 p-3 mb-3 bg-white">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div>
                                                <strong>Concentradora atual</strong>
                                                <div class="text-muted small">{{ $company?->parent?->name ?: $company?->name }}</div>
                                            </div>
                                            <span class="badge text-bg-light border">A empresa aberta não aparece na lista de associação</span>
                                        </div>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">Buscar outra empresa existente</label>
                                                <input type="search" class="form-control" wire:model.debounce.300ms="branchSearch" placeholder="Ex.: Satel São Paulo">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Associar como unidade</label>
                                                <select class="form-select" wire:model="branchAttachId">
                                                    <option value="">Selecione uma empresa existente</option>
                                                    @foreach ($branchCandidates as $candidate)
                                                        <option value="{{ $candidate->id }}">{{ $candidate->name }} · {{ $candidate->email }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($branchSearch && !$branchCandidates->count())
                                                    <div class="text-muted small mt-1">Nenhuma empresa livre encontrada com esse termo.</div>
                                                @endif
                                            </div>
                                            <div class="col-md-2 d-grid">
                                                <button type="button" class="btn btn-outline-primary" wire:click.prevent="attachExistingBranch" @disabled(!$branchAttachId)>
                                                    <i class="ri-links-line"></i> Associar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($company?->branches?->count())
                                        <div class="row g-2">
                                            @foreach ($company->branches as $branch)
                                                <div class="col-md-6 col-xl-4" wire:key="branch_{{ $branch->id }}">
                                                    <div class="border rounded-2 p-3 h-100 bg-white">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="{{ $branch->logo_url }}" alt="Logo da unidade" style="width: 38px; height: 38px; object-fit: contain; border-radius: 8px; background:#0f172a; padding:4px;">
                                                            <div>
                                                                <div class="fw-bold">{{ $branch->name }}</div>
                                                                <div class="text-muted small">{{ $branch->Address->first()?->city ?: $branch->email }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex gap-2 mt-3 small text-muted">
                                                            <span>{{ $branch->contracts->count() }} contratos</span>
                                                            <span>{{ $branch->contracts->flatMap(fn ($contract) => $contract->services)->unique('id')->count() }} atividades</span>
                                                        </div>
                                                        <div class="mt-3 text-end">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="detachBranch('{{ $branch->id }}')">
                                                                <i class="ri-link-unlink"></i> Desassociar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted small">Nenhuma unidade cadastrada.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Contratos e atividades</h5>
                                        <div class="text-muted small">Configure a base que sera usada no cadastro dos usuarios.</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="newContract">
                                        <i class="ri-add-line"></i> Contrato
                                    </button>
                                </div>
                                <div class="card-body">
                                    @if ($showContractForm)
                                        <div class="border rounded-2 p-3 mb-3 bg-white">
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label">Numero do contrato</label>
                                                    <input type="text" class="form-control" wire:model.defer="contractNumber">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Validade</label>
                                                    <input type="date" class="form-control" wire:model.defer="contractDateEnd">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label d-block">Tipo</label>
                                                    <div class="d-flex gap-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="companyContractService" wire:model="contractService">
                                                            <label class="form-check-label" for="companyContractService">Serviços</label>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="companyContractConstruction" wire:model="contractConstruction">
                                                            <label class="form-check-label" for="companyContractConstruction">Construção</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                                                        <strong>Atividades liberadas</strong>
                                                        <input type="search" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Buscar atividade" wire:model.debounce.300ms="contractActivitySearch">
                                                    </div>
                                                    <div class="row g-2" style="max-height: 320px; overflow:auto;">
                                                        @forelse ($services_l as $activity)
                                                            <div class="col-md-6 col-xl-4" wire:key="company_contract_activity_{{ $activity->id }}">
                                                                <div class="border rounded-2 p-3 h-100">
                                                                    <div class="d-flex align-items-start gap-2">
                                                                        <input class="form-check-input mt-1" type="checkbox" value="{{ $activity->id }}" wire:model="contractSelectedServices" id="company_activity_{{ $activity->id }}">
                                                                        <div class="flex-grow-1">
                                                                            <label class="fw-bold mb-1" for="company_activity_{{ $activity->id }}">{{ $activity->service }}</label>
                                                                            <div class="d-flex flex-wrap gap-1">
                                                                                @if ($activity->project)
                                                                                    <span class="badge text-bg-primary">Projeto</span>
                                                                                @endif
                                                                                @if ($activity->construction)
                                                                                    <span class="badge text-bg-secondary">Construcao</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-check form-switch mt-3">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="contractServiceDispatch.{{ $activity->id }}" id="company_dispatch_{{ $activity->id }}" @disabled(!in_array((string) $activity->id, $contractSelectedServices, true) && !in_array($activity->id, $contractSelectedServices, true))>
                                                                        <label class="form-check-label" for="company_dispatch_{{ $activity->id }}">Permitir despacho</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="col-12 text-center text-muted py-3">Nenhuma atividade encontrada.</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                                <div class="col-12 d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="cancelContract">Cancelar</button>
                                                    <button type="button" class="btn btn-primary" wire:click.prevent="saveContract">
                                                        <i class="ri-save-line"></i> Salvar contrato
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Contrato</th>
                                                    <th>Validade</th>
                                                    <th>Tipo</th>
                                                    <th>Atividades</th>
                                                    <th class="text-end"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($company?->contracts ?? [] as $contract)
                                                    <tr wire:key="company_contract_row_{{ $contract->id }}">
                                                        <td class="fw-bold">{{ $contract->number }}</td>
                                                        <td>{{ $contract->date_end ? date('d/m/Y', strtotime($contract->date_end)) : '-' }}</td>
                                                        <td>
                                                            @if ($contract->service)
                                                                <span class="badge text-bg-primary">Serviços</span>
                                                            @endif
                                                            @if ($contract->construction)
                                                                <span class="badge text-bg-secondary">Construção</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @forelse ($contract->services->take(4) as $service)
                                                                    <span class="badge text-bg-light border">
                                                                        {{ $service->service }}
                                                                        @if ($service->pivot->dispatch)
                                                                            <span class="text-danger">/ despacho</span>
                                                                        @endif
                                                                    </span>
                                                                @empty
                                                                    <span class="text-muted small">Sem atividades</span>
                                                                @endforelse
                                                                @if ($contract->services->count() > 4)
                                                                    <span class="badge text-bg-secondary">+{{ $contract->services->count() - 4 }}</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="editContract({{ $contract->id }})">
                                                                <i class="ri-pencil-line"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" wire:click.prevent="confirmRemoveContract({{ $contract->id }})">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">Nenhum contrato cadastrado.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-4 flex-wrap mt-3">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Imagem de Preview -->
                                    <div class="image-preview">
                                        <img src="{{ $this->logoPreviewUrl($i) }}" alt="{{ $this->title_img($i)->title }}"
                                            class="img-thumbnail shadow-sm bg-white"
                                            style="width: 100px; height: 100px; object-fit: contain;">
                                    </div>

                                    <!-- Input de Upload e Barra de Progresso -->
                                    <div class="upload-section">
                                        <label for="photo{{ $i }}"
                                            class="form-label">{{ $this->title_img($i)->title }}</label>
                                        <input type="file" id="photo{{ $i }}" class="form-control"
                                            wire:model="photo{{ $i }}" accept="image/*">
                                        @error('photo' . $i)
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endfor
                        </div>

                    </div>



                </div>

                <!-- Rodapé do Modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="save">Salvar
                        alterações</button>
                </div>
            </div>
        </div>
    </div>
</div>
