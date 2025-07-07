<div>
    <div wire:ignore.self class="modal fade" id="controlModProtestModal" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">
                        CONTROLE DO DESDOBRAMENTO PARA:
                        <strong
                            class="text-white">{{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }}</strong>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- First Row -->
                    <div class="row g-3 mb-4">
                        <!-- Basic Info Card -->
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-3">INFORMAÇÕES BÁSICAS</h6>
                                    <p class="mb-2"><strong>Nota:</strong> {{ $modProtest?->protest?->nota }}</p>
                                    <p class="mb-2"><strong>Municipio:</strong> {{ $modProtest?->protest?->cidade }}
                                    </p>
                                    <p class="mb-2"><strong>Grupo:</strong>
                                        {{ $modProtest?->protest?->txtGrpCodificacao }}</p>
                                    <p class="mb-2"><strong>Causa:</strong> {{ $modProtest?->protest?->descCausa }}
                                    </p>
                                    <p class="mb-2"><strong>SubCausa:</strong>
                                        {{ $modProtest?->protest?->descSubCausa }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Associated Notes Card -->
                        <div class="col-md-8">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-3">INFORMAÇÕES NOTAS ASSOCIADAS</h6>
                                    @if ($modProtest?->protest?->notes->isNotEmpty())
                                        <!-- Notes content -->
                                        <div class="mb-3">
                                            <p class="mb-2"><strong>Nota:</strong>
                                                {{ $modProtest?->protest?->Notes[$notePage]?->note ?? '--' }}</p>
                                            <p class="mb-2"><strong>Rubrica:</strong>
                                                {{ $modProtest?->protest?->Notes[$notePage]?->rubrica ?? '--' }}</p>
                                            <p class="mb-2"><strong>Municipio:</strong>
                                                {{ $modProtest?->protest?->Notes[$notePage]?->lexp ?? '--' }}</p>
                                            <p class="mb-2"><strong>Cliente:</strong>
                                                {{ $modProtest?->protest?->Notes[$notePage]?->client ?? '--' }}</p>
                                            <!-- Status or CentroTrabalho -->
                                            @if ($modProtest?->protest?->Notes[$notePage]?->type_note == 2)
                                                <p class="mb-2"><strong>Status:</strong>
                                                    {{ $modProtest?->protest?->Notes[$notePage]?->nstats ?? '--' }}</p>
                                            @elseif($modProtest?->protest?->Notes[$notePage]?->type_note == 1)
                                                <p class="mb-2"><strong>CentroTrabalho:</strong>
                                                    {{ $modProtest?->protest?->Notes[$notePage]?->centerJob ?? '--' }}
                                                </p>
                                            @endif
                                        </div>
                                        <!-- Navigation buttons -->
                                        <div class="d-flex justify-content-end gap-2">
                                            <button wire:click="previousNote" class="btn btn-sm btn-outline-secondary"
                                                {{ $notePage <= 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </button>
                                            <button wire:click="nextNote" class="btn btn-sm btn-outline-secondary"
                                                {{ $notePage >= $modProtest?->protest?->Notes?->count() - 1 ? 'disabled' : '' }}>
                                                Próximo <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary mb-0">
                                            <div class="text-center">SEM NOTAS ASSOCIADAS</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Row -->
                    <div class="row g-3">
                        <!-- Service Selection Card -->
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-3">SELECIONAR SERVIÇO PARA ATIVIDADE</h6>
                                    <div class="mb-3">
                                        <select class="form-select form-select-lg mb-3" wire:model="serviceId">
                                            <option value="">Selecione um serviço</option>
                                            @forelse ($serviceList as $service)
                                                <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                            @empty
                                                <option value="">Nenhum serviço disponível</option>
                                            @endforelse
                                            <option value="construction"> --- Construção ---</option>
                                            <option value="maintenance"> --- Engenharia ---</option>
                                        </select>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="requireTracking"
                                            wire:model="needsEvidence">
                                        <label class="form-check-label" for="requireTracking">Acompanhamento</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="needsConfirmation"
                                            wire:model="needsEvidence">
                                        <label class="form-check-label" for="needsConfirmation">Exigir Evidencia</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="col-md-8">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-3">
                                        <i class="bi bi-person me-2"></i>Usuários
                                        @if ($modProtest?->Assignments?->isNotEmpty())
                                            <span
                                                class="badge text-bg-secondary ms-2">{{ $modProtest->Assignments?->count() }}</span>
                                        @endif
                                    </h6>

                                    <!-- User Assignment Section -->
                                    <div class="user-assignment-section">

                                        <!-- Select User with Floating Label -->
                                        <div class="form-floating mb-3">
                                            @if ($serviceId)
                                                <select class="form-select" id="selectUser" wire:model="selectedUser"
                                                    wire:loading.attr="disabled" wire:target="serviceId">
                                                    <option value="">Selecione um usuário</option>
                                                    @forelse ($userList as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}
                                                        </option>

                                                    @empty
                                                        <option value="">Nenhum usuário disponível</option>
                                                    @endforelse
                                                </select>
                                                <label for="selectUser">Usuário</label>
                                                <div class="position-absolute top-50 end-0 translate-middle-y me-3"
                                                    wire:loading wire:target="serviceId">
                                                    <div class="spinner-border spinner-border-sm text-primary"
                                                        role="status">
                                                        <span class="visually-hidden">Carregando...</span>
                                                    </div>
                                                </div>
                                                <div wire:loading.remove wire:target="serviceId">
                                                    <!-- Content loaded -->
                                                </div>
                                            @else
                                                <select class="form-select" id="selectUser" disabled>
                                                    <option value="">Selecione o tipo de atividade primeiro
                                                    </option>
                                                </select>
                                                <label for="selectUser">Usuário</label>
                                            @endif
                                        </div>

                                        <!-- Role Toggle Buttons -->
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="executorToggle" wire:model="isExecutor">
                                                    <label class="form-check-label fw-bold text-success"
                                                        for="executorToggle">
                                                        <i class="bi bi-person-gear me-1"></i>Executante
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="responsibleToggle" wire:model="isResponsible">
                                                    <label class="form-check-label fw-bold text-warning"
                                                        for="responsibleToggle">
                                                        <i class="bi bi-person-check me-1"></i>Responsável
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="engineerToggle" wire:model="isEngineer">
                                                    <label class="form-check-label fw-bold text-info"
                                                        for="engineerToggle">
                                                        <i class="bi bi-person-badge me-1"></i>Engenheiro
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Add Button -->
                                        <div class="text-end mt-3">
                                            <button type="button" class="btn btn-sm btn-primary"
                                                wire:click="addUserAssignment">
                                                <i class="bi bi-plus-circle me-1"></i>Adicionar
                                            </button>
                                        </div>

                                        <!-- Assigned Users List with Scrollbar -->
                                        <div class="assigned-users mt-3">
                                            <h6 class="text-muted mb-2">Usuários Atribuídos:</h6>
                                            <div class="user-list border border-secondary rounded p-3"
                                                style="max-height: 250px; overflow-y: auto;">
                                                <div
                                                    class="d-flex align-items-center justify-content-between border-bottom py-2">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-circle fs-5 text-primary me-2"></i>
                                                        <div>
                                                            <div class="d-flex align-items-center">
                                                                <span class="fw-medium">João Silva</span>
                                                                <div class="ms-2">
                                                                    <span
                                                                        class="badge bg-success me-1">Executante</span>
                                                                    <span class="badge bg-warning">Responsável</span>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar-plus me-1"></i>Atribuído:
                                                                15/12/2024
                                                                <span class="ms-2"><i
                                                                        class="bi bi-calendar-check me-1"></i>Conclusão:
                                                                    --</span>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        wire:click="removeUser(1)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <div
                                                    class="d-flex align-items-center justify-content-between border-bottom py-2">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-circle fs-5 text-primary me-2"></i>
                                                        <div>
                                                            <div class="d-flex align-items-center">
                                                                <span class="fw-medium">Maria Santos</span>
                                                                <div class="ms-2">
                                                                    <span class="badge bg-info">Engenheiro</span>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar-plus me-1"></i>Atribuído:
                                                                14/12/2024
                                                                <span class="ms-2"><i
                                                                        class="bi bi-calendar-check me-1"></i>Conclusão:
                                                                    16/12/2024</span>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        wire:click="removeUser(2)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="col-md-12">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-3">
                                    <i class="bi bi-chat-dots me-2"></i>OBSERVAÇÕES PARA
                                    {{ $modProtest?->protest?->nota }} - #{{ $modProtest?->med_id }}
                                    @if ($modProtest?->comments?->isNotEmpty())
                                        <span
                                            class="badge text-bg-secondary ms-2">{{ $modProtest->comments?->count() }}</span>
                                    @endif
                                </h6>

                                <!-- Comments List -->
                                <div class="comments-section border border-secondary rounded mb-3 p-2"
                                    style="max-height: 250px; overflow-y: auto;">

                                    @if ($modProtest?->Comments?->isNotEmpty())
                                        @foreach ($modProtest?->Comments as $comment)
                                            <div class="comment-container">
                                                <div
                                                    class="comment-item py-2 {{ !$loop->last ? 'border-bottom border-secondary' : '' }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="d-flex gap-2">
                                                            <div class="comment-avatar">
                                                                <i
                                                                    class="ri-user-line fs-4 text-primary align-middle"></i>
                                                            </div>
                                                            <div class="comment-content">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center w-100">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        @if ($comment->user?->email)
                                                                            <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                                                style="cursor:pointer"
                                                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')">
                                                                            </i>
                                                                        @endif
                                                                        <span
                                                                            class="fw-bold {{ $comment->user_id === auth()->user()->id ? 'text-primary' : '' }}">{{ $comment->user->name }}</span>
                                                                        <small class="text-muted">
                                                                            <i class="ri-time-line align-middle"></i>
                                                                            {{ $comment->created_at->diffForHumans() }}
                                                                        </small>
                                                                    </div>
                                                                    @if (
                                                                        ($comment->created_at->diffInHours() < 1 && $comment->id === $modProtest->comments->max('id')) ||
                                                                            auth()->user()->admin ||
                                                                            auth()->user()->superadm)
                                                                        <i class="ri-delete-bin-fill text-danger"
                                                                            style="cursor: pointer;"
                                                                            wire:click="deleteComment({{ $comment->id }})"
                                                                            title="Excluir comentário"></i>
                                                                    @endif
                                                                </div>
                                                                <p class="mb-0 text-secondary mt-1">
                                                                    {{ $comment->message }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <style>
                                                .comment-container::-webkit-scrollbar {
                                                    width: 5px;
                                                }

                                                .comment-container::-webkit-scrollbar-track {
                                                    background: #f1f1f1;
                                                }

                                                .comment-container::-webkit-scrollbar-thumb {
                                                    background: #888;
                                                    border-radius: 5px;
                                                }
                                            </style>
                                        @endforeach
                                    @else
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i>Não há observações para
                                            exibir.
                                        </div>
                                    @endif

                                </div>

                                <!-- Comment Input -->
                                <div class="comment-input">
                                    <textarea class="form-control mb-2" rows="3" wire:model.defer="comment"
                                        placeholder="Digite seu comentário..."></textarea>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-primary"
                                            wire:click.prevent="addComment">
                                            <i class="ri-send-plane-fill me-1"></i> Enviar
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
