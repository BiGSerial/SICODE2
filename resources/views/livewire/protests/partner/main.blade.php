<div>
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

    <x-show-loading />

    {{-- Filtros / topo --}}
    <div class="row g-3 mb-4 align-items-end">
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
                                    $totalSeconds = max($slaDue->diffInSeconds($startRef), 1);
                                    $elapsedSeconds = min(max(now()->diffInSeconds($startRef), 0), $totalSeconds);
                                    $slaProgress = intval(($elapsedSeconds / $totalSeconds) * 100);

                                    $daysLeft = now()->startOfDay()->diffInDays($slaDue->startOfDay(), false);

                                    if ($daysLeft < 0) {
                                        $slaClassBar = 'bg-danger';
                                        $slaLabel = 'Vencido há ' . abs($daysLeft) . ' dia(s)';
                                    } elseif ($daysLeft <= 3) {
                                        $slaClassBar = 'bg-warning';
                                        $slaLabel = 'Vence em ' . $daysLeft . ' dia(s)';
                                    } else {
                                        $slaClassBar = 'bg-success';
                                        $slaLabel = 'No prazo, faltam ' . $daysLeft . ' dia(s)';
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
                                                onclick="window.location.href='{{ route('protests.partner.view', $job->id) }}'">
                                                <i class="ri-play-circle-line"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                title="Visualizar"
                                                onclick="window.location.href='{{ route('protests.partner.view', $job->id) }}'">
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

@livewire('protests.common.messages', key('partner-main-messages-modal'))
