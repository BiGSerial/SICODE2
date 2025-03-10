@extends('layouts.padrao_ext')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-light px-3 py-2  my-1 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Informe de Obra</li>
                <li class="breadcrumb-item active" aria-current="page">Obras Informadas</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('content')
    @livewire('reports.workreports')
@endsection
