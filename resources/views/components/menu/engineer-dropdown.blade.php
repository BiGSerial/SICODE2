@php
    $sections = [
        [
            'label' => 'ENGENHARIA',
            'items' => [
                ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'engineers.validation'],
                ['label' => 'VIABILIDADE', 'route' => 'engineers.viab_list'],
                ['label' => 'INFORMES CONCLUSÃO', 'route' => 'engineers.informes'],
                [
                    'label' => 'INFORMES PARCIAIS',
                    'route' => 'engineers.parciais',
                    'countComponent' => 'engineers.counts.count-parcial',
                    'countKey' => 'engineer-parciais-awaiting-top',
                ],
                ['label' => 'NOTAS D5', 'route' => 'engineers.d5'],
                [
                    'label' => 'CANCELAMENTO',
                    'route' => 'engineers.cancellations.index',
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
    width="320px"
    id-prefix="engenharia"
    layout="inline"
/>
