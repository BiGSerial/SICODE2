@props([
    'icon'        => 'bi-inbox',
    'message'     => 'Nenhum item encontrado.',
    'subtext'     => null,
    'actionLabel' => null,
    'actionWire'  => null,
])
<div class="text-center py-5 text-muted">
    <i class="{{ $icon }} fs-1 d-block mb-3"></i>
    <p class="mb-1 fw-semibold">{{ $message }}</p>
    @if($subtext)
        <p class="small">{{ $subtext }}</p>
    @endif
    @if($actionLabel)
        <button class="btn btn-sm btn-outline-primary mt-2" wire:click="{{ $actionWire }}">
            {{ $actionLabel }}
        </button>
    @endif
</div>
