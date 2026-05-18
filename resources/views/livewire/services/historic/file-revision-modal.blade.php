@php
    $modalId = 'fileRevisionModal-' . $production->id;
    $files = $files ?? collect();
    $previews = $previews ?? [];
    $nextName = $nextName ?? null;
    $selectedMeta = $selectedMeta ?? null;
@endphp

<div>
    <style>
        .revision-modal .modal-content {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #51606a;
            background: #2f2f2f;
            color: #e5e7eb;
        }

        .revision-modal .modal-header {
            background: #16586d;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .revision-modal .modal-body {
            background: #2f2f2f;
        }

        .revision-title-sm {
            color: #b6d8e3;
            font-size: .76rem;
            letter-spacing: .04em;
            font-weight: 700;
        }

        .revision-option {
            border: 1px solid #4b5563;
            border-radius: 10px;
            background: #353535;
            cursor: pointer;
            transition: .2s ease;
            padding: .8rem;
            margin-bottom: .55rem;
        }

        .revision-option:hover {
            border-color: #6b7280;
        }

        .revision-option.active {
            border-color: #d5e6ef;
            background: #d9e4eb;
            color: #123f54;
        }

        .revision-option .meta {
            font-size: .92rem;
            color: #d1d5db;
        }

        .revision-option.active .meta {
            color: #164e63;
        }

        .rev-badge {
            padding: .05rem .42rem;
            border-radius: .34rem;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-weight: 700;
            font-size: .82rem;
        }

        .revision-option.active .rev-badge {
            background: #134e63;
        }
    </style>

    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
        <i class="ri-upload-cloud-2-line"></i> Revisar
    </button>

    <div wire:ignore.self class="modal fade revision-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header text-white">
                    <div>
                        <h5 class="modal-title mb-0">Revisão de arquivos</h5>
                        <small class="opacity-75">{{ $production->Note->note }} - Produção #{{ $production->id }}</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="revision-title-sm mb-2">SELECIONE O ARQUIVO</div>
                        </div>

                        <div class="col-12">
                            @foreach ($files as $row)
                                @php
                                    $rowId = data_get($row, 'id');
                                    $file = data_get($row, 'file');
                                    $baseName = data_get($row, 'base_name');
                                    $currentLabel = data_get($row, 'current_label');
                                    $nextLabel = data_get($row, 'next_label');
                                @endphp
                                @continue(!$rowId || !$file)
                                @php
                                    $isActive = (int) $selectedFileId === (int) $rowId;
                                    $previewUrl = $previews[$file->id] ?? null;
                                @endphp
                                <div class="revision-option {{ $isActive ? 'active' : '' }}"
                                    wire:click="$set('selectedFileId', {{ $rowId }})">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-3"
                                            style="width:44px;height:44px;background:rgba(255,255,255,.07);">
                                            @if ($previewUrl)
                                                <img src="{{ $previewUrl }}" alt="{{ $baseName }}"
                                                    style="width:100%;height:100%;object-fit:cover;border-radius:.4rem;">
                                            @else
                                                <i class="ri-file-pdf-2-line fs-4"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $baseName }}</div>
                                            <div class="meta">
                                                Revisão atual:
                                                <span class="rev-badge">{{ $currentLabel }}</span>
                                                <span class="mx-1">→</span>
                                                nova revisão:
                                                <strong>{{ $nextLabel }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @error('selectedFileId')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        @if ($nextName && $selectedMeta)
                            <div class="col-12">
                                <div class="alert py-2 mb-0" style="background:#d9e4eb;color:#1f4f63;border:0;">
                                    <i class="ri-information-line me-1"></i>
                                    O arquivo <strong>{{ $selectedMeta['base_name'] }}</strong> receberá a revisão
                                    <strong>{{ $selectedMeta['next_label'] }}</strong>.
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-semibold text-uppercase text-white-50">Novo arquivo</label>
                            <input type="file" class="form-control" wire:model="upload"
                                style="background:#2d2d2d;border:1px dashed #64748b;color:#e5e7eb;"
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.odt,.xls,.xlsx,.xlsm,.ods,.dwg,.dxf,.dws,.dwt,.dgn,.rvt,.rfa,.skp">
                            @error('upload')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top" style="border-color:#4b5563 !important;background:#2f2f2f;">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-info text-white" wire:click="confirmSaveRevision" wire:loading.attr="disabled"
                        @disabled(!$selectedFileId)
                        style="background:#1f5e74;border-color:#1f5e74;">
                        <span wire:loading.remove wire:target="confirmSaveRevision,saveRevision">Enviar revisão</span>
                        <span wire:loading wire:target="confirmSaveRevision,saveRevision">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('script')
        <script>
            window.addEventListener('confirm-file-revision-upload', function(event) {
                const payload = event.detail || {};
                Swal.fire({
                    title: 'Confirmar nova revisão?',
                    html: `
                        <div class="text-start">
                            <div><strong>Arquivo base:</strong> ${payload.currentName ?? '-'}</div>
                            <div><strong>Nova versão:</strong> ${payload.nextName ?? '-'}</div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, enviar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0f766e',
                }).then((result) => {
                    if (!result.isConfirmed || !payload.componentId) {
                        return;
                    }

                    const component = Livewire.find(payload.componentId);
                    if (component) {
                        component.call('saveRevision');
                    }
                });
            });
        </script>
    @endpush
@endonce
