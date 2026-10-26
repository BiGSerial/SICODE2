<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div>
            <h2 class="dash-title">Meu histórico — Encerramento</h2>
            <div class="dash-subtitle">Ordens suas já finalizadas, aguardando mês da meta, encerradas ou canceladas. O tipo mostrado é o congelado no encerramento.</div>
        </div>
    </div>

    <div class="table-card filter-bar p-3 mb-3">
        <x-closure.bulk-search-result :report="$this->bulkReport()" class="mb-3" />
        <div class="row g-3 align-items-end">
            @include('livewire.closure.work._filters')
            <div class="col-12 col-md-3">
                <label for="closure-history-status" class="form-label fw-semibold">Situação</label>
                <select id="closure-history-status" class="form-select" wire:model="status">
                    <option value="all">Todas</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">Limpar filtros</button>
                <button type="button" class="btn btn-outline-success btn-sm" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        @include('livewire.closure.work._order-head')
                        <th>Situação</th>
                        <th>Desde</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @php $informValues = $this->informValues($states); @endphp
@forelse ($states as $state)
                        @php $signal = \App\Services\Closure\ClosureSignal::forState($state); @endphp
                        <tr wire:key="closure-history-{{ $state->id }}" @class(['signal-row signal-' . ($signal['key'] ?? '') => $signal !== null]) @if ($signal) data-signal="{{ $signal['key'] }}" title="{{ $signal['label'] }}: {{ $signal['hint'] }}" @endif>
                            @include('livewire.closure.work._order-cells')
                            <td><span class="badge {{ \App\Enum\ClosureOperationalStatus::badgeClassFor($state->operational_status) }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($state->operational_status) }}</span>
</td>
                            <td class="small text-muted">{{ $state->entered_current_status_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="pe-3 text-end"><a href="{{ route('closure.order.detail', ['order' => $state->order_id, 'ctx' => 'service']) }}" class="btn btn-outline-dark btn-sm">Ordem</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">Nenhuma Ordem no seu histórico.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $states->links() }}</div>
    </div>
</div>
