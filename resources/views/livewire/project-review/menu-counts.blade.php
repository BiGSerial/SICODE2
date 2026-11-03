<span wire:poll.30s class="d-inline-flex align-items-center gap-1">
    @if ($pending)
        <span class="badge text-bg-danger" title="Itens para analisar">{{ $pending }}</span>
    @endif
    @if ($returns)
        <span class="badge text-bg-warning text-dark" title="Retornos para confirmar ou analisar">{{ $returns }}</span>
    @endif
</span>
