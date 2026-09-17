@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Engenharia</li>
        <li class="breadcrumb-item active" aria-current="page">Notas em Cancelamento</li>
    </ol>
@endsection

@section('menu')
    @livewire('engineers.menu', key('engineers-menu'))
@endsection

@section('content')
    @livewire('engineers.cancellation-approvals.regional')
@endsection
