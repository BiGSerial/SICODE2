@props(['request'])

@php
    $isWorkReportScope = ($request->scope?->value ?? $request->scope) === \App\Enum\CancellationRequestScope::WORK_FORM_ONLY->value;
    $requestedReports = $isWorkReportScope ? $request->WorkReports : collect();
@endphp

@if ($isWorkReportScope)
    <div class="oexterno-subcard mt-3">
        <div class="section-title">Informes</div>
        @if ($requestedReports->isEmpty())
            <p class="mb-0 small text-muted">
                Solicitação anterior à escolha de informe: abrange todos os informes ativos da nota.
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Informe</th>
                            <th>Informado em</th>
                            <th>Situação</th>
                            <th>Cancelado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requestedReports as $report)
                            <tr>
                                <td>{{ $report->displayLabel() }}</td>
                                <td>{{ $report->informed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>{{ $report->current_status_label ?: '-' }}</td>
                                <td>{{ $report->canceled ? 'Sim' : 'Não' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
