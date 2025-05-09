<div>
    <div wire:ignore.self class="modal fade" id="modalEntityProtocol" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">NOVO PROTOCOLO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($external)
                        <div class="row g-3">
                            <!-- Entity Type -->
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <select name="entity_type_id" id="entity_type_id" class="form-select"
                                        wire:model="selectedType">
                                        <option value="">Selecione...</option>
                                        @foreach ($entityTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="entity_type_id" class="form-label">Tipo de Entidade</label>
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="search" search="search"
                                        placeholder="Nome da entidade" value="{{ old('search') }}" wire:model="search">
                                    <label for="search">Buscar Entidade</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="entity" id="entity" class="form-select"
                                        wire:model.defer="external.entity_id">
                                        <option value="">Selecione...</option>
                                        @foreach ($entities as $entity)
                                            <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="entity" class="form-label">Tipo</label>
                                </div>
                            </div>

                            <!-- Nick -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="protocol" name="protocol"
                                        placeholder="Apelido" value="{{ old('protocol') }}" wire:model.defer="protocol">
                                    <label for="protocol">Apelido</label>
                                </div>
                            </div>

                            <!-- Observations -->
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Observações..." id="observations" name="observations" style="height: 100px;"
                                        wire:model.defer="observations">{{ old('observations') }}</textarea>
                                    <label for="observations">Observações</label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2"
                        wire:click="$set('entityEdit', null)">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:click="saveEntity">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
