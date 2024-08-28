<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_edit_file" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                @if ($file)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h4 class="modal-title fs-5">Editar Arquivo</h4>
                    </div>
                    <div class="modal-body">

                        <form wire:submit.prevent="updateFile">
                            <!-- Nome Simbólico do Arquivo -->
                            <div class="mb-3">
                                <label for="file_name" class="form-label">Nome do Arquivo</label>
                                <input type="text" class="form-control @error('file.file_name') is-invalid @enderror"
                                    id="file_name" wire:model.defer="file.file_name">
                                @error('file.file_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Carregar Novo Arquivo -->
                            <div class="mb-3">
                                <label for="newFile" class="form-label">Substituir Arquivo</label>
                                <input type="file" class="form-control @error('newFile') is-invalid @enderror"
                                    id="newFile" wire:model="newFile">
                                @error('newFile')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                @if ($newFile)
                                    <small class="text-success">Arquivo "{{ $newFile->getClientOriginalName() }}" pronto
                                        para upload.</small>
                                @endif
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click.prevent="closeAll">Fechar</button>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
