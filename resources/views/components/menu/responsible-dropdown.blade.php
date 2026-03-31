@php
    $sections = [
        [
            'label' => 'RESPONSÁVEL',
            'items' => [
                ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'responsible.validation'],
                ['label' => 'VIABILIDADE', 'route' => 'responsible.viab_list'],
                ['label' => 'INFORMES CONCLUSÃO', 'route' => 'responsible.informes'],
                ['label' => 'INFORMES PARCIAIS', 'route' => 'responsible.parciais'],
                ['label' => 'NOTAS D5', 'route' => 'responsible.d5'],
            ],
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="RESPONSÁVEL"
    :sections="$sections"
    width="320px"
    id-prefix="responsavel"
    layout="inline"
/>
