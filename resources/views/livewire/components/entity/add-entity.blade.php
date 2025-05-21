<div>
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="modalEntity" tabindex="-1" aria-labelledby="modalEntityLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityLabel">CADASTRO DE ENTIDADE </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-3">
                        <select class="form-select form-select-sm border border-secondary" wire:model="selectedType">
                            <option value="">Selecione o tipo...</option>
                            @foreach ($entityTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model.defer="name"
                            class="form-control form-control-sm border border-secondary"
                            placeholder="Tipo de entidade..." @disabble(!$selectedType)>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="addEntity">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>

                    @if ($entityEdit)

                        <div class="card shadow-sm">
                            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                <h5 class="mb-0">Editar Entidade</h5>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Entity Type -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="entity_type_id" id="entity_type_id"
                                                class="form-select @error('entityEdit.entity_type_id') is-invalid @enderror"
                                                wire:model.defer="entityEdit.entity_type_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($entityTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                            <label for="entity_type_id" class="form-label">Tipo de Entidade</label>
                                            @error('entityEdit.entity_type_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Name -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text"
                                                class="form-control @error('entityEdit.name') is-invalid @enderror"
                                                id="name" name="name" placeholder="Nome da entidade"
                                                value="{{ old('name') }}" wire:model.defer="entityEdit.name">
                                            <label for="name">Nome</label>
                                            @error('entityEdit.name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Nick -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text"
                                                class="form-control @error('entityEdit.nick') is-invalid @enderror"
                                                id="nick" name="nick" placeholder="Apelido"
                                                value="{{ old('nick') }}" wire:model.defer="entityEdit.nick">
                                            <label for="nick">Apelido</label>
                                            @error('entityEdit.nick')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Boolean Flags como switches -->
                                    <div class="col-md-6 d-flex flex-wrap align-items-center">
                                        <div class="form-check form-switch me-3">
                                            <input
                                                class="form-check-input @error('entityEdit.approve') is-invalid @enderror"
                                                type="checkbox" id="approve" name="approve"
                                                {{ old('approve') ? 'checked' : '' }}
                                                wire:model.defer="entityEdit.approve">
                                            <label class="form-check-label" for="approve">Aprovação</label>
                                            @error('entityEdit.approve')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-check form-switch me-3">
                                            <input
                                                class="form-check-input @error('entityEdit.eon') is-invalid @enderror"
                                                type="checkbox" id="eon" name="eon"
                                                {{ old('eon') ? 'checked' : '' }} wire:model.defer="entityEdit.eon">
                                            <label class="form-check-label" for="eon">Eo</label>
                                            @error('entityEdit.eon')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-check form-switch me-3">
                                            <input
                                                class="form-check-input @error('entityEdit.cad') is-invalid @enderror"
                                                type="checkbox" id="cad" name="cad"
                                                {{ old('cad') ? 'checked' : '' }} wire:model.defer="entityEdit.cad">
                                            <label class="form-check-label" for="cad">Cad</label>
                                            @error('entityEdit.cad')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-check form-switch">
                                            <input
                                                class="form-check-input @error('entityEdit.map') is-invalid @enderror"
                                                type="checkbox" id="map" name="map"
                                                {{ old('map') ? 'checked' : '' }} wire:model.defer="entityEdit.map">
                                            <label class="form-check-label" for="map">Mapa</label>
                                            @error('entityEdit.map')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Docs (JSON) via múltiplos arquivos -->
                                    <div class="col-12">
                                        <label class="form-label">Documentos Nescessários</label>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" wire:model.defer="newDoc"
                                                placeholder="Nome do documento" wire:keydown.enter="addDoc">
                                            <button class="btn btn-primary" type="button" wire:click="addDoc">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @if (isset($entityEdit['docs']) && is_array($entityEdit['docs']))
                                                @foreach ($entityEdit['docs'] as $index => $doc)
                                                    <div
                                                        class="badge bg-light text-dark d-flex align-items-center p-2 border border-secondary">
                                                        <span>{{ $doc }}</span>
                                                        <button type="button" class="btn-close ms-2"
                                                            wire:click="removeDoc({{ $index }})"
                                                            aria-label="Remove"></button>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Contatos e Portais:</label>
                                        <div class="d-flex gap-2 mb-3">
                                            <button class="btn btn-primary btn-sm" type="button"
                                                wire:click="addConctact">
                                                <i class="ri-add-line"></i> Novo Contato
                                            </button>
                                            <button class="btn btn-primary btn-sm" type="button"
                                                wire:click="addPortal">
                                                <i class="ri-add-line"></i> Novo Portal
                                            </button>
                                        </div>

                                        @if ($newContact)
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control form-control-sm"
                                                                wire:model.defer="newContact.name"
                                                                placeholder="Nome do contato">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="email" class="form-control form-control-sm"
                                                                wire:model.defer="newContact.email"
                                                                placeholder="Email">
                                                        </div>
                                                        <div class="col-12 text-end">
                                                            <button class="btn btn-secondary btn-sm"
                                                                wire:click="$set('newContact', null)">Cancelar</button>
                                                            <button class="btn btn-primary btn-sm"
                                                                wire:click="saveContact">Salvar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($newPortal)
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="row g-2">
                                                        <div class="col-12">
                                                            <input type="url" class="form-control form-control-sm"
                                                                wire:model.defer="newPortal.url"
                                                                placeholder="URL do Portal">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control form-control-sm"
                                                                wire:model.defer="newPortal.user"
                                                                placeholder="Usuário">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control form-control-sm"
                                                                wire:model.defer="newPortal.password"
                                                                placeholder="Senha">
                                                        </div>
                                                        <div class="col-12 text-end">
                                                            <button class="btn btn-secondary btn-sm"
                                                                wire:click="$set('newPortal', null)">Cancelar</button>
                                                            <button class="btn btn-primary btn-sm"
                                                                wire:click="savePortal">Salvar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="table-responsive mt-3">
                                            @if ($entityEdit->contacts->isNotEmpty())
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Tipo</th>
                                                            <th>Informações</th>
                                                            <th style="width: 50px">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($entityEdit->contacts as $index => $contact)
                                                            <tr>
                                                                <td class="align-middle">
                                                                    @if (isset($contact->name))
                                                                        <span class="badge bg-info">Contato</span>
                                                                    @else
                                                                        <span class="badge bg-success">Portal</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if (isset($contact->name) && isset($contact->email))
                                                                        <div class="d-flex flex-column">
                                                                            <small><strong>Nome:</strong>
                                                                                {{ $contact->name }}</small>
                                                                            <small><strong>Email:</strong>
                                                                                {{ $contact->email }}</small>
                                                                        </div>
                                                                    @endif

                                                                    @if (isset($contact->url))
                                                                        <div class="d-flex flex-column">
                                                                            <small><strong>URL:</strong>
                                                                                <a href="{{ $contact->url }}"
                                                                                    target="_blank"
                                                                                    class="text-decoration-none">
                                                                                    {{ $contact->url }}
                                                                                </a>
                                                                            </small>
                                                                            @if (isset($contact->user))
                                                                                <small><strong>Usuário:</strong>
                                                                                    {{ $contact->user }}</small>
                                                                                <small><strong>Senha:</strong>
                                                                                    {{ $contact->password }}</small>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    <button type="button"
                                                                        class="btn btn-danger btn-sm"
                                                                        wire:click="removeContact({{ $contact->id }})"
                                                                        title="Remover">
                                                                        <i class="ri-delete-bin-2-line"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="alert alert-info mb-0">
                                                    Nenhum contato ou portal cadastrado.
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Observations -->
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" placeholder="Observações..." id="observations" name="observations"
                                                style="height: 100px;" wire:model.defer="entityEdit.observations">{{ old('observations') }}</textarea>
                                            <label for="observations">Observações</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button type="button" class="btn btn-secondary me-2"
                                    wire:click="$set('entityEdit', null)">Cancelar</button>
                                <button type="submit" class="btn btn-primary"
                                    wire:click="saveEntity">Salvar</button>
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div
                                class="card-header bg-light text-secondary fw-bold d-flex align-items-center justify-content-between edp-bg-sprucegreen-70 text-edp-verde">
                                <span>Lista de Entidade</span>

                            </div>
                            <div class="card-body">
                                <input type="text" wire:model.live="search"
                                    class="form-control form-control-sm border border-secondary mb-2"
                                    placeholder="Pesquisar...">
                                @if ($lists->isNotEmpty())

                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-condensed table-striped mt-2">
                                            <thead class="sticky-top bg-white">
                                                <tr>
                                                    <th style="width: 20%">Tipo</th>
                                                    <th style="width: 60%">Entidade</th>
                                                    <th style="width: 20%">Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($lists as $list)
                                                    <tr class="align-middle">
                                                        <td>{{ $list->type?->name }}</td>
                                                        <td>{{ $list->name }}</td>
                                                        <td>
                                                            <button type="button" class="btn btn-primary btn-sm"
                                                                wire:click="entityEdit({{ $list->id }})">
                                                                <i class="ri-edit-2-line"></i>
                                                            </button>

                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                wire:click="deleteEntity({{ $list->id }})">
                                                                <i class="ri-delete-bin-2-line"></i>
                                                            </button>

                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="card my-1 py-0">
                                        <div class="card-body">
                                            <h5 class="text-center">NENHUM TIPO ENCONTRADO</h5>
                                        </div>
                                    </div>

                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
