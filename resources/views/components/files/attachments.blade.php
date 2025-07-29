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
    $modalId = 'attCompModal_' . uniqid();

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

<div {{ $attributes->merge(['class' => 'attachments-component ' . $class]) }}>
    @if ($card)
        <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0">
            <div class="card-body">
    @endif

    <div class="attachments-comp-inner">
        {{-- @if ($showHeader)
            <h6 class="text-muted mb-3">{{ $header }}</h6>
        @endif --}}

        @if ($files->isNotEmpty())
            {{-- usa Alpine para “container query” --}}
            <div x-data="{ isWide: false }" x-init="() => {
                isWide = $el.clientWidth >= 992;
                window.addEventListener('resize', () => isWide = $el.clientWidth >= 992);
            }" :class="{ 'attachments-comp-grid--wide': isWide }"
                class="attachments-comp-grid">
                {{-- arquivos --}}
                @if ($others->isNotEmpty())
                    <aside class="attachments-comp-files p-3">
                        <h6 class="text-primary fw-bold mb-3">
                            <i class="ri-file-list-3-line me-2"></i>
                            Arquivos
                            <span class="badge bg-primary-subtle text-primary ms-2">{{ $others->count() }}</span>
                        </h6>
                        @foreach ($others as $file)
                            <div class="attachments-comp-file-item card mb-2 shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="attachments-comp-file-icon {{ __wrapper_class_for_ext($file->extension) }} rounded-2 d-flex align-items-center justify-content-center"
                                                style="width:45px;height:45px;">
                                                <i class="{{ __icon_for_ext($file->extension) }} fs-4"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-semibold text-truncate" style="max-width:180px"
                                                title="{{ $file->stored_name }}">
                                                {{ $file->stored_name }}
                                            </h6>
                                            <small class="text-muted d-block">
                                                <i class="ri-file-line me-1"></i>{{ __human_filesize($file->size) }}
                                                &middot;
                                                <i
                                                    class="ri-calendar-line me-1"></i>{{ optional($file->created_at)->format('d/m/Y') }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        @if ($downloadAction)
                                            <button wire:click="{{ $downloadAction }}({{ $file->id }})"
                                                class="btn btn-outline-primary btn-sm rounded-pill">
                                                <i class="ri-download-line"></i>
                                            </button>
                                        @endif
                                        @if ($deleteAction)
                                            <button wire:click="{{ $deleteAction }}({{ $file->id }})"
                                                class="btn btn-outline-danger btn-sm rounded-pill">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </aside>
                @endif

                {{-- galeria --}}
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
                                                alt="{{ $image->stored_name }}" data-bs-toggle="modal"
                                                data-bs-target="#{{ $modalId }}"
                                                onclick="showImageModal_{{ $modalId }}('{{ asset('storage/' . $image->path) }}','{{ addslashes($image->stored_name) }}')">
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
                                                &middot; {{ optional($image->created_at)->format('d/m/Y') }}</small>
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
</div><!-- .card-body -->
</div><!-- .card -->
@endif

@if ($images->isNotEmpty())
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}_title">Visualizar Imagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="{{ $modalId }}_img" src="" class="img-fluid" alt="">
                </div>
            </div>
        </div>
    </div>
    <script>
        function showImageModal_{{ $modalId }}(src, title) {
            document.getElementById('{{ $modalId }}_img').src = src;
            document.getElementById('{{ $modalId }}_title').textContent = title;
        }
    </script>
@endif

@push('css')
    <style>
        /* padrão: empilhado */
        .attachments-comp-grid {
            display: block;
        }

        /* quando Alpine marcar isWide=true, aplica grid */
        .attachments-comp-grid--wide {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
        }

        /* hover e ícones mantêm-se iguais */
        .attachments-comp-file-item {
            transition: transform .25s, box-shadow .25s, border-left .25s;
            border-left: 4px solid transparent !important;
        }

        .attachments-comp-file-item:hover {
            transform: translateX(5px);
            border-left-color: var(--bs-primary) !important;
            border-left: 4px solid var(--bs-primary) !important;
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

        .attachments-comp-image-item:hover .attachments-comp-image {
            transform: scale(1.05);
        }

        .attachments-component .btn {
            transition: all .3s ease;
        }

        .attachments-component .btn:hover {
            transform: translateY(-2px);
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
</div>
