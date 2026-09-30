@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item">N2 · Decisões</li>
        <li class="breadcrumb-item active" aria-current="page">{{ $current['label'] }}</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    @php $firstRow = $rows->first(); @endphp
    <div class="closure-dash">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0 fw-bold" style="color: var(--dash-ink)">Decisões <span class="badge text-bg-warning fs-6 align-middle">N2</span></h1>
                <div class="small text-muted">{{ $companies->pluck('name')->implode(' · ') }}</div>
            </div>
            @if ($tab === 'decidir' && $firstRow)
                <a href="{{ route('quality.process', $firstRow) }}" class="btn btn-primary btn-dash"><i class="ri-play-circle-line"></i> Decidir a mais antiga</a>
            @endif
        </div>

        @include('quality._feedback')
        @livewire('quality.stage-strip', ['level' => 'n2', 'tab' => $tab], key('stage-strip'))

        <div class="wk-help mb-3"><strong>{{ $current['step'] }}. {{ $current['label'] }}</strong> — {{ $current['help'] }}</div>

        <div class="table-card p-3 mb-3">@include('quality._bulk-search', ['bulkScope' => $bulkScope, 'bulkNotes' => $bulkNotes, 'bulkReport' => $bulkReport])</div>

        <form method="get" class="table-card wk-toolbar p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-5"><div class="input-group input-group-sm"><span class="input-group-text"><i class="ri-search-line"></i></span><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por Nota, OV ou material"></div></div>
                @if ($companies->count() > 1)
                    <div class="col-lg-3"><select name="company_id" class="form-select form-select-sm"><option value="">Todas as empresas</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') === $company->id)>{{ $company->name }}</option>@endforeach</select></div>
                @endif
                <div class="col-lg-2"><select name="ordem" class="form-select form-select-sm"><option value="antigas" @selected(request('ordem', 'antigas') === 'antigas')>Mais antigas primeiro</option><option value="recentes" @selected(request('ordem') === 'recentes')>Mais recentes primeiro</option><option value="nota" @selected(request('ordem') === 'nota')>Por Nota</option></select></div>
                <div class="col-auto d-flex gap-2"><button class="btn btn-sm btn-primary btn-dash">Filtrar</button><a href="{{ route('quality.n2', $tab) }}" class="btn btn-sm btn-outline-secondary">Limpar</a></div>
            </div>
            @include('quality._note-filters')
        </form>

        <div class="table-card" style="overflow: visible;">
            <div class="px-3 pt-3 small text-muted"><strong>{{ $rows->total() }}</strong> obra(s) nesta etapa</div>
            <div class="ob-list">
                @forelse ($rows as $process)
                    @include('quality._obra', ['process' => $process, 'showCompany' => true, 'who' => 'n1', 'showReason' => $tab === 'com-n1', 'primary' => $current['action'], 'ageLabel' => ['decidir' => 'aguardando você', 'encerrar' => 'parado', 'com-n1' => 'com o N1'][$tab], 'action' => ['decidir' => 'Decidir', 'encerrar' => 'Resolver', 'com-n1' => 'Abrir'][$tab]])
                @empty
                    <div class="ob-empty"><i class="ri-inbox-2-line"></i><div class="fw-bold">Nada nesta etapa.</div><div class="small">{{ $tab === 'decidir' ? 'Quando o N1 aprovar uma obra, ela cai aqui para a sua decisão.' : 'Acompanhe as outras etapas acima.' }}</div></div>
                @endforelse
            </div>
            <div class="p-3">{{ $rows->links('quality._pagination') }}</div>
        </div>
    </div>
@endsection

