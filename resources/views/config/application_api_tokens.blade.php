@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Configuração</li>
        <li class="breadcrumb-item">Sistema</li>
        <li class="breadcrumb-item active">Tokens de API</li>
    </ol>
@endsection

@section('menu')
    @include('config.system-menu')
@endsection

@section('content')
    <div class="container-fluid mt-4">
        @livewire('config.system.application-api-tokens', key('application-api-tokens'))
    </div>
@endsection
