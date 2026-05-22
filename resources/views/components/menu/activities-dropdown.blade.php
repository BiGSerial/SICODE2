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
    $showProjectReviewShortcut = auth()->user()->can('analyst');

    $buildDispatchItems = fn($items) => $items
        ->map(fn($service) => [
            'label' => mb_strtoupper($service->Service->service),
            'route' => 'dispatch.main',
            'routeParams' => ['service' => $service->service_id],
            'icon' => $service->Service->icon,
            'iconClass' => 'text-danger',
        ])
        ->values()
        ->all();

    $buildServiceItems = fn($items) => $items
        ->map(fn($service) => [
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

@if ($showProjeto || $showConstrucao || $showProjectReviewShortcut)
    <x-menu.dynamic-dropdown
        title="ATIVIDADES"
        :nodes="$nodes"
        width="340px"
        id-prefix="atividades"
        layout="inline"
    />
@endif
