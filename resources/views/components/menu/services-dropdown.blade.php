@props([
    'paymentService' => null,
    'reportsLinks' => [],
])

@php
    $reportItems = collect($reportsLinks)
        ->map(fn($report) => [
            'label' => $report['label'],
            'route' => $report['route'],
            'icon' => 'ri-file-chart-line',
            'visible' => $report['visible'] ?? true,
        ])
        ->values()
        ->all();

    $canViewReports = auth()->check()
        && (auth()->user()->can('management') || auth()->user()->can('projectReviewReports'));

    $sections = [
        [
            'items' => [
                [
                    'label' => 'CANCELAMENTO DE NOTAS',
                    'route' => 'cancellations.index',
                    'icon' => 'ri-close-circle-line',
                ],
                [
                    'label' => 'OCORRÊNCIAS',
                    'route' => 'occurrences.index',
                    'icon' => 'ri-alarm-warning-line',
                    'can' => 'occ.access',
                ],
            ],
            'children' => [
                [
                    'label' => 'RELATÓRIOS',
                    'visible' => $canViewReports,
                    'open' => 'side',
                    'items' => $reportItems,
                ],
            ],
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="SERVIÇOS"
    :sections="$sections"
    width="340px"
    id-prefix="servicos"
    layout="inline"
/>
