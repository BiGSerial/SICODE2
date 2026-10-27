@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0 no-print">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item">Home</li>
            <li class="breadcrumb-item">Relatórios</li>
            <li class="breadcrumb-item active" aria-current="page">Prazo de Projetos</li>
        </ol>
    </nav>
@endsection

@section('content')
    @livewire('reports.project-deadline-dashboard')
@endsection
