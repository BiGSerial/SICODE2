@push('css')
    <style>
        .modal-header-modern {
            background: linear-gradient(90deg, #6a82fb 0%, #fc5c7d 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            padding: 1.8rem 2rem 1.2rem 2rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border: none;
        }

        .modal-header-content {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            min-width: 0;
        }

        .modal-header-icon {
            font-size: 2.7rem;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            flex-shrink: 0;
        }

        .modal-header-texts {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.18rem;
        }

        .modal-header-title {
            font-size: 1.30rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: break-spaces;
            word-break: break-word;
            letter-spacing: 0.5px;
        }

        .modal-header-desc {
            font-size: .98rem;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 400;
            white-space: break-spaces;
            word-break: break-word;
            margin-top: 2px;
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
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .comments-container {
            max-height: 260px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        @media (max-width: 600px) {
            .modal-header-modern {
                padding: 1.2rem 0.9rem 1rem 1rem;
            }

            .modal-header-title {
                font-size: 1.03rem;
            }

            .modal-header-icon {
                font-size: 1.4rem;
                width: 33px;
                height: 33px;
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
                    <div class="modal-header-icon">
                        <i class="ri-settings-5-fill"></i>
                    </div>
                    <div class="modal-header-texts">
                        <span class="modal-header-title">
                            Controle da Medida:
                            <strong>{{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }}</strong>
                        </span>
                        <span class="modal-header-desc">
                            Abra uma atividade (ProtestJob) para alguém OU encerre a medida imediatamente.
                        </span>
                    </div>
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
                                    <span class="fw-medium small">
                                        {{ $modProtest?->protest?->comments->last()?->message }}
                                    </span>
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
                                                <i class="ri-camera-line me-1"></i>Evidência obrigatória
                                            </label>
                                        </div>
                                    </div>

                                    {{-- SLA --}}
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="datetime-local" class="form-control" id="slaDue"
                                                wire:model.defer="sla_due_at">
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
                                            <label for="jobNotes">Orientações / Comentário inicial</label>
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
                                            wire:click="closeNow">
                                            <i class="ri-shut-down-line me-2"></i>
                                            Encerrar Agora
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>{{-- /row g-4 mb-4 --}}

                {{-- COMENTÁRIOS DA MEDIDA (histórico interno da medida) --}}
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="modern-card-title d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="ri-chat-3-line me-2"></i>
                                        Observações da Medida
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
                                            <div class="comment-item mb-3 p-3 bg-white rounded border">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-circle bg-primary text-white">
                                                            {{ strtoupper(substr($c->user->name, 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="fw-medium {{ $c->user_id === auth()->id() ? 'text-primary' : '' }}">
                                                                {{ $c->user->name }}
                                                            </span>

                                                            <div class="d-flex align-items-center gap-2">
                                                                @if ($c->user?->email)
                                                                    <i class="ri-microsoft-teams-line text-primary"
                                                                        style="cursor:pointer"
                                                                        onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $c->user?->email }}','_blank')"
                                                                        title="Abrir no Teams"></i>
                                                                @endif
                                                                <small class="text-muted">
                                                                    <i class="ri-time-line me-1"></i>
                                                                    {{ $c->created_at->diffForHumans() }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @php
                                                        $isLast = $c->id === $modProtest->comments->max('id');
                                                        $fresh = $c->created_at->diffInHours() < 1;
                                                        $canDelete =
                                                            ($fresh && $isLast) ||
                                                            auth()->user()->admin ||
                                                            auth()->user()->superadm;
                                                    @endphp

                                                    @if ($canDelete)
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            title="Excluir comentário"
                                                            wire:click="markCommentForDeletion({{ $c->id }})">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                                <p class="mb-0 text-dark">{{ $c->message }}</p>
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
