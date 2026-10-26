<div class="closure-dash">
    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Lista Encerramento</h2>
                <div class="dash-subtitle">Todas as Ordens do Encerramento, despachadas ou não. Assumir depende de o despacho ter liberado a auto-atribuição.</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if ($enabled)
                    <span class="badge bg-light text-dark px-3 py-2" title="Tipos liberados pelo despacho">Auto-atribuição liberada ({{ implode('/', $allowedTypes) }})</span>
                @else
                    <span class="badge bg-light text-dark px-3 py-2" title="O despacho precisa liberar em Configurações do Encerramento"><i class="ri-lock-line"></i> Auto-atribuição não liberada</span>
                @endif
                <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedOrderIds) }} selecionada(s)</span>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><x-closure.stat-card label="Total" :value="number_format($counts['total'], 0, ',', '.')" tone="blue" icon="ri-list-check" :active="$scope === 'all'" wire:click="$set('scope', 'all')" /></div>
        <div class="col-6 col-md-3"><x-closure.stat-card label="Sem posse" :value="number_format($counts['undispatched'], 0, ',', '.')" tone="amber" icon="ri-inbox-line" note="não despachadas" :active="$scope === 'free'" wire:click="$set('scope', 'free')" /></div>
        <div class="col-6 col-md-3"><x-closure.stat-card label="Minhas" :value="$scope === 'mine' ? number_format($orders->total(), 0, ',', '.') : '—'" tone="teal" icon="ri-user-line" note="atribuídas a mim" :active="$scope === 'mine'" wire:click="$set('scope', 'mine')" /></div>
    </div>

    <div class="table-card filter-bar p-3 mb-3">
        <x-closure.bulk-search-result :report="$this->bulkReport()" class="mb-3" />
        <div class="row g-3 align-items-end">
            @include('livewire.closure.work._filters')
            <div class="col-12 col-md-6 d-flex flex-wrap align-items-center gap-2 justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">Limpar filtros</button>
                <button type="button" class="btn btn-outline-success btn-sm" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens</h3>
                <div class="chart-subtitle">{{ number_format($orders->total(), 0, ',', '.') }} Ordem(ns). Só é possível marcar as que você pode assumir; nas demais, passe o mouse para ver o motivo.</div>
            </div>
            <button type="button" class="btn btn-dash btn-sm text-white" style="background: var(--dash-green);" wire:click="askAssume" @disabled(count($selectedOrderIds) === 0)>Assumir selecionadas</button>
        </div>
        <x-closure.signal-legend />
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 dash-table">
                <thead>
                    <tr>
                        <th class="ps-3"><span class="visually-hidden">Selecionar</span></th>
                        <th class="text-center">Tipo</th>
                        <th>Nota</th>
                        <th>Ordem</th>
                        <th>Compet.</th>
                        <th>Obra / Município</th>
                        <th class="text-end" title="Valor da ADS do informe a que a Ordem pertence">Valor da obra (ADS)</th>
                        <th>Status SAP</th>
                        <th>Status da atividade</th>
                        <th>Responsável</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            [$can, $why] = $this->assumability($order);
                            $sig         = \App\Services\Closure\ClosureSignal::forRow($order);
                            $rowClass    = $sig ? 'signal-row signal-' . $sig['key'] : '';
                        @endphp
                        <tr wire:key="closure-all-{{ $order->id }}" @class([$rowClass => $rowClass !== '']) @if ($sig) data-signal="{{ $sig['key'] }}" title="{{ $sig['label'] }}: {{ $sig['hint'] }}" @endif data-ordem="{{ $order->ordem }}">
                            <td class="ps-3">
                                @if ($can)
                                    <input type="checkbox" class="form-check-input" @checked(in_array($order->id, $selectedOrderIds, true)) wire:click="toggleOrder({{ $order->id }})" aria-label="Selecionar Ordem {{ $order->ordem }}">
                                @else
                                    <input type="checkbox" class="form-check-input" disabled title="{{ $why }}" aria-label="Ordem {{ $order->ordem }}: {{ $why }}">
                                @endif
                            </td>
                            <td class="text-center"><span class="type-dot badge {{ \App\Services\Closure\ClosureMetaClassifier::badgeClass($order->meta_type) }}" title="{{ \App\Services\Closure\ClosureMetaClassifier::label($order->meta_type) }}">{{ \App\Services\Closure\ClosureMetaClassifier::letter($order->meta_type) ?? '—' }}</span></td>
                            <td>{{ $order->note_number }}@if ($order->partial_id) <span class="badge bg-light text-dark border" title="Parcial #{{ $order->partial_id }}">Parcial</span>@endif</td>
                            <td class="fw-semibold">{{ $order->ordem }}</td>
                            <td class="small text-nowrap">{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($order->competence) }}</td>
                            <td class="small">
                                <div class="text-truncate" title="{{ $order->descricao }}">{{ $order->descricao ?: '—' }}</div>
                                <div class="text-muted text-truncate">{{ $order->city_name ?: '—' }}</div>
                            </td>
                            <td class="small text-end text-nowrap" title="Valor da ADS do informe a que a Ordem pertence">{{ \App\Services\Closure\ClosureInformValue::format($order->inform_value) }}</td>
                            <td class="text-muted small"><div class="text-truncate" title="{{ $order->statusSist }}">{{ $order->statusSist ?: '—' }}</div></td>
                            <td>
                                @if ($order->state_id)
                                    <span class="badge {{ \App\Enum\ClosureOperationalStatus::badgeClassFor($order->operational_status) }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($order->operational_status) }}</span>
                                @else
                                    <span class="d-inline-flex align-items-center gap-1 text-muted small"><span class="status-dot" style="background: #cbd5e1;"></span>Não despachada</span>
                                @endif
                            </td>
                            <td class="small"><div class="text-truncate" title="{{ $order->assignee_name }}">{{ $order->assignee_name ?: ($order->holder_company_name ? 'Pilha de ' . $order->holder_company_name : '—') }}</div></td>
                            <td class="pe-3 text-end text-nowrap">
                                <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'service']) }}" class="icon-btn" title="Abrir obra" aria-label="Abrir obra da Ordem {{ $order->ordem }}"><i class="ri-external-link-line"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11"><x-closure.empty-state title="Nenhuma Ordem encontrada" text="Ajuste a busca ou os filtros." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="d-inline-flex align-items-center gap-2 small text-muted">
                <label for="closure-all-per-page" class="mb-0">Itens por página</label>
                <select id="closure-all-per-page" class="form-select form-select-sm w-auto" wire:model="perPage">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </span>
            {{ $orders->links() }}
        </div>
    </div>

    @if ($receipt)
        <div class="table-card mt-3" id="closure-all-receipt">
            <div class="chart-head"><h3 class="chart-title">Resultado da atribuição</h3><span class="badge bg-secondary">{{ count($receipt['successes']) }} assumida(s), {{ count($receipt['refusals']) }} recusada(s)</span></div>
            <div class="row g-0">
                <div class="col-12 col-lg-6 p-3 border-end"><h4 class="h6">Assumidas</h4><ul class="mb-0 ps-3">@forelse ($receipt['successes'] as $o)<li>{{ $o }}</li>@empty<li class="text-muted">Nenhuma.</li>@endforelse</ul></div>
                <div class="col-12 col-lg-6 p-3"><h4 class="h6">Recusadas</h4><ul class="mb-0 ps-3">@forelse ($receipt['refusals'] as $r)<li><strong>{{ $r['order'] }}</strong>: {{ $r['reason'] }}</li>@empty<li class="text-muted">Nenhuma.</li>@endforelse</ul></div>
            </div>
        </div>
    @endif
</div>
