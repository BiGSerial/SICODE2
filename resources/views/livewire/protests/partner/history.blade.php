<div>
    <div class="mb-4">
        <div class="row g-3 mb-4">
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

            <div class="col-md-1 d-flex align-items-end">
                <button wire:click="clearFilters" type="button" class="btn btn-outline-secondary w-100"
                    title="Limpar filtros">
                    <i class="ri-refresh-line"></i>
                </button>
            </div>
        </div>
    </div>

    @if ($list->count() > 0)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <i class="ri-information-line"></i>
                Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
            </div>
            <div>
                {{ $list->links() }}
            </div>
        </div>
    @endif

    <div class="card">
        <h4 class="card-header">HISTÓRICO DE ATIVIDADES - PARCEIRO</h4>
        <div class="table-responsive">
            @if ($list->count() > 0)
                <table class="table table-striped table-bordered align-middle">
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
                                <td>
                                    {{ $noteRef?->note ?? 'Sem anotação' }}
                                </td>
                                <td class="text-start">
                                    {{ \Illuminate\Support\Str::limit($job->close_reason ?? '-', 80) }}
                                </td>
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
            <div>
                <i class="ri-information-line"></i>
                Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
            </div>
            <div>
                {{ $list->links() }}
            </div>
        </div>
    @endif
</div>
