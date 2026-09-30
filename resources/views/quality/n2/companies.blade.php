@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item">N2 · Decisões</li>
        <li class="breadcrumb-item active" aria-current="page">Por empresa</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="mb-3"><h1 class="h3 mb-0 fw-bold" style="color: var(--dash-ink)">Decisões <span class="badge text-bg-warning fs-6 align-middle">N2</span></h1></div>
        @include('quality._feedback')
        @livewire('quality.stage-strip', ['level' => 'n2', 'tab' => 'empresas'], key('stage-strip'))

        <div class="wk-help mb-3"><strong>Por empresa</strong> — Onde estão as obras em andamento: com os usuários, no N1 ou no N2.</div>
        <div class="usr-grid p-0 mb-3">
            @forelse ($byCompany as $row)
                @php $total = max(1, $row['total']); @endphp
                <div class="usr-card">
                    <div class="usr-head"><div class="ob-avatar"><i class="ri-building-line"></i></div><div class="flex-grow-1"><div class="fw-bold">{{ $row['company']?->name }}</div><div class="ob-role">{{ $row['total'] }} obra(s) em andamento</div></div></div>
                    <div class="pipe mt-3" style="height: 14px; border-radius: 8px;" title="Distribuição das obras">
                        <span style="flex: {{ $row['withUsers'] }}; background: #0891b2;"></span><span style="flex: {{ $row['atN1'] }}; background: #f59e0b;"></span><span style="flex: {{ $row['atN2'] }}; background: #4f46e5;"></span>
                    </div>
                    <div class="usr-stats">
                        <div class="usr-stat"><b style="color:#0891b2">{{ $row['withUsers'] }}</b><span>Com usuários</span></div>
                        <div class="usr-stat"><b style="color:#d97706">{{ $row['atN1'] }}</b><span>No N1</span></div>
                        <div class="usr-stat"><b style="color:#4f46e5">{{ $row['atN2'] }}</b><span>No N2</span></div>
                    </div>
                </div>
            @empty
                <div class="ob-empty" style="grid-column: 1 / -1;"><i class="ri-building-line"></i>Nenhuma obra em andamento nas suas empresas.</div>
            @endforelse
        </div>
        <div class="text-muted small mt-3">{{ $completed }} obra(s) concluída(s). <a href="{{ route('quality.history') }}">Ver histórico</a></div>
    </div>
@endsection
