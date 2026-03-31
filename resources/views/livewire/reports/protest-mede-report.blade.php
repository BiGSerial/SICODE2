@push('css')
    <style>
        .pmr-page {
            --pmr-bg: #f6f7fb;
            --pmr-surface: #ffffff;
            --pmr-ink: #1f2933;
            --pmr-muted: #6b7280;
            --pmr-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--pmr-bg);
            padding: 1.5rem 0;
        }

        .pmr-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .pmr-filter-card {
            background-color: var(--pmr-surface);
            border: 1px solid var(--pmr-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .pmr-filter-card .form-label {
            margin-bottom: 0.55rem;
        }

        .pmr-filter-card .form-control,
        .pmr-filter-card .form-select {
            min-height: 46px;
            font-size: 1rem;
        }

        .pmr-filter-card .pmr-type-select {
            min-height: 170px;
        }

        .pmr-info-card {
            background: var(--pmr-surface);
            border: 1px solid var(--pmr-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            padding: 1rem 1.25rem;
        }

        .pmr-table-card {
            background: var(--pmr-surface);
            border: 1px solid var(--pmr-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .pmr-pagination {
            background: #fff;
            border: 1px solid var(--pmr-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            margin-bottom: 0.75rem;
        }
    </style>
@endpush

<div class="pmr-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="pmr-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-1">RELATÓRIO DE RECLAMAÇÃO</h2>
                <div class="text-light opacity-75">Exporta a base de medidas encerradas (MEDE) do Painel 5 com colunas de conclusão</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" wire:click="clearFilters">
                    <i class="ri-filter-off-line me-1"></i> Limpar
                </button>
                <button class="btn btn-light btn-sm text-dark" wire:click="exportReport" wire:loading.attr="disabled"
                    wire:target="exportReport">
                    <span wire:loading.remove wire:target="exportReport">
                        <i class="ri-file-excel-2-line me-1"></i> Exportar relatório
                    </span>
                    <span wire:loading wire:target="exportReport">Gerando...</span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pmr-filter-card">
                    <label class="form-label small text-muted">Data inicial</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="dt_in"
                        max="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pmr-filter-card">
                    <label class="form-label small text-muted">Data final</label>
                    <input type="date" class="form-control border border-secondary" wire:model.lazy="dt_out"
                        max="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pmr-filter-card">
                    <label class="form-label small text-muted">Despachante</label>
                    <select wire:model="userId" class="form-select border border-secondary">
                        <option value="">Todos</option>
                        @foreach ($usersOptions as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pmr-filter-card">
                    <label class="form-label small text-muted">Tipos de reclamação</label>
                    <select class="form-select border border-secondary pmr-type-select" wire:model="protestTypes" multiple size="6">
                        @foreach ($protestTypeOptions as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pmr-filter-card">
                    <label class="form-label small text-muted">Registros por página</label>
                    <select wire:model="perPage" class="form-select border border-secondary">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="pmr-info-card">
            <div class="fw-semibold mb-1">Escopo do relatório</div>
            <div class="text-muted">
                Base igual ao <strong>Painel 5 - Produtividade dos Despachantes (MEDE)</strong>, incluindo as colunas
                <strong>Conclusao Nota</strong> e <strong>Conclusao Medida</strong>.
            </div>
        </div>

        @if ($rows->count())
            <div class="pmr-pagination">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <small>
                            Exibindo {{ $rows->firstItem() }} até {{ $rows->lastItem() }} de {{ $rows->total() }}
                        </small>
                    </div>
                </div>
            </div>
        @endif

        <div class="pmr-table-card mt-3">
            @if (!$rows->count())
                <div class="card-body">
                    <h5 class="text-center text-muted mb-0">Nenhuma reclamação encontrada para os filtros informados.</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Medida</th>
                                <th>Reclamação</th>
                                <th>Tipo Nota</th>
                                <th>Status Medida</th>
                                <th>Conclusão Nota</th>
                                <th>Conclusão Medida</th>
                                <th>Abertura Reclamação</th>
                                <th>Criação Medida</th>
                                <th>Conclusão desejada</th>
                                <th>Fim Medida</th>
                                <th>Dentro do prazo</th>
                                <th>Despachante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row->med_id ?? '---' }}</td>
                                    <td>{{ $row->protest_nota ?? '---' }}</td>
                                    <td>{{ $row->protest_tipo_nota ?? '---' }}</td>
                                    <td>{{ $row->statusSist ?? '---' }}</td>
                                    <td>{{ $row->protest_stat_usuar ?? '---' }}</td>
                                    <td>{{ $row->statMedida ?? '---' }}</td>
                                    <td>{{ $row->protest_dt_abertura_nota_fmt }}</td>
                                    <td>{{ $row->dt_criacao_medida_fmt }}</td>
                                    <td>{{ $row->due_base_fmt }}</td>
                                    <td>{{ $row->dt_fim_medida_fmt }}</td>
                                    <td>
                                        @if ($row->is_on_time)
                                            <span class="badge bg-success">Sim</span>
                                        @else
                                            <span class="badge bg-danger">Não</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->dispatcher_name ?? '---' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
