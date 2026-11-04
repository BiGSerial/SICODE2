@extends('layouts.padrao')

@section('menu')
    @include('closure.closure-menu')
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Encerramento</li>
        <li class="breadcrumb-item active" aria-current="page">Configurações</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.settings.dispatch-settings', key('closure-settings'))
@endsection
