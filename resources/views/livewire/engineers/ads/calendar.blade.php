<div>
    <x-show-loading />

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Calendário de dias não úteis</h5>
            <span class="badge bg-light text-dark">Fonte: Feriados API</span>
        </div>
        <div class="card-body">
            @if ($errorMessage)
                <div class="alert alert-danger">{{ $errorMessage }}</div>
            @endif

            @if ($lastMessage)
                <div class="alert alert-success">{{ $lastMessage }}</div>
            @endif

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">UF</label>
                    <select class="form-select border border-secondary" wire:model.defer="state">
                        @foreach (['ES', 'SP', 'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SE', 'TO'] as $uf)
                            <option value="{{ $uf }}">{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Ano</label>
                    <input type="number" min="2000" max="2100" class="form-control border border-secondary" wire:model.defer="year">
                </div>
                <div class="col-12 col-md-6 d-flex gap-2">
                    <button class="btn btn-primary" wire:click="consult" wire:loading.attr="disabled" wire:target="consult">
                        <i class="ri-search-line me-1"></i> Consultar Feriados
                    </button>
                    <button class="btn btn-success" wire:click="confirmImport" wire:loading.attr="disabled" wire:target="confirmImport">
                        <i class="ri-upload-cloud-line me-1"></i> Importar Calendário
                    </button>
                </div>
            </div>

            <div class="mt-3 text-muted">
                Última atualização local: {{ $lastImportedAt ? \Carbon\Carbon::parse($lastImportedAt)->format('d/m/Y H:i') : 'sem importação' }}
            </div>
        </div>
    </div>

    @if (!empty($previewRows))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white">Pré-visualização da API</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Bancário</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewRows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['type'] ?: '-' }}</td>
                                <td>{{ $row['is_banking_holiday'] ? 'SIM' : 'NÃO' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-secondary text-white">Calendário local importado</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>UF</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Importado em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($importedRows as $holiday)
                        <tr>
                            <td>{{ $holiday->date?->format('d/m/Y') }}</td>
                            <td>{{ $holiday->state }}</td>
                            <td>{{ $holiday->name }}</td>
                            <td>{{ $holiday->type ?: '-' }}</td>
                            <td>{{ $holiday->imported_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nenhum feriado importado para a UF/ano selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
