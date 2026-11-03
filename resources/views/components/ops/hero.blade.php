@props([
    'eyebrow'   => null,
    'title',
    'subtitle'  => null,
    'updatedAt' => null,
])

{{-- Cabeçalho da lista operacional: mesmo hero escuro em degradê das dashboards (.mp-hero). Ações (Exportar etc.) vão no slot. --}}
<header {{ $attributes->class(["mp-hero", "ops-hero"]) }}>
    <div class="ops-hero-text">
        @if ($eyebrow)
            <div class="mp-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="mp-hero-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mp-hero-sub">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="ops-hero-side">
        @if ($updatedAt)
            <div class="ops-hero-updated" title="Última atualização dos dados vindos do SAP">
                <i class="ri-refresh-line" aria-hidden="true"></i>
                Dados atualizados <strong>{{ \Carbon\Carbon::parse($updatedAt)->diffForHumans() }}</strong>
            </div>
        @endif
        {{ $slot }}
    </div>
</header>
