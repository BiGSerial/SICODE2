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
    </style>
@endpush

<div class="protest-page">
    @php
        use Illuminate\Support\Str;
        use Carbon\CarbonInterval;

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

        if (!function_exists('formatSlaDiff')) {
            function formatSlaDiff(int $seconds): string
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
    @endphp

    <x-show-loading />

    <div class="container-fluid">
        <div class="protest-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <h4 class="mb-0">Reclamações Destinadas a Você</h4>
                <small class="text-white-50">Visualização operacional de atividades em aberto</small>
            </div>
        </div>

    {{-- Filtros / topo --}}
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

        {{-- Busca geral: nota, cidade, texto do job --}}
        <div class="col-md-6">
            <div class="form-floating position-relative">
                <input wire:model.debounce.500ms="search" type="text" id="search" class="form-control"
                    placeholder="Buscar por nota, cidade ou texto do job...">
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

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center text-bg-primary">
            <h5 class="mb-0">
                <i class="ri-task-line me-2"></i>
                RECLAMAÇÕES DESTINADAS A VOCÊ
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

                                // SLA: usa sla_due_at como data limite do job
                                $slaDue = $job->sla_due_at;
                                $startRef = $job->accepted_at ?? $job->sent_at;

                                $slaProgress = null;
                                $slaClassBar = 'bg-success';
                                $slaLabel = 'Sem SLA definido';

                                if ($slaDue && $startRef) {
                                    $now = now();
                                    $totalSeconds = max($slaDue->diffInSeconds($startRef), 1);
                                    $elapsedSecondsRaw = $now->diffInSeconds($startRef, false);
                                    $elapsedSeconds = min(max($elapsedSecondsRaw, 0), $totalSeconds);
                                    $slaProgress = intval(($elapsedSeconds / $totalSeconds) * 100);

                                    $secondsToDue = $now->diffInSeconds($slaDue, false);

                                    if ($secondsToDue < 0) {
                                        $slaClassBar = 'bg-danger';
                                        $slaLabel = 'Vencido há ' . formatSlaDiff(abs($secondsToDue));
                                    } elseif ($secondsToDue <= 259200) {
                                        $slaClassBar = 'bg-warning';
                                        $slaLabel = 'Vence em ' . formatSlaDiff($secondsToDue);
                                    } else {
                                        $slaClassBar = 'bg-success';
                                        $slaLabel = 'No prazo, faltam ' . formatSlaDiff($secondsToDue);
                                    }
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

                                $canAccept = is_null($job->accepted_at);
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
                                <td>
                                    @if ($job->is_advance)
                                    <span class="badge text-bg-primary" title="Avança Parceiro">
                                        A
                                    </span>
                                        
                                    @endif
                                    @if ($job->need_evidence)
                                    <span class="badge text-bg-warning" title="Evidenciar">
                                        NE
                                    </span>
                                        
                                    @endif
                                </td>

                                {{-- Nota ref (se houver note relacionada na MedProtest ou Protest) --}}
                                <td class="text-start">
                                    @php
                                        $noteRef = null;

                                        if ($med && $med->Notes?->isNotEmpty()) {
                                            $noteRef = $med->Notes->last()->note;
                                        } elseif ($protest && $protest->Notes?->isNotEmpty()) {
                                            $noteRef = $protest->Notes->last()->note;
                                        }
                                    @endphp

                                    <span class="fw-semibold">
                                        {{ $noteRef ?? '—' }}
                                    </span>
                                </td>

                                {{-- Município --}}
                                <td class="text-start text-uppercase">
                                    {{ $protest?->cidade ?? '—' }}
                                </td>

                                {{-- SLA do Job: data + barra de progresso --}}
                                <td>
                                    @if ($slaDue)
                                        <div class="small mb-1">
                                            Limite: <strong>{{ $slaDue->format('d/m/Y H:i') }}</strong>
                                        </div>

                                        @if (!is_null($slaProgress))
                                            <div class="progress" style="height: .6rem;">
                                                <div class="progress-bar {{ $slaClassBar }}"
                                                    style="width: {{ $slaProgress }}%;"></div>
                                            </div>
                                            <div class="small text-muted mt-1">
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
                                                $messageTitle = 'Última mensagem é sua/equipe';
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
                                        @if ($canAccept)
                                            <button type="button" class="btn btn-sm btn-success"
                                                title="Aceitar e abrir"
                                                onclick="window.location.href='{{ route('protests.services.view', $job->id) }}'">
                                                <i class="ri-play-circle-line"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                title="Visualizar"
                                                onclick="window.location.href='{{ route('protests.services.view', $job->id) }}'">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4">
                    <div class="alert alert-info mb-0 text-center">
                        Nenhuma demanda destinada a você com os filtros atuais.
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

    {{-- Modal de visualização do job (mesmo usado no Monitoring) --}}

    </div>
</div>

@livewire('protests.common.messages', key('services-main-messages-modal'))
