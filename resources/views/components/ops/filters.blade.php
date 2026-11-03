@props([
    'payload',
])

{{-- Filtros multi-seleção centralizados (trait WithListFilters). Um único bloco Alpine (`opsFilterPanel`) para todos os dropdowns:
     a seleção fica pendente no navegador e vai ao servidor em UMA chamada ao fechar o menu. `wire:ignore`: as opções não viajam
     a cada resposta; o servidor avisa a seleção final pelo evento `ops-filters`. --}}
<div class="ops-filters" wire:ignore x-data="opsFilterPanel()" data-config="{{ json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    @ops-filters.window="receive($event.detail.sel)">
    <template x-for="def in defs" :key="def.key">
        <div class="dropdown ops-filter" x-init="$el.addEventListener('hidden.bs.dropdown', () => commit())">
            <button class="ops-filter-btn" :class="{ 'is-active': sel[def.key].length }" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <span x-text="def.label"></span>
                <span class="ops-filter-count" x-show="sel[def.key].length" x-text="sel[def.key].length"></span>
                <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu p-2">
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar…" :aria-label="'Buscar em ' + def.label" x-model="q[def.key]">
                <div class="d-flex justify-content-between mb-1 px-1 small">
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" @click="selectVisible(def.key)">Selecionar visíveis</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" @click="clear(def.key)">Limpar</button>
                </div>
                <div class="ops-filter-list">
                    <template x-for="opt in visible(def.key)" :key="opt.v">
                        <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input mt-0" type="checkbox" :checked="sel[def.key].includes(opt.v)" @change="toggle(def.key, opt.v)">
                            <span class="flex-grow-1 text-truncate" x-text="opt.l" :title="opt.l"></span>
                        </label>
                    </template>
                    <span class="dropdown-item text-muted" x-show="visible(def.key).length === 0">Nenhuma opção disponível.</span>
                </div>
                <div class="text-end mt-2">
                    <button type="button" class="btn btn-primary btn-sm btn-dash" @click="$el.closest('.dropdown').querySelector('[data-bs-toggle]').click()">Aplicar</button>
                </div>
            </div>
        </div>
    </template>
</div>
