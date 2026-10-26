@props([
    'label',
    'name',
    'hint'     => null,
    'required' => false,
])

{{-- Campo de formulário: rótulo sempre visível, ajuda curta e erro de validação do Livewire junto ao campo. O controle vai no slot. --}}
<div {{ $attributes->class(['ui-field']) }}>
    <label for="{{ $name }}">{{ $label }}@if ($required)<span class="ui-required" aria-hidden="true"> *</span>@endif</label>
    {{ $slot }}
    @if ($hint)
        <div class="ui-hint">{{ $hint }}</div>
    @endif
    @error($name)
        <div class="ui-error" role="alert">{{ $message }}</div>
    @enderror
</div>
