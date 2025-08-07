@php
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        /* Cabeçalho moderno */
        .protest-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2rem 2rem 1.5rem 2rem;
            color: white;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .protest-header::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .protest-header .header-title {
            font-size: 2.3rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, .08);
        }

        .protest-header .header-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .protest-header .header-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.98rem;
        }

        .modern-card {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.09);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .modern-card-body {
            padding: 1.35rem;
        }

        .modern-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .modern-card-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .badge-status {
            font-size: 1rem;
            padding: .5em 1.3em;
        }

        .progress {
            height: 8px;
        }

        .avatar-circle {
            font-size: 14px;
            font-weight: 600;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-bubble {
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }

        .chat-container {
            height: 340px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #6c757d #f8f9fa;
        }

        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: #6c757d;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: #495057;
        }

        .table {
            font-size: .98rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        @media (max-width: 900px) {
            .protest-header {
                padding: 1rem;
            }

            .header-title {
                font-size: 1.5rem;
            }

            .modern-card-body {
                padding: .8rem;
            }

            .modern-card-value {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush

<div>
    <x-show-loading />

    {{-- ==== Cabeçalho Moderno ==== --}}
    <div class="protest-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="header-content">
                    <div class="d-flex align-items-center mb-2">
                        <div class="header-icon me-3">
                            <i class="ri-error-warning-line fs-2"></i>
                        </div>
                        <div>
                            <h1 class="header-title mb-0">
                                Reclamação #{{ $protest->nota }}
                                <span class="badge bg-light text-primary ms-2">{{ $protest->tipoNota }}</span>
                            </h1>
                            <div class="header-subtitle text-white-50">{{ $protest->cidade }} —
                                {{ $protest->txtGrpCodificacao }}</div>
                        </div>
                    </div>
                    <p class="header-description mb-0">
                        <i class="ri-information-line me-1"></i>
                        Detalhamento, progresso e interação sobre a demanda.
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-end">
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
                <span class="badge badge-status bg-{{ $status['color'] }} text-light">
                    <i class="{{ $status['icon'] }} me-1"></i>
                    {{ $status['text'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- ==== Linha dos Cartões Principais ==== --}}
    <div class="row">
        {{-- Info Básica --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-information-line me-1"></i>Informações Básicas</div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between"><span class="text-muted small">Nota:</span><span
                                class="fw-medium">{{ $protest->nota }}</span></div>
                        <div class="d-flex justify-content-between"><span
                                class="text-muted small">Município:</span><span
                                class="fw-medium">{{ $protest->cidade }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Grupo:</span><span
                                class="fw-medium">{{ $protest->txtGrpCodificacao }}</span></div>
                        <div class="border-top pt-2 mt-2">
                            <span class="text-muted small d-block">Causa:</span>
                            <span class="fw-medium small">{{ $protest->descCausa }}</span>
                            <span class="text-muted small d-block mt-1">SubCausa:</span>
                            <span class="fw-medium small">{{ $protest->descSubCausa }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cronograma --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-calendar-line me-1"></i>Cronograma</div>
                    <div class="text-center mb-3">
                        <i class="{{ $status['icon'] }} fs-3 text-{{ $status['color'] }} me-2"></i>
                        <span class="badge bg-{{ $status['color'] }} px-3 py-2">{{ $status['text'] }}</span>
                        <br>
                        @if ($dtConclusao && !$dtConclusao->isPast())
                            <small class="text-muted">{{ abs($daysDiff) }} dias restantes</small>
                        @elseif($dtConclusao)
                            <small class="text-danger">{{ abs($daysDiff) }} dias em atraso</small>
                        @endif
                    </div>
                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-play-circle-line me-1"></i>Abertura:</span>
                            <span class="fw-medium small">{{ $protest->dtAberturaNota?->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><i class="ri-flag-line me-1"></i>Conclusão:</span>
                            <span class="fw-medium small">{{ $protest->dtConclusaoDesej?->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Métricas --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-dashboard-line me-1"></i>Métricas</div>
                    @php
                        $totalMedidas = $protest->medProtests?->count() ?? 0;
                        $medidasConcluidas = $protest->medProtests?->where('statusSist', 'MEDE')->count() ?? 0;
                        $ultimaMovimentacao = $protest->medProtests?->sortByDesc('updated_at')->first();
                        $progressoPercentual =
                            $totalMedidas > 0 ? round(($medidasConcluidas / $totalMedidas) * 100) : 0;
                    @endphp
                    <div class="d-flex flex-column gap-3">
                        <div class="text-center">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted">Progresso:</span>
                                <span class="small fw-medium">{{ $progressoPercentual }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: {{ $progressoPercentual }}%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ $medidasConcluidas }}/{{ $totalMedidas }}
                                medidas</small>
                        </div>
                        <div class="border-top pt-2">
                            <div class="row text-center">
                                <div class="col-6"><span
                                        class="fs-5 fw-bold text-primary">{{ $totalMedidas }}</span><br><small
                                        class="text-muted">Total</small></div>
                                <div class="col-6"><span
                                        class="fs-5 fw-bold text-success">{{ $medidasConcluidas }}</span><br><small
                                        class="text-muted">Concluídas</small></div>
                            </div>
                            @if ($ultimaMovimentacao)
                                <div class="text-center mt-2 pt-2 border-top">
                                    <small class="text-muted"><i class="ri-time-line me-1"></i>Última atualização:
                                        {{ $ultimaMovimentacao->updated_at->diffForHumans() }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==== Obras Associadas ==== --}}


    <div class="modern-card">
        <div class="modern-card-body">
            <div class="modern-card-title mb-2"><i class="ri-attachment-line me-2"></i>Anexos & Evidências
            </div>
            <x-files.attachments :files="$protest->evidenceFiles" deleteAction="deleteFile" downloadAction="dowloadFile" />
        </div>
    </div>

    <div class="modern-card">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="modern-card-title"><i class="ri-building-line me-1"></i>Obras Associadas</span>
                <button class="btn btn-sm btn-warning"
                    wire:click.defer="$emitTo('services.oexterno.actions.protest.add-notes-relation', 'openAddNotesRelation', {{ $protest->id }})">
                    <i class="ri-add-box-fill me-1"></i>Associar
                </button>
            </div>
            @if ($protest->all_notes->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th>Nota/OV</th>
                                <th>Cliente</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($protest->all_notes as $note)
                                <tr class="text-center align-middle">
                                    <td><span
                                            class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">{{ $note->note }}</span>
                                    </td>
                                    <td class="fw-medium">{{ $note->client }}</td>
                                    <td><span class="text-muted small">{{ $note->rubrica }}</span></td>
                                    <td>{{ $note->lexp }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;"
                                            title="{{ $note->material }}">{{ $note->material }}</div>
                                    </td>
                                    <td><span
                                            class="badge bg-info bg-opacity-10 text-info">{{ $note->type_note == 2 ? $note->nstats : $note->centerjob }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" title="Remover Associação"
                                            data-bs-toggle="tooltip"
                                            wire:click.prevent="removeNoteFromProtest({{ $note->pivot->id }})"
                                            onclick="return confirm('Remover esta associação?')">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="ri-building-line fs-1 mb-3 opacity-50"></i>
                    <h5 class="mb-2">Nenhuma obra associada</h5>
                    <p class="mb-0 text-center">Clique no botão "Associar" para vincular notas ou OVs a esta
                        reclamação
                    </p>
                </div>
            @endif
        </div>
    </div>


    {{-- ==== Medidas Registradas ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="modern-card-title mb-3"><i class="ri-list-check-2 me-2"></i>Medidas Registradas</div>
            @if ($protest->medProtests?->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th>#</th>
                                <th>Status</th>
                                <th>Descrição</th>
                                <th>Data Criação</th>
                                <th>Data Fim Desejada</th>
                                <th>Data Fim</th>
                                <th>Acompanhado Por</th>
                                <th>Responsável</th>
                                <th>Situação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($protest->medProtests?->sortByDesc('dtCriacaoMedida') as $medProtest)
                                @php
                                    $isWarning = $medProtest->needsConfirmation && !$medProtest->completed;
                                    $rowClass = $isWarning ? 'table-warning' : '';
                                    $responsible = $medProtest->assignments?->where('responsible', true)->last()?->User
                                        ?->name;
                                    $userResponsible = $medProtest->assignments?->where('user', true)->last()?->User
                                        ?->name;
                                    $assignment = $medProtest->assignments?->where('user', true)->last();
                                    $resp = $medProtest->assignments?->where('responsible', true)->last();
                                    if ($assignment) {
                                        $statusSit = $assignment->completed ? 'Concluída' : 'Pendente';
                                        $badgeClass = $assignment->completed ? 'bg-success' : 'bg-warning';
                                    } else {
                                        $statusSit = 'N/A';
                                        $badgeClass = 'bg-secondary';
                                        $assignment = null;
                                    }
                                @endphp
                                <tr class="text-center align-middle {{ $rowClass }}">
                                    <td>
                                        <span
                                            class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">{{ $medProtest->med_id }}</span>
                                        @if ($medProtest->needsConfirmation)
                                            <i class="ri-eye-line fs-5 text-primary" data-bs-toggle="tooltip"
                                                title="Em acompanhamento"></i>
                                        @endif
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
                                            title="{{ $medProtest->txtCodMedida }}">{{ $medProtest->txtCodMedida }}
                                        </div>
                                    </td>
                                    <td><span
                                            class="badge bg-secondary bg-opacity-10 text-secondary">{{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        @if ($medProtest->dtFimMedidaDesej)
                                            <span
                                                class="text-muted small">{{ $medProtest->dtFimMedidaDesej->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($medProtest->dtFimMedida)
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success">{{ $medProtest->dtFimMedida->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><span class="fw-medium">{{ $responsible ?? 'N/A' }}</span></td>
                                    <td><span class="fw-medium">{{ $userResponsible ?? 'N/A' }}</span></td>
                                    <td>
                                        <span
                                            class="badge {{ $badgeClass }} bg-opacity-10 text-{{ str_replace('bg-', '', $badgeClass) }}">{{ $statusSit }}</span>
                                    </td>
                                    <td>
                                        @if (($medProtest->needsConfirmation && $medProtest->completed) || $medProtest->statusSist === 'MEDA')
                                            @if ($assignment?->completed)
                                                @if (!$resp->completed)
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
                                            <button class="btn btn-sm btn-outline-info" title="Imprimir Medida"
                                                data-bs-toggle="tooltip"
                                                onclick="window.open('{{ route('protests.print', $medProtest->id) }}', '_blank')">
                                                <i class="ri-printer-line"></i>
                                            </button>
                                        @else
                                            @if ($medProtest->Assignments->isNotEmpty())
                                                <button class="btn btn-sm btn-outline-primary"
                                                    title="Visualizar Medida" data-bs-toggle="tooltip"><i
                                                        class="ri-eye-line"></i></button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    title="Sem Medida Registrada" disabled><i
                                                        class="ri-eye-line"></i></button>
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

    {{-- ==== Interações: Comentários e Anexos ==== --}}

    <div class="modern-card">
        <div class="modern-card-body">
            <div class="modern-card-title mb-2"><i class="ri-chat-3-line me-2"></i>Discussão e Comentários
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control @error('comment') is-invalid @enderror" placeholder="Digite seu comentário..."
                    id="floatingTextarea" style="height: 80px" wire:model.defer="comment"></textarea>
                <label for="floatingTextarea">Seu comentário</label>
                @error('comment')
                    <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                @enderror
                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-primary" wire:click.prevent="addComment">
                        <i class="ri-send-plane-fill me-1"></i> Enviar Comentário
                    </button>
                </div>
            </div>
            <div class="chat-container border rounded bg-light">
                @forelse($protest->comments->sortBy('created_at') as $comment)
                    <div class="chat-message p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-circle bg-primary text-white">
                                    {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span
                                            class="fw-semibold {{ $comment->user_id === auth()->user()->id ? 'text-primary' : 'text-dark' }}">{{ $comment->user->name }}</span>
                                        @if ($comment->user?->email)
                                            <button class="btn btn-sm btn-outline-primary p-1"
                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')"
                                                title="Abrir chat no Teams">
                                                <i class="bx bxl-microsoft-teams fs-6"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <small class="text-muted"><i
                                                class="ri-time-line me-1"></i>{{ $comment->created_at->diffForHumans() }}</small>
                                        @if (
                                            ($comment->created_at->diffInHours() < 1 && $comment->id === $protest->comments->max('id')) ||
                                                auth()->user()->admin ||
                                                auth()->user()->superadm)
                                            <button class="btn btn-sm btn-outline-danger p-1"
                                                wire:click="deleteComment({{ $comment->id }})"
                                                title="Excluir comentário"
                                                onclick="return confirm('Excluir este comentário?')">
                                                <i class="ri-delete-bin-line fs-6"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div
                                    class="message-bubble p-3 rounded-3 {{ $comment->user_id === auth()->user()->id ? 'bg-primary bg-opacity-10' : 'bg-light' }}">
                                    <p class="mb-0 text-dark">{{ $comment->message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="ri-chat-3-line fs-1 mb-3 opacity-50"></i>
                        <h5 class="mb-2">Nenhum comentário ainda</h5>
                        <p class="mb-0 text-center">Seja o primeiro a comentar nesta reclamação</p>
                    </div>
                @endforelse
            </div>
        </div>


    </div>

    {{-- ==== Livewire Modals (Fora do layout principal) ==== --}}
    @livewire('services.oexterno.actions.protest.add-notes-relation', key('add-notes-relation-' . $protest->id))
    @livewire('services.oexterno.actions.protest.control-med-protest', key('control-med-protest-' . $protest->id))
</div>
