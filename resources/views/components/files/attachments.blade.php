@props([
    'files' => collect([]),
    'deleteAction' => null,
    'downloadAction' => null,
    'showHeader' => true,
    'header' => 'ARQUIVOS ANEXADOS:',
    'card' => true,
    'class' => '',
])

@php
    $imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];
    $images = $files->filter(fn($f) => in_array(strtolower($f->extension), $imgExt));
    $others = $files->filter(fn($f) => !in_array(strtolower($f->extension), $imgExt));

    if (!function_exists('__human_filesize')) {
        function __human_filesize($bytes, $decimals = 2)
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $factor = max(0, min(count($units) - 1, (int) floor((strlen((string) $bytes) - 1) / 3)));
            return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
        }
    }
    if (!function_exists('__icon_for_ext')) {
        function __icon_for_ext($ext)
        {
            return match (strtolower($ext)) {
                'pdf' => 'ri-file-pdf-line',
                'doc', 'docx' => 'ri-file-word-2-line',
                'xls', 'xlsx', 'csv' => 'ri-file-excel-2-line',
                'ppt', 'pptx' => 'ri-file-ppt-2-line',
                'zip', 'rar', '7z' => 'ri-archive-line',
                'txt', 'log' => 'ri-file-text-line',
                default => 'ri-file-3-line',
            };
        }
    }
    if (!function_exists('__wrapper_class_for_ext')) {
        function __wrapper_class_for_ext($ext)
        {
            return match (strtolower($ext)) {
                'pdf' => 'attachments-comp-file-icon--pdf',
                'doc', 'docx' => 'attachments-comp-file-icon--doc',
                'xls', 'xlsx', 'csv' => 'attachments-comp-file-icon--xls',
                'ppt', 'pptx' => 'attachments-comp-file-icon--ppt',
                'zip', 'rar', '7z' => 'attachments-comp-file-icon--zip',
                'txt', 'log' => 'attachments-comp-file-icon--txt',
                default => 'attachments-comp-file-icon--default',
            };
        }
    }

    // Namespace único por instância para isolar CSS/JS
    $__ATTACH_NS = 'attch-' . uniqid();
@endphp

