@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <div wire:ignore.self class="modal fade" id="modalEntityProtocol" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">NOVA ENTIDADE PROTOCOLAR {{ $note->note }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($external)
                        <div class="row g-3">
                            <!-- Entity Type -->
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <select name="entity_type_id" id="entity_type_id"
                                        class="form-select @error('selectedType') is-invalid @enderror"
                                        wire:model="selectedType">
                                        <option value="">Selecione...</option>
                                        @foreach ($entityTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="entity_type_id" class="form-label">Tipo de Entidade</label>
                                    @error('selectedType')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('search') is-invalid @enderror"
                                        id="search" search="search" placeholder="Nome da entidade"
                                        value="{{ old('search') }}" wire:model="search">
                                    <label for="search">Buscar Entidade</label>
                                    @error('search')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="entity" id="entity"
                                        class="form-select @error('external.entity_id') is-invalid @enderror"
                                        wire:model.defer="external.entity_id">
                                        <option value="">Selecione...</option>
                                        @foreach ($entities as $entity)
                                            <option value="{{ $entity->id }}">
                                                {{ $entity->nick ? $entity->nick . ' - ' : '' }}
                                                {{ $entity->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="entity" class="form-label">Tipo</label>
                                    @error('external.entity_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Protocolo -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('protocol') is-invalid @enderror"
                                        id="protocol" name="protocol" placeholder="Apelido"
                                        value="{{ old('protocol') }}" wire:model.defer="protocol">
                                    <label for="protocol">Protocolo</label>
                                    @error('protocol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="title" id="title"
                                        class="form-select @error('title') is-invalid @enderror" wire:model="title">
                                        <option value="">Selecione...</option>
                                        @foreach (SelectOptions::getProtocolReasons() as $reason)
                                            <option value="{{ $reason->value }}">{{ $reason->reason }}</option>
                                        @endforeach
                                    </select>
                                    <label for="title" class="form-label">Titulo</label>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Observations -->
                            <div class="col-12 mb-2">
                                <div class="form-floating">
                                    <textarea class="form-control @error('observations') is-invalid @enderror" placeholder="Observações..."
                                        id="observations" name="observations" style="height: 100px;" wire:model.defer="observations">{{ old('observations') }}</textarea>
                                    <label for="observations">Descrição</label>
                                    @error('observations')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <livewire:files.manager.generic-file-uploader :note="$note" :parent-model="$external"
                            relation="files" :upload-types="SelectOptions::getProtocolReasons()" :column="'prefix'" :service-id="$serviceId" :identifiers="[$note->note]" />
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" wire:click="closeAll">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:click="saveEntity">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
