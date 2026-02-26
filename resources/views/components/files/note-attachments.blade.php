@props([
    'files' => collect([]),
    'selectionModel' => 'selectedFiles',
])

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
    use App\Models\Service;

    $allFiles = collect($files ?? []);
    $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    $isSuperAdm = (bool) auth()->user()?->superadm;
    $tacitRestrictedById = $allFiles
        ->mapWithKeys(fn ($f) => [(int) $f->id => (bool) $f->isTacitAdsRestricted()])
        ->all();

    $serviceUuidMap = [];
    $serviceIdMap = [];
    Service::query()
        ->select('id', 'uuid', 'service')
        ->get()
        ->each(function ($svc) use (&$serviceUuidMap, &$serviceIdMap) {
            if ($svc->uuid) {
                $serviceUuidMap[(string) $svc->uuid] = $svc->service;
            }
            $serviceIdMap[(string) $svc->id] = $svc->service;
        });

    $grouped = $allFiles->sortBy('file_name')->groupBy(function ($f) use ($serviceUuidMap, $serviceIdMap) {
        $serviceKey = trim((string) ($f->service_id ?? ''));
        if ($serviceKey === '') {
            return 'Outros';
        }
        if (Str::isUuid($serviceKey)) {
            return $serviceUuidMap[$serviceKey] ?? 'Outros';
        }
        return $serviceIdMap[$serviceKey] ?? 'Outros';
    });
    $orderedKeys = $grouped->keys()->filter(fn ($k) => $k !== 'Outros')->sort()->values()->all();
    if ($grouped->has('Outros')) {
        $orderedKeys[] = 'Outros';
    }
    $servicesCount = count($orderedKeys);
    $filesCount = $allFiles->count();
    $defaultSelectedService = $orderedKeys[0] ?? 'all';
    $allFileIds = $allFiles
        ->filter(fn ($f) => $isSuperAdm || !($tacitRestrictedById[(int) $f->id] ?? false))
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
    $idsByService = collect($orderedKeys)
        ->mapWithKeys(fn ($serviceName) => [
            $serviceName => $grouped
                ->get($serviceName, collect())
                ->filter(fn ($f) => $isSuperAdm || !($tacitRestrictedById[(int) $f->id] ?? false))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ])
        ->toArray();
    $globalImages = $allFiles
        ->filter(fn ($f) => in_array(strtolower((string) $f->ext), $imageExt, true))
        ->sortBy('file_name')
        ->values();
    $globalImageIds = $globalImages->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    $imageItems = $globalImages
        ->map(function ($f) use ($isSuperAdm, $tacitRestrictedById) {
            $nameWithExt = pathinfo($f->file_name, PATHINFO_FILENAME) . '.' . $f->ext;
            return [
                'id' => (int) $f->id,
                'name' => $nameWithExt,
                'url' => route('files.preview', ['file' => $f->id, 'v' => optional($f->updated_at)->timestamp]),
                'download' => ($isSuperAdm || !($tacitRestrictedById[(int) $f->id] ?? false))
                    ? route('files.download', $f->id)
                    : '',
                'restricted' => (bool) ($tacitRestrictedById[(int) $f->id] ?? false),
            ];
        })
        ->values();
    $previewModalId = 'nsPreviewModal_' . uniqid();
    $previewCarouselId = 'nsPreviewCarousel_' . uniqid();
    $previewTitleId = 'nsPreviewTitle_' . uniqid();
    $previewDownloadId = 'nsPreviewDownload_' . uniqid();

    $fmtSize = function (?string $path): string {
        if (!$path || !Storage::exists($path)) {
            return '---';
        }
        $size = Storage::size($path);
        if ($size < 1024) {
            return $size . ' B';
        }
        if ($size < 1024 * 1024) {
            return number_format($size / 1024, 1, ',', '.') . ' KB';
        }
        return number_format($size / 1024 / 1024, 2, ',', '.') . ' MB';
    };

    $serviceLabel = function (string $serviceName): string {
        return $serviceName === 'Outros' ? 'Outros (sem serviço)' : $serviceName;
    };

