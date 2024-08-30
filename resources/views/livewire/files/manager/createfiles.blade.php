<div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress">
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_mass_upload" tabindex="-1" aria-labelledby="massUploadLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                @if ($note)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h4 class="modal-title fs-5">Upload de Arquivos em Massa para {{ $note->note }}</h4>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="saveFiles">
                            <!-- Tipo de Envio -->
                            <div class="mb-3">
                                <label for="upload_type" class="form-label">Tipo de Envio</label>
                                <select class="form-select @error('uploadType') is-invalid @enderror" id="upload_type"
                                    wire:model.defer="uploadType" required>
                                    <option value="" selected>Selecione o tipo de envio</option>
                                    <option value="CROQUI">Croqui</option>
                                    <option value="PROJETO">Projeto</option>
                                    <option value="ASBUILT">Asbuilt</option>
                                    <option value="ADS">ADS</option>
                                    <option value="IMAGEM">Imagens</option>
                                </select>
                                @error('uploadType')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Seleção do Serviço -->
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Selecionar Serviço</label>
                                <select class="form-select @error('service_id') is-invalid @enderror" id="service_id"
                                    wire:model.defer="service_id">
                                    <option value="" selected>Selecione um serviço</option>
                                    @if ($services)
                                        @foreach ($services as $service)
                                            <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('service_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Upload de Arquivos Múltiplos -->
                            <div class="mb-3">
                                <label for="files" class="form-label">Selecionar Arquivos</label>
                                <input type="file" class="form-control @error('files') is-invalid @enderror"
                                    id="files" wire:model="files" multiple>
                                @error('files')
                                    @foreach ($errors->get('files.*') as $fileErrors)
                                        @foreach ($fileErrors as $error)
                                            <div class="invalid-feedback">
                                                {{ $error }}
                                            </div>
                                        @endforeach
                                    @endforeach
                                @enderror
                            </div>

                            <!-- Barra de Progresso usando Alpine.js -->
                            <div class="mb-3" x-show="isUploading">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" :style="`width: ${progress}%`"
                                        aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                                        <span x-text="progress + '%'"></span>
                                    </div>
                                </div>
                            </div>

                            @if (count($tempFiles))
                                <table class="table table-condensed table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center align-middle">Nome Arquivo</th>
                                            <th class="text-center align-middle">Tipo</th>
                                            <th class="text-center align-middle">Serviço</th>
                                            <th class="text-center align-middle"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tempFiles as $index => $tempFile)
                                            <tr wire:key='{{ $tempFile['file']->getClientOriginalName() }}'>
                                                <td class="text-center align-middle">
                                                    {{ $tempFile['file']->getClientOriginalName() }}</td>
                                                <td class="text-center align-middle">{{ $tempFile['uploadType'] }}</td>
                                                <td class="text-center align-middle">
                                                    @if ($tempFile['service_id'])
                                                        {{-- {{ $service->where('uuid', $tempFile['service_id'])->first()->service }} --}}
                                                        {{ $services->firstWhere('uuid', $tempFile['service_id'])->service ?? '' }}
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle"> <i
                                                        class="ri-delete-bin-2-line text-danger fs-5"
                                                        style="cursor: pointer;"
                                                        wire:click.prevent="removeFile({{ $index }})"></i></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click.prevent="closeAll">Fechar</button>
                                <button type="submit" class="btn btn-primary">Iniciar Upload</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
