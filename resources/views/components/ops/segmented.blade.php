@props([
    'name',
    'options',
    'label' => null,
])

{{-- Seletor segmentado (3 ou 4 opções que se excluem), ex.: Nota | OV | Ambos. `options` é `valor => rótulo`; o `wire:model` vem do chamador. --}}
<div class="ops-segmented" role="radiogroup" @if ($label) aria-label="{{ $label }}" @endif>
    @foreach ($options as $value => $text)
        <input type="radio" class="btn-check" name="{{ $name }}" id="{{ $name }}-{{ $loop->index }}" value="{{ $value }}" {{ $attributes->whereStartsWith('wire:model') }}>
        <label class="ops-segmented-item" for="{{ $name }}-{{ $loop->index }}">{{ $text }}</label>
    @endforeach
</div>
