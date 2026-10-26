

@if ($opsState)
    @php
        $opsTotal = $opsList->count();
        $opsConf  = $opsList->filter(fn ($op) => $this->isConfirmedOperation($op->status))->count();
        $opsPct   = $opsTotal ? round($opsConf / $opsTotal * 100) : 0;
    @endphp
    <div class="cpop-overlay" wire:click.self="closeOperations" x-data @keydown.escape.window="$wire.closeOperations()">
        <div class="cpop" style="max-width: 520px; --tone: #155f67;" role="dialog" aria-modal="true" aria-label="Operações da Ordem {{ $opsState->Order?->ordem }}">
            <div class="cpop-head">
                <span class="cpop-icon"><i class="ri-list-check-2"></i></span>
                <div class="flex-grow-1">
                    <h3 class="cpop-title">Operações da Ordem {{ $opsState->Order?->ordem }}</h3>
                    <div class="cpop-sub">Nota {{ $opsState->Order?->Note?->note ?? '—' }} · {{ $opsConf }} de {{ $opsTotal }} confirmadas</div>
                    <div class="cpop-progress" aria-hidden="true"><span style="width: {{ $opsPct }}%;"></span></div>
                </div>
                <button type="button" class="cpop-close" aria-label="Fechar" wire:click="closeOperations"><i class="ri-close-line"></i></button>
            </div>
            <div class="cpop-body">
                <ul class="cpop-ops">
                    @forelse ($opsList as $op)
                        @php $done = $this->isConfirmedOperation($op->status); @endphp
                        <li wire:key="cpop-op-{{ $op->id }}">
                            <i class="{{ $done ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-muted' }}" aria-hidden="true"></i>
                            <span class="cpop-op-code">{{ $op->operacao }}</span>
                            <span class="cpop-op-desc text-truncate" title="{{ $op->descOperacao }}">{{ $op->descOperacao ?: '—' }}</span>
                            <span class="badge {{ $done ? 'bg-success' : 'bg-light text-dark border' }}">{{ $op->status ?: '—' }}</span>
                        </li>
                    @empty
                        <li class="text-muted justify-content-center py-4">Esta Ordem não possui operações.</li>
                    @endforelse
                </ul>
            </div>
            <div class="cpop-foot">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="closeOperations">Fechar</button>
            </div>
        </div>
    </div>
@endif

