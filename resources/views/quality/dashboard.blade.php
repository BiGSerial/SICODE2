@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">Visão geral</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">Qualidade <span class="badge text-bg-warning fs-6 align-middle">{{ app(\App\Services\Quality\QualityRoles::class)->roleLabel(auth()->user()) }}</span></h1>
            <div class="dash-subtitle">Acompanhe os dois ciclos de cada Nota, quem está com cada obra e há quanto tempo, sem perder o histórico de nenhuma rodada.</div>
        </div>

        @include('quality._feedback')

        <form method="get" class="table-card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label small mb-1">Despachado de</label><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label class="form-label small mb-1">Despachado até</label><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Empresa</label>
                    <select name="company_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Ciclo</label>
                    <select name="phase" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($phases as $phase)<option value="{{ $phase->value }}" @selected(request('phase') === $phase->value)>{{ $phase->label() }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-sm btn-primary btn-dash"><i class="ri-filter-3-line"></i> Filtrar</button></div>
            </div>
        </form>

        @php
            $pipe = [
                ['n1_dispatch', 'No N1 · despachar / devolvidos', '#d97706', route('quality.queue', ['fila' => 'aguardando-despacho'])],
                ['designer', 'Com os usuários', '#0891b2', route('quality.queue', ['fila' => 'aguardando-desenhista'])],
                ['n1_review', 'Análise do N1', '#2563eb', route('quality.queue', ['fila' => 'em-andamento'])],
                ['n2_review', 'Decisão do N2', '#4f46e5', route('quality.queue', ['fila' => 'aguardando-n2'])],
                ['completed', 'Concluídas', '#059669', route('quality.history', ['status' => 'COMPLETED'])],
            ];
            $initials = fn (?string $name) => $name ? collect(preg_split('/\s+/', trim($name)))->filter(fn ($w) => mb_strlen($w) > 2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') : '?';
        @endphp

        {{-- Pipeline: onde estão todas as obras --}}
        <div class="table-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2"><span class="metric-label">Fluxo das obras agora</span><span class="small text-muted">clique em uma etapa para ver as obras</span></div>
            <div class="pipe">
                @foreach ($pipe as [$key, $label, $color, $link])
                    <a href="{{ $link }}" class="pipe-seg" style="background: {{ $color }}; flex-grow: {{ max(1, $counts[$key] ?? 0) }};"><b>{{ $counts[$key] ?? 0 }}</b><span>{{ $label }}</span></a>
                @endforeach
            </div>
        </div>

        {{-- Indicadores de atenção --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3"><a href="{{ route('quality.pool') }}" class="text-decoration-none"><div class="kpi"><div class="kpi-ico"><i class="ri-stack-line"></i></div><div><div class="kpi-label">Pool elegível</div><div class="kpi-value fs-4">{{ $counts['pool'] ?? 0 }}</div><div class="kpi-sub">para despachar ao N1</div></div></div></a></div>
            <div class="col-6 col-lg-3"><div class="kpi"><div class="kpi-ico" style="background:#fee2e2;color:#b91c1c"><i class="ri-alarm-warning-line"></i></div><div><div class="kpi-label">Paradas há 5+ dias</div><div class="kpi-value fs-4">{{ $stalledTotal }}</div><div class="kpi-sub">precisam de atenção</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="kpi"><div class="kpi-ico" style="background:#fff3cd;color:#8a6d00"><i class="ri-arrow-go-back-line"></i></div><div><div class="kpi-label">Com devoluções</div><div class="kpi-value fs-4">{{ $counts['rejected'] }}</div><div class="kpi-sub">já tiveram rejeição</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="kpi"><div class="kpi-ico" style="background:#dcfce7;color:#15803d"><i class="ri-timer-flash-line"></i></div><div><div class="kpi-label">Tempo médio</div><div class="kpi-value fs-4">{{ $metrics['average_hours'] !== null ? $metrics['average_hours'] . ' h' : '—' }}</div><div class="kpi-sub">do despacho à conclusão</div></div></div></div>
            @if (($counts['pending_close'] ?? 0) > 0)
                <div class="col-12"><a href="{{ route('quality.queue', ['fila' => 'aguardando-n2']) }}" class="text-decoration-none"><div class="alert alert-warning mb-0 d-flex align-items-center gap-2"><i class="ri-error-warning-line fs-4"></i><div><strong>{{ $counts['pending_close'] }} obra(s) com encerramento pendente.</strong> A aprovação final do N2 foi registrada, mas o encerramento não foi concluído.</div></div></a></div>
            @endif
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="table-card" style="overflow: visible;">
                    <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
                        <div><div class="chart-title"><i class="ri-alarm-warning-line text-danger"></i> Paradas há mais tempo</div><div class="chart-subtitle">Obras ativas há 2+ dias na mesma etapa.</div></div>
                        <a href="{{ route('quality.queue', ['fila' => 'em-andamento']) }}" class="btn btn-sm btn-outline-primary">Ver todas em andamento</a>
                    </div>
                    @include('quality._processes-table', ['processes' => $stalled, 'ageLabel' => 'parada'])
                </div>
            </div>
            <div class="col-xl-4">
                <div class="table-card h-100">
                    <div class="p-3 pb-0"><div class="chart-title"><i class="ri-building-line"></i> Por empresa</div><div class="chart-subtitle">Onde cada empresa está travada.</div></div>
                    <div class="p-3">
                        @forelse ($byCompany as $companyId => $row)
                            @php $companyModel = $companies->firstWhere('id', $companyId); @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-baseline"><span class="fw-bold">{{ $companyModel?->name ?? 'Empresa' }}</span><span class="small text-muted">{{ $row['total'] }} em andamento @if ($row['late'])· <span class="text-danger fw-bold">{{ $row['late'] }} parada(s)</span>@endif</span></div>
                                <div class="pipe mt-1" style="height: 12px; border-radius: 8px;"><span style="flex: {{ $row['withUsers'] }}; background:#0891b2"></span><span style="flex: {{ $row['atN1'] }}; background:#f59e0b"></span><span style="flex: {{ $row['atN2'] }}; background:#4f46e5"></span></div>
                                <div class="small text-muted mt-1"><span style="color:#0891b2">●</span> {{ $row['withUsers'] }} usuários · <span style="color:#d97706">●</span> {{ $row['atN1'] }} N1 · <span style="color:#4f46e5">●</span> {{ $row['atN2'] }} N2</div>
                            </div>
                        @empty
                            <div class="ob-empty"><i class="ri-building-line"></i>Nenhuma obra em andamento.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card mb-4" style="overflow: visible;">
            <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
                <div><div class="chart-title">Movimentações recentes</div><div class="chart-subtitle">Últimas obras que andaram no fluxo.</div></div>
                <a href="{{ route('quality.history') }}" class="btn btn-sm btn-outline-primary">Histórico completo</a>
            </div>
            @include('quality._processes-table', ['processes' => $recent])
        </div>
    </div>
@endsection
