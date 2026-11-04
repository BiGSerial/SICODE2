<div class="dropdown ops-row-actions">
    <button type="button" class="btn btn-sm btn-outline-secondary ops-menu-btn" data-bs-toggle="dropdown" data-closure-fixed-dropdown aria-expanded="false" aria-label="Ações da Ordem {{ $state->Order?->ordem }}" title="Mais ações da Ordem"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
    <ul class="dropdown-menu dropdown-menu-end closure-actions-menu">
        @php
            $statusIcons = ['IN_PROGRESS' => 'ri-play-line text-success', 'PAUSED' => 'ri-pause-circle-line text-secondary', 'DEPOSIT_BLOCKED' => 'ri-lock-line text-danger', 'DEPOSIT_RELEASED' => 'ri-lock-unlock-line text-success'];
            $activeRi    = ($riByNote ?? collect())->get($state->Order?->note_id);
        @endphp
        @if ($controlMode ?? false)
            @if ($this->canManage($state, 'reassign'))
                <li><button type="button" class="dropdown-item" wire:click="openManage({{ $state->id }}, 'reassign')"><i class="ri-user-shared-line me-2"></i>Trocar responsável</button></li>
            @endif
            @if ($this->canManage($state, 'unassign'))
                <li><button type="button" class="dropdown-item" wire:click="openManage({{ $state->id }}, 'unassign')"><i class="ri-user-unfollow-line me-2"></i>Desatribuir</button></li>
            @endif
            @if ($this->canManage($state, 'remove'))
                <li><button type="button" class="dropdown-item text-danger" wire:click="openManage({{ $state->id }}, 'remove')"><i class="ri-arrow-go-back-line me-2"></i>Remover (devolver à pilha)</button></li>
            @endif
        @elseif ($state->operational_status === 'CLOSED')
            {{-- Encerrada que o SAP reabriu: reabrir a atividade (mesmo estado e auditoria), sem novo despacho. --}}
            @if ($this->canReopen($state))
                <li><button type="button" class="dropdown-item fw-semibold" wire:click="reopenActivity({{ $state->id }})"><i class="ri-refresh-line me-2 text-success"></i>Reabrir atividade</button></li>
            @endif
        @elseif ($activeRi)
            {{-- Obra com RI ativo: todas as Ordens estão travadas; só resta acompanhar (e a gestão pode cancelar o RI). --}}
            <li><span class="dropdown-item disabled" title="Enviado em {{ $activeRi->sent_at?->format('d/m/Y H:i') }}. Destrava quando o Desenho concluir."><i class="ri-lock-line me-2 text-danger"></i>Obra travada: RI com o Desenho</span></li>
            @can('closure.manage')
                <li><button type="button" class="dropdown-item" wire:click="cancelRi({{ $activeRi->id }})"><i class="ri-lock-unlock-line me-2 text-warning"></i>Cancelar RI…</button></li>
            @endcan
        @else
            @php
                $blocked    = $this->finishBlockedReason($state);
                $canStart   = $this->canRun($state, 'start');
                $canFinish  = $this->canRun($state, 'finish');
                // Menu dinâmico: "Iniciar" já leva a Em andamento (Atribuída), então "Voltar a Em andamento" só existe a partir de pausa/finalizada/etc.
                $targets    = $this->canChangeStatus($state) ? array_values(array_diff($this->statusTargets($state), ['WAITING_PARTNER'], $canStart ? ['IN_PROGRESS'] : [])) : [];
                // Devolver / pedir correção só existe para Medição e Fiscalização (caixas de área); um clique abre o formulário já preenchido.
                $canReturn  = $this->canSendRi($state) && $state->Order?->note_id;
                $canMove    = $this->canTransfer($state);
            @endphp
            @if ($canStart)
                <li><button type="button" class="dropdown-item" wire:click="runAction({{ $state->id }}, 'start')"><i class="ri-play-circle-line me-2 text-success"></i>Iniciar</button></li>
            @endif
            @if ($canFinish)
                <li>
                    @if ($blocked)
                        <span class="dropdown-item disabled" title="{{ $blocked }}"><i class="ri-checkbox-circle-line me-2"></i>Finalizar (indisponível)</span>
                    @else
                        <button type="button" class="dropdown-item" wire:click="runAction({{ $state->id }}, 'finish')"><i class="ri-checkbox-circle-line me-2 text-success"></i>Finalizar</button>
                    @endif
                </li>
            @endif
            @foreach ($targets as $target)
                <li><button type="button" class="dropdown-item" wire:click="changeStatus({{ $state->id }}, '{{ $target }}')"><i class="{{ $statusIcons[$target] ?? 'ri-arrow-right-line' }} me-2"></i>{{ $this->statusActionLabel($target) }}</button></li>
            @endforeach
            @if ($canReturn)
                @foreach (\App\Services\Closure\ClosureArea::inboxOptions() as $areaKey => $areaLabel)
                    <li><a class="dropdown-item text-danger" href="{{ route('closure.work.show', ['note' => $state->Order->note_id, 'ctx' => 'service', 'devolver' => $areaKey, 'estado' => $state->id]) }}"><i class="ri-arrow-go-back-line me-2"></i>Devolver à {{ $areaLabel }}…</a></li>
                @endforeach
            @endif
            @if ($canMove)
                <li><button type="button" class="dropdown-item" wire:click="transferState({{ $state->id }})"><i class="ri-exchange-line me-2 text-primary"></i>Transferir…</button></li>
            @endif
        @endif
        <li><hr class="dropdown-divider"></li>
        @if ($state->Order?->note_id)
            <li><a class="dropdown-item" href="{{ route('closure.work.show', ['note' => $state->Order->note_id, 'ctx' => ($controlMode ?? false) ? 'dispatch' : 'service']) }}"><i class="ri-building-line me-2"></i>Abrir obra</a></li>
        @endif
        <li><a class="dropdown-item" href="{{ route('closure.order.detail', ['order' => $state->order_id, 'ctx' => ($controlMode ?? false) ? 'dispatch' : 'service']) }}"><i class="ri-file-list-3-line me-2"></i>Abrir ordem</a></li>
    </ul>
</div>
