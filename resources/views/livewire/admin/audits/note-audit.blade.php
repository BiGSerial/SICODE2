<div class="audit-page">
    <x-show-loading />

    <style>
        .audit-page {
            --audit-bg: #f6f7fb;
            --audit-surface: #ffffff;
            --audit-ink: #1f2933;
            --audit-muted: #6b7280;
            --audit-accent: #0f766e;
            --audit-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--audit-bg);
            padding: 1.5rem 0;
        }

        .audit-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .audit-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .audit-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .audit-filter-card {
            background-color: var(--audit-surface);
            border: 1px solid var(--audit-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .audit-filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--audit-muted);
        }

        .audit-table-card {
            background: var(--audit-surface);
            border: 1px solid var(--audit-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .audit-table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }
    </style>

    <div class="container-fluid">
        <div class="audit-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>AUDITORIA DE NOTAS</h2>
                <div class="meta">Consulta de manobras por nota, modelo e atividade</div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3">
                    <div class="col-12 col-xl-9">
                        <div class="audit-filter-card">
                            <h6>Filtros</h6>
                            <form wire:submit.prevent="applyFilters" class="row g-2 align-items-end">
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Nota</label>
                                    <input type="text" class="form-control" wire:model.defer="note"
                                        placeholder="Numero da nota">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Atividade</label>
                                    <select class="form-select" wire:model.defer="serviceId">
                                        <option value="">Todas</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label">Acao</label>
                                    <select class="form-select" wire:model.defer="action">
                                        <option value="">Todas</option>
                                        <option value="created">created</option>
                                        <option value="updated">updated</option>
                                        <option value="deleted">deleted</option>
                                        <option value="restored">restored</option>
                                        <option value="force_deleted">force_deleted</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Modelo</label>
                                    <select class="form-select" wire:model.defer="modelClass">
                                        <option value="">Todos</option>
                                        @foreach ($modelOptions as $modelOption)
                                            <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Usuario</label>
                                    <select class="form-select" wire:model.defer="userId">
                                        <option value="">Todos</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">De</label>
                                    <input type="date" class="form-control" wire:model.defer="dateFrom">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Ate</label>
                                    <input type="date" class="form-control" wire:model.defer="dateTo">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Por pagina</label>
                                    <select class="form-select" wire:model="perPage">
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 d-flex gap-2 justify-content-end">
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                    <button type="button" class="btn btn-outline-secondary"
                                        wire:click="clearFilters">Limpar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-12 col-xl-3">
                        <div class="audit-filter-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h6>Resumo</h6>
                                <div class="text-muted small">Use os filtros para localizar a auditoria desejada.</div>
                            </div>
                            <div class="mt-3">
                                <span class="badge text-bg-secondary">{{ $audits->total() }}</span>
                                <span class="text-muted small ms-1">registros encontrados</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="audit-table-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuario</th>
                                <th>Acao</th>
                                <th>Modelo</th>
                                <th>Nota</th>
                                <th>Atividade</th>
                                <th>Mudancas</th>
                                <th class="text-end">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($audits as $audit)
                                @php
                                    $after = json_decode($audit->after ?? '', true) ?? [];
                                    $before = json_decode($audit->before ?? '', true) ?? [];
                                    $noteId = $after['note_id'] ?? $before['note_id'] ?? null;
                                    $serviceId = $after['service_id'] ?? $before['service_id'] ?? null;
                                    $noteLabel = $noteId ? ($noteMap[$noteId] ?? $noteId) : '-';
                                    $serviceLabel = $serviceId ? ($serviceMap[$serviceId] ?? $serviceId) : '-';
                                @endphp
                                <tr>
                                    <td>{{ date('d/m/Y H:i:s', strtotime($audit->created_at)) }}</td>
                                    <td>{{ $audit->User->name ?? 'N/A' }}</td>
                                    <td>{{ strtoupper($audit->action) }}</td>
                                    <td>{{ $audit->model_class }}</td>
                                    <td>{{ $noteLabel }}</td>
                                    <td>{{ $serviceLabel }}</td>
                                    <td>{{ $this->diffCount($audit) }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            wire:click="openDetails({{ $audit->id }})">
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Nenhuma auditoria encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($audits->hasPages())
                    <div class="mt-3">
                        {{ $audits->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="modal fade" id="audit_note_detail" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="audit_note_detailLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="audit_note_detailLabel">Detalhes da Auditoria</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($selectedAudit)
                            @php
                                $summaryNote = $selectedSummary['note_id'] ?? null;
                                $summaryService = $selectedSummary['service_id'] ?? null;
                                $summaryNoteLabel = $summaryNote ? ($noteMap[$summaryNote] ?? $summaryNote) : '-';
                                $summaryServiceLabel = $summaryService
                                    ? ($serviceMap[$summaryService] ?? $summaryService)
                                    : '-';
                            @endphp
                            <dl class="row">
                                <dt class="col-sm-3">Usuario:</dt>
                                <dd class="col-sm-9">{{ $selectedAudit->User->name ?? 'N/A' }}</dd>
                                <dt class="col-sm-3">Data:</dt>
                                <dd class="col-sm-9">
                                    {{ date('d/m/Y H:i:s', strtotime($selectedAudit->created_at)) }}
                                </dd>
                                <dt class="col-sm-3">Acao:</dt>
                                <dd class="col-sm-9">{{ strtoupper($selectedAudit->action) }}</dd>
                                <dt class="col-sm-3">Modelo:</dt>
                                <dd class="col-sm-9">{{ $selectedAudit->model_class }}</dd>
                                <dt class="col-sm-3">Nota:</dt>
                                <dd class="col-sm-9">{{ $summaryNoteLabel }}</dd>
                                <dt class="col-sm-3">Atividade:</dt>
                                <dd class="col-sm-9">{{ $summaryServiceLabel }}</dd>
                            </dl>

                            @if (count($selectedDiff))
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Campo</th>
                                                <th>Antes</th>
                                                <th>Depois</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($selectedDiff as $diff)
                                                <tr>
                                                    <td class="fw-bold">{{ $diff['field'] }}</td>
                                                    <td>{{ $diff['before'] }}</td>
                                                    <td>{{ $diff['after'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-secondary mb-0">
                                    Nenhuma diferenca encontrada entre before e after.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-secondary mb-0">
                                Nenhum registro selecionado.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
