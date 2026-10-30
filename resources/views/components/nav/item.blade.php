@props([
    'item',
    'activeItem' => null,
])

{{-- Item do menu lateral padrão (docs/PADRAO_MENU_LATERAL.md). Contadores = componentes Livewire declarados em `counts`. --}}
@php $isActive = $item['key'] === $activeItem; @endphp

<li class="snav-li" data-snav-item>
    <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="snav-item {{ $isActive ? 'is-active' : '' }}" @if ($isActive) aria-current="page" @endif
        @if (!empty($item['target'])) target="{{ $item['target'] }}" rel="noopener" @endif>
        <span class="snav-ico"><i class="{{ $item['icon'] }}" aria-hidden="true"></i></span>
        <span class="snav-label" data-snav-label>{{ $item['label'] }}</span>
        @if (($item['target'] ?? null) === '_blank')
            <i class="ri-external-link-line snav-ext" aria-hidden="true" title="Abre em outra aba"></i>
        @endif
        @if (!empty($item['counts']))
            <span class="snav-count">
                @foreach ($item['counts'] as $i => $count)
                    @livewire($count[0], $count[1] ?? [], key('snav-' . $item['key'] . '-' . $i))
                @endforeach
            </span>
        @endif
    </a>
</li>
