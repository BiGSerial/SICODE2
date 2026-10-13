<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Minha Atividade — Encerramento</h2>
                <div class="dash-subtitle">Ordens que estão com você ou na pilha da sua empresa.</div>
            </div>
            <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedStateIds) }} selecionada(s)</span>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert">{{ $feedbackMessage }}</div>
    @endif

    <div class="table-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="closure-work-status" class="form-label fw-semibold">Tratativa</label>
                <select id="closure-work-status" class="form-select" wire:model="status">
                    <option value="all">Todas as situações</option>
                    <option value="AVAILABLE">Disponível</option>
                    <option value="ASSIGNED">Atribuída</option>
                    <option value="IN_PROGRESS">Em andamento</option>
                    <option value="WAITING_RETURN">Aguardando retorno</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="closure-work-possession" class="form-label fw-semibold">Posse</label>
                <select id="closure-work-possession" class="form-select" wire:model="possession">
                    <option value="all">Minha fila e pilha da empresa</option>
                    <option value="mine">Comigo</option>
                    <option value="stack">Pilha da empresa</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="closure-work-batch" class="form-label fw-semibold">Ação em lote</label>
                <div class="input-group">
                    <select id="closure-work-batch" class="form-select" wire:model="batchAction">
                        <option value="">Escolha uma ação</option>
                        <option value="claim">Assumir</option>
                        <option value="start">Iniciar</option>
                        <option value="wait_for_return">Aguardar retorno</option>
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
            @if (in_array($batchAction, ['wait_for_return', 'return_to_pool'], true))
                <div class="mt-3">
                    <label for="closure-work-batch-reason" class="form-label fw-semibold">Motivo da ação em lote</label>
                    <textarea id="closure-work-batch-reason" class="form-control" rows="2" wire:model.defer="batchReason" placeholder="Explique por que as Ordens devem aguardar ou voltar para a pilha."></textarea>
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
            <h3 class="chart-title mb-2">{{ $pendingAction === 'wait' ? 'Motivo da espera' : 'Motivo da devolução' }}</h3>
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
            <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleStates">Selecionar/limpar visíveis</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:48px"></th>
                        <th>Ordem</th>
                        <th>Nota/OV</th>
                        <th>SAP</th>
                        <th>Tratativa</th>
                        <th>Em posse de</th>
                        <th>Desde quando</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($states as $state)
                        @php
                            $sapClosed = str_starts_with((string) $state->Order?->statusSist, 'ENTE') || str_starts_with((string) $state->Order?->statusSist, 'ENCE');
                            $selected = in_array($state->id, $selectedStateIds);
                        @endphp
                        <tr wire:key="closure-work-state-{{ $state->id }}">
                            <td class="ps-3"><input type="checkbox" class="form-check-input" @checked($selected) wire:click="toggleState({{ $state->id }})"></td>
                            <td class="fw-semibold">{{ $state->Order?->ordem ?? '—' }}</td>
                            <td>{{ $state->Order?->Note?->note ?? '—' }}</td>
                            <td><span class="badge-stack {{ $sapClosed ? 'badge-closed' : 'badge-open' }}">{{ $sapClosed ? 'Encerrada' : 'Em aberto' }}</span></td>
                            <td><span class="badge-stack badge-type">{{ str_replace('_', ' ', $state->operational_status) }}</span></td>
                            <td>{{ $this->holderLabel($state) }}</td>
                            <td class="small text-muted">{{ $this->elapsedLabel($state) }}</td>
                            <td class="pe-3 text-end">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Ações da Ordem {{ $state->Order?->ordem }}">
                                    @if ($this->canRun($state, 'claim'))
                                        <button type="button" class="btn btn-outline-primary" wire:click="runAction({{ $state->id }}, 'claim')">Assumir</button>
                                    @endif
                                    @if ($this->canRun($state, 'start'))
                                        <button type="button" class="btn btn-outline-success" wire:click="runAction({{ $state->id }}, 'start')">Iniciar</button>
                                    @endif
                                    @if ($this->canRun($state, 'wait'))
                                        <button type="button" class="btn btn-outline-warning" wire:click="runAction({{ $state->id }}, 'wait')">Aguardar</button>
                                    @endif
                                    @if ($this->canRun($state, 'return'))
                                        <button type="button" class="btn btn-outline-secondary" wire:click="runAction({{ $state->id }}, 'return')">Devolver</button>
                                    @endif
                                    <a href="{{ route('closure.order.detail', $state->order_id) }}" class="btn btn-outline-dark">Detalhe</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Não há Ordens disponíveis nesta fila.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($batchPreview)
        <div class="table-card mt-3" id="closure-work-batch-preview">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Prévia da ação em lote</h3>
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
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" wire:click="confirmBatchAction">Confirmar ação em lote</button>
            </div>
        </div>
    @endif

    @if ($batchReceipt)
        <div class="table-card mt-3" id="closure-work-receipt">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Recibo da ação em lote</h3>
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
