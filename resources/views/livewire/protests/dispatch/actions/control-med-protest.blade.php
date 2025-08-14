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
    aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">

            {{-- Header moderno --}}
            <div class="modal-header-modern">
                <div class="modal-header-content">
                    <div class="modal-header-icon">
                        <i class="ri-settings-5-fill"></i>
                    </div>
                    <div class="modal-header-texts">
                        <span class="modal-header-title">
                            Controle do Desdobramento:
                            <strong>{{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }}</strong>
                        </span>
                        <span class="modal-header-desc">
                            Gerencie atribuições, medidas, comentários e configuração deste processo.
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-modern" data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4 mb-4">
                    {{-- Info Básica --}}
                    <div class="col-lg-4">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-information-line me-2"></i>Informações Básicas
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between"><span class="text-muted">Nota:</span>
                                        <strong>{{ $modProtest?->protest?->nota }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between"><span
                                            class="text-muted">Município:</span>
                                        <span>{{ $modProtest?->protest?->cidade }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Grupo:</span>
                                        <span>{{ $modProtest?->protest?->txtGrpCodificacao }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Causa:</span>
                                        <span>{{ $modProtest?->protest?->descCausa }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between"><span
                                            class="text-muted">SubCausa:</span>
                                        <span>{{ $modProtest?->protest?->descSubCausa }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notas Associadas (swiper entre notas) --}}
                    <div class="col-lg-8">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-file-list-3-line me-2"></i>Notas Associadas
                                </div>
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
                                            {{ $notePage + 1 }} de {{ $modProtest?->protest?->all_notes?->count() }}
                                        </small>
                                        <div class="btn-group" role="group">
                                            <button wire:click="previousNote" class="btn btn-sm btn-outline-primary"
                                                {{ $notePage <= 0 ? 'disabled' : '' }}>
                                                <i class="ri-arrow-left-s-line"></i>
                                            </button>
                                            <button wire:click="nextNote" class="btn btn-sm btn-outline-primary"
                                                {{ $notePage >= $modProtest?->protest?->all_notes?->count() - 1 ? 'disabled' : '' }}>
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
                </div>

                {{-- Configuração e usuários --}}
                <div class="row g-4 mb-4">

                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-settings-2-line me-2"></i>Configuração e Atribuição de Usuários
                                </div>

                                {{-- Configuração do Serviço --}}
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="serviceSelect" wire:model="serviceId">
                                                <option value="">Todos os serviços</option>
                                                @forelse ($serviceList as $service)
                                                    <option value="{{ $service->uuid }}">{{ $service->service }}
                                                    </option>
                                                @empty
                                                    <option value="">Nenhum serviço disponível</option>
                                                @endforelse
                                                <option value="construction">--- Construção ---</option>
                                                <option value="maintenance">--- Engenharia ---</option>
                                            </select>
                                            <label for="serviceSelect">Filtrar por Serviço</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 align-items-center h-100">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="requireTracking"
                                                    wire:model="modProtest.needsConfirmation">
                                                <label class="form-check-label fw-medium" for="requireTracking">
                                                    <i class="ri-eye-line me-1"></i>Acompanhamento
                                                </label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="needsConfirmation"
                                                    wire:model="modProtest.needsEvidence">
                                                <label class="form-check-label fw-medium" for="needsConfirmation">
                                                    <i class="ri-camera-line me-1"></i>Exigir Evidência
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Atribuição de Usuários --}}
                                <div class="border-top pt-4">
                                    <div
                                        class="modern-card-title d-flex justify-content-between align-items-center mb-3">
                                        <span><i class="ri-team-line me-2"></i>Usuários Atribuídos</span>
                                        @if ($modProtest?->Assignments?->isNotEmpty() || !empty($usersTemporarilyAssigned))
                                            <span class="badge bg-primary">
                                                {{ ($modProtest?->Assignments?->count() ?? 0) + count($usersTemporarilyAssigned ?? []) }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Busca e Seleção de Usuário --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-5">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="userSearch"
                                                    wire:model.debounce.300ms="userSearch"
                                                    placeholder="Buscar usuário...">
                                                <label for="userSearch">Buscar usuário por nome</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
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
                                                        role="status"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="engineerToggle"
                                                    wire:model="isEngineer">
                                                <label class="form-check-label fw-medium text-info"
                                                    for="engineerToggle">
                                                    <i class="ri-user-star-line me-1"></i>Engenheiro
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-1">
                                            <button type="button" class="btn btn-primary w-100 h-100"
                                                wire:click="addUserAssignment" @disabled($modProtest?->completed)>
                                                <i class="ri-user-add-line me-1"></i>Add
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Lista de Usuários Atribuídos --}}
                                    <div class="border rounded p-3 bg-light comments-container">
                                        @php
                                            $hasUsers =
                                                !empty($usersTemporarilyAssigned) ||
                                                $modProtest?->Assignments?->isNotEmpty();
                                        @endphp
                                        @if ($hasUsers)
                                            <div class="d-flex flex-column gap-2">
                                                {{-- Temporários --}}
                                                @if (!empty($usersTemporarilyAssigned))
                                                    @foreach ($usersTemporarilyAssigned as $user)
                                                        <div
                                                            class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="ri-user-line text-primary"></i>
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
                                                                <i class="ri-close-line"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                {{-- Salvos --}}
                                                @if ($modProtest?->assignments?->isNotEmpty())
                                                    @foreach ($modProtest?->assignments as $assignment)
                                                        @php
                                                            $typeUser = $assignment->monitoring
                                                                ? 'Engenheiro'
                                                                : ($assignment->responsible
                                                                    ? 'Responsável'
                                                                    : 'Usuário');
                                                            $rowClass = $assignment->monitoring
                                                                ? 'bg-info'
                                                                : ($assignment->responsible
                                                                    ? 'bg-warning'
                                                                    : 'bg-success');
                                                        @endphp
                                                        <div
                                                            class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="ri-user-follow-line text-success"></i>
                                                                <span
                                                                    class="fw-medium">{{ $assignment->getRelation('user')->name }}</span>
                                                                <span
                                                                    class="badge {{ $rowClass }}">{{ $typeUser }}</span>
                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="removeUserAssignment('{{ $assignment->id }}')"
                                                                @disabled($modProtest?->completed)>
                                                                <i class="ri-close-line"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-3">
                                                <i class="ri-user-unfollow-line fs-3"></i>
                                                <p class="mb-0 mt-2">Nenhum usuário atribuído</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Comentários/Observações --}}
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="modern-card">
                                <div class="modern-card-body">
                                    <div class="modern-card-title d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="ri-chat-3-line me-2"></i>
                                            Observações - {{ $modProtest?->protest?->nota }}
                                            #{{ $modProtest?->med_id }}
                                        </span>
                                        @if ($modProtest?->comments?->isNotEmpty())
                                            <span
                                                class="badge bg-primary">{{ $modProtest->comments?->count() }}</span>
                                        @endif
                                    </div>
                                    <div class="comment-input-section mb-4">
                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="commentInput" rows="3" wire:model.defer="comment"
                                                placeholder="Digite seu comentário..." style="height: 80px;"></textarea>
                                            <label for="commentInput">Digite seu comentário...</label>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-primary px-4"
                                                wire:click.prevent="addComment">
                                                <i class="ri-send-plane-2-line me-2"></i>Enviar Comentário
                                            </button>
                                        </div>
                                    </div>
                                    <div class="comments-container bg-light rounded p-3">
                                        @if ($modProtest?->Comments?->isNotEmpty())
                                            @foreach ($modProtest?->Comments->sortByDesc('created_at') as $comment)
                                                <div class="comment-item mb-3 p-3 bg-white rounded border">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="avatar-circle bg-primary text-white">
                                                                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                                            </div>
                                                            <div>
                                                                <span
                                                                    class="fw-medium {{ $comment->user_id === auth()->user()->id ? 'text-primary' : '' }}">
                                                                    {{ $comment->user->name }}
                                                                </span>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    @if ($comment->user?->email)
                                                                        <i class="ri-microsoft-teams-line text-primary"
                                                                            style="cursor:pointer"
                                                                            onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')"
                                                                            title="Abrir no Teams"></i>
                                                                    @endif
                                                                    <small class="text-muted">
                                                                        <i class="ri-time-line me-1"></i>
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
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <p class="mb-0 text-dark">{{ $comment->message }}</p>
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
                    </div>

                    {{-- Botões de ação --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="modern-card">
                                <div class="modern-card-body">
                                    <div class="modern-card-title">
                                        <i class="ri-checkbox-multiple-line me-2"></i>Ações do Desdobramento
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-success w-100 py-3"
                                                wire:click="saveMeasures" @disabled($modProtest?->completed)>
                                                <i class="ri-save-3-fill me-2"></i>
                                                <span class="fw-medium">Salvar Medidas</span>
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-outline-secondary w-100 py-3"
                                                wire:click="cancelChanges">
                                                <i class="ri-close-circle-line me-2"></i>
                                                <span class="fw-medium">Cancelar Alterações</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /modal-body -->
            </div>
        </div>
    </div>
