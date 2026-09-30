@php
    $is      = fn (string ...$names) => collect($names)->contains(fn ($name) => \Illuminate\Support\Str::is($name, $route));
    $link    = fn (bool $on) => $on ? 'text-warning' : 'text-white';
    $badge   = function (array $tab) {
        if ($tab['count'] < 1) { return ''; }
        $late = $tab['oldest'] && $tab['oldest']->lt(now()->subDays(5));
        $tone = $late ? 'danger' : ($tab['action'] ? 'warning' : 'secondary');

        return '<span class="badge rounded-pill text-bg-' . $tone . ' ms-auto" style="font-size: .7rem;" title="' . ($late ? 'Há obra parada há 5+ dias' : ($tab['action'] ? 'Depende de você' : 'Em acompanhamento')) . '">' . $tab['count'] . '</span>';
    };
    $flex    = 'display: flex; align-items: center;';
@endphp
<div wire:poll.15s>
<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item px-3 pb-2">
            <div class="text-white-50 small text-uppercase">Seu perfil na Qualidade</div>
            <span class="badge text-bg-warning">{{ $roles->roleLabel($user) }}</span>
            @if ($roles->isDualMode($user))
                <form method="post" action="{{ route('quality.view-as') }}" class="mt-1">
                    @csrf
                    <div class="text-white-50 small">Atuar como</div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button name="mode" value="manager" class="btn {{ $roles->viewMode($user) === 'manager' ? 'btn-warning' : 'btn-outline-light' }}">Gestão</button>
                        <button name="mode" value="member" class="btn {{ $roles->viewMode($user) === 'member' ? 'btn-warning' : 'btn-outline-light' }}">Equipe N1/N2</button>
                    </div>
                </form>
            @endif
            @unless ($isManager)<div class="text-white small">{{ $roles->companyNames($user) }}</div>@endunless
        </li>

        @if ($showN1)
            <li class="nav-item">
                <a class="nav-link" data-bs-target="#quality-n1-nav" data-bs-toggle="collapse" href="#"><i class="bi bi-clipboard-check"></i><span>N1 · MINHAS OBRAS</span><i class="bi bi-chevron-down ms-auto"></i></a>
                <ul id="quality-n1-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        @foreach (['despachar' => ['bi-send', 'DESPACHAR'], 'com-usuarios' => ['bi-person-workspace', 'COM OS USUÁRIOS'], 'analisar' => ['bi-search', 'ANALISAR'], 'devolvidos' => ['bi-arrow-return-left', 'DEVOLVIDOS PELO N2'], 'no-n2' => ['bi-hourglass-split', 'NO N2']] as $key => [$icon, $label])
                            <li><a href="{{ route('quality.n1', $key) }}" class="nav-item fw-normal {{ $link($is('quality.n1') && $tab === $key) }}" style="{{ $flex }}"><i class="bi {{ $icon }} fw-light fs-5"></i><span> {{ $n1Tabs[$key]['step'] }} · {{ $label }}</span>{!! $badge($n1Tabs[$key]) !!}</a></li>
                        @endforeach
                        <li><a href="{{ route('quality.n1.users') }}" class="nav-item fw-normal {{ $link($is('quality.n1.users')) }}"><i class="bi bi-people fw-light fs-5"></i><span> MEUS USUÁRIOS</span></a></li>
                        <li><a href="{{ route('quality.history') }}" class="nav-item fw-normal {{ $link($is('quality.history', 'quality.process')) }}"><i class="bi bi-clock-history fw-light fs-5"></i><span> HISTÓRICO</span></a></li>
                    </div>
                </ul>
            </li>
        @endif

        @if ($showN2)
            <li class="nav-item">
                <a class="nav-link" data-bs-target="#quality-n2-nav" data-bs-toggle="collapse" href="#"><i class="bi bi-person-check"></i><span>N2 · DECISÕES</span><i class="bi bi-chevron-down ms-auto"></i></a>
                <ul id="quality-n2-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        @foreach (['decidir' => ['bi-check2-square', 'DECIDIR'], 'encerrar' => ['bi-exclamation-triangle', 'ENCERRAMENTO PENDENTE'], 'com-n1' => ['bi-arrow-return-left', 'DEVOLVIDOS, COM O N1']] as $key => [$icon, $label])
                            <li><a href="{{ route('quality.n2', $key) }}" class="nav-item fw-normal {{ $link($is('quality.n2') && $tab === $key) }}" style="{{ $flex }}"><i class="bi {{ $icon }} fw-light fs-5"></i><span> {{ $n2Tabs[$key]['step'] }} · {{ $label }}</span>{!! $badge($n2Tabs[$key]) !!}</a></li>
                        @endforeach
                        <li><a href="{{ route('quality.n2', 'empresas') }}" class="nav-item fw-normal {{ $link($is('quality.n2') && $tab === 'empresas') }}"><i class="bi bi-building fw-light fs-5"></i><span> POR EMPRESA</span></a></li>
                        @unless ($showN1)<li><a href="{{ route('quality.history') }}" class="nav-item fw-normal {{ $link($is('quality.history', 'quality.process')) }}"><i class="bi bi-clock-history fw-light fs-5"></i><span> HISTÓRICO</span></a></li>@endunless
                    </div>
                </ul>
            </li>
        @endif

        @if ($isManager)
            <li class="nav-item">
                <a class="nav-link" data-bs-target="#quality-operacao-nav" data-bs-toggle="collapse" href="#"><i class="bi bi-shield-check"></i><span>OPERAÇÃO</span><i class="bi bi-chevron-down ms-auto"></i></a>
                <ul id="quality-operacao-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li><a href="{{ route('quality.dashboard') }}" class="nav-item fw-normal {{ $link($is('quality.dashboard')) }}" style="{{ $flex }}"><i class="bi bi-speedometer2 fw-light fs-5"></i><span> VISÃO GERAL</span>@if (($mgmt['paradas'] ?? 0) > 0)<span class="badge rounded-pill text-bg-danger ms-auto" style="font-size: .7rem;" title="Obras paradas há 5+ dias">{{ $mgmt['paradas'] }}</span>@endif</a></li>
                        <li><a href="{{ route('quality.pool') }}" class="nav-item fw-normal {{ $link($is('quality.pool')) }}"><i class="bi bi-stack fw-light fs-5"></i><span> POOL (DESPACHAR AO N1)</span></a></li>
                        <li><a href="{{ route('quality.queue', ['fila' => 'em-andamento']) }}" class="nav-item fw-normal {{ $link($is('quality.queue') && $fila === 'em-andamento') }}" style="{{ $flex }}"><i class="bi bi-arrow-repeat fw-light fs-5"></i><span> EM ANDAMENTO</span><span class="badge rounded-pill text-bg-secondary ms-auto" style="font-size: .7rem;">{{ $mgmt['andamento'] ?? 0 }}</span></a></li>
                        <li><a href="{{ route('quality.queue', ['fila' => 'aguardando-n2']) }}" class="nav-item fw-normal {{ $link($is('quality.queue') && $fila === 'aguardando-n2') }}" style="{{ $flex }}"><i class="bi bi-person-check fw-light fs-5"></i><span> AGUARDANDO N2</span><span class="badge rounded-pill text-bg-secondary ms-auto" style="font-size: .7rem;">{{ $mgmt['n2'] ?? 0 }}</span></a></li>
                        <li><a href="{{ route('quality.history') }}" class="nav-item fw-normal {{ $link($is('quality.history', 'quality.process')) }}"><i class="bi bi-clock-history fw-light fs-5"></i><span> HISTÓRICO</span></a></li>
                    </div>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#quality-config-nav" data-bs-toggle="collapse" href="#"><i class="bi bi-gear"></i><span>CONFIGURAÇÃO</span><i class="bi bi-chevron-down ms-auto"></i></a>
                <ul id="quality-config-nav" class="nav-content collapse {{ $is('quality.categories', 'quality.settings', 'quality.team') ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li><a href="{{ route('quality.categories') }}" class="nav-item fw-normal {{ $link($is('quality.categories')) }}"><i class="bi bi-tags fw-light fs-5"></i><span> MOTIVOS DE REJEIÇÃO</span></a></li>
                        <li><a href="{{ route('quality.team') }}" class="nav-item fw-normal {{ $link($is('quality.team')) }}"><i class="bi bi-people fw-light fs-5"></i><span> EQUIPE N1 / N2</span></a></li>
                        <li><a href="{{ route('quality.settings') }}" class="nav-item fw-normal {{ $link($is('quality.settings')) }}"><i class="bi bi-sliders fw-light fs-5"></i><span> CRITÉRIOS E ATIVIDADES</span></a></li>
                    </div>
                </ul>
            </li>
        @endif
    </ul>
</aside>
</div>
