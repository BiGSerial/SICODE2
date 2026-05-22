@php
    use App\Helpers\FileIcon;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'];
    $canPreviewImage = function ($file) use ($imageExtensions) {
        if (!in_array(mb_strtolower($file->ext), $imageExtensions, true)) {
            return false;
        }

        $rawPath = ltrim((string) $file->path, '/');
        $storageCandidates = array_values(array_unique(array_filter([
            $rawPath,
            Str::startsWith($rawPath, 'storage/') ? Str::after($rawPath, 'storage/') : null,
        ])));

        foreach ($storageCandidates as $candidate) {
            if (Storage::exists($candidate) || Storage::disk('public')->exists($candidate)) {
                return true;
            }
        }

        $fsCandidates = array_values(array_unique(array_filter([
            public_path($rawPath),
            public_path('storage/' . $rawPath),
            storage_path('app/public/' . $rawPath),
            storage_path('app/' . $rawPath),
        ])));

        foreach ($fsCandidates as $candidate) {
            if (is_file($candidate)) {
                return true;
            }
        }

        return false;
    };
@endphp

<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="modal_form_work" tabindex="-1" aria-labelledby="modalFormWorkLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50 work-show-modal">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <div>
                        <h4 class="my-0 fw-bold" id="modalFormWorkLabel">OBRA INFORMADA</h4>
                        @if ($form)
                            <small class="d-block mt-1 text-white-50">
                                Nota/OV {{ $form->Note->note }} - {{ mb_strtoupper($form->Note->lexp ?? '') }}
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    @if ($form)
                        @php
                            $files = $form->Note->Files ?? collect();
                            $groupedFiles = $files
                                ->groupBy(fn ($file) => mb_strtoupper($file->service->service ?? 'OUTROS'))
                                ->sortKeys();
                            $previewFiles = $files
                                ->filter(fn ($f) => in_array(mb_strtolower($f->ext), $imageExtensions, true))
                                ->sortBy('file_name')
                                ->values();
                            $modalScope = 'work-' . $form->id;
                        @endphp

                        <div class="work-summary-grid mb-3">
                            <div class="work-summary-item">
                                <span>NOTA/OV</span>
                                <strong>{{ $form->Note->note }}</strong>
                            </div>
                            <div class="work-summary-item">
                                <span>EMPREITEIRA</span>
                                <strong>{{ mb_strtoupper($form->Company->name ?? '-') }}</strong>
                            </div>
                            <div class="work-summary-item">
                                <span>DATA DE EXECUÇÃO</span>
                                <strong>{{ date('d/m/Y', strToTime($form->date)) }}</strong>
                            </div>
                            <div class="work-summary-item">
                                <span>ARQUIVOS</span>
                                <strong>{{ $files->count() }}</strong>
                            </div>
                        </div>

                        <div class="card work-card">
                            <h5 class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                INFORMAÇÕES
                            </h5>
                            <div class="card-body">
                                <div class="work-info-grid">
                                    <div class="work-info-row">
                                        <span>ORDEM</span>
                                        <strong>
                                            @forelse ($form->Orders as $order)
                                                <span class="badge text-bg-light border me-1 mb-1">{{ $order->ordem }}</span>
                                            @empty
                                                -
                                            @endforelse
                                        </strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>RUBRICA</span>
                                        <strong>{{ mb_strtoupper($form->Note->rubrica ?? '-') }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>MUNICIPIO</span>
                                        <strong>{{ mb_strtoupper($form->Note->lexp ?? '-') }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>MUDANÇA NO PROJETO</span>
                                        <strong>{{ $form->changes ? 'SIM' : 'NÃO' }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>DATA PRIMEIRO INFORME</span>
                                        <strong>{{ $form->created_at->format('d/m/Y H:i:s') }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>DATA DO INFORME</span>
                                        <strong>{{ isset($form->informed_at) ? date('d/m/Y H:i:s', strToTime($form->informed_at)) : '-' }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>NÚMERO DD</span>
                                        <strong>{{ $form->dd ?: '-' }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>EQUIPE WPA</span>
                                        <strong>{{ mb_strtoupper($form->team ?: '-') }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>ENCARREGADO RESPONSÁVEL</span>
                                        <strong>{{ mb_strtoupper($form->responsible ?: '-') }}</strong>
                                    </div>
                                    <div class="work-info-row">
                                        <span>RESPONSÁVEL PELO INFORME</span>
                                        <strong>{{ mb_strtoupper($form->informer ?: '-') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card work-card mt-3">
                            <div
                                class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <h5 class="my-0 fw-bold">ARQUIVOS ANEXADOS</h5>
                                @if ($files->isNotEmpty())
                                    <span class="badge text-bg-light">{{ $files->count() }} arquivo(s)</span>
                                @endif
                            </div>
                            <div class="card-body">
                                @if ($files->isNotEmpty())
                                    <div class="work-files" data-work-files="{{ $modalScope }}" wire:ignore.self>
                                        <ul class="nav nav-pills work-file-tabs" id="{{ $modalScope }}-tabs"
                                            role="tablist">
                                            @foreach ($groupedFiles as $serviceName => $group)
                                                @php
                                                    $tabId = $modalScope . '-tab-' . $loop->index;
                                                    $paneId = $modalScope . '-pane-' . $loop->index;
                                                @endphp
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                        id="{{ $tabId }}" data-bs-toggle="pill"
                                                        data-bs-target="#{{ $paneId }}" type="button"
                                                        role="tab" aria-controls="{{ $paneId }}"
                                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                        {{ $serviceName }}
                                                        <span class="badge rounded-pill text-bg-light ms-1">
                                                            {{ $group->count() }}
                                                        </span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <div class="tab-content mt-3" id="{{ $modalScope }}-tab-content">
                                            @foreach ($groupedFiles as $serviceName => $group)
                                                @php
                                                    $paneId = $modalScope . '-pane-' . $loop->index;
                                                    $serviceFiles = $group->sortBy('file_name')->values();
                                                    $imageFiles = $serviceFiles
                                                        ->filter(fn ($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                                                        ->values();
                                                    $documentFiles = $serviceFiles
                                                        ->reject(fn ($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                                                        ->values();
                                                @endphp
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                    id="{{ $paneId }}" role="tabpanel" tabindex="0">
                                                    @if ($imageFiles->isNotEmpty())
                                                        <div class="work-thumb-grid mb-3">
                                                            @foreach ($imageFiles as $file)
                                                                @php
                                                                    $hasPreviewImage = $canPreviewImage($file);
                                                                    $previewIndex = $previewFiles->search(fn ($previewFile) => $previewFile->id === $file->id);
                                                                @endphp
                                                                <div class="work-thumb work-thumb-image"
                                                                    title="Visualizar {{ $file->file_name }}">
                                                                    <button type="button" class="work-thumb-preview"
                                                                        data-work-preview-open
                                                                        data-preview-modal="#{{ $modalScope }}-preview-modal"
                                                                        data-preview-carousel="#{{ $modalScope }}-preview-carousel"
                                                                        data-preview-index="{{ $previewIndex }}">
                                                                        @if ($hasPreviewImage)
                                                                            <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                                                alt="{{ $file->file_name }}" loading="lazy"
                                                                                onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                                            <span class="work-thumb-placeholder d-none">SEM IMAGEM</span>
                                                                        @else
                                                                            <span class="work-thumb-placeholder">SEM IMAGEM</span>
                                                                        @endif
                                                                        <span class="work-thumb-name">{{ $file->file_name }}</span>
                                                                    </button>
                                                                    <a class="work-thumb-download"
                                                                        href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                        title="Baixar arquivo">
                                                                        <i class="ri-download-2-line"></i>
                                                                    </a>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($documentFiles->isNotEmpty())
                                                        <div class="work-file-list">
                                                            @foreach ($documentFiles as $file)
                                                            <div class="work-file-row">
                                                                <div class="work-file-main">
                                                                    <i class="{{ FileIcon::getIcon($file->ext)->icon }} work-file-icon"></i>
                                                                    <div>
                                                                        <strong>{{ $file->file_name }}</strong>
                                                                        <small>{{ mb_strtoupper($file->ext) }}</small>
                                                                    </div>
                                                                </div>
                                                                <div class="work-file-actions">
                                                                    <a class="btn btn-sm btn-outline-primary"
                                                                        href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                        title="Baixar arquivo">
                                                                        <i class="ri-download-2-line"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="work-empty-state">
                                        <i class="ri-folder-open-line"></i>
                                        <strong>NENHUM ARQUIVO ANEXADO</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <div class="card work-card h-100">
                                    <h5 class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        OBSERVAÇÕES
                                    </h5>
                                    <div class="card-body">
                                        @if (!trim($form->observation))
                                            <div class="work-empty-state small-state">NENHUMA OBSERVAÇÃO</div>
                                        @else
                                            <div class="work-rich-text">{!! $form->observation !!}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card work-card h-100">
                                    <h5 class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        EQUIPAMENTOS
                                    </h5>
                                    <div class="card-body">
                                        @if (!$form->Equipment || !$form->Equipment->count())
                                            <div class="work-empty-state small-state">
                                                NENHUM EQUIPAMENTO INSTALADO INFORMADO
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center">Tipo</th>
                                                            <th class="text-center">Patrimonio</th>
                                                            <th class="text-center">Movimento</th>
                                                            <th class="text-center">Fases</th>
                                                            <th class="text-center">Poste RF</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($form->Equipment as $equip)
                                                            <tr>
                                                                <td class="text-center">{{ $equip->type }}</td>
                                                                <td class="text-center">{{ $equip->patrimony }}</td>
                                                                <td class="text-center">
                                                                    @if ($equip->installed)
                                                                        <i class="ri-arrow-right-line fs-3 text-success"></i>
                                                                    @else
                                                                        <i class="ri-arrow-left-line fs-3 text-danger"></i>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">{{ $equip->fases }}</td>
                                                                <td class="text-center">{{ $equip->pole }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <div class="card work-card h-100">
                                    <h5 class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        INFORMAÇÃO DE DANOS
                                    </h5>
                                    <div class="card-body">
                                        @if (!trim($form->description))
                                            <div class="work-empty-state small-state">NENHUMA INFORMAÇÃO</div>
                                        @else
                                            <div class="work-rich-text">{!! $form->description !!}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card work-card h-100">
                                    <h5 class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        MEDIDORES
                                    </h5>
                                    <div class="card-body">
                                        @if (!$form->Meeters->count())
                                            <div class="work-empty-state small-state">
                                                NENHUM MEDIDOR INSTALADO INFORMADO
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center">Medidor</th>
                                                            <th class="text-center">Borne</th>
                                                            <th class="text-center">Fases</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($form->Meeters as $meeter)
                                                            <tr>
                                                                <td class="text-center">{{ $meeter->number }}</td>
                                                                <td class="text-center">{{ $meeter->borne }}</td>
                                                                <td class="text-center">{{ $meeter->fases }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($form && $previewFiles->isNotEmpty())
        <div class="modal fade work-preview-modal" id="{{ $modalScope }}-preview-modal"
            tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">VISUALIZAÇÃO RÁPIDA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div id="{{ $modalScope }}-preview-carousel" class="carousel slide"
                            data-bs-interval="false">
                            <div class="carousel-inner">
                                @foreach ($previewFiles as $file)
                                    @php
                                        $hasPreviewImage = $canPreviewImage($file);
                                    @endphp
                                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                        <div class="work-preview-stage">
                                            @if ($hasPreviewImage)
                                                <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                    alt="{{ $file->file_name }}"
                                                    onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                <div class="work-preview-placeholder d-none">SEM IMAGEM</div>
                                            @else
                                                <div class="work-preview-placeholder">SEM IMAGEM</div>
                                            @endif
                                        </div>
                                        <div class="work-preview-caption">
                                            <strong>{{ $file->file_name }}</strong>
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('files.download', ['file' => $file->id]) }}">
                                                <i class="ri-download-2-line me-1"></i>Baixar
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($previewFiles->count() > 1)
                                <button class="carousel-control-prev" type="button"
                                    data-bs-target="#{{ $modalScope }}-preview-carousel"
                                    data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button"
                                    data-bs-target="#{{ $modalScope }}-preview-carousel"
                                    data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Próxima</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@pushOnce('css')
    <style>
        .work-show-modal {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .work-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .work-summary-item,
        .work-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        }

        .work-summary-item {
            background: #fff;
            padding: .85rem 1rem;
        }

        .work-summary-item span,
        .work-info-row span,
        .work-file-row small {
            display: block;
            color: #6c757d;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .work-summary-item strong {
            display: block;
            margin-top: .2rem;
            color: #212529;
            font-size: .98rem;
            overflow-wrap: anywhere;
        }

        .work-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .work-info-row {
            min-height: 64px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fbfbfc;
            padding: .65rem .75rem;
        }

        .work-info-row strong {
            display: block;
            margin-top: .25rem;
            overflow-wrap: anywhere;
        }

        .work-file-tabs {
            gap: .5rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: .75rem;
        }

        .work-file-tabs .nav-link {
            border: 1px solid #d9dee3;
            border-radius: 8px;
            color: #495057;
            font-weight: 700;
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        .work-file-tabs .nav-link.active {
            background: #1f6f54;
            border-color: #1f6f54;
            color: #fff;
        }

        .work-thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(136px, 1fr));
            gap: .75rem;
        }

        .work-thumb {
            border: 1px solid #e1e5ea;
            border-radius: 8px;
            background: #fff;
            padding: .45rem;
            position: relative;
            text-align: left;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .work-thumb-preview {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0;
            text-align: left;
        }

        .work-thumb:hover {
            border-color: #1f6f54;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
            transform: translateY(-1px);
        }

        .work-thumb img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            border-radius: 6px;
            background: #f1f3f5;
        }

        .work-thumb-placeholder {
            display: grid;
            place-items: center;
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 6px;
            background: #e9ecef;
            color: #6c757d;
            font-size: .78rem;
            font-weight: 800;
            text-align: center;
        }

        .work-thumb-name {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            min-height: 2.5em;
            margin-top: .45rem;
            overflow: hidden;
            color: #343a40;
            font-size: .78rem;
            font-weight: 700;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .work-thumb-download {
            display: grid;
            place-items: center;
            position: absolute;
            top: .65rem;
            right: .65rem;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 8px;
            background: rgba(33, 37, 41, .78);
            color: #fff;
            font-size: 1rem;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }

        .work-thumb-download:hover {
            background: #1f6f54;
            color: #fff;
            transform: translateY(-1px);
        }

        .work-file-list {
            display: grid;
            gap: .5rem;
        }

        .work-file-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            min-height: 58px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            padding: .55rem .65rem;
        }

        .work-file-main {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .work-file-main strong {
            display: block;
            color: #212529;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .work-file-icon {
            color: #1f6f54;
            flex: 0 0 auto;
            font-size: 1.75rem;
        }

        .work-file-actions {
            display: flex;
            gap: .35rem;
            flex: 0 0 auto;
        }

        .work-empty-state {
            display: grid;
            place-items: center;
            min-height: 120px;
            border: 1px dashed #cfd6dd;
            border-radius: 8px;
            background: #fff;
            color: #6c757d;
            text-align: center;
            padding: 1rem;
        }

        .work-empty-state i {
            font-size: 2rem;
        }

        .work-empty-state.small-state {
            min-height: 96px;
            font-weight: 700;
        }

        .work-rich-text {
            overflow-wrap: anywhere;
        }

        .work-preview-stage {
            display: grid;
            place-items: center;
            min-height: 70vh;
            background: #111827;
        }

        .work-preview-stage img {
            display: block;
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }

        .work-preview-placeholder {
            display: grid;
            place-items: center;
            width: min(620px, 90%);
            min-height: 360px;
            border: 1px dashed #6b7280;
            border-radius: 8px;
            background: #374151;
            color: #f8f9fa;
            font-size: 1.25rem;
            font-weight: 800;
            text-align: center;
        }

        .work-preview-caption {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            background: #fff;
        }

        .work-preview-caption strong {
            overflow-wrap: anywhere;
        }

        @media (max-width: 992px) {
            .work-summary-grid,
            .work-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .work-summary-grid,
            .work-info-grid {
                grid-template-columns: 1fr;
            }

            .work-file-row,
            .work-preview-caption {
                align-items: stretch;
                flex-direction: column;
            }

            .work-file-actions {
                justify-content: flex-end;
            }
        }
    </style>
@endpushOnce

@pushOnce('script')
    <script>
        function restoreWorkFileTabs(root) {
            if (typeof bootstrap === 'undefined') {
                return;
            }

            (root || document).querySelectorAll('[data-work-files]').forEach(function (manager) {
                const scope = manager.getAttribute('data-work-files');
                const storedTab = sessionStorage.getItem('work-files-active-tab:' + scope);
                const button = storedTab ? document.getElementById(storedTab) : null;

                if (button && manager.contains(button)) {
                    bootstrap.Tab.getOrCreateInstance(button).show();
                    return;
                }

                const firstButton = manager.querySelector('[data-bs-toggle="pill"]');
                if (firstButton) {
                    bootstrap.Tab.getOrCreateInstance(firstButton).show();
                }
            });
        }

        document.addEventListener('shown.bs.tab', function (event) {
            const manager = event.target.closest('[data-work-files]');
            if (!manager || !event.target.id) {
                return;
            }

            sessionStorage.setItem('work-files-active-tab:' + manager.getAttribute('data-work-files'), event.target.id);
        });

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-work-preview-open]');
            if (!trigger || typeof bootstrap === 'undefined') {
                return;
            }

            const modalSelector = trigger.getAttribute('data-preview-modal');
            const carouselSelector = trigger.getAttribute('data-preview-carousel');
            const index = parseInt(trigger.getAttribute('data-preview-index') || '0', 10);
            const modal = document.querySelector(modalSelector);
            const carousel = document.querySelector(carouselSelector);

            if (!modal || !carousel) {
                return;
            }

            bootstrap.Carousel.getOrCreateInstance(carousel, {
                interval: false,
                ride: false,
                touch: true
            }).to(index);

            bootstrap.Modal.getOrCreateInstance(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            }).show();
        });

        document.addEventListener('livewire:load', function () {
            restoreWorkFileTabs(document);

            if (window.Livewire && Livewire.hook) {
                Livewire.hook('message.processed', function () {
                    restoreWorkFileTabs(document);
                });
            }
        });

        document.addEventListener('hidden.bs.modal', function () {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        });

        // Corrige z-index quando a obra ou o preview são abertos dentro de outro modal
        // (ex.: viabilityDetailModal no search). nth-of-type é frágil pois depende da
        // quantidade total de backdrops na página — calculamos dinamicamente.
        document.addEventListener('show.bs.modal', function (event) {
            const target = event.target;
            if (target.id !== 'modal_form_work' && !target.classList.contains('work-preview-modal')) {
                return;
            }
            const openCount = document.querySelectorAll('.modal.show').length;
            if (openCount === 0) return;

            const newZ = 1055 + openCount * 10;
            target.style.zIndex = newZ;

            setTimeout(function () {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                const last = backdrops[backdrops.length - 1];
                if (last) last.style.zIndex = newZ - 5;
            }, 0);
        });
    </script>
@endpushOnce
