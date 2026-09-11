<div>
    <div class="modal fade" id="adminPartialModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down">
            @if ($partial)
                <form class="modal-content" wire:submit.prevent="save">
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
                            <i class="ri-edit-2-line"></i>
                            <span>Editar Informe Parcial #{{ $partial->id }}</span>
                            <span class="badge text-bg-light">{{ $partial->Note?->note ?? 'sem nota' }}</span>
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-xl-7">
                                <div class="card h-100">
                                    <div class="card-header fw-semibold">Informacoes</div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Nota</label>
                                                <input type="text" class="form-control" value="{{ $partial->Note?->note ?? '---' }}" readonly>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Empresa</label>
                                                <select class="form-select @error('partial.company_id') is-invalid @enderror"
                                                    wire:model.defer="partial.company_id">
                                                    <option value="">Selecione...</option>
                                                    @foreach ($companies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('partial.company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Usuario</label>
                                                <select class="form-select @error('partial.user_id') is-invalid @enderror"
                                                    wire:model.defer="partial.user_id">
                                                    <option value="">Selecione...</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('partial.user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Responsavel</label>
                                                <input type="text" class="form-control @error('partial.responsible') is-invalid @enderror"
                                                    wire:model.defer="partial.responsible">
                                                @error('partial.responsible') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Valor</label>
                                                <input type="number" step="0.01" class="form-control @error('partial.value') is-invalid @enderror"
                                                    wire:model.defer="partial.value">
                                                @error('partial.value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Observacao</label>
                                                <textarea class="form-control @error('partial.observation') is-invalid @enderror"
                                                    rows="3" wire:model.defer="partial.observation"></textarea>
                                                @error('partial.observation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Informacao da engenharia</label>
                                                <textarea class="form-control @error('partial.engineer_info') is-invalid @enderror"
                                                    rows="3" wire:model.defer="partial.engineer_info"></textarea>
                                                @error('partial.engineer_info') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="card mb-3">
                                    <div class="card-header fw-semibold">Status</div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="partialAllow"
                                                        wire:model.defer="partial.allow">
                                                    <label class="form-check-label" for="partialAllow">Aprovado</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="partialDeny"
                                                        wire:model.defer="partial.deny">
                                                    <label class="form-check-label" for="partialDeny">Rejeitado</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="partialPayment"
                                                        wire:model.defer="partial.payment">
                                                    <label class="form-check-label" for="partialPayment">Pagamento</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="partialSupervision"
                                                        wire:model.defer="partial.supervision">
                                                    <label class="form-check-label" for="partialSupervision">Supervisao</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="partialComplete"
                                                        wire:model.defer="partial.complete">
                                                    <label class="form-check-label" for="partialComplete">Completo</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" class="btn btn-outline-success btn-sm" wire:click="approve">Marcar aprovado</button>
                                            <button type="button" class="btn btn-outline-warning btn-sm" wire:click="reject">Marcar rejeitado</button>
                                        </div>

                                        <div class="row g-3 mt-1">
                                            <div class="col-md-4">
                                                <label class="form-label">Decisao</label>
                                                <input type="datetime-local" class="form-control" wire:model.defer="decisionAt">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Pagamento</label>
                                                <input type="datetime-local" class="form-control" wire:model.defer="paymentAt">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Supervisao</label>
                                                <input type="datetime-local" class="form-control" wire:model.defer="supervisionAt">
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-1">
                                            <div class="col-md-4">
                                                <label class="form-label">Engenharia</label>
                                                <select class="form-select" wire:model.defer="partial.engineer_id">
                                                    <option value="">---</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Supervisao</label>
                                                <select class="form-select" wire:model.defer="partial.supervision_id">
                                                    <option value="">---</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Pagamento</label>
                                                <select class="form-select" wire:model.defer="partial.payment_id">
                                                    <option value="">---</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header fw-semibold">Associacoes das atividades</div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-12 col-xl-6">
                                                <div class="fw-semibold mb-2">Atividades disponiveis</div>
                                                @if (!empty($availableOrders))
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Ordem</th>
                                                                    <th>Status</th>
                                                                    <th></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($availableOrders as $order)
                                                                    <tr>
                                                                        <td>{{ $order['ordem'] ?? '' }}</td>
                                                                        <td>{{ $order['statusSist'] ?? '---' }}</td>
                                                                        <td class="text-end">
                                                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                                                wire:click="addOrder({{ $order['id'] ?? 0 }})">
                                                                                Adicionar
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <div class="alert alert-secondary mb-0">Sem atividades disponiveis para esta nota.</div>
                                                @endif
                                            </div>
                                            <div class="col-12 col-xl-6">
                                                <div class="fw-semibold mb-2">Atividades vinculadas</div>
                                                @if (!empty($linkedOrders))
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Ordem</th>
                                                                    <th>Status</th>
                                                                    <th></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($linkedOrders as $order)
                                                                    <tr>
                                                                        <td>{{ $order['ordem'] ?? '' }}</td>
                                                                        <td>{{ $order['statusSist'] ?? '---' }}</td>
                                                                        <td class="text-end">
                                                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                                                wire:click="removeOrder({{ $order['id'] ?? 0 }})">
                                                                                Desassociar
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <div class="alert alert-secondary mb-0">Sem atividades vinculadas.</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header fw-semibold">Producoes do informe parcial</div>
                                    <div class="card-body">
                                        <div class="row g-3 align-items-end mb-3">
                                            <div class="col-12 col-lg-9">
                                                <label class="form-label">Producoes candidatas da nota</label>
                                                <select class="form-select" wire:model.defer="productionId">
                                                    <option value="">Selecione uma producao...</option>
                                                    @foreach ($availableProductions as $production)
                                                        @php
                                                            $productionIdValue = data_get($production, 'id');
                                                            $serviceName = data_get($production, 'service.service') ?? 'Sem servico';
                                                            $userName = data_get($production, 'user.name') ?? 'Sem usuario';
                                                            $statusNote = data_get($production, 'status_note') ?? 'sem status';
                                                        @endphp
                                                        <option value="{{ $productionIdValue }}">
                                                            #{{ $productionIdValue }} - {{ $serviceName }} - {{ $userName }} - {{ $statusNote }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-lg-3">
                                                <button type="button" class="btn btn-primary w-100" wire:click="addProduction">
                                                    Associar producao
                                                </button>
                                            </div>
                                        </div>

                                        @if (!empty($linkedProductions))
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Servico</th>
                                                            <th>Usuario</th>
                                                            <th>Status</th>
                                                            <th>Flags</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($linkedProductions as $production)
                                                            @php
                                                                $productionIdValue = data_get($production, 'id');
                                                                $serviceName = data_get($production, 'service.service') ?? '---';
                                                                $userName = data_get($production, 'user.name') ?? '---';
                                                            @endphp
                                                            <tr>
                                                                <td>#{{ $productionIdValue }}</td>
                                                                <td>{{ $serviceName }}</td>
                                                                <td>{{ $userName }}</td>
                                                                <td>{{ data_get($production, 'status_note') ?? '---' }}</td>
                                                                <td>
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        <button type="button"
                                                                            class="btn btn-sm {{ data_get($production, 'partial') ? 'btn-success' : 'btn-outline-secondary' }}"
                                                                            wire:click="toggleProductionFlag({{ $productionIdValue }}, 'partial')">
                                                                            Parcial
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm {{ data_get($production, 'completed') ? 'btn-success' : 'btn-outline-secondary' }}"
                                                                            wire:click="toggleProductionFlag({{ $productionIdValue }}, 'completed')">
                                                                            Concluida
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm {{ data_get($production, 'confirmed') ? 'btn-success' : 'btn-outline-secondary' }}"
                                                                            wire:click="toggleProductionFlag({{ $productionIdValue }}, 'confirmed')">
                                                                            Confirmada
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                                <td class="text-end">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                                        wire:click="removeProduction({{ $productionIdValue }})">
                                                                        Desassociar
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-secondary mb-0">Sem producoes vinculadas ao informe parcial.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Salvar</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
