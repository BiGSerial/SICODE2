@props(['request'])

@php
    $isWorkReportScope = ($request->scope?->value ?? $request->scope) === \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value;
    $requestedReports = $isWorkReportScope ? $request->WorkReports : collect();
@endphp

@if ($isWorkReportScope)
    <div class="oexterno-subcard mt-3" style="height:auto">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="section-title mb-0">Informes da solicitação</div>
            @if ($requestedReports->isNotEmpty())
                <span class="badge rounded-pill bg-secondary">{{ $requestedReports->count() }}</span>
            @endif
        </div>
        @if ($requestedReports->isEmpty())
            <p class="mb-0 small text-muted">
                Solicitação anterior à escolha de informe: abrange todos os informes ativos da nota.
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th>Informe</th>
                            <th>Informado em</th>
                            <th>Situação</th>
                            <th>Cancelamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requestedReports as $report)
                            <tr>
                                <td class="fw-semibold">{{ $report->displayLabel() }}</td>
                                <td>{{ $report->informed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td><span class="badge {{ $report->canceled ? 'bg-secondary' : 'bg-info text-dark' }}">{{ $report->current_status_label ?: '-' }}</span></td>
                                <td>@if ($report->canceled)<span class="badge bg-danger"><i class="ri-close-circle-line align-middle"></i> Cancelado</span>@else<span class="badge bg-light text-muted border">Ativo</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
