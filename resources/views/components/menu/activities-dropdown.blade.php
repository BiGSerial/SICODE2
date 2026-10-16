@props([
    'menuProjeto' => 0,
    'menuConstrucao' => 0,
])

@php
    $toServices = Auth()->user()->ToServices ?? collect();

    $projectDispatchItems = auth()->user()->can('operator')
        ? $toServices->filter(fn($service) => $service->dispatch && $service->Service?->project)
        : collect();

    $projectServiceItems = auth()->user()->can('user')
        ? $toServices->filter(fn($service) => $service->service && $service->Service?->project)
        : collect();

    $constructionDispatchItems = auth()->user()->can('operator')
        ? $toServices->filter(fn($service) => $service->dispatch && $service->Service?->construction)
        : collect();

    $constructionServiceItems = auth()->user()->can('user')
        ? $toServices->filter(fn($service) => $service->service && $service->Service?->construction)
        : collect();

    $showProjeto = $projectDispatchItems->isNotEmpty() || $projectServiceItems->isNotEmpty();
    $showConstrucao = $constructionDispatchItems->isNotEmpty() || $constructionServiceItems->isNotEmpty();
    $showQualityShortcut = auth()->user()->can('quality.access');
    $showProjectReviewShortcut = auth()->user()->can('analyst');

    // Serviços com telas próprias: folder => rota/gate por tipo de menu.
    $specializedRoutes = [
        'encerramento' => [
            'dispatch' => ['route' => 'closure.dispatch', 'can' => 'closure.dispatch'],
            'service'  => ['route' => 'closure.work', 'can' => 'closure.work'],
        ],
    ];
    $specialized = fn($service, string $kind) => $specializedRoutes[$service->Service->folder ?? ''][$kind] ?? null;

    $buildDispatchItems = fn($items) => $items
        ->map(fn($service) => [
            'label' => mb_strtoupper($service->Service->service),
            'route' => $specialized($service, 'dispatch')['route'] ?? 'dispatch.main',
            'routeParams' => $specialized($service, 'dispatch') ? [] : ['service' => $service->service_id],
            'can' => $specialized($service, 'dispatch')['can'] ?? null,
            'icon' => $service->Service->icon,
            'iconClass' => 'text-danger',
        ])
        ->values()
        ->all();

    $buildServiceItems = fn($items) => $items
        ->map(fn($service) => $specialized($service, 'service')
            ? [
                'label' => mb_strtoupper($service->Service->service),
                'route' => $specialized($service, 'service')['route'],
                'can' => $specialized($service, 'service')['can'],
                'icon' => $service->Service->icon,
            ]
            : [
                'label' => mb_strtoupper($service->Service->service),
                'route' => 'services.main',
                'routeParams' => ['service' => $service->service_id],
                'icon' => $service->Service->icon,
                'countComponent' => 'components.count.countnotes',
                'countParams' => ['service' => $service->service_id],
                'countKey' => 'menu' . $service->service_id,
            ])
        ->values()
        ->all();

    $nodes = collect([
        $showQualityShortcut
            ? [
                'kind' => 'item',
                'label' => 'QUALIDADE',
                'route' => 'quality.index',
                'icon' => 'ri-shield-check-line',
            ]
            : null,
        $showProjectReviewShortcut
            ? [
                'kind' => 'item',
                'label' => 'ANÁLISE PROJETO',
                'route' => 'project_review.list',
                'icon' => 'ri-file-search-line',
            ]
            : null,
        $showProjeto
            ? [
                'kind' => 'group',
                'label' => 'PROJETO',
                'open' => 'down',
                'nodes' => array_values(array_filter([
                    $projectDispatchItems->isNotEmpty()
                        ? [
                            'kind' => 'group',
                            'label' => 'DESPACHO',
                            'open' => 'side',
                            'accent' => 'danger',
                            'nodes' => $buildDispatchItems($projectDispatchItems),
                        ]
                        : null,
                    $projectServiceItems->isNotEmpty()
                        ? [
                            'kind' => 'group',
                            'label' => 'SERVIÇO',
                            'open' => 'side',
                            'nodes' => $buildServiceItems($projectServiceItems),
                        ]
                        : null,
                ])),
            ]
            : null,
        $showConstrucao
            ? [
                'kind' => 'group',
                'label' => 'CONSTRUÇÃO',
                'open' => 'down',
                'nodes' => array_values(array_filter([
                    $constructionDispatchItems->isNotEmpty()
                        ? [
                            'kind' => 'group',
                            'label' => 'DESPACHO',
                            'open' => 'side',
                            'accent' => 'danger',
                            'nodes' => $buildDispatchItems($constructionDispatchItems),
                        ]
                        : null,
                    $constructionServiceItems->isNotEmpty()
                        ? [
                            'kind' => 'group',
                            'label' => 'SERVIÇO',
                            'open' => 'side',
                            'nodes' => $buildServiceItems($constructionServiceItems),
                        ]
                        : null,
                ])),
            ]
            : null,
    ])
        ->filter()
        ->values()
        ->all();
@endphp

@if ($showProjeto || $showConstrucao || $showProjectReviewShortcut || $showQualityShortcut)
    <x-menu.dynamic-dropdown
        title="ATIVIDADES"
        :nodes="$nodes"
        width="340px"
        id-prefix="atividades"
        layout="inline"
    />
@endif
