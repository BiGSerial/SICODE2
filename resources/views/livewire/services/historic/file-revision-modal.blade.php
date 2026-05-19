@php
    use App\Helpers\FileIcon;

    $modalId = $isSingleton ? 'fileRevisionModalSingleton' : 'fileRevisionModal-' . ($production->id ?? 0);
    $files = $files ?? collect();
    $previews = $previews ?? [];
    $nextName = $nextName ?? null;
    $selectedMeta = $selectedMeta ?? null;
    $uploadTypeOptions = $uploadTypeOptions ?? [];
@endphp

<div>
    <style>
        .revision-modal .modal-content {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dbe3ef;
            background: #f8fbff;
            color: #1f2937;
        }

        .revision-modal .modal-header {
            background: #225E66;
            border-bottom: 1px solid rgba(255, 255, 255, .18);
        }

        .revision-modal .modal-body {
            background: #ffffff;
        }

        .revision-title-sm {
            color: #334155;
            font-size: .76rem;
            letter-spacing: .04em;
            font-weight: 700;
        }

        .revision-option {
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            background: #ffffff;
            cursor: pointer;
            transition: .2s ease;
            padding: .8rem;
            margin-bottom: .55rem;
            color: #0f172a;
        }

        .revision-option:hover {
            border-color: #225E66;
            box-shadow: 0 0 0 .12rem rgba(34, 94, 102, .15);
        }

        .revision-option.active {
            border-color: #225E66;
            background: #eaf4f5;
            color: #143F47;
        }

        .revision-option .meta {
            font-size: .92rem;
            color: #475569;
        }

        .revision-option.active .meta {
            color: #225E66;
        }

        .rev-badge {
            padding: .05rem .42rem;
            border-radius: .34rem;
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            font-size: .82rem;
        }

        .revision-option.active .rev-badge {
            background: #225E66;
            color: #ffffff;
        }

        .revision-guide {
            border: 1px solid #9fc3c8;
            background: #eef6f7;
            color: #225E66;
            border-radius: .5rem;
            padding: .6rem .75rem;
            font-size: .9rem;
        }

        .revision-guide-steps {
            margin: 0;
            padding-left: 1.1rem;
        }

        .revision-guide-steps li {
            margin-bottom: .25rem;
        }

        .revision-flow-chip {
            display: inline-block;
            font-size: .75rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .15rem .55rem;
            margin-right: .35rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
        }

        .revision-flow-chip.active {
            border-color: #225E66;
            background: #eaf4f5;
            color: #143F47;
        }

        .pending-files-table thead th {
            background: #225E66;
            color: #ffffff;
            border-bottom: 1px solid #dbe3ef;
        }

        .pending-files-table tbody td {
            background: #ffffff;
            color: #1f2937;
            border-color: #e5e7eb;
        }
    </style>

    @if (!$isSingleton)
        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
            <i class="ri-upload-cloud-2-line"></i> Revisar
        </button>
    @endif

    <div wire:ignore.self class="modal fade revision-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header text-white">
                    <div>
                        <h5 class="modal-title mb-0">Revisão de arquivos</h5>
                        @if ($production)
                            <small class="opacity-75">{{ $production->Note->note }} - Produção #{{ $production->id }}</small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if (!$production)
                        <div class="text-center py-5">
                            <span class="spinner-border text-secondary" role="status"></span>
                        </div>
                    @else
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="revision-title-sm mb-2">SELECIONE O ARQUIVO</div>
                            <div class="small text-muted">
                                Sem seleção: envia novo arquivo por tipo. Com seleção: revisão ou adicionar folhas.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="revision-guide">
                                <div class="fw-semibold mb-1">Guia de uso</div>
                                <ol class="revision-guide-steps">
                                    <li>Selecione o arquivo para atualizar <strong>ou</strong> envie novos arquivos para o projeto.</li>
                                    <li>Se selecionar um arquivo, decida: adicionar novas folhas ou atualizar somente a revisão.</li>
                                    <li>Revise a lista de arquivos enviados e remova os incorretos antes de confirmar.</li>
                                </ol>
                            </div>
                        </div>

                        <div class="col-12">
                            <span class="revision-flow-chip {{ !$selectedFileId ? 'active' : '' }}">Enviar novo por tipo</span>
                            <span class="revision-flow-chip {{ $selectedFileId && !$appendSheets ? 'active' : '' }}">Atualizar revisão</span>
                            <span class="revision-flow-chip {{ $selectedFileId && $appendSheets ? 'active' : '' }}">Adicionar folhas</span>
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
                                    $rowIcon = FileIcon::getIcon(strtolower((string) $file->ext));
                                @endphp
                                <div wire:key="revision-file-row-{{ $production->id }}-{{ $rowId }}"
                                    class="revision-option {{ $isActive ? 'active' : '' }}"
                                    wire:click="toggleSelectedFile({{ $rowId }})">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-3"
                                            style="width:44px;height:44px;background:rgba(255,255,255,.07);">
                                            @if ($previewUrl)
                                                <img wire:key="revision-file-img-{{ $production->id }}-{{ $rowId }}-{{ md5((string) $previewUrl) }}"
                                                    src="{{ $previewUrl }}" alt="{{ $baseName }}"
                                                    style="width:100%;height:100%;object-fit:cover;border-radius:.4rem;"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                                <i class="{{ $rowIcon->icon ?? 'ri-file-fill text-secondary' }} fs-4"
                                                    style="display:none;"></i>
                                            @else
                                                <i class="{{ $rowIcon->icon ?? 'ri-file-fill text-secondary' }} fs-4"></i>
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

                        @if (!$selectedFileId)
                            {{-- 1. Tipo primeiro — habilita o input de arquivo --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold text-uppercase text-muted">Tipo do novo arquivo</label>
                                <select class="form-select" wire:model="uploadType"
                                    style="background:#ffffff;border:1px solid #ced4da;color:#212529;">
                                    <option value="">Selecione o tipo</option>
                                    @foreach ($uploadTypeOptions as $option)
                                        <option value="{{ $option->value }}">{{ $option->reason }}</option>
                                    @endforeach
                                </select>
                                @error('uploadType')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                                @if (!$uploadType)
                                    <div class="small text-muted mt-1">
                                        <i class="ri-information-line"></i> Selecione o tipo para liberar o envio de arquivos.
                                    </div>
                                @endif
                            </div>

                            {{-- 2. Arquivos — liberado após escolher o tipo --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold text-uppercase text-muted">
                                    Arquivos
                                    <span class="text-secondary fw-normal text-lowercase">(múltiplos permitidos · máx. 50 MB por arquivo)</span>
                                </label>
                                <input type="file" class="form-control" wire:model="newUploads" multiple
                                    @disabled(!$uploadType)
                                    style="background:#ffffff;border:1px dashed #94a3b8;color:#212529;"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.odt,.xls,.xlsx,.xlsm,.ods,.dwg,.dxf,.dws,.dwt,.dgn,.rvt,.rfa,.skp">
                                @error('newUploads')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                                @error('newUploads.*')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                                <div wire:loading wire:target="newUploads" class="small text-primary mt-1">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Processando arquivos…
                                </div>
                                @if ($uploadType && count($this->pendingUploads) === 0)
                                    <div class="small text-warning mt-1">Selecione um ou mais arquivos para continuar.</div>
                                @endif
                            </div>

                            {{-- Lista de arquivos prontos para envio --}}
                            @if (count($this->pendingUploads))
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0 pending-files-table">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width:48px;"></th>
                                                    <th>Arquivo selecionado</th>
                                                    <th class="text-center" style="width:90px;">Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->pendingUploads as $idx => $pendingUpload)
                                                    @php
                                                        $pendingExt  = strtolower(pathinfo($pendingUpload->getClientOriginalName(), PATHINFO_EXTENSION));
                                                        $pendingIcon = FileIcon::getIcon($pendingExt);
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center"><i class="{{ $pendingIcon->icon ?? 'ri-file-fill' }}"></i></td>
                                                        <td>{{ $pendingUpload->getClientOriginalName() }}</td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                wire:click="removePendingUpload({{ $idx }})" title="Remover">
                                                                <i class="ri-delete-bin-2-line"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="appendSheets-{{ $modalId }}"
                                        wire:model="appendSheets">
                                    <label class="form-check-label" for="appendSheets-{{ $modalId }}">
                                        Adicionar folha(s) ao arquivo selecionado
                                    </label>
                                </div>
                                @if (!$appendSheets && !$upload)
                                    <div class="small text-warning mt-1">Anexe um arquivo de revisão ou marque "Adicionar folha(s)".</div>
                                @endif
                            </div>

                            @if ($appendSheets)
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="prependSheets-{{ $modalId }}"
                                            wire:model="prependSheets">
                                        <label class="form-check-label" for="prependSheets-{{ $modalId }}">
                                            Estou enviando a primeira folha (renumerar grupo desde F01)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-uppercase text-muted">Novas folhas</label>
                                    <input type="file" class="form-control" wire:model="newUploads" multiple
                                        style="background:#ffffff;border:1px dashed #94a3b8;color:#212529;"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.odt,.xls,.xlsx,.xlsm,.ods,.dwg,.dxf,.dws,.dwt,.dgn,.rvt,.rfa,.skp">
                                    @error('newUploads')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                    @error('newUploads.*')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                    @if (count($this->pendingUploads) === 0)
                                        <div class="small text-warning mt-1">Selecione uma ou mais folhas para adicionar.</div>
                                    @endif
                                </div>

                                @if (count($this->pendingUploads))
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0 pending-files-table">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width:48px;"></th>
                                                        <th>Folha selecionada</th>
                                                        <th class="text-center" style="width:90px;">Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($this->pendingUploads as $idx => $pendingUpload)
                                                        @php
                                                            $pendingExt = strtolower(pathinfo($pendingUpload->getClientOriginalName(), PATHINFO_EXTENSION));
                                                            $pendingIcon = FileIcon::getIcon($pendingExt);
                                                        @endphp
                                                        <tr>
                                                            <td class="text-center"><i class="{{ $pendingIcon->icon ?? 'ri-file-fill' }}"></i></td>
                                                            <td>{{ $pendingUpload->getClientOriginalName() }}</td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    wire:click="removePendingUpload({{ $idx }})" title="Remover">
                                                                    <i class="ri-delete-bin-2-line"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-uppercase text-muted">Novo arquivo de revisão</label>
                                    <input type="file" class="form-control" wire:model="upload"
                                        style="background:#ffffff;border:1px dashed #94a3b8;color:#212529;"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.odt,.xls,.xlsx,.xlsm,.ods,.dwg,.dxf,.dws,.dwt,.dgn,.rvt,.rfa,.skp">
                                    @error('upload')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif
                        @endif
                    </div>
                    @endif
                </div>

                <div class="modal-footer border-top" style="border-color:#dbe3ef !important;background:#f8fbff;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    @if ($production)
                    <button type="button" class="btn btn-info text-white" wire:click="confirmSaveRevision" wire:loading.attr="disabled"
                        @disabled((!$selectedFileId && !$uploadType) || (!$selectedFileId && count($this->pendingUploads) === 0) || ($selectedFileId && !$appendSheets && !$upload) || ($selectedFileId && $appendSheets && count($this->pendingUploads) === 0))
                        style="background:#225E66;border-color:#225E66;">
                        <span wire:loading.remove wire:target="confirmSaveRevision,saveRevision">Enviar revisão</span>
                        <span wire:loading wire:target="confirmSaveRevision,saveRevision">Enviando...</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        if (!window.__fileRevisionModalListeners) {
            window.__fileRevisionModalListeners = true;

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

            window.addEventListener('close-file-revision-modal', function(event) {
                const payload = event.detail || {};
                if (!payload.modalId) {
                    return;
                }

                const modalEl = document.getElementById(payload.modalId);
                if (!modalEl) {
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();

                // Garante limpeza de backdrop/classe residual em fechamentos sucessivos via Livewire.
                document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
            });

            window.addEventListener('show-file-revision-modal-singleton', function() {
                const modalEl = document.getElementById('fileRevisionModalSingleton');
                if (!modalEl) return;
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.show();
            });
        }
    </script>
@endpush
