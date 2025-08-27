<div x-data="{
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
    handleDragLeave(e) { e.preventDefault(); if (!e.currentTarget.contains(e.relatedTarget)) { this.isDragOver = false; } },
    handleDrop(e) {
        e.preventDefault();
        this.isDragOver = false;
        const files = e.dataTransfer.files;
        if (files.length) {
            $refs.fileInput.files = files;
            $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}"
    x-on:livewire-upload-start="
        isUploading=true;
        totalSize=[...$refs.fileInput.files].reduce((s,f)=> s + f.size, 0);
        progress=0; uploaded=0;"
    x-on:livewire-upload-progress="
        progress=$event.detail.progress;
        uploaded=Math.round(totalSize*(progress/100));"
    x-on:livewire-upload-error="isUploading=false; progress=0; uploaded=0"
    x-on:livewire-upload-finish="progress=100; uploaded=totalSize; setTimeout(()=> isUploading=false, 400);">
    {{-- Zona de upload minimalista, SEM card/container adicional --}}
    <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center" :class="{ 'drag-over': isDragOver }"
        @dragover="handleDragOver" @dragenter="handleDragEnter" @dragleave="handleDragLeave" @drop="handleDrop"
        @click="$refs.fileInput.click()">

        <div class="mb-2">
            <i class="ri-cloud-line fs-1 text-primary"></i>
        </div>
        <div class="fw-semibold text-primary mb-1">
            Arraste evidências aqui ou clique para selecionar
        </div>
        <div class="text-muted small mb-2">
            Tipos: {{ strtoupper(implode(', ', $config['allowed_exts'])) }} — Máx:
            {{ $config['max_size_mb'] }}MB/arquivo
        </div>

        <input type="file" class="d-none" x-ref="fileInput" multiple
            accept="{{ implode(',', array_map(fn($t) => '.' . $t, $config['allowed_exts'])) }}" wire:model="files">

        @error('files.*')
            <div class="text-danger small mt-2"><i class="ri-error-warning-line me-1"></i>{{ $message }}</div>
        @enderror
    </div>

    {{-- Barra de progresso --}}
    <div class="mt-2" x-show="isUploading" style="display:none;">
        <div class="progress" style="height:6px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                :style="`width:${progress}%`" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="d-flex justify-content-between small mt-1">
            <span class="text-muted"><i class="ri-upload-line me-1"></i>Enviando…</span>
            <span class="text-primary fw-semibold"
                x-text="`${progress}% - ${human(uploaded)} de ${human(totalSize)}`"></span>
        </div>
    </div>

    {{-- Lista de arquivos temporários anexados --}}
    @if (count($tempFiles))
        <div class="mt-3">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                    {{ count($tempFiles) }} {{ count($tempFiles) === 1 ? 'arquivo' : 'arquivos' }}
                </span>

                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancelEvidences">
                    <i class="ri-close-circle-line me-1"></i> Limpar fila
                </button>
            </div>

            <ul class="list-unstyled m-0">
                @foreach ($tempFiles as $i => $t)
                    <li class="temp-file-item d-flex align-items-center justify-content-between p-3 mb-2 rounded-3">
                        <div class="d-flex align-items-center gap-3 flex-grow-1 text-truncate">
                            {{-- Ícone por extensão --}}
                            <div class="file-icon">
                                @if (strtolower($t['extension']) === 'pdf')
                                    <i class="ri-file-pdf-2-fill text-danger fs-3"></i>
                                @elseif(in_array(strtolower($t['extension']), ['jpg', 'jpeg', 'png']))
                                    <i class="ri-image-fill text-info fs-3"></i>
                                @else
                                    <i class="ri-file-3-fill text-secondary fs-3"></i>
                                @endif
                            </div>

                            {{-- Nome + detalhes --}}
                            <div class="text-truncate">
                                <div class="fw-semibold text-truncate" title="{{ $t['original_name'] }}">
                                    {{ $t['original_name'] }}
                                </div>
                                <small class="text-muted">
                                    {{ strtoupper($t['extension']) }} —
                                    {{ number_format($t['size'] / 1048576, 2) }} MB
                                </small>
                            </div>
                        </div>

                        {{-- Botão remover --}}
                        <button class="btn btn-outline-danger btn-sm" wire:click="removeTemp({{ $i }})"
                            title="Remover">
                            <i class="ri-close-line"></i>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <style>
        /* Escopo isolado */
        .temp-file-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: .2s ease;
        }

        .temp-file-item:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.25);
        }

        .temp-file-item .file-icon {
            flex-shrink: 0;
        }
    </style>



    <style>
        .upload-zone {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
            min-height: 140px;
            cursor: pointer;
            transition: .25s ease;
        }

        .upload-zone.drag-over {
            border-color: var(--bs-success) !important;
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.15) 100%);
            transform: scale(1.01);
        }
    </style>
</div>
