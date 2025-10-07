{{-- resources/views/livewire/components/filter/dropdown.blade.php --}}
@php
    $type = $def['type'] ?? 'single';
    $btn = $def['button_label'] ?? ucfirst($filterKey);
    $placeholder = $def['placeholder'] ?? 'Todos';
@endphp

<div class="dropdown">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
        aria-expanded="false" wire:click="open">
        {{ $btn }}
        @if ($type === 'multi' && !empty($current))
            <span class="badge bg-primary ms-1">{{ count((array) $current) }}</span>
        @elseif($type === 'single' && $current)
            <span class="badge bg-primary ms-1">1</span>
        @endif
    </button>

    <div class="dropdown-menu p-2" style="min-width: 280px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">{{ $placeholder }}</small>
            <button class="btn btn-sm btn-link text-danger p-0" wire:click="clear">Limpar</button>
        </div>

        <div style="max-height: 280px; overflow:auto;">
            @forelse($options as $opt)
                @php
                    $isActive =
                        $type === 'multi' ? in_array($opt['value'], (array) $current, true) : $current == $opt['value'];
                @endphp
                <button type="button" class="dropdown-item d-flex align-items-center {{ $isActive ? 'active' : '' }}"
                    wire:click="toggleValue('{{ $opt['value'] }}')" data-opt data-label="{{ $opt['label'] }}">
                    @if ($type === 'multi')
                        <input type="checkbox" class="form-check-input me-2" {{ $isActive ? 'checked' : '' }} />
                    @endif
                    <span class="flex-grow-1 text-start">{{ $opt['label'] }}</span>
                </button>
            @empty
                <div class="text-muted small">Abra o filtro para carregar opções…</div>
            @endforelse
        </div>
    </div>
</div>
