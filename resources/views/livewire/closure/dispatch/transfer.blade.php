<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Transferência de Encerramento</h2>
                <div class="dash-subtitle">Pedidos de prioridade pendentes e transferência de responsabilidade por obra.</div>
            </div>
            <span class="badge bg-light text-dark px-3 py-2">{{ $priorities->count() }} pedido(s) pendente(s)</span>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert" id="closure-dash-feedback">{{ $feedbackMessage }}</div>
    @endif

    @can('closure.work')
        <div class="mb-3">@livewire('closure.transfers.center', [], key('closure-transfers-center'))</div>
    @endcan

    @include('livewire.closure.dispatch._transfer-receipt')

    @include('livewire.closure.dispatch._priority-requests')

    <div class="table-card p-3 mb-3" id="closure-transfer-search">
        <h3 class="chart-title mb-2">Reatribuir uma obra (gestão)</h3>
        <div class="small text-muted mb-2">Atalho da gestão: move direto, sem aceite. Entre usuários, use Transferir em Minha atividade.</div>
        <label for="closure-transfer-note" class="form-label small mb-1">Buscar Nota</label>
        <input id="closure-transfer-note" type="search" class="form-control form-control-sm" style="max-width: 320px" wire:model.debounce.400ms="noteSearch" placeholder="Número da Nota">
        @if (trim($noteSearch) !== '')
            <div class="mt-2">
                @forelse ($notes as $note)
                    <button type="button" class="btn btn-outline-dark btn-sm btn-dash me-1 mb-1" wire:key="transfer-note-{{ $note->id }}" wire:click="openTransfer({{ $note->id }})">Transferir {{ $note->note }}</button>
                @empty
                    <span class="small text-muted">Nenhuma Nota com Ordens de Encerramento encontrada.</span>
                @endforelse
            </div>
        @endif
    </div>

    <div class="table-card mb-3" id="closure-critical-works">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Obras críticas</h3>
                <div class="chart-subtitle">Ordens P paradas ou pendência sem resposta há mais de {{ $staleDays }} dias</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small mb-0" for="staleDays">Dias</label>
                <input id="staleDays" type="number" min="1" max="365" class="form-control form-control-sm" style="width: 80px" wire:model.lazy="staleDays">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th class="ps-3">Obra</th><th>Ordens abertas</th><th>P</th><th>P paradas</th><th>Mais antiga</th><th class="pe-3">Ações</th></tr></thead>
                <tbody>
                    @forelse ($critical as $work)
                        <tr wire:key="critical-{{ $work['note_id'] }}">
                            <td class="ps-3 fw-semibold">{{ $work['note'] }}</td>
                            <td>{{ $work['open_orders'] }}</td>
                            <td>{{ $work['passive_orders'] }}</td>
                            <td>{{ $work['stale_passive'] }}</td>
                            <td>{{ $work['oldest_days'] !== null ? $work['oldest_days'] . ' d' : '—' }}</td>
                            <td class="pe-3">
                                <a class="row-action" href="{{ route('closure.work.show', ['note' => $work['note_id'], 'ctx' => 'dispatch']) }}">Abrir obra</a>
                                <button type="button" class="btn btn-outline-dark btn-sm btn-dash" wire:click="openTransfer({{ $work['note_id'] }})">Transferir</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted py-4 text-center">Nenhuma obra crítica.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('livewire.closure.dispatch._transfer-form')

    @include('livewire.closure.dispatch._decide-script')
</div>
