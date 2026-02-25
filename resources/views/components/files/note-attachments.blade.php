@props([
    'files' => collect([]),
    'selectionModel' => 'selectedFiles',
])

@php
    use Illuminate\Support\Facades\Storage;
    use App\Models\Service;

    $allFiles = collect($files ?? []);
    $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    $isSuperAdm = (bool) auth()->user()?->superadm;

    $serviceMap = [];
    Service::query()
        ->select('uuid', 'service')
        ->get()
        ->each(function ($svc) use (&$serviceMap) {
            if ($svc->uuid) {
                $serviceMap[(string) $svc->uuid] = $svc->service;
            }
        });

    $grouped = $allFiles->sortBy('file_name')->groupBy(function ($f) use ($serviceMap) {
        $serviceKey = trim((string) ($f->service_id ?? ''));
        if ($serviceKey === '') {
            return 'Outros';
        }

        return $serviceMap[$serviceKey] ?? 'Outros';
    });
    $orderedKeys = $grouped->keys()->filter(fn ($k) => $k !== 'Outros')->sort()->values()->all();
    if ($grouped->has('Outros')) {
        $orderedKeys[] = 'Outros';
    }
    $servicesCount = count($orderedKeys);
    $filesCount = $allFiles->count();
    $defaultSelectedService = $orderedKeys[0] ?? 'all';
    $allFileIds = $allFiles->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    $idsByService = collect($orderedKeys)
        ->mapWithKeys(fn ($serviceName) => [
            $serviceName => $grouped->get($serviceName, collect())->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ])
        ->toArray();
    $globalImages = $allFiles
        ->filter(fn ($f) => in_array(strtolower((string) $f->ext), $imageExt, true))
        ->sortBy('file_name')
        ->values();
    $imageItems = $globalImages
        ->map(function ($f) {
            $nameWithExt = pathinfo($f->file_name, PATHINFO_FILENAME) . '.' . $f->ext;
            return [
                'id' => (int) $f->id,
                'name' => $nameWithExt,
                'url' => route('files.preview', ['file' => $f->id, 'v' => optional($f->updated_at)->timestamp]),
                'download' => route('files.download', $f->id),
            ];
        })
        ->values()
        ->all();
    $imageIndexById = [];
    foreach ($globalImages as $index => $img) {
        $imageIndexById[(int) $img->id] = (int) $index;
    }

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
    imageItems: @js($imageItems),
    activeImageIndex: null,
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
    },
    openImage(index) {
        if (!this.imageItems.length) return;
        this.activeImageIndex = index;
    },
    closeImage() {
        this.activeImageIndex = null;
    },
    prevImage() {
        if (this.activeImageIndex === null || this.imageItems.length <= 1) return;
        this.activeImageIndex = (this.activeImageIndex - 1 + this.imageItems.length) % this.imageItems.length;
    },
    nextImage() {
        if (this.activeImageIndex === null || this.imageItems.length <= 1) return;
        this.activeImageIndex = (this.activeImageIndex + 1) % this.imageItems.length;
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

        .ns-image-modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, .85);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .ns-image-modal-card {
            width: min(92vw, 1100px);
            max-height: 92vh;
            background: #0f172a;
            border: 1px solid #334155;
            box-shadow: 0 24px 48px rgba(2, 6, 23, .55);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .ns-image-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem .75rem;
            color: #e2e8f0;
            border-bottom: 1px solid #334155;
            background: #111827;
        }

        .ns-image-modal-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            background: #020617;
        }

        .ns-image-modal-body img {
            max-width: 100%;
            max-height: calc(92vh - 110px);
            object-fit: contain;
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
                            $globalImageIndex = $imageIndexById[(int) $file->id] ?? 0;
                        @endphp
                        <div class="ns-attach-thumb" wire:key="img-{{ $file->id }}">
                            <img src="{{ $previewImageUrl }}" alt="{{ $nameWithExt }}" style="cursor:pointer;"
                                x-on:click.prevent="openImage({{ $globalImageIndex }})">
                            <div class="ns-attach-meta">
                                <div class="name" title="{{ $nameWithExt }}">{{ $nameWithExt }}</div>
                                <div class="mb-1">{{ $fmtSize($file->path) }} · {{ optional($file->created_at)->format('d/m/Y') }}</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-check m-0">
                                        <input class="form-check-input border border-secondary" type="checkbox" value="{{ $file->id }}" wire:model.defer="{{ $selectionModel }}">
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
                                        <a class="btn btn-sm btn-outline-primary py-0 px-2" href="{{ route('files.download', $file->id) }}" title="Baixar arquivo">
                                            <i class="ri-download-line"></i>
                                        </a>
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
                                        <input class="form-check-input border border-secondary" type="checkbox" value="{{ $file->id }}" wire:model.defer="{{ $selectionModel }}">
                                    </td>
                                    <td class="align-middle">{{ $nameWithExt }}</td>
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
                                            <a class="btn btn-sm btn-outline-primary py-0 px-2" href="{{ route('files.download', $file->id) }}" title="Baixar arquivo">
                                                <i class="ri-download-line"></i>
                                            </a>
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

    <template x-if="activeImageIndex !== null && imageItems[activeImageIndex]">
        <div class="ns-image-modal" x-on:keydown.escape.window="closeImage()">
            <div class="ns-image-modal-card" x-on:click.outside="closeImage()">
                <div class="ns-image-modal-head">
                    <div class="small text-truncate" x-text="imageItems[activeImageIndex]?.name"></div>
                    <div class="d-flex align-items-center gap-2">
                        <template x-if="imageItems.length > 1">
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-light" x-on:click="prevImage()">
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" x-on:click="nextImage()">
                                    <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </div>
                        </template>
                        <a class="btn btn-sm btn-primary" x-bind:href="imageItems[activeImageIndex]?.download">
                            <i class="ri-download-line"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-light" x-on:click="closeImage()">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
                <div class="ns-image-modal-body">
                    <img x-bind:src="imageItems[activeImageIndex]?.url" x-bind:alt="imageItems[activeImageIndex]?.name">
                </div>
            </div>
        </div>
    </template>
</div>
