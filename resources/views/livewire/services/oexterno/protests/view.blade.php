<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />



    <div class="card mb-0 shadow rounded-bottom-0" style='z-index: 1;'>
        <div
            class="card-header {{ $protest->tipoNota == 'OU' ? 'text-bg-danger' : 'edp-bg-sprucegreen-70 text-edp-verde' }} py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reclamação #{{ $protest->nota }}</h5>
                <span class="badge bg-light text-primary">{{ $protest->tipoNota }}</span>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-3">
                <!-- Card de Informações Básicas -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary bg-opacity-10 border-0">
                            <h6 class="mb-0 text-primary fw-semibold">
                                <i class="ri-information-line me-2"></i>Informações Básicas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Nota:</span>
                                    <span class="fw-medium">{{ $protest->nota }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Município:</span>
                                    <span class="fw-medium">{{ $protest->cidade }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Grupo:</span>
                                    <span class="fw-medium">{{ $protest->txtGrpCodificacao }}</span>
                                </div>
                                <div class="border-top pt-2 mt-2">
                                    <div class="mb-1">
                                        <span class="text-muted small d-block">Causa:</span>
                                        <span class="fw-medium small">{{ $protest->descCausa }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">SubCausa:</span>
                                        <span class="fw-medium small">{{ $protest->descSubCausa }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $now = now();
                    $dtConclusao = $protest->dtConclusaoDesej;
                    $daysDiff = $dtConclusao ? $now->diffInDays($dtConclusao, false) : 0;

                    if ($dtConclusao && $dtConclusao->isPast()) {
                        $status = ['color' => 'danger', 'text' => 'Vencida', 'icon' => 'ri-close-circle-line'];
                    } elseif ($daysDiff > 3) {
                        $status = ['color' => 'success', 'text' => 'No Prazo', 'icon' => 'ri-check-circle-line'];
                    } else {
                        $status = ['color' => 'warning', 'text' => 'Vencendo', 'icon' => 'ri-time-line'];
                    }
                @endphp

                <!-- Card de Datas -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-info bg-opacity-10 border-0">
                            <h6 class="mb-0 text-info fw-semibold">
                                <i class="ri-calendar-line me-2"></i>Cronograma
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-3">
                                <div class="text-center">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="{{ $status['icon'] }} fs-4 text-{{ $status['color'] }} me-2"></i>
                                        <span
                                            class="badge bg-{{ $status['color'] }} px-3 py-2">{{ $status['text'] }}</span>
                                    </div>
                                    @if ($dtConclusao && !$dtConclusao->isPast())
                                        <small class="text-muted">{{ abs($daysDiff) }} dias restantes</small>
                                    @elseif($dtConclusao)
                                        <small class="text-danger">{{ abs($daysDiff) }} dias em atraso</small>
                                    @endif
                                </div>

                                <div class="border-top pt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">
                                            <i class="ri-play-circle-line me-1"></i>Abertura:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $protest->dtAberturaNota?->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Conclusão:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $protest->dtConclusaoDesej?->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Métricas -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-success bg-opacity-10 border-0">
                            <h6 class="mb-0 text-success fw-semibold">
                                <i class="ri-dashboard-line me-2"></i>Métricas
                            </h6>
                        </div>
                        <div class="card-body">
                            @php
                                $totalMedidas = $protest->medProtests?->count() ?? 0;
                                $medidasConcluidas = $protest->medProtests?->where('statusSist', 'MEDE')->count() ?? 0;
                                $ultimaMovimentacao = $protest->medProtests?->sortByDesc('updated_at')->first();
                                $progressoPercentual =
                                    $totalMedidas > 0 ? round(($medidasConcluidas / $totalMedidas) * 100) : 0;
                            @endphp

                            <div class="d-flex flex-column gap-3">
                                <!-- Progresso das Medidas -->
                                <div class="text-center">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted">Progresso:</span>
                                        <span class="small fw-medium">{{ $progressoPercentual }}%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width: {{ $progressoPercentual }}%"></div>
                                    </div>
                                    <small
                                        class="text-muted mt-1 d-block">{{ $medidasConcluidas }}/{{ $totalMedidas }}
                                        medidas</small>
                                </div>

                                <div class="border-top pt-2">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="d-flex flex-column">
                                                <span class="fs-4 fw-bold text-primary">{{ $totalMedidas }}</span>
                                                <small class="text-muted">Total</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex flex-column">
                                                <span class="fs-4 fw-bold text-success">{{ $medidasConcluidas }}</span>
                                                <small class="text-muted">Concluídas</small>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($ultimaMovimentacao)
                                        <div class="text-center mt-2 pt-2 border-top">
                                            <small class="text-muted">
                                                <i class="ri-time-line me-1"></i>
                                                Última atualização:
                                                {{ $ultimaMovimentacao->updated_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning bg-opacity-10 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-warning fw-semibold">
                                    <i class="ri-building-line me-2"></i>Obras Associadas
                                </h6>
                                <button class="btn btn-sm btn-warning" title="Associar Nota/OV" data-bs-toggle="tooltip"
                                    wire:click.defer="$emitTo('services.oexterno.actions.protest.add-notes-relation', 'openAddNotesRelation', {{ $protest->id }})">
                                    <i class="ri-add-box-fill me-1"></i>Associar
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            @if ($protest->all_notes->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th class="fw-semibold">Nota/OV</th>
                                                <th class="fw-semibold">Cliente</th>
                                                <th class="fw-semibold">Rubrica</th>
                                                <th class="fw-semibold">Município</th>
                                                <th class="fw-semibold">Descrição</th>
                                                <th class="fw-semibold">Status</th>
                                                <th class="fw-semibold">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($protest->all_notes as $note)
                                                <tr class="text-center align-middle">
                                                    <td>
                                                        <span
                                                            class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">
                                                            {{ $note->note }}
                                                        </span>
                                                    </td>
                                                    <td class="fw-medium">{{ $note->client }}</td>
                                                    <td>
                                                        <span class="text-muted small">{{ $note->rubrica }}</span>
                                                    </td>
                                                    <td>{{ $note->lexp }}</td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;"
                                                            title="{{ $note->material }}">
                                                            {{ $note->material }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                            {{ $note->type_note == 2 ? $note->nstats : $note->centerjob }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                            title="Remover Associação" data-bs-toggle="tooltip"
                                                            wire:click.prevent="removeNoteFromProtest({{ $note->pivot->id }})"
                                                            onclick="return confirm('Tem certeza que deseja remover esta associação?')">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div
                                    class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                                    <i class="ri-building-line fs-1 mb-3 opacity-50"></i>
                                    <h5 class="mb-2">Nenhuma obra associada</h5>
                                    <p class="mb-0 text-center">Clique no botão "Associar" para vincular notas ou OVs a
                                        esta reclamação</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary bg-opacity-10 border-0">
                            <h6 class="mb-0 text-primary fw-semibold">
                                <i class="ri-chat-3-line me-2"></i>Adicionar Comentário
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-floating mb-3">
                                <textarea class="form-control @error('comment') is-invalid @enderror" placeholder="Digite seu comentário..."
                                    id="floatingTextarea" style="height: 120px" wire:model.defer="comment"></textarea>
                                <label for="floatingTextarea">Seu comentário</label>

                            </div>
                            @error('comment')
                                <div class="invalid-feedback d-block mb-3">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" wire:click.prevent="addComment">
                                    <i class="ri-send-plane-fill me-1"></i> Enviar Comentário
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary bg-opacity-10 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-primary fw-semibold">
                                    <i class="ri-chat-3-line me-2"></i>Discussão - {{ $protest->nota }}
                                </h6>
                                @if ($protest->comments->isNotEmpty())
                                    <span class="badge bg-primary">{{ $protest->comments->count() }}
                                        comentários</span>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="chat-container" style="height: 400px; overflow-y: auto;">
                                @forelse($protest->comments->sortBy('created_at') as $comment)
                                    <div class="chat-message p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <div class="d-flex gap-3">
                                            <!-- Avatar -->
                                            <div class="flex-shrink-0">
                                                <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px; border-radius: 50%;">
                                                    {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                                                </div>
                                            </div>

                                            <!-- Message Content -->
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span
                                                            class="fw-semibold {{ $comment->user_id === auth()->user()->id ? 'text-primary' : 'text-dark' }}">
                                                            {{ $comment->user->name }}
                                                        </span>
                                                        @if ($comment->user?->email)
                                                            <button class="btn btn-sm btn-outline-primary p-1"
                                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')"
                                                                title="Abrir chat no Teams">
                                                                <i class="bx bxl-microsoft-teams fs-6"></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted">
                                                            <i class="ri-time-line me-1"></i>
                                                            {{ $comment->created_at->diffForHumans() }}
                                                        </small>
                                                        @if (
                                                            ($comment->created_at->diffInHours() < 1 && $comment->id === $protest->comments->max('id')) ||
                                                                auth()->user()->admin ||
                                                                auth()->user()->superadm)
                                                            <button class="btn btn-sm btn-outline-danger p-1"
                                                                wire:click="deleteComment({{ $comment->id }})"
                                                                title="Excluir comentário"
                                                                onclick="return confirm('Tem certeza que deseja excluir este comentário?')">
                                                                <i class="ri-delete-bin-line fs-6"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Message Text -->
                                                <div
                                                    class="message-bubble p-3 rounded-3 {{ $comment->user_id === auth()->user()->id ? 'bg-primary bg-opacity-10' : 'bg-light' }}">
                                                    <p class="mb-0 text-dark">{{ $comment->message }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                                        <i class="ri-chat-3-line fs-1 mb-3 opacity-50"></i>
                                        <h5 class="mb-2">Nenhum comentário ainda</h5>
                                        <p class="mb-0 text-center">Seja o primeiro a comentar nesta reclamação</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .chat-container {
                    scrollbar-width: thin;
                    scrollbar-color: #6c757d #f8f9fa;
                }

                .chat-container::-webkit-scrollbar {
                    width: 6px;
                }

                .chat-container::-webkit-scrollbar-track {
                    background: #f8f9fa;
                    border-radius: 3px;
                }

                .chat-container::-webkit-scrollbar-thumb {
                    background: #6c757d;
                    border-radius: 3px;
                }

                .chat-container::-webkit-scrollbar-thumb:hover {
                    background: #495057;
                }

                .chat-message:hover {
                    background-color: #f8f9fa;
                }

                .avatar-circle {
                    font-size: 14px;
                    font-weight: 600;
                }

                .message-bubble {
                    border: 1px solid #e9ecef;
                    transition: all 0.2s ease;
                }

                .chat-container {
                    scroll-behavior: smooth;
                }
            </style>
        </div>
    </div>
    <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0">
        <div class="card-header bg-info bg-opacity-10 border-0">
            <h6 class="mb-0 text-info fw-semibold">
                <i class="ri-attachment-line me-2"></i>Anexos e Evidências
            </h6>
        </div>
        <div class="card-body">
            <x-files.attachments :files="$protest->evidenceFiles" deleteAction="deleteFile" downloadAction="dowloadFile"
                card="" />
        </div>
    </div>
    <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0">
        <div class="card-header bg-primary bg-opacity-10 border-0">
            <h6 class="mb-0 text-primary fw-semibold">
                <i class="ri-list-check-2 me-2"></i>Medidas Registradas
            </h6>
        </div>
        <div class="card-body p-0">
            @if ($protest->medProtests?->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="fw-semibold">#</th>
                                <th class="fw-semibold">Status</th>
                                <th class="fw-semibold">Descrição</th>
                                <th class="fw-semibold">Data Criação</th>
                                <th class="fw-semibold">Data Fim Desejada</th>
                                <th class="fw-semibold">Data Fim</th>
                                <th class="fw-semibold">Acompanhado Por</th>
                                <th class="fw-semibold">Responsável</th>
                                <th class="fw-semibold">Situação</th>
                                <th class="fw-semibold">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($protest->medProtests?->sortByDesc('dtCriacaoMedida') as $medProtest)
                                @php
                                    $isWarning = $medProtest->needsConfirmation && !$medProtest->completed;
                                    $rowClass = $isWarning ? 'table-warning' : '';
                                @endphp
                                <tr class="text-center align-middle {{ $rowClass }}">
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span
                                                class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">
                                                {{ $medProtest->med_id }}
                                            </span>
                                            @if ($medProtest->needsConfirmation)
                                                <i class="ri-eye-line fs-5 text-primary" data-bs-toggle="tooltip"
                                                    title="Em acompanhamento"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($medProtest->statusSist === 'MEDA')
                                            <span class="badge bg-success">ABERTO</span>
                                        @else
                                            <span class="badge bg-secondary">FECHADO</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;"
                                            title="{{ $medProtest->txtCodMedida }}">
                                            {{ $medProtest->txtCodMedida }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            {{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($medProtest->dtFimMedidaDesej)
                                            <span class="text-muted small">
                                                {{ $medProtest->dtFimMedidaDesej->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($medProtest->dtFimMedida)
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                {{ $medProtest->dtFimMedida->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $responsible = $medProtest->assignments?->where('responsible', true)->last()
                                                ?->User?->name;
                                        @endphp
                                        <span class="fw-medium">{{ $responsible ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $userResponsible = $medProtest->assignments?->where('user', true)->last()
                                                ?->User?->name;
                                        @endphp
                                        <span class="fw-medium">{{ $userResponsible ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $assignment = $medProtest->assignments?->where('user', true)->last();
                                            $responsible = $medProtest->assignments
                                                ?->where('responsible', true)
                                                ->last();

                                            if ($assignment) {
                                                $status = $assignment->completed ? 'Concluída' : 'Pendente';
                                                $badgeClass = $assignment->completed ? 'bg-success' : 'bg-warning';
                                            } else {
                                                $status = 'N/A';
                                                $badgeClass = 'bg-secondary';
                                                $assignment = null;
                                            }
                                        @endphp
                                        <span
                                            class="badge {{ $badgeClass }} bg-opacity-10 text-{{ str_replace('bg-', '', $badgeClass) }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td>

                                        @if (($medProtest->needsConfirmation && $medProtest->completed) || $medProtest->statusSist === 'MEDA')
                                            @if ($assignment?->completed)
                                                @if (!$responsible->completed)
                                                    <button class="btn btn-sm btn-outline-success"
                                                        title="Aprovar Medida" data-bs-toggle="tooltip"
                                                        wire:click.prevent="approveMed({{ $medProtest->id }})">
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        title="Rejeitar Medida" data-bs-toggle="tooltip"
                                                        wire:click="rejectMed({{ $medProtest->id }})">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        title="Visualizar Medida" data-bs-toggle="tooltip">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                @endif
                                            @else
                                                <button class="btn btn-sm btn-outline-primary"
                                                    title="Gerenciar Medida" data-bs-toggle="tooltip"
                                                    wire:click.prevent="$emitTo('services.oexterno.actions.protest.control-med-protest', 'openModProtestControl', {{ $medProtest->id }})">
                                                    <i class="ri-play-circle-line"></i>
                                                </button>
                                            @endif
                                        @else
                                            @if ($medProtest->Assignments->isNotEmpty())
                                                <button class="btn btn-sm btn-outline-primary"
                                                    title="Visualizar Medida" data-bs-toggle="tooltip">
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    title="Sem Medida Registrada" data-bs-toggle="tooltip"
                                                    @disabled(true)>
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="ri-list-check-2 fs-1 mb-3 opacity-50"></i>
                    <h5 class="mb-2">Nenhuma medida registrada</h5>
                    <p class="mb-0 text-center">Não há medidas cadastradas para esta reclamação</p>
                </div>
            @endif
        </div>
    </div>



</div>




{{-- Livewire Components --}}
@livewire('services.oexterno.actions.protest.add-notes-relation', key('add-notes-relation-' . $protest->id))
@livewire('services.oexterno.actions.protest.control-med-protest', key('control-med-protest-' . $protest->id))

{{-- Modal de Controle de Medidas --}}
</div>
