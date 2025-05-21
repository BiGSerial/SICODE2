<div>
    <div wire:ignore.self class="modal fade" id="modalEditEntityProtocol" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">ALTERAR ENTIDADE PROTOCOLAR</h5>
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
                                            <option value="{{ (int) $entity->id }}">{{ $entity->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="entity" class="form-label">Tipo</label>
                                </div>
                            </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" wire:click="closeAll">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:click="saveEdit">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
