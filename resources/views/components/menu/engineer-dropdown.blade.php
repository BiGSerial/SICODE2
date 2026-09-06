@php
    $sections = [
        [
            'label' => 'ENGENHARIA',
            'items' => [
                ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'engineers.validation', 'icon' => 'ri-file-search-line'],
                ['label' => 'VIABILIDADE', 'route' => 'engineers.viab_list', 'icon' => 'ri-bar-chart-line'],
                ['label' => 'INFORMES CONCLUSÃO', 'route' => 'engineers.informes', 'icon' => 'ri-file-text-line'],
                [
                    'label' => 'INFORMES PARCIAIS',
                    'route' => 'engineers.parciais',
                    'icon' => 'ri-file-list-3-line',
                    'countComponent' => 'engineers.counts.count-parcial',
                    'countKey' => 'engineer-parciais-awaiting-top',
                ],
                ['label' => 'NOTAS D5', 'route' => 'engineers.d5', 'icon' => 'ri-sticky-note-line'],
                [
                    'label' => 'CANCELAMENTO',
                    'route' => 'engineers.cancellations.index',
                    'icon' => 'ri-close-circle-line',
                    'countComponent' => 'components.count.cancellation-requests',
                    'countParams' => ['mode' => 'engineer_pending', 'userId' => (string) auth()->id()],
                    'countKey' => 'engineer-cancellations-pending-top',
                ],
            ],
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="ENGENHARIA"
    :sections="$sections"
    id-prefix="engenharia"
    layout="inline"
/>
