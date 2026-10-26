@props([
    'id',
    'title',
])

{{-- Painel lateral (offcanvas Bootstrap). Abrir: $dispatch('closure:drawer-open', { id: '…' }) ou dispatchBrowserEvent('closure:drawer-open', ['id' => '…']).
     Deve ficar DENTRO do container `.closure-dash`. wire:ignore.self preserva o estado aberto entre re-renders do Livewire. --}}
<div class="offcanvas offcanvas-end closure-drawer" tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $id }}-title" wire:ignore.self>
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="{{ $id }}-title">{{ $title }}</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        {{ $slot }}
    </div>
</div>
