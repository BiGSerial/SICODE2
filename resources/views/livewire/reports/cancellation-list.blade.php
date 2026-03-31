<div class="ri-page">
    <x-show-loading />

    @push('css')
        <style>
            .ri-page {
                --ri-bg: #f7f8fb;
                --ri-border: #e5e7eb;
                background: radial-gradient(circle at 12% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 15%, #ecfeff, transparent 35%),
                    var(--ri-bg);
                padding: 1.5rem 0;
                font-family: var(--bs-body-font-family, var(--bs-font-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif));
            }

            .ri-page,
            .ri-page * {
                font-family: var(--bs-body-font-family, var(--bs-font-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif)) !important;
            }

            .ri-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.3rem 1.6rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1rem;
            }

            .panel {
                background: #fff;
                border: 1px solid var(--ri-border);
                border-radius: 1rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .table thead th {
                font-size: 0.74rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                white-space: nowrap;
            }
        </style>
    @endpush

    <div class="container-fluid">
        <div class="ri-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div>
                <h4 class="mb-1">Lista Geral de Cancelamentos</h4>
                <div class="small text-white-50">Consulta completa com filtros de período e indicadores por solicitação.</div>
            </div>
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Período inicial</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dateFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Período final</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dateTo">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model="status">
                        <option value="">Todos</option>
                        @foreach($statusOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tipo</label>
                    <select class="form-select form-select-sm" wire:model="scope">
                        <option value="">Todos</option>
                        @foreach($scopeOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Categoria</label>
                    <select class="form-select form-select-sm" wire:model="categoryId">
                        <option value="">Todas</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Busca (ID/Nota)</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Ex: 12345, 8899001"
                        wire:model.debounce.500ms="search">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Visão</label>
                    <select class="form-select form-select-sm" wire:model="visibilityMode">
                        @foreach($visibilityOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label small mb-1">Solicitante (um ou mais)</label>
                    <select class="form-select form-select-sm" wire:model="requesterIds" multiple size="4">
                        @foreach($requesterOptions as $requester)
                            <option value="{{ $requester->id }}">{{ $requester->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Dica: segure `Ctrl` para múltipla seleção.</small>
                </div>
            </div>
        </div>

        <div class="panel p-3">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Aguardando</th>
                            <th>Solicitante</th>
                            <th>Executor</th>
                            <th>Engenheiro</th>
                            <th>Abertura</th>
                            <th>Encerramento</th>
                            <th>Execução</th>
                            <th>Aprov. Eng.</th>
                            <th>Encerramento</th>
                            <th>Finalização</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->note_number ?: '-' }}</td>
                                <td><span class="badge bg-dark">{{ $row->category_name ?: '-' }}</span></td>
                                <td><span class="badge {{ $row->scope_badge_class }}">{{ $row->scope_label }}</span></td>
                                <td><span class="badge {{ $row->status_badge_class }}">{{ $row->status_label }}</span></td>
                                <td>
                                    @if($row->waiting_label)
                                        <span class="badge {{ $row->waiting_badge_class }}">{{ $row->waiting_label }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $row->requester_name ?: '-' }}</td>
                                <td>{{ $row->assignee_name ?: '-' }}</td>
                                <td>{{ $row->engineer_name ?: '-' }}</td>
                                <td>{{ optional(\Carbon\Carbon::parse($row->opened_at))->format('d/m/Y H:i') }}</td>
                                <td>{{ $row->closed_at ? \Carbon\Carbon::parse($row->closed_at)->format('d/m/Y H:i') : '-' }}</td>
                                <td>{{ $row->exec_human }}</td>
                                <td>
                                    <div><span class="badge {{ $row->engineer_approval_badge_class }}">{{ $row->engineer_approval_label }}</span></div>
                                    <div class="small text-muted mt-1">{{ $row->eng_human }}</div>
                                </td>
                                <td>{{ $row->close_human }}</td>
                                <td>{{ $row->final_human }}</td>
                                <td>
                                    <a href="{{ route('cancellations.show', ['request' => $row->id]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center text-muted">Nenhum cancelamento encontrado no período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</div>
