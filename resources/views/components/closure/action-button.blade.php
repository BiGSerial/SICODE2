@props([
    'label',
    'icon'     => null,
    'tone'     => 'primary',
    'disabled' => false,
    'reason'   => null,
])

{{-- Botão de ação. Quando indisponível, fica desabilitado COM o motivo no tooltip (nunca deixar o usuário clicar para receber erro).
     Tons: primary (verde, ação principal), secondary, danger. A ação vai por atributo (wire:click="…"). --}}
@php
    $class = match ($tone) {
        'danger'    => 'btn-danger',
        'secondary' => 'btn-outline-secondary',
        default     => 'btn-success',
    };
@endphp

<span @class(['action-wrap', 'is-disabled' => $disabled]) @if ($disabled && $reason) title="{{ $reason }}" tabindex="0" @endif>
    <button type="button" {{ $attributes->class(['btn', 'btn-sm', 'btn-dash', $class]) }} @disabled($disabled) wire:loading.attr="disabled">
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $label }}
    </button>
</span>
