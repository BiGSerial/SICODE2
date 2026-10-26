@props([
    'tabs',
    'active',
    'action' => 'setTab',
])

{{-- Abas com contagem e identidade por natureza. Cada tab: ['key', 'label', 'count', 'tone' (opcional), 'icon' (opcional)].
     Clique chama o método Livewire `$action('key')`. --}}
<div class="inbox-tabs" role="tablist">
    @foreach ($tabs as $tab)
        <button type="button"
                role="tab"
                wire:key="inbox-tab-{{ $tab['key'] }}"
                wire:click="{{ $action }}('{{ $tab['key'] }}')"
                aria-selected="{{ $active === $tab['key'] ? 'true' : 'false' }}"
                @class(['inbox-tab', 'tone-' . ($tab['tone'] ?? 'muted'), 'is-active' => $active === $tab['key']])>
            @if (!empty($tab['icon']))
                <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
            @endif
            <span>{{ $tab['label'] }}</span>
            <span class="inbox-count">{{ $tab['count'] ?? 0 }}</span>
        </button>
    @endforeach
</div>
