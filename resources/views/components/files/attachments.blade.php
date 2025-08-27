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
            $factor = floor((strlen($bytes) - 1) / 3);
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
@endphp

{{-- PASSO 1: Adicionar novas variáveis de estado (isLoading, modalContentWidth) --}}
<div x-data="{
    isWide: false,
    viewingImage: null,
    viewingTitle: '',
    isLoading: false,
    modalContentWidth: 'auto'
}" class="attachments-component-wrapper">

    <div {{ $attributes->merge(['class' => 'attachments-component ' . $class]) }}>
        @if ($card)
            <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0">
                <div class="card-body">
        @endif

        <div class="attachments-comp-inner">
            @if ($files->isNotEmpty())
                <div x-init="() => {
                    isWide = $el.clientWidth >= 992;
                    window.addEventListener('resize', () => isWide = $el.clientWidth >= 992);
                }" :class="{ 'attachments-comp-grid--wide': isWide }"
                    class="attachments-comp-grid">

                    @if ($others->isNotEmpty())
                        <aside class="attachments-comp-files p-3">
                            {{-- ... HTML para outros arquivos ... --}}
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
                                                {{-- PASSO 2: Resetar o estado ao clicar em uma nova imagem --}}
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
                                                <small class="text-muted d-block">{{ __human_filesize($image->size) }}
                                                    &middot;
                                                    {{ optional($image->created_at)->format('d/m/Y') }}</small>
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

{{-- PASSO 3: Modificar o modal para usar as novas variáveis e o evento @load --}}
<template x-if="viewingImage">
    <div x-show="viewingImage" @keydown.escape.window="viewingImage = null"
        style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;"
        class="p-4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div @click="viewingImage = null" style="position: relative; top: 0; left: 0; right: 0; bottom: 0;"></div>

        {{-- Aplicando o tamanho dinâmico ao container do conteúdo do modal --}}
        <div @click.stop class="bg-white rounded-3 shadow-lg d-flex flex-column"
            :style="{ width: modalContentWidth, transition: 'width 0.3s ease' }"
            style="z-index: 10000; max-width: 95vw; max-height: 95vh; overflow: hidden; left: 40%;">

            <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="flex-shrink: 0;">
                <h5 class="modal-title" x-text="viewingTitle"></h5>
                <button type="button" class="btn-close" @click="viewingImage = null"></button>
            </div>

            <div class="modal-body text-center p-0"
                style="flex-grow: 1; min-height: 150px; display: flex; align-items: center; justify-content: center;">
                {{-- Opcional: Spinner de carregamento --}}
                <div x-show="isLoading" class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>

                {{-- A imagem agora tem o evento @load --}}
                <img :src="viewingImage" class="img-fluid" x-show="!isLoading" style="display: none;"
                    x-init="$el.style.display = 'block'" :alt="viewingTitle"
                    @load="
                            isLoading = false;
                            const img = $event.target;
                            const padding = 32; // espaço de segurança (16px de cada lado)
                            const maxHeight = window.innerHeight - 120; // altura máxima (descontando header e margens)

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
        /* padrão: empilhado */
        .attachments-comp-grid {
            display: block;
        }

        /* quando Alpine marcar isWide=true, aplica grid */
        @media (min-width: 992px) {
            .attachments-comp-grid--wide {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 1rem;
            }
        }

        /* hover e ícones mantêm-se iguais */
        .attachments-comp-file-item {
            transition: transform .25s, box-shadow .25s, border-left .25s;
            border-left: 4px solid transparent !important;
        }

        .attachments-comp-file-item:hover {
            transform: translateX(5px);
            border-left-color: var(--bs-primary) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }

        .attachments-comp-file-item:hover .attachments-comp-file-icon {
            transform: scale(1.1);
        }

        .attachments-comp-image-item {
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .attachments-comp-image-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .attachments-comp-image {
            transition: transform .3s ease;
        }

        .attachments-comp-image-item:hover .attachments-comp-image {
            transform: scale(1.05);
        }

        .attachments-component .btn {
            transition: all .3s ease;
        }

        .attachments-component .btn:hover {
            transform: translateY(-2px);
        }

        .attachments-comp-file-icon {
            transition: transform .25s;
        }

        .attachments-comp-file-icon--pdf {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .attachments-comp-file-icon--doc {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .attachments-comp-file-icon--xls {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .attachments-comp-file-icon--ppt {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .attachments-comp-file-icon--zip {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .attachments-comp-file-icon--txt {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .attachments-comp-file-icon--default {
            background: rgba(0, 0, 0, 0.05);
            color: #6c757d;
        }
    </style>
@endpush
