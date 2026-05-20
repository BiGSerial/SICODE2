@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Jurídico</li>
        <li class="breadcrumb-item active" aria-current="page">Fila de Demandas</li>
    </ol>
@endsection

@section('menu')
    @livewire('legal.menu', ['activeSection' => 'controlador'], key('legal-menu'))
@endsection

@section('content')
    @livewire('legal.controller.demand-queue', key('legal-demand-queue'))
@endsection
