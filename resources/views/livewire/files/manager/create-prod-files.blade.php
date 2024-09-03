<div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress">
    <x-show-loading />
    @if ($production)
        <div class="card">
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                <h4 class="card-title fs-5">Upload de Arquivos em Lote para {{ $production->Note->note }}</h4>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="saveFiles">
                    <!-- Tipo de Envio -->
                    <div class="mb-3">
                        <label for="upload_type" class="form-label">Tipo de Envio</label>
                        <select class="form-select @error('uploadType') is-invalid @enderror" id="upload_type"
                            wire:model="uploadType" required>
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



                    <!-- Upload de Arquivos Múltiplos -->
                    <div class="mb-3">
                        <label for="files" class="form-label">Selecionar Arquivos</label>
                        <input type="file" class="form-control @error('files') is-invalid @enderror" id="files"
                            wire:model="files" multiple @disabled(!$uploadType)>
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
                                                class="ri-delete-bin-2-line text-danger fs-5" style="cursor: pointer;"
                                                wire:click.prevent="removeFile({{ $index }})"></i></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" wire:click.prevent="closeAll">Fechar</button>
                        <button type="submit" class="btn btn-primary">Iniciar Upload</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
