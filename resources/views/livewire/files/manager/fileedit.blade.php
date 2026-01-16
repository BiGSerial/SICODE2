<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_edit_file" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content edp-bg-stategrey-50">
                @if ($file)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <div>
                            <h4 class="modal-title fs-5 mb-1">Editar Arquivo</h4>
                            <small class="text-muted">Atualize nome, nota, servico ou substitua o arquivo.</small>
                        </div>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="updateFile">
                            <div class="row g-3">
                                <div class="col-12 col-lg-7">
                                    <div class="p-3 border rounded-3 bg-white h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Detalhes do arquivo</h6>
                                            <small class="text-muted">Edicao rapida</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="file_name" class="form-label">Nome do arquivo</label>
                                            <input type="text"
                                                class="form-control @error('file.file_name') is-invalid @enderror"
                                                id="file_name" wire:model.defer="file.file_name">
                                            @error('file.file_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="noteNumber" class="form-label">Nota relacionada</label>
                                            <input type="text"
                                                class="form-control @error('noteNumber') is-invalid @enderror"
                                                id="noteNumber" wire:model.defer="noteNumber"
                                                placeholder="Ex: 4500123456">
                                            @error('noteNumber')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Informe o numero da nota para trocar o vinculo.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="service_id" class="form-label">Servico</label>
                                            <select id="service_id"
                                                class="form-select @error('file.service_id') is-invalid @enderror"
                                                wire:model.defer="file.service_id">
                                                <option value="">Sem servico / Outros</option>
                                                @foreach ($services as $service)
                                                    <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                                @endforeach
                                            </select>
                                            @error('file.service_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="rounded-3 bg-light p-2 small text-muted">
                                            <div>Nome original: {{ $file->original_name ?? '-' }}</div>
                                            <div>Extensao: {{ $file->ext ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-5">
                                    <div class="p-3 border rounded-3 bg-white h-100">
                                        <h6 class="mb-2">Substituir arquivo</h6>
                                        <p class="small text-muted mb-3">
                                            O novo arquivo sera salvo no mesmo local e com o mesmo nome base.
                                        </p>
                                        <div class="mb-2">
                                            <label for="newFile" class="form-label">Novo arquivo</label>
                                            <input type="file"
                                                class="form-control @error('newFile') is-invalid @enderror"
                                                id="newFile" wire:model="newFile">
                                            @error('newFile')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div
                                            x-data="{ uploading: false, progress: 0 }"
                                            x-on:livewire-upload-start="uploading = true; progress = 0"
                                            x-on:livewire-upload-finish="uploading = false; progress = 100"
                                            x-on:livewire-upload-error="uploading = false"
                                            x-on:livewire-upload-progress="progress = $event.detail.progress"
                                            class="mt-2"
                                        >
                                            <template x-if="uploading">
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                                                        <span>Enviando arquivo</span>
                                                        <span x-text="progress + '%'"></span>
                                                    </div>
                                                    <div class="progress progress-sm" style="height: 8px;">
                                                        <div
                                                            class="progress-bar progress-bar-striped progress-bar-animated"
                                                            role="progressbar"
                                                            :style="`width: ${progress}%`"
                                                            aria-valuemin="0"
                                                            aria-valuemax="100"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        @if ($newFile)
                                            <div class="alert alert-success py-2 mb-0 small">
                                                Pronto para upload: {{ $newFile->getClientOriginalName() }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click.prevent="closeAll">Fechar</button>
                                <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
