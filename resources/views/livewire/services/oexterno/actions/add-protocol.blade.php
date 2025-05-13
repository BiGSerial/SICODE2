@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <div wire:ignore.self class="modal fade" id="modalAddProtocol" tabindex="-1" aria-labelledby="modalEntityProtocolLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">NOVO PROTOCOLO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($external)
                        <div class="row g-3">


                            <!-- Protocolo -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="protocol" name="protocol"
                                        placeholder="Apelido" value="{{ old('protocol') }}" wire:model.defer="protocol">
                                    <label for="protocol">Protocolo</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="title" id="title" class="form-select" wire:model="title">
                                        <option value="">Selecione...</option>
                                        @foreach (SelectOptions::getProtocolReasons() as $reason)
                                            <option value="{{ $reason->value }}">{{ $reason->reason }}</option>
                                        @endforeach
                                    </select>
                                    <label for="title" class="form-label">Titulo</label>
                                </div>
                            </div>

                            <!-- Observations -->
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Observações..." id="observations" name="observations" style="height: 100px;"
                                        wire:model.defer="observations">{{ old('observations') }}</textarea>
                                    <label for="observations">Descrição</label>
                                </div>
                            </div>

                            <livewire:files.manager.generic-file-uploader :note="$external->note" :parent-model="$external"
                                relation="files" :upload-types="SelectOptions::getProtocolReasons()" :column="'prefix'" :service-id="$serviceId"
                                :identifiers="[$external->note->note]" />
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" wire:click="closeAll">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:click="saveProtocol">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
