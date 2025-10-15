<div class="modern-card mb-2" x-data="{ open: @entangle('open').defer, searchLocal: @entangle('search').defer }">
    <div class="modern-card-body">
        {{-- chips ativos --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="flex-grow-1">
                @php
                    $activeCount = collect($state ?? [])
                        ->filter(fn($v) => is_array($v) ? count($v) : $v !== null && $v !== '')
                        ->count();
                @endphp
                @if ($activeCount)
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($state as $k => $v)
                            @php $has = is_array($v) ? count($v) : ($v !== null && $v !== ''); @endphp
                            @if ($has)
                                <span class="badge bg-primary-subtle text-primary rounded-pill">
                                    {{ collect($config)->firstWhere('key', $k)['label'] ?? $k }}:
                                    <span class="ms-1">
                                        @if (is_array($v))
                                            {{ implode(', ', array_slice($v, 0, 2)) }}{{ count($v) > 2 ? '…' : '' }}
                                        @else
                                            {{ $v }}
                                        @endif
                                    </span>
                                    <button class="btn btn-sm btn-link text-primary ms-1 p-0"
                                        wire:click="clear('{{ $k }}')">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </span>
                            @endif
                        @endforeach
                        <button class="btn btn-outline-secondary btn-sm" wire:click="clearAll">Limpar tudo</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Linha dos filtros --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
            @foreach ($config as $def)
                @php
                    $key = $def['key'];
                    $label = $def['label'];
                    $type = $def['type'] ?? 'multi';
                    $isOpen = $open === $key;
                @endphp

                <div class="position-relative">
                    <button type="button" class="btn btn-light border d-flex align-items-center gap-2"
                        wire:click="openDropdown('{{ $key }}')">
                        <i class="ri-equalizer-line"></i> {{ $label }}
                        @if (!empty($state[$key]))
                            <span class="badge bg-primary text-white">
                                {{ is_array($state[$key]) ? count($state[$key]) : 1 }}
                            </span>
                        @endif
                    </button>

                    @if ($isOpen)
                        <div class="card shadow position-absolute mt-2" style="min-width: 280px; z-index: 100;"
                            @click.away="$wire.closeDropdown()">
                            <div class="card-body">
                                @if (in_array($type, ['multi', 'single']))
                                    <input type="text" class="form-control mb-2"
                                        placeholder="{{ $def['placeholder'] ?? 'Buscar...' }}"
                                        wire:model.debounce.400ms="search.{{ $key }}"
                                        wire:keydown.debounce.400ms="$wire.refreshDropdown('{{ $key }}')">
                                @endif

                                <div style="max-height: 260px; overflow:auto;">
                                    @php $opts = $options[$key] ?? []; @endphp

                                    @if ($type === 'multi')
                                        @forelse ($opts as $opt)
                                            <label class="d-flex align-items-center gap-2 py-1">
                                                <input type="checkbox" class="form-check-input"
                                                    value="{{ $opt['value'] }}"
                                                    wire:click="toggleState('{{ $key }}', '{{ $opt['value'] }}')"
                                                    @if (in_array($opt['value'], $state[$key] ?? [], true)) checked @endif>
                                                <span>{{ $opt['label'] }}</span>
                                            </label>
                                        @empty
                                            <small class="text-muted">Nenhum resultado</small>
                                        @endforelse
                                    @elseif ($type === 'single')
                                        @forelse ($opts as $opt)
                                            <label class="d-flex align-items-center gap-2 py-1">
                                                <input type="radio" class="form-check-input"
                                                    name="f-{{ $key }}" value="{{ $opt['value'] }}"
                                                    wire:click="toggleState('{{ $key }}', '{{ $opt['value'] }}')"
                                                    @if (($state[$key] ?? null) == $opt['value']) checked @endif>
                                                <span>{{ $opt['label'] }}</span>
                                            </label>
                                        @empty
                                            <small class="text-muted">Nenhum resultado</small>
                                        @endforelse
                                    @elseif ($type === 'text')
                                        <input type="text" class="form-control"
                                            wire:model.defer="state.{{ $key }}"
                                            placeholder="{{ $def['placeholder'] ?? 'Digite...' }}">
                                    @elseif ($type === 'date')
                                        <input type="date" class="form-control"
                                            wire:model.defer="state.{{ $key }}">
                                    @elseif ($type === 'month')
                                        <input type="month" class="form-control"
                                            wire:model.defer="state.{{ $key }}">
                                    @elseif ($type === 'daterange')
                                        <div class="d-flex gap-2">
                                            <input type="date" class="form-control"
                                                wire:model.defer="state.{{ $key }}.start">
                                            <input type="date" class="form-control"
                                                wire:model.defer="state.{{ $key }}.end">
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-between mt-2">
                                    <button class="btn btn-outline-secondary btn-sm"
                                        wire:click="$set('state.{{ $key }}', {{ $type === 'multi' ? '[]' : 'null' }})">
                                        Limpar
                                    </button>
                                    @if ($manualApply)
                                        <button class="btn btn-primary btn-sm" wire:click="apply">
                                            Aplicar
                                        </button>
                                    @else
                                        <button class="btn btn-light btn-sm"
                                            wire:click="closeDropdown()">Fechar</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($manualApply)
                <button class="btn btn-primary d-flex align-items-center gap-2" wire:click="apply">
                    <i class="ri-filter-3-line"></i> Aplicar
                </button>
            @endif
        </div>
    </div>
</div>
