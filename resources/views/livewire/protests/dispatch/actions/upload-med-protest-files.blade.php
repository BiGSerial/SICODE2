@push('css')
    <style>
        .modal-header-modern {
            background: linear-gradient(120deg, #3b82f6 0%, #06b6d4 40%, #3b82f6 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: none;
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.15);
        }

        .modal-header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            min-width: 0;
            width: 100%;
        }

        .modal-header-icon {
            font-size: 1.35rem;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            flex-shrink: 0;
        }

        .modal-header-title {
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
            color: #fff;
        }

        .modal-header-code {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: 600;
            padding: .3rem .8rem;
            border-radius: 999px;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .btn-close-modern {
            filter: invert(1) grayscale(.3);
            opacity: .85;
            width: 36px;
            height: 36px;
            margin: -0.6rem -0.6rem 0 1rem;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .18s;
        }

        .btn-close-modern:hover,
        .btn-close-modern:focus {
            background: rgba(255, 255, 255, 0.14);
            opacity: 1;
        }

        .modern-card {
            border: 0;
            border-radius: 16px;
            background-color: #fff;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, .5);
        }

        .modern-card-body {
            padding: 1.5rem;
        }

        .modern-card-title {
            font-weight: 600;
            font-size: .95rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .upload-zone {
            transition: all 0.3s ease;
            cursor: pointer;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
        }

        .upload-zone:hover {
            border-color: var(--bs-primary) !important;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.15) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15);
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

        .upload-zone.drag-over {
            border-color: var(--bs-success) !important;
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.15) 100%);
            transform: scale(1.02);
        }
    </style>
@endpush

