<div>
    @php
        use Carbon\Carbon;
        use App\Enum\ProtestJobStatus;

        $now = now();

        /**
         * ==== CÁLCULO DO PRAZO PRINCIPAL DA RECLAMAÇÃO ====
         * - NA  => usa dtConclusaoDesej do Protest
         * - OUTROS => usa dtFimMedidaDesej da medida com MAIOR med_id
         */

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

        // Dias totais entre abertura e prazo
        $totalDays = $openedAt && $mainDeadline ? max($openedAt->diffInDays($mainDeadline), 1) : null;

        // Dias já percorridos
        $elapsedDays =
            $openedAt && $mainDeadline ? min($openedAt->diffInDays(min($now, $mainDeadline)), $totalDays) : null;

        $timelinePct = $totalDays && $elapsedDays !== null ? round(($elapsedDays / $totalDays) * 100) : null;

        // Diferença em dias pro status global
        $daysDiff = $mainDeadline
            ? $now->diffInDays($mainDeadline, false) // negativo se já passou
            : null;

        if (!$mainDeadline) {
            $globalStatus = [
                'color' => 'secondary',
                'icon' => 'ri-question-line',
                'text' => 'Sem prazo definido',
            ];
            $daysText = '—';
        } elseif ($mainDeadline->endOfDay()->isPast()) {
            $globalStatus = [
                'color' => 'danger',
                'icon' => 'ri-close-circle-line',
                'text' => 'Vencida',
            ];
            $daysText = abs($daysDiff) . ' dia(s) em atraso';
        } elseif ($daysDiff <= 3) {
            $globalStatus = [
                'color' => 'warning',
                'icon' => 'ri-time-line',
                'text' => 'Vencendo',
            ];
            $daysText = $daysDiff . ' dia(s) restantes';
        } else {
            $globalStatus = [
                'color' => 'success',
                'icon' => 'ri-check-circle-line',
                'text' => 'No prazo',
            ];
            $daysText = $daysDiff . ' dia(s) restantes';
        }

        /**
         * ==== MÉTRICAS GERAIS DE MEDIDAS E JOBS ====
         */

        $totalMedidas = $protest->medProtests?->count() ?? 0;
        $medidasAtivas = $protest->medProtests?->where('statusSist', 'MEDA')->count() ?? 0;
        $medidasEncerradas = $protest->medProtests?->where('statusSist', 'MEDE')->count() ?? 0;

        // Junta todos os jobs das medidas
        $allJobs = $protest->medProtests ? $protest->medProtests->flatMap->ProtestJobs : collect();

        $totalJobs = $allJobs->count();

        $openStatusValues = [
            ProtestJobStatus::OPENED->value,
            ProtestJobStatus::ASSIGNED->value,
            ProtestJobStatus::IN_PROGRESS->value,
            ProtestJobStatus::WAITING->value,
            ProtestJobStatus::REOPENED->value,
        ];

        $jobsAbertos = $allJobs->filter(fn($job) => in_array($job->status->value, $openStatusValues, true))->count();

        $jobsConcluidos = $allJobs->filter(fn($job) => $job->status->value === ProtestJobStatus::DONE->value)->count();

        $jobsAtrasados = $allJobs
            ->filter(fn($job) => $job->sla_due_at && !$job->finished_at && $now->gt($job->sla_due_at))
            ->count();

        $jobsParaConfirmar = $allJobs
            ->filter(fn($job) => $job->status->value === ProtestJobStatus::DONE->value && !$job->confirmed)
            ->count();

        $progressGlobalJobs = $totalJobs > 0 ? round(($jobsConcluidos / $totalJobs) * 100) : 0;
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
                --avatar-size: 40px;
                font-size: 14px;
                font-weight: 600;
                width: var(--avatar-size);
                height: var(--avatar-size);
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

            /* ===== estilos novos para as medidas e atividades ===== */

            .measure-card {
                border: 1px solid rgba(0, 0, 0, .03);
                border-radius: 16px;
                box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
                background: #fff;
                transition: box-shadow .18s, transform .18s;
            }

            .measure-card:hover {
                box-shadow: 0 16px 32px rgba(0, 0, 0, .08);
                transform: translateY(-2px);
            }

            .measure-card-header {
                border-top-left-radius: 16px;
                border-top-right-radius: 16px;
                padding: .9rem 1.2rem;
                background: linear-gradient(135deg, #4e73df 0%, #1cc88a 100%);
                color: #fff;
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: flex-start;
                row-gap: .5rem;
            }

            .measure-header-left .code-badge {
                font-size: .8rem;
                font-weight: 600;
                background: rgba(255, 255, 255, .15);
                border-radius: 10px;
                padding: .3rem .6rem;
                display: inline-flex;
                align-items: center;
                gap: .4rem;
            }

            .measure-header-left .status-badge {
                font-size: .7rem;
                font-weight: 500;
                background: #fff;
                color: #111;
                border-radius: 8px;
                padding: .25rem .5rem;
            }

            .measure-header-right .small-date-line {
                font-size: .7rem;
                opacity: .9;
                line-height: 1.2;
                text-align: right;
            }

            .measure-card-body {
                padding: 1rem 1.2rem 0 1.2rem;
            }

            .measure-row {
                display: flex;
                flex-wrap: wrap;
                row-gap: .5rem;
                column-gap: 1rem;
                margin-bottom: 1rem;
            }

            .measure-col {
                flex: 1 1 180px;
                min-width: 180px;
            }

            .measure-label {
                font-size: .7rem;
                text-transform: uppercase;
                color: #6c757d;
                letter-spacing: .03em;
                font-weight: 500;
            }

            .measure-value {
                font-size: .9rem;
                font-weight: 600;
                color: #2c3e50;
                line-height: 1.3;
            }

            .measure-jobs-toggle {
                font-size: .8rem;
                font-weight: 500;
            }

            .jobs-panel {
                border-top: 1px solid #f1f3f5;
                padding: 1rem 1.2rem;
                background: #fafbfc;
                border-bottom-left-radius: 16px;
                border-bottom-right-radius: 16px;
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

            .chat-container .avatar-circle {
                --avatar-size: 50px;
            }

            .avatar-circle img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
        </style>

        {{-- estilos das linhas de job / medida (mantidos do seu código) --}}
        <style>
            .icon-btn-table,
            .job-action-btn {
                width: 32px;
                height: 32px;
                border-radius: .5rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                line-height: 1;
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
                display: grid;
                grid-template-columns: minmax(250px, 320px) minmax(0, 1fr);
                gap: .9rem 1.2rem;
                align-items: start;
            }

            .job-col-block {
                font-size: .8rem;
                line-height: 1.4;
            }

            .job-col-block--meta {
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                border-radius: .6rem;
                padding: .6rem .7rem;
            }

            .job-col-block--content {
                min-width: 0;
            }

            .job-result-head {
                display: flex;
                flex-wrap: wrap;
                gap: .75rem 1.25rem;
            }

            .job-result-head>div {
                min-width: 170px;
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

            .job-resolution-text {
                white-space: pre-line;
                word-break: break-word;
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: .5rem;
                padding: .65rem .75rem;
            }

            .job-meta-list {
                display: grid;
                grid-template-columns: 1fr;
                gap: .35rem;
            }

            .job-meta-item {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                gap: .6rem;
                border-bottom: 1px dashed #e5e7eb;
                padding-bottom: .2rem;
            }

            .job-meta-item:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .job-meta-item span {
                font-size: .7rem;
                text-transform: uppercase;
                color: #6b7280;
                letter-spacing: .03em;
                font-weight: 500;
                white-space: nowrap;
            }

            .job-meta-item strong {
                font-size: .78rem;
                color: #1f2937;
                text-align: right;
            }

            .job-actions-under-meta {
                margin-top: .75rem;
                padding-top: .65rem;
                border-top: 1px dashed #d1d5db;
                display: flex;
                flex-wrap: wrap;
                gap: .4rem;
            }

            .sla-bar-wrap-job {
                width: 220px;
                max-width: 100%;
                background: #f1f3f5;
                border-radius: 6px;
                overflow: hidden;
                box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .05);
                height: 8px;
                display: flex;
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

            .jobs-cell {
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
            }

            @media (max-width: 991px) {
                .job-body-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endpush

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
                        @php
                            switch ($protest->tipoNota) {
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
                        <div>
                            <h1 class="header-title mb-0">
                                {{ $tipo }} #{{ $protest->nota }}
                                <span class="badge bg-light text-primary ms-2">{{ $protest->tipoNota }}</span>
                            </h1>
                            <div class="header-subtitle text-white-50">
                                {{ $protest->cidade }} — {{ $protest->txtGrpCodificacao }}
                            </div>
                        </div>
                    </div>
                    <p class="header-description mb-0">
                        <i class="ri-information-line me-1"></i>
                        Detalhamento, progresso e interação sobre a demanda.
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge badge-status bg-{{ $globalStatus['color'] }} text-light">
                    <i class="{{ $globalStatus['icon'] }} me-1"></i>
                    {{ $globalStatus['text'] }}
                </span>
                <div class="mt-2 small text-white-75">
                    {{ $mainDeadline ? $daysText : 'Sem prazo principal definido' }}
                </div>
            </div>
        </div>
    </div>

    {{-- ==== Linha dos Cartões Principais ==== --}}
    <div class="row">
        {{-- Info Básica --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title">
                        <i class="ri-information-line me-1"></i>Informações Básicas
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Nota:</span>
                            <span class="fw-medium">{{ $protest->nota }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Município:</span>
                            <span class="fw-medium">{{ $protest->cidade }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Grupo:</span>
                            <span class="fw-medium">{{ $protest->txtGrpCodificacao }}</span>
                        </div>
                        <div class="border-top pt-2 mt-2">
                            <span class="text-muted small d-block">Causa:</span>
                            <span
                                class="fw-medium small">{{ $protest->medProtests?->last()?->txtCodCodificacao }}</span>
                                <span class="text-muted small d-block mt-1">SubCausa:</span>
                            <span class="fw-medium small">{{ $protest->medProtests?->last()?->txtCodMedida }}</span>

                            @if ($readOnly)
                                <div class="d-flex justify-content-between align-items-center my-1">
                                    <span class="text-muted small">Categoria do Protesto:</span>
                                </div>
                                <span class="fw-medium small mb-2 d-block">
                                    {{ $protest->type ?? 'SEM CATEGORIA DEFINIDA' }}
                                </span>
                            @else
                                <div class="d-flex justify-content-between align-items-center my-1">
                                    <span class="text-muted small">Categoria do Protesto:</span>
                                    @if (!$showTypeEdit)
                                        <button class="btn btn-sm btn-outline-primary p-1" wire:click="editType"
                                            title="Editar categoria">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-success p-1" wire:click="saveType"
                                            title="Salvar categoria">
                                            <i class="ri-save-line"></i>
                                        </button>
                                    @endif
                                </div>

                                @if ($showTypeEdit)
                                    <select class="form-select form-select-sm mb-2" wire:model.defer="typeEdit">
                                        <option value="">Selecione uma categoria</option>
                                        @foreach ($protestCategories as $category)
                                            <option value="{{ $category->value }}">{{ $category->reason }}</option>
                                        @endforeach
                                    </select>
                                    @error('typeEdit')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                @else
                                    <span class="fw-medium small mb-2 d-block">
                                        {{ $protest->type ?? 'SEM CATEGORIA DEFINIDA' }}
                                    </span>
                                @endif
                            @endif

                            @if ($readOnly)
                                <div class="d-flex justify-content-between align-items-center my-1">
                                    <span class="text-muted small">Descrição:</span>
                                </div>
                                <span class="fw-medium small" style="white-space: pre-line;">
                                    {{ $protest->resume ?? 'SEM DESCRIÇÃO PARA RECLAMAÇÃO' }}
                                </span>
                            @else
                                <div class="d-flex justify-content-between align-items-center my-1">
                                    <span class="text-muted small">Descrição:</span>
                                    @if (!$showResumeEdit)
                                        <button class="btn btn-sm btn-outline-primary p-1" wire:click="editResume"
                                            title="Editar descrição">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-success p-1" wire:click="saveResume"
                                            title="Salvar descrição">
                                            <i class="ri-save-line"></i>
                                        </button>
                                    @endif
                                </div>

                                @if ($showResumeEdit)
                                    <textarea class="form-control form-control-sm" rows="6" wire:model.defer="resumeEdit"></textarea>
                                @else
                                    <span class="fw-medium small" style="white-space: pre-line;">
                                        {{ $protest->resume ?? 'SEM DESCRIÇÃO PARA RECLAMAÇÃO' }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cronograma (refinado) --}}
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
                        <small
                            class="d-block mt-1 {{ $mainDeadline && $mainDeadline->isPast() ? 'text-danger' : 'text-muted' }}">
                            {{ $mainDeadline ? $daysText : 'Prazo não definido' }}
                        </small>
                    </div>

                    <div class="border-top pt-2">
                        <div class="mb-2">
                            <span class="text-muted small d-block">
                                <i class="ri-flag-line me-1"></i>Prazo principal
                            </span>
                            <span class="fw-medium small d-block">
                                {{ $mainDeadline ? $mainDeadline->format('d/m/Y') : '—' }}
                            </span>
                            <span class="text-muted small">{{ $mainDeadlineOrigin }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">
                                <i class="ri-play-circle-line me-1"></i>Abertura:
                            </span>
                            <span class="fw-medium small">
                                {{ $openedAt?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>

                        @if ($openedAt && $mainDeadline && $timelinePct !== null)
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Linha do tempo da reclamação</span>
                                    <span class="small fw-medium">{{ $timelinePct }}%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-{{ $mainDeadline->isPast() ? 'danger' : 'primary' }}"
                                        role="progressbar" style="width: {{ $timelinePct }}%;"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1 small text-muted">
                                    <span>{{ $openedAt->format('d/m/Y') }}</span>
                                    <span>{{ $mainDeadline->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        @else
                            <div class="mt-2 text-muted small">
                                Sem dados suficientes para montar a linha do tempo (abertura ou prazo ausentes).
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Métricas (refinado para controlador) --}}
        <div class="col-md-4 mb-3">
            <div class="modern-card h-100">
                <div class="modern-card-body">
                    <div class="modern-card-title">
                        <i class="ri-dashboard-line me-1"></i>Métricas da Reclamação
                    </div>

                    {{-- Progresso global das atividades --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">Progresso das atividades (jobs)</span>
                            <span class="small fw-medium">
                                {{ $progressGlobalJobs }}%
                            </span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                style="width: {{ $progressGlobalJobs }}%;"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            {{ $jobsConcluidos }}/{{ $totalJobs }} atividades concluídas
                        </small>
                    </div>

                    <div class="border-top pt-2">
                        <div class="row text-center mb-2">
                            <div class="col-4">
                                <span class="fs-5 fw-bold text-primary">{{ $totalMedidas }}</span><br>
                                <small class="text-muted">Medidas</small>
                            </div>
                            <div class="col-4">
                                <span class="fs-5 fw-bold text-success">{{ $medidasAtivas }}</span><br>
                                <small class="text-muted">Ativas</small>
                            </div>
                            <div class="col-4">
                                <span class="fs-5 fw-bold text-info">{{ $medidasEncerradas }}</span><br>
                                <small class="text-muted">Encerradas</small>
                            </div>
                        </div>

                        <div class="row text-center mt-3">
                            <div class="col-6 mb-2">
                                <span class="fw-bold">{{ $jobsAbertos }}</span><br>
                                <small class="text-muted">
                                    <i class="ri-task-line me-1"></i>Jobs em aberto
                                </small>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="fw-bold">{{ $jobsAtrasados }}</span><br>
                                <small class="text-danger">
                                    <i class="ri-timer-flash-line me-1"></i>Jobs atrasados
                                </small>
                            </div>
                            <div class="col-12 mt-1">
                                <span class="fw-bold">{{ $jobsParaConfirmar }}</span><br>
                                <small class="text-warning">
                                    <i class="ri-check-double-line me-1"></i>
                                    Concluídos aguardando confirmação
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>



    {{-- ==== Anexos & Evidências ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="modern-card-title mb-2"><i class="ri-attachment-line me-2"></i>Anexos & Evidências</div>
            <x-files.attachments :files="$protest->evidenceFiles" :deleteAction="$readOnly ? null : 'deleteFile'"
                downloadAction="downloadFiles" />
        </div>
    </div>

    {{-- ==== Obras Associadas ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="modern-card-title"><i class="ri-building-line me-1"></i>Obras Associadas</span>
                @unless ($readOnly)
                    <button class="btn btn-sm btn-warning"
                        wire:click.defer="$emitTo('protests.dispatch.actions.add-notes-relation', 'openAddNotesRelation', {{ $protest->id }})">
                        <i class="ri-add-box-fill me-1"></i>Associar
                    </button>
                @endunless
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
                                @unless ($readOnly)
                                    <th>Ações</th>
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($protest->all_notes as $note)
                                <tr class="text-center align-middle">
                                    <td>
                                        <span
                                            class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">{{ $note->note }}</span>
                                    </td>
                                    <td class="fw-medium">{{ $note->client }}</td>
                                    <td><span class="text-muted small">{{ $note->rubrica }}</span></td>
                                    <td>{{ $note->lexp }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;"
                                            title="{{ $note->material }}">{{ $note->material }}</div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-info bg-opacity-10 text-info">{{ $note->type_note == 2 ? $note->nstats : $note->centerjob }}</span>
                                    </td>
                                    @unless ($readOnly)
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" title="Remover Associação"
                                                data-bs-toggle="tooltip"
                                                wire:click.prevent="removeNoteFromProtest({{ $note->pivot->id }})"
                                                onclick="return confirm('Remover esta associação?')">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    @endunless
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="ri-building-line fs-1 mb-3 opacity-50"></i>
                    <h5 class="mb-2">Nenhuma obra associada</h5>
                    @if ($readOnly)
                        <p class="mb-0 text-center">Não há notas ou OVs associadas a esta reclamação.</p>
                    @else
                        <p class="mb-0 text-center">Clique no botão "Associar" para vincular notas ou OVs a esta
                            reclamação
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ==== Medidas Registradas ==== --}}

    <div class="modern-card">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between flex-wrap align-items-start mb-3">
                <div class="modern-card-title mb-2">
                    <i class="ri-list-check-2 me-2"></i>Medidas Registradas
                </div>
            </div>

            @if ($protest->medProtests?->isNotEmpty())
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:.95rem;">
                        <thead class="table-light">
                            <tr class="text-nowrap">
                                <th style="min-width:160px;">Medida</th>
                                <th style="min-width:220px;">Responsável / Executor</th>
                                <th style="min-width:160px;">Situação Execução</th>
                                <th style="min-width:170px;">Data Abertura</th>
                                <th style="min-width:170px;">Prazo da Medida</th>
                                <th style="min-width:280px;">SLA da Atividade Atual</th>
                                @if ($readOnly)
                                    <th style="width:1%;"></th>
                                @else
                                    <th class="text-center" style="width:1%;">Ações</th>
                                    <th style="width:1%;"></th>
                                @endif
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($protest->medProtests->sortByDesc('med_id') as $medProtest)
                                @php
                                    /** @var \App\Models\ProtestJob|null $last */
                                    $last = $medProtest->LastProtestJob;
                                    $jobs = $medProtest->ProtestJobs ?? collect();
                                    $expanded = $readOnly ? ($expandedJobs[$medProtest->id] ?? true) : ($expandedJobs[$medProtest->id] ?? false);

                                    // === Métricas de jobs por medida ===
                                    $jobsTotal = $jobs->count();

                                    $openStatusValues = [
                                        \App\Enum\ProtestJobStatus::OPENED->value,
                                        \App\Enum\ProtestJobStatus::ASSIGNED->value,
                                        \App\Enum\ProtestJobStatus::IN_PROGRESS->value,
                                        \App\Enum\ProtestJobStatus::WAITING->value,
                                        \App\Enum\ProtestJobStatus::REOPENED->value,
                                    ];

                                    $jobsOpen = $jobs
                                        ->filter(fn($job) => in_array($job->status->value, $openStatusValues, true))
                                        ->count();

                                    $jobsClosed = $jobsTotal - $jobsOpen;

                                    // Pessoas (do LAST)
                                    $creator = $last?->creator?->name;
                                    $owner = $last?->owner?->name;

                                    // Datas (já são Carbon por cast do modelo)
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

                                    $medStatus = [
                                        'status' => 'Desconhecido',
                                        'badge' => 'text-bg-secondary',
                                    ];
                                    if ($medFinished) {
                                        if ($medFinished?->startOfDay()->gt($prazoDate?->startOfDay())) {
                                            $medStatus = [
                                                'status' => 'Fora do Prazo',
                                                'badge' => 'text-bg-danger',
                                            ];
                                        } else {
                                            $medStatus = [
                                                'status' => 'No Prazo',
                                                'badge' => 'text-bg-success',
                                            ];
                                        }
                                    } else {
                                        if (now()->startOfDay()->gt($prazoDate?->startOfDay())) {
                                            $medStatus = [
                                                'status' => 'Atrasada',
                                                'badge' => 'text-bg-danger',
                                            ];
                                        } else {
                                            $medStatus = [
                                                'status' => 'No Prazo',
                                                'badge' => 'text-bg-success',
                                            ];
                                        }
                                    }

                                    // Badge de SLA do card principal
                                    $slaBadge = '<span class="badge bg-secondary">SEM SLA</span>';
                                    if ($last && $dueAt) {
                                        if (!$finishAt && $nowRef->lte($dueAt)) {
                                            $slaBadge = '<span class="badge bg-success text-light">NO PRAZO</span>';
                                        } elseif (!$finishAt && $nowRef->gt($dueAt)) {
                                            $slaBadge = '<span class="badge bg-danger text-light">ATRASADO</span>';
                                        } elseif ($finishAt && $finishAt->lte($dueAt)) {
                                            $slaBadge =
                                                '<span class="badge bg-success text-light">ENTREGUE NO PRAZO</span>';
                                        } elseif ($finishAt && $finishAt->gt($dueAt)) {
                                            $slaBadge =
                                                '<span class="badge bg-danger text-light">ENTREGUE COM ATRASO</span>';
                                        }
                                    }

                                    // Barra de SLA (percentual do LAST)
                                    $pct = 0;
                                    if ($startAt && $dueAt) {
                                        $total = max($dueAt->diffInSeconds($startAt), 1);
                                        $until = $finishAt ?: $nowRef;
                                        $spent = max(min($until->diffInSeconds($startAt), $total), 0);
                                        $pct = max(0, min(100, round(($spent / $total) * 100)));
                                    }

                                    // Situação Execução (usar accessors do LAST; se não houver, cai para statusSist da medida)
                                    $execStatusText = $last?->status_label ?? ($medProtest->statusSist ?? '—');
                                    $execStatusClass = $last?->status_badge_class ?? 'bg-secondary';

                                    // Estados da medida (pelo statusSist)
                                    $isClosedMeasure = $medProtest->statusSist === 'MEDE';
                                    $isActiveMeasure = $medProtest->statusSist === 'MEDA';
                                    $showActions = $isActiveMeasure;

                                    // Destaque visual da linha quando o LAST está atrasado e em aberto
                                    $rowClass = '';
                                    if ($last && !$finishAt && $dueAt && $nowRef->gt($dueAt)) {
                                        $rowClass = 'table-danger';
                                    }

                                    // Badge curto do status da medida
                                    $statusBadgeColor = $isClosedMeasure
                                        ? 'info'
                                        : ($isActiveMeasure
                                            ? 'success'
                                            : 'secondary');
                                    $statusBadgeText = $isClosedMeasure
                                        ? 'Encerrada'
                                        : ($isActiveMeasure
                                            ? 'Ativa'
                                            : $medProtest->statusSist ?? '—');
                                @endphp

                                {{-- ===== LINHA PRINCIPAL DA MEDIDA ===== --}}
                                <tr class="measure-row-main {{ $rowClass }}"
                                    style="--bs-table-bg: var(--bs-body-bg);">
                                    {{-- Medida --}}
                                    <td class="align-top border-start"
                                        style="border-left: 4px solid {{ $isClosedMeasure ? '#0dcaf0' : ($rowClass ? '#dc3545' : '#0d6efd') }};">
                                        <div class="d-flex flex-column">
                                            <div class="fw-bold d-flex align-items-center gap-2 mb-1">
                                                <span># {{ $medProtest->med_id }}</span>
                                                <span
                                                    class="badge bg-{{ $statusBadgeColor }}">{{ $statusBadgeText }}</span>
                                                @if ($isClosedMeasure)
                                                    <span class="badge bg-secondary">Finalizada</span>
                                                @endif
                                            </div>

                                            <div class="text-muted small text-truncate" style="max-width:260px;">
                                                {{ $medProtest->txtCodMedida }}
                                            </div>

                                            {{-- Métricas de jobs da medida --}}
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

                                    {{-- Responsável / Executor (do LAST) --}}
                                    <td class="align-top">
                                        <div class="mini-label">Resp. Técnico</div>
                                        <div class="mini-value mb-2">{{ $creator ?? '—' }}</div>
                                        <div class="mini-label">Executor</div>
                                        <div class="mini-value">
                                            @if ($owner)
                                                <span class="badge bg-secondary">{{ $owner }}</span>
                                            @else
                                                <span class="badge bg-danger">SEM EXECUTOR</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Situação Execução (do LAST via accessor) --}}
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

                                    {{-- Data Abertura Medida --}}
                                    <td class="align-top">
                                        <div class="mini-label">Data Abertura</div>
                                        <div class="mini-value mb-2">
                                            {{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}</div>
                                        {{-- @if ($medFinished)
                                            <div>
                                                <span class="badge bg-info">
                                                    Concluído em {{ $medFinished?->format('d/m/Y H:i') }}
                                                </span>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="badge {{ $medStatus['badge'] }}">
                                                {{ $medStatus['status'] }}
                                            </span>
                                        </div> --}}
                                    </td>

                                    {{-- Prazo da Medida --}}
                                    <td class="align-top">
                                        <div class="mini-label">Prazo Desejado</div>
                                        <div class="mini-value mb-2">{{ $prazoTxt }}</div>
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

                                    {{-- SLA da Atividade Atual (LAST) --}}
                                    <td class="align-top">
                                        <div class="mini-label d-flex align-items-center justify-content-between">
                                            <span>SLA / Progresso</span>
                                            {!! $slaBadge !!}
                                        </div>

                                        @if ($startAt && $dueAt)
                                            <div class="progress my-2" style="height:10px;">
                                                <div class="progress-bar {{ !$finishAt && $dueAt && now()->gt($dueAt) ? 'bg-danger' : 'bg-success' }}"
                                                    role="progressbar" style="width: {{ $pct }}%;"></div>
                                            </div>
                                            <div class="sla-info-lines">
                                                <div>Limite:
                                                    <strong class="text-primary">
                                                        {{ $dueAt?->format('d/m/Y H:i') }}
                                                    </strong>
                                                </div>
                                                @if (!$finishAt && $dueAt)
                                                    <div>
                                                        {{ now()->lte($dueAt)
                                                            ? 'restam ' . now()->diffForHumans($dueAt, true)
                                                            : 'atraso há ' . $dueAt->diffForHumans(now(), true) }}
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

                                    {{-- Ações (somente MEDA) --}}
                                    @if ($readOnly)
                                        <td class="text-end align-top">
                                            @if ($jobs->isNotEmpty())
                                                <button class="btn btn-outline-secondary icon-btn-table"
                                                    wire:click="toggleJobs({{ $medProtest->id }})"
                                                    title="Recolher/expandir atividades"
                                                    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                                                    aria-controls="jobs-{{ $medProtest->id }}">
                                                    <i
                                                        class="{{ $expanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="text-center align-top" style="white-space:nowrap;">
                                            @if ($showActions)
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <button class="btn btn-outline-primary icon-btn-table"
                                                        title="Gerenciar / Criar Atividade"
                                                        wire:click.prevent="$emitTo('protests.dispatch.actions.control-med-protest', 'openModProtestControl', {{ $medProtest->id }})">
                                                        <i class="ri-send-plane-line"></i>
                                                    </button>
                                                    @if ($isActiveMeasure && $last && !$finishAt && $jobs->every(fn($job) => $job->confirmed))
                                                        <button class="btn btn-outline-success icon-btn-table"
                                                            title="Confirmar Conclusão da Medida"
                                                            wire:click.prevent="approveMed({{ $medProtest->id }})">
                                                            <i class="ri-check-line"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>

                                        {{-- Expand toggle --}}
                                        <td class="text-end align-top">
                                            <div class="d-flex align-items-center justify-content-end gap-1">
                                                <button class="btn btn-outline-success icon-btn-table"
                                                    title="Anexar arquivos da medida"
                                                    wire:click.prevent="$emitTo('protests.dispatch.actions.upload-med-protest-files', 'openUploader', {{ $medProtest->id }})">
                                                    <i class="ri-upload-cloud-2-line"></i>
                                                </button>
                                                @if ($jobs->isNotEmpty())
                                                    <button class="btn btn-outline-secondary icon-btn-table"
                                                        wire:click="toggleJobs({{ $medProtest->id }})"
                                                        title="Ver atividades relacionadas"
                                                        aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                                                        aria-controls="jobs-{{ $medProtest->id }}">
                                                        <i
                                                            class="{{ $expanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>

                                {{-- ===== BLOCO DE JOBS (COLLAPSE) ===== --}}
                                @if ($jobs->isNotEmpty() && $expanded)
                                    <tr id="jobs-{{ $medProtest->id }}">
                                        <td colspan="{{ $readOnly ? 7 : 8 }}" class="jobs-cell">
                                            @foreach ($jobs->sortByDesc('created_at') as $job)
                                                @php
                                                    // Accessors do modelo
                                                    $jobStatusLabel = $job->status_label;
                                                    $jobStatusBadgeClass = $job->status_badge_class;
                                                    $jobPriorityLabel = $job->priority_label;
                                                    $jobPriorityBadge = $job->priority_badge_class;

                                                    // Datas
                                                    $jStart = $job->started_at ?? $job->created_at;
                                                    $jDue = $job->sla_due_at;
                                                    $jFinish = $job->finished_at;

                                                    // Badge SLA job
                                                    $jBadge = '<span class="badge bg-secondary">sem SLA</span>';
                                                    if ($jDue) {
                                                        if (!$jFinish && now()->lte($jDue)) {
                                                            $jBadge =
                                                                '<span class="badge bg-success text-light">no prazo</span>';
                                                        } elseif (!$jFinish && now()->gt($jDue)) {
                                                            $jBadge =
                                                                '<span class="badge bg-danger text-light">atrasado</span>';
                                                        } elseif ($jFinish && $jFinish->lte($jDue)) {
                                                            $jBadge =
                                                                '<span class="badge bg-success text-light">entregue no prazo</span>';
                                                        } else {
                                                            $jBadge =
                                                                '<span class="badge bg-danger text-light">entregue com atraso</span>';
                                                        }
                                                    }

                                                    // Percentual SLA job
                                                    $jPct = 0;
                                                    if ($jStart && $jDue) {
                                                        $jTotal = max($jDue->diffInSeconds($jStart), 1);
                                                        $jUntil = $jFinish ?: now();
                                                        $jSpent = max(min($jUntil->diffInSeconds($jStart), $jTotal), 0);
                                                        $jPct = max(0, min(100, round(($jSpent / $jTotal) * 100)));
                                                    }

                                                @endphp

                                                <div
                                                    class="job-box {{ !$jFinish && $jDue && now()->gt($jDue) ? 'border-danger border-2' : '' }}">
                                                    <div class="job-header-line">
                                                        <div class="job-left-chunk">
                                                            <span class="job-id-badge">ATVD {{ $job->id }}</span>

                                                            {{-- Status via accessor --}}
                                                            <span class="job-status-pill {{ $jobStatusBadgeClass }}">
                                                                {{ strtoupper($jobStatusLabel) }}
                                                            </span>

                                                            {{-- Prioridade via accessor --}}
                                                            <span
                                                                class="job-priority-pill badge {{ $jobPriorityBadge }}">
                                                                {{ $jobPriorityLabel }}
                                                            </span>

                                                            @if ($job->is_advance)
                                                                <span class="badge bg-dark text-white"
                                                                    style="font-size:.65rem;">AVANÇA</span>
                                                            @endif
                                                            @if ($job->need_evidence)
                                                                <span class="badge bg-warning text-dark"
                                                                    style="font-size:.65rem;">EVIDÊNCIA</span>
                                                            @endif
                                                        </div>

                                                        <div class="job-right-chunk">
                                                            @unless ($readOnly)
                                                                <div class="job-owner text-end me-2">
                                                                    <div class="label">Responsável</div>
                                                                    <div class="value">{{ $job->owner?->name ?? '—' }}
                                                                    </div>
                                                                </div>
                                                            @endunless
                                                        </div>
                                                    </div>

                                                    <div class="job-body-grid">
                                                        <div class="job-col-block job-col-block--meta">
                                                            <div class="job-label job-sla-headline">
                                                                <span>SLA / Progresso</span>
                                                                <span
                                                                    class="job-sla-badge">{!! $jBadge !!}</span>
                                                            </div>

                                                            @if ($jStart && $jDue)
                                                                <div class="progress mb-2 mt-1" style="height: 10px;">
                                                                    <div class="progress-bar {{ !$jFinish && now()->gt($jDue) ? 'bg-danger' : 'bg-success' }}"
                                                                        role="progressbar"
                                                                        style="width: {{ $jPct }}%;"
                                                                        aria-valuenow="{{ $jPct }}"
                                                                        aria-valuemin="0" aria-valuemax="100">
                                                                        {{ $jPct }}%
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <div class="sla-info-lines">
                                                                <div>Limite:
                                                                    {{ $jDue ? $jDue?->format('d/m/Y H:i') : '—' }}
                                                                </div>
                                                                @if (!$jFinish && $jDue)
                                                                    <div>
                                                                        {{ now()->lte($jDue)
                                                                            ? 'restam ' . now()->diffForHumans($jDue, true)
                                                                            : 'atraso há ' . $jDue->diffForHumans(now(), true) }}
                                                                    </div>
                                                                @endif
                                                                @if ($jFinish)
                                                                    <div>Finalizado:
                                                                        {{ $jFinish?->format('d/m/Y H:i') }}</div>
                                                                @endif
                                                            </div>

                                                            <div class="job-meta-list mt-3">
                                                                <div class="job-meta-item">
                                                                    <span>Despachado em</span>
                                                                    <strong>{{ ($job->sent_at ?? $job->created_at)?->format('d/m/Y H:i') ?? '—' }}</strong>
                                                                </div>
                                                                <div class="job-meta-item">
                                                                    <span>Status</span>
                                                                    <strong>{{ strtoupper($jobStatusLabel) }}</strong>
                                                                </div>
                                                                <div class="job-meta-item">
                                                                    <span>Quem realizou</span>
                                                                    <strong>{{ $job->owner?->name ?? '—' }}</strong>
                                                                </div>
                                                                <div class="job-meta-item">
                                                                    <span>Finalizado em</span>
                                                                    <strong>{{ $job->finished_at?->format('d/m/Y H:i') ?? '—' }}</strong>
                                                                </div>
                                                                <div class="job-meta-item">
                                                                    <span>Finalizado por</span>
                                                                    <strong>{{ $job->closer?->name ?? '—' }}</strong>
                                                                </div>
                                                            </div>

                                                            @unless ($readOnly)
                                                                <div class="job-actions-under-meta">
                                                                    <button class="btn btn-outline-info job-action-btn"
                                                                        title="Visualizar atividade"
                                                                        wire:click="$emitTo('protests.dispatch.actions.view-protest-job', 'open', {{ $job->id }})">
                                                                        <i class="ri-eye-line"></i>
                                                                    </button>
                                                                    @if (!$job->confirmed && $job->status->value !== 'canceled')
                                                                        <button class="btn btn-outline-primary job-action-btn"
                                                                            title="Editar atividade"
                                                                            wire:click.prevent="$emitTo('protests.dispatch.actions.edit-control-med-protest', 'openJobEditor', {{ $job->id }})"
                                                                            @disabled($job->status->value === 'done')>
                                                                            <i class="ri-pencil-line"></i>
                                                                        </button>
                                                                        <button class="btn btn-outline-success job-action-btn"
                                                                            title="Marcar como concluída"
                                                                            wire:click.prevent="toConfirmJob({{ $job->id }})"
                                                                            @disabled($job->status->value !== 'done')>
                                                                            <i class="ri-check-line"></i>
                                                                        </button>
                                                                        <button class="btn btn-outline-warning job-action-btn"
                                                                            title="Reabrir atividade"
                                                                            wire:click.prevent="toReopen({{ $job->id }})"
                                                                            @disabled($job->status->value !== 'done')>
                                                                            <i class="ri-refresh-line"></i>
                                                                        </button>
                                                                        <button class="btn btn-outline-danger job-action-btn"
                                                                            title="Cancelar atividade"
                                                                            wire:click.prevent="toCancelJob({{ $job->id }})"
                                                                            @disabled($job->status->value === 'cancelled')>
                                                                            <i class="ri-close-line"></i>
                                                                        </button>
                                                                        @can('admin')
                                                                            <button class="btn btn-outline-danger job-action-btn"
                                                                                title="Deletar atividade"
                                                                                wire:click.prevent="deleteJob({{ $job->id }})"
                                                                                onclick="return confirm('Tem certeza que deseja deletar esta atividade?')">
                                                                                <i class="ri-delete-bin-line"></i>
                                                                            </button>
                                                                        @endcan
                                                                    @endif
                                                                </div>
                                                            @endunless
                                                        </div>

                                                        <div class="job-col-block job-col-block--content">
                                                            <div class="job-label">Descrição do Pedido</div>
                                                            <div class="job-value job-resolution-text mb-3">
                                                                {{ $job->notes ?? '—' }}
                                                            </div>

                                                            <div class="job-result-head">
                                                                <div>
                                                                    <div class="job-label">Finalizado em</div>
                                                                    <div class="job-value">
                                                                        {{ $job->finished_at?->format('d/m/Y H:i') ?? '—' }}
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="job-label">Avaliação</div>
                                                                    <div class="job-value text-uppercase">
                                                                        {{ $job->medProtest?->result ?? '—' }}
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="job-label mt-3">Resolução da Atividade</div>
                                                            <div class="job-value job-resolution-text">
                                                                {{ $job->close_reason ?? '—' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
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
                    <p class="mb-0 text-center">Não há medidas cadastradas para esta reclamação</p>
                </div>
            @endif
        </div>
    </div>



    {{-- ==== Interações / Comentários ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="modern-card-title mb-2">
                <i class="ri-chat-3-line me-2"></i>
                Observações para #{{ $protest?->nota }}
                <p class="fw-light my-0 py-1" style="font-size: 0.75rem;">
                    A última observação estará visível para todos os usuários das medidas.
                </p>
            </div>

            <div class="row">
                @unless ($readOnly)
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <textarea class="form-control @error('comment') is-invalid @enderror" placeholder="Digite sua observação..."
                                id="floatingTextarea" style="height: 200px" wire:model.defer="comment"></textarea>

                            <label for="floatingTextarea">Sua Observação</label>

                            @error('comment')
                                <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                            @enderror

                            <div class="d-grid mt-2">
                                <button type="submit" class="btn btn-primary" wire:click.prevent="addComment">
                                    <i class="ri-send-plane-fill me-1"></i>
                                    Enviar Observação
                                </button>
                            </div>
                        </div>
                    </div>
                @endunless

                <div class="{{ $readOnly ? 'col-md-12' : 'col-md-8' }}">
                    <div class="chat-container border rounded bg-light">
                        @forelse($protest->comments->sortByDesc('created_at') as $comment)
                            <div class="chat-message p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-circle" title="{{ $comment->user->name }}">
                                            <img src="{{ $comment->user->avatar_url }}"
                                                alt="Avatar de {{ $comment->user->name }}">
                                        </div>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="fw-semibold {{ $comment->user_id === auth()->user()->id ? 'text-primary' : 'text-dark' }}">
                                                    {{ $comment->user->name }}
                                                </span>

                                                @if (!$readOnly && $comment->user?->email)
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
                                                    !$readOnly &&
                                                        (($comment->created_at->diffInHours() < 1 && $comment->id === $protest->comments->max('id')) ||
                                                            auth()->user()->admin ||
                                                            auth()->user()->superadm))
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
                                <p class="mb-0 text-center">
                                    Seja o primeiro a comentar nesta reclamação
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==== Livewire Modals ==== --}}
    @unless ($readOnly)
        @livewire('protests.dispatch.actions.add-notes-relation', key('add-notes-relation-' . $protest->id))
        @livewire('protests.dispatch.actions.control-med-protest', key('control-med-protest-' . $protest->id))
        @livewire('protests.dispatch.actions.edit-control-med-protest', key('edit-control-med-protest-' . $protest->id))
        @livewire('protests.dispatch.actions.upload-med-protest-files', key('upload-med-protest-files-' . $protest->id))
        @livewire('protests.dispatch.actions.view-protest-job', key('view-protest-job'))
    @endunless
</div>
