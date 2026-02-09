<div>
    <x-show-loading />

    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0">Destinatários padrão de solicitação automática ADS</h6>
        </div>

        <div class="card-body">
            <div class="alert {{ $testMode ? 'alert-warning' : 'alert-success' }} d-flex justify-content-between align-items-center">
                <div>
                    <strong>Modo teste da automação ADS:</strong>
                    @if ($testMode)
                        Ativo. Cria local e não envia para SQL Server.
                    @else
                        Inativo. Cria local e envia para SQL Server.
                    @endif
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" wire:model="testMode" id="adsAutoTestMode">
                    <label class="form-check-label" for="adsAutoTestMode">Teste</label>
                </div>
            </div>

            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-5">
                    <label class="form-label">Buscar usuário</label>
                    <input type="text" class="form-control" wire:model.debounce.500ms="search"
                        placeholder="Nome ou e-mail">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Selecionar</label>
                    <select class="form-select" wire:model="selectedUserId">
                        <option value="">Selecione...</option>
                        @foreach ($candidates as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="button" class="btn btn-primary" wire:click="addRecipient">Adicionar</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Cadastrado em</th>
                            <th style="width: 120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recipients as $item)
                            <tr>
                                <td>{{ $item->user?->name ?? '---' }}</td>
                                <td>{{ $item->user?->email ?? '---' }}</td>
                                <td>{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="removeRecipient({{ $item->id }})">
                                        Remover
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nenhum usuário configurado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