<div wire:ignore.self class="modal fade" id="uploadMedProtestFilesModal" tabindex="-1"
    aria-labelledby="uploadMedProtestFilesModalLabel" aria-hidden="true">

    <x-show-loading />

    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header modal-header-modern border-0">
                <div class="modal-header-content">
                    <div class="modal-header-icon">
                        <i class="ri-upload-cloud-2-line"></i>
                    </div>
                    <h5 class="modal-header-title mb-0">Upload de Recebidos da Medida</h5>
                    @if ($medProtest)
                        <span class="modal-header-code">
                            {{ $medProtest->protest?->nota }}#{{ $medProtest->med_id }}
                        </span>
                    @endif
                </div>
                <button type="button" class="btn-close btn-close-modern" aria-label="Fechar"
                    data-bs-dismiss="modal" wire:click="closeModal"></button>
            </div>

            <div class="modal-body p-4">
                @if ($medProtest)
                    <div class="modern-card">
                        <div class="modern-card-body">
                            <div class="modern-card-title">
                                <i class="ri-upload-cloud-2-line me-2"></i>Recebidos da Medida
                            </div>

                            <div x-data="{
                                isUploading: false,
                                progress: 0,
                                totalSize: 0,
                                uploaded: 0,
                                human(bytes) {
                                    const u = ['B', 'KB', 'MB', 'GB', 'TB'];
                                    let i = 0;
                                    while (bytes >= 1024 && i < u.length - 1) {
                                        bytes /= 1024;
                                        i++
                                    }
                                    return (i ? bytes.toFixed(2) : bytes.toFixed(0)) + ' ' + u[i];
                                }
                            }"
                                x-on:livewire-upload-start="
                                    isUploading = true;
                                    totalSize = [...$refs.fileInput.files].reduce((s,f)=> s + f.size, 0);
                                    progress = 0; uploaded = 0;
                                "
                                x-on:livewire-upload-progress="
                                    progress = $event.detail.progress;
                                    uploaded = Math.round(totalSize * (progress/100));
                                "
                                x-on:livewire-upload-error="isUploading=false; progress=0; uploaded=0"
                                x-on:livewire-upload-finish="
                                    progress = 100; uploaded = totalSize;
                                    setTimeout(()=> isUploading=false, 600);
                                ">

                                <div class="upload-zone p-4 border-2 border-dashed border-primary rounded-3 text-center bg-light position-relative overflow-hidden @error('files.*') border-danger @enderror"
                                    id="uploadZoneMedProtest" ondragover="handleMedProtestDragOver(event)"
                                    ondrop="handleMedProtestDrop(event)" ondragenter="handleMedProtestDragEnter(event)"
                                    ondragleave="handleMedProtestDragLeave(event)"
                                    onclick="document.getElementById('fileInputMedProtest').click()">
                                    <div class="upload-zone-bg"></div>
                                    <div class="position-relative">
                                        <div class="upload-icon mb-3">
                                            <i class="ri-cloud-line fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="text-primary fw-bold mb-2">Arraste arquivos aqui ou clique para selecionar
                                        </h5>
                                        <p class="text-muted mb-3">
                                            Formatos aceitos:
                                            {{ mb_strtoupper(implode(', ', $filesConfig['allowedTypes'])) }}
                                        </p>
                                        <input type="file"
                                            class="form-control d-none @error('files.*') is-invalid @enderror"
                                            id="fileInputMedProtest" x-ref="fileInput" multiple
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt" wire:model="files">
                                        <button type="button" class="btn btn-primary btn-lg px-4"
                                            onclick="event.stopPropagation(); document.getElementById('fileInputMedProtest').click()">
                                            <i class="ri-folder-open-line me-2"></i>
                                            Selecionar Arquivos
                                        </button>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Máximo: {{ $filesConfig['maxSize'] / 1024 }}MB por arquivo
                                            </small>
                                        </div>
                                        @error('files.*')
                                            <div class="alert alert-danger mt-3 mb-0 py-2">
                                                <i class="ri-error-warning-line me-2"></i>
                                                <small>{{ $message }}</small>
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="my-2 py-1" x-show="isUploading" style="display:none;">
                                    <div class="progress position-relative"
                                        style="height:4px; border-radius:2px; overflow:hidden;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" :style="`width:${progress}%`"
                                            :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <i class="ri-upload-line me-1"></i>
                                            Enviando arquivos...
                                        </small>
                                        <small class="text-primary fw-semibold"
                                            x-text="`${progress}% - ${human(uploaded)} de ${human(totalSize)}`">
                                        </small>
                                    </div>
                                </div>

                                @if ($tempFiles && count($tempFiles) > 0)
                                    <div class="mb-4 mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary fw-bold mb-0">
                                                <i class="ri-file-list-3-line me-2"></i>
                                                Arquivos Selecionados
                                            </h6>
                                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                                {{ count($tempFiles) }} {{ count($tempFiles) == 1 ? 'arquivo' : 'arquivos' }}
                                            </span>
                                        </div>
                                        <div class="files-container mt-3">
                                            @foreach ($tempFiles as $index => $file)
                                                <div class="card border-0 shadow-sm mb-2" wire:key="med-temp-{{ $index }}">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-3">
                                                                    <div class="file-icon {{ $this->getFileIconClass($file->getClientOriginalExtension()) }} rounded-2 d-flex align-items-center justify-content-center"
                                                                        style="width:45px; height:45px;">
                                                                        <i
                                                                            class="{{ $this->getFileIcon($file->getClientOriginalExtension()) }} fs-4"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-1 fw-semibold">
                                                                        {{ $file->getClientOriginalName() }}</h6>
                                                                    <div class="d-flex align-items-center text-muted small">
                                                                        <i class="ri-file-line me-1"></i>
                                                                        <span
                                                                            class="me-3">{{ $this->formatFileSize($file->getSize()) }}</span>
                                                                        <i class="ri-check-line text-success me-1"></i>
                                                                        <span class="text-success">Pronto para upload</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-sm rounded-pill"
                                                                title="Remover arquivo"
                                                                wire:click="removeFile({{ $index }})">
                                                                <i class="ri-close-line"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-3">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                                            wire:click="clearAllFiles">
                                            <i class="ri-delete-bin-line me-2"></i>
                                            Limpar Tudo
                                        </button>
                                        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                            wire:click="saveFiles">
                                            <i class="ri-upload-2-line me-2"></i>
                                            Salvar Arquivos
                                        </button>
                                    </div>
                                @else
                                    <div class="alert alert-light border mb-0 mt-3">
                                        <i class="ri-information-line me-1"></i>
                                        Nenhum arquivo em fila para envio.
                                    </div>
                                @endif
                            </div>

                            <hr class="my-4">

                            <div class="modern-card-title">
                                <i class="ri-attachment-line me-2"></i>Arquivos anexados
                            </div>
                            <x-files.attachments :files="$medProtest->EvidenceFiles"
                                deleteAction="{{ auth()->user()->superadm ? 'deleteFile' : '' }}"
                                downloadAction="downloadFile" />
                        </div>
                    </div>

                    <div class="modern-card mt-3">
                        <div class="modern-card-body">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2"
                                data-bs-dismiss="modal" wire:click="closeModal">
                                <i class="ri-arrow-go-back-line me-2"></i>
                                Fechar
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        Nenhuma medida carregada para upload.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function handleMedProtestDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        }

        function handleMedProtestDragEnter(e) {
            e.preventDefault();
            document.getElementById('uploadZoneMedProtest').classList.add('drag-over');
        }

        function handleMedProtestDragLeave(e) {
            e.preventDefault();
            if (!e.currentTarget.contains(e.relatedTarget)) {
                document.getElementById('uploadZoneMedProtest').classList.remove('drag-over');
            }
        }

        function handleMedProtestDrop(e) {
            e.preventDefault();
            document.getElementById('uploadZoneMedProtest').classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length) {
                const fileInput = document.getElementById('fileInputMedProtest');
                fileInput.files = files;
                const changeEvent = new Event('change', {
                    bubbles: true
                });
                fileInput.dispatchEvent(changeEvent);
            }
        }
    </script>
@endpush
