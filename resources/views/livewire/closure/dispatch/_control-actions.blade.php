<div class="dropdown">
    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-closure-fixed-dropdown aria-expanded="false" aria-label="Ações da Ordem {{ $state->Order?->ordem }}">Ações</button>
    <ul class="dropdown-menu dropdown-menu-end closure-actions-menu">
        @php
            $statusIcons = ['IN_PROGRESS' => 'ri-play-line text-success', 'PAUSED' => 'ri-pause-circle-line text-secondary', 'DEPOSIT_BLOCKED' => 'ri-lock-line text-danger', 'DEPOSIT_RELEASED' => 'ri-lock-unlock-line text-success'];
            $activeRi    = ($riByNote ?? collect())->get($state->Order?->note_id);
        @endphp
        @if ($controlMode ?? false)
            @if ($this->canManage($state, 'reassign') || $this->canManage($state, 'unassign') || $this->canManage($state, 'remove'))
                <li><h6 class="dropdown-header">Gerir</h6></li>
            @endif
            @if ($this->canManage($state, 'reassign'))
                <li><button type="button" class="dropdown-item" wire:click="openManage({{ $state->id }}, 'reassign')"><i class="ri-user-shared-line me-2"></i>Trocar responsável</button></li>
            @endif
            @if ($this->canManage($state, 'unassign'))
                <li><button type="button" class="dropdown-item" wire:click="openManage({{ $state->id }}, 'unassign')"><i class="ri-user-unfollow-line me-2"></i>Desatribuir</button></li>
            @endif
            @if ($this->canManage($state, 'remove'))
                <li><button type="button" class="dropdown-item text-danger" wire:click="openManage({{ $state->id }}, 'remove')"><i class="ri-arrow-go-back-line me-2"></i>Remover (devolver à pilha)</button></li>
            @endif
        @elseif ($activeRi)
            {{-- Obra com RI ativo: todas as Ordens estão travadas; só resta acompanhar (e a gestão pode cancelar o RI). --}}
            <li><h6 class="dropdown-header">Obra travada</h6></li>
            <li><span class="dropdown-item disabled"><i class="ri-lock-line me-2 text-danger"></i>RI com o Desenho em andamento <small class="d-block text-muted">Enviado {{ $activeRi->sent_at?->format('d/m/Y H:i') }}. Destrava quando o Desenho concluir.</small></span></li>
            @can('closure.manage')
                <li><button type="button" class="dropdown-item" wire:click="cancelRi({{ $activeRi->id }})"><i class="ri-lock-unlock-line me-2 text-warning"></i>Cancelar RI… <small class="d-block text-muted">Só destrava as Ordens; o Desenho segue com o retorno.</small></button></li>
            @endcan
        @else
            @php
                $blocked   = $this->finishBlockedReason($state);
                $canStart  = $this->canRun($state, 'start');
                $canFinish = $this->canRun($state, 'finish');
                $targets   = $this->canChangeStatus($state) ? array_values(array_diff($this->statusTargets($state), ['WAITING_PARTNER'])) : [];
                $canPartner = $this->canChangeStatus($state) && in_array('WAITING_PARTNER', $this->statusTargets($state), true);
                $canRi     = $this->canSendRi($state);
                $canReturn = false; // o executor não devolve à pilha: devolver é pela parceira, pelo RI ou pela transferência
                $canMove   = $this->canTransfer($state);
            @endphp
            @if ($canStart || $canFinish)
                <li><h6 class="dropdown-header">Trabalhar a Ordem</h6></li>
            @endif
            @if ($canStart)
                <li><button type="button" class="dropdown-item" wire:click="runAction({{ $state->id }}, 'start')"><i class="ri-play-circle-line me-2 text-success"></i>Iniciar</button></li>
            @endif
            @if ($canFinish)
                <li>
                    @if ($blocked)
                        <span class="dropdown-item disabled" title="{{ $blocked }}"><i class="ri-checkbox-circle-line me-2"></i>Finalizar <small class="d-block text-muted">{{ $blocked }}</small></span>
                    @else
                        <button type="button" class="dropdown-item" wire:click="runAction({{ $state->id }}, 'finish')"><i class="ri-checkbox-circle-line me-2 text-success"></i>Finalizar</button>
                    @endif
                </li>
            @endif
            @if ($targets !== [])
                <li><h6 class="dropdown-header">Alterar o status</h6></li>
                @foreach ($targets as $target)
                    <li><button type="button" class="dropdown-item" wire:click="changeStatus({{ $state->id }}, '{{ $target }}')"><i class="{{ $statusIcons[$target] ?? 'ri-arrow-right-line' }} me-2"></i>{{ $this->statusActionLabel($target) }}</button></li>
                @endforeach
            @endif
            @if ($canPartner || $canRi)
                <li><h6 class="dropdown-header">Devolver / pedir correção</h6></li>
            @endif
            @if ($canPartner)
                <li><button type="button" class="dropdown-item" wire:click="changeStatus({{ $state->id }}, 'WAITING_PARTNER')"><i class="ri-team-line me-2 text-primary"></i>Enviar à parceira… <small class="d-block text-muted">Pendência para a empresa parceira responder.</small></button></li>
            @endif
            @if ($canRi)
                <li><button type="button" class="dropdown-item" wire:click="sendRi({{ $state->id }})"><i class="ri-draft-line me-2 text-danger"></i>Enviar RI ao Desenho… <small class="d-block text-muted">Retorno interno da obra inteira; trava as Ordens até o Desenho concluir.</small></button></li>
            @endif
            @if ($canMove)
                <li><h6 class="dropdown-header">Passar para outra pessoa</h6></li>
                <li><button type="button" class="dropdown-item" wire:click="transferState({{ $state->id }})"><i class="ri-exchange-line me-2 text-primary"></i>Transferir para outro usuário… <small class="d-block text-muted">Ele aceita ou rejeita.</small></button></li>
            @endif
        @endif
        <li><h6 class="dropdown-header">Abrir</h6></li>
        @if ($state->Order?->note_id)
            <li><a class="dropdown-item" href="{{ route('closure.work.show', ['note' => $state->Order->note_id, 'ctx' => ($controlMode ?? false) ? 'dispatch' : 'service']) }}"><i class="ri-building-line me-2"></i>Abrir obra</a></li>
        @endif
        <li><a class="dropdown-item" href="{{ route('closure.order.detail', ['order' => $state->order_id, 'ctx' => ($controlMode ?? false) ? 'dispatch' : 'service']) }}"><i class="ri-file-list-3-line me-2"></i>Abrir ordem</a></li>
    </ul>
</div>

@once
    <script>
        // O menu fica em posição fixa para não ser cortado pelo overflow da tabela (a última linha ficava escondida).
        if (!window.__closureFixedDropdown) {
            window.__closureFixedDropdown = true;
            document.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-closure-fixed-dropdown]');
                if (toggle && window.bootstrap && !window.bootstrap.Dropdown.getInstance(toggle)) {
                    window.bootstrap.Dropdown.getOrCreateInstance(toggle, { popperConfig: (defaults) => ({ ...defaults, strategy: 'fixed', modifiers: [...(defaults.modifiers || []), { name: 'flip', options: { fallbackPlacements: ['top-end', 'bottom-end', 'top-start'] } }, { name: 'preventOverflow', options: { padding: 8 } }] }) });
                }
            }, true);
        }
    </script>
@endonce
