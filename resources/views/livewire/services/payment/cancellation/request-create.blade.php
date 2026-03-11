<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-ink: #1f2933;
                --oe-muted: #6b7280;
                --oe-accent: #0f766e;
                --oe-border: #e5e7eb;
                background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                    var(--oe-bg);
                padding: 1.5rem 0;
            }

            .oexterno-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.5rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1.5rem;
            }

            .oexterno-header h2 {
                font-weight: 700;
                letter-spacing: 0.02em;
                margin: 0;
            }

            .oexterno-card {
                background: var(--oe-surface);
                border: 1px solid var(--oe-border);
                border-radius: 0.9rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .mode-pill {
                border: 1px solid #d1d5db;
                border-radius: .65rem;
                padding: .6rem .75rem;
                background: #fff;
            }
        </style>

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Solicitação de Cancelamento</h2>
                <span class="meta">Abertura individual e em massa por Nota/OV.</span>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="oexterno-card p-3 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Modo de abertura</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="mode-pill w-100">
                                    <input class="form-check-input me-2" type="radio" wire:model="createMode" value="single" wire:click="setCreateMode('single')">
                                    Individual (manual)
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="mode-pill w-100">
                                    <input class="form-check-input me-2" type="radio" wire:model="createMode" value="bulk" wire:click="setCreateMode('bulk')">
                                    Em massa por Nota/OV
                                </label>
                            </div>
                        </div>
                    </div>

                    @if($createMode === 'single')
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Número da Nota</label>
                                <input type="text" class="form-control" wire:model.defer="noteSearch" placeholder="Ex: 123456" />
                                @error('noteSearch')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" wire:click="findNote">Buscar</button>
                            </div>
                        </div>

                        @if($note)
                            <hr />
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Nota:</strong> {{ $note->note }}</p>
                                    <p class="mb-1"><strong>Cliente:</strong> {{ $note->client ?? '-' }}</p>
                                    <p class="mb-1"><strong>Status:</strong> {{ $note->status ?? '-' }}</p>
                                </div>
                                <div class="col-md-6">
                                    @if($noteCanceled)
                                        <div class="alert alert-danger">Nota já cancelada. Não é possível abrir nova solicitação.</div>
                                    @elseif($hasOpenNoteFullRequest)
                                        <div class="alert alert-warning">Já existe solicitação em aberto para cancelamento da nota inteira.</div>
                                    @elseif($hasCanceledOrders)
                                        <div class="alert alert-warning">Existem ordens já canceladas. Selecione ordens específicas.</div>
                                    @endif
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Categoria</label>
                                    <select class="form-select" wire:model.defer="categoryId">
                                        <option value="">Selecione...</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('categoryId')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Escopo</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::NOTE_FULL->value }}" id="scopeFull" @if($noteCanceled || $hasCanceledOrders || $hasOpenNoteFullRequest) disabled @endif>
                                        <label class="form-check-label" for="scopeFull">Cancelar nota inteira</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value }}" id="scopePartial">
                                        <label class="form-check-label" for="scopePartial">Cancelar ordens específicas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value }}" id="scopeWorkForm" @if(!$hasWorkForm) disabled @endif>
                                        <label class="form-check-label" for="scopeWorkForm">Cancelar somente WorkForm</label>
                                    </div>
                                    @if(!$hasWorkForm)
                                        <div class="small text-muted mt-1">Esta nota não possui WorkForm para cancelamento.</div>
                                    @endif
                                    @error('scope')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <label class="form-label">Ordens</label>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Ordem</th>
                                                    <th>Status</th>
                                                    <th>Cancelada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($orders as $order)
                                                    <tr>
                                                        <td>
                                                            @if(!$order['canceled'])
                                                                <input class="form-check-input" type="checkbox" value="{{ $order['id'] }}" wire:model="selectedOrders" @if($scope !== \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value) disabled @endif>
                                                            @endif
                                                        </td>
                                                        <td>{{ $order['ordem'] }}</td>
                                                        <td>{{ $order['status'] ?? '-' }}</td>
                                                        <td>{{ $order['canceled'] ? 'Sim' : 'Não' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @error('selectedOrders')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label class="form-label">Lista de Nota/OV para busca em massa</label>
                                <textarea class="form-control" rows="4" wire:model.defer="bulkNotesInput" placeholder="Cole Notas/OV separadas por vírgula, espaço ou linha."></textarea>
                                @error('bulkNotesInput')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" wire:click="processBulkNotes">Buscar em massa</button>
                            </div>
                        </div>

                        @if($bulkProcessed)
                            <hr />
                            <div class="row g-2 mb-2">
                                <div class="col-md-4"><div class="alert alert-light border mb-0">Encontradas: <strong>{{ $bulkTotalNotes }}</strong></div></div>
                                <div class="col-md-4"><div class="alert alert-light border mb-0">Aptas (escopo): <strong>{{ $bulkEligibleNotes }}</strong></div></div>
                                <div class="col-md-4"><div class="alert alert-light border mb-0">Selecionadas: <strong>{{ $bulkSelectedNotes }}</strong></div></div>
                            </div>

                            @if(count($bulkNotFoundValues))
                                <div class="alert alert-warning py-2">
                                    Não encontradas: {{ implode(', ', array_slice($bulkNotFoundValues, 0, 15)) }}@if(count($bulkNotFoundValues) > 15) ... @endif
                                </div>
                            @endif

                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Escopo (massa)</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::NOTE_FULL->value }}" id="bulkScopeFull">
                                        <label class="form-check-label" for="bulkScopeFull">Nota inteira</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value }}" id="bulkScopeOrders">
                                        <label class="form-check-label" for="bulkScopeOrders">Ordens específicas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" wire:model="scope" value="{{ \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value }}" id="bulkScopeWorkform">
                                        <label class="form-check-label" for="bulkScopeWorkform">Informe de Obra (WorkForm)</label>
                                    </div>
                                    @error('scope')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Categoria</label>
                                    <select class="form-select" wire:model.defer="categoryId">
                                        <option value="">Selecione...</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('categoryId')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-check mt-3 mb-2">
                                <input class="form-check-input" type="checkbox" id="bulkAllNotes" wire:model="bulkUseAllFilteredNotes">
                                <label class="form-check-label" for="bulkAllNotes">Selecionar todas as notas aptas ao escopo atual</label>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Nota</th>
                                            <th>OV</th>
                                            <th>Cliente</th>
                                            <th>WorkForm</th>
                                            <th>Ordens válidas</th>
                                            <th>Apta escopo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bulkVisibleNotes as $item)
                                            @php
                                                $eligibleForScope = match($scope) {
                                                    \App\Enum\CancellationRequestScope::NOTE_FULL->value => (bool) ($item['eligible_note_full'] ?? false),
                                                    \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value => (bool) ($item['eligible_workform'] ?? false),
                                                    default => (bool) ($item['eligible_orders'] ?? false),
                                                };
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if($eligibleForScope)
                                                        <input class="form-check-input" type="checkbox" value="{{ $item['id'] }}" wire:model="selectedBulkNoteIds" @if($bulkUseAllFilteredNotes) disabled @endif>
                                                    @endif
                                                </td>
                                                <td>{{ $item['note'] }}</td>
                                                <td>{{ $item['ov'] }}</td>
                                                <td>{{ $item['client'] }}</td>
                                                <td>{!! $item['has_workform'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' !!}</td>
                                                <td>{{ $item['orders_eligible'] }}</td>
                                                <td>
                                                    {!! $eligibleForScope ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' !!}
                                                    @if(($scope ?? null) === \App\Enum\CancellationRequestScope::NOTE_FULL->value && ($item['has_open_note_full_request'] ?? false))
                                                        <div class="small text-warning">Pedido em aberto</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($bulkTotalNotes > $bulkNotesRenderLimit)
                                <div class="small text-muted">Mostrando {{ $bulkNotesRenderLimit }} notas para manter desempenho.</div>
                            @endif
                            @error('selectedBulkNoteIds')<span class="text-danger small">{{ $message }}</span>@enderror

                            @if($scope === \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value)
                                <hr />
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label">Filtrar ordens válidas (Ordem / Nota / OV)</label>
                                        <input type="text" class="form-control" wire:model.defer="bulkOrderSearch" placeholder="Digite para filtrar...">
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-outline-primary w-100" wire:click="$refresh">Aplicar filtro</button>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2 mb-2">
                                    <div class="col-md-6"><div class="alert alert-light border mb-0">Ordens filtradas: <strong>{{ $bulkFilteredOrdersTotal }}</strong></div></div>
                                    <div class="col-md-6"><div class="alert alert-light border mb-0">Ordens selecionadas: <strong>{{ $bulkSelectedOrders }}</strong></div></div>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="bulkAllOrders" wire:model="bulkSelectAllFilteredOrders">
                                    <label class="form-check-label" for="bulkAllOrders">Selecionar todas as ordens filtradas</label>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Ordem</th>
                                                <th>Nota</th>
                                                <th>OV</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bulkFilteredOrders as $order)
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" value="{{ $order['id'] }}" wire:model="selectedBulkOrderIds" @if($bulkSelectAllFilteredOrders) disabled @endif>
                                                    </td>
                                                    <td>{{ $order['ordem'] }}</td>
                                                    <td>{{ $order['note'] }}</td>
                                                    <td>{{ $order['ov'] }}</td>
                                                    <td>{{ $order['status'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($bulkFilteredOrdersTotal > $bulkOrdersRenderLimit)
                                    <div class="small text-muted">Mostrando {{ $bulkOrdersRenderLimit }} ordens para manter desempenho. O processamento usa todas as ordens filtradas.</div>
                                @endif
                                @error('selectedBulkOrderIds')<span class="text-danger small">{{ $message }}</span>@enderror
                            @endif
                        @endif
                    @endif

                    @if(($createMode === 'single' && $note) || ($createMode === 'bulk' && $bulkHasValidTargets))
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Justificativa do solicitante *</label>
                                <textarea class="form-control" rows="3" wire:model.defer="description" placeholder="Descreva claramente o motivo do cancelamento para o executante."></textarea>
                                @error('description')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endif

                    @if($createMode === 'single' && $note)
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Evidências</label>
                                <input type="file" class="form-control" multiple wire:model="files">
                                @error('files.*')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-12 mt-2">
                                @if(count($tempFiles))
                                    <ul class="list-group">
                                        @foreach($tempFiles as $index => $file)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>{{ $file['original_name'] }} ({{ number_format($file['size'] / 1024, 1) }} KB)</span>
                                                <button class="btn btn-sm btn-outline-danger" wire:click="removeTempFile({{ $index }})">Remover</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @elseif($createMode === 'bulk' && $bulkHasValidTargets)
                        <div class="alert alert-light border mt-3 mb-0">
                            No envio em massa não há evidências por solicitação para evitar associação indevida item a item.
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-md-5">
                            @if(($createMode === 'single' && $note) || ($createMode === 'bulk' && $bulkHasValidTargets))
                                <button class="btn btn-success w-100" wire:click="submit" @if($createMode === 'single' && $noteCanceled) disabled @endif>
                                    {{ $createMode === 'bulk' ? 'Enviar solicitação em massa' : 'Enviar solicitação' }}
                                </button>
                            @elseif($createMode === 'bulk' && $bulkProcessed)
                                <div class="alert alert-warning py-2 mb-0">
                                    Sem obras válidas para o escopo atual. Ajuste o escopo ou a seleção.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="oexterno-card p-3">
                    <div class="fw-semibold mb-2">Regras rápidas</div>
                    <div>
                        <ul class="small mb-0">
                            <li>Busca em massa é por Nota/OV; o sistema traz aptidão por escopo.</li>
                            <li>Em ordens específicas, só aparecem ordens válidas (não canceladas e fora de status ENT/ENC).</li>
                            <li>`Selecionar todas` usa exatamente os itens filtrados no momento.</li>
                            <li>Processamento é em lotes para robustez e economia de memória.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
