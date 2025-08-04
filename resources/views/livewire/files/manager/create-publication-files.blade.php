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
            <div class="card mb-0 mt-0 ">
                <div class="card-body">
                    <!-- Alpine para interação -->
                    <div class="px-3" x-data="{
                        isUploading: false,
                        progress: 0,
                        totalSize: 0,
                        uploaded: 0,
                        isDragOver: false,
                        human(bytes) {
                            const u = ['B', 'KB', 'MB', 'GB', 'TB'];
                            let i = 0;
                            while (bytes >= 1024 && i < u.length - 1) {
                                bytes /= 1024;
                                i++
                            }
                            return (i ? bytes.toFixed(2) : bytes.toFixed(0)) + ' ' + u[i];
                        },
                        handleDragOver(e) {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'copy';
                        },
                        handleDragEnter(e) {
                            e.preventDefault();
                            this.isDragOver = true;
                        },
                        handleDragLeave(e) {
                            e.preventDefault();
                            if (!e.currentTarget.contains(e.relatedTarget)) {
                                this.isDragOver = false;
                            }
                        },
                        handleDrop(e) {
                            e.preventDefault();
                            this.isDragOver = false;
                            const files = e.dataTransfer.files;
                            if (files.length) {
                                this.$refs.fileInput.files = files;
                                this.$refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    }"
                        x-on:livewire-upload-start="
                            isUploading = true;
                            totalSize = [...$refs.fileInput.files].reduce((s,f)=> s + f.size, 0);
                            progress = 0; uploaded = 0;"
                        x-on:livewire-upload-progress="
                            progress = $event.detail.progress;
                            uploaded = Math.round(totalSize * (progress/100));"
                        x-on:livewire-upload-error="isUploading=false; progress=0; uploaded=0"
                        x-on:livewire-upload-finish="progress = 100; uploaded = totalSize; setTimeout(()=> isUploading=false, 600);">

                        <!-- Zona de upload com float label -->
                        <div class="form-floating mb-3">
                            <div class="upload-zone p-4 border-2 border-dashed border-primary rounded-3 text-center bg-light position-relative overflow-hidden"
                                :class="{ 'drag-over': isDragOver, 'border-danger': @js($errors->has('files.*')) }"
                                @dragover="handleDragOver" @drop="handleDrop" @dragenter="handleDragEnter"
                                @dragleave="handleDragLeave" @click="$refs.fileInput.click()">

                                <div class="upload-zone-bg"></div>
                                <div class="position-relative">
                                    <div class="upload-icon mb-3">
                                        <i class="ri-cloud-line fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-primary fw-bold mb-2">Arraste arquivos aqui ou clique para
                                        selecionar</h5>
                                    <p class="text-muted mb-3">Formatos aceitos:
                                        {{ mb_strtoupper(implode(', ', $filesConfig['allowedTypes'])) }}</p>

                                    <input type="file"
                                        class="form-control d-none @error('files.*') is-invalid @enderror"
                                        id="fileInput" x-ref="fileInput" multiple
                                        accept="{{ implode(',', array_map(fn($t) => '.' . $t, $filesConfig['allowedTypes'])) }}"
                                        wire:model="files">

                                    <button type="button" class="btn btn-primary btn-lg px-4"
                                        @click.stop="$refs.fileInput.click()">
                                        <i class="ri-folder-open-line me-2"></i>
                                        Selecionar Arquivos
                                    </button>

                                    <div class="mt-2">
                                        <small class="text-muted">Máximo: {{ $filesConfig['maxSize'] / 1024 }}MB por
                                            arquivo</small>
                                    </div>
                                </div>
                            </div>
                            <label for="fileInput" class="text-primary fw-semibold">
                                <i class="ri-attachment-line me-1"></i>
                                Anexar Arquivos
                            </label>
                        </div>

                        @error('files.*')
                            <div class="alert alert-danger mb-3 py-2">
                                <i class="ri-error-warning-line me-2"></i>
                                <small>{{ $message }}</small>
                            </div>
                        @enderror

                        <!-- Barra de Progresso -->
                        <div class="mb-3" x-show="isUploading" style="display:none;">
                            <div class="progress position-relative mb-2" style="height:6px; border-radius:3px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    :style="`width:${progress}%`" :aria-valuenow="progress" aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="ri-upload-line me-1"></i>
                                    Enviando arquivos...
                                </small>
                                <small class="text-primary fw-semibold"
                                    x-text="`${progress}% - ${human(uploaded)} de ${human(totalSize)}`">
                                </small>
                            </div>
                        </div>

                        <!-- Lista de arquivos selecionados -->
                        @if ($tempFiles && count($tempFiles) > 0)
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-floating flex-grow-1 me-3">
                                        <div class="form-control border-0 bg-transparent p-0 h-auto">
                                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                                {{ count($tempFiles) }}
                                                {{ count($tempFiles) == 1 ? 'arquivo' : 'arquivos' }}
                                            </span>
                                        </div>
                                        <label class="text-primary fw-semibold">
                                            <i class="ri-file-list-3-line me-1"></i>
                                            Arquivos Selecionados
                                        </label>
                                    </div>
                                </div>

                                <div class="files-container">
                                    @foreach ($tempFiles as $index => $file)
                                        {{-- @dd($file) --}}
                                        <div
                                            class="file-item border rounded-3 p-3 mb-3 bg-white shadow-sm position-relative overflow-hidden">
                                            <!-- Background subtle pattern -->
                                            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10">
                                                <div class="bg-primary"
                                                    style="background: linear-gradient(45deg, transparent 30%, rgba(13,110,253,0.05) 50%, transparent 70%);">
                                                </div>
                                            </div>

                                            <div class="position-relative">
                                                <div class="d-flex align-items-center">
                                                    <!-- File Icon -->
                                                    <div class="file-icon-wrapper me-3 flex-shrink-0">
                                                        <div class="file-icon {{ $this->getFileIconClass($file['file']->getClientOriginalExtension()) }} rounded-3 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 56px; height: 56px;">
                                                            <i
                                                                class="{{ $this->getFileIcon($file['file']->getClientOriginalExtension()) }} fs-3 text-white"></i>
                                                        </div>
                                                    </div>

                                                    <!-- File Info -->
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="mb-2">
                                                            <h6 class="mb-1 fw-bold text-dark text-truncate"
                                                                title="{{ $file['file']->getClientOriginalName() }}">
                                                                {{ $file['file']->getClientOriginalName() }}
                                                            </h6>
                                                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                                                <div class="d-flex align-items-center text-muted">
                                                                    <i class="ri-file-line me-1 fs-6"></i>
                                                                    <span
                                                                        class="small fw-medium">{{ $this->formatFileSize($file['file']->getSize()) }}</span>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <span
                                                                        class="badge bg-success-subtle text-success rounded-pill px-2 py-1">
                                                                        <i class="ri-check-line me-1"></i>
                                                                        Pronto para upload
                                                                    </span>
                                                                </div>
                                                                <div class="d-flex align-items-center text-primary">
                                                                    <i class="ri-hashtag me-1 fs-6"></i>
                                                                    <span
                                                                        class="small fw-medium">{{ $index + 1 }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Progress indicator (visual enhancement) -->
                                                        <div class="progress bg-light"
                                                            style="height: 3px; border-radius: 2px;">
                                                            <div class="progress-bar bg-success" role="progressbar"
                                                                style="width: 100%; transition: width 0.3s ease;"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Remove Button -->
                                                    <div class="ms-3 flex-shrink-0">
                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 36px; height: 36px;" title="Remover arquivo"
                                                            wire:click="removeFile({{ $index }})">
                                                            <i class="ri-close-line fs-6"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Botões de ação -->
                                <div class="d-flex justify-content-end gap-3 mt-3">
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                                        wire:click="clearAllFiles">
                                        <i class="ri-delete-bin-line me-2"></i>
                                        Limpar Tudo
                                    </button>
                                    {{-- <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                        wire:click="saveFiles">
                                        <i class="ri-upload-2-line me-2"></i>
                                        Salvar Arquivos
                                    </button> --}}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- CSS atualizado -->
                    <style>
                        .upload-zone {
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            cursor: pointer;
                            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
                            min-height: 200px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .upload-zone:hover {
                            border-color: var(--bs-primary) !important;
                            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.15) 100%);
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15);
                        }

                        .upload-zone.drag-over {
                            border-color: var(--bs-success) !important;
                            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.15) 100%);
                            transform: scale(1.02);
                            box-shadow: 0 12px 35px rgba(25, 135, 84, 0.2);
                        }

                        .upload-zone-bg {
                            position: absolute;
                            top: -50%;
                            left: -50%;
                            width: 200%;
                            height: 200%;
                            background: radial-gradient(circle, rgba(13, 110, 253, 0.1) 0%, transparent 70%);
                            animation: float 6s ease-in-out infinite;
                            pointer-events: none;
                        }

                        @keyframes float {

                            0%,
                            100% {
                                transform: translateY(0px) rotate(0deg);
                            }

                            50% {
                                transform: translateY(-10px) rotate(180deg);
                            }
                        }

                        .upload-icon {
                            animation: bounce 2s infinite;
                        }

                        @keyframes bounce {

                            0%,
                            20%,
                            50%,
                            80%,
                            100% {
                                transform: translateY(0);
                            }

                            40% {
                                transform: translateY(-10px);
                            }

                            60% {
                                transform: translateY(-5px);
                            }
                        }

                        .file-item {
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            border-left: 4px solid transparent !important;
                        }

                        .file-item:hover {
                            transform: translateX(5px);
                            border-left-color: var(--bs-primary) !important;
                            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
                        }

                        .file-item:hover .file-icon {
                            transform: scale(1.1);
                            transition: transform 0.3s ease;
                        }

                        .progress-bar {
                            background: linear-gradient(45deg, #007bff, #0056b3);
                            border-radius: 3px;
                        }

                        .btn {
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        }

                        .btn:hover {
                            transform: translateY(-2px);
                        }

                        .form-floating>label {
                            padding: 0.375rem 0.75rem;
                        }

                        .form-floating>.form-control {
                            padding-top: 1.625rem;
                            padding-bottom: 0.625rem;
                        }
                    </style>
                </div>
            </div>
        @endif
    </div>
</div>
