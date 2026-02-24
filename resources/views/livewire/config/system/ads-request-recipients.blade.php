<div class="ads-rec-page">
    <x-show-loading />

    <style>
        .ads-rec-page {
            --ads-bg: #f6f7fb;
            --ads-surface: #ffffff;
            --ads-ink: #1f2933;
            --ads-muted: #6b7280;
            --ads-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--ads-bg);
            padding: 1.5rem 0;
        }

        .ads-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .ads-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .ads-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--ads-surface);
            border: 1px solid var(--ads-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .filters-grid .filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--ads-muted);
        }

        .summary-bar {
            background: var(--ads-surface);
            border: 1px solid var(--ads-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-item {
            font-size: 0.92rem;
            color: var(--ads-muted);
        }

        .summary-item strong {
            color: var(--ads-ink);
        }

        .table-card {
            background: var(--ads-surface);
            border: 1px solid var(--ads-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }
    </style>

    <div class="container-fluid">
        <div class="ads-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>Automacao ADS</h2>
                <div class="meta">Gestao de destinatarios e roteamento automatico</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Destinatarios ativos</div>
                <div><strong>{{ $recipients->count() }}</strong></div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12 col-xl-4">
                        <div class="filter-card">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                <h6 class="mb-0">Modo de execucao</h6>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" wire:model="testMode" id="adsAutoTestMode">
                                    <label class="form-check-label" for="adsAutoTestMode">Teste</label>
                                </div>
                            </div>

                            <div class="alert {{ $testMode ? 'alert-warning' : 'alert-success' }} mb-0 py-2">
                                @if ($testMode)
                                    <strong>Ativo:</strong> cria local e nao envia para SQL Server.
                                @else
                                    <strong>Inativo:</strong> cria local e envia para SQL Server.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="filter-card">
                            <h6>Roteamento automatico</h6>
                            <div class="form-floating mb-2">
                                <select class="form-select border border-secondary" wire:model="selectedServiceId"
                                    id="adsDefaultService">
                                    <option value="">Sem servico padrao (usa apenas destinatarios padrao)</option>
                                    @foreach ($serviceOptions as $service)
                                        <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                    @endforeach
                                </select>
                                <label for="adsDefaultService">Servico padrao</label>
                            </div>
                            <div class="small text-muted">
                                Se houver producao atribuida (`status = 2`) neste servico para a nota, a solicitacao vai direto para esse usuario.
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <div class="filter-card">
                            <h6>Adicionar destinatario</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control border border-secondary"
                                            wire:model.debounce.500ms="search" id="adsUserSearch"
                                            placeholder="Digite nome ou e-mail">
                                        <label for="adsUserSearch">Buscar usuario</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <select class="form-select border border-secondary" wire:model="selectedUserId"
                                            id="adsUserSelect">
                                            <option value="">Selecione...</option>
                                            @foreach ($candidates as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                            @endforeach
                                        </select>
                                        <label for="adsUserSelect">Selecionar usuario</label>
                                    </div>
                                </div>
                                <div class="col-12 d-grid">
                                    <button type="button" class="btn btn-primary" wire:click="addRecipient"
                                        @disabled(!$selectedUserId)>
                                        <span wire:loading.remove wire:target="addRecipient">Adicionar destinatario</span>
                                        <span wire:loading wire:target="addRecipient">Adicionando...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="summary-item">
                        Servico padrao atual:
                        <strong>{{ optional($serviceOptions->firstWhere('uuid', $selectedServiceId))->service ?? 'Nao configurado' }}</strong>
                    </div>
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Busca retornou <strong>{{ $candidates->count() }}</strong> usuario(s).
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Cadastrado em</th>
                            <th style="width: 120px;">Acoes</th>
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
                                        wire:click="removeRecipient({{ $item->id }})"
                                        wire:loading.attr="disabled" wire:target="removeRecipient({{ $item->id }})">
                                        Remover
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhum usuario configurado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
