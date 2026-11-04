@props(['clear' => null])

{{-- Barra flutuante de seleção em lote. Vive dentro de um `x-data="opsSelection()"`; a contagem é do navegador (as caixas usam
     `wire:model.defer`, então o servidor só sabe a seleção na próxima ação). Ações no slot; "Limpar" chama `clearSelection` (ou a expressão Alpine de `clear`, quando a seleção vive só no navegador). --}}
<div class="ops-selection" x-show="count > 0" x-cloak x-transition.opacity>
    <div class="ops-selection-count"><strong x-text="count"></strong> <span x-text="count === 1 ? 'selecionada' : 'selecionadas'"></span></div>
    <div class="ops-selection-actions">{{ $slot }}</div>
    <button type="button" class="ops-selection-clear" @if ($clear) @click="{{ $clear }}" @else wire:click="clearSelection" @endif aria-label="Limpar seleção"><i class="ri-close-line"></i></button>
</div>
