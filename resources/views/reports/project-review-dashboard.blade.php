@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">Relatórios</li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard Análise de Projetos</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('reports.project-review-menu')
@endsection

@section('content')
    @livewire('project-review.governance-dashboard')
@endsection
