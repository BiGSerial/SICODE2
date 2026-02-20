@push('css')
    <style>
        .medprotest-header {
            background: linear-gradient(120deg, #0f172a 0%, #0f766e 100%);
            border-radius: 18px;
            color: #fff;
            padding: 1.8rem 2rem 1.2rem 2rem;
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
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: .25rem;
            text-shadow: 0 2px 5px rgba(0, 0, 0, .09);
        }

        .medprotest-header .header-details {
            color: rgba(255, 255, 255, 0.86);
            font-size: .95rem;
        }

        .medprotest-header .tag-pill {
            background: rgba(0, 0, 0, .18);
            border-radius: 999px;
            padding: .25rem .7rem;
            font-size: .75rem;
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
            font-size: .9rem;
            font-weight: 600;
            color: #607d8b;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .avatar-circle {
            width: 50px;
            height: 50px;
            min-width: 50px;
            min-height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            background: #f1f5f9;
        }

        .chat-container .avatar-circle {
            width: 50px !important;
            height: 50px !important;
            min-width: 50px !important;
            min-height: 50px !important;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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

        .message-bubble {
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }

        .message-bubble:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, .06);
        }

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

        .file-item:hover .file-icon {
            transform: scale(1.1);
        }

        .progress-bar {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }

        #closeReason:focus {
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
        }

        .sla-chip {
            font-size: .78rem;
            border-radius: 999px;
            padding: .12rem .6rem;
        }
    </style>
@endpush

@php
    /** @var \App\Models\ProtestJob $job */
    use Carbon\CarbonInterval;

    $medProtest = $job->medProtest;
    $protest = $medProtest?->protest;

    if (!function_exists('formatSlaInterval')) {
        function formatSlaInterval(int $seconds): string
        {
            $interval = CarbonInterval::seconds($seconds)->cascade();
            $parts = [];

            if ($interval->d > 0) {
                $parts[] = $interval->d . ' dia' . ($interval->d > 1 ? 's' : '');
            }

            if ($interval->h > 0) {
                $parts[] = $interval->h . 'h';
            }

            if ($interval->i > 0 && count($parts) < 2) {
                $parts[] = $interval->i . 'min';
            }

            if (empty($parts) && $interval->s > 0) {
                $parts[] = $interval->s . 's';
            }

            return $parts ? implode(' e ', $parts) : 'menos de 1 minuto';
        }
    }

    // Tipo da reclamação
    $tipoLabel = match ($protest?->tipoNota) {
        'OU' => 'Ouvidoria',
        'NA' => 'Atendimento',
        'PR' => 'Procon',
        default => 'Reclamação',
    };

    // Flags de status do job
    $jobStarted = filled($job->started_at);
    $jobFinished = filled($job->finished_at);
    $jobClosed = filled($job->closed_at);

    // SLA do JOB (sempre conta a partir do started_at ou created_at até sla_due_at)
    $now = now();
    $baseStart = $job->started_at ?? $job->created_at;
    $dueAt = $job->sla_due_at;

    $slaStatus = [
        'color' => 'secondary',
        'text' => 'SLA não configurado',
        'icon' => 'ri-time-line',
        'percent' => 0,
        'label' => 'Sem datas suficientes para cálculo',
    ];

    if ($baseStart && $dueAt) {
        $totalSec = max($dueAt->diffInSeconds($baseStart), 1);
        $elapsedRaw = $now->diffInSeconds($baseStart, false);
        $elapsedSec = min(max($elapsedRaw, 0), $totalSec);
        $percent = intval(($elapsedSec / $totalSec) * 100);

        $secondsToDue = $now->diffInSeconds($dueAt, false);

        if ($secondsToDue < 0) {
            $slaStatus['color'] = 'danger';
            $slaStatus['text'] = 'SLA estourado';
            $slaStatus['icon'] = 'ri-error-warning-line';
            $slaStatus['percent'] = 100;
            $slaStatus['label'] = 'Atraso de ' . formatSlaInterval(abs($secondsToDue));
        } elseif ($percent >= 80) {
            $slaStatus['color'] = 'warning';
            $slaStatus['text'] = 'SLA em atenção';
            $slaStatus['icon'] = 'ri-timer-line';
            $slaStatus['percent'] = $percent;
            $slaStatus['label'] = 'Vence em ' . formatSlaInterval($secondsToDue);
        } else {
            $slaStatus['color'] = 'success';
            $slaStatus['text'] = 'SLA no prazo';
            $slaStatus['icon'] = 'ri-check-line';
            $slaStatus['percent'] = $percent;
            $slaStatus['label'] = 'No prazo, faltam ' . formatSlaInterval($secondsToDue);
        }
    }

    // Evidência obrigatória
    $needsEvidence = (bool) ($medProtest->needsEvidence ?? false);
    $hasEvidence = $medProtest?->evidenceFiles?->count() > 0;
