@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Relatórios</li>
                <li class="breadcrumb-item active" aria-current="page">Lista Cancelamentos</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('reports.cancellations-menu')
@endsection

@section('content')
    @livewire('reports.cancellation-list')
@endsection
