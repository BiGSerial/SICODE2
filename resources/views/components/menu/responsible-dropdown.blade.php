@php
    $sections = [
        [
            'label' => 'RESPONSÁVEL',
            'items' => [
                ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'responsible.validation', 'icon' => 'ri-file-search-line'],
                ['label' => 'VIABILIDADE', 'route' => 'responsible.viab_list', 'icon' => 'ri-bar-chart-line'],
                ['label' => 'INFORMES CONCLUSÃO', 'route' => 'responsible.informes', 'icon' => 'ri-file-text-line'],
                ['label' => 'INFORMES PARCIAIS', 'route' => 'responsible.parciais', 'icon' => 'ri-file-list-3-line'],
                ['label' => 'NOTAS D5', 'route' => 'responsible.d5', 'icon' => 'ri-sticky-note-line'],
            ],
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="RESPONSÁVEL"
    :sections="$sections"
    id-prefix="responsavel"
    layout="inline"
/>
