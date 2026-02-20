@push('css')
    <style>
        .protest-page {
            --pp-bg: #f6f7fb;
            --pp-surface: #ffffff;
            --pp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--pp-bg);
            padding: 1.5rem 0;
        }

        .protest-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .protest-filter-shell {
            background: var(--pp-surface);
            border: 1px solid var(--pp-border);
            border-radius: 1rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
@endpush

<div class="protest-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="protest-header">
            <h4 class="mb-0">Histórico de Atividades do Parceiro</h4>
            <small class="text-white-50">Reclamações concluídas do usuário logado</small>
        </div>

        <div class="protest-filter-shell">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="form-floating">
                        <select wire:model="perPage" id="perPage" class="form-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="perPage">Registros por página</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-floating">
                        <input wire:model.debounce.400ms="search" type="text" id="search" class="form-control"
                            placeholder="Buscar por nota ou observação">
                        <label for="search">Buscar por nota / observação</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-floating">
                        <input wire:model="month" type="month" id="month" class="form-control"
                            max="{{ date('Y-m') }}">
                        <label for="month">Mês de encerramento</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-floating">
                        <input wire:model="dt_start" type="date" id="dt_start" class="form-control"
                            max="{{ $dt_end ?? date('Y-m-d') }}">
                        <label for="dt_start">Data início</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-floating">
                        <input wire:model="dt_end" type="date" id="dt_end" class="form-control"
                            min="{{ $dt_start }}" max="{{ date('Y-m-d') }}">
                        <label for="dt_end">Data fim</label>
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="clearFilters" type="button" class="btn btn-outline-secondary w-100"
                        title="Limpar filtros">
                        <i class="ri-refresh-line me-1"></i> Limpar
                    </button>
                </div>
            </div>
        </div>

        @if ($list->count() > 0)
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">
                    <i class="ri-information-line"></i>
                    Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
                </div>
                <div>
                    {{ $list->links() }}
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header text-bg-primary">
                <h5 class="mb-0">HISTÓRICO DE ATIVIDADES - PARCEIRO</h5>
            </div>
            <div class="table-responsive">
                @if ($list->count() > 0)
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr class="text-center">
                                <th class="col-1">Reclamação</th>
                                <th class="col-1">Tipo</th>
                                <th class="col-1">Medida</th>
                                <th class="col-2">Responsável</th>
                                <th class="col-1">Abertura Recl.</th>
                                <th class="col-1">Prazo Oficial</th>
                                <th class="col-1">Medida Enc.</th>
                                <th class="col-1">Encerrada SICODE</th>
                                <th class="col-1">SLA</th>
                                <th class="col-2">Nota Ref.</th>
                                <th class="col-2">Observação</th>
                                <th class="col-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list as $job)
                                @php
                                    $med = $job->medProtest;
                                    $protest = $med?->protest;
                                    $deadline = $this->deadlineFor($job);
                                    $closedAt = $job->closed_at ?? $job->finished_at;
                                    $withinSla = $this->finishedWithinDeadline($job);
                                    $noteRef = $protest?->notes?->last() ?? $med?->notes?->last();
                                @endphp
                                <tr class="text-center" wire:key="partner-job-{{ $job->id }}">
                                    <td class="fw-semibold">{{ $protest?->nota ?? '-' }}</td>
                                    <td class="fw-semibold">{{ $protest?->tipoNota ?? '-' }}</td>
                                    <td>{{ $med?->med_id ?? '-' }}</td>
                                    <td>{{ $job->owner?->name ?? '–' }}</td>
                                    <td>{{ optional($protest?->dtAberturaNota)->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ optional($deadline)->format('d/m/Y') ?? 'Sem prazo' }}</td>
                                    <td>{{ optional($med?->dtFimMedida)->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ optional($closedAt)->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        @if (is_null($withinSla))
                                            <span class="badge bg-secondary-subtle text-secondary">Sem prazo</span>
                                        @elseif ($withinSla)
                                            <span class="badge bg-success-subtle text-success">Dentro do prazo</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Fora do prazo</span>
                                        @endif
                                    </td>
                                    <td>{{ $noteRef?->note ?? 'Sem anotação' }}</td>
                                    <td class="text-start">{{ \Illuminate\Support\Str::limit($job->close_reason ?? '-', 80) }}</td>
                                    <td>
                                        @if ($job->med_protest_id)
                                            <a href="{{ route('protests.partner.view', $job->id) }}" class="text-primary"
                                                title="Visualizar">
                                                <i class="ri-play-circle-fill fs-4"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info mb-0">
                        Nenhum registro encontrado para os filtros informados.
                    </div>
                @endif
            </div>
        </div>

        @if ($list->count() > 0)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">
                    <i class="ri-information-line"></i>
                    Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
                </div>
                <div>
                    {{ $list->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
