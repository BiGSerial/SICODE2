@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item">Qualidade</li><li class="breadcrumb-item"><a href="{{ route('quality.process', $process) }}">Nota {{ $process->Note?->note }}</a></li><li class="breadcrumb-item active">Checklist</li></ol>
@endsection

@section('menu') @include('quality._menu') @endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="table-card p-4 text-center">
            <div class="kpi-ico mx-auto mb-3"><i class="ri-list-check-2"></i></div>
            <h1 class="h4">Checklist ainda não iniciado</h1>
            <p class="text-muted mb-3">O checklist desta passagem será criado quando a obra retornar para a análise do N1. Neste momento ela está em <strong>{{ $process->state->label() }}</strong>.</p>
            <a class="btn btn-primary btn-sm" href="{{ route('quality.process', $process) }}"><i class="ri-arrow-left-line"></i> Voltar ao processo</a>
        </div>
    </div>
@endsection
