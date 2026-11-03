@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('legal.queue') }}">Fila de Demandas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detalhe da Demanda</li>
    </ol>
@endsection

@section('menu')
    @livewire('legal.menu', key('legal-menu'))
@endsection

@section('content')
    @livewire('legal.controller.demand-detail', ['uuid' => $uuid], key('legal-demand-detail-'.$uuid))
@endsection
