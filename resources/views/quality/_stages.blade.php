{{-- Etapas do fluxo como abas. Espera $tabs, $tab, $routeName; opcional $extra = [['label','href','icon','active']]. --}}
<div class="wk-stages mb-3" role="tablist" aria-label="Etapas">
    @foreach ($tabs as $key => $stage)
        <a href="{{ route($routeName, $key) }}" class="wk-stage {{ $tab === $key ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
            @if ($stage['action'] && $stage['count'] > 0)<span class="wk-dot" title="Depende de você"></span>@endif
            <div><span class="wk-step">{{ $stage['step'] }}</span><span class="wk-name">{{ $stage['label'] }}</span></div>
            <div class="wk-count">{{ $stage['count'] }}</div>
            <div class="wk-meta">@if ($stage['oldest'])mais antiga @include('quality._age', ['since' => $stage['oldest']])@else{{ $stage['action'] ? 'nada pendente' : 'nenhuma' }}@endif</div>
        </a>
    @endforeach
    @foreach ($extra ?? [] as $link)
        <a href="{{ $link['href'] }}" class="wk-stage {{ !empty($link['active']) ? 'is-active' : '' }}" style="flex: 0 0 auto; min-width: 150px;">
            <div><span class="wk-step"><i class="{{ $link['icon'] }}"></i></span><span class="wk-name">{{ $link['label'] }}</span></div>
            <div class="wk-meta mt-2">{{ $link['hint'] ?? '' }}</div>
        </a>
    @endforeach
</div>