@endphp

<div class="ns-attach" x-data="{
    selectedService: @js($defaultSelectedService),
    allFileIds: @js($allFileIds),
    idsByService: @js($idsByService),
    applySelectAll() {
        const ids = this.selectedService === 'all'
            ? this.allFileIds
            : (this.idsByService[this.selectedService] || []);
        $wire.set('{{ $selectionModel }}', ids);
    },
    clearSelection() {
        $wire.set('{{ $selectionModel }}', []);
    },
    saveSelectedService() {
        try {
            window.localStorage.setItem(@js('note-attachments-selected-service'), this.selectedService);
        } catch (e) {}
    },
    restoreSelectedService() {
        try {
            const saved = window.localStorage.getItem(@js('note-attachments-selected-service'));
            if (saved && (saved === 'all' || this.idsByService[saved])) {
                this.selectedService = saved;
            }
        } catch (e) {}
    }
}" x-init="restoreSelectedService()">
    <style>
        [x-cloak] {
            display: none !important;
        }

        .ns-attach {
            border: 1px solid #dbe2ea;
            border-radius: 0;
            background: rgba(248, 250, 252, .55);
            overflow: hidden;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .ns-attach-head {
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            border-bottom: 1px solid #e2e8f0;
            padding: .62rem .9rem;
            font-size: .78rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: #0f766e;
            font-weight: 700;
        }

        .ns-attach-toolbar {
            background: linear-gradient(180deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #dbe2ea;
            padding: .65rem .9rem;
        }

        .ns-attach-pills {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .ns-pill {
            background: #e2e8f0;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            padding: .2rem .5rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .ns-pill strong {
            color: #0b5f6b;
            margin-left: .15rem;
        }

        .ns-attach-section {
            padding: .75rem .9rem;
            border-bottom: 1px solid #f1f5f9;
            background: rgba(255, 255, 255, .72);
        }

        .ns-attach-section:last-child {
            border-bottom: 0;
        }

        .ns-attach-title {
            font-size: .76rem;
            font-weight: 700;
            color: #0b5f6b;
            margin-bottom: .65rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .22rem .55rem;
            background: #e6fffa;
            border: 1px solid #99f6e4;
        }

        .ns-attach-title-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .55rem;
        }

        .ns-attach-title-info {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            background: #f1f5f9;
            border: 1px solid #dbe2ea;
            padding: .18rem .48rem;
        }

        .ns-attach-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: .6rem;
        }

        .ns-attach-thumb {
            border: 1px solid #dbe2ea;
            border-radius: .45rem;
            overflow: hidden;
            background: rgba(248, 250, 252, .9);
        }

        .ns-attach-thumb img {
            width: 100%;
            height: 115px;
            object-fit: cover;
            display: block;
        }

        .ns-attach-meta {
            padding: .45rem .55rem;
            font-size: .74rem;
            color: #475569;
        }

        .ns-attach-meta .name {
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ns-attach .table thead th {
            font-size: .72rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ns-attach .actions {
            display: flex;
            align-items: center;
            gap: .25rem;
        }

        .ns-attach .table-responsive,
        .ns-attach .table,
        .ns-attach .table thead,
        .ns-attach .table tbody,
        .ns-attach .table tr,
        .ns-attach .table th,
        .ns-attach .table td {
            border-radius: 0 !important;
        }

        .ns-attach-section.ns-section-all {
            margin: .7rem;
            padding: .75rem;
            border: 1px solid #dbe7f3;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
            border-left: 4px solid #0f766e;
        }

        .ns-attach-section.ns-section-all .ns-attach-title {
            background: linear-gradient(90deg, #dcfce7, #ecfeff);
            border-color: #a7f3d0;
        }

        .ns-mode-hint {
            font-size: .72rem;
            color: #475569;
            margin-top: .35rem;
        }

        .ns-mode-hint strong {
            color: #0b5f6b;
        }

        .ns-select-actions {
            margin-top: .45rem;
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .ns-modal-dark .modal-content {
            background: #0f172a;
            border: 1px solid #334155;
        }

        .ns-modal-dark .modal-header,
        .ns-modal-dark .modal-footer {
            border-color: #334155;
            color: #e2e8f0;
        }

        .ns-modal-dark .btn-close {
            filter: invert(1) grayscale(1);
        }

        .ns-modal-dark .modal-body {
            min-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #020617;
            position: relative;
            padding: 0;
        }

        .ns-modal-dark .modal-body img {
            width: 100%;
            max-height: 76vh;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }

        .ns-carousel-zone {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 14%;
            min-width: 68px;
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, .92);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color .2s ease;
        }

        .ns-carousel-zone i {
            font-size: 1.9rem;
            opacity: .4;
            transition: opacity .2s ease;
        }

        .ns-carousel-zone:hover {
            background: rgba(2, 6, 23, .35);
        }

        .ns-carousel-zone:hover i {
            opacity: 1;
        }

        .ns-carousel-zone-prev {
            left: 0;
        }

        .ns-carousel-zone-next {
            right: 0;
        }
    </style>

    <div class="ns-attach-head d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span>Arquivos por Serviço</span>
        <div class="ns-attach-pills">
            <span class="ns-pill">Serviços <strong>{{ $servicesCount }}</strong></span>
            <span class="ns-pill">Arquivos <strong>{{ $filesCount }}</strong></span>
        </div>
    </div>
    @if (!empty($orderedKeys))
        <div class="ns-attach-toolbar">
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-bold">Atividade / Serviço</label>
                <select class="form-select form-select-sm border-primary-subtle" style="max-width: 320px;" x-model="selectedService" x-on:change="saveSelectedService()">
                    <option value="all">Todos</option>
                    @foreach ($orderedKeys as $serviceName)
                        <option value="{{ $serviceName }}">
                            {{ $serviceLabel($serviceName) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ns-mode-hint" x-show="selectedService !== 'all'" x-cloak>
                Exibindo apenas a atividade selecionada. Para visão consolidada use <strong>Todos</strong>.
            </div>
            <div class="ns-mode-hint" x-show="selectedService === 'all'" x-cloak>
                Modo <strong>Todos</strong> ativo: os arquivos estão separados por blocos de atividade.
            </div>
            <div class="ns-select-actions">
                <button type="button" class="btn btn-sm btn-outline-primary"
                    x-on:click="applySelectAll()">
                    <i class="ri-checkbox-multiple-line me-1"></i>
                    Selecionar todos
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    x-on:click="clearSelection()">
                    Limpar seleção
                </button>
            </div>
        </div>
    @endif

    @forelse ($orderedKeys as $serviceIndex => $serviceName)
        @php
            $serviceFiles = $grouped->get($serviceName, collect());
            $images = $serviceFiles->filter(fn ($f) => in_array(strtolower((string) $f->ext), $imageExt, true));
            $others = $serviceFiles->reject(fn ($f) => in_array(strtolower((string) $f->ext), $imageExt, true));
        @endphp

        <div class="ns-attach-section"
            x-bind:class="selectedService === 'all' ? 'ns-section-all' : ''"
            x-show="selectedService === 'all' || selectedService === @js($serviceName)" x-cloak>
            <div class="ns-attach-title-wrap">
                <div class="ns-attach-title">
                    {{ $serviceLabel($serviceName) }}
                    <span class="text-muted">({{ $serviceFiles->count() }})</span>
                </div>
                <div class="ns-attach-title-info" x-show="selectedService === 'all'" x-cloak>
                    Atividade {{ $serviceIndex + 1 }} de {{ $servicesCount }}
                </div>
            </div>

            @if ($images->isNotEmpty())
                <div class="ns-attach-grid mb-2">
                    @foreach ($images as $file)
                        @php
                            $nameWithExt = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;
                            $previewImageUrl = route('files.preview', ['file' => $file->id, 'v' => optional($file->updated_at)->timestamp]);
                            $currentIndex = array_search((int) $file->id, $globalImageIds, true);
                            $globalImageIndex = $currentIndex === false ? 0 : $currentIndex;
                        @endphp
                        <div class="ns-attach-thumb" wire:key="img-{{ $file->id }}">
                            <img src="{{ $previewImageUrl }}" alt="{{ $nameWithExt }}" style="cursor:pointer;"
                                data-ns-preview-modal="{{ $previewModalId }}"
                                data-preview-index="{{ $globalImageIndex }}">
                            <div class="ns-attach-meta">
                                <div class="name" title="{{ $nameWithExt }}">{{ $nameWithExt }}</div>
                                @if ($isSuperAdm && ($tacitRestrictedById[(int) $file->id] ?? false))
                                    <div class="mb-1">
                                        <span class="badge text-bg-dark">ADS TÁCITA</span>
                                    </div>
                                @endif
                                <div class="mb-1">{{ $fmtSize($file->path) }} · {{ optional($file->created_at)->format('d/m/Y') }}</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-check m-0">
                                        @if ($isSuperAdm || !($tacitRestrictedById[(int) $file->id] ?? false))
                                            <input class="form-check-input border border-secondary" type="checkbox" value="{{ $file->id }}" wire:key="chk-img-{{ $file->id }}" wire:model="{{ $selectionModel }}">
                                        @endif
                                    </label>
                                    <div class="actions">
                                        @if ($isSuperAdm)
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                wire:click.prevent="$emitTo('files.manager.fileedit', 'editFile', {{ $file->id }})"
                                                title="Editar arquivo">
                                                <i class="ri-pencil-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                                wire:click.prevent="$emitTo('files.manager.fileedit', 'deleteFile', {{ $file->id }})"
                                                title="Excluir arquivo">
                                                <i class="ri-delete-bin-2-line"></i>
                                            </button>
                                        @endif
                                        @if ($isSuperAdm || !($tacitRestrictedById[(int) $file->id] ?? false))
                                            <a class="btn btn-sm btn-outline-primary py-0 px-2" href="{{ route('files.download', $file->id) }}" title="Baixar arquivo">
                                                <i class="ri-download-line"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    @endforeach
                </div>
            @endif

            @if ($others->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:36px;"></th>
                                <th>Arquivo</th>
                                <th class="text-center">Data</th>
                                <th class="text-center">Tam.</th>
                                <th class="text-center" style="width: {{ $isSuperAdm ? '120px' : '48px' }};"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($others as $file)
                                @php $nameWithExt = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext; @endphp
                                <tr wire:key="doc-{{ $file->id }}">
                                    <td class="text-center align-middle">
                                        @if ($isSuperAdm || !($tacitRestrictedById[(int) $file->id] ?? false))
                                            <input class="form-check-input border border-secondary" type="checkbox" value="{{ $file->id }}" wire:key="chk-doc-{{ $file->id }}" wire:model="{{ $selectionModel }}">
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $nameWithExt }}
                                        @if ($isSuperAdm && ($tacitRestrictedById[(int) $file->id] ?? false))
                                            <span class="badge text-bg-dark ms-1">ADS TÁCITA</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ optional($file->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="text-center align-middle">{{ $fmtSize($file->path) }}</td>
                                    <td class="text-center align-middle">
                                        <div class="actions justify-content-center">
                                            @if ($isSuperAdm)
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                    wire:click.prevent="$emitTo('files.manager.fileedit', 'editFile', {{ $file->id }})"
                                                    title="Editar arquivo">
                                                    <i class="ri-pencil-line"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                                    wire:click.prevent="$emitTo('files.manager.fileedit', 'deleteFile', {{ $file->id }})"
                                                    title="Excluir arquivo">
                                                    <i class="ri-delete-bin-2-line"></i>
                                                </button>
                                            @endif
                                            @if ($isSuperAdm || !($tacitRestrictedById[(int) $file->id] ?? false))
                                                <a class="btn btn-sm btn-outline-primary py-0 px-2" href="{{ route('files.download', $file->id) }}" title="Baixar arquivo">
                                                    <i class="ri-download-line"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="p-3 text-center text-muted">Sem arquivos anexados.</div>
    @endforelse

    @if ($imageItems->isNotEmpty())
        <div class="modal fade ns-modal-dark" id="{{ $previewModalId }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title text-truncate pe-3" id="{{ $previewTitleId }}"></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="{{ $previewCarouselId }}" class="carousel slide w-100" data-bs-interval="false" data-bs-touch="true" data-bs-wrap="true">
                            <div class="carousel-inner">
                                @foreach ($imageItems as $idx => $img)
                                    <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}"
                                        data-name="{{ $img['name'] }}"
                                        data-download="{{ $img['download'] }}">
                                        <img src="{{ $img['url'] }}" alt="{{ $img['name'] }}">
                                    </div>
                                @endforeach
                            </div>
                            @if ($imageItems->count() > 1)
                                <button class="ns-carousel-zone ns-carousel-zone-prev" type="button"
                                    data-bs-target="#{{ $previewCarouselId }}" data-bs-slide="prev" aria-label="Imagem anterior">
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                                <button class="ns-carousel-zone ns-carousel-zone-next" type="button"
                                    data-bs-target="#{{ $previewCarouselId }}" data-bs-slide="next" aria-label="Próxima imagem">
                                    <i class="ri-arrow-right-s-line"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div></div>
                        <a class="btn btn-sm btn-primary" id="{{ $previewDownloadId }}" href="#" target="_blank" rel="noopener">
                            <i class="ri-download-line me-1"></i>Baixar
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (function() {
                const modalId = @js($previewModalId);
                const carouselId = @js($previewCarouselId);
                const titleId = @js($previewTitleId);
                const downloadId = @js($previewDownloadId);
                const bindKey = 'nsPreviewBound_' + modalId;
                if (window[bindKey]) return;
                window[bindKey] = true;

                const modalEl = document.getElementById(modalId);
                const carouselEl = document.getElementById(carouselId);
                const titleEl = document.getElementById(titleId);
                const downloadEl = document.getElementById(downloadId);
                if (!modalEl || !carouselEl || !window.bootstrap) return;

                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                const carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, {
                    interval: false,
                    ride: false,
                    wrap: true,
                    touch: true
                });

                const syncMeta = () => {
                    const active = carouselEl.querySelector('.carousel-item.active');
                    if (!active) return;
                    const name = active.getAttribute('data-name') || '';
                    const download = active.getAttribute('data-download') || '#';
                    if (titleEl) titleEl.textContent = name;
                    if (downloadEl) {
                        if (download && download !== '#') {
                            downloadEl.setAttribute('href', download);
                            downloadEl.classList.remove('d-none');
                        } else {
                            downloadEl.setAttribute('href', '#');
                            downloadEl.classList.add('d-none');
                        }
                    }
                };

                carouselEl.addEventListener('slid.bs.carousel', syncMeta);
                modalEl.addEventListener('shown.bs.modal', syncMeta);

                document.addEventListener('click', function(e) {
                    const trigger = e.target.closest('[data-ns-preview-modal="' + modalId + '"]');
                    if (!trigger) return;
                    const index = parseInt(trigger.getAttribute('data-preview-index') || '0', 10);
                    carousel.to(Number.isNaN(index) ? 0 : index);
                    modal.show();
                });
            })();
        </script>
    @endif
</div>
