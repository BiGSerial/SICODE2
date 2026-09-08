@props([
    'wireModel',
    'selected' => null,
    'id' => null,
    'label' => 'Ícone',
])

@once
    <style>
        .icon-picker-toggle {
            height: 38px;
        }

        .icon-picker-current {
            max-width: 70%;
        }

        .icon-picker-menu {
            width: 320px;
            max-height: 360px;
            overflow-y: auto;
        }

        .icon-picker-group-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            padding: 0.35rem 0.15rem 0.2rem;
            display: flex;
            justify-content: space-between;
        }

        .icon-picker-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.25rem;
        }

        .icon-picker-option {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            aspect-ratio: 1;
            border: 1px solid transparent;
            border-radius: 0.35rem;
            background: transparent;
            font-size: 1.05rem;
            color: #3d4f64;
            cursor: pointer;
            transition: background 0.1s ease, border-color 0.1s ease, color 0.1s ease;
        }

        .icon-picker-option:hover {
            background: #f0f5ff;
            border-color: #b8cbf5;
        }

        .icon-picker-option.is-active {
            background: #263CC8;
            border-color: #263CC8;
            color: #fff;
        }

        .icon-picker-loading,
        .icon-picker-empty {
            font-size: 0.78rem;
        }
    </style>
@endonce

@php
    $pickerId = 'icon-picker-' . ($id ?: \Illuminate\Support\Str::random(8));

    $fallbackIcon = 'ri-customer-service-2-line';

    // Sugestões: ícones já usados pelos serviços reais do sistema + alguns genéricos.
    $suggested = [
        'ri-customer-service-2-line',
        'ri-booklet-fill',
        'ri-user-search-line',
        'ri-building-line',
        'ri-ball-pen-fill',
        'ri-government-fill',
        'ri-user-star-line',
        'ri-eye-line',
        'ri-lightbulb-flash-fill',
        'ri-secure-payment-line',
        'ri-contacts-line',
        'ri-settings-3-line',
        'ri-map-pin-line',
        'ri-tools-line',
        'ri-truck-line',
        'ri-file-list-3-line',
    ];

    $prettyLabel = function (string $class): string {
        $stripped = preg_replace('/^ri-/', '', $class);
        $stripped = preg_replace('/-(line|fill)$/', '', $stripped);

        return ucwords(str_replace('-', ' ', $stripped));
    };

    $suggestedIcons = collect($suggested)->mapWithKeys(fn ($class) => [$class => $prettyLabel($class)]);

    $selectedLabel = $selected ? ($suggestedIcons->get($selected) ?? $prettyLabel($selected)) : null;

    $iconsJsonUrl = asset('assets/vendor/remixicon/icons.json');
@endphp

<div
    class="icon-picker"
    x-data="{
        q: '',
        selected: @js($selected),
        selectedLabel: @js($selectedLabel),
        allIcons: [],
        loading: false,
        loadError: false,
        async ensureIcons() {
            if (this.allIcons.length || this.loading) {
                return;
            }
            this.loading = true;
            try {
                if (!window.__riIconsPromise) {
                    window.__riIconsPromise = fetch('{{ $iconsJsonUrl }}').then(r => r.json());
                }
                this.allIcons = await window.__riIconsPromise;
            } catch (e) {
                this.loadError = true;
            } finally {
                this.loading = false;
            }
        },
        select(cls, lbl) {
            this.selected = cls;
            this.selectedLabel = lbl;
            @this.set('{{ $wireModel }}', cls);
        },
        get filteredIcons() {
            if (!this.q) {
                return this.allIcons;
            }
            const needle = this.q.toLowerCase();
            return this.allIcons.filter(i => i.class.includes(needle) || i.label.toLowerCase().includes(needle));
        },
    }"
>
    @if ($label)
        <label class="form-label">{{ $label }}</label>
    @endif

    <div class="dropdown">
        <button
            type="button"
            id="{{ $pickerId }}"
            class="btn btn-outline-secondary icon-picker-toggle d-flex align-items-center w-100"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            @click="ensureIcons()"
        >
            <i :class="selected || '{{ $fallbackIcon }}'" class="fs-5 text-primary me-2"></i>
            <span class="icon-picker-current text-truncate" x-text="selectedLabel || 'Selecionar ícone'"></span>
            <i class="ri-arrow-down-s-line ms-auto text-muted"></i>
        </button>

        <div class="dropdown-menu icon-picker-menu p-2" aria-labelledby="{{ $pickerId }}">
            <input
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Buscar entre os {{ count($suggested) }}+ ícones..."
                x-model="q"
                @click.stop
            >

            <template x-if="q === ''">
                <div>
                    <div class="icon-picker-group-label">Sugeridos</div>
                    <div class="icon-picker-grid mb-2">
                        @foreach ($suggestedIcons as $iconClass => $iconLabel)
                            <button
                                type="button"
                                class="icon-picker-option"
                                :class="{ 'is-active': selected === '{{ $iconClass }}' }"
                                title="{{ $iconLabel }}"
                                @click="select('{{ $iconClass }}', '{{ $iconLabel }}')"
                            >
                                <i class="{{ $iconClass }}"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            </template>

            <div class="icon-picker-group-label">
                <span>{{ 'Todos os ícones' }}</span>
                <span x-show="allIcons.length" x-text="filteredIcons.length + ' / ' + allIcons.length"></span>
            </div>

            <div wire:ignore>
                <div class="icon-picker-loading text-muted text-center py-2" x-show="loading">
                    Carregando ícones...
                </div>
                <div class="icon-picker-loading text-danger text-center py-2" x-show="loadError">
                    Não foi possível carregar o catálogo de ícones.
                </div>
                <div class="icon-picker-empty text-muted text-center py-2" x-show="!loading && !loadError && filteredIcons.length === 0">
                    Nenhum ícone encontrado para "<span x-text="q"></span>".
                </div>
                <div class="icon-picker-grid" x-show="!loading">
                    <template x-for="icon in filteredIcons" :key="icon.class">
                        <button
                            type="button"
                            class="icon-picker-option"
                            :class="{ 'is-active': selected === icon.class }"
                            :title="icon.label"
                            @click="select(icon.class, icon.label)"
                        >
                            <i :class="icon.class"></i>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
