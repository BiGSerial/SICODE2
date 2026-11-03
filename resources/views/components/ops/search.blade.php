@props([
    'placeholder' => 'Buscar',
    'label'       => 'Buscar',
])

{{-- Busca da lista. O `wire:model` vem do chamador (use `.debounce.500ms`: sem isso cada tecla vira uma requisição). O slot recebe ações extras (busca em massa). --}}
<div class="ops-search">
    <i class="ri-search-line ops-search-icon" aria-hidden="true"></i>
    <input type="search" {{ $attributes->merge(['class' => 'form-control form-control-sm', 'placeholder' => $placeholder, 'aria-label' => $label, 'autocomplete' => 'off']) }}>
    {{ $slot }}
</div>
