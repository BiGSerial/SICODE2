@props([
    'label',
    'model',
    'options' => [],
    'selected' => [],
    'keyPrefix' => 'filter',
    'emptyText' => 'Nenhuma opção disponível.',
])

@php
    $selectedCount = count((array) $selected);
@endphp

<div class="dropdown">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        {{ $label }}
        @if ($selectedCount)
            <span class="badge text-bg-secondary ms-1">{{ $selectedCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu p-2" style="max-height: 320px; overflow-y: auto; min-width: 240px;">
        @forelse ($options as $option)
            <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input mt-0" type="checkbox"
                    wire:model.defer="{{ $model }}"
                    wire:key="{{ $keyPrefix }}-{{ md5($option['value']) }}"
                    value="{{ $option['value'] }}">
                <span class="flex-grow-1">{{ $option['value'] }}</span>
                <span class="badge text-bg-light">{{ $option['count'] }}</span>
            </label>
        @empty
            <span class="dropdown-item text-muted">{{ $emptyText }}</span>
        @endforelse
    </div>
</div>
