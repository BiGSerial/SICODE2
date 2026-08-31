<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-border: #e5e7eb;
                background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
                padding: 1.5rem 0;
            }
            .oexterno-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.5rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, .2);
                margin-bottom: 1.5rem;
            }
            .oexterno-card {
                background: var(--oe-surface);
                border: 1px solid var(--oe-border);
                border-radius: .9rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
            }
            .mode-pill {
                border: 1px solid #d1d5db;
                border-radius: .65rem;
                padding: .65rem .75rem;
                background: #fff;
            }
        </style>

        <div class="oexterno-header">
            <h2 class="mb-0">Solicitação de Descancelamento</h2>
            <span class="text-white-50">Reabertura de Nota/OV, ordens ou informe cancelado.</span>
        </div>

        <div class="oexterno-card p-3">
            <div class="mb-3">
                <label class="form-label">Modo de abertura</label>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="mode-pill w-100">
                            <input class="form-check-input me-2" type="radio" wire:model="createMode" value="single" wire:click="setCreateMode('single')">
                            Individual
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

            @if ($createMode === 'single')
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Número da Nota/OV</label>
                        <input type="text" class="form-control" wire:model.defer="noteSearch" placeholder="Ex: 4001954077">
                        @error('noteSearch')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" wire:click="findNote">
                            <i class="ri-search-line me-1"></i>Buscar
                        </button>
                    </div>
                </div>

                @if ($note)
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Nota/OV</div>
                                <div class="fw-semibold">{{ $note->note }}</div>
                                <div class="text-muted small mt-2">Cliente</div>
                                <div class="fw-semibold">{{ $note->client ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Situação</div>
                                <div class="fw-semibold">{{ $note->status ?? '-' }}</div>
                                <div class="mt-2">
                                    <span class="badge {{ $note->canceled ? 'bg-danger' : 'bg-secondary' }}">
                                        {{ $note->canceled ? 'Cancelada' : 'Não cancelada' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Ordens canceladas</div>
                                <div class="fw-semibold">{{ collect($orders)->where('canceled', true)->count() }}</div>
                                <div class="text-muted small mt-2">Informe cancelado</div>
                                <div class="fw-semibold">{{ $note->WorkFormAny?->canceled ? 'Sim' : 'Não' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-5">
                            <label class="form-label">Escopo</label>
                            @foreach ($scopeOptions as $option)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="scope" value="{{ $option->value }}" id="uncxlScope{{ $option->value }}">
                                    <label class="form-check-label" for="uncxlScope{{ $option->value }}">{{ $option->label() }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Ordens</label>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Ordem</th>
                                            <th>Status</th>
                                            <th>Cancelada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($orders as $order)
                                            <tr>
                                                <td>
                                                    @if ($order['canceled'])
                                                        <input class="form-check-input" type="checkbox" wire:model="selectedOrders" value="{{ $order['id'] }}" @if($scope !== \App\Enum\CancellationRequestScope::ORDERS_PARTIAL->value) disabled @endif>
                                                    @endif
                                                </td>
                                                <td>{{ $order['ordem'] }}</td>
                                                <td>{{ $order['status'] }}</td>
                                                <td>{{ $order['canceled'] ? 'Sim' : 'Não' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center">Nenhuma ordem encontrada.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">Notas/OVs canceladas</label>
                        <textarea class="form-control" rows="4" wire:model.defer="bulkNotesInput" placeholder="Cole Notas/OVs separadas por vírgula, espaço ou linha."></textarea>
                        @error('bulkNotesInput')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" wire:click="processBulkNotes">Buscar em massa</button>
                    </div>
                </div>

                @if ($bulkProcessed)
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Nota/OV</th>
                                    <th>Cliente</th>
                                    <th>Ordens canceladas</th>
                                    <th>Apta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bulkCandidates as $item)
                                    <tr>
                                        <td>
                                            @if ($item['eligible'])
                                                <input class="form-check-input" type="checkbox" wire:model="selectedBulkNoteIds" value="{{ $item['id'] }}">
                                            @endif
                                        </td>
                                        <td>{{ $item['note'] }}</td>
                                        <td>{{ $item['client'] }}</td>
                                        <td>{{ $item['canceled_orders'] }}</td>
                                        <td>{{ $item['eligible'] ? 'Sim' : 'Não' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif

            <div class="mt-3">
                <label class="form-label">Justificativa</label>
                <textarea class="form-control" rows="3" wire:model.defer="description"></textarea>
                @error('description')<span class="text-danger small">{{ $message }}</span>@enderror
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-success" wire:click="submit">
                    <i class="ri-arrow-go-back-line me-1"></i>Solicitar descancelamento
                </button>
            </div>
        </div>
    </div>
</div>
