@push('css')
    <style>
        .protest-page {
            --pp-bg: #f6f7fb;
            --pp-surface: #ffffff;
            --pp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--pp-bg);
            padding: 1.5rem 0;
        }

        .protest-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .protest-filter-shell {
            background: var(--pp-surface);
            border: 1px solid var(--pp-border);
            border-radius: 1rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .form-floating > .form-select[multiple] {
            height: 8.5rem !important;
            padding-top: 1.9rem;
            padding-bottom: 0.6rem;
        }

        .histogram-card-body {
            display: flex;
            flex-direction: column;
        }

        .histogram-chart-wrap {
            position: relative;
            width: 100%;
            height: clamp(260px, 34vh, 420px);
            max-height: 420px;
            overflow: hidden;
        }

        .histogram-chart-wrap canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .sla-progress-on-time {
            background-color: #198754 !important;
        }

    </style>
@endpush

<div class="protest-page">
    @php
        use Illuminate\Support\Str;

        if (!function_exists('reduceName')) {
            function reduceName(string $name = null, bool $first = false): string
            {
                if (!$name) {
                    return '';
                }

                $parts = explode(' ', trim($name));

                if (count($parts) === 0) {
                    return '';
                }

                if ($first || count($parts) === 1) {
                    return $parts[0];
                }

                return $parts[0] . ' ' . end($parts);
            }
        }
    @endphp

    @php $currentUserId = auth()->id(); @endphp

    <x-show-loading />

    <div class="container-fluid">
        <div class="protest-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <h4 class="mb-0">Reclamações em Acompanhamento</h4>
                <small class="text-white-50">Visão da equipe com filtros e exportação da tela</small>
            </div>
            <button wire:click="exportToExcel" type="button" class="btn btn-light btn-sm text-dark">
                <i class="ri-file-excel-2-line me-1"></i> Exportar em tela
            </button>
        </div>

    {{-- ================= FILTROS / TOPO ================= --}}
    <div class="protest-filter-shell">
        <div class="row g-3 align-items-end">
        {{-- Registros por página --}}
        <div class="col-md-2">
            <div class="form-floating">
                <select wire:model="perPage" id="perPage" class="form-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <label for="perPage">Registros por página</label>
            </div>
        </div>

        {{-- Selecionar responsável (inclui hierarquia, delegados e delegações) --}}
                <div class="col-md-3">
                    <div class="form-floating">
                        <select wire:model="selectedUserId" id="selectedUserId" class="form-select" multiple size="4">
                            @foreach ($availableUsers as $user)
                                @php $isCurrent = $currentUserId === $user->id; @endphp
                                <option value="{{ $user->id }}">
                            {{ $isCurrent ? 'Você' : reduceName($user->name) }} ({{ $user->email }})
                            @if ($isCurrent)
                                — atual
                            @endif
                        </option>
                    @endforeach
                </select>
                <label for="selectedUserId">Responsável</label>
            </div>
        </div>

        {{-- Apenas o usuário selecionado (ignora descendentes) --}}
                <div class="col-md-2">
                    <div class="form-check mt-md-4 pt-md-2">
                        <input wire:model="onlySelectedUser" type="checkbox" id="onlySelectedUser" class="form-check-input"
                    {{ !empty($selectedUserId) ? '' : 'disabled' }}>
                <label class="form-check-label" for="onlySelectedUser">
                    Apenas selecionado
                </label>
                <small class="text-muted d-block">Sem descendência.</small>
            </div>
        </div>

        {{-- CodF --}}
        <div class="col-md-2">
            <div class="form-floating">
                <select wire:model="selectedCodf" id="selectedCodf" class="form-select" multiple size="4">
                    @foreach ($codfOptions as $codf)
                        <option value="{{ $codf }}">{{ $codf }}</option>
                    @endforeach
                </select>
                <label for="selectedCodf">CodF</label>
            </div>
        </div>

        {{-- Busca geral: nota, cidade, responsável ou texto do job --}}
        <div class="col-md-3">
            <div class="form-floating position-relative">
                <input wire:model.debounce.500ms="search" type="text" id="search" class="form-control"
                    placeholder="Buscar por nota, cidade, responsável ou texto do job...">
                <label for="search">Buscar</label>
            </div>
        </div>

        {{-- Limpar --}}
        <div class="col-md-2 d-flex align-items-end">
            <button wire:click="clearFilters" type="button" class="btn btn-outline-secondary w-100">
                <i class="ri-eraser-line me-1"></i> Limpar
            </button>
        </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body histogram-card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Histograma de Previsões Mensais</div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" wire:model="histogramSource" style="min-width: 190px;">
                        <option value="desired">Data desejada</option>
                        <option value="sla">Data SLA do job</option>
                    </select>
                    <select class="form-select form-select-sm" wire:model="histogramYear" style="min-width: 110px;">
                        @forelse (($histogramData['years'] ?? []) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @empty
                            <option value="{{ now()->year }}">{{ now()->year }}</option>
                        @endforelse
                    </select>
                    @if (!empty($histogramData['selectedMonth']))
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearHistogramFilter">
                            Limpar mês
                        </button>
                    @endif
                </div>
            </div>
            <div id="accompany-histogram-data" data-payload='@json($histogramData)'></div>
            <div class="histogram-chart-wrap" wire:ignore>
                <canvas id="accompanyHistogram"></canvas>
            </div>
            @php
                $selectedMonth = (int) ($histogramData['selectedMonth'] ?? 0);
                $series = (array) ($histogramData['series'] ?? []);
                $overdue = array_values((array) ($series['overdue'] ?? []));
                $dueSoon = array_values((array) ($series['dueSoon'] ?? []));
                $within = array_values((array) ($series['within'] ?? []));
                $monthLabels = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
            @endphp
            <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                @foreach ($monthLabels as $monthNumber => $monthLabel)
                    @php
                        $index = $monthNumber - 1;
                        $monthTotal = (int) ($overdue[$index] ?? 0) + (int) ($dueSoon[$index] ?? 0) + (int) ($within[$index] ?? 0);
                        $isActive = $selectedMonth === $monthNumber;
                    @endphp
                    <button type="button" class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                        @disabled($monthTotal <= 0)
                        wire:click="setHistogramBucket({{ $monthNumber }})">
                        {{ $monthLabel }}
                    </button>
                @endforeach
            </div>
            <small class="text-muted">Clique em uma barra para filtrar a lista por mês/ano.</small>
        </div>
    </div>

    {{-- Info de paginação topo --}}
    @if ($list->count() > 0)
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted">
                <i class="ri-information-line"></i>
                Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
            </div>
            <div>
                {{ $list->links() }}
            </div>
        </div>
    @endif

    {{-- ================= LISTA PRINCIPAL ================= --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center text-bg-primary">
            <h5 class="mb-0">
                <i class="ri-team-line me-2"></i>
                RECLAMAÇÕES EM ACOMPANHAMENTO DA SUA EQUIPE
            </h5>
        </div>

        <div class="table-responsive">
            @if ($list->count() > 0)
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th style="width: 110px;">Prioridade</th>
                            <th style="width: 120px;">Reclamação</th>
                            <th style="width: 70px;">Tipo</th>
                            <th style="width: 70px;"></th>
                            <th style="width: 160px;">Nota ref.</th>
                            <th style="width: 180px;">Município</th>
                            <th style="width: 180px;">Responsável</th>
                            <th style="width: 120px;">Dias c/ usuário</th>
                            <th style="width: 220px;">SLA do Job</th>
                            <th style="width: 260px;">Descrição do Job</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 60px;">
                                <i class="ri-message-3-line" title="Mensagens"></i>
                            </th>
                            <th style="width: 80px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($list as $job)
                            @php
                                $protest = $job->protest;
                                $med = $job->medProtest;

                                // SLA: usa sent_at/accepted_at como início e sla_due_at como limite
                                $slaDue = $job->sla_due_at;
                                $startRef = $job->accepted_at ?? ($job->sent_at ?? $job->created_at);
                                $finishedAt = $job->finished_at;

                                $slaProgressGreen = null;
                                $slaProgressRed = 0;
                                $slaLabel = 'Sem SLA definido';
                                $slaLabelClass = 'text-muted';

                                if ($slaDue && $startRef) {
                                    $windowSeconds = max($startRef->diffInSeconds($slaDue, false), 1);
                                    $lateSeconds = 0;

                                    if ($finishedAt) {
                                        $effectiveEnd = $finishedAt;
                                        $totalSeconds = max($startRef->diffInSeconds($effectiveEnd, false), 1);
                                        $onTimeEnd = $finishedAt->lessThan($slaDue) ? $finishedAt : $slaDue;
                                        $onTimeSeconds = max($startRef->diffInSeconds($onTimeEnd, false), 0);
                                        $lateSeconds = max($slaDue->diffInSeconds($finishedAt, false), 0);

                                        if ($finishedAt->lessThanOrEqualTo($slaDue)) {
                                            // Finalizado no prazo: escala até o SLA, mantendo o trecho faltante visível.
                                            $slaProgressGreen = round(($onTimeSeconds / $windowSeconds) * 100, 2);
                                            $slaProgressRed = 0;
                                        } else {
                                            // Finalizado fora do prazo: escala total até o finished_at (verde + vermelho = 100%).
                                            $slaProgressGreen = round(($onTimeSeconds / $totalSeconds) * 100, 2);
                                            $slaProgressRed = round(($lateSeconds / $totalSeconds) * 100, 2);
                                        }
                                    } else {
                                        $greenEnd = now()->min($slaDue);
                                        $greenSeconds = max(min($startRef->diffInSeconds($greenEnd, false), $windowSeconds), 0);
                                        $lateSeconds = max($slaDue->diffInSeconds(now(), false), 0);
                                        $scaleSeconds = max($windowSeconds + $lateSeconds, 1);

                                        $slaProgressGreen = round(($greenSeconds / $scaleSeconds) * 100, 2);
                                        $slaProgressRed = round(($lateSeconds / $scaleSeconds) * 100, 2);
                                    }

                                    if ($slaProgressRed > 0 && $slaProgressRed < 1) {
                                        $slaProgressRed = 1;
                                        $slaProgressGreen = max(0, round(100 - $slaProgressRed, 2));
                                    }

                                    if ($finishedAt) {
                                        $daysAtFinish = $finishedAt->startOfDay()->diffInDays($slaDue->startOfDay(), false);
                                        if ($daysAtFinish < 0) {
                                            $slaLabel = 'Finalizado em ' . $finishedAt->format('d/m/Y H:i');
                                            $slaLabelClass = 'text-danger';
                                        } else {
                                            $slaLabel = 'Finalizado em ' . $finishedAt->format('d/m/Y H:i');
                                            $slaLabelClass = 'text-success';
                                        }
                                    } else {
                                        $daysLeft = now()->startOfDay()->diffInDays($slaDue->startOfDay(), false);
                                        if ($daysLeft < 0) {
                                            $slaLabel = 'Vencido há ' . abs($daysLeft) . ' dia(s)';
                                        } elseif ($daysLeft <= 3) {
                                            $slaLabel = 'Vence em ' . $daysLeft . ' dia(s)';
                                        } else {
                                            $slaLabel = 'No prazo, faltam ' . $daysLeft . ' dia(s)';
                                        }
                                    }
                                }

                                // Dias com o usuário (responsável)
                                $referenceDate = $job->sent_at ?? $job->created_at;
                                $daysWithOwner = null;

                                if ($referenceDate) {
                                    $daysWithOwner = $referenceDate
                                        ->copy()
                                        ->startOfDay()
                                        ->diffInDays(now()->startOfDay());
                                }

                                // Estado de mensagens (usa mesma lógica do monitoring)
                                $currentUserId = auth()->id();
                                $creatorId = $job->created_by ?? ($job->creator_id ?? optional($job->creator)->id);
                                $lastComment = $med?->Comments?->first();
                                $hasMessage = false;
                                $pendingForYou = false;

                                if ($creatorId && $lastComment) {
                                    $authorId = $lastComment->user_id;

                                    if ($authorId) {
                                        $isFromDispatcher = $authorId === $creatorId;
                                        $isFromCurrentUser = $currentUserId && $authorId === $currentUserId;

                                        $hasMessage = !$isFromDispatcher;
                                        $pendingForYou = $hasMessage && !$isFromCurrentUser;
                                    }
                                }

                                // Referência de nota
                                $noteRef = null;
                                if ($med && $med->Notes?->isNotEmpty()) {
                                    $noteRef = $med->Notes->last()->note;
                                } elseif ($protest && $protest->Notes?->isNotEmpty()) {
                                    $noteRef = $protest->Notes->last()->note;
                                }
                            @endphp

                            <tr class="text-center">
                                {{-- Prioridade --}}
                                <td>
                                    <span class="badge {{ $job->priority_badge_class }}">
                                        {{ $job->priority_label }}
                                    </span>
                                </td>

                                {{-- Número Reclamação --}}
                                <td class="fw-bold">
                                    {{ $protest?->nota ?? '—' }}
                                </td>

                                {{-- Tipo --}}
                                <td>
                                    <span class="badge text-bg-secondary">
                                        {{ $protest?->tipoNota ?? '—' }}
                                    </span>
                                </td>

                                {{-- Flags (avanço / necessidade evidência) --}}
                                <td>
                                    @if ($job->is_advance)
                                        <span class="badge text-bg-primary" title="Avança Parceiro">
                                            A
                                        </span>
                                    @endif

                                    @if ($job->need_evidence)
                                        <span class="badge text-bg-warning" title="Requer evidência">
                                            NE
                                        </span>
                                    @endif
                                </td>

                                {{-- Nota ref --}}
                                <td class="text-start">
                                    <span class="fw-semibold">
                                        {{ $noteRef ?? '—' }}
                                    </span>
                                </td>

                                {{-- Município --}}
                                <td class="text-start text-uppercase">
                                    {{ $protest?->cidade ?? '—' }}
                                </td>

                                {{-- Responsável (owner) --}}
                                <td class="text-start">
                                    {{ reduceName($job->owner?->name) ?: '—' }}
                                </td>

                                {{-- Dias com o usuário --}}
                                <td>
                                    @if (!is_null($daysWithOwner))
                                        <span class="badge text-bg-secondary"
                                            title="Desde {{ $referenceDate?->format('d/m/Y H:i') }}">
                                            {{ $daysWithOwner }} dia{{ $daysWithOwner == 1 ? '' : 's' }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- SLA do Job --}}
                                <td>
                                    @if ($job->sla_due_at)
                                        <div class="small mb-1">
                                            Limite: <strong>{{ $job->sla_due_at->format('d/m/Y H:i') }}</strong>
                                        </div>

                                        @if (!is_null($slaProgressGreen))
                                            <div class="progress" style="height: .6rem;">
                                                @if ($slaProgressGreen > 0)
                                                    <div class="progress-bar sla-progress-on-time"
                                                        style="width: {{ $slaProgressGreen }}%;"></div>
                                                @endif
                                                @if ($slaProgressRed > 0)
                                                    <div class="progress-bar bg-danger"
                                                        style="width: {{ $slaProgressRed }}%;"></div>
                                                @endif
                                            </div>
                                            <div class="small mt-1 {{ $slaLabelClass }}">
                                                {{ $slaLabel }}
                                            </div>
                                        @else
                                            <span class="badge text-bg-secondary">Sem referência de início</span>
                                        @endif
                                    @else
                                        <span class="badge text-bg-secondary">Sem SLA</span>
                                    @endif
                                </td>

                                {{-- Descrição do Job (notes) --}}
                                <td class="text-start">
                                    @if ($job->notes)
                                        <span title="{{ $job->notes }}">
                                            {{ Str::limit($job->notes, 80) }}
                                        </span>
                                    @else
                                        <span class="text-muted">Sem descrição definida.</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="badge {{ $job->status_badge_class }}">
                                        {{ $job->status_label }}
                                    </span>
                                </td>

                                {{-- Ícone de comentário --}}
                                <td>
                                    @if ($med?->id)
                                        @php
                                            $messageTitle = 'Abrir mensagens da Medida';

                                            if ($pendingForYou) {
                                                $messageTitle =
                                                    'Última mensagem é de outro usuário, aguardando sua resposta';
                                            } elseif ($hasMessage) {
                                                $messageTitle = 'Última mensagem é da sua equipe';
                                            }
                                        @endphp

                                        <button type="button"
                                            class="btn btn-link p-0 border-0 text-decoration-none align-middle"
                                            title="{{ $messageTitle }}"
                                            wire:click="$emitTo('protests.common.messages', 'openMessagesModal', {{ $med->id }})">
                                            @if ($pendingForYou)
                                                <i class="ri-message-3-fill text-info"></i>
                                            @elseif ($hasMessage)
                                                <i class="ri-message-2-line text-muted"></i>
                                            @else
                                                <i class="ri-chat-1-line text-muted"></i>
                                            @endif
                                        </button>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Ações --}}
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            title="Visualizar"
                                            onclick="window.location.href='{{ route('protests.services.view_controller', $job->id) }}'">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4">
                    <div class="alert alert-info mb-0 text-center">
                        Nenhuma demanda em acompanhamento para sua equipe com os filtros atuais.
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Paginação rodapé --}}
    @if ($list->count() > 0)
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="small text-muted">
                <i class="ri-information-line"></i>
                Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
            </div>
            <div>
                {{ $list->links() }}
            </div>
        </div>
    @endif
    </div>
