<div class="closure-dash">
    @include('livewire.closure._styles')
    @include('livewire.closure.work._confirm-script')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Minha Atividade — Encerramento</h2>
                <div class="dash-subtitle">Ordens atribuídas a você{{ auth()->user()->can('closure.manage') ? ' (como gestor, você vê as de todos)' : '' }}.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if ($selfAssignEnabled)
                    <a href="{{ route('closure.work.available') }}" class="btn btn-light btn-sm">Pegar atividades</a>
                @endif
                <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedStateIds) }} selecionada(s)</span>
            </div>
        </div>
    </div>

    <div class="table-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            @include('livewire.closure.work._filters')
            <div class="col-12 col-md-3">
                <label for="closure-work-status" class="form-label fw-semibold">Status da atividade</label>
                <select id="closure-work-status" class="form-select" wire:model="status">
                    <option value="all">Em tratativa (todos)</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="closure-work-batch" class="form-label fw-semibold">Ações em massa</label>
                <div class="input-group">
                    <select id="closure-work-batch" class="form-select" wire:model="batchAction">
                        <option value="">Escolha uma ação</option>
                        <option value="start">Iniciar</option>
                        <option value="change_status">Alterar status</option>
                        <option value="finish">Finalizar</option>
                        <option value="return_to_pool">Devolver à pilha</option>
                        @can('closure.manage')
                            <option value="assign">Atribuir</option>
                            <option value="transfer">Transferir</option>
                            <option value="set_priority">Ajustar prioridade</option>
                        @endcan
                    </select>
                    <button type="button" class="btn btn-outline-secondary" wire:click="prepareBatchPreview">Ver prévia</button>
                </div>
            </div>
            @if ($batchAction === 'change_status')
                <div class="col-12 col-md-4">
                    <label for="closure-work-batch-status" class="form-label fw-semibold">Novo status</label>
                    <select id="closure-work-batch-status" class="form-select" wire:model="batchStatus">
                        <option value="">Selecione</option>
                        <option value="PAUSED">Pausa</option>
                        <option value="WAITING_PARTNER">Aguardando Parceira</option>
                        <option value="DEPOSIT_BLOCKED">Depósito Bloqueado</option>
                        <option value="DEPOSIT_RELEASED">Depósito Liberado</option>
                        <option value="IN_PROGRESS">Em andamento</option>
                    </select>
                </div>
            @endif
            @if ($batchAction === 'return_to_pool' || ($batchAction === 'change_status' && in_array($batchStatus, ['PAUSED', 'WAITING_PARTNER', 'DEPOSIT_BLOCKED'], true)))
                <div class="col-12">
                    <label for="closure-work-batch-reason" class="form-label fw-semibold">Motivo da ação em massa</label>
                    <textarea id="closure-work-batch-reason" class="form-control" rows="2" wire:model.defer="batchReason" placeholder="Explique o motivo."></textarea>
                </div>
            @endif
            @can('closure.manage')
                @if (in_array($batchAction, ['assign', 'transfer', 'set_priority'], true))
                    <div class="col-12 mt-3 border-top pt-3">
                        <div class="small fw-semibold text-uppercase text-muted mb-2">Controles de gestão</div>
                        <div class="row g-3">
                            @if (in_array($batchAction, ['assign', 'transfer'], true))
                                <div class="col-12 col-md-6">
                                    <label for="closure-work-destination-company" class="form-label fw-semibold">Empresa de destino</label>
                                    <select id="closure-work-destination-company" class="form-select" wire:model="managerCompanyId">
                                        <option value="">Selecione</option>
                                        @foreach ($this->destinationCompanies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="closure-work-destination-user" class="form-label fw-semibold">Usuário de destino {{ $batchAction === 'assign' ? '' : '(opcional)' }}</label>
                                    <select id="closure-work-destination-user" class="form-select" wire:model="managerAssigneeId" @disabled(!$managerCompanyId)>
                                        <option value="">{{ $batchAction === 'assign' ? 'Selecione' : 'Enviar para a pilha' }}</option>
                                        @foreach ($this->destinationUsers as $destinationUser)
                                            <option value="{{ $destinationUser->id }}">{{ $destinationUser->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if ($batchAction === 'set_priority')
                                <div class="col-12 col-md-6">
                                    <label for="closure-work-priority" class="form-label fw-semibold">Prioridade</label>
                                    <select id="closure-work-priority" class="form-select" wire:model="managerPriority">
                                        <option value="0">Normal</option>
                                        <option value="1">Alta</option>
                                    </select>
                                </div>
                            @endif
                            @if ($batchAction === 'transfer')
                                <div class="col-12">
                                    <label for="closure-work-transfer-reason" class="form-label fw-semibold">Motivo da transferência</label>
                                    <textarea id="closure-work-transfer-reason" class="form-control" rows="2" wire:model.defer="batchReason" placeholder="Explique o motivo da transferência."></textarea>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>

    @if ($pendingAction)
        <div class="table-card p-3 mb-3" id="closure-work-reason">
            <h3 class="chart-title mb-2">
                {{ $pendingAction === 'return' ? 'Motivo da devolução' : 'Motivo — ' . \App\Enum\ClosureOperationalStatus::labelFor($pendingStatus) }}
            </h3>
            <p class="text-muted small">Este registro ficará visível na linha do tempo da Ordem.</p>
            <label for="closure-work-action-reason" class="form-label fw-semibold">Motivo</label>
            <textarea id="closure-work-action-reason" class="form-control" rows="3" wire:model.defer="actionReason" placeholder="Descreva o motivo de forma objetiva."></textarea>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelActionWithReason">Cancelar</button>
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-teal);" wire:click="confirmActionWithReason">Confirmar ação</button>
            </div>
        </div>
    @endif

    <div class="table-card">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Fila operacional</h3>
                <div class="chart-subtitle">O status SAP é informativo; as ações abaixo não o alteram.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleStates">Selecionar/limpar visíveis</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:48px"></th>
                        @include('livewire.closure.work._order-head')
                        <th>Status da atividade</th>
                        <th>Prioridade</th>
                        <th>Tempo no status</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($states as $state)
                        @php
                            $selected = in_array($state->id, $selectedStateIds);
                            $blocked  = $this->finishBlockedReason($state);
                        @endphp
                        <tr wire:key="closure-work-state-{{ $state->id }}">
                            <td class="ps-3"><input type="checkbox" class="form-check-input" @checked($selected) wire:click="toggleState({{ $state->id }})"></td>
                            @include('livewire.closure.work._order-cells')
                            <td>
                                <span class="badge {{ \App\Enum\ClosureOperationalStatus::badgeClassFor($state->operational_status) }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($state->operational_status) }}</span>
                                @if ($state->operational_status === 'WAITING')
                                    <div class="small text-muted">Aguarda o mês da meta</div>
                                @endif
                            </td>
                            <td>@if ($state->priority)<span class="badge bg-danger">Prioritária</span>@else<span class="text-muted small">Normal</span>@endif</td>
                            <td class="small text-muted" title="{{ $this->holderLabel($state) }}">{{ $this->elapsedLabel($state) }}</td>
                            <td class="pe-3 text-end text-nowrap">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Ações da Ordem {{ $state->Order?->ordem }}">
                                    @if ($this->canRun($state, 'start'))
                                        <button type="button" class="btn btn-outline-success" wire:click="runAction({{ $state->id }}, 'start')">Iniciar</button>
                                    @endif
                                    @if ($this->canChangeStatus($state))
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">Alterar status</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @foreach ($this->statusTargets($state) as $target)
                                                    <li><button type="button" class="dropdown-item" wire:click="changeStatus({{ $state->id }}, '{{ $target }}')">{{ $this->statusActionLabel($target) }}</button></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if ($this->canRun($state, 'finish'))
                                        @if ($blocked)
                                            <span class="d-inline-block" title="{{ $blocked }}" data-bs-toggle="tooltip" tabindex="0">
                                                <button type="button" class="btn btn-outline-secondary" disabled style="pointer-events:none">Finalizar</button>
                                            </span>
                                        @else
                                            <button type="button" class="btn btn-outline-success" wire:click="runAction({{ $state->id }}, 'finish')">Finalizar</button>
                                        @endif
                                    @endif
                                    @if ($this->canRun($state, 'return'))
                                        <button type="button" class="btn btn-outline-secondary" wire:click="runAction({{ $state->id }}, 'return')">Devolver</button>
                                    @endif
                                    @if ($state->Order?->note_id)
                                        <a href="{{ route('closure.work.show', $state->Order->note_id) }}" class="btn btn-outline-primary">Obra</a>
                                    @endif
                                    <a href="{{ route('closure.order.detail', $state->order_id) }}" class="btn btn-outline-dark">Ordem</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center text-muted py-4">Não há Ordens nesta lista.@if ($selfAssignEnabled) Use "Pegar atividades" para assumir novas.@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($batchPreview)
        <div class="table-card mt-3" id="closure-work-batch-preview">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Prévia da ação em massa</h3>
                    <div class="chart-subtitle">A confirmação fará uma nova verificação antes de alterar cada Ordem.</div>
                </div>
                <span class="badge bg-secondary">{{ count($batchPreview['eligible']) }} pronta(s), {{ count($batchPreview['refusals']) }} recusada(s)</span>
            </div>
            <div class="row g-0">
                <div class="col-12 col-lg-6 p-3 border-end">
                    <h4 class="h6">Prontas para a ação</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchPreview['eligible'] as $order)
                            <li>{{ $order }}</li>
                        @empty
                            <li class="text-muted">Nenhuma Ordem pronta.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-12 col-lg-6 p-3">
                    <h4 class="h6">Recusadas na prévia</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchPreview['refusals'] as $refusal)
                            <li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>
                        @empty
                            <li class="text-muted">Nenhuma recusa.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="p-3 border-top d-flex justify-content-end">
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" wire:click="confirmBatchAction">Confirmar ação em massa</button>
            </div>
        </div>
    @endif

    @if ($batchReceipt)
        <div class="table-card mt-3" id="closure-work-receipt">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Recibo da ação em massa</h3>
                    <div class="chart-subtitle">Identificador: {{ $batchReceipt['batchId'] }}</div>
                </div>
                <span class="badge bg-secondary">{{ count($batchReceipt['successes']) }} sucesso(s), {{ count($batchReceipt['refusals']) }} recusa(s)</span>
            </div>
            <div class="row g-0">
                <div class="col-12 col-lg-6 p-3 border-end">
                    <h4 class="h6">Processadas</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchReceipt['successes'] as $order)
                            <li>{{ $order }}</li>
                        @empty
                            <li class="text-muted">Nenhuma Ordem processada.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-12 col-lg-6 p-3">
                    <h4 class="h6">Recusadas</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchReceipt['refusals'] as $refusal)
                            <li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>
                        @empty
                            <li class="text-muted">Nenhuma recusa.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
