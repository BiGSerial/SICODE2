<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="card mb-0 shadow rounded-bottom-0" style='z-index: 1;'>
        <div
            class="card-header {{ $medProtest->protest->tipoNota == 'OU' ? 'text-bg-danger' : 'edp-bg-sprucegreen-70 text-edp-verde' }} py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reclamação #{{ $medProtest->protest->nota }} - #{{ $medProtest->med_id }}</h5>
                <span class="badge bg-light text-primary">{{ $medProtest->protest->tipoNota }}</span>
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
                                    <span class="fw-medium">{{ $medProtest->protest->nota }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Município:</span>
                                    <span class="fw-medium">{{ $medProtest->protest->cidade }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Grupo:</span>
                                    <span class="fw-medium">{{ $medProtest->protest->txtGrpCodificacao }}</span>
                                </div>
                                <div class="border-top pt-2 mt-2">
                                    <div class="mb-1">
                                        <span class="text-muted small d-block">Causa:</span>
                                        <span class="fw-medium small">{{ $medProtest->protest->descCausa }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">SubCausa:</span>
                                        <span class="fw-medium small">{{ $medProtest->protest->descSubCausa }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $now = now();
                    $dtConclusao = $medProtest->dtFimMedida;
                    $daysDiff = $dtConclusao ? $now->diffInDays($dtConclusao, false) : 0;

                    if ($dtConclusao && $dtConclusao->isPast($medProtest->protest->dtConclusaoDesej)) {
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
                                    @if ($dtConclusao && !$dtConclusao->isPast($medProtest->protest->dtConclusaoDesej))
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
                                            class="fw-medium small">{{ $medProtest->protest->dtAberturaNota?->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center  mb-2">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Conclusão Desejada:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $medProtest->protest->dtConclusaoDesej?->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Conclusão:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $medProtest->dtFimMedida?->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $now = now();
                    $dtConclusao = $medProtest->dtFimMedida;
                    $daysDiff = $dtConclusao ? $medProtest->dtFimMedidaDesej->diffInDays($dtConclusao, false) : 0;
                    $assignment = $medProtest->assignments?->where('user', true)->where('completed', true)->first();

                    if ($dtConclusao && $dtConclusao->isPast($medProtest->dtFimMedidaDesej)) {
                        $status = ['color' => 'danger', 'text' => 'Vencida', 'icon' => 'ri-close-circle-line'];
                    } elseif ($daysDiff > 3) {
                        $status = ['color' => 'success', 'text' => 'No Prazo', 'icon' => 'ri-check-circle-line'];
                    } else {
                        $status = ['color' => 'warning', 'text' => 'Vencendo', 'icon' => 'ri-time-line'];
                    }
                @endphp
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-success bg-opacity-10 border-0">
                            <h6 class="mb-0 text-success fw-semibold">
                                <i class="ri-calendar-line me-2"></i>Cronograma Esta Medida
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
                                            class="fw-medium small">{{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center  mb-2">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Conclusão Desejada:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $medProtest->dtFimMedidaDesej?->format('d/m/Y') }}</span>

                                    </div>
                                    <div class="d-flex justify-content-between align-items-center  mb-2">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Conclusão:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $medProtest->dtFimMedida?->format('d/m/Y') }}</span>

                                    </div>
                                    <div class="d-flex justify-content-between align-items-cente">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Usuario Responsável:
                                        </span>
                                        <span class="fw-medium small">{{ $assignment?->User?->name }}</span>

                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">
                                            <i class="ri-flag-line me-1"></i>Conclusão Sicode:
                                        </span>
                                        <span
                                            class="fw-medium small">{{ $assignment?->ended_at?->format('d/m/Y H:i:s') }}</span>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning bg-opacity-10 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-warning fw-semibold">
                                    <i class="ri-building-line me-2"></i>Obras Associadas
                                </h6>
                                <button class="btn btn-sm btn-warning" title="Associar Nota/OV" data-bs-toggle="tooltip"
                                    wire:click.defer="$emitTo('protests.actions.protest.add-notes-relation', 'openAddNotesRelation', {{ $medProtest->id }})"
                                    @disabled($medProtest->completed)>
                                    <i class="ri-add-box-fill me-1"></i>Associar
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            @if ($medProtest->Notes->isNotEmpty())
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
                                            @foreach ($medProtest->Notes as $note)
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
                                                            wire:click.prevent="removeNoteFromProtest({{ $note->id }})"
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
                                    <i class="ri-chat-3-line me-2"></i>Discussão - {{ $medProtest->protest->nota }}
                                </h6>
                                @if ($medProtest->comments->isNotEmpty())
                                    <span class="badge bg-primary">{{ $medProtest->comments->count() }}
                                        comentários</span>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="chat-container" style="height: 400px; overflow-y: auto;">
                                @forelse($medProtest->comments->sortBy('created_at') as $comment)
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
                                                            ($comment->created_at->diffInHours() < 1 && $comment->id === $medProtest->protest->comments->max('id')) ||
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
        </div>
    </div>

    <style>
        .info-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .table-row-hover:hover {
            background-color: rgba(13, 110, 253, 0.05);
            transition: background-color 0.3s ease;
        }

        .comment-item {
            transition: all 0.3s ease;
        }

        .comment-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }

        .comments-container::-webkit-scrollbar {
            width: 6px;
        }

        .comments-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }

        .comments-container::-webkit-scrollbar-thumb {
            background: rgba(13, 110, 253, 0.3);
            border-radius: 3px;
        }

        .comments-container::-webkit-scrollbar-thumb:hover {
            background: rgba(13, 110, 253, 0.5);
        }

        /*
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        } */
    </style>

    <div class="row g-1">
        @if (false)
            <div class="col-md-7">
                <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0 ">
                    <div class="card-body">
                        <div class="border rounded py-3 border-secondary">
                            <h6 class="text-muted mb-2 ms-2 text-primary">ANEXAR ARQUIVOS:</h6>

                            <div class="px-3" x-data="{
                                isUploading: false,
                                progress: 0,
                                totalSize: 0,
                                uploaded: 0,
                                human(bytes) {
                                    const u = ['B', 'KB', 'MB', 'GB', 'TB'];
                                    let i = 0;
                                    while (bytes >= 1024 && i < u.length - 1) {
                                        bytes /= 1024;
                                        i++
                                    }
                                    return (i ? bytes.toFixed(2) : bytes.toFixed(0)) + ' ' + u[i];
                                }
                            }"
                                x-on:livewire-upload-start="
            isUploading = true;
            totalSize = [...$refs.fileInput.files].reduce((s,f)=> s + f.size, 0);
            progress = 0; uploaded = 0;
         "
                                x-on:livewire-upload-progress="
            progress = $event.detail.progress;
            uploaded = Math.round(totalSize * (progress/100));
         "
                                x-on:livewire-upload-error="isUploading=false; progress=0; uploaded=0"
                                x-on:livewire-upload-finish="
            progress = 100; uploaded = totalSize;
            setTimeout(()=> isUploading=false, 600);
         ">

                                <div class="upload-zone p-4 border-2 border-dashed border-primary rounded-3 text-center bg-light position-relative overflow-hidden @error('files.*') border-danger @enderror"
                                    id="uploadZone" ondragover="handleDragOver(event)" ondrop="handleDrop(event)"
                                    ondragenter="handleDragEnter(event)" ondragleave="handleDragLeave(event)"
                                    onclick="document.getElementById('fileInput').click()">
                                    <div class="upload-zone-bg"></div>
                                    <div class="position-relative">
                                        <div class="upload-icon mb-3">
                                            <i class="ri-cloud-line fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="text-primary fw-bold mb-2">Arraste arquivos aqui ou clique para
                                            selecionar</h5>
                                        <p class="text-muted mb-3">Formatos aceitos:
                                            {{ mb_strtoupper(implode(', ', $filesConfig['allowedTypes'])) }}</p>

                                        <input type="file"
                                            class="form-control d-none @error('files.*') is-invalid @enderror"
                                            id="fileInput" x-ref="fileInput" multiple
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt"
                                            wire:model="files">

                                        <button type="button" class="btn btn-primary btn-lg px-4"
                                            onclick="event.stopPropagation(); document.getElementById('fileInput').click()">
                                            <i class="ri-folder-open-line me-2"></i>
                                            Selecionar Arquivos
                                        </button>

                                        <div class="mt-2">
                                            <small class="text-muted">Máximo: {{ $filesConfig['maxSize'] / 1024 }}MB
                                                por
                                                arquivo</small>
                                        </div>

                                        @error('files.*')
                                            <div class="alert alert-danger mt-3 mb-0 py-2">
                                                <i class="ri-error-warning-line me-2"></i>
                                                <small>{{ $message }}</small>
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- PROGRESS -->
                                <div class="my-0 py-0" x-show="isUploading" style="display:none;">
                                    <div class="progress position-relative"
                                        style="height:4px; border-radius:2px; overflow:hidden;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" :style="`width:${progress}%`" :aria-valuenow="progress"
                                            aria-valuemin="0" aria-valuemax="100"
                                            style="background:linear-gradient(45deg,#007bff,#0056b3); transition:width .3s ease;">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <i class="ri-upload-line me-1"></i>
                                            Enviando arquivos...
                                        </small>
                                        <small class="text-primary fw-semibold"
                                            x-text="`${progress}% - ${human(uploaded)} de ${human(totalSize)}`">
                                        </small>
                                    </div>
                                </div>

                                @if ($tempFiles && count($tempFiles) > 0)
                                    <div class="mb-4 mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary fw-bold mb-0">
                                                <i class="ri-file-list-3-line me-2"></i>
                                                Arquivos Selecionados
                                            </h6>
                                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                                {{ count($tempFiles) }}
                                                {{ count($tempFiles) == 1 ? 'arquivo' : 'arquivos' }}
                                            </span>
                                        </div>

                                        <div class="files-container mt-3">
                                            @foreach ($tempFiles as $index => $file)
                                                <div class="file-item card border-0 shadow-sm mb-2">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="file-icon-wrapper me-3">
                                                                    <div class="file-icon {{ $this->getFileIconClass($file->getClientOriginalExtension()) }} rounded-2 d-flex align-items-center justify-content-center"
                                                                        style="width:45px; height:45px;">
                                                                        <i
                                                                            class="{{ $this->getFileIcon($file->getClientOriginalExtension()) }} fs-4"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-1 fw-semibold">
                                                                        {{ $file->getClientOriginalName() }}</h6>
                                                                    <div
                                                                        class="d-flex align-items-center text-muted small">
                                                                        <i class="ri-file-line me-1"></i>
                                                                        <span
                                                                            class="me-3">{{ $this->formatFileSize($file->getSize()) }}</span>
                                                                        <i class="ri-check-line text-success me-1"></i>
                                                                        <span class="text-success">Pronto para
                                                                            upload</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-sm rounded-pill"
                                                                title="Remover arquivo"
                                                                wire:click="removeFile({{ $index }})">
                                                                <i class="ri-close-line"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-3">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                                            wire:click="clearAllFiles">
                                            <i class="ri-delete-bin-line me-2"></i>
                                            Limpar Tudo
                                        </button>
                                        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                            wire:click="saveFiles">
                                            <i class="ri-upload-2-line me-2"></i>
                                            Salvar Arquivos
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <style>
                                .upload-zone {
                                    transition: all 0.3s ease;
                                    cursor: pointer;
                                    background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
                                }

                                .upload-zone:hover {
                                    border-color: var(--bs-primary) !important;
                                    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.15) 100%);
                                    transform: translateY(-2px);
                                    box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15);
                                }

                                .upload-zone-bg {
                                    position: absolute;
                                    top: -50%;
                                    left: -50%;
                                    width: 200%;
                                    height: 200%;
                                    background: radial-gradient(circle, rgba(13, 110, 253, 0.1) 0%, transparent 70%);
                                    animation: float 6s ease-in-out infinite;
                                    pointer-events: none;
                                }

                                @keyframes float {

                                    0%,
                                    100% {
                                        transform: translateY(0px) rotate(0deg);
                                    }

                                    50% {
                                        transform: translateY(-10px) rotate(180deg);
                                    }
                                }

                                .upload-icon {
                                    animation: bounce 2s infinite;
                                }

                                @keyframes bounce {

                                    0%,
                                    20%,
                                    50%,
                                    80%,
                                    100% {
                                        transform: translateY(0);
                                    }

                                    40% {
                                        transform: translateY(-10px);
                                    }

                                    60% {
                                        transform: translateY(-5px);
                                    }
                                }

                                .file-item {
                                    transition: all 0.3s ease;
                                    border-left: 4px solid transparent !important;
                                }

                                .file-item:hover {
                                    transform: translateX(5px);
                                    border-left-color: var(--bs-primary) !important;
                                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
                                }

                                .progress-bar {
                                    background: linear-gradient(45deg, #007bff, #0056b3);
                                }

                                .btn {
                                    transition: all 0.3s ease;
                                }

                                .btn:hover {
                                    transform: translateY(-2px);
                                }

                                .file-item:hover .file-icon {
                                    transform: scale(1.1);
                                }

                                .upload-zone.drag-over {
                                    border-color: var(--bs-success) !important;
                                    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.15) 100%);
                                    transform: scale(1.02);
                                }
                            </style>

                            <script>
                                function handleDragOver(e) {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'copy';
                                }

                                function handleDragEnter(e) {
                                    e.preventDefault();
                                    document.getElementById('uploadZone').classList.add('drag-over');
                                }

                                function handleDragLeave(e) {
                                    e.preventDefault();
                                    if (!e.currentTarget.contains(e.relatedTarget)) {
                                        document.getElementById('uploadZone').classList.remove('drag-over');
                                    }
                                }

                                function handleDrop(e) {
                                    e.preventDefault();
                                    document.getElementById('uploadZone').classList.remove('drag-over');
                                    const files = e.dataTransfer.files;
                                    if (files.length) {
                                        const fileInput = document.getElementById('fileInput');
                                        fileInput.files = files;
                                        const changeEvent = new Event('change', {
                                            bubbles: true
                                        });
                                        fileInput.dispatchEvent(changeEvent);
                                        // Não use @this.set com File objects; o wire:model já resolve.
                                    }
                                }
                            </script>
                        </div>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-primary bg-opacity-10 border-0">
                        <h6 class="mb-0 text-primary fw-semibold">
                            <i class="ri-chat-3-line me-2"></i>ENCERRAMENTO MEDIDA
                        </h6>
                    </div>
                    <div class="card-body">
                        @php
                            $needEvidence = $medProtest->needsConfirmation;
                            $hasEvidence = $medProtest->evidenceFiles->isNotEmpty();
                            $canFinalize = $needEvidence ? $hasEvidence : true;

                        @endphp
                        <div class="card">
                            <h6 class="card-header">CONDIÇÕES PARA ENCERRAMENTO</h6>
                            <div class="card-body">
                                @if ($canFinalize)
                                    <p class="text-muted">Nenhuma restrição para Encerrar</p>
                                @else
                                    <p class="text-danger">É necessário fornecer evidências para encerrar a medida.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-success" wire:click="finishMedProtest"
                                @disabled(!$canFinalize)>
                                <i class="ri-check-line me-2"></i>Encerrar Medida
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-md-12">
            <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0 ">
                <div class="card-body">
                    <div class="border rounded py-3 border-secondary">
                        <h6 class="text-muted mb-2 ms-2 text-primary">ARQUIVOS ANEXADOS:</h6>

                        <div>
                            <x-files.attachments :files="$medProtest->evidenceFiles"
                                deleteAction="{{ auth()->user()->superadm ? 'deleteFile' : '' }}"
                                downloadAction="downloadFile" card="" />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>



    {{-- Componentes Livewire podem ser adicionados aqui --}}
    @livewire('protests.actions.protest.add-notes-relation', key('medProtest-AddNotesRelation'))

</div>