</div>

@livewire('protests.common.messages', key('services-accompany-messages-modal'))

@push('scripts')
    <script>
        document.addEventListener('livewire:load', () => {
            let accompanyHistogramChart = null;
            let lastSignature = null;

            const buildAccompanyHistogram = () => {
                const canvas = document.getElementById('accompanyHistogram');
                const payloadNode = document.getElementById('accompany-histogram-data');

                if (!canvas || !payloadNode || typeof Chart === 'undefined') {
                    return;
                }

                const raw = payloadNode.dataset.payload || '{}';
                if (accompanyHistogramChart && lastSignature === raw) {
                    return;
                }

                let payload;
                try {
                    payload = JSON.parse(raw);
                } catch (e) {
                    return;
                }

                const labels = payload.labels || [];
                const series = payload.series || {};
                const overdueData = series.overdue || [];
                const dueSoonData = series.dueSoon || [];
                const withinData = series.within || [];
                const selectedMonth = payload.selectedMonth ? Number(payload.selectedMonth) : null;
                const sourceLabel = payload.source === 'sla' ? 'SLA do job' : 'Data desejada';
                const filterBySelectedMonth = (data) => selectedMonth
                    ? labels.map((_, i) => ((i + 1) === selectedMonth ? Number(data[i] ?? 0) : 0))
                    : data;
                const displayOverdueData = filterBySelectedMonth(overdueData);
                const displayDueSoonData = filterBySelectedMonth(dueSoonData);
                const displayWithinData = filterBySelectedMonth(withinData);

                const colorize = (base) => labels.map((_, i) => selectedMonth === (i + 1) ? base.replace('0.8', '1') : base);
                const border = labels.map((_, i) => selectedMonth === (i + 1) ? 'rgba(15,23,42,1)' : 'rgba(15,23,42,.45)');

                if (accompanyHistogramChart) {
                    accompanyHistogramChart.destroy();
                }

                accompanyHistogramChart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: `Vencidos - ${sourceLabel} (${payload.selectedYear ?? ''})`,
                            data: displayOverdueData,
                            backgroundColor: colorize('rgba(220,53,69,0.8)'),
                            borderColor: border,
                            borderWidth: 1,
                            borderRadius: 6,
                            stack: 'prazo',
                        }, {
                            label: `Vencendo - ${sourceLabel} (${payload.selectedYear ?? ''})`,
                            data: displayDueSoonData,
                            backgroundColor: colorize('rgba(255,193,7,0.8)'),
                            borderColor: border,
                            borderWidth: 1,
                            borderRadius: 6,
                            stack: 'prazo',
                        }, {
                            label: `A vencer - ${sourceLabel} (${payload.selectedYear ?? ''})`,
                            data: displayWithinData,
                            backgroundColor: colorize('rgba(25,135,84,0.8)'),
                            borderColor: border,
                            borderWidth: 1,
                            borderRadius: 6,
                            stack: 'prazo',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            },
                        },
                        onClick: (evt, elements) => {
                            if (!elements.length) return;
                            const month = elements[0].index + 1;
                            const root = canvas.closest('[wire\\:id]');
                            if (!root) return;
                            const componentId = root.getAttribute('wire:id');
                            if (!componentId) return;
                            Livewire.find(componentId).call('setHistogramBucket', month);
                        },
                    },
                });

                lastSignature = raw;
            };

            buildAccompanyHistogram();
            Livewire.hook('message.processed', () => buildAccompanyHistogram());
        });
    </script>
@endpush
