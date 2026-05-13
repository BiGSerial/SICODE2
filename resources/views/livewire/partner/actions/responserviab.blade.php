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

    <div wire:ignore.self class="modal fade" id="modal_resp_viability" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="modal-title fw-bold">VIABILIDADE</h4>
                </div>

                <div class="modal-body">
                    @if ($viability)
                        @php
                            $status = null;

                            $dueDate = \Carbon\Carbon::parse($viability->sended_at)->addDays($viability->getDays() + 7);
                            $today = \Carbon\Carbon::now();
                            $daysDifference = 0;

                            if ($dueDate) {
                                $daysDifference = $today->diffInDays($dueDate);
                                if ($dueDate->isBefore($today)) {
                                    $daysDifference *= -1;
                                }

                                if ($daysDifference < 1) {
                                    $status = ['color' => 'text-bg-danger', 'info' => 'VENCIDO'];
                                } elseif ($daysDifference < 3) {
                                    $status = ['color' => 'text-bg-warning', 'info' => 'VENCENDO'];
                                } else {
                                    $status = ['color' => 'text-bg-success', 'info' => 'NO PRAZO'];
                                }
                            }

                            $color = 'grey';
                            $days = new \App\Helpers\DaysLeft($viability->Note);
                            $days_left = $days->getDaysLeft();
                            $count = 0;
                            $files = $viability->Note->Files ?? collect();
                            $groupedFiles = $files
                                ->groupBy(fn($file) => mb_strtoupper($file->service->service ?? 'OUTROS'))
                                ->sortKeys();
                            $previewFiles = $files->sortBy('file_name')->values();
                            $modalScope = 'viab-' . $viability->id;

                            if ($viability->approved) {
                                $count++;
                                $color = 'green';
                            } elseif ($viability->rejected) {
                                $count++;
                                $color = 'red';
                            }

                            if (($viability->rejected || $viability->approved) && !$viability->completed) {
                                $status = ['color' => 'text-bg-primary', 'info' => 'EM AVALIAÇÃO'];
                            }
                        @endphp

                        <div class="row g-3">

                            {{-- INFORMAÇÕES GERAIS --}}
                            <div class="col-md-6">
                                <div class="card work-card h-100">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                        INFORMAÇÕES GERAIS</div>
                                    <div class="card-body">
                                        <div class="work-info-grid">
                                            <div class="work-info-row">
                                                <span>NOTA/OV</span>
                                                <strong>{{ $viability->Note->note }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>ORDENS</span>
                                                @php
                                                    $orders = $viability->Note->orders->reject(function ($order) {
                                                        return str_starts_with($order->statusSist, 'ENT') ||
                                                            str_starts_with($order->statusSist, 'ENC');
                                                    });
                                                @endphp
                                                <strong>
                                                    @forelse($orders as $order)
                                                        <span class="badge text-bg-light border me-1 mb-1">{{ $order->ordem }}</span>
                                                    @empty
                                                        ---
                                                    @endforelse
                                                </strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>CLIENTE</span>
                                                <strong>{{ mb_strtoupper($viability->Note->client) }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>DESCRIÇÃO</span>
                                                <strong>{{ mb_strtoupper($viability->Note->material) }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>RÚBRICA</span>
                                                <strong>{{ mb_strtoupper($viability->Note->rubrica) }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>CRITICIDADE</span>
                                                <strong>{{ mb_strtoupper($viability->Note->txpriority) }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>MUNICÍPIO</span>
                                                <strong>{{ mb_strtoupper($viability->Note->lexp) }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>STATUS VIABILIDADE</span>
                                                <strong>
                                                @if ($viability->approved && !$viability->rejected)
                                                    <span class="text-success">APROVADO</span>
                                                @elseif (!$viability->approved && $viability->rejected)
                                                    <span class="text-danger">REJEITADO</span>
                                                @else
                                                    <span class="text-muted">DESCONHECIDO</span>
                                                @endif
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- STATUS E DATAS --}}
                            <div class="col-md-6">
                                <div class="card work-card h-100">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">STATUS E
                                        DATAS</div>
                                    <div class="card-body p-3">
                                        <div class="work-info-grid">
                                            <div class="work-info-row">
                                                <span>VIABILIZADO EM</span>
                                                <strong>{{ $viability->returned_at?->format('d/m/Y H:i:s') }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>PRAZO VIABILIDADE</span>
                                                <strong class="text-danger">{{ $dueDate ? $dueDate->format('d/m/Y') : '---' }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>PRAZO DA OBRA</span>
                                                <strong class="text-primary">
                                                    @php
                                                        $daysLeft = new \App\Helpers\DaysLeft($viability->Note);
                                                    @endphp
                                                    {{ $daysLeft->getLastDate() }}
                                                </strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>STATUS</span>
                                                <strong>
                                                @php $st = \App\Custom\Viabilitiesstatus::status($viability->status); @endphp
                                                <span class="badge {{ $st->colorbg }}">{{ $st->status }}</span>
                                                </strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>CONTRATAÇÃO</span>
                                                <strong>
                                                @if ($viability->hired)
                                                    <span class="text-success">CONTRATADO</span>
                                                @else
                                                    <span class="text-danger">NÃO CONTRATADO</span>
                                                @endif
                                                </strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>CONTRATADO EM</span>
                                                <strong>{{ $viability->hired_at ? $viability->hired_at->format('d/m/Y H:i:s') : '---' }}</strong>
                                            </div>
                                            <div class="work-info-row">
                                                <span>PROGRAMADOR RESPONSÁVEL</span>
                                                <strong>
                                                @if ($viability->Engineer)
                                                    <span
                                                        class="fw-bold text-primary">{{ $viability->Engineer->name }}</span>
                                                    ({{ $viability->Engineer->email }})
                                                @endif
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RETORNO DA VIABILIDADE --}}
                            @if ($viability->Form)
                                <div class="col-md-6">
                                    <div class="card work-card h-100">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                            RETORNO VIABILIDADE</div>
                                        <div class="card-body">
                                            <div class="work-info-grid work-info-grid-return">
                                                <div class="work-info-row">
                                                    <span>MOTIVO</span>
                                                    <strong>{{ $viability->Form->reason }}</strong>
                                                </div>
                                                <div class="work-info-row">
                                                    <span>IMPACTO</span>
                                                    <strong>{{ $viability->Form->changes * 10 }}%</strong>
                                                </div>
                                                <div class="work-info-row">
                                                    <span>RESPONSÁVEL</span>
                                                    <strong>{{ mb_strtoupper($viability->Form->responsible) }}</strong>
                                                </div>
                                                <div class="work-info-row work-info-row-full">
                                                    <span>DESCRIÇÃO</span>
                                                    <strong>{{ $viability->Form->description }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- COMENTÁRIOS --}}
                            @if ($viability->comments->isNotEmpty())
                                <div class="col-6">
                                    <div class="card work-card">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                            COMENTÁRIOS VIABILIDADE</div>
                                        <div class="card-body">
                                            <ul class="list-group">
                                                @foreach ($viability->comments->sortByDesc('created_at') as $index => $comment)
                                                    <li
                                                        class="list-group-item d-flex justify-content-between align-items-start shadow mb-2">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold mb-2 border-bottom border-success">
                                                                #{{ $viability->comments->count() - $index }}
                                                                {{ $comment->User->name }}
                                                            </div>
                                                            {!! $comment->message !!}
                                                        </div>
                                                        <span
                                                            class="badge bg-light text-dark">{{ date('d/m/Y H:i', strtotime($comment->created_at)) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- ARQUIVOS ANEXADOS --}}
                            <div class="col-12">
                                <div class="card work-card">
                                    <div
                                        class="card-header py-2 my-0 edp-bg-sprucegreen-70 text-edp-verde d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <h5 class="my-0 fw-bold">ARQUIVOS ANEXADOS</h5>
                                        @if ($files->isNotEmpty())
                                            <span class="badge text-bg-light">{{ $files->count() }} arquivo(s)</span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        @if ($files->isNotEmpty())
                                            <div class="work-files" data-work-files="{{ $modalScope }}"
                                                wire:ignore.self>
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
                                                                ->filter(fn($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                                                                ->values();
                                                            $documentFiles = $serviceFiles
                                                                ->reject(fn($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                                                                ->values();
                                                        @endphp
                                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                            id="{{ $paneId }}" role="tabpanel" tabindex="0">
                                                            @if ($imageFiles->isNotEmpty())
                                                                <div class="work-thumb-grid mb-3">
                                                                    @foreach ($imageFiles as $file)
                                                                    @php
                                                                        $hasPreviewImage = $canPreviewImage($file);
                                                                        $previewIndex = $previewFiles->search(fn($previewFile) => $previewFile->id === $file->id);
                                                                    @endphp
                                                                    <div class="work-thumb work-thumb-image"
                                                                        title="Visualizar {{ $file->file_name }}">
                                                                        <button type="button"
                                                                            class="work-thumb-preview"
                                                                            data-work-preview-open
                                                                            data-preview-modal="#{{ $modalScope }}-preview-modal"
                                                                            data-preview-carousel="#{{ $modalScope }}-preview-carousel"
                                                                            data-preview-index="{{ $previewIndex }}">
                                                                            @if ($hasPreviewImage)
                                                                                <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                                                    alt="{{ $file->file_name }}"
                                                                                    loading="lazy"
                                                                                    onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                                                <span
                                                                                    class="work-thumb-placeholder d-none">SEM
                                                                                    IMAGEM</span>
                                                                            @else
                                                                                <span class="work-thumb-placeholder">SEM
                                                                                    IMAGEM</span>
                                                                            @endif
                                                                            <span
                                                                                class="work-thumb-name">{{ $file->file_name }}</span>
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
                                                                                <i
                                                                                    class="{{ FileIcon::getIcon($file->ext)->icon }} work-file-icon"></i>
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
                            </div>

                            @if ($viability->tacit && $viability->files->isEmpty())
                                <div class="mb-3">
                                    @livewire('files.manager.create-viab-files', ['viability' => $viability, 'service' => 'VIABILIDADE'], key('files_forms'))
                                </div>
                            @endif

                            {{-- RESPONDER ATIVIDADE --}}
                            @if ($viability->treplica && $viability->status == 5)
                                <div class="col-12">
                                    <div class="card work-card">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                            RESPONDER ATIVIDADE</div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Decisão</label>
                                                    <select class="form-select form-select-sm border border-secondary"
                                                        wire:model.defer="decision">
                                                        @foreach (\App\Helpers\SelectOptions::getResponserOptions() as $options)
                                                            <option @once selected @endonce
                                                                value="{{ $options->value }}">{{ $options->info }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">Texto Descritivo</label>
                                                    <textarea class="form-control border border-secondary" rows="3" wire:model.defer="responser"></textarea>
                                                </div>
                                                <div class="d-flex justify-content-end mt-3">
                                                    <button class="btn btn-sm btn-danger"
                                                        wire:click="toResponser()">ENVIAR</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div> {{-- /row --}}

                        @if ($previewFiles->isNotEmpty())
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
                                                            $isPreviewImage = in_array(mb_strtolower($file->ext), $imageExtensions, true);
                                                            $hasPreviewImage = $canPreviewImage($file);
                                                        @endphp
                                                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                                            <div class="work-preview-stage">
                                                                @if ($isPreviewImage && $hasPreviewImage)
                                                                    <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                                        alt="{{ $file->file_name }}"
                                                                        onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                                    <div class="work-preview-placeholder d-none">SEM IMAGEM
                                                                    </div>
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
                                                        <span class="carousel-control-prev-icon"
                                                            aria-hidden="true"></span>
                                                        <span class="visually-hidden">Anterior</span>
                                                    </button>
                                                    <button class="carousel-control-next" type="button"
                                                        data-bs-target="#{{ $modalScope }}-preview-carousel"
                                                        data-bs-slide="next">
                                                        <span class="carousel-control-next-icon"
                                                            aria-hidden="true"></span>
                                                        <span class="visually-hidden">Próxima</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                @if ($viability?->tacit && $viability?->files->isEmpty())
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"
                            wire:click="$emitTo('files.manager.create-viab-files', 'saveFiles')">Salvar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@pushOnce('css')
    <style>
        .work-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        }

        .work-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .work-info-grid-return {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .work-info-row-full {
            grid-column: 1 / -1;
        }

        .work-info-row {
            min-height: 64px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fbfbfc;
            padding: .65rem .75rem;
        }

        .work-info-row span,
        .work-file-row small {
            display: block;
            color: #6c757d;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .work-info-row strong {
            display: block;
            margin-top: .25rem;
            color: #212529;
            overflow-wrap: anywhere;
            line-height: 1.25;
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

        .work-file-row small {
            display: block;
            color: #6c757d;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
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

        .work-preview-modal {
            z-index: 1065;
        }

        .modal-backdrop.show:nth-of-type(2) {
            z-index: 1060;
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

        @media (max-width: 576px) {
            .work-info-grid {
                grid-template-columns: 1fr;
            }

            .work-info-grid-return {
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

            (root || document).querySelectorAll('[data-work-files]').forEach(function(manager) {
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

        document.addEventListener('shown.bs.tab', function(event) {
            const manager = event.target.closest('[data-work-files]');
            if (!manager || !event.target.id) {
                return;
            }

            sessionStorage.setItem('work-files-active-tab:' + manager.getAttribute('data-work-files'), event.target.id);
        });

        document.addEventListener('click', function(event) {
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

        document.addEventListener('livewire:load', function() {
            restoreWorkFileTabs(document);

            if (window.Livewire && Livewire.hook) {
                Livewire.hook('message.processed', function() {
                    restoreWorkFileTabs(document);
                });
            }
        });

        document.addEventListener('hidden.bs.modal', function() {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        });
    </script>
@endpushOnce
