{{-- Filtro multi "Status usuário" (2 primeiros tokens de orders.statusUser), igual ao da lista do Despacho. --}}
@php($suCount = count($statusUsers))
<div class="dropdown" wire:key="status-user-filter">
    <button type="button" class="form-select text-start text-truncate" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-closure-fixed-dropdown aria-expanded="false">
        {{ $suCount ? 'Status usuário (' . $suCount . ')' : 'Todos' }}
    </button>
    <div class="dropdown-menu p-2" style="min-width: 280px; max-height: 340px; overflow-y: auto;">
        @forelse ($statusUserOptions as $option)
            <label class="dropdown-item d-flex align-items-center gap-2 rounded" style="cursor:pointer;" wire:key="su-{{ $option['v'] }}">
                <input type="checkbox" class="form-check-input mt-0" value="{{ $option['v'] }}" wire:model.defer="statusUsers">
                <span class="small fw-semibold">{{ $option['v'] }}</span>
                <span class="ms-auto badge bg-light text-dark border">{{ $option['n'] }}</span>
            </label>
        @empty
            <div class="small text-muted px-2 py-1">Nenhum status usuário nas Ordens desta lista.</div>
        @endforelse
        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 px-1">
            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted" wire:click="$set('statusUsers', [])">Limpar</button>
            <button type="button" class="btn btn-sm btn-primary" wire:click="$refresh">Aplicar</button>
        </div>
    </div>
</div>
