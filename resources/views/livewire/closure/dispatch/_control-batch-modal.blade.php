@if ($showBatch)
    @php
        $selectedCount = count($selectedStateIds);
        $action  = $batchActions[$batchAction] ?? ['label' => 'Ação em massa', 'icon' => 'ri-stack-line', 'hint' => ''];
        $reason  = in_array($batchAction, ['return_to_pool', 'transfer'], true) || ($batchAction === 'change_status' && in_array($batchStatus, ['PAUSED', 'DEPOSIT_BLOCKED'], true));
        $hasRcpt = !empty($batchReceipt);
        $hasPrev = !empty($batchPreview);
    @endphp
    {{-- Modal no padrão ops-modal (docs/PADRAO_LISTAS_OPERACIONAIS.md §11): cabeçalho em degradê, corpo claro, rodapé branco. Uma raiz só. --}}
    <div class="modal ops-modal d-block show" tabindex="-1" role="dialog" aria-modal="true" aria-label="{{ $action['label'] }}" x-data @keydown.escape.window="$wire.closeBatch()">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Ação em massa · {{ $selectedCount }} {{ $selectedCount === 1 ? 'Ordem' : 'Ordens' }}</span>
                        <h5 class="modal-title">{{ $action['label'] }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" aria-label="Fechar" wire:click="closeBatch"></button>
                </div>

                <div class="modal-body">
                    @if ($hasRcpt)
                        <p class="ops-modal-note">Lote concluído. Identificador: <code>{{ $batchReceipt['batchId'] }}</code></p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="ops-modal-card">
                                    <div class="ops-modal-card-head tone-green"><i class="ri-checkbox-circle-line" aria-hidden="true"></i> Processadas ({{ count($batchReceipt['successes']) }})</div>
                                    <div class="ops-modal-card-body">
                                        <ul class="mb-0 ps-3">
                                            @forelse ($batchReceipt['successes'] as $order)<li>{{ $order }}</li>@empty<li class="text-muted">Nenhuma Ordem processada.</li>@endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ops-modal-card">
                                    <div class="ops-modal-card-head tone-red"><i class="ri-close-circle-line" aria-hidden="true"></i> Recusadas ({{ count($batchReceipt['refusals']) }})</div>
                                    <div class="ops-modal-card-body">
                                        <ul class="mb-0 ps-3">
                                            @forelse ($batchReceipt['refusals'] as $refusal)<li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>@empty<li class="text-muted">Nenhuma recusa.</li>@endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($hasPrev)
                        <p class="ops-modal-note">Confira a prévia. A confirmação faz uma nova verificação antes de alterar cada Ordem.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="ops-modal-card">
                                    <div class="ops-modal-card-head tone-green"><i class="ri-checkbox-circle-line" aria-hidden="true"></i> Prontas ({{ count($batchPreview['eligible']) }})</div>
                                    <div class="ops-modal-card-body">
                                        <ul class="mb-0 ps-3">
                                            @forelse ($batchPreview['eligible'] as $order)<li>{{ $order }}</li>@empty<li class="text-muted">Nenhuma Ordem pronta.</li>@endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ops-modal-card">
                                    <div class="ops-modal-card-head tone-red"><i class="ri-close-circle-line" aria-hidden="true"></i> Recusadas ({{ count($batchPreview['refusals']) }})</div>
                                    <div class="ops-modal-card-body">
                                        <ul class="mb-0 ps-3">
                                            @forelse ($batchPreview['refusals'] as $refusal)<li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>@empty<li class="text-muted">Nenhuma recusa.</li>@endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="ops-modal-note">{{ $action['hint'] }} Vale para as {{ $selectedCount }} Ordens selecionadas; você vê a prévia antes de confirmar.</p>
                        @if (!in_array($batchAction, ['assign', 'transfer', 'set_priority', 'return_to_pool', 'change_status'], true))
                            <p class="mb-0 small text-muted">Esta ação não tem campos: clique em "Ver prévia" para conferir quais Ordens estão prontas e quais serão recusadas.</p>
                        @endif
                        <div class="row g-3">
                            @if (in_array($batchAction, ['assign', 'transfer'], true))
                                <div class="col-md-6">
                                    <label for="closure-batch-company" class="form-label text-uppercase small fw-bold">Empresa de destino</label>
                                    <select id="closure-batch-company" class="form-select" wire:model="managerCompanyId">
                                        <option value="">Selecione</option>
                                        @foreach ($this->destinationCompanies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="closure-batch-user" class="form-label text-uppercase small fw-bold">Usuário de destino {{ $batchAction === 'assign' ? '' : '(opcional)' }}</label>
                                    <select id="closure-batch-user" class="form-select" wire:model="managerAssigneeId" @disabled(!$managerCompanyId)>
                                        <option value="">{{ $batchAction === 'assign' ? 'Selecione' : 'Enviar para a pilha' }}</option>
                                        @foreach ($this->destinationUsers as $destinationUser)
                                            <option value="{{ $destinationUser->id }}">{{ $destinationUser->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if ($batchAction === 'change_status')
                                <div class="col-md-6">
                                    <label for="closure-batch-status" class="form-label text-uppercase small fw-bold">Novo status</label>
                                    <select id="closure-batch-status" class="form-select" wire:model="batchStatus">
                                        <option value="">Selecione</option>
                                        <option value="PAUSED">Pausa</option>
                                        <option value="DEPOSIT_BLOCKED">Depósito Bloqueado</option>
                                        <option value="DEPOSIT_RELEASED">Depósito Liberado</option>
                                        <option value="IN_PROGRESS">Em andamento</option>
                                    </select>
                                </div>
                            @endif
                            @if ($batchAction === 'set_priority')
                                <div class="col-md-6">
                                    <label for="closure-batch-priority" class="form-label text-uppercase small fw-bold">Prioridade</label>
                                    <select id="closure-batch-priority" class="form-select" wire:model="managerPriority">
                                        <option value="1">Alta</option>
                                        <option value="0">Normal</option>
                                    </select>
                                </div>
                            @endif
                            @if ($reason)
                                <div class="col-12">
                                    <label for="closure-batch-reason" class="form-label text-uppercase small fw-bold">Motivo *</label>
                                    <textarea id="closure-batch-reason" class="form-control" rows="2" wire:model.defer="batchReason" placeholder="Explique o motivo. Fica registrado na linha do tempo de cada Ordem."></textarea>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    @if ($hasRcpt)
                        <button type="button" class="btn btn-primary" wire:click="closeBatch">Fechar</button>
                    @elseif ($hasPrev)
                        <button type="button" class="btn btn-outline-secondary me-auto" wire:click="editBatch"><i class="ri-arrow-left-line" aria-hidden="true"></i> Voltar</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeBatch">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="confirmBatchAction" wire:loading.attr="disabled" wire:target="confirmBatchAction" @disabled(count($batchPreview['eligible']) === 0)><i class="ri-check-line" aria-hidden="true"></i> Confirmar ({{ count($batchPreview['eligible']) }})</button>
                    @else
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeBatch">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="prepareBatchPreview" wire:loading.attr="disabled" wire:target="prepareBatchPreview"><i class="ri-eye-line" aria-hidden="true"></i> Ver prévia</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop show"></div>
@endif
