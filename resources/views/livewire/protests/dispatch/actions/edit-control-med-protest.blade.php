@push('css')
    <style>
        .modal-header-modern {
            background: linear-gradient(120deg, #3b82f6 0%, #06b6d4 40%, #3b82f6 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: none;
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.15);
        }

        .modal-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
            min-width: 0;
            width: 100%;
        }

        .modal-header-main {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
            flex: 1;
        }

        .modal-header-icon {
            font-size: 1.5rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            flex-shrink: 0;
            color: #fff;
        }

        .modal-header-title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: .5px;
            margin: 0;
            color: #fff;
        }

        .modal-header-code {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: 600;
            padding: .35rem .9rem;
            border-radius: 999px;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .btn-close-modern {
            filter: invert(1) grayscale(.3);
            opacity: .85;
            width: 38px;
            height: 38px;
            margin: -0.8rem -0.8rem 0 1.1rem;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .18s;
        }

        .btn-close-modern:hover,
        .btn-close-modern:focus {
            background: rgba(255, 255, 255, 0.14);
            opacity: 1;
        }

        .modern-card {
            border: 0;
            border-radius: 16px;
            background-color: #fff;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, .5);
        }

        .modern-card-body {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
        }

        .modern-card-title {
            font-weight: 600;
            font-size: .95rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .badge-status {
            font-size: .7rem;
            line-height: 1rem;
            font-weight: 600;
            padding: .4rem .6rem;
            border-radius: .5rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .avatar-circle {
            --avatar-size: 50px;
            width: var(--avatar-size);
            height: var(--avatar-size);
            max-width: var(--avatar-size);
            max-height: var(--avatar-size);
            min-width: var(--avatar-size);
            min-height: var(--avatar-size);
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .comments-container {
            max-height: 220px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        @media (max-width: 600px) {
            .modal-header-modern {
                padding: 1.2rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .modal-header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .modal-header-title {
                font-size: 1.05rem;
            }

            .modal-header-icon {
                font-size: 1.2rem;
                width: 40px;
                height: 40px;
            }

            .modal-header-code {
                align-self: flex-start;
            }

            .avatar-circle {
                --avatar-size: 40px;
                font-size: 14px;
                font-weight: 600;
                width: var(--avatar-size);
                height: var(--avatar-size);
            }
        }
    </style>
@endpush

<div wire:ignore.self class="modal fade" id="editProtestJobModal" tabindex="-1" aria-labelledby="editProtestJobModalLabel"
    aria-hidden="true">

    <x-show-loading />

    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">

            {{-- HEADER --}}
            <div class="modal-header modal-header-modern border-0">
                <div class="modal-header-content">
                    <div class="modal-header-main">
                        <div class="modal-header-icon">
                            <i class="ri-tools-fill"></i>
                        </div>
                        <h5 class="modal-header-title mb-0">Editar Atividade</h5>
                    </div>
                    @if ($job)
                        <span class="modal-header-code">
                            ATVD#{{ $job->id }}
                        </span>
                    @endif
                </div>
                <button type="button" class="btn-close btn-close-modern flex-shrink-0" aria-label="Fechar"
                    data-bs-dismiss="modal" wire:click="closeEditor"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-4">

                @if ($job)
                    {{-- STATUS RESUMO --}}
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="modern-card">
                                <div class="modern-card-body">
                                    <div class="modern-card-title">
                                        <i class="ri-information-line me-2"></i>Resumo da Atividade
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Status Atual:</span>
                                                <span class="fw-bold">
                                                    <span
                                                        class="{{ $job->status_badge_class ?? 'badge bg-secondary' }}">
                                                        {{ $job->status_label }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Prioridade:</span>
                                                <span class="fw-bold">
                                                    <span
                                                        class="{{ $job->priority_badge_class ?? 'badge bg-secondary' }}">
                                                        {{ $job->priority_label }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Responsável:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->owner?->name ?? '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Criado por:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->creator?->name ?? '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">SLA:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->sla_due_at?->format('d/m/Y H:i') ?? '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Medida:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->medProtest?->protest?->nota }}
                                                    #{{ $job->medProtest?->med_id }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <span class="text-muted small d-block">Descrição / Instrução atual:</span>
                                            <span class="fw-medium small text-dark">
                                                {{ $job->notes ?: '—' }}
                                            </span>
                                        </div>
                                    </div> {{-- /row g-3 --}}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FORM DE EDIÇÃO --}}
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="modern-card">
                                <div class="modern-card-body">

                                    <div class="modern-card-title">
                                        <i class="ri-edit-box-line me-2"></i>Ajustes da Atividade
                                    </div>

                                    <div class="row g-3">

                                        {{-- Responsável --}}
                                        <div class="col-md-6">
                                            <div class="form-floating position-relative">
                                                <select class="form-select" id="editOwner" wire:model="owner_id">
                                                    <option value="">Selecione o responsável</option>
                                                    @foreach ($userList as $u)
                                                        <option value="{{ $u->id }}">
                                                            {{ mb_strtoupper($u->name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="editOwner">Responsável</label>
                                            </div>
                                            @error('owner_id')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror

                                            {{-- campo busca rápida --}}
                                            <div class="mt-2">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-search-line"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Buscar usuário..."
                                                        wire:model.debounce.300ms="userSearch">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Prioridade --}}
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="editPriority" wire:model="priority">
                                                    @foreach ($priorityOptions as $opt)
                                                        <option value="{{ $opt->value }}">
                                                            {{ $opt->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="editPriority">Prioridade</label>
                                            </div>
                                            @error('priority')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        {{-- Flags --}}
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" id="editAdvanceToggle"
                                                    wire:model="is_advance">
                                                <label class="form-check-label fw-medium text-info"
                                                    for="editAdvanceToggle">
                                                    <i class="ri-road-map-line me-1"></i>Avanço Parceiro
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox"
                                                    id="editEvidenceToggle" wire:model="need_evidence">
                                                <label class="form-check-label fw-medium text-warning"
                                                    for="editEvidenceToggle">
                                                    <i class="ri-mail-open-line me-1"></i>Recebido obrigatório
                                                </label>
                                            </div>
                                        </div>

                                        {{-- SLA --}}
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="datetime-local" class="form-control" id="editSlaDue"
                                                    wire:model="sla_due_at" step="3600" list="allowedHours">
                                                <datalist id="allowedHours">
                                                    <option value="00:00"></option>
                                                    <option value="08:00"></option>
                                                    <option value="12:00"></option>
                                                    <option value="18:00"></option>
                                                </datalist>
                                                <label for="editSlaDue">Retorno até (SLA)</label>
                                            </div>
                                            @error('sla_due_at')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        {{-- Notas / instrução --}}
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <textarea class="form-control" style="height: 90px" id="editNotes" wire:model="notes"
                                                    placeholder="Atualize instruções ao responsável"></textarea>
                                                <label for="editNotes">Instruções / Observações</label>
                                            </div>
                                            @error('notes')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                    </div>{{-- /row g-3 --}}

                                    {{-- AÇÕES GERAIS DO JOB: salvar config / mudar status --}}
                                    <div class="row g-3 mt-4">
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-success w-100 py-3"
                                                wire:click="saveJob">
                                                <i class="ri-save-3-fill me-2"></i>
                                                Salvar Alterações
                                            </button>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-warning w-100 py-3 text-dark"
                                                wire:click="reopenJob"
                                                @if (!$job || !in_array($job->status->value, ['done', 'canceled'])) disabled @endif>
                                                <i class="ri-restart-line me-2"></i>
                                                Reabrir
                                            </button>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger w-100 py-3"
                                                wire:click="promptFinishReason"
                                                @if (!$job || $job->status->value === 'done' || $showReasonClose) disabled @endif>
                                                <i class="ri-check-double-line me-2"></i>
                                                Concluir
                                            </button>
                                        </div>

                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-outline-danger w-100 py-3"
                                                wire:click="cancelJob"
                                                @if (!$job || $job->status->value === 'canceled') disabled @endif>
                                                <i class="ri-close-circle-line me-2"></i>
                                                Cancelar Atividade
                                            </button>
                                        </div>
                                    </div>{{-- /row g-3 mt-4 --}}

                                @if ($showReasonClose)
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                                <div class="alert alert-danger mb-0">
                                                    <div class="form-floating mb-3">
                                                        <textarea class="form-control" style="height: 120px" id="finishReason" wire:model.defer="reason_close"
                                                            placeholder="Descreva o motivo da conclusão"></textarea>
                                                        <label for="finishReason">Informe os detalhes da
                                                            conclusão</label>
                                                    </div>
                                                    @error('reason_close')
                                                        <small
                                                            class="text-danger d-block mb-2">{{ $message }}</small>
                                                    @enderror

                                                    <div class="form-floating mb-3">
                                                        <select class="form-select" id="finishResult" wire:model="result">
                                                            <option value="">Selecione</option>
                                                            @foreach ($resultOptions as $opt)
                                                                <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label for="finishResult">Resultado da medida (obrigatório)</label>
                                                    </div>
                                                    @error('result')
                                                        <small
                                                            class="text-danger d-block mb-2">{{ $message }}</small>
                                                    @enderror
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button type="button" class="btn btn-danger flex-fill"
                                                            wire:click="finishJob">
                                                            <i class="ri-check-line me-1"></i>
                                                            Confirmar conclusão
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-outline-secondary flex-fill"
                                                            wire:click="cancelFinishReason">
                                                            <i class="ri-close-line me-1"></i>
                                                            Cancelar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                @endif

                                <hr class="my-4">

                                <div class="modern-card-title">
                                    <i class="ri-upload-cloud-2-line me-2"></i>Recebidos da Medida
                                </div>

                                @if ($job?->medProtest)
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Selecionar arquivos</label>
                                            <input type="file"
                                                class="form-control @error('files.*') is-invalid @enderror"
                                                wire:model="files" multiple
                                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt">
                                            <small class="text-muted">
                                                Tipos permitidos:
                                                {{ implode(', ', array_map('mb_strtoupper', $filesConfig['allowedTypes'])) }}
                                                - Máx: {{ $filesConfig['maxSize'] / 1024 }}MB
                                            </small>
                                            @error('files.*')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="text-muted small mt-2" wire:loading wire:target="files">
                                                <i class="ri-loader-4-line me-1"></i>Carregando arquivos...
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="bg-light border rounded p-3 h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-semibold">Arquivos recebidos</span>
                                                    <span class="badge bg-primary">
                                                        {{ $job->medProtest?->EvidenceFiles?->count() }}
                                                    </span>
                                                </div>
                                                <x-files.attachments :files="$job->medProtest?->EvidenceFiles"
                                                    deleteAction="{{ auth()->user()->superadm ? 'deleteFile' : '' }}"
                                                    downloadAction="downloadFile" />
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-3">

                                    @if ($tempFiles && count($tempFiles) > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-semibold mb-0 text-primary">Recebidos prontos para envio</h6>
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                                {{ count($tempFiles) }}
                                            </span>
                                        </div>
                                        <div class="mt-2">
                                            @foreach ($tempFiles as $index => $file)
                                                <div class="card border-0 shadow-sm mb-2" wire:key="edit-temp-{{ $index }}">
                                                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="file-icon {{ $this->getFileIconClass($file->getClientOriginalExtension()) }} rounded-2 d-flex align-items-center justify-content-center"
                                                                style="width:42px;height:42px;">
                                                                <i
                                                                    class="{{ $this->getFileIcon($file->getClientOriginalExtension()) }} fs-5"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold">{{ $file->getClientOriginalName() }}</div>
                                                                <small class="text-muted">{{ $this->formatFileSize($file->getSize()) }}</small>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            wire:click="removeFile({{ $index }})">
                                                            <i class="ri-close-line"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <button type="button" class="btn btn-outline-secondary flex-fill"
                                                wire:click="clearAllFiles">
                                                <i class="ri-delete-bin-line me-1"></i>Limpar lista
                                            </button>
                                            <button type="button" class="btn btn-primary flex-fill"
                                                wire:click="saveFiles">
                                                <i class="ri-upload-2-line me-1"></i>Salvar recebidos
                                            </button>
                                        </div>
                                    @else
                                        <div class="alert alert-light border mb-0">
                                            <i class="ri-information-line me-1"></i>
                                            Nenhum arquivo em fila para envio.
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-info mb-0">
                                        Esta atividade não possui medida associada para anexos.
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        Nenhuma atividade carregada.
                    </div>
                @endif

                {{-- FECHAR MODAL --}}
                <div class="row g-3">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-outline-secondary w-100 py-3"
                                            data-bs-dismiss="modal" wire:click="closeEditor">
                                            <i class="ri-arrow-go-back-line me-2"></i>
                                            Fechar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- /row g-3 --}}

            </div>{{-- /modal-body --}}
        </div>
    </div>
</div>
