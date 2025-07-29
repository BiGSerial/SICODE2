<div>
    <div wire:ignore.self class="modal fade" id="controlModProtestModal" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">
                        <i class="bi bi-gear-fill me-2"></i>
                        CONTROLE DO DESDOBRAMENTO PARA:
                        <strong>{{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }}</strong>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Information Cards Row -->
                    <div class="row g-4 mb-4">
                        <!-- Basic Info Card -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="bi bi-info-circle-fill me-2"></i>INFORMAÇÕES BÁSICAS
                                    </h6>
                                </div>
                                <div class="card-body">
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
                                            <span>{{ $modProtest?->protest?->descCausa }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">SubCausa:</span>
                                            <span>{{ $modProtest?->protest?->descSubCausa }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Associated Notes Card -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="bi bi-journal-text me-2"></i>NOTAS ASSOCIADAS
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if ($modProtest?->protest?->all_notes?->isNotEmpty())
                                        <div class="d-flex flex-column gap-2 mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Nota:</span>
                                                <span>{{ $modProtest?->protest?->all_notes[$notePage]?->note ?? '--' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Rubrica:</span>
                                                <span>{{ $modProtest?->protest?->all_notes[$notePage]?->rubrica ?? '--' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Município:</span>
                                                <span>{{ $modProtest?->protest?->all_notes[$notePage]?->lexp ?? '--' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Cliente:</span>
                                                <span>{{ $modProtest?->protest?->all_notes[$notePage]?->client ?? '--' }}</span>
                                            </div>

                                            @if ($modProtest?->protest?->all_notes[$notePage]?->type_note == 2)
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Status:</span>
                                                    <span>{{ $modProtest?->protest?->all_notes[$notePage]?->nstats ?? '--' }}</span>
                                                </div>
                                            @elseif($modProtest?->protest?->all_notes[$notePage]?->type_note == 1)
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Centro Trabalho:</span>
                                                    <span>{{ $modProtest?->protest?->all_notes[$notePage]?->centerJob ?? '--' }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                {{ $notePage + 1 }} de
                                                {{ $modProtest?->protest?->all_notes?->count() }}
                                            </small>
                                            <div class="btn-group" role="group">
                                                <button wire:click="previousNote" class="btn btn-sm btn-outline-primary"
                                                    {{ $notePage <= 0 ? 'disabled' : '' }}>
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <button wire:click="nextNote" class="btn btn-sm btn-outline-primary"
                                                    {{ $notePage >= $modProtest?->protest?->all_notes?->count() - 1 ? 'disabled' : '' }}>
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-light border-0 text-center mb-0">
                                            <i class="bi bi-info-circle text-muted me-2"></i>
                                            SEM NOTAS ASSOCIADAS
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration Row -->
                    <div class="row g-4 mb-4">
                        <!-- Service Configuration Card -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light border-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="bi bi-gear me-2"></i>CONFIGURAÇÃO DO SERVIÇO
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="serviceSelect" wire:model="serviceId">
                                            <option value="">Selecione um serviço</option>
                                            @forelse ($serviceList as $service)
                                                <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                            @empty
                                                <option value="">Nenhum serviço disponível</option>
                                            @endforelse
                                            <option value="construction">--- Construção ---</option>
                                            <option value="maintenance">--- Engenharia ---</option>
                                        </select>
                                        <label for="serviceSelect">Serviço</label>
                                    </div>

                                    <div class="d-flex flex-column gap-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requireTracking"
                                                wire:model="modProtest.needsConfirmation">
                                            <label class="form-check-label fw-medium" for="requireTracking">
                                                <i class="bi bi-eye me-1"></i>Acompanhamento
                                            </label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="needsConfirmation"
                                                wire:model="modProtest.needsEvidence">
                                            <label class="form-check-label fw-medium" for="needsConfirmation">
                                                <i class="bi bi-camera me-1"></i>Exigir Evidência
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Assignment Card -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div
                                    class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="bi bi-people-fill me-2"></i>USUÁRIOS ATRIBUÍDOS
                                    </h6>
                                    @if ($modProtest?->Assignments?->isNotEmpty() || !empty($usersTemporarilyAssigned))
                                        <span class="badge bg-primary">
                                            {{ ($modProtest?->Assignments?->count() ?? 0) + count($usersTemporarilyAssigned ?? []) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <!-- User Selection Form -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-6">
                                            <div class="form-floating position-relative">
                                                <select class="form-select" id="selectUser" wire:model="selectedUser"
                                                    wire:loading.attr="disabled" wire:target="updatedServiceId">
                                                    <option value="">Selecione um usuário</option>
                                                    @if (!empty($userList))
                                                        @foreach ($userList as $user)
                                                            <option value="{{ $user->id }}">{{ $user->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <label for="selectUser">Usuário</label>

                                                <div class="position-absolute top-50 end-0 translate-middle-y me-3"
                                                    wire:loading wire:target="updatedServiceId">
                                                    <div class="spinner-border spinner-border-sm text-primary"
                                                        role="status">
                                                        <span class="visually-hidden">Carregando...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="engineerToggle"
                                                    wire:model="isEngineer">
                                                <label class="form-check-label fw-medium text-info"
                                                    for="engineerToggle">
                                                    <i class="bi bi-person-badge me-1"></i>Engenheiro
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <button type="button" class="btn btn-primary w-100 h-100"
                                                wire:click="addUserAssignment">
                                                <i class="bi bi-plus-circle me-1"></i>Adicionar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Assigned Users List -->
                                    <div class="border rounded p-3 bg-light"
                                        style="max-height: 200px; overflow-y: auto;">
                                        @php
                                            $hasUsers =
                                                !empty($usersTemporarilyAssigned) ||
                                                $modProtest?->Assignments?->isNotEmpty();
                                        @endphp

                                        @if ($hasUsers)
                                            <div class="d-flex flex-column gap-2">
                                                <!-- Temporary Users -->
                                                @if (!empty($usersTemporarilyAssigned))
                                                    @foreach ($usersTemporarilyAssigned as $user)
                                                        <div
                                                            class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi bi-person-circle text-primary"></i>
                                                                <span class="fw-medium">{{ $user['name'] }}</span>
                                                                <span
                                                                    class="badge {{ $user['isEngineer'] ? 'bg-info' : 'bg-warning' }}">
                                                                    {{ $user['isEngineer'] ? 'Engenheiro' : 'Responsável' }}
                                                                </span>
                                                                <small class="text-muted">(Pendente)</small>
                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="removeTempUserAssignment('{{ $user['id'] }}')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                @endif

                                                <!-- Saved Users -->
                                                @if ($modProtest?->assignments?->isNotEmpty())
                                                    @foreach ($modProtest?->assignments as $assignment)
                                                        <div
                                                            class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi bi-person-check-fill text-success"></i>
                                                                <span
                                                                    class="fw-medium">{{ $assignment->getRelation('user')->name }}</span>
                                                                <span
                                                                    class="badge {{ $assignment->monitoring ? 'bg-info' : 'bg-warning' }}">
                                                                    {{ $assignment->monitoring ? 'Engenheiro' : 'Responsável' }}
                                                                </span>
                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="removeUserAssignment('{{ $assignment->id }}')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-3">
                                                <i class="bi bi-person-x fs-3"></i>
                                                <p class="mb-0 mt-2">Nenhum usuário atribuído</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div
                                    class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="bi bi-chat-dots-fill me-2"></i>
                                        OBSERVAÇÕES - {{ $modProtest?->protest?->nota }} #{{ $modProtest?->med_id }}
                                    </h6>
                                    @if ($modProtest?->comments?->isNotEmpty())
                                        <span class="badge bg-primary">{{ $modProtest->comments?->count() }}</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <!-- Comment Input -->
                                    <div class="comment-input-section mb-4">
                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="commentInput" rows="3" wire:model.defer="comment"
                                                placeholder="Digite seu comentário..." style="height: 80px;"></textarea>
                                            <label for="commentInput">Digite seu comentário...</label>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-primary px-4"
                                                wire:click.prevent="addComment">
                                                <i class="bi bi-send-fill me-2"></i>Enviar Comentário
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Comments List -->
                                    <div class="comments-container bg-light rounded p-3"
                                        style="max-height: 300px; overflow-y: auto;">
                                        @if ($modProtest?->Comments?->isNotEmpty())
                                            @foreach ($modProtest?->Comments->sortByDesc('created_at') as $comment)
                                                <div class="comment-item mb-3 p-3 bg-white rounded border">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                                style="width: 32px; height: 32px;">
                                                                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                                            </div>
                                                            <div>
                                                                <span
                                                                    class="fw-medium {{ $comment->user_id === auth()->user()->id ? 'text-primary' : '' }}">
                                                                    {{ $comment->user->name }}
                                                                </span>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    @if ($comment->user?->email)
                                                                        <i class="bi bi-microsoft-teams text-primary"
                                                                            style="cursor:pointer"
                                                                            onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')"
                                                                            title="Abrir no Teams"></i>
                                                                    @endif
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-clock me-1"></i>
                                                                        {{ $comment->created_at->diffForHumans() }}
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if (
                                                            ($comment->created_at->diffInHours() < 1 && $comment->id === $modProtest->comments->max('id')) ||
                                                                auth()->user()->admin ||
                                                                auth()->user()->superadm)
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="deleteComment({{ $comment->id }})"
                                                                title="Excluir comentário">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <p class="mb-0 text-dark">{{ $comment->message }}</p>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-chat-square-text fs-3"></i>
                                                <p class="mb-0 mt-2">Não há observações para exibir</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="bi bi-floppy me-2"></i>AÇÕES DO DESDOBRAMENTO
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-success w-100 py-3"
                                                wire:click="saveMeasures">
                                                <i class="bi bi-check-circle-fill me-2"></i>
                                                <span class="fw-medium">Salvar Medidas</span>
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-outline-secondary w-100 py-3"
                                                wire:click="cancelChanges">
                                                <i class="bi bi-x-circle me-2"></i>
                                                <span class="fw-medium">Cancelar Alterações</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .comments-container::-webkit-scrollbar {
            width: 6px;
        }

        .comments-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .comments-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .comments-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</div>
