@extends('layouts.padrao')

@php
    use App\Enum\ProtestJobStatus;
    use Carbon\Carbon;
@endphp

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Protests</li>
                <li class="breadcrumb-item">Common</li>
                <li class="breadcrumb-item active" aria-current="page">Nota {{ $note->note }}</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="container-fluid py-3">
        @php
            $noteDue = $note->dueDate();
            $dueDiff = $noteDue instanceof Carbon ? now()->diffInDays($noteDue, false) : null;
            $noteStatusBadge = [
                'color' => 'secondary',
                'text' => 'Sem prazo calculado',
            ];

            if ($noteDue instanceof Carbon) {
                if ($noteDue->endOfDay()->isPast()) {
                    $noteStatusBadge = [
                        'color' => 'danger',
                        'text' => 'Prazo vencido',
                    ];
                } elseif ($dueDiff !== null && $dueDiff <= 3) {
                    $noteStatusBadge = [
                        'color' => 'warning',
                        'text' => 'Prazo curto',
                    ];
                } else {
                    $noteStatusBadge = [
                        'color' => 'success',
                        'text' => 'No prazo',
                    ];
                }
            }

            $allMedidas = $protests->flatMap->medProtests ?? collect();
            $totalMedidas = $allMedidas->count();
            $medidasAtivas = $allMedidas->where('statusSist', 'MEDA')->count();
            $medidasEncerradas = $allMedidas->where('statusSist', 'MEDE')->count();

            $allJobs = $allMedidas->flatMap->ProtestJobs ?? collect();
            $totalJobs = $allJobs->count();

            $openStatusValues = [
                ProtestJobStatus::OPENED->value,
                ProtestJobStatus::ASSIGNED->value,
                ProtestJobStatus::IN_PROGRESS->value,
                ProtestJobStatus::WAITING->value,
                ProtestJobStatus::REOPENED->value,
            ];

            $jobsAbertos = $allJobs
                ->filter(fn($job) => in_array($job->status->value ?? null, $openStatusValues, true))
                ->count();

            $jobsConcluidos = $allJobs
                ->filter(fn($job) => ($job->status->value ?? null) === ProtestJobStatus::DONE->value)
                ->count();

            $jobsAtrasados = $allJobs
                ->filter(fn($job) => $job->sla_due_at && !$job->finished_at && now()->gt($job->sla_due_at))
                ->count();

            $progressGlobalJobs = $totalJobs > 0 ? round(($jobsConcluidos / $totalJobs) * 100) : 0;

            $fiveNote = $note->FiveNote;
        @endphp

        <div class="protest-header note-hero mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="d-flex align-items-center mb-2">
                            <div class="header-icon me-3">
                                <i class="ri-file-list-3-line fs-2"></i>
                            </div>
                            <div>
                                <h1 class="header-title mb-0">
                                    Nota {{ $note->note }}
                                    <span class="badge bg-light text-primary ms-2">
                                        {{ $note->type_note === 2 ? 'OV' : 'Nota' }}
                                    </span>
                                </h1>
                                <div class="header-subtitle text-white-50">
                                    Cliente: {{ $note->client ?? 'N/D' }}
                                    @if ($note->lexp)
                                        ƒ?" {{ $note->lexp }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="header-description mb-0">
                            <i class="ri-information-line me-1"></i>
                            Visão consolidada das reclamações associadas e suas medidas.
                        </p>
                    </div>
                </div>

                <div class="col-md-4 text-end">
                    <span class="badge badge-status bg-{{ $noteStatusBadge['color'] }} text-light">
                        {{ $noteStatusBadge['text'] }}
                    </span>
                    <div class="mt-2 small text-white-75">
                        @if ($noteDue instanceof Carbon)
                            Prazo: {{ $noteDue->format('d/m/Y') }}
                            <br>
                            {{ $dueDiff >= 0 ? $dueDiff . ' dia(s) restantes' : abs($dueDiff) . ' dia(s) em atraso' }}
                        @else
                            Sem prazo definido
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="modern-card h-100">
                    <div class="modern-card-body">
                        <div class="modern-card-title">
                            <i class="ri-information-line me-1"></i>Informações da Nota
                        </div>
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Criada em</span>
                                <span class="fw-semibold">{{ optional($note->dt_created)->format('d/m/Y') ?? 'N/D' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Status</span>
                                <span class="fw-semibold">{{ $note->status ?? $note->nstats ?? 'N/D' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Rubrica</span>
                                <span class="fw-semibold">{{ $note->rubrica ?? 'N/D' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Valor</span>
                                <span class="fw-semibold">
                                    {{ $note->currency ?? 'R$' }} {{ number_format((float) $note->value, 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="border-top pt-2 mt-2">
                                <span class="text-muted text-uppercase small d-block">Descrição</span>
                                <span class="fw-medium" style="white-space: pre-line;">
                                    {{ $note->material ?? 'Sem descrição cadastrada.' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="modern-card h-100">
                    <div class="modern-card-body">
                        <div class="modern-card-title">
                            <i class="ri-honour-line me-1"></i>Detalhes da D5
                        </div>
                        @if ($fiveNote)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Nota D5</span>
                                    <span class="fw-semibold">{{ $fiveNote->note_d5 }}</span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Visível p/ parceiro</span>
                                    <span class="fw-semibold">
                                        <span class="badge {{ $fiveNote->visible_partner ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $fiveNote->visible_partner ? 'Sim' : 'Não' }}
                                        </span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Pago</span>
                                    <span class="fw-semibold">
                                        <span class="badge {{ $fiveNote->is_payed ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ $fiveNote->is_payed ? 'Pago' : 'Pendente' }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="border-top pt-2">
                                <span class="text-muted small d-block">Flags</span>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <span class="badge {{ $fiveNote->is_completed ? 'bg-success' : 'bg-secondary' }}">
                                        Concluída
                                    </span>
                                    <span class="badge {{ $fiveNote->is_supervisioned ? 'bg-info' : 'bg-secondary' }}">
                                        Supervisionada
                                    </span>
                                    <span class="badge {{ $fiveNote->is_archived ? 'bg-dark' : 'bg-secondary' }}">
                                        Arquivada
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="text-muted small">
                                Nenhuma D5 vinculada a esta nota.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="modern-card h-100">
                    <div class="modern-card-body">
                        <div class="modern-card-title">
                            <i class="ri-dashboard-line me-1"></i>Resumo dos Protestos
                        </div>
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Reclamações vinculadas</span>
                                <span class="fw-semibold">{{ $protests->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Medidas cadastradas</span>
                                <span class="fw-semibold">{{ $totalMedidas }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Medidas ativas</span>
                                <span class="fw-semibold">{{ $medidasAtivas }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Medidas encerradas</span>
                                <span class="fw-semibold">{{ $medidasEncerradas }}</span>
                            </div>
                        </div>
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Progresso das atividades</span>
                                <span class="small fw-semibold">{{ $progressGlobalJobs }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $progressGlobalJobs }}%;"></div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ $jobsConcluidos }}/{{ $totalJobs }} atividades concluídas
                                @if ($jobsAtrasados > 0)
                                    ƒ?" <span class="text-danger">{{ $jobsAtrasados }} atrasadas</span>
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($protests as $index => $protest)
            @php
                $now = now();
                $latestMeasure = null;
                $mainDeadline = null;
                $mainDeadlineOrigin = null;

                if ($protest->tipoNota === 'NA') {
                    $mainDeadline = $protest->dtConclusaoDesej;
                    $mainDeadlineOrigin = 'Prazo do atendimento (SAC)';
                } else {
                    $latestMeasure = $protest->medProtests ? $protest->medProtests->sortByDesc('med_id')->first() : null;
                    $mainDeadline = $latestMeasure?->dtFimMedidaDesej;
                    $mainDeadlineOrigin = $latestMeasure ? 'Prazo da medida #' . $latestMeasure->med_id : 'Prazo não definido';
                }

                $openedAt = $protest->dtAberturaNota;
                $totalDays = $openedAt && $mainDeadline ? max($openedAt->diffInDays($mainDeadline), 1) : null;
                $elapsedDays =
                    $openedAt && $mainDeadline ? min($openedAt->diffInDays(min($now, $mainDeadline)), $totalDays) : null;
                $timelinePct = $totalDays && $elapsedDays !== null ? round(($elapsedDays / $totalDays) * 100) : null;
                $daysDiff = $mainDeadline ? $now->diffInDays($mainDeadline, false) : null;

                if (!$mainDeadline) {
                    $globalStatus = ['color' => 'secondary', 'icon' => 'ri-question-line', 'text' => 'Sem prazo definido'];
                    $daysText = 'Sem prazo principal definido';
                } elseif ($mainDeadline->endOfDay()->isPast()) {
                    $globalStatus = ['color' => 'danger', 'icon' => 'ri-close-circle-line', 'text' => 'Vencida'];
                    $daysText = abs($daysDiff) . ' dia(s) em atraso';
                } elseif ($daysDiff <= 3) {
                    $globalStatus = ['color' => 'warning', 'icon' => 'ri-time-line', 'text' => 'Vencendo'];
                    $daysText = $daysDiff . ' dia(s) restantes';
                } else {
                    $globalStatus = ['color' => 'success', 'icon' => 'ri-check-circle-line', 'text' => 'No prazo'];
                    $daysText = $daysDiff . ' dia(s) restantes';
                }

                $protestMedidas = $protest->medProtests ?? collect();
                $protestJobs = $protestMedidas->flatMap->ProtestJobs ?? collect();

                $protestJobsAbertos = $protestJobs
                    ->filter(fn($job) => in_array($job->status->value ?? null, $openStatusValues, true))
                    ->count();

                $protestJobsConcluidos = $protestJobs
                    ->filter(fn($job) => ($job->status->value ?? null) === ProtestJobStatus::DONE->value)
                    ->count();

                $protestJobsAtrasados = $protestJobs
                    ->filter(fn($job) => $job->sla_due_at && !$job->finished_at && $now->gt($job->sla_due_at))
                    ->count();

                $protestProgress = $protestJobs->count() > 0 ? round(($protestJobsConcluidos / $protestJobs->count()) * 100) : 0;

                $protest->loadMissing('evidenceFiles');
            @endphp

            <section class="mb-5" id="protest-{{ $protest->id }}">
                <div class="protest-header mb-3">
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
                                        <div class="header-subtitle text-white-50">
                                            {{ $protest->cidade }} ƒ?" {{ $protest->txtGrpCodificacao }}
                                        </div>
                                    </div>
                                </div>
                                <p class="header-description mb-0">
                                    {{ $protest->descricao ?? 'Reclamação sem descrição detalhada.' }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge badge-status bg-{{ $globalStatus['color'] }} text-light">
                                <i class="{{ $globalStatus['icon'] }} me-1"></i>
                                {{ $globalStatus['text'] }}
                            </span>
                            <div class="mt-2 small text-white-75">
                                {{ $daysText }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-information-line me-1"></i>Informações Básicas
                                </div>
                                <div class="d-flex flex-column gap-2 small">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Nota</span>
                                        <span class="fw-semibold">{{ $protest->nota }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Município</span>
                                        <span class="fw-semibold">{{ $protest->cidade ?? 'N/D' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Grupo</span>
                                        <span class="fw-semibold">{{ $protest->txtGrpCodificacao ?? 'N/D' }}</span>
                                    </div>
                                    <div class="border-top pt-2 mt-2">
                                        <span class="text-muted text-uppercase small d-block">Causa</span>
                                        <span class="fw-medium small">
                                            {{ $protest->medProtests?->last()?->txtCodCodificacao ?? 'Não informada' }}
                                        </span>
                                        <span class="text-muted text-uppercase small d-block mt-2">Subcausa</span>
                                        <span class="fw-medium small">
                                            {{ $protest->medProtests?->last()?->txtCodMedida ?? 'Não informada' }}
                                        </span>
                                        <span class="text-muted text-uppercase small d-block mt-2">Resumo</span>
                                        <span class="fw-medium small" style="white-space: pre-line;">
                                            {{ $protest->resume ?? 'Sem resumo cadastrado.' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-calendar-line me-1"></i>Cronograma
                                </div>
                                <div class="mb-3 text-center">
                                    <i class="{{ $globalStatus['icon'] }} fs-3 text-{{ $globalStatus['color'] }} me-2"></i>
                                    <span class="badge bg-{{ $globalStatus['color'] }} px-3 py-2">
                                        {{ $globalStatus['text'] }}
                                    </span>
                                    <br>
                                    <small class="d-block mt-1 {{ $mainDeadline && $mainDeadline->isPast() ? 'text-danger' : 'text-muted' }}">
                                        {{ $mainDeadline ? $daysText : 'Prazo não definido' }}
                                    </small>
                                </div>
                                <div class="border-top pt-2">
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">
                                            <i class="ri-flag-line me-1"></i>Prazo principal
                                        </span>
                                        <span class="fw-medium small d-block">
                                            {{ $mainDeadline ? $mainDeadline->format('d/m/Y') : 'N/D' }}
                                        </span>
                                        <span class="text-muted small">{{ $mainDeadlineOrigin }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">
                                            <i class="ri-play-circle-line me-1"></i>Abertura:
                                        </span>
                                        <span class="fw-medium small">{{ $openedAt?->format('d/m/Y') ?? 'N/D' }}</span>
                                    </div>

                                    @if ($openedAt && $mainDeadline && $timelinePct !== null)
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Linha do tempo</span>
                                                <span class="small fw-medium">{{ $timelinePct }}%</span>
                                            </div>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-{{ $mainDeadline->isPast() ? 'danger' : 'primary' }}"
                                                    style="width: {{ $timelinePct }}%;"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1 small text-muted">
                                                <span>{{ $openedAt->format('d/m/Y') }}</span>
                                                <span>{{ $mainDeadline->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-2 text-muted small">
                                            Sem dados suficientes para montar a linha do tempo.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="modern-card h-100">
                            <div class="modern-card-body">
                                <div class="modern-card-title">
                                    <i class="ri-dashboard-line me-1"></i>Métricas da Reclamação
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-muted">Progresso das atividades</span>
                                        <span class="small fw-medium">{{ $protestProgress }}%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: {{ $protestProgress }}%;"></div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        {{ $protestJobsConcluidos }}/{{ $protestJobs->count() }} atividades concluídas
                                    </small>
                                </div>

                                <div class="border-top pt-2">
                                    <div class="row text-center mb-2">
                                        <div class="col-4">
                                            <span class="fs-5 fw-bold text-primary">{{ $protestMedidas->count() }}</span><br>
                                            <small class="text-muted">Medidas</small>
                                        </div>
                                        <div class="col-4">
                                            <span class="fs-5 fw-bold text-success">
                                                {{ $protestMedidas->where('statusSist', 'MEDA')->count() }}
                                            </span><br>
                                            <small class="text-muted">Ativas</small>
                                        </div>
                                        <div class="col-4">
                                            <span class="fs-5 fw-bold text-info">
                                                {{ $protestMedidas->where('statusSist', 'MEDE')->count() }}
                                            </span><br>
                                            <small class="text-muted">Encerradas</small>
                                        </div>
                                    </div>
                                    <div class="row text-center mt-3">
                                        <div class="col-6 mb-2">
                                            <span class="fw-bold">{{ $protestJobsAbertos }}</span><br>
                                            <small class="text-muted">
                                                <i class="ri-task-line me-1"></i>Jobs em aberto
                                            </small>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <span class="fw-bold text-danger">{{ $protestJobsAtrasados }}</span><br>
                                            <small class="text-danger">
                                                <i class="ri-timer-flash-line me-1"></i>Atrasados
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modern-card mb-3">
                    <div class="modern-card-body">
                        <div class="modern-card-title mb-2">
                            <i class="ri-attachment-line me-2"></i>Anexos & Evidências
                        </div>
                        <x-files.attachments :files="$protest->evidenceFiles ?? collect()" :delete-action="null" :download-action="null" />
                    </div>
                </div>

                <div class="modern-card mb-3">
                    <div class="modern-card-body">
                        <div class="modern-card-title d-flex justify-content-between align-items-center mb-2">
                            <span><i class="ri-building-line me-2"></i>Obras Associadas</span>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($protest->all_notes as $relNote)
                                            <tr class="text-center align-middle">
                                                <td>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">
                                                        {{ $relNote->note }}
                                                    </span>
                                                </td>
                                                <td class="fw-medium">{{ $relNote->client }}</td>
                                                <td><span class="text-muted small">{{ $relNote->rubrica }}</span></td>
                                                <td>{{ $relNote->lexp }}</td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $relNote->material }}">
                                                        {{ $relNote->material }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        {{ $relNote->type_note == 2 ? $relNote->nstats : $relNote->centerjob }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                                <i class="ri-building-line fs-1 mb-2 opacity-50"></i>
                                <p class="mb-0 text-center">Nenhuma obra associada a esta reclamação.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modern-card mb-3">
                    <div class="modern-card-body">
                        <div class="d-flex justify-content-between flex-wrap align-items-start mb-3">
                            <div class="modern-card-title mb-2">
                                <i class="ri-list-check-2 me-2"></i>Medidas Registradas
                            </div>
                        </div>

                        @if ($protestMedidas->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" style="font-size: .95rem;">
                                    <thead class="table-light">
                                        <tr class="text-nowrap">
                                            <th style="min-width:160px;">Medida</th>
                                            <th style="min-width:220px;">Responsável / Executor</th>
                                            <th style="min-width:160px;">Situação Execução</th>
                                            <th style="min-width:170px;">Data Abertura</th>
                                            <th style="min-width:170px;">Prazo da Medida</th>
                                            <th style="min-width:280px;">SLA da Atividade Atual</th>
                                            <th style="min-width:260px;">Atividades</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($protestMedidas->sortByDesc('med_id') as $medProtest)
                                            @php
                                                $last = $medProtest->LastProtestJob;
                                                $jobs = $medProtest->ProtestJobs ?? collect();

                                                $jobsTotal = $jobs->count();
                                                $jobsOpen = $jobs
                                                    ->filter(fn($job) => in_array($job->status->value ?? null, $openStatusValues, true))
                                                    ->count();
                                                $jobsClosed = $jobsTotal - $jobsOpen;

                                                $creator = $last?->creator?->name;
                                                $owner = $last?->owner?->name;

                                                $startAt = $last->started_at ?? $last?->created_at;
                                                $dueAt = $last?->sla_due_at;
                                                $finishAt = $last?->finished_at;
                                                $nowRef = now();

                                                $prazoTxt =
                                                    $protest->tipoNota == 'NA'
                                                        ? $protest->dtConclusaoDesej?->format('d/m/Y H:i')
                                                        : $medProtest->dtFimMedidaDesej?->format('d/m/Y H:i');

                                                $prazoDate =
                                                    $protest->tipoNota == 'NA'
                                                        ? $protest->dtConclusaoDesej
                                                        : $medProtest->dtFimMedidaDesej;

                                                $medFinished = $medProtest->dtFimMedida;
                                                $medStatus = ['status' => 'Desconhecido', 'badge' => 'text-bg-secondary'];

                                                if ($medFinished) {
                                                    if ($medFinished?->startOfDay()->gt($prazoDate?->startOfDay())) {
                                                        $medStatus = ['status' => 'Fora do Prazo', 'badge' => 'text-bg-danger'];
                                                    } else {
                                                        $medStatus = ['status' => 'No Prazo', 'badge' => 'text-bg-success'];
                                                    }
                                                } else {
                                                    if (now()->startOfDay()->gt($prazoDate?->startOfDay())) {
                                                        $medStatus = ['status' => 'Atrasada', 'badge' => 'text-bg-danger'];
                                                    } else {
                                                        $medStatus = ['status' => 'No Prazo', 'badge' => 'text-bg-success'];
                                                    }
                                                }

                                                $slaBadge = '<span class="badge bg-secondary">SEM SLA</span>';
                                                if ($last && $dueAt) {
                                                    if (!$finishAt && $nowRef->lte($dueAt)) {
                                                        $slaBadge = '<span class="badge bg-success text-light">NO PRAZO</span>';
                                                    } elseif (!$finishAt && $nowRef->gt($dueAt)) {
                                                        $slaBadge = '<span class="badge bg-danger text-light">ATRASADO</span>';
                                                    } elseif ($finishAt && $finishAt->lte($dueAt)) {
                                                        $slaBadge = '<span class="badge bg-success text-light">ENTREGUE NO PRAZO</span>';
                                                    } elseif ($finishAt && $finishAt->gt($dueAt)) {
                                                        $slaBadge = '<span class="badge bg-danger text-light">ENTREGUE COM ATRASO</span>';
                                                    }
                                                }

                                                $pct = 0;
                                                if ($startAt && $dueAt) {
                                                    $totalSeconds = max($dueAt->diffInSeconds($startAt), 1);
                                                    $until = $finishAt ?: $nowRef;
                                                 	  $spent = max(min($until->diffInSeconds($startAt), $totalSeconds), 0);
                                                    $pct = max(0, min(100, round(($spent / $totalSeconds) * 100)));
                                                }

                                                $execStatusText = $last?->status_label ?? ($medProtest->statusSist ?? 'N/D');
                                                $execStatusClass = $last?->status_badge_class ?? 'bg-secondary';

                                                $isClosedMeasure = $medProtest->statusSist === 'MEDE';
                                                $isActiveMeasure = $medProtest->statusSist === 'MEDA';

                                                $statusBadgeColor = $isClosedMeasure
                                                    ? 'info'
                                                    : ($isActiveMeasure
                                                        ? 'success'
                                                        : 'secondary');
                                                $statusBadgeText = $isClosedMeasure
                                                    ? 'Encerrada'
                                                    : ($isActiveMeasure
                                                        ? 'Ativa'
                                                        : ($medProtest->statusSist ?? 'N/D'));
                                            @endphp

                                            <tr class="measure-row-main">
                                                <td class="align-top border-start"
                                                    style="border-left: 4px solid {{ $isClosedMeasure ? '#0dcaf0' : '#0d6efd' }};">
                                                    <div class="d-flex flex-column">
                                                        <div class="fw-bold d-flex align-items-center gap-2 mb-1">
                                                            <span># {{ $medProtest->med_id }}</span>
                                                            <span class="badge bg-{{ $statusBadgeColor }}">
                                                                {{ $statusBadgeText }}
                                                            </span>
                                                            @if ($isClosedMeasure)
                                                                <span class="badge bg-secondary">Finalizada</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-muted small text-truncate" style="max-width:260px;">
                                                            {{ $medProtest->txtCodMedida }}
                                                        </div>
                                                        <div class="mt-1 small">
                                                            <i class="ri-task-line me-1 text-secondary"></i>
                                                            <span class="text-muted">
                                                                {{ $jobsTotal }} atividade(s)
                                                            </span>
                                                            @if ($jobsTotal > 0)
                                                                <span class="ms-2 text-success">
                                                                    {{ $jobsClosed }} encerrada(s)
                                                                </span>
                                                                <span class="ms-2 text-warning">
                                                                    {{ $jobsOpen }} em aberto
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-top">
                                                    <div class="mini-label">Resp. Técnico</div>
                                                    <div class="mini-value mb-2">{{ $creator ?? 'N/D' }}</div>
                                                    <div class="mini-label">Executor</div>
                                                    <div class="mini-value">
                                                        @if ($owner)
                                                            <span class="badge bg-secondary">{{ $owner }}</span>
                                                        @else
                                                            <span class="badge bg-danger">Sem executor</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="align-top">
                                                    <div class="mini-label">Situação</div>
                                                    <div class="mini-value mb-2">
                                                        <span class="badge {{ $execStatusClass }}">
                                                            {{ strtoupper($execStatusText) }}
                                                        </span>
                                                    </div>
                                                    @if ($finishAt)
                                                        <div class="text-muted small">
                                                            Fechou: {{ $finishAt?->format('d/m/Y H:i') }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="align-top">
                                                    <div class="mini-label">Data Abertura</div>
                                                    <div class="mini-value mb-2">
                                                        {{ $medProtest->dtCriacaoMedida?->format('d/m/Y') ?? 'N/D' }}
                                                    </div>
                                                </td>
                                                <td class="align-top">
                                                    <div class="mini-label">Prazo Desejado</div>
                                                    <div class="mini-value mb-2">{{ $prazoTxt ?? 'N/D' }}</div>
                                                    @if ($medFinished)
                                                        <div>
                                                            <span class="badge bg-info">
                                                                Concluído em {{ $medFinished?->format('d/m/Y') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="badge {{ $medStatus['badge'] }}">
                                                            {{ $medStatus['status'] }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="align-top">
                                                    <div class="mini-label d-flex align-items-center justify-content-between">
                                                        <span>SLA / Progresso</span>
                                                        {!! $slaBadge !!}
                                                    </div>

                                                    @if ($startAt && $dueAt)
                                                        <div class="progress my-2" style="height:10px;">
                                                            <div class="progress-bar {{ !$finishAt && $dueAt && now()->gt($dueAt) ? 'bg-danger' : 'bg-success' }}"
                                                                style="width: {{ $pct }}%;"></div>
                                                        </div>
                                                        <div class="sla-info-lines small">
                                                            <div>Limite:
                                                                <strong class="text-primary">
                                                                    {{ $dueAt?->format('d/m/Y H:i') }}
                                                                </strong>
                                                            </div>
                                                            @if (!$finishAt && $dueAt)
                                                                <div>
                                                                    {{ now()->lte($dueAt)
                                                                        ? 'Restam ' . now()->diffForHumans($dueAt, true)
                                                                        : 'Atraso há ' . $dueAt->diffForHumans(now(), true) }}
                                                                </div>
                                                            @endif
                                                            @if ($finishAt)
                                                                <div>Finalizado:
                                                                    {{ $finishAt?->format('d/m/Y H:i') }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="text-muted">Sem referência de SLA.</div>
                                                    @endif
                                                </td>
                                                <td class="align-top">
                                                    <div class="mini-label">Visão Geral</div>
                                                    <div class="mini-value">
                                                        <span class="badge bg-light text-dark">
                                                            {{ $jobsTotal }} atividade(s) registradas
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>

                                            @if ($jobs->isNotEmpty())
                                                <tr>
                                                    <td colspan="7" class="jobs-cell">
                                                        <div class="jobs-panel">
                                                            @foreach ($jobs as $job)
                                                                @php
                                                                    $jStart = $job->started_at ?? $job->created_at;
                                                                    $jDue = $job->sla_due_at;
                                                                    $jFinish = $job->finished_at;
                                                                    $jPct = 0;
                                                                    if ($jStart && $jDue) {
                                                                        $jTotal = max($jDue->diffInSeconds($jStart), 1);
                                                                        $jUntil = $jFinish ?: now();
                                                                        $jSpent = max(min($jUntil->diffInSeconds($jStart), $jTotal), 0);
                                                                        $jPct = max(0, min(100, round(($jSpent / $jTotal) * 100)));
                                                                    }

                                                                    $jBadge = '<span class="badge bg-secondary">SEM SLA</span>';
                                                                    if ($jDue) {
                                                                        if (!$jFinish && now()->lte($jDue)) {
                                                                            $jBadge = '<span class="badge bg-success">NO PRAZO</span>';
                                                                        } elseif (!$jFinish && now()->gt($jDue)) {
                                                                            $jBadge = '<span class="badge bg-danger">ATRASADO</span>';
                                                                        } elseif ($jFinish && $jFinish->lte($jDue)) {
                                                                            $jBadge = '<span class="badge bg-success">ENTREGUE NO PRAZO</span>';
                                                                        } elseif ($jFinish && $jFinish->gt($jDue)) {
                                                                            $jBadge = '<span class="badge bg-danger">ENTREGUE COM ATRASO</span>';
                                                                        }
                                                                    }

                                                                    $jobStatusBadgeClass = $job->status_badge_class ?? 'bg-secondary';
                                                                    $jobStatusLabel = $job->status_label ?? strtoupper($job->status->value ?? '');
                                                                    $jobPriorityBadge = $job->priority_badge_class ?? 'bg-secondary';
                                                                    $jobPriorityLabel = $job->priority_label ?? 'N/D';
                                                                @endphp

                                                                <div class="job-box">
                                                                    <div class="job-header-line">
                                                                        <div class="job-left-chunk">
                                                                            <span class="job-id-badge">ATVD {{ $job->id }}</span>
                                                                            <span class="job-status-pill {{ $jobStatusBadgeClass }}">
                                                                                {{ strtoupper($jobStatusLabel) }}
                                                                            </span>
                                                                            <span class="job-priority-pill badge {{ $jobPriorityBadge }}">
                                                                                {{ $jobPriorityLabel }}
                                                                            </span>
                                                                            @if ($job->is_advance)
                                                                                <span class="badge bg-dark text-white" style="font-size:.65rem;">AVANÇA</span>
                                                                            @endif
                                                                            @if ($job->need_evidence)
                                                                                <span class="badge bg-warning text-dark" style="font-size:.65rem;">EVIDÊNCIA</span>
                                                                            @endif
                                                                        </div>
                                                                        <div class="job-right-chunk">
                                                                            <div class="job-owner text-end me-2">
                                                                                <div class="label">Responsável</div>
                                                                                <div class="value">{{ $job->owner?->name ?? 'N/D' }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="job-body-grid">
                                                                        <div class="job-col-block">
                                                                            <div class="job-label job-sla-headline">
                                                                                <span>SLA / Progresso</span>
                                                                                <span class="job-sla-badge">{!! $jBadge !!}</span>
                                                                            </div>
                                                                            @if ($jStart && $jDue)
                                                                                <div class="progress mb-2 mt-1" style="height: 10px;">
                                                                                    <div class="progress-bar {{ !$jFinish && now()->gt($jDue) ? 'bg-danger' : 'bg-success' }}"
                                                                                        role="progressbar" style="width: {{ $jPct }}%;"
                                                                                        aria-valuenow="{{ $jPct }}" aria-valuemin="0"
                                                                                        aria-valuemax="100">
                                                                                        {{ $jPct }}%
                                                                                    </div>
                                                                                </div>
                                                                            @endif

                                                                            <div class="sla-info-lines">
                                                                                <div>Limite:
                                                                                    {{ $jDue ? $jDue?->format('d/m/Y H:i') : 'N/D' }}
                                                                                </div>
                                                                                @if (!$jFinish && $jDue)
                                                                                    <div>
                                                                                        {{ now()->lte($jDue)
                                                                                            ? 'Restam ' . now()->diffForHumans($jDue, true)
                                                                                            : 'Atraso há ' . $jDue->diffForHumans(now(), true) }}
                                                                                    </div>
                                                                                @endif
                                                                                @if ($jFinish)
                                                                                    <div>Finalizado:
                                                                                        {{ $jFinish?->format('d/m/Y H:i') }}</div>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        <div class="job-col-block">
                                                                            <div class="job-label">Criado em</div>
                                                                            <div class="job-value">
                                                                                {{ $jStart?->format('d/m/Y H:i') ?? 'N/D' }}
                                                                            </div>
                                                                            <div class="job-label mt-3">Observações</div>
                                                                            <div class="job-value" style="white-space:pre-line;">
                                                                                {{ $job->notes }}
                                                                            </div>
                                                                        </div>

                                                                        <div class="job-col-block">
                                                                            <div class="job-label">Finalizado em</div>
                                                                            <div class="job-value">
                                                                                {{ $job->finished_at?->format('d/m/Y H:i') ?? 'N/D' }}
                                                                            </div>
                                                                            <div class="job-label mt-3">Resultado</div>
                                                                            <div class="job-value" style="white-space:pre-line;">
                                                                                {{ $job->close_reason ?? 'N/D' }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                                <i class="ri-list-check-2 fs-1 mb-3 opacity-50"></i>
                                <h5 class="mb-2">Nenhuma medida registrada</h5>
                                <p class="mb-0 text-center">Não há medidas cadastradas para esta reclamação.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modern-card">
                    <div class="modern-card-body">
                        <div class="modern-card-title mb-2">
                            <i class="ri-chat-3-line me-2"></i>
                            Observações registradas
                        </div>

                        <div class="chat-container border rounded bg-light">
                            @forelse ($protest->comments->sortByDesc('created_at') as $comment)
                                <div class="chat-message p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="d-flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle" title="{{ $comment->user->name ?? 'Usuário' }}">
                                                <img src="{{ $comment->user->avatar_url ?? asset('img/user.png') }}"
                                                    alt="Avatar">
                                            </div>
                                        </div>

                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <span class="fw-semibold text-dark">
                                                    {{ $comment->user->name ?? 'Usuário removido' }}
                                                </span>
                                                <small class="text-muted">
                                                    <i class="ri-time-line me-1"></i>
                                                    {{ $comment->created_at?->diffForHumans() }}
                                                </small>
                                            </div>

                                            <div class="message-bubble p-3 rounded-3 bg-white">
                                                <p class="mb-0 text-dark">{{ $comment->message }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted py-5">
                                    <i class="ri-chat-3-line fs-1 mb-3 opacity-50"></i>
                                    <h5 class="mb-2">Nenhum comentário registrado</h5>
                                    <p class="mb-0 text-center">Ainda não existem observações para esta reclamação.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
@endsection

@push('css')
    <style>
        .note-hero {
            background: linear-gradient(135deg, #1f6feb 0%, #6639a6 100%);
        }

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
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .badge-status {
            font-size: 1rem;
            padding: .5em 1.3em;
        }

        .progress {
            height: 8px;
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
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .message-bubble {
            border: 1px solid #e9ecef;
            transition: all 0.2s;
            background: #fff;
        }

        .chat-container {
            max-height: 340px;
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

        .measure-row-main td {
            vertical-align: top;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .mini-label {
            font-size: .7rem;
            line-height: 1.1;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: .03em;
            margin-bottom: .25rem;
        }

        .mini-value {
            font-size: .92rem;
            line-height: 1.3;
            font-weight: 600;
            color: #2c3e50;
        }

        .jobs-panel {
            border-top: 1px solid #f1f3f5;
            padding: 1rem 1.2rem;
            background: #fafbfc;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .jobs-cell {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .job-box {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .03);
            padding: .9rem 1rem;
            font-size: .8rem;
            line-height: 1.4;
            margin-bottom: .75rem;
        }

        .job-header-line {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            row-gap: .5rem;
            margin-bottom: .75rem;
        }

        .job-left-chunk {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            column-gap: .5rem;
            row-gap: .4rem;
        }

        .job-id-badge {
            font-size: .75rem;
            font-weight: 600;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: .5rem;
            padding: .25rem .5rem;
            line-height: 1.2;
        }

        .job-priority-pill {
            background: #fff;
            border: 1px solid #adb5bd;
            font-size: .7rem;
            font-weight: 500;
            border-radius: .5rem;
            padding: .2rem .5rem;
            line-height: 1.2;
            text-transform: uppercase;
            color: #343a40;
        }

        .job-status-pill {
            font-size: .7rem;
            font-weight: 600;
            border-radius: .5rem;
            padding: .25rem .5rem;
            line-height: 1.2;
            color: #fff;
        }

        .job-right-chunk {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            column-gap: .5rem;
            row-gap: .5rem;
        }

        .job-owner {
            font-size: .75rem;
            line-height: 1.2;
            text-align: right;
        }

        .job-owner .label {
            font-size: .65rem;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 500;
            letter-spacing: .03em;
        }

        .job-owner .value {
            font-weight: 600;
            color: #212529;
            font-size: .8rem;
        }

        .job-body-grid {
            display: flex;
            flex-wrap: wrap;
            row-gap: .75rem;
            column-gap: 1.5rem;
        }

        .job-col-block {
            min-width: 200px;
            max-width: 280px;
            flex: 1 1 200px;
            font-size: .8rem;
            line-height: 1.4;
        }

        .job-label {
            font-size: .7rem;
            font-weight: 500;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: .25rem;
        }

        .job-value {
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.3;
            font-size: .8rem;
        }

        .sla-info-lines {
            font-size: .8rem;
            color: #495057;
            line-height: 1.3;
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
        }
    </style>
@endpush
