<div wire:poll.60s class="d-inline">
    @if ($mode === 'dot')
        @if ($count > 0)
            <span class="closure-menu-dot" title="{{ $count }} pendência(s) de Encerramento aguardando você"><span class="visually-hidden">{{ $count }} pendência(s) aguardando você</span></span>
        @endif
    @else
        <span @class(['badge', 'ms-2', 'bg-danger' => $count > 0, 'bg-light text-dark' => $count === 0]) aria-label="{{ $count }} pendência(s) aguardando você">{{ $count }}</span>
    @endif
</div>
