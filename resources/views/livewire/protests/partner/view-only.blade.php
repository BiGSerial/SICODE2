@push('css')
    <style>
        .medprotest-header {
            background: linear-gradient(120deg, #0f172a 0%, #0f766e 100%);
            border-radius: 18px;
            color: #fff;
            padding: 2rem 2rem 1.2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.2);
            position: relative;
            overflow: hidden;
        }

        .medprotest-header::before {
            content: '';
            position: absolute;
            right: -20px;
            top: -40px;
            width: 170px;
            height: 170px;
            background: rgba(255, 255, 255, 0.09);
            border-radius: 50%;
        }

        .medprotest-header .header-title {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: .2rem;
            text-shadow: 0 2px 5px rgba(0, 0, 0, .09);
        }

        .medprotest-header .header-details {
            color: rgba(255, 255, 255, 0.86);
            font-size: 1.05rem;
        }

        .modern-card {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            margin-bottom: 1.3rem;
        }

        .modern-card-body {
            padding: 1.3rem;
        }

        .modern-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #607d8b;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .badge-status {
            font-size: 1rem;
            padding: .45em 1.2em;
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
            height: 310px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #0f766e #f8f9fa;
        }

        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: #0f766e;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: #115e59;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: .97rem;
        }

        @media (max-width: 900px) {
            .medprotest-header {
                padding: 1rem;
            }

            .header-title {
                font-size: 1.2rem;
            }

            .modern-card-body {
                padding: .8rem;
            }
        }
    </style>
@endpush

