@extends('layouts.padrao')

@section('menu')
    @include('services.encerramento.menu')
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">{{ $service->service }}</li>
        <li class="breadcrumb-item active" aria-current="page">Transferências</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.transfers.center', key('closure-transfers'))
@endsection