@if ($manageState ?? null)
    @php
        $cfg = [
            'reassign' => ['Trocar responsável', 'ri-user-shared-line', '#155f67', 'A Ordem passa para o novo responsável. O motivo fica registrado na linha do tempo.', 'Confirmar troca'],
            'unassign' => ['Desatribuir', 'ri-user-unfollow-line', '#b45309', 'A Ordem continua com a empresa atual, sem responsável, e volta a ficar disponível.', 'Desatribuir'],
            'remove'   => ['Remover da fila', 'ri-arrow-go-back-line', '#c0392b', 'A Ordem é devolvida à pilha da empresa de origem.', 'Remover'],
        ][$manageMode] ?? ['Ação', 'ri-settings-3-line', '#155f67', '', 'Confirmar'];
    @endphp
    <div class="cpop-overlay" wire:click.self="cancelManage" x-data @keydown.escape.window="$wire.cancelManage()">
        <div class="cpop" style="max-width: 500px; --tone: {{ $cfg[2] }};" role="dialog" aria-modal="true" aria-label="{{ $cfg[0] }}">
            <div class="cpop-head">
                <span class="cpop-icon"><i class="{{ $cfg[1] }}"></i></span>
                <div>
                    <h3 class="cpop-title">{{ $cfg[0] }}</h3>
                    <div class="cpop-sub">Ordem {{ $manageState->Order?->ordem }}</div>
                </div>
                <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelManage"><i class="ri-close-line"></i></button>
            </div>
            <div class="cpop-body">
                <dl class="cpop-meta">
                    <div><dt>Nota</dt><dd>{{ $manageState->Order?->Note?->note ?? '—' }}</dd></div>
                    <div><dt>Status</dt><dd>{{ \App\Enum\ClosureOperationalStatus::labelFor($manageState->operational_status) }}</dd></div>
                    <div><dt>Empresa atual</dt><dd>{{ $manageState->CurrentHolderCompany?->name ?? '—' }}</dd></div>
                    <div><dt>Responsável atual</dt><dd>{{ $manageState->CurrentHolderUser?->name ?? 'Sem responsável' }}</dd></div>
                </dl>

                <div class="cpop-hint">{{ $cfg[3] }}</div>

                @if ($manageMode === 'reassign')
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="closure-manage-company" class="cpop-label">Empresa</label>
                            <select id="closure-manage-company" class="form-select" wire:model="managerCompanyId">
                                <option value="">Selecione</option>
                                @foreach ($this->destinationCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="closure-manage-user" class="cpop-label">Novo responsável</label>
                            <select id="closure-manage-user" class="form-select" wire:model="managerAssigneeId" @disabled(!$managerCompanyId)>
                                <option value="">Selecione</option>
                                @foreach ($this->destinationUsers as $destinationUser)
                                    <option value="{{ $destinationUser->id }}">{{ $destinationUser->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <label for="closure-manage-reason" class="cpop-label">Motivo <span class="text-danger">*</span></label>
                <textarea id="closure-manage-reason" class="form-control" rows="3" wire:model.defer="manageReason" placeholder="Descreva o motivo de forma objetiva."></textarea>
            </div>
            <div class="cpop-foot">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelManage">Cancelar</button>
                <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background: {{ $cfg[2] }};" wire:click="confirmManage" wire:loading.attr="disabled" wire:target="confirmManage">{{ $cfg[4] }}</button>
            </div>
        </div>
    </div>
@endif

@if (($pendingState ?? null) && $pendingAction)
    @php
        $isReturn  = $pendingAction === 'return';
        $tone      = $isReturn ? '#c0392b' : '#b45309';
        $pendTitle = $isReturn ? 'Devolver à pilha' : 'Alterar status — ' . \App\Enum\ClosureOperationalStatus::labelFor($pendingStatus);
    @endphp
    <div class="cpop-overlay" wire:click.self="cancelActionWithReason" x-data @keydown.escape.window="$wire.cancelActionWithReason()">
        <div class="cpop" style="max-width: 480px; --tone: {{ $tone }};" role="dialog" aria-modal="true" aria-label="{{ $pendTitle }}">
            <div class="cpop-head">
                <span class="cpop-icon"><i class="{{ $isReturn ? 'ri-arrow-go-back-line' : 'ri-pause-circle-line' }}"></i></span>
                <div>
                    <h3 class="cpop-title">{{ $pendTitle }}</h3>
                    <div class="cpop-sub">Ordem {{ $pendingState->Order?->ordem }} · Nota {{ $pendingState->Order?->Note?->note ?? '—' }}</div>
                </div>
                <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelActionWithReason"><i class="ri-close-line"></i></button>
            </div>
            <div class="cpop-body">
                <div class="cpop-hint">{{ $isReturn ? 'A Ordem volta para a pilha e fica disponível para outro responsável.' : 'O novo status passa a valer assim que você confirmar.' }} O motivo fica visível na linha do tempo da Ordem.</div>
                <label for="closure-work-action-reason" class="cpop-label">Motivo <span class="text-danger">*</span></label>
                <textarea id="closure-work-action-reason" class="form-control" rows="3" wire:model.defer="actionReason" placeholder="Descreva o motivo de forma objetiva." autofocus></textarea>
            </div>
            <div class="cpop-foot">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelActionWithReason">Cancelar</button>
                <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background: {{ $tone }};" wire:click="confirmActionWithReason" wire:loading.attr="disabled" wire:target="confirmActionWithReason">Confirmar</button>
            </div>
        </div>
    </div>
@endif

@if ($partnerState ?? null)
    @php $subs = $issueCategories->firstWhere('id', (int) $issueCategoryId)?->Subcategories ?? collect(); @endphp
    <div class="cpop-overlay" wire:click.self="cancelPartnerSend" x-data @keydown.escape.window="$wire.cancelPartnerSend()">
        <div class="cpop" style="max-width: 760px; --tone: #263cc8;" role="dialog" aria-modal="true" aria-label="Enviar à parceira">
            <div class="cpop-head">
                <span class="cpop-icon"><i class="ri-send-plane-2-line"></i></span>
                <div>
                    <h3 class="cpop-title">Enviar à parceira</h3>
                    <div class="cpop-sub">Ordem {{ $partnerState->Order?->ordem }} · Nota {{ $partnerState->Order?->Note?->note ?? '—' }}</div>
                </div>
                <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelPartnerSend"><i class="ri-close-line"></i></button>
            </div>
            <div class="cpop-body">
                <div class="cpop-hint">Descreva o que a parceira precisa resolver. A Ordem fica em <strong>Aguardando Parceira</strong> até ela devolver a solução, e você valida o retorno.</div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="issue-company" class="cpop-label">Parceira <span class="text-danger">*</span></label>
                        <select id="issue-company" class="form-select @error('issueCompanyId') is-invalid @enderror" wire:model.defer="issueCompanyId">
                            <option value="">Selecione</option>
                            @foreach ($this->partnerCompanies as $company)
                                <option value="{{ $company->id }}">{{ $company->display_name }}</option>
                            @endforeach
                        </select>
                        @error('issueCompanyId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="issue-category" class="cpop-label">Categoria <span class="text-danger">*</span></label>
                        <select id="issue-category" class="form-select @error('issueCategoryId') is-invalid @enderror" wire:model="issueCategoryId">
                            <option value="">Selecione</option>
                            @foreach ($issueCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('issueCategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="issue-subcategory" class="cpop-label">Subcategoria <span class="text-danger">*</span></label>
                        <select id="issue-subcategory" class="form-select @error('issueSubcategoryId') is-invalid @enderror" wire:model.defer="issueSubcategoryId" @disabled(!$issueCategoryId)>
                            <option value="">Selecione</option>
                            @foreach ($subs as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}{{ $sub->requires_description ? ' (exige descrição)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('issueSubcategoryId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label for="issue-description" class="cpop-label">Descrição</label>
                        <textarea id="issue-description" class="form-control @error('issueDescription') is-invalid @enderror" rows="6" maxlength="2000" wire:model.defer="issueDescription" placeholder="Detalhe a pendência para a parceira."></textarea>
                        @error('issueDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="cpop-foot">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelPartnerSend">Cancelar</button>
                <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background: #263cc8;" wire:click="sendToPartner" wire:loading.attr="disabled" wire:target="sendToPartner"><i class="ri-send-plane-line"></i> Enviar à parceira</button>
            </div>
        </div>
    </div>
@endif
