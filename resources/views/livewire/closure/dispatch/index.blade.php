<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Despacho de Encerramento</h2>
                <div class="dash-subtitle">Selecione Ordens de uma Nota/OV e confira o resultado antes de confirmar.</div>
            </div>
            <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedOrderIds) }} selecionada(s)</span>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert">{{ $feedbackMessage }}</div>
    @endif

    <div class="table-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="closure-dispatch-search" class="form-label fw-semibold">Buscar Nota ou Ordem</label>
                <input id="closure-dispatch-search" type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Ex.: Nota 400... ou Ordem 150...">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="closure-dispatch-scope" class="form-label fw-semibold">Carteira</label>
                <select id="closure-dispatch-scope" class="form-select" wire:model="scope">
                    <option value="all">Meta e passivo</option>
                    <option value="meta">Meta atual</option>
                    <option value="passive">Passivo</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label for="closure-dispatch-company" class="form-label fw-semibold">Empresa de destino</label>
                <select id="closure-dispatch-company" class="form-select" wire:model="companyId">
                    <option value="">Selecione</option>
                    @foreach ($this->companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label for="closure-dispatch-assignee" class="form-label fw-semibold">Usuário (opcional)</label>
                <select id="closure-dispatch-assignee" class="form-select" wire:model="assigneeId" @disabled(!$companyId)>
                    <option value="">Enviar para a pilha</option>
                    @foreach ($this->assignees as $assignee)
                        <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="table-card mb-3">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens disponíveis</h3>
                <div class="chart-subtitle">A seleção deve conter Ordens da mesma Nota/OV.</div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleOrders">Selecionar/limpar visíveis</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 48px;"></th>
                        <th>Ordem</th>
                        <th>Nota/OV</th>
                        <th>Carteira</th>
                        <th>status SAP</th>
                        <th class="pe-3">Situação atual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $isSelected = in_array($order->id, $selectedOrderIds);
                            $cycle = $order->ClosureTarget?->Cycle;
                            $isPassive = $cycle && $cycle->periodKey() < \App\Models\ClosureCycle::currentPeriodKey();
                        @endphp
                        <tr wire:key="closure-dispatch-order-{{ $order->id }}">
                            <td class="ps-3"><input type="checkbox" class="form-check-input" @checked($isSelected) wire:click="toggleOrder({{ $order->id }})"></td>
                            <td class="fw-semibold">{{ $order->ordem }}</td>
                            <td>{{ $order->Note?->note ?? '—' }}</td>
                            <td><span class="badge-stack {{ $isPassive ? 'badge-aging-high' : 'badge-open' }}">{{ $isPassive ? 'Passivo' : 'Meta' }}</span></td>
                            <td class="text-muted small">{{ $order->statusSist ?: 'Sem informação' }}</td>
                            <td class="pe-3">
                                @if ($order->ClosureState)
                                    <span class="text-warning-emphasis">Já possui vínculo operacional</span>
                                @elseif ($order->canceled)
                                    <span class="text-danger">Cancelada</span>
                                @elseif (str_starts_with((string) $order->statusSist, 'ENTE') || str_starts_with((string) $order->statusSist, 'ENCE'))
                                    <span class="text-success">Encerrada no SAP</span>
                                @else
                                    <span class="text-muted">A conferir na prévia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma Ordem encontrada para estes filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-dash text-white" style="background: var(--dash-teal);" wire:click="preparePreview">Ver prévia do despacho</button>
    </div>

    @if ($showPreview)
        <div class="table-card mt-3" id="closure-dispatch-preview">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Prévia do despacho</h3>
                    <div class="chart-subtitle">Nota/OV {{ $previewNoteLabel }}. A confirmação fará uma nova verificação.</div>
                </div>
                <span class="badge bg-secondary">{{ count($previewRows) }} Ordem(ns)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th class="ps-3">Ordem</th><th>Nota/OV</th><th class="pe-3">Resultado da prévia</th></tr></thead>
                    <tbody>
                        @foreach ($previewRows as $row)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $row['ordem'] }}</td>
                                <td>{{ $row['note'] }}</td>
                                <td class="pe-3">
                                    @if ($row['eligible'])
                                        <span class="badge-stack badge-closed">Pronta para despacho</span>
                                    @else
                                        <span class="badge-stack badge-aging-high">Recusada</span><span class="ms-2 text-muted">{{ $row['reason'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="small text-muted">A confirmação não altera dados do SAP.</span>
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" wire:click="confirmDispatch">Confirmar despacho</button>
            </div>
        </div>
    @endif
</div>
