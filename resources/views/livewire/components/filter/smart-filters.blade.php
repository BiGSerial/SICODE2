{{-- resources/views/livewire/components/filter/smart-filters.blade.php --}}
<div class="d-flex flex-wrap gap-2">
    @foreach ($config as $key => $def)
        <livewire:components.filter.dropdown :filter-key="$key" :def="Arr::except($def, ['query'])" :current="data_get($filters, $key)"
            wire:key="dd-{{ $this->id }}-{{ $key }}" />
    @endforeach

    <div class="ms-2">
        <button class="btn btn-sm btn-outline-secondary"
            wire:click="$emitUp('filters:updated', {{ json_encode($filters) }})">
            Aplicar
        </button>
        <button class="btn btn-sm btn-link text-danger p-0" onclick="@this.set('filters', {})">
            Limpar tudo
        </button>
    </div>
</div>
