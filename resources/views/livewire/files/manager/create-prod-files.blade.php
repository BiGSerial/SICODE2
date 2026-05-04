<div>
    @php
        use App\Helpers\FileIcon;
        use App\Helpers\SelectOptions;
    @endphp
    <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
        x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress">
        <x-show-loading />
        @if ($production)
            <div class="prod-files-wrap">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="fs-6 fw-bold mb-0">Arquivos do Encerramento</h4>
                        <div class="small opacity-75 mt-1">
                            {{ $production->Note->note }} • {{ $production->Service->service }}
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <div class="row g-3 align-items-start">
                            <div class="col-12 col-lg-5">
                                <label for="upload_type" class="form-label fw-semibold mb-1">
                                    Tipo de Envio <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select border-secondary @error('uploadType') is-invalid @enderror"
                                        id="upload_type" wire:model="uploadType" required>
                                        <option value="" selected>Selecione o tipo de envio</option>
                                        @foreach ($this->getFilesTypeOptions() as $fileType)
                                            <option value="{{ $fileType->value }}">{{ $fileType->reason }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                                        data-bs-target="#showUseType" aria-expanded="false" aria-controls="showUseType"
                                        title="Ver instruções">
                                        <i class="ri-question-line"></i>
                                    </button>
                                </div>
                                @error('uploadType')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-lg-7">
                                <div class="collapse" id="showUseType">
                                    <div class="alert alert-light border mb-0">
                                        Selecione o tipo antes de cada upload. Você pode enviar múltiplos arquivos em
                                        sequência para o mesmo tipo.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 p-3 rounded border border-2 border-secondary border-opacity-25 bg-light">
                            <label for="files" class="form-label fw-semibold mb-1">Selecionar Arquivos</label>
                            <div class="small text-muted mb-2">
                                Formatos permitidos: imagem, PDF, Office e CAD. Tamanho máximo por arquivo: 40MB.
                            </div>
                            <input type="file" class="form-control border-secondary @error('files') is-invalid @enderror"
                                id="files" wire:model="files" multiple @disabled(!$uploadType)
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.dwg,.dxf,.rvt,.rfa,.dgn"
                                placeholder="escolha primeiro o tipo de arquivo">
                            @if (!$uploadType)
                                <div class="small mt-2 text-danger">Selecione o tipo de envio para habilitar o upload.</div>
                            @endif
                            @error('files')
                                @foreach ($errors->get('files.*') as $fileErrors)
                                    @foreach ($fileErrors as $error)
                                        <div class="invalid-feedback d-block">{{ $error }}</div>
                                    @endforeach
                                @endforeach
                            @enderror
                        </div>

                        <div class="mt-3" x-show="isUploading">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    :style="`width: ${progress}%`" aria-valuenow="progress" aria-valuemin="0"
                                    aria-valuemax="100">
                                    <span x-text="progress + '%'"></span>
                                </div>
                            </div>
                        </div>

                        @if (count($tempFiles))
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 60px;"></th>
                                            <th>Nome Arquivo</th>
                                            <th class="text-center" style="width: 180px;">Tipo</th>
                                            <th class="text-center" style="width: 80px;">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tempFiles as $index => $tempFile)
                                            <tr wire:key='{{ $tempFile['file']->getClientOriginalName() }}'>
                                                <td class="text-center">
                                                    <i class="{{ FileIcon::getIcon($tempFile['ext'])->icon }} fs-4"></i>
                                                </td>
                                                <td>{{ $tempFile['file']->getClientOriginalName() }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border">{{ $tempFile['uploadType'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        wire:click.prevent="removeFile({{ $index }})" title="Remover">
                                                        <i class="ri-delete-bin-2-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($alertFile)
                                <div class="alert alert-danger mt-3 mb-0 text-center">
                                    Existem arquivos que parecem não pertencer à obra
                                    <strong>{{ $production->Note->note }}</strong>.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>
