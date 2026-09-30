@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">{{ $definition['label'] }}</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">{{ $definition['label'] }}</h1>
            <div class="dash-subtitle">{{ $definition['hint'] }}</div>
        </div>

        @include('quality._feedback')

        <ul class="nav nav-pills mb-3 flex-wrap gap-1">
            @foreach ($definitions as $queueKey => $queueDef)
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 {{ $queueKey === $key ? 'active' : '' }}" href="{{ route('quality.queue', ['fila' => $queueKey]) }}">
                        {{ $queueDef['label'] }} <span class="badge {{ $queueKey === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ $queues[$queueKey] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="get" class="table-card p-3 mb-3">
            <input type="hidden" name="fila" value="{{ $key }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small mb-1">Nota ou OV</label><input name="note" value="{{ request('note') }}" class="form-control form-control-sm" placeholder="Pesquisar"></div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Empresa</label>
                    <select name="company_id" class="form-select form-select-sm"><option value="">Todas</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>@endforeach</select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-sm btn-primary btn-dash"><i class="ri-search-line"></i> Filtrar</button></div>
            </div>
            @include('quality._note-filters')
        </form>

        <div class="table-card">
            @include('quality._processes-table', ['processes' => $processes])
            <div class="p-3">{{ $processes->links('quality._pagination') }}</div>
        </div>
    </div>
@endsection