@endphp

<div>
    <x-show-loading />

    {{-- ================= CABEÇALHO DO JOB ================= --}}
    <div class="medprotest-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8 col-md-7 col-12 mb-3 mb-md-0">
                <div class="d-flex align-items-start gap-3">
                    <div class="d-flex flex-column align-items-center">
                        <i class="ri-tools-line fs-2"></i>
                        <span class="tag-pill mt-2">
                            JOB #{{ $job->id }}
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <div class="header-title">
                            {{ $tipoLabel }} #{{ $protest?->nota ?? '—' }}
                            @if ($medProtest)
                                <span class="mx-1">|</span> Medida #{{ $medProtest->med_id }}
                            @endif
                        </div>

                        <div class="header-details mb-1">
                            {{ $protest?->cidade ?? 'Município não informado' }}
                            @if ($protest?->txtGrpCodificacao)
                                · {{ $protest->txtGrpCodificacao }}
                            @endif
                        </div>

                        <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                            <span class="tag-pill">
                                <i class="ri-user-6-line me-1"></i>
                                Responsável: {{ $job->owner?->name ?? 'Não atribuído' }}
                            </span>

                            <span class="tag-pill">
                                <i class="ri-user-follow-line me-1"></i>
                                Despachante: {{ $job->creator?->name ?? '—' }}
                            </span>

                            <span class="tag-pill">
                                <i class="ri-flag-2-line me-1"></i>
                                Status:
                                <span class="badge {{ $job->status_badge_class }} ms-1">
                                    {{ $job->status_label }}
                                </span>
                            </span>

                            <span class="tag-pill">
                                <i class="ri-vip-crown-2-line me-1"></i>
                                Prioridade:
                                <span class="badge {{ $job->priority_badge_class }} ms-1">
                                    {{ $job->priority_label }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- <div class="header-details mt-2">
                    <i class="ri-information-line me-1"></i>
                    Instruções do Job:
                    <span class="fw-semibold">
                        {{ $job->notes ?? 'Sem instruções detalhadas cadastradas.' }}
                    </span>
                </div> --}}
            </div>

            {{-- Painel compacto de SLA no cabeçalho --}}
            <div class="col-lg-4 col-md-5 col-12 text-end">
                <div class="d-inline-block text-start" style="min-width: 220px;">
                    <div class="mb-1 d-flex justify-content-between align-items-center">
                        <span class="small text-white-50">
                            <i class="ri-timer-flash-line me-1"></i>SLA da atividade
                        </span>
                        <span class="badge bg-{{ $slaStatus['color'] }} sla-chip">
                            <i class="{{ $slaStatus['icon'] }} me-1"></i>{{ $slaStatus['text'] }}
                        </span>
                    </div>

                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $slaStatus['color'] }}" role="progressbar"
                            style="width: {{ $slaStatus['percent'] }}%;" aria-valuenow="{{ $slaStatus['percent'] }}"
                            aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-1 small">
                        <span>
                            Início:
                            {{ $baseStart?->format('d/m/Y H:i') ?? '—' }}
                        </span>
                        <span>
                            Fim SLA:
                            {{ $dueAt?->format('d/m/Y H:i') ?? '—' }}
                        </span>
                    </div>

                    <div class="small mt-1">
                        {{ $slaStatus['label'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= LINHA DE CARDS PRINCIPAIS ================= --}}
    <div class="row mb-3">
        {{-- Informações da Reclamação --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title">
                        <i class="ri-information-line me-1"></i>Dados da Reclamação
                    </div>

                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Número:</span>
                            <span class="fw-semibold">{{ $protest?->nota ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tipo:</span>
                            <span class="fw-semibold">{{ $protest?->tipoNota ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Município:</span>
                            <span class="fw-semibold">{{ $protest?->cidade ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Abertura:</span>
                            <span class="fw-semibold">
                                {{ $protest?->dtAberturaNota?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Conclusão desejada (Recl.):</span>
                            <span class="fw-semibold">
                                {{ $protest?->dtConclusaoDesej?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>
                        <div class="border-top pt-2 mt-2">
                            <div class="text-muted mb-1">Resumo da Reclamação</div>
                            <div class="fw-medium">
                                {{ $protest?->resume ?? ($protest?->descricao ?? 'Sem resumo disponível.') }}
                            </div>
                        </div>
                        <div class="border-top pt-2 mt-2">
                            <div class="alert alert-primary border-0 shadow-sm mb-0" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-shrink-0">
                                        <i class="ri-information-line fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading mb-2 fw-bold">
                                            <i class="ri-file-list-line me-1"></i>Instruções desta Atividade
                                        </h6>
                                        <div class="fw-medium">
                                            {{ $job->notes ?? 'Sem instruções detalhadas cadastradas.' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cronograma da Medida --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title">
                        <i class="ri-calendar-line me-1"></i>Cronograma da Medida
                    </div>

                    <div class="small d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Criação da Medida:</span>
                            <span class="fw-semibold">
                                {{ $medProtest?->dtCriacaoMedida?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Fim desejado (Medida):</span>
                            <span class="fw-semibold">
                                {{ $medProtest?->dtFimMedidaDesej?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Conclusão da Medida:</span>
                            <span class="fw-semibold">
                                {{ $medProtest?->dtFimMedida?->format('d/m/Y') ?? 'Pendente' }}
                            </span>
                        </div>

                        <div class="border-top pt-2 mt-2">
                            <div class="text-muted mb-1">Código / Causa</div>
                            <div class="fw-medium">
                                {{ $medProtest?->codMedida }}
                                @if ($medProtest?->txtCodCodificacao)
                                    · {{ $medProtest->txtCodCodificacao }}
                                @endif
                            </div>
                            @if ($medProtest?->txtCodMedida)
                                <div class="text-muted mt-1 small">
                                    {{ $medProtest->txtCodMedida }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Controle da Atividade (Start / Close) --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body d-flex flex-column">
                    <div class="modern-card-title">
                        <i class="ri-play-circle-line me-1"></i>Controle da Atividade
                    </div>

                    <div class="mb-2 small">
                        @if (!$jobStarted)
                            <div class="alert alert-info py-2 mb-2">
                                <i class="ri-information-line me-1"></i>
                                Clique em <strong>Iniciar atividade</strong> para registrar o início do atendimento
                                deste Job.
                            </div>
                        @else
                            <div class="alert alert-light border py-2 mb-2">
                                <i class="ri-time-line me-1"></i>
                                Atividade iniciada em
                                <strong>{{ $job->started_at?->format('d/m/Y H:i') }}</strong>.
                            </div>
                        @endif

                        @if ($jobFinished)
                            <div class="alert alert-success py-2 mb-2">
                                <i class="ri-check-double-line me-1"></i>
                                Job finalizado em
                                <strong>{{ $job->finished_at?->format('d/m/Y H:i') }}</strong>.
                            </div>
                        @endif

                        @if ($needsEvidence)
                            <div
                                class="alert {{ $hasEvidence ? 'alert-success' : 'alert-warning' }} py-2 mb-2 small d-flex align-items-start gap-2">
                                <i class="ri-attachment-2 mt-1"></i>
                                <div>
                                    Esta medida exige anexos recebidos.
                                    @if ($hasEvidence)
                                        <strong>{{ $medProtest->evidenceFiles->count() }}</strong> arquivo(s) já
                                        anexado(s).
                                    @else
                                        Anexe pelo menos <strong>um arquivo</strong> antes de encerrar o Job.
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Campo de parecer / close_reason --}}
                    <div class="mb-3 flex-grow-1">
                        <div class="form-floating">
                            <textarea class="form-control @error('closeReason') is-invalid @enderror" id="closeReason"
                                placeholder="Descreva o parecer técnico final / motivo do encerramento." style="height: 130px"
                                wire:model.defer="closeReason"></textarea>
                            <label for="closeReason">Parecer técnico final / motivo do encerramento</label>
                        </div>
                        @error('closeReason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            <i class="ri-information-line me-1"></i>
                            Este texto alimenta o campo <code>close_reason</code> do Job e será usado em relatórios.
                        </small>
                    </div>

                    <div class="d-flex flex-column gap-2 mt-auto">
                        {{-- Botão iniciar --}}
                        <button type="button" class="btn btn-outline-primary w-100" wire:click="startJob"
                            @disabled($jobStarted)>
                            <i class="ri-play-circle-line me-1"></i>
                            Iniciar atividade
                        </button>

                        {{-- Botão encerrar --}}
                        <button type="button" class="btn btn-primary w-100" wire:click="finishJob"
                            @disabled(!$jobStarted || $jobFinished)>
                            <i class="ri-check-double-line me-1"></i>
                            Encerrar atividade
                        </button>

                        <small class="text-muted">
                            O encerramento registra o Job como concluído, mas o SLA continua sendo contado para fins
                            de análise.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= CONTEÚDO DEPENDENTE DO INÍCIO DO JOB ================= --}}
    @if ($jobStarted)

        {{-- ================= OBRAS ASSOCIADAS ================= --}}
        <div class="modern-card">
            <div class="modern-card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="modern-card-title">
                        <i class="ri-building-line me-1"></i>Obras / Notas associadas à Medida
                    </span>
                    @if ($medProtest)
                        <button class="btn btn-sm btn-warning"
                            wire:click="$emitTo('protests.services.actions.add-notes-relation', 'openAddNotesRelation', {{ $medProtest->id }})"
                            @disabled($jobFinished)>
                            <i class="ri-add-box-fill me-1"></i>Associar notas
                        </button>
                    @endif
                </div>

                @if ($medProtest && $medProtest->all_notes->isNotEmpty())
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($medProtest->all_notes as $note)
                                    <tr class="text-center align-middle">
                                        <td>
                                            <span
                                                class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">
                                                {{ $note->note }}
                                            </span>
                                        </td>
                                        <td class="fw-medium">{{ $note->client }}</td>
                                        <td><span class="text-muted small">{{ $note->rubrica }}</span></td>
                                        <td>{{ $note->lexp }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 220px;"
                                                title="{{ $note->material }}">
                                                {{ $note->material }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                {{ $note->type_note == 2 ? $note->nstats : $note->centerjob }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center py-3 text-muted">
                        <i class="ri-building-line fs-1 mb-2 opacity-50"></i>
                        <div>Nenhuma obra associada à medida até o momento.</div>
                    </div>
                @endif
            </div>
        </div>
        {{-- ==== Anexos & Evidências ==== --}}
        <div class="modern-card">
            <div class="modern-card-body">
                <div class="modern-card-title mb-2"><i class="ri-attachment-line me-2"></i>Anexos & Evidências</div>
                <x-files.attachments :files="$protest->evidenceFiles" downloadAction="downloadFiles" />
            </div>
        </div>

        {{-- ================= ANEXOS / COMENTÁRIOS ================= --}}
        <div class="row mt-3">
            {{-- Upload + lista de arquivos --}}
            <div class="col-md-6">
                <div class="modern-card mb-4">
                    <div class="modern-card-body">
                        <div class="modern-card-title">
                            <i class="ri-upload-cloud-2-line me-2"></i>Recebidos da Medida
                        </div>

                        @if (!$medProtest)
                            <div class="alert alert-warning small">
                                MedProtest não encontrado para este Job. Anexos não disponíveis.
                            </div>
                        @else
                            @if (!$jobFinished)
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
                                            <h5 class="text-primary fw-bold mb-2">
                                                Arraste arquivos aqui ou clique para selecionar
                                            </h5>
                                            <p class="text-muted mb-3">
                                                Formatos aceitos:
                                                {{ mb_strtoupper(implode(', ', $filesConfig['allowedTypes'])) }}
                                            </p>

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

                                    {{-- Barra de progresso --}}
                                    <div class="my-2 py-1" x-show="isUploading" style="display:none;">
                                        <div class="progress position-relative"
                                            style="height:4px; border-radius:2px; overflow:hidden;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" :style="`width:${progress}%`"
                                                :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
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
                                </div>

                                {{-- Lista de arquivos em fila (tempFiles) --}}
                                @if ($tempFiles && count($tempFiles) > 0)
                                    <div class="mb-4 mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary fw-bold mb-0">
                                                <i class="ri-file-list-3-line me-2"></i>Arquivos selecionados
                                            </h6>
                                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                                {{ count($tempFiles) }}
                                                {{ count($tempFiles) == 1 ? 'arquivo' : 'arquivos' }}
                                            </span>
                                        </div>

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
                                                                    {{ $file->getClientOriginalName() }}
                                                                </h6>
                                                                <div
                                                                    class="d-flex align-items-center text-muted small">
                                                                    <i class="ri-file-line me-1"></i>
                                                                    <span class="me-3">
                                                                        {{ $this->formatFileSize($file->getSize()) }}
                                                                    </span>
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

                                    <div class="d-flex justify-content-end gap-3">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                                            wire:click="clearAllFiles">
                                            <i class="ri-delete-bin-line me-2"></i>Limpar tudo
                                        </button>
                                        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                            wire:click="saveFiles">
                                            <i class="ri-upload-2-line me-2"></i>Salvar arquivos
                                        </button>
                                    </div>
                                @endif
                            @endif

                            <hr class="my-3">

                            {{-- Lista de anexos já salvos --}}
                            <div class="modern-card-title mb-2">
                                <i class="ri-attachment-line me-1"></i>Arquivos anexados
                            </div>

                            <x-files.attachments :files="$medProtest->evidenceFiles"
                                deleteAction="{{ auth()->user()->superadm ? 'deleteFile' : '' }}"
                                downloadAction="downloadFiles" />
                        @endif
                    </div>
                </div>
            </div>

            {{-- Comentários da MedProtest --}}
            <div class="col-md-6">
                <div class="modern-card mb-4">
                    <div class="modern-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="modern-card-title">
                                <i class="ri-chat-3-line me-2"></i>Discussão da Medida
                            </span>
                            @if ($medProtest && $medProtest->comments->isNotEmpty())
                                <span class="badge bg-primary">
                                    {{ $medProtest->comments->count() }} comentário(s)
                                </span>
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="chat-container border rounded bg-light">
                                    @if ($medProtest && $medProtest->comments->isNotEmpty())
                                        @foreach ($medProtest->comments->sortByDesc('created_at') as $comment)
                                            <div class="chat-message p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                <div class="d-flex gap-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar-circle"
                                                            title="{{ $comment->user->name }}">
                                                            <img src="{{ $comment->user->avatar_url }}"
                                                                alt="Avatar de {{ $comment->user->name }}">
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div
                                                            class="d-flex justify-content-between align-items-start mb-1">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span
                                                                    class="fw-semibold {{ $comment->user_id === auth()->id() ? 'text-primary' : 'text-dark' }}">
                                                                    {{ $comment->user->name }}
                                                                </span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <small class="text-muted">
                                                                    <i class="ri-time-line me-1"></i>
                                                                    {{ $comment->created_at->diffForHumans() }}
                                                                </small>
                                                                @if ($comment->user_id === auth()->id())
                                                                    <button class="btn btn-sm btn-outline-danger p-1"
                                                                        wire:click="removeComment({{ $comment->id }})"
                                                                        title="Excluir comentário"
                                                                        onclick="return confirm('Tem certeza que deseja excluir este comentário?')">
                                                                        <i class="ri-delete-bin-line fs-6"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="message-bubble p-3 rounded-3 {{ $comment->user_id === auth()->id() ? 'bg-primary bg-opacity-10' : 'bg-white' }}">
                                                            <p class="mb-0 text-dark">{{ $comment->message }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div
                                            class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                                            <i class="ri-chat-3-line fs-1 mb-3 opacity-50"></i>
                                            <h6 class="mb-1">Nenhum comentário ainda</h6>
                                            <p class="mb-0 text-center small">
                                                Use o campo abaixo para registrar dúvidas, encaminhamentos e
                                                tratativas desta medida.
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control @error('comment') is-invalid @enderror" placeholder="Digite seu comentário..."
                                        id="floatingTextarea" style="height: 140px" wire:model.defer="comment"></textarea>
                                    <label for="floatingTextarea">Seu comentário</label>
                                </div>
                                @error('comment')
                                    <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                @enderror
                                <div class="d-grid">
                                    <button type="button" class="btn btn-primary" wire:click.prevent="addComment">
                                        <i class="ri-send-plane-fill me-1"></i>
                                        Enviar comentário
                                    </button>
                                </div>
                            </div>
                        </div> {{-- row g-3 --}}
                    </div>
                </div>
            </div>
        </div> {{-- row mt-3 --}}
    @else
        <div class="modern-card">
            <div class="modern-card-body">
                <div class="alert alert-info mb-0">
                    <i class="ri-information-line me-1"></i>
                    A atividade deste Job ainda não foi iniciada. Clique em
                    <strong>Iniciar atividade</strong> para começar o atendimento.
                </div>
            </div>
        </div>
    @endif

    {{-- COMPONENTE DE RELACIONAR NOTAS --}}
    @if ($medProtest)
        @livewire('protests.services.actions.add-notes-relation', ['medProtestId' => $medProtest->id], key('medProtest-AddNotesRelation-' . $medProtest->id))
    @endif

    @push('scripts')
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
                }
            }
        </script>
    @endpush
</div>
