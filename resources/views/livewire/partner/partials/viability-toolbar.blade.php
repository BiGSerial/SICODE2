{{-- Ferramentas das listas de viabilidade da parceira (padrão de controle): busca, abas opcionais, filtros na URL e chips.
     Variáveis: $placeholder, $hasCriteria, $tabs (valor => rótulo, opcional), $tabsLabel, $extra (linha extra, opcional). --}}
<section class="ops-toolbar" aria-label="Filtros da lista">
    <div class="ops-toolbar-row">
        <x-ops.search wire:model.debounce.500ms="search" :placeholder="$placeholder ?? 'Buscar por nota ou ordem…'" label="Buscar" />

        @if (!empty($tabs))
            <x-ops.segmented name="partnerViabTab" :label="$tabsLabel ?? 'Situação'" wire:model="tab" :options="$tabs" />
        @endif

        <span class="ops-toolbar-spacer"></span>

        @if ($hasCriteria)
            <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
        @endif
    </div>

    @if (!empty($extra))
        <div class="ops-toolbar-row">
            {{ $extra }}
        </div>
    @endif

    @if ($this->listFilterPayload()['defs'])
        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>
    @endif

    @if ($this->hasListFilters())
        <div class="ops-toolbar-row">
            <x-ops.chips :chips="$this->listFilterChips()" />
        </div>
    @endif
</section>
