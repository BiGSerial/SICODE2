<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade five-modal" id="finishD5Modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content five-shell">
                <div class="modal-header five-hero">
                    <div>
                        <h5 class="modal-title mb-1">
                            <i class="ri-check-double-line me-1"></i>
                            Conclusao da D5 - {{ $five?->note_d5 ?: 'Numero nao gerado' }}
                        </h5>
                        <div class="five-hero-meta">
                            <span><i class="ri-file-list-3-line me-1"></i>Nota {{ $five?->note?->note ?: '---' }}</span>
                            <span><i class="ri-time-line me-1"></i>{{ optional($five?->dispatch_at)->format('d/m/Y H:i') ?: '---' }}</span>
                            @if ($five?->isPassive)
                                <span class="badge text-bg-info">Passivo</span>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"
                        wire:click="clearAll"></button>
                </div>

                <div class="modal-body five-body">
                    @if ($five)
                        @php
                            $orderedProductions = ($five->productions ?? collect())->sortByDesc(function ($p) {
                                return $p->completed_at ?? $p->created_at;
                            });
                            $files = $five->EvidenceFiles ?? collect();
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
                            $imageFiles = $files->filter(function ($file) use ($imageExtensions) {
                                $ext = strtolower(pathinfo($file->original_name ?: $file->path ?: '', PATHINFO_EXTENSION));
                                return in_array($ext, $imageExtensions, true);
                            })->values();
                            $otherFiles = $files->filter(function ($file) use ($imageFiles) {
                                return !$imageFiles->contains('id', $file->id);
                            });
                        @endphp

                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <section class="five-panel">
                                    <h6 class="five-title"><i class="ri-layout-grid-line me-1"></i>Dados da D5</h6>
                                    <div class="five-grid">
                                        <div class="five-field">
                                            <div class="five-k">Local instalacao</div>
                                            <div class="five-v">{{ $five->loc_install ?: '---' }}</div>
                                        </div>
                                        <div class="five-field">
                                            <div class="five-k">Conjunto</div>
                                            <div class="five-v">{{ $five->conjunto ?: '---' }}</div>
                                        </div>
                                        <div class="five-field">
                                            <div class="five-k">PEP</div>
                                            <div class="five-v">{{ $five->pep ?: '---' }}</div>
                                        </div>
                                        <div class="five-field">
                                            <div class="five-k">Empresa</div>
                                            <div class="five-v">{{ $five->company?->name ?: '---' }}</div>
                                        </div>
                                        <div class="five-field">
                                            <div class="five-k">Motivo</div>
                                            <div class="five-v">{{ $five->reason ?: '---' }}</div>
                                        </div>
                                        <div class="five-field">
                                            <div class="five-k">Codificacao</div>
                                            <div class="five-v">{{ $five->codify ?: '---' }}</div>
                                        </div>
                                    </div>

                                    <div class="five-details mt-3">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                            <div class="five-k mb-0">Detalhes</div>
                                            @if ($five->isPassive)
                                                <button type="button" class="btn btn-sm btn-outline-info five-inline-btn"
                                                    wire:click="startEditDescription">
                                                    <i class="ri-edit-2-line me-1"></i>Editar
                                                </button>
                                            @endif
                                        </div>
                                        <div class="five-note-box">{{ $five->description ?: '---' }}</div>
                                    </div>
                                </section>

                                @if ($five->isPassive && $editingDescription)
                                    <section class="five-panel mt-3 five-passive">
                                        <h6 class="five-title"><i class="ri-tools-line me-1"></i>Edicao do passivo</h6>
                                        <label class="five-k mb-1" for="description">Detalhes</label>
                                        <textarea id="description" class="form-control five-field-input" rows="3" wire:model.defer="five.description"></textarea>
                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            <button class="btn btn-outline-secondary" wire:click="cancelEditDescription">Fechar</button>
                                            <button class="btn btn-outline-info" wire:click="savePassiveDetails">Salvar detalhes</button>
                                        </div>
                                    </section>
                                @endif

                                <section class="five-panel mt-3">
                                    <h6 class="five-title"><i class="ri-attachment-2 me-1"></i>Evidencias anexadas</h6>
                                    @if ($files->isEmpty())
                                        <div class="five-empty">Nenhum arquivo de evidencia anexado.</div>
                                    @else
                                        @if ($imageFiles->isNotEmpty())
                                            <div class="five-gallery-grid">
                                                @foreach ($imageFiles as $index => $file)
                                                    <div class="five-thumb-card">
                                                        <button type="button" class="five-thumb-trigger"
                                                            data-bs-toggle="modal" data-bs-target="#finishD5GalleryModal"
                                                            data-index="{{ $index }}">
                                                            <img src="{{ asset('storage/' . $file->path) }}"
                                                                alt="{{ $file->original_name ?: 'Evidencia' }}"
                                                                loading="lazy">
                                                        </button>
                                                        <a href="{{ asset('storage/' . $file->path) }}"
                                                            download="{{ $file->original_name ?: basename($file->path) }}"
                                                            class="five-thumb-download" title="Download">
                                                            <i class="ri-download-2-line"></i>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($otherFiles->isNotEmpty())
                                            <div class="five-file-list mt-2">
                                                @foreach ($otherFiles as $file)
                                                    <div class="five-file-item">
                                                        <i class="ri-attachment-2"></i>
                                                        <span>{{ $file->original_name ?: basename($file->path) }}</span>
                                                        <a href="{{ asset('storage/' . $file->path) }}"
                                                            download="{{ $file->original_name ?: basename($file->path) }}"
                                                            class="five-download-link" title="Download">
                                                            <i class="ri-download-2-line"></i>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif

                                    <div class="mt-3">
                                        @livewire('files.evidence.upload-evidence', ['five' => $five, 'type' => 'D5', 'origin' => 'EMPREITEIRA'], key('finish-d5-evidence-' . $five->id . '-' . $evidenceKey))
                                    </div>
                                </section>
                            </div>

                            <div class="col-12 col-lg-4">
                                <section class="five-panel five-panel--conclusion h-100">
                                    <h6 class="five-title"><i class="ri-check-line me-1"></i>Concluir D5</h6>

                                    <div class="five-details">
                                        <label for="responsibleName" class="five-k mb-1">
                                            Responsavel pela informacao <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control five-field-input @error('five.name') is-invalid @enderror"
                                            id="responsibleName" wire:model.bounce.1s="five.name"
                                            placeholder="Digite o nome do responsavel">
                                        @error('five.name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="five-details mt-3">
                                        <label for="observations" class="five-k mb-1">Observacoes</label>
                                        <textarea class="form-control five-field-input @error('observations') is-invalid @enderror" id="observations"
                                            wire:model.defer="observations" rows="4" placeholder="Digite as observacoes"></textarea>
                                        @error('observations')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="five-info mt-3">
                                        <ul class="mb-0 ps-3">
                                            <li>Ao encerrar, registraremos a data/hora da conclusao.</li>
                                            <li>As evidencias anexadas ficam vinculadas a esta D5.</li>
                                        </ul>
                                    </div>

                                    <div class="five-details mt-3">
                                        <div class="five-k mb-2">Historico de conclusoes</div>
                                        @forelse ($orderedProductions as $production)
                                            <article class="five-item">
                                                <div class="five-dot"></div>
                                                <div class="five-item-body">
                                                    <div class="five-item-head">
                                                        <span class="five-badge">{{ $production->service?->service ?? 'Servico' }}</span>
                                                        <span class="five-date">
                                                            {{ ($production->completed_at ?? $production->created_at)?->format('d/m/Y H:i') ?: '---' }}
                                                        </span>
                                                    </div>
                                                    <div class="five-line"><strong>Responsavel:</strong>
                                                        {{ $production->user?->name ?? '---' }}
                                                        @if ($production->User?->email)
                                                            <span class="teams-contact-icon ms-1" title="Entrar em contato"
                                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $production->User?->email }}', '_blank')">
                                                                <i class="bx bxl-microsoft-teams align-middle"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="five-line"><strong>Resultado:</strong>
                                                        {{ $production->analise?->conclusion ?? 'Nao informado' }}
                                                    </div>
                                                    @if (!empty($production->analise?->info))
                                                        <div class="five-subnote">{!! nl2br(e($production->analise?->info)) !!}</div>
                                                    @endif
                                                </div>
                                            </article>
                                        @empty
                                            <div class="five-empty">Sem historico de conclusoes.</div>
                                        @endforelse
                                    </div>
                                </section>
                            </div>
                        </div>
                    @else
                        <div class="five-empty text-center">Nenhuma informacao carregada.</div>
                    @endif
                </div>

                <div class="modal-footer five-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click="clearAll">Cancelar</button>
                    <button class="btn btn-success" wire:click="finishD5" @disabled($isSaving)
                        wire:loading.attr="disabled" wire:target="finishD5,toSave">
                        <i class="ri-checkbox-circle-line me-1"></i>Encerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $galleryImageFiles = collect();
        if ($five) {
            $galleryImageFiles = ($five->EvidenceFiles ?? collect())->filter(function ($file) {
                $ext = strtolower(pathinfo($file->original_name ?: $file->path ?: '', PATHINFO_EXTENSION));
                return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
            })->values();
        }
    @endphp

    <div wire:ignore.self class="modal fade five-gallery-modal" id="finishD5GalleryModal" tabindex="-1"
        aria-labelledby="finishD5GalleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content five-gallery-shell">
                <div class="modal-header">
                    <h5 class="modal-title" id="finishD5GalleryModalLabel">Galeria de evidencias</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($galleryImageFiles->isNotEmpty())
                        <div id="finishD5GalleryCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                            <div class="carousel-inner">
                                @foreach ($galleryImageFiles as $index => $file)
                                    <div class="carousel-item @if ($index === 0) active @endif">
                                        <div class="five-gallery-image-wrap">
                                            <img src="{{ asset('storage/' . $file->path) }}" class="d-block w-100"
                                                alt="{{ $file->original_name ?: 'Evidencia' }}">
                                        </div>
                                        <div class="five-gallery-caption">
                                            <span>{{ $file->original_name ?: basename($file->path) }}</span>
                                            <a href="{{ asset('storage/' . $file->path) }}"
                                                download="{{ $file->original_name ?: basename($file->path) }}"
                                                class="five-gallery-download" title="Download">
                                                <i class="ri-download-2-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#finishD5GalleryCarousel"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#finishD5GalleryCarousel"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Proximo</span>
                            </button>
                        </div>
                    @else
                        <div class="five-empty text-center">Sem imagens para exibir.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .five-modal .modal-dialog {
            max-width: min(1400px, 96vw);
            margin: 1rem auto;
        }

        .five-modal .five-shell {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            background: #f3f6fb;
            box-shadow: 0 24px 55px rgba(15, 23, 42, .28);
        }

        .five-modal .five-hero {
            border: 0;
            color: #f8fafc;
            background: linear-gradient(130deg, #0f172a 0%, #0f766e 68%, #0b4d4a 100%);
        }

        .five-modal .five-hero-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: .85rem;
            opacity: .92;
        }

        .five-modal .five-body {
            padding: 1rem;
            background:
                radial-gradient(circle at 10% 0%, rgba(226, 232, 240, .8), transparent 45%),
                radial-gradient(circle at 90% 100%, rgba(204, 251, 241, .65), transparent 50%),
                #f3f6fb;
        }

        .five-modal .five-panel {
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            padding: 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .five-modal .five-panel--conclusion {
            background: linear-gradient(180deg, #f0fdf4 0%, #f7fee7 100%);
            border-color: #86efac;
            box-shadow: 0 12px 28px rgba(22, 163, 74, .14);
        }

        .five-modal .five-title {
            margin: 0 0 .75rem;
            font-size: .9rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #334155;
            font-weight: 700;
        }

        .five-modal .five-grid {
            display: grid;
            gap: .65rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .five-modal .five-field {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .55rem .65rem;
            background: #f8fafc;
        }

        .five-modal .five-k {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            font-weight: 700;
        }

        .five-modal .five-v {
            color: #0f172a;
            font-weight: 600;
            margin-top: .15rem;
            word-break: break-word;
        }

        .five-modal .five-note-box {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: .7rem;
            background: #f8fafc;
            color: #334155;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .five-modal .five-inline-btn {
            border-radius: 999px;
            font-weight: 600;
        }

        .five-modal .five-passive {
            border-color: #fed7aa;
            background: linear-gradient(145deg, #fff7ed, #ffffff);
        }

        .five-modal .five-field-input {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
        }

        .five-modal .five-field-input:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 .2rem rgba(34, 197, 94, .14);
        }

        .five-modal .five-info {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            color: #475569;
            padding: .7rem;
            font-size: .9rem;
        }

        .five-modal .five-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: .5rem;
        }

        .five-modal .five-thumb-card {
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            overflow: hidden;
            background: #f8fafc;
        }

        .five-modal .five-thumb-trigger {
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
            overflow: hidden;
            cursor: pointer;
            aspect-ratio: 1 / 1;
            width: 100%;
            transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
        }

        .five-modal .five-thumb-trigger:hover {
            transform: translateY(-1px);
            border-color: #94a3b8;
            box-shadow: 0 10px 18px rgba(15, 23, 42, .14);
        }

        .five-modal .five-thumb-trigger img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .five-modal .five-thumb-download {
            display: flex;
            justify-content: center;
            align-items: center;
            border-top: 1px solid #dbe3ef;
            padding: .25rem 0;
            color: #0f172a;
            text-decoration: none;
            background: #fff;
        }

        .five-modal .five-thumb-download:hover {
            background: #eef2ff;
            color: #1d4ed8;
        }

        .five-modal .five-file-list {
            display: grid;
            gap: .45rem;
        }

        .five-modal .five-file-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: .45rem .55rem;
            color: #334155;
            background: #f8fafc;
            display: flex;
            align-items: center;
            gap: .45rem;
            min-width: 0;
        }

        .five-modal .five-file-item span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1 1 auto;
        }

        .five-modal .five-download-link {
            color: #0f172a;
            text-decoration: none;
            padding: .2rem;
            border-radius: 6px;
        }

        .five-modal .five-download-link:hover {
            background: #eef2ff;
            color: #1d4ed8;
        }

        .five-modal .five-item {
            position: relative;
            padding-left: 1rem;
            margin-bottom: .85rem;
        }

        .five-modal .five-item:last-child {
            margin-bottom: 0;
        }

        .five-modal .five-item::before {
            content: "";
            position: absolute;
            left: 4px;
            top: 0;
            bottom: -10px;
            width: 2px;
            background: #dbe3ef;
        }

        .five-modal .five-item:last-child::before {
            bottom: 14px;
        }

        .five-modal .five-dot {
            position: absolute;
            left: 0;
            top: 6px;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            border: 2px solid #fff;
            background: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .16);
        }

        .five-modal .five-item-body {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .7rem .75rem;
            background: #f8fafc;
        }

        .five-modal .five-item-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .35rem;
        }

        .five-modal .five-badge {
            display: inline-block;
            font-size: .74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #155e75;
            background: #ecfeff;
            border: 1px solid #bae6fd;
            border-radius: 999px;
            padding: .15rem .45rem;
        }

        .five-modal .five-date {
            font-size: .78rem;
            color: #64748b;
            white-space: nowrap;
        }

        .five-modal .five-line {
            color: #334155;
            line-height: 1.35;
            margin-bottom: .2rem;
        }

        .five-modal .five-subnote {
            margin-top: .45rem;
            border-top: 1px dashed #cbd5e1;
            padding-top: .45rem;
            color: #475569;
            font-size: .9rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .five-modal .teams-contact-icon {
            cursor: pointer;
            display: inline-block;
            transition: all .2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }

        .five-modal .teams-contact-icon:hover {
            background: rgba(0, 120, 212, .1);
            transform: scale(1.04);
        }

        .five-modal .five-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            color: #64748b;
            padding: .8rem;
        }

        .five-modal .five-footer {
            border-top: 1px solid #dbe3ef;
            background: #f8fafc;
        }

        .five-gallery-modal .modal-dialog {
            max-width: min(1200px, 95vw);
        }

        .five-gallery-modal .five-gallery-shell {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
            background: #0f172a;
            color: #e2e8f0;
            box-shadow: 0 24px 55px rgba(15, 23, 42, .4);
        }

        .five-gallery-modal .modal-header {
            border: 0;
            background: #0b1220;
        }

        .five-gallery-modal .modal-body {
            background: #020617;
            padding: .75rem;
        }

        .five-gallery-modal .five-gallery-image-wrap {
            width: 100%;
            min-height: 55vh;
            max-height: 72vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #020617;
        }

        .five-gallery-modal .five-gallery-image-wrap img {
            width: auto;
            max-width: 100%;
            max-height: 72vh;
            object-fit: contain;
        }

        .five-gallery-modal .five-gallery-caption {
            margin-top: .5rem;
            font-size: .9rem;
            color: #cbd5e1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: .5rem;
            min-width: 0;
        }

        .five-gallery-modal .five-gallery-caption span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .five-gallery-modal .five-gallery-download {
            color: #e2e8f0;
            text-decoration: none;
            border: 1px solid #334155;
            border-radius: 999px;
            padding: .1rem .45rem;
            flex: 0 0 auto;
        }

        .five-gallery-modal .five-gallery-download:hover {
            color: #7dd3fc;
            border-color: #7dd3fc;
        }

        .five-gallery-modal .carousel-control-prev,
        .five-gallery-modal .carousel-control-next {
            width: 10%;
        }

        @media (max-width: 991px) {
            .five-modal .five-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        (function() {
            if (window.__finishD5GalleryInit) {
                return;
            }
            window.__finishD5GalleryInit = true;

            document.addEventListener('click', function(event) {
                const trigger = event.target.closest('.five-thumb-trigger');
                if (!trigger) {
                    return;
                }
                const galleryEl = document.getElementById('finishD5GalleryCarousel');
                if (!galleryEl || !window.bootstrap) {
                    return;
                }
                const index = Number(trigger.getAttribute('data-index') || 0);
                const carousel = bootstrap.Carousel.getOrCreateInstance(galleryEl, {
                    interval: false
                });
                carousel.to(index);
            });

            const parentModalEl = document.getElementById('finishD5Modal');
            const childModalEl = document.getElementById('finishD5GalleryModal');
            if (!parentModalEl || !childModalEl) {
                return;
            }

            childModalEl.addEventListener('show.bs.modal', function() {
                parentModalEl.classList.add('five-modal-stacked');
            });

            childModalEl.addEventListener('hidden.bs.modal', function() {
                parentModalEl.classList.remove('five-modal-stacked');
                if (window.bootstrap && !parentModalEl.classList.contains('show')) {
                    const parentModal = bootstrap.Modal.getOrCreateInstance(parentModalEl);
                    parentModal.show();
                }
            });
        })();
    </script>
</div>
