<div>
    @if ($show)
        <div class="cpop-overlay" wire:click.self="close" @keydown.escape.window="$wire.close()">
            <div class="cpop cpop-md tone-blue" role="dialog" aria-modal="true" aria-label="Transferir para outro usuário">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-exchange-line"></i></span>
                    <div>
                        <h3 class="cpop-title">Transferir para outro usuário</h3>
                        <div class="cpop-sub">{{ $states->count() }} Ordem(ns). Quem receber aceita ou rejeita; você pode cancelar enquanto estiver pendente.</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="close"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="mb-3 small">
                        @foreach ($states as $state)
                            <span class="badge bg-light text-dark border me-1 mb-1" wire:key="send-state-{{ $state->id }}">{{ $state->Order?->ordem }} <span class="text-muted">· Nota {{ $state->Order?->Note?->note }}</span></span>
                        @endforeach
                    </div>

                    <label for="transfer-user-search" class="cpop-label">Quem vai receber *</label>
                    <input id="transfer-user-search" type="search" class="form-control form-control-sm mb-2" wire:model.debounce.300ms="userSearch" placeholder="Buscar usuário do Encerramento…">
                    <select class="form-select mb-1" wire:model="toUserId" aria-label="Destinatário" size="{{ min(max($users->count(), 2), 6) }}">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" wire:key="send-user-{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('toUserId') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                    <label for="transfer-reason" class="cpop-label mt-3">Motivo *</label>
                    <textarea id="transfer-reason" class="form-control" rows="3" wire:model.defer="reason" placeholder="Por que está passando estas Ordens?"></textarea>
                    @error('reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('send') <div class="alert alert-warning mt-3 mb-0 py-2 small">{{ $message }}</div> @enderror
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary" wire:click="close">Cancelar</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="send" wire:loading.attr="disabled" wire:target="send"><i class="ri-send-plane-line"></i> Enviar transferência</button>
                </div>
            </div>
        </div>
    @endif
</div>
