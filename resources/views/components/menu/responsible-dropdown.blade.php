@php
    // Mesma definição do menu lateral (App\Support\Menu\ResponsibleNav): topo e lateral nunca divergem.
    $sections = [
        [
            'label' => 'RESPONSÁVEL',
            'items' => \App\Support\Menu\ResponsibleNav::topItems(),
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="RESPONSÁVEL"
    :sections="$sections"
    id-prefix="responsavel"
    layout="inline"
/>
