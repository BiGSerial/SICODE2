<div>
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
                display: flex;
                align-items: center;
                gap: .5rem;
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

                .modern-card-value {
                    font-size: 1.5rem;
                }
            }
        </style>

        <style>
            /* botão ação em tabela / job-line */
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

            /* cada atividade (job) expandida */
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

            /* bloco da esquerda (id, status, prioridade) */
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

            /* bloco da direita (dono + botões) */
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

            /* corpo da atividade (SLA, datas, notas) */
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

            /* cabeçalho do bloco de SLA de cada atividade */
            .job-sla-headline {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
                row-gap: .5rem;
                column-gap: .5rem;
            }

            .job-sla-badge .badge {
                font-size: .65rem;
            }

            /* barra SLA da atividade */
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

            /* linha mais "painel técnico" da medida */
            .measure-row-main td {
                vertical-align: top;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }

            /* célula compacta de label/value na tabela principal */
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
                font-size: .9rem;
                line-height: 1.3;
                font-weight: 600;
                color: #2c3e50;
            }

            /* barra SLA na tabela principal (medida) */
            .sla-bar-wrap {
                width: 220px;
                max-width: 100%;
                background: #f1f3f5;
                border-radius: 6px;
                overflow: hidden;
                box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .05);
                height: 10px;
                display: flex;
            }

            .sla-seg {
                height: 100%;
                font-size: 0;
            }

            /* cores dos segmentos */
            .sla-ontrack {
                background: #0d6efd33;
            }

            /* decorrido dentro do prazo */
            .sla-remaining {
                background: #ced4da;
            }

            /* tempo restante até SLA */
            .sla-sla-window {
                background: #19875466;
            }

            /* janela SLA "teórica" */
            .sla-early {
                background: #198754;
            }

            /* sobra antes do SLA */
            .sla-late {
                background: #dc3545;
            }

            /* atraso */

            /* legenda / status sla */
            .sla-info-lines {
                font-size: .75rem;
                line-height: 1.3;
                color: #495057;
            }

            .sla-info-lines .badge {
                font-size: .65rem;
            }

            /* linha expandida de jobs */
            .jobs-cell {
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
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

                    if ($protest->tipoNota == 'OU') {
                        if ($protest->medProtests->where('statusSist' == 'MEDA')->isNotEmpty()) {
                            $dtConclusao = $protest->medProtests->where('statusSist', 'MEDA')->last()->dtFimMedidaDesej;
                        } else {
                            $dtConclusao = $protest->medProtests->last()->dtFimMedidaDesej;
                        }
                    } else {
                        $dtConclusao = $protest->dtConclusaoDesej;
                    }

                    $daysDiff = $dtConclusao ? $now->diffInDays($dtConclusao, false) : 0;

                    if ($dtConclusao && $dtConclusao->endOfDay()->isPast()) {
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
                            <span
                                class="fw-medium small">{{ $protest->medProtests?->last()?->txtCodCodificacao }}</span>
                            <span class="text-muted small d-block mt-1">SubCausa:</span>
                            <span class="fw-medium small">{{ $protest->medProtests?->last()?->txtCodMedida }}</span>
                            <span class="text-muted small d-block mt-1">Descrição:</span>
                            <span class="fw-medium small">{{ $protest->comments->last()?->message }}</span>
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
                            <span class="text-muted small"><i class="ri-flag-line me-1"></i>Conclusão Desejada:</span>
                            <span class="fw-medium small">{{ $dtConclusao?->format('d/m/Y') }}</span>
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

    {{-- ==== Anexos & Evidências ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="modern-card-title mb-2"><i class="ri-attachment-line me-2"></i>Anexos & Evidências</div>
            <x-files.attachments :files="$protest->evidenceFiles" deleteAction="deleteFile" downloadAction="dowloadFile" />
        </div>
    </div>

    {{-- ==== Obras Associadas ==== --}}
    <div class="modern-card">
        <div class="modern-card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="modern-card-title"><i class="ri-building-line me-1"></i>Obras Associadas</span>
                <button class="btn btn-sm btn-warning"
                    wire:click.defer="$emitTo('protests.dispatch.actions.add-notes-relation', 'openAddNotesRelation', {{ $protest->id }})">
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
                    <p class="mb-0 text-center">Clique no botão "Associar" para vincular notas ou OVs a esta reclamação
                    </p>
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
                    <table class="table mb-0" style="font-size:.9rem;">
                        <thead class="table-light">
                            <tr class="text-nowrap">
                                <th style="min-width:140px;">Medida</th>
                                <th style="min-width:180px;">Responsável / Executor</th>
                                <th style="min-width:140px;">Situação Execução</th>
                                <th style="min-width:160px;">Prazo da Medida</th>
                                <th style="min-width:260px;">SLA da Atividade Atual</th>
                                <th style="width:1%; text-align:center;">Ações</th>
                                <th style="width:1%;"></th> {{-- expand --}}
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($protest->medProtests->sortByDesc('dtCriacaoMedida') as $medProtest)
                                @php
                                    // ===== Dados básicos da medida =====
                                    $assignmentResp = $medProtest->assignments?->where('responsible', true)->last();
                                    $assignmentUser = $medProtest->assignments?->where('user', true)->last();

                                    $responsibleName = $assignmentResp?->User?->name ?? '—';
                                    $executorName = $assignmentUser?->User?->name ?? null;

                                    $hasExecutor = (bool) $executorName;

                                    if ($assignmentUser) {
                                        $execStatusText = $assignmentUser->completed ? 'Concluída' : 'Pendente';
                                        $execStatusColor = $assignmentUser->completed ? 'success' : 'warning';
                                        $finishedAt = $assignmentUser->ended_at?->format('d/m/Y H:i');
                                    } else {
                                        $execStatusText = 'Sem Atribuição';
                                        $execStatusColor = 'danger';
                                        $finishedAt = null;
                                    }

                                    $isAberta = $medProtest->statusSist === 'MEDA';
                                    $statusBadgeText = $isAberta ? 'ABERTO' : 'FECHADO';
                                    $statusBadgeColor = $isAberta ? 'success' : 'secondary';

                                    $prazoDesejadoCarbon = $medProtest->dtFimMedidaDesej;
                                    $prazoDesejadoTxt = $prazoDesejadoCarbon
                                        ? $prazoDesejadoCarbon->format('d/m/Y')
                                        : '—';

                                    $prazoBadge = null;
                                    if ($prazoDesejadoCarbon) {
                                        $diffDias = now()->diffInDays($prazoDesejadoCarbon, false);

                                        if ($prazoDesejadoCarbon->endOfDay()->isPast()) {
                                            $prazoBadge = '<span class="badge bg-danger">VENCIDA</span>';
                                        } elseif ($diffDias <= 1) {
                                            $prazoBadge =
                                                '<span class="badge bg-warning text-dark">vence em ' .
                                                $diffDias .
                                                'd</span>';
                                        } else {
                                            $prazoBadge =
                                                '<span class="badge bg-success">faltam ' . $diffDias . 'd</span>';
                                        }
                                    }

                                    $fimReal = $medProtest->dtFimMedida
                                        ? $medProtest->dtFimMedida->format('d/m/Y')
                                        : null;
                                    $onTime = null;
                                    if ($medProtest->dtFimMedida && $medProtest->dtFimMedidaDesej) {
                                        $onTime = $medProtest->dtFimMedida <= $medProtest->dtFimMedidaDesej;
                                    }
                                    if ($fimReal) {
                                        $fimRealBadge =
                                            $onTime === null
                                                ? '<span class="badge bg-secondary">Finalizada ' . $fimReal . '</span>'
                                                : ($onTime
                                                    ? '<span class="badge bg-success">Finalizada ' .
                                                        $fimReal .
                                                        '</span>'
                                                    : '<span class="badge bg-danger">Finalizada ' .
                                                        $fimReal .
                                                        '</span>');
                                    } else {
                                        $fimRealBadge = null;
                                    }

                                    $jobs = $medProtest->ProtestJobs ?? collect();

                                    // job principal (aberto mais urgente; se não houver, pega mais recente)
                                    $openJobs = $jobs->filter(function ($j) {
                                        $st = $j->status instanceof \BackedEnum ? $j->status->value : $j->status;
                                        return !in_array($st, ['done', 'canceled']);
                                    });

                                    $mainJob =
                                        $openJobs->sortBy('sla_due_at')->first() ??
                                        $jobs->sortByDesc('created_at')->first();

                                    // ===== Cálculo da barra SLA (medida) =====
                                    $startAt = $mainJob?->created_at
                                        ? \Carbon\Carbon::parse($mainJob->created_at)
                                        : null;
                                    $dueAt = $mainJob?->sla_due_at ? \Carbon\Carbon::parse($mainJob->sla_due_at) : null;
                                    $finishAt = $mainJob?->finished_at
                                        ? \Carbon\Carbon::parse($mainJob->finished_at)
                                        : null;
                                    $nowRef = now();

                                    $endRef = $finishAt ?? $nowRef;

                                    $segments = [];
                                    $slaStateBadge = '';
                                    $slaExplainTop = '';
                                    $slaExplainMid = '';
                                    $slaExplainBot = '';
                                    $isLateNow = false;

                                    if ($mainJob && $startAt && $dueAt) {
                                        $compareEnd = $finishAt ?? $nowRef;

                                        $totalSpan = $compareEnd->max($dueAt)->diffInSeconds($startAt);
                                        $totalSpan = max($totalSpan, 1);

                                        $pct = function ($from, $to) use ($startAt, $totalSpan) {
                                            $sec = $to->diffInSeconds($from, false);
                                            if ($sec < 0) {
                                                $sec = 0;
                                            }
                                            return max(0, min(100, ($sec / $totalSpan) * 100));
                                        };

                                        if (!$finishAt) {
                                            if ($nowRef->lte($dueAt)) {
                                                // ainda no prazo
                                                $pastPct = $pct($startAt, $nowRef);
                                                $remainingPct = $pct($nowRef, $dueAt);

                                                $segments[] = ['class' => 'sla-ontrack', 'w' => $pastPct];
                                                $segments[] = ['class' => 'sla-remaining', 'w' => $remainingPct];

                                                $slaStateBadge =
                                                    '<span class="badge bg-success text-light">no prazo</span>';
                                                $slaExplainTop = 'Limite: ' . $dueAt->format('d/m/Y H:i');
                                                $slaExplainMid = 'em ' . now()->diffForHumans($dueAt, true);
                                                $slaExplainBot =
                                                    'Status Atividade: ' .
                                                    strtoupper(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $mainJob->status instanceof \BackedEnum
                                                                ? $mainJob->status->value
                                                                : $mainJob->status,
                                                        ),
                                                    );
                                            } else {
                                                // atrasado em andamento
                                                $onTimePct = $pct($startAt, $dueAt);
                                                $latePct = $pct($dueAt, $nowRef);

                                                $segments[] = ['class' => 'sla-sla-window', 'w' => $onTimePct];
                                                $segments[] = ['class' => 'sla-late', 'w' => $latePct];

                                                $isLateNow = true;
                                                $slaStateBadge =
                                                    '<span class="badge bg-danger text-light">ATRASADO</span>';
                                                $slaExplainTop = 'Limite: ' . $dueAt->format('d/m/Y H:i');
                                                $slaExplainMid = 'atraso há ' . $dueAt->diffForHumans($nowRef, true);
                                                $slaExplainBot =
                                                    'Status Atividade: ' .
                                                    strtoupper(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $mainJob->status instanceof \BackedEnum
                                                                ? $mainJob->status->value
                                                                : $mainJob->status,
                                                        ),
                                                    );
                                            }
                                        } else {
                                            // finalizado
                                            if ($finishAt->lte($dueAt)) {
                                                // terminou ANTES do SLA
                                                $workPct = $pct($startAt, $finishAt);
                                                $sparePct = $pct($finishAt, $dueAt);

                                                $segments[] = ['class' => 'sla-ontrack', 'w' => $workPct];
                                                $segments[] = ['class' => 'sla-early', 'w' => $sparePct];

                                                $slaStateBadge =
                                                    '<span class="badge bg-success text-light">entregue no prazo</span>';
                                                $slaExplainTop = 'Concluído: ' . $finishAt->format('d/m/Y H:i');
                                                $slaExplainMid = 'SLA: ' . $dueAt->format('d/m/Y H:i');
                                                $slaExplainBot = 'Folga de ' . $finishAt->diffForHumans($dueAt, true);
                                            } else {
                                                // terminou DEPOIS do SLA
                                                $onTimePct = $pct($startAt, $dueAt);
                                                $latePct = $pct($dueAt, $finishAt);

                                                $segments[] = ['class' => 'sla-sla-window', 'w' => $onTimePct];
                                                $segments[] = ['class' => 'sla-late', 'w' => $latePct];

                                                $slaStateBadge =
                                                    '<span class="badge bg-danger text-light">entregue com atraso</span>';
                                                $slaExplainTop = 'Concluído: ' . $finishAt->format('d/m/Y H:i');
                                                $slaExplainMid = 'SLA: ' . $dueAt->format('d/m/Y H:i');
                                                $slaExplainBot = 'Atraso de ' . $dueAt->diffForHumans($finishAt, true);
                                            }
                                        }
                                    } else {
                                        // sem SLA / sem atividade principal
                                        if ($mainJob) {
                                            $slaStateBadge = '<span class="badge bg-secondary">sem SLA definido</span>';
                                            $slaExplainTop =
                                                'Atividade ' .
                                                $mainJob->id .
                                                ' criada em ' .
                                                $mainJob->created_at?->format('d/m/Y H:i');
                                            $slaExplainBot =
                                                'Status: ' .
                                                strtoupper(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $mainJob->status instanceof \BackedEnum
                                                            ? $mainJob->status->value
                                                            : $mainJob->status,
                                                    ),
                                                );
                                        } else {
                                            $slaStateBadge = '<span class="badge bg-danger">AGUARDANDO DESPACHO</span>';
                                            $slaExplainTop = 'Nenhuma atividade atribuída ainda.';
                                        }
                                    }

                                    // todos jobs encerrados?
                                    $allJobsClosed = $jobs->every(function ($j) {
                                        $st = $j->status instanceof \BackedEnum ? $j->status->value : $j->status;
                                        return in_array($st, ['done', 'canceled']);
                                    });

                                    $canConfirmMeasure = $allJobsClosed && !$medProtest->completed;
                                    $expanded = $expandedJobs[$medProtest->id] ?? false;
                                @endphp

                                {{-- ===== LINHA PRINCIPAL ===== --}}
                                <tr class="measure-row-main @if ($isLateNow) table-danger @endif">
                                    {{-- Medida --}}
                                    <td style="min-width:140px;">
                                        <div class="fw-bold mb-1">
                                            # {{ $medProtest->med_id }}
                                            <span
                                                class="badge bg-{{ $statusBadgeColor }} ms-1">{{ $statusBadgeText }}</span>
                                            @if ($medProtest->completed)
                                                <span class="badge bg-info ms-1">
                                                    <i class="ri-check-double-line me-1"></i>Finalizada
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-muted small text-truncate" style="max-width:240px;">
                                            {{ $medProtest->txtCodMedida }}
                                        </div>
                                    </td>

                                    {{-- Responsável / Executor --}}
                                    <td style="min-width:180px;">
                                        <div class="mini-label">Resp. Técnico</div>
                                        <div class="mini-value mb-2">
                                            {{ $responsibleName }}
                                        </div>

                                        <div class="mini-label">Executor</div>
                                        <div class="mini-value">
                                            @if ($hasExecutor)
                                                <span class="badge bg-secondary">{{ $executorName }}</span>
                                            @else
                                                <span class="badge bg-danger">SEM EXECUTOR</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Situação Execução --}}
                                    <td style="min-width:140px;">
                                        <div class="mini-label">Situação</div>
                                        <div class="mini-value mb-2">
                                            <span class="badge bg-{{ $execStatusColor }}">
                                                {{ strtoupper($execStatusText) }}
                                            </span>
                                        </div>

                                        @if ($finishedAt)
                                            <div class="text-muted small" style="line-height:1.3;">
                                                Fechou: {{ $finishedAt }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Prazo da Medida --}}
                                    <td style="min-width:160px;">
                                        <div class="mini-label">Prazo Desejado</div>
                                        <div class="mini-value mb-2">{{ $prazoDesejadoTxt }}</div>

                                        @if ($prazoBadge)
                                            <div class="mb-1">{!! $prazoBadge !!}</div>
                                        @endif

                                        @if ($fimRealBadge)
                                            <div>{!! $fimRealBadge !!}</div>
                                        @endif
                                    </td>

                                    {{-- SLA da Atividade Atual --}}
                                    <td style="min-width:260px;">
                                        <div class="mini-label d-flex align-items-center justify-content-between">
                                            <span>SLA / Progresso</span>
                                            {!! $slaStateBadge !!}
                                        </div>

                                        @if (!empty($segments))
                                            <div class="sla-bar-wrap mb-2 mt-1">
                                                @foreach ($segments as $seg)
                                                    <div class="sla-seg {{ $seg['class'] }}"
                                                        style="width: {{ number_format($seg['w'], 2, '.', '') }}%;">
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="sla-info-lines">
                                            @if ($slaExplainTop)
                                                <div>{{ $slaExplainTop }}</div>
                                            @endif
                                            @if ($slaExplainMid)
                                                <div>{{ $slaExplainMid }}</div>
                                            @endif
                                            @if ($slaExplainBot)
                                                <div>{{ $slaExplainBot }}</div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Ações --}}
                                    <td style="text-align:center; white-space:nowrap;">
                                        <button class="btn btn-outline-primary icon-btn-table mb-1"
                                            title="Gerenciar / Criar Atividade"
                                            wire:click.prevent="$emitTo('protests.dispatch.actions.control-med-protest', 'openModProtestControl', {{ $medProtest->id }})">
                                            <i class="ri-play-circle-line"></i>
                                        </button>

                                        @if ($medProtest->completed)
                                            <button class="btn btn-outline-info icon-btn-table mb-1"
                                                title="Imprimir Laudo da Medida"
                                                onclick="window.open('{{ route('protests.print', $medProtest->id) }}', '_blank')">
                                                <i class="ri-printer-line"></i>
                                            </button>
                                        @endif

                                        @if ($canConfirmMeasure)
                                            <button class="btn btn-outline-success icon-btn-table mb-1"
                                                title="Confirmar Conclusão da Medida"
                                                wire:click.prevent="approveMed({{ $medProtest->id }})">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Expand toggle --}}
                                    <td class="text-end align-top">
                                        @if ($jobs->isNotEmpty())
                                            <button class="btn btn-outline-secondary icon-btn-table"
                                                wire:click="toggleJobs({{ $medProtest->id }})"
                                                title="Ver atividades relacionadas">
                                                <i
                                                    class="{{ $expanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                {{-- ===== LINHA EXPANDIDA COM OS JOBS ===== --}}
                                @if ($jobs->isNotEmpty() && $expanded)
                                    <tr>
                                        <td colspan="7" class="jobs-cell">
                                            @foreach ($jobs->sortByDesc('created_at') as $job)
                                                @php
                                                    // normaliza enums
                                                    $jobStatusRaw =
                                                        $job->status instanceof \BackedEnum
                                                            ? $job->status->value
                                                            : $job->status;
                                                    $jobPriorityRaw =
                                                        $job->priority instanceof \BackedEnum
                                                            ? $job->priority->value
                                                            : $job->priority;

                                                    $statusColorMap = [
                                                        'opened' => 'primary',
                                                        'assigned' => 'info',
                                                        'in_progress' => 'warning',
                                                        'waiting' => 'secondary',
                                                        'done' => 'success',
                                                        'canceled' => 'dark',
                                                        'reopened' => 'danger',
                                                    ];
                                                    $statusColor = $statusColorMap[$jobStatusRaw] ?? 'secondary';

                                                    // datas
                                                    $jobStart = $job->created_at
                                                        ? \Carbon\Carbon::parse($job->created_at)
                                                        : null;
                                                    $jobDue = $job->sla_due_at
                                                        ? \Carbon\Carbon::parse($job->sla_due_at)
                                                        : null;
                                                    $jobFinish = $job->finished_at
                                                        ? \Carbon\Carbon::parse($job->finished_at)
                                                        : null;
                                                    $nowRef = now();
                                                    $compareEnd = $jobFinish ?? $nowRef;

                                                    $jobDueTxt = $jobDue ? $jobDue->format('d/m/Y H:i') : '—';
                                                    $jobFinishTxt = $jobFinish ? $jobFinish->format('d/m/Y H:i') : '—';

                                                    // barra SLA por job
                                                    $jobSegments = [];
                                                    $jobSlaStateBadge = '';
                                                    $jobSlaLine1 = '';
                                                    $jobSlaLine2 = '';
                                                    $jobSlaLine3 = '';
                                                    $jobLateNow = false;

                                                    if ($jobStart && $jobDue) {
                                                        $totalSpan = $compareEnd
                                                            ->max($jobDue)
                                                            ->diffInSeconds($jobStart);
                                                        $totalSpan = max($totalSpan, 1);

                                                        $pctJob = function ($from, $to) use ($jobStart, $totalSpan) {
                                                            $sec = $to->diffInSeconds($from, false);
                                                            if ($sec < 0) {
                                                                $sec = 0;
                                                            }
                                                            return max(0, min(100, ($sec / $totalSpan) * 100));
                                                        };

                                                        if (!$jobFinish) {
                                                            // em andamento
                                                            if ($nowRef->lte($jobDue)) {
                                                                // dentro do prazo
                                                                $pastPct = $pctJob($jobStart, $nowRef);
                                                                $remainPct = $pctJob($nowRef, $jobDue);

                                                                $jobSegments[] = [
                                                                    'class' => 'sla-ontrack',
                                                                    'w' => $pastPct,
                                                                ];
                                                                $jobSegments[] = [
                                                                    'class' => 'sla-remaining',
                                                                    'w' => $remainPct,
                                                                ];

                                                                $jobSlaStateBadge =
                                                                    '<span class="badge bg-success text-light">no prazo</span>';
                                                                $jobSlaLine1 =
                                                                    'Limite: ' . $jobDue->format('d/m/Y H:i');
                                                                $jobSlaLine2 =
                                                                    'restam ' . now()->diffForHumans($jobDue, true);
                                                                $jobSlaLine3 =
                                                                    'Status: ' .
                                                                    strtoupper(str_replace('_', ' ', $jobStatusRaw));
                                                            } else {
                                                                // atrasado em andamento
                                                                $onTimePct = $pctJob($jobStart, $jobDue);
                                                                $latePct = $pctJob($jobDue, $nowRef);

                                                                $jobSegments[] = [
                                                                    'class' => 'sla-sla-window',
                                                                    'w' => $onTimePct,
                                                                ];
                                                                $jobSegments[] = [
                                                                    'class' => 'sla-late',
                                                                    'w' => $latePct,
                                                                ];

                                                                $jobLateNow = true;
                                                                $jobSlaStateBadge =
                                                                    '<span class="badge bg-danger text-light">ATRASADO</span>';
                                                                $jobSlaLine1 =
                                                                    'Limite: ' . $jobDue->format('d/m/Y H:i');
                                                                $jobSlaLine2 =
                                                                    'atraso há ' .
                                                                    $jobDue->diffForHumans($nowRef, true);
                                                                $jobSlaLine3 =
                                                                    'Status: ' .
                                                                    strtoupper(str_replace('_', ' ', $jobStatusRaw));
                                                            }
                                                        } else {
                                                            // finalizado
                                                            if ($jobFinish->lte($jobDue)) {
                                                                // entregue ANTES
                                                                $workPct = $pctJob($jobStart, $jobFinish);
                                                                $sparePct = $pctJob($jobFinish, $jobDue);

                                                                $jobSegments[] = [
                                                                    'class' => 'sla-ontrack',
                                                                    'w' => $workPct,
                                                                ];
                                                                $jobSegments[] = [
                                                                    'class' => 'sla-early',
                                                                    'w' => $sparePct,
                                                                ];

                                                                $jobSlaStateBadge =
                                                                    '<span class="badge bg-success text-light">entregue no prazo</span>';
                                                                $jobSlaLine1 =
                                                                    'Concluído: ' . $jobFinish->format('d/m/Y H:i');
                                                                $jobSlaLine2 = 'SLA: ' . $jobDue->format('d/m/Y H:i');
                                                                $jobSlaLine3 =
                                                                    'Folga de ' .
                                                                    $jobFinish->diffForHumans($jobDue, true);
                                                            } else {
                                                                // entregue DEPOIS
                                                                $onTimePct = $pctJob($jobStart, $jobDue);
                                                                $latePct = $pctJob($jobDue, $jobFinish);

                                                                $jobSegments[] = [
                                                                    'class' => 'sla-sla-window',
                                                                    'w' => $onTimePct,
                                                                ];
                                                                $jobSegments[] = [
                                                                    'class' => 'sla-late',
                                                                    'w' => $latePct,
                                                                ];

                                                                $jobSlaStateBadge =
                                                                    '<span class="badge bg-danger text-light">entregue com atraso</span>';
                                                                $jobSlaLine1 =
                                                                    'Concluído: ' . $jobFinish->format('d/m/Y H:i');
                                                                $jobSlaLine2 = 'SLA: ' . $jobDue->format('d/m/Y H:i');
                                                                $jobSlaLine3 =
                                                                    'Atraso de ' .
                                                                    $jobDue->diffForHumans($jobFinish, true);
                                                            }
                                                        }
                                                    } else {
                                                        // sem SLA
                                                        $jobSlaStateBadge =
                                                            '<span class="badge bg-secondary">sem SLA</span>';
                                                        $jobSlaLine1 =
                                                            'Criada em ' . $job->created_at?->format('d/m/Y H:i');
                                                        $jobSlaLine2 =
                                                            'Status: ' .
                                                            strtoupper(str_replace('_', ' ', $jobStatusRaw));
                                                    }
                                                @endphp

                                                <div
                                                    class="job-box @if ($jobLateNow && !$jobFinish) border-danger border-2 @endif">

                                                    {{-- HEADER DA ATIVIDADE --}}
                                                    <div class="job-header-line">

                                                        <div class="job-left-chunk">
                                                            <span class="job-id-badge">
                                                                ATVD {{ $job->id }}
                                                            </span>

                                                            <span class="job-status-pill bg-{{ $statusColor }}">
                                                                {{ strtoupper(str_replace('_', ' ', $jobStatusRaw)) }}
                                                            </span>

                                                            <span class="job-priority-pill">
                                                                {{ $jobPriorityRaw }}
                                                            </span>

                                                            @if ($job->is_advance)
                                                                <span class="badge bg-dark text-white"
                                                                    style="font-size:.65rem;">AVANÇO</span>
                                                            @endif

                                                            @if ($job->need_evidence)
                                                                <span class="badge bg-warning text-dark"
                                                                    style="font-size:.65rem;">EVIDÊNCIA</span>
                                                            @endif
                                                        </div>

                                                        <div class="job-right-chunk">
                                                            <div class="job-owner text-end me-2">
                                                                <div class="label">Responsável</div>
                                                                <div class="value">{{ $job->owner?->name ?? '—' }}
                                                                </div>
                                                            </div>

                                                            {{-- Botões de ação do job --}}
                                                            <div class="d-flex align-items-center gap-1">

                                                                {{-- EDITAR JOB --}}
                                                                <button class="btn btn-outline-primary job-action-btn"
                                                                    title="Editar atividade"
                                                                    wire:click.prevent="$emitTo('protests.dispatch.actions.edit-control-med-protest', 'openJobEditor', {{ $job->id }})">
                                                                    <i class="ri-pencil-line"></i>
                                                                </button>

                                                                {{-- FINALIZAR --}}
                                                                <button class="btn btn-outline-success job-action-btn"
                                                                    title="Marcar como concluída"
                                                                    wire:click.prevent="$emitTo('protests.dispatch.actions.edit-control-med-protest', 'finishJob', {{ $job->id }})">
                                                                    <i class="ri-check-line"></i>
                                                                </button>

                                                                {{-- REABRIR --}}
                                                                <button class="btn btn-outline-warning job-action-btn"
                                                                    title="Reabrir atividade"
                                                                    wire:click.prevent="$emitTo('protests.dispatch.actions.edit-control-med-protest', 'reopenJob', {{ $job->id }})">
                                                                    <i class="ri-refresh-line"></i>
                                                                </button>

                                                                {{-- CANCELAR --}}
                                                                <button class="btn btn-outline-danger job-action-btn"
                                                                    title="Cancelar atividade"
                                                                    wire:click.prevent="$emitTo('protests.dispatch.actions.edit-control-med-protest', 'cancelJob', {{ $job->id }})">
                                                                    <i class="ri-close-line"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- CORPO DA ATIVIDADE --}}
                                                    <div class="job-body-grid">

                                                        {{-- BLOCO SLA --}}
                                                        <div class="job-col-block">
                                                            <div class="job-label job-sla-headline">
                                                                <span>SLA / Progresso</span>
                                                                <span
                                                                    class="job-sla-badge">{!! $jobSlaStateBadge !!}</span>
                                                            </div>

                                                            @if (!empty($jobSegments))
                                                                <div class="sla-bar-wrap-job mb-2 mt-1">
                                                                    @foreach ($jobSegments as $seg)
                                                                        <div class="sla-seg {{ $seg['class'] }}"
                                                                            style="width: {{ number_format($seg['w'], 2, '.', '') }}%;">
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            <div class="sla-info-lines">
                                                                @if ($jobSlaLine1)
                                                                    <div>{{ $jobSlaLine1 }}</div>
                                                                @endif
                                                                @if ($jobSlaLine2)
                                                                    <div>{{ $jobSlaLine2 }}</div>
                                                                @endif
                                                                @if ($jobSlaLine3)
                                                                    <div>{{ $jobSlaLine3 }}</div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- BLOCO DATAS --}}
                                                        <div class="job-col-block">
                                                            <div class="job-label">Prazo SLA</div>
                                                            <div class="job-value">
                                                                {{ $jobDueTxt }}
                                                                @if ($job->sla_breached_at && !$jobFinish)
                                                                    <span class="badge bg-danger ms-1"
                                                                        style="font-size:.65rem;">Atraso</span>
                                                                @endif
                                                            </div>

                                                            <div class="job-label mt-3">Finalizado em</div>
                                                            <div class="job-value">
                                                                {{ $jobFinishTxt }}
                                                            </div>
                                                        </div>

                                                        {{-- BLOCO NOTAS / INSTRUÇÕES --}}
                                                        @if ($job->notes)
                                                            <div class="job-col-block">
                                                                <div class="job-label">Instruções / Observações</div>
                                                                <div class="job-value" style="white-space:pre-line;">
                                                                    {{ $job->notes }}
                                                                </div>
                                                            </div>
                                                        @endif

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

                <div class="col-md-8">
                    <div class="chat-container border rounded bg-light">
                        @forelse($protest->comments->sortByDesc('created_at') as $comment)
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
    @livewire('protests.dispatch.actions.add-notes-relation', key('add-notes-relation-' . $protest->id))
    @livewire('protests.dispatch.actions.control-med-protest', key('control-med-protest-' . $protest->id))
    @livewire('protests.dispatch.actions.edit-control-med-protest', key('edit-control-med-protest-' . $protest->id))
</div>
