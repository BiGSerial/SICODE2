@php
    // Mesma definição do menu lateral (App\Support\Menu\EngineerNav): topo e lateral nunca divergem.
    $sections = [
        [
            'label' => 'ENGENHARIA',
            'items' => \App\Support\Menu\EngineerNav::topItems(),
        ],
    ];
@endphp

<x-menu.dynamic-dropdown
    title="ENGENHARIA"
    :sections="$sections"
    id-prefix="engenharia"
    layout="inline"
/>
