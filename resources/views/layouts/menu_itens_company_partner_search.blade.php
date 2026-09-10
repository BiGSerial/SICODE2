@php
    $partnerCan = fn (string $permission) => \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), $permission);
    $partner_search_sections = [
        [
            'items' => [
                ...($partnerCan('portal.search_notes')
                    ? [['label' => 'BUSCAR NOTAS', 'route' => 'partner.search.notes', 'icon' => 'ri-search-eye-line']]
                    : []),
            ],
        ],
    ];
@endphp

@if (collect($partner_search_sections)->flatMap(fn ($section) => $section['items'])->isNotEmpty())
    <x-menu.dynamic-dropdown title="BUSCAR" :sections="$partner_search_sections" id-prefix="partner-buscar" layout="inline" />
@endif

@stack('modals')
