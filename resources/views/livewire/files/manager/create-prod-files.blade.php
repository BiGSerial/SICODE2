@php
    use App\Helpers\FileIcon;
    use App\Helpers\SelectOptions;
@endphp
<div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress">
    <x-show-loading />
    @if ($production)
        <div class="card">
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                <h4 class="card-title fs-5 my-0 py-0">Upload de Arquivos em Lote para {{ $production->Note->note }} em
                    {{ $production->Service->service }}</h4>
            </div>
            <div class="card-body">

                <!-- Tipo de Envio -->
                <div class="mb-3 col-mb-6">
                    <label for="upload_type" class="form-label">Tipo de Envio <span
                            class="text-danger fw-bold">*</span></label>
                    <select class="form-select border-secondary @error('uploadType') is-invalid @enderror"
                        id="upload_type" wire:model="uploadType" required style="max-width: 350px;">
                        <option value="" selected>Selecione o tipo de envio</option>
                        @foreach (SelectOptions::getFilesType() as $fileType)
                            <option value="{{ $fileType->value }}">{{ $fileType->reason }}</option>
                        @endforeach
                    </select>
                    @error('uploadType')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>



                <!-- Upload de Arquivos Múltiplos -->
                <div class="mb-3">
                    <label for="files" class="form-label">Selecionar Arquivos @if (!$uploadType)
                            <i class="text-danger"> ( Selecione primeiro o Tipo de Envio )</i>
                        @endif
                    </label>
                    <input type="file" class="form-control border-secondary @error('files') is-invalid @enderror"
                        id="files" wire:model="files" multiple @disabled(!$uploadType)
                        placeholder="escolher primeiro o tipo de arquivo">
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
                                <th class="text-center align-middle"></th>
                                <th class="text-center align-middle">Nome Arquivo</th>
                                <th class="text-center align-middle">Tipo</th>
                                <th class="text-center align-middle"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tempFiles as $index => $tempFile)
                                <tr wire:key='{{ $tempFile['file']->getClientOriginalName() }}'>
                                    <td class="text-center align-middle">
                                        <i class="{{ FileIcon::getIcon($tempFile['ext'])->icon }} fs-4 my-0 py-0"></i>
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $tempFile['file']->getClientOriginalName() }}</td>
                                    <td class="text-center align-middle">{{ $tempFile['uploadType'] }}</td>

                                    <td class="text-center align-middle"> <i
                                            class="ri-delete-bin-2-line text-danger fs-5" style="cursor: pointer;"
                                            wire:click.prevent="removeFile({{ $index }})"></i></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($alertFile)
                        <div class="card text-bg-danger">
                            <p class="text-center my-1 py-1 fs-5 text-uppercase">Existem Arquivos que parecem não
                                pertencer a Obra:
                                <strong>{{ $production->Note->note }}</strong>
                            </p>
                        </div>
                    @endif
                @endif

            </div>
        </div>
    @endif
</div>
