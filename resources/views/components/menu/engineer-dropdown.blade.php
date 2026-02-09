@php
    $sections = [
        [
            'label' => 'ENGENHARIA',
            'items' => [
                ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'engineers.validation'],
                ['label' => 'VIABILIDADE', 'route' => 'engineers.viability'],
                ['label' => 'INFORMES CONCLUSÃO', 'route' => 'engineers.informes'],
                ['label' => 'INFORMES PARCIAIS', 'route' => 'engineers.parciais'],
                ['label' => 'NOTAS D5', 'route' => 'engineers.d5'],
            ],
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="ENGENHARIA"
    :sections="$sections"
    width="320px"
    id-prefix="engenharia"
/>
