@props(['paginator'])
@if($paginator->hasPages())
    <nav aria-label="Paginação" class="mt-3">
        {{ $paginator->links() }}
    </nav>
@endif
