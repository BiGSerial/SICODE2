<div class="closure-dash">
    @include('livewire.closure._styles')

    @php
        $pageIds = $orders->pluck('id')->map(fn ($id) => (int) $id);
        $allVisible = $pageIds->isNotEmpty() && $pageIds->every(fn ($id) => in_array($id, $selectedOrderIds));
    @endphp

    <style>
        .closure-dash .dispatch-settings-backdrop { position: fixed; inset: 0; background: rgba(16, 32, 51, .45); z-index: 1040; }
        .closure-dash .dispatch-settings-panel { position: fixed; top: 0; right: 0; bottom: 0; width: min(420px, 100%); background: #fff; z-index: 1050; box-shadow: -12px 0 30px rgba(16, 32, 51, .2); overflow-y: auto; }
    </style>

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Despacho de Encerramento</h2>
                <div class="dash-subtitle">Ordens de várias Notas: selecione, escolha a empresa/usuário e confira a prévia antes de confirmar.</div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedOrderIds) }} selecionada(s)</span>
                <a href="{{ route('closure.dispatch.dashboard') }}" class="btn btn-light btn-sm btn-dash">Painel analítico</a>
                <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click="openSettings">Configurações</button>
            </div>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} mb-3" role="alert">{{ $feedbackMessage }}</div>
    @endif

    @if ($receipt)
        <div class="table-card mb-3" id="closure-dispatch-receipt">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Recibo do despacho</h3>
                    <div class="chart-subtitle">Resultado por Ordem. Recusadas permanecem selecionadas.</div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="$set('receipt', [])">Fechar</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th class="ps-3">Tipo</th><th>Nota</th><th>Ordem</th><th class="pe-3">Resultado</th></tr></thead>
                    <tbody>
                        @foreach ($receipt as $row)
                            <tr wire:key="receipt-{{ $row['order_id'] }}">
                                <td class="ps-3">{!! \App\Services\Closure\ClosureMetaClassifier::badge($row['type']) !!}</td>
                                <td>{{ $row['note'] ?? '—' }}</td>
                                <td class="fw-semibold">{{ $row['ordem'] ?? $row['order_id'] }}</td>
                                <td class="pe-3">
                                    @if ($row['status'] === \App\Services\Closure\ClosureMultiDispatchResult::STATUS_DISPATCHED)
                                        <span class="badge-stack badge-closed">Despachada</span>
                                    @elseif ($row['status'] === \App\Services\Closure\ClosureMultiDispatchResult::STATUS_ALREADY)
                                        <span class="badge-stack badge-open">Já despachada</span>
                                    @else
                                        <span class="badge-stack badge-aging-high">Recusada</span><span class="ms-2 text-muted">{{ $row['message'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="table-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-3">
                <label for="closure-dispatch-search" class="form-label fw-semibold">Buscar Nota ou Ordem</label>
                <input id="closure-dispatch-search" type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Nota ou Ordem">
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label fw-semibold d-block">Tipo</label>
                @foreach (\App\Services\Closure\ClosureMetaClassifier::TYPES as $type)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="closure-dispatch-type-{{ $type }}" value="{{ $type }}" wire:model="types">
                        <label class="form-check-label" for="closure-dispatch-type-{{ $type }}">{!! \App\Services\Closure\ClosureMetaClassifier::badge($type) !!}</label>
                    </div>
                @endforeach
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="closure-dispatch-status" class="form-label fw-semibold">Status operacional</label>
                <select id="closure-dispatch-status" class="form-select" wire:model="status">
                    <option value="">Todos</option>
                    <option value="NONE">Não despachada</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="closure-dispatch-filter-company" class="form-label fw-semibold">Empresa atual</label>
                <select id="closure-dispatch-filter-company" class="form-select" wire:model="filterCompanyId">
                    <option value="">Todas</option>
                    @foreach ($this->companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="closure-dispatch-city" class="form-label fw-semibold">Município</label>
                <select id="closure-dispatch-city" class="form-select" wire:model="cityId">
                    <option value="">Todos</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->rdMunicipio }}">{{ $city->municipio }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="closure-dispatch-responsible" class="form-label fw-semibold">Responsável</label>
                <select id="closure-dispatch-responsible" class="form-select" wire:model="filterAssigneeId">
                    <option value="">Todos</option>
                    @foreach ($responsibles as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="closure-dispatch-undispatched" wire:model="onlyUndispatched">
                    <label class="form-check-label" for="closure-dispatch-undispatched">Só não despachadas</label>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3 text-lg-end">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button>
            </div>
        </div>
    </div>

    <div class="table-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label for="closure-dispatch-company" class="form-label fw-semibold">Empresa de destino</label>
                <select id="closure-dispatch-company" class="form-select" wire:model="companyId">
                    <option value="">Selecione</option>
                    @foreach ($this->companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="closure-dispatch-assignee" class="form-label fw-semibold">Usuário (opcional)</label>
                <select id="closure-dispatch-assignee" class="form-select" wire:model="assigneeId" @disabled(!$companyId)>
                    <option value="">Enviar para a pilha</option>
                    @foreach ($this->assignees as $assignee)
                        <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="button" class="btn btn-dash text-white flex-fill" style="background: var(--dash-teal);" wire:click="preparePreview">Ver prévia do despacho</button>
                <button type="button" class="btn btn-outline-secondary btn-dash" wire:click="clearSelection" @disabled(!$selectedOrderIds)>Limpar</button>
            </div>
        </div>
    </div>

    @if ($showPreview)
        <div class="table-card mb-3" id="closure-dispatch-preview">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Prévia do despacho</h3>
                    <div class="chart-subtitle">A confirmação fará uma nova verificação por Ordem. Recusas não impedem as demais.</div>
                </div>
                <span class="badge bg-secondary">{{ count($previewRows) }} Ordem(ns)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th class="ps-3">Tipo</th><th>Nota</th><th>Ordem</th><th class="pe-3">Resultado da prévia</th></tr></thead>
                    <tbody>
                        @foreach ($previewRows as $row)
                            <tr wire:key="preview-{{ $row['id'] }}">
                                <td class="ps-3">{!! \App\Services\Closure\ClosureMetaClassifier::badge($row['type']) !!}</td>
                                <td>{{ $row['note'] }}</td>
                                <td class="fw-semibold">{{ $row['ordem'] }}</td>
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
                <span class="small text-muted">{{ collect($previewRows)->where('eligible', false)->count() }} recusada(s). A confirmação não altera dados do SAP.</span>
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" id="closure-dispatch-confirm"
                    onclick="Swal.fire({title: 'Confirmar despacho?', text: '{{ count($previewRows) }} Ordem(ns) serão despachadas.', icon: 'question', showCancelButton: true, confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar'}).then(function (r) { if (r.isConfirmed) { @this.confirmDispatch(); } });">
                    Confirmar despacho
                </button>
            </div>
        </div>
    @endif

    <div class="table-card mb-3">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens</h3>
                <div class="chart-subtitle">{{ $orders->total() }} Ordem(ns). A seleção vale entre páginas e Notas.</div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleOrders">{{ $allVisible ? 'Limpar página' : 'Selecionar página' }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <colgroup>
                    <col style="width: 44px;"><col style="width: 60px;"><col style="width: 130px;"><col style="width: 120px;">
                    <col><col><col style="width: 110px;"><col style="width: 90px;"><col style="width: 140px;">
                    <col><col style="width: 90px;"><col style="width: 110px;"><col style="width: 90px;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3"></th>
                        <th>Tipo</th>
                        <th>Nota</th>
                        <th>Ordem</th>
                        <th>Obra / Município</th>
                        <th>Empresa atual</th>
                        <th>Status SAP</th>
                        <th>Operações</th>
                        <th>Status operacional</th>
                        <th>Responsável</th>
                        <th>Prioridade</th>
                        <th>Fim real OP20</th>
                        <th class="pe-3">Obra</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="closure-dispatch-order-{{ $order->id }}">
                            <td class="ps-3"><input type="checkbox" class="form-check-input" @checked(in_array((int) $order->id, $selectedOrderIds)) wire:click="toggleOrder({{ $order->id }})"></td>
                            <td>{!! \App\Services\Closure\ClosureMetaClassifier::badge($order->meta_type) !!}</td>
                            <td>
                                {{ $order->note_number }}
                                @if ($order->partial_id)
                                    <span class="badge bg-light text-dark border" title="Parcial #{{ $order->partial_id }}">Parcial</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $order->ordem }}</td>
                            <td>
                                <div class="text-truncate" title="{{ $order->descricao }}">{{ $order->descricao ?: '—' }}</div>
                                <div class="small text-muted">{{ $order->city_name ?: '—' }}</div>
                            </td>
                            <td class="small">{{ $order->holder_company_name ?: '—' }}</td>
                            <td class="text-muted small">{{ $order->statusSist ?: '—' }}</td>
                            <td class="small" title="Operações confirmadas / total">{{ (int) $order->ops_conf }}/{{ (int) $order->ops_total }}</td>
                            <td>
                                @if ($order->state_id)
                                    <span class="badge {{ \App\Enum\ClosureOperationalStatus::badgeClassFor($order->operational_status) }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($order->operational_status) }}</span>
                                @else
                                    <span class="text-muted small">Não despachada</span>
                                @endif
                            </td>
                            <td class="small">{{ $order->assignee_name ?: '—' }}</td>
                            <td>@if ($order->is_priority)<span class="badge bg-warning text-dark">Prioridade</span>@else<span class="text-muted">—</span>@endif</td>
                            <td class="small">{{ $order->fim_real_op20 ? \Carbon\Carbon::parse($order->fim_real_op20)->format('d/m/Y') : '—' }}</td>
                            <td class="pe-3"><a href="{{ route('closure.work.show', $order->note_id) }}" class="btn btn-outline-secondary btn-sm">Abrir</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center text-muted py-4">Nenhuma Ordem encontrada para estes filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $orders->links() }}</div>
    </div>

    @if ($showSettings)
        <div class="dispatch-settings-backdrop" wire:click="closeSettings"></div>
        <div class="dispatch-settings-panel p-4" id="closure-dispatch-settings" role="dialog" aria-label="Configurações do Encerramento">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h3 class="chart-title mb-0">Configurações do Encerramento</h3>
                <button type="button" class="btn-close" aria-label="Fechar" wire:click="closeSettings"></button>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="closure-setting-future" wire:model.defer="allowFutureCompletion">
                <label class="form-check-label" for="closure-setting-future">Permitir concluir obras de Meta Futura <span class="d-block small text-muted">Elas vão para o status Aguardando até a meta chegar.</span></label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="closure-setting-self-assign" wire:model.defer="selfAssignEnabled">
                <label class="form-check-label" for="closure-setting-self-assign">Habilitar lista de auto-atribuição no Serviço</label>
            </div>
            <div class="mb-4">
                <div class="fw-semibold mb-1">Tipos liberados para auto-atribuição</div>
                @foreach (\App\Services\Closure\ClosureMetaClassifier::TYPES as $type)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="closure-setting-type-{{ $type }}" value="{{ $type }}" wire:model.defer="selfAssignTypes">
                        <label class="form-check-label" for="closure-setting-type-{{ $type }}">{!! \App\Services\Closure\ClosureMetaClassifier::badge($type) !!} {{ \App\Services\Closure\ClosureMetaClassifier::label($type) }}</label>
                    </div>
                @endforeach
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" wire:click="saveSettings">Salvar</button>
                <button type="button" class="btn btn-outline-secondary btn-dash" wire:click="closeSettings">Cancelar</button>
            </div>
        </div>
    @endif
</div>
