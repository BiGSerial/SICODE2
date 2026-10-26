@props([
    'label',
    'field',
    'options',
])

{{-- wire:ignore: as opções são geradas pelo Alpine (x-for); se o Livewire reescrevesse esse trecho (ex.: ao remover um chip), as opções perderiam os
     handlers e o filtro "morria" até recarregar a página. O estado vem do x-data (data-options) e não do HTML deste bloco.
     Multi-dropdown do Encerramento: mesmo visual do x-filters.multi-dropdown (botão com contador, checkboxes, badge de contagem,
     data-bs-auto-close="outside"), com busca, "Todos" e "Limpar". A seleção fica pendente no cliente (escopo Alpine de
     closureFilterPanel) e vai ao servidor numa chamada só quando o menu fecha. --}}
<div class="dropdown closure-multi" wire:ignore x-init="$el.addEventListener('hidden.bs.dropdown', () => commit())">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <span class="closure-multi-label" x-text="buttonLabel('{{ $field }}', @js($label))">{{ $label }}</span>
    </button>
    <div class="dropdown-menu p-2">
        <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar..." aria-label="Buscar em {{ $label }}" x-model="q.{{ $field }}">
        <div class="d-flex justify-content-between mb-1 px-1 small">
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" @click="selectVisible('{{ $field }}', {{ $options }})">Selecionar todos</button>
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" @click="clear('{{ $field }}')">Limpar</button>
        </div>
        <div class="closure-multi-list">
            <template x-for="opt in visible('{{ $field }}', {{ $options }})" :key="opt.v">
                <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input mt-0" type="checkbox" :checked="sel.{{ $field }}.includes(opt.v)" @change="toggle('{{ $field }}', opt.v)">
                    <span class="flex-grow-1 text-truncate" x-text="opt.l" :title="opt.l"></span>
                    <span class="badge text-bg-light" x-text="opt.n"></span>
                </label>
            </template>
            <span class="dropdown-item text-muted" x-show="visible('{{ $field }}', {{ $options }}).length === 0">Nenhuma opção disponível.</span>
        </div>
        <div class="text-end mt-2"><button type="button" class="btn btn-primary btn-sm btn-dash" @click="$el.closest('.dropdown').querySelector('[data-bs-toggle]').click()">Aplicar</button></div>
    </div>
</div>
