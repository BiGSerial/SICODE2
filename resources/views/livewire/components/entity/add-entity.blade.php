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
                                        <label for="entity_type_id" class="form-label">Tipo de Entidade</label>
                                        <select name="entity_type_id" id="entity_type_id" class="form-select"
                                            wire:model.defer="entityEdit.entity_type_id">
                                            <option value="">Selecione...</option>
                                            @foreach ($entityTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Name -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Nome da entidade" value="{{ old('name') }}"
                                                wire:model.defer="entityEdit.name">
                                            <label for="name">Nome</label>
                                        </div>
                                    </div>

                                    <!-- Nick -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="nick" name="nick"
                                                placeholder="Apelido" value="{{ old('nick') }}"
                                                wire:model.defer="entityEdit.nick">
                                            <label for="nick">Apelido</label>
                                        </div>
                                    </div>

                                    <!-- Boolean Flags como switches -->
                                    <div class="col-md-6 d-flex flex-wrap align-items-center">
                                        <div class="form-check form-switch me-3">
                                            <input class="form-check-input" type="checkbox" id="approve"
                                                name="approve" {{ old('approve') ? 'checked' : '' }}
                                                wire:model.defer="entityEdit.approve">
                                            <label class="form-check-label" for="approve">Aprovação</label>
                                        </div>
                                        <div class="form-check form-switch me-3">
                                            <input class="form-check-input" type="checkbox" id="eon"
                                                name="eon" {{ old('eon') ? 'checked' : '' }}
                                                wire:model.defer="entityEdit.eon">
                                            <label class="form-check-label" for="eon">Eo</label>
                                        </div>
                                        <div class="form-check form-switch me-3">
                                            <input class="form-check-input" type="checkbox" id="cad"
                                                name="cad" {{ old('cad') ? 'checked' : '' }}
                                                wire:model.defer="entityEdit.cad">
                                            <label class="form-check-label" for="cad">Cad</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="map"
                                                name="map" {{ old('map') ? 'checked' : '' }}
                                                wire:model.defer="entityEdit.map">
                                            <label class="form-check-label" for="map">Mapa</label>
                                        </div>
                                    </div>

                                    <!-- Docs (JSON) via múltiplos arquivos -->
                                    <div class="col-12">
                                        <label class="form-label">Documentos Nescessários</label>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" wire:model.defer="newDoc"
                                                placeholder="Nome do documento">
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
                                @if ($lists->isNotEmpty())
                                    <input type="text" wire:model.live="search"
                                        class="form-control form-control-sm border border-secondary"
                                        placeholder="Pesquisar...">
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
                                                                wire:click="editEntity({{ $list->id }})">
                                                                <i class="ri-edit-2-line"></i>
                                                            </button>

                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                wire:click="delete({{ $list->id }})">
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
