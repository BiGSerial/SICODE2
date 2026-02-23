@push('css')
    <style>
        .modal-header-modern {
            background: linear-gradient(120deg, #6a82fb 0%, #64b5f6 50%, #6a82fb 100%);
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
            font-size: 1.6rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            flex-shrink: 0;
        }

        .modal-header-title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: .5px;
            margin: 0;
        }

        .modal-header-desc {
            font-size: .95rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        .modal-header-code {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: 600;
            padding: .35rem .9rem;
            border-radius: 999px;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
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

        .avatar-circle {
            --avatar-size: 50px;
            width: var(--avatar-size);
            height: var(--avatar-size);
            min-width: var(--avatar-size);
            min-height: var(--avatar-size);
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            background: #f1f5f9;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .comments-container {
            max-height: 260px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .chat-message {
            border-radius: 12px;
        }

        .message-bubble {
            border: 1px solid #e9ecef;
            transition: all 0.2s;
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
        }
    </style>
@endpush

<div wire:ignore.self class="modal fade" id="controlModProtestModal" tabindex="-1"
    aria-labelledby="controlModProtestModalLabel" aria-hidden="true">

    <x-show-loading />

    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">

            {{-- HEADER MODERNO --}}
            <div class="modal-header-modern">
                <div class="modal-header-content">
                    <div class="modal-header-main">
                        <div class="modal-header-icon">
                            <i class="ri-settings-5-fill"></i>
                        </div>
                        <div class="d-flex flex-column gap-1">
                            <span class="modal-header-title">Controle da Medida</span>
                            <span class="modal-header-desc">
                                Abra uma atividade (ProtestJob) ou encerre a medida imediatamente.
                            </span>
                        </div>
                    </div>
                    @if ($modProtest)
                        <span class="modal-header-code">
                            {{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }}
                        </span>
                    @endif
                </div>
                <button type="button" class="btn-close btn-close-modern" data-bs-dismiss="modal"
                    wire:click="cancelChanges" aria-label="Fechar"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-4">

                {{-- BLOCO SUPERIOR: INFO BÁSICA + NOTAS ASSOCIADAS --}}
                <div class="row g-4 mb-4">

                    {{-- Info Básica da Reclamação / Medida --}}
                    <div class="col-lg-4">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-information-line me-2"></i>Informações Básicas
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Nota:</span>
                                        <strong>{{ $modProtest?->protest?->nota }}</strong>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Município:</span>
                                        <span>{{ $modProtest?->protest?->cidade }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Grupo:</span>
                                        <span>{{ $modProtest?->protest?->txtGrpCodificacao }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Causa:</span>
                                        <span>{{ $modProtest?->txtCodCodificacao }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">SubCausa:</span>
                                        <span>{{ $modProtest?->txtCodMedida }}</span>
                                    </div>

                                    <span class="text-muted small d-block mt-1">Descrição:</span>
                                    <div class="description-container"
                                        style="max-height: 120px; overflow-y: auto; scrollbar-width: thin;">
                                        <span class="fw-medium small"
                                            style="white-space: pre-line;">{{ $modProtest?->protest?->resume ?? 'SEM DESCRIÇÃO PARA RECLAMAÇÃO' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notas Associadas / Paginação interna --}}
                    <div class="col-lg-8">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-file-list-3-line me-2"></i>Notas Associadas
                                </div>

                                @if ($modProtest?->protest?->all_notes?->isNotEmpty())
                                    @php
                                        $current = $modProtest?->protest?->all_notes[$notePage] ?? null;
                                    @endphp

                                    <div class="d-flex flex-column gap-2 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Nota:</span>
                                            <span>{{ $current?->note ?? '--' }}</span>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Rubrica:</span>
                                            <span>{{ $current?->rubrica ?? '--' }}</span>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Município:</span>
                                            <span>{{ $current?->lexp ?? '--' }}</span>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Cliente:</span>
                                            <span>{{ $current?->client ?? '--' }}</span>
                                        </div>

                                        @if ($current?->type_note == 2)
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Status:</span>
                                                <span>{{ $current?->nstats ?? '--' }}</span>
                                            </div>
                                        @elseif ($current?->type_note == 1)
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Centro Trabalho:</span>
                                                <span>{{ $current?->centerJob ?? '--' }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            {{ $notePage + 1 }} de {{ $modProtest?->protest?->all_notes?->count() }}
                                        </small>

                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" wire:click="previousPage"
                                                @if ($notePage <= 0) disabled @endif>
                                                <i class="ri-arrow-left-s-line"></i>
                                            </button>

                                            <button class="btn btn-sm btn-outline-primary" wire:click="nextPage"
                                                @if ($notePage >= $modProtest?->protest?->all_notes?->count() - 1) disabled @endif>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-light border-0 text-center mb-0">
                                        <i class="ri-information-line text-muted me-2"></i>SEM NOTAS ASSOCIADAS
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div> {{-- /row g-4 mb-4 --}}

                {{-- NOVA ATIVIDADE (ProtestJob) --}}
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">

                                <div class="modern-card-title">
                                    <i class="ri-clipboard-line me-2"></i>Nova Atividade (Despacho)
                                </div>

                                <div class="row g-3">

                                    {{-- Responsável --}}
                                    <div class="col-md-6">
                                        <div class="form-floating position-relative">
                                            <select class="form-select" id="jobOwner" wire:model.defer="selectedUser">
                                                <option value="">Selecione o responsável</option>
                                                @foreach ($userList as $u)
                                                    <option value="{{ $u->id }}">
                                                        {{ mb_strtoupper($u->name) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="jobOwner">Responsável</label>
                                        </div>
                                        @error('selectedUser')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror

                                        {{-- busca rápida de usuário --}}
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
                                            <select class="form-select" id="jobPriority" wire:model.defer="priority">
                                                @foreach ($priorityOptions as $opt)
                                                    <option value="{{ $opt->value }}">
                                                        {{ $opt->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="jobPriority">Prioridade</label>
                                        </div>
                                        @error('priority')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    {{-- Flags --}}
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="isAdvanceToggle"
                                                wire:model.defer="is_advance">
                                            <label class="form-check-label fw-medium text-info" for="isAdvanceToggle">
                                                <i class="ri-road-map-line me-1"></i>Avanço Parceiro
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="needEvidenceToggle"
                                                wire:model.defer="need_evidence">
                                            <label class="form-check-label fw-medium text-warning"
                                                for="needEvidenceToggle">
                                                <i class="ri-mail-open-line me-1"></i>Recebido obrigatório
                                            </label>
                                        </div>
                                    </div>

                                    {{-- SLA --}}
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="datetime-local" class="form-control" id="slaDue"
                                                wire:model.defer="sla_due_at" step="3600"
                                                list="allowedHoursControl">
                                            <datalist id="allowedHoursControl">
                                                <option value="00:00"></option>
                                                <option value="08:00"></option>
                                                <option value="12:00"></option>
                                                <option value="18:00"></option>
                                            </datalist>
                                            <label for="slaDue">Retorno até (SLA)</label>
                                        </div>
                                        @error('sla_due_at')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    {{-- Orientações iniciais --}}
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" style="height: 150px" id="jobNotes" wire:model.defer="notes"
                                                placeholder="Orientações para o responsável"></textarea>
                                            <label for="jobNotes">Orientações / Detalhe a atividade do
                                                responsável</label>
                                        </div>
                                        @error('notes')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                </div>{{-- /row g-3 --}}

                                <div class="row g-3 mt-4">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-success w-100 py-3"
                                            wire:click="dispatchJob">
                                            <i class="ri-send-plane-fill me-2"></i>
                                            Despachar Atividade
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-danger w-100 py-3"
                                            wire:click="closeNow"
                                            @if ($showReasonClose) disabled @endif>
                                            <i class="ri-shut-down-line me-2"></i>
                                            Encerrar Agora
                                        </button>
                                    </div>
                                </div>

                                @if ($showReasonClose)
                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-danger mb-0">
                                                <div class="form-floating mb-3">
                                                    <textarea class="form-control" style="height: 120px" id="closeReason"
                                                        wire:model.defer="reason_close" placeholder="Informe o motivo da conclusão"></textarea>
                                                    <label for="closeReason">Detalhe o motivo do encerramento imediato</label>
                                                </div>
                                                @error('reason_close')
                                                    <small class="text-danger d-block mb-2">{{ $message }}</small>
                                                @enderror
                                                <div class="form-floating mb-3">
                                                    <select class="form-select" id="closeResult" wire:model="result">
                                                        <option value="">Selecione</option>
                                                        @foreach ($resultOptions as $opt)
                                                            <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label for="closeResult">Resultado da medida (obrigatório)</label>
                                                </div>
                                                @error('result')
                                                    <small class="text-danger d-block mb-2">{{ $message }}</small>
                                                @enderror
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-danger flex-fill"
                                                        wire:click="doCloseMeasureNow">
                                                        <i class="ri-check-line me-1"></i>
                                                        Confirmar encerramento
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary flex-fill"
                                                        wire:click="cancelCloseNow">
                                                        <i class="ri-close-line me-1"></i>
                                                        Cancelar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>{{-- /row g-4 mb-4 --}}

                {{-- RECEBIDOS DA MEDIDA --}}
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-upload-cloud-2-line me-2"></i>Recebidos da Medida
                                </div>

                                @if ($modProtest)
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
                                                - M?x: {{ $filesConfig['maxSize'] / 1024 }}MB
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
                                                        {{ $modProtest->EvidenceFiles->count() }}
                                                    </span>
                                                </div>

                                                <x-files.attachments :files="$modProtest->EvidenceFiles"
                                                    deleteAction="{{ auth()->user()->superadm ? 'deleteFile' : '' }}"
                                                    downloadAction="downloadFile" />
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-3">

                                    @if ($tempFiles && count($tempFiles) > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-semibold mb-0 text-primary">
                                                Recebidos prontos para envio
                                            </h6>
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                                {{ count($tempFiles) }}
                                            </span>
                                        </div>

                                        <div class="mt-2">
                                            @foreach ($tempFiles as $index => $file)
                                                <div class="card border-0 shadow-sm mb-2" wire:key="temp-file-{{ $index }}">
                                                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="file-icon {{ $this->getFileIconClass($file->getClientOriginalExtension()) }} rounded-2 d-flex align-items-center justify-content-center"
                                                                style="width:42px;height:42px;">
                                                                <i
                                                                    class="{{ $this->getFileIcon($file->getClientOriginalExtension()) }} fs-5"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold">{{ $file->getClientOriginalName() }}</div>
                                                                <small class="text-muted">
                                                                    {{ $this->formatFileSize($file->getSize()) }}
                                                                </small>
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
                                        Selecione uma medida para anexar recebidos.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COMENTÁRIOS DA MEDIDA (histórico interno da medida) --}}
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="modern-card-title d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="ri-chat-3-line me-2"></i>
                                        Comentários da Medida
                                        ({{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }})
                                    </span>

                                    @if ($modProtest?->comments?->isNotEmpty())
                                        <span class="badge bg-primary">
                                            {{ $modProtest->comments?->count() }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Input de novo comentário --}}
                                <div class="comment-input-section mb-4">
                                    <div class="form-floating mb-3">
                                        <textarea class="form-control" id="commentInput" rows="3" style="height:80px;" wire:model.defer="comment"
                                            placeholder="Digite seu comentário..."></textarea>
                                        <label for="commentInput">Digite seu comentário...</label>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-primary px-4"
                                            wire:click.prevent="addCommentToMedProtest">
                                            <i class="ri-send-plane-2-line me-2"></i>Enviar Comentário
                                        </button>
                                    </div>
                                </div>

                                {{-- Lista de comentários --}}
                                <div class="comments-container bg-light rounded p-3">
                                    @if ($modProtest?->Comments?->isNotEmpty())
                                        @foreach ($modProtest?->Comments->sortByDesc('created_at') as $c)
                                            @php
                                                $isLast = $c->id === $modProtest->comments->max('id');
                                                $fresh = $c->created_at->diffInHours() < 1;
                                                $canDelete =
                                                    ($fresh && $isLast) ||
                                                    auth()->user()->admin ||
                                                    auth()->user()->superadm;
                                            @endphp

                                            <div
                                                class="chat-message p-3 {{ !$loop->last ? 'border-bottom mb-3 pb-3' : '' }}">
                                                <div class="d-flex gap-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar-circle" title="{{ $c->user->name }}">
                                                            <img src="{{ $c->user->avatar_url }}"
                                                                alt="Avatar de {{ $c->user->name }}">
                                                        </div>
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div
                                                            class="d-flex justify-content-between align-items-start mb-1">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span
                                                                    class="fw-semibold {{ $c->user_id === auth()->user()->id ? 'text-primary' : 'text-dark' }}">
                                                                    {{ $c->user->name }}
                                                                </span>

                                                                @if ($c->user?->email)
                                                                    <button class="btn btn-sm btn-outline-primary p-1"
                                                                        onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $c->user?->email }}', '_blank')"
                                                                        title="Abrir chat no Teams">
                                                                        <i class="bx bxl-microsoft-teams fs-6"></i>
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            <div class="d-flex align-items-center gap-2">
                                                                <small class="text-muted">
                                                                    <i class="ri-time-line me-1"></i>
                                                                    {{ $c->created_at->diffForHumans() }}
                                                                </small>

                                                                @if ($canDelete)
                                                                    <button class="btn btn-sm btn-outline-danger p-1"
                                                                        wire:click="markCommentForDeletion({{ $c->id }})"
                                                                        title="Excluir comentário">
                                                                        <i class="ri-delete-bin-line fs-6"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="message-bubble p-3 rounded-3 {{ $c->user_id === auth()->user()->id ? 'bg-primary bg-opacity-10' : 'bg-light' }}">
                                                            <p class="mb-0 text-dark">{{ $c->message }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted py-4">
                                            <i class="ri-chat-3-line fs-3"></i>
                                            <p class="mb-0 mt-2">Não há observações para exibir</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- /row g-4 mb-4 --}}

                {{-- BOTÃO CANCELAR / FECHAR MODAL --}}
                <div class="row g-3">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-outline-secondary w-100 py-3"
                                            wire:click="closeModal" data-bs-dismiss="modal">
                                            <i class="ri-close-circle-line me-2"></i>
                                            Fechar / Cancelar Alterações
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
