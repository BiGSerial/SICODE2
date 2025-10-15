{{--
    Componente: <x-filters.dynamic />
    Filtros dinâmicos modernos e responsivos com Bootstrap 5
--}}

@props([
    'filters' => [],
    'applyAction' => null,
    'class' => '',
])

@php
    use Illuminate\Support\Str;

    // Define o modificador do wire:model baseado no applyAction
    $wireModifier = $applyAction ? 'defer' : 'live';

    // Função otimizada para resolver opções
    $resolveOptions = function (array $cfg): array {
        $src = $cfg['source'] ?? [];
        $mode = $src['mode'] ?? 'array';
        $ovKey = $cfg['option_value'] ?? 'value';
        $olKey = $cfg['option_label'] ?? 'label';

        // Mode: Eloquent
        if ($mode === 'eloquent' && !empty($src['model'])) {
            $q = $src['model']::query();

            if (!empty($src['where'])) {
                foreach ($src['where'] as $w) {
                    $q->where($w[0], $w[2] ?? '=', $w[1] ?? null);
                }
            }

            if (!empty($src['orderBy'])) {
                $q->orderBy($src['orderBy'][0], $src['orderBy'][1] ?? 'asc');
            }

            return $q
                ->pluck($src['label'] ?? 'name', $src['key'] ?? 'id')
                ->map(fn($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
                ->values()
                ->toArray();
        }

        // Mode: Collection
        if ($mode === 'collection' && isset($src['data'])) {
            $col = collect($src['data']);

            // Collection associativa [value => label]
            if ($col->isNotEmpty() && !is_numeric($col->keys()->first())) {
                return $col
                    ->map(
                        fn($label, $value) => [
                            'value' => (string) $value,
                            'label' => (string) $label,
                        ],
                    )
                    ->values()
                    ->toArray();
            }

            // Collection de objetos/arrays
            return $col
                ->map(
                    fn($item) => [
                        'value' => (string) (is_array($item) ? $item[$ovKey] ?? '' : $item->{$ovKey} ?? ''),
                        'label' => (string) (is_array($item) ? $item[$olKey] ?? '' : $item->{$olKey} ?? ''),
                    ],
                )
                ->filter(fn($opt) => $opt['value'] !== '' && $opt['label'] !== '')
                ->values()
                ->toArray();
        }

        // Mode: Array
        $data = $src['data'] ?? [];
        if (empty($data)) {
            return [];
        }

        // Array associativo [value => label]
        if (array_keys($data) !== range(0, count($data) - 1)) {
            return collect($data)
                ->map(
                    fn($label, $value) => [
                        'value' => (string) $value,
                        'label' => (string) $label,
                    ],
                )
                ->values()
                ->toArray();
        }

        // Array de arrays/objetos
        return collect($data)
            ->map(
                fn($item) => [
                    'value' => (string) (is_array($item) ? $item[$ovKey] ?? '' : $item->{$ovKey} ?? ''),
                    'label' => (string) (is_array($item) ? $item[$olKey] ?? '' : $item->{$olKey} ?? ''),
                ],
            )
            ->filter(fn($opt) => $opt['value'] !== '' && $opt['label'] !== '')
            ->values()
            ->toArray();
    };

    // Gera script de limpeza
    $clearScript = collect($filters)
        ->pluck('model')
        ->filter()
        ->map(function ($model) use ($filters) {
            $filter = collect($filters)->firstWhere('model', $model);
            $isMultiple =
                in_array($filter['type'] ?? 'select', ['checkselect', 'multiselect']) || ($filter['multiple'] ?? false);
            return "@this.set('{$model}', " . ($isMultiple ? '[]' : "''") . ');';
        })
        ->join('');
@endphp

<style>
    .filters-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .filter-wrapper {
        min-width: 180px;
        max-width: 280px;
        flex: 1 1 200px;
    }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .filter-select,
    .filter-dropdown-btn {
        height: 38px;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }

    .filter-dropdown-btn {
        background: white;
        border: 1px solid #dee2e6;
        text-align: left;
        position: relative;
        padding-right: 2rem;
    }

    .filter-dropdown-btn:hover,
    .filter-dropdown-btn:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15);
    }

    .filter-dropdown-btn .badge {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.7rem;
    }

    .filter-dropdown-menu {
        min-width: 250px;
        max-width: 350px;
        border: 1px solid #dee2e6;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-radius: 0.5rem;
        padding: 0.5rem;
    }

    .filter-search {
        margin-bottom: 0.5rem;
        border-radius: 0.375rem;
    }

    .filter-options-list {
        max-height: 280px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e0 #f7fafc;
    }

    .filter-options-list::-webkit-scrollbar {
        width: 6px;
    }

    .filter-options-list::-webkit-scrollbar-track {
        background: #f7fafc;
        border-radius: 3px;
    }

    .filter-options-list::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }

    .filter-option-item {
        padding: 0.5rem;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: background 0.15s ease;
        border: none;
        font-size: 0.875rem;
    }

    .filter-option-item:hover {
        background: #e9ecef;
    }

    .filter-option-item.active {
        background: #e7f1ff;
        color: #0d6efd;
    }

    .filter-checkbox-item {
        padding: 0.4rem 0.5rem;
        border: none;
        border-radius: 0.25rem;
        margin-bottom: 0.15rem;
        cursor: pointer;
        transition: background 0.15s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-checkbox-item:hover {
        background: #f8f9fa;
    }

    .filter-checkbox-item input[type="checkbox"] {
        flex-shrink: 0;
        width: 1rem;
        height: 1rem;
        cursor: pointer;
    }

    .filter-actions-bar {
        gap: 0.5rem;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid #dee2e6;
    }

    @media (max-width: 768px) {
        .filters-container {
            padding: 0.75rem;
        }

        .filter-wrapper {
            min-width: 100%;
            max-width: 100%;
        }
    }
</style>

<div class="filters-container {{ $class }}">
    <div class="row g-2">
        {{-- Filtros dinâmicos --}}
        @foreach ($filters as $idx => $f)
            @php
                $id = $f['id'] ?? 'filter_' . Str::slug($f['label'] ?? 'field') . '_' . $idx;
                $type = $f['type'] ?? 'select';
                $label = $f['label'] ?? 'Filtro';
                $model = $f['model'] ?? null;
                $placeholder = $f['placeholder'] ?? 'Selecione';
                $multiple = (bool) ($f['multiple'] ?? false);
                $searchable = (bool) ($f['searchable'] ?? false);
                $options = $resolveOptions($f);

                $currentValue = $model ? $this->{$model} ?? null : null;
                $isMultiType = in_array($type, ['checkselect', 'multiselect']);

                // Cálculo do texto do botão
                $btnText = $placeholder;
                $badgeCount = 0;

                if ($type === 'dropdown' && $currentValue) {
                    $selected = collect($options)->firstWhere('value', $currentValue);
                    $btnText = $selected['label'] ?? $placeholder;
                } elseif ($isMultiType && is_array($currentValue)) {
                    $badgeCount = count($currentValue);
                    if ($badgeCount > 0) {
                        $btnText = $placeholder;
                    }
                }
            @endphp

            <div class="col-auto filter-wrapper">
                <label class="filter-label d-block" for="{{ $id }}">{{ $label }}</label>

                {{-- SELECT NATIVO --}}
                @if ($type === 'select')
                    <select id="{{ $id }}" class="form-select form-select-sm filter-select"
                        {!! $multiple ? 'multiple size="' . ($f['size'] ?? 3) . '"' : '' !!}
                        @if ($model) wire:model.{{ $wireModifier }}="{{ $model }}" @endif>

                        @unless ($multiple)
                            <option value="">{{ $placeholder }}</option>
                        @endunless

                        @foreach ($options as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- DROPDOWN SINGLE SELECT --}}
                @if ($type === 'dropdown')
                    <div wire:ignore.self class="dropdown w-100" x-data="{ open: false }">
                        <button class="btn filter-dropdown-btn w-100 text-start" type="button" @click="open = !open"
                            @click.outside="open = false">
                            <span class="text-truncate d-inline-block" style="max-width: calc(100% - 1.5rem);">
                                {{ $btnText }}
                            </span>
                        </button>

                        <div class="dropdown-menu filter-dropdown-menu w-100" :class="{ 'show': open }">
                            <button
                                class="dropdown-item filter-option-item @if (!$currentValue) active @endif"
                                type="button"
                                @if ($model) wire:click="$set('{{ $model }}', '')" @endif
                                @click="open = false">
                                {{ $placeholder }}
                            </button>

                            <div class="dropdown-divider my-1"></div>

                            @foreach ($options as $opt)
                                <button
                                    class="dropdown-item filter-option-item @if ($currentValue == $opt['value']) active @endif"
                                    type="button"
                                    @if ($model) wire:click="$set('{{ $model }}', '{{ $opt['value'] }}')" @endif
                                    @click="open = false">
                                    {{ $opt['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- DROPDOWN MULTI SELECT (CHECKBOXES) --}}
                @if ($isMultiType)
                    <div wire:ignore.self class="dropdown w-100" x-data="{ open: false, search: '' }">
                        <button class="btn filter-dropdown-btn w-100 text-start" type="button" @click="open = !open"
                            @click.outside="open = false">
                            <span class="text-truncate d-inline-block" style="max-width: calc(100% - 2rem);">
                                {{ $btnText }}
                            </span>
                            @if ($badgeCount > 0)
                                <span class="badge bg-primary">{{ $badgeCount }}</span>
                            @endif
                        </button>

                        <div wire:ignore.self class="dropdown-menu filter-dropdown-menu w-100"
                            :class="{ 'show': open }">
                            @if ($searchable)
                                <input type="text" class="form-control form-control-sm filter-search"
                                    placeholder="Buscar..." x-model="search" @click.stop>
                            @endif

                            <div class="filter-options-list">
                                @foreach ($options as $opt)
                                    <label class="filter-checkbox-item w-100"
                                        @if ($searchable) x-show="'{{ Str::lower($opt['label']) }}'.includes(search.toLowerCase())" @endif
                                        @click.stop>
                                        <input class="form-check-input" type="checkbox" value="{{ $opt['value'] }}"
                                            @if ($model) wire:model.{{ $wireModifier }}="{{ $model }}" @endif
                                            @click.stop>
                                        <span class="flex-grow-1">{{ $opt['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Ações dos Filtros --}}
        <div class="col-12">
            <div class="d-flex filter-actions-bar flex-wrap">
                @if ($applyAction)
                    <button type="button" class="btn btn-primary btn-sm px-3" wire:click="{{ $applyAction }}">
                        <i class="bi bi-funnel-fill me-1"></i>
                        Aplicar
                    </button>
                @endif

                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click.prevent="cleanFilters">
                    <i class="bi bi-x-circle me-1"></i>
                    Limpar
                </button>
            </div>
        </div>
    </div>''
</div>
