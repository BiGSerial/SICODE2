@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">Histórico</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">Histórico de processos</h1>
            <div class="dash-subtitle">Todos os processos do seu escopo. Abra um processo para ver cada rodada, rejeição, arquivo e evento.</div>
        </div>

        @include('quality._feedback')

        <form method="get" class="table-card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small mb-1">Nota ou OV</label><input name="note" value="{{ request('note') }}" class="form-control form-control-sm" placeholder="Pesquisar"></div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Empresa</label>
                    <select name="company_id" class="form-select form-select-sm"><option value="">Todas</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>@endforeach</select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Situação</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach (\App\Enum\QualityProcessStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Ciclo</label>
                    <select name="phase" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach (\App\Enum\QualityStageType::cases() as $phase)<option value="{{ $phase->value }}" @selected(request('phase') === $phase->value)>{{ $phase->short() }}</option>@endforeach
                    </select>
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
