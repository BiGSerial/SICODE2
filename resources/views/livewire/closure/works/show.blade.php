@php
    use App\Enum\ClosureOperationalStatus;
    use App\Services\Closure\ClosureMetaClassifier;

    $note = $overview['note'];
    $issueStatusLabels = [
        'OPEN' => 'Aberta', 'REQUESTED' => 'Aguardando resposta', 'RESPONDED' => 'Respondida',
        'VALIDATING' => 'Em validação', 'RETURNED' => 'Devolvida p/ correção', 'RESOLVED' => 'Resolvida',
    ];
    $eventLabels = [
        'REQUESTED' => 'Enviada à parceira', 'RESPONDED' => 'Parceira respondeu', 'VALIDATING' => 'Em validação',
        'RETURNED_FOR_CORRECTION' => 'Devolvida para correção', 'RESOLVED' => 'Resolvida', 'OPENED' => 'Aberta',
    ];
    $roleLabels = ['DISPATCHER' => 'Despachante', 'INTERNAL' => 'Encerramento', 'PARTNER' => 'Parceira'];
@endphp
<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Obra {{ $note['number'] }} @if ($note['type']) <span class="badge bg-light text-dark align-middle">{{ $note['type'] }}</span> @endif</h2>
                <div class="dash-subtitle">
                    {{ $note['city'] ?: 'Município não informado' }} · {{ $note['client'] ?: 'Cliente não informado' }}
                    @if ($overview['company']) · {{ $overview['company']['name'] }} @endif
                    @if ($note['value'] !== null) · {{ $note['currency'] ?: 'R$' }} {{ number_format((float) $note['value'], 2, ',', '.') }} @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach ($typeSummary as $letter => $count)
                    <span class="badge {{ ClosureMetaClassifier::badgeClass($letter) }} px-3 py-2" title="{{ ClosureMetaClassifier::label($letter) }}">{{ $letter }} · {{ $count }}</span>
                @endforeach
                @if ($overview['has_priority'] && $internal)
                    <span class="badge bg-danger px-3 py-2">Prioridade</span>
                @endif
            </div>
        </div>
    </div>

    @if ($feedback)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert" id="closure-work-feedback">{{ $feedback }}</div>
    @endif

    {{-- Ordens --}}
    <div class="table-card mb-3">
        <div class="chart-head p-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="chart-title">Ordens da obra</div>
                <div class="chart-subtitle">{{ count($rows) }} Ordem(ns) · {{ count($overview['open_issues']) }} pendência(s) aberta(s)</div>
            </div>
            @if ($internal && $canFinishAny)
                <button type="button" class="btn btn-dash btn-success btn-sm" wire:click="askFinish('all')">Finalizar obra</button>
            @endif
        </div>

        @if ($pendingFinish !== null)
            <div class="alert alert-warning m-3 d-flex justify-content-between align-items-center" role="alert">
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

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Ordem</th>
                        <th>Status SAP</th>
                        <th>Status da atividade</th>
                        @if ($internal)
                            <th>Responsável / detentor</th>
                            <th>Prioridade</th>
                        @endif
                        <th class="text-center">Operações</th>
                        <th class="text-center">Pendências</th>
                        @if ($internal)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="work-order-{{ $row['state_id'] }}">
                            <td>{!! ClosureMetaClassifier::badge($row['type']) !!}</td>
                            <td>
                                <strong>{{ $row['ordem'] }}</strong>
                                @if ($internal && auth()->user()->can('closure.view'))
                                    <a href="{{ route('closure.order.detail', $row['order_id']) }}" class="small ms-1">detalhe</a>
                                @endif
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($row['description'], 60) }}</div>
                            </td>
                            <td>{{ $row['status_sist'] ?? '—' }}</td>
                            <td><span class="badge {{ ClosureOperationalStatus::badgeClassFor($row['operational_status']) }}">{{ ClosureOperationalStatus::labelFor($row['operational_status']) }}</span></td>
                            @if ($internal)
                                <td>{{ $row['assignee']['name'] ?? ($row['holder_company'] ?? '—') }}</td>
                                <td>{!! $row['priority'] ? '<span class="badge bg-danger">Sim</span>' : '<span class="text-muted">—</span>' !!}</td>
                            @endif
                            <td class="text-center">{{ $row['ops'] }}</td>
                            <td class="text-center">{{ $row['open_issues'] }}</td>
                            @if ($internal)
                                <td class="text-end text-nowrap">
                                    @if ($row['manage'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectStateForStatus({{ $row['state_id'] }})">Status</button>
                                    @endif
                                    @if ($row['finish'])
                                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="askFinish('{{ $row['state_id'] }}')">Finalizar</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma Ordem disponível para você nesta obra.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Alterar status --}}
    @if ($internal && $statusState)
        <div class="chart-card mb-3 p-3" id="closure-status-area">
            <div class="chart-title mb-2">Alterar status da atividade — Ordem {{ $statusState->Order?->ordem }}</div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label">Novo status (atual: {{ ClosureOperationalStatus::labelFor($statusState->operational_status) }})</label>
                    <select class="form-select" wire:model="statusTarget" @disabled($statusConfirming)>
                        <option value="">Selecione…</option>
                        @foreach ($this->targetsFor($statusState) as $target)
                            <option value="{{ $target }}">{{ ClosureOperationalStatus::labelFor($target) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label">Motivo @if ($statusTarget !== '' && $this->reasonRequired($statusState, $statusTarget)) <span class="text-danger">*</span> @endif</label>
                    <input type="text" class="form-control @error('statusReason') is-invalid @enderror" wire:model.defer="statusReason" maxlength="500" @disabled($statusConfirming)>
                    @error('statusReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    @if ($statusConfirming)
                        <button type="button" class="btn btn-warning" wire:click="confirmStatusChange">Confirmar alteração</button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="prepareStatusChange">Alterar</button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" wire:click="cancelStatusChange">Cancelar</button>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        {{-- Chat --}}
        <div class="col-12 col-xl-7">
            <div class="chart-card h-100">
                <div class="chart-head p-3">
                    <div class="chart-title">Conversa da obra</div>
                    <div class="chart-subtitle">{{ $internal ? 'Interno = só equipe do Encerramento; Com parceira = visível à empresa.' : 'Conversa com a equipe de Encerramento.' }}</div>
                </div>
                <div class="px-3" wire:poll.15s style="max-height: 420px; overflow-y: auto;" id="closure-chat">
                    @forelse ($messages as $m)
                        <div class="border-bottom py-2" wire:key="msg-{{ $m->id }}">
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
                        <div class="text-muted py-4 text-center">Nenhuma mensagem ainda.</div>
                    @endforelse
                </div>
                <div class="p-3">
                    <textarea class="form-control @error('chatBody') is-invalid @enderror" rows="2" wire:model.defer="chatBody" maxlength="5000" placeholder="Escreva uma mensagem…"></textarea>
                    @error('chatBody') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                        @if ($internal)
                            <select class="form-select form-select-sm w-auto" wire:model.defer="chatVisibility">
                                <option value="INTERNAL_ONLY">Interno</option>
                                <option value="WITH_PARTNER">Com parceira</option>
                            </select>
                        @endif
                        <input type="file" class="form-control form-control-sm w-auto" multiple wire:model="chatAttachments">
                        <button type="button" class="btn btn-sm btn-primary ms-auto" wire:click="postMessage" wire:loading.attr="disabled" wire:target="chatAttachments">Enviar</button>
                    </div>
                    @error('chatAttachments.*') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Prioridade --}}
        @if ($internal)
            <div class="col-12 col-xl-5">
                <div class="chart-card h-100 p-3">
                    <div class="chart-title mb-2">Prioridade da obra</div>
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
                        <label class="form-label">Pedir prioridade para as Ordens abertas desta obra</label>
                        <textarea class="form-control mb-2" rows="2" wire:model.defer="priorityReason" maxlength="1000" placeholder="Motivo do pedido"></textarea>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="requestPriority">Pedir prioridade</button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Enviar à parceira --}}
    @if ($internal)
        <div class="chart-card mb-3 p-3" id="closure-send-partner">
            <div class="chart-title mb-2">Enviar à parceira para resolver</div>
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label">Categoria <span class="text-danger">*</span></label>
                    <select class="form-select @error('issueCategoryId') is-invalid @enderror" wire:model="issueCategoryId">
                        <option value="">Selecione…</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('issueCategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Subcategoria <span class="text-danger">*</span></label>
                    <select class="form-select @error('issueSubcategoryId') is-invalid @enderror" wire:model.defer="issueSubcategoryId">
                        <option value="">Selecione…</option>
                        @foreach ($categories->firstWhere('id', (int) $issueCategoryId)?->Subcategories ?? [] as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->name }}{{ $sub->requires_description ? ' (exige descrição)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('issueSubcategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Empresa destino <span class="text-danger">*</span></label>
                    <select class="form-select @error('issueCompanyId') is-invalid @enderror" wire:model.defer="issueCompanyId">
                        <option value="">Selecione…</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('issueCompanyId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Bloqueante?</label>
                    <select class="form-select" wire:model.defer="issueBlocking">
                        <option value="1">Sim — bloqueia a Ordem</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ordem(ns) alvo <span class="text-danger">*</span></label>
                    <div class="border rounded p-2 @error('issueOrderIds') border-danger @enderror">
                        @foreach ($rows as $row)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="io-{{ $row['state_id'] }}" value="{{ $row['state_id'] }}" wire:model.defer="issueOrderIds">
                                <label class="form-check-label" for="io-{{ $row['state_id'] }}">{{ $row['ordem'] }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('issueOrderIds') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control @error('issueDescription') is-invalid @enderror" rows="2" wire:model.defer="issueDescription" maxlength="2000"></textarea>
                    @error('issueDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <button type="button" class="btn btn-primary mt-2" wire:click="sendToPartner">Enviar à parceira</button>
        </div>
    @endif

    {{-- Pendências --}}
    <div class="chart-card mb-3">
        <div class="chart-head p-3">
            <div class="chart-title">Pendências abertas</div>
            <div class="chart-subtitle">{{ $closedIssues }} já resolvida(s) nesta obra.</div>
        </div>
        @forelse ($issues as $issue)
            <div class="px-3 pb-3 border-bottom" wire:key="issue-{{ $issue->id }}">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pt-2">
                    <div>
                        <strong>Ordem {{ $issue->Order?->ordem }}</strong> —
                        {{ $issue->Category?->name }} / {{ $issue->Subcategory?->name }}
                        <span class="badge bg-secondary">{{ $issueStatusLabels[$issue->status] ?? $issue->status }}</span>
                        <span class="badge bg-light text-dark">Ciclo {{ $issue->cycle }}</span>
                        @if ($issue->blocking) <span class="badge bg-danger">Bloqueante</span> @endif
                        <div class="small text-muted">Destino: {{ $issue->DestinationCompany?->name }}</div>
                        @if ($issue->description) <div class="small">{{ $issue->description }}</div> @endif
                    </div>
                    <div class="d-flex gap-1">
                        @if ($internal && $issue->status === 'RESPONDED')
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="startIssueAction({{ $issue->id }}, 'validate')">Validar</button>
                        @endif
                        @if ($internal && in_array($issue->status, ['RESPONDED', 'VALIDATING'], true))
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="startIssueAction({{ $issue->id }}, 'return')">Devolver</button>
                            <button type="button" class="btn btn-sm btn-success" wire:click="startIssueAction({{ $issue->id }}, 'resolve')">Resolver</button>
                        @endif
                        @if ($canRespond && in_array($issue->status, ['REQUESTED', 'RETURNED'], true))
                            <button type="button" class="btn btn-sm btn-primary" wire:click="startIssueAction({{ $issue->id }}, 'respond')">Responder</button>
                        @endif
                    </div>
                </div>
                <ul class="small mt-2 mb-0 list-unstyled">
                    @foreach ($issue->Events as $event)
                        <li><span class="text-muted">{{ $event->created_at?->format('d/m/Y H:i') }}</span> · {{ $eventLabels[$event->type] ?? $event->type }} · {{ $event->Actor?->name }}@if (!empty($event->meta['message'])) — “{{ $event->meta['message'] }}”@endif</li>
                    @endforeach
                </ul>
                @if ($actingIssueId === $issue->id)
                    <div class="mt-2 p-2 bg-light rounded">
                        <label class="form-label">
                            {{ ['validate' => 'Observação da validação (opcional)', 'return' => 'Motivo da devolução', 'resolve' => 'Observação da resolução (opcional)', 'respond' => 'Retorno da parceira'][$actingMode] ?? '' }}
                        </label>
                        <textarea class="form-control @error('actingMessage') is-invalid @enderror" rows="2" wire:model.defer="actingMessage" maxlength="5000"></textarea>
                        @error('actingMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($actingMode === 'respond')
                            <input type="file" class="form-control form-control-sm mt-2" multiple wire:model="actingAttachments">
                            @error('actingAttachments.*') <div class="text-danger small">{{ $message }}</div> @enderror
                        @endif
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success" wire:click="confirmIssueAction" wire:loading.attr="disabled" wire:target="actingAttachments">Confirmar</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelIssueAction">Cancelar</button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-muted text-center py-4">Nenhuma pendência aberta.</div>
        @endforelse
    </div>
</div>
