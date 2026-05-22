<div>
    <x-show-loading />

    @if (!$sqlSyncEnabled)
        <div class="alert alert-warning">
            Modo teste sem envio para SQL Server está habilitado. As solicitações serão registradas apenas localmente.
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <h4 class="my-0">Solicitacao de pedidos ADS</h4>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Company</label>
                    <select class="form-select border border-secondary" wire:model="companyId">
                        <option value="">Selecione</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-8">
                    <label class="form-label">Notas (separe por virgula, espaco ou quebra de linha)</label>
                    <textarea class="form-control border border-secondary" rows="3" wire:model.defer="notesInput"></textarea>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary w-100" wire:click.prevent="analyzeNotes"
                        wire:loading.attr="disabled" wire:target="analyzeNotes,processRequests,confirmProcessRequests"
                        @if (!$companyId || $isProcessingRequests) disabled @endif>
                        <i class="ri-search-line align-middle"></i> Pre-analisar
                    </button>
                    <button class="btn btn-outline-secondary w-100" wire:click.prevent="removeAllPreview">
                        Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row">
                <div class="col">
                    <h5 class="my-0">Pre-analise</h5>
                </div>
                <div class="col d-flex justify-content-end align-items-center">
                    @php
                        $processableCount = collect($previewItems)->where('can_process', true)->count();
                    @endphp
                    <span class="me-3">Aptos: {{ $processableCount }}</span>
                    <button class="btn btn-success btn-sm" wire:click.prevent="processRequests"
                        wire:loading.attr="disabled" wire:target="processRequests,confirmProcessRequests"
                        @if ($processableCount === 0 || !$companyId || $isProcessingRequests) disabled @endif>
                        <span wire:loading.remove wire:target="processRequests,confirmProcessRequests">
                            <i class="ri-check-line align-middle"></i> Processar lista
                        </span>
                        <span wire:loading wire:target="processRequests,confirmProcessRequests">Processando...</span>
                    </button>
                    <button class="btn btn-outline-danger btn-sm ms-2" wire:click.prevent="removeAllPreview"
                        @if (count($previewItems) === 0) disabled @endif>
                        Remover todos
                    </button>
                    <button class="btn btn-outline-danger btn-sm ms-2" wire:click.prevent="removeSelectedPreview"
                        @if (count($selectedPreviewItems ?? []) === 0) disabled @endif>
                        Remover selecionados
                    </button>
                </div>
            </div>
        </div>
        @php
            $blockedItems = collect($previewItems)->where('can_process', false);
        @endphp
        @if ($blockedItems->count())
            <div class="alert alert-warning mx-3 mt-3 mb-0">
                <strong>{{ $blockedItems->count() }} nota(s) bloqueada(s).</strong>
                Verifique a coluna detalhe para o motivo de cada bloqueio.
            </div>
        @endif
        <div class="table-responsible">
            <table class="table table-sm table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;"></th>
                        <th class="text-center">Nota</th>
                        <th>Status</th>
                        <th>Detalhe</th>
                        <th class="text-center">BAIXAR ADS</th>
                        <th class="text-center">Anterior</th>
                        <th class="text-center">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($previewItems as $item)
                        <tr wire:key="preview_{{ $item['note_number'] }}" @class(['table-danger' => !$item['can_process']])>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input"
                                    wire:model="selectedPreviewItems"
                                    value="{{ $item['note_number'] }}">
                            </td>
                            <td class="text-center fw-bold">{{ $item['note_number'] }}</td>
                            <td>
                                <span class="badge {{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
                            </td>
                            <td>
                                @if (!$item['can_process'])
                                    <div class="text-danger fw-semibold">
                                        <i class="ri-error-warning-line align-middle"></i> Bloqueado
                                    </div>
                                @endif
                                <div>{{ $item['message'] }}</div>
                            </td>
                            <td class="text-center">
                                @if (!empty($item['last_url']))
                                    <a href="{{ $item['last_url'] }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                        BAIXAR ADS
                                    </a>
                                    @if (!empty($item['last_url_age']))
                                        <small class="text-muted d-block mt-1">{{ $item['last_url_age'] }}</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($item['previous_request_id'])
                                    #{{ $item['previous_request_id'] }}
                                    @if ($item['previous_status'])
                                        <span class="text-muted">({{ $item['previous_status'] }})</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-danger btn-sm"
                                    wire:click.prevent="removePreview('{{ $item['note_number'] }}')">
                                    Remover
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Nenhuma nota analisada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <div class="row align-items-end g-3">
                <div class="col-12">
                    <h5 class="my-0 fw-bold text-uppercase">Solicitações em andamento</h5>
                </div>
                <div class="col-12 col-lg-7">
                    <label class="form-label">Buscar nota(s)</label>
                    <textarea class="form-control border border-secondary" rows="2"
                        wire:model.debounce.500ms="activeSearch"
                        placeholder="Separe por vírgula, espaço ou quebra de linha"></textarea>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="row g-2 justify-content-end">
                        <div class="col-6 col-lg-4">
                            <label class="form-label">Por página</label>
                            <select class="form-select border border-secondary" wire:model="activePerPage">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-8 d-flex align-items-end">
                            <button class="btn btn-outline-light w-100" wire:click.prevent="syncAllRequests"
                                @if ($activeRequests->isEmpty() || !$sqlSyncEnabled) disabled @endif>
                                Sincronizar todos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsible">
            <table class="table table-sm table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center">Nota</th>
                        <th>Empresa</th>
                        <th>Usuario</th>
                        <th>Status</th>
                        <th>Descricao</th>
                        <th class="text-center">Versao</th>
                        <th class="text-center">SQL Server</th>
                        <th class="text-center">Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activeRequests as $request)
                        <tr wire:key="active_{{ $request->id }}">
                            <td class="text-center fw-bold">{{ $request->note?->note ?? $request->note_id }}</td>
                            <td>{{ $request->company?->name ?? '-' }}</td>
                            <td>{{ $request->requestedBy?->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $request->status?->badgeClass() ?? 'text-bg-secondary' }}">
                                    {{ $request->status?->label() }}
                                </span>
                            </td>
                            <td>{{ $request->description ?? '-' }}</td>
                            <td class="text-center">{{ $request->version }}</td>
                            <td class="text-center">
                                @if (!$sqlSyncEnabled)
                                    <span class="badge text-bg-secondary">Modo teste</span>
                                @else
                                @php
                                    $sqlStatus = $sqlStatusBySicodeId->get($request->id)?->status;
                                    $isSynced = $sqlStatus && $sqlStatus === $request->status?->value;
                                @endphp
                                @if (!$sqlStatus)
                                    <span class="badge text-bg-warning">Nao encontrado</span>
                                @elseif ($isSynced)
                                    <span class="badge text-bg-success">Sincronizado</span>
                                @else
                                    <span class="badge text-bg-danger">Diferente</span>
                                @endif
                                @endif
                            </td>
                            <td class="text-center">{{ $request->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">Nenhuma solicitacao em andamento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2">
            {{ $activeRequests->links() }}
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <div class="row align-items-end g-3">
                <div class="col-12">
                    <h5 class="my-0 fw-bold text-uppercase">Histórico de solicitações</h5>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Buscar nota(s)</label>
                    <textarea class="form-control border border-secondary" rows="2"
                        wire:model.debounce.500ms="historySearch"
                        placeholder="Separe por vírgula, espaço ou quebra de linha"></textarea>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="row g-2 justify-content-end">
                        <div class="col-6 col-lg-4">
                            <label class="form-label">De</label>
                            <input type="date" class="form-control border border-secondary"
                                wire:model="historyStart">
                        </div>
                        <div class="col-6 col-lg-4">
                            <label class="form-label">Até</label>
                            <input type="date" class="form-control border border-secondary" wire:model="historyEnd">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Empresa</label>
                            <select class="form-select border border-secondary" wire:model="historyCompanyId">
                                <option value="">Todas</option>
                                @foreach ($companyOptions as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-4">
                            <label class="form-label">Por página</label>
                            <select class="form-select border border-secondary" wire:model="historyPerPage">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-4 d-flex align-items-end">
                            <button class="btn btn-outline-light w-100" wire:click.prevent="clearHistoryFilters">
                                Limpar
                            </button>
                        </div>
                        <div class="col-12 col-lg-4 d-flex align-items-end">
                            <button class="btn btn-light w-100" wire:click.prevent="exportHistory"
                                @if ($historyRequests->total() === 0) disabled @endif>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsible">
            <table class="table table-sm table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center">Nota</th>
                        <th>Empresa</th>
                        <th>Usuario</th>
                        <th>Status</th>
                        <th>Descricao</th>
                        <th>ADS</th>
                        <th class="text-center">Versao</th>
                        <th class="text-center">Criado em</th>
                        <th class="text-center">Concluido</th>
                        <th class="text-center">Cancelado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historyRequests as $request)
                        <tr wire:key="history_{{ $request->id }}">
                            <td class="text-center fw-bold">{{ $request->note?->note ?? $request->note_id }}</td>
                            <td>{{ $request->company?->name ?? '-' }}</td>
                            <td>{{ $request->requestedBy?->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $request->status?->badgeClass() ?? 'text-bg-secondary' }}">
                                    {{ $request->status?->label() }}
                                </span>
                            </td>
                            <td>{{ $request->description ?? '-' }}</td>
                            <td>
                                @if ($request->url)
                                    <a href="{{ $request->url }}" class="btn btn-sm btn-outline-primary" target="_self">
                                        Baixar ADS
                                    </a>
                                @else
                                    <span class="text-muted">Sem link</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $request->version }}</td>
                            <td class="text-center">{{ $request->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-center">{{ $request->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="text-center">{{ $request->canceled_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">Nenhuma solicitacao encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="row mt-3">
            <div class="col-6">
                {{ $historyRequests->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle">
                    Exibindo {{ $historyRequests->firstItem() ?? 0 }} ate
                    {{ $historyRequests->lastItem() ?? 0 }}
                    de {{ $historyRequests->total() }}
                    registros.
                </span>
            </div>
        </div>
    </div>
</div>
