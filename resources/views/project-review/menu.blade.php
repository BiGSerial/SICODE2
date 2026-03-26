@php
    use App\Models\Production;

    $pendingProjectReviewBase = Production::query()
        ->where('status', Production::STATUS_IN_PROJECT_REVIEW);

    $pendingProjectReviewCount = (clone $pendingProjectReviewBase)->count();

    $pendingReturnsCount = (clone $pendingProjectReviewBase)
        ->where(function ($q) {
            $q->whereHas('ProjectReviewCycles', function ($cycleQuery) {
                $cycleQuery->where('round_number', '>', 1);
            })->orWhereHas('ProjectReviewCycles', function ($cycleQuery) {
                $cycleQuery->where('decision', 'REJECTED');
            })->orWhereHas('Notetimelines', function ($timelineQuery) {
                $timelineQuery->where('status', Production::STATUS_REJECTED_PROJECT_REVIEW);
            });
        })
        ->count();
@endphp

<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.categories') ? '' : 'collapsed' }}"
               href="{{ route('project_review.categories') }}">
                <i class="ri-price-tag-3-line"></i><span>CATEGORIAS</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.list') ? '' : 'collapsed' }}"
               href="{{ route('project_review.list') }}">
                <i class="ri-task-line"></i>
                <span>LISTA PARA ANALISAR</span>
                <div class="ms-auto d-flex align-items-center gap-1">
                    <span class="badge text-bg-danger" title="Itens para analisar">{{ $pendingProjectReviewCount }}</span>
                    <span class="badge text-bg-warning text-dark" title="Retornos para confirmar/analisar">{{ $pendingReturnsCount }}</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.dashboard') ? '' : 'collapsed' }}"
               href="{{ route('project_review.dashboard') }}">
                <i class="ri-dashboard-line"></i><span>DASHBOARD GOVERNANÇA</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.history') ? '' : 'collapsed' }}"
               href="{{ route('project_review.history') }}">
                <i class="ri-history-line"></i><span>HISTÓRICO DAS ANÁLISES</span>
            </a>
        </li>
    </ul>
</aside>
