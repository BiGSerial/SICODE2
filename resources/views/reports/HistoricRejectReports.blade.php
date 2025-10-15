@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Informe de Obra</li>
                <li class="breadcrumb-item active" aria-current="page">Hitórico Informes Rejeitados</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('reports.menu')
@endsection

@section('content')
    @livewire('reports.historic-rejected-reports', key('rejected-Hitoric-Reports'))
@endsection
