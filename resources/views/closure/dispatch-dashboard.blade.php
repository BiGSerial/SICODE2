@extends('layouts.padrao')

@section('menu')
    @include('closure.closure-menu')
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('closure.overview') }}">Encerramento</a></li>
        <li class="breadcrumb-item active" aria-current="page">Painel analítico</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.dispatch.dashboard', key('closure-dispatch-dashboard'))
@endsection
