@php
    $partner_search_sections = [
        [
            'items' => [
                ['label' => 'BUSCAR NOTAS', 'route' => 'partner.search.notes', 'icon' => 'ri-search-eye-line'],
            ],
        ],
    ];
@endphp

<x-menu.dynamic-dropdown title="BUSCAR" :sections="$partner_search_sections" id-prefix="partner-buscar" layout="inline" />

@stack('modals')