<div>
    <x-show-loading />

    {{-- ==== CABEÇALHO ==== --}}
    <div class="medprotest-header mb-4">
        <div class="row align-items-center">
            <div class="col-8">
                <div class="d-flex align-items-center mb-2">
                    <i class="ri-tools-line fs-2 me-3"></i>
                    <div>
                        @php

                            switch ($medProtest->protest->tipoNota) {
                                case 'OU':
                                    $tipo = 'Ouvidoria';
                                    break;
                                case 'NA':
                                    $tipo = 'Atendimento';
                                    break;
                                case 'PR':
                                    $tipo = 'Procon';
                                    break;
                                default:
                                    $tipo = 'Reclamação';
                                    break;
                            }
                        @endphp
                        <div class="header-title">
                            {{ $tipo }} #{{ $medProtest->protest->nota }} <span class="mx-1">|</span> Medida
                            #{{ $medProtest->med_id }}
                            <span class="badge bg-light text-primary ms-2">{{ $medProtest->codMedida }}</span>
                        </div>
                        <div class="header-details">
                            {{ $medProtest->protest->cidade }} - ({{ $medProtest->protest->txtGrpCodificacao }})
                        </div>
                    </div>
                </div>
                <div class="header-details">
                    <i class="ri-information-line me-1"></i>
                    Ficha detalhada da medida executada na {{ $tipo }}.
                </div>
            </div>
            <div class="col-4 text-end">
                @php
                    $now = now();
                    $dtConclusao = $medProtest->dtFimMedida ?? $now;
                    $dtConclusaoDesej = $medProtest->dtFimMedidaDesej;
                    $daysDiff = $dtConclusaoDesej ? $dtConclusao->diffInDays($dtConclusaoDesej, false) : 0;

                    if ($dtConclusaoDesej && $dtConclusao->startOfDay()->gt($dtConclusaoDesej->startOfDay())) {
                        $status = ['color' => 'danger', 'text' => 'Vencida', 'icon' => 'ri-close-circle-line'];
                    } elseif ($dtConclusaoDesej && $dtConclusao->startOfDay()->lte($dtConclusaoDesej->startOfDay())) {
                        $status = ['color' => 'success', 'text' => 'No Prazo', 'icon' => 'ri-check-circle-line'];
                    } else {
                        $status = ['color' => 'warning', 'text' => 'Vencendo', 'icon' => 'ri-time-line'];
                    }
                @endphp
                <span class="badge badge-status bg-{{ $status['color'] }} text-light">
                    <i class="{{ $status['icon'] }} me-1"></i>{{ $status['text'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- ==== LINHA DE CARDS ==== --}}
    <div class="row mb-2">
        {{-- INFO --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-information-line me-1"></i>Informações Básicas</div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between"><span class="text-muted small">Nota:</span><span
                                class="fw-medium">{{ $medProtest->protest->nota }}</span></div>
                        <div class="d-flex justify-content-between"><span
                                class="text-muted small">Município:</span><span
                                class="fw-medium">{{ $medProtest->protest->cidade }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Grupo:</span><span
                                class="fw-medium">{{ $medProtest->protest->txtGrpCodificacao }}</span></div>
                        <div class="border-top pt-2 mt-2">
                            <span class="text-muted small d-block">Causa Raiz:</span>
                            <span class="fw-medium small">{{ $medProtest->txtCodCodificacao }}</span>
                            <span class="text-muted small d-block mt-1">SubCausa:</span>
                            <span class="fw-medium small">{{ $medProtest->txtCodMedida }}</span>
                            <span class="text-muted small d-block mt-1">Descrição:</span>
                            <span class="fw-medium small">{{ $medProtest->protest->comments->last()?->message }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CRONOGRAMA PRINCIPAL --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                @php
                    $now = now();
                    $dtConclusao = $medProtest->dtFimMedida ?? $now;
                    $dtConclusaoDesej = $medProtest->protest->dtConclusaoDesej;
                    $daysDiff = $dtConclusaoDesej ? $dtConclusao->diffInDays($dtConclusaoDesej, false) : 0;

                    if ($dtConclusaoDesej && $dtConclusao->startOfDay()->gt($dtConclusaoDesej->startOfDay())) {
                        $status = ['color' => 'danger', 'text' => 'Vencida', 'icon' => 'ri-close-circle-line'];
                    } elseif ($dtConclusaoDesej && $dtConclusao->startOfDay()->lte($dtConclusaoDesej->startOfDay())) {
                        $status = ['color' => 'success', 'text' => 'No Prazo', 'icon' => 'ri-check-circle-line'];
                    } else {
                        $status = ['color' => 'warning', 'text' => 'Vencendo', 'icon' => 'ri-time-line'];
                    }
                @endphp
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-calendar-line me-1"></i>Cronograma</div>
                    <div class="text-center mb-2">
                        <i class="{{ $status['icon'] }} fs-3 text-{{ $status['color'] }} me-2"></i>
                        <span class="badge bg-{{ $status['color'] }} px-3 py-2">{{ $status['text'] }}</span>
                        <br>
                        @if ($dtConclusao && !$dtConclusao->isPast($medProtest->protest->dtConclusaoDesej))
                            <small class="text-muted">{{ abs($daysDiff) }} dias restantes</small>
                        @elseif($dtConclusao)
                            <small class="text-danger">{{ abs($daysDiff) }} dias em atraso</small>
                        @endif
                    </div>
                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-play-circle-line me-1"></i>Abertura:</span>
                            <span
                                class="fw-medium small">{{ $medProtest->protest->dtAberturaNota?->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-flag-line me-1"></i>Conclusão Desejada:</span>
                            <span
                                class="fw-medium small">{{ $medProtest->protest->dtConclusaoDesej?->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><i class="ri-flag-line me-1"></i>Conclusão:</span>
                            <span class="fw-medium small">{{ $medProtest->dtFimMedida?->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CRONOGRAMA DA MEDIDA --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                @php
                    $now = now();
                    $dtFimMedida = $medProtest->dtFimMedida ?? $now;
                    $dtFimMedidaDesej = $medProtest->dtFimMedidaDesej;
                    $daysDiffMedida = $dtFimMedidaDesej ? $dtFimMedida->diffInDays($dtFimMedidaDesej, false) : 0;
                    $assignment = $medProtest->assignments?->where('user', true)->last();

                    if ($dtFimMedidaDesej && $dtFimMedida->startOfDay()->gt($dtFimMedidaDesej->startOfDay())) {
                        $statusMed = ['color' => 'danger', 'text' => 'Vencida', 'icon' => 'ri-close-circle-line'];
                    } elseif ($dtFimMedidaDesej && $dtFimMedida->startOfDay()->lte($dtFimMedidaDesej->startOfDay())) {
                        $statusMed = ['color' => 'success', 'text' => 'No Prazo', 'icon' => 'ri-check-circle-line'];
                    } else {
                        $statusMed = ['color' => 'warning', 'text' => 'Vencendo', 'icon' => 'ri-time-line'];
                    }
                @endphp
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-calendar-line me-1"></i>Cronograma da Medida</div>
                    <div class="text-center mb-2">
                        <i class="{{ $statusMed['icon'] }} fs-3 text-{{ $statusMed['color'] }} me-2"></i>
                        <span class="badge bg-{{ $statusMed['color'] }} px-3 py-2">{{ $statusMed['text'] }}</span>
                        <br>
                        @if ($dtFimMedidaDesej && $dtFimMedida && !$dtFimMedida->isPast($dtFimMedidaDesej))
                            <small class="text-muted">{{ abs($daysDiffMedida) }} dias restantes</small>
                        @elseif($dtFimMedidaDesej && $dtFimMedida)
                            <small class="text-danger">{{ abs($daysDiffMedida) }} dias em atraso</small>
                        @endif
                    </div>
                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-play-circle-line me-1"></i>Abertura:</span>
                            <span class="fw-medium small">{{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-flag-line me-1"></i>Conclusão Desejada:</span>
                            <span class="fw-medium small">{{ $medProtest->dtFimMedidaDesej?->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-flag-line me-1"></i>Conclusão:</span>
                            <span class="fw-medium small">{{ $medProtest->dtFimMedida?->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="ri-user-3-line me-1"></i>Usuário
                                Responsável:</span>
                            <span class="fw-medium small">{{ $assignment?->User?->name ?? 'Não atribuído' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><i class="ri-calendar-check-line me-1"></i>Conclusão
                                SICODE:</span>
                            <span
                                class="fw-medium small">{{ $assignment?->ended_at?->format('d/m/Y H:i:s') ?? 'Pendente' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==== TABELA DE OBRAS ASSOCIADAS ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="modern-card-title"><i class="ri-building-line me-1"></i>Obras Associadas</span>
                {{-- <button class="btn btn-sm btn-warning"
                    wire:click.defer="$emitTo('protests.services.actions.add-notes-relation', 'openAddNotesRelation', {{ $medProtest->id }})"
                    @disabled($medProtest->completed)>
                    <i class="ri-add-box-fill me-1"></i>Associar
                </button> --}}
            </div>
            @if ($medProtest->all_notes->isNotEmpty())
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
                            @foreach ($medProtest->all_notes as $note)
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
                                        {{-- @if ($medProtest->notes->contains($note->id))
                                            <button class="btn btn-sm btn-outline-danger" title="Remover Associação"
                                                data-bs-toggle="tooltip"
                                                wire:click.prevent="removeNoteFromProtest({{ $note->id }})"
                                                onclick="return confirm('Remover esta associação?')">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        @endif --}}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                    <i class="ri-building-line fs-1 mb-3 opacity-50"></i>
                    <h5 class="mb-2">Nenhuma obra associada</h5>
                    <p class="mb-0 text-center">Clique no botão "Associar" para vincular notas ou OVs a esta medida</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ==== ANEXOS (ARQUIVOS ANEXADOS) ==== --}}
    <div class="row">
        {{-- <div class="col-md-6">
            <div class="modern-card mb-4">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-upload-cloud-2-line me-2"></i>Anexar Arquivos</div>
                    <div x-data="{
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
                                <h5 class="text-primary fw-bold mb-2">Arraste arquivos aqui ou clique para selecionar
                                </h5>
                                <p class="text-muted mb-3">
                                    Formatos aceitos: {{ mb_strtoupper(implode(', ', $filesConfig['allowedTypes'])) }}
                                </p>
                                <input type="file"
                                    class="form-control d-none @error('files.*') is-invalid @enderror" id="fileInput"
                                    x-ref="fileInput" multiple
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt" wire:model="files">
                                <button type="button" class="btn btn-primary btn-lg px-4"
                                    onclick="event.stopPropagation(); document.getElementById('fileInput').click()">
                                    <i class="ri-folder-open-line me-2"></i>
                                    Selecionar Arquivos
                                </button>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Máximo: {{ $filesConfig['maxSize'] / 1024 }}MB por arquivo
                                    </small>
                                </div>
                                @error('files.*')
                                    <div class="alert alert-danger mt-3 mb-0 py-2">
                                        <i class="ri-error-warning-line me-2"></i>
                                        <small>{{ $message }}</small>
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Barra de Progresso -->
                        <div class="my-2 py-1" x-show="isUploading" style="display:none;">
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
                                        {{ count($tempFiles) }} {{ count($tempFiles) == 1 ? 'arquivo' : 'arquivos' }}
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
                                                            <div class="d-flex align-items-center text-muted small">
                                                                <i class="ri-file-line me-1"></i>
                                                                <span
                                                                    class="me-3">{{ $this->formatFileSize($file->getSize()) }}</span>
                                                                <i class="ri-check-line text-success me-1"></i>
                                                                <span class="text-success">Pronto para upload</span>
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
        </div> --}}
        <div class="col-md-12">
            <div class="modern-card">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-attachment-line me-2"></i>Arquivos Anexados</div>
                    <x-files.attachments :files="$medProtest->evidenceFiles"
                        deleteAction="{{ auth()->user()->superadm ? 'deleteFile' : '' }}"
                        downloadAction="downloadFile" />
                </div>
            </div>
        </div>
    </div>

    {{-- ==== COMENTÁRIOS ==== --}}
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title"><i class="ri-chat-3-line me-2"></i>Adicionar Comentário</div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control @error('comment') is-invalid @enderror" placeholder="Digite seu comentário..."
                            id="floatingTextarea" style="height: 120px" wire:model.defer="comment"></textarea>
                        <label for="floatingTextarea">Seu comentário</label>
                    </div>
                    @error('comment')
                        <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
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
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="modern-card-title"><i class="ri-chat-3-line me-2"></i>Discussão -
                            {{ $medProtest->protest->nota }}</span>
                        @if ($medProtest->comments->isNotEmpty())
                            <span class="badge bg-primary">{{ $medProtest->comments->count() }} comentários</span>
                        @endif
                    </div>
                    <div class="chat-container border rounded bg-light">
                        @forelse($medProtest->comments->sortByDesc('created_at') as $comment)
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
                                                {{-- @if ($comment->user?->email)
                                                    <button class="btn btn-sm btn-outline-primary p-1"
                                                        onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')"
                                                        title="Abrir chat no Teams">
                                                        <i class="bx bxl-microsoft-teams fs-6"></i>
                                                    </button>
                                                @endif --}}
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted">
                                                    <i class="ri-time-line me-1"></i>
                                                    {{ $comment->created_at->diffForHumans() }}
                                                </small>
                                                {{-- @if (($comment->created_at->diffInHours() < 1 && $comment->id === $medProtest->protest->comments->max('id')) || auth()->user()->admin || auth()->user()->superadm)
                                                    <button class="btn btn-sm btn-outline-danger p-1"
                                                        wire:click="deleteComment({{ $comment->id }})"
                                                        title="Excluir comentário"
                                                        onclick="return confirm('Tem certeza que deseja excluir este comentário?')">
                                                        <i class="ri-delete-bin-line fs-6"></i>
                                                    </button>
                                                @endif --}}
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
                                <p class="mb-0 text-center">Seja o primeiro a comentar nesta medida</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- COMPONENTES LIVEWIRE --}}
    @livewire('protests.services.actions.add-notes-relation', key('medProtest-AddNotesRelation'))
</div>
