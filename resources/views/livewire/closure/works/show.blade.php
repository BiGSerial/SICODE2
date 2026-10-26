@php
    use App\Enum\ClosureOperationalStatus;
    use App\Services\Closure\ClosureMetaClassifier;

    $note = $overview['note'];
    $issueStatusLabels = [
        'OPEN' => 'Aberta', 'REQUESTED' => 'Aguardando resposta', 'RESPONDED' => 'Respondida',
        'VALIDATING' => 'Em validação', 'RETURNED' => 'Devolvida p/ correção', 'RESOLVED' => 'Resolvida',
    ];
    $eventLabels = [
        'RELEASED_BY_OPERATOR' => 'Desbloqueada pelo operador', 'REQUESTED' => 'Enviada à parceira', 'RESPONDED' => 'Parceira respondeu', 'VALIDATING' => 'Em validação',
        'RETURNED_FOR_CORRECTION' => 'Devolvida para correção', 'RESOLVED' => 'Resolvida', 'OPENED' => 'Aberta',
    ];
    $roleLabels = ['DISPATCHER' => 'Despachante', 'INTERNAL' => 'Encerramento', 'PARTNER' => 'Parceira'];
@endphp
<div class="closure-dash work-page">
    @include('livewire.closure._styles')

    @if (!empty($observing))
        <div class="inbox-note mb-3 flex-wrap" role="status">
            <i class="ri-eye-line" aria-hidden="true"></i>
            <span><strong>Modo observador:</strong> você está vendo esta obra exatamente como a parceira vê. Controles internos e dados de outras empresas não aparecem.</span>
            @if (count($observing) > 1)
                <label class="ms-auto d-flex align-items-center gap-2 mb-0 small fw-bold" for="observe-company">Observar como
                    <select id="observe-company" class="form-select form-select-sm" wire:model="observeCompanyId">
                        @foreach ($observing as $companyId => $companyName)
                            <option value="{{ $companyId }}" @selected($companyId === $observedId)>{{ $companyName }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <span class="ms-auto fw-bold">{{ reset($observing) }}</span>
            @endif
        </div>
    @endif

    @php
        $finishedStatuses = ['FINISHED', 'WAITING', 'CLOSED'];
        // Concluída = finalizada/encerrada no Encerramento OU já encerrada no SAP (ENTE/ENCE); canceladas ficam fora da conta.
        $sapClosedOf      = fn ($r) => str_starts_with((string) $r['status_sist'], 'ENTE') || str_starts_with((string) $r['status_sist'], 'ENCE');
        $countable        = $rows->reject(fn ($r) => $r['canceled']);
        $totalOrders      = $countable->count();
        $doneOrders       = $countable->filter(fn ($r) => $sapClosedOf($r) || in_array($r['operational_status'] ?? '', $finishedStatuses, true))->count();
        $pct              = $totalOrders ? (int) round($doneOrders / $totalOrders * 100) : 0;
        $statusChips      = $countable->groupBy(fn ($r) => $sapClosedOf($r) ? 'SAP_CLOSED' : ($r['has_state'] ? $r['operational_status'] : 'NOT_DISPATCHED'))->map->count();
        $metaItems        = [
            ['ri-map-pin-2-line', $note['city'] ?: null],
            ['ri-user-3-line', $note['client'] ?: null],
            ['ri-money-dollar-circle-line', $note['value'] !== null ? ($note['currency'] ?: 'R$') . ' ' . number_format((float) $note['value'], 2, ',', '.') : null],
        ];
        $tabs = [['overview', 'ri-dashboard-line', 'Visão geral', null], ['ri', 'ri-draft-line', 'Retorno Interno', $dossierCounts['ri'] ?? 0], ['informs', 'ri-file-list-3-line', 'Informes', $dossierCounts['informs'] ?? 0], ['files', 'ri-folder-2-line', 'Arquivos', $dossierCounts['files'] ?? 0]];
    @endphp
    <section class="work-hero mb-3">
        <div class="work-hero-main">
            <div class="work-id">
                <span class="work-id-icon" aria-hidden="true"><i class="ri-building-4-line"></i></span>
                <div class="min-w-0">
                    <div class="work-eyebrow">Gestão da obra</div>
                    <h2 class="work-title">Obra {{ $note['number'] }} @if ($note['type'])<span class="work-type">{{ $note['type'] }}</span>@endif @if ($overview['has_priority'] && $internal)<span class="work-flag"><i class="ri-flag-2-fill"></i> Prioridade</span>@endif</h2>
                    <div class="work-meta">
                        @foreach ($metaItems as [$icon, $text])
                            @if ($text)<span><i class="{{ $icon }}"></i> {{ $text }}</span>@endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="work-actions">
                @if ($internal)
                    <button type="button" class="btn btn-light btn-sm fw-semibold" wire:click="openPartnerModal"><i class="ri-send-plane-2-line"></i> Enviar à parceira</button>
                @endif
                @if ($internal && $activeRi)
                    <span class="work-lock" title="Enviado em {{ $activeRi->sent_at?->format('d/m/Y H:i') }}"><i class="ri-lock-line"></i> RI com o Desenho</span>
                @elseif ($internal && $canSendRi)
                    <button type="button" class="btn btn-outline-light btn-sm fw-semibold" wire:click="$emit('closureRiOpen', {{ $noteId }})"><i class="ri-draft-line"></i> Enviar RI ao Desenho</button>
                @endif
                @if ($internal && $canFinishAny)
                    <button type="button" class="btn btn-success btn-sm fw-semibold" wire:click="askFinish('all')"><i class="ri-checkbox-circle-line"></i> Finalizar obra</button>
                @endif
                <button type="button" class="work-icon-btn" wire:click="$refresh" title="Atualizar" aria-label="Atualizar"><i class="ri-refresh-line" wire:loading.class="ri-spin" wire:target="$refresh"></i></button>
            </div>
        </div>

        <div class="work-progress">
            <div class="work-progress-head">
                <span><strong>{{ $doneOrders }}</strong> de {{ $totalOrders }} Ordem(ns) concluída(s)</span>
                <span class="work-progress-pct">{{ $pct }}%</span>
            </div>
            <div class="work-progress-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $pct }}%"></span></div>
            <div class="work-chips">
                @foreach ($typeSummary as $letter => $count)
                    <span class="work-chip" title="{{ ClosureMetaClassifier::label($letter) }}"><b>{{ $letter }}</b> {{ $count }}</span>
                @endforeach
                @foreach ($statusChips as $statusKey => $count)
                    <span class="work-chip is-status">{{ ['NOT_DISPATCHED' => 'Não despachada', 'SAP_CLOSED' => 'Encerrada no SAP'][$statusKey] ?? ClosureOperationalStatus::labelFor($statusKey) }} · {{ $count }}</span>
                @endforeach
            </div>
        </div>

        @if ($internal && $activeRi && $riStage)
            <div class="work-banner" role="status">
                <i class="ri-lock-line" aria-hidden="true"></i>
                <div class="flex-grow-1"><strong>Obra travada por RI com o Desenho</strong> · {{ $riStage['label'] }}<span class="d-none d-md-inline"> — {{ $riStage['hint'] }} Enviado em {{ $activeRi->sent_at?->format('d/m/Y H:i') }}.</span></div>
                <button type="button" class="btn btn-sm btn-light" wire:click="setTab('ri')">Acompanhar</button>
            </div>
        @endif

        @if ($internal)
            <nav class="work-tabs" role="tablist" aria-label="Dossiê da obra">
                @foreach ($tabs as [$key, $icon, $label, $count])
                    <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" class="work-tab @if ($tab === $key) is-active @endif" wire:click="setTab('{{ $key }}')" wire:key="work-tab-{{ $key }}">
                        <i class="{{ $icon }}"></i><span>{{ $label }}</span>
                        @if ($count !== null)<em>{{ $count }}</em>@endif
                    </button>
                @endforeach
            </nav>
        @endif
    </section>

    @if ($feedback)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert" id="closure-work-feedback">{{ $feedback }}</div>
    @endif


    @if ($internal && $tab === 'ri')
        @include('livewire.closure.works._ri')
    @elseif ($internal && $tab === 'informs')
        @include('livewire.closure.works._informs')
    @elseif ($internal && $tab === 'files')
        @include('livewire.closure.works._files')
    @endif

    @if ($tab === 'overview')
    {{-- Ordens --}}
    <div class="table-card mb-3">
        <div class="od-section-head">
            <div>
                <h3><i class="ri-list-check-2 text-muted"></i> Ordens da obra</h3>
                <div class="small text-muted mt-1">{{ count($rows) }} Ordem(ns) · {{ count($overview['open_issues']) }} pendência(s) aberta(s)</div>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if ($internal)
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="show-canceled" wire:model="showCanceled">
                        <label class="form-check-label small" for="show-canceled">Mostrar canceladas</label>
                    </div>
                @endif
            </div>
        </div>

        @if ($pendingFinish !== null)
            <div class="alert alert-warning mx-3 d-flex justify-content-between align-items-center" role="alert">
                <span>Confirmar a finalização {{ $pendingFinish === 'all' ? 'de todas as Ordens finalizáveis sob sua responsabilidade' : 'da Ordem selecionada' }}? Ordens de meta futura (F) podem ser recusadas.</span>
                <span class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success" wire:click="confirmFinish">Confirmar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelFinish">Cancelar</button>
                </span>
            </div>
        @endif

        @if ($finishReceipt)
            <div class="m-3" id="closure-finish-receipt">
                <strong>Recibo da finalização</strong>
                <ul class="mb-0">
                    @foreach ($finishReceipt as $r)
                        <li class="{{ $r['ok'] ? 'text-success' : 'text-danger' }}">Ordem {{ $r['ordem'] }}: {{ $r['ok'] ? 'finalizada (' . $r['message'] . ')' : 'recusada — ' . $r['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
            $chipColors = ['ASSIGNED' => ['#e8f0ff', '#263cc8'], 'IN_PROGRESS' => ['#dbeafe', '#1d4ed8'], 'PAUSED' => ['#f1f5f9', '#475569'], 'WAITING_PARTNER' => ['#fef3c7', '#b45309'], 'DEPOSIT_BLOCKED' => ['#fee2e2', '#b91c1c'], 'DEPOSIT_RELEASED' => ['#dcfce7', '#15803d'], 'FINISHED' => ['#dcfce7', '#15803d'], 'WAITING' => ['#ede9fe', '#6d28d9'], 'AVAILABLE' => ['#f1f5f9', '#475569'], 'CLOSED' => ['#dcfce7', '#15803d'], 'CANCELLED' => ['#f1f5f9', '#475569']];
        @endphp
        <div class="table-responsive dash-scroll">
            <table class="table table-sm align-middle mb-0 dash-table" style="min-width: 980px;">
                <thead>
                    <tr>
                        <th class="ps-3 text-center">Tipo</th>
                        <th>Compet.</th>
                        <th>Ordem</th>
                        <th>Status SAP</th>
                        <th>Atividade</th>
                        @if ($internal)
                            <th>Responsável</th>
                            <th>Prioridade</th>
                        @endif
                        <th class="text-center">Operações</th>
                        <th class="text-center">Pendências</th>
                        @if ($internal)
                            <th class="pe-3 text-end">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $sap    = collect(preg_split('/\s+/', trim((string) $row['status_sist']), -1, PREG_SPLIT_NO_EMPTY))->take(2)->implode(' ');
                            $colors = $chipColors[$row['operational_status'] ?? ''] ?? ['#f1f5f9', '#475569'];
                            $holder = $row['assignee']['name'] ?? null;
                            $sapClosed = str_starts_with((string) $row['status_sist'], 'ENTE') || str_starts_with((string) $row['status_sist'], 'ENCE');
                            $apt       = !$row['canceled'] && !$sapClosed && isset($eligibleSet[$row['order_id']]);
                            $live      = $row['has_state'] && !$row['canceled'] && !$sapClosed && !in_array($row['operational_status'], ['CLOSED', 'CANCELLED'], true);
                            $blockId   = $blockingByState[$row['state_id'] ?? 0] ?? null;
                            $canDispatchRow = !$row['has_state'] && $canDispatch && $apt;
                        @endphp
                        <tr wire:key="work-order-{{ $row['order_id'] }}" @class(['text-muted' => $row['canceled']])>
                            <td class="ps-3 text-center"><span class="type-dot badge {{ ClosureMetaClassifier::badgeClass($row['type']) }}" title="{{ ClosureMetaClassifier::label($row['type']) }}">{{ ClosureMetaClassifier::letter($row['type']) ?? '—' }}</span></td>
                            <td class="small text-nowrap">{{ ClosureMetaClassifier::competenceLabel($row['competence']) }}</td>
                            <td>
                                <div class="ord-main">{{ $row['ordem'] }} @if ($row['canceled']) <span class="badge bg-dark">Cancelada</span> @endif @if ($internal && auth()->user()->can('closure.view'))<a href="{{ route('closure.order.detail', ['order' => $row['order_id'], 'ctx' => $ctx]) }}" class="small fw-normal ms-1" title="Abrir o detalhe da Ordem">detalhe <i class="ri-external-link-line"></i></a>@endif</div>
                                @if ($row['description'])
                                    <div class="ord-sub text-truncate" style="max-width: 320px;" title="{{ $row['description'] }}">{{ $row['description'] }}</div>
                                @endif
                            </td>
                            <td><span class="sap-chip" title="{{ $row['status_sist'] }}">{{ $sap ?: '—' }}</span></td>
                            <td>
                                @if ($row['has_state'])
                                    <span class="st-chip" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">{{ ClosureOperationalStatus::labelFor($row['operational_status']) }}</span>
                                    @if ($sig = ($depositSignals[$row['state_id']] ?? null))
                                        <div class="mt-1"><span class="st-chip" style="background:#fee2e2;color:#b91c1c;" title="Depósito {{ $sig->deposit_code }}{{ $sig->deposit_label ? ' · ' . $sig->deposit_label : '' }} bloqueado desde {{ $sig->created_at?->format('d/m/Y') }}{{ $sig->origin === 'CALENDAR' ? ' (inventário)' : '' }}. Volta ao status anterior quando o depósito for liberado."><i class="ri-lock-line"></i> Depósito {{ $sig->deposit_code }}</span></div>
                                    @endif
                                @else
                                    @if ($sapClosed)
                                        <span class="st-chip" style="background:#e8f0ff;color:#263cc8;" title="A Ordem já está encerrada no SAP: não há o que encerrar.">Encerrada no SAP</span>
                                    @elseif ($row['canceled'])
                                        <span class="st-chip" style="background:#f1f5f9;color:#475569;">Cancelada</span>
                                    @elseif (!$apt)
                                        <span class="st-chip" style="background:#f1f5f9;color:#64748b;" title="Pertence à obra, mas ainda não está apta para encerramento (sem a operação 0020 confirmada).">Não apta p/ encerramento</span>
                                    @else
                                        <span class="st-chip" style="background: #fef3c7; color: #b45309;">Aguardando despacho</span>
                                    @endif
                                @endif
                            </td>
                            @if ($internal)
                                <td>
                                    @if ($holder || ($row['holder_company'] ?? null))
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar-sm" aria-hidden="true">{{ $holder ? $initials($holder) : '—' }}</span>
                                            <div class="lh-sm" style="min-width:0;">
                                                <div class="fw-semibold small text-truncate">{{ $holder ?? 'Sem responsável' }}</div>
                                                <div class="ord-sub text-truncate">{{ $row['holder_company'] ?? '—' }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>@if ($row['priority'])<span class="st-chip" style="background:#fee2e2;color:#b91c1c;">Prioritária</span>@else<span class="text-muted">—</span>@endif</td>
                            @endif
                            <td class="text-center" style="min-width: 96px;">
                                <span class="ops-btn d-inline-block @if ($row['ops_total'] && $row['ops_confirmed'] === $row['ops_total']) is-done @endif" style="cursor: default;">{{ $row['ops_confirmed'] }}/{{ $row['ops_total'] }}</span>
                                <div class="ops-meter" aria-hidden="true"><span style="width: {{ $row['ops_total'] ? (int) round($row['ops_confirmed'] / $row['ops_total'] * 100) : 0 }}%"></span></div>
                            </td>
                            <td class="text-center">@if ($row['open_issues'])<span class="badge bg-warning text-dark">{{ $row['open_issues'] }}</span>@else<span class="text-muted">0</span>@endif</td>
                            @if ($internal)
                                <td class="pe-3 text-end cell-visible">
                                    @if ($canDispatchRow || $live)
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-closure-fixed-dropdown aria-expanded="false" aria-label="Ações da Ordem {{ $row['ordem'] }}">Ações</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($canDispatchRow)
                                                <li><button type="button" class="dropdown-item" onclick="window.dispatchEvent(new CustomEvent('closure-dispatch-open', { detail: { rows: [{ id: {{ $row['order_id'] }}, ordem: @js($row['ordem']), note: @js($note['number']), type: @js($row['type']), comp: @js(ClosureMetaClassifier::competenceLabel($row['competence'])) }] } }))"><i class="ri-send-plane-line me-2"></i>Despachar</button></li>
                                            @endif
                                            @if ($live)
                                                @if ($blockId)
                                                    <li><button type="button" class="dropdown-item fw-semibold" wire:click="startIssueAction({{ $blockId }}, 'release')"><i class="ri-lock-unlock-line me-2"></i>Desbloquear sem retorno</button></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                @if ($row['manage'])
                                                    <li><button type="button" class="dropdown-item" wire:click="selectStateForStatus({{ $row['state_id'] }})"><i class="ri-refresh-line me-2"></i>Alterar status</button></li>
                                                @endif
                                                @if ($row['finish'])
                                                    <li><button type="button" class="dropdown-item" wire:click="askFinish('{{ $row['state_id'] }}')"><i class="ri-checkbox-circle-line me-2 text-success"></i>Finalizar</button></li>
                                                @endif
                                                <li><button type="button" class="dropdown-item" wire:click="openPartnerModal({{ $row['state_id'] }})"><i class="ri-send-plane-2-line me-2"></i>Enviar à parceira</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                    @else
                                        <span class="text-muted small" title="Sem ação de encerramento para esta Ordem">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Nenhuma Ordem {{ $internal ? 'encontrada' : 'disponível para você' }} nesta obra.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endif

    @if ($canDispatch)
        @livewire('closure.dispatch.dispatch-modal')
        <div x-data x-on:hidden.bs.modal.window="if ($event.target.id === 'closure_dispatch_modal') { $wire.$refresh() }"></div>
    @endif

    {{-- Alterar status --}}
    @if ($internal && $statusState)
        <div class="cpop-overlay" wire:click.self="cancelStatusChange" x-data @keydown.escape.window="$wire.cancelStatusChange()">
            <div class="cpop" style="max-width: 520px; --tone: #b45309;" role="dialog" aria-modal="true" aria-label="Alterar status" id="closure-status-area">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-refresh-line"></i></span>
                    <div>
                        <h3 class="cpop-title">Alterar status da atividade</h3>
                        <div class="cpop-sub">Ordem {{ $statusState->Order?->ordem }} · atual: {{ ClosureOperationalStatus::labelFor($statusState->operational_status) }}</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelStatusChange"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="mb-3">
                        <label class="cpop-label">Novo status</label>
                        <select class="form-select" wire:model="statusTarget" @disabled($statusConfirming)>
                            <option value="">Selecione…</option>
                            @foreach ($this->targetsFor($statusState) as $target)
                                <option value="{{ $target }}">{{ ClosureOperationalStatus::labelFor($target) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="cpop-label">Motivo @if ($statusTarget !== '' && $this->reasonRequired($statusState, $statusTarget)) <span class="text-danger">*</span> @endif</label>
                    <textarea class="form-control @error('statusReason') is-invalid @enderror" rows="3" wire:model.defer="statusReason" maxlength="500" @disabled($statusConfirming) placeholder="Descreva o motivo de forma objetiva."></textarea>
                    @error('statusReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($statusConfirming)
                        <div class="cpop-hint mt-3">Confira os dados e confirme a alteração.</div>
                    @endif
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelStatusChange">Cancelar</button>
                    @if ($statusConfirming)
                        <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background: #b45309;" wire:click="confirmStatusChange">Confirmar alteração</button>
                    @else
                        <button type="button" class="btn btn-primary btn-sm px-3" wire:click="prepareStatusChange">Alterar</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($tab === 'overview')
    <div class="row g-3 mb-3">
        {{-- Chat --}}
        <div class="col-12 col-xl-7">
            <div class="chart-card h-100">
                <div class="od-section-head border-bottom">
                    <h3><i class="ri-chat-3-line text-muted"></i> Conversa da obra</h3>
                    <div class="small text-muted text-end">{{ $internal ? 'Interno = só equipe do Encerramento; Com parceira = visível à empresa.' : 'Conversa com a equipe de Encerramento.' }}</div>
                </div>
                <div class="px-3" wire:poll.15s style="max-height: 420px; overflow-y: auto;" id="closure-chat">
                    @forelse ($messages as $m)
                        <div class="chat-msg @if ($m->author_id === auth()->id()) is-mine @endif" wire:key="msg-{{ $m->id }}">
                            <div class="d-flex justify-content-between small">
                                <span>
                                    <strong>{{ $m->Author?->name ?? 'Usuário' }}</strong>
                                    <span class="badge bg-secondary">{{ $roleLabels[$m->author_role] ?? $m->author_role }}</span>
                                    @if ($internal)
                                        <span class="badge {{ $m->visibility === 'WITH_PARTNER' ? 'bg-info text-dark' : 'bg-dark' }}">{{ $m->visibility === 'WITH_PARTNER' ? 'Com parceira' : 'Interno' }}</span>
                                    @endif
                                    @if ($m->Issue)
                                        <span class="badge bg-warning text-dark">{{ $m->Issue->Subcategory?->name ?? 'Pendência' }}</span>
                                    @endif
                                </span>
                                <span class="text-muted">{{ $m->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <div style="white-space: pre-wrap;">{{ $m->body }}</div>
                            @foreach ($m->EvidenceFiles as $file)
                                <a class="small me-2" target="_blank" href="{{ route('files.evidence.preview', ['file' => $file->id]) }}"><i class="bi bi-paperclip"></i>{{ $file->original_name }}</a>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-center text-muted py-5 my-3 rounded" style="background:#f6fafb;"><i class="ri-chat-smile-3-line d-block" style="font-size:2rem;opacity:.5;"></i><div class="fw-semibold">Nenhuma mensagem ainda.</div><div class="small">Inicie uma conversa operacional abaixo para registrar tratativas.</div></div>
                    @endforelse
                </div>
                <div class="p-3">
                    <textarea class="form-control @error('chatBody') is-invalid @enderror" rows="3" wire:model.defer="chatBody" maxlength="5000" placeholder="Escreva uma mensagem…"></textarea>
                    @error('chatBody') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                        @if ($internal)
                            <select class="form-select form-select-sm w-auto" wire:model.defer="chatVisibility">
                                <option value="INTERNAL_ONLY">Interno</option>
                                <option value="WITH_PARTNER">Com parceira</option>
                            </select>
                        @endif
                        <button type="button" class="btn btn-sm btn-primary ms-auto" wire:click="postMessage"><i class="ri-send-plane-line"></i> Enviar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Prioridade --}}
        @if ($internal)
            <div class="col-12 col-xl-5">
                <div class="chart-card h-100 d-flex flex-column">
                    <div class="od-section-head pb-0"><div><h3><i class="ri-flag-line text-muted"></i> Prioridade da obra</h3><div class="small text-muted mt-1">Pedir prioridade para as Ordens abertas desta obra</div></div></div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                    @php $pending = $overview['priority_pending']; @endphp
                    @if ($pending)
                        <div class="alert alert-warning mb-2">
                            <strong>Pedido pendente</strong><br>
                            {{ $pending->reason }}
                        </div>
                        @if ($this->isDispatcher())
                            <input type="text" class="form-control mb-2" wire:model.defer="priorityDecisionNote" placeholder="Observação da decisão (opcional)">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm" wire:click="decidePriority({{ $pending->id }}, 'accept')">Aceitar</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="decidePriority({{ $pending->id }}, 'decline')">Recusar</button>
                            </div>
                        @endif
                    @else
                        <label class="cpop-label">Motivo do pedido</label>
                        <textarea class="form-control flex-grow-1 mb-3" rows="5" wire:model.defer="priorityReason" maxlength="1000" placeholder="Descreva os motivos técnicos ou regulatórios para a alteração da prioridade…"></textarea>
                        <div><button type="button" class="btn btn-outline-primary btn-sm" wire:click="requestPriority"><i class="ri-flashlight-line"></i> Pedir prioridade</button></div>
                    @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    @endif

    {{-- Enviar à parceira (modal operacional) --}}
    @if ($internal && $partnerModal)
        @php $subs = $categories->firstWhere('id', (int) $issueCategoryId)?->Subcategories ?? collect(); @endphp
        <div class="cpop-overlay" wire:click.self="closePartnerModal" x-data @keydown.escape.window="$wire.closePartnerModal()" id="closure-send-partner">
            <div class="cpop" style="max-width: 1060px; --tone: #263cc8;" role="dialog" aria-modal="true" aria-label="Enviar à parceira">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-send-plane-2-line"></i></span>
                    <div>
                        <h3 class="cpop-title">Enviar à parceira para resolver</h3>
                        <div class="cpop-sub">Obra {{ $note['number'] }}@if ($note['city']) · {{ $note['city'] }}@endif</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="closePartnerModal"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="cpop-section-title">1 · A quem e sobre o quê</div>
                            <div class="mb-3">
                                <label class="cpop-label">Ordem(ns) <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2 @error('issueOrderIds') border border-danger rounded p-2 @enderror">
                                    @foreach ($rows as $row)
                                        <div wire:key="io-{{ $row['order_id'] }}">
                                            @if ($row['has_state'])
                                                <input class="btn-check" type="checkbox" id="io-{{ $row['state_id'] }}" value="{{ $row['state_id'] }}" wire:model.defer="issueOrderIds" autocomplete="off">
                                                <label class="btn btn-outline-primary btn-sm" for="io-{{ $row['state_id'] }}">{{ $row['ordem'] }}</label>
                                            @else
                                                <input class="btn-check" type="checkbox" disabled id="io-o{{ $row['order_id'] }}">
                                                <label class="btn btn-outline-secondary btn-sm disabled" for="io-o{{ $row['order_id'] }}" title="Despache a Ordem primeiro">{{ $row['ordem'] }} · não despachada</label>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if ($rows->contains(fn ($r) => !$r['has_state']))
                                    <div class="small text-muted mt-1">Ordens não despachadas não podem ser enviadas à parceira: despache a Ordem primeiro.</div>
                                @endif
                                @error('issueOrderIds') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="cpop-label">Parceira <span class="text-danger">*</span></label>
                                    <select class="form-select @error('issueCompanyId') is-invalid @enderror" wire:model.defer="issueCompanyId">
                                        <option value="">Selecione…</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->display_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('issueCompanyId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="cpop-label">Categoria <span class="text-danger">*</span></label>
                                    <select class="form-select @error('issueCategoryId') is-invalid @enderror" wire:model="issueCategoryId">
                                        <option value="">Selecione…</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('issueCategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="cpop-label">Subcategoria <span class="text-danger">*</span></label>
                                    <select class="form-select @error('issueSubcategoryId') is-invalid @enderror" wire:model.defer="issueSubcategoryId" @disabled(!$issueCategoryId)>
                                        <option value="">Selecione…</option>
                                        @foreach ($subs as $sub)
                                            <option value="{{ $sub->id }}">{{ $sub->name }}{{ $sub->requires_description ? ' (exige descrição)' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('issueSubcategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <label class="cpop-label">Contexto geral <span class="text-muted fw-normal">(opcional, a menos que a subcategoria exija)</span></label>
                            <textarea class="form-control @error('issueDescription') is-invalid @enderror" rows="4" wire:model.defer="issueDescription" maxlength="2000" placeholder="Explique a situação para a parceira."></textarea>
                            @error('issueDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="cpop-section-title d-flex align-items-center justify-content-between">
                                <span>2 · Lista de exigências</span>
                                <span class="od-count">{{ collect($issueRequirements)->filter(fn ($t) => trim((string) $t) !== '')->count() }} item(ns)</span>
                            </div>
                            <div class="small text-muted mb-2">Cada exigência é um item que a parceira deve cumprir. Aparecem numeradas para ela, no chat e na pendência.</div>
                            <div class="d-flex flex-column gap-2" style="max-height: 340px; overflow-y: auto;">
                                @foreach ($issueRequirements as $index => $requirement)
                                    <div class="d-flex align-items-start gap-2" wire:key="req-{{ $index }}">
                                        <span class="cpop-num">{{ $index + 1 }}</span>
                                        <textarea class="form-control form-control-sm" rows="2" maxlength="500" wire:model.defer="issueRequirements.{{ $index }}" placeholder="Ex.: enviar a ART assinada, regularizar o depósito, anexar o croqui…"></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeRequirement({{ $index }})" aria-label="Remover exigência {{ $index + 1 }}" @disabled(count($issueRequirements) === 1 && trim((string) $requirement) === '')><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="addRequirement"><i class="ri-add-line"></i> Adicionar exigência</button>

                            <div class="cpop-hint mt-4 mb-0">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="issue-blocking" wire:model.defer="issueBlocking">
                                    <label class="form-check-label fw-semibold" for="issue-blocking">Pendência bloqueante</label>
                                </div>
                                <div class="small">A Ordem fica em <strong>Aguardando Parceira</strong>. Mesmo bloqueante, <strong>você pode desbloquear a qualquer momento</strong> (Ações da Ordem ou da pendência), sem depender do retorno da parceira.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="closePartnerModal">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background: #263cc8;" wire:click="sendToPartner" wire:loading.attr="disabled" wire:target="sendToPartner"><i class="ri-send-plane-line"></i> Enviar à parceira</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Ação sobre pendência (modal) --}}
    @if ($actingIssueId && ($actingIssue = $issues->firstWhere('id', $actingIssueId)))
        @php
            $actCfg = [
                'validate' => ['Validar retorno', 'ri-check-double-line', '#263cc8', 'Observação da validação (opcional)', 'Validar'],
                'return'   => ['Devolver para correção', 'ri-arrow-go-back-line', '#c0392b', 'Motivo da devolução', 'Devolver'],
                'resolve'  => ['Resolver pendência', 'ri-checkbox-circle-line', '#0f8a77', 'Observação da resolução (opcional)', 'Resolver'],
                'respond'  => ['Responder pendência', 'ri-reply-line', '#263cc8', 'Retorno da parceira', 'Enviar retorno'],
                'release'  => ['Desbloquear sem retorno', 'ri-lock-unlock-line', '#b45309', 'Motivo do desbloqueio', 'Desbloquear'],
            ][$actingMode] ?? ['Pendência', 'ri-settings-3-line', '#155f67', 'Mensagem', 'Confirmar'];
        @endphp
        <div class="cpop-overlay" wire:click.self="cancelIssueAction" x-data @keydown.escape.window="$wire.cancelIssueAction()">
            <div class="cpop" style="max-width: 560px; --tone: {{ $actCfg[2] }};" role="dialog" aria-modal="true" aria-label="{{ $actCfg[0] }}">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="{{ $actCfg[1] }}"></i></span>
                    <div>
                        <h3 class="cpop-title">{{ $actCfg[0] }}</h3>
                        <div class="cpop-sub">Ordem {{ $actingIssue->Order?->ordem }} · {{ $actingIssue->Category?->name }} / {{ $actingIssue->Subcategory?->name }}</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelIssueAction"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    @if ($actingMode === 'release')
                        <div class="cpop-hint">A pendência é encerrada agora{{ $actingIssue->blocking ? ' e a Ordem volta ao status anterior' : '' }}, <strong>sem depender do retorno da parceira</strong>. O motivo fica registrado no histórico e no chat da obra.</div>
                    @endif
                    <label for="acting-message" class="cpop-label">{{ $actCfg[3] }}@if (in_array($actingMode, ['return', 'respond', 'release'], true)) <span class="text-danger">*</span>@endif</label>
                    <textarea id="acting-message" class="form-control @error('actingMessage') is-invalid @enderror" rows="4" wire:model.defer="actingMessage" maxlength="5000"></textarea>
                    @error('actingMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($actingMode === 'respond')
                        <input type="file" class="form-control form-control-sm mt-2" multiple wire:model="actingAttachments">
                        @error('actingAttachments.*') <div class="text-danger small">{{ $message }}</div> @enderror
                    @endif
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelIssueAction">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background: {{ $actCfg[2] }};" wire:click="confirmIssueAction" wire:loading.attr="disabled" wire:target="confirmIssueAction,actingAttachments">{{ $actCfg[4] }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pendências --}}
    @if ($tab === 'overview')
    <div class="chart-card mb-3">
        <div class="od-section-head border-bottom">
            <div><h3><i class="ri-error-warning-line text-muted"></i> Pendências abertas</h3><div class="small text-muted mt-1">{{ $closedIssues }} já resolvida(s) nesta obra.</div></div>
            <span class="od-count">{{ $issues->count() }} aberta(s)</span>
        </div>
        @forelse ($issues as $issue)
            <div class="issue-card px-3 pb-3" wire:key="issue-{{ $issue->id }}">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pt-2">
                    <div>
                        <strong>Ordem {{ $issue->Order?->ordem }}</strong> —
                        {{ $issue->Category?->name }} / {{ $issue->Subcategory?->name }}
                        <span class="badge bg-secondary">{{ $issueStatusLabels[$issue->status] ?? $issue->status }}</span>
                        <span class="badge bg-light text-dark">Ciclo {{ $issue->cycle }}</span>
                        @if ($issue->blocking) <span class="badge bg-danger">Bloqueante</span> @endif
                        <div class="small text-muted">Destino: {{ $issue->DestinationCompany?->name }}</div>
                        @if ($issue->description) <div class="small">{{ $issue->description }}</div> @endif
                        @if (!empty($issue->requirements))
                            <ol class="small mb-0 mt-1 ps-3">
                                @foreach ($issue->requirements as $requirement)<li>{{ $requirement }}</li>@endforeach
                            </ol>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        @if ($internal && $issue->status === 'RESPONDED')
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="startIssueAction({{ $issue->id }}, 'validate')">Validar</button>
                        @endif
                        @if ($internal && in_array($issue->status, ['RESPONDED', 'VALIDATING'], true))
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="startIssueAction({{ $issue->id }}, 'return')">Devolver</button>
                            <button type="button" class="btn btn-sm btn-success" wire:click="startIssueAction({{ $issue->id }}, 'resolve')">Resolver</button>
                        @endif
                        @if ($internal)
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="startIssueAction({{ $issue->id }}, 'release')" title="Encerra a pendência e devolve a Ordem ao ponto anterior, sem esperar a parceira"><i class="ri-lock-unlock-line"></i> Desbloquear</button>
                        @endif
                        @if (!$internal)
                            @php $canReplyNow = $canRespond && in_array($issue->status, ['REQUESTED', 'RETURNED'], true); @endphp
                            <a class="btn btn-sm {{ $canReplyNow ? 'btn-success' : 'btn-outline-secondary' }}" href="{{ route('partner.closure.issue.show', $issue->id) }}">{{ $canReplyNow ? 'Ver pedido e responder' : 'Ver pedido' }}</a>
                        @endif
                    </div>
                </div>
                <ul class="small mt-2 mb-0 list-unstyled">
                    @foreach ($issue->Events as $event)
                        <li><span class="text-muted">{{ $event->created_at?->format('d/m/Y H:i') }}</span> · {{ $eventLabels[$event->type] ?? $event->type }} · {{ $event->Actor?->name }}@if (!empty($event->meta['message'])) — “{{ $event->meta['message'] }}”@endif
                            @if (!empty($event->meta['answers']))
                                <ul class="mt-1 mb-1 ps-3">
                                    @foreach ($event->meta['answers'] as $answer)
                                        <li>{{ $answer['index'] }}. {{ $answer['text'] }} → <strong>{{ \App\Services\Closure\ClosureIssueWorkflowService::ANSWER_LABELS[$answer['status']] ?? $answer['status'] }}</strong>@if ($answer['note']) — {{ $answer['note'] }}@endif</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (!empty($event->meta['attachments']))<span class="badge bg-light text-dark"><i class="bi bi-paperclip"></i> {{ $event->meta['attachments'] }} anexo(s)</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="text-center text-muted py-4"><i class="ri-checkbox-circle-line d-block text-success" style="font-size:1.8rem;"></i>Nenhuma pendência aberta.</div>
        @endforelse
    </div>

    @endif

    @if ($internal)
        @livewire('closure.internal-returns.modal')
    @endif

    @once
        <script>
            if (!window.__closureFixedDropdown) {
                window.__closureFixedDropdown = true;
                document.addEventListener('click', function (event) {
                    const toggle = event.target.closest('[data-closure-fixed-dropdown]');
                    if (toggle && window.bootstrap && !window.bootstrap.Dropdown.getInstance(toggle)) {
                        window.bootstrap.Dropdown.getOrCreateInstance(toggle, { popperConfig: (defaults) => ({ ...defaults, strategy: 'fixed' }) });
                    }
                }, true);
            }
        </script>
    @endonce
</div>
