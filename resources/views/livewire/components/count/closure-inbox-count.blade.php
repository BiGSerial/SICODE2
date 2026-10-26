<span wire:poll.300s>
    @if ($count)
        <span class="badge {{ $kind === 'dispatched' ? 'bg-danger' : 'bg-primary' }}" title="{{ $kind === 'dispatched' ? 'Pendências despachadas esperando resolver' : 'Ordens em acompanhamento' }}">{{ number_format($count, 0, ',', '.') }}</span>
    @endif
</span>
