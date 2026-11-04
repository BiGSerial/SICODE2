@props([
    'title',
    'close',
    'eyebrow' => null,
    'size'    => '',
])

{{-- Modal no padrão ops-modal (docs/PADRAO_LISTAS_OPERACIONAIS.md §11), aberto por estado do Livewire (`@if`). `close` = método que fecha;
     Esc também fecha. Corpo no slot; rodapé no slot nomeado `footer`. --}}
<div class="modal ops-modal d-block show" tabindex="-1" role="dialog" aria-modal="true" aria-label="{{ $title }}" x-data @keydown.escape.window="$wire.{{ $close }}()">
    <div class="modal-dialog modal-dialog-centered {{ $size }}">
        <div class="modal-content closure-dash ops-list">
            <div class="modal-header">
                <div>
                    @if ($eyebrow)
                        <span class="modal-eyebrow">{{ $eyebrow }}</span>
                    @endif
                    <h5 class="modal-title">{{ $title }}</h5>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" aria-label="Fechar" wire:click="{{ $close }}"></button>
            </div>
            <div class="modal-body">{{ $slot }}</div>
            @isset($footer)
                <div class="modal-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
<div class="modal-backdrop show"></div>
