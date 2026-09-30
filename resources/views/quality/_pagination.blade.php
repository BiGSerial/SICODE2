{{-- Paginação em PT-BR no padrão Bootstrap 5 do SICODE. Uso: ->links('quality._pagination') --}}
@if ($paginator->total() > 0)
    <nav class="d-flex flex-wrap justify-content-between align-items-center gap-2" aria-label="Paginação">
        <p class="small text-muted mb-0">
            Exibindo <span class="fw-semibold">{{ $paginator->firstItem() }}</span> a <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            de <span class="fw-semibold">{{ $paginator->total() }}</span> resultado(s)
        </p>

        @if ($paginator->hasPages())
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if ($paginator->onFirstPage())<span class="page-link">Anterior</span>@else<a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                    @else
                        @foreach ($element as $page => $url)
                            <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif>
                                @if ($page == $paginator->currentPage())<span class="page-link">{{ $page }}</span>@else<a class="page-link" href="{{ $url }}">{{ $page }}</a>@endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    @if ($paginator->hasMorePages())<a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima</a>@else<span class="page-link">Próxima</span>@endif
                </li>
            </ul>
        @endif
    </nav>
@endif
