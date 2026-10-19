    {{-- Transferência de responsabilidade --}}
    @if ($transferNoteId)
        <div class="table-card mb-3 border-primary" id="closure-transfer-form">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Transferir responsabilidade</h3>
                    <div class="chart-subtitle">Todas as Ordens transferíveis da obra vão para o usuário escolhido ({{ $transferStates->count() }} Ordem(ns)).</div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="cancelTransfer">Cancelar</button>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Usuário de destino</label>
                        <select class="form-select form-select-sm" wire:model="transferUserId">
                            <option value="">Selecione...</option>
                            @foreach ($transferUsers as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label small mb-1">Motivo (obrigatório)</label>
                        <input type="text" class="form-control form-control-sm" wire:model.defer="transferReason" maxlength="500">
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm btn-dash w-100" wire:click="transfer" wire:loading.attr="disabled">Confirmar transferência</button>
                    </div>
                </div>
                <div class="small text-muted mt-2">Ordens: {{ $transferStates->map(fn ($s) => $s->Order->ordem)->implode(', ') ?: 'nenhuma transferível (Ordens aguardando parceira ficam de fora)' }}</div>
            </div>
        </div>
    @endif