<div x-data="{
    isWide: false,
    viewingImage: null,
    viewingTitle: '',
    isLoading: false,
    modalContentWidth: 'auto'
}" class="attachments-component-wrapper" data-ui="{{ $__ATTACH_NS }}">
    <div {{ $attributes->merge(['class' => 'attachments-component ' . $class]) }}>
        @if ($card)
            <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0">
                <div class="card-body">
        @endif

        <div class="attachments-comp-inner">
            @if ($showHeader)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="ri-attachment-2 text-primary"></i>
                    <strong class="text-uppercase small text-muted">{{ $header }}</strong>
                </div>
            @endif

            @if ($files->isNotEmpty())
                <div x-init="() => {
                    isWide = $el.clientWidth >= 992;
                    window.addEventListener('resize', () => isWide = $el.clientWidth >= 992);
                }" :class="{ 'attachments-comp-grid--wide': isWide }"
                    class="attachments-comp-grid">
                    @if ($others->isNotEmpty())
                        <aside class="attachments-comp-files p-3">
                            <h6 class="fw-bold mb-3">
                                <i class="ri-file-3-line me-2"></i>Arquivos
                                <span
                                    class="badge bg-secondary-subtle text-secondary ms-2">{{ $others->count() }}</span>
                            </h6>

                            <div class="list-group list-group-flush">
                                @foreach ($others as $f)
                                    @php $ext = strtolower($f->extension); @endphp
                                    <div
                                        class="list-group-item attachments-comp-file-item d-flex align-items-center gap-3">
                                        <div class="attachments-comp-file-icon {{ __wrapper_class_for_ext($ext) }} d-inline-flex align-items-center justify-content-center rounded-2"
                                            style="width:42px;height:42px;">
                                            <i class="{{ __icon_for_ext($ext) }}"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-truncate" title="{{ $f->stored_name }}">
                                                {{ $f->stored_name }}</div>
                                            <small class="text-muted">
                                                {{ strtoupper($ext) }} &middot; {{ __human_filesize($f->size) }}
                                                &middot; {{ optional($f->created_at)->format('d/m/Y') }}
                                            </small>
                                        </div>
                                        <div class="ms-auto">
                                            <div class="btn-group">
                                                @if ($downloadAction)
                                                    <button class="btn btn-sm btn-outline-secondary"
                                                        wire:click="{{ $downloadAction }}({{ $f->id }})"
                                                        title="Baixar">
                                                        <i class="ri-download-line"></i>
                                                    </button>
                                                @endif
                                                @if ($deleteAction)
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        wire:click="{{ $deleteAction }}({{ $f->id }})"
                                                        title="Remover">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </aside>
                    @endif

                    @if ($images->isNotEmpty())
                        <section class="attachments-comp-gallery p-3">
                            <h6 class="text-primary fw-bold mb-3">
                                <i class="ri-image-line me-2"></i>
                                Galeria de Imagens
                                <span class="badge bg-primary-subtle text-primary ms-2">{{ $images->count() }}</span>
                            </h6>

                            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
                                @foreach ($images as $image)
                                    <div class="col" style="min-width: 12rem">
                                        <div class="attachments-comp-image-item card border-0 shadow-sm h-100">
                                            <div class="position-relative">
                                                <img src="{{ asset('storage/' . $image->path) }}"
                                                    class="card-img-top attachments-comp-image"
                                                    style="object-fit:cover;cursor:pointer;height:160px"
                                                    alt="{{ $image->stored_name }}"
                                                    @click="
                                                        viewingImage = '{{ asset('storage/' . $image->path) }}';
                                                        viewingTitle = '{{ addslashes($image->stored_name) }}';
                                                        isLoading = true;
                                                        modalContentWidth = 'auto';
                                                    ">
                                                <div class="position-absolute top-0 end-0 p-2">
                                                    <button class="btn btn-sm btn-dark bg-opacity-75 rounded-pill"
                                                        data-bs-toggle="dropdown">
                                                        <i class="ri-more-2-line"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if ($downloadAction)
                                                            <li>
                                                                <button class="dropdown-item"
                                                                    wire:click="{{ $downloadAction }}({{ $image->id }})">
                                                                    <i class="ri-download-line me-2"></i>Baixar
                                                                </button>
                                                            </li>
                                                        @endif
                                                        @if ($deleteAction)
                                                            <li>
                                                                <button class="dropdown-item text-danger"
                                                                    wire:click="{{ $deleteAction }}({{ $image->id }})">
                                                                    <i class="ri-delete-bin-line me-2"></i>Remover
                                                                </button>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="card-body p-2 text-truncate">
                                                <small class="d-block"
                                                    title="{{ $image->stored_name }}">{{ $image->stored_name }}</small>
                                                <small class="text-muted d-block">
                                                    {{ __human_filesize($image->size) }} &middot;
                                                    {{ optional($image->created_at)->format('d/m/Y') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="ri-file-unknow-line fs-1 mb-2"></i><br>Nenhum arquivo anexado.
                </div>
            @endif
        </div>

        @if ($card)
    </div>
</div>
@endif
</div>

{{-- Modal isolado pelo namespace --}}
<template x-if="viewingImage">
    <div x-show="viewingImage" class="attachments-modal-overlay p-4" @keydown.escape.window="viewingImage = null"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click="viewingImage = null" style="position:absolute; inset:0;"></div>

        <div @click.stop class="attachments-modal-content"
            :style="{ width: modalContentWidth, transition: 'width 0.3s ease' }">
            <div class="attachments-modal-header">
                <h5 class="modal-title" x-text="viewingTitle"></h5>
                <button type="button" class="btn-close" @click="viewingImage = null"></button>
            </div>

            <div class="attachments-modal-body">
                <div x-show="isLoading" class="spinner-border attachments-spinner text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>

                <img :src="viewingImage" class="img-fluid" x-show="!isLoading" style="display:none;"
                    x-init="$el.style.display = 'block'" :alt="viewingTitle"
                    @load="
                            isLoading = false;
                            const img = $event.target;
                            const padding = 32; // segurança
                            const maxHeight = window.innerHeight - 120; // desconta header/margens

                            let targetWidth = img.naturalWidth;
                            let targetHeight = img.naturalHeight;
                            const ratio = targetWidth / targetHeight;

                            if (targetHeight > maxHeight) {
                                targetHeight = maxHeight;
                                targetWidth = targetHeight * ratio;
                            }

                            const maxWidth = window.innerWidth - padding;
                            if (targetWidth > maxWidth) {
                                targetWidth = maxWidth;
                            }

                            modalContentWidth = targetWidth + 'px';
                        ">
            </div>
        </div>
    </div>
</template>
</div>

@push('css')
    <style>
        /* ==================== ESCOPAGEM TOTAL POR NAMESPACE ==================== */
        /* Reset mínimo para dentro do wrapper, sem afetar o resto da página */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-component {
            all: revert;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-component * {
            box-sizing: border-box;
        }

        /* Grid padrão: empilhado */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-grid {
            display: block;
        }

        /* Quando Alpine marcar isWide=true, aplica grid */
        @media (min-width: 992px) {
            [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-grid--wide {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 1rem;
            }
        }

        /* Itens de arquivo (não-imagem) */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-item {
            transition: transform .25s, box-shadow .25s, border-left .25s;
            border-left: 4px solid transparent !important;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-item:hover {
            transform: translateX(5px);
            border-left-color: var(--bs-primary, #0d6efd) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1) !important;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-item:hover .attachments-comp-file-icon {
            transform: scale(1.1);
        }

        /* Cards da galeria */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-image-item {
            transition: transform .3s ease, box-shadow .3s ease;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-image-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, .15) !important;
        }

        /* Imagens da galeria */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-image {
            transition: transform .3s ease;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-image-item:hover .attachments-comp-image {
            transform: scale(1.05);
        }

        /* Botões dentro do componente (não afeta outros .btn da página) */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-component .btn {
            transition: all .3s ease;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-component .btn:hover {
            transform: translateY(-2px);
        }

        /* Ícones por tipo de arquivo */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon {
            transition: transform .25s;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--pdf {
            background: rgba(220, 53, 69, .1);
            color: #dc3545;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--doc {
            background: rgba(13, 110, 253, .1);
            color: #0d6efd;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--xls {
            background: rgba(25, 135, 84, .1);
            color: #198754;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--ppt {
            background: rgba(255, 193, 7, .1);
            color: #ffc107;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--zip {
            background: rgba(108, 117, 125, .1);
            color: #6c757d;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--txt {
            background: rgba(13, 110, 253, .1);
            color: #0d6efd;
        }

        [data-ui="{{ $__ATTACH_NS }}"] .attachments-comp-file-icon--default {
            background: rgba(0, 0, 0, .05);
            color: #6c757d;
        }

        /* Overlay do modal */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Conteúdo do modal */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-modal-content {
            background: #fff;
            border-radius: .75rem;
            box-shadow: 0 10px 35px rgba(0, 0, 0, .25);
            display: flex;
            flex-direction: column;
            max-width: 95vw;
            max-height: 95vh;
            overflow: hidden;
        }

        /* Header do modal */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
        }

        /* Body do modal */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-modal-body {
            flex: 1;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Spinner */
        [data-ui="{{ $__ATTACH_NS }}"] .attachments-spinner {
            width: 2.5rem;
            height: 2.5rem;
        }
    </style>
@endpush
