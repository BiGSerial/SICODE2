@php $uploadUid = 'upd-' . $this->id; @endphp

<div x-data="{
    isUploading: false,
    progress: 0,
    totalSize: 0,
    uploaded: 0,
    human(bytes) {
        if (!bytes) return '0 B';
        const u = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < u.length - 1) { bytes /= 1024;
            i++; }
        return bytes.toFixed(1) + ' ' + u[i];
    },
    // Função disparada tanto pelo clique quanto pelo drop
    uploadFiles(files) {
        if (!files || files.length === 0) return;

        this.isUploading = true;
        this.progress = 0;
        this.totalSize = Array.from(files).reduce((s, f) => s + f.size, 0);

        @this.uploadMultiple('files', files,
            () => { this.isUploading = false;
                this.progress = 100; },
            () => { this.isUploading = false;
                alert('Erro no upload'); },
            (e) => { this.progress = e.detail.progress;
                this.uploaded = Math.round(this.totalSize * (this.progress / 100)); }
        );
    }
}" class="w-100">

    <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center position-relative"
        style="cursor: pointer; background: rgba(13, 110, 253, 0.03);" {{-- Previne comportamento padrão do navegador --}}
        x-on:dragover.prevent="$el.classList.add('drag-over')" x-on:dragleave.prevent="$el.classList.remove('drag-over')"
        x-on:drop.prevent="$el.classList.remove('drag-over'); uploadFiles($event.dataTransfer.files)"
        {{-- Clique abre o seletor --}} x-on:click="$refs.fileInput.click()">
        <div class="mb-2">
            <i class="ri-cloud-line fs-1 text-primary"></i>
        </div>
        <div class="fw-bold text-primary">Arraste ou Clique para Upload</div>
        <div class="small text-muted">Limite: {{ $config['max_size_mb'] }}MB por arquivo</div>

        {{-- Input REAL (escondido) --}}
        <input type="file" x-ref="fileInput" class="d-none" multiple
            accept=".{{ implode(',.', $config['allowed_exts']) }}" x-on:change="uploadFiles($event.target.files)">
    </div>

    <div class="mt-3" x-show="isUploading" x-cloak>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" :style="`width: ${progress}%`"
                role="progressbar"></div>
        </div>
        <div class="d-flex justify-content-between mt-1 small">
            <span class="text-muted">Enviando...</span>
            <span class="fw-bold" x-text="`${progress}% (${human(uploaded)} / ${human(totalSize)})`"></span>
        </div>
    </div>

    @if (count($tempFiles))
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">{{ count($tempFiles) }} arquivo(s) na fila</h6>
                <button type="button" class="btn btn-sm btn-link text-danger" wire:click="cancelEvidences">Limpar
                    Tudo</button>
            </div>
            <ul class="list-group">
                @foreach ($tempFiles as $i => $t)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="text-truncate">
                            <i class="ri-file-line me-2"></i>
                            <span class="fw-semibold">{{ $t['original_name'] }}</span>
                            <small class="text-muted ms-2">{{ number_format($t['size'] / 1024 / 1024, 2) }} MB</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0"
                            wire:click="removeTemp({{ $i }})">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <style>
        [x-cloak] {
            display: none !important;
        }

        .upload-zone {
            transition: all 0.2s ease;
            border-color: #dee2e6 !important;
        }

        .upload-zone:hover {
            border-color: #0d6efd !important;
            background: rgba(13, 110, 253, 0.06) !important;
        }

        .upload-zone.drag-over {
            border-color: #198754 !important;
            background: rgba(25, 135, 84, 0.1) !important;
            transform: scale(1.01);
        }
    </style>
</div>
