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
        ])
        ->values()
        ->all();

    $sections = [
        [
            'label' => 'SERVIÇOS GERAIS',
            'items' => [
                [
                    'label' => 'CANCELAMENTO DE NOTAS',
                    'route' => 'cancellations.index',
                    'icon' => 'ri-close-circle-line',
                ],
            ],
            'children' => [
                [
                    'label' => 'RELATÓRIOS',
                    'can' => 'management',
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
/>
