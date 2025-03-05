<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-gray rounded shadow">
                <!-- Cabeçalho do Modal -->
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h5 class="modal-title fw-bold" id="companyModalLabel">Atualizar Dados da Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
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

                        <div class="d-flex align-items-center gap-4 flex-wrap mt-3">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Imagem de Preview -->
                                    <div class="image-preview">
                                        @php
                                            $column = $this->title_img($i)->name;
                                        @endphp
                                        @if (${'photo' . $i})
                                            <img src="{{ ${'photo' . $i}->temporaryUrl() }}" alt="Preview"
                                                class="img-thumbnail shadow-sm"
                                                style="width: 100px; height: 100px; object-fit: contain;">
                                        @elseif (isset($company) && !empty($company->$column))
                                            <img src="" alt="Imagem armazenada"
                                                class="img-thumbnail shadow-sm"
                                                style="width: 100px; height: 100px; object-fit: contain;">
                                        @else
                                            <img src="" alt="Sem imagem" class="img-thumbnail shadow-sm"
                                                style="width: 100px; height: 100px; object-fit: contain;">
                                        @endif
                                    </div>

                                    <!-- Input de Upload e Barra de Progresso -->
                                    <div class="upload-section">
                                        <label for="photo{{ $i }}"
                                            class="form-label">{{ $this->title_img($i)->title }}</label>
                                        <input type="file" id="photo{{ $i }}" class="form-control"
                                            wire:model="photo{{ $i }}">

                                        <!-- Barra de progresso -->
                                        <div class="progress mt-2" style="height: 5px; width: 150px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" style="width: {{ $uploadProgress[$i] ?? 0 }}%;"
                                                aria-valuenow="{{ $uploadProgress[$i] ?? 0 }}" aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
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
