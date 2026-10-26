@extends('layouts.padrao')

@section('menu')
    @include('closure.closure-menu')
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Encerramento</li>
        <li class="breadcrumb-item active" aria-current="page">Mapa de status por serviço</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.settings.status-user-map', key('closure-status-map'))
@endsection
