@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('legal.subdemand.monitor') }}">Monitor de Subdemandas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detalhe da Subdemanda</li>
    </ol>
@endsection

@section('menu')
    @livewire('legal.menu', ['activeSection' => 'controlador'], key('legal-menu'))
@endsection

@section('content')
    @livewire('legal.controller.subdemand-detail', ['uuid' => $uuid], key('legal-subdemand-detail-'.$uuid))
@endsection

